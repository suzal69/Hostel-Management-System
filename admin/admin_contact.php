<?php
require '../includes/config.inc.php';
require_once __DIR__ . '/admin_header.php';

// Handle form submission
if (isset($_POST['submit'])) {
    $receiver_id = $_POST['receiver_id'];
    $subject = $_POST['subject'];
    $message = $_POST['message'];
    $sender_id = $_SESSION['admin_id']; // Assuming admin session
    
    // Insert message into database
    $insert = "INSERT INTO message (sender_id, receiver_id, hostel_id, subject_h, message, msg_date, msg_time, read_status) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $insert);
    
    // Set current date and time
    $msg_date = date('Y-m-d');
    $msg_time = date('h:i A');
    $read_status = 0;
    
    mysqli_stmt_bind_param($stmt, "iisssssi", $sender_id, $receiver_id, $hostel_id, $subject, $message, $msg_date, $msg_time, $read_status);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "<script>alert('Message sent successfully!');</script>";
    } else {
        echo "<script>alert('Error sending message.');</script>";
    }
    mysqli_stmt_close($stmt);
}

// Fetch managers from the database
$managers = [];
$sql = "SELECT Hostel_man_id, Fname, Lname, Mob_no, email FROM Hostel_Manager WHERE Isadmin = 0";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $managers[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin Contact</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="keywords" content="Consultancy Profile Widget Responsive web template, Bootstrap Web Templates, Flat Web Templates, Android Compatible web template, Smartphone Compatible web template, free webdesigns for Nokia, Samsung, LG, SonyEricsson, Motorola web design" />
    <script type="application/x-javascript"> addEventListener("load", function() { setTimeout(hideURLbar, 0); }, false);
    function hideURLbar(){ window.scrollTo(0,1); } </script>
    <script src="web_profile/js/jquery-2.1.3.min.js" type="text/javascript"></script>
    <script type="text/javascript" src="web_profile/js/sliding.form.js"></script>
    <link href="web_profile/css/style.css" rel="stylesheet" type="text/css" media="all" />
    <link rel="stylesheet" href="web_profile/css/font-awesome.min.css" />
    <link rel="stylesheet" href="web_profile/css/smoothbox.css" type='text/css' media="all" />
    <link href="//fonts.googleapis.com/css?family=Pathway+Gothic+One" rel="stylesheet">
    <link href='//fonts.googleapis.com/css?family=Open+Sans:400,300,300italic,400italic,600,600italic,700,700italic,800,800italic' rel='stylesheet' type='text/css'>
    <link rel="stylesheet" href="web_home/css_home/bootstrap.css">
    <link rel="stylesheet" href="web_home/css_home/style.css" type="text/css" media="all" />
    <link rel="stylesheet" href="admin_styles.css">
</head>
<body>
    <br><br><br>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fa fa-envelope"></i> Contact Hostel Manager</h1>
            <p>Send messages to hostel managers and manage communications</p>
        </div>

        <!-- Message Form Section -->
        <div class="section">
            <div class="section-header">
                <h3><i class="fa fa-paper-plane"></i> Send Message</h3>
            </div>
            
            <div class="mail_grid_w3l">
                <h2>Contact Hostel Manager</h2>
                <form action="admin_contact.php" method="post">
                    <div class="form-group">
                        <label for="receiver_id">Select Manager</label>
                        <select id="receiver_id" name="receiver_id" class="form-control" required>
                            <option value="">Select Manager</option>
                            <?php foreach ($managers as $manager): ?>
                                <option value="<?php echo $manager['Hostel_man_id']; ?>">
                                    <?php echo htmlspecialchars($manager['Fname'] . ' ' . $manager['Lname']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <input type="text" id="subject" name="subject" placeholder="Subject" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" placeholder="Message..." class="form-control" rows="6" required></textarea>
                    </div>
                    
                    <button type="submit" name="submit" class="btn-submit">
                        <i class="fa fa-paper-plane"></i> Send Message
                    </button>
                </form>
            </div>
        </div>
    </div> <!-- Close main container -->

    <footer class="py-5" style="background:#36454F;">
        <div class="container py-md-5">
            <div class="footer-logo mb-5 text-center">
                <a class="navbar-brand" href="home.php">Peaceful Living for Young <span class="display"> Scholars</span></a>
            </div>
            <div class="footer-grid">
                <div class="list-footer">
                    <ul class="footer-nav text-center">
                        <li>
                            <a href="home_manager.php">Home</a>
                        </li>
                        <li>
                            <a href="allocate_room.php">Allocate</a>
                        </li>
                        <li>
                            <a href="admin_contact.php">Contact</a>
                        </li>
                        <li>
                            <a href="admin/manager_profile.php">Profile</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

<!-- js-scripts -->     
	<script type="text/javascript" src="web_home/js/jquery-2.2.3.min.js"></script>
	<script type="text/javascript" src="web_home/js/bootstrap.js"></script> <!-- Necessary-JavaScript-File-For-Bootstrap -->
	<!-- //js -->
</body>
</html>
                    