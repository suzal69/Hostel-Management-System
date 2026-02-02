<?php
  require_once __DIR__ . '/../includes/config.inc.php';
  require_once __DIR__ . '/admin_header.php';

  // Use session variables set by includes/login-hm.inc.php
  if (empty($_SESSION['admin_username']) || empty($_SESSION['isadmin']) || $_SESSION['isadmin'] != 1) {
    header('Location: ../login-hostel_manager.php');
    exit;
  }

  // Handle update manager details
  if (isset($_POST['update_manager'])) {
    $manager_id = $_POST['manager_id'];
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $mob_no = $_POST['mob_no'];
    $email = $_POST['email'];
    
    $query_update = "UPDATE hostel_manager SET Fname = ?, Lname = ?, Mob_no = ?, email = ? WHERE Hostel_man_id = ?";
    $stmt = mysqli_prepare($conn, $query_update);
    mysqli_stmt_bind_param($stmt, "ssssi", $fname, $lname, $mob_no, $email, $manager_id);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "<script>alert('Manager information updated successfully!');</script>";
    } else {
        echo "<script>alert('Error updating manager information.');</script>";
    }
    mysqli_stmt_close($stmt);
  }

  // Handle approve status toggle
  if (isset($_POST['toggle_approval'])) {
    $manager_id = $_POST['manager_id'];
    $current_status = $_POST['current_status'];
    $new_status = ($current_status == 'approved') ? 'not approved' : 'approved';
    
    $query_update_status = "UPDATE hostel_manager SET approval_status = ? WHERE Hostel_man_id = ?";
    $stmt = mysqli_prepare($conn, $query_update_status);
    mysqli_stmt_bind_param($stmt, "si", $new_status, $manager_id);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "<script>alert('Manager approval status updated to: " . $new_status . "!');</script>";
    } else {
        echo "<script>alert('Error updating approval status.');</script>";
    }
    mysqli_stmt_close($stmt);
  }

  // Get all managers with their hostel details
  $query_managers = "SELECT hm.*, h.Hostel_name 
                     FROM hostel_manager hm
                     LEFT JOIN Hostel h ON hm.Hostel_id = h.Hostel_id
                     ORDER BY hm.Fname, hm.Lname";
  $result_managers = mysqli_query($conn, $query_managers);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>PLYS | Manager Details</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <link rel="stylesheet" href="admin_styles.css">
</head>
<body>
<br><br><br>
<div class="container">
    <!-- Header -->
    <div class="header">
        <h1><i class="fa fa-users"></i> Manager Details</h1>
        <p>View and manage hostel manager information</p>
    </div>

    <!-- Manager Table Section -->
    <div class="section">
        <div class="section-header">
            <h3><i class="fa fa-list"></i> Hostel Managers</h3>
        </div>
        
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Mobile Number</th>
                        <th>Email</th>
                        <th>Hostel</th>
                        <th>Approval Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (mysqli_num_rows($result_managers) == 0) {
                        echo '<tr><td colspan="7" class="text-center">No managers found</td></tr>';
                    } else {
                        while ($manager = mysqli_fetch_assoc($result_managers)) {
                            $status_class = ($manager['approval_status'] == 'approved') ? 'status-approved' : 'status-not-approved';
                            $status_text = ($manager['approval_status'] == 'approved') ? 'Approved' : 'Not Approved';
                            if($manager['Isadmin'] == '1') {
                                continue;
                            }
                                echo "<tr>
                                    <td>" . htmlspecialchars($manager['Fname']) . "</td>
                                    <td>" . htmlspecialchars($manager['Lname']) . "</td>
                                    <td>" . htmlspecialchars($manager['Mob_no']) . "</td>
                                    <td>" . htmlspecialchars($manager['email']) . "</td>
                                    <td>" . htmlspecialchars($manager['Hostel_name'] ?? 'Not Assigned') . "</td>
                                    <td><span class='{$status_class}'>{$status_text}</span></td>
                                    <td>
                                        <button class='btn btn-primary btn-sm' onclick='editManager(\"{$manager['Hostel_man_id']}\", \"" . htmlspecialchars($manager['Fname']) . "\", \"" . htmlspecialchars($manager['Lname']) . "\", \"" . htmlspecialchars($manager['Mob_no']) . "\", \"" . htmlspecialchars($manager['email']) . "\")'>Edit</button>";
                                
                                if ($manager['approval_status'] == 'approved') {
                                    echo "<button class='btn btn-danger btn-sm' onclick='toggleApproval(\"{$manager['Hostel_man_id']}\", \"approved\")'>Disapprove</button>";
                                } else {
                                    echo "<button class='btn btn-primary btn-sm' onclick='toggleApproval(\"{$manager['Hostel_man_id']}\", \"not approved\")'>Approve</button>";
                                }
                                
                                echo "</td>
                                      </tr>";
                                }
                        }
                        ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit Manager Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('editModal')">&times;</span>
        <h2>Edit Manager Information</h2>
        <form action="manager_details.php" method="post">
            <input type="hidden" name="manager_id" id="edit_manager_id">
            <div class="form-group">
                <label for="edit_fname">First Name:</label>
                <input type="text" name="fname" id="edit_fname" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="edit_lname">Last Name:</label>
                <input type="text" name="lname" id="edit_lname" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="edit_mob_no">Mobile Number:</label>
                <input type="text" name="mob_no" id="edit_mob_no" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="edit_email">Email:</label>
                <input type="email" name="email" id="edit_email" class="form-control" required>
            </div>
            <button type="submit" name="update_manager" class="btn-submit">Update Manager</button>
        </form>
    </div>
</div>

<script>
function editManager(managerId, fname, lname, mobNo, email) {
    document.getElementById('edit_manager_id').value = managerId;
    document.getElementById('edit_fname').value = fname;
    document.getElementById('edit_lname').value = lname;
    document.getElementById('edit_mob_no').value = mobNo;
    document.getElementById('edit_email').value = email;
    document.getElementById('editModal').style.display = 'block';
}

function toggleApproval(managerId, currentStatus) {
    const newStatus = (currentStatus == 'approved') ? 'not approved' : 'approved';
    const confirmMessage = `Are you sure you want to change the approval status to "${newStatus}"?`;
    
    if (confirm(confirmMessage)) {
        const form = document.createElement('form');
        form.method = 'post';
        form.action = 'manager_details.php';
        
        const managerInput = document.createElement('input');
        managerInput.type = 'hidden';
        managerInput.name = 'manager_id';
        managerInput.value = managerId;
        
        const statusInput = document.createElement('input');
        statusInput.type = 'hidden';
        statusInput.name = 'current_status';
        statusInput.value = currentStatus;
        
        const toggleInput = document.createElement('input');
        toggleInput.type = 'hidden';
        toggleInput.name = 'toggle_approval';
        toggleInput.value = '1';
        
        form.appendChild(managerInput);
        form.appendChild(statusInput);
        form.appendChild(toggleInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
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
