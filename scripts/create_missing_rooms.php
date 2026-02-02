<?php
require dirname(__DIR__) . '/includes/config.inc.php';

echo "=== Creating Missing Rooms (201-210 and 301-310) ===\n\n";

// Get the hostel ID (assuming hostel_id = 1 for now)
$hostel_id = 1;

// Create rooms 201-210 with 2 beds each
echo "Creating rooms 201-210 (2 beds each)...\n";
for ($roomNo = 201; $roomNo <= 210; $roomNo++) {
    // Check if room already exists
    $checkQuery = "SELECT Room_id FROM Room WHERE Room_No = $roomNo AND Hostel_id = $hostel_id";
    $checkResult = mysqli_query($conn, $checkQuery);
    
    if (mysqli_num_rows($checkResult) == 0) {
        // Insert new room
        $insertQuery = "INSERT INTO Room (Hostel_id, Room_No, bed_capacity) VALUES ($hostel_id, $roomNo, 2)";
        if (mysqli_query($conn, $insertQuery)) {
            echo "Created Room $roomNo with 2 beds\n";
        } else {
            echo "Error creating Room $roomNo: " . mysqli_error($conn) . "\n";
        }
    } else {
        echo "Room $roomNo already exists, updating capacity to 2 beds\n";
        $updateQuery = "UPDATE Room SET bed_capacity = 2 WHERE Room_No = $roomNo AND Hostel_id = $hostel_id";
        mysqli_query($conn, $updateQuery);
    }
}

// Create rooms 301-310 with 3 beds each
echo "\nCreating rooms 301-310 (3 beds each)...\n";
for ($roomNo = 301; $roomNo <= 310; $roomNo++) {
    // Check if room already exists
    $checkQuery = "SELECT Room_id FROM Room WHERE Room_No = $roomNo AND Hostel_id = $hostel_id";
    $checkResult = mysqli_query($conn, $checkQuery);
    
    if (mysqli_num_rows($checkResult) == 0) {
        // Insert new room
        $insertQuery = "INSERT INTO Room (Hostel_id, Room_No, bed_capacity) VALUES ($hostel_id, $roomNo, 3)";
        if (mysqli_query($conn, $insertQuery)) {
            echo "Created Room $roomNo with 3 beds\n";
        } else {
            echo "Error creating Room $roomNo: " . mysqli_error($conn) . "\n";
        }
    } else {
        echo "Room $roomNo already exists, updating capacity to 3 beds\n";
        $updateQuery = "UPDATE Room SET bed_capacity = 3 WHERE Room_No = $roomNo AND Hostel_id = $hostel_id";
        mysqli_query($conn, $updateQuery);
    }
}

echo "\n=== Room Creation Complete ===\n";

// Show final room list
echo "\nFinal room list:\n";
$selectQuery = "SELECT Room_id, Room_No, bed_capacity FROM Room WHERE Hostel_id = $hostel_id ORDER BY Room_No";
$selectResult = mysqli_query($conn, $selectQuery);
while ($room = mysqli_fetch_assoc($selectResult)) {
    echo "Room {$room['Room_No']} (ID: {$room['Room_id']}): {$room['bed_capacity']} beds\n";
}

mysqli_close($conn);
?>