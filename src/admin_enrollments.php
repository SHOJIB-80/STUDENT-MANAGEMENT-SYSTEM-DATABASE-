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
    <title>View Enrollments - Admin</title>
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
        .enrollments-wrapper {
            width: 100%;
            overflow-x: auto;
            background: #fff;
            border-radius: 0;
            box-shadow: none;
            padding: 40px;
            margin: 0;
            box-sizing: border-box;
        }
        .enrollments-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        .enrollments-table th {
            background: #007bff;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            white-space: nowrap;
        }
        .enrollments-table td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            vertical-align: middle;
        }
        .enrollments-table tr:hover {
            background: #f9f9f9;
        }
        .enrollments-table tr:last-child td {
            border-bottom: none;
        }
        .status-enrolled {
            background: #28a745;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            text-align: center;
            display: inline-block;
        }
        .status-dropped {
            background: #dc3545;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            text-align: center;
            display: inline-block;
        }
        .grade-form {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }
        .grade-form input {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
            min-width: 80px;
        }
        .grade-form button {
            padding: 8px 16px;
            background: #17a2b8;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: background 0.3s;
        }
        .grade-form button:hover {
            background: #138496;
        }
    </style>
</head>
<body>
<div class="header"><h2>View Enrollments (Admin)</h2></div>
<div class="enrollments-wrapper">
    <h3>All Student Enrollments</h3>
    <?php
    $sql = "SELECT e.id, e.grade, s.student_id, s.student_name, c.course_code, c.course_name, f.name AS faculty, e.status, e.enrolled_at FROM enrollments e 
            JOIN students s ON e.student_id = s.id 
            JOIN courses c ON e.course_id = c.id 
            LEFT JOIN faculties f ON c.faculty_id = f.id 
            ORDER BY e.enrolled_at DESC";
    $result = $db->query($sql);
    if ($result && $result->num_rows > 0) {
        print "<table class='enrollments-table'>";
        print "<tr><th style='width:12%;'>Student ID</th><th style='width:15%;'>Student Name</th><th style='width:20%;'>Course</th><th style='width:12%;'>Department</th><th style='width:10%;'>Status</th><th style='width:8%;'>Grade</th><th style='width:15%;'>Enrolled Date</th><th style='width:18%;'>Assign Grade</th></tr>";
        while($row = $result->fetch_assoc()) {
            print "<tr>";
            print "<td>" . htmlspecialchars($row['student_id']) . "</td>";
            print "<td>" . htmlspecialchars($row['student_name']) . "</td>";
            print "<td>" . htmlspecialchars($row['course_code']) . "<br><small>" . htmlspecialchars($row['course_name']) . "</small></td>";
            print "<td>" . ($row['faculty'] ?: 'N/A') . "</td>";
            $status_class = ($row['status'] === 'enrolled') ? 'status-enrolled' : 'status-dropped';
            print "<td><span class='$status_class'>" . ucfirst($row['status']) . "</span></td>";
            print "<td><strong>" . ($row['grade'] !== null ? htmlspecialchars($row['grade']) : '—') . "</strong></td>";
            print "<td>" . substr($row['enrolled_at'], 0, 10) . "</td>";
            // actions: form posts back to this page to process
            print "<td>";
            print "<form method='post' action='admin_enrollments.php' class='grade-form'>";
            print "<input type='hidden' name='assign_grade' value='1'>";
            print "<input type='hidden' name='enrollment_id' value='" . intval($row['id']) . "'>";
            print "<input type='text' name='grade' value='" . htmlspecialchars($row['grade']) . "' placeholder='e.g. A or 85'>";
            print "<button type='submit'>Set</button>";
            print "</form>";
            print "</td>";
            print "</tr>";
        }
        print "</table>";
    } else {
        print "No enrollments found.";
    }
    ?>
    <p style="margin-top:20px;"><a href="index.php" class="btn">Back to Dashboard</a></p>
</div>
</body>
</html>