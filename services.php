<?php
require 'includes/config.inc.php';
require 'includes/user_header.php';

// Check if student is already allocated
$student_id = $_SESSION['roll'] ?? '';
$is_allocated = false;
$allocation_info = null;

if (!empty($student_id)) {
    $query = "SELECT s.*, r.Room_No, h.Hostel_name, ba.bed_number, ba.include_food, ba.food_plan 
              FROM Student s
              LEFT JOIN Room r ON s.Room_id = r.Room_id
              LEFT JOIN Hostel h ON s.Hostel_id = h.Hostel_id
              LEFT JOIN bed_allocation ba ON s.Student_id = ba.student_id AND ba.is_active = 1
              WHERE s.Student_id = ? AND ba.allocation_id IS NOT NULL";
    
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $student_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $is_allocated = true;
            $allocation_info = mysqli_fetch_assoc($result);
        }
        mysqli_stmt_close($stmt);
    }
}
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
    .services {
        padding: 50px 0;
    }
    .heading {
        font-size: 2.5rem;
        color: #003366;
        text-align: center;
        margin-bottom: 30px;
        height: auto;
        transition: all 0.3s ease;
    }
    .agile_text_box {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        color: #fff;
        text-align: center;
        z-index: 2;
    }
    .agile_text_box i {
        font-size: 2rem;
        margin-bottom: 10px;
    }
    .agile_text_box h3 {
        font-size: 1.2rem;
        margin-bottom: 5px;
    }
    .agile_text_box p {
        font-size: 0.9rem;
    }
    footer {
        background-color: #003366;
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
    .heading {
        text-align: center;
        color: #003366;
        font-weight: 600;
        margin-bottom: 50px;
        font-size: 2.5rem;
    }
    .hostel-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        margin-bottom: 30px;
    }
    .hostel-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 40px rgba(0,0,0,0.15);
    }
    .hostel-image {
        width: 100%;
        height: 200px;
        object-fit: cover;
        border-bottom: 3px solid #003366;
    }
    .hostel-content {
        padding: 25px;
    }
    .hostel-title {
        color: #003366;
        font-weight: 600;
        font-size: 1.5rem;
        margin-bottom: 15px;
        text-align: center;
    }
    .hostel-details {
        color: #666;
        font-size: 14px;
        line-height: 1.6;
    }
    .hostel-details p {
        margin-bottom: 10px;
    }
    .hostel-details strong {
        color: #003366;
        font-weight: 600;
    }
    .hostel-features {
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #e9ecef;
    }
    .feature-item {
        display: flex;
        align-items: center;
        margin-bottom: 8px;
        font-size: 13px;
    }
    .feature-icon {
        color: #28a745;
        margin-right: 8px;
    }
    /* Request card styles */
    .request-card {
        border: 2px solid #e9ecef;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        cursor: pointer;
        transition: all 0.3s ease;
        background: white;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    }
    .request-card:hover {
        border-color: #003366;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        transform: translateY(-2px);
    }
    .request-card h4 {
        color: #003366;
        margin-bottom: 10px;
        font-size: 1.3rem;
    }
    .request-card h4 i {
        margin-right: 8px;
    }
    .request-card p {
        color: #666;
        margin: 0;
        font-size: 0.95rem;
    }
    .student-info {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 25px;
        border-left: 4px solid #003366;
    }
    .btn-request {
        display: inline-block;
        padding: 10px 20px;
        background: linear-gradient(135deg, #003366 0%, #004080 100%);
        color: white;
        text-decoration: none;
        border-radius: 25px;
        font-weight: 600;
        transition: all 0.3s ease;
        margin-top: 15px;
    }
    .btn-request:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        color: white;
    }
</style>

<body>

<!-- services -->
<div class="container">
    <!-- Header -->
    <div class="header">
        <h1><i class="fas fa-building"></i> 
            <?php echo $is_allocated ? 'Accommodation Services' : 'Hostels'; ?>
        </h1>
        <p>
            <?php echo $is_allocated ? 'Manage your current accommodation and request changes' : 'Explore our comfortable and affordable hostel options'; ?>
        </p>
    </div>

    <?php if ($is_allocated): ?>
        <!-- Student Current Allocation Info -->
        <div class="section">
            <div class="section-header">
                <h3><i class="fas fa-info-circle"></i> Current Allocation</h3>
            </div>
            
            <div class="student-info">
                <p><strong>Name:</strong> <?php echo htmlspecialchars($_SESSION['fname'] . ' ' . $_SESSION['lname']); ?></p>
                <p><strong>Student ID:</strong> <?php echo htmlspecialchars($student_id); ?></p>
                <p><strong>Room:</strong> <?php echo htmlspecialchars($allocation_info['Room_No'] ?? 'Not Assigned'); ?></p>
                <p><strong>Hostel:</strong> <?php echo htmlspecialchars($allocation_info['Hostel_name'] ?? 'Not Assigned'); ?></p>
                <p><strong>Food Service:</strong> 
                    <?php 
                    if ($allocation_info['include_food'] == 1 && !empty($allocation_info['food_plan'])) {
                        echo ucfirst($allocation_info['food_plan']) . ' Plan';
                    } else {
                        echo 'No Food Plan';
                    }
                    ?>
                </p>
            </div>
        </div>

        <!-- Change Request Options -->
        <div class="section">
            <div class="section-header">
                <h3><i class="fas fa-exchange-alt"></i> Request Changes</h3>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="request-card" onclick="window.location.href='request_form.php'">
                        <h4><i class="fas fa-exchange-alt"></i> Change Room</h4>
                        <p>Request to change your current room to a different room</p>
                        <a href="request_form.php" class="btn-request">
                            <i class="fas fa-arrow-right"></i> Apply for Room Change
                        </a>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="request-card" onclick="window.location.href='request_form.php'">
                        <h4><i class="fas fa-utensils"></i> Change Food Plan</h4>
                        <p>Request to modify your current food plan or add/remove food service</p>
                        <a href="request_form.php" class="btn-request">
                            <i class="fas fa-arrow-right"></i> Apply for Food Plan Change
                        </a>
                    </div>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="home.php" class="btn-request">
                    <i class="fas fa-arrow-left"></i> Back to Home
                </a>
            </div>
        </div>

    <?php else: ?>
        <!-- Original Hostels Section for new students -->
        <div class="section">
            <div class="section-header">
                <h3><i class="fas fa-home"></i> Available Hostels</h3>
            </div>
            
            <div class="row">
                <?php
                $sql = "SELECT * FROM Hostel";
                $result = mysqli_query($conn, $sql);
                if(mysqli_num_rows($result) > 0){
                    while($row = mysqli_fetch_assoc($result)){
                        $hostel_name = $row['Hostel_name'];
                        $hostel_id = $row['Hostel_id'];
                        
                        if ($hostel_name == "admin") {
                            continue;
                        }
                        
                        // Map hostels to images
                        if (stripos($hostel_name, '1') !== false || $hostel_id == 1) {
                            $image_name = 'oneBed.jpg';
                            $description = 'Comfortable single occupancy rooms';
                            $capacity = '1-Bed Rooms';
                            $price = 'Rs 5,000 - 7,500/month';
                        } elseif (stripos($hostel_name, '2') !== false || $hostel_id == 2) {
                            $image_name = 'twoBed.jpeg';
                            $description = 'Spacious double sharing rooms';
                            $capacity = '2-Bed Rooms';
                            $price = 'Rs 2,500 - 5,000/month';
                        } else {
                            $image_name = 'threeBed.jpg';
                            $description = 'Economical triple sharing rooms';
                            $capacity = '3-Bed Rooms';
                            $price = 'Rs 1,667 - 4,167/month';
                        }
                        
                        echo "<div class='col-md-4'>
                                <div class='hostel-card'>
                                    <img src='web_home/images/$image_name' alt='$hostel_name' class='hostel-image'>
                                    <div class='hostel-content'>
                                        <h3 class='hostel-title'>$hostel_name</h3>
                                        <div class='hostel-details'>
                                            <p><strong>Room Type:</strong> $capacity</p>
                                            <p><strong>Room Price:</strong> $price</p>
                                            <p>$description</p>
                                            <p><strong>Food Plans Available:</strong></p>
                                            <p>• Basic: Rs500/month</p>
                                            <p>• Standard: Rs1,500/month</p>
                                            <p>• Premium: Rs2,500/month</p>
                                            <p><strong>Features:</strong> WiFi • Mess • 24/7 Security</p>
                                            <div style='margin-top: 20px; text-align: center;'>
                                                <a href='application_form.php?id=".urlencode($hostel_name)."' class='btn-apply' style='display: inline-block; padding: 12px 30px; background: linear-gradient(135deg, #003366 0%, #004080 100%); color: white; text-decoration: none; border-radius: 25px; font-weight: 600; transition: all 0.3s ease; text-transform: uppercase; letter-spacing: 1px;'>Apply Now</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>";
                    }
                }
                ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- banner-bottom -->

<!-- banner-bottom -->
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
	<!-- //here ends scrolling icon -->
	<!-- start-smoth-scrolling -->
	
<!-- //js-scripts -->

</body>
</html>