<?php include('server.php');
session_start();

if (!isset($_SESSION['username'])) {
    header('location: login.php');
    exit();
}

$username = $_SESSION['username'];
// find student's internal id
$student_ref_id = null;
$stmt = mysqli_prepare($db, "SELECT student_ref_id FROM users WHERE username = ? LIMIT 1");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 's', $username);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $sref);
    if (mysqli_stmt_fetch($stmt)) { $student_ref_id = $sref; }
    mysqli_stmt_close($stmt);
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Courses</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
<div class="header"><h2>Available Courses</h2></div>
<div class="content">
<?php if (!$student_ref_id): ?>
    <div class="error">No linked student record found for your account. Please register using your Student ID as username, or ask admin to link your account.</div>
    <p><a href="index.php">Back to Dashboard</a></p>
<?php else: ?>
    <?php
    $sql = "SELECT c.id, c.course_code, c.course_name, f.name AS faculty, c.max_students, c.enroll_start, c.enroll_end,
            (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id AND e.status = 'enrolled') AS enrolled_count,
            (SELECT status FROM enrollments e2 WHERE e2.course_id = c.id AND e2.student_id = ? LIMIT 1) AS my_status
            FROM courses c LEFT JOIN faculties f ON c.faculty_id = f.id";
    $stmt = mysqli_prepare($db, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $student_ref_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $cid, $ccode, $cname, $faculty, $max, $start, $end, $enrolled_count, $my_status);

        print "<table style='width:100%; border-collapse: collapse;' border='1'>";
        print "<tr style='background:#007bff; color:#fff;'><th>Code</th><th>Name</th><th>Faculty</th><th>Enrolled</th><th>Max</th><th>Enroll Window</th><th>Action</th></tr>";
        while (mysqli_stmt_fetch($stmt)) {
            $now = date('Y-m-d H:i:s');
            $can_enroll = (is_null($start) || $start <= $now) && (is_null($end) || $now <= $end);
            $space_left = ($max == 0) ? true : ($enrolled_count < $max);
            print "<tr>";
            print "<td style='padding:8px;'>$ccode</td>";
            print "<td style='padding:8px;'>$cname</td>";
            print "<td style='padding:8px;'>" . ($faculty ?: 'N/A') . "</td>";
            print "<td style='padding:8px;'>$enrolled_count</td>";
            print "<td style='padding:8px;'>" . ($max==0? 'Unlimited' : $max) . "</td>";
            print "<td style='padding:8px;'>" . ($start? $start : 'Open') . " - " . ($end? $end : 'No end') . "</td>";
            print "<td style='padding:8px;'>";
            if ($my_status === 'enrolled') {
                // show drop button if within window
                if ($can_enroll) {
                    echo "<form method='post' action='enroll_action.php' style='display:inline;'>";
                    echo "<input type='hidden' name='action' value='drop'>";
                    echo "<input type='hidden' name='course_id' value='".htmlspecialchars($cid)."'>";
                    echo "<button type='submit' class='btn' style='background:#dc3545;'>Drop</button>";
                    echo "</form>";
                } else {
                    echo "<em>Enrolled</em>";
                }
            } else {
                if ($can_enroll && $space_left) {
                    echo "<form method='post' action='enroll_action.php' style='display:inline;'>";
                    echo "<input type='hidden' name='action' value='enroll'>";
                    echo "<input type='hidden' name='course_id' value='".htmlspecialchars($cid)."'>";
                    echo "<button type='submit' class='btn' style='background:#28a745;'>Enroll</button>";
                    echo "</form>";
                } else {
                    echo "<em>Closed/Full</em>";
                }
            }
            print "</td>";
            print "</tr>";
        }
        print "</table>";
        mysqli_stmt_close($stmt);
    } else {
        echo "Failed to load courses.";
    }
    ?>
    <p><a href="index.php">Back to Dashboard</a></p>
<?php endif; ?>
</div>
</body>
</html>