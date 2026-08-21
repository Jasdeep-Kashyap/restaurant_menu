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
$customer_email = trim($input['customer_email'] ?? 'customer@obsidian.com');
$phone_raw      = trim($input['customer_phone'] ?? '');
$phone_digits   = preg_replace('/[^0-9]/', '', $phone_raw);
$customer_phone = (strlen($phone_digits) === 10 && in_array($phone_digits[0], ['6', '7', '8', '9'])) ? $phone_digits : '9876543210';

if ($customer_name === '') {
    echo json_encode(['success' => false, 'error' => 'Please provide your name before proceeding to payment.']);
    exit;
}

// Server-side authoritative cart calculation from session
$cart        = $_SESSION['cart'] ?? [];
$total_price = 0.0;
$items_list  = [];

foreach ($cart as $ci) {
    $qty   = intval($ci['qty'] ?? 1);
    $price = floatval($ci['price'] ?? 0);
    $total_price += $price * $qty;
    $items_list[] = ($ci['name'] ?? 'Item') . ' (×' . $qty . ')';
}
$total_price = round($total_price, 2);

// Fallback to client total if session cart is somehow empty but client sent valid data
if ($total_price <= 0 && !empty($input['total_price']) && floatval($input['total_price']) > 0) {
    $total_price = floatval($input['total_price']);
}

$items_json = !empty($items_list) ? json_encode($items_list) : trim($input['items_json'] ?? '');

if ($total_price <= 0 || empty($items_json) || $items_json === '[]') {
    echo json_encode(['success' => false, 'error' => 'Your cart is empty. Please add items to your cart before proceeding.']);
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
    'customer_phone' => $customer_phone,
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
