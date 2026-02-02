<?php
require_once 'includes/config.inc.php';

echo "<h2>Database Payment Records Check</h2>";

global $conn;

// Check for the specific payment IDs that are failing
$payment_ids_to_check = [
    'room_allocation_20790522_20260117170946',
    'room_allocation_20790522_20260117171659'
];

echo "<h3>Checking Payment Records:</h3>";

foreach ($payment_ids_to_check as $payment_id) {
    echo "<h4>Payment ID: " . htmlspecialchars($payment_id) . "</h4>";
    
    // Check by payment_id field
    $stmt = $conn->prepare("SELECT payment_id, student_id, amount, status, purchase_order_id FROM payments WHERE payment_id = ?");
    $stmt->bind_param("s", $payment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo "<p><strong>By payment_id field:</strong></p>";
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<pre>" . print_r($row, true) . "</pre>";
        }
    } else {
        echo "<p>No record found</p>";
    }
    
    // Check by purchase_order_id field
    $stmt2 = $conn->prepare("SELECT payment_id, student_id, amount, status, purchase_order_id FROM payments WHERE purchase_order_id = ?");
    $stmt2->bind_param("s", $payment_id);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    
    echo "<p><strong>By purchase_order_id field:</strong></p>";
    if ($result2->num_rows > 0) {
        while ($row = $result2->fetch_assoc()) {
            echo "<pre>" . print_r($row, true) . "</pre>";
        }
    } else {
        echo "<p>No record found</p>";
    }
    
    echo "<hr>";
}

// Check all recent payments for student 20790522
echo "<h3>All Recent Payments for Student 20790522:</h3>";
$stmt3 = $conn->prepare("SELECT payment_id, purchase_order_id, student_id, amount, status, created_at FROM payments WHERE student_id = '20790522' ORDER BY created_at DESC LIMIT 10");
$stmt3->execute();
$result3 = $stmt3->get_result();

echo "<table border='1' style='border-collapse: collapse; width: 100%'>";
echo "<tr><th>Payment ID</th><th>Purchase Order ID</th><th>Student ID</th><th>Amount</th><th>Status</th><th>Created</th></tr>";

while ($row = $result3->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['payment_id']) . "</td>";
    echo "<td>" . htmlspecialchars($row['purchase_order_id']) . "</td>";
    echo "<td>" . htmlspecialchars($row['student_id']) . "</td>";
    echo "<td>" . $row['amount'] . "</td>";
    echo "<td>" . htmlspecialchars($row['status']) . "</td>";
    echo "<td>" . $row['created_at'] . "</td>";
    echo "</tr>";
}
echo "</table>";
?>
