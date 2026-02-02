<?php
<?php
require __DIR__ . '/../PHPMailer/src/Exception.php';
require __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require __DIR__ . '/../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.example.com';      // <-- set host
    $mail->SMTPAuth = true;
    $mail->Username = 'user@example.com';  // <-- set user
    $mail->Password = 'secret';            // <-- set password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    $mail->Debugoutput = function($str, $level){ echo "[PHPMailer debug] $str\n"; };

    $mail->setFrom('from@example.com', 'Test');
    $mail->addAddress('to@example.com', 'Receiver');
    $mail->Subject = 'PHPMailer test';
    $mail->Body    = 'Hello from test script';
    if ($mail->send()) {
        echo "Test mail sent successfully\n";
    } else {
        echo "Test mail failed: " . $mail->ErrorInfo . "\n";
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}