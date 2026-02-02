<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/admin_header.php';

// Admin auth (same as other admin pages)
if (empty($_SESSION['admin_username']) && (empty($_SESSION['isadmin']) || $_SESSION['isadmin'] != 1)) {
    header('Location: ../login-hostel_manager.php');
    exit;
}

// Fetch unreviewed classification audits
$sql = "SELECT a.id AS audit_id, a.complaint_id, a.student_id, a.hostel_id, a.urgency, a.topic, a.score, a.suggested_manager_id, a.created_at, s.Fname, s.Lname
    FROM complaint_classification_audit a
    LEFT JOIN student s ON s.Student_id = a.student_id
    WHERE a.reviewed = 0
    ORDER BY a.created_at DESC
    LIMIT 100";

$res = mysqli_query($conn, $sql);
$total_audits = mysqli_num_rows($res);

?>
<br><br><br>
<div class="container">
    <!-- Header -->
    <div class="header">
        <h1><i class="fa fa-clipboard-check"></i> Complaint Review</h1>
        <p>Review classifier decisions and correct urgency / assigned manager</p>
    </div>

    <!-- Complaint Review Section -->
    <div class="section">
        <div class="section-header">
            <h3><i class="fa fa-list"></i> Unreviewed Complaint Classifications</h3>
        </div>
        
        <?php if ($total_audits == 0): ?>
            <div class="alert alert-success">No unreviewed classifications at this time.</div>
        <?php else: ?>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Audit ID</th>
                        <th>Complaint ID</th>
                        <th>Student</th>
                        <th>Hostel</th>
                        <th>Complaint Text</th>
                        <th>Pred. Urgency</th>
                        <th>Pred. Topic</th>
                        <th>Score</th>
                        <th>Sugg. Manager</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
        <?php while ($row = mysqli_fetch_assoc($res)) : 
            // Try to fetch complaint text (schema may use complaint_id or Complaint_id)
            $complaint_text = '';
            if (!empty($row['complaint_id'])) {
                $cid = $row['complaint_id'];
                $q1 = mysqli_prepare($conn, "SELECT description FROM complaints WHERE complaint_id = ? LIMIT 1");
                if ($q1) {
                    mysqli_stmt_bind_param($q1, "i", $cid);
                    mysqli_stmt_execute($q1);
                    $r1 = mysqli_stmt_get_result($q1);
                    if ($rr = mysqli_fetch_assoc($r1)) $complaint_text = $rr['description'];
                    mysqli_stmt_close($q1);
                }
                if ($complaint_text === '') {
                    $q2 = mysqli_prepare($conn, "SELECT description FROM complaints WHERE Complaint_id = ? LIMIT 1");
                    if ($q2) {
                        mysqli_stmt_bind_param($q2, "i", $cid);
                        mysqli_stmt_execute($q2);
                        $r2 = mysqli_stmt_get_result($q2);
                        if ($rr2 = mysqli_fetch_assoc($r2)) $complaint_text = $rr2['description'];
                        mysqli_stmt_close($q2);
                    }
                }
            }
        ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['audit_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['complaint_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['student_id'] . ' - ' . ($row['Fname'] ?? '') . ' ' . ($row['Lname'] ?? '')); ?></td>
                    <td><?php echo htmlspecialchars($row['hostel_id']); ?></td>
                    <td style="max-width:250px;white-space:pre-wrap;font-size:0.9em"><?php echo htmlspecialchars(substr($complaint_text, 0, 100)); ?></td>
                    <td><span class="status-<?php echo $row['urgency'] === 'high' ? 'not-approved' : ($row['urgency'] === 'medium' ? 'alert-danger' : 'alert-success'); ?>"><?php echo htmlspecialchars($row['urgency']); ?></span></td>
                    <td><?php echo htmlspecialchars($row['topic']); ?></td>
                    <td><?php echo number_format($row['score'], 2); ?></td>
                    <td><?php echo htmlspecialchars($row['suggested_manager_id']); ?></td>
                    <td>
                        <button type="button" class="btn btn-primary btn-sm" onclick="openReviewModal(<?php echo $row['audit_id']; ?>)">Review</button>
                        
                        <!-- Modal for review -->
                        <div class="modal" id="reviewModal<?php echo $row['audit_id']; ?>">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h2>Review Complaint #<?php echo $row['complaint_id']; ?></h2>
                                    <span class="close" onclick="closeModal('reviewModal<?php echo $row['audit_id']; ?>')">&times;</span>
                                </div>
                                <form method="post" action="admin_process_review.php">
                                    <div class="modal-body">
                                        <input type="hidden" name="audit_id" value="<?php echo htmlspecialchars($row['audit_id']); ?>">
                                        <input type="hidden" name="complaint_id" value="<?php echo htmlspecialchars($row['complaint_id']); ?>">
                                        <input type="hidden" name="hostel_id" value="<?php echo htmlspecialchars($row['hostel_id']); ?>">
                                        
                                        <div class="form-group">
                                            <label for="urgency<?php echo $row['audit_id']; ?>">Urgency:</label>
                                            <select class="form-control" id="urgency<?php echo $row['audit_id']; ?>" name="urgency">
                                                <option value="low" <?php echo $row['urgency'] === 'low' ? 'selected' : ''; ?>>Low</option>
                                                <option value="medium" <?php echo $row['urgency'] === 'medium' ? 'selected' : ''; ?>>Medium</option>
                                                <option value="high" <?php echo $row['urgency'] === 'high' ? 'selected' : ''; ?>>High</option>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="topic<?php echo $row['audit_id']; ?>">Topic:</label>
                                            <input type="text" class="form-control" id="topic<?php echo $row['audit_id']; ?>" name="topic" value="<?php echo htmlspecialchars($row['topic']); ?>">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="assigned_manager_id<?php echo $row['audit_id']; ?>">Assign Manager ID:</label>
                                            <input type="text" class="form-control" id="assigned_manager_id<?php echo $row['audit_id']; ?>" name="assigned_manager_id" value="<?php echo htmlspecialchars($row['suggested_manager_id'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-danger btn-sm" onclick="closeModal('reviewModal<?php echo $row['audit_id']; ?>')">Cancel</button>
                                        <button type="submit" class="btn-submit">Save Review</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </td>
                </tr>
        <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function openReviewModal(modalId) {
    document.getElementById('reviewModal' + modalId).style.display = 'block';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}
</script>
