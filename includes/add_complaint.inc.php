<?php
session_start();
require_once 'config.inc.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Use 'roll' session variable (student login sets $_SESSION['roll'])
    if (isset($_SESSION['roll'], $_POST['category'], $_POST['description'])) {
        $studentId = $_SESSION['roll'];  // Student ID is stored in 'roll'
        $category = $_POST['category'];
        $description = $_POST['description'];

        // Validate input
        if (empty($category) || empty($description)) {
            header("Location: ../student_complaint.php?error=invalidinput");
            exit();
        }

        // Check if student has active food plan when category is Food Service
        if ($category === 'Food Service') {
            $food_plan_query = "SELECT ba.include_food, ba.food_plan 
                                FROM bed_allocation ba 
                                WHERE ba.student_id = ? AND ba.is_active = 1";
            $food_stmt = mysqli_stmt_init($conn);
            if (mysqli_stmt_prepare($food_stmt, $food_plan_query)) {
                mysqli_stmt_bind_param($food_stmt, "s", $studentId);
                mysqli_stmt_execute($food_stmt);
                $food_result = mysqli_stmt_get_result($food_stmt);
                $food_allocation = mysqli_fetch_assoc($food_result);
                mysqli_stmt_close($food_stmt);
                
                if (!$food_allocation || $food_allocation['include_food'] != 1 || empty($food_allocation['food_plan'])) {
                    header("Location: ../student_complaint.php?error=nofoodplan");
                    exit();
                }
            } else {
                header("Location: ../student_complaint.php?error=sqlerror");
                exit();
            }
        }

        // Get hostel_id from the student's record
        $sql = "SELECT Hostel_id FROM student WHERE Student_id = ?";
        $stmt = mysqli_stmt_init($conn);
        if (mysqli_stmt_prepare($stmt, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $studentId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $student = mysqli_fetch_assoc($result);
            
            if (!$student) {
                header("Location: ../student_complaint.php?error=studentnotfound");
                exit();
            }
            
            $hostelId = $student['Hostel_id'];
            mysqli_stmt_close($stmt);
        } else {
            header("Location: ../complaint.php?error=sqlerror");
            exit();
        }

        if ($hostelId) {
            $status = 'open';
            $submissionDate = date('Y-m-d H:i:s');

            // Classify complaint text for urgency/topic and suggest a manager
            require_once __DIR__ . '/complaint_nlp.php';
            $nlpResult = classifyComplaintText($description, $conn, $hostelId);
            $urgency = $nlpResult['urgency'] ?? 'low';
            $suggested_manager_id = $nlpResult['suggested_manager_id'] ?? null;

            // Insert complaint including urgency and assigned manager when available
            $sql = "INSERT INTO complaints (student_id, hostel_id, category, description, submission_date, status, urgency, assigned_manager_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_stmt_init($conn);

            if (mysqli_stmt_prepare($stmt, $sql)) {
                // Bind params: studentId (s), hostelId (i), category (s), description (s), submissionDate (s), status (s), urgency (s), assigned_manager_id (i/null)
                $assigned_manager_param = $suggested_manager_id; // may be null
                mysqli_stmt_bind_param($stmt, "sisssssi", $studentId, $hostelId, $category, $description, $submissionDate, $status, $urgency, $assigned_manager_param);
                mysqli_stmt_execute($stmt);

                if (mysqli_stmt_affected_rows($stmt) > 0) {
                    // Retrieve the inserted complaint id
                    $complaint_id = mysqli_insert_id($conn);

                    // Save audit entry for classifier decision (if table exists)
                    $audit_sql = "INSERT INTO complaint_classification_audit (complaint_id, student_id, hostel_id, urgency, topic, score, suggested_manager_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $audit_stmt = mysqli_stmt_init($conn);
                    if (mysqli_stmt_prepare($audit_stmt, $audit_sql)) {
                        $audit_score = $nlpResult['score'] ?? 0.0;
                        $audit_topic = $nlpResult['topic'] ?? 'general';
                        // Types: i (complaint_id), s (student_id), i (hostel_id), s (urgency), s (topic), d (score), i (suggested_manager_id)
                        mysqli_stmt_bind_param($audit_stmt, "isissdi", $complaint_id, $studentId, $hostelId, $urgency, $audit_topic, $audit_score, $assigned_manager_param);
                        @mysqli_stmt_execute($audit_stmt);
                        mysqli_stmt_close($audit_stmt);
                    }

                    // Optionally notify the suggested manager by inserting a system message (if messaging table exists)
                    if (!empty($assigned_manager_param)) {
                        $sub = "[Urgency: " . strtoupper($urgency) . "] New complaint: " . substr($category, 0, 50);
                        $msg = "A new complaint was submitted. Urgency: {$urgency}. Topic: " . ($nlpResult['topic'] ?? 'general') . ".";
                        $m_insert = "INSERT INTO message (sender_id, receiver_id, hostel_id, subject_h, message, msg_date, msg_time) VALUES (?, ?, ?, ?, ?, ?, ?)";
                        $m_stmt = mysqli_stmt_init($conn);
                        if (mysqli_stmt_prepare($m_stmt, $m_insert)) {
                            $sysSender = 'system';
                            $today_date = date("Y-m-d");
                            $time = date("h:i A");
                            mysqli_stmt_bind_param($m_stmt, "siissss", $sysSender, $assigned_manager_param, $hostelId, $sub, $msg, $today_date, $time);
                            mysqli_stmt_execute($m_stmt);
                            mysqli_stmt_close($m_stmt);
                        }
                    }

                    mysqli_stmt_close($stmt);
                    header("Location: ../student_complaint.php?success=complaintadded");
                    exit();
                } else {
                    mysqli_stmt_close($stmt);
                    header("Location: ../student_complaint.php?error=failedtoadd");
                    exit();
                }
            } else {
                header("Location: ../student_complaint.php?error=sqlerror");
                exit();
            }
        } else {
            header("Location: ../student_complaint.php?error=nohostelfound");
            exit();
        }

    } else {
        header("Location: ../student_complaint.php?error=invalidinput");
        exit();
    }
} else {
    header("Location: ../student_complaint.php");
    exit();
}
mysqli_close($conn);
