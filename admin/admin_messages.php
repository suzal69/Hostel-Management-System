<?php
require_once __DIR__ . '/../includes/config.inc.php';

// Ensure session is started
if (function_exists('session_status')) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
} else {
    if (session_id() === '') {
        session_start();
    }
}

// Assuming admin_id is stored in session
$admin_id = $_SESSION['admin_id'] ?? null;

if (!$admin_id) {
    // Redirect to login or show an error if admin ID is not set
    header("Location: ../index.php?error=notloggedin");
    exit();
}

// Fetch messages for the admin
$messages = [];
if (isset($conn) && $admin_id) {
    $sql_messages = "SELECT * FROM Message WHERE receiver_id = ? ORDER BY sent_at DESC";
    $stmt_messages = mysqli_prepare($conn, $sql_messages);
    if ($stmt_messages) {
        mysqli_stmt_bind_param($stmt_messages, "s", $admin_id);
        mysqli_stmt_execute($stmt_messages);
        $result_messages = mysqli_stmt_get_result($stmt_messages);
        while ($row = mysqli_fetch_assoc($result_messages)) {
            $messages[] = $row;
        }
        mysqli_stmt_close($stmt_messages);
    }
}

// Mark messages as read when viewed
if (!empty($messages)) {
    $sql_mark_read = "UPDATE Message SET read_status = 1 WHERE receiver_id = ? AND read_status = 0";
    $stmt_mark_read = mysqli_prepare($conn, $sql_mark_read);
    if ($stmt_mark_read) {
        mysqli_stmt_bind_param($stmt_mark_read, "s", $admin_id);
        mysqli_stmt_execute($stmt_mark_read);
        mysqli_stmt_close($stmt_mark_read);
    }
}

include 'admin_header.php';
?>

<div class="container mt-5">
    <h2 class="text-center mb-4">Admin Messages</h2>
    <?php if (empty($messages)): ?>
        <div class="alert alert-info text-center" role="alert">
            No messages received.
        </div>
    <?php else: ?>
        <div class="list-group">
            <?php foreach ($messages as $message): ?>
                <div class="list-group-item list-group-item-action flex-column align-items-start <?php echo $message['read_status'] == 0 ? 'list-group-item-warning' : ''; ?>">
                    <div class="d-flex w-100 justify-content-between">
                        <h5 class="mb-1">From: <?php echo htmlspecialchars($message['sender_id']); ?></h5>
                        <small><?php echo htmlspecialchars($message['sent_at']); ?></small>
                    </div>
                    <p class="mb-1"><?php echo nl2br(htmlspecialchars($message['message_content'])); ?></p>
                    <small>Status: <?php echo $message['read_status'] == 0 ? 'Unread' : 'Read'; ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; // Assuming a footer file exists ?>
