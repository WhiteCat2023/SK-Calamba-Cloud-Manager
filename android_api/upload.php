
<?php
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    if (isset($input['auth_token']) && $input['auth_token'] === "Ramsey") {
        $folderPath = $input['folder_path'] ?? '';
        $targetDir =  'uploads/' . $folderPath;

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        if (isset($input['files'])) {
            foreach ($input['files'] as $file) {
                $fileName = $file['file_name'];
                $fileData = base64_decode($file['file_data']);
                $filePath = $targetDir . '/' . $fileName;

                if (file_put_contents($filePath, $fileData)) {
                    $response['status'] = 'success';
                    $response['message'] = 'Files uploaded successfully.';
                    $response['file_name'][] = $fileName;
                } else {
                    $response['message'] = 'Error saving file: ' . $fileName;
                }
            }
        } else {
            $response['message'] = 'No files provided.';
        }
    } else {
        $response['message'] = 'Unauthorized access.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>