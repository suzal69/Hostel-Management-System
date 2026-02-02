<?php
  // Start session only if none is active
  if (session_status() === PHP_SESSION_NONE) {
      session_start();
  }

  $servername = "localhost"; 
  $dBUsername = "root";
  $dBPassword = "";
  $dBName = "hostel_management_system";

  $conn = mysqli_connect($servername, $dBUsername, $dBPassword, $dBName);

  if (!$conn) {
    die("Connection Failed: ".mysqli_connect_error());
  }

// ---- SMTP CONFIGURATION ----
// For security, replace these defaults with environment-specific values and do NOT commit secrets to source control.
// If you use Gmail, create an App Password and place the value below in SMTP_PASS.
// Example (uncomment and set):
if (!defined('SMTP_HOST')) define('SMTP_HOST', 'smtp.gmail.com');
if (!defined('SMTP_USER')) define('SMTP_USER', 'sthapitsuzal@gmail.com');
// Note: App Passwords are 16 characters without spaces. Using provided App Password (spaces removed).
if (!defined('SMTP_PASS')) define('SMTP_PASS', 'dmgwhbisnwkdlqne');
if (!defined('SMTP_PORT')) define('SMTP_PORT', 587);
if (!defined('SMTP_SECURE')) define('SMTP_SECURE', 'tls'); // or 'ssl'
if (!defined('SMTP_FROM_EMAIL')) define('SMTP_FROM_EMAIL', 'sthapitsuzal@gmail.com');
if (!defined('SMTP_FROM_NAME')) define('SMTP_FROM_NAME', 'Student Management System');
// Enable verbose debug output for SMTP (set to false after testing)
if (!defined('SMTP_DEBUG')) define('SMTP_DEBUG', true);
// Turn off debug log file creation by default (set to true only for troubleshooting)
if (!defined('ENABLE_DEBUG_LOG')) define('ENABLE_DEBUG_LOG', false);

?>
