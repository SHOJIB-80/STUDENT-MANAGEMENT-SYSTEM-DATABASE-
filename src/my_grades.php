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
    <title>My Grades</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
<div class="header"><h2>My Grades</h2></div>
<div class="content">
<?php if (!$student_ref_id): ?>
    <div class="error">No linked student record found for your account.</div>
    <p><a href="index.php" class="btn">Back to Dashboard</a></p>
<?php else: ?>
    <h3>Your Grades</h3>
    <?php
    // Fetch enrollments and course credits; compute CGPA
    $sql = "SELECT c.course_code, c.course_name, f.name AS faculty, e.status, e.grade, e.enrolled_at, c.credits FROM enrollments e 
            JOIN courses c ON e.course_id = c.id 
            LEFT JOIN faculties f ON c.faculty_id = f.id 
            WHERE e.student_id = ? 
            ORDER BY e.enrolled_at DESC";
    $stmt = mysqli_prepare($db, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $student_ref_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $ccode, $cname, $faculty, $status, $grade, $enrolled_at, $credits);

        $has = false;
        $rows = [];
        $total_points = 0.0;
        $total_credits = 0.0;

        function grade_to_points($g) {
            if ($g === null || $g === '') return null;
            $g = trim($g);
            // letter grades mapping (common US scale)
            $map = [
                'A+' => 4.0, 'A' => 4.0, 'A-' => 3.7,
                'B+' => 3.3, 'B' => 3.0, 'B-' => 2.7,
                'C+' => 2.3, 'C' => 2.0, 'C-' => 1.7,
                'D' => 1.0, 'F' => 0.0
            ];
            $u = strtoupper($g);
            if (isset($map[$u])) return $map[$u];

            // numeric grade fallback (0-100)
            if (is_numeric($g)) {
                $n = floatval($g);
                if ($n >= 90) return 4.0;
                if ($n >= 80) return 3.0;
                if ($n >= 70) return 2.0;
                if ($n >= 60) return 1.0;
                return 0.0;
            }
            return null; // unknown format
        }

        while (mysqli_stmt_fetch($stmt)) {
            $has = true;
            $rows[] = ['code'=>$ccode,'name'=>$cname,'faculty'=>$faculty,'status'=>$status,'grade'=>$grade,'date'=>$enrolled_at,'credits'=>($credits?:0)];
            $gp = grade_to_points($grade);
            $cr = floatval($credits ?: 0);
            if ($gp !== null && $cr > 0) {
                $total_points += ($gp * $cr);
                $total_credits += $cr;
            }
        }
        mysqli_stmt_close($stmt);

        if ($has) {
            // CGPA
            $cgpa = ($total_credits > 0) ? ($total_points / $total_credits) : null;
            print "<p><strong>CGPA:</strong> " . ($cgpa !== null ? number_format($cgpa, 2) : 'N/A') . "</p>";
            print "<table style='width:100%; border-collapse: collapse;' border='1'>";
            print "<tr style='background:#007bff; color:#fff;'><th>Course Code</th><th>Course Name</th><th>Faculty</th><th>Credits</th><th>Status</th><th>Grade</th><th>Enrolled Date</th></tr>";
            foreach ($rows as $row) {
                $status_color = ($row['status'] === 'enrolled') ? '#28a745' : '#dc3545';
                print "<tr>";
                print "<td style='padding:8px;'>" . htmlspecialchars($row['code']) . "</td>";
                print "<td style='padding:8px;'>" . htmlspecialchars($row['name']) . "</td>";
                print "<td style='padding:8px;'>" . ($row['faculty'] ?: 'N/A') . "</td>";
                print "<td style='padding:8px;'>" . htmlspecialchars($row['credits']) . "</td>";
                print "<td style='padding:8px; background:$status_color; color:white;'>" . ucfirst($row['status']) . "</td>";
                print "<td style='padding:8px;'>" . ($row['grade'] !== null ? htmlspecialchars($row['grade']) : '—') . "</td>";
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