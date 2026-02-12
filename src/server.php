<?php
session_start();

$errors = [];

// ================= DATABASE =================
$db = mysqli_connect('localhost', 'root', '', 'student_management_system');
if (!$db) {
    die("Database connection failed");
}

// ================= ENSURE SCHEMA =================
// Safely check and add student_ref_id column if it doesn't exist
$col_check = mysqli_query($db, "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='users' AND COLUMN_NAME='student_ref_id'");
if ($col_check && mysqli_num_rows($col_check) === 0) {
    mysqli_query($db, "ALTER TABLE users ADD COLUMN student_ref_id INT NULL DEFAULT NULL AFTER role");
}

// ================= INCLUDE HELPERS =================
require_once('scheduling_helpers.php');

// ================= ADD COURSE (ADMIN) =================
if (isset($_POST['add_course'])) {

    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        $errors[] = "Unauthorized access";
    } else {
        $code    = trim($_POST['course_code']);
        $name    = trim($_POST['course_name']);
        $credits = (int)$_POST['credits'];
        $max     = (int)$_POST['max_students'];

        if ($code === '' || $name === '') {
            $errors[] = "All fields are required";
        }

        if (empty($errors)) {
            $stmt = mysqli_prepare(
                $db,
                "INSERT INTO courses (course_code, course_name, credits, max_students)
                 VALUES (?, ?, ?, ?)"
            );
            mysqli_stmt_bind_param($stmt, "ssii", $code, $name, $credits, $max);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $_SESSION['success'] = "Course added successfully";
        }
    }

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
    }

    header("Location: admin_courses.php");
    exit();
}

// ================= REGISTER USER =================
if (isset($_POST['reg_user'])) {
    // Accept student_name and student_id; use student_id as the account username
    $student_name = trim($_POST['student_name'] ?? '');
    $student_id_input = trim($_POST['student_id'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $pass1    = $_POST['password_1'] ?? '';
    $pass2    = $_POST['password_2'] ?? '';

    // Use student_id as username for login
    $username = $student_id_input;

    // Validation
    if ($student_name === '') $errors[] = "Full name required";
    if ($student_id_input === '') $errors[] = "Student ID required";
    if ($email === '') $errors[] = "Email required";
    if ($pass1 === '') $errors[] = "Password required";
    if ($pass1 !== $pass2) $errors[] = "Passwords do not match";

    if (empty($errors)) {
        // Check if username or email already exists
        $check_dup = mysqli_prepare($db, "SELECT id FROM users WHERE username=? OR email=?");
        mysqli_stmt_bind_param($check_dup, 'ss', $username, $email);
        mysqli_stmt_execute($check_dup);
        mysqli_stmt_store_result($check_dup);

        if (mysqli_stmt_num_rows($check_dup) > 0) {
            $errors[] = "Student ID or email already registered";
        }
        mysqli_stmt_close($check_dup);
    }

    if (empty($errors)) {
        $hash = password_hash($pass1, PASSWORD_DEFAULT);

        // ===== ALWAYS ENSURE A STUDENT RECORD EXISTS =====
        $linked_student_id = null;
        
        // First try to find existing student by student_id
        $find_st = mysqli_prepare($db, "SELECT id FROM students WHERE student_id = ? LIMIT 1");
        if ($find_st) {
            mysqli_stmt_bind_param($find_st, 's', $student_id_input);
            mysqli_stmt_execute($find_st);
            mysqli_stmt_bind_result($find_st, $found_id);
            if (mysqli_stmt_fetch($find_st)) {
                $linked_student_id = $found_id;
            }
            mysqli_stmt_close($find_st);
        }

        // If not found by student_id, create a new student record
        if (!$linked_student_id) {
            $create_st = mysqli_prepare($db, "INSERT INTO students (student_name, student_id, email) VALUES (?, ?, ?)");
            if ($create_st) {
                mysqli_stmt_bind_param($create_st, 'sss', $student_name, $student_id_input, $email);
                if (mysqli_stmt_execute($create_st)) {
                    $linked_student_id = mysqli_insert_id($db);
                }
                mysqli_stmt_close($create_st);
            }
        }

        // ===== CREATE USER ACCOUNT WITH STUDENT REFERENCE =====
        if ($linked_student_id > 0) {
            $create_user = mysqli_prepare($db, 
                "INSERT INTO users (username, email, password, role, student_ref_id) 
                 VALUES (?, ?, ?, 'student', ?)"
            );
            if ($create_user) {
                mysqli_stmt_bind_param($create_user, 'sssi', $username, $email, $hash, $linked_student_id);
                mysqli_stmt_execute($create_user);
                mysqli_stmt_close($create_user);
                
                // Auto-login the user
                $_SESSION['username'] = $username;
                $_SESSION['role'] = 'student';
                $_SESSION['success'] = "Registration successful";
                header("Location: index.php");
                exit();
            } else {
                $errors[] = "Failed to create user account";
            }
        } else {
            $errors[] = "Failed to create student record";
        }
    }

    $_SESSION['errors'] = $errors;
}

// ================= LOGIN USER =================
if (isset($_POST['login_user'])) {

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if ($username === '' || $password === '') {
        $errors[] = "All fields required";
    }

    if (empty($errors)) {
        $stmt = mysqli_prepare($db,
            "SELECT password, role FROM users WHERE username=? LIMIT 1"
        );
        mysqli_stmt_bind_param($stmt, 's', $username);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $hash, $role);

        if (mysqli_stmt_fetch($stmt) && password_verify($password, $hash)) {
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $role;
            $_SESSION['success'] = "Logged in successfully";
            header("Location: index.php");
            exit();
        } else {
            $errors[] = "Wrong username or password";
        }
        mysqli_stmt_close($stmt);
    }

    $_SESSION['errors'] = $errors;
}

// ================= ADD STUDENT (ADMIN) =================
if (isset($_POST['add_student'])) {

    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        $errors[] = "Unauthorized access";
    } else {
        $student_name = trim($_POST['student_name']);
        $student_id = trim($_POST['student_id']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $course = trim($_POST['course']);

        if ($student_name === '' || $student_id === '' || $email === '') {
            $errors[] = "Name, Student ID, and Email are required";
        }

        // Check if student already exists
        if (empty($errors)) {
            $stmt = mysqli_prepare($db, "SELECT id FROM students WHERE student_id=?");
            mysqli_stmt_bind_param($stmt, 's', $student_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);

            if (mysqli_stmt_num_rows($stmt) > 0) {
                $errors[] = "Student ID already exists";
            }
            mysqli_stmt_close($stmt);
        }

        if (empty($errors)) {
            $stmt = mysqli_prepare(
                $db,
                "INSERT INTO students (student_name, student_id, email, phone, course)
                 VALUES (?, ?, ?, ?, ?)"
            );
            mysqli_stmt_bind_param($stmt, "sssss", $student_name, $student_id, $email, $phone, $course);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $_SESSION['success'] = "Student added successfully";
        }
    }

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
    }

    header("Location: add_student.php");
    exit();
}

// ================= EDIT STUDENT (ADMIN) =================
if (isset($_POST['edit_student'])) {

    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        $errors[] = "Unauthorized access";
    } else {
        $student_id = trim($_POST['student_id']);
        $student_name = trim($_POST['student_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $course = trim($_POST['course']);

        if ($student_id === '') {
            $errors[] = "Student ID is required";
        }

        if (empty($errors)) {
            // Build update query based on what fields were provided
            $updates = [];
            $params = [];
            $types = '';

            if ($student_name !== '') {
                $updates[] = "student_name = ?";
                $params[] = $student_name;
                $types .= 's';
            }
            if ($email !== '') {
                $updates[] = "email = ?";
                $params[] = $email;
                $types .= 's';
            }
            if ($phone !== '') {
                $updates[] = "phone = ?";
                $params[] = $phone;
                $types .= 's';
            }
            if ($course !== '') {
                $updates[] = "course = ?";
                $params[] = $course;
                $types .= 's';
            }

            if (!empty($updates)) {
                $params[] = $student_id;
                $types .= 's';

                $sql = "UPDATE students SET " . implode(", ", $updates) . " WHERE student_id = ? LIMIT 1";
                $stmt = mysqli_prepare($db, $sql);

                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, $types, ...$params);
                    mysqli_stmt_execute($stmt);

                    if (mysqli_stmt_affected_rows($stmt) > 0) {
                        $_SESSION['success'] = "Student updated successfully";
                    } else {
                        $errors[] = "Student not found or no changes made";
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $errors[] = "Database error: " . mysqli_error($db);
                }
            } else {
                $errors[] = "Please provide at least one field to update";
            }
        }
    }

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
    } else {
        $_SESSION['success'] = "Student updated successfully";
    }

    header("Location: edit_student.php");
    exit();
}

// ================= DELETE STUDENT (ADMIN) =================
if (isset($_POST['delete_student'])) {

    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        $errors[] = "Permission denied";
    } else {
        $id = trim($_POST['student_id']);

        $stmt = mysqli_prepare($db,
            "DELETE FROM students WHERE student_id=? LIMIT 1"
        );
        mysqli_stmt_bind_param($stmt, 's', $id);
        mysqli_stmt_execute($stmt);

        if (mysqli_stmt_affected_rows($stmt) > 0) {
            $_SESSION['success'] = "Student deleted";
        } else {
            $errors[] = "Student not found";
        }
        mysqli_stmt_close($stmt);
    }

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
    }

    header("Location: index.php");
    exit();
}

// ================= DELETE COURSE (ADMIN) =================
if (isset($_POST['delete_course'])) {

    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        $errors[] = "Unauthorized access";
    } else {
        $course_code = trim($_POST['course_code']);

        if ($course_code === '') {
            $errors[] = "Course code is required";
        }

        if (empty($errors)) {
            $stmt = mysqli_prepare($db,
                "DELETE FROM courses WHERE course_code=? LIMIT 1"
            );
            mysqli_stmt_bind_param($stmt, 's', $course_code);
            mysqli_stmt_execute($stmt);

            if (mysqli_stmt_affected_rows($stmt) > 0) {
                $_SESSION['success'] = "Course deleted successfully";
            } else {
                $errors[] = "Course not found";
            }
            mysqli_stmt_close($stmt);
        }
    }

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
    }

    header("Location: admin_courses.php");
    exit();
}

// ================= ASSIGN GRADE (ADMIN) =================
if (isset($_POST['assign_grade'])) {

    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        $errors[] = "Unauthorized access";
    } else {
        $enrollment_id = intval($_POST['enrollment_id']);
        $grade = trim($_POST['grade']);

        if ($enrollment_id <= 0) {
            $errors[] = "Invalid enrollment ID";
        }

        if (empty($errors)) {
            $stmt = mysqli_prepare($db,
                "UPDATE enrollments SET grade=? WHERE id=? LIMIT 1"
            );
            mysqli_stmt_bind_param($stmt, 'si', $grade, $enrollment_id);
            mysqli_stmt_execute($stmt);

            if (mysqli_stmt_affected_rows($stmt) > 0) {
                $_SESSION['success'] = "Grade assigned successfully";
            } else {
                $errors[] = "Enrollment not found";
            }
            mysqli_stmt_close($stmt);
        }
    }

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
    }

    header("Location: admin_enrollments.php");
    exit();
}
// ================= ADD COURSE SECTION (ADMIN) =================
if (isset($_POST['add_section'])) {

    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        $errors[] = "Unauthorized access";
    } else {
        $course_id = intval($_POST['course_id']);
        $section_name = trim($_POST['section_name']);
        $instructor = trim($_POST['instructor']);
        $capacity = intval($_POST['capacity']);

        if ($course_id <= 0) {
            $errors[] = "Invalid course ID";
        }
        if ($section_name === '') {
            $errors[] = "Section name is required";
        }
        if ($capacity <= 0) {
            $errors[] = "Capacity must be greater than 0";
        }

        if (empty($errors)) {
            $stmt = mysqli_prepare($db, 
                "INSERT INTO course_sections (course_id, section_name, instructor, capacity)
                 VALUES (?, ?, ?, ?)"
            );
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'issi', $course_id, $section_name, $instructor, $capacity);
                mysqli_stmt_execute($stmt);
                
                if (mysqli_stmt_affected_rows($stmt) > 0) {
                    $_SESSION['success'] = "Section created successfully";
                } else {
                    $errors[] = "Failed to create section";
                }
                mysqli_stmt_close($stmt);
            } else {
                $errors[] = "Database error";
            }
        }
    }

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
    }

    header("Location: admin_courses.php");
    exit();
}

// ================= ADD SECTION SCHEDULE (ADMIN) =================
if (isset($_POST['add_schedule'])) {

    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        $errors[] = "Unauthorized access";
    } else {
        $section_id = intval($_POST['section_id']);
        $day_of_week = trim($_POST['day_of_week']);
        $start_time = trim($_POST['start_time']);

        if ($section_id <= 0) {
            $errors[] = "Invalid section ID";
        }
        if (!in_array($day_of_week, ['S', 'M', 'T', 'W', 'R', 'F'])) {
            $errors[] = "Invalid day of week";
        }
        if ($start_time === '') {
            $errors[] = "Start time is required";
        }

        if (empty($errors)) {
            // Calculate end time (1.5 hours after start)
            $start_dt = DateTime::createFromFormat('H:i', $start_time);
            if (!$start_dt) {
                $errors[] = "Invalid time format";
            } else {
                $end_dt = clone $start_dt;
                $end_dt->add(new DateInterval('PT90M'));
                $end_time = $end_dt->format('H:i');

                $stmt = mysqli_prepare($db,
                    "INSERT INTO section_schedules (section_id, day_of_week, start_time, end_time)
                     VALUES (?, ?, ?, ?)"
                );
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, 'isss', $section_id, $day_of_week, $start_time, $end_time);
                    mysqli_stmt_execute($stmt);
                    
                    if (mysqli_stmt_affected_rows($stmt) > 0) {
                        $_SESSION['success'] = "Schedule added successfully";
                    } else {
                        $errors[] = "Failed to add schedule";
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $errors[] = "Database error";
                }
            }
        }
    }

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
    }

    header("Location: admin_courses.php");
    exit();
}

?>