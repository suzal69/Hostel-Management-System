<?php
$sessionStarted = false;
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    $sessionStarted = true;
}
require_once 'includes/config.inc.php';
require_once 'includes/manager_header.php'; // Provides manager header and navigation
require_once 'includes/notification_helper.php';

// Check if manager is logged in
if (!isset($_SESSION['hostel_man_id'])) {
    header("Location: login-hostel_manager.php"); // Redirect to login page if not logged in
    exit();
}

$hostelManId = $_SESSION['hostel_man_id'];
$successMessage = '';
$errorMessage = '';
$infoMessage = '';

if (isset($_GET['success'])) {
    if ($_GET['success'] == 'complaintupdated') {
        $successMessage = 'Complaint updated successfully!';
    }
} elseif (isset($_GET['error'])) {
    if ($_GET['error'] == 'failedtoupdate') {
        $errorMessage = 'Failed to update complaint. Please try again.';
    } elseif ($_GET['error'] == 'invalidinput') {
        $errorMessage = 'Invalid input. Please fill all fields.';
    } elseif ($_GET['error'] == 'sqlerror') {
        $errorMessage = 'A database error occurred. Please try again later.';
    } elseif ($_GET['error'] == 'invalidstatus') {
        $errorMessage = 'Invalid status selected.';
    }
} elseif (isset($_GET['info'])) {
    if ($_GET['info'] == 'nostatuschange') {
        $infoMessage = 'Complaint status was already the selected value. No changes made.';
    }
}

// Determine manager's hostel id (prefer session value set on login)
$managerHostelId = $_SESSION['hostel_id'] ?? null;
if (!$managerHostelId) {
    $sql = "SELECT Hostel_id FROM hostel_manager WHERE Hostel_man_id = ?";
    $stmt = mysqli_stmt_init($conn);
    if (mysqli_stmt_prepare($stmt, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $hostelManId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $managerData = mysqli_fetch_assoc($result);
        if ($managerData) {
            $managerHostelId = $managerData['Hostel_id'];
            // Optionally save to session if it wasn't there
            $_SESSION['hostel_id'] = $managerHostelId; 
        }
        mysqli_stmt_close($stmt);
    }
}

if (!$managerHostelId) {
    $errorMessage = 'Could not determine your hostel. Please contact admin.';
}

// Fetch all complaints for the manager's hostel
$allComplaints = [];
if ($managerHostelId) {
    // CORRECTION: Selecting 's.Student_id' instead of the non-existent 's.Reg_no'
    $sql = "SELECT c.*, s.Fname, s.Lname, s.Student_id 
            FROM complaints c
            JOIN student s ON c.student_id = s.Student_id
            WHERE c.hostel_id = ?
            ORDER BY c.submission_date DESC";
            
    $stmt = mysqli_stmt_init($conn);
    if (mysqli_stmt_prepare($stmt, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $managerHostelId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $allComplaints[] = $row;
        }
        mysqli_stmt_close($stmt);
    } else {
        // Detailed error reporting in case of further issues
        $errorMessage = 'Failed to prepare complaints query. MySQL Error: ' . mysqli_error($conn); 
    }
}


// Temporary debug panel - enable by adding ?debug=1 to the URL (visible only to logged-in managers)
if (isset($_GET['debug']) && $_GET['debug'] === '1') {
    echo '<div style="background:#fff3cd;border:1px solid #ffecb5;padding:12px;margin:12px;border-radius:6px;">';
    echo '<strong>DEBUG</strong><br>';
    echo 'Manager Hostel ID (from session/lookup): <code>' . htmlspecialchars($managerHostelId) . '</code><br>';
    // show a quick distribution of complaints by hostel
    $dist = [];
    $sql_dist = "SELECT hostel_id, COUNT(*) AS cnt FROM complaints GROUP BY hostel_id";
    if ($resd = mysqli_query($conn, $sql_dist)) {
        while ($r = mysqli_fetch_assoc($resd)) {
            $dist[(string)$r['hostel_id']] = (int)$r['cnt'];
        }
    } else {
        echo 'DB error (dist): ' . htmlspecialchars(mysqli_error($conn)) . '<br>';
    }
    echo 'Complaints per hostel: <pre>' . htmlspecialchars(var_export($dist, true)) . '</pre>';
    echo 'Complaints fetched for this manager: <strong>' . count($allComplaints) . '</strong><br>';
    echo 'Session contents: <pre>' . htmlspecialchars(var_export($_SESSION, true)) . '</pre>';
    echo 'Last DB error: <code>' . htmlspecialchars(mysqli_error($conn)) . '</code>';
    echo '</div>';
}


// Calculate overall Complaint Resolution Rate and Average Resolution Time for the manager's hostel
$totalComplaints = 0;
$resolvedComplaints = 0;
$totalResolutionTime = 0; // in days
$resolvedComplaintsCount = 0;

foreach ($allComplaints as $complaint) {
    $totalComplaints++;
    if ($complaint['status'] === 'resolved') {
        $resolvedComplaints++;
        if ($complaint['submission_date'] && $complaint['resolve_date']) {
            $submissionDateTime = new DateTime($complaint['submission_date']);
            $resolveDateTime = new DateTime($complaint['resolve_date']);
            $interval = $submissionDateTime->diff($resolveDateTime);
            $totalResolutionTime += $interval->days;
            $resolvedComplaintsCount++;
        }
    }
}

$overallComplaintResolutionRate = ($totalComplaints > 0) ? ($resolvedComplaints / $totalComplaints) * 100 : 0;
$overallAvgResolutionTime = ($resolvedComplaintsCount > 0) ? $totalResolutionTime / $resolvedComplaintsCount : 0;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Complaints</title>
    <!-- <link rel="stylesheet" href="web/css/style.css"> -->
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
    
    /* Complaint specific styles */
    .complaint-card {
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        background-color: #fff;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .status-open { color: #ff9800; }
    .status-in_progress { color: #2196f3; }
    .status-resolved { color: #4caf50; }
    .stats-card {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        text-align: center;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    }
    .filter-form {
        background: white;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-exclamation-triangle"></i> Manage Complaints</h1>
            <p>View and manage student complaints in your hostel</p>
        </div>
        
        <?php if ($successMessage): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $successMessage; ?>
            </div>
        <?php endif; ?>
        <?php if ($errorMessage): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $errorMessage; ?>
            </div>
        <?php endif; ?>
        <?php if ($infoMessage): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> <?php echo $infoMessage; ?>
            </div>
        <?php endif; ?>

        <!-- Statistics Section -->
        <div class="section">
            <div class="section-header">
                <h3><i class="fas fa-chart-bar"></i> Statistics</h3>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="stats-card">
                        <h4>Overall Resolution Rate (Your Hostel)</h4>
                        <p><strong><?php echo round($overallComplaintResolutionRate, 2); ?>%</strong></p>
                    </div>
            </div>
            <div class="col-md-6">
                <div class="stats-card">
                    <h4>Overall Avg. Resolution Time (Your Hostel)</h4>
                    <p><strong><?php echo round($overallAvgResolutionTime, 2); ?> days</strong></p>
                </div>
            </div>
        </div>

        <!-- All Complaints Section -->
        <div class="section">
            <div class="section-header">
                <h3><i class="fas fa-list"></i> All Complaints</h3>
            </div>
            
            <?php if (empty($allComplaints)): ?>
                <p>No complaints found for your hostel.</p>
            <?php else: ?>
                <?php foreach ($allComplaints as $complaint): ?>
                    <div class="complaint-card">
                        <p><strong>Complaint ID:</strong> <?php echo $complaint['complaint_id']; ?></p>
                        
                        <p><strong>Student:</strong> <?php echo htmlspecialchars($complaint['Fname'] . ' ' . $complaint['Lname'] . ' (' . $complaint['Student_id'] . ')'); ?></p>
                        
                        <p><strong>Category:</strong> <?php echo htmlspecialchars($complaint['category']); ?></p>
                        <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($complaint['description'])); ?></p>
                        <p><strong>Submitted On:</strong> <?php echo $complaint['submission_date']; ?></p>
                        <p><strong>Status:</strong> <span class="status-<?php echo $complaint['status']; ?>"><?php echo ucfirst($complaint['status']); ?></span></p>
                        <?php if ($complaint['status'] === 'resolved' && $complaint['resolve_date']): ?>
                            <p><strong>Resolved On:</strong> <?php echo $complaint['resolve_date']; ?></p>
                            <?php
                                $submissionDateTime = new DateTime($complaint['submission_date']);
                                $resolveDateTime = new DateTime($complaint['resolve_date']);
                                $interval = $submissionDateTime->diff($resolveDateTime);
                            ?>
                            <p><strong>Resolution Time:</strong> <?php echo $interval->days; ?> days</p>
                        <?php endif; ?>

                        <form action="includes/update_complaint.inc.php" method="POST" class="mt-3">
                            <input type="hidden" name="complaint_id" value="<?php echo $complaint['complaint_id']; ?>">
                            <div class="form-group">
                                <label for="status-<?php echo $complaint['complaint_id']; ?>">Update Status:</label>
                                <select class="form-control" id="status-<?php echo $complaint['complaint_id']; ?>" name="status">
                                    <option value="open" <?php echo ($complaint['status'] == 'open') ? 'selected' : ''; ?>>Open</option>
                                    <option value="in_progress" <?php echo ($complaint['status'] == 'in_progress') ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="resolved" <?php echo ($complaint['status'] == 'resolved') ? 'selected' : ''; ?>>Resolved</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm mt-2">Update</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div> 
    </div><!-- Close main container -->

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
</body>
</html>
<?php mysqli_close($conn); ?>