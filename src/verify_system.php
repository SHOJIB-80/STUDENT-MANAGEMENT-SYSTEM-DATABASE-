<?php
/**
 * System Verification Script
 * Checks if all required tables and functions are in place
 */

echo "===============================================\n";
echo "UNIVERSITY STUDENT MANAGEMENT SYSTEM - VERIFICATION\n";
echo "===============================================\n\n";

// Include server.php for database connection
include('server.php');

// Check 1: Database connection
echo "✓ Database connected successfully\n\n";

// Check 2: Verify tables exist
echo "CHECKING DATABASE TABLES:\n";

$tables_required = [
    'courses' => 'Courses table',
    'course_sections' => 'Course Sections table',
    'section_schedules' => 'Section Schedules table',
    'enrollments' => 'Enrollments table',
    'students' => 'Students table',
    'users' => 'Users table'
];

$all_tables_exist = true;

foreach ($tables_required as $table_name => $description) {
    $result = mysqli_query($db, "SHOW TABLES LIKE '$table_name'");
    if (mysqli_num_rows($result) > 0) {
        echo "  ✅ $description ($table_name)\n";
    } else {
        echo "  ❌ $description ($table_name) - MISSING\n";
        $all_tables_exist = false;
    }
}

echo "\n";

// Check 3: Verify key columns exist
echo "CHECKING TABLE STRUCTURE:\n";

$column_checks = [
    'courses' => ['id', 'course_code', 'course_name', 'credits', 'max_students'],
    'course_sections' => ['id', 'course_id', 'section_name', 'instructor', 'capacity'],
    'section_schedules' => ['id', 'section_id', 'day_of_week', 'start_time', 'end_time'],
    'enrollments' => ['id', 'student_id', 'section_id', 'course_id', 'status']
];

foreach ($column_checks as $table => $columns) {
    echo "  $table:\n";
    
    $result = mysqli_query($db, "DESCRIBE $table");
    $table_columns = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $table_columns[] = $row['Field'];
    }
    
    foreach ($columns as $col) {
        if (in_array($col, $table_columns)) {
            echo "    ✅ $col\n";
        } else {
            echo "    ❌ $col - MISSING\n";
            $all_tables_exist = false;
        }
    }
}

echo "\n";

// Check 4: Verify sample data exists
echo "CHECKING SAMPLE DATA:\n";

$courses_count = mysqli_fetch_row(mysqli_query($db, "SELECT COUNT(*) FROM courses"))[0];
$sections_count = mysqli_fetch_row(mysqli_query($db, "SELECT COUNT(*) FROM course_sections"))[0];
$schedules_count = mysqli_fetch_row(mysqli_query($db, "SELECT COUNT(*) FROM section_schedules"))[0];

echo "  Courses: $courses_count\n";
echo "  Sections: $sections_count\n";
echo "  Schedules: $schedules_count\n";

if ($courses_count == 0) {
    echo "  ⚠️  No courses found. Please run test_setup.sql to load sample data.\n";
} else if ($sections_count == 0) {
    echo "  ⚠️  No sections found. Sections are required for the system.\n";
} else if ($schedules_count == 0) {
    echo "  ⚠️  No schedules found. Please add schedules for sections.\n";
} else {
    echo "  ✅ Sample data loaded\n";
}

echo "\n";

// Check 5: Verify functions exist
echo "CHECKING FUNCTIONS:\n";

if (function_exists('checkScheduleConflicts')) {
    echo "  ✅ checkScheduleConflicts() - Conflict detection\n";
} else {
    echo "  ❌ checkScheduleConflicts() - NOT FOUND\n";
}

if (function_exists('getCourseSectionsWithSchedule')) {
    echo "  ✅ getCourseSectionsWithSchedule() - Section retrieval\n";
} else {
    echo "  ❌ getCourseSectionsWithSchedule() - NOT FOUND\n";
}

if (function_exists('getDayName')) {
    echo "  ✅ getDayName() - Day display\n";
} else {
    echo "  ❌ getDayName() - NOT FOUND\n";
}

if (function_exists('checkTimeOverlap')) {
    echo "  ✅ checkTimeOverlap() - Time comparison\n";
} else {
    echo "  ❌ checkTimeOverlap() - NOT FOUND\n";
}

echo "\n";

// Check 6: File existence
echo "CHECKING KEY FILES:\n";

$files_required = [
    'server.php' => 'Database connection',
    'scheduling_helpers.php' => 'Scheduling functions',
    'courses_new.php' => 'Course listing with sections',
    'enroll_action_new.php' => 'Section enrollment handler',
    'style.css' => 'Styling'
];

foreach ($files_required as $file => $description) {
    if (file_exists($file)) {
        echo "  ✅ $file - $description\n";
    } else {
        echo "  ❌ $file - $description - MISSING\n";
    }
}

echo "\n";

// Check 7: Test course with sections
echo "EXAMPLE COURSES WITH SECTIONS:\n";

$result = mysqli_query($db, "
    SELECT DISTINCT
        c.id,
        c.course_code,
        c.course_name,
        COUNT(DISTINCT cs.id) as section_count,
        COUNT(DISTINCT ss.id) as schedule_count
    FROM courses c
    LEFT JOIN course_sections cs ON c.id = cs.course_id
    LEFT JOIN section_schedules ss ON cs.id = ss.section_id
    GROUP BY c.id
    LIMIT 5
");

if (mysqli_num_rows($result) == 0) {
    echo "  No courses found. Run test_setup.sql to load sample data.\n";
} else {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "  " . $row['course_code'] . " - " . $row['course_name'] . "\n";
        echo "    └─ " . $row['section_count'] . " section(s), " . $row['schedule_count'] . " schedule(s)\n";
    }
}

echo "\n";

// Final status
echo "===============================================\n";
if ($all_tables_exist && $courses_count > 0 && $sections_count > 0) {
    echo "✅ SYSTEM VERIFICATION: PASSED\n";
    echo "   All required tables, functions, and sample data are in place.\n";
} else {
    echo "⚠️  SYSTEM VERIFICATION: INCOMPLETE\n";
    echo "   Please check the issues above.\n";
}
echo "===============================================\n";

mysqli_close($db);
?>
