<?php
// setup_schema.php - Ensure all required columns exist

$db = mysqli_connect('localhost', 'root', '', 'student_management_system');
if (!$db) {
    die("DB connection failed");
}

// Ensure users.student_ref_id exists
mysqli_query($db, "
    ALTER TABLE users 
    ADD COLUMN student_ref_id INT NULL DEFAULT NULL 
    AFTER role
");

// Ensure email_confirmations table doesn't exist (we're not using it)
// skip for now

$db->close();
echo "Schema setup complete.";
