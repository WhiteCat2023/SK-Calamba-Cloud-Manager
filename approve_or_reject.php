<?php
    require 'db.php';
    
    header('Content-Type: application/json');
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $request_id = $_POST['file_id'] ?? null;
        $action = $_POST['action'] ?? null;
    
        if (!$request_id || !$action) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request. Missing file_id or action.']);
            exit;
        }
    
        $stmt = $conn->prepare('SELECT file_name, file_path FROM deletion_requests WHERE file_id = ?');
        $stmt->bind_param('i', $request_id);
        $stmt->execute();
        $result = $stmt->get_result();
    
        if ($result->num_rows > 0) {
            $file = $result->fetch_assoc();
            $file_name = $file['file_name'];
            $file_path = $file['file_path']; 
    
            if (!file_exists($file_path)) {
                echo json_encode(['status' => 'error', 'message' => 'File does not exist on the server.']);
                exit;
            }
    
            if ($action === 'approve') {
                if (unlink($file_path)) {
                    $stmt = $conn->prepare('UPDATE deletion_requests SET file_request_status = "approved" WHERE file_id = ?');
                    $stmt->bind_param('i', $request_id);
                    
                    if ($stmt->execute()) {
                        echo json_encode(['status' => 'success', 'message' => 'File deleted successfully and request approved.']);
                    } else {
                        echo json_encode(['status' => 'error', 'message' => 'Failed to update request status to approved.']);
                    }
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to delete the file from the server.']);
                }
            } elseif ($action === 'reject') {
                $stmt = $conn->prepare('UPDATE deletion_requests SET file_request_status = "rejected" WHERE file_id = ?');
                $stmt->bind_param('i', $request_id);
        
                if ($stmt->execute()) {
                    echo json_encode(['status' => 'success', 'message' => 'Deletion request rejected.']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to update request status to rejected.']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Invalid action provided.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Request not found.']);
        }
    
        $stmt->close();
        $conn->close();
    }
?>
