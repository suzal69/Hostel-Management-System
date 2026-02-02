<?php
require_once 'includes/config.inc.php';

// Check if user is logged in
if (!isset($_SESSION['roll'])) {
    echo json_encode(['hasOverlap' => false]);
    exit();
}

$student_id = $_SESSION['roll'];
$start_date = $_POST['start_date'] ?? '';
$end_date = $_POST['end_date'] ?? '';

if (empty($start_date) || empty($end_date)) {
    echo json_encode(['hasOverlap' => false]);
    exit();
}

// Check for overlapping approved leave dates
$overlap_query = "SELECT COUNT(*) as overlapping_days 
                 FROM leave_applications 
                 WHERE student_id = ? 
                 AND status = 'approved'
                 AND (
                     (start_date <= ? AND end_date >= ?) OR
                     (start_date <= ? AND end_date >= ?) OR
                     (start_date >= ? AND end_date <= ?)
                 )";

$stmt = mysqli_prepare($conn, $overlap_query);
mysqli_stmt_bind_param($stmt, "ssssss", 
                       $student_id, $start_date, $start_date,
                       $end_date, $end_date, $start_date, $end_date);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$overlap_data = mysqli_fetch_assoc($result);

$hasOverlap = $overlap_data['overlapping_days'] > 0;

echo json_encode(['hasOverlap' => $hasOverlap]);
?>
