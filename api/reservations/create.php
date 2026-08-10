<?php
/**
 * api/reservations/create.php — POST /api/reservations/create.php
 * Creates a time-bound table reservation with guest credentials & cancellation token.
 */
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed. Use POST.']);
    exit;
}

$raw  = file_get_contents('php://input');
$body = json_decode($raw, true) ?? $_POST;

$table_id       = isset($body['table_id']) ? (int)$body['table_id'] : 0;
$customer_name  = trim($body['customer_name'] ?? '');
$customer_email = filter_var(trim($body['customer_email'] ?? ''), FILTER_VALIDATE_EMAIL);
$customer_phone = trim($body['customer_phone'] ?? '');
$res_date       = trim($body['res_date'] ?? '');
$start_time     = trim($body['start_time'] ?? ''); // HH:MM
$duration_hours = isset($body['duration_hours']) ? max(1, min(4, (int)$body['duration_hours'])) : 2;
$guests         = isset($body['guests']) ? max(1, (int)$body['guests']) : 2;

if (!$table_id || !$customer_name || !$customer_email || !$customer_phone || !$res_date || !$start_time) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'All fields (name, valid email, phone, date, time) are required.']);
    exit;
}

// Format start_time to HH:MM:SS
if (strlen($start_time) === 5) {
    $start_time .= ':00';
}

// Calculate end_time (ETA + duration_hours)
$startTimestamp = strtotime("$res_date $start_time");
if (!$startTimestamp) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid date or time format.']);
    exit;
}

$endTimestamp   = $startTimestamp + ($duration_hours * 3600);
$end_time       = date('H:i:s', $endTimestamp);

// Grace period timestamp (ETA + 15 minutes)
$graceTimestamp = $startTimestamp + (15 * 60);
$grace_deadline = date('H:i', $graceTimestamp);
$deadline_time  = date('H:i', $endTimestamp);

try {
    // 1. Auto-release expired reservations (ETA + 15 min passed and no check-in)
    $pdo->exec("
        UPDATE `reservations`
        SET status = 'EXPIRED'
        WHERE status = 'CONFIRMED'
          AND TIMESTAMP(res_date, start_time) + INTERVAL 15 MINUTE < NOW()
    ");

    // 2. Check if table exists
    $tableStmt = $pdo->prepare("SELECT id, name, zone, capacity, status FROM `tables` WHERE id = :id");
    $tableStmt->execute([':id' => $table_id]);
    $table = $tableStmt->fetch();

    if (!$table) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Selected table not found.']);
        exit;
    }

    if ($table['capacity'] < $guests) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => "Table {$table['name']} only seats up to {$table['capacity']} guests."]);
        exit;
    }

    // 3. Check slot overlap
    $overlapStmt = $pdo->prepare("
        SELECT id FROM `reservations`
        WHERE table_id = :table_id
          AND res_date = :res_date
          AND status = 'CONFIRMED'
          AND start_time < :end_time
          AND end_time > :start_time
        LIMIT 1
    ");
    $overlapStmt->execute([
        ':table_id'   => $table_id,
        ':res_date'   => $res_date,
        ':start_time' => $start_time,
        ':end_time'   => $end_time
    ]);

    if ($overlapStmt->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'error' => "Table {$table['name']} is already booked for the selected time slot."]);
        exit;
    }

    // 4. Create reservation with cancellation token
    $cancellation_token = bin2hex(random_bytes(16));

    $insertStmt = $pdo->prepare("
        INSERT INTO `reservations` 
        (table_id, customer_name, customer_email, customer_phone, res_date, start_time, end_time, duration_hours, guests, status, cancellation_token)
        VALUES 
        (:table_id, :customer_name, :customer_email, :customer_phone, :res_date, :start_time, :end_time, :duration_hours, :guests, 'CONFIRMED', :token)
    ");

    $insertStmt->execute([
        ':table_id'       => $table_id,
        ':customer_name'  => $customer_name,
        ':customer_email' => $customer_email,
        ':customer_phone' => $customer_phone,
        ':res_date'       => $res_date,
        ':start_time'     => $start_time,
        ':end_time'       => $end_time,
        ':duration_hours' => $duration_hours,
        ':guests'         => $guests,
        ':token'          => $cancellation_token
    ]);

    $reservation_id = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Reservation confirmed successfully!',
        'data'    => [
            'reservation_id'     => $reservation_id,
            'table_name'         => $table['name'],
            'zone'               => $table['zone'],
            'customer_name'      => $customer_name,
            'customer_email'     => $customer_email,
            'customer_phone'     => $customer_phone,
            'res_date'           => $res_date,
            'eta_time'           => substr($start_time, 0, 5),
            'end_time'           => $deadline_time,
            'duration_hours'     => $duration_hours,
            'grace_deadline'     => $grace_deadline,
            'cancellation_token' => $cancellation_token
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error during reservation processing.']);
}
