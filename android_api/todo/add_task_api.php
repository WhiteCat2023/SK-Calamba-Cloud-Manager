<?php
    include 'todo_db_conn.php';
    
    // Retrieve JSON data from the request body
    header('Content-Type: application/json');
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Check if all required fields are provided
    if (!empty($data['title']) && !empty($data['description']) && !empty($data['startDate']) && !empty($data['endDate'])) {
        // Sanitizing inputs to prevent SQL injections
        $title = $conn->real_escape_string($data['title']);
        $description = $conn->real_escape_string($data['description']);
        $startDate = $conn->real_escape_string($data['startDate']);
        $endDate = $conn->real_escape_string($data['endDate']);
        $pending = "pending";
        
        // Prepare the SQL statement to prevent SQL injection
        $stmt = $conn->prepare("INSERT INTO todos (task_title, task_description, task_is_completed, task_start_date, task_end_date) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $title, $description,$pending, $startDate, $endDate); 
        
        // Execute the statement and check for success
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Task added successfully']); 
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Task add unsuccessful: ' . $stmt->error]); 
        }
        $stmt->close(); 
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Please fill in all required inputs']); 
    }
    
    $conn->close();
?>
