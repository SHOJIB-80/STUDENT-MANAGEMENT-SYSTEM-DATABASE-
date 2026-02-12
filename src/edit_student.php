<?php include('server.php');
// restrict to admin users
if (!isset($_SESSION['username']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
	header('location: index.php');
	exit();
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Edit Student - Student Management System</title>
  <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
  <div class="header">
  	<h2>Edit Student</h2>
  </div>
	 
  <form method="post" action="edit_student.php">
	 	<?php if (isset($_SESSION['success'])) : ?>
			<div class="error success" >
				<h3>
					<?php
						echo $_SESSION['success'];
						unset($_SESSION['success']);
					?>
				</h3>
			</div>
		<?php endif ?>
	   <?php include('errors.php'); ?>
  	<div class="input-group">
  		<label>Student ID to Edit</label>
  		<input type="text" name="student_id" required>
  	</div>
  	<div class="input-group">
  		<label>Student Name</label>
  		<input type="text" name="student_name">
  	</div>
  	<div class="input-group">
  		<label>Email</label>
  		<input type="email" name="email">
  	</div>
  	<div class="input-group">
  		<label>Phone</label>
  		<input type="text" name="phone">
  	</div>
  	<div class="input-group">
  		<label>Department</label>
  		<input type="text" name="course">
  	</div>
  	<div class="input-group">
  		<button type="submit" class="btn" name="edit_student">Update Student</button>
  	</div>
  	<p>
  		<a href="index.php" class="btn">Back to Dashboard</a>
  	</p>
  </form>
</body>
</html>
