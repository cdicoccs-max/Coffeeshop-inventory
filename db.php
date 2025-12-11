<?php
// Database configuration
$host = "localhost";
$user = "root";
$pass = "";
$db = "coffee_inventory";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>