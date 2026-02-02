<?php
// CRITICAL: session_start() must be in manager_header.php or added here if not.
// Assuming manager_header.php includes session_start(), but adding a check just in case.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/manager_header.php'; // Assuming this includes config and session_start()

// Ensure hostel_id and hostel_man_id are available
$hostel_id = $_SESSION['hostel_id'] ?? null;
$hostel_man_id = $_SESSION['hostel_man_id'] ?? null;

if (!$hostel_id || !$hostel_man_id) {
    // Redirect or show error if manager info is not set
    echo "<script>document.addEventListener('DOMContentLoaded', function(){ showPopup('Manager session not found. Please log in again.','error'); setTimeout(function(){ window.location.href = 'login-hostel_manager.php'; }, 1600); });</script>";
    exit();
}

// --- CRITICAL SESSION MESSAGE CHECK AND CLEARING LOGIC ---
// This logic displays the success/error message and prevents the "0" output
if (isset($_SESSION['success_message'])) {
    $message = htmlspecialchars($_SESSION['success_message']);
    // Use the styled popup instead of alert
    echo "<script>document.addEventListener('DOMContentLoaded', function(){ showPopup('" . $message . "','success'); });</script>";
    // Clear the session variable immediately
    unset($_SESSION['success_message']);
} else if (isset($_SESSION['error_message'])) {
    $message = htmlspecialchars($_SESSION['error_message']);
    echo "<script>document.addEventListener('DOMContentLoaded', function(){ showPopup('" . $message . "','error'); });</script>";
    unset($_SESSION['error_message']);
}
// --------------------------------------------------------

$hostel_id_int = (int)$hostel_id;

// Variables for pre-populating the form (These seem unused on this page)
$prefill_student_id = $_GET['student_id'] ?? '';
$prefill_message = $_GET['message'] ?? '';
$prefill_subject = $_GET['subject'] ?? 'Room Change Notification'; // Default subject for room change

?>

<!DOCTYPE html>
<html lang="en">
<head>
<title>Message Manager</title>
    
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
    .table-container {
        background: white;
        border-radius: 10px;
        padding: 25px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        overflow-x: auto;
        margin-top: 30px;
    }
    .table {
        width: 100%;
        border-collapse: collapse;
    }
    .table th {
        background: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
        font-weight: 600;
        color: #003366;
        padding: 12px;
        text-align: left;
    }
    .table td {
        padding: 12px;
        vertical-align: middle;
        border-bottom: 1px solid #e9ecef;
    }
    .btn {
        padding: 8px 20px;
        border: none;
        border-radius: 20px;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-right: 10px;
    }
    .btn-primary {
        background: linear-gradient(135deg, #003366 0%, #004080 100%);
        color: white;
    }
    .btn-danger {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
    }
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    .btn-sm {
        padding: 6px 12px;
        font-size: 14px;
        margin-right: 5px;
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
    
    /* Footer styles */
    footer {
        background-color: #36454F;
        color: #ffffff;
        padding: 40px 0;
        text-align: center;
    }
    .footer-logo a {
        color: #ffffff;
        font-size: 1.5rem;
        text-decoration: none;
    }
    .footer-nav {
        list-style: none;
        padding: 0;
        margin: 20px 0 0;
    }
    .footer-nav li {
        display: inline-block;
        margin: 0 15px;
    }
    .footer-nav a {
        color: #ffffff;
        text-decoration: none;
        font-weight: 400;
    }
    .footer-nav a:hover {
        color: #ffcc00;
    }
    
    /* Message specific styles */
    .card {
        border: 1px solid #ddd;
        border-radius: 8px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .card-header {
        background: #f8f9fa;
        padding: 15px;
        border-bottom: 1px solid #ddd;
        font-weight: 600;
    }
    .card-body {
        padding: 15px;
    }
</style>
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-envelope"></i> Message Manager</h1>
            <p>Manage messages between students, managers, and admin</p>
        </div>

        <!-- Message History Section -->
        <div class="section">
            <div class="section-header">
                <h3><i class="fas fa-history"></i> Message History</h3>
            </div>
<?php

// Received messages (from students or admin)
$received_sql = "SELECT
                    m.*,
                    s.Fname AS student_fname,
                    s.Lname AS student_lname,
                    hm.Fname AS manager_fname,
                    hm.Lname AS manager_lname,
                    hm.Isadmin AS is_admin
                 FROM Message m
                 LEFT JOIN Student s ON m.sender_id = s.Student_id
                 LEFT JOIN hostel_manager hm ON m.sender_id = hm.Hostel_man_id
                 WHERE m.receiver_id = ?
                 ORDER BY m.msg_date DESC, m.msg_time DESC";

$rstmt = mysqli_prepare($conn, $received_sql);
mysqli_stmt_bind_param($rstmt, "s", $hostel_man_id);
mysqli_stmt_execute($rstmt);
$received = mysqli_stmt_get_result($rstmt);

$found_any = false;
if ($received && mysqli_num_rows($received) > 0) {
    $found_any = true;
    while ($row = mysqli_fetch_assoc($received)) {
        $sender = '';
        if ($row['is_admin'] == 1) {
            $sender = 'Admin';
        } else if (!empty($row['manager_fname'])) {
            $sender = trim($row['manager_fname'] . ' ' . $row['manager_lname']);
        } else if (!empty($row['student_fname'])) {
            $sender = trim($row['student_fname'] . ' ' . $row['student_lname']);
        } else {
            $sender = $row['sender_id']; // Fallback
        }
        ?>
        <div class="card mb-4" data-type="received">
            <div class="card-header">
                <b><?php echo htmlspecialchars($row['subject_h']); ?></b>
                <span style="float: right; font-size: 0.8em; color: #007bff">(Received)</span>
                <?php if (isset($row['message_type']) && $row['message_type'] === 'request'): ?>
                    <span style="float: right; margin-right: 10px; font-size: 0.8em; background: #ffc107; color: #212529; padding: 2px 8px; border-radius: 12px;">
                        <i class="fas fa-file-alt"></i> Request
                    </span>
                <?php endif; ?>
            </div>
            <div class="card-body"><?php echo nl2br(htmlspecialchars($row['message'])); ?></div>
            <div class="card-footer">
                <?php echo htmlspecialchars('From: ' . $sender); ?>
                <span style="float: right"><?php echo htmlspecialchars($row['msg_date'] . " " . $row['msg_time']); ?></span>
                <?php if (isset($row['message_type']) && $row['message_type'] === 'request' && isset($row['request_action']) && isset($row['student_id'])): ?>
                    <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #eee;">
                        <?php if ($row['request_action'] === 'change_room'): ?>
                            <a href="change_room.php?student_id=<?php echo htmlspecialchars($row['student_id']); ?>" class="btn btn-sm btn-primary" style="margin-right: 5px;">
                                <i class="fas fa-exchange-alt"></i> Change Room
                            </a>
                        <?php elseif ($row['request_action'] === 'change_food'): ?>
                            <a href="change_food_status.php?student_id=<?php echo htmlspecialchars($row['student_id']); ?>" class="btn btn-sm btn-primary" style="margin-right: 5px;">
                                <i class="fas fa-utensils"></i> Change Food Status
                            </a>
                        <?php endif; ?>
                        <button onclick="markAsCompleted(<?php echo $row['msg_id']; ?>)" class="btn btn-sm btn-success">
                            <i class="fas fa-check"></i> Mark as Completed
                        </button>
                    </div>
                <?php elseif (isset($row['subject_h']) && in_array($row['subject_h'], ['change_room', 'change_food']) && isset($row['sender_id'])): ?>
                    <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #eee;">
                        <a href="allocated_rooms.php" class="btn btn-sm btn-info" style="margin-right: 5px;">
                            <i class="fas fa-door-open"></i> View Allocated Rooms
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}

// Sent messages (messages this manager sent to others)
$sent_sql = "SELECT m.*, s.Fname AS other_fname, s.Lname AS other_lname
             FROM Message m
             LEFT JOIN Student s ON m.receiver_id = s.Student_id
             WHERE m.sender_id = ?
             ORDER BY m.msg_date DESC, m.msg_time DESC";

$sstmt = mysqli_prepare($conn, $sent_sql);
mysqli_stmt_bind_param($sstmt, "s", $hostel_man_id);
mysqli_stmt_execute($sstmt);
$sent = mysqli_stmt_get_result($sstmt);

if ($sent && mysqli_num_rows($sent) > 0) {
    $found_any = true;
    while ($row = mysqli_fetch_assoc($sent)) {
        $recipient = trim(($row['other_fname'] ?? '') . ' ' . ($row['other_lname'] ?? '')) ?: $row['receiver_id'];
        ?>
        <div class="card mb-4" data-type="sent">
            <div class="card-header">
                <b><?php echo htmlspecialchars($row['subject_h']); ?></b>
                <span style="float: right; font-size: 0.8em; color: #28a745">(Sent)</span>
            </div>
            <div class="card-body"><?php echo nl2br(htmlspecialchars($row['message'])); ?></div>
            <div class="card-footer">
                <?php echo htmlspecialchars('To: ' . $recipient); ?>
                <span style="float: right"><?php echo htmlspecialchars($row['msg_date'] . " " . $row['msg_time']); ?></span>
            </div>
        </div>
        <?php
    }
}

if (!$found_any) {
    echo "<p>No messages found.</p>";
}
// Note: Only close statements if they were successfully prepared/executed
if (isset($rstmt)) { mysqli_stmt_close($rstmt); }
if (isset($sstmt)) { mysqli_stmt_close($sstmt); }

// Mark all received messages as read
if (isset($conn) && $hostel_man_id) {
    $update_read_status_sql = "UPDATE Message SET read_status = 1 WHERE receiver_id = ? AND read_status = 0";
    $stmt_update_read_status = mysqli_prepare($conn, $update_read_status_sql);
    if ($stmt_update_read_status) {
        mysqli_stmt_bind_param($stmt_update_read_status, "s", $hostel_man_id);
        mysqli_stmt_execute($stmt_update_read_status);
        mysqli_stmt_close($stmt_update_read_status);
    }
}
?>
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
                        <a href="contact_manager.php">Contact</a>
                    </li>
                    <li>
                        <a href="admin/manager_profile.php">Profile</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</footer>
	<script type="text/javascript" src="web_home/js/jquery-2.2.3.min.js"></script>
	<script type="text/javascript" src="web_home/js/bootstrap.js"></script> <!-- Necessary-JavaScript-File-For-Bootstrap -->
	<!-- //js -->

	<!-- banner js -->
	<script src="web_home/js/snap.svg-min.js"></script>
	<script src="web_home/js/main.js"></script> <!-- Resource jQuery -->
	<!-- //banner js -->

	<!-- flexSlider --><!-- for testimonials -->
	<script defer src="web_home/js/jquery.flexslider.js"></script>
	<script type="text/javascript">
		$(window).load(function(){
		  $('.flexslider').flexslider({
			animation: "slide",
			start: function(slider){
			  $('body').removeClass('loading');
			}
		  });
		});
	</script>
	<!-- //flexSlider --><!-- for testimonials -->

	<!-- start-smoth-scrolling -->
	<script src="web_home/js/SmoothScroll.min.js"></script>
	<script type="text/javascript" src="web_home/js/move-top.js"></script>
	<script type="text/javascript" src="web_home/js/easing.js"></script>
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			$(".scroll").click(function(event){
				event.preventDefault();
				$('html,body').animate({scrollTop:$(this.hash).offset().top},1000);
			});
		});
	</script>
	<!-- here stars scrolling icon -->
	<script type="text/javascript">
		$(document).ready(function() {
			$().UItoTop({ easingType: 'easeOutQuart' });
		});
	</script>
	
	<!-- JavaScript for handling request completion -->
	<script>
	function markAsCompleted(messageId) {
		if (confirm('Are you sure you want to mark this request as completed?')) {
			// Create AJAX request to update message status
			var xhr = new XMLHttpRequest();
			xhr.open('POST', 'includes/mark_request_completed.php', true);
			xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
			xhr.onreadystatechange = function() {
				if (xhr.readyState === 4 && xhr.status === 200) {
					if (xhr.responseText === 'success') {
						showPopup('Request marked as completed successfully!', 'success');
						// Reload the page after a short delay
						setTimeout(function() {
							window.location.reload();
						}, 1500);
					} else {
						showPopup('Error marking request as completed. Please try again.', 'error');
					}
				}
			};
			xhr.send('message_id=' + messageId);
		}
	}
	</script>

<!-- Custom CSS/JS popup -->
<div id="custom-popup" class="custom-popup" role="alert" aria-hidden="true">
  <div class="custom-popup-inner">
    <button id="custom-popup-close" class="custom-popup-close" aria-label="Close">&times;</button>
    <div id="custom-popup-message" class="custom-popup-message"></div>
  </div>
</div>
<style>
/* Popup styles */
.custom-popup{display:none;position:fixed;right:20px;top:20px;z-index:9999;min-width:260px;max-width:360px;padding:0;}
.custom-popup.show{display:block;animation:fadeIn 0.25s ease-out}
.custom-popup-inner{position:relative;background:#fff;border-left:6px solid #28a745;padding:15px 15px 12px 15px;border-radius:6px;box-shadow:0 6px 18px rgba(0,0,0,0.12);}
.custom-popup.success .custom-popup-inner{border-color:#28a745}
.custom-popup.error .custom-popup-inner{border-color:#dc3545}
.custom-popup-message{font-size:14px;color:#222}
.custom-popup-close{background:transparent;border:0;font-size:20px;line-height:1;color:#666;position:absolute;right:8px;top:6px;cursor:pointer}
@keyframes fadeIn{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:none}}
</style>
<script>
function showPopup(message,type){var p=document.getElementById('custom-popup');var m=document.getElementById('custom-popup-message');p.className='custom-popup show '+(type==='success'?'success':'error');p.setAttribute('aria-hidden','false');m.textContent=message;clearTimeout(window._customPopupTimer);window._customPopupTimer=setTimeout(function(){p.classList.remove('show');p.setAttribute('aria-hidden','true');},3000);}document.addEventListener('click',function(e){if(e.target&& (e.target.id==='custom-popup' || e.target.id==='custom-popup-close')){document.getElementById('custom-popup').classList.remove('show');document.getElementById('custom-popup').setAttribute('aria-hidden','true');}});</script>

</body>
</html>