<?php
  require 'includes/config.inc.php';
  include 'includes/manager_header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<title> Empty Rooms</title>
	
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
	<link rel="stylesheet" href="web_home/css_home/bootstrap.css"> <!-- Bootstrap-Core-CSS -->
	<link rel="stylesheet" href="web_home/css_home/style.css" type="text/css" media="all" /> <!-- Style-CSS --> 
	<!-- <link rel="stylesheet" href="web_home/css_home/fontawesome-all.css"> Font-Awesome-Icons-CSS -->
	<!-- //css files -->
	
	<!-- web-fonts -->
	<!-- <link href="//fonts.googleapis.com/css?family=Poiret+One&amp;subset=cyrillic,latin-ext" rel="stylesheet">
	<link href="//fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i,800,800i&amp;subset=cyrillic,cyrillic-ext,greek,greek-ext,latin-ext,vietnamese" rel="stylesheet"> -->
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

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
    .contact {
        padding: 20px 0;
    }
    .mail_grid_w3l {
        max-width: 600px;
        margin: 0 auto;
        background: #fff;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    .mail_grid_w3l input[type="text"] {
        width: 100%;
        padding: 10px;
        margin-bottom: 10px;
        border: 1px solid #ced4da;
        border-radius: 5px;
    }
    .mail_grid_w3l input[type="submit"] {
        background-color: #dc3545;
        color: #fff;
        border: none;
        padding: 10px 20px;
        border-radius: 5px;
        cursor: pointer;
    }
    .mail_grid_w3l input[type="submit"]:hover {
        background-color: #c82333;
    }
    .table-container {
        max-width: 800px;
        margin: 20px auto;
        background: #fff;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    .table-container h2 {
        font-size: 2rem;
        color: #003366;
        text-align: center;
        margin-bottom: 20px;
    }
    .table {
        width: 100%;
        border-collapse: collapse;
    }
    .table th,
    .table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #dee2e6;
    }
    .table th {
        background-color: #003366;
        color: #fff;
    }
    .table td {
        background-color: #e9ecef;
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
</style>

</head>

<body>


<br><br><br>
<section class="contact py-5">
    <div class="container">
        <div class="mail_grid_w3l">
            <form action="empty_rooms.php" method="post">
                <div class="row">
                    <div class="col-md-9"> 
                        <input type="text" placeholder="Search by Room Number" name="search_box" value="<?php echo isset($_POST['search_box']) ? htmlspecialchars($_POST['search_box']) : ''; ?>">
                    </div>
                    <div class="col-md-3">
                        <input type="submit" value="Search" name="search">
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
<?php
   if (isset($_POST['search'])) {
       $search_box = mysqli_real_escape_string($conn, $_POST['search_box']);
       $hostel_id = $_SESSION['hostel_id'];
    // consider Allocated could be stored as '0', 0 or NULL; treat non-'1' as available
    $query_search = "SELECT * FROM Room WHERE Room_No LIKE '$search_box%' AND Hostel_id = '" . (int)$hostel_id . "' AND (Allocated = '0' OR Allocated = 0 OR Allocated IS NULL)";
       $result_search = mysqli_query($conn, $query_search);

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
           <th>Hostel Name</th>
           <th>Room Number</th>
         </tr>
       </thead>
       <tbody>
       <?php
       if (mysqli_num_rows($result_search) == 0) {
           echo '<tr><td colspan="2">No Rows Returned</td></tr>';
       } else {
           while ($row_search = mysqli_fetch_assoc($result_search)) {
               echo "<tr><td>{$hostel_name}</td><td>{$row_search['Room_No']}</td></tr>\n";
           }
       }
       ?>
       </tbody>
      </table>
    </div>
<?php
}
?>

<div class="table-container">
<h2 class="heading text-capitalize mb-sm-5 mb-4"> Empty Rooms </h2>
<?php
   $hostel_id = $_SESSION['hostel_id'];
    // show rooms that are not allocated; be robust to Allocated field being '0', 0 or NULL
    $query1 = "SELECT * FROM Room WHERE Hostel_id = '" . (int)$hostel_id . "' AND (Allocated = '0' OR Allocated = 0 OR Allocated IS NULL)";
   $result1 = mysqli_query($conn, $query1);
   // Select the hostel name from hostel table
   $query6 = "SELECT * FROM Hostel WHERE Hostel_id = '$hostel_id'";
   $result6 = mysqli_query($conn, $query6);
   $row6 = mysqli_fetch_assoc($result6);
   $hostel_name = $row6['Hostel_name'];
?>

<table class="table">
  <thead>
    <tr>
      <th>Hostel Name</th>
      <th>Room Number</th>
    </tr>
  </thead>
  <tbody>
  <?php
    if (mysqli_num_rows($result1) == 0) {
       echo '<tr><td colspan="2">No Rows Returned</td></tr>';
    } else {
        while ($row1 = mysqli_fetch_assoc($result1)) {
            echo "<tr><td>{$hostel_name}</td><td>{$row1['Room_No']}</td></tr>\n";
        }
    }
  ?>
  </tbody>
</table>
</div>
<br><br><br>
<br><br><br>
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