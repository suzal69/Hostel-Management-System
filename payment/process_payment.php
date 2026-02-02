<?php
// Set error handling FIRST before anything else
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/payment_process_error.log');

// Prevent output buffering issues and ensure JSON-only output
if (ob_get_level() > 0) {
    ob_end_clean();
}
ob_start();

// Set JSON header immediately to prevent HTML output
header('Content-Type: application/json; charset=utf-8');

// Error handling - log errors instead of outputting them
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("Payment Process Error [$errno]: $errstr in $errfile on line $errline");
    // Return false to continue with PHP's internal error handler
    return false;
}, E_ALL);

// Process payment initiation - Create pending payment record
require_once __DIR__ . '/../includes/config.inc.php';
require_once __DIR__ . '/../includes/esewa_payment.inc.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['roll']) && !isset($_SESSION['student_id'])) {
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Not authenticated. Please log in first.'
    ]);
    exit();
}

$student_id = $_SESSION['student_id'] ?? $_SESSION['roll'];
$payment_gateway = $_POST['payment_gateway'] ?? '';
$amount = $_POST['amount'] ?? 0;
$payment_type = $_POST['payment_type'] ?? 'room_allocation';
$purchase_order_id = $_POST['purchase_order_id'] ?? '';

// Validate inputs
if (!$payment_gateway || $amount <= 0 || !$purchase_order_id) {
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid payment details. Required: payment_gateway, amount, purchase_order_id'
    ]);
    exit();
}

// Log payment processing
// error_log("Payment Processing Started: Student=$student_id, Gateway=$payment_gateway, Amount=$amount");

try {
    global $conn;
    
    // Step 1: Create initial payment record with 'pending' status
    $payment_id = generateEsewaPaymentId($student_id, $payment_type);
    
    // Check if payment already exists
    $check_stmt = $conn->prepare("SELECT payment_id FROM payments WHERE payment_id = ?");
    $check_stmt->bind_param("s", $payment_id);
    $check_stmt->execute();
    $existing = $check_stmt->get_result()->fetch_assoc();
    
    if ($existing) {
        $payment_id = generateEsewaPaymentId($student_id, $payment_type . '_' . time());
    }
    
    // Insert pending payment record
    $insert_stmt = $conn->prepare("
        INSERT INTO payments 
        (payment_id, student_id, amount, payment_type, status, purchase_order_id, payment_gateway, created_at, updated_at) 
        VALUES (?, ?, ?, ?, 'pending', ?, ?, NOW(), NOW())
    ");
    
    $insert_stmt->bind_param("ssdsss", $payment_id, $student_id, $amount, $payment_type, $purchase_order_id, $payment_gateway);
    
    if (!$insert_stmt->execute()) {
        // Log to file only, not output
        throw new Exception("Failed to create payment record: " . $insert_stmt->error);
    }
    // Log payment processing
    // error_log("Payment record created: Payment_ID=$payment_id, Status=pending");
    
    // Step 2: Store payment details in session for later reference
    $_SESSION['payment_id'] = $payment_id;
    $_SESSION['payment_amount'] = $amount;
    $_SESSION['payment_type'] = $payment_type;
    
    // Step 3: Proceed with payment gateway based on selection
    if ($payment_gateway === 'esewa') {
        // Initiate eSewa payment
        $transaction_uuid = 'TXN-' . time() . '-' . rand(1000, 9999);
        $payment_result = initiateEsewaPayment($student_id, $amount, $payment_type, $purchase_order_id, ['transaction_uuid' => $transaction_uuid]);
        
        if ($payment_result['success']) {
            // Store transaction UUID for verification
            $_SESSION['transaction_uuid'] = $payment_result['transaction_uuid'];
            
            // Return JSON response for form submission
            ob_end_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'form_data' => $payment_result['form_data'],
                'payment_url' => $payment_result['payment_url'],
                'payment_id' => $payment_id
            ]);
            exit();
        } else {
            // Check if it's an ES104 error (invalid credentials)
            if (strpos($payment_result['error'] ?? '', 'ES104') !== false) {
                // Try with alternative credentials
                // Try mock payment as fallback
                $_SESSION['error'] = "eSewa test credentials are not working. Please try Mock Payment for testing.";
                ob_end_clean();
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'error' => 'eSewa test credentials invalid (ES104). Please use Mock Payment for testing.',
                    'use_mock' => true
                ]);
                exit();
            } else {
                // Payment initiation failed for other reasons
                $update_stmt = $conn->prepare("UPDATE payments SET status = 'failed' WHERE payment_id = ?");
                $update_stmt->bind_param("s", $payment_id);
                $update_stmt->execute();
                
                ob_end_clean();
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'error' => $payment_result['error'] ?? 'Payment initiation failed'
                ]);
                exit();
            }
        }
    } else {
        ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error' => 'Unknown payment gateway'
        ]);
        exit();
    }
    
} catch (Exception $e) {
    // Log to file only, not output
    error_log("Payment processing error: " . $e->getMessage());
    
    ob_clean();
    ob_start();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error' => 'Payment processing error: ' . $e->getMessage()
    ]);
    ob_end_flush();
    exit();
}

// Clean and output final JSON
ob_end_clean();
header('Content-Type: application/json; charset=utf-8');
?>
