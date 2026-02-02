<?php
// eSewa Payment Gateway Configuration
// For security, replace these with your actual eSewa merchant credentials

// eSewa API Configuration - Multiple test credentials for fallback
if (!defined('ESEWA_MERCHANT_CODE')) define('ESEWA_MERCHANT_CODE', 'EPAYTEST');
if (!defined('ESEWA_SECRET_KEY')) define('ESEWA_SECRET_KEY', '8gBm/:&EnhH.1/q');
if (!defined('ESEWA_TEST_MODE')) define('ESEWA_TEST_MODE', true); // Set to false for production

// Alternative test credentials (try if main ones don't work)
if (!defined('ESEWA_ALT_MERCHANT_CODE')) define('ESEWA_ALT_MERCHANT_CODE', '1000');
if (!defined('ESEWA_ALT_SECRET_KEY')) define('ESEWA_ALT_SECRET_KEY', 'test_secret_key');

// Additional test credentials from eSewa documentation
if (!defined('ESEWA_TEST_CREDENTIALS')) define('ESEWA_TEST_CREDENTIALS', serialize([
    [
        'merchant_code' => 'EPAYTEST',
        'secret_key' => '8gBm/:&EnhH.1/q',
        'description' => 'Primary test credentials'
    ],
    [
        'merchant_code' => '1000',
        'secret_key' => 'test_secret_key',
        'description' => 'Alternative test credentials'
    ],
    [
        'merchant_code' => 'NP-ES-TEST',
        'secret_key' => 'test12345',
        'description' => 'Secondary test credentials'
    ],
    [
        'merchant_code' => 'TEST_MERCHANT',
        'secret_key' => 'test_key_123',
        'description' => 'Additional test credentials 1'
    ],
    [
        'merchant_code' => 'TEST_MERCHANT_2',
        'secret_key' => 'test_key_456',
        'description' => 'Additional test credentials 2'
    ]
]));

// Alternative test credentials (fallback options)
if (!defined('ESEWA_TEST_CREDENTIALS_ALTERNATIVE')) define('ESEWA_TEST_CREDENTIALS_ALTERNATIVE', serialize([
    [
        'merchant_code' => 'EPAYTEST',
        'secret_key' => '8gBm/:&EnhH.1/q',
        'description' => 'Primary test credentials (alternative)'
    ],
    [
        'merchant_code' => '1000',
        'secret_key' => 'test_secret_key',
        'description' => 'Alternative test credentials (secondary)'
    ]
]));

// eSewa URLs
if (ESEWA_TEST_MODE) {
    // Test/Sandbox URLs - Multiple options for fallback
    $esewa_urls = [
        'primary' => 'https://rc-epay.esewa.com.np/api/epay/main/v2/form',
        'secondary' => 'https://uat.esewa.com.np/api/epay/main/v2/form',
        'v1_fallback' => 'https://rc-epay.esewa.com.np/api/epay/main',
        'legacy' => 'https://esewa.com.np/epay/main'
    ];
    
    // Use V2 test endpoint as primary
    if (!defined('ESEWA_PAYMENT_URL')) define('ESEWA_PAYMENT_URL', $esewa_urls['primary']);
    if (!defined('ESEWA_PAYMENT_URLS')) define('ESEWA_PAYMENT_URLS', serialize($esewa_urls));
    
    if (!defined('ESEWA_VERIFICATION_URL')) define('ESEWA_VERIFICATION_URL', 'https://rc-epay.esewa.com.np/api/epay/transaction/status/');
} else {
    // Production URLs - CORRECTED
    if (!defined('ESEWA_PAYMENT_URL')) define('ESEWA_PAYMENT_URL', 'https://epay.esewa.com.np/api/epay/main/v2/form');
    if (!defined('ESEWA_VERIFICATION_URL')) define('ESEWA_VERIFICATION_URL', 'https://epay.esewa.com.np/api/epay/transaction/status/');
}

// eSewa Configuration
if (!defined('ESEWA_SUCCESS_URL')) define('ESEWA_SUCCESS_URL', 'http://localhost/project/payment/esewa_success.php');
if (!defined('ESEWA_FAILURE_URL')) define('ESEWA_FAILURE_URL', 'http://localhost/project/payment/esewa_failure.php');

// Payment Gateway Selection
if (!defined('AVAILABLE_PAYMENT_GATEWAYS')) define('AVAILABLE_PAYMENT_GATEWAYS', serialize(['esewa']));
if (!defined('ENABLE_MOCK_PAYMENT')) define('ENABLE_MOCK_PAYMENT', true); // Enable mock payment for testing

/**
 * Get eSewa configuration
 * @return array eSewa configuration parameters
 */
function getEsewaConfig() {
    return [
        'merchant_code' => ESEWA_MERCHANT_CODE,
        'secret_key' => ESEWA_SECRET_KEY,
        'test_mode' => ESEWA_TEST_MODE,
        'payment_url' => ESEWA_PAYMENT_URL,
        'verification_url' => ESEWA_VERIFICATION_URL,
        'success_url' => ESEWA_SUCCESS_URL,
        'failure_url' => ESEWA_FAILURE_URL
    ];
}

/**
 * Generate unique payment ID for eSewa
 * @param string $student_id Student ID
 * @param string $payment_type Payment type
 * @return string Unique payment ID
 */
function generateEsewaPaymentId($student_id, $payment_type) {
    $timestamp = date('YmdHis');
    $random = mt_rand(1000, 9999);
    return 'ESEW_' . $timestamp . '_' . $random;
}
?>
