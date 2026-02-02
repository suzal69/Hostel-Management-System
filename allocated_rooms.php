<?php
require 'includes/config.inc.php';
include 'includes/manager_header.php';

// Check if user is logged in and hostel_id is set
if (!isset($_SESSION['username']) || !isset($_SESSION['hostel_id'])) {
    header('Location: login-hostel_manager.php');
    exit();
}

$hostel_id = $_SESSION['hostel_id'];

// Query to get hostel name
$query6 = "SELECT Hostel_name FROM Hostel WHERE Hostel_id = ?";
$stmt = mysqli_prepare($conn, $query6);
mysqli_stmt_bind_param($stmt, "i", $hostel_id);
mysqli_stmt_execute($stmt);
$result6 = mysqli_stmt_get_result($stmt);
$row6 = mysqli_fetch_assoc($result6);
$hostel_name = $row6 ? $row6['Hostel_name'] : 'Unknown Hostel';

// Update the main query to use JOIN with bed allocation
$query1 = "SELECT s.*, r.Room_No, h.Hostel_name, ba.bed_number, ba.include_food, ba.food_plan 
           FROM Student s
           LEFT JOIN Room r ON s.Room_id = r.Room_id
           LEFT JOIN Hostel h ON s.Hostel_id = h.Hostel_id
           LEFT JOIN bed_allocation ba ON s.Student_id = ba.student_id AND ba.is_active = 1
           WHERE s.Hostel_id = ?";
$stmt = mysqli_prepare($conn, $query1);
mysqli_stmt_bind_param($stmt, "i", $hostel_id);
mysqli_stmt_execute($stmt);
$result1 = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<title> Allocated Rooms</title>
	
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
	<!--bootsrap -->

	<!--// Meta tag Keywords -->
		
<!-- css files -->
<link rel="stylesheet" href="web_home/css_home/bootstrap.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
    .btn-danger {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
    }
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    .status-badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
    }
    .status-allocated {
        background-color: #d4edda;
        color: #155724;
    }
    .status-vacant {
        background-color: #fff3cd;
        color: #856404;
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
    
    /* Bootstrap dropdown styles to match manager_header.php */
    .dropdown-menu {
        background-color: #003366;
        border: 1px solid #004080;
        box-shadow: 0 8px 16px rgba(0,0,0,0.2);
    }
    .dropdown-menu .dropdown-item {
        color: #ffffff;
        padding: 8px 16px;
        transition: all 0.2s ease;
    }
    .dropdown-menu .dropdown-item:hover {
        background-color: #001f4d;
        color: #ffcc00;
    }
    .dropdown-menu .dropdown-item:focus {
        background-color: #001f4d;
        color: #ffcc00;
        outline: none;
    }
    .dropdown-menu .dropdown-item:active {
        background-color: #002244;
        color: #ffcc00;
    }
</style>

</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-list"></i> Allocated Rooms</h1>
            <p>View and manage all room allocations in your hostel</p>
        </div>
        
        <!-- Main Content -->
        <div class="section">
            <div class="section-header">
                <h3><i class="fas fa-bed"></i> Room Allocations</h3>
            </div>
            
            <div class="mail_grid_w3l">
            <form action="allocated_rooms.php" method="post">
                <div class="form-group">
                    <label for="search_box">Search by Roll Number</label>
                    <input type="text" id="search_box" name="search_box" class="form-control" placeholder="Enter Roll Number" value="<?php echo isset($_POST['search_box']) ? htmlspecialchars($_POST['search_box']) : ''; ?>">
                </div>
                <button type="submit" name="search" class="btn-submit">
                    <i class="fas fa-search"></i> Search
                </button>
            </form>
        </div>
    </div>
<?php
   if (isset($_POST['search'])) {
       $search_box = mysqli_real_escape_string($conn, $_POST['search_box']);
       $hostel_id = $_SESSION['hostel_id'];
       $query_search = "SELECT s.*, r.Room_No, h.Hostel_name, ba.bed_number, ba.include_food, ba.food_plan 
                FROM Student s
                LEFT JOIN Room r ON s.Room_id = r.Room_id 
                LEFT JOIN Hostel h ON s.Hostel_id = h.Hostel_id
                LEFT JOIN bed_allocation ba ON s.Student_id = ba.student_id AND ba.is_active = 1
                WHERE s.Student_id LIKE ? AND s.Hostel_id = ?";
       $stmt_search = mysqli_prepare($conn, $query_search);
       $search_param = $search_box . '%';
       mysqli_stmt_bind_param($stmt_search, "ss", $search_param, $hostel_id);
       mysqli_stmt_execute($stmt_search);
       $result_search = mysqli_stmt_get_result($stmt_search);

       // Select the hostel name from hostel table
       $query6 = "SELECT * FROM Hostel WHERE Hostel_id = '$hostel_id'";
       $result6 = mysqli_query($conn, $query6);
       $row6 = mysqli_fetch_assoc($result6);
       $hostel_name = $row6['Hostel_name'];
       ?>
       <div class="table-container">
       <table class="table">
       <thead>
         <tr>
           <th>Student Name</th>
           <th>Student ID</th>
           <th>Contact Number</th> 
           <th>Hostel</th>
           <th>Room Number</th>
           <th>Bed Number</th>
           <th>Food Plan</th>
           <th>Actions</th>
         </tr>
       </thead>
       <tbody>
       <?php
       if (mysqli_num_rows($result_search) == 0) {
           echo '<tr><td colspan="8">No Rows Returned</td></tr>';
       } else {
           while ($row_search = mysqli_fetch_assoc($result_search)) {
               $room_id = $row_search['Room_id']; 
               $query7 = "SELECT * FROM Room WHERE Room_id = '$room_id'";
               $result7 = mysqli_query($conn, $query7);
               $row7 = mysqli_fetch_assoc($result7);
               $room_no = $row7['Room_No'];
               $student_name = htmlspecialchars($row_search['Fname'] . " " . $row_search['Lname']);
               $bed_number = $row_search['bed_number'] ? 'Bed ' . $row_search['bed_number'] : 'Not Assigned';
               
               // Determine food plan display
               $food_plan_display = 'No Food Plan';
               if ($row_search['include_food'] == 1 && !empty($row_search['food_plan'])) {
                   $food_plan_display = ucfirst($row_search['food_plan']);
               }
               
               echo "<tr><td>{$student_name}</td><td>{$row_search['Student_id']}</td><td>{$row_search['Mob_no']}</td><td>{$hostel_name}</td><td>{$room_no}</td><td>{$bed_number}</td><td>{$food_plan_display}</td>
                    <td>
                        <div class='dropdown'>
                            <button class='btn btn-primary dropdown-toggle' type='button' id='searchDropdown{$row_search['Student_id']}' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>
                                Actions <span class='caret'></span>
                            </button>
                            <ul class='dropdown-menu' aria-labelledby='searchDropdown{$row_search['Student_id']}'>
                                <li><a class='dropdown-item' href='change_room.php?student_id={$row_search['Student_id']}'>
                                    <i class='fas fa-exchange-alt'></i> Change Room
                                </a></li>
                                <li><a class='dropdown-item' href='vacate_rooms.php?student_id={$row_search['Student_id']}'>
                                    <i class='fas fa-door-open'></i> Vacate Room
                                </a></li>
                                <li><a class='dropdown-item' href='change_food_status.php?student_id={$row_search['Student_id']}'>
                                    <i class='fas fa-utensils'></i> Change Food Status
                                </a></li>
                            </ul>
                        </div>
                    </td></tr>\n";
           }
       }
       ?>
       </tbody>
      </table>
    </div>
<?php
}
?>

<!-- All Rooms Section -->
<div class="section">
    <div class="section-header">
        <h3><i class="fas fa-list"></i> All Allocated Rooms</h3>
    </div>
    
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Student ID</th>
                    <th>Contact Number</th> 
                    <th>Hostel</th>
                    <th>Room Number</th>
                    <th>Bed Number</th>
                    <th>Food Plan</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $hostel_id = $_SESSION['hostel_id'];
            $query1 = "SELECT s.*, r.Room_No, h.Hostel_name, ba.bed_number, ba.include_food, ba.food_plan 
                FROM Student s
                LEFT JOIN Room r ON s.Room_id = r.Room_id 
                LEFT JOIN Hostel h ON s.Hostel_id = h.Hostel_id
                LEFT JOIN bed_allocation ba ON s.Student_id = ba.student_id AND ba.is_active = 1
                WHERE s.Hostel_id = '$hostel_id'";
            $result1 = mysqli_query($conn, $query1);
            
            if (mysqli_num_rows($result1) == 0) {
                echo '<tr><td colspan="8">No students allocated</td></tr>';
            } else {
                while ($row1 = mysqli_fetch_assoc($result1)) {
                    $room_id = $row1['Room_id']; 
                    $query2 = "SELECT * FROM Room WHERE Room_id = '$room_id'";
                    $result2 = mysqli_query($conn, $query2);
                    $row2 = mysqli_fetch_assoc($result2);
                    $room_no = $row2['Room_No'];
                    $student_name = htmlspecialchars($row1['Fname'] . " " . $row1['Lname']);
                    $bed_number = $row1['bed_number'] ? 'Bed ' . $row1['bed_number'] : 'Not Assigned';
                    
                    // Determine food plan display
                    $food_plan_display = 'No Food Plan';
                    if ($row1['include_food'] == 1 && !empty($row1['food_plan'])) {
                        $food_plan_display = ucfirst($row1['food_plan']);
                    }
                    
                    echo "<tr><td>{$student_name}</td><td>{$row1['Student_id']}</td><td>{$row1['Mob_no']}</td><td>{$row1['Hostel_name']}</td><td>{$room_no}</td><td>{$bed_number}</td><td>{$food_plan_display}</td>
                    <td>
                        <div class='dropdown'>
                            <button class='btn btn-primary dropdown-toggle' type='button' id='actionDropdown{$row1['Student_id']}' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>
                                Actions <span class='caret'></span>
                            </button>
                            <ul class='dropdown-menu' aria-labelledby='actionDropdown{$row1['Student_id']}'>
                                <li><a class='dropdown-item' href='change_room.php?student_id={$row1['Student_id']}'>
                                    <i class='fas fa-exchange-alt'></i> Change Room
                                </a></li>
                                <li><a class='dropdown-item' href='vacate_rooms.php?student_id={$row1['Student_id']}'>
                                    <i class='fas fa-door-open'></i> Vacate Room
                                </a></li>
                                <li><a class='dropdown-item' href='change_food_status.php?student_id={$row1['Student_id']}'>
                                    <i class='fas fa-utensils'></i> Change Food Status
                                </a></li>
                            </ul>
                        </div>
                    </td></tr>\n";
                }
            }
            ?>
            </tbody>
        </table>
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
	<script type="text/javascript" src="web_home/js/jquery-2.2.3.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
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
	
	<!-- Custom JavaScript for dropdowns in table -->
	<script>
	$(document).ready(function() {
		// Handle dropdown toggles
		$('.dropdown-toggle').on('click', function(e) {
			e.preventDefault();
			e.stopPropagation();
			
			var $dropdown = $(this).parent().find('.dropdown-menu');
			var $allDropdowns = $('.dropdown-menu');
			
			// Close all other dropdowns
			$allDropdowns.not($dropdown).removeClass('show');
			
			// Toggle current dropdown
			$dropdown.toggleClass('show');
		});
		
		// Close dropdowns when clicking outside
		$(document).on('click', function(e) {
			if (!$(e.target).closest('.dropdown').length) {
				$('.dropdown-menu').removeClass('show');
			}
		});
		
		// Close dropdowns when clicking on dropdown items
		$('.dropdown-item').on('click', function() {
			$('.dropdown-menu').removeClass('show');
		});
	});
	</script>
</body>
</html>
