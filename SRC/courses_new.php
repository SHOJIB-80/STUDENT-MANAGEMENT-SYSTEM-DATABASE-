<?php 
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors', '0');

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
    <title>Courses with Sections</title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <style>
        .course-card {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
            background: #f9f9f9;
        }
        .section-card {
            background: #fff;
            border-left: 4px solid #007bff;
            padding: 12px;
            margin: 10px 0;
            border-radius: 3px;
        }
        .schedule-item {
            font-size: 0.9em;
            color: #555;
            margin: 5px 0;
            display: inline-block;
            background: #e8f4f8;
            padding: 4px 8px;
            border-radius: 3px;
            margin-right: 5px;
        }
        .conflict-warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .conflict-item {
            margin: 5px 0;
            font-size: 0.9em;
        }
        .btn-small {
            padding: 6px 12px;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
<div class="header"><h2>Available Courses & Sections</h2></div>
<div class="content" style="width: 95%; max-width: 1400px; margin: 0 auto;">

<?php if (!$student_ref_id): ?>
    <div class="error">No linked student record found for your account. Please register using your Student ID as username, or ask admin to link your account.</div>
    <p><a href="index.php">Back to Dashboard</a></p>
<?php else: ?>
    <?php if (isset($_SESSION['success'])): ?>
        <div style="background:#d4edda; color:#155724; padding:10px; border-radius:5px; margin-bottom:15px;">
            <?php echo htmlspecialchars($_SESSION['success']); ?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['errors'])): ?>
        <div style="background:#f8d7da; color:#721c24; padding:10px; border-radius:5px; margin-bottom:15px;">
            <?php foreach ($_SESSION['errors'] as $err): ?>
                <div><?php echo htmlspecialchars($err); ?></div>
            <?php endforeach; ?>
        </div>
        <?php unset($_SESSION['errors']); ?>
    <?php endif; ?>

    <?php
    $sql = "SELECT DISTINCT c.id, c.course_code, c.course_name, c.credits, f.name AS faculty
            FROM courses c
            LEFT JOIN faculties f ON c.faculty_id = f.id
            ORDER BY c.course_code";
    
    // Use regular query instead of prepared statement for outer loop to avoid "Commands out of sync" error
    $result = mysqli_query($db, $sql);

    if ($result) {
        echo "<div class='course-list'>";

        while ($row = mysqli_fetch_assoc($result)) {
            $course_id = $row['id'];
            $course_code = $row['course_code'];
            $course_name = $row['course_name'];
            $credits = $row['credits'];
            $faculty = isset($row['name']) ? $row['name'] : 'N/A';
            echo "<div class='course-card'>";
            echo "<h3>" . htmlspecialchars($course_name) . "</h3>";
            echo "<div class='course-meta'>";
            echo "<strong>Code:</strong> " . htmlspecialchars($course_code) . " | ";
            echo "<strong>Credits:</strong> " . htmlspecialchars($credits) . " | ";
            echo "<strong>Faculty:</strong> " . ($faculty ?: 'N/A');
            echo "</div>";

            // Get sections for this course
            $sections = getCourseSectionsWithSchedule($db, $course_id, $student_ref_id);

            if (empty($sections)) {
                echo "<p style='color:#999;'>No sections available for this course.</p>";
            } else {
                echo "<div class='sections-container'>";
                
                foreach ($sections as $section) {
                    echo "<div class='section-card'>";
                    echo "<strong>Section: " . htmlspecialchars($section['name']) . "</strong>";
                    
                    if ($section['instructor']) {
                        echo " | <small>Instructor: " . htmlspecialchars($section['instructor']) . "</small>";
                    }
                    
                    echo "<br>";
                    
                    // Show schedule
                    if (!empty($section['schedule'])) {
                        echo "<div class='schedule-info'>";
                        foreach ($section['schedule'] as $slot) {
                            echo "<span class='schedule-item'>";
                            echo $slot['day'] . " " . $slot['start_time'] . "-" . $slot['end_time'];
                            echo "</span>";
                        }
                        echo "</div>";
                    }
                    
                    // Show enrollment status
                    echo "<div style='margin-top:8px;'>";
                    echo "<strong>Enrollment:</strong> " . $section['enrollment'] . " / " . $section['capacity'];
                    
                    if ($section['is_full']) {
                        echo " <span style='color:red;'>(Full)</span>";
                    }
                    echo "</div>";

                    // Action buttons
                    echo "<div class='course-actions' style='margin-top:10px;'>";
                    
                    if ($section['my_status'] === 'enrolled') {
                        echo "<span class='badge badge-green'>✓ Enrolled</span> ";
                        echo "<form method='post' action='enroll_action_new.php' style='display:inline;'>";
                        echo "<input type='hidden' name='action' value='drop'>";
                        echo "<input type='hidden' name='section_id' value='" . $section['id'] . "'>";
                        echo "<button type='submit' class='btn btn-small' style='background:#dc3545;'>Drop</button>";
                        echo "</form>";
                    } else {
                        if ($section['is_full']) {
                            echo "<span class='badge badge-red'>Full</span>";
                        } else {
                            // Check for schedule conflicts
                            $conflicts = checkScheduleConflicts($db, $student_ref_id, $section['id']);
                            
                            if ($conflicts) {
                                echo "<div class='conflict-warning'>";
                                echo "<strong>⚠ Schedule Conflict:</strong>";
                                echo "<br>You already have course(s) at this time:";
                                echo "<ul>";
                                foreach ($conflicts as $conflict) {
                                    echo "<li class='conflict-item'>";
                                    echo htmlspecialchars($conflict['course_code']) . " - " . 
                                         htmlspecialchars($conflict['course_name']) . "<br>";
                                    echo "<small>" . $conflict['day'] . " " . $conflict['time'] . "</small>";
                                    echo "</li>";
                                }
                                echo "</ul>";
                                echo "</div>";
                            } else {
                                echo "<form method='post' action='enroll_action_new.php' style='display:inline;'>";
                                echo "<input type='hidden' name='action' value='enroll'>";
                                echo "<input type='hidden' name='section_id' value='" . $section['id'] . "'>";
                                echo "<button type='submit' class='btn btn-small' style='background:#28a745;'>Enroll</button>";
                                echo "</form>";
                            }
                        }
                    }
                    
                    echo "</div>";
                    echo "</div>";
                }
                
                echo "</div>";
            }

            echo "</div>";
        }

        echo "</div>";
        mysqli_free_result($result);
    } else {
        echo "Error fetching courses";
    }
    ?>

<?php endif; ?>

<hr>
<p><a href="my_enrollments.php">View My Enrollments</a> | <a href="index.php">Back to Dashboard</a></p>
</div>
</body>
</html>
