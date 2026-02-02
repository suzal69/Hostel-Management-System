<?php
require dirname(__DIR__) . '/includes/config.inc.php';

echo "=== Creating Sample Bed Allocations ===\n\n";

// Sample student data
$students = [
    ['id' => 'STUDENT001', 'fname' => 'John', 'lname' => 'Doe'],
    ['id' => 'STUDENT002', 'fname' => 'Jane', 'lname' => 'Smith'],
    ['id' => 'STUDENT003', 'fname' => 'Mike', 'lname' => 'Johnson'],
    ['id' => 'STUDENT004', 'fname' => 'Sarah', 'lname' => 'Williams'],
    ['id' => 'STUDENT005', 'fname' => 'David', 'lname' => 'Brown']
];

// Create sample students if they don't exist
echo "Creating sample students...\n";
foreach ($students as $student) {
    $checkQuery = "SELECT Student_id FROM Student WHERE Student_id = '{$student['id']}'";
    $checkResult = mysqli_query($conn, $checkQuery);
    
    if (mysqli_num_rows($checkResult) == 0) {
        $insertQuery = "INSERT INTO Student (Student_id, Fname, Lname, gender, Mob_no, Dept, Year_of_study, Pwd, Hostel_id, Email, is_verified) 
                        VALUES ('{$student['id']}', '{$student['fname']}', '{$student['lname']}', 'Male', '9876543210', 'Computer Science', '2', 'password123', 1, '{$student['id']}@email.com', 1)";
        if (mysqli_query($conn, $insertQuery)) {
            echo "Created student: {$student['fname']} {$student['lname']} ({$student['id']})\n";
        }
    }
}

// Create bed allocations
echo "\nCreating bed allocations...\n";

// Allocate 2 students to Room 201 (2-bed room)
$allocations = [
    ['room_id' => 68, 'room_no' => 201, 'bed_number' => 1, 'student_id' => 'STUDENT001'],
    ['room_id' => 68, 'room_no' => 201, 'bed_number' => 2, 'student_id' => 'STUDENT002'],
    
    // Allocate 1 student to Room 202 (2-bed room)
    ['room_id' => 69, 'room_no' => 202, 'bed_number' => 1, 'student_id' => 'STUDENT003'],
    
    // Allocate 3 students to Room 301 (3-bed room)
    ['room_id' => 78, 'room_no' => 301, 'bed_number' => 1, 'student_id' => 'STUDENT004'],
    ['room_id' => 78, 'room_no' => 301, 'bed_number' => 2, 'student_id' => 'STUDENT005'],
];

foreach ($allocations as $allocation) {
    // Check if allocation already exists
    $checkQuery = "SELECT allocation_id FROM bed_allocation WHERE room_id = {$allocation['room_id']} AND bed_number = {$allocation['bed_number']} AND is_active = 1";
    $checkResult = mysqli_query($conn, $checkQuery);
    
    if (mysqli_num_rows($checkResult) == 0) {
        $start_date = date("Y-m-d");
        $end_date = date('Y-m-d', strtotime('+1 year'));
        
        $insertQuery = "INSERT INTO bed_allocation (student_id, room_id, bed_number, allocation_price, start_date, end_date, is_active) 
                        VALUES ('{$allocation['student_id']}', {$allocation['room_id']}, {$allocation['bed_number']}, 5000, '$start_date', '$end_date', 1)";
        
        if (mysqli_query($conn, $insertQuery)) {
            echo "Allocated Bed {$allocation['bed_number']} in Room {$allocation['room_no']} to {$allocation['student_id']}\n";
            
            // Update student's room_id
            $updateStudentQuery = "UPDATE Student SET Room_id = {$allocation['room_id']} WHERE Student_id = '{$allocation['student_id']}'";
            mysqli_query($conn, $updateStudentQuery);
        } else {
            echo "Error allocating bed: " . mysqli_error($conn) . "\n";
        }
    } else {
        echo "Bed {$allocation['bed_number']} in Room {$allocation['room_no']} already allocated\n";
    }
}

echo "\n=== Sample Allocations Complete ===\n";

// Show current occupancy
echo "\nCurrent room occupancy:\n";
$selectQuery = "SELECT r.Room_id, r.Room_No, r.bed_capacity, 
                (SELECT COUNT(*) FROM bed_allocation ba WHERE ba.room_id = r.Room_id AND ba.is_active = 1) as current_occupancy
                FROM Room r 
                WHERE r.Hostel_id = 1 AND r.Room_No IN (201, 202, 301)
                ORDER BY r.Room_No";
$selectResult = mysqli_query($conn, $selectQuery);
while ($room = mysqli_fetch_assoc($selectResult)) {
    $available = $room['bed_capacity'] - $room['current_occupancy'];
    echo "Room {$room['Room_No']}: {$room['current_occupancy']}/{$room['bed_capacity']} beds occupied ({$available} available)\n";
}

mysqli_close($conn);
?>
