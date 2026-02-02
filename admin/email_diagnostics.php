<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/functions.inc.php';

// Admin-only
if (empty($_SESSION['admin_username']) && (empty($_SESSION['isadmin']) || $_SESSION['isadmin'] != 1)) {
    header('Location: ../login-hostel_manager.php');
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['test_to'])) {
    $to = trim($_POST['test_to']);
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $message = 'Invalid email address.';
    } else {
        $ok = sendEmail($to, 'SMTP Diagnostics Test', '<p>SMTP diagnostics test from HMS at ' . date('Y-m-d H:i:s') . '</p>');
        $logline = date('Y-m-d H:i:s') . " - DIAG SEND to={$to} result=" . ($ok ? 'OK' : 'FAIL') . "\n";
        file_put_contents(__DIR__ . '/../includes/smtp_diag.log', $logline, FILE_APPEND);
        $message = $ok ? 'Test email sent (check inbox/spam).' : 'Test send failed; check logs.';
    }
}

// Read last 60 lines of signup_php_error.log
$logfile = __DIR__ . '/../includes/signup_php_error.log';
$lastLines = [];
if (file_exists($logfile)) {
    $lines = file($logfile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $lastLines = array_slice($lines, -60);
}

// Fetch pending managers with failures
$pending = [];
$sql = "SELECT Username, Fname, Lname, email, token, email_failed, email_attempts, last_email_attempt, is_verified FROM pending_hostel_manager ORDER BY last_email_attempt DESC, email_attempts DESC";
$res = mysqli_query($conn, $sql);
if ($res) {
    while ($r = mysqli_fetch_assoc($res)) {
        $pending[] = $r;
    }
}

require_once 'admin_header.php';
?>
<div class="container p-6">
    <h2 class="text-2xl font-bold mb-4">Email Diagnostics</h2>
    <?php if ($message): ?>
        <div style="padding:10px;background:#eef2ff;border-left:4px solid #6366f1;"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <section style="margin-top:1rem;">
        <h3>SMTP Configuration (non-sensitive)</h3>
        <table style="width:100%;border-collapse:collapse;margin-top:8px;">
            <tr><td style="width:200px;font-weight:bold">Host</td><td><?= htmlspecialchars(defined('SMTP_HOST') ? SMTP_HOST : 'not defined') ?></td></tr>
            <tr><td style="font-weight:bold">User</td><td><?= htmlspecialchars(defined('SMTP_USER') ? SMTP_USER : 'not defined') ?></td></tr>
            <tr><td style="font-weight:bold">Port</td><td><?= htmlspecialchars(defined('SMTP_PORT') ? SMTP_PORT : 'not defined') ?></td></tr>
            <tr><td style="font-weight:bold">Secure</td><td><?= htmlspecialchars(defined('SMTP_SECURE') ? SMTP_SECURE : 'not defined') ?></td></tr>
            <tr><td style="font-weight:bold">SMTP_PASS set?</td><td><?= (defined('SMTP_PASS') && SMTP_PASS !== '') ? 'yes' : '<span style="color:orange">no</span>' ?></td></tr>
        </table>
    </section>

    <section style="margin-top:1.5rem;">
        <h3>Send a test email</h3>
        <form method="post">
            <label for="test_to">Recipient</label>
            <input id="test_to" name="test_to" type="email" required placeholder="you@example.com" style="margin-left:8px;padding:6px;">
            <button type="submit" style="margin-left:8px;padding:6px 10px;">Send test</button>
        </form>
        <p style="font-size:0.9rem;color:#6b7280;margin-top:6px;">Test messages are logged to <code>includes/smtp_diag.log</code> and PHPMailer debug appears in <code>includes/signup_php_error.log</code>.</p>
    </section>

    <section style="margin-top:1.5rem;">
        <h3>Pending Host Managers (email failures highlighted)</h3>
        <table style="width:100%;border-collapse:collapse;margin-top:8px;">
            <thead><tr><th>Username</th><th>Name</th><th>Email</th><th>Verified</th><th>Email Status</th><th>Attempts</th><th>Last Attempt</th><th>Token</th></tr></thead>
            <tbody>
                <?php if (empty($pending)): ?>
                    <tr><td colspan="8">No pending managers.</td></tr>
                <?php else: foreach ($pending as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['Username']) ?></td>
                        <td><?= htmlspecialchars($p['Fname'] . ' ' . $p['Lname']) ?></td>
                        <td><?= htmlspecialchars($p['email']) ?></td>
                        <td><?= !empty($p['is_verified']) ? '<span style="color:green">Yes</span>' : '<span style="color:orange">No</span>' ?></td>
                        <td><?= !empty($p['email_failed']) ? '<span style="color:red">Failed</span>' : (intval($p['email_attempts'])>0 ? '<span style="color:green">Sent</span>' : '<span style="color:gray">Pending</span>') ?></td>
                        <td><?= intval($p['email_attempts']) ?></td>
                        <td><?= htmlspecialchars($p['last_email_attempt']) ?></td>
                        <td style="font-family:monospace;"><?= htmlspecialchars(substr($p['token'],0,10)) ?>...</td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </section>

    <section style="margin-top:1.5rem;">
        <h3>Recent PHPMailer Log (last 60 non-empty lines)</h3>
        <pre style="background:#0b1220;color:#e6eef8;padding:10px;max-height:420px;overflow:auto;"><?= htmlspecialchars(implode("\n", $lastLines)) ?></pre>
    </section>
</div>

<?php require_once 'admin_footer.php'; ?>
