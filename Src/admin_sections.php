<?php
include('server.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('location: login.php');
    exit();
}

// Get course list for dropdown
$courses = [];
$stmt = mysqli_prepare($db, "SELECT id, course_code, course_name FROM courses ORDER BY course_code");
if ($stmt) {
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $cid, $ccode, $cname);
    while (mysqli_stmt_fetch($stmt)) {
        $courses[] = ['id' => $cid, 'code' => $ccode, 'name' => $cname];
    }
    mysqli_stmt_close($stmt);
}

// Get all sections with their schedules
$sections_data = [];
$stmt = mysqli_prepare($db, "
    SELECT cs.id, cs.course_id, c.course_code, c.course_name, cs.section_name, cs.instructor, cs.capacity,
           (SELECT COUNT(*) FROM enrollments WHERE section_id = cs.id AND status = 'enrolled') AS enrolled
    FROM course_sections cs
    JOIN courses c ON cs.course_id = c.id
    ORDER BY c.course_code, cs.section_name
");

if ($stmt) {
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $sec_id, $course_id, $ccode, $cname, $sec_name, $instructor, $capacity, $enrolled);
    
    while (mysqli_stmt_fetch($stmt)) {
        // Get schedules for this section
        $schedules = [];
        $sched_stmt = mysqli_prepare($db, "
            SELECT day_of_week, start_time, end_time
            FROM section_schedules
            WHERE section_id = ?
            ORDER BY FIELD(day_of_week, 'S', 'M', 'T', 'W', 'R', 'F')
        ");
        
        if ($sched_stmt) {
            mysqli_stmt_bind_param($sched_stmt, 'i', $sec_id);
            mysqli_stmt_execute($sched_stmt);
            mysqli_stmt_bind_result($sched_stmt, $day, $start, $end);
            
            while (mysqli_stmt_fetch($sched_stmt)) {
                $schedules[] = [
                    'day' => getDayName($day),
                    'start' => $start,
                    'end' => $end
                ];
            }
            mysqli_stmt_close($sched_stmt);
        }
        
        $sections_data[] = [
            'id' => $sec_id,
            'course_id' => $course_id,
            'course_code' => $ccode,
            'course_name' => $cname,
            'section_name' => $sec_name,
            'instructor' => $instructor,
            'capacity' => $capacity,
            'enrolled' => $enrolled,
            'schedules' => $schedules
        ];
    }
    mysqli_stmt_close($stmt);
}

$time_slots = getAvailableTimeSlots();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Course Sections & Schedules</title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <style>
        .admin-section {
            background: #f5f5f5;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
            border-left: 4px solid #007bff;
        }
        .admin-section h3 {
            margin-top: 0;
            color: #007bff;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .section-item {
            background: white;
            border: 1px solid #ddd;
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .section-header {
            font-weight: bold;
            font-size: 1.1em;
            color: #333;
        }
        .section-details {
            font-size: 0.9em;
            color: #666;
            margin: 8px 0;
        }
        .schedule-list {
            background: #f9f9f9;
            padding: 8px;
            border-radius: 3px;
            margin: 8px 0;
        }
        .schedule-item {
            background: #e8f4f8;
            padding: 6px 10px;
            margin: 4px 0;
            border-radius: 3px;
            font-size: 0.9em;
        }
        .btn-group {
            margin-top: 10px;
        }
        .btn-add-schedule {
            background: #17a2b8;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
        }
        .btn-add-schedule:hover {
            background: #138496;
        }
    </style>
</head>
<body>
<div class="header"><h2>Manage Course Sections & Schedules</h2></div>
<div class="content" style="width: 95%; max-width: 1200px; margin: 0 auto;">

<?php if (isset($_SESSION['success'])): ?>
    <div style="background:#d4edda; color:#155724; padding:10px; border-radius:5px; margin-bottom:15px;">
        <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['errors'])): ?>
    <div style="background:#f8d7da; color:#721c24; padding:10px; border-radius:5px; margin-bottom:15px;">
        <?php foreach ($_SESSION['errors'] as $err): ?>
            <div><?php echo htmlspecialchars($err); ?></div>
        <?php endforeach; unset($_SESSION['errors']); ?>
    </div>
<?php endif; ?>

<!-- CREATE NEW SECTION -->
<div class="admin-section">
    <h3>Create New Section</h3>
    <form method="post" action="server.php">
        <div class="form-row">
            <div class="form-group">
                <label for="course_id">Select Course:</label>
                <select name="course_id" id="course_id" required>
                    <option value="">-- Choose a course --</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo $course['id']; ?>">
                            <?php echo htmlspecialchars($course['code'] . ' - ' . $course['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="section_name">Section Name (e.g., A, B, C or 01, 02):</label>
                <input type="text" name="section_name" id="section_name" placeholder="e.g., A" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="instructor">Instructor:</label>
                <input type="text" name="instructor" id="instructor" placeholder="Instructor name">
            </div>
            <div class="form-group">
                <label for="capacity">Capacity:</label>
                <input type="number" name="capacity" id="capacity" value="30" min="1" required>
            </div>
        </div>
        <button type="submit" name="add_section" class="btn" style="background:#28a745;">Create Section</button>
    </form>
</div>

<!-- VIEW & MANAGE SECTIONS -->
<div class="admin-section">
    <h3>Existing Sections & Schedules</h3>
    
    <?php if (empty($sections_data)): ?>
        <p style="color:#999;">No sections created yet.</p>
    <?php else: ?>
        <?php foreach ($sections_data as $section): ?>
            <div class="section-item">
                <div class="section-header">
                    <?php echo htmlspecialchars($section['course_code']) . " - Section " . htmlspecialchars($section['section_name']); ?>
                </div>
                <div class="section-details">
                    <strong>Course:</strong> <?php echo htmlspecialchars($section['course_name']); ?><br>
                    <strong>Instructor:</strong> <?php echo htmlspecialchars($section['instructor'] ?: 'N/A'); ?><br>
                    <strong>Enrollment:</strong> <?php echo $section['enrolled']; ?> / <?php echo $section['capacity']; ?>
                </div>
                
                <?php if (!empty($section['schedules'])): ?>
                    <div class="schedule-list">
                        <strong>Schedule:</strong>
                        <?php foreach ($section['schedules'] as $sched): ?>
                            <div class="schedule-item">
                                <?php echo $sched['day'] . " " . $sched['start'] . " - " . $sched['end']; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="schedule-list">
                        <strong>Schedule:</strong> <em style="color:#999;">No schedule set</em>
                    </div>
                <?php endif; ?>
                
                <div class="btn-group">
                    <form method="post" action="server.php" style="display:inline;">
                        <input type="hidden" name="section_id" value="<?php echo $section['id']; ?>">
                        <input type="hidden" name="day_of_week" id="day_<?php echo $section['id']; ?>" value="M">
                        <input type="hidden" name="start_time" id="time_<?php echo $section['id']; ?>" value="08:00">
                        
                        <!-- Inline form for quick schedule addition -->
                        <select id="day_<?php echo $section['id']; ?>" name="day_of_week" style="width:auto; padding:6px;">
                            <option value="S">Sunday</option>
                            <option value="M" selected>Monday</option>
                            <option value="T">Tuesday</option>
                            <option value="W">Wednesday</option>
                            <option value="R">Thursday</option>
                            <option value="F">Friday</option>
                        </select>
                        
                        <select id="time_<?php echo $section['id']; ?>" name="start_time" style="width:auto; padding:6px;">
                            <?php foreach ($time_slots as $slot): ?>
                                <option value="<?php echo $slot['start_time']; ?>">
                                    <?php echo $slot['start_time'] . " (" . $slot['slot_number'] . ")"; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <button type="submit" name="add_schedule" class="btn-add-schedule">Add Schedule</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- TIME SLOTS REFERENCE -->
<div class="admin-section">
    <h3>Available Time Slots Reference</h3>
    <p><small>Each slot is 1 hour 30 minutes with a 10-minute break between courses.</small></p>
    <table style="width:100%; border-collapse:collapse;">
        <tr style="background:#e8f4f8;">
            <th style="border:1px solid #ddd; padding:8px;">Slot #</th>
            <th style="border:1px solid #ddd; padding:8px;">Start Time</th>
            <th style="border:1px solid #ddd; padding:8px;">End Time</th>
        </tr>
        <?php foreach ($time_slots as $slot): ?>
            <tr>
                <td style="border:1px solid #ddd; padding:8px; text-align:center;"><?php echo $slot['slot_number']; ?></td>
                <td style="border:1px solid #ddd; padding:8px;"><?php echo $slot['start_time']; ?></td>
                <td style="border:1px solid #ddd; padding:8px;"><?php echo $slot['end_time']; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

<hr>
<p><a href="admin_courses.php">Back to Courses</a> | <a href="index.php">Back to Dashboard</a></p>
</div>
</body>
</html>
