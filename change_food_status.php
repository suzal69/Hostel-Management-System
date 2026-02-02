<?php
require 'includes/config.inc.php';
require_once 'includes/manager_header.php';

// Check if user is logged in and hostel_id is set
if (!isset($_SESSION['username']) || !isset($_SESSION['hostel_id'])) {
    header('Location: login-hostel_manager.php');
    exit();
}

$hostel_id = $_SESSION['hostel_id'];
$success_message = '';
$error_message = '';

// Get student ID from URL parameter
if (!isset($_GET['student_id']) || empty($_GET['student_id'])) {
    header('Location: allocated_rooms.php');
    exit();
}

$student_id = $_GET['student_id'];

// Get current student information and food plan
$query = "SELECT s.*, ba.allocation_id, ba.include_food, ba.food_plan, ba.allocation_price, r.Room_No, h.Hostel_name
          FROM Student s
          LEFT JOIN bed_allocation ba ON s.Student_id = ba.student_id AND ba.is_active = 1
          LEFT JOIN Room r ON s.Room_id = r.Room_id
          LEFT JOIN Hostel h ON s.Hostel_id = h.Hostel_id
          WHERE s.Student_id = ? AND s.Hostel_id = ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ss", $student_id, $hostel_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    header('Location: allocated_rooms.php');
    exit();
}

$student = mysqli_fetch_assoc($result);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $include_food = $_POST['include_food'] ?? 0;
    $food_plan = $_POST['food_plan'] ?? '';
    $allocation_id = $student['allocation_id'];
    
    // Validate inputs
    if ($include_food == 1 && empty($food_plan)) {
        $error_message = "Please select a food plan when including food service.";
    } else {
        // Update the bed allocation
        $update_query = "UPDATE bed_allocation 
                        SET include_food = ?, food_plan = ? 
                        WHERE allocation_id = ?";
        
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "isi", $include_food, $food_plan, $allocation_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $success_message = "Food status updated successfully!";
            
            // Send message to student about food status change
            $current_date = date('Y-m-d');
            $current_time = date('h:i A');
            
            if ($include_food == 1) {
                $subject = "Food Service Activated";
                $message_text = "Your food service has been activated successfully! Food Plan: " . ucfirst($food_plan) . ". Room: {$student['Room_No']}, Hostel: {$student['Hostel_name']}. Please check your student portal for details.";
            } else {
                $subject = "Food Service Deactivated";
                $message_text = "Your food service has been deactivated. Room: {$student['Room_No']}, Hostel: {$student['Hostel_name']}. Please check your student portal for details.";
            }
            
            $query_insert_message = "INSERT INTO message (sender_id, receiver_id, hostel_id, subject_h, message, msg_date, msg_time, read_status) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, 0)";
            $stmt_message = mysqli_prepare($conn, $query_insert_message);
            $sender_id = $_SESSION['hostel_man_id'] ?? 'admin';
            mysqli_stmt_bind_param($stmt_message, "ssissss", $sender_id, $student_id, $hostel_id, $subject, $message_text, $current_date, $current_time);
            mysqli_stmt_execute($stmt_message);
            mysqli_stmt_close($stmt_message);
            
            // Refresh student data
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ss", $student_id, $hostel_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $student = mysqli_fetch_assoc($result);
        } else {
            $error_message = "Error updating food status. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<title>Change Food Status</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta charset="utf-8">

<!-- css files -->
<link rel="stylesheet" href="web_home/css_home/bootstrap.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<style>
    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
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
    .student-info {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 25px;
        border-left: 4px solid #003366;
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
        font-size: 0.95rem;
    }
    .form-control:focus {
        border-color: #003366;
        outline: none;
        box-shadow: 0 0 0 0.2rem rgba(0, 51, 102, 0.25);
    }
    .btn {
        padding: 10px 25px;
        border: none;
        border-radius: 25px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-right: 10px;
        text-decoration: none;
        display: inline-block;
    }
    .btn-primary {
        background: linear-gradient(135deg, #003366 0%, #004080 100%);
        color: white;
    }
    .btn-secondary {
        background: #6c757d;
        color: white;
    }
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
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
    .current-status {
        font-size: 1.1rem;
        padding: 10px;
        background: #e9ecef;
        border-radius: 5px;
        margin-bottom: 15px;
    }
    .food-plan-badge {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 600;
        margin-left: 10px;
    }
    .has-food {
        background-color: #d4edda;
        color: #155724;
    }
    .no-food {
        background-color: #f8d7da;
        color: #721c24;
    }
    
    /* Bootstrap dropdown styles to match manager_header.php */
    .dropdown-menu {
        background-color: #003366;
        border: 1px solid #004080;
        box-shadow: 0 8px 16px rgba(0,0,0,0.2);
    }
    .dropdown-menu .dropdown-item {
        color: #ffffff;
        padding: 8px 16px;
        transition: all 0.2s ease;
    }
    .dropdown-menu .dropdown-item:hover {
        background-color: #001f4d;
        color: #ffcc00;
    }
    .dropdown-menu .dropdown-item:focus {
        background-color: #001f4d;
        color: #ffcc00;
        outline: none;
    }
    .dropdown-menu .dropdown-item:active {
        background-color: #002244;
        color: #ffcc00;
    }
</style>

</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-utensils"></i> Change Food Status</h1>
            <p>Update food service status for student</p>
        </div>
        
        <!-- Student Information -->
        <div class="section">
            <div class="section-header">
                <h3><i class="fas fa-user"></i> Student Information</h3>
            </div>
            
            <div class="student-info">
                <p><strong>Name:</strong> <?php echo htmlspecialchars($student['Fname'] . ' ' . $student['Lname']); ?></p>
                <p><strong>Student ID:</strong> <?php echo htmlspecialchars($student['Student_id']); ?></p>
                <p><strong>Contact:</strong> <?php echo htmlspecialchars($student['Mob_no']); ?></p>
                <p><strong>Room:</strong> <?php echo htmlspecialchars($student['Room_No'] ?? 'Not Assigned'); ?></p>
                <p><strong>Hostel:</strong> <?php echo htmlspecialchars($student['Hostel_name']); ?></p>
                
                <div class="current-status">
                    <strong>Current Food Status:</strong>
                    <?php if ($student['include_food'] == 1 && !empty($student['food_plan'])): ?>
                        <span class="food-plan-badge has-food">
                            <?php echo ucfirst($student['food_plan']); ?> Plan
                        </span>
                    <?php else: ?>
                        <span class="food-plan-badge no-food">
                            No Food Plan
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Update Form -->
        <div class="section">
            <div class="section-header">
                <h3><i class="fas fa-edit"></i> Update Food Status</h3>
            </div>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <form action="change_food_status.php?student_id=<?php echo htmlspecialchars($student_id); ?>" method="POST">
                <div class="form-group">
                    <label for="include_food">Include Food Service</label>
                    <select name="include_food" id="include_food" class="form-control" required>
                        <option value="0" <?php echo ($student['include_food'] == 0) ? 'selected' : ''; ?>>No Food Service</option>
                        <option value="1" <?php echo ($student['include_food'] == 1) ? 'selected' : ''; ?>>Include Food Service</option>
                    </select>
                </div>
                
                <div class="form-group" id="food_plan_group">
                    <label for="food_plan">Food Plan</label>
                    <select name="food_plan" id="food_plan" class="form-control">
                        <option value="">Select Food Plan</option>
                        <option value="basic" <?php echo ($student['food_plan'] == 'basic') ? 'selected' : ''; ?>>Basic</option>
                        <option value="standard" <?php echo ($student['food_plan'] == 'standard') ? 'selected' : ''; ?>>Standard</option>
                        <option value="premium" <?php echo ($student['food_plan'] == 'premium') ? 'selected' : ''; ?>>Premium</option>
                    </select>
                </div>
                
                <div style="margin-top: 30px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Food Status
                    </button>
                    <a href="allocated_rooms.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Allocated Rooms
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Show/hide food plan based on include_food selection
        document.getElementById('include_food').addEventListener('change', function() {
            const foodPlanGroup = document.getElementById('food_plan_group');
            const foodPlanSelect = document.getElementById('food_plan');
            
            if (this.value === '1') {
                foodPlanGroup.style.display = 'block';
                foodPlanSelect.required = true;
            } else {
                foodPlanGroup.style.display = 'none';
                foodPlanSelect.required = false;
                foodPlanSelect.value = '';
            }
        });
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            const includeFood = document.getElementById('include_food');
            includeFood.dispatchEvent(new Event('change'));
        });
    </script>
    
    <!-- Bootstrap JavaScript for dropdowns -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.min.js"></script>
</body>
</html>