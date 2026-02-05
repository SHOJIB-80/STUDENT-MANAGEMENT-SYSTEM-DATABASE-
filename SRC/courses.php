<?php 
include('server.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
<div class="content" style="
  width: 95%;
  max-width: 1400px;
  margin: 0 auto;
">
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

        echo "<div class='course-list'>";

while (mysqli_stmt_fetch($stmt)) {

    $now = date('Y-m-d H:i:s');
    $can_enroll = (is_null($start) || $start <= $now) && (is_null($end) || $now <= $end);
    $space_left = ($max == 0) ? true : ($enrolled_count < $max);

    echo "<div class='course-card'>";

    echo "<div class='course-title'>" . htmlspecialchars($cname) . "</div>";
    echo "<div class='course-code'>Course Code: " . htmlspecialchars($ccode) . "</div>";

    echo "<div class='course-meta'><strong>Faculty:</strong> " . ($faculty ?: 'N/A') . "</div>";
    echo "<div class='course-meta'><strong>Enrolled:</strong> $enrolled_count / " . ($max == 0 ? "Unlimited" : $max) . "</div>";

    echo "<div class='course-meta'><strong>Enrollment:</strong> " . 
         ($start ?: 'Open') . " → " . ($end ?: 'No end') . "</div>";

    echo "<div class='course-actions'>";

    if ($my_status === 'enrolled') {

        echo "<span class='badge badge-green'>Enrolled</span> ";

        if ($can_enroll) {
            echo "<form method='post' action='enroll_action.php'>";
            echo "<input type='hidden' name='action' value='drop'>";
            echo "<input type='hidden' name='course_id' value='$cid'>";
            echo "<button type='submit' class='btn' style='background:#dc3545;'>Drop</button>";
            echo "</form>";
        }

    } else {

        if ($can_enroll && $space_left) {
            echo "<form method='post' action='enroll_action.php'>";
            echo "<input type='hidden' name='action' value='enroll'>";
            echo "<input type='hidden' name='course_id' value='$cid'>";
            echo "<button type='submit' class='btn' style='background:#28a745;'>Enroll</button>";
            echo "</form>";
        } else {
            echo "<span class='badge badge-red'>Closed / Full</span>";
        }
    }

    echo "</div>";
    echo "</div>";
}

echo "</div>";

        mysqli_stmt_close($stmt);
    } else {
        echo "Failed to load courses.";
    }
    ?>
<div class="back-wrapper">
  <a href="index.php" class="btn btn-back">
    ← Back to Dashboard
  </a>
</div>

<?php endif; ?>
</div>
</body>
</html>