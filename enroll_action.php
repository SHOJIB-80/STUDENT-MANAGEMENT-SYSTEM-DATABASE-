<?php include('server.php');
session_start();

if (!isset($_SESSION['username'])) {
    header('location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('location: courses.php');
    exit();
}

$action = $_POST['action'] ?? '';
$course_id = intval($_POST['course_id'] ?? 0);
$username = $_SESSION['username'];

// find student_ref_id
$student_ref_id = null;
$stmt = mysqli_prepare($db, "SELECT student_ref_id FROM users WHERE username = ? LIMIT 1");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 's', $username);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $sref);
    if (mysqli_stmt_fetch($stmt)) { $student_ref_id = $sref; }
    mysqli_stmt_close($stmt);
}

if (!$student_ref_id) {
    $_SESSION['success'] = "No linked student record found. Contact admin.";
    header('location: courses.php');
    exit();
}

// fetch course info
$course = null;
$cs = mysqli_prepare($db, "SELECT id, max_students, enroll_start, enroll_end, (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = courses.id AND e.status='enrolled') AS enrolled_count FROM courses WHERE id = ? LIMIT 1");
if ($cs) {
    mysqli_stmt_bind_param($cs, 'i', $course_id);
    mysqli_stmt_execute($cs);
    mysqli_stmt_bind_result($cs, $cid, $max_students, $start, $end, $enrolled_count);
    if (mysqli_stmt_fetch($cs)) {
        $course = ['id'=>$cid,'max'=>$max_students,'start'=>$start,'end'=>$end,'enrolled_count'=>$enrolled_count];
    }
    mysqli_stmt_close($cs);
}

if (!$course) {
    $_SESSION['success'] = "Course not found.";
    header('location: courses.php');
    exit();
}

$now = date('Y-m-d H:i:s');
$within_window = (is_null($course['start']) || $course['start'] <= $now) && (is_null($course['end']) || $now <= $course['end']);

if ($action === 'enroll') {
    if (!$within_window) {
        $_SESSION['success'] = "Enrollment window closed.";
        header('location: courses.php'); exit();
    }
    if ($course['max'] > 0 && $course['enrolled_count'] >= $course['max']) {
        $_SESSION['success'] = "Course is full.";
        header('location: courses.php'); exit();
    }
    // insert or update enrollment
    $ins = mysqli_prepare($db, "INSERT INTO enrollments (student_id, course_id, status) VALUES (?, ?, 'enrolled') ON DUPLICATE KEY UPDATE status='enrolled', enrolled_at=CURRENT_TIMESTAMP");
    if ($ins) {
        mysqli_stmt_bind_param($ins, 'ii', $student_ref_id, $course['id']);
        mysqli_stmt_execute($ins);
        mysqli_stmt_close($ins);
        $_SESSION['success'] = "Successfully enrolled.";
    } else {
        $_SESSION['success'] = "Failed to enroll.";
    }
    header('location: courses.php'); exit();
}

if ($action === 'drop') {
    if (!$within_window) {
        $_SESSION['success'] = "Drop window closed.";
        header('location: courses.php'); exit();
    }
    $upd = mysqli_prepare($db, "UPDATE enrollments SET status='dropped' WHERE student_id = ? AND course_id = ? LIMIT 1");
    if ($upd) {
        mysqli_stmt_bind_param($upd, 'ii', $student_ref_id, $course['id']);
        mysqli_stmt_execute($upd);
        mysqli_stmt_close($upd);
        $_SESSION['success'] = "Successfully dropped.";
    } else {
        $_SESSION['success'] = "Failed to drop.";
    }
    header('location: courses.php'); exit();
}

header('location: courses.php');
exit();
