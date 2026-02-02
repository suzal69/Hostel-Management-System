<?php
require '../includes/config.inc.php';
require_once __DIR__ . '/admin_header.php';

// Handle room management actions
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'create_room') {
        $hostel_id = $_POST['hostel_id'];
        $room_no = $_POST['room_no'];
        $bed_capacity = $_POST['bed_capacity'];
        $base_price = $_POST['base_price'];
        
        $insert = "INSERT INTO Room (Hostel_id, Room_No, bed_capacity, base_price, current_occupancy) 
                   VALUES (?, ?, ?, ?, 0)";
        $stmt = mysqli_prepare($conn, $insert);
        mysqli_stmt_bind_param($stmt, "iiid", $hostel_id, $room_no, $bed_capacity, $base_price);
        
        if (mysqli_stmt_execute($stmt)) {
            echo "<script>alert('Room created successfully!');</script>";
        } else {
            echo "<script>alert('Error creating room.');</script>";
        }
        mysqli_stmt_close($stmt);
        
    } elseif ($action === 'update_price') {
        $room_id = $_POST['room_id'];
        $base_price = $_POST['base_price'];
        
        $update = "UPDATE Room SET base_price = ? WHERE Room_id = ?";
        $stmt = mysqli_prepare($conn, $update);
        mysqli_stmt_bind_param($stmt, "di", $base_price, $room_id);
        
        if (mysqli_stmt_execute($stmt)) {
            echo "<script>alert('Room price updated successfully!');</script>";
        } else {
            echo "<script>alert('Error updating price.');</script>";
        }
        mysqli_stmt_close($stmt);
        
    } elseif ($action === 'delete_room') {
        $room_id = $_POST['room_id'];
        
        // Check if room has active allocations
        $check = "SELECT COUNT(*) as count FROM bed_allocation WHERE room_id = ? AND is_active = 1";
        $stmt = mysqli_prepare($conn, $check);
        mysqli_stmt_bind_param($stmt, "i", $room_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        if ($row['count'] > 0) {
            echo "<script>alert('Cannot delete room with active allocations!');</script>";
        } else {
            $delete = "DELETE FROM Room WHERE Room_id = ?";
            $stmt = mysqli_prepare($conn, $delete);
            mysqli_stmt_bind_param($stmt, "i", $room_id);
            
            if (mysqli_stmt_execute($stmt)) {
                echo "<script>alert('Room deleted successfully!');</script>";
            } else {
                echo "<script>alert('Error deleting room.');</script>";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Get all hostels for dropdown
$hostels_query = "SELECT Hostel_id, Hostel_name FROM Hostel ORDER BY Hostel_name";
$hostels_result = mysqli_query($conn, $hostels_query);

// Get all rooms with details
$rooms_query = "SELECT r.*, h.Hostel_name, 
                (SELECT COUNT(*) FROM bed_allocation ba WHERE ba.room_id = r.Room_id AND ba.is_active = 1) as current_occupancy
                FROM Room r 
                JOIN Hostel h ON r.Hostel_id = h.Hostel_id 
                ORDER BY h.Hostel_name, r.Room_No";
$rooms_result = mysqli_query($conn, $rooms_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>PLYS | Room Management</title>
    
    <!-- Meta tag Keywords -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <meta name="keywords" content="PLYS Responsive web template, Bootstrap Web Templates, Flat Web Templates, Android Compatible web template, 
    Smartphone Compatible web template, free webdesigns for Nokia, Samsung, LG, SonyEricsson, Motorola web design" />
</head>
<body>
<br><br><br>
<div class="container">
    <!-- Header -->
    <div class="header">
        <h1><i class="fa fa-bed"></i> Room Management</h1>
        <p>Manage hostel rooms, pricing, and occupancy</p>
    </div>

    <!-- Create Room Section -->
    <div class="section">
        <div class="section-header">
            <h3><i class="fa fa-plus"></i> Create New Room</h3>
        </div>
        
        <div class="mail_grid_w3l">
            <form method="post">
                <input type="hidden" name="action" value="create_room">
                <div class="form-group">
                    <label for="hostel_id">Hostel</label>
                    <select name="hostel_id" class="form-control" required>
                        <option value="">Select Hostel</option>
                        <?php while ($hostel = mysqli_fetch_assoc($hostels_result)): ?>
                            <option value="<?php echo $hostel['Hostel_id']; ?>">
                                <?php echo htmlspecialchars($hostel['Hostel_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="room_no">Room Number</label>
                    <input type="number" name="room_no" class="form-control" required min="1">
                </div>
                <div class="form-group">
                    <label for="bed_capacity">Bed Capacity</label>
                    <select name="bed_capacity" class="form-control" required>
                        <option value="1">1 Bed</option>
                        <option value="2">2 Beds</option>
                        <option value="3">3 Beds</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="base_price">Base Price</label>
                    <input type="number" name="base_price" class="form-control" step="0.01" min="0" required>
                </div>
                <button type="submit" class="btn btn-primary">Create Room</button>
            </form>
        </div>
        
        <!-- Existing Rooms -->
        <div class="card">
            <h3>Existing Rooms</h3>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Room ID</th>
                            <th>Hostel</th>
                            <th>Room Number</th>
                            <th>Bed Capacity</th>
                            <th>Current Occupancy</th>
                            <th>Base Price</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php mysqli_data_seek($hostels_result, 0); ?>
                        <?php while ($room = mysqli_fetch_assoc($rooms_result)): ?>
                            <tr>
                                <td><?php echo $room['Room_id']; ?></td>
                                <td><?php echo htmlspecialchars($room['Hostel_name']); ?></td>
                                <td><?php echo $room['Room_No']; ?></td>
                                <td><?php echo $room['bed_capacity']; ?></td>
                                <td><?php echo $room['current_occupancy']; ?>/<?php echo $room['bed_capacity']; ?></td>
                                <td><?php echo number_format($room['base_price'], 2); ?></td>
                                <td>
                                    <?php
                                    if ($room['current_occupancy'] == 0) {
                                        echo '<span class="occupancy-badge occupancy-empty">Empty</span>';
                                    } elseif ($room['current_occupancy'] < $room['bed_capacity']) {
                                        echo '<span class="occupancy-badge occupancy-partial">Partial</span>';
                                    } else {
                                        echo '<span class="occupancy-badge occupancy-full">Full</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-warning" onclick="openPriceModal(<?php echo $room['Room_id']; ?>, <?php echo $room['base_price']; ?>)">Edit Price</button>
                                        <button class="btn btn-danger" onclick="deleteRoom(<?php echo $room['Room_id']; ?>)">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Price Update Modal -->
    <div id="priceModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close" onclick="closePriceModal()">&times;</span>
                <h3>Update Room Price</h3>
            </div>
            <div class="modal-body">
                <form method="post">
                    <input type="hidden" name="action" value="update_price">
                    <input type="hidden" id="modal_room_id" name="room_id">
                    <div class="form-group">
                        <label for="modal_base_price">New Base Price</label>
                        <input type="number" id="modal_base_price" name="base_price" class="form-control" step="0.01" min="0" required>
                    </div>
                    <button type="submit" class="btn btn-success">Update Price</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function openPriceModal(roomId, currentPrice) {
            document.getElementById('modal_room_id').value = roomId;
            document.getElementById('modal_base_price').value = currentPrice;
            document.getElementById('priceModal').style.display = 'block';
        }
        
        function closePriceModal() {
            document.getElementById('priceModal').style.display = 'none';
        }
        
        function deleteRoom(roomId) {
            if (confirm('Are you sure you want to delete this room? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'post';
                form.innerHTML = '<input type="hidden" name="action" value="delete_room"><input type="hidden" name="room_id" value="' + roomId + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('priceModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
    
    <br><br><br>
    <br><br><br>
    <br><br><br>
    <!-- footer -->
    <footer class="py-5" style="background:#36454F;">
        <div class="container py-md-5">
            <div class="footer-logo mb-5 text-center">
                <a class="navbar-brand" href="../home.php">Peaceful Living for Young <span class="display"> Scholars</span></a>
            </div>
            <div class="footer-grid">
                <div class="list-footer">
                    <ul class="footer-nav text-center">
                        <li>
                            <a href="../home.php">Home</a>
                        </li>
                        <li>
                            <a href="../services.php">Hostels</a>
                        </li>
                        <li>
                            <a href="../contact.php">Contact</a>
                        </li>
                        <li>
                            <a href="admin_contact.php">Admin</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>