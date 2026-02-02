<?php
  require 'includes/config.inc.php';
  echo "<script>console.log('Page loaded successfully');</script>";
  require_once 'includes/user_header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<title> Intrend Interior Category Flat Bootstrap Responsive Website Template | Services : W3layouts</title>
	
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
	<!--// Meta tag Keywords -->
		
	<!-- css files -->
	<link rel="stylesheet" href="web_home/css_home/bootstrap.css"> <!-- Bootstrap-Core-CSS -->
	<link rel="stylesheet" href="web_home/css_home/style.css" type="text/css" media="all" /> 
	<!-- //css files -->
	
	<!-- web-fonts -->
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

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
        text-align: center;
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
        max-width: 800px;
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
    .contact-fields-w3ls input[type="text"],
    .contact-fields-w3ls textarea,
    .contact-fields-w3ls input[type="password"],
    .contact-fields-w3ls input[type="date"],
    .contact-fields-w3ls select {
        border: 2px solid #e9ecef;
        border-radius: 8px;
        padding: 12px;
        width: 100%;
        box-sizing: border-box;
        font-size: 0.95rem;
        font-family: 'Poppins', sans-serif;
        transition: border-color 0.3s ease;
    }
    .contact-fields-w3ls input:focus,
    .contact-fields-w3ls textarea:focus,
    .contact-fields-w3ls select:focus {
        border-color: #003366;
        outline: none;
        box-shadow: 0 0 0 0.2rem rgba(0, 51, 102, 0.25);
    }
    .contact-fields-w3ls textarea {
        height: 120px;
        resize: vertical;
    }
    .contact-fields-w3ls input[type="checkbox"] {
        margin-right: 8px;
        transform: scale(1.2);
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
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(255,204,0,0.3);
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
    .contact-fields-w3ls input[disabled] {
        background-color: #f8f9fa;
        color: #6c757d;
        cursor: not-allowed;
    }
    @media (max-width: 768px) {
        .header h1 {
            font-size: 2rem;
        }
        .section-header h3 {
            font-size: 1.5rem;
        }
        .mail_grid_w3l {
            padding: 20px;
        }
    }
    
    /* Request card hover effects */
    .request-card:hover {
        border-color: #003366;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }
    .request-card h4 i {
        margin-right: 8px;
    }
</style>

</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-file-alt"></i> Application Form</h1>
            <p>Apply for hostel accommodation</p>
        </div>

        <!-- Application Form Section -->
        <div class="section">
            <div class="section-header">
                <h3><i class="fa fa-edit"></i> Application Details</h3>
            </div>
            
            <div class="mail_grid_w3l">
                <form action="application_form.php?id=<?php echo isset($_GET['id']) ? htmlspecialchars($_GET['id']) : ''; ?>" method="post">
                        <!-- Original form content continues here -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Full Name</label>
                                    <input type="text" id="name" name="Name" value="<?php echo htmlspecialchars($_SESSION['fname'] . " " . $_SESSION['lname']); ?>" required disabled>
                                </div>
                                <div class="form-group">
                                    <label for="roll_no">Roll Number</label>
                                    <input type="text" id="roll_no" name="roll_no" value="<?php echo htmlspecialchars($_SESSION['roll']); ?>" required disabled>
                                </div>
                                <div class="form-group">
                                    <label for="hostel">Hostel</label>
                                    <input type="text" id="hostel" name="hostel" value="<?php echo isset($_GET['id']) ? htmlspecialchars($_GET['id']) : ''; ?>" required disabled>
                                </div>
                                <div class="form-group">
                                    <label for="pwd">Password</label>
                                    <input type="password" id="pwd" name="pwd" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="start_date">Start Date</label>
                                    <input type="date" id="start_date" name="start_date" required>
                                </div>
                                <div class="form-group">
                                    <label for="end_date">End Date</label>
                                    <input type="date" id="end_date" name="end_date" required>
                                </div>
                                <div class="form-group">
                                    <label for="room_select">Preferred Room</label>
                                    <select name="room_id" id="room_select" required>
                                        <option value="">Select Room</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="bed_select">Preferred Bed</label>
                                    <select name="bed_number" id="bed_select" disabled required>
                                        <option value="">Select Room First</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" id="include_food" name="include_food" onchange="toggleFoodOptions()">
                                        Include Food Service
                                    </label>
                                </div>
                                <div class="form-group" id="food_options" style="display: none;">
                                    <label for="food_plan">Food Plan</label>
                                    <select name="food_plan" id="food_plan">
                                        <option value="">Select Food Plan</option>
                                        <option value="basic">Basic: Breakfast + Dinner only (Rs500)</option>
                                        <option value="standard">Standard: Breakfast + Lunch + Dinner (Veg) (Rs1500)</option>
                                        <option value="premium">Premium: Breakfast + Lunch + Dinner (Veg/Non-Veg) + Evening Snacks (Rs2500)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="message">Additional Message</label>
                                    <textarea id="message" name="Message" placeholder="Enter any additional information..." required></textarea>
                                </div>
                            </div>
                        </div>
                        <button type="submit" name="submit" class="btn-submit">
                            <i class="fa fa-paper-plane"></i> Submit Application
                        </button>
                    </form>
                </div>
            </div>
    </div>
<!-- footer -->
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
						<a href="services.php">Hostels</a>
					</li>
					<li>
						<a href="contact.php">Contact</a>
					</li>
					<li>
						<a href="profile.php">Profile</a>
					</li>
				</ul>
			</div>
		</div>
	</div>
</footer>
<!-- footer -->

<!-- js-scripts -->		

	<!-- js -->
	<script type="text/javascript" src="web_home/js/jquery-2.2.3.min.js"></script>
	<script type="text/javascript" src="web_home/js/bootstrap.js"></script> <!-- Necessary-JavaScript-File-For-Bootstrap --> 
	<!-- //js -->

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
	<!-- //here ends scrolling icon -->
	<!-- start-smoth-scrolling -->
	
<!-- //js-scripts -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');
        const form = document.querySelector('form');

        // Calculate tomorrow's date
        const today = new Date();
        const tomorrow = new Date(today);
        tomorrow.setDate(tomorrow.getDate() + 1);
        
        const yyyy = tomorrow.getFullYear();
        const mm = String(tomorrow.getMonth() + 1).padStart(2, '0');
        const dd = String(tomorrow.getDate()).padStart(2, '0');
        const minDate = `${yyyy}-${mm}-${dd}`;

        // Set minimum start date to tomorrow
        startDateInput.setAttribute('min', minDate);

        // Update end date constraints when start date changes
        startDateInput.addEventListener('change', function() {
            const selectedStartDate = new Date(startDateInput.value);
            
            // Calculate minimum end date (6 months after start date)
            const minEndDate = new Date(selectedStartDate);
            minEndDate.setMonth(minEndDate.getMonth() + 6);
            
            // Calculate maximum end date (4 years after start date)
            const maxEndDate = new Date(selectedStartDate);
            maxEndDate.setFullYear(maxEndDate.getFullYear() + 4);
            
            const minEndDateStr = minEndDate.toISOString().split('T')[0];
            const maxEndDateStr = maxEndDate.toISOString().split('T')[0];
            
            endDateInput.setAttribute('min', minEndDateStr);
            endDateInput.setAttribute('max', maxEndDateStr);
            
            // Clear end date if it's before the new minimum
            if (endDateInput.value && new Date(endDateInput.value) < minEndDate) {
                endDateInput.value = '';
            }
        });

        // Validate on form submission
        form.addEventListener('submit', function(event) {
            const startDate = new Date(startDateInput.value);
            const endDate = new Date(endDateInput.value);
            
            // Check if start date is at least tomorrow
            if (startDate <= today) {
                event.preventDefault();
                showPopup('Start date must be from tomorrow onwards.','error');
                return false;
            }
            
            // Check if dates are not the same
            if (startDate.getTime() === endDate.getTime()) {
                event.preventDefault();
                showPopup('Start date and end date cannot be the same. They must have at least 6 months gap.','error');
                return false;
            }
            
            // Check if end date is at least 6 months after start date
            const minEndDate = new Date(startDate);
            minEndDate.setMonth(minEndDate.getMonth() + 6);
            if (endDate < minEndDate) {
                event.preventDefault();
                showPopup('End date must be at least 6 months after the start date.','error');
                return false;
            }
            
            // Check if duration does not exceed 4 years
            const maxEndDate = new Date(startDate);
            maxEndDate.setFullYear(maxEndDate.getFullYear() + 4);
            if (endDate > maxEndDate) {
                event.preventDefault();
                showPopup('Duration cannot exceed 4 years.','error');
                return false;
            }
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const hostelId = '<?php echo isset($_GET['id']) ? htmlspecialchars($_GET['id']) : ''; ?>';
    const roomSelect = document.getElementById('room_select');
    const bedSelect = document.getElementById('bed_select');
    
    if (hostelId) {
        // Load available rooms for this hostel
        fetch(`includes/get_available_rooms_for_applications.php?hostel_name=${encodeURIComponent(hostelId)}`)
            .then(response => response.json())
            .then(data => {
                roomSelect.innerHTML = '<option value="">Select Room</option>';
                if (data.success && data.rooms.length > 0) {
                    data.rooms.forEach(room => {
                        roomSelect.innerHTML += `<option value="${room.room_id}">Room ${room.room_no} (${room.available_beds} beds available)</option>`;
                    });
                } else {
                    roomSelect.innerHTML = '<option value="">No rooms available</option>';
                }
            })
            .catch(error => {
                console.error('Error loading rooms:', error);
                roomSelect.innerHTML = '<option value="">Error loading rooms</option>';
            });
    }
    
    // Handle room selection to populate bed options
    roomSelect.addEventListener('change', function() {
        const roomId = this.value;
        
        if (!roomId) {
            bedSelect.innerHTML = '<option value="">Select Room First</option>';
            bedSelect.disabled = true;
            return;
        }
        
        // Fetch available beds for selected room
        fetch(`includes/get_available_beds.php?room_id=${roomId}`)
            .then(response => response.json())
            .then(data => {
                bedSelect.innerHTML = '<option value="">Select Bed</option>';
                if (data.success && data.beds.length > 0) {
                    data.beds.forEach(bed => {
                        bedSelect.innerHTML += `<option value="${bed.bed_number}">Bed ${bed.bed_number}</option>`;
                    });
                    bedSelect.disabled = false;
                } else {
                    bedSelect.innerHTML = '<option value="">No beds available</option>';
                    bedSelect.disabled = true;
                }
            })
            .catch(error => {
                console.error('Error fetching beds:', error);
                bedSelect.innerHTML = '<option value="">Error loading beds</option>';
                bedSelect.disabled = true;
            });
    });
});

// Function to toggle food options dropdown
function toggleFoodOptions() {
    const includeFoodCheckbox = document.getElementById('include_food');
    const foodOptionsDiv = document.getElementById('food_options');
    const foodPlanSelect = document.getElementById('food_plan');
    
    if (includeFoodCheckbox.checked) {
        foodOptionsDiv.style.display = 'block';
        foodPlanSelect.required = true;
    } else {
        foodOptionsDiv.style.display = 'none';
        foodPlanSelect.required = false;
        foodPlanSelect.value = '';
    }
}
</script>

</body>
</html>

<?php
   if(isset($_POST['submit'])){
     echo "<script>console.log('PHP processing started');</script>";
     $roll = $_SESSION['roll'];
     echo "<script>console.log('Student roll: $roll');</script>";
     $password = $_POST['pwd'];
     $hostel = $_GET['id'];
     echo "<script>console.log('Hostel: $hostel');</script>";
     $message = $_POST['Message'];
     $start_date = $_POST['start_date'];
     $end_date = $_POST['end_date'];
     $preferred_room_id = isset($_POST['room_id']) ? (int)$_POST['room_id'] : null;
     $preferred_bed_number = isset($_POST['bed_number']) ? (int)$_POST['bed_number'] : null;
     $include_food = isset($_POST['include_food']) ? 1 : 0;
     $food_plan = isset($_POST['food_plan']) ? $_POST['food_plan'] : null;

     $query_imp = "SELECT * FROM Student WHERE Student_id = '$roll'";
     $result_imp = mysqli_query($conn,$query_imp);
     $row_imp = mysqli_fetch_assoc($result_imp);
     $room_id = $row_imp['Room_id'];
     echo "<script>console.log('Current room_id: $room_id');</script>";
     if(is_null($room_id)){
         echo "<script>console.log('Student has no room - proceeding');</script>";
     
     $query_imp2 = "SELECT * FROM Application WHERE Student_id = '$roll'";
     $result_imp2 = mysqli_query($conn,$query_imp2);
     echo "<script>console.log('Existing applications: " . mysqli_num_rows($result_imp2) . "');</script>";
     if(mysqli_num_rows($result_imp2)==0){
         echo "<script>console.log('No existing applications - proceeding');</script>";
     $query = "SELECT * FROM Student WHERE Student_id = '$roll'";
     $result = mysqli_query($conn,$query);
     if($row = mysqli_fetch_assoc($result)){
     	$pwdCheck = password_verify($password, $row['Pwd']);
     	echo "<script>console.log('Password check: " . ($pwdCheck ? 'success' : 'failed') . "');</script>";
     	if($pwdCheck == false){
            echo "<script>document.addEventListener('DOMContentLoaded', function(){ showPopup('Incorrect Password!!','error'); });</script>";
     	}
     	else if($pwdCheck == true) {
            echo "<script>console.log('Password verified - proceeding');</script>";
         $query2 = "SELECT * FROM Hostel WHERE Hostel_name = '$hostel'";
         $result2 = mysqli_query($conn,$query2);
         $row2 = mysqli_fetch_assoc($result2);
         $hostel_id = (int)$row2['Hostel_id'];
         echo "<script>console.log('Hostel ID: $hostel_id');</script>";
         
         // Validate room and bed preferences if provided
         if ($preferred_room_id && $preferred_bed_number) {
             $room_check = "SELECT * FROM Room WHERE Room_id = ? AND Hostel_id = ?";
             $stmt_room = mysqli_prepare($conn, $room_check);
             mysqli_stmt_bind_param($stmt_room, "ii", $preferred_room_id, $hostel_id);
             mysqli_stmt_execute($stmt_room);
             $room_result = mysqli_stmt_get_result($stmt_room);
             
             if (mysqli_num_rows($room_result) == 0) {
                 echo "<script>document.addEventListener('DOMContentLoaded', function(){ showPopup('Invalid room selection','error'); });</script>";
                 mysqli_stmt_close($stmt_room);
                 exit();
             }
             
             $room_data = mysqli_fetch_assoc($room_result);
             if ($preferred_bed_number > $room_data['bed_capacity']) {
                 echo "<script>document.addEventListener('DOMContentLoaded', function(){ showPopup('Invalid bed number for selected room','error'); });</script>";
                 mysqli_stmt_close($stmt_room);
                 exit();
             }
             
             // Check if bed is already occupied
             $bed_check = "SELECT allocation_id FROM bed_allocation WHERE room_id = ? AND bed_number = ? AND is_active = 1";
             $stmt_bed = mysqli_prepare($conn, $bed_check);
             mysqli_stmt_bind_param($stmt_bed, "ii", $preferred_room_id, $preferred_bed_number);
             mysqli_stmt_execute($stmt_bed);
             $bed_result = mysqli_stmt_get_result($stmt_bed);
             
             if (mysqli_num_rows($bed_result) > 0) {
                 echo "<script>document.addEventListener('DOMContentLoaded', function(){ showPopup('Selected bed is already occupied','error'); });</script>";
                 mysqli_stmt_close($stmt_room);
                 mysqli_stmt_close($stmt_bed);
                 exit();
             }
             
             mysqli_stmt_close($stmt_room);
             mysqli_stmt_close($stmt_bed);
         }
         
         $query3 = "INSERT INTO Application (Student_id, Hostel_id, Application_status, Message, start_date, end_date, preferred_room_id, preferred_bed_number, include_food, food_plan) 
                    VALUES ('$roll', '$hostel_id', 1, '$message', '$start_date', '$end_date', '$preferred_room_id', '$preferred_bed_number', '$include_food', '$food_plan')";
         echo "<script>console.log('About to execute simple query');</script>";
         $result3 = mysqli_query($conn, $query3);
         echo "<script>console.log('Simple query result: " . ($result3 ? 'success' : 'failed') . "');</script>";
         if($result3){
             echo "<script>console.log('Application inserted successfully');</script>";
             echo "<script>document.addEventListener('DOMContentLoaded', function(){ showPopup('Application sent successfully','success'); });</script>";
         } else {
             echo "<script>console.log('Simple query error: " . mysqli_error($conn) . "');</script>";
             echo "<script>document.addEventListener('DOMContentLoaded', function(){ showPopup('Error submitting application','error'); });</script>";
         }
     	}
     }
     }
     else{
     	echo "<script>document.addEventListener('DOMContentLoaded', function(){ showPopup('You have Already applied for a Room','error'); });</script>";
     }
     }
     else{
          echo "<script>document.addEventListener('DOMContentLoaded', function(){ showPopup('You have Already been alloted a Room','error'); });</script>";   
      }
   }
?>