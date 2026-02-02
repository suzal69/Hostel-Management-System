<?php
require_once __DIR__ . '/../includes/init.php';

// Admin auth
if (empty($_SESSION['admin_username']) && (empty($_SESSION['isadmin']) || $_SESSION['isadmin'] != 1)) {
    header('Location: ../login-hostel_manager.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin_complaint_review.php');
    exit;
}

$audit_id = $_POST['audit_id'] ?? null;
$complaint_id = $_POST['complaint_id'] ?? null;
$urgency = $_POST['urgency'] ?? null;
$topic = $_POST['topic'] ?? null;
$assigned_manager_id = $_POST['assigned_manager_id'] ?? null;

if (!$audit_id || !$complaint_id) {
    header('Location: admin_complaint_review.php?error=invalid');
    exit;
}

// Update complaints table with corrected urgency and assigned manager
// Use two attempts to accommodate different PK column names (complaint_id or Complaint_id)
$assigned_param = ($assigned_manager_id === '' ? null : $assigned_manager_id);

// Determine hostel_id for notification: prefer posted value, fallback to audit record
$hostel_id = $_POST['hostel_id'] ?? null;
if (empty($hostel_id)) {
    $hstmt = mysqli_prepare($conn, "SELECT hostel_id FROM complaint_classification_audit WHERE id = ? LIMIT 1");
    if ($hstmt) {
        mysqli_stmt_bind_param($hstmt, "i", $audit_id);
        mysqli_stmt_execute($hstmt);
        $hres = mysqli_stmt_get_result($hstmt);
        if ($hrow = mysqli_fetch_assoc($hres)) {
            $hostel_id = $hrow['hostel_id'];
        }
        mysqli_stmt_close($hstmt);
    }
}

if ($assigned_param === null) {
    // Set assigned_manager_id to NULL explicitly
    $update_sql = "UPDATE complaints SET urgency = ?, assigned_manager_id = NULL WHERE complaint_id = ?";
    $stmt = mysqli_stmt_init($conn);
    if (mysqli_stmt_prepare($stmt, $update_sql)) {
        mysqli_stmt_bind_param($stmt, "si", $urgency, $complaint_id);
        mysqli_stmt_execute($stmt);
        $affected = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);
    } else {
        $affected = 0;
    }
    if (empty($affected)) {
        $update_sql2 = "UPDATE complaints SET urgency = ?, assigned_manager_id = NULL WHERE Complaint_id = ?";
        $stmt2 = mysqli_stmt_init($conn);
        if (mysqli_stmt_prepare($stmt2, $update_sql2)) {
            mysqli_stmt_bind_param($stmt2, "si", $urgency, $complaint_id);
            mysqli_stmt_execute($stmt2);
            mysqli_stmt_close($stmt2);
        }
    }
} else {
    $update_sql = "UPDATE complaints SET urgency = ?, assigned_manager_id = ? WHERE complaint_id = ?";
    $stmt = mysqli_stmt_init($conn);
    if (mysqli_stmt_prepare($stmt, $update_sql)) {
        mysqli_stmt_bind_param($stmt, "sii", $urgency, $assigned_param, $complaint_id);
        mysqli_stmt_execute($stmt);
        $affected = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);
    } else {
        $affected = 0;
    }
    if (empty($affected)) {
        $update_sql2 = "UPDATE complaints SET urgency = ?, assigned_manager_id = ? WHERE Complaint_id = ?";
        $stmt2 = mysqli_stmt_init($conn);
        if (mysqli_stmt_prepare($stmt2, $update_sql2)) {
            mysqli_stmt_bind_param($stmt2, "sii", $urgency, $assigned_param, $complaint_id);
            mysqli_stmt_execute($stmt2);
            mysqli_stmt_close($stmt2);
        }
    }
}

// Mark audit as reviewed and save corrected topic if provided
$reviewer = $_SESSION['admin_username'] ?? 'admin';
$audit_update = "UPDATE complaint_classification_audit SET reviewed = 1, reviewer_id = ?, reviewed_at = NOW(), topic = ? WHERE id = ?";
$a_stmt = mysqli_stmt_init($conn);
if (mysqli_stmt_prepare($a_stmt, $audit_update)) {
    mysqli_stmt_bind_param($a_stmt, "ssi", $reviewer, $topic, $audit_id);
    mysqli_stmt_execute($a_stmt);
    mysqli_stmt_close($a_stmt);
}

// Notify assigned manager via internal message if manager assigned
if (!empty($assigned_param) && is_numeric($assigned_param)) {
    $sender = $reviewer;
    $receiver = (string)$assigned_param;
    $today_date = date("Y-m-d");
    $time = date("h:i A");
    $subject = "[Urgency: " . strtoupper($urgency) . "] Complaint review #" . $complaint_id;
    $body = "Complaint #" . $complaint_id . " reviewed by " . $reviewer . ". Urgency: " . $urgency . ". Topic: " . $topic . ". Please follow up.";

    $m_sql = "INSERT INTO message (sender_id, receiver_id, hostel_id, subject_h, message, msg_date, msg_time) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $m_stmt = mysqli_stmt_init($conn);
    if (mysqli_stmt_prepare($m_stmt, $m_sql)) {
        mysqli_stmt_bind_param($m_stmt, "ssissss", $sender, $receiver, $hostel_id, $subject, $body, $today_date, $time);
        mysqli_stmt_execute($m_stmt);
        mysqli_stmt_close($m_stmt);
    }
}

header('Location: admin_complaint_review.php?success=1');
exit;
