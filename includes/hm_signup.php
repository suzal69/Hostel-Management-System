<?php
// =================================================================================
// 1. CRITICAL DIAGNOSTIC SETUP (Applies to both handlers)
// =================================================================================
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/signup_php_error.log');

// Ensures that any execution-halting error is at least logged to the server logs
set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && ($err['type'] === E_ERROR || $err['type'] === E_PARSE || $err['type'] === E_COMPILE_ERROR || $err['type'] === E_CORE_ERROR)) {
        // Log FATAL errors to a dedicated file
        $log_content = gmdate('Y-m-d H:i:s') . " - !!! FATAL ERROR !!!: " . print_r($err, true) . "\n";
        file_put_contents(__DIR__ . '/signup_fatal_error.log', $log_content, FILE_APPEND);
        http_response_code(500);
        // Show a diagnostic message ONLY when display_errors is ON (which it is here)
        echo "<h1>FATAL ERROR DETECTED</h1>";
        echo "<p>Script execution stopped abruptly. Check <code>includes/signup_fatal_error.log</code> and <code>includes/signup_php_error.log</code> for details.</p>";
        echo "<p>Error details: " . htmlspecialchars($err['message']) . " in " . htmlspecialchars($err['file']) . " on line " . htmlspecialchars($err['line']) . "</p>";
        // Exit to prevent further PHP output
        exit();
    }
});

// =================================================================================
// 2. PHPMailer Path Validation and Setup (Applies to both handlers)
// =================================================================================

$mailer_files = [
    'PHPMailer' => '/../PHPMailer/src/PHPMailer.php',
    'SMTP'      => '/../PHPMailer/src/SMTP.php',
    'Exception' => '/../PHPMailer/src/Exception.php',
];

foreach ($mailer_files as $name => $path) {
    $full_path = __DIR__ . $path;
    if (!file_exists($full_path)) {
        http_response_code(500);
        die("<h1>FATAL: PHPMailer File Missing</h1><p>Could not find <strong>{$name}</strong>. Expected path:</p><pre>" . htmlspecialchars($full_path) . "</pre><p>Please verify the PHPMailer library is correctly placed at <code>../PHPMailer/src/</code> relative to the <code>includes</code> folder.</p>");
    }
    require_once $full_path; // Use require_once for safety
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Include shared helper functions (defines sendEmail)
require_once __DIR__ . '/functions.inc.php';

// =================================================================================
// 3. UTILITY FUNCTION (Shared by both handlers)
// =================================================================================

/**
 * Displays a JavaScript alert message and navigates back in browser history.
 * Used for instant, client-side validation feedback.
 * @param string $msg The message to display in the alert box.
 */
function alertAndBack($msg) {
    // Set response code 400 for bad request, although the script exits immediately
    http_response_code(400); 
    echo "<!DOCTYPE html><html><head><script>alert('" . addslashes($msg) . "'); window.history.back();</script></head><body></body></html>";
    exit();
}


// =================================================================================
// 4. HANDLER 1: STUDENT SIGNUP (signup-submit) - RETAINS EMAIL VERIFICATION
// =================================================================================

try {
    if (isset($_POST['signup-submit'])) {
        
        // --- 4.1 CONFIG REQUIRE CHECK ---
        $config_path = __DIR__ . '/config.inc.php';
        if (!file_exists($config_path)) {
            die("<h1>FATAL: Configuration File Missing</h1><p>Could not find <code>config.inc.php</code> at expected path: <pre>" . htmlspecialchars($config_path) . "</pre></p>");
        }
        require $config_path;
        
        // Check if $conn was created successfully by config.inc.php
        if (!isset($conn) || !$conn) {
            alertAndBack("Database connection failed. Check config.inc.php details.");
        }

        $roll      = trim($_POST['student_roll_no']);
        $fname     = trim($_POST['student_fname']);
        $lname     = trim($_POST['student_lname']);
        $mobile    = trim($_POST['mobile_no']);
        $dept      = trim($_POST['department']);
        $year      = trim($_POST['year_of_study']);
        $email     = trim($_POST['email']);
        $password  = $_POST['pwd'];
        $cnfpassword = $_POST['confirmpwd'];

        // === Validations ===
        if (empty($roll) || empty($fname) || empty($lname) || empty($mobile) || empty($dept) || empty($year) || empty($email) || empty($password) || empty($cnfpassword)) {
            alertAndBack("Please fill in all fields.");
        }
        if (!preg_match("/^[0-9]+$/", $roll)) alertAndBack("Invalid Roll Number. Only numbers allowed.");
        if (!preg_match("/^[A-Za-z]+$/", $fname)) alertAndBack("First name should only contain letters.");
        if (!preg_match("/^[A-Za-z]+$/", $lname)) alertAndBack("Last name should only contain letters.");
        if (!preg_match("/^[0-9]{10}$/", $mobile)) alertAndBack("Mobile number must be exactly 10 digits.");
        if (!preg_match("/^[A-Za-z ]+$/", $dept)) alertAndBack("Department should only contain letters and spaces.");
        if (!preg_match("/^[1-5]$/", $year)) alertAndBack("Year of study must be between 1 and 5.");
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) alertAndBack("Invalid email format.");

        $passwordPattern = "/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/";
        if (!preg_match($passwordPattern, $password)) {
            alertAndBack("Password must be at least 8 characters, with at least one uppercase letter, one lowercase letter, one number, and one special character.");
        }
        if ($password !== $cnfpassword) alertAndBack("Passwords do not match.");

        // === Duplicate checks in both Student and PendingStudent ===
        if (!$conn) {
            alertAndBack("Database connection failed");
        }

        $checkQueries = [
            ["SELECT Student_id FROM Student WHERE Student_id=?", "s", $roll, "A student with this Roll Number already exists."],
            ["SELECT Student_id FROM pending_students WHERE Student_id=?", "s", $roll, "A pending signup with this Roll Number already exists. Check your email."],
            ["SELECT Email FROM Student WHERE Email=?", "s", $email, "A student with this Email already exists."],
            ["SELECT Email FROM pending_students WHERE Email=?", "s", $email, "A pending signup with this Email already exists. Check your email."]
        ];

        foreach ($checkQueries as $q) {
            $stmt = mysqli_stmt_init($conn);
            if (!$stmt) {
                error_log("Failed to initialize statement: " . mysqli_error($conn));
                alertAndBack("Failed to initialize database statement.");
            }
            if (!mysqli_stmt_prepare($stmt, $q[0])) {
                error_log("Failed to prepare statement: " . mysqli_error($conn));
                alertAndBack("Database error during check.");
            }
            // Bind using the correct variable from the array
            if (!mysqli_stmt_bind_param($stmt, $q[1], $q[2])) {
                error_log("Failed to bind parameters: " . mysqli_error($conn));
                alertAndBack("Database parameter error.");
            }
            if (!mysqli_stmt_execute($stmt)) {
                error_log("Failed to execute query: " . mysqli_error($conn));
                alertAndBack("Database execution error during check.");
            }
            mysqli_stmt_store_result($stmt);
            if (mysqli_stmt_num_rows($stmt) > 0) {
                mysqli_stmt_close($stmt);
                alertAndBack($q[3]);
            }
            mysqli_stmt_close($stmt);
        }

        // === Generate token & expiry ===
        $token = bin2hex(random_bytes(32));
        $expires_at = date("Y-m-d H:i:s", strtotime("+1 day")); // valid for 24 hours
        $verifyLink = "http://localhost/project/verify.php?token=" . $token;

        // === Insert into pending_students (not Student) ===
        $sql = "INSERT INTO pending_students 
                (Student_id, Fname, Lname, Mob_no, Dept, Year_of_study, Email, Pwd, token, expires_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_stmt_init($conn);
        
        if (!mysqli_stmt_prepare($stmt, $sql)) {
            error_log("Failed to prepare INSERT: " . mysqli_error($conn));
            alertAndBack("Database error: Could not prepare insert statement for student.");
        }

        $hashedPwd = password_hash($password, PASSWORD_DEFAULT);
        
        // Bind string: sssss (5 strings: roll, fname, lname, mobile, dept), i (1 int: year), ssss (4 strings: email, pwd, token, expires_at)
        // Total 10 characters: sssssissss
        if (!mysqli_stmt_bind_param($stmt, "sssssissss", $roll, $fname, $lname, $mobile, $dept, $year, $email, $hashedPwd, $token, $expires_at)) {
            error_log("Failed to bind INSERT params (Student): " . mysqli_error($conn));
            alertAndBack("Database error: Could not bind insert parameters for student.");
        }
        

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt); 
            
            // === Send verification email using shared helper (uses SMTP config) ===
            $body = "<h3>Welcome, $fname!</h3>\n" .
                    "<p>Please click the link below to verify your account (valid for 24 hours):</p>\n" .
                    "<a href='$verifyLink'>$verifyLink</a>";

            try {
                $sent = sendEmail($email, 'Verify Your Email', $body);
                if (!$sent) {
                    // Deleting the pending record if email fails (preserve existing behaviour)
                    $sqlDel = "DELETE FROM pending_students WHERE Student_id = ?";
                    $stmtDel = mysqli_stmt_init($conn);
                    if (mysqli_stmt_prepare($stmtDel, $sqlDel)) {
                        mysqli_stmt_bind_param($stmtDel, "s", $roll);
                        mysqli_stmt_execute($stmtDel);
                        mysqli_stmt_close($stmtDel);
                    }
                    header("Location: ../signup.php?error=mailsendfailed");
                    exit();
                } else {
                    header("Location: ../index.php?signup=success&email_sent=1");
                    exit();
                }
            } catch (Exception $e) {
                // delete pending record if an unexpected exception occurs
                $sqlDel = "DELETE FROM pending_students WHERE Student_id = ?";
                $stmtDel = mysqli_stmt_init($conn);
                if (mysqli_stmt_prepare($stmtDel, $sqlDel)) {
                    mysqli_stmt_bind_param($stmtDel, "s", $roll);
                    mysqli_stmt_execute($stmtDel);
                    mysqli_stmt_close($stmtDel);
                }
                error_log("Email send exception: " . $e->getMessage());
                header("Location: ../signup.php?error=emailfailed&message=" . urlencode($e->getMessage()));
                exit();
            }
        } else {
            $error = mysqli_error($conn);
            mysqli_stmt_close($stmt);
            error_log("Database INSERT Error: " . $error);
            header("Location: ../signup.php?error=sqlerror&message=" . urlencode($error));
            exit();
        }

        mysqli_close($conn); 
    } 

} catch (Throwable $e) {
    // Catch-all for exceptions/Throwables in Handler 1
    $log = gmdate('Y-m-d H:i:s') . " - EXCEPTION: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n" . $e->getTraceAsString() . "\n\n";
    file_put_contents(__DIR__ . '/signup_debug.log', $log, FILE_APPEND);
    error_log($log);
    http_response_code(500);
    echo "An internal error occurred while processing student signup. Check includes/signup_debug.log for details.";
    exit();
}


// =================================================================================
// 5. HANDLER 2: HOSTEL MANAGER CREATION (hm_signup_submit) - REMOVED TOKEN/VERIFICATION LOGIC
// =================================================================================

try {
    if (isset($_POST['hm_signup_submit'])) {
        require_once __DIR__ . '/config.inc.php'; 

        if (!isset($conn) || !$conn) {
            alertAndBack("Database connection failed for Hostel Manager creation. Check config.inc.php details.");
        }

        $username    = trim($_POST['hm_uname']);
        $fname       = trim($_POST['hm_fname']);
        $lname       = trim($_POST['hm_lname']);
        $mobile      = trim($_POST['hm_mobile']);
        $HostelID    = trim($_POST['hostel_id']);
        $email       = trim($_POST['Email']);
        $password    = $_POST['pass'];
        $cnfpassword = $_POST['confpass'];

        // === Validations ===
        if (empty($username) || empty($fname) || empty($lname) || empty($mobile) || empty($HostelID) || empty($email) || empty($password) || empty($cnfpassword)) {
            alertAndBack("Please fill in all fields.");
        }
        if (!preg_match("/^[A-Za-z]+$/", $fname)) {
            alertAndBack("First name should only contain letters.");
        }
        if (!preg_match("/^[A-Za-z]+$/", $lname)) {
            alertAndBack("Last name should only contain letters.");
        }
        if (!preg_match("/^9[0-9]{9}$/", $mobile)) {
            alertAndBack("Mobile number must start with digit '9' and be exactly 10 digits.");
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            alertAndBack("Invalid email format.");
        }
        if (!preg_match("/^[a-zA-Z0-9]*$/", $username)) {
            alertAndBack("Invalid username. Only letters and numbers allowed.");
        }
        
        $passwordPattern = "/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/";
        if (!preg_match($passwordPattern, $password)) {
            alertAndBack("Password must be at least 8 characters, with at least one uppercase letter, one lowercase letter, one number, and one special character.");
        }
        
        if ($password !== $cnfpassword) {
            alertAndBack("Passwords do not match.");
        }

        // Check if username already exists
        $sql = "SELECT Username FROM hostel_manager WHERE Username=?";
        $stmt = mysqli_stmt_init($conn);
        if (!mysqli_stmt_prepare($stmt, $sql)) {
            error_log("HM Signup: Failed to prepare username check.");
            alertAndBack("Database error during username check.");
        }
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            alertAndBack("A Hostel Manager account with this Username already exists.");
        }
        mysqli_stmt_close($stmt);

        // Check if email already exists
        $sql = "SELECT email FROM hostel_manager WHERE email=?";
        $stmt = mysqli_stmt_init($conn);
        if (!mysqli_stmt_prepare($stmt, $sql)) {
            error_log("HM Signup: Failed to prepare email check.");
            alertAndBack("Database error during email check.");
        }
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            alertAndBack("A Hostel Manager account with this Email already exists.");
        }
        mysqli_stmt_close($stmt);

        $hashedPwd = password_hash($password, PASSWORD_DEFAULT);
        $isadmin = 0;
        $token = bin2hex(random_bytes(32));
        $expires_at = date("Y-m-d H:i:s", strtotime("+1 day")); 

        $sql = "INSERT INTO pending_hostel_manager 
                (Username, Fname, Lname, Mob_no, Hostel_id, Pwd, Isadmin, email, token, expires_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_stmt_init($conn);
        if (!mysqli_stmt_prepare($stmt, $sql)) {
            error_log("HM Signup: Failed to prepare INSERT: " . mysqli_error($conn));
            alertAndBack("Database error: Could not prepare insert statement. Details: " . mysqli_error($conn)); 
        }
        
        if (!mysqli_stmt_bind_param($stmt, "sssiisssss", 
            $username, 
            $fname, 
            $lname, 
            $mobile, 
            $HostelID, 
            $hashedPwd, 
            $isadmin, 
            $email,
            $token,
            $expires_at
        )) {
            error_log("HM Signup: Failed to bind INSERT params. Check types and count.");
            alertAndBack("Database error: Could not bind insert parameters. Check server logs for details.");
        }
        
        $insertResult = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if (!$insertResult) {
            error_log("HM Signup: Database INSERT failed: " . mysqli_error($conn));
            alertAndBack("Registration failed due to a database error. Please try again.");
        }
        
        // Send verification email to the Hostel Manager (they must verify email before admin approval)
        $verifyLink = "http://localhost/project/verify_hm.php?token=$token";
        $body = "<h3>Welcome, $fname!</h3>
                 <p>Please click the link below to verify your email address (valid for 24 hours):</p>
                 <a href='$verifyLink'>$verifyLink</a>
                 <p>After verification, an admin will review and approve your manager account.</p>";

        // Use helper sendEmail to centralize PHPMailer configuration and logging
        $sent = sendEmail($email, 'Verify your Hostel Manager Account', $body);
        if (!$sent) {
            // If sending verification email fails, DO NOT delete the pending record.
            // Instead mark it so admins can see and resend the verification later.
            $sqlUpd = "UPDATE pending_hostel_manager SET email_failed = 1, email_attempts = COALESCE(email_attempts,0) + 1, last_email_attempt = NOW() WHERE token = ?";
            $stmtUpd = mysqli_stmt_init($conn);
            if (mysqli_stmt_prepare($stmtUpd, $sqlUpd)) {
                mysqli_stmt_bind_param($stmtUpd, "s", $token);
                mysqli_stmt_execute($stmtUpd);
                mysqli_stmt_close($stmtUpd);
            } else {
                error_log("HM Signup: Failed to prepare update on mail failure: " . mysqli_error($conn));
            }

            // Redirect back to signup page but keep record in DB so admin can resend
            header("Location: ../signup_hm.php?error=mailsendfailed&token=" . urlencode($token));
            exit();
        }

        // Send notification to admin that a new pending HM signed up (optional, admin will still need to approve)
        $admin_email_query = "SELECT email FROM hostel_manager WHERE Isadmin = 1";
        $admin_email_result = mysqli_query($conn, $admin_email_query);
        if ($admin_email_row = mysqli_fetch_assoc($admin_email_result)) {
            $admin_email = $admin_email_row['email'];
            $adminBody = "<h3>New Hostel Manager Signup (Pending Verification)</h3>
                          <p>User <b>$username</b> ($fname $lname) has signed up and is pending email verification and admin approval.</p>
                          <p>Email: $email</p>";
            // Do not block signup if admin notification fails
            sendEmail($admin_email, 'New Hostel Manager Signup Pending', $adminBody);
        }

        header("Location: ../index.php?hm_signup=success");
        exit();

        mysqli_close($conn);

    } else if (!isset($_POST['signup-submit'])) {
        // Fallback for direct browser access (GET request)
        header("Location: ../index.php");
        exit();
    }
} catch (Throwable $e) {
    // Catch-all for exceptions/Throwables in Handler 2
    $log = gmdate('Y-m-d H:i:s') . " - EXCEPTION: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n" . $e->getTraceAsString() . "\n\n";
    file_put_contents(__DIR__ . '/hm_signup_debug.log', $log, FILE_APPEND);
    error_log($log);
    http_response_code(500);
    echo "An internal error occurred while processing Hostel Manager signup. Check includes/hm_signup_debug.log for details.";
    exit();
}
?>
