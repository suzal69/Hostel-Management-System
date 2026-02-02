<?php
// eSewa V2 Payment Success Callback
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

if (!$encoded_data && !$payment_id_from_url) {
    $_SESSION['error'] = "Error! No response data received from eSewa.";
    error_log("eSewa Success callback: No data and no URL parameters. GET: " . json_encode($_GET));
    header("Location: ../student_room_details.php");
    exit();
}

// 2. Decode the Base64 JSON string
if ($encoded_data) {
    $decoded_json = base64_decode($encoded_data);
    $response = json_decode($decoded_json, true);

    // 3. Extract the V2 fields with extreme flexibility
    $status           = $response['status'] ?? '';
    // Handle total_amount vs totalAmount and ensure it's not treated as empty if it's "0"
    $total_amount     = $response['total_amount'] ?? $response['totalAmount'] ?? 0;
    // Handle uuid vs code
    $transaction_uuid = $response['transaction_uuid'] ?? $response['transaction_code'] ?? '';
    $ref_id           = $response['ref_id'] ?? '';

    // Log what we actually got to see why the next check might fail
    error_log("Extracted: status=$status, amt=$total_amount, uuid=$transaction_uuid, ref=$ref_id");

} else if ($payment_id_from_url && $transaction_uuid_from_url) {
    // FALLBACK: Use URL parameters when eSewa doesn't send encoded data
    error_log("eSewa Success callback fallback: Using URL parameters. Payment ID: " . $payment_id_from_url . ", TXN UUID: " . $transaction_uuid_from_url);
    
    // Retrieve payment details from database using payment_id first (eSewa payment ID)
    global $conn;
    
    // Try matching by payment_id field first
    $fetch_payment = $conn->prepare("SELECT payment_id, amount, student_id, status FROM payments WHERE payment_id = ?");
    $fetch_payment->bind_param("s", $payment_id_from_url);
    $fetch_payment->execute();
    $payment_record = $fetch_payment->get_result()->fetch_assoc();
    
    if (!$payment_record) {
        // Try by purchase_order_id as second attempt
        $fetch_payment2 = $conn->prepare("SELECT payment_id, amount, student_id, status FROM payments WHERE purchase_order_id = ?");
        $fetch_payment2->bind_param("s", $payment_id_from_url);
        $fetch_payment2->execute();
        $payment_record = $fetch_payment2->get_result()->fetch_assoc();
    }
    
    if (!$payment_record) {
        // If purchase_order_id doesn't match, try searching by status='pending' and transaction pattern
        // as a last resort (handles edge cases)
        error_log("eSewa Success callback: Payment record not found by payment_id or purchase_order_id. TXN UUID: " . $transaction_uuid_from_url);
        $_SESSION['error'] = "Error! Payment record not found. Please contact support.";
        header("Location: ../student_room_details.php");
        exit();
    }
    
    // CRITICAL: Store student_id and payment_id from database for use in payment completion
    $student_id = $payment_record['student_id'];
    $actual_payment_id = $payment_record['payment_id']; // Use the actual payment_id from database
    $transaction_uuid = $transaction_uuid_from_url;
    $status = "COMPLETE";  // Assume success since callback endpoint is for success
    $total_amount = $payment_record['amount'];  // Retrieve from database
    $ref_id = $transaction_uuid;  // Use transaction_uuid as ref_id in fallback
} else {
    $_SESSION['error'] = "Error! No response data received from eSewa.";
    error_log("eSewa Success callback: No data and no URL parameters. GET: " . json_encode($_GET));
    header("Location: ../student_room_details.php");
    exit();
}

// 4. Refined Validation Check
// We remove empty($total_amount) because if it's a string "0", empty() returns true.
// We also allow empty ref_id since eSewa sometimes doesn't send it back
if ($status === '' || $transaction_uuid === '') {
    $_SESSION['error'] = "Error! Invalid payment response from eSewa - missing V2 parameters";
    header("Location: ../student_room_details.php");
    exit();
}

// If ref_id is empty, use transaction_code as the reference
if (empty($ref_id) && !empty($response['transaction_code'])) {
    $ref_id = $response['transaction_code'];
}

// 4. Validate and Verify using V2 format
if ($status === "COMPLETE") {
    // For V2, we create the payment record only AFTER successful payment
    global $conn;
    
    // In fallback path, we already have student_id from database lookup (line 92)
    // Only try to get from session if we don't already have it
    if (!isset($student_id) || empty($student_id)) {
        $student_id = $_SESSION['student_id'] ?? $_SESSION['roll'] ?? null;
        
        // If no student_id in session, try to extract from transaction_uuid
        if (!$student_id && $transaction_uuid) {
            // Extract student_id from transaction_uuid pattern: TXN-timestamp-random
            // This is a fallback - ideally student_id should be in session
        }
    }
    
    if (!$student_id) {
        $_SESSION['error'] = "Error! Unable to identify student for payment completion.";
        header("Location: ../student_room_details.php");
        exit();
    }
    
    // In fallback path, we already have the payment record from the database lookup
    // We should UPDATE the existing pending record instead of creating a new one
    if ($payment_id_from_url && $transaction_uuid_from_url) {
        // FALLBACK: Update existing pending payment record
        $payment_id = $actual_payment_id; // Use the actual payment_id from database lookup
        
        // Update the existing payment record to 'completed' with transaction details
        // Use the payment_id we already found instead of searching by purchase_order_id pattern
        $stmt = $conn->prepare("UPDATE payments SET status = 'completed', transaction_id = ?, payment_gateway = 'esewa', updated_at = NOW() WHERE payment_id = ?");
        $transaction_id_to_store = !empty($ref_id) ? $ref_id : $transaction_uuid;
        
        // Debug logging
        error_log("eSewa Success: Updating payment_id=$payment_id with transaction_id=$transaction_id_to_store");
        
        $stmt->bind_param("ss", $transaction_id_to_store, $payment_id);
        
        if (!$stmt->execute()) {
            error_log("eSewa Success: Failed to update payment - " . $stmt->error);
            $_SESSION['error'] = "Error! Failed to record your payment. Please contact support.";
            header("Location: ../student_room_details.php");
            exit();
        }
        
        // Log successful update
        error_log("eSewa Success: Successfully updated payment_id=$payment_id to completed status");
        
        // Fetch the updated payment record for success message
        $fetch_updated = $conn->prepare("SELECT student_id, amount FROM payments WHERE payment_id = ?");
        $fetch_updated->bind_param("s", $payment_id);
        $fetch_updated->execute();
        $updated_payment = $fetch_updated->get_result()->fetch_assoc();
        
        if (!$updated_payment) {
            $_SESSION['error'] = "Error! Payment record not found after update. Please contact support.";
            header("Location: ../student_room_details.php");
            exit();
        }
        
        $student_id = $updated_payment['student_id'];
    } else {
        // Normal path: Create new payment record with 'completed' status
        $payment_id = generateEsewaPaymentId($student_id, 'room_allocation');
        
        // Check if this payment already exists (duplicate prevention)
        $check_stmt = $conn->prepare("SELECT payment_id FROM payments WHERE payment_id = ?");
        $check_stmt->bind_param("s", $payment_id);
        $check_stmt->execute();
        $existing_payment = $check_stmt->get_result()->fetch_assoc();
        
        if ($existing_payment) {
            // Generate new ID if duplicate found
            $payment_id = generateEsewaPaymentId($student_id, 'room_allocation_' . time());
        }
        
        // Insert payment record with 'completed' status and actual eSewa transaction details
        $stmt = $conn->prepare("INSERT INTO payments (payment_id, student_id, amount, payment_type, status, purchase_order_id, transaction_id, payment_gateway, created_at, updated_at) VALUES (?, ?, ?, ?, 'completed', ?, ?, 'esewa', NOW(), NOW())");
        $payment_type = 'room_allocation';
        // Use ref_id as transaction_id if available, otherwise use transaction_uuid as fallback
        $transaction_id_to_store = !empty($ref_id) ? $ref_id : $transaction_uuid;
        $stmt->bind_param("ssdsss", $payment_id, $student_id, $total_amount, $payment_type, $payment_id, $transaction_id_to_store);
        
        if (!$stmt->execute()) {
            $_SESSION['error'] = "Error! Failed to record your payment. Please contact support.";
            header("Location: ../student_room_details.php");
            exit();
        }
    }
    
    // Get detailed payment information for success message
    $student_details_stmt = $conn->prepare("
        SELECT s.Fname, s.Lname, s.total_paid, s.last_payment_date, s.payment_due_date,
               ba.allocation_price, ba.start_date, ba.end_date, ba.room_id, ba.bed_number
        FROM student s
        LEFT JOIN bed_allocation ba ON s.student_id = ba.student_id AND ba.is_active = 1
        WHERE s.student_id = ?
    ");
    $student_details_stmt->bind_param("s", $student_id);
    $student_details_stmt->execute();
    $student_details = $student_details_stmt->get_result()->fetch_assoc();
    
    // Calculate payment duration and details
    $payment_date = date('Y-m-d');
    $next_payment_date = date('Y-m-d', strtotime('+1 month'));
    
    // Get payment type details
    $payment_type_display = '';
    if ($student_details['allocation_price'] > 0) {
        $payment_type_display = 'Room Allocation';
        $duration_months = '1 month';
    } else {
        $payment_type_display = 'Other';
        $duration_months = '1 month';
    }
    
    // Create detailed success message
    $success_message = "
        <div class='alert alert-success'>
            <h4><i class='fa fa-check-circle'></i> Payment Completed Successfully!</h4>
            <div class='row'>
                <div class='col-md-6'>
                    <p><strong>Transaction ID:</strong> $ref_id</p>
                    <p><strong>Payment Type:</strong> $payment_type_display</p>
                    <p><strong>Amount Paid:</strong> NPR $total_amount</p>
                    <p><strong>Payment Date:</strong> " . date('d M Y') . "</p>
                </div>
                <div class='col-md-6'>";
    
    if ($student_details['room_id']) {
        $success_message .= "
                    <p><strong>Room:</strong> " . htmlspecialchars($student_details['room_id']) . ", Bed " . htmlspecialchars($student_details['bed_number']) . "</p>
                    <p><strong>Duration:</strong> $duration_months</p>";
    }
    
    $success_message .= "
                    <p><strong>Student:</strong> " . htmlspecialchars($student_details['Fname'] . ' ' . $student_details['Lname']) . "</p>
                </div>
            </div>
            <hr>
            <p class='mb-0'><i class='fa fa-info-circle'></i> Your payment has been processed and your room allocation has been updated.</p>
        </div>";
    
    $_SESSION['success'] = $success_message;
    
    // Update student payment records
    try {
        // Update student total paid and last payment date
        $update_student_sql = "UPDATE Student SET total_paid = COALESCE(total_paid, 0) + ?, last_payment_date = NOW(), payment_due_date = DATE_ADD(NOW(), INTERVAL 1 MONTH) WHERE Student_id = ?";
        $update_student_stmt = $conn->prepare($update_student_sql);
        $update_student_stmt->bind_param("ds", $total_amount, $student_id);
        $update_student_stmt->execute();
        
        // Update bed allocation payment status if exists
        $bed_allocation_sql = "UPDATE bed_allocation SET payment_status = 'paid', payment_id = ? WHERE student_id = ? AND is_active = 1";
        $bed_allocation_stmt = $conn->prepare($bed_allocation_sql);
        if ($bed_allocation_stmt) {
            $bed_allocation_stmt->bind_param("ss", $payment_id, $student_id);
            $bed_allocation_stmt->execute();
        }
    } catch (Exception $e) {
    }
    
} else {
    $_SESSION['error'] = "Payment incomplete or failed. Status received: " . $status;
}

// Redirect back to the student room details page
header("Location: ../student_room_details.php");
exit();
?>
