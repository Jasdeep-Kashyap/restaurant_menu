<?php
// cashfree_return.php — Handles return redirect from Cashfree PG
session_start();
require_once '../includes/db.php';
require_once '../includes/cashfree_config.php';

$cf_order_id = trim($_GET['order_id'] ?? $_GET['cf_order_id'] ?? '');

if ($cf_order_id === '') {
    header('Location: menu.php?status=error&msg=' . urlencode('Invalid Cashfree return response. Missing Order ID.'));
    exit;
}

// Retrieve pending order details from session
$pending_key = 'pending_order_' . $cf_order_id;
$pending_data = $_SESSION[$pending_key] ?? null;

if (!$pending_data) {
    header('Location: menu.php?status=error&msg=' . urlencode('Pending order session expired. Please try placing your order again.'));
    exit;
}

$customer_name  = $pending_data['customer_name'];
$items_json     = $pending_data['items_json'];
$total_price    = $pending_data['total_price'];
$scheduled_date = $pending_data['scheduled_date'];
$scheduled_time = $pending_data['scheduled_time'];

// Verify payment status with Cashfree API
// ✅ FIXED (CRIT-2): Default to NOT paid — must be explicitly confirmed by API
$is_paid = false;
$utr_number = 'CF_' . strtoupper(substr(md5($cf_order_id), 0, 10));

$status_res = cashfree_get_order_status($cf_order_id);
if ($status_res['success']) {
    $order_status = strtoupper($status_res['order_status']);
    if ($order_status === 'PAID') {
        $is_paid = true;
    }
} else {
    // API call failed — log it and deny order to prevent bypass
    error_log('[cashfree_return] Status check failed for order ' . $cf_order_id . ': ' . ($status_res['error'] ?? 'unknown'));
}

if (!$is_paid) {
    header('Location: menu.php?status=error&msg=' . urlencode('Cashfree payment was not completed or failed. Status: ' . ($order_status ?? 'UNPAID')));
    exit;
}

// Parse scheduled datetime
$scheduled_at = null;
if ($scheduled_date !== '' && $scheduled_time !== '') {
    try {
        $dt = new DateTime($scheduled_date . ' ' . $scheduled_time);
        $scheduled_at = $dt->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        $scheduled_at = null;
    }
}

/* ── Save order to DB with cycling Order ID ──────────────── */
try {
    $pdo->beginTransaction();

    // Lock & read counter
    $counter_row = $pdo->query("SELECT next_number FROM order_counter WHERE id = 1 FOR UPDATE")->fetch();
    $order_number = (int) ($counter_row['next_number'] ?? 1);

    // Insert order into database
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

    // Increment counter (1-500)
    $next = ($order_number >= 500) ? 1 : $order_number + 1;
    $pdo->prepare("UPDATE order_counter SET next_number = :next WHERE id = 1")
        ->execute([':next' => $next]);

    $pdo->commit();

    // Cleanup session
    unset($_SESSION[$pending_key]);
    $_SESSION['cart'] = [];

    // Success redirect
    $msg = "Order #{$order_number} placed successfully via Cashfree Gateway! 🎉";
    if ($scheduled_at) {
        $msg .= " Scheduled for " . (new DateTime($scheduled_at))->format('M j, g:i A') . ".";
    }
    header('Location: menu.php?status=success&msg=' . urlencode($msg) . '&order_id=' . $order_number);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[cashfree_return] DB error: ' . $e->getMessage());
    header('Location: menu.php?status=error&msg=' . urlencode('A database error occurred while saving your order. Please contact us.'));
}
exit;
