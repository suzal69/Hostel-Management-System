<?php
require 'includes/config.inc.php';

if (isset($_POST['application_id']) && isset($_POST['start_date']) && isset($_POST['end_date'])) {
    $application_id = $_POST['application_id'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    // Server-side validation: ensure dates are valid and not in the past
    $d1 = DateTime::createFromFormat('Y-m-d', $start_date);
    $d2 = DateTime::createFromFormat('Y-m-d', $end_date);
    $today = new DateTime();
    $today->setTime(0,0,0);

    if (!$d1 || $d1->format('Y-m-d') !== $start_date) {
        echo "<script>alert('Invalid Start Date format'); window.location='allocate_room.php';</script>";
        exit();
    }
    if (!$d2 || $d2->format('Y-m-d') !== $end_date) {
        echo "<script>alert('Invalid End Date format'); window.location='allocate_room.php';</script>";
        exit();
    }

    // Start date must be today or future (not past)
    if ($d1 < $today) {
        echo "<script>alert('Start Date cannot be in the past. Please select today or a future date.'); window.location='allocate_room.php';</script>";
        exit();
    }

    // End date must be same or after start date
    if ($d2 < $d1) {
        echo "<script>alert('End Date must be the same or after Start Date.'); window.location='allocate_room.php';</script>";
        exit();
    }

    $query = "UPDATE Application SET start_date = ?, end_date = ? WHERE Application_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ssi", $start_date, $end_date, $application_id);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "<script>alert('Dates updated successfully'); window.location='allocate_room.php';</script>";
    } else {
        echo "<script>alert('Error updating dates'); window.location='allocate_room.php';</script>";
    }
} else {
    header('Location: allocate_room.php');
    exit();
}
?>