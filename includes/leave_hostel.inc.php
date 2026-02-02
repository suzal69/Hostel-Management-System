<?php
require 'config.inc.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['leave_hostel'])) {
    
    $student_id = $_POST['student_id'] ?? '';
    $room_id = $_POST['room_id'] ?? '';
    
    if (empty($student_id) || empty($room_id)) {
        $_SESSION['error'] = "Invalid request. Please try again.";
        header("Location: ../student_room_details.php");
        exit();
    }
    
    // Start transaction for data integrity
    mysqli_begin_transaction($conn);
    
    try {
        // Step 1: Get current bed allocation info
        $query_get_allocation = "SELECT allocation_id, bed_number FROM bed_allocation 
                                 WHERE student_id = ? AND room_id = ? AND is_active = 1";
        $stmt_get = mysqli_prepare($conn, $query_get_allocation);
        if (!$stmt_get) {
            throw new Exception("Failed to prepare allocation query.");
        }
        
        mysqli_stmt_bind_param($stmt_get, "si", $student_id, $room_id);
        mysqli_stmt_execute($stmt_get);
        $result_get = mysqli_stmt_get_result($stmt_get);
        
        if ($row_get = mysqli_fetch_assoc($result_get)) {
            $allocation_id = $row_get['allocation_id'];
            $bed_number = $row_get['bed_number'];
        } else {
            throw new Exception("No active bed allocation found for this student.");
        }
        mysqli_stmt_close($stmt_get);
        
        // Step 2: Delete the bed allocation record entirely (to avoid foreign key constraint issues)
        $query_delete_allocation = "DELETE FROM bed_allocation WHERE student_id = ? AND room_id = ? AND is_active = 1";
        $stmt_delete = mysqli_prepare($conn, $query_delete_allocation);
        if (!$stmt_delete) {
            throw new Exception("Failed to prepare allocation delete query.");
        }
        
        mysqli_stmt_bind_param($stmt_delete, "si", $student_id, $room_id);
        if (!mysqli_stmt_execute($stmt_delete)) {
            throw new Exception("Error deleting bed allocation: " . mysqli_error($conn));
        }
        mysqli_stmt_close($stmt_delete);
        
        // Step 3: Delete from application table first (due to foreign key constraint)
        $query_delete_application = "DELETE FROM Application WHERE Student_id = ?";
        $stmt_delete_app = mysqli_prepare($conn, $query_delete_application);
        if (!$stmt_delete_app) {
            throw new Exception("Failed to prepare application delete query.");
        }
        
        mysqli_stmt_bind_param($stmt_delete_app, "s", $student_id);
        if (!mysqli_stmt_execute($stmt_delete_app)) {
            throw new Exception("Error deleting from application table: " . mysqli_error($conn));
        }
        mysqli_stmt_close($stmt_delete_app);
        
        // Step 4: Update student record to remove room and hostel assignment (set to NULL for foreign key constraints)
        $query_update_student = "UPDATE Student 
                                 SET Room_id = NULL, Hostel_id = NULL 
                                 WHERE Student_id = ?";
        $stmt_student = mysqli_prepare($conn, $query_update_student);
        if (!$stmt_student) {
            throw new Exception("Failed to prepare student update query.");
        }
        
        mysqli_stmt_bind_param($stmt_student, "s", $student_id);
        if (!mysqli_stmt_execute($stmt_student)) {
            throw new Exception("Error updating student record: " . mysqli_error($conn));
        }
        mysqli_stmt_close($stmt_student);
        
        // Step 5: Update room occupancy (decrease by 1)
        $query_update_occupancy = "UPDATE Room 
                                   SET current_occupancy = 
                                   (SELECT COUNT(*) FROM bed_allocation ba 
                                    WHERE ba.room_id = ? AND ba.is_active = 1)
                                   WHERE Room_id = ?";
        $stmt_occupancy = mysqli_prepare($conn, $query_update_occupancy);
        if (!$stmt_occupancy) {
            throw new Exception("Failed to prepare occupancy update query.");
        }
        
        mysqli_stmt_bind_param($stmt_occupancy, "ii", $room_id, $room_id);
        if (!mysqli_stmt_execute($stmt_occupancy)) {
            throw new Exception("Error updating room occupancy: " . mysqli_error($conn));
        }
        mysqli_stmt_close($stmt_occupancy);
        
        // Step 6: Check if room is now empty and update accordingly
        $query_check_occupancy = "SELECT current_occupancy FROM Room WHERE Room_id = ?";
        $stmt_check = mysqli_prepare($conn, $query_check_occupancy);
        if ($stmt_check) {
            mysqli_stmt_bind_param($stmt_check, "i", $room_id);
            mysqli_stmt_execute($stmt_check);
            $result_check = mysqli_stmt_get_result($stmt_check);
            if ($row_check = mysqli_fetch_assoc($result_check)) {
                $current_occupancy = $row_check['current_occupancy'];
                
                // If no one is in the room, ensure room is marked as available
                if ($current_occupancy == 0) {
                    $query_reset_room = "UPDATE Room SET current_occupancy = 0 WHERE Room_id = ?";
                    $stmt_reset = mysqli_prepare($conn, $query_reset_room);
                    if ($stmt_reset) {
                        mysqli_stmt_bind_param($stmt_reset, "i", $room_id);
                        mysqli_stmt_execute($stmt_reset);
                        mysqli_stmt_close($stmt_reset);
                    }
                }
            }
            mysqli_stmt_close($stmt_check);
        }
        
        // Commit transaction
        mysqli_commit($conn);
        
        // Clear session variables related to room
        unset($_SESSION['room_id']);
        unset($_SESSION['hostel_id']);
        
        $_SESSION['success'] = "You have successfully left the hostel. Your room allocation has been removed.";
        
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        $_SESSION['error'] = "Error leaving hostel: " . $e->getMessage();
    }
    
} else {
    $_SESSION['error'] = "Invalid request method.";
}

// Redirect back to student room details
header("Location: ../student_room_details.php");
exit();
?>
