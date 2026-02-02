<?php
require 'config.inc.php';

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo 'error';
    exit();
}

// Get message ID
$message_id = $_POST['message_id'] ?? '';

if (empty($message_id)) {
    echo 'error';
    exit();
}

// Update message status to 'completed'
$query = "UPDATE messages SET status = 'completed' WHERE msg_id = ?";
$stmt = mysqli_prepare($conn, $query);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $message_id);
    if (mysqli_stmt_execute($stmt)) {
        echo 'success';
    } else {
        echo 'error';
    }
    mysqli_stmt_close($stmt);
} else {
    echo 'error';
}

mysqli_close($conn);
?>
