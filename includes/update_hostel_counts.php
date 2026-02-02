<?php
require __DIR__ . '/config.inc.php';

echo "Starting hostel counts update...\n";

// Get all hostels
$q = "SELECT Hostel_id, No_of_rooms FROM Hostel";
$res = mysqli_query($conn, $q);

while ($h = mysqli_fetch_assoc($res)) {
    $hid = (int)$h['Hostel_id'];
    $total_rooms = (int)$h['No_of_rooms'];

    // Count allocated rooms for this hostel
    $countAllocatedSql = "SELECT COUNT(*) AS allocated_rooms 
                         FROM Room 
                         WHERE Hostel_id = ? AND Allocated = '1'";
    $stmt = mysqli_prepare($conn, $countAllocatedSql);
    mysqli_stmt_bind_param($stmt, "i", $hid);
    mysqli_stmt_execute($stmt);
    $cres = mysqli_stmt_get_result($stmt);
    $crow = mysqli_fetch_assoc($cres);
    mysqli_stmt_close($stmt);
    $allocated_rooms = (int)($crow['allocated_rooms'] ?? 0);

    // Update both current_no_of_rooms and No_of_students
    $current = $total_rooms - $allocated_rooms;
    if ($current < 0) $current = 0;

    $up = "UPDATE Hostel 
           SET current_no_of_rooms = ?,
               No_of_students = ? 
           WHERE Hostel_id = ?";
    $ust = mysqli_prepare($conn, $up);
    mysqli_stmt_bind_param($ust, "iii", $current, $allocated_rooms, $hid);
    mysqli_stmt_execute($ust);
    mysqli_stmt_close($ust);

    echo "Hostel {$hid}: total={$total_rooms}, allocated={$allocated_rooms}, current={$current}\n";
}

echo "Update complete.\n";
?>