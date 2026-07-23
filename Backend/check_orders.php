<?php
// check_orders.php - API endpoint to get the latest order state
require_once '../includes/db.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT MAX(id) as max_id, COUNT(id) as total_count FROM orders");
    $result = $stmt->fetch();
    
    echo json_encode([
        'max_id' => (int)$result['max_id'],
        'count' => (int)$result['total_count']
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?>
