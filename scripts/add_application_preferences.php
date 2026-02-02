<?php
require 'config.inc.php';

// Get hostel name from URL parameter
$hostelName = $_GET['hostel_name'] ?? null;

if (!$hostelName) {
    echo json_encode(['success' => false, 'error' => 'Hostel name not provided']);
    exit();
}

// Get hostel ID from hostel name
$query_hostel = "SELECT Hostel_id FROM Hostel WHERE Hostel_name = ?";
$stmt_hostel = mysqli_prepare($conn, $query_hostel);
if ($stmt_hostel) {
    mysqli_stmt_bind_param($stmt_hostel, "s", $hostelName);
    mysqli_stmt_execute($stmt_hostel);
    $result_hostel = mysqli_stmt_get_result($stmt_hostel);
    
    if (mysqli_num_rows($result_hostel) == 0) {
        echo json_encode(['success' => false, 'error' => 'Hostel not found']);
        mysqli_stmt_close($stmt_hostel);
        exit();
    }
    
    $hostel_data = mysqli_fetch_assoc($result_hostel);
    $hostel_id = $hostel_data['Hostel_id'];
    mysqli_stmt_close($stmt_hostel);
    
    // Get all rooms with available capacity in this hostel
    $query_rooms = "SELECT r.Room_id, r.Room_No, r.bed_capacity,
                           (SELECT COUNT(*) FROM bed_allocation ba WHERE ba.room_id = r.Room_id AND ba.is_active = 1) as current_occupancy
                    FROM Room r 
                    WHERE r.Hostel_id = ? AND r.bed_capacity > 
                    (SELECT COUNT(*) FROM bed_allocation ba WHERE ba.room_id = r.Room_id AND ba.is_active = 1)
                    ORDER BY r.Room_No";
    
    $stmt_rooms = mysqli_prepare($conn, $query_rooms);
    if ($stmt_rooms) {
        mysqli_stmt_bind_param($stmt_rooms, "i", $hostel_id);
        mysqli_stmt_execute($stmt_rooms);
        $result_rooms = mysqli_stmt_get_result($stmt_rooms);
        
        $rooms = [];
        while ($row = mysqli_fetch_assoc($result_rooms)) {
            $available_beds = $row['bed_capacity'] - $row['current_occupancy'];
            $rooms[] = [
                'room_id' => $row['Room_id'],
                'room_no' => $row['Room_No'],
                'available_beds' => $available_beds
            ];
        }
        
        echo json_encode(['success' => true, 'rooms' => $rooms]);
        mysqli_stmt_close($stmt_rooms);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database query failed']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Database query failed']);
}

mysqli_close($conn);
?>