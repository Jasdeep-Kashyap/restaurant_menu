<?php
// create_cashfree_order.php — Ajax endpoint to initiate Cashfree payment
session_start();
header('Content-Type: application/json');

require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/cashfree_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$customer_name  = trim($input['customer_name'] ?? '');
$customer_email = trim($input['customer_email'] ?? 'testcustomer@example.com');
$customer_phone = trim($input['customer_phone'] ?? '9999999999');
$total_price    = floatval($input['total_price'] ?? 0);
$items_json     = trim($input['items_json'] ?? '');

if ($customer_name === '' || $total_price <= 0) {
    echo json_encode(['success' => false, 'error' => 'Please provide customer name and valid cart total.']);
    exit;
}

// Generate unique order ID
$cf_order_id = 'ORDER_' . time() . '_' . rand(1000, 9999);

// Construct return URL
$protocol   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host       = $_SERVER['HTTP_HOST'] ?? 'localhost';
$return_url = $protocol . '://' . $host . dirname($_SERVER['SCRIPT_NAME']) . '/cashfree_return.php';

// Store pending order details in session
$_SESSION['pending_order_' . $cf_order_id] = [
    'customer_name'  => $customer_name,
    'items_json'     => $items_json,
    'total_price'    => $total_price,
    'scheduled_date' => $input['scheduled_date'] ?? '',
    'scheduled_time' => $input['scheduled_time'] ?? '',
];

// Call Cashfree API to create order
$result = cashfree_create_order([
    'order_id'       => $cf_order_id,
    'amount'         => $total_price,
    'customer_name'  => $customer_name,
    'customer_email' => $customer_email,
    'customer_phone' => $customer_phone,
    'return_url'     => $return_url,
]);

if ($result['success']) {
    echo json_encode([
        'success'            => true,
        'payment_session_id' => $result['payment_session_id'],
        'cf_order_id'        => $result['cf_order_id'],
        'environment'        => CF_ENV,
    ]);
} else {
    error_log('[create_cashfree_order] Order creation failed: ' . ($result['error'] ?? 'unknown'));
    echo json_encode([
        'success' => false,
        'error'   => $result['error'] ?? 'Payment gateway error. Please try again.',
    ]);
}
exit;
