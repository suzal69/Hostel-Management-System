<?php
// Legacy entry point removed; redirect users to new pages.
session_start();
if (isset($_SESSION['hostel_man_id'])) {
    header('Location: manager_complaints.php');
    exit();
}
if (isset($_SESSION['roll'])) {
    header('Location: student_complaint.php');
    exit();
}
header('Location: index.php');
exit();
?>
