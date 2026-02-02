<?php
// =================================================================================
// 1. CRITICAL DIAGNOSTIC SETUP
// =================================================================================
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Stop writing errors to local file by default
ini_set('log_errors', 0);

// Disable PHP file error logging by default
ini_set('log_errors', 0);

// Old shutdown handler that wrote to __DIR__.'/signup_fatal_error.log'
// register_shutdown_function(function () {
//     $err = error_get_last();
//     if ($err && ($err['type'] === E_ERROR || ... )) {
//         $log_content = gmdate('Y-m-d H:i:s') . " - !!! FATAL ERROR !!!: " . print_r($err, true) . "\n";
//         file_put_contents(__DIR__ . '/signup_fatal_error.log', $log_content, FILE_APPEND);
//         http_response_code(500);
//         echo "<h1>FATAL ERROR DETECTED</h1>";
//         echo "<p>Script execution stopped abruptly. Check <code>includes/signup_fatal_error.log</code> and <code>includes/signup_php_error.log</code> for details.</p>";
//         exit();
//     }
// });

// New safe handler (no disk write unless ENABLE_DEBUG_LOG === true)
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && ($err['type'] === E_ERROR || $err['type'] === E_PARSE || $err['type'] === E_COMPILE_ERROR || $err['type'] === E_CORE_ERROR)) {
        $log_content = gmdate('Y-m-d H:i:s') . " - !!! FATAL ERROR !!!: " . print_r($err, true) . "\n";
        debug_write(__DIR__ . '/signup_fatal_error.log', $log_content);
        http_response_code(500);
        echo "<h1>FATAL ERROR DETECTED</h1>";
        echo "<p>Script execution stopped abruptly.</p>";
        exit();
    }
});

// =================================================================================
// 2. PHPMailer Path Validation
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
        // This is the CRITICAL failure point if the WSoD happens here.
        die("<h1>FATAL: PHPMailer File Missing</h1><p>Could not find <strong>{$name}</strong>. Expected path:</p><pre>" . htmlspecialchars($full_path) . "</pre><p>Please verify the PHPMailer library is correctly placed at <code>../PHPMailer/src/</code> relative to the <code>includes</code> folder.</p>");
    }
    require $full_path;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// =================================================================================
// 3. MAIN LOGIC (Wrapped in try/catch)
// =================================================================================

// Simple function to redirect/alert (using a safer HTML approach)
function alertAndBack($msg) {
    echo "<!DOCTYPE html><html><head><script>alert('" . addslashes($msg) . "'); window.history.back();</script></head><body></body></html>";
    exit();
}


try {
    // Check if form was submitted via POST
    if (isset($_POST['signup-submit'])) {
        
        // --- 3.1 CONFIG REQUIRE CHECK ---
        $config_path = __DIR__ . '/config.inc.php';
        if (!file_exists($config_path)) {
             die("<h1>FATAL: Configuration File Missing</h1><p>Could not find <code>config.inc.php</code> at expected path: <pre>" . htmlspecialchars($config_path) . "</pre></p>");
        }
        require $config_path;
        
        // Check if $conn was created successfully by config.inc.php
        if (!isset($conn) || !$conn) {
             alertAndBack("Database connection failed. Check config.inc.php details.");
        }

        $roll   = trim($_POST['student_roll_no']);
        $fname  = trim($_POST['student_fname']);
        $lname  = trim($_POST['student_lname']);
        $mobile = trim($_POST['mobile_no']);
        $dept   = trim($_POST['department']);
        $year   = trim($_POST['year_of_study']);
        $email  = trim($_POST['email']);
        $password = $_POST['pwd'];
        $cnfpassword = $_POST['confirmpwd'];
        $gender = trim($_POST['gender']); // Retrieve gender

        // === Validations ===
        if (empty($roll) || empty($fname) || empty($lname) || empty($mobile) || empty($dept) || empty($year) || empty($email) || empty($password) || empty($cnfpassword) || empty($gender)) {
            alertAndBack("Please fill in all fields.");
        }
        if (!preg_match("/^[0-9]+$/", $roll)) alertAndBack("Invalid Roll Number. Only numbers allowed.");
        if (!preg_match("/^[A-Za-z]+$/", $fname)) alertAndBack("First name should only contain letters.");
        if (!preg_match("/^[A-Za-z]+$/", $lname)) alertAndBack("Last name should only contain letters.");
        if (!preg_match("/^9(?!(\\d)\\1{8})\\d{9}$/", $mobile)) alertAndBack("Mobile number must start with 9, be exactly 10 digits, and not contain all identical digits.");
        if (!preg_match("/^[A-Za-z ]+$/", $dept)) alertAndBack("Department should only contain letters and spaces.");
        if (!preg_match("/^[1-5]$/", $year)) alertAndBack("Year of study must be between 1 and 5.");
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^[a-zA-Z][a-zA-Z0-9._-]*@[a-z]+\.com$/i', $email)) {
    alertAndBack("Please use a valid email starting with alphabet and ending in [alphabets].com (e.g., name@gmail.com).");
}
        if (!in_array($gender, ['Male', 'Female', 'Other'])) alertAndBack("Invalid Gender selected."); // Validate gender input

        $passwordPattern = "/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/";
        if (!preg_match($passwordPattern, $password)) {
            alertAndBack("Password must be at least 8 chars, with uppercase, lowercase, number, and special character.");
        }
        if ($password !== $cnfpassword) alertAndBack("Passwords do not match.");

        // === Duplicate checks in both Student and PendingStudent ===
        // Connection already checked above, but adding this for clarity
        if (!$conn) {
            alertAndBack("Database connection failed");
        }

        $checkQueries = [
            ["SELECT Student_id FROM Student WHERE Student_id=?", "s", $roll, "A student with this Roll Number already exists."],
            ["SELECT Student_id FROM pending_students WHERE Student_id=?", "s", $roll, "A pending signup with this Roll Number already exists."],
            ["SELECT Email FROM Student WHERE Email=?", "s", $email, "A student with this Email already exists."],
            ["SELECT Email FROM pending_students WHERE Email=?", "s", $email, "A pending signup with this Email already exists."]
        ];

        foreach ($checkQueries as $q) {
            $stmt = mysqli_stmt_init($conn);
            if (!$stmt) {
                // Critical failure, this should be very rare
                error_log("Failed to initialize statement: " . mysqli_error($conn), 0);
                alertAndBack("Failed to initialize database statement.");
            }
            if (!mysqli_stmt_prepare($stmt, $q[0])) {
                error_log("Failed to prepare statement: " . mysqli_error($conn), 0);
                alertAndBack("Database error during check.");
            }
            if (!mysqli_stmt_bind_param($stmt, $q[1], $q[2])) {
                 error_log("Failed to bind parameters: " . mysqli_error($conn), 0);
                alertAndBack("Database parameter error.");
            }
            if (!mysqli_stmt_execute($stmt)) {
                 error_log("Failed to execute query: " . mysqli_error($conn), 0);
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
        $admission_date = date("Y-m-d"); // Get current date for admission_date

        // === Insert into pending_students (not Student) ===
        $sql = "INSERT INTO pending_students 
                (Student_id, Fname, Lname, Mob_no, Dept, Year_of_study, Gender, Email, Pwd, token, expires_at, admission_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_stmt_init($conn);
        
        if (!mysqli_stmt_prepare($stmt, $sql)) {
            error_log("Failed to prepare INSERT: " . mysqli_error($conn), 0);
            alertAndBack("Database error: Could not prepare insert statement.");
        }

        $hashedPwd = password_hash($password, PASSWORD_DEFAULT);
        // Student_id (s), Fname (s), Lname (s), Mob_no (s), Dept (s), Year_of_study (i), Gender (s), Email (s), Pwd (s), token (s), expires_at (s), admission_date (s)
        // Total 12 placeholders, types: ssssssisssss
         if (!mysqli_stmt_bind_param($stmt, "sssssissssss", $roll, $fname, $lname, $mobile, $dept, $year, $gender, $email, $hashedPwd, $token, $expires_at, $admission_date)) {
            error_log("Failed to bind INSERT params: " . mysqli_error($conn), 0);
            alertAndBack("Database error: Could not bind insert parameters.");
        }
        

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);  // Close statement after successful execution
            
            // === Send verification email ===
            $mail = new PHPMailer(true);
            
            try {
                // Email Configuration
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'sthapitsuzal@gmail.com'; 
                $mail->Password   = 'ovqx rzjn tzmo ibcg'; // <-- REMINDER: USE AN APP PASSWORD, NOT YOUR MAIN PASSWORD
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                // debug: route to PHP error_log
                $mail->SMTPDebug = 0;
                $mail->Debugoutput = 'error_log'; // or null

                $mail->setFrom('sthapitsuzal@gmail.com', 'Student Management System');
                $mail->addAddress($email, $fname);

                $mail->isHTML(true);
                $mail->Subject = 'Verify Your Email';
                $mail->Body    = "<h3>Welcome, $fname!</h3>
                                 <p>Please click the link below to verify your account (valid for 24 hours):</p>
                                 <a href='$verifyLink'>$verifyLink</a>";

                // Attempt to send
                if (!$mail->send()) {
                    error_log("PHPMailer send() failed: " . $mail->ErrorInfo);
                    // Deleting the pending record is good practice here
                    $sqlDel = "DELETE FROM pending_students WHERE Student_id = ?";
                    $stmtDel = mysqli_stmt_init($conn);
                    if (mysqli_stmt_prepare($stmtDel, $sqlDel)) {
                        mysqli_stmt_bind_param($stmtDel, "s", $roll);
                        mysqli_stmt_execute($stmtDel);
                        mysqli_stmt_close($stmtDel);
                    }
                    header("Location: ../signup.php?error=mailsendfailed&detail=" . urlencode($mail->ErrorInfo));
                    exit();
                } else {
                    header("Location: ../index.php?signup=success&email_sent=1");
                    exit();
                }
            } catch (Exception $e) {
                // delete pending record if email fails
                $sqlDel = "DELETE FROM pending_students WHERE Student_id = ?";
                $stmtDel = mysqli_stmt_init($conn);
                if (mysqli_stmt_prepare($stmtDel, $sqlDel)) {
                    mysqli_stmt_bind_param($stmtDel, "s", $roll);
                    mysqli_stmt_execute($stmtDel);
                    mysqli_stmt_close($stmtDel);
                }
                error_log("PHPMailer Exception: " . $e->getMessage(), 0);
                header("Location: ../signup.php?error=emailfailed&message=" . urlencode($e->getMessage()));
                exit();
            }
        } else {
            $error = mysqli_error($conn);
            mysqli_stmt_close($stmt);
            error_log("Database INSERT Error: " . $error, 0);
            header("Location: ../signup.php?error=sqlerror&message=" . urlencode($error));
            exit();
        }

        mysqli_close($conn); // Close connection after everything is done
    } else {
        // This is the expected block for direct browser access (GET request)
        header("Location: ../signup.php");
        exit();
    }

} catch (Throwable $e) {
    // Catch-all for exceptions/Throwables
    $log = gmdate('Y-m-d H:i:s') . " - EXCEPTION: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n" . $e->getTraceAsString() . "\n\n";
    file_put_contents(__DIR__ . '/signup_debug.log', $log, FILE_APPEND);
    error_log($log);
    http_response_code(500);
    // This is shown if a Throwable occurs AFTER the includes succeed.
    echo "An internal error occurred while processing signup. Check includes/signup_debug.log for details.";
    exit();
}
?>
