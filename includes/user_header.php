<?php
require_once __DIR__ . '/config.inc.php';


// Ensure session is started and student roll number is available
if (function_exists('session_status')) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
} else {
    if (session_id() === '') {
        session_start();
    }
}

$roll_no = $_SESSION['roll'] ?? null;

if (!$roll_no) {
    // Redirect to login or show an error if student ID is not set
    header("Location: index.php?error=notloggedin");
    exit();
}

// Fetch notification count for unread messages
$message_notification_count = 0;
$leave_notification_count = 0;
$complaint_notification_count = 0;

// Check if the database connection ($conn) is available
if (isset($conn)) {
    // Correct SQL query to count unread messages for the current user (receiver_id)
    $sql_message_notifications = "SELECT COUNT(*) AS count FROM Message WHERE receiver_id = ? AND read_status = 0";
    $stmt_message_notifications = mysqli_prepare($conn, $sql_message_notifications);
    if ($stmt_message_notifications) {
        mysqli_stmt_bind_param($stmt_message_notifications, "s", $roll_no);
        mysqli_stmt_execute($stmt_message_notifications);
        $result_message_notifications = mysqli_stmt_get_result($stmt_message_notifications);
        if ($row = mysqli_fetch_assoc($result_message_notifications)) {
            $message_notification_count = $row['count'];
        }
        mysqli_stmt_close($stmt_message_notifications);
    } else {
        // Optional: Log an error if the statement preparation failed
        // error_log("Failed to prepare message notification statement: " . mysqli_error($conn));
    }
    
    // Count unread leave responses (approved/rejected leave applications)
    $sql_leave_notifications = "SELECT COUNT(*) AS count FROM leave_applications 
                                 WHERE student_id = ? AND status IN ('approved', 'rejected') 
                                 AND notification_read = 0";
    $stmt_leave_notifications = mysqli_prepare($conn, $sql_leave_notifications);
    if ($stmt_leave_notifications) {
        mysqli_stmt_bind_param($stmt_leave_notifications, "s", $roll_no);
        mysqli_stmt_execute($stmt_leave_notifications);
        $result_leave_notifications = mysqli_stmt_get_result($stmt_leave_notifications);
        if ($row = mysqli_fetch_assoc($result_leave_notifications)) {
            $leave_notification_count = $row['count'];
        }
        mysqli_stmt_close($stmt_leave_notifications);
    }
    
    // Count unread complaint responses (resolved/completed/in_progress complaints)
    $sql_complaint_notifications = "SELECT COUNT(*) AS count FROM complaints 
                                    WHERE student_id = ? AND status IN ('resolved', 'completed', 'in_progress') 
                                    AND notification_read = 0";
    $stmt_complaint_notifications = mysqli_prepare($conn, $sql_complaint_notifications);
    if ($stmt_complaint_notifications) {
        mysqli_stmt_bind_param($stmt_complaint_notifications, "s", $roll_no);
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
<title>Intrend Interior Category Flat Bootstrap Responsive Website Template | Index : W3layouts</title>

    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <meta name="keywords" content="Intrend Responsive web template, Bootstrap Web Templates, Flat Web Templates, Android Compatible web template,
    Smartphone Compatible web template, free webdesigns for Nokia, Samsung, LG, SonyEricsson, Motorola web design" />
    <script type="application/x-javascript">
        addEventListener("load", function () {
            setTimeout(hideURLbar, 0);
        }, false);

        function hideURLbar() {
            window.scrollTo(0, 1);
        }
    </script>
    <link rel="stylesheet" href="web_home/css_home/bootstrap.css"> 
    <link rel="stylesheet" href="web_home/css_home/style.css" type="text/css" media="all" /> 
    <link rel="stylesheet" href="web_profile/css/font-awesome.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
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
        background-color: #003366 !important;
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
    
</style>

</head>
<body>

<!-- Banner and Navbar -->
    <header>
        <nav class="navbar navbar-expand-lg">
            <div class="container">
                <a class="navbar-brand" href="home.php">PLYS</a>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                    <span class="navbar-toggler-icon" style="color:white;"></span>
                </button>
                <div class="collapse navbar-collapse justify-content-center" id="navbarSupportedContent">
                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item active">
                            <a class="nav-link" href="home.php">Home <span class="sr-only">(current)</span></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="services.php">Hostels</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="student_room_details.php">Room</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="leave_application.php">Leave
                                <?php if ($leave_notification_count > 0): ?>
                                    <span class="notification-badge"><?php echo $leave_notification_count; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="student_complaint.php">Complaints
                                <?php if ($complaint_notification_count > 0): ?>
                                    <span class="notification-badge"><?php echo $complaint_notification_count; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="contact.php">Contact</a>
                        </li>
                        <li class="nav-item">
                            <!-- *** FIX APPLIED HERE: Displaying the notification badge *** -->
                            <a class="nav-link" href="message_user.php">Message Received
                                <?php if ($message_notification_count > 0): ?>
                                    <span class="notification-badge"><?php echo $message_notification_count; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="dropdown nav-item">
                            <a href="#" class="dropdown-toggle nav-link" data-toggle="dropdown"><?php echo htmlspecialchars($_SESSION['fname']); ?>
                                <b class="caret"></b>
                            </a>
                            <ul class="dropdown-menu agile_short_dropdown">
                                <li>
                                    <a class="dropdown-item" href="profile.php">My Profile</a>
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

<!-- Removed the duplicate manager code block for clarity, assuming it's from a separate file -->
