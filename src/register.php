<?php include('server.php') ?>
<!DOCTYPE html>
<html>
<head>
  <title>Registration system PHP and MySQL</title>
  <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
  <div class="header">
  	<h2>Register</h2>
  </div>
	
  <form method="post" action="register.php">
    <?php include('errors.php'); ?>
    <div class="input-group">
      <label>Full Name (will be saved to student record)</label>
      <input type="text" name="student_name" value="<?php echo $_POST['student_name'] ?? ''; ?>" required>
    </div>
    <div class="input-group">
      <label>Student ID (this will be your login username)</label>
      <input type="text" name="student_id" value="<?php echo $_POST['student_id'] ?? ''; ?>" required>
    </div>
    <div class="input-group">
      <label>Email</label>
      <input type="email" name="email" value="<?php echo $_POST['email'] ?? ''; ?>" required>
    </div>
    <div class="input-group">
      <label>Password</label>
      <input type="password" name="password_1" required>
    </div>
    <div class="input-group">
      <label>Confirm Password</label>
      <input type="password" name="password_2" required>
    </div>
    <div class="input-group">
      <button type="submit" class="btn" name="reg_user">Register</button>
    </div>
    <p>
      Already a member? <a href="login.php">Sign in</a>
    </p>
  </form>
</body>
</html>