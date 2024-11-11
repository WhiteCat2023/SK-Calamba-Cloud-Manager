<?php
// FTP server credentials
$ftp_server = "153.92.11.176";
$ftp_username = "u843230181.skcalamba.scarlet2.io";
$ftp_userpass = "Berndt@123";

$secret_token = "bf4edef043130d19e11048aab68d4c512b62d2de1d000514b65410876e9a96f2";

// Get the requested path from the Android app
$path = isset($_POST['path']) ? $_POST['path'] : '/';

// Check if 'auth_token' is present and valid
if (!isset($_POST['auth_token']) || $_POST['auth_token'] !== $secret_token) {
    die(json_encode(['status' => 'error', 'message' => 'Unauthorized or missing auth token']));
}

// Connect to the FTP server
$conn_id = ftp_connect($ftp_server);
if (!$conn_id) {
    die(json_encode(['status' => 'error', 'message' => 'Could not connect to FTP server']));
}

// Login to the FTP server
$login_result = ftp_login($conn_id, $ftp_username, $ftp_userpass);
if (!$login_result) {
    ftp_close($conn_id);
    die(json_encode(['status' => 'error', 'message' => 'FTP login failed']));
}

// Get the file list (using ftp_rawlist for detailed information)
$files = ftp_rawlist($conn_id, $path);
if ($files === false) {
    ftp_close($conn_id);
    die(json_encode(['status' => 'error', 'message' => 'Failed to retrieve file list']));
}

// Close FTP connection
ftp_close($conn_id);

// Prepare data for the Android app
$data = [];
foreach ($files as $file) {
    // Split the rawlist data into individual file info
    $file_info = preg_split("/\s+/", $file, 9);
    $permissions = $file_info[0];
    $isDir = $permissions[0] === 'd';
    $size = $file_info[4];  // File size in bytes
    $date = $file_info[5] . ' ' . $file_info[6] . ' ' . $file_info[7]; // Modification date
    $name = $file_info[8];  // File or directory name

    // Add the file details to the response array
    $data[] = [
        'name' => $name,
        'isDirectory' => $isDir,
        'size' => $isDir ? '' : $size,
        'date' => $date,
    ];
}

// Return the data as JSON
echo json_encode(['status' => 'success', 'files' => $data]);
?>
