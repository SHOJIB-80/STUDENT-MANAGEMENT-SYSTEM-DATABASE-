<?php include('server.php') ?>
<!DOCTYPE html>
<html>
<head>
  <title>Delete Student - Student Management System</title>
  <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
  <div class="header">
  	<h2>Delete Student</h2>
  </div>
	 
  <form method="post" action="delete_student.php">
  	<?php include('errors.php'); ?>
  	<div class="input-group">
  		<label>Enter Student ID to Delete</label>
  		<input type="text" name="student_id" required>
  	</div>
  	<div class="input-group">
  		<button type="submit" class="btn" name="delete_student" style="background-color: #dc3545;">Delete Student</button>
  	</div>
  	<p>
  		<a href="index.php" class="btn">Back to Dashboard</a>
  	</p>
  </form>
</body>
</html>
