<?php
session_start();
require_once 'includes/config.inc.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if student is logged in
if (!isset($_SESSION['roll'])) {
    echo json_encode(['hasFoodPlan' => false, 'error' => 'Not logged in']);
    exit();
}

$studentId = $_SESSION['roll'];

// Check if student has active food plan
$food_plan_query = "SELECT ba.include_food, ba.food_plan 
                    FROM bed_allocation ba 
                    WHERE ba.student_id = ? AND ba.is_active = 1";

$stmt = mysqli_prepare($conn, $food_plan_query);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $studentId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $food_allocation = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    $hasFoodPlan = false;
    if ($food_allocation && $food_allocation['include_food'] == 1 && !empty($food_allocation['food_plan'])) {
        $hasFoodPlan = true;
    }
    
    echo json_encode([
        'hasFoodPlan' => $hasFoodPlan,
        'foodPlan' => $food_allocation['food_plan'] ?? null,
        'includeFood' => $food_allocation['include_food'] ?? 0
    ]);
} else {
    echo json_encode(['hasFoodPlan' => false, 'error' => 'Database error']);
}
?>
