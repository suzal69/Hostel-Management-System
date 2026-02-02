<?php
require dirname(__DIR__) . '/includes/config.inc.php';

echo "=== Fixing Room.current_occupancy Field ===\n\n";

// Update all rooms' current_occupancy field based on actual bed_allocation counts
$updateQuery = "UPDATE Room r 
                SET r.current_occupancy = (
                    SELECT COUNT(*) FROM bed_allocation ba 
                    WHERE ba.room_id = r.Room_id AND ba.is_active = 1
                )
                WHERE r.Hostel_id = 1";

if (mysqli_query($conn, $updateQuery)) {
    echo "Successfully updated current_occupancy for all rooms in Hostel ID 1\n";
    
    // Show the updated values
    $checkQuery = "SELECT r.Room_id, r.Room_No, r.bed_capacity, r.current_occupancy,
                   (SELECT COUNT(*) FROM bed_allocation ba WHERE ba.room_id = r.Room_id AND ba.is_active = 1) as actual_count
                   FROM Room r 
                   WHERE r.Hostel_id = 1 
                   ORDER BY r.Room_No";
    
    $result = mysqli_query($conn, $checkQuery);
    echo "\nUpdated occupancy values:\n";
    while ($room = mysqli_fetch_assoc($result)) {
        $available = $room['bed_capacity'] - $room['current_occupancy'];
        echo "Room {$room['Room_No']}: {$room['current_occupancy']}/{$room['bed_capacity']} ({$available} available)\n";
    }
} else {
    echo "Error updating occupancy: " . mysqli_error($conn) . "\n";
}

mysqli_close($conn);
?>
