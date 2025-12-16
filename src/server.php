<?php
session_start();

// initializing variables
$username = "";
$email    = "";
$errors = array(); 

// Database configuration - update these if your local MySQL has a password or different user
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'student_management_system';

// connect to the database with error handling
$db = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$db) {
    die("Database connection failed: " . mysqli_connect_error());
}

// REGISTER USER
if (isset($_POST['reg_user'])) {
    // receive all input values from the form (trim inputs)
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password_1 = $_POST['password_1'];
    $password_2 = $_POST['password_2'];

    // validation
    if (empty($username)) { $errors[] = "Username is required"; }
    if (empty($email)) { $errors[] = "Email is required"; }
    if (empty($password_1)) { $errors[] = "Password is required"; }
    if ($password_1 != $password_2) { $errors[] = "The two passwords do not match"; }

    // check existing user (prepared statement)
    $stmt = mysqli_prepare($db, "SELECT username, email FROM users WHERE username = ? OR email = ? LIMIT 1");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'ss', $username, $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $existing_username, $existing_email);
        if (mysqli_stmt_fetch($stmt)) {
            if ($existing_username === $username) { $errors[] = "Username already exists"; }
            if ($existing_email === $email) { $errors[] = "Email already exists"; }
        }
        mysqli_stmt_close($stmt);
    }

    // register user if no errors
    if (count($errors) == 0) {
        // secure password hashing
        $password_hashed = password_hash($password_1, PASSWORD_DEFAULT);

        $stmt = mysqli_prepare($db, "INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'sss', $username, $email, $password_hashed);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                // Ensure there is a students record linked to this user.
                // We use the username as the student's `student_id` when creating the student record.
                $student_ref_id = null;
                $check = mysqli_prepare($db, "SELECT id FROM students WHERE student_id = ? LIMIT 1");
                if ($check) {
                    mysqli_stmt_bind_param($check, 's', $username);
                    mysqli_stmt_execute($check);
                    mysqli_stmt_bind_result($check, $existing_student_id);
                    if (mysqli_stmt_fetch($check)) {
                        $student_ref_id = $existing_student_id;
                    }
                    mysqli_stmt_close($check);
                }

                if (!$student_ref_id) {
                    $ins = mysqli_prepare($db, "INSERT INTO students (student_id, student_name, email) VALUES (?, ?, ?)");
                    $student_name = $username;
                    if ($ins) {
                        mysqli_stmt_bind_param($ins, 'sss', $username, $student_name, $email);
                        mysqli_stmt_execute($ins);
                        $student_ref_id = mysqli_insert_id($db);
                        mysqli_stmt_close($ins);
                    }
                }

                // Link the users table to the students row (if available)
                if ($student_ref_id) {
                    $upd = mysqli_prepare($db, "UPDATE users SET student_ref_id = ? WHERE username = ? LIMIT 1");
                    if ($upd) {
                        mysqli_stmt_bind_param($upd, 'is', $student_ref_id, $username);
                        mysqli_stmt_execute($upd);
                        mysqli_stmt_close($upd);
                    }
                }

                $_SESSION['username'] = $username;
                $_SESSION['success'] = "You are now logged in";
                header('location: index.php');
                exit();
        } else {
            $errors[] = "Registration failed: could not prepare statement";
        }
    }
}

// LOGIN USER
if (isset($_POST['login_user'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username)) { $errors[] = "Username is required"; }
    if (empty($password)) { $errors[] = "Password is required"; }

    if (count($errors) == 0) {
        // fetch hashed password and role for the user
        $stmt = mysqli_prepare($db, "SELECT password, role FROM users WHERE username = ? LIMIT 1");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $username);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $hashed_password, $role);
            if (mysqli_stmt_fetch($stmt)) {
                if (password_verify($password, $hashed_password)) {
                    $_SESSION['username'] = $username;
                    $_SESSION['role'] = $role;
                    $_SESSION['success'] = "You are now logged in";
                    mysqli_stmt_close($stmt);
                    header('location: index.php');
                    exit();
                } else {
                    $errors[] = "Wrong username/password combination";
                }
            } else {
                $errors[] = "Wrong username/password combination";
            }
            mysqli_stmt_close($stmt);
        } else {
            $errors[] = "Login failed: could not prepare statement";
        }
    }
}

// EDIT STUDENT (admin)
if (isset($_POST['edit_student'])) {
    // Only allow admins
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        $errors[] = "Permission denied.";
    } else {
        $student_id = trim($_POST['student_id']);
        $student_name = trim($_POST['student_name']);
        $email_s = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $course = trim($_POST['course']);

        if (empty($student_id)) {
            $errors[] = "Student ID is required to edit.";
        }

        if (count($errors) == 0) {
            // build dynamic update
            $fields = array();
            $types = '';
            $params = array();

            if ($student_name !== '') { $fields[] = 'student_name = ?'; $types .= 's'; $params[] = $student_name; }
            if ($email_s !== '') { $fields[] = 'email = ?'; $types .= 's'; $params[] = $email_s; }
            if ($phone !== '') { $fields[] = 'phone = ?'; $types .= 's'; $params[] = $phone; }
            if ($course !== '') { $fields[] = 'course = ?'; $types .= 's'; $params[] = $course; }

            if (empty($fields)) {
                $errors[] = 'No fields provided to update.';
            } else {
                $sql = "UPDATE students SET " . implode(', ', $fields) . " WHERE student_id = ? LIMIT 1";
                $types_final = $types . 's';
                $params_final = array_merge($params, array($student_id));

                $stmt = mysqli_prepare($db, $sql);
                if ($stmt) {
                    // bind params dynamically
                    $bind_names[] = $types_final;
                    for ($i = 0; $i < count($params_final); $i++) {
                        $bind_name = 'bind' . $i;
                        $$bind_name = $params_final[$i];
                        $bind_names[] = &$$bind_name;
                    }
                    call_user_func_array('mysqli_stmt_bind_param', array_merge(array($stmt), $bind_names));
                    if (mysqli_stmt_execute($stmt)) {
                        $_SESSION['success'] = 'Student updated successfully.';
                    } else {
                        $errors[] = 'Failed to update student: ' . mysqli_stmt_error($stmt);
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $errors[] = 'Failed to prepare update statement: ' . mysqli_error($db);
                }
            }
        }
    }
}

// ASSIGN GRADE (admin)
if (isset($_POST['assign_grade'])) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        $errors[] = "Permission denied.";
    } else {
        $enrollment_id = intval($_POST['enrollment_id'] ?? 0);
        $grade = trim($_POST['grade'] ?? '');

        if ($enrollment_id <= 0) {
            $errors[] = 'Invalid enrollment.';
        } else {
            $stmt = mysqli_prepare($db, "UPDATE enrollments SET grade = ? WHERE id = ? LIMIT 1");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'si', $grade, $enrollment_id);
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['success'] = 'Grade assigned/updated successfully.';
                } else {
                    $errors[] = 'Failed to assign grade: ' . mysqli_stmt_error($stmt);
                }
                mysqli_stmt_close($stmt);
            } else {
                $errors[] = 'Failed to prepare grade update: ' . mysqli_error($db);
            }
        }
    }


    // persist errors to session (so admin_enrollments.php can show them after redirect)
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
    } else {
        unset($_SESSION['errors']);
    }

    header('Location: admin_enrollments.php');
    exit();

}
// DELETE STUDENT (admin)
if (isset($_POST['delete_student'])) {

    // Only allow admins
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        $errors[] = "Permission denied.";
    } else {
        $student_id = trim($_POST['student_id']);

        if (empty($student_id)) {
            $errors[] = "Student ID is required.";
        }

        if (count($errors) == 0) {
            // Prepare delete query
            $stmt = mysqli_prepare($db, "DELETE FROM students WHERE student_id = ? LIMIT 1");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 's', $student_id);
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['success'] = "Student deleted successfully.";
                    mysqli_stmt_close($stmt);
                    header("Location: index.php");
                    exit();
                } else {
                    $errors[] = "Failed to delete student: " . mysqli_stmt_error($stmt);
                }
                mysqli_stmt_close($stmt);
            } else {
                $errors[] = "Delete failed: " . mysqli_error($db);
            }
        }
    }

    // Keep errors if any
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
    }

}

?>