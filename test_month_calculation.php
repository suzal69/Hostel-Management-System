<?php
// Test script to verify month calculation logic
require_once 'includes/config.inc.php';

// Test student ID (replace with actual student ID from your database)
$test_student_id = 'TEST001';

// Get payment history
$paymentHistory = [];
$paymentQuery = "SELECT * FROM payments WHERE student_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($paymentQuery);
if ($stmt) {
    $stmt->bind_param("s", $test_student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $paymentHistory[] = $row;
    }
    $stmt->close();
}

// Calculate payment summary (same logic as student_room_details.php)
$paymentSummary = [
    'total_transactions' => count($paymentHistory),
    'total_paid' => 0,
    'pending_amount' => 0,
    'completed_payments' => 0
];

foreach ($paymentHistory as $payment) {
    if ($payment['status'] === 'completed') {
        $paymentSummary['total_paid'] += $payment['amount'];
        $paymentSummary['completed_payments']++;
    }
}

// Calculate next month to pay
$nextMonthToPay = $paymentSummary['completed_payments'] + 1;

echo "<h3>Payment Summary for Student: $test_student_id</h3>";
echo "<p><strong>Total Transactions:</strong> " . $paymentSummary['total_transactions'] . "</p>";
echo "<p><strong>Completed Payments:</strong> " . $paymentSummary['completed_payments'] . "</p>";
echo "<p><strong>Next Month to Pay:</strong> Month $nextMonthToPay</p>";

echo "<h4>Payment Details:</h4>";
echo "<table border='1'>";
echo "<tr><th>Payment ID</th><th>Amount</th><th>Status</th><th>Date</th></tr>";
foreach ($paymentHistory as $payment) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($payment['payment_id']) . "</td>";
    echo "<td>" . number_format($payment['amount'], 2) . "</td>";
    echo "<td>" . ucfirst($payment['status']) . "</td>";
    echo "<td>" . date('d M Y', strtotime($payment['created_at'])) . "</td>";
    echo "</tr>";
}
echo "</table>";

// Test the specific scenario: Month 7 of 14
echo "<h3>Test Scenario: Month 7 of 14</h3>";
echo "<p>If you see 'Month 7 of 14', it means:</p>";
echo "<ul>";
echo "<li>6 payments have 'completed' status in the database</li>";
echo "<li>The allocation duration is 14 months total</li>";
echo "<li>The student is now paying for month 7</li>";
echo "</ul>";
?>
