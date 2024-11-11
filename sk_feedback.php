<?php
    include 'db.php'; 
    header('Content-Type: application/json');

    // Set additional security headers
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST");
    header("Access-Control-Allow-Headers: Content-Type");

    // Check for a valid database connection
    if ($conn->connect_error) {
        echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
        exit();
    }

    // Get the JSON payload
    $data = json_decode(file_get_contents('php://input'), true);

    // Validate and sanitize input fields
    if (!isset($data['feedback_title']) || !isset($data['feedback_description']) || !isset($data['feedback_by'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
        exit();
    }

    $feedback_title = trim($data['feedback_title']);
    $feedback_description = trim($data['feedback_description']);
    $feedback_by = trim($data['feedback_by']);

    // Simple sanitization (you may adjust this based on your requirements)
    if (!preg_match("/^[a-zA-Z0-9 ]*$/", $feedback_title) || !preg_match("/^[a-zA-Z0-9 ]*$/", $feedback_description)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid characters in input']);
        exit();
    }

    // Prepare the SQL statement
    $sql = "INSERT INTO feedback (feedback_by, feedback_title, feedback_description) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to prepare statement']);
        exit();
    }

    // Bind parameters and execute
    $stmt->bind_param("iss", $feedback_by, $feedback_title, $feedback_description);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Feedback sent successfully']);
    } else {
        // Log the error for debugging (do not show SQL errors to users)
        error_log("Database error: " . $stmt->error);
        echo json_encode(['status' => 'error', 'message' => 'Feedback failed']);
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();
?>
