<?php
// eSewa V2 Payment Failure Callback
require_once __DIR__ . '/../includes/config.inc.php';
require_once __DIR__ . '/../includes/esewa_payment.inc.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Get the encoded data from eSewa V2
$encoded_data = $_GET['data'] ?? null;

// Fallback: Check if payment_id and transaction_uuid are in URL params
$payment_id_from_url = $_GET['payment_id'] ?? null;
$transaction_uuid_from_url = $_GET['transaction_uuid'] ?? null;

// Session fallback for student identification
$student_id_from_session = $_SESSION['student_id'] ?? $_SESSION['roll'] ?? null;

if ($encoded_data) {
    // 2. Decode the Base64 JSON string
    $decoded_json = base64_decode($encoded_data);
    $response = json_decode($decoded_json, true);
    
    // 3. Extract the V2 fields (eSewa sometimes sends transaction_code instead of transaction_uuid)
    $status           = $response['status'] ?? '';
    $total_amount     = $response['total_amount'] ?? 0;
    $transaction_uuid = $response['transaction_uuid'] ?? $response['transaction_code'] ?? ''; // Handle both field names
    $ref_id           = $response['ref_id'] ?? '';
    
    // Update payment status to failed if we can identify the payment
    if (!empty($transaction_uuid)) {
        global $conn;
        
        try {
            // Find the payment record using the transaction_uuid
            $stmt = $conn->prepare("SELECT payment_id FROM payments WHERE transaction_id = ? OR purchase_order_id LIKE ?");
            $search_pattern = "%{$transaction_uuid}%";
            $stmt->bind_param("ss", $ref_id, $search_pattern);
            $stmt->execute();
            $result = $stmt->get_result();
            $payment_record = $result->fetch_assoc();
            
            $payment_id = $payment_record['payment_id'] ?? '';
            
            if (!empty($payment_id)) {
                $stmt = $conn->prepare("UPDATE payments SET status = 'failed', updated_at = NOW() WHERE payment_id = ? AND status = 'pending'");
                $stmt->bind_param("s", $payment_id);
                $stmt->execute();
            }
            
        } catch (Exception $e) {
        }
    }
    
    // Set V2-specific error message with detailed debugging info
    $error_details = [];
    
    // Add detailed error information based on eSewa status
    switch($status) {
        case 'FAILED':
            $error_details[] = 'Payment was declined by eSewa.';
            $error_details[] = 'Possible reasons: Invalid card, insufficient funds, or gateway error.';
            break;
        case 'CANCELLED':
            $error_details[] = 'You cancelled the payment.';
            break;
        case 'PENDING':
            $error_details[] = 'Payment is still processing. Please wait a few moments and refresh.';
            break;
        default:
            $error_details[] = 'Payment status: ' . $status;
            break;
    }
    
    // Log critical failure information for debugging
    error_log("CRITICAL: eSewa Payment Failed - Status: $status, Amount: $total_amount, TXN: $transaction_uuid, Ref: $ref_id, Student: " . ($_SESSION['student_id'] ?? $_SESSION['roll'] ?? 'UNKNOWN'));
    
    // Set specific payment failure details for better UI display
    $_SESSION['payment_failure'] = [
        'status' => $status,
        'amount' => $total_amount,
        'transaction_uuid' => $transaction_uuid,
        'ref_id' => $ref_id,
        'details' => $error_details,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    $_SESSION['error'] = "Payment Failed: " . implode(' ', $error_details);
    
} else {
    // Fallback: Use URL parameters if eSewa didn't send response data
    error_log("CRITICAL: eSewa Payment Failure - No data received from eSewa. GET: " . json_encode($_GET) . ", POST: " . json_encode($_POST));
    
    global $conn;
    
    // Try to find payment using URL parameters first
    if ($payment_id_from_url && $transaction_uuid_from_url) {
        // Mark payment as failed using the purchase_order_id
        $update_stmt = $conn->prepare("UPDATE payments SET status = 'failed', transaction_id = ?, updated_at = NOW() WHERE purchase_order_id = ?");
        $update_stmt->bind_param("ss", $transaction_uuid_from_url, $payment_id_from_url);
        
        if ($update_stmt->execute()) {
            error_log("Payment marked as failed using URL parameters. Payment ID: " . $payment_id_from_url . ", TXN UUID: " . $transaction_uuid_from_url);
            
            $_SESSION['payment_failure_details'] = [
                'status' => 'FAILED',
                'transaction_uuid' => $transaction_uuid_from_url,
                'payment_id' => $payment_id_from_url,
                'reason' => 'No response data from eSewa (URL params used)',
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
    } else if ($student_id_from_session) {
        // Fallback: Try to find pending payment by student_id
        try {
            $find_stmt = $conn->prepare("SELECT payment_id, amount FROM payments WHERE student_id = ? AND status = 'pending' ORDER BY created_at DESC LIMIT 1");
            $find_stmt->bind_param("s", $student_id_from_session);
            $find_stmt->execute();
            $result = $find_stmt->get_result();
            $payment_record = $result->fetch_assoc();
            
            if ($payment_record) {
                // Mark this payment as failed
                $update_stmt = $conn->prepare("UPDATE payments SET status = 'failed', updated_at = NOW() WHERE payment_id = ?");
                $update_stmt->bind_param("s", $payment_record['payment_id']);
                $update_stmt->execute();
                
                error_log("Payment marked as failed using student session fallback. Student: " . $student_id_from_session . ", Payment ID: " . $payment_record['payment_id']);
                
                $_SESSION['payment_failure_details'] = [
                    'status' => 'FAILED',
                    'student_id' => $student_id_from_session,
                    'payment_id' => $payment_record['payment_id'],
                    'reason' => 'No response data from eSewa (session fallback)',
                    'timestamp' => date('Y-m-d H:i:s')
                ];
            }
        } catch (Exception $e) {
            error_log("Failed to mark payment as failed in fallback: " . $e->getMessage());
        }
    }
    
    $_SESSION['error'] = "Payment has been cancelled or failed. Please try again or contact support if the problem persists.";
}

// Redirect back to the student room details page
header("Location: ../student_room_details.php");
exit();
?>
