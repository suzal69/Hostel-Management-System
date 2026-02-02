<?php
  require_once __DIR__ . '/../includes/config.inc.php';
  require_once __DIR__ . '/admin_header.php';

  // Use session variables set by includes/login-hm.inc.php
  if (empty($_SESSION['admin_username']) || empty($_SESSION['isadmin']) || $_SESSION['isadmin'] != 1) {
    header('Location: ../login-hostel_manager.php');
    exit;
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
        $success_message = "Room changed successfully! Student moved to Room " . $new_room_id . ", Bed " . $new_bed_number;

    } catch (mysqli_sql_exception $exception) {
        mysqli_rollback($conn);
        $error_message = "Failed to change room. Please try again. Error: " . $exception->getMessage();
    }
  }

  // Get all students with current allocations for dropdown
  $query_students = "SELECT s.Student_id, s.Fname, s.Lname, s.Room_id, s.Hostel_id,
                            h.Hostel_name, r.Room_No, ba.bed_number, ba.start_date, ba.end_date
                     FROM Student s
                     LEFT JOIN Hostel h ON s.Hostel_id = h.Hostel_id
                     LEFT JOIN Room r ON s.Room_id = r.Room_id
                     LEFT JOIN bed_allocation ba ON s.Student_id = ba.student_id AND ba.is_active = 1
                     WHERE s.Room_id IS NOT NULL
                     ORDER BY s.Fname, s.Lname";
  $result_students = mysqli_query($conn, $query_students);

  // Pre-select student if coming from students.php
  $preselected_student_id = isset($_GET['student_id']) ? $_GET['student_id'] : '';
  $preselected_student = null;
  
  if ($preselected_student_id) {
      // Get the preselected student's details
      $query_preselect = "SELECT s.Student_id, s.Fname, s.Lname, s.Room_id, s.Hostel_id,
                                 h.Hostel_name, r.Room_No, ba.bed_number, ba.start_date, ba.end_date
                          FROM Student s
                          LEFT JOIN Hostel h ON s.Hostel_id = h.Hostel_id
                          LEFT JOIN Room r ON s.Room_id = r.Room_id
                          LEFT JOIN bed_allocation ba ON s.Student_id = ba.student_id AND ba.is_active = 1
                          WHERE s.Student_id = ? AND s.Room_id IS NOT NULL";
      $stmt_preselect = mysqli_prepare($conn, $query_preselect);
      mysqli_stmt_bind_param($stmt_preselect, "s", $preselected_student_id);
      mysqli_stmt_execute($stmt_preselect);
      $result_preselect = mysqli_stmt_get_result($stmt_preselect);
      $preselected_student = mysqli_fetch_assoc($result_preselect);
      mysqli_stmt_close($stmt_preselect);
  }

  // Get all available rooms from the same hostel as the student
  if ($preselected_student) {
      // If student is preselected, get rooms from their hostel only
      $query_rooms = "SELECT r.Room_id, r.Room_No, r.bed_capacity, r.current_occupancy,
                             (r.bed_capacity - r.current_occupancy) as available_beds, h.Hostel_name
                      FROM Room r
                      JOIN Hostel h ON r.Hostel_id = h.Hostel_id
                      WHERE r.bed_capacity > r.current_occupancy AND r.Hostel_id = ?
                      ORDER BY r.Room_No";
      $stmt_rooms = mysqli_prepare($conn, $query_rooms);
      mysqli_stmt_bind_param($stmt_rooms, "i", $preselected_student['Hostel_id']);
      mysqli_stmt_execute($stmt_rooms);
      $result_rooms = mysqli_stmt_get_result($stmt_rooms);
      mysqli_stmt_close($stmt_rooms);
  } else {
      // If no student is selected, get all available rooms
      $query_rooms = "SELECT r.Room_id, r.Room_No, r.bed_capacity, r.current_occupancy,
                             (r.bed_capacity - r.current_occupancy) as available_beds, h.Hostel_name, h.Hostel_id
                      FROM Room r
                      JOIN Hostel h ON r.Hostel_id = h.Hostel_id
                      WHERE r.bed_capacity > r.current_occupancy
                      ORDER BY h.Hostel_name, r.Room_No";
      $result_rooms = mysqli_query($conn, $query_rooms);
  }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>PLYS | Admin Change Room</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <link rel="stylesheet" href="admin_styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body>
<br><br><br>
<div class="container">
    <!-- Header -->
    <div class="header">
        <h1><i class="fa fa-exchange"></i> Admin Change Room</h1>
        <p>Change student room allocations and manage bed assignments</p>
    </div>

    <!-- Change Room Form Section -->
    <div class="section">
        <div class="section-header">
            <h3><i class="fa fa-bed"></i> Room Allocation</h3>
        </div>
        
        <div class="mail_grid_w3l">
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
        
        <form action="admin_change_room.php" method="post">
                <input type="hidden" name="old_room_id" id="old_room_id">
                
                <div class="form-group">
                    <label for="student_id">Select Student:</label>
                    <select name="student_id" id="student_id" class="form-control" required onchange="updateCurrentInfo()">
                        <option value="">Select Student</option>
                        <?php 
                        mysqli_data_seek($result_students, 0);
                        while ($student = mysqli_fetch_assoc($result_students)): ?>
                            <option value="<?php echo $student['Student_id']; ?>" 
                                    data-room-id="<?php echo $student['Room_id']; ?>"
                                    data-hostel-name="<?php echo htmlspecialchars($student['Hostel_name']); ?>"
                                    data-room-no="<?php echo htmlspecialchars($student['Room_No']); ?>"
                                    data-bed-number="<?php echo htmlspecialchars($student['bed_number']); ?>"
                                    data-start-date="<?php echo htmlspecialchars($student['start_date']); ?>"
                                    data-end-date="<?php echo htmlspecialchars($student['end_date']); ?>"
                                    <?php echo ($preselected_student_id == $student['Student_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($student['Fname'] . ' ' . $student['Lname'] . ' (' . $student['Student_id'] . ')'); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="current-info" id="current_info" <?php if ($preselected_student) echo ''; else echo 'style="display: none;"'; ?>>
                    <h4>Current Allocation</h4>
                    <p><strong>Student ID:</strong> <span id="current_student_id"><?php echo htmlspecialchars($preselected_student['Student_id'] ?? ''); ?></span></p>
                    <p><strong>Hostel:</strong> <span id="current_hostel"><?php echo htmlspecialchars($preselected_student['Hostel_name'] ?? ''); ?></span></p>
                    <p><strong>Room:</strong> <span id="current_room"><?php echo htmlspecialchars($preselected_student['Room_No'] ?? ''); ?></span></p>
                    <p><strong>Bed:</strong> <span id="current_bed"><?php echo htmlspecialchars($preselected_student['bed_number'] ?? ''); ?></span></p>
                    <p><strong>Start Date:</strong> <span id="current_start_date"><?php echo htmlspecialchars($preselected_student['start_date'] ?? ''); ?></span></p>
                    <p><strong>End Date:</strong> <span id="current_end_date"><?php echo htmlspecialchars($preselected_student['end_date'] ?? ''); ?></span></p>
                    <input type="hidden" id="old_room_id" value="<?php echo htmlspecialchars($preselected_student['Room_id'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="new_room_id">Select New Room:</label>
                    <select name="new_room_id" id="new_room_id" class="form-control" required onchange="loadAvailableBeds()">
                        <option value="">Select Room</option>
                        <?php mysqli_data_seek($result_rooms, 0); ?>
                        <?php while ($room = mysqli_fetch_assoc($result_rooms)): ?>
                            <option value="<?php echo $room['Room_id']; ?>" 
                                    data-available-beds="<?php echo $room['available_beds']; ?>"
                                    data-hostel-name="<?php echo htmlspecialchars($room['Hostel_name']); ?>">
                                <?php echo htmlspecialchars($room['Hostel_name'] . ' - Room ' . $room['Room_No'] . ' (' . $room['available_beds'] . ' beds available)'); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="new_bed_number">Select New Bed:</label>
                    <select name="new_bed_number" id="new_bed_number" class="form-control" required>
                        <option value="">Select Room First</option>
                    </select>
                </div>
                
                <button type="submit" name="change_room" class="btn-submit">Change Room</button>
                <a href="students.php" class="btn btn-primary btn-sm" style="margin-left: 10px;">Back to Students</a>
            </form>
        </div>
    </div>
</div>

<script>
function updateCurrentInfo() {
    const select = document.getElementById('student_id');
    const selectedOption = select.options[select.selectedIndex];
    const currentInfoDiv = document.getElementById('current_info');
    
    if (select.value) {
        document.getElementById('old_room_id').value = selectedOption.dataset.roomId;
        document.getElementById('current_student_id').textContent = select.value;
        document.getElementById('current_hostel').textContent = selectedOption.dataset.hostelName;
        document.getElementById('current_room').textContent = selectedOption.dataset.roomNo;
        document.getElementById('current_bed').textContent = selectedOption.dataset.bedNumber;
        document.getElementById('current_start_date').textContent = selectedOption.dataset.startDate;
        document.getElementById('current_end_date').textContent = selectedOption.dataset.endDate;
        currentInfoDiv.style.display = 'block';
        
        // Load rooms from the same hostel as the selected student
        loadRoomsByHostel(selectedOption.dataset.roomId);
    } else {
        currentInfoDiv.style.display = 'none';
        // Load all rooms when no student is selected
        loadAllRooms();
    }
}

function loadRoomsByHostel(studentRoomId) {
    // Get the hostel_id from the selected student's room
    const studentSelect = document.getElementById('student_id');
    const selectedOption = studentSelect.options[studentSelect.selectedIndex];
    const hostelName = selectedOption.dataset.hostelName;
    
    // Filter rooms by hostel name (since we don't have hostel_id in the dropdown)
    const roomSelect = document.getElementById('new_room_id');
    roomSelect.innerHTML = '<option value="">Loading rooms...</option>';
    
    // Fetch all rooms and filter by hostel
    fetch('get_rooms_by_hostel.php?hostel_name=' + encodeURIComponent(hostelName))
        .then(response => response.json())
        .then(data => {
            roomSelect.innerHTML = '<option value="">Select Room</option>';
            
            if (data.success && data.rooms) {
                data.rooms.forEach(room => {
                    const option = document.createElement('option');
                    option.value = room.Room_id;
                    option.textContent = 'Room ' + room.Room_No + ' (' + room.available_beds + ' beds available)';
                    option.dataset.availableBeds = room.available_beds;
                    roomSelect.appendChild(option);
                });
            } else {
                roomSelect.innerHTML = '<option value="">No rooms available in this hostel</option>';
            }
        })
        .catch(error => {
            console.error('Error loading rooms:', error);
            roomSelect.innerHTML = '<option value="">Error loading rooms</option>';
        });
}

function loadAllRooms() {
    const roomSelect = document.getElementById('new_room_id');
    roomSelect.innerHTML = '<option value="">Select Room</option>';
    
    // Load all available rooms when no student is selected
    <?php if (!$preselected_student): ?>
        <?php mysqli_data_seek($result_rooms, 0); ?>
        <?php while ($room = mysqli_fetch_assoc($result_rooms)): ?>
            const option = document.createElement('option');
            option.value = '<?php echo $room['Room_id']; ?>';
            option.textContent = '<?php echo htmlspecialchars($room['Hostel_name'] . ' - Room ' . $room['Room_No'] . ' (' . $room['available_beds'] . ' beds available)'); ?>';
            option.dataset.availableBeds = '<?php echo $room['available_beds']; ?>';
            roomSelect.appendChild(option);
        <?php endwhile; ?>
    <?php endif; ?>
}

function loadAvailableBeds() {
    const roomId = document.getElementById('new_room_id').value;
    const bedSelect = document.getElementById('new_bed_number');
    
    if (!roomId) {
        bedSelect.innerHTML = '<option value="">Select Room First</option>';
        return;
    }
    
    fetch('../includes/get_available_beds.php?room_id=' + roomId)
        .then(response => response.json())
        .then(data => {
            bedSelect.innerHTML = '<option value="">Select Bed</option>';
            
            if (data.success && data.beds) {
                data.beds.forEach(bed => {
                    const option = document.createElement('option');
                    option.value = bed.bed_number;
                    option.textContent = 'Bed ' + bed.bed_number;
                    bedSelect.appendChild(option);
                });
            } else {
                bedSelect.innerHTML = '<option value="">No beds available</option>';
            }
        })
        .catch(error => {
            console.error('Error loading beds:', error);
            bedSelect.innerHTML = '<option value="">Error loading beds</option>';
        });
}

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($preselected_student): ?>
        // If student is preselected, show their info and load their hostel rooms
        updateCurrentInfo();
    <?php else: ?>
        // Load all rooms initially
        loadAllRooms();
    <?php endif; ?>
});
</script>

</body>
</html>
