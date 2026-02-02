<?php
require 'includes/config.inc.php';
require_once 'includes/price_calculator.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if manager is logged in - try different possible session variable names
$managerId = $_SESSION['hostel_manager_id'] ?? $_SESSION['manager_id'] ?? $_SESSION['admin_id'] ?? null;
$username = $_SESSION['username'] ?? $_SESSION['manager_username'] ?? null;

if (!$managerId && !$username) {
    header("Location: login-hostel_manager.php");
    exit();
}

// Get manager's hostel ID
$hostelId = $_SESSION['hostel_id'] ?? null;
if (!$hostelId) {
    echo "<script>alert('Manager hostel not assigned!'); window.location.href='manager_profile.php';</script>";
    exit();
}

require_once 'includes/manager_header.php';

// Handle room selection
$selectedRoomId = $_GET['room_id'] ?? null;
$roomDetails = null;
$bedAllocations = [];

if ($selectedRoomId) {
    // Get specific room details (without start/end dates)
    $sql_room = "SELECT r.*, h.Hostel_name 
                 FROM Room r 
                 JOIN Hostel h ON r.Hostel_id = h.Hostel_id
                 WHERE r.Room_id = ? AND r.Hostel_id = ?";
    $stmt_room = mysqli_prepare($conn, $sql_room);
    if (mysqli_stmt_prepare($stmt_room, $sql_room)) {
        mysqli_stmt_bind_param($stmt_room, "ii", $selectedRoomId, $hostelId);
        mysqli_stmt_execute($stmt_room);
        $result_room = mysqli_stmt_get_result($stmt_room);
        if ($row_room = mysqli_fetch_assoc($result_room)) {
            $roomDetails = $row_room;
        }
        mysqli_stmt_close($stmt_room);
    }
    
    // Get bed allocations for this room
    if ($roomDetails) {
        $sql_beds = "SELECT ba.bed_number, ba.allocation_price, ba.start_date, ba.end_date, ba.include_food, ba.food_plan,
                            s.Student_id, s.Fname, s.Lname, s.Mob_no, s.Dept, s.Year_of_study
                     FROM bed_allocation ba
                     JOIN student s ON ba.student_id = s.Student_id
                     WHERE ba.room_id = ? AND ba.is_active = 1
                     ORDER BY ba.bed_number";
        $stmt_beds = mysqli_prepare($conn, $sql_beds);
        if (mysqli_stmt_prepare($stmt_beds, $sql_beds)) {
            mysqli_stmt_bind_param($stmt_beds, "i", $selectedRoomId);
            mysqli_stmt_execute($stmt_beds);
            $result_beds = mysqli_stmt_get_result($stmt_beds);
            while ($row_bed = mysqli_fetch_assoc($result_beds)) {
                $bedAllocations[] = $row_bed;
            }
            mysqli_stmt_close($stmt_beds);
        }
    }
}

// Get all rooms in manager's hostel
$rooms_query = "SELECT r.*, 
                (SELECT COUNT(*) FROM bed_allocation ba WHERE ba.room_id = r.Room_id AND ba.is_active = 1) as current_occupancy
                FROM Room r 
                WHERE r.Hostel_id = ? 
                ORDER BY r.Room_No";
$stmt_rooms = mysqli_prepare($conn, $rooms_query);
if (mysqli_stmt_prepare($stmt_rooms, $rooms_query)) {
    mysqli_stmt_bind_param($stmt_rooms, "i", $hostelId);
    mysqli_stmt_execute($stmt_rooms);
    $rooms_result = mysqli_stmt_get_result($stmt_rooms);
} else {
    $rooms_result = null;
}

// Optional student payment history when a student_id is provided for a selected room
$viewStudentId = $_GET['student_id'] ?? null;
$paymentHistory = [];
$paymentSummary = [
    'total_transactions' => 0,
    'total_paid' => 0,
    'pending_amount' => 0,
    'completed_payments' => 0,
    'success_rate' => 0
];
$selectedStudentInfo = null;

if ($selectedRoomId && $viewStudentId && !empty($bedAllocations)) {
    foreach ($bedAllocations as $alloc) {
        if ($alloc['Student_id'] == $viewStudentId) {
            $selectedStudentInfo = $alloc;
            break;
        }
    }
    if ($selectedStudentInfo) {
        $paymentQuery = "SELECT * FROM payments WHERE student_id = ? ORDER BY created_at DESC";
        $stmt = $conn->prepare($paymentQuery);
        if ($stmt) {
            $stmt->bind_param("s", $viewStudentId);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $paymentHistory[] = $row;
            }
            $stmt->close();
        }
        $paymentSummary['total_transactions'] = count($paymentHistory);
        foreach ($paymentHistory as $p) {
            if (($p['status'] ?? '') === 'completed') {
                $paymentSummary['total_paid'] += (float)$p['amount'];
                $paymentSummary['completed_payments']++;
            } elseif (($p['status'] ?? '') === 'pending') {
                $paymentSummary['pending_amount'] += (float)$p['amount'];
            }
        }
        $paymentSummary['success_rate'] = $paymentSummary['total_transactions'] > 0
            ? round(($paymentSummary['completed_payments'] / $paymentSummary['total_transactions']) * 100, 1)
            : 0;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>PLYS | Room Details</title>
    
    <!-- Meta tag Keywords -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <meta name="keywords" content="PLYS Responsive web template, Bootstrap Web Templates, Flat Web Templates, Android Compatible web template, 
    Smartphone Compatible web template, free webdesigns for Nokia, Samsung, LG, SonyEricsson, Motorola web design" />
    <script type="application/x-javascript">
        addEventListener("load", function () {
            setTimeout(hideURLbar, 0);
        }, false);

        function hideURLbar() {
            window.scrollTo(0, 1);
        }
    </script>
    <!--// Meta tag Keywords -->
 
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
    
    /* Room details specific styles */
    .room-selector {
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        padding: 30px;
        margin-bottom: 40px;
        border: none;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .room-selector:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 40px rgba(0,0,0,0.15);
    }
    .room-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    .room-card {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        border: 2px solid transparent;
        transition: all 0.3s ease;
        cursor: pointer;
        position: relative;
        overflow: hidden;
    }
    .room-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #003366, #ffcc00);
    }
    .room-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,51,102,0.15);
        border-color: #003366;
    }
    .room-card.selected {
        background: linear-gradient(135deg, #e8f0ff 0%, #d0e4ff 100%);
        border-color: #003366;
        transform: scale(1.05);
    }
    .room-number {
        font-size: 24px;
        font-weight: 700;
        color: #003366;
        margin-bottom: 10px;
    }
    .room-capacity {
        font-size: 14px;
        color: #666;
        margin-bottom: 8px;
    }
    .room-occupancy {
        font-size: 16px;
        font-weight: 600;
        margin-bottom: 15px;
    }
    .occupancy-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        display: inline-block;
    }
    .occupancy-full { 
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
    }
    .occupancy-partial { 
        background: linear-gradient(135deg, #ffc107 0%, #ffdb4d 100%);
        color: #212529;
    }
    .occupancy-empty { 
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
    }
    .room-details {
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        padding: 40px;
        margin-bottom: 40px;
        border: none;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .room-details:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 40px rgba(0,0,0,0.15);
    }
    .room-header {
        border-bottom: 3px solid #003366;
        padding-bottom: 20px;
        margin-bottom: 30px;
        text-align: center;
    }
    .room-header h2 {
        color: #003366;
        font-weight: 600;
        margin: 0;
    }
    .room-info {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 25px;
        margin-bottom: 40px;
    }
    .info-item {
        padding: 20px;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 12px;
        border-left: 5px solid #003366;
        transition: all 0.3s ease;
    }
    .info-item:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,51,102,0.1);
    }
    .info-label {
        font-weight: 600;
        color: #003366;
        margin-bottom: 8px;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .info-value {
        color: #333;
        font-size: 18px;
        font-weight: 500;
    }
    .bed-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
        margin-top: 30px;
    }
    .bed-card {
        background: white;
        border: 3px solid #e9ecef;
        border-radius: 15px;
        padding: 25px;
        text-align: center;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    .bed-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 5px;
        background: linear-gradient(90deg, #003366, #ffcc00);
    }
    .bed-card.occupied {
        border-color: #28a745;
        background: linear-gradient(135deg, #f8fff8 0%, #e8f8e8 100%);
    }
    .bed-card.occupied::before {
        background: #28a745;
    }
    .bed-card.empty {
        border-color: #6c757d;
        background: #f8f9fa;
        opacity: 0.8;
    }
    .bed-card.empty::before {
        background: #6c757d;
    }
    .bed-number {
        font-size: 20px;
        font-weight: 700;
        color: #003366;
        margin-bottom: 15px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .bed-status {
        font-size: 14px;
    }
    .student-info {
        margin-top: 15px;
    }
    .student-name {
        font-weight: 600;
        color: #333;
        margin-bottom: 8px;
        font-size: 16px;
    }
    .student-details {
        font-size: 13px;
        color: #666;
        line-height: 1.6;
    }
    .empty-bed {
        color: #6c757d;
        font-style: italic;
    }
    .no-rooms {
        text-align: center;
        padding: 60px;
        color: #6c757d;
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    .no-rooms h3 {
        color: #003366;
        margin-bottom: 20px;
    }
    .back-link {
        display: inline-block;
        margin-top: 30px;
        padding: 15px 30px;
        background: linear-gradient(135deg, #003366 0%, #004080 100%);
        color: white;
        text-decoration: none;
        border-radius: 30px;
        font-weight: 600;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .back-link:hover {
        background: linear-gradient(135deg, #ffcc00 0%, #ffdb4d 100%);
        color: #003366;
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(255,204,0,0.3);
    }
    .section-title {
        color: #003366;
        font-weight: 600;
        margin-bottom: 25px;
        text-align: center;
        font-size: 1.8rem;
    }
    @media (max-width: 768px) {
        .room-info {
            grid-template-columns: 1fr;
        }
        .bed-grid {
            grid-template-columns: 1fr;
        }
        .room-grid {
            grid-template-columns: 1fr;
        }
        .page-title {
            font-size: 2rem;
        }
    }
</style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-bed"></i> Room Details Management</h1>
            <p>View detailed information about rooms and bed allocations</p>
        </div>

        <!-- Room Selection Section -->
        <div class="section">
            <div class="section-header">
                <h3><i class="fas fa-door-open"></i> Select a Room to View Details</h3>
            </div>
            
            <?php if ($rooms_result && mysqli_num_rows($rooms_result) > 0): ?>
                <div class="room-grid">
                    <?php while ($room = mysqli_fetch_assoc($rooms_result)): ?>
                        <div class="room-card <?php echo ($selectedRoomId == $room['Room_id']) ? 'selected' : ''; ?>" 
                             onclick="selectRoom(<?php echo $room['Room_id']; ?>)">
                            <div class="room-number">Room <?php echo $room['Room_No']; ?></div>
                            <div class="room-capacity"><?php echo $room['bed_capacity']; ?>-Bed Room</div>
                            <div class="room-occupancy">
                                <?php echo $room['current_occupancy']; ?>/<?php echo $room['bed_capacity']; ?> occupied
                            </div>
                            <?php
                            if ($room['current_occupancy'] == 0) {
                                echo '<span class="occupancy-badge occupancy-empty">Empty</span>';
                            } elseif ($room['current_occupancy'] < $room['bed_capacity']) {
                                echo '<span class="occupancy-badge occupancy-partial">Partial</span>';
                            } else {
                                echo '<span class="occupancy-badge occupancy-full">Full</span>';
                            }
                            ?>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-rooms">
                    <h3>No Rooms Found</h3>
                    <p>No rooms have been created for your hostel yet.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Room Details Section -->
        <?php if ($roomDetails): ?>
            <div class="section">
                <div class="section-header">
                    <h3><i class="fas fa-info-circle"></i> Room Details</h3>
                </div>
                
                <div class="room-details">
                    <div class="room-header">
                        <h2>Room <?php echo htmlspecialchars($roomDetails['Room_No']); ?> - <?php echo htmlspecialchars($roomDetails['Hostel_name']); ?></h2>
                    </div>
                    
                    <div class="room-info">
                        <div class="info-item">
                            <div class="info-label">Room Type</div>
                            <div class="info-value"><?php echo $roomDetails['bed_capacity']; ?>-Bed Room</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Base Price</div>
                            <div class="info-value"><?php echo number_format($roomDetails['base_price'], 2); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Current Occupancy</div>
                            <div class="info-value"><?php echo $roomDetails['current_occupancy']; ?>/<?php echo $roomDetails['bed_capacity']; ?> beds occupied</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Available Beds</div>
                            <div class="info-value"><?php echo ($roomDetails['bed_capacity'] - $roomDetails['current_occupancy']); ?> beds</div>
                        </div>
                    </div>
                    
                    <h3 class="section-title">Bed Layout & Student Details</h3>
                    <div class="bed-grid">
                        <?php for ($i = 1; $i <= $roomDetails['bed_capacity']; $i++): ?>
                            <?php
                            $bedInfo = null;
                            foreach ($bedAllocations as $allocation) {
                                if ($allocation['bed_number'] == $i) {
                                    $bedInfo = $allocation;
                                    break;
                                }
                            }
                            ?>
                            <div class="bed-card <?php echo $bedInfo ? 'occupied' : 'empty'; ?>">
                                <div class="bed-number">Bed <?php echo $i; ?></div>
                                <div class="bed-status">
                                    <?php if ($bedInfo): ?>
                                        <div class="student-info">
                                            <div class="student-name"><?php echo htmlspecialchars($bedInfo['Fname'] . ' ' . $bedInfo['Lname']); ?></div>
                                            <div class="student-details">
                                                ID: <?php echo htmlspecialchars($bedInfo['Student_id']); ?><br>
                                                Phone: <?php echo htmlspecialchars($bedInfo['Mob_no']); ?><br>
                                                Dept: <?php echo htmlspecialchars($bedInfo['Dept']); ?><br>
                                                Year: <?php echo htmlspecialchars($bedInfo['Year_of_study']); ?><br>
                                                Price: Rs<?php echo number_format(calculateStudentPrice($bedInfo, $roomDetails['current_occupancy']), 2); ?><br>
                                                Food Plan: <?php echo getFoodPlanDisplay($bedInfo['include_food'], $bedInfo['food_plan']); ?><br>
                                                Since: <?php echo date('M j, Y', strtotime($bedInfo['start_date'])); ?>
</div>
<div style="margin-top:10px;">
    <a href="manager_room_details.php?room_id=<?php echo urlencode($selectedRoomId); ?>&student_id=<?php echo urlencode($bedInfo['Student_id']); ?>#payment-history" class="btn btn-sm btn-primary">View Payment History</a>
</div>
</div>
<?php else: ?>
                                        <div class="empty-bed">Empty Bed</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
<?php endfor; ?>
<?php if ($selectedStudentInfo): ?>
    <div class="table-container" id="payment-history" style="margin-top:30px;">
        <h3 class="section-title" style="text-align:left;">
            Payment History - <?php echo htmlspecialchars($selectedStudentInfo['Fname'] . ' ' . $selectedStudentInfo['Lname']); ?>
            (<?php echo htmlspecialchars($selectedStudentInfo['Student_id']); ?>)
        </h3>
        <?php if (count($paymentHistory) > 0): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Payment ID</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Gateway</th>
                            <th>Status</th>
                            <th>Transaction ID</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($paymentHistory as $payment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($payment['payment_id']); ?></td>
                                <td><?php echo date('d M Y H:i', strtotime($payment['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($payment['payment_type'] ?? 'N/A')); ?></td>
                                <td>NPR <?php echo number_format((float)$payment['amount'], 2); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($payment['payment_gateway'] ?? 'N/A')); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($payment['status'] ?? 'N/A')); ?></td>
                                <td><?php echo htmlspecialchars($payment['transaction_id'] ?? 'N/A'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">No payment history found for this student.</div>
        <?php endif; ?>
    </div>
<?php endif; ?>
</div>
</div>
            
        <?php elseif ($selectedRoomId): ?>
            <div class="section">
                <div class="no-rooms">
                    <h3>Room Not Found</h3>
                    <p>The selected room could not be found or doesn't belong to your hostel.</p>
                </div>
            </div>
        <?php endif; ?>
        
        <a href="manager_profile.php" class="back-link">← Back to Profile</a>
    </div> <!-- Close main container -->

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
		
		// Function to handle room selection
		function selectRoom(roomId) {
			// Redirect to the same page with the selected room ID
			window.location.href = 'manager_room_details.php?room_id=' + roomId;
		}
	</script>

<!-- Custom CSS/JS popup -->
<div id="custom-popup" class="custom-popup" role="alert" aria-hidden="true">
  <div class="custom-popup-inner">
    <button id="custom-popup-close" class="custom-popup-close" aria-label="Close">&times;</button>
    <div id="custom-popup-message" class="custom-popup-message"></div>
  </div>
</div>
<style>
/* Popup styles */
.custom-popup{display:none;position:fixed;right:20px;top:20px;z-index:9999;min-width:260px;max-width:360px;padding:0;}
.custom-popup.show{display:block;animation:fadeIn 0.25s ease-out}
.custom-popup-inner{position:relative;background:#fff;border-left:6px solid #28a745;padding:15px 15px 12px 15px;border-radius:6px;box-shadow:0 6px 18px rgba(0,0,0,0.12);}
.custom-popup.success .custom-popup-inner{border-color:#28a745}
.custom-popup.error .custom-popup-inner{border-color:#dc3545}
.custom-popup-message{font-size:14px;color:#222}
.custom-popup-close{background:transparent;border:0;font-size:20px;line-height:1;color:#666;position:absolute;right:8px;top:6px;cursor:pointer}
@keyframes fadeIn{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:none}}
</style>
<script>
function showPopup(message,type){var p=document.getElementById('custom-popup');var m=document.getElementById('custom-popup-message');p.className='custom-popup show '+(type==='success'?'success':'error');p.setAttribute('aria-hidden','false');m.textContent=message;clearTimeout(window._customPopupTimer);window._customPopupTimer=setTimeout(function(){p.classList.remove('show');p.setAttribute('aria-hidden','true');},3000);}document.addEventListener('click',function(e){if(e.target&& (e.target.id==='custom-popup' || e.target.id==='custom-popup-close')){document.getElementById('custom-popup').classList.remove('show');document.getElementById('custom-popup').setAttribute('aria-hidden','true');}});</script>

</body>
</html>