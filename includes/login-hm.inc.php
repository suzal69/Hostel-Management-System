<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'config.inc.php'; // Provides a mysqli connection in $conn

/**
 * Handles error logging and redirection back to the login page.
 */
function log_and_redirect($message, $error_code, $username = 'N/A') {
    $log_message = gmdate('Y-m-d H:i:s') . " - [User: $username] - $message\n";
    file_put_contents(__DIR__ . '/login_debug.log', $log_message, FILE_APPEND);
    header("Location: ../login-hostel_manager.php?error=$error_code");
    exit();
}

if (isset($_POST['login-submit'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['pwd']);

    file_put_contents(__DIR__ . '/login_debug.log', gmdate('Y-m-d H:i:s') . " - [User: $username] - Login attempt received\n", FILE_APPEND);

    if (empty($username) || empty($password)) {
        log_and_redirect("Empty fields detected", "emptyfields", $username);
    }

    if (!preg_match("/^[A-Za-z][A-Za-z0-9]*$/", $username)) {
        log_and_redirect("Invalid username format", "invalidusername", $username);
    }

    $sql = "SELECT Hostel_man_id, Username, Fname, Lname, Mob_no, Hostel_id, Pwd, Isadmin, email, approval_status FROM hostel_manager WHERE Username = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        // Log the specific database error for debugging
        $error_message = "Database error: prepare failed. Error: " . $conn->error;
        log_and_redirect($error_message, "sqlerror", $username);
    }

    $stmt->bind_param("s", $username);

    if (!$stmt->execute()) {
        log_and_redirect("Database error: execute failed", "sqlerror", $username);
    }

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row) {
        if ($row['Isadmin'] != 1 && $row['approval_status'] !== 'approved') {
            log_and_redirect("Account not approved", "notapproved", $username);
        }

        if (!password_verify($password, $row['Pwd'])) {
            log_and_redirect("Wrong password", "wrongpwd", $username);
        }

        // Setup successful session variables
        $_SESSION['hostel_man_id'] = $row['Hostel_man_id'];
        $_SESSION['hostel_id'] = $row['Hostel_id'];
        $_SESSION['fname'] = $row['Fname'];
        $_SESSION['Mob_no'] = $row['Mob_no'];
        $_SESSION['email'] = $row['email'];
        $_SESSION['username'] = $row['Username'];
        $_SESSION['isadmin'] = $row['Isadmin'];
        $_SESSION['roll'] = ($row['Isadmin'] == 1) ? "Admin" : $row['Username'];

        if ($row['Isadmin'] == 1) {
            // ADMIN LOGIN
            $_SESSION['admin_id'] = $row['Hostel_man_id'];
            $_SESSION['admin_username'] = $row['Username'];
            $redirect_url = "../admin/admin_home.php?login=success";
        } else {
            // MANAGER LOGIN
            unset($_SESSION['admin_id']);
            unset($_SESSION['admin_username']);
            $redirect_url = "../home_manager.php?login=success";
        }

        $log_message = gmdate('Y-m-d H:i:s') . " - [User: $username] - Login successful. Redirecting to $redirect_url\n";
        file_put_contents(__DIR__ . '/login_debug.log', $log_message, FILE_APPEND);

        header("Location: " . $redirect_url);
        exit();

    } else {
        log_and_redirect("No user found", "nouser", $username);
    }

    $stmt->close();

} else {
    log_and_redirect("Invalid access to login script", "invalidaccess");
}
$conn->close();
?>
