<?php
require dirname(__DIR__) . '/includes/config.inc.php';

echo "Starting room capacity updates...\n";

// Update rooms 101-110 to 1 bed
$sql1 = "UPDATE Room SET bed_capacity = 1 WHERE Room_No BETWEEN 101 AND 110";
$stmt1 = mysqli_prepare($conn, $sql1);
if (mysqli_stmt_execute($stmt1)) {
    $affected1 = mysqli_stmt_affected_rows($stmt1);
    echo "Updated $affected1 rooms (101-110) to 1 bed capacity\n";
} else {
    echo "Error updating rooms 101-110: " . mysqli_error($conn) . "\n";
}
mysqli_stmt_close($stmt1);

// Update rooms 201-210 to 2 beds
$sql2 = "UPDATE Room SET bed_capacity = 2 WHERE Room_No BETWEEN 201 AND 210";
$stmt2 = mysqli_prepare($conn, $sql2);
if (mysqli_stmt_execute($stmt2)) {
    $affected2 = mysqli_stmt_affected_rows($stmt2);
    echo "Updated $affected2 rooms (201-210) to 2 bed capacity\n";
} else {
    echo "Error updating rooms 201-210: " . mysqli_error($conn) . "\n";
}
mysqli_stmt_close($stmt2);

// Update rooms 301-310 to 3 beds
$sql3 = "UPDATE Room SET bed_capacity = 3 WHERE Room_No BETWEEN 301 AND 310";
$stmt3 = mysqli_prepare($conn, $sql3);
if (mysqli_stmt_execute($stmt3)) {
    $affected3 = mysqli_stmt_affected_rows($stmt3);
    echo "Updated $affected3 rooms (301-310) to 3 bed capacity\n";
} else {
    echo "Error updating rooms 301-310: " . mysqli_error($conn) . "\n";
}
mysqli_stmt_close($stmt3);

echo "Room capacity update completed!\n";

// Display updated room information
echo "\nCurrent room capacities:\n";
$select_sql = "SELECT Room_No, bed_capacity FROM Room WHERE Room_No BETWEEN 101 AND 310 ORDER BY Room_No";
$result = mysqli_query($conn, $select_sql);
while ($row = mysqli_fetch_assoc($result)) {
    echo "Room {$row['Room_No']}: {$row['bed_capacity']} bed(s)\n";
}

mysqli_close($conn);
?>