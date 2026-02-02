<?php
// get_student_details.php
// AJAX endpoint to fetch student details

require_once 'config.inc.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if manager is logged in
if (!isset($_SESSION['hostel_man_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get the roll number from POST request
$roll_no = $_POST['roll_no'] ?? '';

if (empty($roll_no)) {
    echo json_encode(['success' => false, 'message' => 'Roll number is required']);
    exit();
}

// Get manager's hostel ID
$hostel_id = $_SESSION['hostel_id'] ?? null;

if (!$hostel_id) {
    echo json_encode(['success' => false, 'message' => 'Hostel ID not found']);
    exit();
}

// Fetch student details with room and bed information
$query = "SELECT s.Student_id, s.Fname, s.Lname, s.Mob_no, s.Dept, s.Year_of_study, r.Room_No, r.Room_id, ba.bed_number, ba.start_date, ba.end_date 
          FROM Student s 
          LEFT JOIN Room r ON s.Room_id = r.Room_id 
          LEFT JOIN bed_allocation ba ON s.Student_id = ba.student_id AND ba.is_active = 1
          WHERE s.Student_id = ? AND s.Hostel_id = ?";

$stmt = mysqli_prepare($conn, $query);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ss", $roll_no, $hostel_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        // Prepare student details
        $student_details = [
            'success' => true,
            'roll_no' => $row['Student_id'],
            'name' => $row['Fname'] . ' ' . $row['Lname'],
            'contact' => $row['Mob_no'],
            'department' => $row['Dept'],
            'year' => $row['Year_of_study'],
            'room_no' => $row['Room_No'] ?: 'Not Allocated',
            'room_id' => $row['Room_id'] ?: '',
            'bed_number' => $row['bed_number'] ?: '',
            'start_date' => $row['start_date'] ? date('M j, Y', strtotime($row['start_date'])) : '',
            'end_date' => $row['end_date'] ? date('M j, Y', strtotime($row['end_date'])) : ''
        ];
        
        echo json_encode($student_details);
    } else {
        echo json_encode(['success' => false, 'message' => 'Student not found']);
    }
    
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

mysqli_close($conn);
?>
