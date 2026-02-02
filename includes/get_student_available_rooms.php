<?php
require 'config.inc.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get student's hostel ID
$student_hostel_id = $_SESSION['hostel_id'] ?? null;
if (!$student_hostel_id) {
    echo json_encode(['success' => false, 'error' => 'Student hostel not assigned']);
    exit();
}

// Get student's current room ID to exclude it from available rooms
$student_room_id = $_SESSION['room_id'] ?? null;

// Get all rooms with available capacity in the student's hostel (excluding their current room)
$query = "SELECT r.Room_id, r.Room_No, r.bed_capacity,
                 (SELECT COUNT(*) FROM bed_allocation ba WHERE ba.room_id = r.Room_id AND ba.is_active = 1) as current_occupancy
          FROM Room r 
          WHERE r.Hostel_id = ? 
          AND r.bed_capacity > (SELECT COUNT(*) FROM bed_allocation ba WHERE ba.room_id = r.Room_id AND ba.is_active = 1)";

// Add condition to exclude student's current room if they have one
if ($student_room_id) {
    $query .= " AND r.Room_id != ?";
}

$query .= " ORDER BY r.Room_No";

$stmt = mysqli_prepare($conn, $query);
if ($stmt) {
    if ($student_room_id) {
        mysqli_stmt_bind_param($stmt, "ii", $student_hostel_id, $student_room_id);
    } else {
        mysqli_stmt_bind_param($stmt, "i", $student_hostel_id);
    }
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
