<?php


// This script finds allocations whose end_date <= today, vacates them, re-opens room and updates hostel counts,
// and notifies both manager and student. Run via cron / Task Scheduler daily.

$today = date('Y-m-d');

// 1) Find students whose end_date is reached or passed and still allocated
$sel = "SELECT s.Student_id, s.Room_id, s.Hostel_id, r.Room_No
        FROM Student s
        JOIN Room r ON s.Room_id = r.Room_id
        WHERE s.end_date IS NOT NULL AND s.end_date <= ? AND s.Hostel_id IS NOT NULL";
$stmt = mysqli_prepare($conn, $sel);
mysqli_stmt_bind_param($stmt, "s", $today);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$sysSender = 'system';
$time = date("h:i A");

while ($row = mysqli_fetch_assoc($res)) {
    $stu = $row['Student_id'];
    $room_id = (int)$row['Room_id'];
    $hostel_id = (int)$row['Hostel_id'];
    $room_no = $row['Room_No'];

    // Start transaction
    mysqli_begin_transaction($conn);

    // 1) Clear student allocation
    $q1 = "UPDATE Student SET Hostel_id = NULL, Room_id = NULL, start_date = NULL, end_date = NULL WHERE Student_id = ?";
    $u1 = mysqli_prepare($conn, $q1);
    mysqli_stmt_bind_param($u1, "s", $stu);
    mysqli_stmt_execute($u1);
    mysqli_stmt_close($u1);

    // 2) Delete from bed_allocation table
    $q1b = "DELETE FROM bed_allocation WHERE student_id = ? AND is_active = 1";
    $u1b = mysqli_prepare($conn, $q1b);
    mysqli_stmt_bind_param($u1b, "s", $stu);
    mysqli_stmt_execute($u1b);
    mysqli_stmt_close($u1b);

    // 3) Delete from application table
    $q1c = "DELETE FROM application WHERE Student_id = ?";
    $u1c = mysqli_prepare($conn, $q1c);
    mysqli_stmt_bind_param($u1c, "s", $stu);
    mysqli_stmt_execute($u1c);
    mysqli_stmt_close($u1c);

    // 4) Check if other students remain in the room
    $checkSql = "SELECT COUNT(*) as remaining_students FROM Student WHERE Room_id = ? AND Hostel_id IS NOT NULL";
    $checkStmt = mysqli_prepare($conn, $checkSql);
    mysqli_stmt_bind_param($checkStmt, "i", $room_id);
    mysqli_stmt_execute($checkStmt);
    $checkRes = mysqli_stmt_get_result($checkStmt);
    $checkRow = mysqli_fetch_assoc($checkRes);
    $remaining_students = $checkRow['remaining_students'];
    mysqli_stmt_close($checkStmt);

    // 5) Update room status based on remaining students
    if ($remaining_students == 0) {
        // No students left - mark room as completely free
        $q2 = "UPDATE Room SET Allocated = '0', start_date = NULL, end_date = NULL, current_occupancy = 0 WHERE Room_id = ?";
    } else {
        // Students still in room - just reduce occupancy by 1
        $q2 = "UPDATE Room SET current_occupancy = GREATEST(current_occupancy - 1, 0) WHERE Room_id = ?";
    }
    $u2 = mysqli_prepare($conn, $q2);
    mysqli_stmt_bind_param($u2, "i", $room_id);
    mysqli_stmt_execute($u2);
    mysqli_stmt_close($u2);

    // 3) Increment hostel available count
    $q3 = "UPDATE Hostel SET current_no_of_rooms = COALESCE(current_no_of_rooms, No_of_rooms) + 1 WHERE Hostel_id = ?";
    $u3 = mysqli_prepare($conn, $q3);
    mysqli_stmt_bind_param($u3, "i", $hostel_id);
    mysqli_stmt_execute($u3);
    mysqli_stmt_close($u3);

    // 4) Notify manager
    $mSql = "SELECT Hostel_man_id FROM hostel_manager WHERE Hostel_id = ? LIMIT 1";
    $mstmt = mysqli_prepare($conn, $mSql);
    mysqli_stmt_bind_param($mstmt, "i", $hostel_id);
    mysqli_stmt_execute($mstmt);
    $mres = mysqli_stmt_get_result($mstmt);
    $mrow = mysqli_fetch_assoc($mres);
    mysqli_stmt_close($mstmt);
    $managerId = $mrow['Hostel_man_id'] ?? null;

    $subM = "Allocation expired - student vacated automatically";
    $msgM = "Student {$stu} allocation (room {$room_no}) expired on or before {$today} and has been vacated automatically.";

    if ($managerId) {
        $insM = "INSERT INTO message (sender_id, receiver_id, hostel_id, subject_h, message, msg_date, msg_time) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $istM = mysqli_prepare($conn, $insM);
        mysqli_stmt_bind_param($istM, "ssisiss", $sysSender, $managerId, $hostel_id, $subM, $msgM, $today, $time);
        mysqli_stmt_execute($istM);
        mysqli_stmt_close($istM);
    }

    // 5) Notify student
    $subS = "Your hostel allocation has ended";
    $msgS = "Your allocation for room {$room_no} ended on or before {$today}. You have been vacated automatically.";
    $insS = "INSERT INTO message (sender_id, receiver_id, hostel_id, subject_h, message, msg_date, msg_time) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $istS = mysqli_prepare($conn, $insS);
    mysqli_stmt_bind_param($istS, "ssisiss", $sysSender, $stu, $hostel_id, $subS, $msgS, $today, $time);
    mysqli_stmt_execute($istS);
    mysqli_stmt_close($istS);

    mysqli_commit($conn);
}

mysqli_stmt_close($stmt);
echo "Expired allocation check completed.\n";
?>