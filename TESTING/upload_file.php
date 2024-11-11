<?php
// upload_file.php

header('Content-Type: application/json');

include 'db_connect.php';

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve POST data
    $fileName = isset($_POST['fileName']) ? $conn->real_escape_string($_POST['fileName']) : '';
    $filePath = isset($_POST['filePath']) ? $conn->real_escape_string($_POST['filePath']) : '';
    $fileType = isset($_POST['fileType']) ? $conn->real_escape_string($_POST['fileType']) : '';
    $fileSize = isset($_POST['fileSize']) ? intval($_POST['fileSize']) : 0;

    // Validate required fields
    if (empty($fileName) || empty($filePath)) {
        echo json_encode(['success' => false, 'message' => 'File name and path are required.']);
        exit;
    }

    // Insert into database
    $sql = "INSERT INTO files (name, path, type, size) VALUES ('$fileName', '$filePath', '$fileType', '$fileSize')";

    if ($conn->query($sql) === TRUE) {
        echo json_encode(['success' => true, 'message' => 'File metadata saved successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

$conn->close();
?>
