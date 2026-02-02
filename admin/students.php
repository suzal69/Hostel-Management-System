<?php
  require_once __DIR__ . '/../includes/config.inc.php';
  require_once __DIR__ . '/admin_header.php';
  require_once __DIR__ . '/../includes/price_calculator.php';

  // Use session variables set by includes/login-hm.inc.php
  if (empty($_SESSION['admin_username']) || empty($_SESSION['isadmin']) || $_SESSION['isadmin'] != 1) {
    header('Location: ../login-hostel_manager.php');
    exit;
  }

  // Handle edit student personal info
  if (isset($_POST['edit_student'])) {
    $student_id = $_POST['student_id'];
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $mob_no = $_POST['mob_no'];
    $email = $_POST['email'];
    $dept = $_POST['dept'];
    $year_of_study = $_POST['year_of_study'];
    
    $query_update = "UPDATE Student SET Fname = ?, Lname = ?, Mob_no = ?, Email = ?, Dept = ?, Year_of_study = ? WHERE Student_id = ?";
    $stmt = mysqli_prepare($conn, $query_update);
    mysqli_stmt_bind_param($stmt, "sssssss", $fname, $lname, $mob_no, $email, $dept, $year_of_study, $student_id);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "<script>alert('Student information updated successfully!');</script>";
    } else {
        echo "<script>alert('Error updating student information.');</script>";
    }
    mysqli_stmt_close($stmt);
  }

  // Handle delete student (vacate functionality)
  if (isset($_POST['delete_student'])) {
    $student_id = $_POST['student_id'];
    $hostel_id = $_POST['hostel_id'];
    $room_id = $_POST['room_id'];
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Step 1: Get allocation details
        $query_get_allocation = "SELECT allocation_id FROM bed_allocation 
                               WHERE student_id = ? AND is_active = 1";
        $stmt_get = mysqli_prepare($conn, $query_get_allocation);
        mysqli_stmt_bind_param($stmt_get, "s", $student_id);
        mysqli_stmt_execute($stmt_get);
        $result_get = mysqli_stmt_get_result($stmt_get);
        $allocation = mysqli_fetch_assoc($result_get);
        mysqli_stmt_close($stmt_get);

        // Step 2: Delete the bed allocation completely
        $query_delete_allocation = "DELETE FROM bed_allocation WHERE student_id = ? AND is_active = 1";
        $stmt_delete = mysqli_prepare($conn, $query_delete_allocation);
        mysqli_stmt_bind_param($stmt_delete, "s", $student_id);
        mysqli_stmt_execute($stmt_delete);
        mysqli_stmt_close($stmt_delete);

        // Step 3: Update room occupancy
        $query_update_occupancy = "UPDATE Room SET current_occupancy = 
                                 (SELECT COUNT(*) FROM bed_allocation ba 
                                  WHERE ba.room_id = ? AND ba.is_active = 1)
                                 WHERE Room_id = ?";
        $stmt_occupancy = mysqli_prepare($conn, $query_update_occupancy);
        mysqli_stmt_bind_param($stmt_occupancy, "ii", $room_id, $room_id);
        mysqli_stmt_execute($stmt_occupancy);
        mysqli_stmt_close($stmt_occupancy);

        // Step 4: Update Student table: clear room and hostel assignments
        $query1 = "UPDATE Student SET Room_id = NULL, Hostel_id = NULL WHERE Student_id = ?";
        $stmt1 = mysqli_prepare($conn, $query1);
        mysqli_stmt_bind_param($stmt1, "s", $student_id);
        mysqli_stmt_execute($stmt1);
        mysqli_stmt_close($stmt1);

        // Step 5: Update Room table - clear allocation if no more occupants
        $query_check_occupancy = "SELECT COUNT(*) as count FROM bed_allocation 
                                 WHERE room_id = ? AND is_active = 1";
        $stmt_check = mysqli_prepare($conn, $query_check_occupancy);
        mysqli_stmt_bind_param($stmt_check, "i", $room_id);
        mysqli_stmt_execute($stmt_check);
        $result_check = mysqli_stmt_get_result($stmt_check);
        $occupancy_check = mysqli_fetch_assoc($result_check);
        mysqli_stmt_close($stmt_check);

        if ($occupancy_check['count'] == 0) {
            // Room is completely empty - clear allocation data
            $query2 = "UPDATE Room SET Allocated = 0, current_occupancy = 0 WHERE Room_id = ?";
            $stmt2 = mysqli_prepare($conn, $query2);
            mysqli_stmt_bind_param($stmt2, "i", $room_id);
            mysqli_stmt_execute($stmt2);
            mysqli_stmt_close($stmt2);
        }

        // Step 6: Update Hostel table
        $query3 = "UPDATE Hostel SET No_of_students = No_of_students - 1 WHERE Hostel_id = ?";
        $stmt3 = mysqli_prepare($conn, $query3);
        mysqli_stmt_bind_param($stmt3, "i", $hostel_id);
        mysqli_stmt_execute($stmt3);
        mysqli_stmt_close($stmt3);

        // Step 7: Delete any pending application(s) for this student in this hostel
        $query4 = "DELETE FROM Application WHERE Student_id = ? AND Hostel_id = ?";
        $stmt4 = mysqli_prepare($conn, $query4);
        mysqli_stmt_bind_param($stmt4, "si", $student_id, $hostel_id);
        mysqli_stmt_execute($stmt4);
        mysqli_stmt_close($stmt4);

        // Commit transaction
        mysqli_commit($conn);
        echo "<script>alert('Student has been vacated successfully!');</script>";

    } catch (mysqli_sql_exception $exception) {
        mysqli_rollback($conn);
        echo "<script>alert('Failed to vacate student. Please try again.');</script>";
    }
  }

  // Handle change room functionality
  if (isset($_POST['change_room'])) {
    $student_id = $_POST['student_id'];
    $new_room_id = $_POST['new_room_id'];
    $new_bed_number = $_POST['new_bed_number'];
    $old_room_id = $_POST['old_room_id'];
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Step 1: Get current allocation details
        $query_get_current = "SELECT ba.*, r.Hostel_id as old_hostel_id 
                              FROM bed_allocation ba 
                              JOIN Room r ON ba.room_id = r.Room_id 
                              WHERE ba.student_id = ? AND ba.is_active = 1";
        $stmt_get_current = mysqli_prepare($conn, $query_get_current);
        mysqli_stmt_bind_param($stmt_get_current, "s", $student_id);
        mysqli_stmt_execute($stmt_get_current);
        $result_current = mysqli_stmt_get_result($stmt_get_current);
        $current_allocation = mysqli_fetch_assoc($result_current);
        mysqli_stmt_close($stmt_get_current);
        
        if (!$current_allocation) {
            throw new Exception("No active allocation found for student.");
        }
        
        // Step 2: Get new room details
        $query_get_new_room = "SELECT r.Hostel_id as new_hostel_id, r.bed_capacity 
                               FROM Room r 
                               WHERE r.Room_id = ?";
        $stmt_get_new_room = mysqli_prepare($conn, $query_get_new_room);
        mysqli_stmt_bind_param($stmt_get_new_room, "i", $new_room_id);
        mysqli_stmt_execute($stmt_get_new_room);
        $result_new_room = mysqli_stmt_get_result($stmt_get_new_room);
        $new_room_info = mysqli_fetch_assoc($result_new_room);
        mysqli_stmt_close($stmt_get_new_room);
        
        if (!$new_room_info) {
            throw new Exception("New room not found.");
        }
        
        // Step 3: Check if new bed is available
        $query_check_bed = "SELECT allocation_id FROM bed_allocation 
                           WHERE room_id = ? AND bed_number = ? AND is_active = 1";
        $stmt_check_bed = mysqli_prepare($conn, $query_check_bed);
        mysqli_stmt_bind_param($stmt_check_bed, "ii", $new_room_id, $new_bed_number);
        mysqli_stmt_execute($stmt_check_bed);
        $result_check_bed = mysqli_stmt_get_result($stmt_check_bed);
        if (mysqli_num_rows($result_check_bed) > 0) {
            throw new Exception("Selected bed is already occupied.");
        }
        mysqli_stmt_close($stmt_check_bed);
        
        // Step 4: Update existing bed allocation (only room_id and bed_number)
        $query_update_allocation = "UPDATE bed_allocation 
                                   SET room_id = ?, bed_number = ?
                                   WHERE allocation_id = ?";
        $stmt_update_allocation = mysqli_prepare($conn, $query_update_allocation);
        mysqli_stmt_bind_param($stmt_update_allocation, "iii", 
                               $new_room_id, $new_bed_number, 
                               $current_allocation['allocation_id']);
        mysqli_stmt_execute($stmt_update_allocation);
        mysqli_stmt_close($stmt_update_allocation);
        
        // Step 5: Update student's room_id and hostel_id
        $query_update_student = "UPDATE Student SET Room_id = ?, Hostel_id = ? WHERE Student_id = ?";
        $stmt_update_student = mysqli_prepare($conn, $query_update_student);
        mysqli_stmt_bind_param($stmt_update_student, "iis", $new_room_id, $new_room_info['new_hostel_id'], $student_id);
        mysqli_stmt_execute($stmt_update_student);
        mysqli_stmt_close($stmt_update_student);
        
        // Step 6: Update application table with new room details
        $query_update_application = "UPDATE application 
                                    SET preferred_room_id = ?, preferred_bed_number = ?, Hostel_id = ?
                                    WHERE Student_id = ? AND Application_status = 0";
        $stmt_update_application = mysqli_prepare($conn, $query_update_application);
        mysqli_stmt_bind_param($stmt_update_application, "iiis", $new_room_id, $new_bed_number, $new_room_info['new_hostel_id'], $student_id);
        mysqli_stmt_execute($stmt_update_application);
        mysqli_stmt_close($stmt_update_application);
        
        // Step 7: Update old room occupancy and allocation status
        $query_old_occupancy = "UPDATE Room SET 
                               current_occupancy = (SELECT COUNT(*) FROM bed_allocation ba WHERE ba.room_id = ? AND ba.is_active = 1),
                               Allocated = CASE WHEN (SELECT COUNT(*) FROM bed_allocation ba WHERE ba.room_id = ? AND ba.is_active = 1) > 0 THEN 1 ELSE 0 END
                               WHERE Room_id = ?";
        $stmt_old_occupancy = mysqli_prepare($conn, $query_old_occupancy);
        mysqli_stmt_bind_param($stmt_old_occupancy, "iii", $old_room_id, $old_room_id, $old_room_id);
        mysqli_stmt_execute($stmt_old_occupancy);
        mysqli_stmt_close($stmt_old_occupancy);
        
        // Step 8: Update new room occupancy and allocation status
        $query_new_occupancy = "UPDATE Room SET 
                               current_occupancy = (SELECT COUNT(*) FROM bed_allocation ba WHERE ba.room_id = ? AND ba.is_active = 1),
                               Allocated = 1
                               WHERE Room_id = ?";
        $stmt_new_occupancy = mysqli_prepare($conn, $query_new_occupancy);
        mysqli_stmt_bind_param($stmt_new_occupancy, "ii", $new_room_id, $new_room_id);
        mysqli_stmt_execute($stmt_new_occupancy);
        mysqli_stmt_close($stmt_new_occupancy);
        
        // Commit transaction
        mysqli_commit($conn);
        echo "<script>alert('Room changed successfully! All records updated consistently.');</script>";

    } catch (mysqli_sql_exception $exception) {
        mysqli_rollback($conn);
        echo "<script>alert('Failed to change room: " . addslashes($exception->getMessage()) . "');</script>";
    } catch (Exception $exception) {
        mysqli_rollback($conn);
        echo "<script>alert('Failed to change room: " . addslashes($exception->getMessage()) . "');</script>";
    }
  }
  
  // Handle approve pending student
  if (isset($_POST['approve_pending_student'])) {
    $student_id = $_POST['student_id'];
    $token = $_POST['token'];
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Step 1: Get pending student details
        $query_get_pending = "SELECT * FROM pending_students WHERE Student_id = ? AND token = ?";
        $stmt_get_pending = mysqli_prepare($conn, $query_get_pending);
        mysqli_stmt_bind_param($stmt_get_pending, "ss", $student_id, $token);
        mysqli_stmt_execute($stmt_get_pending);
        $result_pending = mysqli_stmt_get_result($stmt_get_pending);
        $pending_details = mysqli_fetch_assoc($result_pending);
        mysqli_stmt_close($stmt_get_pending);
        
        if (!$pending_details) {
            throw new Exception("Pending student not found or invalid token.");
        }
        
        // Step 2: Insert student into main Student table
        $query_insert_student = "INSERT INTO Student (Student_id, Fname, Lname, gender, Mob_no, Dept, Year_of_study, Email, Pwd)
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_insert_student = mysqli_prepare($conn, $query_insert_student);
        mysqli_stmt_bind_param($stmt_insert_student, "sssssisss", 
                               $pending_details['Student_id'],
                               $pending_details['Fname'],
                               $pending_details['Lname'],
                               $pending_details['gender'],
                               $pending_details['Mob_no'],
                               $pending_details['Dept'],
                               $pending_details['Year_of_study'],
                               $pending_details['Email'],
                               $pending_details['Pwd']);
        mysqli_stmt_execute($stmt_insert_student);
        mysqli_stmt_close($stmt_insert_student);
        
        // Step 3: Remove from pending_students table
        $query_delete_pending = "DELETE FROM pending_students WHERE Student_id = ?";
        $stmt_delete_pending = mysqli_prepare($conn, $query_delete_pending);
        mysqli_stmt_bind_param($stmt_delete_pending, "s", $student_id);
        mysqli_stmt_execute($stmt_delete_pending);
        mysqli_stmt_close($stmt_delete_pending);
        
        // Commit transaction
        mysqli_commit($conn);
        echo "<script>alert('Pending student approved and added to system successfully!');</script>";
        
    } catch (mysqli_sql_exception $exception) {
        mysqli_rollback($conn);
        echo "<script>alert('Failed to approve pending student: " . addslashes($exception->getMessage()) . "');</script>";
    }
  }
  
  // Handle payment history view
  $viewPaymentStudentId = $_GET['view_payment_student_id'] ?? null;
  $paymentHistory = [];
  $paymentSummary = [
      'total_transactions' => 0,
      'total_paid' => 0,
      'pending_amount' => 0,
      'completed_payments' => 0,
      'success_rate' => 0
  ];
  $selectedStudentInfo = null;

  if ($viewPaymentStudentId) {
      // Get student information
      $studentQuery = "SELECT Student_id, Fname, Lname FROM Student WHERE Student_id = ?";
      $stmt = $conn->prepare($studentQuery);
      if ($stmt) {
          $stmt->bind_param("s", $viewPaymentStudentId);
          $stmt->execute();
          $result = $stmt->get_result();
          $selectedStudentInfo = $result->fetch_assoc();
          $stmt->close();
      }
      
      if ($selectedStudentInfo) {
          // Get payment history
          $paymentQuery = "SELECT * FROM payments WHERE student_id = ? ORDER BY created_at DESC";
          $stmt = $conn->prepare($paymentQuery);
          if ($stmt) {
              $stmt->bind_param("s", $viewPaymentStudentId);
              $stmt->execute();
              $result = $stmt->get_result();
              while ($row = $result->fetch_assoc()) {
                  $paymentHistory[] = $row;
              }
              $stmt->close();
          }
          
          $paymentSummary['total_transactions'] = count($paymentHistory);
          foreach ($paymentHistory as $payment) {
              if (($payment['status'] ?? '') === 'completed') {
                  $paymentSummary['total_paid'] += (float)$payment['amount'];
                  $paymentSummary['completed_payments']++;
              }
          }
          $paymentSummary['success_rate'] = $paymentSummary['total_transactions'] > 0 
              ? round(($paymentSummary['completed_payments'] / $paymentSummary['total_transactions']) * 100, 1) 
              : 0;
      }
  }
  
  // Handle reject pending student
  if (isset($_POST['reject_pending_student'])) {
    $student_id = $_POST['student_id'];
    
    try {
        // Delete from pending_students table
        $query_delete_pending = "DELETE FROM pending_students WHERE Student_id = ?";
        $stmt_delete_pending = mysqli_prepare($conn, $query_delete_pending);
        mysqli_stmt_bind_param($stmt_delete_pending, "s", $student_id);
        mysqli_stmt_execute($stmt_delete_pending);
        
        if (mysqli_stmt_affected_rows($stmt_delete_pending) > 0) {
            echo "<script>alert('Pending student rejected successfully!');</script>";
        } else {
            echo "<script>alert('Pending student not found!');</script>";
        }
        mysqli_stmt_close($stmt_delete_pending);
        
    } catch (mysqli_sql_exception $exception) {
        echo "<script>alert('Failed to reject pending student: " . addslashes($exception->getMessage()) . "');</script>";
    }
  }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>PLYS | Student Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <link rel="stylesheet" href="admin_styles.css">
</head>
<body>
<br><br><br>
<div class="container">
    <!-- Header -->
    <div class="header">
        <h1><i class="fa fa-graduation-cap"></i> Student Management</h1>
        <p>Manage student information and allocations</p>
    </div>

    <!-- Search Section -->
    <div class="section">
        <div class="section-header">
            <h3><i class="fa fa-search"></i> Search Students</h3>
        </div>
        
        <div class="mail_grid_w3l">
            <form action="students.php" method="post">
                <div class="form-group">
                    <input type="text" class="form-control" placeholder="Search by Roll Number" name="search_box" value="<?php echo isset($_POST['search_box']) ? htmlspecialchars($_POST['search_box']) : ''; ?>">
                </div>
                <button type="submit" class="btn-submit" name="search">Search</button>
            </form>
        </div>
    </div>

    <!-- Main Content Area with Two Columns -->
    <div style="display: flex; gap: 20px; justify-content: center;">
        <!-- Left Column: Student Management -->
        <div style="flex: 1; max-width: 1200px;">
            <!-- Students Table Section -->
            <div class="section">
                <div class="section-header">
                    <h3><i class="fa fa-users"></i> Student Management</h3>
                    <div class="form-group" style="margin: 0; min-width: 200px;">
                        <select id="studentView" class="form-control" onchange="toggleStudentView()">
                            <option value="current">Current Students</option>
                            <option value="pending">Pending Students</option>
                        </select>
                    </div>
                </div>
                    
                <div class="table-container">
            <!-- Current Students Table -->
            <div id="currentStudentsTable">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Student ID</th>
                            <th>Contact</th>
                            <th>Email</th>
                            <th>Hostel</th>
                            <th>Room</th>
                            <th>Bed</th>
                            <th>Price</th>
                            <th>Food Plan</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (isset($_POST['search'])) {
                            $search_box = mysqli_real_escape_string($conn, $_POST['search_box']);
                            $query = "SELECT s.*, h.Hostel_name, r.Room_No, r.current_occupancy, ba.bed_number, ba.start_date, ba.end_date, ba.allocation_price, ba.include_food, ba.food_plan
                                    FROM Student s
                                    LEFT JOIN Hostel h ON s.Hostel_id = h.Hostel_id
                                    LEFT JOIN Room r ON s.Room_id = r.Room_id
                                    LEFT JOIN bed_allocation ba ON s.Student_id = ba.student_id AND ba.is_active = 1
                                    WHERE s.Student_id LIKE '$search_box%'";
                        } else {
                            $query = "SELECT s.*, h.Hostel_name, r.Room_No, r.current_occupancy, ba.bed_number, ba.start_date, ba.end_date, ba.allocation_price, ba.include_food, ba.food_plan
                                    FROM Student s
                                    LEFT JOIN Hostel h ON s.Hostel_id = h.Hostel_id
                                    LEFT JOIN Room r ON s.Room_id = r.Room_id
                                    LEFT JOIN bed_allocation ba ON s.Student_id = ba.student_id AND ba.is_active = 1";
                        }
                        
                        $result = mysqli_query($conn, $query);
                        
                        if (mysqli_num_rows($result) == 0) {
                            echo '<tr><td colspan="12">No students found</td></tr>';
                        } else {
                            while ($row = mysqli_fetch_assoc($result)) {
                                $student_name = htmlspecialchars($row['Fname'] . " " . $row['Lname']);
                                $hostel_name = $row['Hostel_name'] ?? 'Not Allocated';
                                $room_no = $row['Room_No'] ?? 'Not Allocated';
                                $bed_number = $row['bed_number'] ?? 'N/A';
                                $start_date = $row['start_date'] ? date('Y-m-d', strtotime($row['start_date'])) : 'N/A';
                                $end_date = $row['end_date'] ? date('Y-m-d', strtotime($row['end_date'])) : 'N/A';
                                
                                // Price calculation with leave adjustments using centralized function
                                $calculated_price = 'N/A';
                                if ($row['bed_number'] && $row['current_occupancy'] > 0) {
                                    $allocation_data = [
                                        'include_food' => $row['include_food'],
                                        'food_plan' => $row['food_plan']
                                    ];
                                    
                                    // Get student's price with leave adjustments
                                    $student_pricing = getStudentPriceWithLeaveAdjustments($row['Student_id'], $conn);
                                    
                                    if (isset($student_pricing['error'])) {
                                        $calculated_price = '₹' . number_format(calculateStudentPrice($allocation_data, $row['current_occupancy']), 2);
                                    } else {
                                        $calculated_price = '₹' . number_format($student_pricing['final_price'], 2);
                                        if ($student_pricing['leave_reduction'] > 0) {
                                            $calculated_price .= ' <small style="color: #28a745;">(-₹' . number_format($student_pricing['leave_reduction'], 2) . ' leave)</small>';
                                        }
                                    }
                                }
                                
                                // Food plan display
                                $food_plan = 'No Food';
                                if ($row['include_food'] == 1 && $row['food_plan']) {
                                    $food_plan = ucfirst($row['food_plan']);
                                }
                                
                                echo "<tr>
                                        <td>{$student_name}</td>
                                        <td>{$row['Student_id']}</td>
                                        <td>{$row['Mob_no']}</td>
                                        <td>{$row['Email']}</td>
                                        <td>{$hostel_name}</td>
                                        <td>{$room_no}</td>
                                        <td>{$bed_number}</td>
                                        <td>{$calculated_price}</td>
                                        <td>{$food_plan}</td>
                                        <td>{$start_date}</td>
                                        <td>{$end_date}</td>
                                        <td>
                                            <button class='btn btn-primary btn-sm' onclick='editStudent(\"{$row['Student_id']}\", \"{$row['Fname']}\", \"{$row['Lname']}\", \"{$row['Mob_no']}\", \"{$row['Email']}\", \"{$row['Dept']}\", \"{$row['Year_of_study']}\")'>Edit</button>
                                            <button class='btn btn-info btn-sm' onclick='viewPaymentHistory(\"{$row['Student_id']}\", \"{$row['Fname']}\", \"{$row['Lname']}\")'>Payment History</button>";
                                
                                if ($row['Room_id']) {
                                    echo "<a href='admin_change_room.php?student_id={$row['Student_id']}' class='btn btn-primary btn-sm'>Change Room</a>
                                          <button class='btn btn-danger btn-sm' onclick='deleteStudent(\"{$row['Student_id']}\", \"{$row['Hostel_id']}\", \"{$row['Room_id']}\")'>Vacate</button>";
                                }
                                
                                echo "</td>
                                      </tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pending Students Table -->
            <div id="pendingStudentsTable" class="table-responsive" style="display: none;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Student ID</th>
                            <th>Contact</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>Year</th>
                            <th>Gender</th>
                            <th>Admission Date</th>
                            <th>Token Expires</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $pending_query = "SELECT ps.* 
                                        FROM pending_students ps
                                        ORDER BY ps.admission_date DESC, ps.Student_id ASC";
                        
                        $pending_result = mysqli_query($conn, $pending_query);
                        
                        if (mysqli_num_rows($pending_result) == 0) {
                            echo '<tr><td colspan="10">No pending students found</td></tr>';
                        } else {
                            while ($pending_row = mysqli_fetch_assoc($pending_result)) {
                                $student_name = htmlspecialchars($pending_row['Fname'] . " " . $pending_row['Lname']);
                                $admission_date = $pending_row['admission_date'] ? date('Y-m-d', strtotime($pending_row['admission_date'])) : 'Not Set';
                                $expires_at = date('Y-m-d H:i', strtotime($pending_row['expires_at']));
                                
                                echo "<tr>
                                        <td>{$student_name}</td>
                                        <td>{$pending_row['Student_id']}</td>
                                        <td>{$pending_row['Mob_no']}</td>
                                        <td>{$pending_row['Email']}</td>
                                        <td>{$pending_row['Dept']}</td>
                                        <td>{$pending_row['Year_of_study']}</td>
                                        <td>{$pending_row['gender']}</td>
                                        <td>{$admission_date}</td>
                                        <td>{$expires_at}</td>
                                        <td>
                                            <button class='btn btn-primary btn-sm' onclick='approvePendingStudent(\"{$pending_row['Student_id']}\", \"{$pending_row['token']}\")'>Approve</button>
                                            <button class='btn btn-danger btn-sm' onclick='rejectPendingStudent(\"{$pending_row['Student_id']}\")'>Reject</button>
                                        </td>
                                      </tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- End Left Column -->
</div>
<!-- End Main Content Area -->

<!-- Payment History Section (Right Side) -->
<?php if ($selectedStudentInfo): ?>
<div style="position: fixed; right: 20px; top: 100px; width: 350px; max-height: 80vh; overflow-y: auto; z-index: 1000; background: white; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.15);">
    <div class="section" style="margin: 0;">
        <div class="section-header">
            <h3><i class="fa fa-credit-card"></i> Payment History</h3>
            <div class="form-group" style="margin: 0; min-width: 150px;">
                <span style="color: #666; font-size: 0.8em;">
                    <?php echo htmlspecialchars($selectedStudentInfo['Fname'] . ' ' . $selectedStudentInfo['Lname']); ?> 
                    (<?php echo htmlspecialchars($selectedStudentInfo['Student_id']); ?>)
                </span>
                <a href="students.php" class="btn btn-sm btn-secondary" style="margin-left: 5px; padding: 2px 6px; font-size: 0.8em;">
                    <i class="fa fa-times"></i>
                </a>
            </div>
        </div>
        
        <!-- Payment Summary Cards -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px;">
            <div style="background: #f8f9fa; padding: 10px; border-radius: 6px; border-left: 3px solid #007bff;">
                <h6 style="margin: 0 0 3px 0; color: #007bff; font-size: 0.8em;">Total Transactions</h6>
                <p style="margin: 0; font-size: 1em; font-weight: bold;"><?php echo $paymentSummary['total_transactions']; ?></p>
            </div>
            <div style="background: #f8f9fa; padding: 10px; border-radius: 6px; border-left: 3px solid #28a745;">
                <h6 style="margin: 0 0 3px 0; color: #28a745; font-size: 0.8em;">Total Paid</h6>
                <p style="margin: 0; font-size: 1em; font-weight: bold;">NPR <?php echo number_format($paymentSummary['total_paid'], 0); ?></p>
            </div>
            <div style="background: #f8f9fa; padding: 10px; border-radius: 6px; border-left: 3px solid #ffc107;">
                <h6 style="margin: 0 0 3px 0; color: #ffc107; font-size: 0.8em;">Completed</h6>
                <p style="margin: 0; font-size: 1em; font-weight: bold;"><?php echo $paymentSummary['completed_payments']; ?></p>
            </div>
            <div style="background: #f8f9fa; padding: 10px; border-radius: 6px; border-left: 3px solid #17a2b8;">
                <h6 style="margin: 0 0 3px 0; color: #17a2b8; font-size: 0.8em;">Success Rate</h6>
                <p style="margin: 0; font-size: 1em; font-weight: bold;"><?php echo $paymentSummary['success_rate']; ?>%</p>
            </div>
        </div>
        
        <div class="table-container" style="max-height: 300px; overflow-y: auto;">
            <?php if (count($paymentHistory) > 0): ?>
                <table class="table" style="font-size: 0.85em;">
                    <thead>
                        <tr>
                            <th style="padding: 5px;">Date</th>
                            <th style="padding: 5px;">Amount</th>
                            <th style="padding: 5px;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($paymentHistory as $payment): ?>
                            <tr>
                                <td style="padding: 5px;"><?php echo date('d M', strtotime($payment['created_at'])); ?></td>
                                <td style="padding: 5px;">NPR <?php echo number_format((float)$payment['amount'], 0); ?></td>
                                <td style="padding: 5px;">
                                    <span class="badge" style="
                                        <?php if ($payment['status'] === 'completed'): ?>
                                            background-color: #28a745; color: white;
                                        <?php elseif ($payment['status'] === 'pending'): ?>
                                            background-color: #ffc107; color: black;
                                        <?php else: ?>
                                            background-color: #dc3545; color: white;
                                        <?php endif; ?>
                                        font-size: 0.7em; padding: 2px 4px;
                                    ">
                                        <?php echo htmlspecialchars(ucfirst($payment['status'] ?? 'N/A')); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-info" style="font-size: 0.85em;">No payment history found.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Edit Student Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('editModal')">&times;</span>
        <h2>Edit Student Information</h2>
        <form action="students.php" method="post">
            <input type="hidden" name="student_id" id="edit_student_id">
            <div class="form-group">
                <label>First Name:</label>
                <input type="text" name="fname" id="edit_fname" required>
            </div>
            <div class="form-group">
                <label>Last Name:</label>
                <input type="text" name="lname" id="edit_lname" required>
            </div>
            <div class="form-group">
                <label>Contact Number:</label>
                <input type="text" name="mob_no" id="edit_mob_no" required>
            </div>
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" id="edit_email" required>
            </div>
            <div class="form-group">
                <label>Department:</label>
                <input type="text" name="dept" id="edit_dept" required>
            </div>
            <div class="form-group">
                <label>Year of Study:</label>
                <input type="text" name="year_of_study" id="edit_year_of_study" required>
            </div>
            <button type="submit" name="edit_student" class="btn-submit">Update Student</button>
        </form>
    </div>
</div>

<!-- Change Room Modal -->
<div id="changeRoomModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('changeRoomModal')">&times;</span>
        <h2>Change Room</h2>
        <form action="students.php" method="post">
            <input type="hidden" name="student_id" id="change_student_id">
            <input type="hidden" name="old_room_id" id="old_room_id">
            <div class="form-group">
                <label>Current Room:</label>
                <input type="text" id="current_room" readonly>
            </div>
            <div class="form-group">
                <label>New Room:</label>
                <select name="new_room_id" id="new_room_id" required onchange="loadAvailableBeds()">
                    <option value="">Select Room</option>
                </select>
            </div>
            <div class="form-group">
                <label>New Bed Number:</label>
                <select name="new_bed_number" id="new_bed_number" required>
                    <option value="">Select Room First</option>
                </select>
            </div>
            <button type="submit" name="change_room" class="btn-submit">Change Room</button>
        </form>
    </div>
</div>

<script>
function editStudent(studentId, fname, lname, mobNo, email, dept, yearOfStudy) {
    document.getElementById('edit_student_id').value = studentId;
    document.getElementById('edit_fname').value = fname;
    document.getElementById('edit_lname').value = lname;
    document.getElementById('edit_mob_no').value = mobNo;
    document.getElementById('edit_email').value = email;
    document.getElementById('edit_dept').value = dept;
    document.getElementById('edit_year_of_study').value = yearOfStudy;
    document.getElementById('editModal').style.display = 'block';
}

function changeRoom(studentId, roomId) {
    document.getElementById('change_student_id').value = studentId;
    document.getElementById('old_room_id').value = roomId;
    document.getElementById('current_room').value = 'Room ' + roomId;
    loadAvailableRooms();
    document.getElementById('changeRoomModal').style.display = 'block';
}

function deleteStudent(studentId, hostelId, roomId) {
    if (confirm('Are you sure you want to vacate this student? This will remove their room allocation and delete all related data.')) {
        const form = document.createElement('form');
        form.method = 'post';
        form.action = 'students.php';
        
        const studentInput = document.createElement('input');
        studentInput.type = 'hidden';
        studentInput.name = 'student_id';
        studentInput.value = studentId;
        
        const hostelInput = document.createElement('input');
        hostelInput.type = 'hidden';
        hostelInput.name = 'hostel_id';
        hostelInput.value = hostelId;
        
        const roomInput = document.createElement('input');
        roomInput.type = 'hidden';
        roomInput.name = 'room_id';
        roomInput.value = roomId;
        
        const deleteInput = document.createElement('input');
        deleteInput.type = 'hidden';
        deleteInput.name = 'delete_student';
        deleteInput.value = '1';
        
        form.appendChild(studentInput);
        form.appendChild(hostelInput);
        form.appendChild(roomInput);
        form.appendChild(deleteInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

function viewPaymentHistory(studentId, fname, lname) {
    // Redirect to same page with payment history parameter
    window.location.href = 'students.php?view_payment_student_id=' + encodeURIComponent(studentId);
}

function loadAvailableRooms() {
    fetch('../includes/get_available_rooms.php')
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('new_room_id');
            select.innerHTML = '<option value="">Select Room</option>';
            
            if (data.rooms) {
                data.rooms.forEach(room => {
                    const option = document.createElement('option');
                    option.value = room.Room_id;
                    option.textContent = `Room ${room.Room_No} (${room.available_beds} beds available)`;
                    select.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Error loading rooms:', error));
}

function loadAvailableBeds() {
    const roomId = document.getElementById('new_room_id').value;
    const bedSelect = document.getElementById('new_bed_number');
    
    if (!roomId) {
        bedSelect.innerHTML = '<option value="">Select Room First</option>';
        return;
    }
    
    fetch(`../includes/get_available_beds.php?room_id=${roomId}`)
        .then(response => response.json())
        .then(data => {
            bedSelect.innerHTML = '<option value="">Select Bed</option>';
            
            if (data.success && data.beds) {
                data.beds.forEach(bed => {
                    const option = document.createElement('option');
                    option.value = bed.bed_number;
                    option.textContent = `Bed ${bed.bed_number}`;
                    bedSelect.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Error loading beds:', error));
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

function toggleStudentView() {
    const view = document.getElementById('studentView').value;
    const currentTable = document.getElementById('currentStudentsTable');
    const pendingTable = document.getElementById('pendingStudentsTable');
    
    if (view === 'current') {
        currentTable.style.display = 'block';
        pendingTable.style.display = 'none';
    } else {
        currentTable.style.display = 'none';
        pendingTable.style.display = 'block';
    }
}

function approvePendingStudent(studentId, token) {
    if (confirm('Are you sure you want to approve this pending student?')) {
        // Create form to submit approval
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'students.php';
        
        const studentInput = document.createElement('input');
        studentInput.type = 'hidden';
        studentInput.name = 'student_id';
        studentInput.value = studentId;
        
        const tokenInput = document.createElement('input');
        tokenInput.type = 'hidden';
        tokenInput.name = 'token';
        tokenInput.value = token;
        
        const approveInput = document.createElement('input');
        approveInput.type = 'hidden';
        approveInput.name = 'approve_pending_student';
        approveInput.value = '1';
        
        form.appendChild(studentInput);
        form.appendChild(tokenInput);
        form.appendChild(approveInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

function rejectPendingStudent(studentId) {
    if (confirm('Are you sure you want to reject this pending student?')) {
        // Create form to submit rejection
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'students.php';
        
        const studentInput = document.createElement('input');
        studentInput.type = 'hidden';
        studentInput.name = 'student_id';
        studentInput.value = studentId;
        
        const rejectInput = document.createElement('input');
        rejectInput.type = 'hidden';
        rejectInput.name = 'reject_pending_student';
        rejectInput.value = '1';
        
        form.appendChild(studentInput);
        form.appendChild(rejectInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}
</script>

</body>
</html>
