<?php
require dirname(__DIR__) . '/includes/config.inc.php';

echo "=== Checking Current Occupancy Issues ===\n\n";

// Check rooms that should have multiple occupants based on bed_allocation table
echo "Rooms with bed allocations:\n";
$query = "SELECT r.Room_id, r.Room_No, r.bed_capacity, r.current_occupancy as room_occupancy_field,
                (SELECT COUNT(*) FROM bed_allocation ba WHERE ba.room_id = r.Room_id AND ba.is_active = 1) as actual_occupancy
         FROM Room r 
         WHERE r.Hostel_id = 1 
         ORDER BY r.Room_No";

$result = mysqli_query($conn, $query);
while ($room = mysqli_fetch_assoc($result)) {
    echo "Room {$room['Room_No']} (ID: {$room['Room_id']}):\n";
    echo "  - Room.current_occupancy field: {$room['room_occupancy_field']}\n";
    echo "  - Actual bed_allocation count: {$room['actual_occupancy']}\n";
    echo "  - Room capacity: {$room['bed_capacity']}\n";
    
    // Show individual bed allocations for this room
    if ($room['actual_occupancy'] > 0) {
        $bedQuery = "SELECT ba.bed_number, ba.student_id, ba.is_active, s.Fname, s.Lname
                    FROM bed_allocation ba
                    LEFT JOIN student s ON ba.student_id = s.Student_id
                    WHERE ba.room_id = {$room['Room_id']}
                    ORDER BY ba.bed_number";
        $bedResult = mysqli_query($conn, $bedQuery);
        echo "  - Bed allocations:\n";
        while ($bed = mysqli_fetch_assoc($bedResult)) {
            $status = $bed['is_active'] ? 'ACTIVE' : 'INACTIVE';
            $student = $bed['Fname'] ? $bed['Fname'] . ' ' . $bed['Lname'] : $bed['student_id'];
            echo "    * Bed {$bed['bed_number']}: $student ($status)\n";
        }
    }
    echo "\n";
}

// Check if there's a mismatch between room.current_occupancy and actual bed_allocation count
echo "=== Checking for Occupancy Mismatches ===\n";
$mismatchQuery = "SELECT r.Room_id, r.Room_No, r.current_occupancy as room_field,
                  (SELECT COUNT(*) FROM bed_allocation ba WHERE ba.room_id = r.Room_id AND ba.is_active = 1) as actual_count
                  FROM Room r 
                  WHERE r.Hostel_id = 1 AND r.current_occupancy != 
                  (SELECT COUNT(*) FROM bed_allocation ba WHERE ba.room_id = r.Room_id AND ba.is_active = 1)";

$mismatchResult = mysqli_query($conn, $mismatchQuery);
if (mysqli_num_rows($mismatchResult) > 0) {
    echo "Found occupancy mismatches:\n";
    while ($mismatch = mysqli_fetch_assoc($mismatchResult)) {
        echo "Room {$mismatch['Room_No']}: Field shows {$mismatch['room_field']}, actual count is {$mismatch['actual_count']}\n";
    }
} else {
    echo "No occupancy mismatches found.\n";
}

mysqli_close($conn);
?>