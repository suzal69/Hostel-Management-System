<?php
session_start();
require_once 'config.inc.php';
require_once 'notification_helper.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['complaint_id'], $_POST['status'])) {
        $complaintId = (int)$_POST['complaint_id'];
        $status = $_POST['status'];

        // Validate status
        $allowedStatuses = ['open', 'in_progress', 'resolved'];
        if (!in_array($status, $allowedStatuses)) {
            header("Location: ../manager_complaints.php?error=invalidstatus");
            exit();
        }

        $resolveDate = null;
        if ($status === 'resolved') {
            $resolveDate = date('Y-m-d H:i:s');
        }

        // Verify manager session and that the complaint belongs to manager's hostel
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['hostel_man_id'])) {
            header("Location: ../login-hostel_manager.php?error=notloggedin");
            exit();
        }

        $managerHostelId = $_SESSION['hostel_id'] ?? null;
        if (!$managerHostelId) {
            // Fetch from DB as a fallback
            $sql_m = "SELECT Hostel_id FROM hostel_manager WHERE Hostel_man_id = ?";
            $stmt_m = mysqli_stmt_init($conn);
            if (mysqli_stmt_prepare($stmt_m, $sql_m)) {
                mysqli_stmt_bind_param($stmt_m, "i", $_SESSION['hostel_man_id']);
                mysqli_stmt_execute($stmt_m);
                $res_m = mysqli_stmt_get_result($stmt_m);
                $row_m = mysqli_fetch_assoc($res_m);
                $managerHostelId = $row_m['Hostel_id'] ?? null;
                mysqli_stmt_close($stmt_m);
            }
        }

        // Ensure the complaint belongs to this manager's hostel
        $sql_check = "SELECT hostel_id FROM complaints WHERE complaint_id = ?";
        $stmt_check = mysqli_stmt_init($conn);
        if (mysqli_stmt_prepare($stmt_check, $sql_check)) {
            mysqli_stmt_bind_param($stmt_check, "i", $complaintId);
            mysqli_stmt_execute($stmt_check);
            $res_check = mysqli_stmt_get_result($stmt_check);
            $row_check = mysqli_fetch_assoc($res_check);
            mysqli_stmt_close($stmt_check);
            if (!$row_check) {
                header("Location: ../manager_complaints.php?error=invalidinput");
                exit();
            }
            if ($managerHostelId === null || (int)$row_check['hostel_id'] !== (int)$managerHostelId) {
                header("Location: ../manager_complaints.php?error=forbidden");
                exit();
            }
        } else {
            header("Location: ../manager_complaints.php?error=sqlerror");
            exit();
        }

        // Prepare update statement
        if ($resolveDate) {
            $sql = "UPDATE complaints SET status = ?, resolve_date = ? WHERE complaint_id = ?";
            $stmt = mysqli_stmt_init($conn);
            if (mysqli_stmt_prepare($stmt, $sql)) {
                mysqli_stmt_bind_param($stmt, "ssi", $status, $resolveDate, $complaintId);
            } else {
                header("Location: ../manager_complaints.php?error=sqlerror");
                exit();
            }
        } else {
            $sql = "UPDATE complaints SET status = ?, resolve_date = NULL WHERE complaint_id = ?";
            $stmt = mysqli_stmt_init($conn);
            if (mysqli_stmt_prepare($stmt, $sql)) {
                mysqli_stmt_bind_param($stmt, "si", $status, $complaintId);
            } else {
                header("Location: ../manager_complaints.php?error=sqlerror");
                exit();
            }
        }
        
        mysqli_stmt_execute($stmt);

        // Set notification as unread for the student if status is changed from 'open'
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            // Check if previous status was 'open' and new status is different
            $sql_check_status = "SELECT status FROM complaints WHERE complaint_id = ?";
            $stmt_check = mysqli_stmt_init($conn);
            if(mysqli_stmt_prepare($stmt_check, $sql_check_status)){
                mysqli_stmt_bind_param($stmt_check, "i", $complaintId);
                mysqli_stmt_execute($stmt_check);
                $result_check = mysqli_stmt_get_result($stmt_check);
                $current_status_row = mysqli_fetch_assoc($result_check);
                mysqli_stmt_close($stmt_check);
                
                // Set notification as unread if status changed from 'open' to anything else
                if($current_status_row && $current_status_row['status'] === 'open' && $status !== 'open') {
                    setComplaintNotificationUnread($complaintId, $conn);
                }
            }
            mysqli_stmt_close($stmt);
            header("Location: ../manager_complaints.php?success=complaintupdated");
            exit();
        } else {
            // Check if no rows were affected because status was already the same
            $sql_check_status = "SELECT status FROM complaints WHERE complaint_id = ?";
            $stmt_check = mysqli_stmt_init($conn);
            if(mysqli_stmt_prepare($stmt_check, $sql_check_status)){
                mysqli_stmt_bind_param($stmt_check, "i", $complaintId);
                mysqli_stmt_execute($stmt_check);
                $result_check = mysqli_stmt_get_result($stmt_check);
                $current_status_row = mysqli_fetch_assoc($result_check);
                if($current_status_row && $current_status_row['status'] === $status){
                    mysqli_stmt_close($stmt);
                    mysqli_stmt_close($stmt_check);
                    header("Location: ../manager_complaints.php?info=nostatuschange");
                    exit();
                }
                mysqli_stmt_close($stmt_check);
            }
            mysqli_stmt_close($stmt);
            header("Location: ../manager_complaints.php?error=failedtoupdate");
            exit();
        }

    } else {
        header("Location: ../manager_complaints.php?error=invalidinput");
        exit();
    }
} else {
    header("Location: ../manager_complaints.php");
    exit();
}
mysqli_close($conn);
