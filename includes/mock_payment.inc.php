<?php
// Mock Payment Gateway for Testing
// Use this when eSewa sandbox is unavailable

/**
 * Initiate Mock Payment
 * @param string $student_id Student ID
 * @param float $amount Payment amount
 * @param string $payment_type Payment type
 * @param string $purchase_order_id Purchase order ID
 * @return array Payment initiation response
 */
function initiateMockPayment($student_id, $amount, $payment_type, $purchase_order_id) {
    global $conn;
    
    try {
        // Generate unique payment ID
        $payment_id = generateEsewaPaymentId($student_id, $payment_type);
        
        // Insert payment record
        $stmt = $conn->prepare("INSERT INTO payments (payment_id, student_id, amount, payment_type, status, purchase_order_id) VALUES (?, ?, ?, ?, 'pending', ?)");
        $stmt->bind_param("ssdss", $payment_id, $student_id, $amount, $payment_type, $purchase_order_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to create payment record: " . $stmt->error);
        }
        
        // Simulate payment success after 3 seconds
        $payment_data = [
            'payment_id' => $payment_id,
            'amount' => $amount,
            'status' => 'pending',
            'mock_mode' => true
        ];
        
        return [
            'success' => true,
            'payment_id' => $payment_id,
            'mock_mode' => true,
            'form_data' => $payment_data
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Generate mock payment form
 * @param array $payment_data Payment data
 * @return string HTML form
 */
function generateMockPaymentForm($payment_data) {
    $html = '<div class="mock-payment-container">';
    $html .= '<div class="alert alert-warning">';
    $html .= '<h5 class="alert-heading"><i class="fas fa-flask me-2"></i>Mock Payment Mode</h5>';
    $html .= '<p class="mb-2">eSewa sandbox is currently unavailable. Using mock payment for testing.</p>';
    $html .= '<p class="mb-0"><strong>Amount:</strong> NPR ' . number_format($payment_data['amount'], 2) . '</p>';
    $html .= '</div>';
    
    $html .= '<div class="alert alert-info">';
    $html .= '<h6>Payment Details:</h6>';
    $html .= '<pre>' . htmlspecialchars(print_r($payment_data, true)) . '</pre>';
    $html .= '</div>';
    
    $html .= '<form method="GET" action="mock_success.php">';
    $html .= '<input type="hidden" name="payment_id" value="' . htmlspecialchars($payment_data['payment_id']) . '">';
    $html .= '<div class="d-grid gap-2">';
    $html .= '<button type="submit" class="btn btn-warning btn-lg">';
    $html .= '<i class="fas fa-play me-2"></i>Simulate Payment';
    $html .= '</button>';
    $html .= '</div>';
    $html .= '</form>';
    $html .= '</div>';
    
    return $html;
}
?>
