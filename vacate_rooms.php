<?php
// Ensure error reporting is enabled for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Ensure the connection and session are handled
require 'includes/config.inc.php';
require_once 'includes/manager_header.php'; // Assuming this handles session_start and connection

// Fetch Hostel details once
$hostel_id = $_SESSION['hostel_id'] ?? null;
$hostel_name = 'Unknown Hostel'; // Default value

if ($hostel_id) {
    $query1 = "SELECT Hostel_name FROM Hostel WHERE Hostel_id = ?";
    $stmt1 = mysqli_prepare($conn, $query1);
    if ($stmt1) {
        // Assume $hostel_id is an integer if coming from session
        $hostel_id_int = (int)$hostel_id; 
        mysqli_stmt_bind_param($stmt1, "i", $hostel_id_int);
        mysqli_stmt_execute($stmt1);
        $result1 = mysqli_stmt_get_result($stmt1);
        $row1 = mysqli_fetch_assoc($result1);
        $hostel_name = htmlspecialchars($row1['Hostel_name'] ?? 'Unknown Hostel');
        mysqli_stmt_close($stmt1);
    } else {
        error_log("vacate_rooms.php: mysqli_prepare failed for query1: " . mysqli_error($conn));
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<title>Vacate Room Form</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
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
    .social ul {
        list-style: none;
        padding: 0;
    }
    .social ul li {
        display: inline-block;
        margin: 0 15px;
    }
    .social ul li a {
        color: #ffffff;
        font-size: 1.2rem;
    }
    .social ul li a:hover {
        color: #ffcc00;
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
    .agileits_w3layouts-copyright {
        margin-top: 20px;
    }
    .agileits_w3layouts-copyright p {
        font-size: 0.9rem;
    }
</style>
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-door-open"></i> Vacate Room</h1>
            <p>Process student room vacate requests and update allocations</p>
        </div>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <!-- Main Content -->
        <div class="section">
            <div class="section-header">
                <h3><i class="fas fa-user-minus"></i> Vacate Room</h3>
            </div>
            
            <div class="mail_grid_w3l">
                <form action="vacate_rooms.php" method="post">
                    <div class="form-group">
                        <label for="roll_no">Select Roll Number</label>
                        <select name="roll_no" id="roll_no" required class="form-control">
                            <option value="">Select Roll Number</option>
                                <?php
                                $hostel_id_int = (int)$hostel_id; // Ensure for prepared statement
                                // Get all students in the current hostel who have a room_id (are allocated)
                                $query_students = "SELECT s.Student_id, s.Fname, s.Lname 
                                                   FROM Student s 
                                                   WHERE s.Hostel_id = ? AND s.Room_id IS NOT NULL
                                                   ORDER BY s.Student_id";
                                $stmt_students = mysqli_prepare($conn, $query_students);
                                if ($stmt_students) {
                                    mysqli_stmt_bind_param($stmt_students, "i", $hostel_id_int);
                                    mysqli_stmt_execute($stmt_students);
                                    $result_students = mysqli_stmt_get_result($stmt_students);
                                    while ($student = mysqli_fetch_assoc($result_students)) {
                                        echo '<option value="' . htmlspecialchars($student['Student_id']) . '">' 
                                             . htmlspecialchars($student['Student_id'] . ' - ' . $student['Fname'] . ' ' . $student['Lname'])
                                             . '</option>';
                                    }
                                    mysqli_stmt_close($stmt_students);
                                }
                                ?>
                            </select>
                    </div>
                    <div class="form-group">
                        <label for="hostel">Hostel</label>
                        <input type="text" name="hostel" id="hostel" class="form-control" value="<?php echo $hostel_name; ?>" required="" readonly>
                    </div>
                    <div class="form-group">
                        <label for="room_no">Room & Bed</label>
                        <input type="text" name="room_no" id="room_no" class="form-control" placeholder="Room & Bed" required="" readonly>
                    </div>
                    <div class="form-group">
                        <label for="start_date">Start Date</label>
                        <input type="text" name="start_date" id="start_date" class="form-control" placeholder="Start Date" required="" readonly>
                    </div>
                    <div class="form-group">
                        <label for="end_date">End Date</label>
                        <input type="text" name="end_date" id="end_date" class="form-control" placeholder="End Date" required="" readonly>
                    </div>
                    <input type="hidden" name="room_id" id="room_id">
                    <input type="hidden" name="bed_number" id="bed_number">
                    <button type="submit" name="submit" class="btn-submit">
                        <i class="fas fa-door-open"></i> Vacate Room
                    </button>
                </form>
            </div>
        </div>
    </div> <!-- Close main container -->
<?php
// --- Server-side Vacate Logic ---
if (isset($_POST['submit'])) {
    $hostel_id_int = (int)$hostel_id; // Re-declare for safety/scoping

    // Ensure both roll_no and room_id are available
    if (empty($_POST['roll_no']) || empty($_POST['room_id'])) {
        echo "<script>document.addEventListener('DOMContentLoaded', function(){ showPopup('Error: Roll Number or Room ID is missing. Please select a student and ensure room data is fetched.','error'); });</script>";
        // Prevent further execution
        goto end_of_php_block; 
    }
    
    // Sanitize and validate inputs
    $roll = $_POST['roll_no'];
    $room_id = (int)$_POST['room_id'];

    // Query 3: Verify the student/room assignment is correct for this manager's hostel
    $query3 = "SELECT * FROM Student WHERE Student_id = ? AND Hostel_id = ? AND Room_id = ?";
    $stmt3 = mysqli_prepare($conn, $query3);
    
    if ($stmt3 === false) {
        error_log("vacate_rooms.php: mysqli_prepare failed for query3: " . mysqli_error($conn));
        echo "<script>document.addEventListener('DOMContentLoaded', function(){ showPopup('Database error (query3 prepare).','error'); });</script>";
        goto end_of_php_block;
    }
    
    mysqli_stmt_bind_param($stmt3, "sii", $roll, $hostel_id_int, $room_id);
    mysqli_stmt_execute($stmt3);
    $result3 = mysqli_stmt_get_result($stmt3);

    if (mysqli_num_rows($result3) == 0) {
        echo "<script>document.addEventListener('DOMContentLoaded', function(){ showPopup('Verification Failed: The student is not currently allocated to this room in this hostel.','error'); });</script>";
        mysqli_stmt_close($stmt3);
        goto end_of_php_block;
    }
    mysqli_stmt_close($stmt3);

    // --- Redirect to Message Page ---
    // Pass information to the contact manager page to handle the vacating process after sending the message
    $roll_no_encoded = urlencode($roll);
    $room_id_encoded = urlencode($room_id);
    $hostel_id_encoded = urlencode($hostel_id_int);
    $room_no_encoded = urlencode($_POST['room_no']);
    $bed_number_encoded = urlencode($_POST['bed_number'] ?? '');

    $redirect_url = "contact_manager.php?action=vacate&roll_no={$roll_no_encoded}&room_id={$room_id_encoded}&hostel_id={$hostel_id_encoded}&room_no={$room_no_encoded}&bed_number={$bed_number_encoded}";
    echo "<script type='text/javascript'>window.location.href = '{$redirect_url}';</script>";

}
end_of_php_block:
?>

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
<script src="web_home/js/student-details.js"></script>
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
    <script type="text/javascript">
    $(document).ready(function() {
        // Function to load available rooms
        function loadAvailableRooms() {
            $.ajax({
                url: 'includes/get_available_rooms.php',
                method: 'POST',
                dataType: 'json',
                success: function(data) {
                    var roomSelect = $('#new_room_no');
                    roomSelect.html('<option value="">Select New Room</option>');
                    
                    if (data.rooms) {
                        $.each(data.rooms, function(index, room) {
                            roomSelect.append('<option value="' + room.Room_id + '">' + 
                                           'Room ' + room.Room_No + ' (' + room.available_beds + ' bed(s) available)</option>');
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Error loading rooms:", error);
                    showPopup('Error loading available rooms.', 'error');
                }
            });
        }
    }
        
        // Function to load available beds for selected room
        function loadAvailableBeds(roomId) {
            if (!roomId) {
                $('#new_bed_no').html('<option value="">Select Bed Number</option>');
                return;
            }
            
            $.ajax({
                url: 'includes/get_available_beds.php',
                method: 'POST',
                data: { room_id: roomId },
                dataType: 'json',
                success: function(data) {
                    var bedSelect = $('#new_bed_no');
                    bedSelect.html('<option value="">Select Bed Number</option>');
                    
                    if (data.success && data.beds) {
                        $.each(data.beds, function(index, bed) {
                            bedSelect.append('<option value="' + bed.bed_number + '">Bed ' + bed.bed_number + '</option>');
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Error loading beds:", error);
                    showPopup('Error loading available beds.', 'error');
                }
            });
        }
        
        // Load rooms on page load
        loadAvailableRooms();
        
        // When student selection changes
        $('select[name="roll_no"]').change(function() {
            var selectedRollNo = $(this).val();
            
            // Clear current room data
            $('#old_room_no').val('');
            
            if (selectedRollNo) {
                // Use AJAX to get the student's current room and bed
                $.ajax({
                    url: 'includes/get_student_room.php',
                    method: 'POST',
                    data: { roll_no: selectedRollNo },
                    dataType: 'json',
                    success: function(data) {
                        if (data && data.room_no && data.bed_number) {
                            // Show current room and bed
                            $('#old_room_no').val('Room ' + data.room_no + ', Bed ' + data.bed_number);
                        } else {
                            // If student is not allocated or an error occurred
                            $('#old_room_no').val('Not Allocated');
                            showPopup('Student is not currently allocated to a room.', 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error Status: " + status, "Error: " + error, "Response: " + xhr.responseText);
                        showPopup('Error fetching current room information. Check console for details.','error');
                    }
                });
            }
        });
        
        // When new room selection changes
        $('#new_room_no').change(function() {
            var selectedRoomId = $(this).val();
            loadAvailableBeds(selectedRoomId);
        });
    });
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