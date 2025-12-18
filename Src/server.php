<?php
session_start();

$errors = [];

// ================= DATABASE =================
$db = mysqli_connect('localhost', 'root', '', 'student_management_system');
if (!$db) {
    die("Database connection failed");
}

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

    header("Location: manage_courses.php");
    exit();
}

// ================= REGISTER USER =================
if (isset($_POST['reg_user'])) {

    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $pass1    = $_POST['password_1'];
    $pass2    = $_POST['password_2'];

    if ($username === '') $errors[] = "Username required";
    if ($email === '') $errors[] = "Email required";
    if ($pass1 === '') $errors[] = "Password required";
    if ($pass1 !== $pass2) $errors[] = "Passwords do not match";

    if (empty($errors)) {
        $stmt = mysqli_prepare($db, "SELECT id FROM users WHERE username=? OR email=?");
        mysqli_stmt_bind_param($stmt, 'ss', $username, $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors[] = "Username or email already exists";
        }
        mysqli_stmt_close($stmt);
    }

    if (empty($errors)) {
        $hash = password_hash($pass1, PASSWORD_DEFAULT);

        $stmt = mysqli_prepare($db,
            "INSERT INTO users (username, email, password, role)
             VALUES (?, ?, ?, 'student')"
        );
        mysqli_stmt_bind_param($stmt, 'sss', $username, $email, $hash);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $_SESSION['username'] = $username;
        $_SESSION['role'] = 'student';
        $_SESSION['success'] = "Registration successful";

        header("Location: index.php");
        exit();
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

// ================= DELETE STUDENT (ADMIN) =================
if (isset($_POST['delete_student'])) {

    if ($_SESSION['role'] !== 'admin') {
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
