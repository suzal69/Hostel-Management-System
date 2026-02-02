<?php
require_once 'includes/manager_header.php';

$hostel_id = $_SESSION['hostel_id'] ?? null;

// Function to display food plan information
function getFoodPlanDisplay($include_food, $food_plan) {
    if (!$include_food || empty($food_plan)) {
        return '<span class="badge badge-secondary">No Food</span>';
    }
    
    switch ($food_plan) {
        case 'basic':
            return '<span class="badge badge-info">Basic (Rs500)</span>';
        case 'standard':
            return '<span class="badge badge-warning">Standard (Rs1500)</span>';
        case 'premium':
            return '<span class="badge badge-success">Premium (Rs2500)</span>';
        default:
            return '<span class="badge badge-secondary">Unknown</span>';
    }
}
?>

<!-- In-Page CSS for Tables -->
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
    .table-container {
        background: white;
        border-radius: 10px;
        padding: 25px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        overflow-x: auto;
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
    .btn-success {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
    }
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
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
<!-- End of In-Page CSS for Tables -->


<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-bed"></i> Allocate Room</h1>
            <p>Manage room allocation for students in your hostel</p>
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
                <h3><i class="fas fa-search"></i> Search Student</h3>
            </div>
            
            <form action="allocate_room.php" method="post">
                <div class="form-group">
                    <label for="search_box">Search by Roll Number</label>
                    <input type="text" id="search_box" name="search_box" class="form-control" placeholder="Enter Roll Number" value="<?php echo isset($_POST['search_box']) ? htmlspecialchars($_POST['search_box']) : '' ?>">
                </div>
                <button type="submit" name="search" class="btn-submit">
                    <i class="fas fa-search"></i> Search Student
                </button>
            </form>
        </div>

<?php
if (isset($_POST['search'])) {
    $search_box = mysqli_real_escape_string($conn, $_POST['search_box']);

    $query_search = "SELECT * FROM Application WHERE Student_id LIKE '{$search_box}%' AND Hostel_id = '$hostel_id' AND Application_status = '1'";
    $result_search = mysqli_query($conn, $query_search);

    // Get Hostel name
    $query_hostel = "SELECT Hostel_name FROM Hostel WHERE Hostel_id = '$hostel_id'";
    $result_hostel = mysqli_query($conn, $query_hostel);
    $hostel_name = "";
    if ($row_hostel = mysqli_fetch_assoc($result_hostel)) {
        $hostel_name = $row_hostel['Hostel_name'];
    }
    ?>

    <!-- Search Results -->
    <div class="section">
        <div class="section-header">
            <h3><i class="fas fa-users"></i> Search Results</h3>
        </div>
        
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Student ID</th>
                        <th>Hostel</th>
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (!$result_search || mysqli_num_rows($result_search) == 0) {
                        echo '<tr><td colspan="4">No students found</td></tr>';
                    } else {
                        while ($row_search = mysqli_fetch_assoc($result_search)) {
                            $student_id = $row_search['Student_id'];
                            $query_student = "SELECT Fname, Lname FROM Student WHERE Student_id = '$student_id'";
                            $result_student = mysqli_query($conn, $query_student);
                            $student_name = "Unknown";
                            if ($row_student = mysqli_fetch_assoc($result_student)) {
                                $student_name = htmlspecialchars($row_student['Fname'] . " " . $row_student['Lname']);
                            }

                            echo "<tr><td>{$student_name}</td><td>{$row_search['Student_id']}</td><td>" . htmlspecialchars($hostel_name) . "</td><td>" . htmlspecialchars($row_search['Message']) . "</td></tr>";
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

<?php
}
?>

<?php
// Fetch all pending applications for the hostel
$query_applications = "SELECT a.*, s.Fname, s.Lname, s.admission_date as application_date 
                      FROM application a 
                      JOIN student s ON a.Student_id = s.Student_id 
                      WHERE a.Hostel_id = '$hostel_id' AND a.Application_status = '1' 
                      ORDER BY a.Application_id DESC";
$result_applications = mysqli_query($conn, $query_applications);

// Get Hostel name
$query_hostel = "SELECT Hostel_name FROM Hostel WHERE Hostel_id = '$hostel_id'";
$result_hostel = mysqli_query($conn, $query_hostel);
$hostel_name = "";
if ($row_hostel = mysqli_fetch_assoc($result_hostel)) {
    $hostel_name = $row_hostel['Hostel_name'];
}
?>

<!-- Applications Received -->
    <div class="section">
        <div class="section-header">
            <h3><i class="fas fa-clipboard-list"></i> Applications Received</h3>
        </div>
        
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Student ID</th>
                        <th>Hostel</th>
                        <th>Message</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Food Plan</th>
                        <th>Student Preferences</th>
                        <th>Room & Bed Allocation</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
<?php
if (!$result_applications || mysqli_num_rows($result_applications) == 0) {
    echo '<tr><td colspan="10">No Rows Returned</td></tr>';
} else {
    while ($row = mysqli_fetch_assoc($result_applications)) {
        $student_id = $row['Student_id'];
        $application_id = $row['Application_id'];
        
        // Use student name from joined query
        $student_name = htmlspecialchars($row['Fname'] . " " . $row['Lname']);
        
        // Get student preferences
        $preferences = "";
        if (!empty($row['preferred_room_id']) && !empty($row['preferred_bed_number'])) {
            // Get room details for preference
            $pref_room_query = "SELECT Room_No FROM Room WHERE Room_id = ?";
            $stmt_pref = mysqli_prepare($conn, $pref_room_query);
            mysqli_stmt_bind_param($stmt_pref, "i", $row['preferred_room_id']);
            mysqli_stmt_execute($stmt_pref);
            $pref_result = mysqli_stmt_get_result($stmt_pref);
            if ($pref_room = mysqli_fetch_assoc($pref_result)) {
                $preferences = "Room {$pref_room['Room_No']}, Bed {$row['preferred_bed_number']}";
            }
            mysqli_stmt_close($stmt_pref);
        } else {
            $preferences = "No preference";
        }
        
        // Get available rooms with beds for this hostel
        $rooms_query = "SELECT r.Room_id, r.Room_No, r.bed_capacity,
                        (SELECT COUNT(*) FROM bed_allocation ba WHERE ba.room_id = r.Room_id AND ba.is_active = 1) as current_occupancy,
                        (SELECT GROUP_CONCAT(ba.bed_number) FROM bed_allocation ba WHERE ba.room_id = r.Room_id AND ba.is_active = 1) as occupied_beds
                        FROM Room r 
                        WHERE r.Hostel_id = ? 
                        ORDER BY r.Room_No";
        $stmt_rooms = mysqli_prepare($conn, $rooms_query);
        mysqli_stmt_bind_param($stmt_rooms, "i", $hostel_id);
        mysqli_stmt_execute($stmt_rooms);
        $result_rooms = mysqli_stmt_get_result($stmt_rooms);
        
        $room_options = "";
        while ($room = mysqli_fetch_assoc($result_rooms)) {
            $available_beds = $room['bed_capacity'] - $room['current_occupancy'];
            $selected = ($room['Room_id'] == $row['preferred_room_id']) ? 'selected' : '';
            
            // Show all rooms, but indicate availability
            if ($available_beds > 0) {
                $room_options .= "<option value='{$room['Room_id']}' data-capacity='{$room['bed_capacity']}' data-occupied='{$room['current_occupancy']}' data-occupied-beds='{$room['occupied_beds']}' {$selected}>Room {$room['Room_No']} ({$available_beds} beds available)</option>";
            } else {
                $room_options .= "<option value='{$room['Room_id']}' data-capacity='{$room['bed_capacity']}' data-occupied='{$room['current_occupancy']}' data-occupied-beds='{$room['occupied_beds']}' disabled>Room {$room['Room_No']} (Full)</option>";
            }
        }
        mysqli_stmt_close($stmt_rooms);
        
        echo "<tr>
                <td>{$student_name}</td>
                <td>{$row['Student_id']}</td>
                <td>" . htmlspecialchars($hostel_name) . "</td>
                <td>" . htmlspecialchars($row['Message']) . "</td>
                <td>" . htmlspecialchars($row['start_date']) . "</td>
                <td>" . htmlspecialchars($row['end_date']) . "</td>
                <td>" . getFoodPlanDisplay($row['include_food'], $row['food_plan']) . "</td>
                <td><strong>{$preferences}</strong></td>
                <td>
                    <select name='room_id_{$application_id}' class='form-control room-select' data-application-id='{$application_id}' required>
                        <option value=''>Select Room</option>
                        {$room_options}
                    </select>
                    <select name='bed_number_{$application_id}' class='form-control bed-select mt-2' data-application-id='{$application_id}' disabled required>
                        <option value=''>Select Room First</option>
                    </select>
                </td>
                <td>
                    <button class='btn btn-sm btn-primary allocate-btn' data-app-id='{$application_id}'>Allocate</button>
                    <button class='btn btn-sm btn-danger delete-btn ml-1' data-app-id='{$application_id}' data-student-id='{$student_id}'>Delete</button>
                </td>
              </tr>";
    }
}
?>
</tbody>
</table>
</div>
</div>
    </div> <!-- Close main container -->
    </section>

<?php
// Handle individual student allocation with room and bed selection
if (isset($_POST['allocate_student'])) {
    $application_id = (int)$_POST['application_id'];
    $room_id = (int)$_POST['room_id'];
    $bed_number = (int)$_POST['bed_number'];
    
    // Get application details
    $query_app = "SELECT * FROM Application WHERE Application_id = ? AND Hostel_id = ? AND Application_status = '1'";
    $stmt_app = mysqli_prepare($conn, $query_app);
    mysqli_stmt_bind_param($stmt_app, "ii", $application_id, $hostel_id);
    mysqli_stmt_execute($stmt_app);
    $result_app = mysqli_stmt_get_result($stmt_app);
    
    if (!$result_app || mysqli_num_rows($result_app) == 0) {
        echo "<script>document.addEventListener('DOMContentLoaded', function(){ showPopup('Application not found or already processed.','error'); });</script>";
        exit();
    }
    
    $application = mysqli_fetch_assoc($result_app);
    $student_id = $application['Student_id'];
    
    // Get room details
    $query_room = "SELECT * FROM Room WHERE Room_id = ? AND Hostel_id = ?";
    $stmt_room = mysqli_prepare($conn, $query_room);
    mysqli_stmt_bind_param($stmt_room, "ii", $room_id, $hostel_id);
    mysqli_stmt_execute($stmt_room);
    $result_room = mysqli_stmt_get_result($stmt_room);
    
    if (!$result_room || mysqli_num_rows($result_room) == 0) {
        echo "<script>document.addEventListener('DOMContentLoaded', function(){ showPopup('Room not found.','error'); });</script>";
        exit();
    }
    
    $room = mysqli_fetch_assoc($result_room);
    
    // Check if room has reached full capacity
    if ($room['current_occupancy'] >= $room['bed_capacity']) {
        echo "<script>document.addEventListener('DOMContentLoaded', function(){ showPopup('Room has reached full capacity.','error'); });</script>";
        exit();
    }
    
    // Check if bed is already occupied
    $query_check_bed = "SELECT allocation_id FROM bed_allocation 
                        WHERE room_id = ? AND bed_number = ? AND is_active = 1";
    $stmt_check = mysqli_prepare($conn, $query_check_bed);
    mysqli_stmt_bind_param($stmt_check, "ii", $room_id, $bed_number);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);
    
    if (mysqli_num_rows($result_check) > 0) {
        echo "<script>document.addEventListener('DOMContentLoaded', function(){ showPopup('Selected bed is already occupied.','error'); });</script>";
        exit();
    }
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Create bed allocation
        $start_date = date("Y-m-d");
        $end_date = date('Y-m-d', strtotime('+1 year'));
        
        // Calculate base price
        $base_price = 5000; // Default room price
        $food_price = 0;
        
        // Add food plan pricing
        if ($application['include_food'] && !empty($application['food_plan'])) {
            switch ($application['food_plan']) {
                case 'basic':
                    $food_price = 500;
                    break;
                case 'standard':
                    $food_price = 1500;
                    break;
                case 'premium':
                    $food_price = 2500;
                    break;
            }
        }
        
        $allocation_price = $base_price + $food_price;
        
        $query_alloc = "INSERT INTO bed_allocation 
                        (student_id, room_id, bed_number, allocation_price, start_date, end_date, is_active, include_food, food_plan)
                        VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?)";
        $stmt_alloc = mysqli_prepare($conn, $query_alloc);
        mysqli_stmt_bind_param($stmt_alloc, "siidssis", $student_id, $room_id, $bed_number, $allocation_price, $start_date, $end_date, $application['include_food'], $application['food_plan']);
        mysqli_stmt_execute($stmt_alloc);
        mysqli_stmt_close($stmt_alloc);
        
        // Update room occupancy
        $query_update_occupancy = "UPDATE Room SET current_occupancy = 
                                  (SELECT COUNT(*) FROM bed_allocation ba 
                                   WHERE ba.room_id = ? AND ba.is_active = 1)
                                  WHERE Room_id = ?";
        $stmt_occupancy = mysqli_prepare($conn, $query_update_occupancy);
        mysqli_stmt_bind_param($stmt_occupancy, "ii", $room_id, $room_id);
        mysqli_stmt_execute($stmt_occupancy);
        mysqli_stmt_close($stmt_occupancy);
        
        // Update room allocation status if this is the first occupant
        if ($room['current_occupancy'] == 1) {
            $query_update_room = "UPDATE Room SET Allocated = 1 WHERE Room_id = ?";
            $stmt_update_room = mysqli_prepare($conn, $query_update_room);
            mysqli_stmt_bind_param($stmt_update_room, "i", $room_id);
            mysqli_stmt_execute($stmt_update_room);
            mysqli_stmt_close($stmt_update_room);
        }
        
        // Update hostel student count
        $query_update_hostel = "UPDATE Hostel SET No_of_students = No_of_students + 1 WHERE Hostel_id = ?";
        $stmt_hostel = mysqli_prepare($conn, $query_update_hostel);
        mysqli_stmt_bind_param($stmt_hostel, "i", $hostel_id);
        mysqli_stmt_execute($stmt_hostel);
        mysqli_stmt_close($stmt_hostel);
        
        // Update student table with allocation details
        $query_update_student = "UPDATE student SET Hostel_id = ?, Room_id = ?, admission_date = CURDATE(), start_date = ?, end_date = ? WHERE Student_id = ?";
        $stmt_update_student = mysqli_prepare($conn, $query_update_student);
        mysqli_stmt_bind_param($stmt_update_student, "iisss", $hostel_id, $room_id, $application['start_date'], $application['end_date'], $student_id);
        mysqli_stmt_execute($stmt_update_student);
        mysqli_stmt_close($stmt_update_student);
        
        // Update application table with allocated room details and mark as processed
        $query_update_app = "UPDATE application SET Application_status = '0', Room_No = ? WHERE Application_id = ?";
        $stmt_update_app = mysqli_prepare($conn, $query_update_app);
        mysqli_stmt_bind_param($stmt_update_app, "ii", $room['Room_No'], $application_id);
        mysqli_stmt_execute($stmt_update_app);
        mysqli_stmt_close($stmt_update_app);
        
        // Set session variables
        $_SESSION['last_allocated_student_id'] = $student_id;
        $_SESSION['last_allocated_room_no'] = $room['Room_No'];
        
        // Send message to student about allocation
        $current_date = date('Y-m-d');
        $current_time = date('h:i A');
        $subject = "Room Allocation Confirmation";
        $message_text = "Your room has been allocated successfully! Room: {$room['Room_No']}, Bed: {$bed_number}. Hostel: {$hostel_name}. Please check your student portal for details.";
        
        $query_insert_message = "INSERT INTO message (sender_id, receiver_id, hostel_id, subject_h, message, msg_date, msg_time, read_status) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, 0)";
        $stmt_message = mysqli_prepare($conn, $query_insert_message);
        $sender_id = $_SESSION['hostel_man_id'] ?? 'admin';
        mysqli_stmt_bind_param($stmt_message, "ssissss", $sender_id, $student_id, $hostel_id, $subject, $message_text, $current_date, $current_time);
        mysqli_stmt_execute($stmt_message);
        mysqli_stmt_close($stmt_message);
        
        mysqli_commit($conn);
        echo "<script>document.addEventListener('DOMContentLoaded', function(){ showPopup('Room allocated successfully! Message sent to student.','success'); });</script>";
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo "<script>document.addEventListener('DOMContentLoaded', function(){ showPopup('Allocation failed. Please try again.','error'); });</script>";
    }
    
    mysqli_stmt_close($stmt_app);
    mysqli_stmt_close($stmt_room);
    mysqli_stmt_close($stmt_check);
}

// Handle application deletion
if (isset($_POST['delete_application'])) {
    $application_id = (int)$_POST['application_id'];
    $student_id = $_POST['student_id'];
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Delete the application
        $query_delete_app = "DELETE FROM Application WHERE Application_id = ? AND Hostel_id = ? AND Application_status = '1'";
        $stmt_delete = mysqli_prepare($conn, $query_delete_app);
        mysqli_stmt_bind_param($stmt_delete, "ii", $application_id, $hostel_id);
        mysqli_stmt_execute($stmt_delete);
        
        // Check if any row was actually deleted
        if (mysqli_stmt_affected_rows($stmt_delete) > 0) {
            mysqli_commit($conn);
            echo "<script>document.addEventListener('DOMContentLoaded', function(){ showPopup('Application deleted successfully!','success'); });</script>";
        } else {
            mysqli_rollback($conn);
            echo "<script>document.addEventListener('DOMContentLoaded', function(){ showPopup('Application not found or already processed.','error'); });</script>";
        }
        mysqli_stmt_close($stmt_delete);
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo "<script>document.addEventListener('DOMContentLoaded', function(){ showPopup('Deletion failed. Please try again.','error'); });</script>";
    }
}


?>
</body>
</html>

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
function showPopup(message,type){var p=document.getElementById('custom-popup');var m=document.getElementById('custom-popup-message');p.className='custom-popup show '+(type==='success'?'success':'error');p.setAttribute('aria-hidden','false');m.textContent=message;clearTimeout(window._customPopupTimer);window._customPopupTimer=setTimeout(function(){p.classList.remove('show');p.setAttribute('aria-hidden','true');},3000);}document.addEventListener('click',function(e){if(e.target&& (e.target.id==='custom-popup' || e.target.id==='custom-popup-close')){document.getElementById('custom-popup').classList.remove('show');document.getElementById('custom-popup').setAttribute('aria-hidden','true');}});

// Room and Bed Dropdown Functionality
$(document).ready(function() {
    $('.room-select').on('change', function() {
        var roomSelect = $(this);
        var applicationId = roomSelect.data('application-id');
        var bedSelect = $('.bed-select[data-application-id="' + applicationId + '"]');
        
        var selectedOption = roomSelect.find('option:selected');
        var capacity = parseInt(selectedOption.data('capacity'));
        var occupied = parseInt(selectedOption.data('occupied'));
        var occupiedBeds = selectedOption.data('occupied-beds');
        
        bedSelect.empty();
        bedSelect.prop('disabled', false);
        
        if (roomSelect.val() === '') {
            bedSelect.append('<option value="">Select Room First</option>');
            bedSelect.prop('disabled', true);
            return;
        }
        
        // Check if room is full
        if (occupied >= capacity) {
            bedSelect.append('<option value="">Room Full - No Beds Available</option>');
            bedSelect.prop('disabled', true);
            return;
        }
        
        bedSelect.append('<option value="">Select Bed</option>');
        
        // Convert occupied beds to array of numbers
        var occupiedBedNumbers = [];
        if (occupiedBeds) {
            // Handle if occupiedBeds is a string or number
            if (typeof occupiedBeds === 'string') {
                occupiedBedNumbers = occupiedBeds.split(',').map(function(bed) {
                    return parseInt(bed.trim());
                });
            } else if (typeof occupiedBeds === 'number') {
                occupiedBedNumbers = [occupiedBeds];
            }
        }
        
        // Only show available beds
        for (var i = 1; i <= capacity; i++) {
            // Check if this bed number is in the occupied beds array
            var isOccupied = occupiedBedNumbers.includes(i);
            
            if (!isOccupied) {
                bedSelect.append('<option value="' + i + '">Bed ' + i + '</option>');
            }
        }
    });
    
    // Handle allocate button clicks
    $('.allocate-btn').on('click', function() {
        var appId = $(this).data('app-id');
        var roomSelect = $('select[name="room_id_' + appId + '"]');
        var bedSelect = $('select[name="bed_number_' + appId + '"]');
        
        var roomId = roomSelect.val();
        var bedNumber = bedSelect.val();
        
        // Validation
        if (!roomId) {
            alert('Please select a room');
            return;
        }
        
        if (!bedNumber) {
            alert('Please select a bed');
            return;
        }
        
        // Create and submit form
        var form = $('<form>', {
            method: 'POST',
            action: 'allocate_room.php'
        });
        
        form.append($('<input>', {
            type: 'hidden',
            name: 'allocate_student',
            value: '1'
        }));
        
        form.append($('<input>', {
            type: 'hidden',
            name: 'application_id',
            value: appId
        }));
        
        form.append($('<input>', {
            type: 'hidden',
            name: 'room_id',
            value: roomId
        }));
        
        form.append($('<input>', {
            type: 'hidden',
            name: 'bed_number',
            value: bedNumber
        }));
        
        form.appendTo('body').submit();
    });
    
    // Handle delete button clicks
    $('.delete-btn').on('click', function() {
        var appId = $(this).data('app-id');
        var studentId = $(this).data('student-id');
        
        // Confirmation
        if (!confirm('Are you sure you want to delete this application? This action cannot be undone.')) {
            return;
        }
        
        // Create and submit form
        var form = $('<form>', {
            method: 'POST',
            action: 'allocate_room.php'
        });
        
        form.append($('<input>', {
            type: 'hidden',
            name: 'delete_application',
            value: '1'
        }));
        
        form.append($('<input>', {
            type: 'hidden',
            name: 'application_id',
            value: appId
        }));
        
        form.append($('<input>', {
            type: 'hidden',
            name: 'student_id',
            value: studentId
        }));
        
        form.appendTo('body').submit();
    });
});
</script>

</body>
</html>
