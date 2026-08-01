<?php
// check_orders.php - API endpoint to get the latest order state

require_once '../includes/db.php';//Connects to database

header('Content-Type: application/json');//Sends data to frontend in specifiedd data type  

try {
    $stmt = $pdo->query("SELECT MAX(id) as max_id, COUNT(id) as total_count FROM orders");//gets the latest order id and total count of orders 
    $result = $stmt->fetch();//fetches the result

    echo json_encode([
        'max_id' => (int) $result['max_id'],
        'count' => (int) $result['total_count']
    ]);//converts the data to json format
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}//Catches any errors and sends them to the frontend in json format
?>