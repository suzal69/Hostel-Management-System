<?php
require_once '../includes/config.inc.php';
require_once 'admin_header.php';

// // Check if the user is logged in as an admin
// if (!isset($_SESSION['admin_id'])) {
//     header("Location: ../login.php");
//     exit();
// }

// Get all hostels for tabs
$hostels_query = "SELECT * FROM Hostel ORDER BY Hostel_name DESC";
$hostels_result = mysqli_query($conn, $hostels_query);
$hostels = mysqli_fetch_all($hostels_result, MYSQLI_ASSOC);

// Debug: Print hostels data
echo "<!-- Debug: Found " . count($hostels) . " hostels -->";
foreach ($hostels as $hostel) {
    
    echo "<!-- Debug: Hostel ID: " . $hostel['Hostel_id'] . ", Name: " . $hostel['Hostel_name'] . " -->";
}

// Fetch overall analytics data
try {
    // Overall Complaint Resolution Rate
    $sql = "SELECT COUNT(*) as total_tickets FROM complaints";
    $result = mysqli_query($conn, $sql);
    $total_tickets = mysqli_fetch_assoc($result)['total_tickets'];

    $sql = "SELECT COUNT(*) as resolved_tickets FROM complaints WHERE status = 'resolved'";
    $result = mysqli_query($conn, $sql);
    $resolved_tickets = mysqli_fetch_assoc($result)['resolved_tickets'];

    $overall_complaint_resolution_rate = ($total_tickets > 0) ? ($resolved_tickets / $total_tickets) * 100 : 0;

    // Overall Open Tickets by Category
    $sql = "SELECT category, COUNT(*) as count FROM complaints WHERE status = 'open' GROUP BY category";
    $result = mysqli_query($conn, $sql);
    $overall_open_tickets_by_category = mysqli_fetch_all($result, MYSQLI_ASSOC);

    // Overall Average Resolution Time
    $sql = "SELECT AVG(DATEDIFF(resolve_date, submission_date)) as avg_resolution_time FROM complaints WHERE status = 'resolved'";
    $result = mysqli_query($conn, $sql);
    $overall_avg_resolution_time = mysqli_fetch_assoc($result)['avg_resolution_time'];

    // Overall Hostel Occupancy Rate
    $sql = "SELECT SUM(bed_capacity) as total_beds, SUM(current_occupancy) as total_occupied FROM room";
    $result = mysqli_query($conn, $sql);
    $occupancy_data = mysqli_fetch_assoc($result);
    $total_beds = $occupancy_data['total_beds'] ?? 0;
    $total_occupied = $occupancy_data['total_occupied'] ?? 0;
    $total_empty = $total_beds - $total_occupied;

    $overall_hostel_occupancy_rate = ($total_beds > 0) ? ($total_occupied / $total_beds) * 100 : 0;

    // Overall New Admissions (This Month)
    $sql = "SELECT COUNT(*) as new_admissions FROM student WHERE MONTH(admission_date) = MONTH(CURRENT_DATE()) AND YEAR(admission_date) = YEAR(CURRENT_DATE())";
    $result = mysqli_query($conn, $sql);
    $overall_new_admissions = mysqli_fetch_assoc($result)['new_admissions'];

    // Overall Leave Analytics
    $sql = "SELECT COUNT(*) as total_applications, 
                   SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_applications,
                   SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_applications,
                   SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_applications,
                   SUM(leave_days) as total_leave_days,
                   SUM(estimated_reduction) as total_savings
            FROM leave_applications";
    $result = mysqli_query($conn, $sql);
    $overall_leave_data = mysqli_fetch_assoc($result);

    // Leave Applications by Month (Last 6 months)
    $sql = "SELECT DATE_FORMAT(applied_date, '%Y-%m') as month, COUNT(*) as applications
            FROM leave_applications 
            WHERE applied_date >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(applied_date, '%Y-%m')
            ORDER BY month";
    $result = mysqli_query($conn, $sql);
    $overall_leave_by_month = mysqli_fetch_all($result, MYSQLI_ASSOC);

    // Leave Status Distribution
    $sql = "SELECT status, COUNT(*) as count 
            FROM leave_applications 
            GROUP BY status";
    $result = mysqli_query($conn, $sql);
    $overall_leave_status = mysqli_fetch_all($result, MYSQLI_ASSOC);

    // Overall Payment Analytics
    $sql = "SELECT COUNT(*) as total_payments, 
                   SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_payments,
                   SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_payments,
                   SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_payments,
                   SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_amount_received,
                   SUM(amount) as total_amount_attempted
            FROM payments";
    $result = mysqli_query($conn, $sql);
    $overall_payment_data = mysqli_fetch_assoc($result);

    // Payments by Month (Last 6 months)
    $sql = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, 
                   COUNT(*) as payment_count,
                   SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as amount_received,
                   SUM(amount) as amount_attempted
            FROM payments 
            WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month";
    $result = mysqli_query($conn, $sql);
    $overall_payments_by_month = mysqli_fetch_all($result, MYSQLI_ASSOC);

    // Payment Status Distribution
    $sql = "SELECT status, COUNT(*) as count, SUM(amount) as total_amount
            FROM payments 
            GROUP BY status";
    $result = mysqli_query($conn, $sql);
    $overall_payment_status = mysqli_fetch_all($result, MYSQLI_ASSOC);

    // Payment Gateway Distribution
    $sql = "SELECT payment_gateway, COUNT(*) as count, SUM(amount) as total_amount
            FROM payments 
            WHERE payment_gateway IS NOT NULL AND payment_gateway != ''
            GROUP BY payment_gateway";
    $result = mysqli_query($conn, $sql);
    $overall_payment_gateways = mysqli_fetch_all($result, MYSQLI_ASSOC);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

// Fetch hostel-specific analytics data
$hostel_analytics = [];
foreach ($hostels as $hostel) {
    $hostel_id = $hostel['Hostel_id'];
    
    try {
        // Initialize default values
        $hostel_complaint_resolution_rate = 0;
        $hostel_open_tickets_by_category = [];
        $hostel_avg_resolution_time = 0;
        $hostel_occupancy_rate = 0;
        $hostel_new_admissions = 0;
        $hostel_total_rooms = 0;
        $hostel_occupied_rooms = 0;
        
        // Hostel-specific Complaint Resolution Rate
        $sql = "SELECT COUNT(*) as total_tickets FROM complaints WHERE hostel_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $hostel_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $hostel_total_tickets = mysqli_fetch_assoc($result)['total_tickets'];

        if ($hostel_total_tickets > 0) {
            $sql = "SELECT COUNT(*) as resolved_tickets FROM complaints WHERE status = 'resolved' AND hostel_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $hostel_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $hostel_resolved_tickets = mysqli_fetch_assoc($result)['resolved_tickets'];
            $hostel_complaint_resolution_rate = ($hostel_resolved_tickets / $hostel_total_tickets) * 100;
        }

        // Hostel-specific Open Tickets by Category
        $sql = "SELECT category, COUNT(*) as count FROM complaints WHERE status = 'open' AND hostel_id = ? GROUP BY category";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $hostel_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $hostel_open_tickets_by_category = mysqli_fetch_all($result, MYSQLI_ASSOC);

        // If no open tickets, provide empty data structure
        if (empty($hostel_open_tickets_by_category)) {
            $hostel_open_tickets_by_category = [['category' => 'No open tickets', 'count' => 0]];
        }

        // Hostel-specific Average Resolution Time
        $sql = "SELECT AVG(DATEDIFF(resolve_date, submission_date)) as avg_resolution_time FROM complaints WHERE status = 'resolved' AND hostel_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $hostel_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $hostel_avg_resolution_time_data = mysqli_fetch_assoc($result);
        $hostel_avg_resolution_time = $hostel_avg_resolution_time_data['avg_resolution_time'] ?? 0;

        // Hostel-specific Occupancy Rate
        $sql = "SELECT SUM(bed_capacity) as total_beds, SUM(current_occupancy) as total_occupied FROM room WHERE Hostel_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $hostel_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $hostel_occupancy_data = mysqli_fetch_assoc($result);
        $hostel_total_beds = $hostel_occupancy_data['total_beds'] ?? 0;
        $hostel_total_occupied = $hostel_occupancy_data['total_occupied'] ?? 0;
        $hostel_total_empty = $hostel_total_beds - $hostel_total_occupied;
        
        $hostel_occupancy_rate = ($hostel_total_beds > 0) ? ($hostel_total_occupied / $hostel_total_beds) * 100 : 0;

        // Hostel-specific New Admissions (This Month)
        $sql = "SELECT COUNT(*) as new_admissions FROM student s JOIN application a ON s.Student_id = a.Student_id WHERE MONTH(s.admission_date) = MONTH(CURRENT_DATE()) AND YEAR(s.admission_date) = YEAR(CURRENT_DATE()) AND a.Hostel_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $hostel_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $hostel_new_admissions_data = mysqli_fetch_assoc($result);
        $hostel_new_admissions = $hostel_new_admissions_data['new_admissions'] ?? 0;

        // Hostel-specific Leave Analytics
        $sql = "SELECT COUNT(*) as total_applications, 
                       SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_applications,
                       SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_applications,
                       SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_applications,
                       SUM(leave_days) as total_leave_days,
                       SUM(estimated_reduction) as total_savings
                FROM leave_applications la
                JOIN Student s ON la.student_id = s.Student_id
                WHERE s.Hostel_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $hostel_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $hostel_leave_data = mysqli_fetch_assoc($result);

        // Leave Applications by Month for this hostel (Last 6 months)
        $sql = "SELECT DATE_FORMAT(la.applied_date, '%Y-%m') as month, COUNT(*) as applications
                FROM leave_applications la
                JOIN Student s ON la.student_id = s.Student_id
                WHERE s.Hostel_id = ? 
                AND la.applied_date >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
                GROUP BY DATE_FORMAT(la.applied_date, '%Y-%m')
                ORDER BY month";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $hostel_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $hostel_leave_by_month = mysqli_fetch_all($result, MYSQLI_ASSOC);

        // Leave Status Distribution for this hostel
        $sql = "SELECT la.status, COUNT(*) as count 
                FROM leave_applications la
                JOIN Student s ON la.student_id = s.Student_id
                WHERE s.Hostel_id = ?
                GROUP BY la.status";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $hostel_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $hostel_leave_status = mysqli_fetch_all($result, MYSQLI_ASSOC);

        // Hostel-specific Payment Analytics
        $sql = "SELECT COUNT(*) as total_payments, 
                       SUM(CASE WHEN p.status = 'completed' THEN 1 ELSE 0 END) as completed_payments,
                       SUM(CASE WHEN p.status = 'pending' THEN 1 ELSE 0 END) as pending_payments,
                       SUM(CASE WHEN p.status = 'failed' THEN 1 ELSE 0 END) as failed_payments,
                       SUM(CASE WHEN p.status = 'completed' THEN p.amount ELSE 0 END) as total_amount_received,
                       SUM(p.amount) as total_amount_attempted
                FROM payments p
                JOIN Student s ON p.student_id = s.Student_id
                WHERE s.Hostel_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $hostel_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $hostel_payment_data = mysqli_fetch_assoc($result);

        // Payments by Month for this hostel (Last 6 months)
        $sql = "SELECT DATE_FORMAT(p.created_at, '%Y-%m') as month, 
                       COUNT(*) as payment_count,
                       SUM(CASE WHEN p.status = 'completed' THEN p.amount ELSE 0 END) as amount_received,
                       SUM(p.amount) as amount_attempted
                FROM payments p
                JOIN Student s ON p.student_id = s.Student_id
                WHERE s.Hostel_id = ? 
                AND p.created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
                GROUP BY DATE_FORMAT(p.created_at, '%Y-%m')
                ORDER BY month";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $hostel_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $hostel_payments_by_month = mysqli_fetch_all($result, MYSQLI_ASSOC);

        // Payment Status Distribution for this hostel
        $sql = "SELECT p.status, COUNT(*) as count, SUM(p.amount) as total_amount
                FROM payments p
                JOIN Student s ON p.student_id = s.Student_id
                WHERE s.Hostel_id = ?
                GROUP BY p.status";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $hostel_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $hostel_payment_status = mysqli_fetch_all($result, MYSQLI_ASSOC);

        // Payment Gateway Distribution for this hostel
        $sql = "SELECT p.payment_gateway, COUNT(*) as count, SUM(p.amount) as total_amount
                FROM payments p
                JOIN Student s ON p.student_id = s.Student_id
                WHERE s.Hostel_id = ? 
                AND p.payment_gateway IS NOT NULL AND p.payment_gateway != ''
                GROUP BY p.payment_gateway";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $hostel_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $hostel_payment_gateways = mysqli_fetch_all($result, MYSQLI_ASSOC);

        $hostel_analytics[$hostel_id] = [
            'complaint_resolution_rate' => $hostel_complaint_resolution_rate,
            'open_tickets_by_category' => $hostel_open_tickets_by_category,
            'avg_resolution_time' => $hostel_avg_resolution_time,
            'occupancy_rate' => $hostel_occupancy_rate,
            'total_beds' => $hostel_total_beds,
            'total_occupied' => $hostel_total_occupied,
            'total_empty' => $hostel_total_empty,
            'new_admissions' => $hostel_new_admissions,
            'leave_data' => $hostel_leave_data,
            'leave_by_month' => $hostel_leave_by_month,
            'leave_status' => $hostel_leave_status,
            'payment_data' => $hostel_payment_data,
            'payments_by_month' => $hostel_payments_by_month,
            'payment_status' => $hostel_payment_status,
            'payment_gateways' => $hostel_payment_gateways
        ];
        
        // Debug: Print analytics data for this hostel
        echo "<!-- Debug: Hostel {$hostel['Hostel_name']} Analytics: " . json_encode($hostel_analytics[$hostel_id]) . " -->";

    } catch (Exception $e) {
        // Set default values on error
        $hostel_analytics[$hostel_id] = [
            'complaint_resolution_rate' => 0,
            'open_tickets_by_category' => [['category' => 'Error loading data', 'count' => 0]],
            'avg_resolution_time' => 0,
            'occupancy_rate' => 0,
            'new_admissions' => 0,
            'total_rooms' => 0,
            'occupied_rooms' => 0,
            'leave_data' => [
                'total_applications' => 0,
                'pending_applications' => 0,
                'approved_applications' => 0,
                'rejected_applications' => 0,
                'total_leave_days' => 0,
                'total_savings' => 0
            ],
            'leave_by_month' => [],
            'leave_status' => [],
            'payment_data' => [
                'total_payments' => 0,
                'completed_payments' => 0,
                'pending_payments' => 0,
                'failed_payments' => 0,
                'total_amount_received' => 0,
                'total_amount_attempted' => 0
            ],
            'payments_by_month' => [],
            'payment_status' => [],
            'payment_gateways' => []
        ];
        echo "Error for hostel {$hostel['Hostel_name']}: " . $e->getMessage();
    }
}

?>
<br><br><br>

<div class="container">
    <!-- Header -->
    <div class="header">
        <h1><i class="fa fa-chart-line"></i> Admin Analytics</h1>
        <p>View comprehensive analytics and statistics for all hostels</p>
    </div>

    <!-- Analytics Section -->
    <div class="section">
        <div class="section-header">
            <h3><i class="fa fa-tachometer"></i> System Overview</h3>
        </div>
        
        <!-- Custom Tab Navigation -->
        <div class="tab-navigation">
            <div class="tab-item active" onclick="showTab('overall')">
                <i class="fa fa-globe"></i> Overall
            </div>
            <?php foreach ($hostels as $hostel): ?>
                <?php if($hostel['Hostel_name'] == 'admin') continue; ?>
                <div class="tab-item" onclick="showTab('hostel-<?php echo $hostel['Hostel_id']; ?>')">
                    <i class="fa fa-building"></i> <?php echo htmlspecialchars($hostel['Hostel_name']); ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Tab Content -->
        <div class="tab-content">
            <!-- Overall Tab -->
            <div class="tab-pane active" id="overall">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo round($overall_complaint_resolution_rate, 2); ?>%</div>
                        <div class="stat-label">Complaint Resolution Rate</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo round($overall_avg_resolution_time, 2); ?></div>
                        <div class="stat-label">Average Resolution Time (Days)</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $overall_new_admissions; ?></div>
                        <div class="stat-label">New Admissions (This Month)</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $overall_leave_data['total_applications'] ?? 0; ?></div>
                        <div class="stat-label">Total Leave Applications</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">Rs<?php echo number_format($overall_leave_data['total_savings'] ?? 0, 0); ?></div>
                        <div class="stat-label">Total Student Savings</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $overall_leave_data['approved_applications'] ?? 0; ?></div>
                        <div class="stat-label">Approved Leaves</div>
                    </div>
                    <!-- Payment Statistics Cards -->
                    <div class="stat-card">
                        <div class="stat-number">Rs<?php echo number_format($overall_payment_data['total_amount_received'] ?? 0, 0); ?></div>
                        <div class="stat-label">Total Payments Received</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $overall_payment_data['completed_payments'] ?? 0; ?></div>
                        <div class="stat-label">Completed Payments</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $overall_payment_data['pending_payments'] ?? 0; ?></div>
                        <div class="stat-label">Pending Payments</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $overall_payment_data['total_payments'] ?? 0; ?></div>
                        <div class="stat-label">Total Payment Attempts</div>
                    </div>
                </div>
                
                <div class="info-grid">
                    <div class="info-card">
                        <div class="info-section">
                            <h4><i class="fa fa-ticket"></i> Open Tickets by Category</h4>
                            <div style="height: 300px; position: relative;">
                                <canvas id="overallOpenTicketsChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="info-card">
                        <div class="info-section">
                            <h4><i class="fa fa-bed"></i> Hostel Occupancy Rate</h4>
                            <div style="height: 300px; position: relative;">
                                <canvas id="overallOccupancyChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="info-card">
                        <div class="info-section">
                            <h4><i class="fa fa-calendar"></i> Leave Applications by Month</h4>
                            <div style="height: 300px; position: relative;">
                                <canvas id="overallLeaveByMonthChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="info-card">
                        <div class="info-section">
                            <h4><i class="fa fa-chart-pie"></i> Leave Status Distribution</h4>
                            <div style="height: 300px; position: relative;">
                                <canvas id="overallLeaveStatusChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <!-- Payment Charts -->
                    <div class="info-card">
                        <div class="info-section">
                            <h4><i class="fa fa-credit-card"></i> Payments by Month</h4>
                            <div style="height: 300px; position: relative;">
                                <canvas id="overallPaymentsByMonthChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="info-card">
                        <div class="info-section">
                            <h4><i class="fa fa-money"></i> Payment Status Distribution</h4>
                            <div style="height: 300px; position: relative;">
                                <canvas id="overallPaymentStatusChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="info-card">
                        <div class="info-section">
                            <h4><i class="fa fa-credit-card"></i> Payment Gateway Usage</h4>
                            <div style="height: 300px; position: relative;">
                                <canvas id="overallPaymentGatewayChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hostel-specific Tabs -->
            <?php foreach ($hostels as $hostel): ?>
                <?php if($hostel['Hostel_name'] == 'admin') continue; ?>
                <?php $analytics = $hostel_analytics[$hostel['Hostel_id']]; ?>
                <div class="tab-pane" id="hostel-<?php echo $hostel['Hostel_id']; ?>">
                    <div class="section-header">
                        <h3><i class="fa fa-building"></i> <?php echo htmlspecialchars($hostel['Hostel_name']); ?> Analytics</h3>
                    </div>
                    
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-number"><?php echo round($analytics['complaint_resolution_rate'], 2); ?>%</div>
                            <div class="stat-label">Complaint Resolution Rate</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo round($analytics['avg_resolution_time'], 2); ?></div>
                            <div class="stat-label">Average Resolution Time (Days)</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $analytics['new_admissions']; ?></div>
                            <div class="stat-label">New Admissions (This Month)</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $analytics['leave_data']['total_applications'] ?? 0; ?></div>
                            <div class="stat-label">Total Leave Applications</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">Rs<?php echo number_format($analytics['leave_data']['total_savings'] ?? 0, 0); ?></div>
                            <div class="stat-label">Total Student Savings</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $analytics['leave_data']['approved_applications'] ?? 0; ?></div>
                            <div class="stat-label">Approved Leaves</div>
                        </div>
                        <!-- Hostel Payment Statistics Cards -->
                        <div class="stat-card">
                            <div class="stat-number">Rs<?php echo number_format($analytics['payment_data']['total_amount_received'] ?? 0, 0); ?></div>
                            <div class="stat-label">Total Payments Received</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $analytics['payment_data']['completed_payments'] ?? 0; ?></div>
                            <div class="stat-label">Completed Payments</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $analytics['payment_data']['pending_payments'] ?? 0; ?></div>
                            <div class="stat-label">Pending Payments</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $analytics['payment_data']['total_payments'] ?? 0; ?></div>
                            <div class="stat-label">Total Payment Attempts</div>
                        </div>
                    </div>
                    
                    <div class="info-grid">
                        <div class="info-card">
                            <div class="info-section">
                                <h4><i class="fa fa-ticket"></i> Open Tickets by Category</h4>
                                <div style="height: 300px; position: relative;">
                                    <canvas id="hostel<?php echo $hostel['Hostel_id']; ?>OpenTicketsChart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="info-card">
                            <div class="info-section">
                                <h4><i class="fa fa-bed"></i> Hostel Occupancy Rate</h4>
                                <div style="height: 300px; position: relative;">
                                    <canvas id="hostel<?php echo $hostel['Hostel_id']; ?>OccupancyChart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="info-card">
                            <div class="info-section">
                                <h4><i class="fa fa-calendar"></i> Leave Applications by Month</h4>
                                <div style="height: 300px; position: relative;">
                                    <canvas id="hostel<?php echo $hostel['Hostel_id']; ?>LeaveByMonthChart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="info-card">
                            <div class="info-section">
                                <h4><i class="fa fa-chart-pie"></i> Leave Status Distribution</h4>
                                <div style="height: 300px; position: relative;">
                                    <canvas id="hostel<?php echo $hostel['Hostel_id']; ?>LeaveStatusChart"></canvas>
                                </div>
                            </div>
                        </div>
                        <!-- Hostel Payment Charts -->
                        <div class="info-card">
                            <div class="info-section">
                                <h4><i class="fa fa-credit-card"></i> Payments by Month</h4>
                                <div style="height: 300px; position: relative;">
                                    <canvas id="hostel<?php echo $hostel['Hostel_id']; ?>PaymentsByMonthChart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="info-card">
                            <div class="info-section">
                                <h4><i class="fa fa-money"></i> Payment Status Distribution</h4>
                                <div style="height: 300px; position: relative;">
                                    <canvas id="hostel<?php echo $hostel['Hostel_id']; ?>PaymentStatusChart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="info-card">
                            <div class="info-section">
                                <h4><i class="fa fa-credit-card"></i> Payment Gateway Usage</h4>
                                <div style="height: 300px; position: relative;">
                                    <canvas id="hostel<?php echo $hostel['Hostel_id']; ?>PaymentGatewayChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
    // Cache busting - force reload
    console.log('Loading admin_analytics.php v<?php echo time(); ?>');
    
function showTab(tabId) {
    // Hide all tab panes
    const tabPanes = document.querySelectorAll('.tab-pane');
    tabPanes.forEach(pane => pane.classList.remove('active'));
    
    // Remove active class from all tab items
    const tabItems = document.querySelectorAll('.tab-item');
    tabItems.forEach(item => item.classList.remove('active'));
    
    // Show selected tab pane
    const selectedPane = document.getElementById(tabId);
    if (selectedPane) {
        selectedPane.classList.add('active');
    }
    
    // Add active class to clicked tab item
    event.target.closest('.tab-item').classList.add('active');
    
    // Initialize hostel charts if switching to a hostel tab
    if (tabId !== 'overall') {
        // Extract hostel ID from tab ID
        const hostelId = tabId.replace('hostel-', '');
        console.log('Debug: Switching to hostel tab, ID:', hostelId);
        
        // Call the specific chart initialization function for this hostel
        setTimeout(function() {
            const functionName = 'initHostel' + hostelId + 'Charts';
            console.log('Debug: Calling function:', functionName);
            
            if (typeof window[functionName] === 'function') {
                window[functionName]();
            } else {
                console.log('Debug: Function not found:', functionName);
            }
        }, 100);
    }
}
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Overall Charts
    var overallOpenTicketsCtx = document.getElementById('overallOpenTicketsChart').getContext('2d');
    var overallOpenTicketsChart = new Chart(overallOpenTicketsCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($overall_open_tickets_by_category, 'category')); ?>,
            datasets: [{
                label: 'Number of Open Tickets',
                data: <?php echo json_encode(array_column($overall_open_tickets_by_category, 'count')); ?>,
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                borderColor: 'rgba(255, 99, 132, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    var overallOccupancyCtx = document.getElementById('overallOccupancyChart').getContext('2d');
    var overallOccupancyChart = new Chart(overallOccupancyCtx, {
        type: 'doughnut',
        data: {
            labels: ['Occupied', 'Empty'],
            datasets: [{
                label: 'Hostel Occupancy',
                data: [<?php echo $total_occupied; ?>, <?php echo $total_empty; ?>],
                backgroundColor: [
                    'rgba(75, 192, 192, 0.2)',
                    'rgba(255, 206, 86, 0.2)'
                ],
                borderColor: [
                    'rgba(75, 192, 192, 1)',
                    'rgba(255, 206, 86, 1)'
                ],
                borderWidth: 1
            }]
        }
    });

    // Overall Leave Applications by Month Chart
    var overallLeaveByMonthCtx = document.getElementById('overallLeaveByMonthChart').getContext('2d');
    var overallLeaveByMonthChart = new Chart(overallLeaveByMonthCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($overall_leave_by_month, 'month')); ?>,
            datasets: [{
                label: 'Leave Applications',
                data: <?php echo json_encode(array_column($overall_leave_by_month, 'applications')); ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Overall Leave Status Distribution Chart
    var overallLeaveStatusCtx = document.getElementById('overallLeaveStatusChart').getContext('2d');
    var overallLeaveStatusChart = new Chart(overallLeaveStatusCtx, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode(array_column($overall_leave_status, 'status')); ?>,
            datasets: [{
                label: 'Leave Status',
                data: <?php echo json_encode(array_column($overall_leave_status, 'count')); ?>,
                backgroundColor: [
                    'rgba(255, 206, 86, 0.2)', // Pending - Yellow
                    'rgba(75, 192, 192, 0.2)', // Approved - Green  
                    'rgba(255, 99, 132, 0.2)'  // Rejected - Red
                ],
                borderColor: [
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(255, 99, 132, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Overall Payments by Month Chart
    var overallPaymentsByMonthCtx = document.getElementById('overallPaymentsByMonthChart').getContext('2d');
    var overallPaymentsByMonthChart = new Chart(overallPaymentsByMonthCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($overall_payments_by_month, 'month')); ?>,
            datasets: [{
                label: 'Amount Received',
                data: <?php echo json_encode(array_column($overall_payments_by_month, 'amount_received')); ?>,
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }, {
                label: 'Payment Count',
                data: <?php echo json_encode(array_column($overall_payments_by_month, 'payment_count')); ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    beginAtZero: true
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    beginAtZero: true,
                    grid: {
                        drawOnChartArea: false
                    }
                }
            }
        }
    });

    // Overall Payment Status Distribution Chart
    var overallPaymentStatusCtx = document.getElementById('overallPaymentStatusChart').getContext('2d');
    var overallPaymentStatusChart = new Chart(overallPaymentStatusCtx, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode(array_column($overall_payment_status, 'status')); ?>,
            datasets: [{
                label: 'Payment Status',
                data: <?php echo json_encode(array_column($overall_payment_status, 'count')); ?>,
                backgroundColor: [
                    'rgba(75, 192, 192, 0.2)', // Completed - Green
                    'rgba(255, 206, 86, 0.2)', // Pending - Yellow
                    'rgba(255, 99, 132, 0.2)'  // Failed - Red
                ],
                borderColor: [
                    'rgba(75, 192, 192, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(255, 99, 132, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Overall Payment Gateway Distribution Chart
    var overallPaymentGatewayCtx = document.getElementById('overallPaymentGatewayChart').getContext('2d');
    var overallPaymentGatewayChart = new Chart(overallPaymentGatewayCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_column($overall_payment_gateways, 'payment_gateway')); ?>,
            datasets: [{
                label: 'Payment Gateway Usage',
                data: <?php echo json_encode(array_column($overall_payment_gateways, 'count')); ?>,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.2)',
                    'rgba(54, 162, 235, 0.2)',
                    'rgba(255, 206, 86, 0.2)',
                    'rgba(75, 192, 192, 0.2)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Hostel-specific Charts - Simple Static Version
    console.log('Debug: Creating static hostel chart functions');
    
    // Hostel 1 (UNO)
    window['initHostel1Charts'] = function() {
        console.log('Debug: Initializing charts for hostel 1');
        createHostelCharts('1');
    };
    
    // Hostel 2 (DOS)  
    window['initHostel2Charts'] = function() {
        console.log('Debug: Initializing charts for hostel 2');
        createHostelCharts('2');
    };
    
    // Hostel 3 (TRES)
    window['initHostel3Charts'] = function() {
        console.log('Debug: Initializing charts for hostel 3');
        createHostelCharts('3');
    };
    
    // Universal function to create charts for any hostel
    function createHostelCharts(hostelId) {
        // Get the analytics data for this hostel
        const hostelAnalytics = <?php echo json_encode($hostel_analytics); ?>;
        const analytics = hostelAnalytics[hostelId];
        
        // Open Tickets by Category
        var ctx = document.getElementById('hostel' + hostelId + 'OpenTicketsChart');
        if (ctx) {
            // Destroy existing chart if it exists
            var existingChart = Chart.getChart(ctx);
            if (existingChart) {
                existingChart.destroy();
            }
            
            const ticketData = analytics.open_tickets_by_category || [];
            new Chart(ctx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: ticketData.map(item => item.category),
                    datasets: [{
                        label: 'Number of Open Tickets',
                        data: ticketData.map(item => item.count),
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
            console.log('Debug: Chart created for hostel ' + hostelId + ' open tickets');
        }

        // Occupancy Rate
        var ctx = document.getElementById('hostel' + hostelId + 'OccupancyChart');
        if (ctx) {
            // Destroy existing chart if it exists
            var existingChart = Chart.getChart(ctx);
            if (existingChart) {
                existingChart.destroy();
            }
            
            new Chart(ctx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['Occupied', 'Empty'],
                    datasets: [{
                        label: 'Hostel Occupancy',
                        data: [analytics.total_occupied || 0, analytics.total_empty || 0],
                        backgroundColor: [
                            'rgba(75, 192, 192, 0.2)',
                            'rgba(255, 206, 86, 0.2)'
                        ],
                        borderColor: [
                            'rgba(75, 192, 192, 1)',
                            'rgba(255, 206, 86, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
            console.log('Debug: Chart created for hostel ' + hostelId + ' occupancy');
        }

        // Leave Applications by Month
        var ctx = document.getElementById('hostel' + hostelId + 'LeaveByMonthChart');
        if (ctx) {
            // Destroy existing chart if it exists
            var existingChart = Chart.getChart(ctx);
            if (existingChart) {
                existingChart.destroy();
            }
            
            const leaveData = analytics.leave_by_month || [];
            new Chart(ctx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: leaveData.map(item => item.month),
                    datasets: [{
                        label: 'Leave Applications',
                        data: leaveData.map(item => item.applications),
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
            console.log('Debug: Chart created for hostel ' + hostelId + ' leave by month');
        }

        // Leave Status Distribution
        var ctx = document.getElementById('hostel' + hostelId + 'LeaveStatusChart');
        if (ctx) {
            // Destroy existing chart if it exists
            var existingChart = Chart.getChart(ctx);
            if (existingChart) {
                existingChart.destroy();
            }
            
            const leaveStatusData = analytics.leave_status || [];
            new Chart(ctx.getContext('2d'), {
                type: 'pie',
                data: {
                    labels: leaveStatusData.map(item => item.status),
                    datasets: [{
                        label: 'Leave Status',
                        data: leaveStatusData.map(item => item.count),
                        backgroundColor: [
                            'rgba(255, 206, 86, 0.2)', // Pending - Yellow
                            'rgba(75, 192, 192, 0.2)', // Approved - Green  
                            'rgba(255, 99, 132, 0.2)'  // Rejected - Red
                        ],
                        borderColor: [
                            'rgba(255, 206, 86, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(255, 99, 132, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
            console.log('Debug: Chart created for hostel ' + hostelId + ' leave status');
        }

        // Payments by Month
        var ctx = document.getElementById('hostel' + hostelId + 'PaymentsByMonthChart');
        if (ctx) {
            // Destroy existing chart if it exists
            var existingChart = Chart.getChart(ctx);
            if (existingChart) {
                existingChart.destroy();
            }
            
            const paymentData = analytics.payments_by_month || [];
            new Chart(ctx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: paymentData.map(item => item.month),
                    datasets: [{
                        label: 'Amount Received',
                        data: paymentData.map(item => item.amount_received),
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
            console.log('Debug: Chart created for hostel ' + hostelId + ' payments by month');
        }

        // Payment Status Distribution
        var ctx = document.getElementById('hostel' + hostelId + 'PaymentStatusChart');
        if (ctx) {
            // Destroy existing chart if it exists
            var existingChart = Chart.getChart(ctx);
            if (existingChart) {
                existingChart.destroy();
            }
            
            const paymentStatusData = analytics.payment_status || [];
            new Chart(ctx.getContext('2d'), {
                type: 'pie',
                data: {
                    labels: paymentStatusData.map(item => item.status),
                    datasets: [{
                        label: 'Payment Status',
                        data: paymentStatusData.map(item => item.count),
                        backgroundColor: [
                            'rgba(75, 192, 192, 0.2)', // Completed - Green
                            'rgba(255, 206, 86, 0.2)', // Pending - Yellow
                            'rgba(255, 99, 132, 0.2)'  // Failed - Red
                        ],
                        borderColor: [
                            'rgba(75, 192, 192, 1)',
                            'rgba(255, 206, 86, 1)',
                            'rgba(255, 99, 132, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
            console.log('Debug: Chart created for hostel ' + hostelId + ' payment status');
        }

        // Payment Gateway Distribution
        var ctx = document.getElementById('hostel' + hostelId + 'PaymentGatewayChart');
        if (ctx) {
            // Destroy existing chart if it exists
            var existingChart = Chart.getChart(ctx);
            if (existingChart) {
                existingChart.destroy();
            }
            
            const gatewayData = analytics.payment_gateways || [];
            new Chart(ctx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: gatewayData.map(item => item.payment_gateway || 'Unknown'),
                    datasets: [{
                        label: 'Payment Gateway Usage',
                        data: gatewayData.map(item => item.count),
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.2)',
                            'rgba(54, 162, 235, 0.2)',
                            'rgba(255, 206, 86, 0.2)',
                            'rgba(75, 192, 192, 0.2)'
                        ],
                        borderColor: [
                            'rgba(255, 99, 132, 1)',
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 206, 86, 1)',
                            'rgba(75, 192, 192, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
            console.log('Debug: Chart created for hostel ' + hostelId + ' payment gateway');
        }
    }
    
    console.log('Debug: All hostel chart functions initialized');
    
    // Test if functions exist
    console.log('Testing function availability:');
    console.log('initHostel1Charts exists:', typeof window.initHostel1Charts);
    console.log('initHostel2Charts exists:', typeof window.initHostel2Charts);
    console.log('initHostel3Charts exists:', typeof window.initHostel3Charts);
</script>

<?php
require_once 'admin_footer.php';
?>