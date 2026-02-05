<?php 
  session_start(); 

  if (!isset($_SESSION['username'])) {
  	$_SESSION['msg'] = "You must log in first";
  	header('location: login.php');
  }
  if (isset($_GET['logout'])) {
  	session_destroy();
  	unset($_SESSION['username']);
  	header("location: login.php");
  }
?>
<!DOCTYPE html>
<html>
<head>
	<title>Home</title>
	<link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>

<div class="header">
	<h2>Student Management System</h2>
</div>

<div class="content">
  	<!-- notification message -->
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

    <!-- logged in user information -->
    <?php  if (isset($_SESSION['username'])) : ?>
    	<h3>Welcome, <strong><?php echo $_SESSION['username']; ?></strong> (<?php echo isset($_SESSION['role']) && $_SESSION['role'] === 'admin' ? 'ADMIN' : 'STUDENT'; ?>)</h3>
    	
    	<!-- Dashboard Menu -->
    	<div style="margin: 20px 0;">
    		<h4>Dashboard Menu</h4>
    		<ul style="list-style-type: none; padding: 0;">
				<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
					<!-- Admin Menu -->
					<li><a href="product.php" style="display: block; padding: 10px; margin: 5px 0; background: #007bff; color: white; text-decoration: none; border-radius: 4px;">Manage Students</a></li>
					<li><a href="add_student.php" style="display: block; padding: 10px; margin: 5px 0; background: #28a745; color: white; text-decoration: none; border-radius: 4px;">Add New Student</a></li>
					<li><a href="edit_student.php" style="display: block; padding: 10px; margin: 5px 0; background: #ffc107; color: white; text-decoration: none; border-radius: 4px;">Edit Student</a></li>
					<li><a href="delete_student.php" style="display: block; padding: 10px; margin: 5px 0; background: #dc3545; color: white; text-decoration: none; border-radius: 4px;">Delete Student</a></li>
					<li><a href="admin_courses.php" style="display: block; padding: 10px; margin: 5px 0; background: #6f42c1; color: white; text-decoration: none; border-radius: 4px;">Manage Courses</a></li>
					<li><a href="admin_enrollments.php" style="display: block; padding: 10px; margin: 5px 0; background: #fd7e14; color: white; text-decoration: none; border-radius: 4px;">View Enrollments</a></li>
				<?php else: ?>
					<!-- Student Menu -->
					<li><a href="courses_new.php" style="display: block; padding: 10px; margin: 5px 0; background: #17a2b8; color: white; text-decoration: none; border-radius: 4px;">Courses / Enroll</a></li>
					<li><a href="my_enrollments.php" style="display: block; padding: 10px; margin: 5px 0; background: #28a745; color: white; text-decoration: none; border-radius: 4px;">My Enrollments</a></li>
				<?php endif; ?>
    			<li><a href="index.php?logout='1'" style="display: block; padding: 10px; margin: 5px 0; background: #6c757d; color: white; text-decoration: none; border-radius: 4px;">Logout</a></li>
    		</ul>
    	</div>
    <?php endif ?>
</div>

</body>
</html>