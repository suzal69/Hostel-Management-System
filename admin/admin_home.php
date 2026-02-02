<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/admin_header.php';

// Admin check: accept the session created by includes/login-hm.inc.php
// (it sets $_SESSION['isadmin'] and $_SESSION['admin_username'])
if (empty($_SESSION['admin_username']) && (empty($_SESSION['isadmin']) || $_SESSION['isadmin'] != 1)) {
    // Redirect to the hostel-manager login page (existing file)
    header('Location: ../login-hostel_manager.php');
    exit;
}

// Fetch dashboard statistics
$total_students = 0;
$total_hostel_managers = 0;
$total_complaints = 0;
$pending_complaints = 0;
$occupied_rooms = 0;
$total_rooms = 0;

if (isset($conn)) {
    // Get total students
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM student");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $total_students = $row['count'] ?? 0;
        $stmt->close();
    }
    
    // Get total hostel managers
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM hostel_manager where Isadmin = 0");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $total_hostel_managers = $row['count'] ?? 0;
        $stmt->close();
    }
    
    // Get total complaints
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM complaints");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $total_complaints = $row['count'] ?? 0;
        $stmt->close();
    }
    
    // Get pending complaints
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM complaints WHERE status = 'pending'");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $pending_complaints = $row['count'] ?? 0;
        $stmt->close();
    }
    
    // Get room statistics using proper bed occupancy logic
    $stmt = $conn->prepare("SELECT SUM(bed_capacity) as total_beds, SUM(current_occupancy) as total_occupied, COUNT(*) as total_rooms FROM room");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $total_beds = $row['total_beds'] ?? 0;
        $total_occupied = $row['total_occupied'] ?? 0;
        $total_rooms = $row['total_rooms'] ?? 0;
        $occupied_rooms = $total_occupied; // Using occupied beds as occupied rooms count
        $stmt->close();
    }
}
?>
<br><br><br>
<body class="admin-body">
<div class="container">
    <!-- Welcome Header -->
    <div class="header">
        <h1>Welcome to Admin Dashboard</h1>
        <p>Manage your hostel management system efficiently</p>
    </div>

    <!-- Statistics Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?php echo $total_students; ?></div>
            <div class="stat-label">Total Students</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $total_hostel_managers; ?></div>
            <div class="stat-label">Hostel Managers</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $total_complaints; ?></div>
            <div class="stat-label">Total Complaints</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $total_occupied . '/' . $total_beds; ?></div>
            <div class="stat-label">Occupied Beds</div>
        </div>
    </div>

    <!-- Quick Actions Section -->
    <div class="section">
        <div class="section-header">
            <h3><i class="fa fa-bolt"></i> Quick Actions</h3>
        </div>
        <div class="info-grid">
            <div class="info-card">
                <div class="info-section">
                    <h4><i class="fa fa-user-plus"></i> User Management</h4>
                    <div style="display: grid; gap: 15px;">
                        <a href="create_hm.php" class="btn btn-primary">Appoint Hostel Manager</a>
                        <a href="students.php" class="btn btn-primary">View All Students</a>
                        <a href="manager_details.php" class="btn btn-primary">Manager Details</a>
                    </div>
                </div>
            </div>
            <div class="info-card">
                <div class="info-section">
                    <h4><i class="fa fa-bed"></i> Room Management</h4>
                    <div style="display: grid; gap: 15px;">
                        <a href="admin_room_management.php" class="btn btn-primary">Manage Rooms</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity Section -->
    <div class="section">
        <div class="section-header">
            <h3><i class="fa fa-chart-line"></i> System Overview</h3>
        </div>
        <div class="info-grid">
            <div class="info-card">
                <div class="info-section">
                    <h4><i class="fa fa-exclamation-triangle"></i> Complaint Status</h4>
                    <div style="padding: 20px; background: #f8f9fa; border-radius: 8px; margin-bottom: 15px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span>Total Complaints:</span>
                            <strong><?php echo $total_complaints; ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span>Pending:</span>
                            <strong style="color: #dc3545;"><?php echo $pending_complaints; ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>Resolved:</span>
                            <strong style="color: #28a745;"><?php echo $total_complaints - $pending_complaints; ?></strong>
                        </div>
                    </div>
                    <a href="admin_complaint_review.php" class="btn btn-primary">Review Complaints</a>
                </div>
            </div>
            <div class="info-card">
                <div class="info-section">
                    <h4><i class="fa fa-chart-bar"></i> Analytics</h4>
                    <div style="padding: 20px; background: #f8f9fa; border-radius: 8px; margin-bottom: 15px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span>Bed Occupancy:</span>
                            <strong><?php echo $total_beds > 0 ? round(($total_occupied / $total_beds) * 100, 1) : 0; ?>%</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span>Total Beds:</span>
                            <strong><?php echo $total_beds; ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span>Available Beds:</span>
                            <strong style="color: #28a745;"><?php echo $total_beds - $total_occupied; ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>Active Managers:</span>
                            <strong><?php echo $total_hostel_managers; ?></strong>
                        </div>
                    </div>
                    <a href="admin_analytics.php" class="btn btn-primary">View Analytics</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Notifications Section -->
    <?php if ($pending_complaints > 0): ?>
    <div class="section">
        <div class="section-header">
            <h3><i class="fa fa-bell"></i> Pending Actions</h3>
        </div>
        <div class="alert alert-warning" style="margin: 20px 0;">
            <strong>Attention:</strong> You have <?php echo $pending_complaints; ?> pending complaint(s) that need review.
            <div style="margin-top: 10px;">
                <a href="admin_complaint_review.php" class="btn btn-primary btn-sm">Review Now</a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- System Information -->
    <div class="section">
        <div class="section-header">
            <h3><i class="fa fa-info-circle"></i> System Information</h3>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
            <div style="padding: 20px; background: #f8f9fa; border-radius: 8px;">
                <h5 style="color: #003366; margin-bottom: 15px;">Quick Links</h5>
                <div style="display: grid; gap: 10px;">
                    <a href="admin_contact.php" class="btn btn-sm btn-primary">Contact Management</a>
                    <a href="admin_profile.php" class="btn btn-sm btn-primary">My Profile</a>
                </div>
            </div>
            <div style="padding: 20px; background: #f8f9fa; border-radius: 8px;">
                <h5 style="color: #003366; margin-bottom: 15px;">Recent Activity</h5>
                <p style="color: #6c757d; margin: 0;">System is running normally. Last backup: <?php echo date('Y-m-d H:i'); ?></p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>

</body>
</html>