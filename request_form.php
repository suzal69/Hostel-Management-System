<?php
require 'includes/config.inc.php';

// Check if user is logged in BEFORE any output
if (!isset($_SESSION['roll'])) {
    header('Location: login.php');
    exit();
}

require_once 'includes/user_header.php';

// Get student's hostel information like services.php does
$student_id = $_SESSION['roll'] ?? '';
$hostel_name = 'Not assigned';

if (!empty($student_id)) {
    $query = "SELECT s.*, h.Hostel_name 
              FROM Student s
              LEFT JOIN Hostel h ON s.Hostel_id = h.Hostel_id
              WHERE s.Student_id = ?";
    
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $student_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $student_info = mysqli_fetch_assoc($result);
            $hostel_name = $student_info['Hostel_name'] ?? 'Not assigned';
        }
        mysqli_stmt_close($stmt);
    }
}

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_type = $_POST['request_type'] ?? '';
    $reason = $_POST['reason'] ?? '';
    $student_id = $_SESSION['roll'];
    $student_name = $_SESSION['fname'] . ' ' . $_SESSION['lname'];
    $hostel_id = $_SESSION['hostel_id'] ?? '';
    
    if (empty($request_type) || empty($reason)) {
        $error_message = "Please fill in all required fields.";
    } else {
        // Insert request into message table
        $query = "INSERT INTO message (sender_id, receiver_id, hostel_id, subject_h, message, msg_date, msg_time, read_status) 
                  VALUES (?, ?, ?, ?, ?, CURDATE(), CURTIME(), 0)";
        
        $sender_id = $_SESSION['roll'];
        $sender_name = $_SESSION['fname'] . ' ' . $_SESSION['lname'];
        
        // Get hostel manager ID for the student's hostel
        $manager_query = "SELECT Hostel_man_id FROM hostel_manager WHERE Hostel_id = ? LIMIT 1";
        $manager_stmt = mysqli_prepare($conn, $manager_query);
        $receiver_id = 'manager'; // fallback
        
        if ($manager_stmt) {
            mysqli_stmt_bind_param($manager_stmt, "i", $hostel_id);
            mysqli_stmt_execute($manager_stmt);
            $manager_result = mysqli_stmt_get_result($manager_stmt);
            if ($manager_row = mysqli_fetch_assoc($manager_result)) {
                $receiver_id = $manager_row['Hostel_man_id'];
            }
            mysqli_stmt_close($manager_stmt);
        }
        
        $subject = $request_type;
        
        $message_content = "Request Type: " . ucfirst($request_type) . "\n";
        $message_content .= "Student ID: " . $student_id . "\n";
        $message_content .= "Student Name: " . $student_name . "\n";
        $message_content .= "Reason: " . $reason;
        
        // Add preferred room information if it's a room change request
        if ($request_type === 'change_room' && !empty($_POST['preferred_room'])) {
            $preferred_room_id = $_POST['preferred_room'];
            // Get room number from database
            $room_query = "SELECT Room_No FROM Room WHERE Room_id = ?";
            $room_stmt = mysqli_prepare($conn, $room_query);
            if ($room_stmt) {
                mysqli_stmt_bind_param($room_stmt, "i", $preferred_room_id);
                mysqli_stmt_execute($room_stmt);
                $room_result = mysqli_stmt_get_result($room_stmt);
                if ($room_row = mysqli_fetch_assoc($room_result)) {
                    $message_content .= "\nPreferred Room: Room " . $room_row['Room_No'];
                }
                mysqli_stmt_close($room_stmt);
            }
        }
        
        // Add preferred food plan information if it's a food change request
        if ($request_type === 'change_food' && !empty($_POST['preferred_food_plan'])) {
            $preferred_food_plan = $_POST['preferred_food_plan'];
            $food_plan_names = [
                'basic' => 'Basic Plan (Rs 500/month)',
                'standard' => 'Standard Plan (Rs 1,500/month)',
                'premium' => 'Premium Plan (Rs 2,500/month)',
                'no_food' => 'No Food Service'
            ];
            $message_content .= "\nPreferred Food Plan: " . ($food_plan_names[$preferred_food_plan] ?? $preferred_food_plan);
        }
        
        $stmt = mysqli_prepare($conn, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sssss", $sender_id, $receiver_id, $hostel_id, $subject, $message_content);
            
            if (mysqli_stmt_execute($stmt)) {
                $success_message = "Your request has been submitted successfully! The manager will review it soon.";
            } else {
                $error_message = "Error submitting request. Please try again.";
            }
            mysqli_stmt_close($stmt);
        } else {
            $error_message = "Database error. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<title>Request Form</title>
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
    .request-type-card {
        border: 2px solid #e9ecef;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 15px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .request-type-card:hover {
        border-color: #003366;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    }
    .request-type-card.selected {
        border-color: #003366;
        background-color: #f8f9fa;
    }
    .request-type-card h4 {
        color: #003366;
        margin-bottom: 10px;
    }
    .request-type-card p {
        color: #666;
        margin: 0;
    }
    .student-info {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 25px;
        border-left: 4px solid #003366;
    }
</style>

</head>
<body>
    <br><br>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-file-alt"></i> Request Form</h1>
            <p>Submit a request for room change or food plan modification</p>
        </div>
        
        <!-- Student Information -->
        <div class="section">
            <div class="section-header">
                <h3><i class="fas fa-user"></i> Student Information</h3>
            </div>
            
            <div class="student-info">
                <p><strong>Name:</strong> <?php echo htmlspecialchars($_SESSION['fname'] . ' ' . $_SESSION['lname']); ?></p>
                <p><strong>Student ID:</strong> <?php echo htmlspecialchars($_SESSION['roll']); ?></p>
                <p><strong>Contact:</strong> <?php echo htmlspecialchars($_SESSION['mob_no'] ?? 'Not provided'); ?></p>
                <p><strong>Hostel:</strong> <?php echo htmlspecialchars($hostel_name); ?></p>
            </div>
        </div>
        
        <!-- Request Form -->
        <div class="section">
            <div class="section-header">
                <h3><i class="fas fa-edit"></i> Request Details</h3>
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
            
            <form action="request_form.php" method="POST">
                <div class="form-group">
                    <label><strong>Request Type</strong></label>
                    <div class="request-type-card" onclick="selectRequestType('change_room')">
                        <h4><i class="fas fa-exchange-alt"></i> Change Room</h4>
                        <p>Request to change your current room to a different room</p>
                    </div>
                    <div class="request-type-card" onclick="selectRequestType('change_food')">
                        <h4><i class="fas fa-utensils"></i> Change Food Plan</h4>
                        <p>Request to modify your current food plan or add/remove food service</p>
                    </div>
                    <input type="hidden" name="request_type" id="request_type" required>
                </div>
                
                <div class="form-group">
                    <label for="reason"><strong>Reason for Request</strong></label>
                    <textarea name="reason" id="reason" class="form-control" rows="5" placeholder="Please provide a detailed reason for your request..." required></textarea>
                </div>
                
                <!-- Room selection dropdown (shown only for room change requests) -->
                <div class="form-group" id="room_selection_group" style="display: none;">
                    <label for="preferred_room"><strong>Preferred Room</strong></label>
                    <select name="preferred_room" id="preferred_room" class="form-control">
                        <option value="">Select Preferred Room</option>
                    </select>
                    <small class="form-text text-muted">Select the room you would like to move to (if available)</small>
                </div>
                
                <!-- Food plan selection dropdown (shown only for food change requests) -->
                <div class="form-group" id="food_selection_group" style="display: none;">
                    <label for="preferred_food_plan"><strong>Preferred Food Plan</strong></label>
                    <select name="preferred_food_plan" id="preferred_food_plan" class="form-control">
                        <option value="">Select Preferred Food Plan</option>
                        <option value="basic">Basic Plan - Rs 500/month</option>
                        <option value="standard">Standard Plan - Rs 1,500/month</option>
                        <option value="premium">Premium Plan - Rs 2,500/month</option>
                        <option value="no_food">No Food Service</option>
                    </select>
                    <small class="form-text text-muted">Select your preferred food plan option</small>
                </div>
                
                <div style="margin-top: 30px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Submit Request
                    </button>
                    <a href="home.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Home
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function selectRequestType(type) {
            // Remove selected class from all cards
            document.querySelectorAll('.request-type-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Add selected class to clicked card
            event.currentTarget.classList.add('selected');
            
            // Set the hidden input value
            document.getElementById('request_type').value = type;
            
            // Show/hide selection dropdowns based on request type
            const roomSelectionGroup = document.getElementById('room_selection_group');
            const foodSelectionGroup = document.getElementById('food_selection_group');
            
            if (type === 'change_room') {
                roomSelectionGroup.style.display = 'block';
                foodSelectionGroup.style.display = 'none';
                loadAvailableRooms();
            } else if (type === 'change_food') {
                roomSelectionGroup.style.display = 'none';
                foodSelectionGroup.style.display = 'block';
            } else {
                roomSelectionGroup.style.display = 'none';
                foodSelectionGroup.style.display = 'none';
            }
        }
        
        // Function to load available rooms for the student
        function loadAvailableRooms() {
            $.ajax({
                url: 'includes/get_student_available_rooms.php',
                method: 'POST',
                dataType: 'json',
                success: function(data) {
                    var roomSelect = $('#preferred_room');
                    roomSelect.html('<option value="">Select Preferred Room</option>');
                    
                    if (data.success && data.rooms) {
                        $.each(data.rooms, function(index, room) {
                            roomSelect.append('<option value="' + room.Room_id + '">' + 
                                           'Room ' + room.Room_No + ' (' + room.available_beds + ' bed(s) available)</option>');
                        });
                    } else if (data.error) {
                        roomSelect.append('<option value="">No available rooms found</option>');
                        console.error('Error loading rooms:', data.error);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Error loading rooms:", error);
                    $('#preferred_room').html('<option value="">Error loading rooms</option>');
                }
            });
        }
    </script>
</body>
</html>
