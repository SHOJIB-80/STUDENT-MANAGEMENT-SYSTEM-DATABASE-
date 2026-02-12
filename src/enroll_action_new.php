<?php 
include('server.php');

// Must be logged in
if (!isset($_SESSION['username'])) {
    header('location: login.php');
    exit();
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('location: courses_new.php');
    exit();
}

$action = $_POST['action'] ?? '';
$section_id = intval($_POST['section_id'] ?? 0);
$username = $_SESSION['username'];

// Get student_ref_id
$student_ref_id = null;
$stmt = mysqli_prepare($db, "
    SELECT student_ref_id 
    FROM users 
    WHERE username = ? 
    LIMIT 1
");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 's', $username);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $sref);

    if (mysqli_stmt_fetch($stmt)) {
        $student_ref_id = $sref;
    }
    mysqli_stmt_close($stmt);
}

if (!$student_ref_id) {
    $_SESSION['errors'][] = "No linked student record found. Contact admin.";
    header('location: courses_new.php');
    exit();
}

// Verify section exists and get course info
$section_data = null;
$stmt = mysqli_prepare($db, "
    SELECT cs.id, cs.course_id, cs.capacity,
           (SELECT COUNT(*) FROM enrollments WHERE section_id = cs.id AND status = 'enrolled') AS enrolled_count
    FROM course_sections cs
    WHERE cs.id = ? LIMIT 1
");

if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'i', $section_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $sec_id, $course_id, $capacity, $enrolled_count);

    if (mysqli_stmt_fetch($stmt)) {
        $section_data = [
            'id' => $sec_id,
            'course_id' => $course_id,
            'capacity' => $capacity,
            'enrolled' => $enrolled_count
        ];
    }
    mysqli_stmt_close($stmt);
}

if (!$section_data) {
    $_SESSION['errors'][] = "Section not found.";
    header('location: courses_new.php');
    exit();
}

// ===== ENROLL ACTION =====
if ($action === 'enroll') {
    // Check if section is full
    if ($section_data['capacity'] > 0 && $section_data['enrolled'] >= $section_data['capacity']) {
        $_SESSION['errors'][] = "This section is full.";
        header('location: courses_new.php');
        exit();
    }

    // Check for schedule conflicts
    $conflicts = checkScheduleConflicts($db, $student_ref_id, $section_id);
    
    if ($conflicts) {
        $_SESSION['errors'][] = "You already have a course at this time:";
        foreach ($conflicts as $conflict) {
            $_SESSION['errors'][] = "  • " . $conflict['course_code'] . " - " . $conflict['course_name'] . 
                                   " (" . $conflict['day'] . " " . $conflict['time'] . ")";
        }
        header('location: courses_new.php');
        exit();
    }

    // Check if already enrolled in this section (any status)
    $check_stmt = mysqli_prepare($db, "
        SELECT id, status FROM enrollments 
        WHERE student_id = ? AND section_id = ?
        LIMIT 1
    ");
    
    $existing_id = null;
    $existing_status = null;
    
    if ($check_stmt) {
        mysqli_stmt_bind_param($check_stmt, 'ii', $student_ref_id, $section_id);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_bind_result($check_stmt, $existing_id, $existing_status);
        mysqli_stmt_fetch($check_stmt);
        mysqli_stmt_close($check_stmt);
    }
    
    if ($existing_id) {
        if ($existing_status === 'enrolled') {
            $_SESSION['errors'][] = "You are already enrolled in this section.";
            header('location: courses_new.php');
            exit();
        } elseif ($existing_status === 'dropped') {
            // Re-enroll: update the existing record from 'dropped' to 'enrolled'
            $update_stmt = mysqli_prepare($db, "
                UPDATE enrollments 
                SET status = 'enrolled', enrolled_at = CURRENT_TIMESTAMP
                WHERE id = ?
                LIMIT 1
            ");
            
            if ($update_stmt) {
                mysqli_stmt_bind_param($update_stmt, 'i', $existing_id);
                
                if (mysqli_stmt_execute($update_stmt)) {
                    $_SESSION['success'] = "Successfully re-enrolled in the section!";
                } else {
                    $_SESSION['errors'][] = "Failed to re-enroll. Please try again.";
                }
                mysqli_stmt_close($update_stmt);
            } else {
                $_SESSION['errors'][] = "Database error occurred.";
            }
            
            header('location: courses_new.php');
            exit();
        }
    }

    // Enroll student (new enrollment)
    $ins = mysqli_prepare($db, "
        INSERT INTO enrollments (student_id, section_id, course_id, status, enrolled_at) 
        VALUES (?, ?, ?, 'enrolled', CURRENT_TIMESTAMP)
    ");

    if ($ins) {
        mysqli_stmt_bind_param($ins, 'iii', $student_ref_id, $section_id, $section_data['course_id']);
        
        if (mysqli_stmt_execute($ins)) {
            $_SESSION['success'] = "Successfully enrolled in the section!";
        } else {
            // Check if it's a duplicate entry error
            if (strpos(mysqli_error($db), 'Duplicate') !== false) {
                $_SESSION['errors'][] = "You are already enrolled in this section.";
            } else {
                $_SESSION['errors'][] = "Failed to enroll. Please try again.";
            }
        }
        mysqli_stmt_close($ins);
    } else {
        $_SESSION['errors'][] = "Database error occurred.";
    }

    header('location: courses_new.php');
    exit();
}

// ===== DROP ACTION =====
if ($action === 'drop') {
    $upd = mysqli_prepare($db, "
        UPDATE enrollments 
        SET status = 'dropped' 
        WHERE student_id = ? AND section_id = ? LIMIT 1
    ");

    if ($upd) {
        mysqli_stmt_bind_param($upd, 'ii', $student_ref_id, $section_id);
        
        if (mysqli_stmt_execute($upd)) {
            if (mysqli_stmt_affected_rows($upd) > 0) {
                $_SESSION['success'] = "Successfully dropped the section.";
            } else {
                $_SESSION['errors'][] = "You are not enrolled in this section.";
            }
        } else {
            $_SESSION['errors'][] = "Failed to drop. Please try again.";
        }
        mysqli_stmt_close($upd);
    } else {
        $_SESSION['errors'][] = "Database error occurred.";
    }

    header('location: courses_new.php');
    exit();
}

// Default redirect
header('location: courses_new.php');
exit();
?>
