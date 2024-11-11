<?php
    include 'todo_db_conn.php';
    
    // Retrieve JSON data from the request body
    header('Content-Type: application/json');
    
    // Get the raw input
    $rawData = file_get_contents('php://input');
    $data = json_decode($rawData, true);
    
    // Debugging: check if data is being received
    if ($data === null) {
        echo json_encode(["error" => "Invalid JSON data: " . json_last_error_msg()]);
        exit; // Stop the script
    }

    // Ensure taskId is present
    if (!isset($data['taskId'])) {
        echo json_encode(["error" => "Missing taskId"]);
        exit; // Stop the script
    }

    $taskId = $data['taskId'];

    // Prepare and execute the delete query
    $sql = "DELETE FROM todos WHERE task_id = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        echo json_encode(["error" => "SQL prepare failed: " . $conn->error]);
        exit;
    }

    $stmt->bind_param('i', $taskId);
    
    if ($stmt->execute()) {
        echo json_encode(["message" => "Task deleted successfully"]);
    } else {
        echo json_encode(["error" => "Error deleting task: " . $stmt->error]);
    }
    
    $stmt->close();
    $conn->close();
?>
