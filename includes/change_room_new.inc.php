<?php
// Start the session at the very beginning
session_start();

// includes/change_room_new.inc.php (UPDATED FOR BED-BASED ALLOCATION SYSTEM)

// Ensure error reporting is enabled for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../includes/config.inc.php';

// Database connection check
if (!isset($conn)) {
    $_SESSION['error_message'] = 'Database connection not available.';
    echo "<script type='text/javascript'>window.location.href = '../change_room.php';</script>";
    exit();
}

if (isset($_POST['submit'])) {
    
    $roll_no = $_POST['roll_no'] ?? null;
    $new_room_id = $_POST['new_room_no'] ?? null;
    $new_bed_no = $_POST['new_bed_no'] ?? null;
    $hostel_id = $_SESSION['hostel_id'] ?? null;

    if (!$roll_no || !$new_room_id || !$new_bed_no || !$hostel_id) {
        $_SESSION['error_message'] = 'Missing required information for room change.';
        echo "<script type='text/javascript'>window.location.href = '../change_room.php';</script>";
        exit();
    }
    
    // Convert variables to expected types
    $hostel_id_int = (int)$hostel_id;
    $new_room_id_int = (int)$new_room_id;
    $new_bed_no_int = (int)$new_bed_no;

    // Start Transaction for data integrity
    mysqli_begin_transaction($conn);
    $success = true;
    
    try {
        // Step 1: Get student's current bed allocation
        $query_current_allocation = "SELECT ba.allocation_id, ba.room_id as old_room_id, ba.bed_number as old_bed_no
                                    FROM bed_allocation ba
                                    WHERE ba.student_id = ? AND ba.is_active = 1";
        $stmt_current = mysqli_prepare($conn, $query_current_allocation);
        if (!$stmt_current) {
            throw new Exception("Failed to prepare query for current allocation.");
        }

        mysqli_stmt_bind_param($stmt_current, "s", $roll_no);
        mysqli_stmt_execute($stmt_current);
        $result_current = mysqli_stmt_get_result($stmt_current);
        $current_allocation = mysqli_fetch_assoc($result_current);
        mysqli_stmt_close($stmt_current);

        if (!$current_allocation) {
            throw new Exception("Student is not currently allocated to any bed.");
        }

        $old_room_id = $current_allocation['old_room_id'];
        $old_bed_no = $current_allocation['old_bed_no'];
        $allocation_id = $current_allocation['allocation_id'];

        // Step 2: Verify the new room belongs to the manager's hostel
        $query_verify_room = "SELECT Room_No, bed_capacity FROM Room WHERE Room_id = ? AND Hostel_id = ?";
        $stmt_verify = mysqli_prepare($conn, $query_verify_room);
        if (!$stmt_verify) {
            throw new Exception("Failed to prepare room verification query.");
        }

        mysqli_stmt_bind_param($stmt_verify, "ii", $new_room_id_int, $hostel_id_int);
        mysqli_stmt_execute($stmt_verify);
        $result_verify = mysqli_stmt_get_result($stmt_verify);
        $room_info = mysqli_fetch_assoc($result_verify);
        mysqli_stmt_close($stmt_verify);

        if (!$room_info) {
            throw new Exception("New room not found in your hostel.");
        }

        // Step 3: Verify the new bed number is within room capacity
        if ($new_bed_no_int > $room_info['bed_capacity']) {
            throw new Exception("Bed number $new_bed_no exceeds room capacity of {$room_info['bed_capacity']}.");
        }

        // Step 4: Check if the new bed is already occupied
        $query_check_bed = "SELECT allocation_id FROM bed_allocation 
                            WHERE room_id = ? AND bed_number = ? AND is_active = 1";
        $stmt_check = mysqli_prepare($conn, $query_check_bed);
        if (!$stmt_check) {
            throw new Exception("Failed to prepare bed check query.");
        }

        mysqli_stmt_bind_param($stmt_check, "ii", $new_room_id_int, $new_bed_no_int);
        mysqli_stmt_execute($stmt_check);
        $result_check = mysqli_stmt_get_result($stmt_check);
        if (mysqli_num_rows($result_check) > 0) {
            throw new Exception("Bed $new_bed_no in the new room is already occupied.");
        }
        mysqli_stmt_close($stmt_check);

        // Step 5: Update the existing bed allocation instead of creating new one
        $query_update_allocation = "UPDATE bed_allocation 
                                   SET room_id = ?, bed_number = ?, start_date = ?, end_date = ?
                                   WHERE allocation_id = ?";
        $stmt_update = mysqli_prepare($conn, $query_update_allocation);
        if (!$stmt_update) {
            throw new Exception("Failed to prepare allocation update query.");
        }

        $start_date = date("Y-m-d");
        $end_date = date('Y-m-d', strtotime('+1 year'));

        mysqli_stmt_bind_param($stmt_update, "iissi", $new_room_id_int, $new_bed_no_int, 
                               $start_date, $end_date, $allocation_id);
        if (!mysqli_stmt_execute($stmt_update)) {
            throw new Exception("Error updating bed allocation: " . mysqli_error($conn));
        }
        mysqli_stmt_close($stmt_update);

        // Step 6: Update student's room_id
        $query_update_student = "UPDATE Student SET Room_id = ? WHERE Student_id = ?";
        $stmt_update_student = mysqli_prepare($conn, $query_update_student);
        if (!$stmt_update_student) {
            throw new Exception("Failed to prepare student update query.");
        }

        mysqli_stmt_bind_param($stmt_update_student, "is", $new_room_id_int, $roll_no);
        if (!mysqli_stmt_execute($stmt_update_student)) {
            throw new Exception("Error updating student record.");
        }
        mysqli_stmt_close($stmt_update_student);

        // Step 7: Update old room occupancy (decrease)
        $query_update_old_room_occupancy = "UPDATE Room SET current_occupancy = 
                                           (SELECT COUNT(*) FROM bed_allocation ba 
                                            WHERE ba.room_id = ? AND ba.is_active = 1)
                                           WHERE Room_id = ?";
        $stmt_old_occupancy = mysqli_prepare($conn, $query_update_old_room_occupancy);
        if ($stmt_old_occupancy) {
            mysqli_stmt_bind_param($stmt_old_occupancy, "ii", $old_room_id, $old_room_id);
            mysqli_stmt_execute($stmt_old_occupancy);
            mysqli_stmt_close($stmt_old_occupancy);
        }

        // Step 8: Update new room occupancy (increase)
        $query_update_new_room_occupancy = "UPDATE Room SET current_occupancy = 
                                           (SELECT COUNT(*) FROM bed_allocation ba 
                                            WHERE ba.room_id = ? AND ba.is_active = 1)
                                           WHERE Room_id = ?";
        $stmt_new_occupancy = mysqli_prepare($conn, $query_update_new_room_occupancy);
        if ($stmt_new_occupancy) {
            mysqli_stmt_bind_param($stmt_new_occupancy, "ii", $new_room_id_int, $new_room_id_int);
            mysqli_stmt_execute($stmt_new_occupancy);
            mysqli_stmt_close($stmt_new_occupancy);
        }

        // If everything succeeded, commit the transaction
        mysqli_commit($conn);

        // Set session variables for contact_manager.php
        $_SESSION['last_allocated_student_id'] = $roll_no;
        $_SESSION['last_allocated_room_no'] = $room_info['Room_No'];
        $_SESSION['room_change_success'] = true;

        $success = true;

    } catch (Exception $e) {
        // If anything failed, rollback the transaction
        mysqli_rollback($conn);
        error_log("Room Change Transaction Failed: " . $e->getMessage());
        
        $_SESSION['error_message'] = "Error changing room. " . $e->getMessage();
        $success = false;
    }
    
    // Final redirect based on outcome
    if ($success) {
        // Redirect to contact_manager.php to send notification
        $redirect_page = "../contact_manager.php?room_change_success=1";
        echo "<script type='text/javascript'>window.location.href = '{$redirect_page}';</script>";
    } else {
        // Redirect to the change_room page to display the error
        $redirect_page = '../change_room.php';
        echo "<script type='text/javascript'>window.location.href = '{$redirect_page}';</script>";
    }
    exit();
}
?>
