<?php
// Start the session at the very beginning
session_start();

// includes/change_room.inc.php (REVISED FOR TRANSACTION, ERROR HANDLING, AND SESSIONS)

// Ensure error reporting is enabled for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../includes/config.inc.php';

// Database connection check
if (!isset($conn)) {
// Use session to store error message before redirecting
    $_SESSION['error_message'] = 'Database connection not available.';
    echo "<script type='text/javascript'>window.location.href = '../change_room.php';</script>";
    exit();
}

if (isset($_POST['submit'])) {
    
    $roll_no = $_POST['roll_no'] ?? null;
    $old_room_no = $_POST['old_room_no'] ?? null;
    $new_room_no = $_POST['new_room_no'] ?? null;
    // Session variable check for hostel_id
    $hostel_id = $_SESSION['hostel_id'] ?? null;

    if (!$roll_no || !$old_room_no || !$new_room_no || !$hostel_id) {
        // Store missing info error in session
        $_SESSION['error_message'] = 'Missing required information for room change.';
        echo "<script type='text/javascript'>window.location.href = '../change_room.php';</script>";
        exit();
    }
    
    // Convert variables to expected types
    $hostel_id_int = (int)$hostel_id;
    $old_room_no_int = (int)$old_room_no;
    $new_room_no_int = (int)$new_room_no;

    // Start Transaction for data integrity
    mysqli_begin_transaction($conn);
    $success = true;
    
    // **Success message will now be stored in the session**
    $old_room_id = null;
    $new_room_id = null;

    try {
        // Step 1: Get OLD room_id
        $query_old_room = "SELECT Room_id FROM Room WHERE Room_No = ? AND Hostel_id = ?";
        $stmt_old_room = mysqli_prepare($conn, $query_old_room);
        if (!$stmt_old_room) {
            throw new Exception("Failed to prepare query for old room ID.");
        }

        mysqli_stmt_bind_param($stmt_old_room, "ii", $old_room_no_int, $hostel_id_int);
        mysqli_stmt_execute($stmt_old_room);
        $result_old_room = mysqli_stmt_get_result($stmt_old_room);
        $row_old_room = mysqli_fetch_assoc($result_old_room);
        mysqli_stmt_close($stmt_old_room);

        if (!$row_old_room) {
            throw new Exception("Old room number ($old_room_no) not found in the specified hostel.");
        }
        $old_room_id = $row_old_room['Room_id'];
        
        // Step 2: Get NEW room_id
        $query_new_room = "SELECT Room_id FROM Room WHERE Room_No = ? AND Hostel_id = ?";
        $stmt_new_room = mysqli_prepare($conn, $query_new_room);
        if (!$stmt_new_room) {
            throw new Exception("Failed to prepare query for new room ID.");
        }

        mysqli_stmt_bind_param($stmt_new_room, "ii", $new_room_no_int, $hostel_id_int);
        mysqli_stmt_execute($stmt_new_room);
        $result_new_room = mysqli_stmt_get_result($stmt_new_room);
        $row_new_room = mysqli_fetch_assoc($result_new_room);
        mysqli_stmt_close($stmt_new_room);

        if (!$row_new_room) {
            throw new Exception("New room number ($new_room_no) not found in the specified hostel.");
        }
        $new_room_id = $row_new_room['Room_id'];


        // Step 3: Update student's room
        $start_date = date("Y-m-d");
        $end_date = date('Y-m-d', strtotime('+1 year'));
        $query_update_student = "UPDATE Student SET Room_id = ? WHERE Student_id = ? AND Room_id = ?";
        $stmt_update_student = mysqli_prepare($conn, $query_update_student);

        if ($stmt_update_student) {
            mysqli_stmt_bind_param($stmt_update_student, "isi", $new_room_id, $roll_no, $old_room_id);
            if (!mysqli_stmt_execute($stmt_update_student)) {
                throw new Exception("Error updating student record: " . mysqli_error($conn));
            }
            if (mysqli_stmt_affected_rows($stmt_update_student) === 0) {
                throw new Exception("Student not found or is already in the new room.");
            }
            mysqli_stmt_close($stmt_update_student);
        } else {
            throw new Exception("Failed to prepare student update query: " . mysqli_error($conn));
        }

        // Step 4: Update old room to be empty (Allocated = '0')
        $query_update_old_room = "UPDATE Room SET Allocated = '0', start_date = NULL, end_date = NULL WHERE Room_id = ?";
        $stmt_update_old_room = mysqli_prepare($conn, $query_update_old_room);
        if ($stmt_update_old_room) {
            mysqli_stmt_bind_param($stmt_update_old_room, "i", $old_room_id);
            if (!mysqli_stmt_execute($stmt_update_old_room)) {
                throw new Exception("Error freeing up old room.");
            }
            mysqli_stmt_close($stmt_update_old_room);
        } else {
            throw new Exception("Failed to prepare old room update query.");
        }


        // Step 5: Update new room to be allocated (Allocated = '1')
        $query_update_new_room = "UPDATE Room SET Allocated = '1', start_date = ?, end_date = ? WHERE Room_id = ?";
        $stmt_update_new_room = mysqli_prepare($conn, $query_update_new_room);
        if ($stmt_update_new_room) {
            mysqli_stmt_bind_param($stmt_update_new_room, "ssi", $start_date, $end_date, $new_room_id);
            if (!mysqli_stmt_execute($stmt_update_new_room)) {
                throw new Exception("Error allocating new room.");
            }
            if (mysqli_stmt_affected_rows($stmt_update_new_room) === 0) {
                throw new Exception("New room was unexpectedly already allocated or missing.");
            }
            mysqli_stmt_close($stmt_update_new_room);
        } else {
            throw new Exception("Failed to prepare new room update query.");
        }
        
        // If everything succeeded, commit the transaction
        mysqli_commit($conn);

        // --- SESSION VARIABLE FIX ---
        // Set session variables for contact_manager.php, similar to allocate_room.php
        // This ensures the student and new room number are pre-selected.
        $_SESSION['last_allocated_student_id'] = $roll_no;
        $_SESSION['last_allocated_room_no'] = $new_room_no;
        $success = true;

        // Get the hostel manager ID from the session for sending messages
        $hostel_man_id = $_SESSION['hostel_man_id'] ?? null;
        if (!$hostel_man_id) {
            error_log("change_room.inc.php: Hostel Manager ID not found in session.");
            // Do not throw exception here, as transaction is committed, just log it.
        }

        // --- Message Notification Logic (Database Insert) ---
        // This part remains the same as it correctly writes the notification to the DB.
        $manSql = "SELECT Hostel_man_id FROM hostel_manager WHERE Hostel_id = ? LIMIT 1";
        $mstmt = mysqli_prepare($conn, $manSql);
        $managerId = null;
        if ($mstmt) {
            mysqli_stmt_bind_param($mstmt, "i", $hostel_id_int);
            mysqli_stmt_execute($mstmt);
            $mres = mysqli_stmt_get_result($mstmt);
            $mrow = mysqli_fetch_assoc($mres);
            $managerId = $mrow['Hostel_man_id'] ?? null;
            mysqli_stmt_close($mstmt);
        }
        
        $today_date = date("Y-m-d");
        $time = date("h:i A");
        $sysSender = 'system';
    } catch (Exception $e) {
        // If anything failed, rollback the transaction
        mysqli_rollback($conn);
        error_log("Room Change Transaction Failed: " . $e->getMessage());
        
        // Store error message in session for redirect
        $_SESSION['error_message'] = "Error changing room. " . $e->getMessage();
        $success = false;
    }
    
    // Final redirect based on outcome, relying solely on session now
    if ($success) {
        // --- REDIRECT FIX ---
        // Redirect to contact_manager.php to pre-fill the message form.
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