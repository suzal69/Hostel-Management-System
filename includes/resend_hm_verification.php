<?php
// User-facing resend handler for pending_hostel_manager verification emails
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/signup_php_error.log');

require_once __DIR__ . '/config.inc.php';
require_once __DIR__ . '/functions.inc.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../signup_hm.php');
    exit();
}

$token = $_POST['token'] ?? '';
if (empty($token)) {
    header('Location: ../signup_hm.php?error=invalidtoken');
    exit();
}

$sql = "SELECT * FROM pending_hostel_manager WHERE token = ? LIMIT 1";
$stmt = mysqli_stmt_init($conn);
if (!mysqli_stmt_prepare($stmt, $sql)) {
    error_log('ResendHM: Failed to prepare select: ' . mysqli_error($conn));
    header('Location: ../signup_hm.php?error=server');
    exit();
}
mysqli_stmt_bind_param($stmt, 's', $token);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$row) {
    header('Location: ../signup_hm.php?error=notfound');
    exit();
}

// Rate limiting: minimum interval between attempts and max attempts per day
$min_interval_seconds = 120; // 2 minutes
$max_attempts = 5;

$last_attempt = $row['last_email_attempt'] ?? null;
if ($last_attempt) {
    $last_ts = strtotime($last_attempt);
    if ($last_ts && (time() - $last_ts) < $min_interval_seconds) {
        header('Location: ../signup_hm.php?error=toofast&token=' . urlencode($token));
        exit();
    }
}

$attempts = intval($row['email_attempts'] ?? 0);
if ($attempts >= $max_attempts) {
    header('Location: ../signup_hm.php?error=maxattempts&token=' . urlencode($token));
    exit();
}

$verifyLink = "http://localhost/project/verify_hm.php?token=" . $token;
$body = "<h3>Hello, " . htmlspecialchars($row['Fname']) . "</h3>\n" .
        "<p>Please click the link below to verify your email address (valid for 24 hours):</p>\n" .
        "<a href='" . $verifyLink . "'>" . $verifyLink . "</a>\n" .
        "<p>After verification, an admin will review and approve your manager account.</p>";

$sent = sendEmail($row['email'], 'Verify your Hostel Manager Account', $body);

// Update DB with attempt result
$updateSql = "UPDATE pending_hostel_manager SET email_attempts = COALESCE(email_attempts,0) + 1, last_email_attempt = NOW(), email_failed = ? WHERE token = ?";
$stmtUpd = mysqli_stmt_init($conn);
if (mysqli_stmt_prepare($stmtUpd, $updateSql)) {
    $failedFlag = $sent ? 0 : 1;
    mysqli_stmt_bind_param($stmtUpd, 'is', $failedFlag, $token);
    mysqli_stmt_execute($stmtUpd);
    mysqli_stmt_close($stmtUpd);
} else {
    error_log('ResendHM: Failed to prepare update: ' . mysqli_error($conn));
}

if ($sent) {
    header('Location: ../signup_hm.php?resend=success&token=' . urlencode($token));
} else {
    header('Location: ../signup_hm.php?resend=failed&token=' . urlencode($token));
}
exit();
