<?php
// filepath: includes/functions.inc.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';

function validateInput($data, $type = 'string') {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    
    switch ($type) {
        case 'email':
            return filter_var($data, FILTER_VALIDATE_EMAIL) ? $data : false;
        case 'int':
            return filter_var($data, FILTER_VALIDATE_INT) ? $data : false;
        case 'float':
            return filter_var($data, FILTER_VALIDATE_FLOAT) ? $data : false;
        default:
            return $data;
    }
}

function sanitizeFileName($filename) {
    $info = pathinfo($filename);
    $ext = $info['extension'];
    $name = $info['filename'];
    
    $name = preg_replace('/[^a-zA-Z0-9\s-]/', '', $name);
    $name = strtolower(trim($name));
    $name = preg_replace('/[\s-]+/', '-', $name);
    
    return $name . '.' . $ext;
}

function getPendingManagerByToken($conn, $token) {
    $sql = "SELECT * FROM pending_hostel_manager WHERE token = ?";
    $stmt = mysqli_stmt_init($conn);
    if (mysqli_stmt_prepare($stmt, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $token);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }
    return null;
}

function deletePendingManager($conn, $token) {
    $sql = "DELETE FROM pending_hostel_manager WHERE token = ?";
    $stmt = mysqli_stmt_init($conn);
    if (mysqli_stmt_prepare($stmt, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $token);
        mysqli_stmt_execute($stmt);
    }
}

function getPendingManagers($conn) {
    $sql = "SELECT * FROM pending_hostel_manager";
    $result = mysqli_query($conn, $sql);
    $managers = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $managers[] = $row;
    }
    return $managers;
}

function sendEmail($to, $subject, $body) {
    $mail = new PHPMailer(true);
    try {
        // Allow overriding via config (recommended to set in includes/config.inc.php)
        $host = defined('SMTP_HOST') ? SMTP_HOST : 'smtp.gmail.com';
        $user = defined('SMTP_USER') ? SMTP_USER : 'sthapitsuzal@gmail.com';
        $pass = defined('SMTP_PASS') ? SMTP_PASS : 'jwyl yqqa udlr egqh';
        $port = defined('SMTP_PORT') ? SMTP_PORT : 587;
        $secure = defined('SMTP_SECURE') ? SMTP_SECURE : 'tls';
        $fromEmail = defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : $user;
        $fromName = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'Student Management System';

        // Email Configuration
        $mail->isSMTP();
        $mail->Host       = $host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $user;
        $mail->Password   = $pass;
        $mail->SMTPSecure = ($secure === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $port;

        // Optional debug logging into PHP error log when SMTP_DEBUG is enabled
        if (defined('SMTP_DEBUG') && SMTP_DEBUG) {
            $mail->SMTPDebug = 2;
            $mail->Debugoutput = function($str, $level) {
                error_log('[PHPMailer] ' . trim($str));
            };
        }

        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        if (!$mail->send()) {
            error_log('PHPMailer send() failed: ' . $mail->ErrorInfo);
            return false;
        }
        return true;
    } catch (Exception $e) {
        // Log both the PHPMailer exception and any ErrorInfo present
        error_log('PHPMailer Exception: ' . $e->getMessage());
        if (isset($mail) && property_exists($mail, 'ErrorInfo')) {
            error_log('PHPMailer ErrorInfo: ' . $mail->ErrorInfo);
        }
        return false;
    }
}

function getAllManagers($conn) {
    $sql = "SELECT hm.*, h.Hostel_name 
            FROM hostel_manager hm
            LEFT JOIN hostel h ON hm.Hostel_id = h.Hostel_id";
    $result = mysqli_query($conn, $sql);
    $managers = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $managers[] = $row;
    }
    return $managers;
}

function deleteManagerById($conn, $managerId) {
    $sql = "DELETE FROM hostel_manager WHERE Hostel_man_id = ?";
    $stmt = mysqli_stmt_init($conn);
    if (mysqli_stmt_prepare($stmt, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $managerId);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_affected_rows($stmt);
    }
    return 0;
}

/**
 * Append debug message to a file only when ENABLE_DEBUG_LOG is true.
 */
function debug_write(string $path, string $message): void {
    if (!defined('ENABLE_DEBUG_LOG') || !ENABLE_DEBUG_LOG) {
        return;
    }
    file_put_contents($path, $message, FILE_APPEND);
}
