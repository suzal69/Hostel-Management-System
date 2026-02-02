<?php
require 'includes/config.inc.php';
require_once 'includes/manager_header.php';

$preselected_student_id = '';
$prefilled_message = '';
$action = '';
$room_id = '';
$hostel_id_vacate = '';
$room_no = '';


// Handle message for a NEW room allocation
if (isset($_GET['allocation_success']) && isset($_SESSION['last_allocated_student_id']) && isset($_SESSION['last_allocated_room_no'])) {
    $preselected_student_id = $_SESSION['last_allocated_student_id'];
    $room_no = $_SESSION['last_allocated_room_no'];
    $prefilled_message = "You have been allocated to room number " . $room_no . ".";

    // Unset session variables
    unset($_SESSION['last_allocated_student_id']);
    unset($_SESSION['last_allocated_room_no']);
}

// Initialize variables
$action = '';
$preselected_student_id = '';
$room_id = '';
$hostel_id_vacate = '';
$room_no = '';
$prefilled_subject = '';
$prefilled_message = '';

// Handle message for ALLOCATION SUCCESS
if (isset($_GET['allocation_success']) && isset($_SESSION['last_allocated_student_id']) && isset($_SESSION['last_allocated_room_no'])) {
    $preselected_student_id = $_SESSION['last_allocated_student_id'];
    $room_no = $_SESSION['last_allocated_room_no'];
    $prefilled_subject = "Room Allocation Notice";
    $prefilled_message = "You have been allocated to room number " . $room_no . ".";

    // Unset session variables
    unset($_SESSION['last_allocated_student_id']);
    unset($_SESSION['last_allocated_room_no']);
}

// Handle message for a ROOM CHANGE
if (isset($_GET['room_change_success']) && isset($_SESSION['last_allocated_student_id']) && isset($_SESSION['last_allocated_room_no'])) {
    $preselected_student_id = $_SESSION['last_allocated_student_id'];
    $room_no = $_SESSION['last_allocated_room_no'];
    $prefilled_subject = "Room Change Notification";
    $prefilled_message = "Your room has been changed to " . $room_no . ".";

    // Unset session variables
    unset($_SESSION['last_allocated_student_id']);
    unset($_SESSION['last_allocated_room_no']);
}

// Handle message for a ROOM VACATE
if (isset($_GET['action']) && $_GET['action'] == 'vacate') {
    $action = 'vacate';
    $preselected_student_id = $_GET['roll_no'];
    $room_id = $_GET['room_id'];
    $hostel_id_vacate = $_GET['hostel_id'];
    $room_no = $_GET['room_no'];
    $prefilled_subject = "Room Vacation Notice";
    $prefilled_message = "You are being vacated from room number " . $room_no . ". Please contact the manager for any queries.";
}

// Fetch student roll numbers for the manager's hostel
// This assumes 'hostel_id' is set in the session after the manager logs in.
$hostel_id = $_SESSION['hostel_id'];
$query6 = "SELECT * FROM Hostel WHERE Hostel_id = '$hostel_id'";
$result6 = mysqli_query($conn, $query6);
$row6 = mysqli_fetch_assoc($result6);
$hostel_name = $row6['Hostel_name'];

$student_rolls = [];
// Get students who are currently allocated to this hostel
$query_students = "SELECT s.Student_id, s.Fname, s.Lname 
                  FROM Student s 
                  WHERE s.Hostel_id = '$hostel_id' 
                  ORDER BY s.Student_id";
$result_students = mysqli_query($conn, $query_students);

if ($result_students) {
    while ($row = mysqli_fetch_assoc($result_students)) {
        $student_rolls[] = $row['Student_id'];
    }
} else {
    error_log("Error fetching students: " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Intrend Interior Category Flat Bootstrap Responsive Website Template | Contact : W3layouts</title>
    
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
</style>

</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-envelope"></i> Contact Manager</h1>
            <p>Send messages to students and manage communications</p>
        </div>

        <!-- Message Form Section -->
        <div class="section">
            <div class="section-header">
                <h3><i class="fas fa-paper-plane"></i> Reply Students</h3>
            </div>
            
            <div class="mail_grid_w3l">
            <form action="contact_manager.php" method="post">
                <?php if ($action === 'vacate') { ?>
                    <input type="hidden" name="action" value="vacate">
                    <input type="hidden" name="room_id" value="<?php echo htmlspecialchars($room_id); ?>">
                    <input type="hidden" name="hostel_id" value="<?php echo htmlspecialchars($hostel_id_vacate); ?>">
                    <input type="hidden" name="roll_no" value="<?php echo htmlspecialchars($preselected_student_id); ?>">
                    <input type="hidden" name="room_no" value="<?php echo htmlspecialchars($room_no); ?>">
                <?php } ?>
                
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" class="form-control" placeholder="Name" value="<?php echo htmlspecialchars($_SESSION['username']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="hostel_name">Hostel</label>
                    <input type="text" id="hostel_name" name="hostel_name" class="form-control" placeholder="Hostel" required value="<?php echo htmlspecialchars($hostel_name); ?>">
                </div>
                
                <div class="form-group">
                    <label for="student_roll_no">Student Roll Number</label>
                    <select id="student_roll_no" name="student_roll_no" class="form-control" required>
                        <option value="" disabled <?php if(empty($preselected_student_id)) echo 'selected'; ?>>Select Student Roll Number</option>
                        <?php
                        foreach ($student_rolls as $roll) {
                            $selected = ($roll == $preselected_student_id) ? 'selected' : '';
                            echo "<option value='".htmlspecialchars($roll)."' ".$selected.">".htmlspecialchars($roll)."</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="subject">Subject</label>
                    <input type="text" id="subject" name="subject" class="form-control" placeholder="Subject" value="<?php echo htmlspecialchars($prefilled_subject); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea id="message" name="message" class="form-control" placeholder="Message..." required><?php echo htmlspecialchars($prefilled_message); ?></textarea>
                </div>
                
                <button type="submit" name="submit" class="btn-submit">
                    <i class="fas fa-paper-plane"></i> Send Message
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

<!-- js-scripts -->     
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
			
			// Handle student selection to show details
			$('#student_roll_no').change(function() {
				var selectedRollNo = $(this).val();
				if (selectedRollNo) {
					// Fetch student details via AJAX
					$.ajax({
						url: 'includes/get_student_details.php',
						method: 'POST',
						data: { roll_no: selectedRollNo },
						dataType: 'json',
						success: function(data) {
							if (data && data.success) {
								// Display student details below the form
								var detailsHtml = '<div class="student-details-section" style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">' +
									'<h4><i class="fas fa-user"></i> Student Details</h4>' +
									'<p><strong>Name:</strong> ' + data.name + '</p>' +
									'<p><strong>Roll Number:</strong> ' + data.roll_no + '</p>' +
									'<p><strong>Contact:</strong> ' + data.contact + '</p>' +
									'<p><strong>Department:</strong> ' + data.department + '</p>' +
									'<p><strong>Year:</strong> ' + data.year + '</p>' +
									'<p><strong>Current Room:</strong> ' + (data.room_no || 'Not Allocated') + '</p>' +
									'</div>';
								
								// Remove any existing details
								$('.student-details-section').remove();
								// Add new details after the form
								$('.mail_grid_w3l form').after(detailsHtml);
							} else {
								// Remove any existing details
								$('.student-details-section').remove();
							}
						},
						error: function() {
							// Remove any existing details on error
							$('.student-details-section').remove();
						}
					});
				} else {
					// Remove details when no student is selected
					$('.student-details-section').remove();
				}
			});
		});
	</script>
        <!-- //here ends scrolling icon -->
    <!-- start-smoth-scrolling -->
<!-- //js-scripts -->

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

<?php
if (isset($_POST['submit'])) {
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);
    $hostel_name = mysqli_real_escape_string($conn, $_POST['hostel_name']);
    $roll = mysqli_real_escape_string($conn, $_POST['student_roll_no']);

    $man_id = $_SESSION['hostel_man_id'];

    $today_date = date("Y-m-d");
    $time = date("h:i A");

    $query = "INSERT INTO Message (sender_id, receiver_id, hostel_id, subject_h, message, msg_date, msg_time) VALUES ('$man_id', '$roll', '$hostel_id', '$subject', '$message', '$today_date', '$time')";
    $result = mysqli_query($conn, $query);

    if ($result) {
        echo "<script>document.addEventListener('DOMContentLoaded', function(){ showPopup('Message sent Successfully!','success'); });</script>";

        if (isset($_POST['action']) && $_POST['action'] == 'vacate') {
            $room_id_vacate = (int)$_POST['room_id'];
            $hostel_id_vacate = (int)$_POST['hostel_id'];
            $roll_no_vacate = $_POST['roll_no'];

            // Start transaction
            mysqli_begin_transaction($conn);

            try {
                // Step 1: Get the student's bed allocation
                $query_get_allocation = "SELECT allocation_id FROM bed_allocation 
                                       WHERE student_id = ? AND is_active = 1";
                $stmt_get = mysqli_prepare($conn, $query_get_allocation);
                mysqli_stmt_bind_param($stmt_get, "s", $roll_no_vacate);
                mysqli_stmt_execute($stmt_get);
                $result_get = mysqli_stmt_get_result($stmt_get);
                $allocation = mysqli_fetch_assoc($result_get);
                mysqli_stmt_close($stmt_get);

                if (!$allocation) {
                    throw new Exception("No active bed allocation found for student.");
                }

                // Step 2: Delete the bed allocation completely (not just deactivate)
                $query_delete_allocation = "DELETE FROM bed_allocation WHERE student_id = ? AND is_active = 1";
                $stmt_delete = mysqli_prepare($conn, $query_delete_allocation);
                mysqli_stmt_bind_param($stmt_delete, "s", $roll_no_vacate);
                mysqli_stmt_execute($stmt_delete);
                mysqli_stmt_close($stmt_delete);

                // Step 3: Update room occupancy
                $query_update_occupancy = "UPDATE Room SET current_occupancy = 
                                         (SELECT COUNT(*) FROM bed_allocation ba 
                                          WHERE ba.room_id = ? AND ba.is_active = 1)
                                         WHERE Room_id = ?";
                $stmt_occupancy = mysqli_prepare($conn, $query_update_occupancy);
                mysqli_stmt_bind_param($stmt_occupancy, "ii", $room_id_vacate, $room_id_vacate);
                mysqli_stmt_execute($stmt_occupancy);
                mysqli_stmt_close($stmt_occupancy);

                // Step 4: Update Student table: clear room and hostel assignments
                $query1 = "UPDATE Student SET Room_id = NULL, Hostel_id = NULL WHERE Student_id = ?";
                $stmt1 = mysqli_prepare($conn, $query1);
                mysqli_stmt_bind_param($stmt1, "s", $roll_no_vacate);
                mysqli_stmt_execute($stmt1);
                mysqli_stmt_close($stmt1);

                // Step 5: Update Room table - clear allocation if no more occupants
                $query_check_occupancy = "SELECT COUNT(*) as count FROM bed_allocation 
                                         WHERE room_id = ? AND is_active = 1";
                $stmt_check = mysqli_prepare($conn, $query_check_occupancy);
                mysqli_stmt_bind_param($stmt_check, "i", $room_id_vacate);
                mysqli_stmt_execute($stmt_check);
                $result_check = mysqli_stmt_get_result($stmt_check);
                $occupancy_check = mysqli_fetch_assoc($result_check);
                mysqli_stmt_close($stmt_check);

                if ($occupancy_check['count'] == 0) {
                    // Room is completely empty - clear allocation data
                    $query2 = "UPDATE Room SET Allocated = 0, current_occupancy = 0 WHERE Room_id = ?";
                    $stmt2 = mysqli_prepare($conn, $query2);
                    mysqli_stmt_bind_param($stmt2, "i", $room_id_vacate);
                    mysqli_stmt_execute($stmt2);
                    mysqli_stmt_close($stmt2);
                }

                // Update Hostel table
                $query3 = "UPDATE Hostel SET No_of_students = No_of_students - 1 WHERE Hostel_id = ?";
                $stmt3 = mysqli_prepare($conn, $query3);
                mysqli_stmt_bind_param($stmt3, "i", $hostel_id_vacate);
                mysqli_stmt_execute($stmt3);
                mysqli_stmt_close($stmt3);

                // Delete any pending application(s) for this student in this hostel
                $query4 = "DELETE FROM Application WHERE Student_id = ? AND Hostel_id = ?";
                $stmt4 = mysqli_prepare($conn, $query4);
                mysqli_stmt_bind_param($stmt4, "si", $roll_no_vacate, $hostel_id_vacate);
                mysqli_stmt_execute($stmt4);
                mysqli_stmt_close($stmt4);

                // Delete any messages sent by or to this student in this hostel
                $query5 = "DELETE FROM message WHERE (sender_id = ? OR receiver_id = ?) AND hostel_id = ?";
                $stmt5 = mysqli_prepare($conn, $query5);
                mysqli_stmt_bind_param($stmt5, "ssi", $roll_no_vacate, $roll_no_vacate, $hostel_id_vacate);
                mysqli_stmt_execute($stmt5);
                mysqli_stmt_close($stmt5);

                // Delete any complaints by this student in this hostel
                $query6 = "DELETE FROM complaints WHERE student_id = ? AND hostel_id = ?";
                $stmt6 = mysqli_prepare($conn, $query6);
                mysqli_stmt_bind_param($stmt6, "si", $roll_no_vacate, $hostel_id_vacate);
                mysqli_stmt_execute($stmt6);
                mysqli_stmt_close($stmt6);

                // Commit transaction
                mysqli_commit($conn);

                echo "<script>document.addEventListener('DOMContentLoaded', function(){ showPopup('Student has been vacated successfully.','success'); setTimeout(function(){ window.location.href='vacate_rooms.php'; }, 1600); });</script>";

            } catch (mysqli_sql_exception $exception) {
                mysqli_rollback($conn);
                echo "<script>document.addEventListener('DOMContentLoaded', function(){ showPopup('Failed to vacate student. Please try again.','error'); });</script>";
                throw $exception;
            }
        }
    } else {
        echo "<script>document.addEventListener('DOMContentLoaded', function(){ showPopup('Error in sending message! Please try again.','error'); });</script>";
    }
}
?>
