<?php
require_once __DIR__ . '/../includes/config.inc.php';

header('Content-Type: application/json');

$hostel_name = isset($_GET['hostel_name']) ? $_GET['hostel_name'] : '';

if (empty($hostel_name)) {
    echo json_encode(['success' => false, 'message' => 'Hostel name is required']);
    exit;
}

// Get rooms from the specified hostel that have available beds
$query = "SELECT r.Room_id, r.Room_No, r.bed_capacity, r.current_occupancy,
                (r.bed_capacity - r.current_occupancy) as available_beds, h.Hostel_name
         FROM Room r
         JOIN Hostel h ON r.Hostel_id = h.Hostel_id
         WHERE h.Hostel_name = ? AND r.bed_capacity > r.current_occupancy
         ORDER BY r.Room_No";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $hostel_name);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$rooms = [];
while ($row = mysqli_fetch_assoc($result)) {
    $rooms[] = $row;
}

mysqli_stmt_close($stmt);

if (count($rooms) > 0) {
    echo json_encode(['success' => true, 'rooms' => $rooms]);
} else {
    echo json_encode(['success' => false, 'message' => 'No available rooms found in this hostel']);
}
?>
