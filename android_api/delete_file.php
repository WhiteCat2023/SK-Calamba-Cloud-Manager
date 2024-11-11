<?php
    require 'db.php';

    header('Content-Type: application/json');

    $data = json_decode(file_get_contents('php://input'), true);

    $auth = "bf4edef043130d19e11048aab68d4c512b62d2de1d000514b65410876e9a96f2";

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $file_name = isset($data['file_name']) ? $data['file_name'] : null;
        $file_path = isset($data['current_path']) ? $data['current_path'] : null;
        $auth_token = isset($data['auth_token']) ? $data['auth_token'] : null;
        $action = isset($data['action']) ? $data['action'] : null;
        $requested_by = isset($data['user_id']) ? $data['user_id'] : null;
        $reason = isset($data['reason']) ? $data['reason'] : null;

        if ($auth_token !== $auth) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid auth token']);
            exit;
        }

        if (empty($file_name) || empty($file_path) || empty($action)) {
            echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
            exit;
        }

        if ($action === "request_permission") {
            $stmt = $conn->prepare('SELECT * FROM deletion_requests WHERE file_name = ? AND file_request_status = ?');
            $status_pending = 'pending';
            $stmt->bind_param('ss', $file_name, $status_pending);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                echo json_encode(['status' => 'success', 'message' => 'A deletion request for this file already exists.']);
            } else {
                $stmt = $conn->prepare('INSERT INTO deletion_requests (file_name, file_request_status, file_path, requested_by, reason) VALUES (?, ?, ?, ?, ?)');
                $status = 'pending';
                $stmt->bind_param('sssss', $file_name, $status, $file_path, $requested_by, $reason);

                if ($stmt->execute()) {
                    echo json_encode(['status' => 'success', 'message' => 'Deletion request submitted']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to submit deletion request']);
                }
            }
        } else {
            // If the action is not recognized
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        }
    } else {
        // If request method is not POST
        echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    }
?>
