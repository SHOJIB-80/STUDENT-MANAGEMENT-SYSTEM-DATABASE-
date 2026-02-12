<?php
include('server.php');
include('scheduling_helpers.php');

$student_id = 7;

// Test conflict detection for each course
$test_sections = [
    20 => 'MATH101-A',
    21 => 'MATH102-A', 
    22 => 'ENG101-A (already enrolled)',
    23 => 'ENG101-B',
    24 => 'BIO101-A (new)'
];

echo "<h2>Conflict Detection Test for Student $student_id</h2>";
echo "<pre>";

foreach ($test_sections as $section_id => $name) {
    $conflicts = checkScheduleConflicts($db, $student_id, $section_id);
    
    echo "\nSection $section_id ($name):\n";
    
    if ($conflicts === null) {
        echo "  ✓ NO CONFLICTS - Can enroll!\n";
    } else {
        echo "  ✗ CONFLICTS FOUND:\n";
        foreach ($conflicts as $conflict) {
            echo "    - " . $conflict['course_code'] . ": " . $conflict['day'] . " " . $conflict['time'] . "\n";
        }
    }
}

echo "\n\nCurrent enrollments:\n";
$result = mysqli_query($db, "
    SELECT cs.section_name, c.course_name 
    FROM enrollments e 
    JOIN course_sections cs ON e.section_id = cs.id 
    JOIN courses c ON cs.course_id = c.id 
    WHERE e.student_id = $student_id AND e.status = 'enrolled'
");

while ($row = mysqli_fetch_assoc($result)) {
    echo "  - " . $row['course_name'] . " (Section " . $row['section_name'] . ")\n";
}

echo "</pre>";
?>
