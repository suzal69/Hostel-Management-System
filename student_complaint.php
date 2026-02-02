<?php
session_start();
require_once 'includes/config.inc.php';
require_once 'includes/user_header.php';
require_once 'includes/notification_helper.php';

// Check if student is logged in (login stores Student_id in $_SESSION['roll'])
if (!isset($_SESSION['roll'])) {
    header("Location: login.php");
    exit();
}

$studentId = $_SESSION['roll'];
$successMessage = '';
$errorMessage = '';

if (isset($_GET['success'])) {
    if ($_GET['success'] == 'complaintadded') {
        $successMessage = '✓ Your complaint has been submitted successfully!';
    }
} elseif (isset($_GET['error'])) {
    $errorMessage = match($_GET['error']) {
        'failedtoadd' => 'Failed to submit complaint. Please try again.',
        'invalidinput' => 'Invalid input. Please fill all fields.',
        'sqlerror' => 'A database error occurred. Please try again later.',
        'nohostelfound' => 'Could not determine your hostel. Please contact support.',
        'nofoodplan' => 'You must have an active food plan to submit a Food Service complaint. Please contact the hostel administrator to add a food plan to your allocation.',
        default => 'An error occurred.'
    };
}

// Mark complaint notifications as read when student visits this page
markComplaintNotificationsRead($studentId, $conn);

// Get student's hostel assignment
$studentHostel = null;
$sql_hostel = "SELECT Hostel_id, Room_id FROM Student WHERE Student_id = ?";
$stmt_hostel = mysqli_stmt_init($conn);
if (mysqli_stmt_prepare($stmt_hostel, $sql_hostel)) {
    mysqli_stmt_bind_param($stmt_hostel, "s", $studentId);
    mysqli_stmt_execute($stmt_hostel);
    $result_hostel = mysqli_stmt_get_result($stmt_hostel);
    if ($row_hostel = mysqli_fetch_assoc($result_hostel)) {
        $studentHostel = $row_hostel;
    }
    mysqli_stmt_close($stmt_hostel);
}

// Fetch student's complaints (student_id is stored as string/varchar)
$studentComplaints = [];
$sql = "SELECT * FROM complaints WHERE student_id = ? ORDER BY submission_date DESC";
$stmt = mysqli_stmt_init($conn);
if (mysqli_stmt_prepare($stmt, $sql)) {
    mysqli_stmt_bind_param($stmt, "s", $studentId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $studentComplaints[] = $row;
    }
    mysqli_stmt_close($stmt);
}

// Calculate personal statistics
$totalPersonalTickets = count($studentComplaints);
$resolvedPersonalTickets = count(array_filter($studentComplaints, fn($c) => $c['status'] === 'resolved'));
$resolvedComplaintCount = $resolvedPersonalTickets;
$totalResolutionTime = 0;
foreach ($studentComplaints as $complaint) {
    if ($complaint['status'] === 'resolved' && $complaint['submission_date'] && $complaint['resolve_date']) {
        $submissionDateTime = new DateTime($complaint['submission_date']);
        $resolveDateTime = new DateTime($complaint['resolve_date']);
        $interval = $submissionDateTime->diff($resolveDateTime);
        $totalResolutionTime += $interval->days;
    }
}

$personalComplaintResolutionRate = ($totalPersonalTickets > 0) ? ($resolvedPersonalTickets / $totalPersonalTickets) * 100 : 0;
$personalAvgResolutionTime = ($resolvedComplaintCount > 0) ? $totalResolutionTime / $resolvedComplaintCount : 0;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Complaints</title>
    <link rel="stylesheet" href="web_home/css_home/bootstrap.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
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
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: linear-gradient(135deg, #003366 0%, #004080 100%);
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
        .complaint-card {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
        }
        .complaint-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .complaint-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e9ecef;
        }
        .complaint-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #003366;
            margin: 0;
        }
        .complaint-status {
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
        .status-resolved {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }
        .status-in-progress {
            background-color: #cce7ff;
            color: #004085;
            border-color: #b3d7ff;
        }
        .complaint-description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 10px;
        }
        .complaint-meta {
            font-size: 0.85rem;
            color: #999;
        }
        @media (max-width: 768px) {
            .header h1 {
                font-size: 2rem;
            }
            .section-header h3 {
                font-size: 1.5rem;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-comment-alt"></i> My Complaints</h1>
            <p>Submit and track your complaints</p>
        </div>

        <?php if ($studentHostel && $studentHostel['Hostel_id'] && $studentHostel['Room_id']): ?>
        <!-- Statistics Section -->
        <div class="section">
            <div class="section-header">
                <h3><i class="fas fa-chart-bar"></i> Your Statistics</h3>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo round($personalComplaintResolutionRate, 2); ?>%</div>
                    <div class="stat-label">Resolution Rate</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo round($personalAvgResolutionTime, 2); ?></div>
                    <div class="stat-label">Avg Resolution Days</div>
                </div>
            </div>
        </div>

        <!-- Submit Complaint Section -->
        <div class="section">
            <div class="section-header">
                <h3><i class="fas fa-plus-circle"></i> Submit New Complaint</h3>
            </div>

        <?php
            if ($successMessage) {
                echo "<div class='alert alert-success'>$successMessage</div>";
            }
            if ($errorMessage) {
                echo "<div class='alert alert-danger'>$errorMessage</div>";
            }
            ?>
            
            <form action="includes/add_complaint.inc.php" method="POST">
                <div class="form-group">
                    <label for="category">Category</label>
                    <select id="category" name="category" class="form-control" required>
                        <option value="">Select Category</option>
                        <option value="Plumbing">Plumbing</option>
                        <option value="Electrical">Electrical</option>
                        <option value="Internet">Internet</option>
                        <option value="Room Maintenance">Room Maintenance</option>
                        <option value="Food Service">Food Service</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="5" placeholder="Describe your complaint in detail..." required></textarea>
                </div>
                <button type="submit" class="btn-submit">
                    <i class="fas fa-paper-plane"></i> Submit Complaint
                </button>
            </form>
        </div>

        <!-- Past Complaints Section -->
        <div class="section">
            <div class="section-header">
                <h3><i class="fas fa-history"></i> My Past Complaints</h3>
            </div>
            
            <?php if (empty($studentComplaints)): ?>
                <div style="text-align: center; padding: 40px; color: #666;">
                    <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.5;"></i>
                    <p>You have not submitted any complaints yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($studentComplaints as $complaint): ?>
                    <div class="complaint-card">
                        <div class="complaint-header">
                            <div class="complaint-title">
                                <i class="fas fa-tag"></i> 
                                <?php echo htmlspecialchars($complaint['category']); ?>
                            </div>
                            <span class="complaint-status status-<?php echo strtolower(str_replace(' ', '-', $complaint['status'])); ?>">
                                <?php echo ucfirst($complaint['status']); ?>
                            </span>
                        </div>
                        <div class="complaint-description">
                            <?php echo htmlspecialchars($complaint['description']); ?>
                        </div>
                        <div class="complaint-meta">
                            <i class="fas fa-calendar"></i> 
                            Submitted: <?php echo date('M d, Y h:i A', strtotime($complaint['submission_date'])); ?>
                            <?php if ($complaint['status'] === 'resolved' && $complaint['resolve_date']): ?>
                                | <i class="fas fa-check-circle"></i> 
                                Resolved: <?php echo date('M d, Y h:i A', strtotime($complaint['resolve_date'])); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
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
                    <a href="services.php" class="btn-submit" style="background: #003366; border: none; padding: 10px 25px; border-radius: 25px;">
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
        // Check food plan status when Food Service is selected
        document.getElementById('category').addEventListener('change', function() {
            const category = this.value;
            const submitButton = document.querySelector('.btn-submit');
            const form = document.querySelector('form');
            
            // Remove any existing food plan warnings
            const existingWarning = document.getElementById('food-plan-warning');
            if (existingWarning) {
                existingWarning.remove();
            }
            
            // Reset submit button state
            submitButton.disabled = false;
            submitButton.style.opacity = '1';
            submitButton.style.cursor = 'pointer';
            
            if (category === 'Food Service') {
                // Check if student has food plan via AJAX
                fetch('check_food_plan.php')
                    .then(response => response.json())
                    .then(data => {
                        if (!data.hasFoodPlan) {
                            // Show warning and disable submit
                            const warning = document.createElement('div');
                            warning.id = 'food-plan-warning';
                            warning.className = 'alert alert-warning';
                            warning.style.marginTop = '10px';
                            warning.innerHTML = '<i class="fas fa-exclamation-triangle"></i> <strong>Food Plan Required:</strong> You must have an active food plan to submit a Food Service complaint. Please contact the hostel administrator to add a food plan to your allocation.';
                            
                            this.parentNode.parentNode.insertBefore(warning, this.parentNode.nextSibling);
                            
                            submitButton.disabled = true;
                            submitButton.style.opacity = '0.6';
                            submitButton.style.cursor = 'not-allowed';
                        }
                    })
                    .catch(error => {
                        console.error('Error checking food plan:', error);
                    });
            }
        });
        
        // Form submission validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const category = document.getElementById('category').value;
            
            if (category === 'Food Service') {
                // Double-check on submission
                fetch('check_food_plan.php')
                    .then(response => response.json())
                    .then(data => {
                        if (!data.hasFoodPlan) {
                            e.preventDefault();
                            alert('You must have an active food plan to submit a Food Service complaint. Please contact the hostel administrator to add a food plan to your allocation.');
                        }
                    })
                    .catch(error => {
                        console.error('Error checking food plan:', error);
                    });
            }
        });
    </script>

</body>
</html>
