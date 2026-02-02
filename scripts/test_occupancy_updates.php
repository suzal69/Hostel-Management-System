<?php
require dirname(__DIR__) . '/includes/config.inc.php';

echo "=== Testing Occupancy Update System ===\n\n";

// Test 1: Simulate a room change (move student from room 201 to room 202)
echo "Test 1: Room Change Simulation\n";
echo "Moving STUDENT001 from Room 201 to Room 202\n";

// Before: Check current occupancy
echo "Before move:\n";
$checkQuery = "SELECT Room_No, current_occupancy, bed_capacity FROM Room WHERE Room_No IN (201, 202) ORDER BY Room_No";
$result = mysqli_query($conn, $checkQuery);
while ($room = mysqli_fetch_assoc($result)) {
    $available = $room['bed_capacity'] - $room['current_occupancy'];
    echo "  Room {$room['Room_No']}: {$room['current_occupancy']}/{$room['bed_capacity']} ({$available} available)\n";
}

// Simulate the room change logic
mysqli_begin_transaction($conn);
try {
    // Step 1: Deactivate old allocation
    $deactivateQuery = "UPDATE bed_allocation SET is_active = 0, end_date = CURDATE() 
                       WHERE student_id = 'STUDENT001' AND is_active = 1";
    mysqli_query($conn, $deactivateQuery);
    
    // Step 2: Update old room occupancy
    $updateOldQuery = "UPDATE Room SET current_occupancy = 
                       (SELECT COUNT(*) FROM bed_allocation ba 
                        WHERE ba.room_id = 68 AND ba.is_active = 1)
                       WHERE Room_id = 68";
    mysqli_query($conn, $updateOldQuery);
    
    // Step 3: Create new allocation
    $newAllocQuery = "INSERT INTO bed_allocation 
                     (student_id, room_id, bed_number, allocation_price, start_date, end_date, is_active)
                     VALUES ('STUDENT001', 69, 2, 5000, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR), 1)";
    mysqli_query($conn, $newAllocQuery);
    
    // Step 4: Update new room occupancy
    $updateNewQuery = "UPDATE Room SET current_occupancy = 
                       (SELECT COUNT(*) FROM bed_allocation ba 
                        WHERE ba.room_id = 69 AND ba.is_active = 1)
                       WHERE Room_id = 69";
    mysqli_query($conn, $updateNewQuery);
    
    // Step 5: Update student
    $updateStudentQuery = "UPDATE Student SET Room_id = 69 WHERE Student_id = 'STUDENT001'";
    mysqli_query($conn, $updateStudentQuery);
    
    mysqli_commit($conn);
    echo "Room change completed successfully!\n";
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo "Error: " . $e->getMessage() . "\n";
}

// After: Check updated occupancy
echo "\nAfter move:\n";
$result = mysqli_query($conn, $checkQuery);
while ($room = mysqli_fetch_assoc($result)) {
    $available = $room['bed_capacity'] - $room['current_occupancy'];
    echo "  Room {$room['Room_No']}: {$room['current_occupancy']}/{$room['bed_capacity']} ({$available} available)\n";
}

// Test 2: Simulate vacating a student
echo "\n\nTest 2: Vacate Student Simulation\n";
echo "Vacating STUDENT002 from Room 201\n";

// Before vacate
echo "Before vacate:\n";
$checkQuery2 = "SELECT Room_No, current_occupancy, bed_capacity FROM Room WHERE Room_No = 201";
$result2 = mysqli_query($conn, $checkQuery2);
$room201_before = mysqli_fetch_assoc($result2);
$available_before = $room201_before['bed_capacity'] - $room201_before['current_occupancy'];
echo "  Room 201: {$room201_before['current_occupancy']}/{$room201_before['bed_capacity']} ({$available_before} available)\n";

// Simulate vacate
mysqli_begin_transaction($conn);
try {
    // Step 1: Deactivate bed allocation
    $deactivateQuery = "UPDATE bed_allocation SET is_active = 0, end_date = CURDATE() 
                       WHERE student_id = 'STUDENT002' AND is_active = 1";
    mysqli_query($conn, $deactivateQuery);
    
    // Step 2: Update room occupancy
    $updateOccupancyQuery = "UPDATE Room SET current_occupancy = 
                            (SELECT COUNT(*) FROM bed_allocation ba 
                             WHERE ba.room_id = 68 AND ba.is_active = 1)
                            WHERE Room_id = 68";
    mysqli_query($conn, $updateOccupancyQuery);
    
    // Step 3: Update student
    $updateStudentQuery = "UPDATE Student SET Room_id = NULL, Hostel_id = NULL WHERE Student_id = 'STUDENT002'";
    mysqli_query($conn, $updateStudentQuery);
    
    mysqli_commit($conn);
    echo "Vacate completed successfully!\n";
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo "Error: " . $e->getMessage() . "\n";
}

// After vacate
echo "\nAfter vacate:\n";
$result2 = mysqli_query($conn, $checkQuery2);
$room201_after = mysqli_fetch_assoc($result2);
$available_after = $room201_after['bed_capacity'] - $room201_after['current_occupancy'];
echo "  Room 201: {$room201_after['current_occupancy']}/{$room201_after['bed_capacity']} ({$available_after} available)\n";

mysqli_close($conn);
?>