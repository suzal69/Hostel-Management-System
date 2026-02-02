<?php
require_once 'includes/config.inc.php';
require_once 'includes/manager_header.php';
require_once 'includes/price_calculator.php';
require_once 'includes/notification_helper.php';

// Check if user is logged in as manager
if (!isset($_SESSION['hostel_man_id'])) {
    header("Location: login-hostel_manager.php");
    exit();
}

// Get manager information
$manager_id = $_SESSION['hostel_man_id'];
$manager_query = "SELECT Fname, Lname FROM hostel_manager WHERE Hostel_man_id = ?";
$stmt = mysqli_prepare($conn, $manager_query);
mysqli_stmt_bind_param($stmt, "i", $manager_id);
mysqli_stmt_execute($stmt);
$manager_result = mysqli_stmt_get_result($stmt);
$manager = mysqli_fetch_assoc($manager_result);
$manager_name = $manager['Fname'] . ' ' . $manager['Lname'];

// Get pending leave applications
$pending_query = "SELECT la.*, s.Fname, s.Lname, s.Mob_no, s.Email, s.Dept, 
                        h.Hostel_name, r.Room_No, ba.bed_number
                 FROM leave_applications la
                 JOIN Student s ON la.student_id = s.Student_id
                 LEFT JOIN bed_allocation ba ON s.Student_id = ba.student_id AND ba.is_active = 1
                 LEFT JOIN Hostel h ON s.Hostel_id = h.Hostel_id
                 LEFT JOIN Room r ON s.Room_id = r.Room_id
                 WHERE la.status = 'pending'
                 ORDER BY la.applied_date DESC";
$pending_result = mysqli_query($conn, $pending_query);

// Get manager's processed applications
$processed_query = "SELECT la.*, s.Fname, s.Lname, s.Mob_no, s.Email, s.Dept,
                           h.Hostel_name, r.Room_No, ba.bed_number
                    FROM leave_applications la
                    JOIN Student s ON la.student_id = s.Student_id
                    LEFT JOIN bed_allocation ba ON s.Student_id = ba.student_id AND ba.is_active = 1
                    LEFT JOIN Hostel h ON s.Hostel_id = h.Hostel_id
                    LEFT JOIN Room r ON s.Room_id = r.Room_id
                    WHERE la.approved_by = ?
                    ORDER BY la.approved_date DESC";
$stmt = mysqli_prepare($conn, $processed_query);
mysqli_stmt_bind_param($stmt, "i", $manager_id);
mysqli_stmt_execute($stmt);
$processed_result = mysqli_stmt_get_result($stmt);

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leave_id = $_POST['leave_id'];
    $action = $_POST['action']; // 'approve' or 'reject'
    $remarks = $_POST['remarks'] ?? '';
    
    try {
        // Update leave application
        $status = ($action === 'approve') ? 'approved' : 'rejected';
        $update_query = "UPDATE leave_applications 
SET status = ?, approved_by = ?, approved_date = CURDATE(), manager_remarks = ?
WHERE leave_id = ?";
        
        $stmt = mysqli_prepare($conn, $update_query);
        if ($action === 'approve') {
            mysqli_stmt_bind_param($stmt, "ssis", $status, $manager_id, $manager_name, $leave_id);
        } else {
            mysqli_stmt_bind_param($stmt, "ssis", $status, $manager_id, $remarks, $leave_id);
        }
        mysqli_stmt_execute($stmt);
        
        // Set notification as unread for the student
        setLeaveNotificationUnread($leave_id, $conn);
        
        // If approved, create leave adjustment record using centralized function
        if ($action === 'approve') {
            if (createLeaveAdjustment($leave_id, $conn)) {
                $success_message = "Leave application approved successfully! Price adjustments have been applied.";
            } else {
                $error_message = "Leave approved but failed to create price adjustments.";
            }
        } else {
            $success_message = "Leave application " . $status . " successfully!";
        }
        
    } catch (Exception $e) {
        $error_message = "Error processing request: " . $e->getMessage();
    }
}

// Get manager statistics
$stats_query = "SELECT 
                  COUNT(*) as total_processed,
                  SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                  SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                  SUM(estimated_reduction) as total_savings
                FROM leave_applications 
                WHERE approved_by = ?";
$stmt = mysqli_prepare($conn, $stats_query);
mysqli_stmt_bind_param($stmt, "i", $manager_id);
mysqli_stmt_execute($stmt);
$stats_result = mysqli_stmt_get_result($stmt);
$stats = mysqli_fetch_assoc($stats_result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Leave Management - Manager Dashboard</title>
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
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-3px);
        }
        .stat-card .number {
            font-size: 32px;
            font-weight: 600;
            color: #003366;
            margin-bottom: 5px;
        }
        .stat-card .label {
            color: #6c757d;
            font-size: 14px;
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
        .leave-item {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        .leave-item:hover {
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        .student-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .price-info {
            background: linear-gradient(135deg, #e3f2fd 0%, #f8f9fa 100%);
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
        }
        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        .price-row.total {
            font-weight: 600;
            border-top: 1px solid #dee2e6;
            padding-top: 10px;
            margin-top: 10px;
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
        .btn-approve {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        .btn-reject {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
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
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .tab-btn {
            padding: 10px 20px;
            border: 1px solid #dee2e6;
            border-radius: 20px;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .tab-btn.active {
            background: linear-gradient(135deg, #003366 0%, #004080 100%);
            color: white;
            border-color: #003366;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            font-weight: 600;
            margin-bottom: 8px;
            display: block;
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
    </style>
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-user-tie"></i> Leave Management</h1>
            <p>Welcome, <?php echo htmlspecialchars($manager_name); ?> - Review and process student leave applications</p>
        </div>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="number"><?php echo mysqli_num_rows($pending_result); ?></div>
                <div class="label">Pending Applications</div>
            </div>
            <div class="stat-card">
                <div class="number"><?php echo $stats['approved'] ?? 0; ?></div>
                <div class="label">Approved</div>
            </div>
            <div class="stat-card">
                <div class="number"><?php echo $stats['rejected'] ?? 0; ?></div>
                <div class="label">Rejected</div>
            </div>
                    </div>

        <!-- Tabs -->
        <div class="tabs">
            <div class="tab-btn active" onclick="showTab('pending')">
                <i class="fas fa-clock"></i> Pending Applications
            </div>
            <div class="tab-btn" onclick="showTab('processed')">
                <i class="fas fa-history"></i> My Processed Applications
            </div>
        </div>

        <!-- Pending Applications -->
        <div id="pending-tab" class="tab-content active">
            <div class="section">
                <div class="section-header">
                    <h3><i class="fas fa-exclamation-triangle"></i> Pending Applications</h3>
                    <span class="status-badge status-pending"><?php echo mysqli_num_rows($pending_result); ?> pending</span>
                </div>
                
                <?php if (mysqli_num_rows($pending_result) > 0): ?>
                    <?php while ($app = mysqli_fetch_assoc($pending_result)): ?>
                        <div class="leave-item" data-leave-id="<?php echo $app['leave_id']; ?>" data-start-date="<?php echo $app['start_date']; ?>" data-end-date="<?php echo $app['end_date']; ?>">
                            <div class="student-info">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                    <div>
                                        <strong><i class="fas fa-user"></i> Student:</strong> 
                                        <?php echo htmlspecialchars($app['Fname'] . ' ' . $app['Lname']); ?> 
                                        (<?php echo htmlspecialchars($app['student_id']); ?>)
                                        <br>
                                        <strong><i class="fas fa-phone"></i> Contact:</strong> <?php echo htmlspecialchars($app['Mob_no']); ?>
                                        <br>
                                        <strong><i class="fas fa-envelope"></i> Email:</strong> <?php echo htmlspecialchars($app['Email']); ?>
                                    </div>
                                    <div>
                                        <strong><i class="fas fa-graduation-cap"></i> Department:</strong> <?php echo htmlspecialchars($app['Dept']); ?>
                                        <br>
                                        <strong><i class="fas fa-home"></i> Room:</strong> 
                                        <?php 
                                        if ($app['Hostel_name']) {
                                            echo htmlspecialchars($app['Hostel_name']) . ' - Room ' . htmlspecialchars($app['Room_No']) . ', Bed ' . htmlspecialchars($app['bed_number']);
                                        } else {
                                            echo 'Not allocated';
                                        }
                                        ?>
                                        <br>
                                        <strong><i class="fas fa-calendar"></i> Applied:</strong> <?php echo date('M d, Y', strtotime($app['applied_date'])); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="price-info">
                                <h5><i class="fas fa-calculator"></i> Leave Details</h5>
                                <div class="price-row">
                                    <span>Leave Type:</span>
                                    <span><?php echo ucfirst($app['leave_type']); ?></span>
                                </div>
                                <div class="price-row">
                                    <span>Period:</span>
                                    <span><?php echo date('M d, Y', strtotime($app['start_date'])); ?> - <?php echo date('M d, Y', strtotime($app['end_date'])); ?></span>
                                </div>
                                <div class="price-row">
                                    <span>Duration:</span>
                                    <span><?php echo $app['leave_days']; ?> days</span>
                                </div>
                                <div class="price-row">
                                    <span>Food Plan:</span>
                                    <span><?php echo htmlspecialchars($app['food_plan']); ?> (Rs<?php echo $app['original_food_price']; ?>/month)</span>
                                </div>
                                <div class="price-row">
                                    <span>Estimated Reduction:</span>
                                    <span>Rs<?php echo number_format($app['estimated_reduction'], 2); ?></span>
                                </div>
                                <div class="price-row total">
                                    <span>Adjusted Price:</span>
                                    <span>Rs<?php echo number_format($app['original_food_price'] - $app['estimated_reduction'], 2); ?></span>
                                </div>
                            </div>
                            
                            <?php if ($app['remarks']): ?>
                                <div class="alert alert-info" style="margin: 15px 0;">
                                    <i class="fas fa-user"></i> <strong>Student Reason:</strong> <?php echo htmlspecialchars($app['remarks']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($app['manager_remarks']): ?>
                                <div class="alert alert-warning" style="margin: 15px 0;">
                                    <i class="fas fa-user-tie"></i> <strong>Manager Remarks:</strong> <?php echo htmlspecialchars($app['manager_remarks']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div style="margin-top: 15px;">
                                <button class="btn btn-approve" onclick="showApprovalModal(<?php echo $app['leave_id']; ?>, 'approve')">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                                <button class="btn btn-reject" onclick="showApprovalModal(<?php echo $app['leave_id']; ?>, 'reject')">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #6c757d;">No pending applications found.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Processed Applications -->
        <div id="processed-tab" class="tab-content">
            <div class="section">
                <div class="section-header">
                    <h3><i class="fas fa-history"></i> My Processed Applications</h3>
                    <span class="status-badge status-approved"><?php echo mysqli_num_rows($processed_result); ?> processed</span>
                </div>
                
                <?php if (mysqli_num_rows($processed_result) > 0): ?>
                    <?php while ($app = mysqli_fetch_assoc($processed_result)): ?>
                        <div class="leave-item" data-leave-id="<?php echo $app['leave_id']; ?>" data-start-date="<?php echo $app['start_date']; ?>" data-end-date="<?php echo $app['end_date']; ?>">
                            <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 20px; align-items: center;">
                                <div>
                                    <strong><i class="fas fa-user"></i> <?php echo htmlspecialchars($app['Fname'] . ' ' . $app['Lname']); ?> (<?php echo htmlspecialchars($app['student_id']); ?>)</strong>
                                    <br>
                                    <strong><i class="fas fa-calendar-alt"></i> Leave:</strong> <?php echo ucfirst($app['leave_type']); ?> - <?php echo $app['leave_days']; ?> days
                                    <br>
                                    <strong><i class="fas fa-calendar"></i> Period:</strong> <?php echo date('M d, Y', strtotime($app['start_date'])); ?> - <?php echo date('M d, Y', strtotime($app['end_date'])); ?>
                                    <br>
                                    <strong><i class="fas fa-rupee-sign"></i> Savings:</strong> Rs<?php echo number_format($app['estimated_reduction'], 2); ?>
                                </div>
                                <div style="text-align: center;">
                                    <span class="status-badge status-<?php echo $app['status']; ?>">
                                        <?php echo ucfirst($app['status']); ?>
                                    </span>
                                </div>
                                <div style="text-align: right;">
                                    <small><strong>Processed:</strong> <?php echo date('M d, Y', strtotime($app['approved_date'])); ?></small>
                                    <?php if ($app['remarks']): ?>
                                        <br>
                                        <small><strong>Student Reason:</strong> <?php echo htmlspecialchars(substr($app['remarks'], 0, 30)); ?>...</small>
                                    <?php endif; ?>
                                    <?php if ($app['manager_remarks']): ?>
                                        <br>
                                        <small><strong>Manager Remarks:</strong> <?php echo htmlspecialchars(substr($app['manager_remarks'], 0, 30)); ?>...</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #6c757d;">No processed applications found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Approval Modal -->
    <div id="approvalModal" class="modal">
        <div class="modal-content">
            <h3 id="modalTitle">Approve Application</h3>
            <form method="POST" id="approvalForm">
                <input type="hidden" name="leave_id" id="modalLeaveId">
                <input type="hidden" name="action" id="modalAction">
                
                <div class="form-group">
                    <label for="modalRemarks">Remarks (Optional)</label>
                    <textarea name="remarks" id="modalRemarks" class="form-control" rows="3" 
                              placeholder="Add any comments or reasons for this decision..."></textarea>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-paper-plane"></i> Submit Decision
                    </button>
                    <button type="button" class="btn-submit" style="background: #6c757d;" onclick="closeModal()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Add active class to clicked button
            event.target.classList.add('active');
        }
        
        function showApprovalModal(leaveId, action) {
            document.getElementById('modalLeaveId').value = leaveId;
            document.getElementById('modalAction').value = action;
            
            const title = action === 'approve' ? 'Approve Application' : 'Reject Application';
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-' + (action === 'approve' ? 'check' : 'times') + '"></i> ' + title;
            
            document.getElementById('approvalModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('approvalModal').style.display = 'none';
            document.getElementById('modalRemarks').value = '';
        }
        
        // Validate dates before form submission
        function validateLeaveDates(leaveId) {
            // Get the leave application data
            const leaveItem = document.querySelector(`[data-leave-id="${leaveId}"]`);
            if (!leaveItem) {
                console.log('Leave item not found for ID:', leaveId);
                return true;
            }
            
            const startDate = new Date(leaveItem.dataset.startDate);
            const endDate = new Date(leaveItem.dataset.endDate);
            
            // Use PHP date from dataset for comparison to avoid timezone issues
            const todayStr = '<?php echo date('Y-m-d'); ?>';
            const tomorrowStr = '<?php echo date('Y-m-d', strtotime('+1 day')); ?>';
            
            // Enhanced debug logging
            console.log('=== VALIDATION DEBUG ===');
            console.log('Leave ID:', leaveId);
            console.log('Raw start date from dataset:', leaveItem.dataset.startDate);
            console.log('Raw end date from dataset:', leaveItem.dataset.endDate);
            console.log('Parsed start date:', startDate.toString());
            console.log('Parsed end date:', endDate.toString());
            console.log('PHP Today (midnight):', todayStr);
            console.log('PHP Tomorrow (midnight):', tomorrowStr);
            console.log('Start date string:', leaveItem.dataset.startDate);
            console.log('End date string:', leaveItem.dataset.endDate);
            console.log('Comparison - startDate < tomorrow:', leaveItem.dataset.startDate < tomorrowStr);
            console.log('Comparison - endDate < today:', leaveItem.dataset.endDate < todayStr);
            console.log('Comparison - startDate <= endDate:', startDate <= endDate);
            console.log('=== END VALIDATION DEBUG ===');
            
            // Validation checks
            if (leaveItem.dataset.startDate < tomorrowStr) {
                console.log('VALIDATION FAILED: Start date is before tomorrow');
                alert('Start date must be from tomorrow onwards. This leave application cannot be processed.');
                return false;
            }
            
            if (leaveItem.dataset.endDate < todayStr) {
                console.log('VALIDATION FAILED: End date is in the past');
                alert('End date cannot be in the past. This leave cannot be processed.');
                return false;
            }
            
            // Allow same date for start and end (1-day leave)
            console.log('VALIDATION PASSED: Same-day leave allowed');
            return true;
        }
        
        // Override form submission to add validation
        document.getElementById('approvalForm').addEventListener('submit', function(e) {
            const leaveId = document.getElementById('modalLeaveId').value;
            const action = document.getElementById('modalAction').value;
            
            // Only validate for approval action
            if (action === 'approve') {
                if (!validateLeaveDates(leaveId)) {
                    e.preventDefault();
                    return false;
                }
            }
        }, { passive: false });
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('approvalModal');
            if (event.target === modal) {
                closeModal();
            }
        }, { passive: false };
    </script>
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
