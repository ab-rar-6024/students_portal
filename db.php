<?php
$host = "localhost";
$user = "root";         // ✅ Use "root" for XAMPP
$pass = "";             // ✅ Empty password by default
$db = "crescent_portal";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
