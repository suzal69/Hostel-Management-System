<?php
require_once __DIR__ . '/config.inc.php';
require 'managerFooter.php';
// Ensure session is started and hostel_id is available
if (function_exists('session_status')) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
} else {
    if (session_id() === '') {
        session_start();
    }
}

if (!isset($_SESSION['hostel_id'])) {
    // Redirect to login or show an error if hostel ID is not set
    echo "<script>alert('Hostel not set. Please login again.'); window.location='home_manager.php';</script>";
    exit();
}

$hostel_id = $_SESSION['hostel_id'];
$hostel_man_id = $_SESSION['hostel_man_id'] ?? null; // Assuming hostel_man_id is also in session

// Fetch notification count for pending room allocations
$allocate_notification_count = 0;
if (isset($conn)) {
    $sql_allocate_notifications = "SELECT COUNT(*) AS count FROM Application WHERE Hostel_id = ? AND Application_status = '1'";
    $stmt_allocate_notifications = mysqli_prepare($conn, $sql_allocate_notifications);
    if ($stmt_allocate_notifications) {
        mysqli_stmt_bind_param($stmt_allocate_notifications, "i", $hostel_id);
        mysqli_stmt_execute($stmt_allocate_notifications);
        $result_allocate_notifications = mysqli_stmt_get_result($stmt_allocate_notifications);
        if ($row = mysqli_fetch_assoc($result_allocate_notifications)) {
            $allocate_notification_count = $row['count'];
        }
        mysqli_stmt_close($stmt_allocate_notifications);
    }
}

// Fetch notification count for unread messages for the hostel manager
$message_notification_count = 0;
if (isset($conn) && $hostel_man_id) {
    $sql_message_notifications = "SELECT COUNT(*) AS count FROM Message WHERE receiver_id = ? AND read_status = 0";
    $stmt_message_notifications = mysqli_prepare($conn, $sql_message_notifications);
    if ($stmt_message_notifications) {
        mysqli_stmt_bind_param($stmt_message_notifications, "s", $hostel_man_id);
        mysqli_stmt_execute($stmt_message_notifications);
        $result_message_notifications = mysqli_stmt_get_result($stmt_message_notifications);
        if ($row = mysqli_fetch_assoc($result_message_notifications)) {
            $message_notification_count = $row['count'];
        }
        mysqli_stmt_close($stmt_message_notifications);
    }
}

// Fetch notification count for pending leave applications
$leave_notification_count = 0;
if (isset($conn) && $hostel_id) {
    $sql_leave_notifications = "SELECT COUNT(*) AS count FROM leave_applications la
                                JOIN Student s ON la.student_id = s.Student_id
                                WHERE s.Hostel_id = ? AND la.status = 'pending'";
    $stmt_leave_notifications = mysqli_prepare($conn, $sql_leave_notifications);
    if ($stmt_leave_notifications) {
        mysqli_stmt_bind_param($stmt_leave_notifications, "i", $hostel_id);
        mysqli_stmt_execute($stmt_leave_notifications);
        $result_leave_notifications = mysqli_stmt_get_result($stmt_leave_notifications);
        if ($row = mysqli_fetch_assoc($result_leave_notifications)) {
            $leave_notification_count = $row['count'];
        }
        mysqli_stmt_close($stmt_leave_notifications);
    }
}

// Fetch notification count for new complaints (open status)
$complaint_notification_count = 0;
if (isset($conn) && $hostel_id) {
    $sql_complaint_notifications = "SELECT COUNT(*) AS count FROM complaints 
                                     WHERE hostel_id = ? AND status = 'open'";
    $stmt_complaint_notifications = mysqli_prepare($conn, $sql_complaint_notifications);
    if ($stmt_complaint_notifications) {
        mysqli_stmt_bind_param($stmt_complaint_notifications, "i", $hostel_id);
        mysqli_stmt_execute($stmt_complaint_notifications);
        $result_complaint_notifications = mysqli_stmt_get_result($stmt_complaint_notifications);
        if ($row = mysqli_fetch_assoc($result_complaint_notifications)) {
            $complaint_notification_count = $row['count'];
        }
        mysqli_stmt_close($stmt_complaint_notifications);
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<title>Hostel Manager Panel</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta charset="utf-8">

<!-- css files -->
<link rel="stylesheet" href="web_home/css_home/bootstrap.css">
<!-- <link rel="stylesheet" href="web_home/css_home/style.css" type="text/css" media="all" /> -->
<!-- <link rel="stylesheet" href="web_home/css_home/fontawesome-all.css"> Font-Awesome-Icons-CSS -->
<!-- <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet"> -->

<!-- Popper.js for Bootstrap dropdowns -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>

<style>
    body {
        font-family: 'Poppins', sans-serif;
        margin: 0;
        padding: 0;
        background-color: #f8f9fa;
    }
    .navbar {
        background-color: #003366;
        padding: 15px 0;
    }
    .navbar-brand, .nav-link {
        color: #ffffffff !important;
        font-weight: 600;
    }
    .navbar-brand {
        font-size: 1.5rem;
    }
    .nav-link:hover {
        color: #ffcc00 !important;
    }
    .dropdown-menu {
        background-color: #003366;
    }
    .dropdown-menu .dropdown-item {
        color: #ffffff;
    }
    .dropdown-menu .dropdown-item:hover {
        background-color: #001f4d;
        color: #ffcc00;
    }
    .notification-badge {
        background-color: #ffc107; /* Yellow color for notifications */
        color: #212529; /* Dark text for contrast */
        font-size: 0.75em;
        padding: .2em .6em;
        border-radius: .25rem;
        margin-left: 5px;
        vertical-align: super;
    }
    
    /* Banner styles for consistent look */
    .banner {
        position: relative;
        height: 80vh;
        background: url('web_home/images/0.jpg') no-repeat center center/cover;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        color: white;
    }
    
    .banner-content h1 {
        font-size: 4rem;
        font-weight: 600;
        margin-bottom: 10px;
    }
    
    .banner-content h2 {
        font-size: 3rem;
        font-weight: 400;
    }
    
    .footer-logo a {
        text-decoration: none;
    }
    
    .footer-nav {
        list-style: none;
        padding: 0;
        margin: 20px 0 0;
    }
    
    .footer-nav li {
        margin-bottom: 10px;
    }
    
    .footer-nav a {
        color: white;
        text-decoration: none;
    }
</style>

</head>
<body>

<!-- Banner and Navbar -->
<header>
 <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="home_manager.php">PLYS</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse justify-content-center" id="navbarSupportedContent">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="home_manager.php">Home <span class="sr-only">(current)</span></a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="allocate_room.php">Allocate Room
                            <?php if ($allocate_notification_count > 0): ?>
                                <span class="notification-badge"><?php echo $allocate_notification_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="dropdown nav-item">
                        <a href="#" class="dropdown-toggle nav-link" data-toggle="dropdown">Rooms
                            <b class="caret"></b>
                        </a>
                        <ul class="dropdown-menu agile_short_dropdown">
                            <li>
                                <a class="dropdown-item" href="allocated_rooms.php">Allocated Rooms</a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="manager_room_details.php">Room Details</a>
                            </li>
                        </ul>
                    </li>
                    <li class="dropdown nav-item">
                        <a href="#" class="dropdown-toggle nav-link" data-toggle="dropdown">Manage
                            <?php 
                            $total_manage_notifications = $leave_notification_count + $complaint_notification_count;
                            if ($total_manage_notifications > 0): ?>
                                <span class="notification-badge"><?php echo $total_manage_notifications; ?></span>
                            <?php endif; ?>
                            <b class="caret"></b>
                        </a>
                        <ul class="dropdown-menu agile_short_dropdown">
                            <li>
                                <a class="dropdown-item" href="leave_management.php">Leave Management
                                    <?php if ($leave_notification_count > 0): ?>
                                        <span class="notification-badge"><?php echo $leave_notification_count; ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="manager_complaints.php">Manage Complaints
                                    <?php if ($complaint_notification_count > 0): ?>
                                        <span class="notification-badge"><?php echo $complaint_notification_count; ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact_manager.php">Contact</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="message_hostel_manager.php">Messages Received
                            <?php if ($message_notification_count > 0): ?>
                                <span class="notification-badge"><?php echo $message_notification_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="dropdown nav-item">
                        <a href="#" class="dropdown-toggle nav-link" data-toggle="dropdown"><?php echo htmlspecialchars($_SESSION['username']); ?>
                            <b class="caret"></b>
                        </a>
                        <ul class="dropdown-menu agile_short_dropdown">
                            <li>
                                <a class="dropdown-item" href="manager_profile.php">My Profile</a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="includes/logout.inc.php">Logout</a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</header>

<br><br><br>
