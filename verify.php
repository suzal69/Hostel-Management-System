<?php
require __DIR__ . '/includes/config.inc.php';

$status = '';
$message = '';

if (isset($_GET['token'])) {
    $token = trim($_GET['token']);

    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        $status = 'error';
        $message = 'Invalid or expired verification token.';
    } else {
        // Find pending student
        $sql = "SELECT * FROM pending_students WHERE token = ?";
        $stmt = mysqli_stmt_init($conn);
        mysqli_stmt_prepare($stmt, $sql);
        mysqli_stmt_bind_param($stmt, "s", $token);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

                if ($row = mysqli_fetch_assoc($result)) {
                    // Ensure Student_id is not null
                                if (empty($row['Student_id'])) {
                                    $status = 'error';
                                    $message = 'Error: Student ID not found in verification data. Please try again or contact support.';
                                } elseif (empty($row['gender'])) {
                                    $status = 'error';
                                    $message = 'Error: Gender not found in verification data. Please try again or contact support.';
                                } else {
                                    // Insert into Student table
                                    $insert_sql = "INSERT INTO `student`(`Student_id`, `Fname`, `Lname`, `gender`, `Mob_no`, `Dept`, `Year_of_study`, `Pwd`, `Hostel_id`, `Room_id`, `Email`, `is_verified`, `admission_date`)
                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)";                        $insert_stmt = mysqli_stmt_init($conn);
                        if (!mysqli_stmt_prepare($insert_stmt, $insert_sql)) {
                            $status = 'error';
                            $message = 'Database error: Could not prepare statement.';
                        } else {
                            $correct_types = "ssssssisssss"; // 12 characters: 11 strings, 1 integer

                                mysqli_stmt_bind_param(
                                    $insert_stmt,
                                    $correct_types,
                                    $row['Student_id'],
                                    $row['Fname'],
                                    $row['Lname'],
                                    $row['gender'], // Fixed case sensitivity
                                    $row['Mob_no'],
                                    $row['Dept'],
                                    $row['Year_of_study'], // Ensure this is the 'i' type
                                    $row['Pwd'],
                                    $row['Hostel_id'], // NEW
                                    $row['Room_id'],   // NEW
                                    $row['Email'],
                                    // REMOVED: $row['is_verified'] (Hardcoded in SQL as '1')
                                    $row['admission_date']
                                );
        
                            if (mysqli_stmt_execute($insert_stmt)) {
                                // Delete from pending_students
                                $del_sql = "DELETE FROM pending_students WHERE token = ?";
                                $del_stmt = mysqli_stmt_init($conn);
                                mysqli_stmt_prepare($del_stmt, $del_sql);
                                mysqli_stmt_bind_param($del_stmt, "s", $token);
                                mysqli_stmt_execute($del_stmt);
        
                                $status = 'success';
                                $message = 'Your email has been verified and account created. You can now log in.';
                            } else {
                                $status = 'error';
                                $message = 'Database error: Could not create account. Details: ' . mysqli_stmt_error($insert_stmt);
                            }
        
                            mysqli_stmt_close($insert_stmt);
                        }
                    }
                } else {
                    $status = 'error';
                    $message = 'Invalid or expired verification token.';
                }
        mysqli_stmt_close($stmt);
    }
} else {
    $status = 'error';
    $message = 'No verification token provided.';
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Email Verification</title>
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
            <a href="index.php">Go to Login</a>
        <?php else: ?>
            <p class="error"><?php echo htmlspecialchars($message); ?></p>
            <a href="index.php">Back to Login</a>
        <?php endif; ?>
    </div>
</body>
</html>
