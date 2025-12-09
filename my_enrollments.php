<?php include('server.php');
session_start();

if (!isset($_SESSION['username'])) {
    header('location: login.php');
    exit();
}

// find student's internal id
$student_ref_id = null;
$stmt = mysqli_prepare($db, "SELECT student_ref_id FROM users WHERE username = ? LIMIT 1");
if ($stmt) {
    $username = $_SESSION['username'];
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
    <title>My Enrollments</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
<div class="header"><h2>My Enrollments</h2></div>
<div class="content">
<?php if (!$student_ref_id): ?>
    <div class="error">No linked student record found for your account.</div>
    <p><a href="index.php" class="btn">Back to Dashboard</a></p>
<?php else: ?>
    <h3>Your Course Enrollments</h3>
    <?php
    $sql = "SELECT e.id, c.id AS course_id, c.course_code, c.course_name, f.name AS faculty, e.status, e.enrolled_at FROM enrollments e 
            JOIN courses c ON e.course_id = c.id 
            LEFT JOIN faculties f ON c.faculty_id = f.id 
            WHERE e.student_id = ? 
            ORDER BY e.enrolled_at DESC";
    $stmt = mysqli_prepare($db, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $student_ref_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $eid, $cid, $ccode, $cname, $faculty, $status, $enrolled_at);
        
        $has_enrollments = false;
        $rows = [];
        while (mysqli_stmt_fetch($stmt)) {
            $has_enrollments = true;
            $rows[] = ['id'=>$eid,'course_id'=>$cid,'code'=>$ccode,'name'=>$cname,'faculty'=>$faculty,'status'=>$status,'date'=>$enrolled_at];
        }
        mysqli_stmt_close($stmt);
        
        if ($has_enrollments) {
            print "<table style='width:100%; border-collapse: collapse;' border='1'>";
            print "<tr style='background:#007bff; color:#fff;'><th>Course Code</th><th>Course Name</th><th>Faculty</th><th>Status</th><th>Enrolled Date</th></tr>";
            foreach ($rows as $row) {
                $status_color = ($row['status'] === 'enrolled') ? '#28a745' : '#dc3545';
                print "<tr>";
                print "<td style='padding:8px;'>" . htmlspecialchars($row['code']) . "</td>";
                print "<td style='padding:8px;'>" . htmlspecialchars($row['name']) . "</td>";
                print "<td style='padding:8px;'>" . ($row['faculty'] ?: 'N/A') . "</td>";
                print "<td style='padding:8px; background:$status_color; color:white;'>" . ucfirst($row['status']) . "</td>";
                print "<td style='padding:8px;'>" . $row['date'] . "</td>";
                print "</tr>";
            }
            print "</table>";
        } else {
            print "You have no course enrollments yet. <a href='courses.php'>Browse courses</a>";
        }
    }
    ?>
    <p><a href="index.php">Back to Dashboard</a></p>
<?php endif; ?>
</div>
</body>
</html>