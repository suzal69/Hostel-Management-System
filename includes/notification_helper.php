<?php
/**
 * Notification Helper Functions
 * Handles marking notifications as read for leave and complaint responses
 */

require_once __DIR__ . '/config.inc.php';

/**
 * Mark leave notifications as read for a specific student
 * 
 * @param string $student_id Student ID
 * @param mysqli $conn Database connection
 * @return bool Success status
 */
function markLeaveNotificationsRead($student_id, $conn) {
    // First check if the notification_read column exists
    $check_column_query = "SHOW COLUMNS FROM leave_applications LIKE 'notification_read'";
    $result = mysqli_query($conn, $check_column_query);
    
    if (mysqli_num_rows($result) == 0) {
        // Column doesn't exist, add it
        $alter_query = "ALTER TABLE leave_applications ADD COLUMN notification_read TINYINT(1) DEFAULT 0";
        mysqli_query($conn, $alter_query);
    }
    
    // Mark all approved/rejected leave applications as read for this student
    $update_query = "UPDATE leave_applications 
                     SET notification_read = 1 
                     WHERE student_id = ? AND status IN ('approved', 'rejected') AND notification_read = 0";
    
    $stmt = mysqli_prepare($conn, $update_query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $student_id);
        $success = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $success;
    }
    
    return false;
}

/**
 * Mark complaint notifications as read for a specific student
 * 
 * @param string $student_id Student ID
 * @param mysqli $conn Database connection
 * @return bool Success status
 */
function markComplaintNotificationsRead($student_id, $conn) {
    // First check if the notification_read column exists
    $check_column_query = "SHOW COLUMNS FROM complaints LIKE 'notification_read'";
    $result = mysqli_query($conn, $check_column_query);
    
    if (mysqli_num_rows($result) == 0) {
        // Column doesn't exist, add it
        $alter_query = "ALTER TABLE complaints ADD COLUMN notification_read TINYINT(1) DEFAULT 0";
        mysqli_query($conn, $alter_query);
    }
    
    // Mark all resolved/completed/in_progress complaints as read for this student
    $update_query = "UPDATE complaints 
                     SET notification_read = 1 
                     WHERE student_id = ? AND status IN ('resolved', 'completed', 'in_progress') AND notification_read = 0";
    
    $stmt = mysqli_prepare($conn, $update_query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $student_id);
        $success = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $success;
    }
    
    return false;
}

/**
 * Set notification as unread when manager responds to leave application
 * 
 * @param int $leave_id Leave application ID
 * @param mysqli $conn Database connection
 * @return bool Success status
 */
function setLeaveNotificationUnread($leave_id, $conn) {
    // Ensure column exists
    $check_column_query = "SHOW COLUMNS FROM leave_applications LIKE 'notification_read'";
    $result = mysqli_query($conn, $check_column_query);
    
    if (mysqli_num_rows($result) == 0) {
        $alter_query = "ALTER TABLE leave_applications ADD COLUMN notification_read TINYINT(1) DEFAULT 0";
        mysqli_query($conn, $alter_query);
    }
    
    $update_query = "UPDATE leave_applications SET notification_read = 0 WHERE leave_id = ?";
    $stmt = mysqli_prepare($conn, $update_query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $leave_id);
        $success = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $success;
    }
    
    return false;
}

/**
 * Set notification as unread when manager responds to complaint
 * 
 * @param int $complaint_id Complaint ID
 * @param mysqli $conn Database connection
 * @return bool Success status
 */
function setComplaintNotificationUnread($complaint_id, $conn) {
    // Ensure column exists
    $check_column_query = "SHOW COLUMNS FROM complaints LIKE 'notification_read'";
    $result = mysqli_query($conn, $check_column_query);
    
    if (mysqli_num_rows($result) == 0) {
        $alter_query = "ALTER TABLE complaints ADD COLUMN notification_read TINYINT(1) DEFAULT 0";
        mysqli_query($conn, $alter_query);
    }
    
    $update_query = "UPDATE complaints SET notification_read = 0 WHERE complaint_id = ?";
    $stmt = mysqli_prepare($conn, $update_query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $complaint_id);
        $success = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $success;
    }
    
    return false;
}

?>
