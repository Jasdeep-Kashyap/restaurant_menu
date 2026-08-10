<?php
/**
 * Backend/check_updates.php
 * Polled every 5 seconds by table_admin.php to see if the page needs a reload.
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/db.php';

try {
    // Get max reservation ID and total confirmed count
    $resStats = $pdo->query("SELECT MAX(id) as max_id, COUNT(id) as cnt FROM `reservations` WHERE status = 'CONFIRMED'")->fetch();
    
    // Hash the table statuses so we know if an admin toggled one
    // Using GROUP_CONCAT is simpler
    $tableHash = md5($pdo->query("SELECT GROUP_CONCAT(status ORDER BY id) FROM `tables`")->fetchColumn());
    
    echo json_encode([
        'success' => true,
        'max_res_id' => (int)($resStats['max_id'] ?? 0),
        'res_count'  => (int)($resStats['cnt'] ?? 0),
        'table_hash' => $tableHash
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false]);
}
