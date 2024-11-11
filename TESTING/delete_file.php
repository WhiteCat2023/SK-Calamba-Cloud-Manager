<?php
// delete_file.php

header('Content-Type: application/json');

include 'db_connect.php';

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve POST data
    $fileId = isset($_POST['id']) ? intval($_POST['id']) : 0;

    if ($fileId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid file ID.']);
        exit;
    }

    // Delete from database
    $sql = "DELETE FROM files WHERE id = $fileId";

    if ($conn->query($sql) === TRUE) {
        echo json_encode(['success' => true, 'message' => 'File deleted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

$conn->close();
?>
