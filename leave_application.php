<?php
require_once 'includes/config.inc.php';
require_once 'includes/user_header.php';
require_once 'includes/notification_helper.php';
require_once 'includes/price_calculator.php';

// Check if user is logged in
if (!isset($_SESSION['roll'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['roll'];
$student_name = $_SESSION['fname'] . ' ' . $_SESSION['lname'];

// Get student's current food plan and allocation
$student_query = "SELECT s.*, ba.allocation_price, h.Hostel_name, r.Room_No, ba.bed_number, 
                         ba.include_food, ba.food_plan, r.current_occupancy
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

// Get leave settings
$settings_query = "SELECT setting_name, setting_value FROM leave_settings WHERE is_active = 1";
$settings_result = mysqli_query($conn, $settings_query);
$settings = [];
while ($row = mysqli_fetch_assoc($settings_result)) {
    $settings[$row['setting_name']] = $row['setting_value'];
}

// Use centralized price calculator to get student's food plan and pricing
$has_food_plan = ($student_data['include_food'] == 1 && !empty($student_data['food_plan']));
$food_plan = $student_data['food_plan'] ?? 'standard'; // Default to standard if not set
$monthly_food_price = $has_food_plan ? getFoodPlanPrice($food_plan) : 0;
$days_in_month = $settings['days_in_month'] ?? 30;
$daily_rate = $has_food_plan ? $monthly_food_price / $days_in_month : 0;

// Check if student has food plan for leave application
if (!$has_food_plan) {
    $no_food_plan_notice = "You don't have an active food plan. You can still apply for leave, but there will be no price reduction since you don't have food charges.";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("DEBUG: Form submission detected");
    error_log("DEBUG: POST data: " . print_r($_POST, true));
    
    $form_leave_type = $_POST['leave_type'];
    
    // Map form leave types to database enum values
    $leave_type_mapping = [
        'short' => 'partial',
        'long' => 'partial'
    ];
    $leave_type = $leave_type_mapping[$form_leave_type] ?? 'partial';
    
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $remarks = $_POST['reason'] ?? '';
    
    // Calculate leave days
    $datetime1 = new DateTime($start_date);
    $datetime2 = new DateTime($end_date);
    $interval = $datetime1->diff($datetime2);
    $leave_days = $interval->days + 1; // Include both start and end dates
    
    // Calculate estimated reduction using centralized function (will be 0 if no food plan)
    $leave_calculation = calculateLeaveReduction($food_plan, $leave_days, $days_in_month);
    $estimated_reduction = $has_food_plan ? $leave_calculation['reduction'] : 0;
    
    $errors = [];
    
    $today = new DateTime();
    $tomorrow = new DateTime();
    $tomorrow->modify('+1 day');
    $start_dt = new DateTime($start_date);
    $end_dt = new DateTime($end_date);
    $max_future_date = new DateTime();
    $max_future_date->modify('+6 months'); // Allow leaves up to 6 months in advance
    
    // Validate dates
    if ($start_dt <= $today) {
        $errors[] = "Start date must be from tomorrow onwards";
    }
    if ($end_dt < $start_dt) {
        $errors[] = "End date must be after or same as start date";
    }
    if ($start_dt > $max_future_date) {
        $errors[] = "Start date cannot be more than 6 months in advance";
    }
    $max_days = $settings['max_leave_days'] ?? 30;
    if ($leave_days > $max_days) {
        $errors[] = "Maximum leave days allowed is $max_days";
    }
    
    // Check for overlapping approved leave dates
    $overlap_query = "SELECT COUNT(*) as overlapping_days 
                     FROM leave_applications 
                     WHERE student_id = ? 
                     AND status = 'approved'
                     AND (
                         (start_date <= ? AND end_date >= ?) OR
                         (start_date <= ? AND end_date >= ?) OR
                         (start_date >= ? AND end_date <= ?)
                     )";
    
    $stmt_overlap = mysqli_prepare($conn, $overlap_query);
    mysqli_stmt_bind_param($stmt_overlap, "sssssss", 
                           $student_id, $start_date, $start_date,
                           $end_date, $end_date, $start_date, $end_date);
    mysqli_stmt_execute($stmt_overlap);
    $overlap_result = mysqli_stmt_get_result($stmt_overlap);
    $overlap_data = mysqli_fetch_assoc($overlap_result);
    
    if ($overlap_data['overlapping_days'] > 0) {
        $errors[] = "You already have approved leave during this period. Please select different dates.";
    }
    
    if (empty($errors)) {
        // Debug: Log that we passed validation
        error_log("DEBUG: Validation passed, proceeding with insertion");
        
        $status = 'pending';
        
        // Debug: Log all values before insertion
        error_log("DEBUG: Attempting to insert leave application with values:");
        error_log("student_id: " . $student_id);
        error_log("leave_type: " . $leave_type);
        error_log("start_date: " . $start_date);
        error_log("end_date: " . $end_date);
        error_log("leave_days: " . $leave_days);
        error_log("status: " . $status);
        error_log("remarks: " . $remarks);
        error_log("food_plan: " . $food_plan);
        error_log("monthly_food_price: " . $monthly_food_price);
        error_log("estimated_reduction: " . $estimated_reduction);
        
        // Insert leave application
        $insert_query = "INSERT INTO leave_applications 
                        (student_id, leave_type, start_date, end_date, leave_days, 
                         status, remarks, food_plan, original_food_price, estimated_reduction)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        error_log("DEBUG: About to prepare statement");
        $stmt = mysqli_prepare($conn, $insert_query);
        
        if ($stmt === false) {
            $error_message = "Failed to prepare statement: " . mysqli_error($conn);
            error_log("DEBUG: Statement preparation failed: " . mysqli_error($conn));
        } else {
            error_log("DEBUG: Statement prepared successfully, binding parameters");
            mysqli_stmt_bind_param($stmt, "ssssisssdd", 
                                   $student_id, $leave_type, $start_date, $end_date, 
                                   $leave_days, $status, $remarks, $food_plan, $monthly_food_price, 
                                   $estimated_reduction);
            
            error_log("DEBUG: Parameters bound, executing statement");
            if (mysqli_stmt_execute($stmt)) {
                $success_message = "Leave application submitted successfully! Your application ID is: " . mysqli_insert_id($conn);
                error_log("DEBUG: Insert successful, ID: " . mysqli_insert_id($conn));
            } else {
                $error_message = "Failed to submit application. Error: " . mysqli_stmt_error($stmt);
                error_log("DEBUG: Statement execution failed: " . mysqli_stmt_error($stmt));
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        // Debug: Log validation errors
        error_log("DEBUG: Validation failed with errors: " . implode(", ", $errors));
        $error_message = implode(", ", $errors);
    }
}

// Mark leave notifications as read when student visits this page
markLeaveNotificationsRead($student_id, $conn);

// Get existing leave applications
$existing_query = "SELECT * FROM leave_applications WHERE student_id = ? ORDER BY applied_date DESC";
$stmt = mysqli_prepare($conn, $existing_query);
mysqli_stmt_bind_param($stmt, "s", $student_id);
mysqli_stmt_execute($stmt);
$existing_applications = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Leave Application - Hostel Management System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="web_home/css_home/bootstrap.css">
    <link rel="stylesheet" href="web_home/css_home/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
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
        .leave-type-card {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }
        .leave-type-card:hover {
            border-color: #003366;
            background-color: #f8f9fa;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .leave-type-card.selected {
            border-color: #003366;
            background-color: #e3f2fd;
            box-shadow: 0 5px 15px rgba(0,51,102,0.2);
        }
        .price-calculation {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #003366;
        }
        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 5px 0;
        }
        .price-row.total {
            font-weight: 600;
            font-size: 1.1rem;
            color: #003366;
            border-top: 2px solid #dee2e6;
            padding-top: 15px;
            margin-top: 15px;
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
            font-family: 'Poppins', sans-serif;
        }
        .btn-submit:hover {
            background: linear-gradient(135deg, #ffcc00 0%, #ffdb4d 100%);
            color: #003366;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255,204,0,0.3);
        }
        .alert {
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            font-family: 'Poppins', sans-serif;
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
        .existing-applications {
            margin-top: 40px;
        }
        .application-card {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            background: white;
            transition: all 0.3s ease;
        }
        .application-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
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
            border-color: #ffeaa7;
        }
        .status-approved {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }
        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
        @media (max-width: 768px) {
            .header h1 {
                font-size: 2rem;
            }
            .section-header h3 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-calendar-alt"></i> Leave Application</h1>
            <p>Apply for leave and get food price reductions</p>
        </div>

        <?php if ($student_data['Hostel_id'] && $student_data['Room_id']): ?>
        <!-- Leave Application Form Section -->
        <div class="section">
            <div class="section-header">
                <h3><i class="fas fa-edit"></i> Application Form</h3>
            </div>
            
            <?php
            if (isset($success_message)) {
                echo "<div class='alert alert-success'>$success_message</div>";
            }
            if (isset($error_message)) {
                echo "<div class='alert alert-danger'>$error_message</div>";
            }
            if (isset($no_food_plan_notice)) {
                echo "<div class='alert alert-info'>$no_food_plan_notice</div>";
            }
            ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="leave_type">Leave Type</label>
                    <div class="leave-type-card" onclick="selectLeaveType('short')">
                        <h4><i class="fas fa-home"></i> Short Leave (1-7 days)</h4>
                        <p>For temporary leave<?php echo $has_food_plan ? ' with food price reduction' : ''; ?></p>
                    </div>
                    <div class="leave-type-card" onclick="selectLeaveType('long')">
                        <h4><i class="fas fa-plane"></i> Long Leave (8+ days)</h4>
                        <p>For extended leave<?php echo $has_food_plan ? ' with food price reduction' : ''; ?></p>
                    </div>
                    <input type="hidden" name="leave_type" id="leave_type" required>
                </div>

                <div class="form-group">
                    <label for="start_date">Start Date</label>
                    <input type="date" id="start_date" name="start_date" class="form-control" required
                           min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                </div>

                <div class="form-group">
                    <label for="end_date">End Date</label>
                    <input type="date" id="end_date" name="end_date" class="form-control" required
                           min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                </div>

                <div class="form-group">
                    <label for="reason">Reason for Leave</label>
                    <textarea id="reason" name="reason" class="form-control" rows="4" placeholder="Enter your reason for leave..." required></textarea>
                </div>

                <?php if ($has_food_plan): ?>
                    <div class="price-calculation" id="priceCalculation" style="display: none;">
                        <h4><i class="fas fa-calculator"></i> Price Calculation</h4>
                        <div class="price-row">
                            <span>Monthly Food Price:</span>
                            <span id="monthlyFoodPrice">Rs<?php echo $monthly_food_price; ?></span>
                        </div>
                        <div class="price-row">
                            <span>Daily Rate:</span>
                            <span id="dailyRate">Rs<?php echo number_format($daily_rate, 2); ?></span>
                        </div>
                        <div class="price-row">
                            <span>Leave Days:</span>
                            <span id="leaveDays">0</span>
                        </div>
                        <div class="price-row">
                            <span>Food Price Reduction:</span>
                            <span id="priceReduction">Rs0.00</span>
                        </div>
                        <div class="price-row total">
                            <span>Adjusted Food Price:</span>
                            <span id="adjustedPrice">Rs<?php echo $monthly_food_price; ?></span>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="price-calculation" id="priceCalculation" style="display: none;">
                        <h4><i class="fas fa-info-circle"></i> Leave Information</h4>
                        <div class="price-row">
                            <span>Food Plan Status:</span>
                            <span style="color: #f39c12;">No Active Food Plan</span>
                        </div>
                        <div class="price-row">
                            <span>Price Reduction:</span>
                            <span style="color: #f39c12;">Not Applicable</span>
                        </div>
                        <div class="price-row info">
                            <span>Note:</span>
                            <span>No food charges to reduce during leave period</span>
                        </div>
                    </div>
                <?php endif; ?>

                <button type="submit" name="submit_leave" class="btn-submit">
                    <i class="fas fa-paper-plane"></i> Submit Application
                </button>
            </form>
        </div>

        <!-- Existing Applications Section -->
        <?php if (mysqli_num_rows($existing_applications) > 0): ?>
            <div class="section existing-applications">
                <div class="section-header">
                    <h3><i class="fas fa-history"></i> Previous Applications</h3>
                </div>
                
                <?php while ($app = mysqli_fetch_assoc($existing_applications)): ?>
                    <div class="application-card">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <h5 style="margin: 0; color: #003366;">
                                <i class="fas fa-calendar"></i> 
                                <?php echo date('M d, Y', strtotime($app['start_date'])); ?> - 
                                <?php echo date('M d, Y', strtotime($app['end_date'])); ?>
                            </h5>
                            <span class="status-badge status-<?php echo strtolower($app['status']); ?>">
                                <?php echo ucfirst($app['status']); ?>
                            </span>
                        </div>
                        <p style="margin: 5px 0; color: #666;">
                            <strong>Type:</strong> <?php echo ucfirst($app['leave_type']); ?> Leave
                        </p>
                        <p style="margin: 5px 0; color: #666;">
                            <strong>Student Reason:</strong> <?php echo htmlspecialchars($app['remarks']); ?>
                        </p>
                        <?php if (!empty($app['manager_remarks'])): ?>
                            <p style="margin: 5px 0; color: #856404; background: #fff3cd; padding: 8px; border-radius: 4px; border-left: 4px solid #ffc107;">
                                <strong><i class="fas fa-user-tie"></i> Manager Remarks:</strong> <?php echo htmlspecialchars($app['manager_remarks']); ?>
                            </p>
                        <?php endif; ?>
                        <?php if ($app['status'] === 'approved' && $app['estimated_reduction'] > 0): ?>
                            <p style="margin: 5px 0; color: #28a745; font-weight: 600; font-size: 1.1rem;">
                                <strong><i class="fas fa-piggy-bank"></i> Savings:</strong> 
                                Rs<?php echo number_format($app['estimated_reduction'], 2); ?>
                            </p>
                        <?php endif; ?>
                        <p style="margin: 5px 0; color: #666; font-size: 0.9rem;">
                            <strong>Applied:</strong> <?php echo date('M d, Y h:i A', strtotime($app['applied_date'])); ?>
                        </p>
                        <?php if (!empty($app['approved_by'])): ?>
                            <p style="margin: 5px 0; color: #666; font-size: 0.9rem;">
                                <strong>Processed by:</strong> <?php echo htmlspecialchars($app['approved_by']); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
        
        <?php else: ?>
            <!-- No Hostel Assigned Message -->
            <div class="section">
                <div class="alert alert-warning" style="text-align: center; padding: 30px; border-radius: 10px;">
                    <h3 style="color: #856404; margin-bottom: 15px;">
                        <i class="fas fa-exclamation-triangle"></i> No Hostel Assigned
                    </h3>
                    <p style="color: #856404; font-size: 1.1rem; margin-bottom: 20px;">
                        You haven't been assigned to any hostel yet. Please contact the hostel administrator or apply for hostel accommodation first.
                    </p>
                    <a href="services.php" class="btn btn-primary" style="background: #003366; border: none; padding: 10px 25px; border-radius: 25px;">
                        <i class="fas fa-home"></i> Apply for Hostel
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="py-5" style="background:#36454F;">
        <div class="container py-md-5">
            <div class="footer-logo mb-5 text-center">
                <a class="navbar-brand" href="home.php">Peaceful Living for Young <span class="display"> Scholars</span></a>
            </div>
            <div class="footer-grid">
                <div class="list-footer">
                    <ul class="footer-nav text-center">
                        <li>
                            <a href="home.php">Home</a>
                        </li>
                        <li>
                            <a href="services.php">Hostels</a>
                        </li>
                        <li>
                            <a href="contact.php">Contact</a>
                        </li>
                        <li>
                            <a href="profile.php">Profile</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script type="text/javascript" src="web_home/js/jquery-2.2.3.min.js"></script>
    <script type="text/javascript" src="web_home/js/bootstrap.js"></script>
    <script type="text/javascript" src="web_home/js/SmoothScroll.min.js"></script>
    <script type="text/javascript" src="web_home/js/move-top.js"></script>
    <script type="text/javascript" src="web_home/js/easing.js"></script>

    <script>
        function selectLeaveType(type) {
            // Remove selected class from all cards
            document.querySelectorAll('.leave-type-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Add selected class to clicked card
            event.currentTarget.classList.add('selected');
            
            // Set hidden input value
            document.getElementById('leave_type').value = type;
            
            // Show price calculation
            document.getElementById('priceCalculation').style.display = 'block';
        }

        // Dynamic date validation
        document.addEventListener('DOMContentLoaded', function() {
            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');
            
            // Update end date minimum when start date changes
            startDateInput.addEventListener('change', function() {
                const startDate = new Date(this.value);
                if (startDate) {
                    // Set minimum end date to same as start date (allow same-day leave)
                    const minEndDate = new Date(startDate);
                    // Don't add 1 day, allow same-day leave
                    // const nextDay = new Date(startDate);
                    // nextDay.setDate(nextDay.getDate() + 1);
                    // const minEndDate = nextDay.toISOString().split('T')[0];
                    endDateInput.min = minEndDate.toISOString().split('T')[0];
                    
                    // Clear end date if it's now invalid
                    if (endDateInput.value && new Date(endDateInput.value) < startDate) {
                        endDateInput.value = '';
                        endDateInput.style.borderColor = '#e9ecef';
                    }
                }
            });
            
            // Validate dates on change
            function validateDates() {
                const startDate = new Date(startDateInput.value);
                const endDate = new Date(endDateInput.value);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                
                const tomorrow = new Date(today);
                tomorrow.setDate(tomorrow.getDate() + 1);
                
                // Student end date from PHP
                const studentEndDate = new Date('<?php echo $student_data['end_date'] ?? ''; ?>');
                
                // Maximum future date (6 months)
                const maxFutureDate = new Date();
                maxFutureDate.setMonth(maxFutureDate.getMonth() + 6);
                
                // Clear previous error states
                startDateInput.style.borderColor = '#e9ecef';
                endDateInput.style.borderColor = '#e9ecef';
                
                // Remove existing error messages
                const existingErrors = document.querySelectorAll('.date-error');
                existingErrors.forEach(error => error.remove());
                
                let isValid = true;
                let errorMessage = '';
                
                // Validate start date
                if (startDateInput.value) {
                    // Convert dates to YYYY-MM-DD format for proper comparison
                    const startDateStr = startDate.toISOString().split('T')[0];
                    const tomorrowStr = tomorrow.toISOString().split('T')[0];
                    
                    // Check 1: Start date must be from tomorrow onwards (not past or present)
                    if (startDateStr < tomorrowStr) {
                        isValid = false;
                        errorMessage = 'Start date must be from tomorrow onwards';
                    }
                    
                    // Check 2: End date must be after or same as start date (allow same-day leave)
                    if (endDate < startDate) {
                        isValid = false;
                        errorMessage = 'End date must be after or same as start date';
                    }
                    
                    // Check 3: Leave period cannot exceed student's hostel end date
                    if (studentEndDate && endDate > studentEndDate) {
                        isValid = false;
                        errorMessage = 'Leave end date cannot exceed your hostel allocation end date (' + 
                            studentEndDate.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) + ')';
                    }
                    
                    // Check 4: Start date cannot be too far in future
                    if (startDate > maxFutureDate) {
                        isValid = false;
                        errorMessage = 'Start date cannot be more than 6 months in advance';
                    }
                }
                
                // Validate end date
                if (startDateInput.value && endDateInput.value && endDate < startDate) {
                    if (!errorMessage) {
                        errorMessage = 'End date must be after or same as start date';
                    }
                    endDateInput.style.borderColor = '#dc3545';
                    isValid = false;
                }
                
                // Show error message if validation fails
                if (!isValid && errorMessage) {
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'date-error';
                    errorDiv.style.color = '#dc3545';
                    errorDiv.style.fontSize = '0.875rem';
                    errorDiv.style.marginTop = '5px';
                    errorDiv.textContent = errorMessage;
                    
                    if (startDateInput.style.borderColor === '#dc3545') {
                        startDateInput.parentNode.appendChild(errorDiv);
                    } else {
                        endDateInput.parentNode.appendChild(errorDiv);
                    }
                }
                
                // Update price calculation if dates are valid
                if (isValid && startDateInput.value && endDateInput.value) {
                    calculatePrice();
                } else {
                    // Reset price calculation if invalid
                    document.getElementById('leaveDays').textContent = '0';
                    <?php if ($has_food_plan): ?>
                        document.getElementById('priceReduction').textContent = 'Rs0.00';
                        document.getElementById('adjustedPrice').textContent = 'Rs<?php echo $monthly_food_price; ?>';
                    <?php endif; ?>
                }
                
                return isValid;
            }

            async function checkOverlappingDates(startDate, endDate) {
                try {
                    const response = await fetch('check_leave_overlap.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `start_date=${startDate}&end_date=${endDate}&student_id=<?php echo $student_id; ?>`
                    });
                    
                    const result = await response.json();
                    return result.hasOverlap;
                } catch (error) {
                    console.error('Error checking overlapping dates:', error);
                    return false;
                }
            }

            startDateInput.addEventListener('change', validateDates);
            endDateInput.addEventListener('change', validateDates);
        });

        function calculatePrice() {
            const startDate = new Date(document.getElementById('start_date').value);
            const endDate = new Date(document.getElementById('end_date').value);
            
            if (startDate && endDate && startDate <= endDate) {
                const timeDiff = endDate - startDate;
                const daysDiff = Math.ceil(timeDiff / (1000 * 60 * 60 * 24)) + 1;
                
                <?php if ($has_food_plan): ?>
                    const dailyRate = <?php echo $daily_rate; ?>;
                    const reduction = daysDiff * dailyRate;
                    const adjustedPrice = <?php echo $monthly_food_price; ?> - reduction;
                    
                    document.getElementById('leaveDays').textContent = daysDiff;
                    document.getElementById('priceReduction').textContent = 'Rs' + reduction.toFixed(2);
                    document.getElementById('adjustedPrice').textContent = 'Rs' + adjustedPrice.toFixed(2);
                <?php else: ?>
                    document.getElementById('leaveDays').textContent = daysDiff;
                    // For students without food plan, no price reduction is calculated
                    // The display already shows "Not Applicable" for price reduction
                <?php endif; ?>
            }
        }

        // Form submission validation
        document.querySelector('form').addEventListener('submit', async function(e) {
            const leaveType = document.getElementById('leave_type').value;
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            const reason = document.getElementById('reason').value;
            
            let isValid = true;
            let errorMessage = '';
            
            // Validate leave type
            if (!leaveType) {
                errorMessage = 'Please select a leave type';
                isValid = false;
            }
            
            // Validate dates
            if (!validateDates()) {
                isValid = false;
            }
            
            // Check for overlapping dates if dates are selected
            if (isValid && startDate && endDate) {
                const hasOverlap = await checkOverlappingDates(startDate, endDate);
                if (hasOverlap) {
                    errorMessage = 'You already have approved leave during this period. Please select different dates.';
                    isValid = false;
                }
            }
            
            // Validate reason
            if (!reason.trim()) {
                errorMessage = errorMessage ? errorMessage + '. Please provide a reason for leave.' : 'Please provide a reason for leave.';
                isValid = false;
            }
            
            // Show error message if validation fails
            if (!isValid) {
                e.preventDefault();
                alert(errorMessage || 'Please fill in all required fields correctly.');
            }
        });

        // Add event listeners for real-time validation
        document.getElementById('start_date').addEventListener('change', function() {
            validateDates();
            // Set minimum end date based on start date (allow same-day leave)
            const startDate = new Date(this.value);
            if (this.value) {
                const minEndDate = new Date(startDate);
                // Don't add 1 day, allow same-day leave
                // minEndDate.setDate(minEndDate.getDate() + 1);
                document.getElementById('end_date').min = minEndDate.toISOString().split('T')[0];
            }
        });
        
        document.getElementById('end_date').addEventListener('change', validateDates);
        
        // Set minimum start date to tomorrow on page load
        window.addEventListener('load', function() {
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            const tomorrowStr = tomorrow.toISOString().split('T')[0];
            document.getElementById('start_date').min = tomorrowStr;
            document.getElementById('start_date').placeholder = 'Select date from ' + tomorrowStr;
        });
    </script>

</body>
</html>
