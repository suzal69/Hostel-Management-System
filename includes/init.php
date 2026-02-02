<?php
// Start session unconditionally - this file should be included first
session_start();

// Set error reporting after session start
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Load DB config
require_once __DIR__ . '/config.inc.php';

// Composer autoload if present
$composer = __DIR__ . '/../vendor/autoload.php';
if (file_exists($composer)) {
    require_once $composer;
} else {
    // Fallback: load PHPMailer classes if not using Composer
    if (file_exists(__DIR__ . '/../PHPMailer/src/PHPMailer.php')) {
        require_once __DIR__ . '/../PHPMailer/src/Exception.php';
        require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
        require_once __DIR__ . '/../PHPMailer/src/SMTP.php';
    }
}

// Optional: set timezone
date_default_timezone_set('UTC');