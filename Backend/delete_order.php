<?php
// delete_order.php — Handles POST request to remove a served order from DB

require_once '../includes/auth.php';
auth_require(['admin']);
require_once '../includes/db.php';//Connects to database

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: orders.php');
    exit;
}

// Verify CSRF token
csrf_verify();
//Checks if the request method is not POST & Exits the script if the request method is not POST

$id = intval($_POST['order_id'] ?? 0);
//Gets the order id from the POST request & converts it to an integer

if ($id <= 0) {
    header('Location: orders.php?del=error&msg=' . urlencode('Invalid order ID.'));
    exit;
}//Checks for Errors

try {
    $check = $pdo->prepare("SELECT id, customer_name FROM orders WHERE id = :id");
    $check->execute([':id' => $id]);
    $row = $check->fetch();//if $check cant fetch the id it got from above it will give $row false which will trigger the error option below

    if (!$row) {
        header('Location: orders.php?del=error&msg=' . urlencode('Order not found.'));
        exit;
    }//Error Option

    $stmt = $pdo->prepare("DELETE FROM orders WHERE id = :id");
    $stmt->execute([':id' => $id]);//executes the delete query

    $name = htmlspecialchars($row['customer_name']);
    header('Location: orders.php?del=success&msg=' . urlencode("Order #{$id} for {$name} marked as served and removed."));//Redirects to orders.php with the success message
} catch (PDOException $e) {
    error_log('[delete_order] DB error: ' . $e->getMessage());
    header('Location: orders.php?del=error&msg=' . urlencode('A database error occurred. Please try again.'));
}//Error Handling
exit;
?>