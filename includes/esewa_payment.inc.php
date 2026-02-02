<?php
// eSewa Payment Processing Functions
require_once __DIR__ . '/config.inc.php';
require_once __DIR__ . '/esewa_config.inc.php';

/**
 * Generate eSewa signature for V2 API
 * @param array $data Data to sign
 * @param string $secret_key Secret key for signing
 * @return string Generated signature
 */
function generateEsewaSignature($data, $secret_key) {
    // Get the signed field names from the data
    $signed_field_names = isset($data['signed_field_names']) ? $data['signed_field_names'] : '';
    $signed_fields = explode(',', $signed_field_names);

    // Create the string to sign - V2 format uses key=value pairs
    $string_to_sign = '';
    foreach ($signed_fields as $field) {
        $field = trim($field);
        if (isset($data[$field])) {
            if (!empty($string_to_sign)) {
                $string_to_sign .= ',';
            }
            $string_to_sign .= $field . '=' . $data[$field];
        }
    }

    // Generate HMAC-SHA256 signature
    $raw_hash = hash_hmac('sha256', $string_to_sign, $secret_key, true);
    $signature = base64_encode($raw_hash);

    return $signature;
}

/**
 * Initiate eSewa Payment with fallback mechanism
 * @param string $student_id Student ID
 * @param float $amount Payment amount
 * @param string $payment_type Payment type (room_allocation, food_plan, etc.)
 * @param string $purchase_order_id Purchase order ID
 * @param array $additional_data Additional payment data
 * @return array Payment initiation response
 */
function initiateEsewaPayment($student_id, $amount, $payment_type, $purchase_order_id, $additional_data = []) {
    global $conn;
    
    try {
        // Generate unique payment ID
        $payment_id = generateEsewaPaymentId($student_id, $payment_type);
        
        // Generate transaction_uuid for V2 callback lookup
        $transaction_uuid = 'TXN-' . time() . '-' . rand(1000, 9999);
        
        // Check if payment ID already exists to prevent duplicates
        $check_stmt = $conn->prepare("SELECT payment_id FROM payments WHERE payment_id = ?");
        $check_stmt->bind_param("s", $payment_id);
        $check_stmt->execute();
        $existing_payment = $check_stmt->get_result()->fetch_assoc();
        
        if ($existing_payment) {
            // Generate new ID if duplicate found
            $payment_id = generateEsewaPaymentId($student_id, $payment_type . '_retry');
        }
        
        // DO NOT create payment record yet - only create after successful payment
        // This prevents premature 'pending' status when user just clicks the button
        
        // Try different configurations with fallback
        $result = tryEsewaPaymentWithFallback($payment_id, $student_id, $amount, $purchase_order_id, $transaction_uuid);
        
        return $result;
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Try eSewa payment with multiple fallback options
 * @param string $payment_id Payment ID
 * @param string $student_id Student ID
 * @param float $amount Payment amount
 * @param string $purchase_order_id Purchase order ID
 * @param string $transaction_uuid Transaction UUID for V2
 * @return array Payment initiation response
 */
function tryEsewaPaymentWithFallback($payment_id, $student_id, $amount, $purchase_order_id, $transaction_uuid) {
    $esewa_config = getEsewaConfig();
    $urls = unserialize(ESEWA_PAYMENT_URLS);
    $credentials = unserialize(ESEWA_TEST_CREDENTIALS);
    $alternative_credentials = unserialize(ESEWA_TEST_CREDENTIALS_ALTERNATIVE);
    
    // First, try to get the best available configuration
    require_once __DIR__ . '/esewa_health_check.inc.php';
    $best_config = getBestEsewaConfiguration();
    
    if ($best_config) {
        // Try the best configuration first
        try {
            $result = generateEsewaPaymentData($payment_id, $student_id, $amount, $purchase_order_id, $best_config['url'], $best_config['credentials'], $transaction_uuid);
            
            if ($result['success']) {
                return $result;
            } else {
                if (strpos($result['error'], 'ES104') !== false) {
                    // Try alternative credentials if primary ones fail with ES104 error
                    $alternative_cred_set = null;
                    foreach ($alternative_credentials as $cred_set) {
                        if ($cred_set['merchant_code'] === $best_config['credentials']['merchant_code']) {
                            $alternative_cred_set = $cred_set;
                            break;
                        }
                    }
                    
                    if ($alternative_cred_set) {
                        $result = generateEsewaPaymentData($payment_id, $student_id, $amount, $purchase_order_id, $best_config['url'], $alternative_cred_set, $transaction_uuid);
                        
                        if ($result['success']) {
                            return $result;
                        }
                    }
                }
            }
        } catch (Exception $e) {
            // Best config failed
        }
    }
    
    // If best config failed, try all other configurations
    foreach ($urls as $url_type => $url) {
        foreach ($credentials as $cred_set) {
            // Skip if this was the best config we already tried
            if ($best_config && $url_type === $best_config['url_type'] && $cred_set['merchant_code'] === $best_config['credentials']['merchant_code']) {
                continue;
            }
            
            try {
                $result = generateEsewaPaymentData($payment_id, $student_id, $amount, $purchase_order_id, $url, $cred_set, $transaction_uuid);
                
                if ($result['success']) {
                    return $result;
                }
                
            } catch (Exception $e) {
                continue;
            }
        }
    }
    
    // All configurations failed
    return [
        'success' => false,
        'error' => 'All eSewa configurations failed. Service may be temporarily unavailable. Please try Mock Payment as an alternative.'
    ];
}

/**
 * Generate eSewa payment data for specific configuration
 * @param string $payment_id Payment ID
 * @param string $student_id Student ID
 * @param float $amount Payment amount
 * @param string $purchase_order_id Purchase order ID
 * @param string $payment_url Payment URL
 * @param array $credentials Merchant credentials
 * @param string $transaction_uuid Transaction UUID for V2
 * @return array Payment data
 */
function generateEsewaPaymentData($payment_id, $student_id, $amount, $purchase_order_id, $payment_url, $credentials, $transaction_uuid = null) {
    $esewa_config = getEsewaConfig();
    
    // 1. Format the amount as STRING integer (critical for eSewa V2)
    $formatted_amount = (string)((int)$amount);
    
    // Use provided transaction_uuid or generate a new one
    if ($transaction_uuid === null) {
        $transaction_uuid = 'TXN-' . time() . '-' . rand(1000, 9999);
    }
    
    $product_code = trim($credentials['merchant_code']);
    $secret_key = trim($credentials['secret_key']);

    // 2. eSewa V2 API Official Signature Format:
    // According to eSewa documentation, signature is computed over specific fields
    // Signed fields (in order): total_amount, transaction_uuid, product_code
    $string_to_sign = "total_amount=$formatted_amount,transaction_uuid=$transaction_uuid,product_code=$product_code";

    // 3. Generate the HMAC-SHA256 Signature
    $raw_hash = hash_hmac('sha256', $string_to_sign, $secret_key, true);
    $signature = base64_encode($raw_hash);

    // Add transaction UUID and purchase order ID to callback URLs as fallback
    $success_url = trim($esewa_config['success_url']) . '?payment_id=' . urlencode($purchase_order_id) . '&transaction_uuid=' . urlencode($transaction_uuid);
    $failure_url = trim($esewa_config['failure_url']) . '?payment_id=' . urlencode($purchase_order_id) . '&transaction_uuid=' . urlencode($transaction_uuid);
    
    // Create payment data - ALL VALUES MUST BE STRINGS for form submission
    $payment_data = [
        'total_amount'            => $formatted_amount,
        'transaction_uuid'        => $transaction_uuid,
        'product_code'            => $product_code,
        'merchant_code'           => $product_code,
        'amount'                  => $formatted_amount,
        'tax_amount'              => '0',
        'product_service_charge'  => '0',
        'product_delivery_charge' => '0',
        'success_url'             => $success_url,
        'failure_url'             => $failure_url,
        'signed_field_names'      => 'total_amount,transaction_uuid,product_code',
        'signature'               => $signature
    ];

    return [
        'success' => true,
        'payment_url' => $payment_url,
        'form_data' => $payment_data,
        'credentials_used' => $credentials['description'] ?? 'Unknown',
        'transaction_uuid' => $transaction_uuid,
        'string_to_sign' => $string_to_sign
    ];
}

/**
 * Verify eSewa Payment V2
 * @param string $payment_id Payment ID
 * @param string $ref_id Reference ID from eSewa (transaction_code)
 * @param float $amount Payment amount
 * @param string $transaction_uuid Transaction UUID from eSewa
 * @return array Verification response
 */
function verifyEsewaPayment($payment_id, $ref_id, $amount, $transaction_uuid) {
    global $conn;
    
    try {
        $esewa_config = getEsewaConfig();
        
        // eSewa V2 Verification uses a GET request with these parameters
        // Endpoint example: https://rc-epay.esewa.com.np/api/epay/transaction/status/
        $product_code = 'EPAYTEST';
        $status_url = "https://rc-epay.esewa.com.np/api/epay/transaction/status/";
        
        $query_params = http_build_query([
            'product_code' => $product_code,
            'total_amount' => $amount,
            'transaction_uuid' => $transaction_uuid
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $status_url . "?" . $query_params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $response_data = json_decode($response, true);
        curl_close($ch);
        
        // V2 Returns a JSON object, not just a "Success" string
        if (isset($response_data['status']) && $response_data['status'] === 'COMPLETE') {
            // SUCCESS Logic
            $stmt = $conn->prepare("UPDATE payments SET status = 'completed', transaction_id = ?, updated_at = NOW() WHERE payment_id = ?");
            $stmt->bind_param("ss", $ref_id, $payment_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update payment status: " . $stmt->error);
            }
            
            return [
                'success' => true,
                'status' => 'completed',
                'transaction_id' => $ref_id,
                'message' => 'Payment verified successfully'
            ];
            
        } else {
            // Update payment record as failed
            $stmt = $conn->prepare("UPDATE payments SET status = 'failed', updated_at = NOW() WHERE payment_id = ?");
            $stmt->bind_param("s", $payment_id);
            $stmt->execute();
            
            return [
                'success' => false,
                'status' => 'failed',
                'message' => 'Payment verification failed or incomplete. Response: ' . json_encode($response_data)
            ];
        }
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Get payment details by payment ID
 * @param string $payment_id Payment ID
 * @return array|null Payment details
 */
function getEsewaPaymentDetails($payment_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM payments WHERE payment_id = ?");
    $stmt->bind_param("s", $payment_id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}


/**
 * Check if payment gateway is available
 * @param string $gateway Gateway name (esewa)
 * @return bool
 */
function isPaymentGatewayAvailable($gateway) {
    $available_gateways = unserialize(AVAILABLE_PAYMENT_GATEWAYS);
    return in_array($gateway, $available_gateways);
}
?>
