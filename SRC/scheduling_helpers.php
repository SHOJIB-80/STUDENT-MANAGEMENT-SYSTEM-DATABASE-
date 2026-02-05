<?php
/**
 * scheduling_helpers.php
 * Helper functions for course scheduling and conflict detection
 */

// Time slot configuration
define('COURSE_DURATION', 90);  // 90 minutes (1.5 hours)
define('BREAK_DURATION', 10);   // 10 minute break
define('START_HOUR', 8);        // 8 AM
define('END_HOUR', 20);         // 8 PM
define('SLOT_DURATION', COURSE_DURATION + BREAK_DURATION); // Total 100 minutes

/**
 * Get all available time slots for a given day
 * Returns array of [start_time => "08:00", end_time => "09:30", slot_number => 1]
 */
function getAvailableTimeSlots() {
    $slots = [];
    $current_hour = START_HOUR;
    $current_minute = 0;
    $slot_number = 1;

    while ($current_hour < END_HOUR) {
        $start_time = sprintf("%02d:%02d", $current_hour, $current_minute);
        
        // Add 90 minutes for course
        $end_minute = $current_minute + COURSE_DURATION;
        $end_hour = $current_hour;
        
        if ($end_minute >= 60) {
            $end_hour += intval($end_minute / 60);
            $end_minute = $end_minute % 60;
        }
        
        // Check if end time exceeds END_HOUR
        if ($end_hour > END_HOUR) {
            break;
        }
        
        $end_time = sprintf("%02d:%02d", $end_hour, $end_minute);
        
        $slots[] = [
            'slot_number' => $slot_number,
            'start_time' => $start_time,
            'end_time' => $end_time
        ];
        
        // Move to next slot (add 100 minutes: 90 course + 10 break)
        $current_minute += SLOT_DURATION;
        if ($current_minute >= 60) {
            $current_hour += intval($current_minute / 60);
            $current_minute = $current_minute % 60;
        }
        
        $slot_number++;
    }

    return $slots;
}

/**
 * Get the display name for a day character
 * S=Sunday, M=Monday, T=Tuesday, W=Wednesday, R=Thursday, F=Friday
 */
function getDayName($day_char) {
    $days = [
        'S' => 'Sunday',
        'M' => 'Monday',
        'T' => 'Tuesday',
        'W' => 'Wednesday',
        'R' => 'Thursday',
        'F' => 'Friday'
    ];
    return $days[$day_char] ?? 'Unknown';
}

/**
 * Check if two time slots overlap
 * Returns true if there's a conflict
 */
function checkTimeOverlap($start1, $end1, $start2, $end2) {
    // Convert time strings to minutes since midnight for comparison
    $start1_mins = strtotime($start1) % 86400 / 60;
    $end1_mins = strtotime($end1) % 86400 / 60;
    $start2_mins = strtotime($start2) % 86400 / 60;
    $end2_mins = strtotime($end2) % 86400 / 60;
    
    // Check if there's an overlap
    return !($end1_mins <= $start2_mins || $end2_mins <= $start1_mins);
}

/**
 * Check for schedule conflicts for a student
 * Returns array with conflict details if conflict exists, null if no conflict
 */
function checkScheduleConflicts($db, $student_id, $proposed_section_id) {
    // Get the proposed section's schedule
    $proposed_schedule = [];
    $stmt = mysqli_prepare($db, "
        SELECT ss.day_of_week, ss.start_time, ss.end_time, cs.course_id, c.course_name, c.course_code
        FROM section_schedules ss
        JOIN course_sections cs ON ss.section_id = cs.id
        JOIN courses c ON cs.course_id = c.id
        WHERE ss.section_id = ?
    ");
    
    if (!$stmt) return null;
    
    mysqli_stmt_bind_param($stmt, 'i', $proposed_section_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $day, $start, $end, $course_id, $course_name, $course_code);
    
    while (mysqli_stmt_fetch($stmt)) {
        $proposed_schedule[] = [
            'day' => $day,
            'start_time' => $start,
            'end_time' => $end,
            'course_id' => $course_id,
            'course_name' => $course_name,
            'course_code' => $course_code
        ];
    }
    mysqli_stmt_close($stmt);
    
    if (empty($proposed_schedule)) {
        return null;
    }
    
    // Get student's current enrolled sections and their schedules in one query
    $stmt = mysqli_prepare($db, "
        SELECT DISTINCT cs.id, c.course_name, c.course_code, ss.day_of_week, ss.start_time, ss.end_time
        FROM enrollments e
        JOIN course_sections cs ON e.section_id = cs.id
        JOIN courses c ON cs.course_id = c.id
        JOIN section_schedules ss ON ss.section_id = cs.id
        WHERE e.student_id = ? AND e.status = 'enrolled'
    ");
    
    if (!$stmt) return null;
    
    mysqli_stmt_bind_param($stmt, 'i', $student_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $section_id, $course_name, $course_code, $existing_day, $existing_start, $existing_end);
    
    $conflicting_courses = [];
    
    while (mysqli_stmt_fetch($stmt)) {
        // Check each proposed schedule slot against existing enrollments
        foreach ($proposed_schedule as $proposed) {
            // Check if same day and time overlap
            if ($existing_day === $proposed['day']) {
                if (checkTimeOverlap($existing_start, $existing_end, $proposed['start_time'], $proposed['end_time'])) {
                    // Check if we already added this conflict
                    $already_added = false;
                    foreach ($conflicting_courses as $conflict) {
                        if ($conflict['course_code'] === $course_code) {
                            $already_added = true;
                            break;
                        }
                    }
                    
                    if (!$already_added) {
                        $conflicting_courses[] = [
                            'course_code' => $course_code,
                            'course_name' => $course_name,
                            'day' => getDayName($existing_day),
                            'time' => $existing_start . ' - ' . $existing_end
                        ];
                    }
                }
            }
        }
    }
    mysqli_stmt_close($stmt);
    
    return empty($conflicting_courses) ? null : $conflicting_courses;
}

/**
 * Get all sections for a course with their schedule and enrollment info
 */
function getCourseSectionsWithSchedule($db, $course_id, $student_id = null) {
    $sections = [];
    
    // Get all sections for this course with enrollment data
    $sql = "
        SELECT 
            cs.id,
            cs.section_name,
            cs.instructor,
            cs.capacity,
            (SELECT COUNT(*) FROM enrollments e WHERE e.section_id = cs.id AND e.status = 'enrolled') AS actual_enrollment,
            (SELECT IF(COUNT(*) > 0, 'enrolled', NULL) FROM enrollments e WHERE e.section_id = cs.id AND e.student_id = ? AND e.status = 'enrolled') AS my_status
        FROM course_sections cs
        WHERE cs.course_id = ?
        ORDER BY cs.section_name
    ";
    
    $stmt = mysqli_prepare($db, $sql);
    if (!$stmt) return [];
    
    mysqli_stmt_bind_param($stmt, 'ii', $student_id, $course_id);
    mysqli_stmt_execute($stmt);
    
    // Store the result to avoid "Commands out of sync" error
    mysqli_stmt_store_result($stmt);
    mysqli_stmt_bind_result($stmt, $section_id, $section_name, $instructor, $capacity, $actual_enrollment, $my_status);
    
    while (mysqli_stmt_fetch($stmt)) {
        // Get schedule for this section using regular query (not prepared statement)
        $schedule_sql = "
            SELECT day_of_week, start_time, end_time
            FROM section_schedules
            WHERE section_id = " . intval($section_id) . "
            ORDER BY FIELD(day_of_week, 'S', 'M', 'T', 'W', 'R', 'F')
        ";
        
        $schedule = [];
        $schedule_result = mysqli_query($db, $schedule_sql);
        
        if ($schedule_result) {
            while ($row = mysqli_fetch_assoc($schedule_result)) {
                $schedule[] = [
                    'day' => getDayName($row['day_of_week']),
                    'day_char' => $row['day_of_week'],
                    'start_time' => $row['start_time'],
                    'end_time' => $row['end_time']
                ];
            }
            mysqli_free_result($schedule_result);
        }
        
        $sections[] = [
            'id' => $section_id,
            'name' => $section_name,
            'instructor' => $instructor,
            'capacity' => $capacity,
            'enrollment' => $actual_enrollment,
            'is_full' => ($actual_enrollment >= $capacity),
            'my_status' => $my_status,
            'schedule' => $schedule
        ];
    }
    
    mysqli_stmt_close($stmt);
    
    return $sections;
}

?>
