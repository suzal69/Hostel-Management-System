<?php
require 'config.inc.php';

// --- START: MANUAL OVERRIDES FOR LOCALHOST TESTING ---
// Check if the script is being accessed directly without a POST request (e.g., in a browser)
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !isset($_POST['roll_no'])) {
    // 1. Manually set a test roll_no for debugging
    $_POST['roll_no'] = '20790520'; // <--- CHANGE THIS FOR DIFFERENT STUDENTS

    // 2. Manually set a test session hostel_id for debugging
    // This value must match the Hostel_id of the test student above.
    // Ensure you uncomment 'session_start()' if you use this block.
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['hostel_id'] = 1; // <--- CHANGE THIS IF TESTING A DIFFERENT HOSTEL

    // Optional: Log the simulated action
    error_log("get_student_room.php: DEBUG MODE ACTIVATED. Using simulated POST data.");
}
// --- END: MANUAL OVERRIDES FOR LOCALHOST TESTING ---


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_POST['roll_no'])) {
    $roll_no = $_POST['roll_no'];
    $hostel_id = $_SESSION['hostel_id']; // Use a variable for clarity in logging
    
    error_log("get_student_room.php: roll_no = " . $roll_no . ", hostel_id = " . $hostel_id);

    // Get the room number and bed allocation for the student (dates from bed_allocation)
    $query = "SELECT r.Room_No, ba.start_date, ba.end_date, r.Room_id, ba.bed_number
              FROM Student s
              JOIN Room r ON s.Room_id = r.Room_id
              LEFT JOIN bed_allocation ba ON s.Student_id = ba.student_id AND ba.is_active = 1
              WHERE s.Student_id = ?
              AND s.Hostel_id = ?";
    error_log("get_student_room.php: SQL Query = " . $query);

    $stmt = mysqli_prepare($conn, $query);
    if ($stmt === false) {
        error_log("get_student_room.php: mysqli_prepare failed: " . mysqli_error($conn));
        // 3. For localhost viewing, print a friendly error
        echo "Error preparing statement: " . mysqli_error($conn);
        exit();
    }
    
    mysqli_stmt_bind_param($stmt, "si", $roll_no, $hostel_id);
    error_log("get_student_room.php: Bound parameters: roll_no = " . $roll_no . " (type s), hostel_id = " . $hostel_id . " (type i)");
    
    if (mysqli_stmt_execute($stmt) === false) {
        error_log("get_student_room.php: mysqli_stmt_execute failed: " . mysqli_stmt_error($stmt));
        // 4. For localhost viewing, print a friendly execution error
        echo "Error executing statement: " . mysqli_stmt_error($stmt);
        exit();
    }
    
    $result = mysqli_stmt_get_result($stmt);

    if ($result) {
        $num_rows = mysqli_num_rows($result);
        error_log("get_student_room.php: Number of rows found = " . $num_rows);
    }

    if ($row = mysqli_fetch_assoc($result)) {
        error_log("get_student_room.php: Found room: " . $row['Room_No'] . ", Full row data: " . print_r($row, true));
        
        // 5. Echo the JSON output clearly and also display the details in a readable format for localhost
        $json_output = json_encode([
            'room_no' => $row['Room_No'], 
            'start_date' => $row['start_date'], 
            'end_date' => $row['end_date'], 
            'room_id' => $row['Room_id'],
            'bed_number' => $row['bed_number']
        ]);
        
        // Output for AJAX call (normal operation)
        echo $json_output; 

        // Additional readable output for localhost testing
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
             echo "<br><br><strong>--- DEBUG INFO ---</strong>";
             echo "<p>Student ID: <strong>" . htmlspecialchars($roll_no) . "</strong></p>";
             echo "<p>Hostel ID (Simulated): <strong>" . htmlspecialchars($hostel_id) . "</strong></p>";
             echo "<h3>Room Details Found:</h3>";
             echo "<ul>";
             echo "<li>Room Number: <strong>" . htmlspecialchars($row['Room_No']) . "</strong></li>";
             echo "<li>Room ID: <strong>" . htmlspecialchars($row['Room_id']) . "</strong></li>";
             echo "<li>Start Date: <strong>" . htmlspecialchars($row['start_date']) . "</strong></li>";
             echo "<li>End Date: <strong>" . htmlspecialchars($row['end_date']) . "</strong></li>";
             echo "</ul>";
        }
        
        exit();
    } else {
        $debug_message = 'No room found for roll_no: ' . $roll_no . ' and hostel_id: ' . $hostel_id;
        error_log("get_student_room.php: " . $debug_message);
        
        $json_output = ['room_no' => null, 'debug_message' => $debug_message];
        echo json_encode($json_output);
        
        // Additional readable output for localhost testing
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo "<br><br><strong>--- DEBUG INFO ---</strong>";
            echo "<p>Search failed. Check if roll number and hostel ID match an allocated record in the 'student' and 'room' tables.</p>";
        }
        
        exit();
    }
} else {
    // If no POST data is set, and we are not in debug mode
    echo json_encode(null);
    exit();
}
?>