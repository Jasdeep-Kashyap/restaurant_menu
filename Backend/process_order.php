<?php
// process_order.php — Handles POST from the checkout flow
// Validates UPI payment confirmation, scheduling, and assigns a cycling Order ID (1–500).

session_start();
require_once '../includes/auth.php';
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: menu.php');
    exit;
}

// Verify CSRF token on every checkout submission
csrf_verify();

/* ── Safe Auto-migrate schema ── */
try {
    // 1. Create order_counter table if it does not exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS order_counter (
            id          TINYINT UNSIGNED PRIMARY KEY DEFAULT 1,
            next_number SMALLINT UNSIGNED NOT NULL DEFAULT 1
        )
    ");
    $pdo->exec("INSERT IGNORE INTO order_counter (id, next_number) VALUES (1, 1)");

    // 2. Add columns to orders table individually if missing (compatible with all MySQL versions)
    $existing_cols = [];
    $stmt = $pdo->query("SHOW COLUMNS FROM orders");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existing_cols[] = strtolower($row['Field']);
    }

    if (!in_array('order_number', $existing_cols)) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN order_number SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER id");
    }
    if (!in_array('scheduled_at', $existing_cols)) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN scheduled_at DATETIME DEFAULT NULL AFTER order_time");
    }
    if (!in_array('utr_number', $existing_cols)) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN utr_number VARCHAR(50) DEFAULT NULL AFTER total_price");
    }
} catch (PDOException $e) {
    // Safe failsafe
}

/* ── Sanitise & validate ─────────────────────────────────── */
$customer_name      = trim($_POST['customer_name'] ?? '');
$items_json         = trim($_POST['items_json']    ?? '');
$payment_confirmed  = ($_POST['payment_confirmed'] ?? '') === '1';
$utr_raw            = trim($_POST['utr_number'] ?? '');
$utr_number         = strtoupper(preg_replace('/[^0-9A-Z]/i', '', $utr_raw));
$scheduled_date     = trim($_POST['scheduled_date'] ?? '');
$scheduled_time     = trim($_POST['scheduled_time'] ?? '');

// ✅ Server-side price recalculation from session cart (MED-2 fix)
// Never trust client-supplied total_price.
$cart        = $_SESSION['cart'] ?? [];
$total_price = 0.0;
foreach ($cart as $ci) {
    $total_price += floatval($ci['price']) * intval($ci['qty']);
}
$total_price = round($total_price, 2);

// Basic field validation
if ($customer_name === '' || $items_json === '' || $total_price <= 0) {
    header('Location: menu.php?status=error&msg=' . urlencode('Please fill all fields and add at least one item.'));
    exit;
}

// Decode to verify it's valid JSON array
$items_array = json_decode($items_json, true);
if (!is_array($items_array) || count($items_array) === 0) {
    header('Location: menu.php?status=error&msg=' . urlencode('No items found in your order.'));
    exit;
}

// Payment & UTR Verification
if (!$payment_confirmed || $utr_number === '') {
    header('Location: menu.php?status=error&msg=' . urlencode('Payment was not verified. Please provide a valid 12-digit UTR/Ref number.'));
    exit;
}

// Validate UTR format (12 digits numeric or standard UPI reference format)
if (!preg_match('/^[0-9A-Z]{8,16}$/i', $utr_number)) {
    header('Location: menu.php?status=error&msg=' . urlencode('Invalid UTR/Transaction Reference ID. It must be 8-16 alphanumeric characters.'));
    exit;
}

// Parse scheduled datetime (optional)
$scheduled_at = null;
if ($scheduled_date !== '' && $scheduled_time !== '') {
    $dt_string = $scheduled_date . ' ' . $scheduled_time;
    try {
        $dt = new DateTime($dt_string);
        $now = new DateTime();
        if ($dt <= $now) {
            header('Location: menu.php?status=error&msg=' . urlencode('Scheduled time must be in the future.'));
            exit;
        }
        $scheduled_at = $dt->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        header('Location: menu.php?status=error&msg=' . urlencode('Invalid scheduled date or time format.'));
        exit;
    }
} elseif ($scheduled_date !== '' || $scheduled_time !== '') {
    // One is set but not the other
    header('Location: menu.php?status=error&msg=' . urlencode('Please provide both date and time for scheduling.'));
    exit;
}

/* ── Save to database with cycling Order ID ──────────────── */
try {
    $pdo->beginTransaction();

    // Lock & read the counter
    $counter_row = $pdo->query("SELECT next_number FROM order_counter WHERE id = 1 FOR UPDATE")->fetch();
    $order_number = (int) ($counter_row['next_number'] ?? 1);

    // Insert the order
    $stmt = $pdo->prepare(
        "INSERT INTO orders (order_number, customer_name, items, total_price, utr_number, scheduled_at)
         VALUES (:order_number, :name, :items, :total, :utr, :scheduled)"
    );
    $stmt->execute([
        ':order_number' => $order_number,
        ':name'         => htmlspecialchars($customer_name, ENT_QUOTES, 'UTF-8'),
        ':items'        => $items_json,
        ':total'        => $total_price,
        ':utr'          => htmlspecialchars($utr_number, ENT_QUOTES, 'UTF-8'),
        ':scheduled'    => $scheduled_at,
    ]);

    // Increment counter, reset to 1 after 500
    $next = ($order_number >= 500) ? 1 : $order_number + 1;
    $pdo->prepare("UPDATE order_counter SET next_number = :next WHERE id = 1")
        ->execute([':next' => $next]);

    $pdo->commit();

    // Clear cart after successful order
    $_SESSION['cart'] = [];

    // Build success redirect with order number
    $msg = "Order #{$order_number} placed for {$customer_name}! 🎉";
    if ($scheduled_at) {
        $dt_display = (new DateTime($scheduled_at))->format('M j, g:i A');
        $msg .= " Scheduled for {$dt_display}.";
    } else {
        $msg .= " We'll be right with you.";
    }

    header('Location: menu.php?status=success&msg=' . urlencode($msg) . '&order_id=' . $order_number);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[process_order] DB error: ' . $e->getMessage());
    header('Location: menu.php?status=error&msg=' . urlencode('A database error occurred. Please try again.'));
}

exit;
?>
