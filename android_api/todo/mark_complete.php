<?php
    include 'todo_db_conn.php';
    
    header('Content-Type: application/json');
    
    
    // Retrieve data from the POST request
    $data = json_decode(file_get_contents('php://input'), true);
    $taskId = $data['taskId'];
    
    // Prepare SQL statement to update task status
    $stmt = $conn->prepare("UPDATE todos SET task_is_completed = 1 WHERE task_id = ?");
    $stmt->bind_param("i", $taskId);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Task marked as complete']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update task']);
    }
    
    $stmt->close();
    $conn->close();
?>