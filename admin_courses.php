<?php 
include('server.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['username']) || (isset($_SESSION['role']) && $_SESSION['role'] !== 'admin')) {
    header('location: index.php');
    exit();
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Courses - Admin</title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <style>
        body {
            margin: 0;
            padding: 0;
        }
        .content {
            width: 100%;
            padding: 0;
            margin: 0;
        }
        .courses-wrapper {
            width: 100%;
            overflow-x: auto;
            background: #fff;
            border-radius: 0;
            box-shadow: none;
            padding: 40px;
            margin: 0;
            box-sizing: border-box;
        }
        .courses-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        .courses-table th {
            background: #007bff;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            white-space: nowrap;
        }
        .courses-table td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            vertical-align: middle;
        }
        .courses-table tr:hover {
            background: #f9f9f9;
        }
        .courses-table tr:last-child td {
            border-bottom: none;
        }
    </style>
</head>
<body>
<div class="header"><h2>Manage Courses (Admin)</h2></div>
<div class="courses-wrapper">
    <h3>Available Courses</h3>
    <?php
    $sql = "SELECT c.id, c.course_code, c.course_name, f.name AS faculty, c.max_students, c.enroll_start, c.enroll_end FROM courses c LEFT JOIN faculties f ON c.faculty_id = f.id";
    $result = $db->query($sql);
    if ($result->num_rows > 0) {
        print "<table class='courses-table'>";
        print "<tr><th style='width:15%;'>Code</th><th style='width:25%;'>Name</th><th style='width:15%;'>Faculty</th><th style='width:12%;'>Max Students</th><th style='width:15%;'>Enroll Start</th><th style='width:15%;'>Enroll End</th></tr>";
        while($row = $result->fetch_assoc()) {
            print "<tr>";
            print "<td>" . htmlspecialchars($row['course_code']) . "</td>";
            print "<td>" . htmlspecialchars($row['course_name']) . "</td>";
            print "<td>" . ($row['faculty'] ?: 'N/A') . "</td>";
            print "<td>" . ($row['max_students'] == 0 ? 'Unlimited' : $row['max_students']) . "</td>";
            print "<td>" . ($row['enroll_start'] ?: 'Open') . "</td>";
            print "<td>" . ($row['enroll_end'] ?: 'No end') . "</td>";
            print "</tr>";
        }
        print "</table>";
    } else {
        print "No courses found.";
    }
    ?>
    <p style="margin-top:20px;"><a href="index.php" class="btn">Back to Dashboard</a></p>
</div>
</body>
</html>