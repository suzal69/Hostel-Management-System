<?php
// FILE: C:\xampp\htdocs\project\admin\create_hm.php

// 1. ESSENTIAL INCLUDES AND SESSION START
// These must be at the very top to handle redirects and session changes.
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/functions.inc.php'; // For sendEmail and database functions

// 2. HEADER-MODIFYING LOGIC (Admin Check)
// This MUST happen before any HTML is output, otherwise the header() fails.
if (empty($_SESSION['admin_username']) && (empty($_SESSION['isadmin']) || $_SESSION['isadmin'] != 1)) {
    header('Location: ../login-hostel_manager.php');
    exit;
}

// 3. HEADER-MODIFYING LOGIC (Approval/Rejection Action)
// The redirect is the critical part that must run before output.
if (isset($_GET['action']) && isset($_GET['token'])) {
    $token = $_GET['token'];
    $action = $_GET['action'];

    $pending_manager = getPendingManagerByToken($conn, $token);
    
    if ($pending_manager) {
        // Ensure the applicant has verified their email before allowing admin approval
        if ($action == 'approve' && empty($pending_manager['is_verified'])) {
            $_SESSION['message'] = "Cannot approve: manager has not verified their email yet.";
            header('Location: create_hm.php');
            exit;
        }
            if ($action == 'resend') {
                // Resend verification email to pending manager
                $verifyLink = "http://localhost/project/verify_hm.php?token=" . $pending_manager['token'];
                $body = "<h3>Hello, " . htmlspecialchars($pending_manager['Fname']) . "</h3>\n" .
                        "<p>Please click the link below to verify your email address (valid for 24 hours):</p>\n" .
                        "<a href='" . $verifyLink . "'>" . $verifyLink . "</a>\n" .
                        "<p>After verification, an admin will review and approve your manager account.</p>";

                $sent = sendEmail($pending_manager['email'], 'Verify your Hostel Manager Account', $body);
                if ($sent) {
                    // Clear email_failed flag and update attempts/time
                    $sqlUpd = "UPDATE pending_hostel_manager SET email_failed = 0, last_email_attempt = NOW(), email_attempts = COALESCE(email_attempts,0) + 1 WHERE token = ?";
                    $stmtUpd = mysqli_stmt_init($conn);
                    if (mysqli_stmt_prepare($stmtUpd, $sqlUpd)) {
                        mysqli_stmt_bind_param($stmtUpd, "s", $token);
                        mysqli_stmt_execute($stmtUpd);
                        mysqli_stmt_close($stmtUpd);
                    }
                    $_SESSION['message'] = "Verification email resent successfully.";
                } else {
                    $sqlUpd = "UPDATE pending_hostel_manager SET email_failed = 1, last_email_attempt = NOW(), email_attempts = COALESCE(email_attempts,0) + 1 WHERE token = ?";
                    $stmtUpd = mysqli_stmt_init($conn);
                    if (mysqli_stmt_prepare($stmtUpd, $sqlUpd)) {
                        mysqli_stmt_bind_param($stmtUpd, "s", $token);
                        mysqli_stmt_execute($stmtUpd);
                        mysqli_stmt_close($stmtUpd);
                    }
                    $_SESSION['message'] = "Failed to resend verification email; marked as failed.";
                }
            }
        if ($action == 'approve') {
            // Add to hostel_manager table
            $sql = "INSERT INTO hostel_manager (Username, Fname, Lname, Mob_no, Hostel_id, Pwd, Isadmin, email, approval_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'approved')";
            $stmt = mysqli_stmt_init($conn);
            if (mysqli_stmt_prepare($stmt, $sql)) {
                mysqli_stmt_bind_param($stmt, "sssiisis", $pending_manager['Username'], $pending_manager['Fname'], $pending_manager['Lname'], $pending_manager['Mob_no'], $pending_manager['Hostel_id'], $pending_manager['Pwd'], $pending_manager['Isadmin'], $pending_manager['email']);
                mysqli_stmt_execute($stmt);
                
                // Get the newly inserted manager ID
                $manager_id = mysqli_insert_id($conn);
                
                // Update the Hostel table to set the Hostel_man_id
                $update_hostel_sql = "UPDATE Hostel SET Hostel_man_id = ? WHERE Hostel_id = ?";
                $update_hostel_stmt = mysqli_stmt_init($conn);
                if (mysqli_stmt_prepare($update_hostel_stmt, $update_hostel_sql)) {
                    mysqli_stmt_bind_param($update_hostel_stmt, "ii", $manager_id, $pending_manager['Hostel_id']);
                    mysqli_stmt_execute($update_hostel_stmt);
                    mysqli_stmt_close($update_hostel_stmt);
                }
            }

            // Delete from pending_hostel_manager
            deletePendingManager($conn, $token);

            // Send approval email
            sendEmail($pending_manager['email'], 'Account Approved', 'Your account has been approved. You can now login.');

            // Add notification for admin about new hostel manager
            $notification_message = "New hostel manager '{$pending_manager['Fname']} {$pending_manager['Lname']}' (Username: {$pending_manager['Username']}) has been approved.";
            $stmt_notify = mysqli_stmt_init($conn);
            $sql_notify = "INSERT INTO notifications (recipient_type, message) VALUES (?, ?)";
            if (mysqli_stmt_prepare($stmt_notify, $sql_notify)) {
                $recipient_type = 'admin';
                mysqli_stmt_bind_param($stmt_notify, "ss", $recipient_type, $notification_message);
                mysqli_stmt_execute($stmt_notify);
            }

        } else if ($action == 'reject') {
            // Delete from pending_hostel_manager
            deletePendingManager($conn, $token);

            // Send rejection email
            sendEmail($pending_manager['email'], 'Account Rejected', 'Your account has been rejected.');
        }
        $_SESSION['message'] = "Hostel manager request " . $action . "ed successfully.";
    } else {
        $_SESSION['message'] = "Error: Pending manager not found or invalid token.";
    }
    
    // **THIS IS THE CRITICAL REDIRECT MOVED TO THE TOP** (Former line 64 logic)
    header('Location: create_hm.php');
    exit;
}

// 4. DATA FETCHING LOGIC (Read-only data needed for the page)
// Fetch pending managers AFTER processing any actions
$pending_managers = getPendingManagers($conn);
// Fetch all hostels to look up names by ID
$hostels_query = "SELECT Hostel_id, Hostel_name FROM hostel";
$hostels_result = mysqli_query($conn, $hostels_query);
$hostels = [];
if ($hostels_result) {
    while ($row = mysqli_fetch_assoc($hostels_result)) {
        $hostels[$row['Hostel_id']] = $row['Hostel_name'];
    }
}


// 5. INCLUDE THE HEADER (HTML OUTPUT STARTS HERE)
// This is where the output begins, locking headers.
require_once 'admin_header.php';

?>

<?php if (!empty($_SESSION['message'])): ?>
    <div class="alert alert-success" style="margin: 20px auto; max-width: 900px;"><?= htmlspecialchars($_SESSION['message']) ?></div>
    <?php unset($_SESSION['message']); ?>
<?php endif; ?>
<br><br><br>
<div class="container">
    <!-- Header -->
    <div class="header">
        <h1><i class="fa fa-user-plus"></i> Pending Hostel Manager Requests</h1>
        <p>Review and approve hostel manager applications</p>
    </div>

    <!-- Table Section -->
    <div class="section">
        <div class="section-header">
            <h3><i class="fa fa-list"></i> Manager Applications</h3>
        </div>
        
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Email Status</th>
                        <th>Verified</th>
                        <th>Hostel ID</th>
                        <th>Hostel Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pending_managers)): ?>
                        <tr>
                            <td colspan="8" class="text-center">No pending requests.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pending_managers as $manager): ?>
                        <tr>
                            <td><?= htmlspecialchars($manager['Username']) ?></td>
                            <td><?= htmlspecialchars($manager['Fname'] . ' ' . $manager['Lname']) ?></td>
                            <td><?= htmlspecialchars($manager['email']) ?></td>
                            <td><?php
                                if (!empty($manager['email_failed'])) {
                                    echo '<span class="alert-danger" style="padding: 2px 8px; border-radius: 4px; font-size: 12px;">Failed</span>';
                                } elseif (!empty($manager['email_attempts'])) {
                                    echo '<span class="alert-success" style="padding: 2px 8px; border-radius: 4px; font-size: 12px;">Sent (' . intval($manager['email_attempts']) . ')</span>';
                                } else {
                                    echo '<span style="color: #6c757d; font-size: 12px;">Pending</span>';
                                }
                            ?></td>
                            <td><?= !empty($manager['is_verified']) ? '<span class="alert-success" style="padding: 2px 8px; border-radius: 4px; font-size: 12px;">Yes</span>' : '<span class="btn btn-sm" style="background: #ffcc00; color: #003366;">No</span>' ?></td>
                            <td><?= htmlspecialchars($manager['Hostel_id']) ?></td>
                            <td>
                                <?php 
                                    // Look up the hostel name using the manager's hostel_id
                                    echo htmlspecialchars($hostels[$manager['Hostel_id']] ?? 'N/A'); 
                                ?>
                            </td>
                            <td>
                                <a href="?action=approve&token=<?= $manager['token'] ?>" class="btn btn-primary btn-sm">Approve</a>
                                <a href="?action=reject&token=<?= $manager['token'] ?>" class="btn btn-danger btn-sm">Reject</a>
                                <?php if (empty($manager['is_verified'])): ?>
                                    <a href="?action=resend&token=<?= $manager['token'] ?>" class="btn btn-primary btn-sm">Resend</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<br><br><br>
<br><br><br>
<footer class="py-5" style="background:#36454F;">
    <div class="container py-md-5">
        <div class="footer-logo mb-5 text-center">
            <a class="navbar-brand" href="admin_home.php">Peaceful Living for Young <span class="display"> Scholars</span></a>
        </div>
    </div>
    <div class="footer-grid">
        <div class="list-footer">
            <ul class="footer-nav text-center">
                <li><a href="admin_home.php">Home</a></li>
                <li><a href="create_hm.php">Appoint</a></li>
                <li><a href="students.php">Students</a></li>
                <li><a href="admin_contact.php">Contact</a></li>
                <li><a href="admin_profile.php">Profile</a></li>
            </ul>
        </div>
    </div>
</footer>

</body>
</html>