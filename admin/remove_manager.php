<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/admin_header.php';
require_once __DIR__ . '/../includes/functions.inc.php';

// Admin check
if (empty($_SESSION['admin_username']) && (empty($_SESSION['isadmin']) || $_SESSION['isadmin'] != 1)) {
    header('Location: ../login-hostel_manager.php');
    exit;
}

// Fetch all hostel managers
$managers = getAllManagers($conn);


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Remove Hostel Manager</title>
    <link rel="stylesheet" href="admin_styles.css">
    <script>
        function confirmRemoval(managerId) {
            if (confirm("Are you sure you want to remove this manager?")) {
                window.location.href = `../includes/hm_remove.php?id=${managerId}`;
            }
        }
    </script>
</head>
<body>
<br><br><br>
<div class="container">
    <!-- Header -->
    <div class="header">
        <h1><i class="fa fa-user-times"></i> Remove Hostel Manager</h1>
        <p>Remove hostel managers from the system</p>
    </div>

    <!-- Manager Table Section -->
    <div class="section">
        <div class="section-header">
            <h3><i class="fa fa-list"></i> Hostel Managers</h3>
        </div>
        
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Hostel Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($managers)): ?>
                        <tr>
                            <td colspan="5" class="text-center">No managers found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($managers as $manager): if ($manager['Isadmin']==1) continue; ?>
                        <tr>
                            <td><?= htmlspecialchars($manager['Username']) ?></td>
                            <td><?= htmlspecialchars($manager['Fname'] . ' ' . $manager['Lname']) ?></td>
                            <td><?= htmlspecialchars($manager['email']) ?></td>
                            <td><?= htmlspecialchars($manager['Hostel_name']) ?></td>
                            <td>
                                <button onclick="confirmRemoval(<?= $manager['Hostel_man_id'] ?>)" class="btn btn-danger btn-sm">Remove</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="py-5">
    <div class="container py-md-5">
        <div class="footer-logo mb-5 text-center">
            <a class="navbar-brand" href="admin_home.php">Peaceful Living for Young <span class="display"> Scholars</span></a>
        </div>
    </div>
    <div class="footer-grid">
        <div class="list-footer">
            <ul class="footer-nav text-center">
                <li><a href="admin_home.php">Home</a></li>
                <li><a href="create_hm.php">Appoint</a></li>
                <li><a href="students.php">Students</a></li>
                <li><a href="admin_contact.php">Contact</a></li>
                <li><a href="admin_profile.php">Profile</a></li>
            </ul>
        </div>
    </div>
</footer>

</body>
</html>