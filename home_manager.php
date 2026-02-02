<?php
require 'includes/config.inc.php';
require_once 'includes/manager_header.php';

// Check if manager is logged in
if (!isset($_SESSION['hostel_man_id'])) {
    header("Location: login-hostel_manager.php");
    exit();
}

// Get manager information
$manager_id = $_SESSION['hostel_man_id'];
$hostel_id = $_SESSION['hostel_id'];

// Get manager statistics
$stats = [
    'total_students' => 0,
    'total_rooms' => 0,
    'occupied_rooms' => 0,
    'pending_applications' => 0,
    'pending_messages' => 0
];

// Get total students in manager's hostel
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM Student WHERE Hostel_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $hostel_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stats['total_students'] = $row['count'] ?? 0;
    $stmt->close();
}

// Get room statistics
$stmt = $conn->prepare("SELECT COUNT(*) as total_rooms, SUM(current_occupancy) as occupied_beds, SUM(bed_capacity) as total_beds FROM Room WHERE Hostel_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $hostel_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stats['total_rooms'] = $row['total_rooms'] ?? 0;
    $stats['occupied_rooms'] = $row['occupied_beds'] ?? 0;
    $stats['total_beds'] = $row['total_beds'] ?? 0;
    $stmt->close();
}

// Get pending room allocation applications
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM Application WHERE Hostel_id = ? AND Application_status = '1'");
if ($stmt) {
    $stmt->bind_param("i", $hostel_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stats['pending_applications'] = $row['count'] ?? 0;
    $stmt->close();
}

// Get pending messages
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM Message WHERE receiver_id = ? AND read_status = 0");
if ($stmt) {
    $stmt->bind_param("s", $manager_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stats['pending_messages'] = $row['count'] ?? 0;
    $stmt->close();
}

// Get recent activities
$recent_activities = [];
$stmt = $conn->prepare("SELECT 'allocation' as type, s.Fname, s.Lname, r.Room_No, ba.allocation_date 
                        FROM bed_allocation ba 
                        JOIN Student s ON ba.student_id = s.Student_id 
                        JOIN Room r ON ba.room_id = r.Room_id 
                        WHERE ba.room_id IN (SELECT Room_id FROM Room WHERE Hostel_id = ?) 
                        ORDER BY ba.allocation_date DESC LIMIT 5");
if ($stmt) {
    $stmt->bind_param("i", $hostel_id);
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
    <title>Manager Dashboard - Hostel Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    
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
        .activity-name {
            font-weight: 600;
            color: #003366;
        }
        .activity-details {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .activity-time {
            color: #6c757d;
            font-size: 0.8rem;
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
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Welcome Header -->
        <div class="header">
            <h1>Welcome to Manager Dashboard</h1>
            <p>Manage your hostel efficiently and effectively</p>
        </div>

        <!-- Statistics Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_students']; ?></div>
                <div class="stat-label">Total Students</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['occupied_rooms']; ?>/<?php echo $stats['total_beds']; ?></div>
                <div class="stat-label">Occupied Beds</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['pending_applications']; ?></div>
                <div class="stat-label">Pending Applications</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['pending_messages']; ?></div>
                <div class="stat-label">Pending Messages</div>
            </div>
        </div>

        <!-- Quick Actions Section -->
        <div class="section">
            <div class="section-header">
                <h3>Quick Actions</h3>
            </div>
            <div class="quick-actions">
                <div class="action-card">
                    <h4>Room Management</h4>
                    <a href="allocate_room.php" class="btn">Allocate Room</a>
                    <a href="allocated_rooms.php" class="btn">View Allocations</a>
                </div>
                <div class="action-card">
                    <h4>Student Management</h4>
                    <a href="change_room.php" class="btn">Change Room</a>
                    <a href="vacate_rooms.php" class="btn">Vacate Room</a>
                </div>
                <div class="action-card">
                    <h4>Communication</h4>
                    <a href="contact_manager.php" class="btn">Send Message</a>
                    <a href="message_hostel_manager.php" class="btn">View Messages</a>
                </div>
                <div class="action-card">
                    <h4>Management</h4>
                    <a href="leave_management.php" class="btn">Leave Management</a>
                    <a href="manager_complaints.php" class="btn">Complaints</a>
                </div>
            </div>
        </div>

        <!-- Recent Activities Section -->
        <div class="section">
            <div class="section-header">
                <h3>Recent Activities</h3>
            </div>
            <?php if (!empty($recent_activities)): ?>
                <?php foreach ($recent_activities as $activity): ?>
                    <div class="activity-item">
                        <div class="activity-info">
                            <div class="activity-name">
                                <?php echo htmlspecialchars($activity['Fname'] . ' ' . $activity['Lname']); ?>
                            </div>
                            <div class="activity-details">
                                Allocated to Room <?php echo htmlspecialchars($activity['Room_No']); ?>
                            </div>
                        </div>
                        <div class="activity-time">
                            <?php echo date('M d, Y', strtotime($activity['allocation_date'])); ?>
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
                        <li><a href="home_manager.php">Home</a></li>
                        <li><a href="allocate_room.php">Allocate</a></li>
                        <li><a href="contact_manager.php">Contact</a></li>
                        <li><a href="manager_profile.php">Profile</a></li>
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