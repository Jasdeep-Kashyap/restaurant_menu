<?php
// delete_order.php — Handles POST request to remove a served order from DB

require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: orders.php');
    exit;
}

$id = intval($_POST['order_id'] ?? 0);

if ($id <= 0) {
    header('Location: orders.php?del=error&msg=' . urlencode('Invalid order ID.'));
    exit;
}

try {
    // Verify the order exists first
    $check = $pdo->prepare("SELECT id, customer_name FROM orders WHERE id = :id");
    $check->execute([':id' => $id]);
    $row = $check->fetch();

    if (!$row) {
        header('Location: orders.php?del=error&msg=' . urlencode('Order not found.'));
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM orders WHERE id = :id");
    $stmt->execute([':id' => $id]);

    $name = htmlspecialchars($row['customer_name']);
    header('Location: orders.php?del=success&msg=' . urlencode("Order #{$id} for {$name} marked as served and removed."));
} catch (PDOException $e) {
    header('Location: orders.php?del=error&msg=' . urlencode('Database error: ' . $e->getMessage()));
}
exit;
?>
