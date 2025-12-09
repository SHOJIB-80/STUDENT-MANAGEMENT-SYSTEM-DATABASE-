<?php include('server.php') ?>
<!DOCTYPE html>
<html>
<head>
  <title>Add Student - Student Management System</title>
  <link rel="stylesheet" type="text/css" href="style.css">
  <link rel="stylesheet" href="style.css">
  
</head>
<body>
  <div class="header">
  	<h2>Add New Student</h2>
  </div>
	 
  <form method="post" action="add_student.php">
  	<?php include('errors.php'); ?>
  	<div class="input-group">
  		<label>Student Name</label>
  		<input type="text" name="student_name" required>
  	</div>
  	<div class="input-group">
  		<label>Student ID</label>
  		<input type="text" name="student_id" required>
  	</div>
  	<div class="input-group">
  		<label>Email</label>
  		<input type="email" name="email" required>
  	</div>
  	<div class="input-group">
  		<label>Phone</label>
  		<input type="text" name="phone">
  	</div>
  	<div class="input-group">
  		<label>Course</label>
  		<input type="text" name="course">
  	</div>
  	<div class="input-group">
  		<button type="submit" class="btn" name="add_student">Add Student</button>
  	</div>
  	<p>
  		<a href="index.php" class="btn">Back to Dashboard</a>
  	</p>
  </form>
</body>
</html>
