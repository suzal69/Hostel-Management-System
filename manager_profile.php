<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Assuming config.inc.php establishes the database connection $conn
require __DIR__ . '/includes/config.inc.php';
require_once 'includes/manager_header.php';
// NOTE: I am assuming that session_start() is called in config.inc.php or somewhere before this point.
// All access to $_SESSION variables are protected using the null coalescing operator (??).
if (session_status() !== PHP_SESSION_ACTIVE) {
    echo "<div><strong style='color:red;'>WARNING: Session is NOT active! (Should be fixed now)</strong></div>";
}

// Get manager's hostel ID
$HOID = $_SESSION['hostel_id'] ?? '';
$HNM = 'Unknown Hostel'; // Default hostel name

// Fetch hostel name
if (!empty($HOID) && isset($conn) && $conn) {
    $query_hostel = "SELECT Hostel_name FROM Hostel WHERE Hostel_id = ?";
    $stmt_hostel = $conn->prepare($query_hostel);
    
    if ($stmt_hostel) {
        $stmt_hostel->bind_param("i", $HOID);
        $stmt_hostel->execute();
        $result_hostel = $stmt_hostel->get_result();
        
        if ($result_hostel && $row_hostel = $result_hostel->fetch_assoc()) {
            $HNM = htmlspecialchars($row_hostel['Hostel_name']);
        }
        $stmt_hostel->close();
    }
}

// Calculate complaint analytics
$overallComplaintResolutionRate = 0.00;
$overallAvgResolutionTime = 0.00;

if (!empty($HOID) && isset($conn) && $conn) {
    // Calculate Resolution Rate
    $stmt_total = $conn->prepare("SELECT COUNT(*) AS TotalComplaints,
                                          SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) AS ResolvedComplaints
                                   FROM complaints
                                   WHERE hostel_id = ?");

    if ($stmt_total) {
        $stmt_total->bind_param("i", $HOID);
        $stmt_total->execute();
        $result_total = $stmt_total->get_result();

        if ($result_total && $row_total = $result_total->fetch_assoc()) {
            $totalComplaints = (int)($row_total['TotalComplaints'] ?? 0);
            $resolvedComplaints = (int)($row_total['ResolvedComplaints'] ?? 0);

            if ($totalComplaints > 0) {
                $overallComplaintResolutionRate = ($resolvedComplaints / $totalComplaints) * 100;
            }
        }
        $stmt_total->close();
    }

    // Calculate Average Resolution Time
    $query_avg_time = "SELECT AVG(DATEDIFF(resolve_date, submission_date)) AS AvgResolutionDays
                       FROM complaints
                       WHERE hostel_id = ? AND status = 'resolved' AND resolve_date IS NOT NULL";

    $stmt_avg_time = $conn->prepare($query_avg_time);

    if ($stmt_avg_time) {
        $stmt_avg_time->bind_param("i", $HOID);
        $stmt_avg_time->execute();
        $result_avg_time = $stmt_avg_time->get_result();

        if ($result_avg_time && $row_avg_time = $result_avg_time->fetch_assoc()) {
            $overallAvgResolutionTime = (float)($row_avg_time['AvgResolutionDays'] ?? 0.00);
        }
        $stmt_avg_time->close();
    }
}

// --------------------------------------------------------------------------------
// FIX 2: RETRIEVE ADMIN INFO (Using prepared statement for best practice)
// --------------------------------------------------------------------------------

$adFname = $adLname = $adUname = $adMob = $adEmail = "Not Available";

if (isset($conn) && $conn) {
    $ad = 1;
    $queryA = "SELECT Fname, Lname, Username, Mob_no, email FROM Hostel_Manager WHERE Isadmin = ?";
    $stmtA = $conn->prepare($queryA);

    if ($stmtA) {
        $stmtA->bind_param("i", $ad); // 'i' for integer type
        $stmtA->execute();
        $resultA = $stmtA->get_result();

        if ($resultA && $rowA = $resultA->fetch_assoc()) {
            // Safely retrieve and assign
            $adFname = htmlspecialchars($rowA['Fname'] ?? 'Admin');
            $adLname = htmlspecialchars($rowA['Lname'] ?? 'User');
            $adUname = htmlspecialchars($rowA['Username'] ?? 'N/A');
            $adMob = htmlspecialchars($rowA['Mob_no'] ?? 'N/A');
            $adEmail = htmlspecialchars($rowA['email'] ?? 'N/A');
        } else {
            echo ""; 
        }
        $stmtA->close();
    }
}
// --------------------------------------------------------------------------------
?>

<!DOCTYPE html>
<html lang="en">
<head>
<title>User Profile</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="keywords" content="Consultancy Profile Widget Responsive web template, Bootstrap Web Templates, Flat Web Templates, Android Compatible web template,
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
        <div class="profile-container">
            <div class="profile-header">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr(htmlspecialchars($_SESSION['fname'] ?? 'H'), 0, 1) . substr(htmlspecialchars($_SESSION['lname'] ?? 'M'), 0, 1)); ?>
                </div>
                <h1 class="profile-name"><?php echo htmlspecialchars(($_SESSION['fname'] ?? 'Hostel') . " " . ($_SESSION['lname'] ?? 'Manager')); ?></h1>
                <p class="profile-title">Hostel Manager</p>
            </div>
            
            <div class="profile-content">
                <!-- Statistics Section -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo round($overallComplaintResolutionRate, 2); ?>%</div>
                        <div class="stat-label">Resolution Rate</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo round($overallAvgResolutionTime, 2); ?></div>
                        <div class="stat-label">Avg Resolution Days</div>
                    </div>
                </div>
                
                <!-- Personal Information Section -->
                <div class="info-section">
                    <h3><i class="fas fa-user"></i> Personal Information</h3>
                    <div class="info-grid">
                        <div class="info-card">
                            <h4>Contact Details</h4>
                            <div class="info-item">
                                <span class="info-label">Username</span>
                                <span class="info-value"><?php echo htmlspecialchars($_SESSION['username'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Phone</span>
                                <span class="info-value"><?php echo htmlspecialchars($_SESSION['Mob_no'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Email</span>
                                <span class="info-value"><?php echo htmlspecialchars($_SESSION['email'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Hostel</span>
                                <span class="info-value"><?php echo htmlspecialchars($HNM ?? 'N/A'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Admin Information Section -->
                <div class="info-section">
                    <h3><i class="fas fa-shield-alt"></i> Admin Information</h3>
                    <div class="info-grid">
                        <div class="info-card">
                            <h4>System Administrator</h4>
                            <div class="info-item">
                                <span class="info-label">Name</span>
                                <span class="info-value"><?php echo ($adFname ?? '') . " " . ($adLname ?? ''); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Username</span>
                                <span class="info-value"><?php echo $adUname ?? 'N/A'; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Phone</span>
                                <span class="info-value"><?php echo $adMob ?? 'N/A'; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Email</span>
                                <span class="info-value"><?php echo $adEmail ?? 'N/A'; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script type="text/javascript" src="web_profile/js/smoothbox.jquery2.js"></script>
    <script type="text/javascript" src="web_home/js/bootstrap.js"></script>

</body>
</html>