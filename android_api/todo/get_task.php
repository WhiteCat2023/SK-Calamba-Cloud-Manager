<?php
    
    include'todo_db_conn.php';
    
    // Retrieve JSON data from the request body
    header('Content-Type: application/json');
    
    $data = json_decode(file_get_contents('php://input'), true);
    //Prepare SQL statement
    $stmt = $conn->prepare("SELECT task_id, task_title, task_description, task_start_date, task_end_date, task_is_completed FROM todos WHERE task_is_completed = 0");
    //This SQL statement only returns task that are not completed
    //Execute the statement
    $stmt->execute();
    
    //Binding results to varibales
    $stmt->bind_result($taskId, $taskTitle, $taskDescription, $taskStartDate, $taskEndDate, $isComplete);
    
    //Initializing task
    $task = [];
    
    while ($stmt->fetch()) {
    $tasks[] = [
        'taskId' => $taskId,
        'taskName' => $taskTitle,
        'status' => $taskDescription,
        'startDate' => $taskStartDate,
        'endDate' => $taskEndDate,
        'isComplete' => $isComplete == 1
        ];
    }
    // Check if tasks were found
    if (!empty($tasks)) {
        // Send tasks as JSON
        echo json_encode($tasks);
    } else {
        // Send error message as JSON if no tasks were found
        echo json_encode(['status' => 'error', 'message' => 'No tasks found']);
    }
    
    // Close the statement and connection
    $stmt->close();
    $conn->close();
?>