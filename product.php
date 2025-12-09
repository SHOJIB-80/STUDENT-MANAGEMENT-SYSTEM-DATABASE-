<!DOCTYPE html>
<html>
<body>

<?php
session_start();

// initializing variables
$username = "";
$email    = "";
$errors = array(); 

// connect to the database
$db = mysqli_connect('localhost', 'root', '', 'student_management_system');

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$sql = "SELECT id, student_id, student_name, email, phone, course FROM students";
$result = $db->query($sql);

if ($result->num_rows > 0) {
    // output data of each row
    print "<table border='1' style='width: 100%; border-collapse: collapse; margin: 20px 0;'>";
    print "<tr style='background-color: #007bff; color: white;'><th>ID</th><th>Student ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Course</th></tr>";
    
    while($row = $result->fetch_assoc()) {
        print "<tr>";
        print "<td style='padding: 10px; border: 1px solid #ddd;'>" . $row["id"] . "</td>";
        print "<td style='padding: 10px; border: 1px solid #ddd;'>" . $row["student_id"] . "</td>";
        print "<td style='padding: 10px; border: 1px solid #ddd;'>" . $row["student_name"] . "</td>";
        print "<td style='padding: 10px; border: 1px solid #ddd;'>" . $row["email"] . "</td>";
        print "<td style='padding: 10px; border: 1px solid #ddd;'>" . $row["phone"] . "</td>";
        print "<td style='padding: 10px; border: 1px solid #ddd;'>" . $row["course"] . "</td>";
        print "</tr>";
    }
    print "</table>";
} else {
    print "0 results";
}



$db->close();   
        ?> 



</body>
</html>