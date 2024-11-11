<?php
// db_connect.php

$servername = "localhost"; // Hostinger typically uses 'localhost'
$username = "u843230181_whitecat";     // Your database username
$password = "WhiteCat@2004"; // Your database password
$dbname = "u843230181_skcalamba"; // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
