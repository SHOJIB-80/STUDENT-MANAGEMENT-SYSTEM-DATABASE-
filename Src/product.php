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

    <div class="page-content table-box">

        <?php
        session_start();

        $db = mysqli_connect('localhost', 'root', '', 'student_management_system');
        if (!$db) {
            die("Connection failed");
        }

        $sql = "SELECT id, student_id, student_name, email, phone, course FROM students";
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
                            <th>Course</th>
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
            <a href="index.php" class="btn btn-back">← Back to Dashboard</a>
        </div>

    </div>
</div>

</body>
</html>
