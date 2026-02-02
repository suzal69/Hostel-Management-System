<?php
require __DIR__ . '/includes/config.inc.php';
// Include helper functions (sendEmail)
require_once __DIR__ . '/includes/functions.inc.php';

// Log the full URL and token for debugging
$request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : (php_sapi_name() === 'cli' ? 'cli' : 'unknown');
file_put_contents(__DIR__ . '/verify_debug.log', gmdate('Y-m-d H:i:s') . " - URL: " . $request_uri . " - Token: " . (isset($_GET['token']) ? $_GET['token'] : 'none') . "\n", FILE_APPEND);

$status = '';
$message = '';

/**
 * Safely close a mysqli statement if it is a valid, initialized object.
 */
function safe_stmt_close($stmt) {
    if (!is_object($stmt)) return;
    // mysqli_stmt objects can sometimes be in an uninitialized state which
    // causes mysqli_stmt_close() to throw an Error. Guard with try/catch
    // to avoid fatal errors and log the unexpected condition.
    try {
        if ($stmt instanceof mysqli_stmt) {
            mysqli_stmt_close($stmt);
        }
    } catch (Throwable $e) {
        error_log("safe_stmt_close: failed to close stmt: " . $e->getMessage());
    }
}

if (isset($_GET['token'])) {
    $token = trim($_GET['token']);

    // Validate token format (64-character hexadecimal)
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        file_put_contents(__DIR__ . '/verify_debug.log', gmdate('Y-m-d H:i:s') . " - Invalid token format: $token\n", FILE_APPEND);
        $status = 'error';
        $message = 'Invalid or expired verification token.';
    } else {
        // First, check if token exists in pending_hostel_manager (preferred workflow)
        $sql = "SELECT * FROM pending_hostel_manager WHERE token = ?";
        $select_stmt = mysqli_stmt_init($conn);
        if (!mysqli_stmt_prepare($select_stmt, $sql)) {
            file_put_contents(__DIR__ . '/verify_debug.log', gmdate('Y-m-d H:i:s') . " - SQL error (pending lookup): " . mysqli_error($conn) . "\n", FILE_APPEND);
            $status = 'error';
            $message = 'Database error. Please try again.';
        } else {
            mysqli_stmt_bind_param($select_stmt, "s", $token);
            mysqli_stmt_execute($select_stmt);
            $result = mysqli_stmt_get_result($select_stmt);

            if ($row = mysqli_fetch_assoc($result)) {
                // Mark the pending manager as verified
                $update_sql = "UPDATE pending_hostel_manager SET is_verified = 1, verified_at = NOW() WHERE token = ?";
                $update_stmt = mysqli_stmt_init($conn);
                if (!mysqli_stmt_prepare($update_stmt, $update_sql)) {
                    file_put_contents(__DIR__ . '/verify_debug.log', gmdate('Y-m-d H:i:s') . " - Update SQL error (pending): " . mysqli_error($conn) . "\n", FILE_APPEND);
                    $status = 'error';
                    $message = 'Failed to verify account. Please try again.';
                } else {
                    mysqli_stmt_bind_param($update_stmt, "s", $token);
                    $updateResult = mysqli_stmt_execute($update_stmt);
                    if ($updateResult) {
                        file_put_contents(__DIR__ . '/verify_debug.log', gmdate('Y-m-d H:i:s') . " - Pending HM verification successful for email: " . $row['email'] . "\n", FILE_APPEND);
                        $status = 'success';
                        $message = 'Your email has been verified. Please wait for admin approval.';
                        // Notify admin that a verified pending manager is awaiting approval
                        $admin_email_query = "SELECT email FROM hostel_manager WHERE Isadmin = 1";
                        $admin_email_result = mysqli_query($conn, $admin_email_query);
                        if ($admin_email_row = mysqli_fetch_assoc($admin_email_result)) {
                            $admin_email = $admin_email_row['email'];
                            $approveLink = "http://localhost/project/admin/create_hm.php?token=" . urlencode($token) . "&action=approve";
                            $body = "<h3>Hostel Manager Verified</h3><p>The user <b>" . htmlspecialchars($row['Username'] ?? $row['email']) . "</b> has verified their email and awaits admin approval.</p><p><a href='$approveLink'>Approve now</a></p>";
                            // Use sendEmail helper; ignore failure
                            sendEmail($admin_email, 'Hostel Manager Verified - Awaiting Approval', $body);
                        }
                    } else {
                        file_put_contents(__DIR__ . '/verify_debug.log', gmdate('Y-m-d H:i:s') . " - Update failed for pending token: $token\n", FILE_APPEND);
                        $status = 'error';
                        $message = 'Failed to verify account. Please try again.';
                    }
                }
                safe_stmt_close($update_stmt);
            } else {
                // Fallback: check the hostel_manager table (handles older flows)
                mysqli_stmt_close($select_stmt);
                $sql2 = "SELECT Hostel_man_id, email FROM hostel_manager WHERE verification_token = ? AND is_verified = 0";
                $select_stmt2 = mysqli_stmt_init($conn);
                if (!mysqli_stmt_prepare($select_stmt2, $sql2)) {
                    file_put_contents(__DIR__ . '/verify_debug.log', gmdate('Y-m-d H:i:s') . " - SQL error (hostel lookup): " . mysqli_error($conn) . "\n", FILE_APPEND);
                    $status = 'error';
                    $message = 'Database error. Please try again.';
                } else {
                    mysqli_stmt_bind_param($select_stmt2, "s", $token);
                    mysqli_stmt_execute($select_stmt2);
                    $result2 = mysqli_stmt_get_result($select_stmt2);
                    if ($row2 = mysqli_fetch_assoc($result2)) {
                        $userId = $row2['Hostel_man_id'];
                        $email = $row2['email'];

                        // Update is_verified status
                        $sql_up = "UPDATE hostel_manager SET is_verified = 1, verification_token = NULL WHERE Hostel_man_id = ?";
                        $update_stmt2 = mysqli_stmt_init($conn);
                        if (!mysqli_stmt_prepare($update_stmt2, $sql_up)) {
                            file_put_contents(__DIR__ . '/verify_debug.log', gmdate('Y-m-d H:i:s') . " - Update SQL error: " . mysqli_error($conn) . "\n", FILE_APPEND);
                            $status = 'error';
                            $message = 'Failed to verify account. Please try again.';
                        } else {
                            mysqli_stmt_bind_param($update_stmt2, "i", $userId);
                            $updateResult2 = mysqli_stmt_execute($update_stmt2);
                            if ($updateResult2) {
                                file_put_contents(__DIR__ . '/verify_debug.log', gmdate('Y-m-d H:i:s') . " - Verification successful for email: $email\n", FILE_APPEND);
                                $status = 'success';
                                $message = 'You are verified! Please log in to continue.';
                            } else {
                                file_put_contents(__DIR__ . '/verify_debug.log', gmdate('Y-m-d H:i:s') . " - Update failed for user ID: $userId\n", FILE_APPEND);
                                $status = 'error';
                                $message = 'Failed to verify account. Please try again.';
                            }
                        }
                        mysqli_stmt_close($update_stmt2);
                    } else {
                        file_put_contents(__DIR__ . '/verify_debug.log', gmdate('Y-m-d H:i:s') . " - Token not found or already verified: $token\n", FILE_APPEND);
                        $status = 'error';
                        $message = 'Invalid or expired verification token.';
                    }
                }
                safe_stmt_close($select_stmt2);
            }
        }
        safe_stmt_close($select_stmt);
    }
} else {
    file_put_contents(__DIR__ . '/verify_debug.log', gmdate('Y-m-d H:i:s') . " - No token provided\n", FILE_APPEND);
    $status = 'error';
    $message = 'No verification token provided. Please click the link in the verification email.';
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Email Verification - Hostel Management System</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background: white;
            padding: 20px 40px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 500px;
        }
        .success { color: #28a745; font-size: 18px; margin-bottom: 20px; }
        .error { color: #dc3545; font-size: 18px; margin-bottom: 20px; }
        a {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 10px;
        }
        a:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Email Verification</h2>
        <?php if ($status === 'success'): ?>
            <p class="success"><?php echo htmlspecialchars($message); ?></p>
            <a href="includes\login-hm.inc.php">Go to Login</a>
        <?php else: ?>
            <p class="error"><?php echo htmlspecialchars($message); ?></p>
            <a href="includes\login-hm.inc.php">Back to Login Page</a>
        <?php endif; ?>
    </div>
</body>
</html>