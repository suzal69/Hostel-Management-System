<?php
// includes/send_message.inc.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'config.inc.php';

if (isset($_POST['send_message'])) {
    $sender_id = $_POST['sender_id'] ?? null;
    $receiver_id = $_POST['receiver_id'] ?? null;
    $hostel_id = $_POST['hostel_id'] ?? null;
    $subject = $_POST['subject'] ?? null;
    $message = $_POST['message'] ?? null;

    if (!$sender_id || !$receiver_id || !$hostel_id || !$subject || !$message) {
        echo "<script type='text/javascript'>alert('Error: All fields are required.'); window.location.href = '../message_hostel_manager.php';</script>";
        exit();
    }

    // Sanitize inputs (basic example, consider more robust sanitization)
    $sender_id = htmlspecialchars($sender_id);
    $receiver_id = htmlspecialchars($receiver_id);
    $hostel_id = (int)$hostel_id; // Ensure it's an integer
    $subject = htmlspecialchars($subject);
    $message = htmlspecialchars($message);

    $msg_date = date("Y-m-d");
    $msg_time = date("H:i:s"); // Use 24-hour format for database storage

    // Prepare and execute the SQL query to insert the message
    $sql = "INSERT INTO Message (sender_id, receiver_id, hostel_id, subject_h, message, msg_date, msg_time, read_status) VALUES (?, ?, ?, ?, ?, ?, ?, 0)";
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ssissss", $sender_id, $receiver_id, $hostel_id, $subject, $message, $msg_date, $msg_time);
        if (mysqli_stmt_execute($stmt)) {
            echo "<script type='text/javascript'>alert('Message sent successfully!'); window.location.href = '../message_hostel_manager.php';</script>";
        } else {
            error_log("Error sending message: " . mysqli_error($conn));
            echo "<script type='text/javascript'>alert('Error sending message. Please try again.'); window.location.href = '../message_hostel_manager.php';</script>";
        }
        mysqli_stmt_close($stmt);
    } else {
        error_log("Error preparing statement: " . mysqli_error($conn));
        echo "<script type='text/javascript'>alert('Database error. Please try again later.'); window.location.href = '../message_hostel_manager.php';</script>";
    }
} else {
    // If accessed directly without form submission
    header("Location: ../message_hostel_manager.php");
    exit();
}
?>