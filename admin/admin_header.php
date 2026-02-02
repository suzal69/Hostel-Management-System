<?php
// FILE: C:\xampp\htdocs\project\admin\admin_header.php

// Ensure config/DB connection is ready
require_once __DIR__ . '/../includes/config.inc.php';

// The session is assumed to be started by config.inc.php

// Assuming admin_id is stored in session
$admin_id = $_SESSION['admin_id'] ?? null;
$username = $_SESSION['username'] ?? 'Admin'; // Default for display

// Initialize counts
$message_notification_count = 0;
// $admin_notification_count = 0; // Disabled as 'notifications' table does not exist in the schema
$hm_notification_count = 0;

if (isset($conn) && $admin_id) {
    // 1. Fetch notification count for unread messages for the admin
    $sql_message_notifications = "SELECT COUNT(*) as count FROM Message WHERE receiver_id = ? AND read_status = 0";
    $stmt_message_notifications = $conn->prepare($sql_message_notifications);
    if ($stmt_message_notifications) {
        $stmt_message_notifications->bind_param("s", $admin_id);
        $stmt_message_notifications->execute();
        $result = $stmt_message_notifications->get_result();
        $row = $result->fetch_assoc();
        $message_notification_count = $row['count'] ?? 0;
        $stmt_message_notifications->close();
    }

    // 2. Fetch notification count for pending hostel manager
    $sql_hm_notifications = "SELECT COUNT(*) as count FROM pending_hostel_manager";
    $stmt_hm_notifications = $conn->prepare($sql_hm_notifications);
    if ($stmt_hm_notifications) {
        $stmt_hm_notifications->execute();
        $result = $stmt_hm_notifications->get_result();
        $row = $result->fetch_assoc();
        $hm_notification_count = $row['count'] ?? 0;
        $stmt_hm_notifications->close();
    }
}
// END OF ALL PHP LOGIC BEFORE HTML OUTPUT
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Hostel Management System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <link href="../web_home/css_home/slider.css" type="text/css" rel="stylesheet" media="all">
<link rel="stylesheet" href="../web_home/css_home/bootstrap.css">
<link rel="stylesheet" href="../web_home/css_home/style.css" type="text/css" media="all" />
<link href="../web/css/style1.css" type="text/css" rel="stylesheet" media="all">
<link rel="stylesheet" href="../web_home/css_home/flexslider.css" type="text/css" media="screen" property="" />
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../web_profile/css/font-awesome.min.css" />
    <link rel="stylesheet" href="admin_styles.css">

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
    /* ... omitted remaining styles for brevity ... */
    .notification-badge {
        background-color: #ffc107; /* Yellow color for notifications */
        color: #212529; /* Dark text for contrast */
        font-size: 0.75em;
        padding: .2em .6em;
        border-radius: .25rem;
        margin-left: 5px;
        vertical-align: super;
    }
    
    /* Custom Dropdown Styles */
    .dropdown {
        position: relative;
    }
    
    .dropdown-menu {
        position: absolute;
        top: 100%;
        left: 0;
        z-index: 1000;
        display: none;
        min-width: 200px;
        background: white;
        border: 1px solid rgba(0,0,0,0.1);
        border-radius: 8px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        padding: 8px 0;
        margin-top: 5px;
    }
    
    .dropdown-menu.show {
        display: block;
    }
    
    .dropdown-item {
        display: block;
        width: 100%;
        padding: 10px 20px;
        clear: both;
        font-weight: 400;
        color: #333;
        text-align: inherit;
        white-space: nowrap;
        background: transparent;
        border: 0;
        text-decoration: none;
        transition: all 0.3s ease;
    }
    
    .dropdown-item:hover {
        color: #003366;
        background: #f8f9fa;
        text-decoration: none;
    }
    
    .dropdown-toggle::after {
        display: inline-block;
        margin-left: 0.255em;
        vertical-align: 0.255em;
        content: "";
        border-top: 0.3em solid;
        border-right: 0.3em solid transparent;
        border-bottom: 0;
        border-left: 0.3em solid transparent;
    }
    
    .navbar-nav {
        display: flex;
        list-style: none;
        margin: 0;
        padding: 0;
    }
    
    .navbar-nav .nav-item {
        position: relative;
    }
    
    .navbar-nav .nav-link {
        display: block;
        padding: 0.5rem 1rem;
        color: white !important;
        text-decoration: none;
        transition: color 0.3s ease;
    }
    
    .navbar-nav .nav-link:hover {
        color: #ffcc00 !important;
    }
    
    .ml-auto {
        margin-left: auto;
    }
    
    .navbar-collapse {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    @media (max-width: 768px) {
        .navbar-collapse {
            flex-direction: column;
        }
        
        .navbar-nav {
            flex-direction: column;
            width: 100%;
            margin-top: 1rem;
        }
        
        .ml-auto {
            margin-left: 0;
            margin-top: 1rem;
        }
    }
    </style>
</head>
<body>
    <header>
  <nav class="navbar navbar-expand-lg">
    <div class="container">
      <a class="navbar-brand" href="admin_home.php">PLYS</a>
      <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
        <span class="navbar-toggler-icon" style="color:white;"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ml-auto">
          <li class="nav-item">
            <a class="nav-link" href="admin_home.php">Home</a>
          </li>
          <li class="dropdown nav-item">
            <a href="#" class="dropdown-toggle nav-link" data-toggle="dropdown">Hostel Manager
                <?php if ($hm_notification_count > 0): ?>
                    <span class="notification-badge"><?php echo $hm_notification_count; ?></span>
                <?php endif; ?>
                <b class="caret"></b>
            </a>
                <ul class="dropdown-menu agile_short_dropdown">
                    <li>
                        <a class="dropdown-item" href="create_hm.php">Appoint
                            <?php if ($hm_notification_count > 0): ?>
                                <span class="notification-badge"><?php echo $hm_notification_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="manager_details.php">Manager Details</a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="remove_manager.php">Remove</a>
                    </li>
                </ul>
          </li>
          <li class="nav-item"><a class="nav-link" href="students.php">Students</a></li>
          <li class="nav-item"><a class="nav-link" href="admin_analytics.php">Analytics</a></li>
          <li class="nav-item"><a class="nav-link" href="admin_complaint_review.php">Complaint Review</a></li>
          <li class="nav-item"><a class="nav-link" href="admin_contact.php">Contact</a></li>
          <li class="nav-item"><a class="nav-link" href="admin_room_management.php">Room</a></li>
          <li class="dropdown nav-item">
            <a href="#" class="dropdown-toggle nav-link" data-toggle="dropdown"><?php echo htmlspecialchars($username); ?>
                <b class="caret"></b>
            </a>
                <ul class="dropdown-menu agile_short_dropdown">
                    <li>
                        <a class="dropdown-item" href="admin_profile.php">My Profile</a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="../includes/logout.inc.php">Logout</a>
                    </li>
                </ul>
          </li>
        </ul>
      </div>
    </div>
  </nav>
</header>

<?php
if (isset($_SESSION['message'])) {
    $message = htmlspecialchars($_SESSION['message']);
    $class = strpos($message, 'Error') !== false ? 'alert-danger' : 'alert-success';
    echo '<div class="container mt-3"><div class="alert ' . $class . ' alert-dismissible fade show" role="alert">' . $message . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div></div>';
    unset($_SESSION['message']);
}
?>

<script>
// Custom Dropdown Functionality
document.addEventListener('DOMContentLoaded', function() {
    // Handle dropdown toggles
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    
    dropdownToggles.forEach(function(toggle) {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Close all other dropdowns
            const allDropdowns = document.querySelectorAll('.dropdown-menu');
            allDropdowns.forEach(function(dropdown) {
                if (dropdown !== toggle.nextElementSibling) {
                    dropdown.classList.remove('show');
                }
            });
            
            // Toggle current dropdown
            const dropdown = toggle.nextElementSibling;
            if (dropdown && dropdown.classList.contains('dropdown-menu')) {
                dropdown.classList.toggle('show');
            }
        });
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.matches('.dropdown-toggle') && !e.target.closest('.dropdown-toggle')) {
            const dropdowns = document.querySelectorAll('.dropdown-menu.show');
            dropdowns.forEach(function(dropdown) {
                dropdown.classList.remove('show');
            });
        }
    });
    
    // Handle mobile navbar toggle
    const navbarToggler = document.querySelector('.navbar-toggler');
    const navbarCollapse = document.querySelector('.navbar-collapse');
    
    if (navbarToggler && navbarCollapse) {
        navbarToggler.addEventListener('click', function() {
            navbarCollapse.classList.toggle('show');
        });
    }
    
    // Handle alert dismissals
    const alertDismissions = document.querySelectorAll('.alert .close');
    alertDismissions.forEach(function(button) {
        button.addEventListener('click', function() {
            const alert = button.closest('.alert');
            if (alert) {
                alert.style.display = 'none';
            }
        });
    });
});
</script>

