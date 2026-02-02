<?php
require_once 'includes/config.inc.php';
require_once 'includes/user_header.php';
require_once 'includes/price_calculator.php';

// Check if student is logged in
if (!isset($_SESSION['student_id']) && !isset($_SESSION['roll'])) {
    header("Location: login.php");
    exit();
}

// Get student ID from session
$studentId = $_SESSION['student_id'] ?? $_SESSION['roll'];

// Fetch student and room details - improved query similar to manager_room_details.php
$studentQuery = "SELECT s.Student_id, s.Fname, s.Lname, s.Email, s.Mob_no, s.Dept, s.Year_of_study,
                ba.room_id, ba.bed_number, ba.allocation_price, ba.start_date, ba.end_date, ba.is_active as allocation_active,
                ba.payment_status as food_payment_status, ba.payment_id as food_payment_id, ba.include_food, ba.food_plan,
                ba.created_at as food_plan_created_at,
                r.Room_No, r.bed_capacity, r.base_price as room_base_price, r.current_occupancy,
                h.Hostel_name, h.Hostel_id
                FROM Student s 
                LEFT JOIN bed_allocation ba ON s.Student_id = ba.student_id AND ba.is_active = 1
                LEFT JOIN Room r ON ba.room_id = r.Room_id
                LEFT JOIN Hostel h ON r.Hostel_id = h.Hostel_id
                WHERE s.Student_id = ?";

$stmt = $conn->prepare($studentQuery);
if ($stmt) {
    $stmt->bind_param("s", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    $stmt->close();
} else {
    die("Error preparing student query: " . $conn->error);
}

// Calculate final price using centralized price calculator
$roomOccupancy = isset($student['current_occupancy']) && (int)$student['current_occupancy'] > 0 ? (int)$student['current_occupancy'] : 1;
$allocationForPricing = [
    'include_food' => (int)($student['include_food'] ?? 0),
    'food_plan' => $student['food_plan'] ?? null
];
$priceBreakdown = getPriceBreakdown($allocationForPricing, $roomOccupancy);
$finalPrice = $priceBreakdown['total_price'];
$pricing = [
    'base_price' => $priceBreakdown['base_room_price'],
    'final_price' => $finalPrice
];

// Get payment history
$paymentHistory = [];
$paymentQuery = "SELECT * FROM payments WHERE student_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($paymentQuery);
if ($stmt) {
    $stmt->bind_param("s", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $paymentHistory[] = $row;
    }
    $stmt->close();
}

// Calculate payment summary
$paymentSummary = [
    'total_transactions' => count($paymentHistory),
    'total_paid' => 0,
    'pending_amount' => 0,
    'completed_payments' => 0
];

foreach ($paymentHistory as $payment) {
    if ($payment['status'] === 'completed') {
        $paymentSummary['total_paid'] += $payment['amount'];
        $paymentSummary['completed_payments']++;
    }
    // Note: Pending amount will be calculated later based on allocation dates
}

$paymentSummary['success_rate'] = $paymentSummary['total_transactions'] > 0 
    ? round(($paymentSummary['completed_payments'] / $paymentSummary['total_transactions']) * 100, 1) 
    : 0;

// Monthly Payment Tracking Logic
$monthlyPaymentData = [];
$allocationCanPayMore = true;
$totalAllocationCost = 0;
$totalMonths = 0;

if ($student['start_date'] && $student['end_date']) {
    $startDate = new DateTime($student['start_date']);
    $endDate = new DateTime($student['end_date']);
    $currentDate = new DateTime();
    
    // Calculate total months in allocation
    $startMonth = $startDate->format('Y-m');
    $endMonth = $endDate->format('Y-m');
    $interval = $startDate->diff($endDate);
    $totalMonths = (int)($interval->days / 30) + 1; // Approximate months
    
    // Calculate total allocation cost for all months
    $totalAllocationCost = $finalPrice * $totalMonths;
    
    // Get completed payments count
    $completedPaymentsCount = 0;
    foreach ($paymentHistory as $payment) {
        if ($payment['status'] === 'completed') {
            $completedPaymentsCount++;
        }
    }
    
    // Calculate real-time pending amount
    // Pending = Total cost - Already paid
    $paymentSummary['pending_amount'] = $totalAllocationCost - $paymentSummary['total_paid'];
    
    // Generate monthly data
    $monthIterator = clone $startDate;
    for ($i = 0; $i < $totalMonths; $i++) {
        $monthKey = $monthIterator->format('Y-m');
        $monthLabel = $monthIterator->format('M Y');
        
        // Check if this month has a corresponding payment
        $isPaid = ($i < $completedPaymentsCount);
        
        $monthlyPaymentData[$monthKey] = [
            'label' => $monthLabel,
            'month_number' => $i + 1,
            'is_paid' => $isPaid,
            'date' => $monthIterator->format('Y-m-d')
        ];
        
        $monthIterator->modify('+1 month');
    }
    
    // Check if all months are paid (disable further payments)
    $allocationCanPayMore = ($completedPaymentsCount < $totalMonths);
}
?>

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
    }

    /* Header Styles */
    .header {
        background: linear-gradient(135deg, #003366 0%, #004080 100%);
        color: white;
        border-radius: 10px;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        text-align: center;
    }

    .header h1 {
        font-size: 2.5rem;
        font-weight: 600;
        margin-bottom: 10px;
        margin-top: 0;
    }

    .header p {
        font-size: 1.1rem;
        margin: 0;
        opacity: 0.9;
    }

    /* Section Styles */
    .section {
        background: white;
        border-radius: 10px;
        padding: 25px;
        margin-bottom: 20px;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
    }

    .w3l-about-1 {
        padding: 20px 0;
        background: transparent;
    }

    .w3l-about-info {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0;
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

    /* Title Styles - Updated for consistency */
    .w3l-title-main {
        font-size: 2rem;
        font-weight: 600;
        color: #003366;
        text-align: center;
        margin: 0 0 25px 0;
        padding-bottom: 0;
        position: relative;
    }

    /* Grid and Card Styles */
    .w3l-abt-grid {
        margin-bottom: 15px;
    }

    .icon-box {
        background: white;
        border-radius: 10px;
        padding: 25px;
        text-align: center;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        border: 2px solid #e9ecef;
        height: 100%;
    }

    .icon-box:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
        border-color: #003366;
    }

    .icon-box h4 {
        font-size: 1.3rem;
        font-weight: 600;
        color: #003366;
        margin-bottom: 15px;
        margin-top: 0;
    }

    .icon-box h4 a {
        color: #003366;
        text-decoration: none;
        transition: color 0.3s ease;
    }

    .icon-box h4 a:hover {
        color: #004080;
    }

    .icon-box p {
        color: #666;
        font-size: 0.95rem;
        line-height: 1.6;
        margin-bottom: 10px;
    }

    .icon-box strong {
        color: #003366;
        font-weight: 600;
    }

    /* Icon Box Color Variations */
    .icon-box-1 {
        border-top: 4px solid #007bff;
        border-bottom: none;
    }

    .icon-box-2 {
        border-top: 4px solid #28a745;
        border-bottom: none;
    }

    .icon-box-3 {
        border-top: 4px solid #ffc107;
        border-bottom: none;
    }

    .icon-box-4 {
        border-top: 4px solid #dc3545;
        border-bottom: none;
    }

    .icon-box-5 {
        border-top: 4px solid #17a2b8;
        border-bottom: none;
    }

    /* Payment History Styles */
    .summary-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }

    .summary-card {
        background: linear-gradient(135deg, #003366, #004080);
        color: white;
        border-radius: 10px;
        padding: 25px;
        text-align: center;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }

    .summary-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    }

    .summary-card:nth-child(2) {
        background: linear-gradient(135deg, #28a745, #218838);
    }

    .summary-card:nth-child(3) {
        background: linear-gradient(135deg, #ffc107, #e0a800);
        color: #333;
    }

    .summary-card:nth-child(3) h4 {
        color: #333;
        opacity: 0.9;
    }

    .summary-card:nth-child(3) h3 {
        color: #333;
    }

    .summary-card:nth-child(4) {
        background: linear-gradient(135deg, #17a2b8, #138496);
    }

    .summary-card h4 {
        font-size: 0.95rem;
        font-weight: 500;
        margin-bottom: 10px;
        margin-top: 0;
        opacity: 0.9;
    }

    .summary-card h3 {
        font-size: 28px;
        font-weight: 700;
        margin: 0;
    }

    /* Form Styles */
    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        font-weight: 600;
        margin-bottom: 8px;
        display: block;
        color: #003366;
        font-size: 0.95rem;
    }

    .form-control {
        border: 2px solid #e9ecef;
        border-radius: 8px;
        padding: 12px;
        width: 100%;
        box-sizing: border-box;
        font-size: 0.95rem;
        font-family: 'Poppins', sans-serif;
    }

    .form-control:focus {
        border-color: #003366;
        outline: none;
        box-shadow: 0 0 0 0.2rem rgba(0, 51, 102, 0.25);
    }

    /* Button Styles */
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
        font-family: 'Poppins', sans-serif;
    }

    .btn-submit:hover {
        background: linear-gradient(135deg, #ffcc00 0%, #ffdb4d 100%);
        color: #003366;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(255, 204, 0, 0.3);
    }

    /* Alert Styles */
    .alert {
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 20px;
        font-family: 'Poppins', sans-serif;
        border: 1px solid transparent;
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

    .alert-info {
        background-color: #d1ecf1;
        border-color: #bee5eb;
        color: #0c5460;
    }

    .alert-warning {
        background-color: #fff3cd;
        border-color: #ffeeba;
        color: #856404;
    }

    /* Status Badges */
    $paymentSummary = [
        'total_transactions' => 0,
        'total_paid' => 0,
        'pending_amount' => 0,
        'success_rate' => 0,
        'completed_payments' => 0
    ];
    $paymentHistory = [];
    .status-badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        display: inline-block;
    }

    .status-completed {
        background: #d4edda;
        color: #155724;
    }

    .status-pending {
        background: #fff3cd;
        color: #856404;
    }

    .status-failed {
        background: #f8d7da;
        color: #721c24;
    }

    /* Table Styles */
    .table {
        margin-bottom: 0;
        font-family: 'Poppins', sans-serif;
    }

    .table thead th {
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
        color: #003366;
        font-weight: 600;
    }

    .table tbody tr:hover {
        background-color: #f8f9fa;
    }

    /* Tab Styles */
    .nav-tabs {
        border-bottom: 2px solid #e9ecef;
    }

    .nav-tabs .nav-link {
        color: #000 !important;
        font-weight: 500;
        border: none;
        border-bottom: 3px solid transparent;
        transition: all 0.3s ease;
        font-family: 'Poppins', sans-serif;
    }

    .nav-tabs .nav-link i {
        color: #000 !important;
    }

    .nav-tabs .nav-link:hover {
        color: #003366 !important;
        border-bottom-color: #003366;
    }

    .nav-tabs .nav-link:hover i {
        color: #003366 !important;
    }

    .nav-tabs .nav-link.active {
        color: #003366 !important;
        background-color: transparent;
        border-bottom-color: #003366;
        border: none;
    }

    .nav-tabs .nav-link.active i {
        color: #003366 !important;
    }

    .tab-content {
        padding-top: 20px;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #666;
    }

    .empty-state i {
        font-size: 48px;
        color: #ddd;
        margin-bottom: 20px;
        display: block;
    }

    .empty-state h5 {
        font-size: 20px;
        margin-bottom: 10px;
        color: #333;
    }

    .empty-state p {
        font-size: 0.95rem;
        margin: 0;
    }
    /* Monthly Payment Tracker */
    .monthly-tracker {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 15px;
        margin: 20px 0;
    }

    .month-card {
        text-align: center;
        padding: 20px;
        border-radius: 10px;
        border: 2px solid #e9ecef;
        transition: all 0.3s ease;
        font-family: 'Poppins', sans-serif;
    }

    .month-card.paid {
        background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
        border-color: #28a745;
    }

    .month-card.unpaid {
        background: white;
        border-color: #e9ecef;
    }

    .month-card.unpaid:hover {
        border-color: #003366;
        box-shadow: 0 3px 10px rgba(0, 51, 102, 0.1);
    }

    .month-card h6 {
        margin: 0 0 10px 0;
        font-weight: 600;
        color: #003366;
        font-size: 0.9rem;
    }

    .month-card .status-icon {
        font-size: 24px;
        margin-bottom: 8px;
    }

    .month-card.paid .status-icon {
        color: #28a745;
    }

    .month-card.unpaid .status-icon {
        color: #ffc107;
    }

    .month-card p {
        margin: 8px 0 0 0;
        font-size: 0.85rem;
        color: #666;
        font-weight: 500;
    }

    .month-card.paid p {
        color: #155724;
    }
    /* Info Card */
    .info-card {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 10px;
        border-left: 4px solid #003366;
    }

    .info-card p {
        margin-bottom: 10px;
        color: #666;
        font-size: 0.95rem;
    }

    .info-card strong {
        color: #003366;
        font-weight: 600;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .header h1 {
            font-size: 2rem;
        }

        .section-header h3 {
            font-size: 1.5rem;
        }

        .icon-box {
            padding: 20px;
            margin-bottom: 15px;
        }

        .summary-cards {
            grid-template-columns: repeat(2, 1fr);
        }

        .w3l-title-main {
            font-size: 1.5rem;
        }
    }
</style>
<br>
<div class="container">
    <!-- Header -->
    <div class="header">
        <h1><i class="fa fa-door-open"></i> Room Information</h1>
        <p>View your allocated room and payment details</p>
    </div>

    <!-- Room Information Section -->
    <div class="section">
        <div class="section-header">
            <h3><i class="fa fa-info-circle"></i> Your Room Details</h3>
        </div>
        
        <div class="row">
                <?php if ($student['room_id']): ?>
                    <!-- Room Details -->
                    <div class="col-md-6 col-lg-4">
                        <div class="w3l-abt-grid">
                            <div class="icon-box icon-box-1">
                                <h4><a href="#url">Room Details</a></h4>
                                <p><strong>Room Type:</strong> <?php echo $student['bed_capacity'] . '-Bed Room'; ?></p>
                                <p><strong>Your Bed:</strong> Bed #<?php echo htmlspecialchars($student['bed_number']); ?></p>
                                <p><strong>Room Number:</strong> <?php echo htmlspecialchars($student['Room_No']); ?></p>
                                <p><strong>Hostel:</strong> <?php echo htmlspecialchars($student['Hostel_name'] ?? 'N/A'); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Allocation Period -->
                    <div class="col-md-6 col-lg-4">
                        <div class="w3l-abt-grid">
                            <div class="icon-box icon-box-2">
                                <h4><a href="#url">Allocation Period</a></h4>
                                <p><strong>Start Date:</strong> <?php echo date('d M Y', strtotime($student['start_date'])); ?></p>
                                <p><strong>End Date:</strong> <?php echo date('d M Y', strtotime($student['end_date'])); ?></p>
                                <p><strong>Duration:</strong> <?php echo (strtotime($student['end_date']) - strtotime($student['start_date'])) / (60 * 60 * 24); ?> days</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Occupancy -->
                    <div class="col-md-6 col-lg-4">
                        <div class="w3l-abt-grid">
                            <div class="icon-box icon-box-3">
                                <h4><a href="#url">Occupancy</a></h4>
                                <p><strong>Room Capacity:</strong> <?php echo $student['bed_capacity']; ?> beds</p>
                                <p><strong>Current Occupancy:</strong> <?php echo $student['current_occupancy']; ?> occupants</p>
                                <p><strong>Available Beds:</strong> <?php echo $student['bed_capacity'] - $student['current_occupancy']; ?> beds</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Food Plan -->
                    <div class="col-md-6 col-lg-4">
                        <div class="w3l-abt-grid">
                            <div class="icon-box icon-box-4">
                                <h4><a href="#url">Food Plan</a></h4>
                                <p><strong>Food Included:</strong> <?php echo $student['include_food'] ? 'Yes' : 'No'; ?></p>
                                <p><strong>Food Plan:</strong> <?php echo ucfirst($student['food_plan'] ?? 'None'); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pricing Details -->
                    <div class="col-md-6 col-lg-4">
                        <div class="w3l-abt-grid">
                            <div class="icon-box icon-box-5">
                                <h4><a href="#url">Pricing Details</a></h4>
                                <p><strong>Your Price:</strong> NPR <?php echo number_format($finalPrice, 2); ?></p>
<p><strong>Room Base Price:</strong> NPR <?php echo number_format($pricing['base_price'], 2); ?></p>
<p><strong>Full Room Price:</strong> NPR <?php echo number_format($pricing['base_price'], 2); ?></p>
                            </div>
                        </div>
                    </div>
                    
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-info">
                            <h4>No Room Allocated</h4>
                            <p>You haven't been assigned a room yet. Please contact the hostel administrator.</p>
                        </div>
                    </div>
                <?php endif; ?>
        </div>
    </div>

<!-- Payment Section -->
<div class="container mt-5">
    <div class="section">
        <div class="section-header">
            <h3><i class="fa fa-credit-card"></i> Payment Information</h3>
        </div>
        
        <!-- Payment Status Messages -->
        <?php
        // Display success message from session
        if (isset($_SESSION['success'])):
        ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php
        // Display error message from session
        if (isset($_SESSION['error'])):
        ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>❌ Payment Failed:</strong> <?php 
                echo htmlspecialchars($_SESSION['error']);
                unset($_SESSION['error']);
                ?>
                <?php if (isset($_SESSION['payment_failure'])): ?>
                    <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid rgba(255,255,255,0.2); font-size: 0.9em;">
                        <div><strong>Failure Details:</strong></div>
                        <ul style="margin: 8px 0 0 0; padding-left: 20px;">
                            <?php if (!empty($_SESSION['payment_failure']['status'])): ?>
                                <li><strong>Status:</strong> <?php echo htmlspecialchars($_SESSION['payment_failure']['status']); ?></li>
                            <?php endif; ?>
                            <?php if (!empty($_SESSION['payment_failure']['amount'])): ?>
                                <li><strong>Amount:</strong> NPR <?php echo number_format($_SESSION['payment_failure']['amount'], 2); ?></li>
                            <?php endif; ?>
                            <?php if (!empty($_SESSION['payment_failure']['timestamp'])): ?>
                                <li><strong>Time:</strong> <?php echo htmlspecialchars($_SESSION['payment_failure']['timestamp']); ?></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <?php unset($_SESSION['payment_failure']); ?>
                <?php endif; ?>
                <br><br>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php
        // Display payment error message
        if (isset($_SESSION['payment_error'])):
        ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <strong>Payment Error:</strong> <?php 
                echo htmlspecialchars($_SESSION['payment_error']);
                unset($_SESSION['payment_error']);
                ?>
            </div>
        <?php endif; ?>
        
        <!-- Monthly Payment Tracker -->
        <?php if (!empty($monthlyPaymentData)): ?>
        <div style="margin-bottom: 30px;">
            <h5 style="color: #003366; margin-bottom: 15px; font-weight: 600;">
                <i class="fa fa-calendar-check"></i> Monthly Payment Schedule
            </h5>
            <div class="monthly-tracker">
                <?php foreach ($monthlyPaymentData as $monthKey => $monthData): ?>
                    <div class="month-card <?php echo $monthData['is_paid'] ? 'paid' : 'unpaid'; ?>">
                        <h6><?php echo $monthData['label']; ?></h6>
                        <div class="status-icon">
                            <?php echo $monthData['is_paid'] ? '<i class="fa fa-check-circle"></i>' : '<i class="fa fa-clock"></i>'; ?>
                        </div>
                        <p><?php echo $monthData['is_paid'] ? 'Paid' : 'Pending'; ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
            
            <div class="row">
                <?php
                $payment_status = $student['food_payment_status'] ?? 'pending';
                $payment_id = $student['food_payment_id'] ?? null;
                
                // Show payment form only if there are months remaining to pay
                if (($payment_status === 'pending' && !$payment_id) || $allocationCanPayMore):
                    $student_price = $finalPrice;
                    $purchase_order_id = 'room_allocation_' . $studentId . '_' . date('YmdHis');
                    $nextMonthToPay = $paymentSummary['completed_payments'];
                    $totalMonths = count($monthlyPaymentData);
                ?>
                    <div class="col-12">
                        <?php if ($allocationCanPayMore): ?>
                        <div class="alert alert-info">
                            <h4>Complete Your Payment</h4>
                            <p><strong>Amount:</strong> NPR <?php echo number_format($student_price, 2); ?></p>
                            <p><strong>Payment Type:</strong> Room Allocation</p>
                            <p><strong>Month:</strong> <?php echo isset($monthlyPaymentData) ? 'Month ' . $nextMonthToPay . ' of ' . $totalMonths : 'N/A'; ?></p>
                        </div>
                        
                        <!-- Payment Gateway Selection -->
                        <div class="text-center mt-4">
                            <h5>Select Payment Gateway</h5>
                            <form method="POST" action="payment/process_payment.php" class="mt-3" id="payment-form">
                                <div class="row justify-content-center">
                                    <div class="col-md-4">
                                        <div class="card" onclick="selectGateway('esewa')" style="cursor: pointer; border: 2px solid transparent; transition: all 0.3s;">
                                            <div class="card-body text-center">
                                                <i class="fa fa-wallet fa-3x text-primary mb-3"></i>
                                                <h5>eSewa</h5>
                                                <p class="text-muted">Pay with eSewa Wallet</p>
                                                <input type="radio" name="payment_gateway" value="esewa" style="display: none;" id="esewa-radio">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <input type="hidden" name="amount" value="<?php echo $student_price; ?>">
                                <input type="hidden" name="payment_type" value="room_allocation">
                                <input type="hidden" name="purchase_order_id" value="<?php echo $purchase_order_id; ?>">
                            </form>
                        </div>
                        
                        <div id="payment-form-container" style="display: none;">
                            <div id="esewa-payment-form-container"></div>
                        </div>
                        <?php endif; ?>
                        
                        <script>
                            function selectGateway(gateway) {
                                if (gateway === 'esewa') {
                                    var radioBtn = document.getElementById('esewa-radio');
                                    if (radioBtn) {
                                        radioBtn.checked = true;
                                    }
                                    
                                    var form = document.getElementById('payment-form');
                                    if (form) {
                                        var formData = new FormData(form);
                                        
                                        fetch(form.action, {
                                            method: 'POST',
                                            body: formData
                                        })
                                        .then(response => {
                                            return response.json();
                                        })
                                        .then(data => {
                                            if (data.success) {
                                                generateAndSubmitEsewaForm(data);
                                            } else {
                                                alert('Error: ' + (data.error || 'Payment processing failed'));
                                            }
                                        })
                                        .catch(error => {
                                            alert('Error: ' + error.message);
                                        });
                                    }
                                }
                            }
                            
                            function generateAndSubmitEsewaForm(data) {
                                var form = document.createElement('form');
                                form.method = 'POST';
                                form.action = data.payment_url;
                                form.style.display = 'none';
                                
                                for (var key in data.form_data) {
                                    if (data.form_data.hasOwnProperty(key)) {
                                        var input = document.createElement('input');
                                        input.type = 'hidden';
                                        input.name = key;
                                        input.value = data.form_data[key];
                                        form.appendChild(input);
                                    }
                                }
                                
                                document.body.appendChild(form);
                                form.submit();
                            }
                        </script>
                    </div>
                    
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-success">
                            <h4>Payment Status: Completed</h4>
                            <?php if ($payment_id): ?>
                                <p><strong>Gateway:</strong> Esewa</p>
                                <p><strong>Amount:</strong> NPR <?php echo number_format($finalPrice ?? 0, 2); ?></p>
                                <p><strong>Payment ID:</strong> <?php echo htmlspecialchars($payment_id); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Payment History Section -->
<div class="container mt-5">
    <div class="section">
        <div class="section-header">
            <h3><i class="fa fa-history"></i> Payment History</h3>
        </div>
        
        <div class="summary-cards">
                    <div class="summary-card">
                        <h4>Total Transactions</h4>
                        <h3><?php echo $paymentSummary['total_transactions']; ?></h3>
                    </div>
                    <div class="summary-card">
                        <h4>Total Paid</h4>
                        <h3>NPR <?php echo number_format($paymentSummary['total_paid'], 2); ?></h3>
                    </div>
                    <div class="summary-card">
                        <h4>Pending Amount</h4>
                        <h3>NPR <?php echo number_format($paymentSummary['pending_amount'], 2); ?></h3>
                    </div>
                    <div class="summary-card">
                        <h4>Success Rate</h4>
                        <h3><?php echo $paymentSummary['success_rate']; ?>%</h3>
                    </div>
                </div>
                
                <!-- Payment Tabs -->
                <ul class="nav nav-tabs" id="paymentTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="recent-tab" data-toggle="tab" href="#recent" role="tab">
                            <i class="fa fa-clock me-2"></i>Recent Payments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="all-tab" data-toggle="tab" href="#all" role="tab">
                            <i class="fa fa-list me-2"></i> All Transactions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="summary-tab" data-toggle="tab" href="#summary" role="tab">
                            <i class="fa fa-chart-pie me-2"></i>Payment Summary
                        </a>
                    </li>
                </ul>
                
                <div class="tab-content" id="paymentTabContent">
                    <div class="tab-pane fade show active" id="recent" role="tabpanel">
                        <?php if (count($paymentHistory) > 0): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Payment ID</th>
                                            <th>Date</th>
                                            <th>Amount</th>
                                            <th>Gateway</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $recentPayments = array_slice($paymentHistory, 0, 5);
                                        foreach ($recentPayments as $payment): 
                                        ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($payment['payment_id']); ?></td>
                                                <td><?php echo date('d M Y H:i', strtotime($payment['created_at'])); ?></td>
                                                <td>NPR <?php echo number_format($payment['amount'], 2); ?></td>
                                                <td><?php echo ucfirst($payment['payment_gateway'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <span class="status-badge status-<?php echo $payment['status']; ?>">
                                                        <?php echo ucfirst($payment['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fa fa-receipt"></i>
                                <h5>No Payment History</h5>
                                <p>You haven't made any payments yet. Your payment history will appear here once you start making payments.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="tab-pane fade" id="all" role="tabpanel">
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
                                                <td><?php echo ucfirst($payment['payment_type'] ?? 'N/A'); ?></td>
                                                <td>NPR <?php echo number_format($payment['amount'], 2); ?></td>
                                                <td><?php echo ucfirst($payment['payment_gateway'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <span class="status-badge status-<?php echo $payment['status']; ?>">
                                                        <?php echo ucfirst($payment['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($payment['transaction_id'] ?? 'N/A'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-receipt"></i>
                                <h5>No Payment History</h5>
                                <p>You haven't made any payments yet. Your payment history will appear here once you start making payments.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="tab-pane fade" id="summary" role="tabpanel">
                        <div class="row">
                            <div class="col-md-6">
                                <h5><i class="fa fa-chart-line me-2"></i> Payment Overview</h5>
                                <div class="info-card">
                                    <p><strong>Allocation Duration:</strong> <?php echo $totalMonths; ?> months</p>
                                    <p><strong>Monthly Cost:</strong> NPR <?php echo number_format($finalPrice, 2); ?></p>
                                    <p><strong>Total Allocation Cost:</strong> NPR <?php echo number_format($totalAllocationCost, 2); ?></p>
                                    <hr style="margin: 10px 0; border: 1px solid #dee2e6;">
                                    <p><strong>Total Transactions:</strong> <?php echo $paymentSummary['total_transactions']; ?></p>
                                    <p><strong>Completed Payments:</strong> <?php echo $paymentSummary['completed_payments']; ?></p>
                                    <p><strong>Total Amount Paid:</strong> NPR <?php echo number_format($paymentSummary['total_paid'], 2); ?></p>
                                    <p><strong>Pending Amount:</strong> NPR <?php echo number_format($paymentSummary['pending_amount'], 2); ?></p>
                                    <p><strong>Success Rate:</strong> <?php echo $paymentSummary['success_rate']; ?>%</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h5><i class="fa fa-calendar me-2"></i>Payment Timeline</h5>
                                <div class="info-card">
                                    <?php if (count($paymentHistory) > 0): ?>
                                        <p><strong>Last Payment:</strong> <?php echo date('d M Y', strtotime($paymentHistory[0]['created_at'])); ?></p>
                                        <p><strong>Last Payment Amount:</strong> NPR <?php echo number_format($paymentHistory[0]['amount'], 2); ?></p>
                                        <p><strong>Payment Method:</strong> <?php echo ucfirst($paymentHistory[0]['payment_gateway'] ?? 'N/A'); ?></p>
                                    <?php else: ?>
                                        <p><strong>Last Payment:</strong> No payments yet</p>
                                        <p><strong>Payment Method:</strong> N/A</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="project/web_home/js_home/bootstrap.js"></script>
<script>
    $(document).ready(function() {
        // Initialize Bootstrap tabs - properly handle tab clicks
        $('#paymentTabs a').on('click', function (e) {
            e.preventDefault();
            // Get the target pane id
            var targetPane = $(this).attr('href');
            // Hide all panes
            $('.tab-pane').removeClass('show active');
            // Show the target pane
            $(targetPane).addClass('show active');
            // Update active tab link
            $('#paymentTabs .nav-link').removeClass('active');
            $(this).addClass('active');
        });
    });
</script>



