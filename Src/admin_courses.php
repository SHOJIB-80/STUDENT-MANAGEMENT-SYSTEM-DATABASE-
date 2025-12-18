<?php
include('server.php');

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Courses</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>

<div class="header">
    <h2>Manage Courses</h2>
</div>

<div class="page-content">

    <!-- ================= ADD COURSE ================= -->
    <h3>Add New Course</h3>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <?php if (isset($_SESSION['errors'])): ?>
        <div class="error">
            <?php foreach ($_SESSION['errors'] as $e) echo $e . "<br>"; ?>
        </div>
        <?php unset($_SESSION['errors']); ?>
    <?php endif; ?>

    <form method="post" action="server.php">
        <div class="input-group">
            <label>Course Code</label>
            <input type="text" name="course_code" required>
        </div>

        <div class="input-group">
            <label>Course Name</label>
            <input type="text" name="course_name" required>
        </div>

        <div class="input-group">
            <label>Credits</label>
            <input type="number" name="credits" min="0" required>
        </div>

        <div class="input-group">
            <label>Max Students (0 = Unlimited)</label>
            <input type="number" name="max_students" value="0" min="0">
        </div>

        <button type="submit" name="add_course" class="btn">
            Add Course
        </button>
    </form>

    <hr style="margin:30px 0;">

    <!-- ================= COURSE LIST ================= -->
    <h3>Available Courses</h3>

    <table class="styled-table">
        <tr>
            <th>Code</th>
            <th>Name</th>
            <th>Credits</th>
            <th>Max Students</th>
        </tr>

        <?php
        $result = mysqli_query($db, "SELECT * FROM courses ORDER BY course_code ASC");

        if (mysqli_num_rows($result) > 0):
            while ($row = mysqli_fetch_assoc($result)):
        ?>
        <tr>
            <td><?= htmlspecialchars($row['course_code']) ?></td>
            <td><?= htmlspecialchars($row['course_name']) ?></td>
            <td><?= (int)$row['credits'] ?></td>
            <td><?= $row['max_students'] == 0 ? 'Unlimited' : (int)$row['max_students'] ?></td>
        </tr>
        <?php endwhile; else: ?>
        <tr>
            <td colspan="4">No courses found.</td>
        </tr>
        <?php endif; ?>
    </table>

    <div class="back-wrapper">
        <a href="index.php" class="btn btn-back">Back to Dashboard</a>
    </div>

</div>

</body>
</html>
