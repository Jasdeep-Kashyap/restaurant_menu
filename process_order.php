<?php
// process_order.php — Handles POST from the order form
// Validates input, saves to DB, returns to menu with result flag.

session_start();
require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: menu.php');
    exit;
}

/* ── Sanitise & validate ─────────────────────────────────── */
$customer_name = trim($_POST['customer_name'] ?? '');
$items_json    = trim($_POST['items_json']    ?? '');
$total_price   = floatval($_POST['total_price'] ?? 0);

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

/* ── Save to database ────────────────────────────────────── */
try {
    $stmt = $pdo->prepare(
        "INSERT INTO orders (customer_name, items, total_price) VALUES (:name, :items, :total)"
    );
    $stmt->execute([
        ':name'  => htmlspecialchars($customer_name, ENT_QUOTES, 'UTF-8'),
        ':items' => $items_json,
        ':total' => $total_price,
    ]);

    // Clear cart after successful order
    $_SESSION['cart'] = [];

    header('Location: menu.php?status=success&msg=' . urlencode("Order placed for $customer_name! 🎉 We'll be right with you."));
} catch (PDOException $e) {
    header('Location: menu.php?status=error&msg=' . urlencode('Database error: ' . $e->getMessage()));
}

exit;
?>
