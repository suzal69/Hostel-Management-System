<?php
// --- 1. CORE CONFIG & SESSION MANAGEMENT ---
require 'includes/config.inc.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if student is logged in. Use 'roll' if 'student_id' is not the primary session key.
// Assuming 'student_id' is used internally and 'roll' is for display/lookup.
$studentId = $_SESSION['student_id'] ?? $_SESSION['roll'] ?? null;
$rollNo = $_SESSION['roll'] ?? $_SESSION['student_id'] ?? null;

// if (!$studentId || !$rollNo) {
//     // Redirect to login page if not logged in. MUST be before any output.
//     header("Location: profile.php");
//     exit();
// }

// --- 2. DATA FETCHING (Using Prepared Statements for security) ---

$studentComplaintsForAnalytics = [];
$personalComplaintResolutionRate = 0;
$personalAvgResolutionTime = 0;
$totalPersonalTickets = 0;
$resolvedPersonalTickets = 0;
$resolvedComplaintCount = 0;
$totalResolutionTime = 0;

// A. Fetch student's complaints
$sql_complaints = "SELECT submission_date, resolve_date, status FROM complaints WHERE student_id = ? ORDER BY submission_date DESC";
$stmt_complaints = mysqli_stmt_init($conn);

if (mysqli_stmt_prepare($stmt_complaints, $sql_complaints)) {
    // Student_id stored as varchar, bind as string
    mysqli_stmt_bind_param($stmt_complaints, "s", $studentId);
    mysqli_stmt_execute($stmt_complaints);
    $result = mysqli_stmt_get_result($stmt_complaints);

    while ($row = mysqli_fetch_assoc($result)) {
        $studentComplaintsForAnalytics[] = $row;
    }
    mysqli_stmt_close($stmt_complaints);
} else {
    // Error handling for the prepared statement
    // error_log("Failed to prepare complaint statement: " . mysqli_error($conn));
}

// B. Calculate personal Complaint Resolution Rate and Average Resolution Time
foreach ($studentComplaintsForAnalytics as $complaint) {
    $totalPersonalTickets++;
    if ($complaint['status'] === 'resolved') {
        $resolvedPersonalTickets++;
        // Ensure both dates exist before calculating time difference
        if (!empty($complaint['submission_date']) && !empty($complaint['resolve_date'])) {
            try {
                $submissionDateTime = new DateTime($complaint['submission_date']);
                $resolveDateTime = new DateTime($complaint['resolve_date']);
                $interval = $submissionDateTime->diff($resolveDateTime);
                $totalResolutionTime += $interval->days;
                $resolvedComplaintCount++;
            } catch (Exception $e) {
                // Handle invalid date formats if necessary
                // error_log("Date Error: " . $e->getMessage());
            }
        }
    }
}

$personalComplaintResolutionRate = ($totalPersonalTickets > 0) ? ($resolvedPersonalTickets / $totalPersonalTickets) * 100 : 0;
$personalAvgResolutionTime = ($resolvedComplaintCount > 0) ? $totalResolutionTime / $resolvedComplaintCount : 0;

// D. Calculate student's total leave savings
$totalLeaveSavings = 0;
$approvedLeaveCount = 0;
$pendingLeaveCount = 0;

$sql_leave_savings = "SELECT status, estimated_reduction FROM leave_applications WHERE student_id = ?";
$stmt_leave_savings = mysqli_stmt_init($conn);

if (mysqli_stmt_prepare($stmt_leave_savings, $sql_leave_savings)) {
    mysqli_stmt_bind_param($stmt_leave_savings, "s", $studentId);
    mysqli_stmt_execute($stmt_leave_savings);
    $result_leave = mysqli_stmt_get_result($stmt_leave_savings);
    
    while ($row_leave = mysqli_fetch_assoc($result_leave)) {
        if ($row_leave['status'] === 'approved') {
            $totalLeaveSavings += $row_leave['estimated_reduction'];
            $approvedLeaveCount++;
        } elseif ($row_leave['status'] === 'pending') {
            $pendingLeaveCount++;
        }
    }
    mysqli_stmt_close($stmt_leave_savings);
}

// C. Fetch Hostel Info and Hostel Manager Info from database
$hostelId = null;
$hostelName = 'None Assigned';
$hmfname = 'None';
$hmlname = 'Assigned';
$hmMob = 'N/A';
$hmemail = 'N/A';
$roomNo = 'None Assigned';

// Get student's current room and hostel assignment from Student table
$sql_student_hostel = "SELECT Room_id, Hostel_id FROM Student WHERE Student_id = ?";
$stmt_student_hostel = mysqli_stmt_init($conn);
if (mysqli_stmt_prepare($stmt_student_hostel, $sql_student_hostel)) {
    mysqli_stmt_bind_param($stmt_student_hostel, "s", $studentId);
    mysqli_stmt_execute($stmt_student_hostel);
    $result_student_hostel = mysqli_stmt_get_result($stmt_student_hostel);
    if ($row_student_hostel = mysqli_fetch_assoc($result_student_hostel)) {
        $roomId = $row_student_hostel['Room_id'];
        $hostelId = $row_student_hostel['Hostel_id'];
    }
    mysqli_stmt_close($stmt_student_hostel);
}

if ($hostelId) {
    // Fetch Hostel Name
    $sql_hostel = "SELECT Hostel_name FROM Hostel WHERE Hostel_id = ?";
    $stmt_hostel = mysqli_stmt_init($conn);
    if (mysqli_stmt_prepare($stmt_hostel, $sql_hostel)) {
        mysqli_stmt_bind_param($stmt_hostel, "s", $hostelId);
        mysqli_stmt_execute($stmt_hostel);
        $result_hostel = mysqli_stmt_get_result($stmt_hostel);
        if ($row_hostel = mysqli_fetch_assoc($result_hostel)) {
            $hostelName = $row_hostel['Hostel_name'];
        }
        mysqli_stmt_close($stmt_hostel);
    }
    
    // Fetch Room Number if student has a room
    if ($roomId) {
        $sql_room = "SELECT Room_No FROM Room WHERE Room_id = ?";
        $stmt_room = mysqli_stmt_init($conn);
        if (mysqli_stmt_prepare($stmt_room, $sql_room)) {
            mysqli_stmt_bind_param($stmt_room, "i", $roomId);
            mysqli_stmt_execute($stmt_room);
            $result_room = mysqli_stmt_get_result($stmt_room);
            if ($row_room = mysqli_fetch_assoc($result_room)) {
                $roomNo = $row_room['Room_No'];
            }
            mysqli_stmt_close($stmt_room);
        }
    }
    
    // Fetch Hostel Manager Info (Non-Admin)
    $sql_hm = "SELECT Fname, Lname, Mob_no, email FROM Hostel_Manager WHERE Hostel_id = ? AND Isadmin = 0";
    $stmt_hm = mysqli_stmt_init($conn);
    if (mysqli_stmt_prepare($stmt_hm, $sql_hm)) {
        mysqli_stmt_bind_param($stmt_hm, "s", $hostelId);
        mysqli_stmt_execute($stmt_hm);
        $result_hm = mysqli_stmt_get_result($stmt_hm);
        if ($row_hm = mysqli_fetch_assoc($result_hm)) {
            $hmfname = $row_hm['Fname'];
            $hmlname = $row_hm['Lname'];
            $hmMob = $row_hm['Mob_no'];
            $hmemail = $row_hm['email'];
        }
        mysqli_stmt_close($stmt_hm);
    }
}

// --- 3. INCLUDE HEADER (After all redirects and data fetching) ---
require 'includes/user_header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>User Profile</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="keywords" content="Consultancy Profile Widget Responsive web template, Bootstrap Web Templates, Flat Web Templates, Android Compatible web template, Smartphone Compatible web template, free webdesigns for Nokia, Samsung, LG, SonyEricsson, Motorola web design" />
    <script type="application/x-javascript">
        addEventListener("load", function() { setTimeout(hideURLbar, 0); }, false);
        function hideURLbar(){ window.scrollTo(0,1); }
    </script>
    <script src="web_profile/js/jquery-2.1.3.min.js" type="text/javascript"></script>
    <script type="text/javascript" src="web_profile/js/sliding.form.js"></script>
    <link href="web_profile/css/style.css" rel="stylesheet" type="text/css" media="all" />
    <link rel="stylesheet" href="web_profile/css/font-awesome.min.css" />
    <link rel="stylesheet" href="web_profile/css/smoothbox.css" type='text/css' media="all" />
    <link href="//fonts.googleapis.com/css?family=Pathway+Gothic+One" rel="stylesheet">
    <link href='//fonts.googleapis.com/css?family=Open+Sans:400,300,300italic,400italic,600,600italic,700,700italic,800,800italic' rel='stylesheet' type='text/css'>
    <script type="text/javascript" src="web_home/js/bootstrap.js"></script> 
    <script src="web_home/js/SmoothScroll.min.js"></script>
    <script type="text/javascript" src="web_home/js/move-top.js"></script>
    <script type="text/javascript" src="web_home/js/easing.js"></script>
    <script type="text/javascript">
        // Your JavaScript functions here
        $(document).ready(function() {
            // Function to scroll to top
            $().UItoTop({ easingType: 'easeOutQuart' });
        });
    </script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background: #f8f9fa;
            min-height: 100vh;
        }
        
        /* Remove old banner styles */
        .banner, .cd-radial-slider-wrapper {
            display: none !important;
        }
        
        /* Modern Profile Container */
        .main {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        /* Hide old navigation */
        #navigation {
            display: none !important;
        }
        
        /* Perfect Modern Card Layout */
        .profile-container {
            background: white;
            border-radius: 24px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            overflow: hidden;
            margin-bottom: 30px;
            border: 1px solid rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .profile-container:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 60px rgba(0,0,0,0.12);
        }
        
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 60px 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .profile-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 20px 20px;
            animation: float 20s linear infinite;
        }
        
        @keyframes float {
            0% { transform: translate(0, 0) rotate(0deg); }
            100% { transform: translate(-50px, -50px) rotate(360deg); }
        }
        
        .profile-avatar {
            width: 140px;
            height: 140px;
            background: white;
            border-radius: 50%;
            margin: 0 auto 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 52px;
            color: #667eea;
            font-weight: 700;
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
            position: relative;
            z-index: 2;
            border: 4px solid rgba(255,255,255,0.2);
            transition: transform 0.3s ease;
        }
        
        .profile-avatar:hover {
            transform: scale(1.05);
        }
        
        .profile-name {
            color: white;
            font-size: 36px;
            font-weight: 700;
            margin: 0 0 12px 0;
            position: relative;
            z-index: 2;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .profile-title {
            color: rgba(255,255,255,0.95);
            font-size: 20px;
            margin: 0;
            position: relative;
            z-index: 2;
            font-weight: 400;
            letter-spacing: 0.5px;
        }
        
        .profile-content {
            padding: 50px 40px;
            background: white;
        }
        
        .info-section {
            margin-bottom: 50px;
        }
        
        .info-section h3 {
            color: #2c3e50;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #667eea;
            display: flex;
            align-items: center;
        }
        
        .info-section h3 i {
            margin-right: 15px;
            color: #667eea;
            font-size: 24px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
        }
        
        .info-card {
            background: #ffffff;
            padding: 30px;
            border-radius: 20px;
            border: 1px solid rgba(0,0,0,0.08);
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .info-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }
        
        .info-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.1);
            border-color: rgba(102, 126, 234, 0.2);
        }
        
        .info-card h4 {
            color: #667eea;
            font-size: 20px;
            font-weight: 600;
            margin: 0 0 25px 0;
            display: flex;
            align-items: center;
        }
        
        .info-card h4::before {
            content: '';
            width: 8px;
            height: 8px;
            background: #667eea;
            border-radius: 50%;
            margin-right: 12px;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #f1f3f5;
            transition: all 0.2s ease;
        }
        
        .info-item:hover {
            background: #f8f9fa;
            margin: 0 -10px;
            padding: 15px 10px;
            border-radius: 8px;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            color: #6c757d;
            font-weight: 500;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .info-value {
            color: #2c3e50;
            font-weight: 600;
            font-size: 15px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 50px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 35px 30px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.25);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 2px, transparent 2px);
            background-size: 30px 30px;
        }
        
        .stat-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 50px rgba(102, 126, 234, 0.35);
        }
        
        .stat-number {
            font-size: 42px;
            font-weight: 800;
            margin-bottom: 12px;
            position: relative;
            z-index: 2;
        }
        
        .stat-label {
            font-size: 16px;
            opacity: 0.95;
            font-weight: 500;
            position: relative;
            z-index: 2;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* Hide old form elements */
        #wrapper, #steps, .w3ls_wrapper, .w3layouts_wrapper, 
        .w3_form, .w3l_form_fancy, .step, .agileinfo, 
        .w3ls_fancy_step, fieldset, legend, .abt-agile,
        .abt-agile-left, .abt-agile-right, .address,
        .address-text {
            display: none !important;
        }
        
        @media (max-width: 768px) {
            .main {
                margin: 20px auto;
                padding: 0 15px;
            }
            
            .profile-header {
                padding: 40px 25px;
            }
            
            .profile-content {
                padding: 35px 25px;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .profile-name {
                font-size: 28px;
            }
            
            .profile-avatar {
                width: 120px;
                height: 120px;
                font-size: 44px;
            }
        }
    </style>
</head>
<body>
    <div class="main">
        <!-- Profile Header Card -->
        <div class="profile-container">
            <div class="profile-header">
                <div class="profile-avatar">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['fname'] . ' ' . $_SESSION['lname']); ?>&background=667eea&color=fff&size=140" alt="Profile Avatar" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                </div>
                <h1 class="profile-name"><?php echo htmlspecialchars($_SESSION['fname'] . ' ' . $_SESSION['lname']); ?></h1>
                <p class="profile-title">Student</p>
            </div>
        </div>

        <!-- Personal Information Section -->
        <div class="profile-container">
            <div class="profile-content">
                <div class="info-section">
                    <h3><i class="fa fa-user"></i> Personal Information</h3>
                    <div class="info-grid">
                        <div class="info-card">
                            <h4>Student Details</h4>
                            <div class="info-item">
                                <span class="info-label">Roll Number</span>
                                <span class="info-value"><?php echo htmlspecialchars($rollNo); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Phone</span>
                                <span class="info-value"><?php echo htmlspecialchars($_SESSION['mob_no'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Email</span>
                                <span class="info-value"><?php echo htmlspecialchars($_SESSION['email'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Department</span>
                                <span class="info-value"><?php echo htmlspecialchars($_SESSION['department'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Year of Study</span>
                                <span class="info-value"><?php echo htmlspecialchars($_SESSION['year_of_study'] ?? 'N/A'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hostel Information Section -->
        <div class="profile-container">
            <div class="profile-content">
                <div class="info-section">
                    <h3><i class="fa fa-home"></i> Hostel Information</h3>
                    <div class="info-grid">
                        <div class="info-card">
                            <h4>Accommodation Details</h4>
                            <div class="info-item">
                                <span class="info-label">Hostel Name</span>
                                <span class="info-value"><?php echo htmlspecialchars($hostelName); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Room Number</span>
                                <span class="info-value"><?php echo htmlspecialchars($roomNo); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Complaint Analytics Section -->
        <div class="profile-container">
            <div class="profile-content">
                <div class="info-section">
                    <h3><i class="fa fa-chart-line"></i> Complaint Analytics</h3>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $totalPersonalTickets; ?></div>
                            <div class="stat-label">Total Complaints</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $resolvedPersonalTickets; ?></div>
                            <div class="stat-label">Resolved Complaints</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo round($personalComplaintResolutionRate, 1); ?>%</div>
                            <div class="stat-label">Resolution Rate</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo round($personalAvgResolutionTime, 1); ?></div>
                            <div class="stat-label">Avg. Resolution Time (Days)</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Leave Analytics Section -->
        <div class="profile-container">
            <div class="profile-content">
                <div class="info-section">
                    <h3><i class="fa fa-calendar"></i> Leave Analytics & Savings</h3>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-number">Rs<?php echo number_format($totalLeaveSavings, 0); ?></div>
                            <div class="stat-label">Total Savings</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $approvedLeaveCount; ?></div>
                            <div class="stat-label">Approved Leaves</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $pendingLeaveCount; ?></div>
                            <div class="stat-label">Pending Leaves</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">
                                <a href="leave_application.php" style="color: white; text-decoration: none;">
                                    <i class="fa fa-plus"></i> Apply
                                </a>
                            </div>
                            <div class="stat-label">New Leave</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hostel Manager Information Section -->
        <div class="profile-container">
            <div class="profile-content">
                <div class="info-section">
                    <h3><i class="fa fa-user-tie"></i> Hostel Manager Information</h3>
                    <div class="info-grid">
                        <div class="info-card">
                            <h4>Manager Details</h4>
                            <div class="info-item">
                                <span class="info-label">Manager Name</span>
                                <span class="info-value"><?php echo htmlspecialchars($hmfname . ' ' . $hmlname); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Hostel</span>
                                <span class="info-value"><?php echo htmlspecialchars($hostelName); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Phone</span>
                                <span class="info-value"><?php echo htmlspecialchars($hmMob); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Email</span>
                                <span class="info-value"><?php echo htmlspecialchars($hmemail); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script type="text/javascript" src="web_profile/js/smoothbox.jquery2.js"></script>
    <script>
        $(document).ready(function() {
            $().UItoTop({ easingType: 'easeOutQuart' });
        });
    </script>
</body>
</html>