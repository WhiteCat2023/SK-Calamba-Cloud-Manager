<?php
    session_start();
    include 'db.php'; 
    header('Content-Type: application/json');

    // Check if the connection is successful
    if ($conn->connect_error) {
        echo json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $conn->connect_error]);
        exit();
    }

    // Get the input data
    $data = json_decode(file_get_contents('php://input'), true);
    $email = $data['email'] ?? null;
    $password = $data['password'] ?? null;

    // Validate input
    if (empty($email) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Email and password are required.']);
        exit();
    }

    // Function to check if the email is verified
    function isEmailVerified($conn, $email) {
        $query = $conn->prepare("SELECT isVerified FROM sk_users WHERE user_email = ?");
        $query->bind_param("s", $email);
        $query->execute();
        $result = $query->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            return (bool)$user['isVerified'];
        }
        return false; // User not found
    }

    // Function to check if the email is active
    function isActive($conn, $email) {
        $query = $conn->prepare("SELECT isActive FROM sk_users WHERE user_email = ?");
        $query->bind_param("s", $email);
        $query->execute();
        $result = $query->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            return (bool)$user['isActive'];
        }
        return false; // User not found
    }

    try {
        // Query to check if the user exists
        $query = $conn->prepare("SELECT * FROM sk_users WHERE user_email = ?");
        $query->bind_param("s", $email);
        $query->execute();
        $result = $query->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // Verify password using password_verify()
            if (password_verify($password, $user['user_password']) || $password === $user['user_password']) {
                if (isEmailVerified($conn, $email)) {
                    if (isActive($conn, $email)) {
                        // Set session variables on successful login
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['user_email'] = $user['user_email'];
                        $_SESSION['user_position'] = $user['user_position'];
                        $_SESSION['user_name'] = $user['user_name'];
                        $_SESSION['user_loggedin'] = true;

                        // Successful login response
                        echo json_encode([
                            'status' => 'success',
                            'message' => 'Login successful!',
                            'user_id' => $_SESSION['user_id'],
                            'user_name' => $_SESSION['user_name'],
                            'user_email' => $_SESSION['user_email'],
                            'user_position' => $_SESSION['user_position'],
                            'user_loggedin' => $_SESSION['user_loggedin']
                        ]);
                    } else {
                        echo json_encode(['status' => 'error', 'message' => 'Please ask your admin to activate your account first.']);
                    }
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Please verify your email first.']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Incorrect password.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'User not found.']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    } finally {
        // Ensure resources are freed
        if (isset($query)) {
            $query->close();
        }
        $conn->close();
    }
?>
