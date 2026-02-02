<?php
// Debug script to check payment status
require_once 'includes/config.inc.php';

// Get recent payments to check their status
echo "<h2>Recent Payment Records Debug</h2>";

$query = "SELECT payment_id, student_id, amount, status, payment_type, purchase_order_id, transaction_id, payment_gateway, created_at, updated_at 
          FROM payments 
          ORDER BY created_at DESC 
          LIMIT 10";

$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Payment ID</th><th>Student ID</th><th>Amount</th><th>Status</th><th>Type</th><th>Purchase Order ID</th><th>Transaction ID</th><th>Gateway</th><th>Created</th><th>Updated</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['payment_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['student_id']) . "</td>";
        echo "<td>" . number_format($row['amount'], 2) . "</td>";
        echo "<td><strong style='color: " . ($row['status'] == 'completed' ? 'green' : ($row['status'] == 'pending' ? 'orange' : 'red')) . "'>" . ucfirst($row['status']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($row['payment_type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['purchase_order_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['transaction_id'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['payment_gateway']) . "</td>";
        echo "<td>" . date('d M Y H:i', strtotime($row['created_at'])) . "</td>";
        echo "<td>" . date('d M Y H:i', strtotime($row['updated_at'])) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No payment records found.</p>";
}

// Check for pending payments specifically
echo "<h2>Pending Payments Only</h2>";

$pending_query = "SELECT payment_id, student_id, amount, status, purchase_order_id, created_at 
                  FROM payments 
                  WHERE status = 'pending' 
                  ORDER BY created_at DESC";

$pending_result = $conn->query($pending_query);

if ($pending_result && $pending_result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Payment ID</th><th>Student ID</th><th>Amount</th><th>Purchase Order ID</th><th>Created</th></tr>";
    
    while ($row = $pending_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['payment_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['student_id']) . "</td>";
        echo "<td>" . number_format($row['amount'], 2) . "</td>";
        echo "<td>" . htmlspecialchars($row['purchase_order_id']) . "</td>";
        echo "<td>" . date('d M Y H:i', strtotime($row['created_at'])) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No pending payments found - this is good!</p>";
}

// Check error logs
echo "<h2>Recent Error Logs</h2>";
$log_file = __DIR__ . '/logs/payment_process_error.log';
if (file_exists($log_file)) {
    $logs = file_get_contents($log_file);
    $recent_logs = implode("\n", array_slice(explode("\n", $logs), -20)); // Last 20 lines
    echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ccc;'>" . htmlspecialchars($recent_logs) . "</pre>";
} else {
    echo "<p>No error log file found.</p>";
}
?>
