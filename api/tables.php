<?php
/**
 * api/tables.php — GET /api/tables.php
 * Returns table floor plan layout with dynamic slot availability based on requested date, ETA, and duration.
 */
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../includes/db.php';

// Parse query params
$date     = trim($_GET['date'] ?? date('Y-m-d'));
$time     = trim($_GET['time'] ?? '19:00');
$duration = isset($_GET['duration']) ? max(1, min(4, (int)$_GET['duration'])) : 2;

if (strlen($time) === 5) {
    $time .= ':00';
}

$startTimestamp = strtotime("$date $time");
if (!$startTimestamp) {
    $date = date('Y-m-d');
    $time = '19:00:00';
    $startTimestamp = strtotime("$date $time");
}
$endTimestamp = $startTimestamp + ($duration * 3600);
$end_time     = date('H:i:s', $endTimestamp);

try {
    // 1. Auto-release unconfirmed expired reservations (ETA + 15 min passed)
    $pdo->exec("
        UPDATE `reservations`
        SET status = 'EXPIRED'
        WHERE status = 'CONFIRMED'
          AND TIMESTAMP(res_date, start_time) + INTERVAL 15 MINUTE < NOW()
    ");

    // 2. Fetch all tables
    $tables = $pdo->query("SELECT id, name, zone, capacity, status AS base_status, x, y FROM `tables` ORDER BY zone, id")->fetchAll();

    // 3. Fetch overlapping CONFIRMED reservations for target slot
    $resStmt = $pdo->prepare("
        SELECT table_id, start_time, end_time, customer_name, duration_hours
        FROM `reservations`
        WHERE res_date = :res_date
          AND status = 'CONFIRMED'
          AND start_time < :end_time
          AND end_time > :start_time
    ");
    $resStmt->execute([
        ':res_date'   => $date,
        ':start_time' => $time,
        ':end_time'   => $end_time
    ]);
    $activeReservations = $resStmt->fetchAll();

    $reservedTableIds = array_flip(array_column($activeReservations, 'table_id'));

    // 4. Calculate dynamic status per table for target slot
    $result = [];
    foreach ($tables as $t) {
        $isReserved = isset($reservedTableIds[$t['id']]) || $t['base_status'] === 'RESERVED';
        $result[] = [
            'id'          => (int)$t['id'],
            'name'        => $t['name'],
            'zone'        => $t['zone'],
            'capacity'    => (int)$t['capacity'],
            'status'      => $isReserved ? 'RESERVED' : 'FREE',
            'base_status' => $t['base_status'],
            'x'           => (int)$t['x'],
            'y'           => (int)$t['y']
        ];
    }

    echo json_encode([
        'success' => true,
        'slot' => [
            'date'           => $date,
            'start_time'     => substr($time, 0, 5),
            'end_time'       => date('H:i', $endTimestamp),
            'duration_hours' => $duration
        ],
        'data' => $result
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error when calculating table availability.']);
}
