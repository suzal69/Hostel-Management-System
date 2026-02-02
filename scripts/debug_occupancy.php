<?php
require dirname(__DIR__) . '/includes/config.inc.php';

echo "=== Debugging Room Occupancy Calculation ===\n\n";

// Check all rooms to see the actual data
$query = "SELECT Room_id, Room_No, bed_capacity FROM Room WHERE Hostel_id = 1 ORDER BY Room_No";
$result = mysqli_query($conn, $query);

while ($room = mysqli_fetch_assoc($result)) {
    $roomId = $room['Room_id'];
    $roomNo = $room['Room_No'];
    $capacity = $room['bed_capacity'];
    
    echo "=== Room $roomNo (ID: $roomId) ===\n";
    echo "Capacity: $capacity\n";
    
    // Method 1: Direct count query
    $countQuery = "SELECT COUNT(*) as count FROM bed_allocation WHERE room_id = $roomId AND is_active = 1";
    $countResult = mysqli_query($conn, $countQuery);
    $count = mysqli_fetch_assoc($countResult)['count'];
    echo "Direct count: $count\n";
    
    // Method 2: List actual allocations
    $allocQuery = "SELECT ba.allocation_id, ba.bed_number, ba.student_id, ba.is_active, s.Fname, s.Lname
                   FROM bed_allocation ba
                   LEFT JOIN student s ON ba.student_id = s.Student_id
                   WHERE ba.room_id = $roomId
                   ORDER BY ba.bed_number";
    $allocResult = mysqli_query($conn, $allocQuery);
    
    echo "Allocations:\n";
    while ($alloc = mysqli_fetch_assoc($allocResult)) {
        $status = $alloc['is_active'] ? 'ACTIVE' : 'INACTIVE';
        $student = $alloc['Fname'] ? $alloc['Fname'] . ' ' . $alloc['Lname'] : $alloc['student_id'];
        echo "  Bed {$alloc['bed_number']}: $student ($status)\n";
    }
    
    // Method 3: Using the same subquery as the main page
    $subqueryQuery = "SELECT r.*, 
                      (SELECT COUNT(*) FROM bed_allocation ba WHERE ba.room_id = r.Room_id AND ba.is_active = 1) as current_occupancy
                      FROM Room r 
                      WHERE r.Room_id = $roomId";
    $subResult = mysqli_query($conn, $subqueryQuery);
    $subRow = mysqli_fetch_assoc($subResult);
    echo "Subquery result: {$subRow['current_occupancy']}\n";
    
    $available = $capacity - $count;
    echo "Available beds: $available\n\n";
}

echo "=== Checking bed_allocation table structure ===\n";
$structureQuery = "DESCRIBE bed_allocation";
$structResult = mysqli_query($conn, $structureQuery);
while ($row = mysqli_fetch_assoc($structResult)) {
    echo "{$row['Field']}: {$row['Type']} (Null: {$row['Null']}, Default: {$row['Default']})\n";
}

mysqli_close($conn);
?>
