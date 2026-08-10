<?php
/**
 * api/tables_status.php — PATCH /api/tables_status.php?id=:id
 * Body (JSON): { "status": "FREE" | "RESERVED" }
 * Returns the updated table row as JSON.
 */
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../includes/db.php';

// Only allow GET (pre-flight) or POST/PATCH
$method = $_SERVER['REQUEST_METHOD'];
if (!in_array($method, ['POST', 'PATCH', 'GET'], true)) {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// For GET with action=status just redirect to tables.php (browser-friendly)
if ($method === 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Use POST or PATCH']);
    exit;
}

// Parse ID
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing or invalid id']);
    exit;
}

// Parse body
$raw  = file_get_contents('php://input');
$body = json_decode($raw, true);

// Also support form-encoded (for admin toggle)
if (empty($body)) {
    $body = $_POST;
}

$new_status = strtoupper(trim($body['status'] ?? ''));
if (!in_array($new_status, ['FREE', 'RESERVED'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'status must be FREE or RESERVED']);
    exit;
}

try {
    $stmt = $pdo->prepare(
        "UPDATE `tables` SET status = :status WHERE id = :id"
    );
    $stmt->execute([':status' => $new_status, ':id' => $id]);

    if ($stmt->rowCount() === 0) {
        // Row might not exist
        $exists = $pdo->prepare("SELECT id FROM `tables` WHERE id = :id");
        $exists->execute([':id' => $id]);
        if (!$exists->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Table not found']);
            exit;
        }
    }

    // Return updated row
    $row = $pdo->prepare(
        "SELECT id, name, zone, capacity, status, x, y FROM `tables` WHERE id = :id"
    );
    $row->execute([':id' => $id]);
    $table = $row->fetch();

    echo json_encode(['success' => true, 'data' => $table]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
