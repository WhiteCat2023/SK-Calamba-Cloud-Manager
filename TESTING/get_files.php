<?php
// get_files.php

header('Content-Type: application/json');

include 'db_connect.php';

// Check if the request method is GET
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $sql = "SELECT * FROM files ORDER BY uploaded_at DESC";
    $result = $conn->query($sql);

    $files = [];

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $files[] = $row;
        }
    }

    echo json_encode(['success' => true, 'files' => $files]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

$conn->close();
?>
