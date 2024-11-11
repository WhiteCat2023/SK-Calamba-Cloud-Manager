<?php
    require 'db.php';
    
    session_start();
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        header('Location: default.php');
        exit();
    }

    // Variables for pagination
    $rows_per_page = 50; // Number of rows per page
    $currentPage = isset($_GET['page-nr']) ? (int)$_GET['page-nr'] : 1;
    $start = ($currentPage - 1) * $rows_per_page;
    
    // Retrieve the search term if it is set
    $searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
    
    // Base SQL query for counting total rows
    $countSql = "SELECT COUNT(*) AS total FROM sk_users WHERE role != 'admin'";
    $params = []; // Parameters for binding
    $types = ""; // Types for bind_param
    
    // Modify SQL query if search is applied
    if ($searchTerm) {
        $countSql .= " AND (user_name LIKE ? OR user_email LIKE ?)";
        $likeTerm = '%' . $searchTerm . '%';
        $params[] = $likeTerm;
        $params[] = $likeTerm;
        $types .= "ss";
    }
    
    // Prepare and execute count statement
    $countStmt = $conn->prepare($countSql);
    if ($params) {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $totalRows = $countResult->fetch_assoc()['total'];
    $countStmt->close();
    
    $pages = ceil($totalRows / $rows_per_page); // Total number of pages
    
    // Retrieve announcements with pagination and search
    $sql = "SELECT * FROM sk_users WHERE role != 'admin'";
    if ($searchTerm) {
        $sql .= " AND (user_name LIKE ? OR user_email LIKE ?)";
    }
    $sql .= " LIMIT ?, ?";
    $stmt = $conn->prepare($sql);
    
    if ($searchTerm) {
        $types .= "ii";
        $params[] = $start;
        $params[] = $rows_per_page;
        $stmt->bind_param($types, ...$params);
    } else {
        $stmt->bind_param("ii", $start, $rows_per_page);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row; 
    }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $user_id = intval($_POST['user_id']);
    $action = $_POST['action'];
    
    // Define SQL based on action type
    if ($action === 'activate') {
        $sql = "UPDATE sk_users SET isActive = 1 WHERE user_id = ?";
    } elseif ($action === 'deactivate') {
        $sql = "UPDATE sk_users SET isActive = 0 WHERE user_id = ?";
    } elseif ($action === 'delete') {
        $sql = "DELETE FROM sk_users WHERE user_id = ?";
    } else {
        echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid action.',
                    text: 'Please try again.'
                });
              </script>";
        exit();
    }

    // Prepare and execute the statement
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        // Redirect back to the same page after successful execution
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    } else {
        // Handle errors with SweetAlert if execution fails
        echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'Failed to process request.',
                    text: 'Please try again.'
                });
              </script>";
    }
}

  
                

    
    // Email validation function
    function checkEmail($email, $conn) {
        $checkEmailSql = "SELECT * FROM sk_users WHERE user_email = ?";
        $checkEmailStmt = $conn->prepare($checkEmailSql);
        $checkEmailStmt->bind_param("s", $email);
        $checkEmailStmt->execute();
        $checkEmailResult = $checkEmailStmt->get_result();

        return $checkEmailResult->num_rows > 0;
    }
    function checkName($name, $conn){
        $sql = "SELECT * FROM sk_users WHERE user_name = ?";
        $checkName = $conn->prepare($sql);
        $checkName->bind_param("s", $name);
        $checkName->execute();
        $result = $checkName->get_result();
        
        return $result->num_rows > 0;
    }
    
                                    require 'PHPMailer/PHPMailer/src/PHPMailer.php';
                                    require 'PHPMailer/PHPMailer/src/Exception.php';
                                    require 'PHPMailer/PHPMailer/src/SMTP.php';
                                                    
                                    // Handle user registration
                                    if ($_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST['submit-btn'])) {
                                        $name = $_POST['fullname'];
                                        $email = $_POST['email'];
                                        $position = $_POST['position'];
                                        $password = $_POST['password'];
                                        $confirmPassword = $_POST['confirm-password'];
                    
                                        // Generate a verification token and code
                                        $verificationToken = bin2hex(random_bytes(32)); // 32 bytes * 2 = 64 characters
                                        $verificationCode = random_int(100000, 999999);
                    
                                        // Input validation
                                        if (empty($name)) {
                                            echo "<script>
                                                    Swal.fire({
                                                        icon: 'error',
                                                        title: 'Oops...',
                                                        text: 'Please enter your fullname'
                                                    });
                                                  </script>";
                                        } elseif (checkName($name, $conn)){
                                            echo "<script>
                                                    Swal.fire({
                                                        icon: 'error',
                                                        title: 'Oops...',
                                                        text: 'Name already exist'
                                                    });
                                                  </script>";
                                        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                            echo "<script>
                                                    Swal.fire({
                                                        icon: 'error',
                                                        title: 'Oops...',
                                                        text: 'Invalid email format'
                                                    });
                                                  </script>";
                                        } elseif (checkEmail($email, $conn)) {
                                            echo "<script>
                                                    Swal.fire({
                                                        icon: 'error',
                                                        title: 'Oops...',
                                                        text: 'Email already exists'
                                                    });
                                                  </script>";
                                        } elseif (empty($position)) {
                                            echo "<script>
                                                    Swal.fire({
                                                        icon: 'error',
                                                        title: 'Oops...',
                                                        text: 'Please select a position'
                                                    });
                                                  </script>";
                                        } elseif (empty($password)) {
                                            echo "<script>
                                                    Swal.fire({
                                                        icon: 'error',
                                                        title: 'Oops...',
                                                        text: 'Please enter a password'
                                                    });
                                                  </script>";
                                        } elseif ($password !== $confirmPassword) {
                                            echo "<script>
                                                    Swal.fire({
                                                        icon: 'error',
                                                        title: 'Oops...',
                                                        text: 'Passwords do not match'
                                                    });
                                                  </script>";
                                        } else {
                                            // Hash the password
                                            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    
                                            // Insert user into the database
                                            $insertSql = "INSERT INTO sk_users (user_name, user_email, user_password, user_position, verificationToken, verification_code, role) VALUES (?, ?, ?, ?, ?, ?, ?)";
                                            $stmt = $conn->prepare($insertSql);
                                            $role = "user"; // User role
                                            $stmt->bind_param("sssssss", $name, $email, $hashedPassword, $position, $verificationToken, $verificationCode, $role);
                    
                                            if ($stmt->execute()) {
                                                // Send verification email
                                                $mail = new PHPMailer\PHPMailer\PHPMailer();
                                                $mail->isSMTP();
                                                $mail->Host = 'smtp.gmail.com'; // Specify your SMTP server
                                                $mail->SMTPAuth = true;
                                                $mail->Username = 'skcalamba6@gmail.com'; // SMTP username
                                                $mail->Password = 'xjtvrdomyrtdidmn'; // SMTP password
                                                $mail->SMTPSecure = 'tls';
                                                $mail->Port = 587; // TCP port to connect to
                    
                                                // Set email format to HTML
                                                $mail->isHTML(true);
                                                $mail->setFrom('skcalamba6@gmail.com', 'SK Calamba');
                                                $mail->addAddress($email); // Add a recipient
                    
                                                $mail->Subject = 'Account Verification';
                                                 $mail->Body = "
                                                    <h1><strong>Verification Code:</strong></h1>
                                                    <h5>{$verificationCode}</h5>
                                                    Click <a href='http://skcalamba.scarlet2.io/userVerify.php?code={$verificationToken}'>here</a> to verify your account.";
                    
                                                if ($mail->send()) {
                                                    header('Location: user-registration.php');
                                                    exit();
                                                } else {
                                                    echo "<script>
                                                        Swal.fire({
                                                            icon: 'success',
                                                            title: 'Registration successful!',
                                                            text: 'However, the verification email could not be sent.'
                                                        });
                                                      </script>";
                                                }
                                            } else {
                                                echo "<script>
                                                        Swal.fire({
                                                            icon: 'error',
                                                            title: 'Failed to register.',
                                                            text: 'Please try again.'
                                                        });
                                                      </script>";
                                            }
                                        }
                                    }
                                    
                                    $stmt->close();
                                    $conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://kit.fontawesome.com/205dbd136e.js" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.lineicons.com/5.0/lineicons.css" />
    <link href="./CSS/nav-aside.css" rel="stylesheet"/>
    <link rel="icon" href="./assets/logo.png" type="image/x-icon">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>User Registration</title>
   
</head>
<body class="d-flex">
    <?php include("./Components/nav-aside.html") ?>
    <div class="main">
        <?php include("./Components/nav-bar.html") ?>
        <main>
            <div>
                <div class="pt-4">
                    <div class="container mb-4">
                        <h1 class="text-center"><i class="lni lni-gemini small-star"></i><strong>User Registration</strong><i class="lni lni-gemini small-star"></i></h1>
                    </div>
                </div>
                   
                <div class="modal fade" id="exampleModal" aria-hidden="true" aria-labelledby="exampleModalLabel" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="exampleModalToggleLabel">New User</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <!-- Registration form -->
                                <form id="registration-form" action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                                    <div class="modal-body">
                                        <div class="form-group mb-2">
                                            <label for="name">Name: </label>
                                            <input id="name" type="text" name="fullname" placeholder="Enter your full name" class="form-control" required/>
                                        </div>
                    
                                        <div class="form-group mb-2">
                                            <label for="email">Email: </label>
                                            <input id="email" name="email" type="email" placeholder="Enter your email" class="form-control" required/>
                                        </div>
                    
                                        <div class="form-group mb-2">
                                            <label for="position">Position: </label>
                                            <select class="form-select" name="position" required>
                                                <option value="" disabled selected>Select Position</option>
                                                <option value="Chairman">Chairman</option>
                                                <option value="Treasurer">Treasurer</option>
                                            </select>
                                        </div>
                    
                                        <div class="form-group mb-2">
                                            <label for="password">Password: </label>
                                            <input id="password" name="password" type="password" placeholder="Enter your password" class="form-control" required/>
                                        </div>
                    
                                        <div class="form-group mb-2">
                                            <label for="confirm-password">Confirm Password: </label>
                                            <input id="confirm-password" name="confirm-password" type="password" placeholder="Confirm your password" class="form-control" required/>
                                        </div>
                                </div>
                                    <div class="modal-footer">
                                        <button id="registration-form" type="submit" name="submit-btn" class="btn btn-primary">Create Account</button>
                                    </div>
                                </form>
                        </div>
                    </div>
                </div>
                <div class="container">
                    <div class="d-flex flex-wrap justify-content-between">
                        <h3 class="mb-1 me-3">Users List</h3>
                        <div class="d-flex flex-grow-1 justify-content-between align-items-center">
                            <button type="button" class="btn  btn-outline-secondary new-btn me-3" data-bs-toggle="modal" data-bs-target="#exampleModal">
                                <i class="lni lni-plus"></i>
                                <span>New User</span>
                            </button>
                            <form method="get" class="d-flex align-items-center w-100" style="max-width:300px; flex:1;">
                                <input type="search" name="search" class="form-control me-2" placeholder="Search" value="<?= htmlspecialchars($searchTerm) ?>"/> <!-- Keep the value of search term -->
                                <button type="submit" class="btn btn-outline-secondary"><i class="lni lni-search-2"></i></button> <!-- Add a search button -->
                            </form>
                        </div>
                    </div>
                    <hr>
                    <div class=" border" style="height:50dvh; border-radius:20px;">
                        <div id="list" class="list-group overflow-auto position-relative h-100">
                            <div class="d-flex justify-content-between position-sticky top-0 start-0 py-2 px-3 border-bottom" style="background:#f9f9f9; z-index:99; border-top-left-radius:20px; border-top-right-radius:20px;">
                                <button class="border-0" style="background:transparent;" onclick="reloadPage()"><i class="fa-solid fa-rotate-right"></i></button>
                                <!-- Pagination Links -->
                                <div class="pagination">
                                            
                                        <span> <?= $currentPage ?> of <?= $pages ?></span>
                                        <a href="?page-nr=1&search=<?= urlencode($searchTerm) ?>"><i class="lni lni-angle-double-left pagination-icon"></i></a>
                                    
                                        <?php if ($currentPage > 1): ?>
                                            <a href="?page-nr=<?= $currentPage - 1 ?>&search=<?= urlencode($searchTerm) ?>"><i class="fa-solid fa-chevron-left pagination-icon"></i></a>
                                        <?php else: ?>
                                            <span><i class="fa-solid fa-chevron-left"></i></span>
                                        <?php endif; ?>
                                    
                                            
                                    
                                        <?php if ($currentPage < $pages): ?>
                                            <a href="?page-nr=<?= $currentPage + 1 ?>&search=<?= urlencode($searchTerm) ?>"><i class="fa-solid fa-chevron-right pagination-icon"></i></a>
                                        <?php else: ?>
                                            <span><i class="fa-solid fa-chevron-right"></i></span>
                                        <?php endif; ?>
                                    
                                        <a href="?page-nr=<?= $pages ?>&search=<?= urlencode($searchTerm) ?>"><i class="lni lni-angle-double-right pagination-icon"></i></a>
                                    </div>
                            </div>
                            <?php if (!empty($users)): ?>
                                <?php foreach ($users as $index => $user): ?>
                                    <div class="list-group-item list-group-item-action d-flex justify-content-between flex-md-row flex-column align-items-md-center" id="<?= htmlspecialchars($user['user_id']) ?>">
                                        <div class="d-flex align-items-center">
                                            <img class="profile-logo me-2" style="padding:0 !important;"  src="https://i.pinimg.com/originals/f1/0f/f7/f10ff70a7155e5ab666bcdd1b45b726d.jpg">
                                            <div class="d-flex w-100 justify-content-between flex-column">
                                                <div class="d-flex align-items-center">
                                                    <small class="text-muted" id="created-by-name"><?= htmlspecialchars($user['user_name']) ?> </small>
                                                    <p class="m-0">&nbsp;•&nbsp;</p>
                                                    <small class="text-muted" id="position"> <strong><?= htmlspecialchars($user['user_position']) ?></strong></small>
                                                </div>
                                                <small class="text-muted" id="created-by-email"><?= htmlspecialchars($user['user_email']) ?></small>
                                            </div>
                                        </div>
                                        <div class="d-flex flex-column flex-md-row">
                                            <div class="d-flex p-2 justify-content-between">
                                                <?php if (!$user['isVerified']): ?>
                                                    <form method="POST" style="display:inline;">
                                                        <button type="button" class="border p-2 d-flex align-items-center" style="border:none; background:transparent; border-radius:10px;" onclick="verifyUser()">
                                                            <i class="fa-solid fa-circle me-2 text-secondary" style="font-size:12px;"></i>
                                                            <small class="m-0">Not Verified</small>
                                                        </button>
                                                    </form>
                                                <?php elseif (!$user['isActive']): ?>
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="user_id" value="<?= htmlspecialchars($user['user_id']); ?>">
                                                        <button type="submit" class="border p-2 d-flex align-items-center" name="action" value="activate" style="border:none; background:transparent; border-radius:10px;">
                                                            <i class="fa-solid fa-circle me-2 text-success" style="font-size:12px;"></i>
                                                            <small class="m-0">Active</small>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="user_id" value="<?= htmlspecialchars($user['user_id']); ?>">
                                                        <button type="submit" class="border p-2 d-flex align-items-center" name="action" value="deactivate" style="border:none; background:transparent; border-radius:10px;">
                                                            <i class="fa-solid fa-circle me-2 text-danger" style="font-size:12px;"></i>
                                                            <small class="m-0">Deactivate</small>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                
                                                <button type="button" class="ms-3" style="border:none; background:transparent;" onclick="confirmDelete(<?= htmlspecialchars($user['user_id']) ?>)"><i class="fa-regular fa-trash-can text-danger"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class=" w-100 h-100 d-flex align-items-center justify-content-center">No User found.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script>
        
    </script>
    <script src="./JS/reload.js"></script>
    <script src="./JS/transitions.js"></script>
    <script>
        function verifyUser(){
            Swal.fire({
                icon: 'warning',
                title: 'Verify user first',
                text: 'Please try again.'
            });
        }
    </script>
    <script src="./JS/confirmDelete_user-registration.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>