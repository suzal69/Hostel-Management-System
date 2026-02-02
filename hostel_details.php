<?php
include_once 'includes/config.inc.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Intrend Interior Category Flat Bootstrap Responsive Website Template | Index : W3layouts</title>

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
    .navbar {
        background-color: #003366;
        padding: 15px 0;
    }
    .navbar-brand, .nav-link {
        color: #ffffff !important;
        font-weight: 600;
    }
    .navbar-brand {
        font-size: 1.5rem;
    }
    .nav-link:hover {
        color: #ffcc00 !important;
    }
    .dropdown-menu {
        background-color: #003366;
    }
    .dropdown-menu .dropdown-item {
        color: #ffffff;
    }
    .dropdown-menu .dropdown-item:hover {
        background-color: #001f4d;
        color: #ffcc00;
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
</style>
</head>
<body>
 <header>
        <nav class="navbar navbar-expand-lg">
            <div class="container">
                <a class="navbar-brand" href="home1.php">PLYS</a>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                    <span class="navbar-toggler-icon" style="color:white;"></span>
                </button>
                <div class="collapse navbar-collapse justify-content-center" id="navbarSupportedContent">
                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item active">
                            <a class="nav-link" href="home1.php">Home <span class="sr-only">(current)</span></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="hostel_details.php">Hostels</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">Login</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
<br><br><br>
<br><br><br>
 <!-- services -->
<div class="container">
    <!-- Header -->
    <div class="header">
        <h1><i class="fas fa-building"></i> Hostels</h1>
        <p>Explore our comfortable and affordable hostel options</p>
    </div>

    <!-- Hostels Section -->
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
                                        <div class='food-plans'>
                                            <div class='food-plan'>
                                                <p><strong>• Basic Plan - Rs500/month:</strong></p>
                                                <p class='food-details'>Breakfast & Dinner (Simple vegetarian meals)</p>
                                                <p class='food-items'>• Breakfast: Tea/Coffee + Bread/Paratha<br>• Dinner: Rice + Dal + Tarkari</p>
                                            </div>
                                            <div class='food-plan'>
                                                <p><strong>• Standard Plan - Rs1,500/month:</strong></p>
                                                <p class='food-details'>Breakfast, Lunch & Dinner (Vegetarian meals)</p>
                                                <p class='food-items'>• Breakfast: Tea/Coffee + Bread/Paratha + Butter<br>• Lunch: Rice + Dal + 2 Tarkari + Salad + Curd<br>• Dinner: Roti + Rice + Dal + Tarkari + Sweet</p>
                                            </div>
                                            <div class='food-plan'>
                                                <p><strong>• Premium Plan - Rs2,500/month:</strong></p>
                                                <p class='food-details'>All Meals (Vegetarian & Non-vegetarian options)</p>
                                                <p class='food-items'>• Breakfast: Tea/Coffee + Bread/Paratha + Butter + Eggs<br>• Lunch: Rice + Dal + 2 Tarkari + Salad + Curd + Chicken/Fish<br>• Dinner: Roti + Rice + Dal + Tarkari + Sweet + Non-veg dish</p>
                                            </div>
                                        </div>
                                        <p><strong>Features:</strong> WiFi • Mess • 24/7 Security</p>
                                        <div style='margin-top: 20px; text-align: center;'>
                                            <a href='index.php' class='btn-apply' style='display: inline-block; padding: 12px 30px; background: linear-gradient(135deg, #003366 0%, #004080 100%); color: white; text-decoration: none; border-radius: 25px; font-weight: 600; transition: all 0.3s ease; text-transform: uppercase; letter-spacing: 1px;'>Register Now</a>
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
</div>

    <footer class="py-5" style="background:#36454F;">
        <div class="container py-md-5">
            <div class="footer-logo mb-5 text-center">
                <a class="navbar-brand" href="home1.php">Peaceful Living for Young <span class="display"> Scholars</span></a>
            </div>
            <div class="footer-grid">
                <div class="list-footer">
                    <ul class="footer-nav text-center">
                        <li><a href="home1.php">Home</a></li>
                        <li><a href="hostel_details.php">Hostels</a></li>
                        <li><a href="index.php">Login</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>
	<!-- js-scripts -->					
	<!-- js -->
	<script type="text/javascript" src="web/js/jquery-2.1.4.min.js"></script>
	<script type="text/javascript" src="web/js/bootstrap.js"></script> <!-- Necessary-JavaScript-File-For-Bootstrap --> 
	<!-- //js -->
	<!-- start-smoth-scrolling -->
	<script type="text/javascript" src="web/js/move-top.js"></script>
	<script type="text/javascript" src="web/js/easing.js"></script>
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			$(".scroll").click(function(event){		
				event.preventDefault();
				$('html,body').animate({scrollTop:$(this.hash).offset().top},1000);
			});
		});
	</script>
	<!-- start-smoth-scrolling -->
	<!-- //js-scripts -->
</body>
</html>
