<?php
require 'config.inc.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get manager's hostel ID
$hostelId = $_SESSION['hostel_id'] ?? null;
if (!$hostelId) {
    echo json_encode(['success' => false, 'error' => 'Manager hostel not assigned']);
    exit();
}

// Get all rooms with available capacity in the manager's hostel
$query = "SELECT r.Room_id, r.Room_No, r.bed_capacity,
                 (SELECT COUNT(*) FROM bed_allocation ba WHERE ba.room_id = r.Room_id AND ba.is_active = 1) as current_occupancy
          FROM Room r 
          WHERE r.Hostel_id = ? AND r.bed_capacity > 
          (SELECT COUNT(*) FROM bed_allocation ba WHERE ba.room_id = r.Room_id AND ba.is_active = 1)
          ORDER BY r.Room_No";

$stmt = mysqli_prepare($conn, $query);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $hostelId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $rooms = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $available_beds = $row['bed_capacity'] - $row['current_occupancy'];
        $rooms[] = [
            'Room_id' => $row['Room_id'],
            'Room_No' => $row['Room_No'],
            'available_beds' => $available_beds
        ];
    }
    
    echo json_encode(['success' => true, 'rooms' => $rooms]);
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['success' => false, 'error' => 'Database query failed']);
}

mysqli_close($conn);
?>
