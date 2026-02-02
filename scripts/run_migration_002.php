<?php
require_once __DIR__ . '/../includes/config.inc.php';

echo "Checking pending_hostel_manager columns...\n";
$table = 'pending_hostel_manager';
$cols = ['is_verified','verified_at'];
$missing = [];
foreach ($cols as $c) {
    $sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?";
    $stmt = mysqli_stmt_init($conn);
    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "Failed to prepare info_schema query: " . mysqli_error($conn) . "\n";
        exit(1);
    }
    mysqli_stmt_bind_param($stmt, 'sss', $dBName, $table, $c);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    if (mysqli_stmt_num_rows($stmt) === 0) {
        $missing[] = $c;
    }
    mysqli_stmt_close($stmt);
}

if (empty($missing)) {
    echo "All columns exist. Nothing to do.\n";
    exit(0);
}

echo "Missing columns: " . implode(', ', $missing) . "\n";

$alter = "ALTER TABLE `pending_hostel_manager`\n";
if (in_array('is_verified', $missing)) {
    $alter .= "  ADD COLUMN `is_verified` TINYINT(1) NOT NULL DEFAULT 0 AFTER `expires_at`,\n";
}
if (in_array('verified_at', $missing)) {
    $alter .= "  ADD COLUMN `verified_at` DATETIME NULL AFTER `is_verified`";
}
$alter = rtrim($alter, ",\n") . ";";

echo "Running migration:\n$alter\n";
if (mysqli_query($conn, $alter)) {
    echo "Migration applied successfully.\n";
    // Log to file
    file_put_contents(__DIR__ . '/migration_002.log', date('Y-m-d H:i:s') . " - applied\n", FILE_APPEND);
    exit(0);
} else {
    echo "Migration failed: " . mysqli_error($conn) . "\n";
    file_put_contents(__DIR__ . '/migration_002.log', date('Y-m-d H:i:s') . " - failed: " . mysqli_error($conn) . "\n", FILE_APPEND);
    exit(1);
}
