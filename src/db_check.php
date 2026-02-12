<?php
// db_check.php - diagnostics for users/students schema and recent rows

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'student_management_system';

$mysqli = new mysqli($host, $user, $pass, $dbname);
if ($mysqli->connect_errno) {
    echo "DB connect failed: " . $mysqli->connect_error;
    exit;
}

function print_table($res) {
    if (!$res) return;
    $rows = [];
    while ($row = $res->fetch_assoc()) $rows[] = $row;
    if (empty($rows)) {
        echo "(no rows)\n";
        return;
    }
    // print header
    $keys = array_keys($rows[0]);
    echo implode("\t", $keys) . "\n";
    foreach ($rows as $r) {
        $vals = [];
        foreach ($keys as $k) $vals[] = (string)$r[$k];
        echo implode("\t", $vals) . "\n";
    }
}

header('Content-Type: text/plain; charset=utf-8');

echo "--- DESCRIBE users ---\n";
$res = $mysqli->query("DESCRIBE users");
if ($res) print_table($res);
else echo "Error: " . $mysqli->error . "\n";

echo "\n--- DESCRIBE students ---\n";
$res = $mysqli->query("DESCRIBE students");
if ($res) print_table($res);
else echo "Error: " . $mysqli->error . "\n";

echo "\n--- DESCRIBE email_confirmations ---\n";
$res = $mysqli->query("DESCRIBE email_confirmations");
if ($res) print_table($res);
else echo "(email_confirmations may not exist) " . $mysqli->error . "\n";

echo "\n--- Recent users ---\n";
$res = $mysqli->query("SELECT id, username, email, student_ref_id, role FROM users ORDER BY id DESC LIMIT 20");
if ($res) print_table($res);
else echo "Error: " . $mysqli->error . "\n";

echo "\n--- Recent students ---\n";
$res = $mysqli->query("SELECT id, student_id, student_name, email FROM students ORDER BY id DESC LIMIT 20");
if ($res) print_table($res);
else echo "Error: " . $mysqli->error . "\n";

$mysqli->close();
