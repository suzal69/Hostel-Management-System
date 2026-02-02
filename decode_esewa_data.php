<?php
// Decode the eSewa data
$encoded_data = "eyJ0cmFuc2FjdGlvbl9jb2RlIjoiMDAwRFg3VCIsInN0YXR1cyI6IkNPTVBMRVRFIiwidG90YWxfYW1vdW50IjoiMjUwMC4wIiwidHJhbnNhY3Rpb25fdXVpZCI6IlRYTi0xNzY5MDkyMTY3LTk0OTkiLCJwcm9kdWN0X2NvZGUiOiJFUEFZVEVTVCIsInNpZ25lZF9maWVsZF9uYW1lcyI6InRyYW5zYWN0aW9uX2NvZGUsc3RhdHVzLHRvdGFsX2Ftb3VudCx0cmFuc2FjdGlvbl91dWlkLHByb2R1Y3RfY29kZSxzaWduZWRfZmllbGRfbmFtZXMiLCJzaWduYXR1cmUiOiJMYkRrc1dIeVhKMU1zZ2FEczlWU0NwR3ZRK2tjWCtEVDA2Tm55ejkweGZVPSJ9";

$decoded_json = base64_decode($encoded_data);
$response = json_decode($decoded_json, true);

echo "<h2>eSewa Callback Data Decoded</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Field</th><th>Value</th><th>Description</th></tr>";

$fields = [
    'transaction_code' => 'eSewa transaction reference code',
    'status' => 'Payment status (COMPLETE = successful)',
    'total_amount' => 'Payment amount',
    'transaction_uuid' => 'Unique transaction identifier',
    'product_code' => 'Product/service identifier',
    'signed_field_names' => 'Fields that were signed for security',
    'signature' => 'Security signature for verification'
];

foreach ($fields as $field => $description) {
    $value = $response[$field] ?? 'Not present';
    echo "<tr>";
    echo "<td><strong>$field</strong></td>";
    echo "<td>" . htmlspecialchars($value) . "</td>";
    echo "<td>$description</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h2>Raw Decoded JSON</h2>";
echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ccc;'>";
echo json_encode($response, JSON_PRETTY_PRINT);
echo "</pre>";

echo "<h2>Key Information</h2>";
echo "<ul>";
echo "<li><strong>Payment Status:</strong> " . ($response['status'] ?? 'Unknown') . " (COMPLETE means successful)</li>";
echo "<li><strong>Amount:</strong> NPR " . ($response['total_amount'] ?? 'Unknown') . "</li>";
echo "<li><strong>Transaction Code:</strong> " . ($response['transaction_code'] ?? 'Unknown') . "</li>";
echo "<li><strong>Transaction UUID:</strong> " . ($response['transaction_uuid'] ?? 'Unknown') . "</li>";
echo "</ul>";
?>
