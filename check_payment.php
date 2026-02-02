<?php
// Check payment status in database
require_once 'includes/config.inc.php';

$payment_id = $_GET['payment_id'] ?? 'ESEW_20260101113947_3700';

echo "<h2>Payment Status Check</h2>";
echo "<p><strong>Payment ID:</strong> " . htmlspecialchars($payment_id) . "</p>";

// Check payment record
$stmt = $conn->prepare("SELECT * FROM payments WHERE payment_id = ?");
$stmt->bind_param("s", $payment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($payment = $result->fetch_assoc()) {
    echo "<h3>Payment Record Found:</h3>";
    echo "<pre>" . print_r($payment, true) . "</pre>";
} else {
    echo "<h3>No payment record found!</h3>";
}

// Check bed allocation
$stmt = $conn->prepare("SELECT * FROM bed_allocation WHERE payment_id = ?");
$stmt->bind_param("s", $payment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($allocation = $result->fetch_assoc()) {
    echo "<h3>Bed Allocation Record:</h3>";
    echo "<pre>" . print_r($allocation, true) . "</pre>";
} else {
    echo "<h3>No bed allocation record found!</h3>";
}

// Show all recent payments for this student
$stmt = $conn->prepare("SELECT * FROM payments WHERE student_id = '20790520' ORDER BY created_at DESC LIMIT 5");
$stmt->execute();
$result = $stmt->get_result();

echo "<h3>Recent Payments for Student 20790520:</h3>";
while ($payment = $result->fetch_assoc()) {
    echo "<pre>" . print_r($payment, true) . "</pre>";
    echo "<hr>";
}
?>
