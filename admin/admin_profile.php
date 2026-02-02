<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/admin_header.php';
// Use the session variables created by includes/login-hm.inc.php
// (it sets $_SESSION['isadmin'] and $_SESSION['admin_username'] for admins)
if (empty($_SESSION['admin_username']) || empty($_SESSION['isadmin']) || $_SESSION['isadmin'] != 1) {
    header('Location: ../login-hostel_manager.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<title>Admin Profile</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="keywords" content="Admin Profile Widget Responsive web template, Bootstrap Web Templates, Flat Web Templates, Android Compatible web template,
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
        background: linear-gradient(135deg, #003366 0%, #004080 100%);
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
        color: #003366;
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
        border-bottom: 3px solid #003366;
        display: flex;
        align-items: center;
    }
    
    .info-section h3 i {
        margin-right: 15px;
        color: #003366;
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
        background: linear-gradient(90deg, #003366, #004080);
    }
    
    .info-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 15px 40px rgba(0,0,0,0.1);
        border-color: rgba(0, 51, 102, 0.2);
    }
    
    .info-item {
        margin-bottom: 20px;
    }
    
    .info-item:last-child {
        margin-bottom: 0;
    }
    
    .info-label {
        font-size: 14px;
        color: #6c757d;
        margin-bottom: 8px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .info-value {
        font-size: 18px;
        color: #2c3e50;
        font-weight: 600;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 30px;
        margin-bottom: 50px;
    }
    
    .stat-card {
        background: linear-gradient(135deg, #003366 0%, #004080 100%);
        color: white;
        padding: 30px;
        border-radius: 20px;
        text-align: center;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.15);
    }
    
    .stat-number {
        font-size: 48px;
        font-weight: 700;
        margin-bottom: 10px;
    }
    
    .stat-label {
        font-size: 16px;
        opacity: 0.9;
        font-weight: 400;
    }
    
    @media (max-width: 768px) {
        .main {
            margin: 20px auto;
            padding: 0 15px;
        }
        
        .profile-header {
            padding: 40px 20px;
        }
        
        .profile-name {
            font-size: 28px;
        }
        
        .profile-title {
            font-size: 18px;
        }
        
        .profile-content {
            padding: 30px 20px;
        }
        
        .info-section h3 {
            font-size: 24px;
        }
        
        .info-grid {
            grid-template-columns: 1fr;
        }
        
        .stats-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
</head>
        margin-bottom: 15px;
    }
    .address {
        list-style: none;
        padding: 0;
    }
    .address-text {
        list-style: none;
        padding: 0;
        display: flex;
    }
    .address-text li:first-child {
        font-weight: bold;
        margin-right: 10px;
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

<div class="main">
    <div class="profile-container">
        <div class="profile-header">
            <div class="profile-avatar">
                <i class="fas fa-user-shield"></i>
            </div>
            <h1 class="profile-name"><?php 
                $fname = $_SESSION['fname'] ?? 'Admin';
                $lname = $_SESSION['lname'] ?? 'User';
                echo htmlspecialchars($fname . " " . $lname); 
            ?></h1>
            <p class="profile-title">System Administrator</p>
        </div>
        
        <div class="profile-content">
            <!-- Personal Information Section -->
            <div class="info-section">
                <h3><i class="fa fa-user"></i> Personal Information</h3>
                <div class="info-grid">
                    <div class="info-card">
                        <div class="info-item">
                            <div class="info-label">Username</div>
                            <div class="info-value"><?php echo htmlspecialchars($_SESSION['username'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Phone Number</div>
                            <div class="info-value"><?php echo htmlspecialchars($_SESSION['mob_no'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Email</div>
                            <div class="info-value"><?php echo htmlspecialchars($_SESSION['email'] ?? 'N/A'); ?></div>
                        </div>
                    </div>
                    <div class="info-card">
                        <div class="info-item">
                            <div class="info-label">Full Name</div>
                            <div class="info-value"><?php 
                                $fname = $_SESSION['fname'] ?? 'N/A';
                                $lname = $_SESSION['lname'] ?? '';
                                echo htmlspecialchars($fname . " " . $lname); 
                            ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Role</div>
                            <div class="info-value">Administrator</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Access Level</div>
                            <div class="info-value">Full Access</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- System Statistics Section -->
            <div class="info-section">
                <h3><i class="fas fa-chart-bar"></i> System Overview</h3>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number">
                            <?php
                            // Get total managers count
                            $managers_query = "SELECT COUNT(*) as count FROM hostel_manager WHERE Isadmin = 0";
                            $managers_result = mysqli_query($conn, $managers_query);
                            $managers_count = mysqli_fetch_assoc($managers_result)['count'] ?? 0;
                            echo $managers_count;
                            ?>
                        </div>
                        <div class="stat-label">Total Managers</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">
                            <?php
                            // Get total students count
                            $students_query = "SELECT COUNT(*) as count FROM Student";
                            $students_result = mysqli_query($conn, $students_query);
                            $students_count = mysqli_fetch_assoc($students_result)['count'] ?? 0;
                            echo $students_count;
                            ?>
                        </div>
                        <div class="stat-label">Total Students</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">
                            <?php
                            // Get total hostels count
                            $hostels_query = "SELECT COUNT(*) as count FROM Hostel";
                            $hostels_result = mysqli_query($conn, $hostels_query);
                            $hostels_count = mysqli_fetch_assoc($hostels_result)['count'] ?? 0;
                            echo $hostels_count;
                            ?>
                        </div>
                        <div class="stat-label">Total Hostels</div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions Section -->
            <div class="info-section">
                <h3><i class="fa fa-cog"></i> Quick Actions</h3>
                <div class="info-grid">
                    <div class="info-card">
                        <div class="info-item">
                            <div class="info-label">Manager Management</div>
                            <div class="info-value">
                                <a href="create_hm.php" style="color: #003366; text-decoration: none; font-weight: 600;">Manage Managers</a>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Student Management</div>
                            <div class="info-value">
                                <a href="students.php" style="color: #003366; text-decoration: none; font-weight: 600;">View Students</a>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Room Management</div>
                            <div class="info-value">
                                <a href="admin_room_management.php" style="color: #003366; text-decoration: none; font-weight: 600;">Manage Rooms</a>
                            </div>
                        </div>
                    </div>
                    <div class="info-card">
                        <div class="info-item">
                            <div class="info-label">Complaint Review</div>
                            <div class="info-value">
                                <a href="admin_complaint_review.php" style="color: #003366; text-decoration: none; font-weight: 600;">Review Complaints</a>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Contact Messages</div>
                            <div class="info-value">
                                <a href="admin_contact.php" style="color: #003366; text-decoration: none; font-weight: 600;">View Messages</a>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">System Settings</div>
                            <div class="info-value">
                                <a href="admin_home.php" style="color: #003366; text-decoration: none; font-weight: 600;">Dashboard</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
										<ul class="address-text">
											<li style="color: black;"><b>Email </b></li>
											<li style="color: black;">: <?php echo htmlspecialchars($_SESSION['email'] ?? 'N/A'); ?></li>
										</ul>
									</li>
								</ul>
							</div>
							<div class="clear"></div>
						</div>
					</fieldset>
				</form>
			</div>
		</div>
	</div>
	<script type="text/javascript" src="../web_profile/js/smoothbox.jquery2.js"></script>
</body>
</html>