<?php
// download_file.php

include 'db_connect.php';

// Check if the request method is GET and 'id' is set
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['id'])) {
    $fileId = intval($_GET['id']);

    // Fetch file details
    $sql = "SELECT * FROM files WHERE id = $fileId";
    $result = $conn->query($sql);

    if ($result->num_rows == 1) {
        $file = $result->fetch_assoc();
        $filePath = $file['path'];

        if (file_exists($filePath)) {
            // Set headers and output the file
            header('Content-Description: File Transfer');
            header('Content-Type: ' . mime_content_type($filePath));
            header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit;
        } else {
            echo "File not found.";
        }
    } else {
        echo "Invalid file ID.";
    }
} else {
    echo "Invalid request.";
}

$conn->close();
?>
