<?php
/**
 * api/reservations/cancel.php — POST /api/reservations/cancel.php
 * Validates cancellation token & email, updates reservation status to CANCELLED, freeing table slot.
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

$token = trim($body['cancellation_token'] ?? '');
$email = filter_var(trim($body['customer_email'] ?? ''), FILTER_VALIDATE_EMAIL);

if (!$token) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Cancellation token is required.']);
    exit;
}

try {
    // Locate reservation
    if ($email) {
        $stmt = $pdo->prepare("
            SELECT r.*, t.name AS table_name, t.zone 
            FROM `reservations` r
            JOIN `tables` t ON r.table_id = t.id
            WHERE r.cancellation_token = :token AND LOWER(r.customer_email) = LOWER(:email)
        ");
        $stmt->execute([':token' => $token, ':email' => $email]);
    } else {
        $stmt = $pdo->prepare("
            SELECT r.*, t.name AS table_name, t.zone 
            FROM `reservations` r
            JOIN `tables` t ON r.table_id = t.id
            WHERE r.cancellation_token = :token
        ");
        $stmt->execute([':token' => $token]);
    }

    $reservation = $stmt->fetch();

    if (!$reservation) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Invalid cancellation token or email mismatch.']);
        exit;
    }

    if ($reservation['status'] === 'CANCELLED') {
        echo json_encode([
            'success' => true,
            'already_cancelled' => true,
            'message' => "Reservation for Table {$reservation['table_name']} is already cancelled.",
            'data'    => $reservation
        ]);
        exit;
    }

    // Cancel reservation
    $updateStmt = $pdo->prepare("UPDATE `reservations` SET status = 'CANCELLED' WHERE id = :id");
    $updateStmt->execute([':id' => $reservation['id']]);

    echo json_encode([
        'success' => true,
        'message' => "Reservation for Table {$reservation['table_name']} on {$reservation['res_date']} has been cancelled.",
        'data'    => [
            'reservation_id' => $reservation['id'],
            'table_name'     => $reservation['table_name'],
            'res_date'       => $reservation['res_date'],
            'customer_name'  => $reservation['customer_name'],
            'status'         => 'CANCELLED'
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error during reservation cancellation.']);
}
