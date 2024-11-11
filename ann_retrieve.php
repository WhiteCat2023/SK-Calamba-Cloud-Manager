<?php
require "db.php";
header('Content-Type: application/json');

try {
    // Check if the endpoint is 'announcements'
    if (isset($_GET['endpoint']) && $_GET['endpoint'] === 'announcements') {
        // Fetch announcements from the database
        $sql = "SELECT ann_id, ann_title, ann_content, created_by, ann_created_at FROM announcements";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result) {
            $announcements = [];

            while ($row = $result->fetch_assoc()) {
                // Prepare the user query for the current announcement
                $sqlName = "SELECT user_name FROM sk_users WHERE user_id = ?";
                $stmtUser  = $conn->prepare($sqlName);
                $stmtUser ->bind_param("i", $row['created_by']);
                $stmtUser ->execute();
                $userResult = $stmtUser ->get_result();
                $user = $userResult->fetch_assoc();

                $announcements[] = [
                    'ann_id' => (int)$row['ann_id'],
                    'ann_title' => $row['ann_title'],
                    'ann_content' => $row['ann_content'],
                    'created_by' => $user ? $user['user_name'] : null,
                    'ann_created_at' => $row['ann_created_at']
                ];

                $stmtUser ->close();
            }

            echo json_encode($announcements);
        } else {
            echo json_encode(['error' => 'Failed to fetch announcements']);
        }
    } else {
        echo json_encode(['error' => 'Invalid API endpoint']);
    }
} catch (Exception $e) {
    // Handle any exceptions and return a JSON error response
    echo json_encode(['error' => 'An error occurred: ' . $e->getMessage()]);
}

$conn->close();
?>