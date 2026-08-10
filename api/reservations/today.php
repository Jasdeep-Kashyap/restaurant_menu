<?php
/**
 * api/reservations/today.php — GET /api/reservations/today.php
 * Returns today's CONFIRMED reservations for the live bookings ticker.
 */
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../../includes/db.php';

try {
    // Auto-release expired
    $pdo->exec("
        UPDATE `reservations`
        SET status = 'EXPIRED'
        WHERE status = 'CONFIRMED'
          AND TIMESTAMP(res_date, start_time) + INTERVAL 15 MINUTE < NOW()
    ");

    $today = date('Y-m-d');
    $now   = date('H:i:s');

    $stmt = $pdo->prepare("
        SELECT r.id, r.customer_name, r.res_date, r.start_time, r.end_time,
               r.guests, r.status, r.duration_hours, t.name AS table_name, t.zone
        FROM `reservations` r
        JOIN `tables` t ON r.table_id = t.id
        WHERE r.res_date = :today
          AND r.status = 'CONFIRMED'
        ORDER BY r.start_time ASC
    ");
    $stmt->execute([':today' => $today]);
    $rows = $stmt->fetchAll();

    // Format times nicely
    foreach ($rows as &$row) {
        $row['start_fmt']    = date('h:i A', strtotime($row['start_time']));
        $row['end_fmt']      = date('h:i A', strtotime($row['end_time']));
        $row['is_current']   = ($row['start_time'] <= $now && $row['end_time'] >= $now);
        $row['is_upcoming']  = $row['start_time'] > $now;
    }
    unset($row);

    echo json_encode([
        'success' => true,
        'date'    => $today,
        'count'   => count($rows),
        'data'    => $rows
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error.']);
}
