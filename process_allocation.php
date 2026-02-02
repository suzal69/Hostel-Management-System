<?php
require_once 'includes/config.inc.php';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $application_id = mysqli_real_escape_string($conn, $_POST['application_id']);
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    $room_id = mysqli_real_escape_string($conn, $_POST['room_id']);
    $start_date = mysqli_real_escape_string($conn, $_POST['start_date']);
    $end_date = mysqli_real_escape_string($conn, $_POST['end_date']);
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // 1. Get room details
        $room_query = "SELECT * FROM room WHERE Room_id = ? FOR UPDATE";
        $stmt = mysqli_prepare($conn, $room_query);
        mysqli_stmt_bind_param($stmt, "i", $room_id);
        mysqli_stmt_execute($stmt);
        $room_result = mysqli_stmt_get_result($stmt);
        $room = mysqli_fetch_assoc($room_result);
        
        if (!$room) {
            throw new Exception("Room not found");
        }
        
        // 2. Check if room has available beds
        $occupancy_query = "SELECT COUNT(*) as occupied_beds FROM bed_allocation WHERE room_id = ? AND is_active = 1";
        $stmt = mysqli_prepare($conn, $occupancy_query);
        mysqli_stmt_bind_param($stmt, "i", $room_id);
        mysqli_stmt_execute($stmt);
        $occupancy_result = mysqli_stmt_get_result($stmt);
        $occupancy = mysqli_fetch_assoc($occupancy_result);
        $occupied_beds = $occupancy['occupied_beds'];
        
        if ($occupied_beds >= $room['bed_capacity']) {
            throw new Exception("Room is already full");
        }
        
        // 3. Calculate price for this allocation
        $new_occupancy = $occupied_beds + 1;
        $price_query = "SELECT r.base_price, pr.price_multiplier 
                        FROM room r 
                        LEFT JOIN pricing_rules pr ON r.bed_capacity = pr.bed_capacity AND pr.occupancy_count = ?
                        WHERE r.Room_id = ?";
        $stmt = mysqli_prepare($conn, $price_query);
        mysqli_stmt_bind_param($stmt, "ii", $new_occupancy, $room_id);
        mysqli_stmt_execute($stmt);
        $price_result = mysqli_stmt_get_result($stmt);
        $price_data = mysqli_fetch_assoc($price_result);
        
        $multiplier = $price_data['price_multiplier'] ?: 1.0;
        $allocation_price = $price_data['base_price'] * $multiplier;
        
        // 4. Find next available bed number
        $bed_query = "SELECT bed_number FROM bed_allocation WHERE room_id = ? AND is_active = 1 ORDER BY bed_number";
        $stmt = mysqli_prepare($conn, $bed_query);
        mysqli_stmt_bind_param($stmt, "i", $room_id);
        mysqli_stmt_execute($stmt);
        $bed_result = mysqli_stmt_get_result($stmt);
        
        $occupied_bed_numbers = [];
        while ($bed_row = mysqli_fetch_assoc($bed_result)) {
            $occupied_bed_numbers[] = $bed_row['bed_number'];
        }
        
        $bed_number = 1;
        while (in_array($bed_number, $occupied_bed_numbers)) {
            $bed_number++;
        }
        
        if ($bed_number > $room['bed_capacity']) {
            throw new Exception("No available beds in this room");
        }
        
        // 5. Create bed allocation
        $allocation_query = "INSERT INTO bed_allocation (room_id, student_id, bed_number, allocation_price, start_date, end_date, is_active) 
                           VALUES (?, ?, ?, ?, ?, ?, 1)";
        $stmt = mysqli_prepare($conn, $allocation_query);
        mysqli_stmt_bind_param($stmt, "isidss", $room_id, $student_id, $bed_number, $allocation_price, $start_date, $end_date);
        mysqli_stmt_execute($stmt);
        
        // 6. Update room occupancy
        $update_room_query = "UPDATE room SET current_occupancy = ? WHERE Room_id = ?";
        $stmt = mysqli_prepare($conn, $update_room_query);
        mysqli_stmt_bind_param($stmt, "ii", $new_occupancy, $room_id);
        mysqli_stmt_execute($stmt);
        
        // 7. Update application status
        $update_app_query = "UPDATE application SET Application_status = 0, Room_No = ? WHERE Application_id = ?";
        $stmt = mysqli_prepare($conn, $update_app_query);
        mysqli_stmt_bind_param($stmt, "ii", $room['Room_No'], $application_id);
        mysqli_stmt_execute($stmt);
        
        // 8. Update student record
        $update_student_query = "UPDATE student SET Hostel_id = ?, Room_id = ?, start_date = ?, end_date = ? WHERE Student_id = ?";
        $stmt = mysqli_prepare($conn, $update_student_query);
        mysqli_stmt_bind_param($stmt, "iisss", $room['Hostel_id'], $room_id, $start_date, $end_date, $student_id);
        mysqli_stmt_execute($stmt);
        
        // 9. Update hostel student count
        $update_hostel_query = "UPDATE hostel SET No_of_students = No_of_students + 1 WHERE Hostel_id = ?";
        $stmt = mysqli_prepare($conn, $update_hostel_query);
        mysqli_stmt_bind_param($stmt, "i", $room['Hostel_id']);
        mysqli_stmt_execute($stmt);
        
        // 10. Send notification to student
        $notification_query = "INSERT INTO message (sender_id, receiver_id, hostel_id, subject_h, message, msg_date, msg_time, read_status) 
                              VALUES ('system', ?, ?, 'Room Allocation', ?, CURDATE(), CURTIME(), 0)";
        $message = "You have been allocated to Room {$room['Room_No']}, Bed {$bed_number}. Price: {$allocation_price}. Your allocation is from {$start_date} to {$end_date}.";
        $stmt = mysqli_prepare($conn, $notification_query);
        mysqli_stmt_bind_param($stmt, "sis", $student_id, $room['Hostel_id'], $message);
        mysqli_stmt_execute($stmt);
        
        // Commit transaction
        mysqli_commit($conn);
        
        // Redirect with success message
        header("Location: allocate_room.php?allocation_success=1&room={$room['Room_No']}&bed={$bed_number}&price={$allocation_price}");
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction
        mysqli_rollback($conn);
        
        // Redirect with error message
        header("Location: allocate_room.php?allocation_error=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    // Redirect if not POST request
    header("Location: allocate_room.php");
    exit();
}
?>
