<?php
require_once 'includes/config.inc.php';
require_once 'includes/user_header.php';

// Check if user is logged in
if (!isset($_SESSION['roll'])) {
    header("Location: login.php");
    exit();
}

// Get student information
$student_id = $_SESSION['roll'];
$student_name = $_SESSION['fname'] . ' ' . $_SESSION['lname'];

// Get student's current allocation and details
$student_query = "SELECT s.*, ba.allocation_price, ba.bed_number, h.Hostel_name, r.Room_No, r.bed_capacity, r.current_occupancy
                 FROM Student s 
                 LEFT JOIN bed_allocation ba ON s.Student_id = ba.student_id AND ba.is_active = 1
                 LEFT JOIN Hostel h ON s.Hostel_id = h.Hostel_id
                 LEFT JOIN Room r ON s.Room_id = r.Room_id
                 WHERE s.Student_id = ?";
$stmt = mysqli_prepare($conn, $student_query);
mysqli_stmt_bind_param($stmt, "s", $student_id);
mysqli_stmt_execute($stmt);
$student_result = mysqli_stmt_get_result($stmt);
$student_data = mysqli_fetch_assoc($student_result);

// Get student statistics
$stats = [
    'pending_leave_applications' => 0,
    'approved_leave_applications' => 0,
    'total_complaints' => 0,
    'pending_complaints' => 0,
    'unread_messages' => 0
];

// Get leave application statistics
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM leave_applications WHERE student_id = ? AND status = 'pending'");
if ($stmt) {
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stats['pending_leave_applications'] = $row['count'] ?? 0;
    $stmt->close();
}

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM leave_applications WHERE student_id = ? AND status = 'approved'");
if ($stmt) {
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stats['approved_leave_applications'] = $row['count'] ?? 0;
    $stmt->close();
}

// Get complaint statistics
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM complaints WHERE student_id = ?");
if ($stmt) {
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stats['total_complaints'] = $row['count'] ?? 0;
    $stmt->close();
}

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM complaints WHERE student_id = ? AND status = 'pending'");
if ($stmt) {
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stats['pending_complaints'] = $row['count'] ?? 0;
    $stmt->close();
}

// Get unread messages
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM Message WHERE receiver_id = ? AND read_status = 0");
if ($stmt) {
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stats['unread_messages'] = $row['count'] ?? 0;
    $stmt->close();
}

// Get recent activities
$recent_activities = [];
$stmt = $conn->prepare("SELECT 'leave' as type, leave_type, start_date, end_date, status, applied_date 
                        FROM leave_applications 
                        WHERE student_id = ? 
                        ORDER BY applied_date DESC LIMIT 3");
if ($stmt) {
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $recent_activities[] = $row;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Student Dashboard - Hostel Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    
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
            padding: 0 15px;
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
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        .stat-card {
            background: linear-gradient(135deg, #003366 0%, #004080 100%);
            color: white;
            padding: 30px;
            border-radius: 16px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .stat-card::before {
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
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }
        .stat-number {
            font-size: 42px;
            font-weight: 700;
            margin-bottom: 10px;
            position: relative;
            z-index: 2;
        }
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
            font-weight: 400;
            position: relative;
            z-index: 2;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        .action-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        .action-card:hover {
            border-color: #003366;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .action-card h4 {
            color: #003366;
            margin-bottom: 15px;
            font-size: 1.2rem;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #003366 0%, #004080 100%);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin: 5px;
        }
        .btn:hover {
            background: linear-gradient(135deg, #ffcc00 0%, #ffdb4d 100%);
            color: #003366;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            text-decoration: none;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        .info-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
        }
        .info-card h4 {
            color: #003366;
            margin-bottom: 15px;
            font-size: 1.1rem;
        }
        .info-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e9ecef;
        }
        .info-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .info-label {
            color: #6c757d;
            font-weight: 500;
        }
        .info-value {
            color: #003366;
            font-weight: 600;
        }
        .activity-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
        }
        .activity-item:last-child {
            border-bottom: none;
        }
        .activity-info {
            flex: 1;
        }
        .activity-type {
            font-weight: 600;
            color: #003366;
            text-transform: capitalize;
        }
        .activity-details {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .activity-time {
            color: #6c757d;
            font-size: 0.8rem;
        }
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }
        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
        .notification-badge {
            background-color: #ffc107;
            color: #212529;
            font-size: 0.75em;
            padding: .2em .6em;
            border-radius: .25rem;
            margin-left: 5px;
            vertical-align: super;
        }
        @keyframes float {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        @media (max-width: 768px) {
            .header h1 {
                font-size: 2rem;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .quick-actions {
                grid-template-columns: 1fr;
            }
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<br>
<body>
    <div class="container">
        <!-- Welcome Header -->
        <div class="header">
            <h1>Welcome to Student Dashboard</h1>
            <p>Hello, <?php echo htmlspecialchars($student_name); ?> - Manage your hostel life efficiently</p>
        </div>

        <!-- Statistics Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['pending_leave_applications']; ?></div>
                <div class="stat-label">Pending Leaves</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['approved_leave_applications']; ?></div>
                <div class="stat-label">Approved Leaves</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['pending_complaints']; ?></div>
                <div class="stat-label">Pending Complaints</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['unread_messages']; ?></div>
                <div class="stat-label">Unread Messages</div>
            </div>
        </div>

        <!-- Student Information Section -->
        <div class="section">
            <div class="section-header">
                <h3><i class="fa fa-user"></i> Your Information</h3>
            </div>
            <div class="info-grid">
                <div class="info-card">
                    <h4>Personal Details</h4>
                    <div class="info-item">
                        <span class="info-label">Student ID:</span>
                        <span class="info-value"><?php echo htmlspecialchars($student_data['Student_id']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Name:</span>
                        <span class="info-value"><?php echo htmlspecialchars($student_name); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Department:</span>
                        <span class="info-value"><?php echo htmlspecialchars($student_data['Dept'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Year:</span>
                        <span class="info-value"><?php echo htmlspecialchars($student_data['Year_of_study'] ?? 'N/A'); ?></span>
                    </div>
                </div>
                <div class="info-card">
                    <h4>Room Details</h4>
                    <?php if ($student_data['Room_No']): ?>
                        <div class="info-item">
                            <span class="info-label">Hostel:</span>
                            <span class="info-value"><?php echo htmlspecialchars($student_data['Hostel_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Room:</span>
                            <span class="info-value"><?php echo htmlspecialchars($student_data['Room_No']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Bed:</span>
                            <span class="info-value"><?php echo htmlspecialchars($student_data['bed_number']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Room Occupancy:</span>
                            <span class="info-value"><?php echo ($student_data['current_occupancy'] ?? 0); ?>/<?php echo ($student_data['bed_capacity'] ?? 0); ?></span>
                        </div>
                    <?php else: ?>
                        <p style="color: #6c757d; text-align: center; padding: 20px;">No room allocated yet</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions Section -->
        <div class="section">
            <div class="section-header">
                <h3><i class="fa fa-bolt"></i> Quick Actions</h3>
            </div>
            <div class="quick-actions">
                <div class="action-card">
                    <h4>Leave Management</h4>
                    <a href="leave_application.php" class="btn">Apply for Leave</a>
                </div>
                <div class="action-card">
                    <h4>Communication</h4>
                    <a href="contact.php" class="btn">Contact Manager</a>
                    <a href="message_user.php" class="btn">View Messages</a>
                </div>
                <div class="action-card">
                    <h4>Services</h4>
                    <a href="complaint.php" class="btn">File Complaint</a>
                    <a href="profile.php" class="btn">Update Profile</a>
                </div>
            </div>
        </div>

        <!-- Recent Activities Section -->
        <div class="section">
            <div class="section-header">
                <h3><i class="fa fa-history"></i> Recent Activities</h3>
            </div>
            <?php if (!empty($recent_activities)): ?>
                <?php foreach ($recent_activities as $activity): ?>
                    <div class="activity-item">
                        <div class="activity-info">
                            <div class="activity-type">
                                <?php echo htmlspecialchars($activity['type']); ?> Application
                            </div>
                            <div class="activity-details">
                                <?php echo ucfirst($activity['leave_type']); ?> - 
                                <?php echo date('M d, Y', strtotime($activity['start_date'])); ?> to 
                                <?php echo date('M d, Y', strtotime($activity['end_date'])); ?>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span class="status-badge status-<?php echo $activity['status']; ?>">
                                <?php echo ucfirst($activity['status']); ?>
                            </span>
                            <div class="activity-time">
                                <?php echo date('M d, Y', strtotime($activity['applied_date'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; color: #6c757d; padding: 20px;">No recent activities found.</p>
            <?php endif; ?>
        </div>
    </div>

    <footer class="py-5" style="background:#36454F;">
        <div class="container py-md-5">
            <div class="footer-logo mb-5 text-center">
                <a class="navbar-brand" href="home.php">Peaceful Living for Young <span class="display"> Scholars</span></a>
            </div>
            <div class="footer-grid">
                <div class="list-footer">
                    <ul class="footer-nav text-center">
                        <li><a href="home.php">Home</a></li>
                        <li><a href="services.php">Hostels</a></li>
                        <li><a href="contact.php">Contact</a></li>
                        <li><a href="profile.php">Profile</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <script type="text/javascript" src="web_home/js/jquery-2.2.3.min.js"></script>
    <script type="text/javascript" src="web_home/js/bootstrap.js"></script>
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
    <script type="text/javascript">
        $(document).ready(function() {
            $().UItoTop({ easingType: 'easeOutQuart' });
        });
    </script>
</body>
</html>