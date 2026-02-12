<?php 
include('server.php');
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
    if (mysqli_stmt_fetch($stmt)) { 
        $student_ref_id = $sref; 
    }
    mysqli_stmt_close($stmt);
}
// fetch student's department (stored in `course` column of `students` table)
$student_department = null;
if ($student_ref_id) {
    $sstmt = mysqli_prepare($db, "SELECT course FROM students WHERE id = ? LIMIT 1");
    if ($sstmt) {
        mysqli_stmt_bind_param($sstmt, 'i', $student_ref_id);
        mysqli_stmt_execute($sstmt);
        mysqli_stmt_bind_result($sstmt, $dept);
        if (mysqli_stmt_fetch($sstmt)) {
            $student_department = $dept;
        }
        mysqli_stmt_close($sstmt);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Enrollments</title>
    <link rel="stylesheet" type="text/css" href="style.css">

    <style>
        /* Page-specific improvements */
        .content-box {
            width: 80%;
            max-width: 900px;
            margin: 20px auto;
            padding: 20px;
            background: rgba(255,255,255,0.95);
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.3);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }

        table th, table td {
            padding: 12px;
            border: 1px solid #ddd;
            font-size: 14px;
        }

        table th {
            background: #007bff;
            color: white;
            text-align: left;
        }

        .status-active {
            background: #28a745;
            color: #fff;
            font-weight: bold;
            text-align: center;
        }

        .status-dropped {
            background: #dc3545;
            color: #fff;
            font-weight: bold;
            text-align: center;
        }

        .no-data {
            padding: 15px;
            background: #fff3cd;
            border: 1px solid #ffecb5;
            border-radius: 8px;
            font-size: 16px;
            margin-top: 20px;
        }

        .top-btn {
            margin-top: 20px;
            display: inline-block;
        }
    </style>
</head>

<body>

<div class="header">
    <h2>My Enrollments</h2>
</div>

<div class="content-box">

<?php if (!$student_ref_id): ?>
    <div class="error">No linked student record found for your account.</div>
    <a href="index.php" class="btn top-btn">Back to Dashboard</a>

<?php else: ?>

    <h3 style="margin-bottom:10px;">Your Course Enrollments</h3>

    <?php
    $has_enrollments = false;  // FIXED VARIABLE

    $sql = "SELECT e.id, c.id AS course_id, c.course_code, c.course_name, f.name AS faculty, 
            cs.section_name, cs.instructor, e.status, e.enrolled_at,
            GROUP_CONCAT(CONCAT(ss.day_of_week,':',ss.start_time,'-',ss.end_time) 
                ORDER BY FIELD(ss.day_of_week, 'S','M','T','W','R','F') SEPARATOR ';') AS schedules
            FROM enrollments e 
            JOIN courses c ON e.course_id = c.id 
            LEFT JOIN faculties f ON c.faculty_id = f.id 
            LEFT JOIN course_sections cs ON e.section_id = cs.id
            LEFT JOIN section_schedules ss ON cs.id = ss.section_id
            WHERE e.student_id = ? 
            GROUP BY e.id
            ORDER BY e.enrolled_at DESC";

    $stmt = mysqli_prepare($db, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $student_ref_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $eid, $cid, $ccode, $cname, $faculty, $sname, $instructor, $status, $enrolled_at, $schedules);

        $rows = [];

        while (mysqli_stmt_fetch($stmt)) {
            $has_enrollments = true;

            // Format schedules string into human readable form
            $schedule_display = "N/A";
            if ($schedules) {
                $parts = explode(';', $schedules);
                $grouped = [];
                foreach ($parts as $p) {
                    if (trim($p) === '') continue;
                    list($day_char, $time) = explode(':', $p, 2);
                    $day_name = getDayName($day_char);
                    // Group by time so days with same time are joined
                    if (!isset($grouped[$time])) $grouped[$time] = [];
                    $grouped[$time][] = $day_name;
                }

                $segments = [];
                foreach ($grouped as $time => $days) {
                    $segments[] = implode(', ', $days) . ' ' . $time;
                }

                $schedule_display = implode(' | ', $segments);
            }

            $rows[] = [
                'code' => $ccode,
                'name' => $cname,
                'department' => $student_department,
                'section' => $sname,
                'instructor' => $instructor,
                'schedule' => $schedule_display,
                'status' => $status,
                'date' => $enrolled_at
            ];
        }
        mysqli_stmt_close($stmt);

        if ($has_enrollments): ?>
            
            <table>
                <tr>
                    <th>Course Code</th>
                    <th>Course Name</th>
                    <th>Section</th>
                    <th>Instructor</th>
                    <th>Schedule</th>
                    <th>Department</th>
                    <th>Status</th>
                    <th>Enrolled Date</th>
                </tr>

                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['code']) ?></td>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= $row['section'] ? htmlspecialchars($row['section']) : 'N/A' ?></td>
                        <td><?= $row['instructor'] ? htmlspecialchars($row['instructor']) : 'TBA' ?></td>
                        <td><?= htmlspecialchars($row['schedule']) ?></td>
                        <td><?= $row['department'] ? htmlspecialchars($row['department']) : 'N/A' ?></td>

                        <td class="<?= $row['status'] === 'enrolled' ? 'status-active' : 'status-dropped' ?>">
                            <?= ucfirst($row['status']) ?>
                        </td>

                        <td><?= $row['date'] ?></td>
                    </tr>
                <?php endforeach; ?>

            </table>

        <?php else: ?>

            <div class="no-data">
                You have no course enrollments yet.  
                <a href="courses_new.php" class="btn" style="margin-left:10px;">Browse Courses</a>
            </div>

        <?php endif;
    }
    ?>

    <a href="index.php" class="btn top-btn">Back to Dashboard</a>

<?php endif; ?>

</div>

</body>
</html>
?>