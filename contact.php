<?php
require_once 'includes/config.inc.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle form submission first, before any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    
    // Validate inputs
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        header("Location: contact.php?error=1");
        exit();
    }
    
    // Get hostel information
    $hostel_name = '';
    $hostel_id = null;
    if (isset($_SESSION['hostel_id'])) {
        // For hostel manager
        $hostel_id = (int) $_SESSION['hostel_id'];
        $query = "SELECT Hostel_name FROM Hostel WHERE Hostel_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $hostel_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            $hostel_name = $row['Hostel_name'];
        }
    } elseif (isset($_SESSION['roll'])) {
        // For student: get hostel name AND id
        $query = "SELECT h.Hostel_name, h.Hostel_id 
                  FROM Student s 
                  JOIN Hostel h ON s.Hostel_id = h.Hostel_id 
                  WHERE s.Student_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $_SESSION['roll']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            $hostel_name = $row['Hostel_name'];
            $hostel_id = (int) $row['Hostel_id'];
        }
    }
    
    // Get manager ID for this hostel
    $manager_id = null;
    if ($hostel_id) {
        error_log("Contact form: Looking for manager for hostel_id: " . $hostel_id);
        $manager_query = "SELECT Hostel_man_id, Hostel_name FROM Hostel WHERE Hostel_id = ?";
        $manager_stmt = mysqli_prepare($conn, $manager_query);
        if ($manager_stmt) {
            mysqli_stmt_bind_param($manager_stmt, "i", $hostel_id);
            mysqli_stmt_execute($manager_stmt);
            $manager_result = mysqli_stmt_get_result($manager_stmt);
            if ($manager_row = mysqli_fetch_assoc($manager_result)) {
                $manager_id = $manager_row['Hostel_man_id'];
                error_log("Contact form: Found hostel: " . $manager_row['Hostel_name'] . ", manager_id: " . ($manager_id ?? 'NULL'));
                
                // Check if manager_id is actually valid by checking the hostel_manager table
                if ($manager_id) {
                    $check_manager_query = "SELECT Hostel_man_id, Fname, Lname FROM hostel_manager WHERE Hostel_man_id = ? AND approval_status = 'approved'";
                    $check_manager_stmt = mysqli_prepare($conn, $check_manager_query);
                    if ($check_manager_stmt) {
                        mysqli_stmt_bind_param($check_manager_stmt, "i", $manager_id);
                        mysqli_stmt_execute($check_manager_stmt);
                        $check_manager_result = mysqli_stmt_get_result($check_manager_stmt);
                        if ($manager_info = mysqli_fetch_assoc($check_manager_result)) {
                            error_log("Contact form: Valid manager found: " . $manager_info['Fname'] . " " . $manager_info['Lname']);
                        } else {
                            error_log("Contact form: Manager_id " . $manager_id . " found in Hostel table but not in hostel_manager table or not approved");
                            $manager_id = null; // Reset to null since manager is not valid
                        }
                        mysqli_stmt_close($check_manager_stmt);
                    }
                }
            } else {
                error_log("Contact form: No hostel found with hostel_id: " . $hostel_id);
            }
            mysqli_stmt_close($manager_stmt);
        } else {
            error_log("Contact form: Failed to prepare manager query: " . mysqli_error($conn));
        }
    } else {
        error_log("Contact form: No hostel_id found in session");
    }
    
    if ($manager_id) {
        // Insert message into database
        $today_date = date("Y-m-d");
        $time = date("H:i:s"); // Use 24-hour format for database
        
        $query = "INSERT INTO Message (sender_id, receiver_id, hostel_id, subject_h, message, msg_date, msg_time) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        
        if ($stmt) {
            $sender_id = $_SESSION['roll'] ?? 'student';
            mysqli_stmt_bind_param($stmt, "ssissss", 
                $sender_id, 
                $manager_id, 
                $hostel_id, 
                $subject, 
                $message, 
                $today_date, 
                $time
            );
            
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                header("Location: contact.php?success=1");
                exit();
            } else {
                $error = mysqli_stmt_error($stmt);
                mysqli_stmt_close($stmt);
                // Log error for debugging
                error_log("Contact form error: " . $error);
                header("Location: contact.php?error=1&msg=" . urlencode($error));
                exit();
            }
        } else {
            $error = mysqli_error($conn);
            error_log("Contact form prepare error: " . $error);
            header("Location: contact.php?error=1&msg=" . urlencode($error));
            exit();
        }
    } else {
        // Fallback: Try to find the correct manager for this student's hostel
        error_log("Contact form: No valid manager found in Hostel table, trying direct hostel_manager lookup");
        $today_date = date("Y-m-d");
        $time = date("H:i:s");
        $message_sent = false;
        
        // Option 1: Find manager with the same hostel_id as the student
        if ($hostel_id) {
            $correct_manager_query = "SELECT Hostel_man_id, Fname, Lname FROM hostel_manager WHERE Hostel_id = ? AND approval_status = 'approved' LIMIT 1";
            $correct_manager_stmt = mysqli_prepare($conn, $correct_manager_query);
            if ($correct_manager_stmt) {
                mysqli_stmt_bind_param($correct_manager_stmt, "i", $hostel_id);
                mysqli_stmt_execute($correct_manager_stmt);
                $correct_manager_result = mysqli_stmt_get_result($correct_manager_stmt);
                if ($correct_manager_info = mysqli_fetch_assoc($correct_manager_result)) {
                    $correct_manager_id = $correct_manager_info['Hostel_man_id'];
                    error_log("Contact form: Found correct manager for hostel_id " . $hostel_id . ": " . $correct_manager_info['Fname'] . " " . $correct_manager_info['Lname']);
                    
                    $query = "INSERT INTO Message (sender_id, receiver_id, hostel_id, subject_h, message, msg_date, msg_time) 
                              VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $stmt = mysqli_prepare($conn, $query);
                    
                    if ($stmt) {
                        $sender_id = $_SESSION['roll'] ?? 'student';
                        mysqli_stmt_bind_param($stmt, "ssissss", 
                            $sender_id, 
                            $correct_manager_id, 
                            $hostel_id, 
                            $subject, 
                            $message, 
                            $today_date, 
                            $time
                        );
                        
                        if (mysqli_stmt_execute($stmt)) {
                            mysqli_stmt_close($stmt);
                            error_log("Contact form: Message sent successfully to correct hostel manager");
                            header("Location: contact.php?success=1");
                            exit();
                        }
                        mysqli_stmt_close($stmt);
                    }
                } else {
                    error_log("Contact form: No approved manager found for hostel_id " . $hostel_id);
                }
                mysqli_stmt_close($correct_manager_stmt);
            }
        }
        
        // Option 2: Try to find any approved manager in the system (only if no manager for this hostel)
        if (!$message_sent) {
            $any_manager_query = "SELECT Hostel_man_id, Fname, Lname FROM hostel_manager WHERE approval_status = 'approved' LIMIT 1";
            $any_manager_stmt = mysqli_prepare($conn, $any_manager_query);
            if ($any_manager_stmt) {
                mysqli_stmt_execute($any_manager_stmt);
                $any_manager_result = mysqli_stmt_get_result($any_manager_stmt);
                if ($any_manager_info = mysqli_fetch_assoc($any_manager_result)) {
                    $fallback_manager_id = $any_manager_info['Hostel_man_id'];
                    error_log("Contact form: Using fallback manager (not for this hostel): " . $any_manager_info['Fname'] . " " . $any_manager_info['Lname']);
                    
                    $query = "INSERT INTO Message (sender_id, receiver_id, hostel_id, subject_h, message, msg_date, msg_time) 
                              VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $stmt = mysqli_prepare($conn, $query);
                    
                    if ($stmt) {
                        $sender_id = $_SESSION['roll'] ?? 'student';
                        mysqli_stmt_bind_param($stmt, "ssissss", 
                            $sender_id, 
                            $fallback_manager_id, 
                            $hostel_id, 
                            $subject, 
                            $message, 
                            $today_date, 
                            $time
                        );
                        
                        if (mysqli_stmt_execute($stmt)) {
                            mysqli_stmt_close($stmt);
                            error_log("Contact form: Message sent successfully to fallback manager");
                            header("Location: contact.php?success=1");
                            exit();
                        }
                        mysqli_stmt_close($stmt);
                    }
                }
                mysqli_stmt_close($any_manager_stmt);
            }
        }
        
        // Option 3: Try to find an admin user
        if (!$message_sent) {
            $admin_query = "SELECT admin_id FROM admin LIMIT 1";
            $admin_stmt = mysqli_prepare($conn, $admin_query);
            if ($admin_stmt) {
                mysqli_stmt_execute($admin_stmt);
                $admin_result = mysqli_stmt_get_result($admin_stmt);
                if ($admin_row = mysqli_fetch_assoc($admin_result)) {
                    $admin_id = $admin_row['admin_id'];
                    error_log("Contact form: Using admin fallback: admin_id " . $admin_id);
                    
                    $query = "INSERT INTO Message (sender_id, receiver_id, hostel_id, subject_h, message, msg_date, msg_time) 
                              VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $stmt = mysqli_prepare($conn, $query);
                    
                    if ($stmt) {
                        $sender_id = $_SESSION['roll'] ?? 'student';
                        mysqli_stmt_bind_param($stmt, "ssissss", 
                            $sender_id, 
                            $admin_id, 
                            $hostel_id, 
                            $subject, 
                            $message, 
                            $today_date, 
                            $time
                        );
                        
                        if (mysqli_stmt_execute($stmt)) {
                            mysqli_stmt_close($stmt);
                            error_log("Contact form: Message sent successfully to admin");
                            header("Location: contact.php?success=1");
                            exit();
                        }
                        mysqli_stmt_close($stmt);
                    }
                }
                mysqli_stmt_close($admin_stmt);
            }
        }
        
        // Option 4: Create a system message that can be viewed by any admin
        if (!$message_sent) {
            error_log("Contact form: Creating system message as last resort");
            $query = "INSERT INTO Message (sender_id, receiver_id, hostel_id, subject_h, message, msg_date, msg_time) 
                      VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            
            if ($stmt) {
                $sender_id = $_SESSION['roll'] ?? 'student';
                $system_receiver = 'system'; // Use system as receiver
                mysqli_stmt_bind_param($stmt, "ssissss", 
                    $sender_id, 
                    $system_receiver, 
                    $hostel_id, 
                    $subject, 
                    $message, 
                    $today_date, 
                    $time
                );
                
                if (mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_close($stmt);
                    error_log("Contact form: System message created successfully");
                    header("Location: contact.php?success=1");
                    exit();
                }
                mysqli_stmt_close($stmt);
            }
        }
        
        // If all options fail, show a helpful error message
        error_log("Contact form: All fallback options failed");
        header("Location: contact.php?error=1&msg=" . urlencode("Unable to send message. The system is currently experiencing issues. Please try again later or contact support directly."));
        exit();
    }
}

require_once 'includes/user_header.php';

// Get the hostel name and id based on whether this is a student or manager (for display purposes)
$hostel_name = '';
$hostel_id = null;
if (isset($_SESSION['hostel_id'])) {
    // For hostel manager
    $hostel_id = (int) $_SESSION['hostel_id'];
    $query = "SELECT Hostel_name FROM Hostel WHERE Hostel_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $hostel_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        $hostel_name = $row['Hostel_name'];
    }
} elseif (isset($_SESSION['roll'])) {
    // For student: get hostel name AND id
    $query = "SELECT h.Hostel_name, h.Hostel_id 
              FROM Student s 
              JOIN Hostel h ON s.Hostel_id = h.Hostel_id 
              WHERE s.Student_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $_SESSION['roll']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        $hostel_name = $row['Hostel_name'];
        $hostel_id = (int) $row['Hostel_id'];
    }
}

// Get student's hostel assignment status for display
$studentHostel = null;
if (isset($_SESSION['roll'])) {
    $sql_hostel = "SELECT Hostel_id, Room_id FROM Student WHERE Student_id = ?";
    $stmt_hostel = mysqli_prepare($conn, $sql_hostel);
    if ($stmt_hostel) {
        mysqli_stmt_bind_param($stmt_hostel, "s", $_SESSION['roll']);
        mysqli_stmt_execute($stmt_hostel);
        $result_hostel = mysqli_stmt_get_result($stmt_hostel);
        if ($row_hostel = mysqli_fetch_assoc($result_hostel)) {
            $studentHostel = $row_hostel;
        }
        mysqli_stmt_close($stmt_hostel);
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<title> PLYS | Contact </title>
    
    <!-- Meta tag Keywords -->
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
    
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            background: linear-gradient(135deg, #003366 0%, #004080 100%);
            color: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .section {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
        }
        .mail_grid_w3l {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        .mail_grid_w3l h2 {
            font-size: 2rem;
            color: #003366;
            text-align: center;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            font-weight: 600;
            margin-bottom: 8px;
            display: block;
            color: #003366;
        }
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 12px;
            width: 100%;
            box-sizing: border-box;
        }
        .btn-submit {
            background: linear-gradient(135deg, #003366 0%, #004080 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }
        .btn-submit:hover {
            background: linear-gradient(135deg, #ffcc00 0%, #ffdb4d 100%);
            color: #003366;
        }
        .alert {
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        .contact-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .contact-info h4 {
            color: #003366;
            margin-bottom: 15px;
        }
        .contact-info p {
            margin-bottom: 10px;
            color: #666;
        }
        .contact-info strong {
            color: #003366;
        }
    </style>
    <!--// Meta tag Keywords -->
        
<!-- css files -->
	<link rel="stylesheet" href="web_home/css_home/bootstrap.css"> <!-- Bootstrap-Core-CSS -->
	<link rel="stylesheet" href="web_home/css_home/style.css" type="text/css" media="all" /> <!-- Style-CSS --> 
	<!-- <link rel="stylesheet" href="web_home/css_home/fontawesome-all.css"> Font-Awesome-Icons-CSS -->
	<!-- //css files -->
	
	<!-- web-fonts -->
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            background: linear-gradient(135deg, #003366 0%, #004080 100%);
            color: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .header h1 {
            font-size: 2.5rem;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .header p {
            font-size: 1.1rem;
            margin: 0;
            opacity: 0.9;
        }
        .section {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
        }
        .section-header h3 {
            font-size: 1.8rem;
            font-weight: 600;
            color: #003366;
            margin: 0;
        }
        .section-header h3 i {
            margin-right: 10px;
            color: #003366;
        }
        .mail_grid_w3l {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        .mail_grid_w3l h2 {
            font-size: 2rem;
            color: #003366;
            text-align: center;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            font-weight: 600;
            margin-bottom: 8px;
            display: block;
            color: #003366;
            font-size: 0.95rem;
        }
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 12px;
            width: 100%;
            box-sizing: border-box;
            font-size: 0.95rem;
            font-family: 'Poppins', sans-serif;
        }
        .form-control:focus {
            border-color: #003366;
            outline: none;
            box-shadow: 0 0 0 0.2rem rgba(0, 51, 102, 0.25);
        }
        .btn-submit {
            background: linear-gradient(135deg, #003366 0%, #004080 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            font-family: 'Poppins', sans-serif;
        }
        .btn-submit:hover {
            background: linear-gradient(135deg, #ffcc00 0%, #ffdb4d 100%);
            color: #003366;
        }
        .alert {
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            font-family: 'Poppins', sans-serif;
        }
        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        .contact-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .contact-info h4 {
            color: #003366;
            margin-bottom: 15px;
            font-size: 1.3rem;
            font-weight: 600;
        }
        .contact-info p {
            margin-bottom: 10px;
            color: #666;
            font-size: 0.95rem;
        }
        .contact-info strong {
            color: #003366;
            font-weight: 600;
        }
    </style>

</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-envelope"></i> Contact Us</h1>
            <p>Get in touch with your hostel manager</p>
        </div>

        <?php if (isset($_SESSION['roll']) && (!$studentHostel || !$studentHostel['Hostel_id'] || !$studentHostel['Room_id'])): ?>
            <!-- No Hostel Assigned Message -->
            <div class="section">
                <div class="alert alert-warning" style="text-align: center; padding: 30px; border-radius: 10px;">
                    <h3 style="color: #856404; margin-bottom: 15px;">
                        <i class="fas fa-exclamation-triangle"></i> No Hostel Assigned
                    </h3>
                    <p style="color: #856404; font-size: 1.1rem; margin-bottom: 20px;">
                        You haven't been assigned to any hostel yet. Please contact the hostel administrator or apply for hostel accommodation first.
                    </p>
                    <a href="services.php" class="btn-submit" style="background: #003366; border: none; padding: 10px 25px; border-radius: 25px; text-decoration: none; display: inline-block;">
                        <i class="fas fa-home"></i> Apply for Hostel
                    </a>
                </div>
            </div>
        <?php else: ?>
        <!-- Contact Form Section -->
        <div class="section">
            <div class="section-header">
                <h3><i class="fas fa-paper-plane"></i> Send Message</h3>
            </div>
            
            <div class="mail_grid_w3l">
                <?php
                if (isset($_GET['success']) && $_GET['success'] == 1) {
                    echo '<div class="alert alert-success">Your message has been sent successfully!</div>';
                }
                if (isset($_GET['error']) && $_GET['error'] == 1) {
                    $error_msg = isset($_GET['msg']) ? urldecode($_GET['msg']) : 'There was an error sending your message. Please try again.';
                    echo '<div class="alert alert-danger">' . htmlspecialchars($error_msg) . '</div>';
                }
                ?>
                
                <form action="contact.php" method="post">
                    <div class="form-group">
                        <label for="name">Your Name</label>
                        <input type="text" id="name" name="name" class="form-control" required 
                               value="<?php echo isset($_SESSION['fname']) && isset($_SESSION['lname']) ? htmlspecialchars($_SESSION['fname'] . ' ' . $_SESSION['lname']) : ''; ?>" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Your Email</label>
                        <input type="email" id="email" name="email" class="form-control" required 
                               value="<?php echo isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : ''; ?>" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label for="hostel">Hostel</label>
                        <input type="text" id="hostel" name="hostel" class="form-control" readonly 
                               value="<?php echo htmlspecialchars($hostel_name); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <input type="text" id="subject" name="subject" class="form-control" placeholder="Enter message subject" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" class="form-control" rows="5" placeholder="Type your message here..." required></textarea>
                    </div>
                    
                    <button type="submit" name="submit" class="btn-submit">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Contact Information Section -->
        <div class="section">
            <div class="section-header">
                <h3><i class="fas fa-info-circle"></i> Contact Information</h3>
            </div>
            
            <div class="contact-info">
                <h4>Hostel Management</h4>
                <p><strong>Hostel:</strong> <?php echo htmlspecialchars($hostel_name ?: 'Not Available'); ?></p>
                <p><strong>Email:</strong> manager@hostel.com</p>
                <p><strong>Phone:</strong> +1234567890</p>
                <p><strong>Address:</strong> Hostel Management Office</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <footer class="py-5" style="background:#36454F;">
        <div class="container py-md-5">
            <div class="footer-logo mb-5 text-center">
                <a class="navbar-brand" href="home.php">Peaceful Living for Young <span class="display"> Scholars</span></a>
            </div>
            <div class="footer-grid">
                <div class="list-footer">
                    <ul class="footer-nav text-center">
                        <li>
                            <a href="home.php">Home</a>
                        </li>
                        <li>
                            <a href="contact.php">Contact</a>
                        </li>
                        <li>
                            <a href="student_profile.php">Profile</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script type="text/javascript" src="web_home/js/jquery-2.2.3.min.js"></script>
    <script type="text/javascript" src="web_home/js/bootstrap.js"></script>
    <script type="text/javascript" src="web_home/js/SmoothScroll.min.js"></script>
    <script type="text/javascript" src="web_home/js/move-top.js"></script>
    <script type="text/javascript" src="web_home/js/easing.js"></script>

</body>
</html>
