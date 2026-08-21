<?php
// cashfree_config.php — Cashfree Payment Gateway Configuration
// Credentials are loaded from the .env file located one level above this file.
// Do NOT hardcode API keys here. Edit the .env file instead.

// ── Load .env ─────────────────────────────────────────────────
$_env_path = __DIR__ . '/../.env';
if (!file_exists($_env_path)) {
    error_log('[Cashfree] .env file not found at ' . $_env_path);
    define('CF_ENV',        'sandbox');
    define('CF_APP_ID',     '');
    define('CF_SECRET_KEY', '');
} else {
    $_env = parse_ini_file($_env_path);
    define('CF_ENV',        $_env['CF_ENV']        ?? 'sandbox');
    define('CF_APP_ID',     $_env['CF_APP_ID']     ?? '');
    define('CF_SECRET_KEY', $_env['CF_SECRET_KEY'] ?? '');
    unset($_env, $_env_path);
}

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
        'x-client-id: '     . CF_APP_ID,
        'x-client-secret: ' . CF_SECRET_KEY,
    ];

    $cust_clean_id = preg_replace('/[^a-zA-Z0-9]/', '', $params['customer_name'] ?? '');
    if (empty($cust_clean_id)) {
        $cust_clean_id = 'GUEST';
    }

    $raw_phone = preg_replace('/[^0-9]/', '', $params['customer_phone'] ?? '');
    $phone = (strlen($raw_phone) === 10 && in_array($raw_phone[0], ['6', '7', '8', '9'])) ? $raw_phone : '9876543210';

    $body = json_encode([
        'order_id'       => $params['order_id'],
        'order_amount'   => floatval($params['amount']),
        'order_currency' => 'INR',
        'customer_details' => [
            'customer_id'    => 'CUST_' . $cust_clean_id . '_' . time(),
            'customer_name'  => $params['customer_name'] ?: 'Obsidian Guest',
            'customer_email' => $params['customer_email'] ?? 'guest@obsidian.com',
            'customer_phone' => $phone,
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
        CURLOPT_SSL_VERIFYPEER => true,  // ✅ FIXED: was false — MITM vulnerability
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($error) {
        error_log('[Cashfree] cURL error: ' . $error);
        return ['success' => false, 'error' => 'Payment gateway connection failed.'];
    }

    $data = json_decode($response, true);

    if ($httpCode === 200 && isset($data['payment_session_id'])) {
        return [
            'success'            => true,
            'payment_session_id' => $data['payment_session_id'],
            'cf_order_id'        => $data['order_id'],
        ];
    }

    $msg = $data['message'] ?? 'Failed to connect to Cashfree API (HTTP ' . $httpCode . ')';
    error_log('[Cashfree] Order creation failed: ' . $msg . ' | HTTP ' . $httpCode);
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
        'x-client-id: '     . CF_APP_ID,
        'x-client-secret: ' . CF_SECRET_KEY,
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,  // ✅ FIXED: was false — MITM vulnerability
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        error_log('[Cashfree] Status check cURL error: ' . $curlErr);
        return ['success' => false, 'error' => 'Payment gateway connection failed.'];
    }

    $data = json_decode($response, true);
    if ($httpCode === 200 && isset($data['order_status'])) {
        return ['success' => true, 'order_status' => $data['order_status'], 'data' => $data];
    }

    error_log('[Cashfree] Status check failed: HTTP ' . $httpCode . ' | ' . json_encode($data));
    return ['success' => false, 'error' => $data['message'] ?? 'Unable to fetch status'];
}
