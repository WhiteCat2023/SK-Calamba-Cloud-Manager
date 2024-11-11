<?php
    $host = 'localhost'; // Change this if your database is hosted remotely
    $db = 'u843230181_skcalamba';
    $user = 'u843230181_whitecat';
    $pass = 'WhiteCat@2004';
    
    // Create a connection
    $conn = new mysqli($host, $user, $pass, $db);
    
    // Check for connection errors
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    } 
?>