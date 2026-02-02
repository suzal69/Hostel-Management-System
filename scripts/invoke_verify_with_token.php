<?php
require_once __DIR__ . '/../includes/config.inc.php';

$res = mysqli_query($conn, "SELECT token, email FROM pending_hostel_manager WHERE is_verified = 0 LIMIT 1");
if ($r = mysqli_fetch_assoc($res)) {
    echo "Found pending token for " . $r['email'] . "\n";
    // Set GET param and include verify flow
    $_GET['token'] = $r['token'];
    // Include verify_hm.php to exercise the flow
    include __DIR__ . '/../verify_hm.php';
} else {
    echo "No pending, unverified hostel manager found.\n";
}
