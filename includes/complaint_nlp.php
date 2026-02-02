<?php
// Simple rule-based classifier and router for complaints
// Returns array: ['urgency'=> 'low'|'medium'|'high', 'topic'=>string, 'score'=>float, 'suggested_manager_id'=>int|null]

function classifyComplaintText(string $text, mysqli $conn, ?int $hostelId = null): array {
    $t = strtolower($text);

    // Weighted keywords for urgency
    $urgent_kw = [
        'fire' => 5, 'gas leak' => 5, 'electric shock' => 5, 'injury' => 5, 'accident' => 5,
        'flood' => 5, 'no power' => 4, 'no water' => 4, 'water leak' => 4
    ];
    $medium_kw = ['leak' => 3, 'broken' => 3, 'not working' => 2, 'internet' => 2, 'rodent' => 2];
    $low_kw = ['dirty' => 1, 'smell' => 1, 'request' => 1, 'suggestion' => 1];

    $score = 0.0;
    foreach ($urgent_kw as $kw => $w) {
        if (strpos($t, $kw) !== false) $score += $w;
    }
    foreach ($medium_kw as $kw => $w) {
        if (strpos($t, $kw) !== false) $score += $w;
    }
    foreach ($low_kw as $kw => $w) {
        if (strpos($t, $kw) !== false) $score += $w;
    }

    if ($score >= 5) $urgency = 'high';
    elseif ($score >= 2) $urgency = 'medium';
    else $urgency = 'low';

    // Topic mapping
    $topic_map = [
        'plumbing' => ['leak','water','toilet','flush','drain','sink'],
        'electrical' => ['electric','power','socket','short circuit','fuse','bulb','light','no power','electric shock'],
        'internet' => ['internet','wifi','slow internet','network'],
        'pest_control' => ['rat','rodent','cockroach','pest'],
        'safety' => ['fire','gas','accident','injury','smoke']
    ];
    $topic_scores = [];
    foreach ($topic_map as $topic => $words) {
        foreach ($words as $w) {
            if (strpos($t, $w) !== false) $topic_scores[$topic] = ($topic_scores[$topic] ?? 0) + 1;
        }
    }
    arsort($topic_scores);
    $topic = $topic_scores ? array_key_first($topic_scores) : 'general';

    // Try to find suggested manager for the hostel
    $suggested_manager_id = null;
    if ($hostelId) {
        $dept_mapping = [
            'plumbing' => 'Maintenance',
            'electrical' => 'Maintenance',
            'internet' => 'IT',
            'pest_control' => 'Maintenance',
            'safety' => 'Security'
        ];
        $desired_dept = $dept_mapping[$topic] ?? null;
        if ($desired_dept) {
            $sql = "SELECT Hostel_man_id FROM hostel_manager WHERE Hostel_id = ? AND Dept = ? LIMIT 1";
            $stmt = mysqli_stmt_init($conn);
            if (mysqli_stmt_prepare($stmt, $sql)) {
                mysqli_stmt_bind_param($stmt, "is", $hostelId, $desired_dept);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                if ($row = mysqli_fetch_assoc($res)) {
                    $suggested_manager_id = (int)$row['Hostel_man_id'];
                }
                mysqli_stmt_close($stmt);
            }
        }
        // fallback to any manager for the hostel
        if (!$suggested_manager_id) {
            $sql2 = "SELECT Hostel_man_id FROM hostel_manager WHERE Hostel_id = ? LIMIT 1";
            $stmt2 = mysqli_stmt_init($conn);
            if (mysqli_stmt_prepare($stmt2, $sql2)) {
                mysqli_stmt_bind_param($stmt2, "i", $hostelId);
                mysqli_stmt_execute($stmt2);
                $res2 = mysqli_stmt_get_result($stmt2);
                if ($row2 = mysqli_fetch_assoc($res2)) $suggested_manager_id = (int)$row2['Hostel_man_id'];
                mysqli_stmt_close($stmt2);
            }
        }
    }

    error_log("ComplaintClassifier: score={$score}, urgency={$urgency}, topic={$topic}, suggested_manager_id=" . ($suggested_manager_id ?? 'null'));

    return ['urgency' => $urgency, 'topic' => $topic, 'score' => $score, 'suggested_manager_id' => $suggested_manager_id];
}

?>
