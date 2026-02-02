<?php
if (isset($_POST['login-submit'])) {

    require 'config.inc.php';
    // Start session only if none is active
    if (function_exists('session_status')) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    } else {
        if (session_id() === '') {
            session_start();
        }
    }

    // Get and trim inputs to remove spaces
    $roll = trim($_POST['student_roll_no']);
    $password = trim($_POST['pwd']);

    // Validation: Check empty fields
    if (empty($roll) || empty($password)) {
        header("Location: ../index.php?error=emptyfields");
        exit();
    }

    // Validation: Ensure roll number is numeric only (adjust if it can have letters)
    if (!preg_match("/^[0-9]+$/", $roll)) {
        header("Location: ../index.php?error=invalidroll");
        exit();
    }

    // Use prepared statements to prevent SQL injection
    $sql = "SELECT * FROM Student WHERE Student_id = ?";
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        header("Location: ../index.php?error=sqlerror");
        exit();
    }

    // Bind parameters (s = string)
    mysqli_stmt_bind_param($stmt, "s", $roll);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        
        // Verify password hash
        if (!password_verify($password, $row['Pwd'])) {
            header("Location: ../index.php?error=wrongpwd");
            exit();
        }

        // Set session variables
        $_SESSION['roll'] = $row['Student_id'];
        $_SESSION['fname'] = $row['Fname'];
        $_SESSION['lname'] = $row['Lname'];
        $_SESSION['mob_no'] = $row['Mob_no'];
        $_SESSION['department'] = $row['Dept'];
        $_SESSION['year_of_study'] = $row['Year_of_study'];
        $_SESSION['email'] = $row['Email'];
        $_SESSION['hostel_id'] = $row['Hostel_id'];
        $_SESSION['room_id'] = $row['Room_id'];

        header("Location: ../home.php?login=success");
        exit();

    } else {
        header("Location: ../index.php?error=nouser");
        exit();
    }

} else {
    header("Location: ../index.php");
    exit();
}
?>
