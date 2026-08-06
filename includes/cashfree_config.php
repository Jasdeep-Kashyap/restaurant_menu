<?php
// cashfree_config.php — Cashfree Payment Gateway Configuration
// Edit credentials below with your Cashfree Merchant App ID and Secret Key.

define('CF_ENV', 'sandbox'); // 'sandbox' or 'production'
define('CF_APP_ID', 'TEST11168022457f99e48d0057ff1a4c22086111'); // Replace with your Cashfree App ID
define('CF_SECRET_KEY', 'cfsk_ma_test_7fcc4614eba493da2b46cf629a8a564a_efe3fbb6'); // Replace with your Cashfree Secret Key
define('CF_API_VERSION', '2023-08-01');

function get_cashfree_base_url(): string {
    return (CF_ENV === 'production')
        ? 'https://api.cashfree.com/pg'
        : 'https://sandbox.cashfree.com/pg';
}

/**
 * Creates a Cashfree Payment Order via REST API (v3)
 */
function cashfree_create_order(array $params): array {
    $url = get_cashfree_base_url() . '/orders';
    
    $headers = [
        'Content-Type: application/json',
        'x-api-version: ' . CF_API_VERSION,
        'x-client-id: ' . CF_APP_ID,
        'x-client-secret: ' . CF_SECRET_KEY,
    ];

    $body = json_encode([
        'order_id'       => $params['order_id'],
        'order_amount'   => floatval($params['amount']),
        'order_currency' => 'INR',
        'customer_details' => [
            'customer_id'    => 'CUST_' . preg_replace('/[^a-zA-Z0-9]/', '', $params['customer_name']) . '_' . time(),
            'customer_name'  => $params['customer_name'],
            'customer_email' => $params['customer_email'] ?? 'customer@example.com',
            'customer_phone' => $params['customer_phone'] ?? '9999999999',
        ],
        'order_meta' => [
            'return_url' => $params['return_url'] . '?order_id={order_id}',
        ]
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return ['success' => false, 'error' => $error];
    }

    $data = json_decode($response, true);

    if ($httpCode === 200 && isset($data['payment_session_id'])) {
        return [
            'success'            => true,
            'payment_session_id' => $data['payment_session_id'],
            'cf_order_id'        => $data['order_id'],
        ];
    }

    // Return detailed error if API response contains failure message
    $msg = $data['message'] ?? 'Failed to connect to Cashfree API (HTTP ' . $httpCode . ')';
    return ['success' => false, 'error' => $msg, 'raw' => $data];
}

/**
 * Fetch Order Status from Cashfree API
 */
function cashfree_get_order_status(string $cf_order_id): array {
    $url = get_cashfree_base_url() . '/orders/' . urlencode($cf_order_id);
    
    $headers = [
        'Content-Type: application/json',
        'x-api-version: ' . CF_API_VERSION,
        'x-client-id: ' . CF_APP_ID,
        'x-client-secret: ' . CF_SECRET_KEY,
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($response, true);
    if ($httpCode === 200 && isset($data['order_status'])) {
        return ['success' => true, 'order_status' => $data['order_status'], 'data' => $data];
    }

    return ['success' => false, 'error' => $data['message'] ?? 'Unable to fetch status'];
}
