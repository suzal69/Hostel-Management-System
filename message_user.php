<?php
require 'includes/config.inc.php';
require 'includes/user_header.php';

// Ensure session is started and student roll number is available
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$roll_no = $_SESSION['roll'] ?? null;

if (!$roll_no) {
    // Redirect to login or show an error if student ID is not set
    header("Location: index.php?error=notloggedin");
    exit();
}

// Fetch notification count for unread messages (This logic is retained from the original header)
$message_notification_count = 0;
if (isset($conn)) {
    $sql_message_notifications = "SELECT COUNT(*) AS count FROM Message WHERE receiver_id = ? AND read_status = 0";
    $stmt_message_notifications = mysqli_prepare($conn, $sql_message_notifications);
    if ($stmt_message_notifications) {
        mysqli_stmt_bind_param($stmt_message_notifications, "s", $roll_no);
        mysqli_stmt_execute($stmt_message_notifications);
        $result_message_notifications = mysqli_stmt_get_result($stmt_message_notifications);
        if ($row = mysqli_fetch_assoc($result_message_notifications)) {
            $message_notification_count = $row['count'];
        }
        mysqli_stmt_close($stmt_message_notifications);
    }
}

// Get student's hostel assignment
$studentHostel = null;
$sql_hostel = "SELECT Hostel_id, Room_id FROM Student WHERE Student_id = ?";
$stmt_hostel = mysqli_stmt_init($conn);
if (mysqli_stmt_prepare($stmt_hostel, $sql_hostel)) {
    mysqli_stmt_bind_param($stmt_hostel, "s", $roll_no);
    mysqli_stmt_execute($stmt_hostel);
    $result_hostel = mysqli_stmt_get_result($stmt_hostel);
    if ($row_hostel = mysqli_fetch_assoc($result_hostel)) {
        $studentHostel = $row_hostel;
    }
    mysqli_stmt_close($stmt_hostel);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Intrend Interior Category Flat Bootstrap Responsive Website Template | Messages</title>
    
    <!-- Meta tag Keywords -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <script type="application/x-javascript">
        addEventListener("load", function () {
            setTimeout(hideURLbar, 0);
        }, false);

        function hideURLbar() {
            window.scrollTo(0, 1);
        }
    </script>
    <!--// Meta tag Keywords -->
        
    <!-- css files -->
    <link rel="stylesheet" href="web_home/css_home/bootstrap.css"> <!-- Bootstrap-Core-CSS -->
    <link rel="stylesheet" href="web_home/css_home/style.css" type="text/css" media="all" /> <!-- Style-CSS --> 
    <!-- //css files -->
    
    <!-- web-fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<style>
    body {
        font-family: 'Poppins', sans-serif;
        margin: 0;
        padding: 0;
        background-color: #f8f9fa;
    }
    .container {
        max-width: 1200px;
        margin: 0 auto;
    }
    .header {
        background: linear-gradient(135deg, #003366 0%, #004080 100%);
        color: white;
        border-radius: 10px;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        text-align: center;
    }
    .header h1 {
        font-size: 2.5rem;
        font-weight: 600;
        margin-bottom: 10px;
    }
    .header p {
        font-size: 1.1rem;
        margin: 0;
        opacity: 0.9;
    }
    .section {
        background: white;
        border-radius: 10px;
        padding: 25px;
        margin-bottom: 20px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    }
    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #e9ecef;
    }
    .section-header h3 {
        font-size: 1.8rem;
        font-weight: 600;
        color: #003366;
        margin: 0;
    }
    .section-header h3 i {
        margin-right: 10px;
        color: #003366;
    }
    .message-card {
        background: white;
        border: 1px solid #e9ecef;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 15px;
        transition: all 0.3s ease;
        box-shadow: 0 3px 10px rgba(0,0,0,0.05);
        position: relative;
    }
    .message-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    .message-card.unread {
        border-left: 4px solid #003366;
        background: linear-gradient(135deg, #f8f9ff 0%, #e8f0ff 100%);
    }
    .message-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid #e9ecef;
    }
    .message-sender {
        font-size: 1.1rem;
        font-weight: 600;
        color: #003366;
        margin: 0;
    }
    .message-time {
        font-size: 0.85rem;
        color: #666;
    }
    .message-content {
        color: #333;
        line-height: 1.6;
        margin-bottom: 15px;
    }
    .message-meta {
        font-size: 0.85rem;
        color: #666;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .message-type {
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    .type-received {
        background-color: #e3f2fd;
        color: #1976d2;
    }
    .type-sent {
        background-color: #f3e5f5;
        color: #7b1fa2;
    }
    .notification-badge {
        background-color: #ffc107;
        color: #212529;
        font-size: 0.75em;
        padding: .2em .6em;
        border-radius: .25rem;
        margin-left: 5px;
        vertical-align: super;
    }
    .empty-state {
        text-align: center;
        padding: 40px;
        color: #666;
    }
    .empty-state i {
        font-size: 3rem;
        margin-bottom: 15px;
        opacity: 0.5;
    }
    @media (max-width: 768px) {
        .header h1 {
            font-size: 2rem;
        }
        .section-header h3 {
            font-size: 1.5rem;
        }
    }
</style>
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-envelope"></i> Message Inbox</h1>
            <p>View and manage your messages</p>
        </div>

        <?php if ($studentHostel && $studentHostel['Hostel_id'] && $studentHostel['Room_id']): ?>
        <!-- Received Messages Section -->
        <div class="section">
            <div class="section-header">
                <h3><i class="fas fa-inbox"></i> Received Messages</h3>
                <?php if ($message_notification_count > 0): ?>
                    <span class="notification-badge"><?php echo $message_notification_count; ?></span>
                <?php endif; ?>
            </div>

        <?php
        $found_any = false;

        // --- 1. RECEIVED MESSAGES (Typically from Hostel Manager/Admin) ---
        // Join Message with Hostel to get the name of the Hostel the message is related to
        $received_sql = "SELECT m.*, h.Hostel_name
                         FROM Message m
                         LEFT JOIN Hostel h ON m.hostel_id = h.Hostel_id
                         WHERE m.receiver_id = ?
                         ORDER BY m.msg_date DESC, m.msg_time DESC";

        if ($rstmt = mysqli_prepare($conn, $received_sql)) {
            mysqli_stmt_bind_param($rstmt, "s", $roll_no);
            mysqli_stmt_execute($rstmt);
            $received = mysqli_stmt_get_result($rstmt);

            if ($received && mysqli_num_rows($received) > 0) {
                $found_any = true;
                $unread_message_ids = [];
                while ($row = mysqli_fetch_assoc($received)) {
                    $hostel_name = htmlspecialchars($row['Hostel_name'] ?? 'System/Admin');
                    $is_unread = $row['read_status'] == 0;
                    
                    // Collect unread message IDs to mark as read
                    if ($is_unread) {
                        $unread_message_ids[] = $row['msg_id'];
                    }
                    ?>
                    <div class="message-card <?php echo $is_unread ? 'unread' : ''; ?>" data-type="received">
                        <div class="message-header">
                            <div class="message-sender">
                                <i class="fas fa-user"></i> <?php echo $hostel_name; ?>
                                <?php if ($is_unread): ?>
                                    <span class="notification-badge">New</span>
                                <?php endif; ?>
                            </div>
                            <div class="message-time">
                                <i class="fas fa-clock"></i> 
                                <?php 
                                echo date('M d, Y h:i A', strtotime($row['msg_date'] . ' ' . $row['msg_time'])); 
                                ?>
                            </div>
                        </div>
                        <div class="message-content">
                            <?php echo nl2br(htmlspecialchars($row['message'])); ?>
                        </div>
                        <div class="message-meta">
                            <span class="message-type type-received">Received</span>
                            <span>Message ID: #<?php echo $row['msg_id']; ?></span>
                        </div>
                    </div>
                    <?php
                }
                
                // Mark unread messages as read
                if (!empty($unread_message_ids)) {
                    $placeholders = str_repeat('?,', count($unread_message_ids) - 1) . '?';
                    $query_mark_read = "UPDATE Message SET read_status = 1 WHERE msg_id IN ($placeholders) AND receiver_id = ?";
                    $stmt_mark_read = mysqli_prepare($conn, $query_mark_read);
                    
                    $types = str_repeat('i', count($unread_message_ids)) . 's';
                    $params = array_merge($unread_message_ids, [$roll_no]);
                    
                    mysqli_stmt_bind_param($stmt_mark_read, $types, ...$params);
                    mysqli_stmt_execute($stmt_mark_read);
                    mysqli_stmt_close($stmt_mark_read);
                }
            }
            mysqli_stmt_close($rstmt);
        }
        ?>

        <?php if (!$found_any): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>No messages found in your inbox.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Sent Messages Section -->
    <div class="section">
        <div class="section-header">
            <h3><i class="fas fa-paper-plane"></i> Sent Messages</h3>
        </div>

        <?php
        // --- 2. SENT MESSAGES ---
        $sent_sql = "SELECT m.*, h.Hostel_name
                    FROM Message m
                    LEFT JOIN Hostel h ON m.hostel_id = h.Hostel_id
                    WHERE m.sender_id = ?
                    ORDER BY m.msg_date DESC, m.msg_time DESC";

        if ($sstmt = mysqli_prepare($conn, $sent_sql)) {
            mysqli_stmt_bind_param($sstmt, "s", $roll_no);
            mysqli_stmt_execute($sstmt);
            $sent = mysqli_stmt_get_result($sstmt);

            if ($sent && mysqli_num_rows($sent) > 0) {
                while ($row = mysqli_fetch_assoc($sent)) {
                    $hostel_name = htmlspecialchars($row['Hostel_name'] ?? 'System/Admin');
                    ?>
                    <div class="message-card" data-type="sent">
                        <div class="message-header">
                            <div class="message-sender">
                                <i class="fas fa-paper-plane"></i> To: <?php echo $hostel_name; ?>
                            </div>
                            <div class="message-time">
                                <i class="fas fa-clock"></i> 
                                <?php 
                                echo date('M d, Y h:i A', strtotime($row['msg_date'] . ' ' . $row['msg_time'])); 
                                ?>
                            </div>
                        </div>
                        <div class="message-content">
                            <?php echo nl2br(htmlspecialchars($row['message'])); ?>
                        </div>
                        <div class="message-meta">
                            <span class="message-type type-sent">Sent</span>
                            <span>Message ID: #<?php echo $row['msg_id']; ?></span>
                        </div>
                    </div>
                    <?php
                }
            } else {
                ?>
                <div class="empty-state">
                    <i class="fas fa-paper-plane"></i>
                    <p>No sent messages found.</p>
                </div>
                <?php
            }
            mysqli_stmt_close($sstmt);
        }
        ?>
    </div>
        
        <?php else: ?>
            <!-- No Hostel Assigned Message -->
            <div class="section">
                <div class="empty-state" style="text-align: center; padding: 40px;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: #856404; margin-bottom: 15px;"></i>
                    <h3 style="color: #856404; margin-bottom: 15px;">No Hostel Assigned</h3>
                    <p style="color: #856404; font-size: 1.1rem; margin-bottom: 20px;">
                        You haven't been assigned to any hostel yet. Please contact the hostel administrator or apply for hostel accommodation first.
                    </p>
                    <a href="services.php" class="btn" style="background: #003366; color: white; padding: 10px 25px; border-radius: 25px; text-decoration: none; display: inline-block;">
                        <i class="fas fa-home"></i> Apply for Hostel
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="py-5" style="background:#36454F;">
        <div class="container py-md-5">
            <div class="footer-logo mb-5 text-center">
                <a class="navbar-brand" href="home.php">Peaceful Living for Young <span class="display"> Scholars</span></a>
            </div>
            <div class="footer-grid">
                <div class="list-footer">
                    <ul class="footer-nav text-center">
                        <li>
                            <a href="home.php">Home</a>
                        </li>
                        <li>
                            <a href="services.php">Hostels</a>
                        </li>
                        <li>
                            <a href="contact.php">Contact</a>
                        </li>
                        <li>
                            <a href="profile.php">Profile</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script type="text/javascript" src="web_home/js/jquery-2.2.3.min.js"></script>
    <script type="text/javascript" src="web_home/js/bootstrap.js"></script>
    <script type="text/javascript" src="web_home/js/SmoothScroll.min.js"></script>
    <script type="text/javascript" src="web_home/js/move-top.js"></script>
    <script type="text/javascript" src="web_home/js/easing.js"></script>

</body>
</html>
