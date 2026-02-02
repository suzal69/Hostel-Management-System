<?php
require 'config.inc.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$roomId = $_GET['room_id'] ?? $_POST['room_id'] ?? null;
if (!$roomId) {
    echo json_encode(['success' => false, 'error' => 'Room ID not provided']);
    exit();
}

// Get room details including hostel information
$query = "SELECT r.bed_capacity, r.Hostel_id, ba.bed_number
          FROM Room r 
          LEFT JOIN bed_allocation ba ON r.Room_id = ba.room_id AND ba.is_active = 1
          WHERE r.Room_id = ?";

$stmt = mysqli_prepare($conn, $query);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $roomId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $occupiedBeds = [];
    $bedCapacity = 0;
    $hostelId = 0;
    
    while ($row = mysqli_fetch_assoc($result)) {
        $bedCapacity = $row['bed_capacity'];
        $hostelId = $row['Hostel_id'];
        if ($row['bed_number']) {
            $occupiedBeds[] = $row['bed_number'];
        }
    }
    
    // Special case for hostel 3: always show beds 1, 2, and 3
    if ($hostelId == 3) {
        $availableBeds = [];
        for ($i = 1; $i <= 3; $i++) {
            if (!in_array($i, $occupiedBeds)) {
                $availableBeds[] = ['bed_number' => $i];
            }
        }
    } else {
        // Normal logic for other hostels
        $availableBeds = [];
        for ($i = 1; $i <= $bedCapacity; $i++) {
            if (!in_array($i, $occupiedBeds)) {
                $availableBeds[] = ['bed_number' => $i];
            }
        }
    }
    
    echo json_encode(['success' => true, 'beds' => $availableBeds]);
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['success' => false, 'error' => 'Database query failed']);
}

mysqli_close($conn);
?>
