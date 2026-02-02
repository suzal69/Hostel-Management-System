<?php
require dirname(__DIR__) . '/includes/config.inc.php';

echo "Adding bed allocation constraint...\n";

// First, let's check if there are any invalid allocations
echo "Checking for invalid bed allocations...\n";
$check_sql = "SELECT ba.allocation_id, ba.room_id, ba.bed_number, r.bed_capacity 
              FROM bed_allocation ba 
              JOIN Room r ON ba.room_id = r.Room_id 
              WHERE ba.bed_number > r.bed_capacity AND ba.is_active = 1";
$result = mysqli_query($conn, $check_sql);

$invalid_allocations = [];
while ($row = mysqli_fetch_assoc($result)) {
    $invalid_allocations[] = $row;
    echo "Invalid allocation found: ID {$row['allocation_id']}, Room {$row['room_id']}, Bed {$row['bed_number']} (max: {$row['bed_capacity']})\n";
}

if (!empty($invalid_allocations)) {
    echo "\nWARNING: Found " . count($invalid_allocations) . " invalid bed allocations!\n";
    echo "These need to be fixed before adding the constraint.\n";
    
    // Option to deactivate invalid allocations
    echo "\nDeactivating invalid allocations...\n";
    $deactivate_sql = "UPDATE bed_allocation SET is_active = 0 WHERE bed_number > (SELECT bed_capacity FROM Room WHERE Room_id = bed_allocation.room_id)";
    if (mysqli_query($conn, $deactivate_sql)) {
        echo "Invalid allocations deactivated.\n";
    }
}

// Add the foreign key constraint
echo "\nAdding CHECK constraint for bed allocation...\n";

// Drop existing constraint if it exists
mysqli_query($conn, "ALTER TABLE bed_allocation DROP CONSTRAINT IF EXISTS chk_bed_number_capacity");

// Add the constraint
$constraint_sql = "ALTER TABLE bed_allocation 
                   ADD CONSTRAINT chk_bed_number_capacity 
                   CHECK (bed_number <= (SELECT bed_capacity FROM Room WHERE Room_id = bed_allocation.room_id))";

if (mysqli_query($conn, $constraint_sql)) {
    echo "CHECK constraint added successfully!\n";
} else {
    echo "Error adding constraint: " . mysqli_error($conn) . "\n";
    
    // Alternative approach: Add a trigger if CHECK constraint fails
    echo "\nTrying alternative approach with triggers...\n";
    
    // Drop existing trigger if it exists
    mysqli_query($conn, "DROP TRIGGER IF EXISTS before_bed_allocation_insert");
    mysqli_query($conn, "DROP TRIGGER IF EXISTS before_bed_allocation_update");
    
    // Create INSERT trigger
    $insert_trigger = "CREATE TRIGGER before_bed_allocation_insert
                       BEFORE INSERT ON bed_allocation
                       FOR EACH ROW
                       BEGIN
                           DECLARE room_capacity INT;
                           SELECT bed_capacity INTO room_capacity FROM Room WHERE Room_id = NEW.room_id;
                           IF NEW.bed_number > room_capacity THEN
                               SIGNAL SQLSTATE '45000' 
                               SET MESSAGE_TEXT = 'Bed number cannot exceed room capacity';
                           END IF;
                       END";
    
    if (mysqli_query($conn, $insert_trigger)) {
        echo "INSERT trigger created successfully!\n";
    } else {
        echo "Error creating INSERT trigger: " . mysqli_error($conn) . "\n";
    }
    
    // Create UPDATE trigger
    $update_trigger = "CREATE TRIGGER before_bed_allocation_update
                       BEFORE UPDATE ON bed_allocation
                       FOR EACH ROW
                       BEGIN
                           DECLARE room_capacity INT;
                           SELECT bed_capacity INTO room_capacity FROM Room WHERE Room_id = NEW.room_id;
                           IF NEW.bed_number > room_capacity THEN
                               SIGNAL SQLSTATE '45000' 
                               SET MESSAGE_TEXT = 'Bed number cannot exceed room capacity';
                           END IF;
                       END";
    
    if (mysqli_query($conn, $update_trigger)) {
        echo "UPDATE trigger created successfully!\n";
    } else {
        echo "Error creating UPDATE trigger: " . mysqli_error($conn) . "\n";
    }
}

// Test the constraint
echo "\nTesting the constraint...\n";
$test_sql = "SELECT Room_id, Room_No, bed_capacity FROM Room WHERE Room_No BETWEEN 101 AND 310 ORDER BY Room_No LIMIT 5";
$test_result = mysqli_query($conn, $test_sql);
if ($test_result) {
    echo "Sample room capacities:\n";
    while ($row = mysqli_fetch_assoc($test_result)) {
        echo "Room {$row['Room_No']} (ID: {$row['Room_id']}): {$row['bed_capacity']} beds\n";
    }
} else {
    echo "Error testing: " . mysqli_error($conn) . "\n";
}

echo "\nBed allocation constraint setup completed!\n";
echo "Now any attempt to allocate a bed number exceeding room capacity will be prevented.\n";

mysqli_close($conn);
?>
