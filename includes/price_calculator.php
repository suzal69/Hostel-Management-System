<?php
/**
 * Centralized Price Calculation System
 * This file contains atomic price calculation functions used across the application
 * to ensure consistent pricing logic and eliminate code duplication.
 */

/**
 * Calculate individual student price based on room occupancy and food plan
 * 
 * @param array $allocation Array containing 'include_food' and 'food_plan' keys
 * @param int $roomOccupancy Current number of occupants in the room
 * @return float Total price for the student
 */
function calculateStudentPrice($allocation, $roomOccupancy) {
    $base_room_price = 5000; // Base room price for the entire room
    $food_price = 0;
    
    // Calculate food plan price
    if ($allocation && isset($allocation['include_food']) && $allocation['include_food'] && !empty($allocation['food_plan'])) {
        switch ($allocation['food_plan']) {
            case 'basic':
                $food_price = 500;
                break;
            case 'standard':
                $food_price = 1500;
                break;
            case 'premium':
                $food_price = 2500;
                break;
        }
    }
    
    // Calculate room price based on occupancy
    if ($roomOccupancy > 0) {
        $individual_room_price = $base_room_price / $roomOccupancy;
    } else {
        $individual_room_price = $base_room_price; // Fallback
    }
    
    return $individual_room_price + $food_price;
}

/**
 * Get food plan display with pricing information
 * 
 * @param int $include_food Boolean flag (0 or 1)
 * @param string $food_plan Food plan type ('basic', 'standard', 'premium')
 * @param bool $show_price Whether to include price in display
 * @return string Formatted food plan display
 */
function getFoodPlanDisplay($include_food, $food_plan, $show_price = true) {
    if (!$include_food || empty($food_plan)) {
        return $show_price ? '<span class="badge badge-secondary">No Food</span>' : 'No Food';
    }
    
    switch ($food_plan) {
        case 'basic':
            return $show_price ? '<span class="badge badge-info">Basic (Rs500)</span>' : 'Basic';
        case 'standard':
            return $show_price ? '<span class="badge badge-warning">Standard (Rs1500)</span>' : 'Standard';
        case 'premium':
            return $show_price ? '<span class="badge badge-success">Premium (Rs2500)</span>' : 'Premium';
        default:
            return $show_price ? '<span class="badge badge-secondary">Unknown</span>' : 'Unknown';
    }
}

/**
 * Get food plan price only
 * 
 * @param string $food_plan Food plan type ('basic', 'standard', 'premium')
 * @return float Food plan price
 */
function getFoodPlanPrice($food_plan) {
    switch ($food_plan) {
        case 'basic':
            return 500;
        case 'standard':
            return 1500;
        case 'premium':
            return 2500;
        default:
            return 0;
    }
}

/**
 * Get base room price
 * 
 * @return float Base room price for the entire room
 */
function getBaseRoomPrice() {
    return 5000;
}

/**
 * Calculate room portion price per student
 * 
 * @param int $roomOccupancy Current number of occupants in the room
 * @return float Individual room portion price
 */
function calculateRoomPortionPrice($roomOccupancy) {
    $base_room_price = getBaseRoomPrice();
    
    if ($roomOccupancy > 0) {
        return $base_room_price / $roomOccupancy;
    } else {
        return $base_room_price; // Fallback
    }
}

/**
 * Get price breakdown for display
 * 
 * @param array $allocation Array containing 'include_food' and 'food_plan' keys
 * @param int $roomOccupancy Current number of occupants in the room
 * @return array Price breakdown components
 */
function getPriceBreakdown($allocation, $roomOccupancy) {
    $room_portion = calculateRoomPortionPrice($roomOccupancy);
    $food_price = 0;
    
    if ($allocation && isset($allocation['include_food']) && $allocation['include_food'] && !empty($allocation['food_plan'])) {
        $food_price = getFoodPlanPrice($allocation['food_plan']);
    }
    
    return [
        'room_portion' => $room_portion,
        'food_price' => $food_price,
        'total_price' => $room_portion + $food_price,
        'base_room_price' => getBaseRoomPrice(),
        'room_occupancy' => $roomOccupancy
    ];
}

/**
 * Calculate leave price reduction
 * 
 * @param string $food_plan Food plan type
 * @param int $leave_days Number of leave days
 * @param int $days_in_month Days in month (default: 30)
 * @return array Leave calculation details
 */
function calculateLeaveReduction($food_plan, $leave_days, $days_in_month = 30) {
    $monthly_food_price = getFoodPlanPrice($food_plan);
    $daily_rate = $monthly_food_price / $days_in_month;
    $reduction = $leave_days * $daily_rate;
    $adjusted_price = $monthly_food_price - $reduction;
    
    return [
        'monthly_food_price' => $monthly_food_price,
        'daily_rate' => $daily_rate,
        'leave_days' => $leave_days,
        'reduction' => $reduction,
        'adjusted_price' => $adjusted_price
    ];
}

/**
 * Get student's current price with leave adjustments
 * 
 * @param string $student_id Student ID
 * @param mysqli $conn Database connection
 * @return array Student's pricing information including leave adjustments
 */
function getStudentPriceWithLeaveAdjustments($student_id, $conn) {
    // Get student's current allocation and room details
    $query = "SELECT s.*, h.Hostel_name, r.Room_No, r.current_occupancy, ba.bed_number, 
                     ba.start_date, ba.end_date, ba.include_food, ba.food_plan
              FROM Student s
              LEFT JOIN Hostel h ON s.Hostel_id = h.Hostel_id
              LEFT JOIN Room r ON s.Room_id = r.Room_id
              LEFT JOIN bed_allocation ba ON s.Student_id = ba.student_id AND ba.is_active = 1
              WHERE s.Student_id = ?";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $student_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $student_data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$student_data || !$student_data['bed_number']) {
        return ['error' => 'Student not allocated to room'];
    }
    
    // Calculate base price
    $allocation = [
        'include_food' => $student_data['include_food'],
        'food_plan' => $student_data['food_plan']
    ];
    
    $base_price = calculateStudentPrice($allocation, $student_data['current_occupancy']);
    
    // Get leave adjustments for current month and next few months (to show upcoming adjustments)
    $current_month = date('Y-m');
    $leave_query = "SELECT SUM(food_price_reduction) as total_reduction 
                    FROM leave_adjustments 
                    WHERE student_id = ? AND month <= ?";
    
    $stmt = mysqli_prepare($conn, $leave_query);
    mysqli_stmt_bind_param($stmt, "ss", $student_id, $current_month);
    mysqli_stmt_execute($stmt);
    $leave_result = mysqli_stmt_get_result($stmt);
    $leave_data = mysqli_fetch_assoc($leave_result);
    mysqli_stmt_close($stmt);
    
    $total_leave_reduction = $leave_data['total_reduction'] ?? 0;
    $final_price = $base_price - $total_leave_reduction;
    
    return [
        'student_id' => $student_id,
        'base_price' => $base_price,
        'leave_reduction' => $total_leave_reduction,
        'final_price' => $final_price,
        'food_plan' => $student_data['food_plan'],
        'include_food' => $student_data['include_food'],
        'room_occupancy' => $student_data['current_occupancy'],
        'room_details' => [
            'hostel' => $student_data['Hostel_name'],
            'room' => $student_data['Room_No'],
            'bed' => $student_data['bed_number']
        ]
    ];
}

/**
 * Create leave adjustment record when leave is approved
 * 
 * @param int $leave_id Leave application ID
 * @param mysqli $conn Database connection
 * @return bool Success status
 */
function createLeaveAdjustment($leave_id, $conn) {
    // Get leave application details
    $query = "SELECT * FROM leave_applications WHERE leave_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $leave_id);
    mysqli_stmt_execute($stmt);
    $leave_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    
    if (!$leave_data) {
        return false;
    }
    
    // Use current month for immediate price reduction
    $billing_month = date('Y-m');
    
    // Calculate leave reduction using centralized function
    $leave_calc = calculateLeaveReduction($leave_data['food_plan'], $leave_data['leave_days']);
    
    // Insert leave adjustment with all required columns
    $adjustment_query = "INSERT INTO leave_adjustments 
                        (leave_id, student_id, month, food_plan, original_food_price, 
                         leave_days, daily_rate, food_price_reduction, adjusted_food_price,
                         processed_date, billing_month, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?, 'processed')";
    
    $stmt = mysqli_prepare($conn, $adjustment_query);
    mysqli_stmt_bind_param($stmt, "isssdiddds", 
                           $leave_id, $leave_data['student_id'], $billing_month,
                           $leave_data['food_plan'], $leave_data['original_food_price'],
                           $leave_data['leave_days'], $leave_calc['daily_rate'], 
                           $leave_calc['reduction'], $leave_calc['adjusted_price'],
                           $billing_month);
    
    $success = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    return $success;
}

?>