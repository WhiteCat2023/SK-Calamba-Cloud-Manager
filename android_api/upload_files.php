<?php

// Define the upload directory (ensure this directory exists and is writable)
define('UPLOAD_DIR', 'uploads/');

// Function to handle the file upload
function handleFileUpload($files, $targetFolder) {
    $response = [];

    // Ensure target directory is created
    $uploadPath = UPLOAD_DIR . $targetFolder;
    if (!is_dir($uploadPath)) {
        if (!mkdir($uploadPath, 0755, true)) {
            // Return error if directory cannot be created
            return [
                'status' => 'error',
                'message' => 'Failed to create target folder: ' . $targetFolder
            ];
        }
    }

    // Check if files array has valid structure
    if (!is_array($files['name'])) {
        return [
            'status' => 'error',
            'message' => 'Invalid file input structure.'
        ];
    }

    foreach ($files['name'] as $key => $name) {
        // Skip empty files
        if ($files['error'][$key] !== UPLOAD_ERR_OK) {
            $response[] = [
                'file' => $name,
                'status' => 'error',
                'message' => 'Error uploading file: ' . $name,
            ];
            continue;
        }

        // Sanitize the file name
        $sanitizedFileName = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', basename($name));

        // Construct the file path
        $filePath = $uploadPath . '/' . $sanitizedFileName;

        // Move the uploaded file to the target directory
        if (move_uploaded_file($files['tmp_name'][$key], $filePath)) {
            $response[] = [
                'file' => $sanitizedFileName,
                'status' => 'success',
                'path' => $filePath,
            ];
        } else {
            $response[] = [
                'file' => $sanitizedFileName,
                'status' => 'error',
                'message' => 'Failed to upload file: ' . $name,
            ];
        }
    }

    return $response;
}

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if the target folder parameter is set
    if (isset($_POST['target_folder'])) {
        $targetFolder = trim($_POST['target_folder']);

        // Check if files are uploaded
        if (isset($_FILES['file']) && !empty($_FILES['file']['name'][0])) {
            $result = handleFileUpload($_FILES['file'], $targetFolder);
            // Return a JSON response
            header('Content-Type: application/json');
            echo json_encode($result);
        } else {
            // No files uploaded
            http_response_code(400);
            echo json_encode(['error' => 'No files uploaded.']);
        }
    } else {
        // Target folder is not specified
        http_response_code(400);
        echo json_encode(['error' => 'Target folder not specified.']);
    }
} else {
    // Method not allowed
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
}
