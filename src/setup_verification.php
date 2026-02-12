<?php
// Quick setup verification page
// This helps you check if everything is set up correctly
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Admin access required");
}

// Check if files exist
$files_to_check = [
    'scheduling_helpers.php',
    'courses_new.php',
    'enroll_action_new.php',
    'admin_sections.php',
    'migration_scheduling.sql'
];

$db = mysqli_connect('localhost', 'root', '', 'student_management_system');
$db_status = [];

if ($db) {
    // Check if tables exist
    $tables = ['course_sections', 'section_schedules'];
    foreach ($tables as $table) {
        $result = mysqli_query($db, "SHOW TABLES LIKE '$table'");
        $db_status[$table] = (mysqli_num_rows($result) > 0) ? '✓' : '✗';
    }
    
    // Check if enrollments has section_id column
    $result = mysqli_query($db, "SHOW COLUMNS FROM enrollments LIKE 'section_id'");
    $db_status['enrollments.section_id'] = (mysqli_num_rows($result) > 0) ? '✓' : '✗';
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Scheduling System Setup Verification</title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <style>
        .setup-item { 
            padding: 12px; 
            margin: 8px 0; 
            border-radius: 4px; 
            border-left: 4px solid #28a745;
            background: #d4edda;
        }
        .setup-item.missing {
            border-left-color: #dc3545;
            background: #f8d7da;
        }
        .status-ok { color: #28a745; font-weight: bold; }
        .status-missing { color: #dc3545; font-weight: bold; }
    </style>
</head>
<body>
<div class="header"><h2>Scheduling System Setup Verification</h2></div>
<div class="content" style="width: 95%; max-width: 900px; margin: 0 auto;">

<h3>Files Status</h3>
<?php foreach ($files_to_check as $file): ?>
    <?php $exists = file_exists($file); ?>
    <div class="setup-item <?php echo $exists ? '' : 'missing'; ?>">
        <span class="<?php echo $exists ? 'status-ok' : 'status-missing'; ?>">
            <?php echo $exists ? '✓' : '✗'; ?>
        </span>
        <?php echo htmlspecialchars($file); ?>
    </div>
<?php endforeach; ?>

<h3 style="margin-top:30px;">Database Status</h3>
<?php if ($db): ?>
    <?php foreach ($db_status as $item => $status): ?>
        <?php $ok = ($status === '✓'); ?>
        <div class="setup-item <?php echo $ok ? '' : 'missing'; ?>">
            <span class="<?php echo $ok ? 'status-ok' : 'status-missing'; ?>">
                <?php echo $status; ?>
            </span>
            <?php echo htmlspecialchars($item); ?>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="setup-item missing">
        <span class="status-missing">✗ Database Connection Failed</span>
    </div>
<?php endif; ?>

<h3 style="margin-top:30px;">Quick Links</h3>
<ul>
    <li><a href="admin_sections.php">→ Manage Sections & Schedules</a></li>
    <li><a href="courses_new.php">→ Browse Courses (Student View)</a></li>
    <li><a href="SCHEDULING_SYSTEM_GUIDE.md">→ Setup Guide & Documentation</a></li>
    <li><a href="admin_courses.php">→ Back to Course Management</a></li>
</ul>

<h3 style="margin-top:30px;">Next Steps</h3>
<ol>
    <li>Run the migration SQL: Copy `migration_scheduling.sql` content into phpMyAdmin</li>
    <li>Go to <a href="admin_sections.php">Manage Sections</a> to create course sections</li>
    <li>Add schedules to each section</li>
    <li>Test enrollment with conflict detection on <a href="courses_new.php">Browse Courses</a></li>
</ol>

</div>
</body>
</html>
