<!DOCTYPE html>
<html>
<head>
    <title>Students List</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="page-wrapper">

    <div class="page-header">
        Student Records
    </div>

    <div class="content table-box">
        <form method="GET" class="search-box">
            <select name="field">
                <option value="all" <?php if(isset($_GET['field']) && $_GET['field']=='all') echo 'selected'; ?>>All</option>
                <option value="student_id" <?php if(isset($_GET['field']) && $_GET['field']=='student_id') echo 'selected'; ?>>Student ID</option>
                <option value="student_name" <?php if(isset($_GET['field']) && $_GET['field']=='student_name') echo 'selected'; ?>>Name</option>
                <option value="email" <?php if(isset($_GET['field']) && $_GET['field']=='email') echo 'selected'; ?>>Email</option>
                <option value="phone" <?php if(isset($_GET['field']) && $_GET['field']=='phone') echo 'selected'; ?>>Phone</option>
                <option value="course" <?php if(isset($_GET['field']) && $_GET['field']=='course') echo 'selected'; ?>>Department</option>
            </select>

            <input type="text" name="q" placeholder="Search students..."
                   value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">

            <button type="submit" class="btn">Search</button>
        </form>

        <?php
        session_start();
        $db = mysqli_connect('localhost', 'root', '', 'student_management_system');
        if (!$db) {
            die("Connection failed: " . mysqli_connect_error());
        }

        $search = isset($_GET['q']) ? trim($_GET['q']) : '';
        $field = isset($_GET['field']) ? $_GET['field'] : 'all';

        $sql = "SELECT id, student_id, student_name, email, phone, course FROM students";

        if ($search !== '') {
            $search_safe = mysqli_real_escape_string($db, $search);
            if ($field == 'all') {
                $sql .= " WHERE (
                    student_id LIKE '%$search_safe%' OR
                    student_name LIKE '%$search_safe%' OR
                    email LIKE '%$search_safe%' OR
                    phone LIKE '%$search_safe%' OR
                    course LIKE '%$search_safe%'
                )";
            } else {
                $allowed_fields = ['student_id','student_name','email','phone','course'];
                if (in_array($field, $allowed_fields)) {
                    $sql .= " WHERE $field LIKE '%$search_safe%'";
                }
            }
        }

        $result = mysqli_query($db, $sql);

        if (mysqli_num_rows($result) > 0) {
            echo "<table class='styled-table'>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Department</th>
                        </tr>
                    </thead>
                    <tbody>";

            while ($row = mysqli_fetch_assoc($result)) {
                echo "<tr>
                        <td>{$row['id']}</td>
                        <td>{$row['student_id']}</td>
                        <td>{$row['student_name']}</td>
                        <td>{$row['email']}</td>
                        <td>{$row['phone']}</td>
                        <td>{$row['course']}</td>
                      </tr>";
            }

            echo "</tbody></table>";
        } else {
            echo "<div class='no-data'>No student records found.</div>";
        }

        mysqli_close($db);
        ?>

        <div class="back-wrapper">
            <a href="index.php" class="btn-back">← Back to Dashboard</a>
        </div>

    </div>
</div>

</body>
</html>
