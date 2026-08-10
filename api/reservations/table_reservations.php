<?php
/**
 * api/reservations/table_reservations.php — GET /api/reservations/table_reservations.php?table_id=X&date=YYYY-MM-DD
 * Returns all reservations for a specific table (optionally filtered by date).
 */
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../../includes/db.php';
if (!isset($pdo) || !$pdo) { $pdo = $GLOBALS['pdo'] ?? null; }

$table_id = isset($_GET['table_id']) ? (int)$_GET['table_id'] : 0;
$date     = isset($_GET['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date']) ? $_GET['date'] : null;

if (!$table_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'table_id parameter is required.']);
    exit;
}

try {
    // 1. Auto-release unconfirmed expired reservations
    $pdo->exec("
        UPDATE `reservations`
        SET status = 'EXPIRED'
        WHERE status = 'CONFIRMED'
          AND TIMESTAMP(res_date, start_time) + INTERVAL 15 MINUTE < NOW()
    ");

    // 2. Fetch table details
    $tableStmt = $pdo->prepare("SELECT id, name, zone, capacity, status FROM `tables` WHERE id = :id");
    $tableStmt->execute([':id' => $table_id]);
    $table = $tableStmt->fetch();

    if (!$table) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Table not found.']);
        exit;
    }

    // 3. Fetch CONFIRMED reservations for this table (excluding CANCELLED)
    if ($date) {
        $stmt = $pdo->prepare("
            SELECT r.*, t.name AS table_name, t.zone
            FROM `reservations` r
            JOIN `tables` t ON r.table_id = t.id
            WHERE r.table_id = :table_id
              AND r.res_date = :res_date
              AND r.status = 'CONFIRMED'
            ORDER BY r.start_time ASC
        ");
        $stmt->execute([':table_id' => $table_id, ':res_date' => $date]);
    } else {
        $stmt = $pdo->prepare("
            SELECT r.*, t.name AS table_name, t.zone
            FROM `reservations` r
            JOIN `tables` t ON r.table_id = t.id
            WHERE r.table_id = :table_id
              AND r.status = 'CONFIRMED'
            ORDER BY r.res_date DESC, r.start_time ASC
            LIMIT 50
        ");
        $stmt->execute([':table_id' => $table_id]);
    }

    $rows = $stmt->fetchAll();
    $now  = date('H:i:s');
    $today= date('Y-m-d');

    foreach ($rows as &$row) {
        $row['duration_hours'] = (float)$row['duration_hours'];
        $row['guests']         = (int)$row['guests'];
        $row['start_fmt']      = date('h:i A', strtotime($row['start_time']));
        $row['end_fmt']        = date('h:i A', strtotime($row['end_time']));
        $row['is_current']     = ($row['res_date'] === $today && $row['start_time'] <= $now && $row['end_time'] >= $now && $row['status'] === 'CONFIRMED');
    }
    unset($row);

    echo json_encode([
        'success'      => true,
        'table'        => [
            'id'       => (int)$table['id'],
            'name'     => $table['name'],
            'zone'     => $table['zone'],
            'capacity' => (int)$table['capacity'],
            'status'   => $table['status']
        ],
        'filter_date'  => $date,
        'count'        => count($rows),
        'data'         => $rows
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error when fetching table reservations.']);
}
