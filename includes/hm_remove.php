<?php
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/functions.inc.php';

// Admin check
if (empty($_SESSION['admin_username']) && (empty($_SESSION['isadmin']) || $_SESSION['isadmin'] != 1)) {
    header('Location: ../login-hostel_manager.php');
    exit;
}

if (isset($_GET['id'])) {
    $managerId = $_GET['id'];
    
    // Function to delete manager by ID
    deleteManagerById($conn, $managerId);
    
    header('Location: ../admin/remove_manager.php');
    exit;
} else {
    header('Location: ../admin/remove_manager.php');
    exit;
}