<?php
// CLI mail test for local debugging (run with PHP CLI)
// Usage: C:\xampp\php\php.exe c:\xampp\htdocs\project\scripts\cli_mail_test.php recipient@example.com
require_once __DIR__ . '/../includes/config.inc.php';
require_once __DIR__ . '/../includes/functions.inc.php';

// Force debug
if (!defined('SMTP_DEBUG')) define('SMTP_DEBUG', true);

$to = $argv[1] ?? null;
if (!$to) {
    echo "Usage: php cli_mail_test.php recipient@example.com\n";
    exit(1);
}

$subject = 'CLI SMTP Test from HMS';
$body = '<p>CLI SMTP test, time: ' . date('Y-m-d H:i:s') . '</p>';

$ok = sendEmail($to, $subject, $body);
$log = date('Y-m-d H:i:s') . " - CLI TEST to=$to result=" . ($ok ? 'OK' : 'FAIL') . "\n";
file_put_contents(__DIR__ . '/cli_mail_test.log', $log, FILE_APPEND);
if ($ok) {
    echo "SEND OK\n";
} else {
    echo "SEND FAIL - check includes/signup_php_error.log and scripts/cli_mail_test.log\n";
}
