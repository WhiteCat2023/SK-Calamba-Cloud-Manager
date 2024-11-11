<?php
    session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./CSS/SignIn.css">
    <!--<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">-->
    <script src="https://kit.fontawesome.com/d00a4a393c.js" crossorigin="anonymous"></script>

    <title>Login</title>
</head>
<body>
    <div class="container">
        <div class="login">
            <img class="logo" src="./assets/logo.png">
            <h1>Sign In</h1>
            <form action="<?php htmlspecialchars($_SERVER["PHP_SELF"])?>" method="post">
                <div class="input-container">
                    <input type="email" name="username" placeholder="Username">
                    <i class="fa-solid fa-at icon"></i>
                </div>
    
                <div class="input-container">
                    <input type="password" name="password" placeholder="Password">
                    <i class="fa-solid fa-lock icon"></i>
                </div>    
                <button type="submit">Login</button>
            </form>
            <?php
                require "db.php";
                
                if($_SERVER['REQUEST_METHOD'] === 'POST'){
                    
                    $email = $_POST['username'];
                    $password = $_POST['password'];
                    
                    if(empty($email)){
                        echo"";
                    }elseif(empty($password)){
                        echo"";
                    }else{
                        $sql = "SELECT * FROM `sk_users` WHERE user_email = ? AND role = 'admin'";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("s", $email);
                        if($stmt->execute()){
                            $result = $stmt->get_result();
                            if($result->num_rows > 0){
                                $user = $result->fetch_assoc();
                                if($user['isVerified'] === 1){
                                    if(password_verify($password, $user['user_password']) || $password === $user['user_password']){
                                        echo "<p>Login successful! Welcome, " . htmlspecialchars($user['user_email']) . "</p>";
                                            $_SESSION['name'] = $user['user_name'];
                                            $_SESSION['adminId'] = $user['user_id'];
                                            $_SESSION['email'] = $user['user_email'];
                                            $_SESSION['position'] = $user['user_position'];
                                            $_SESSION['loggedin'] = true;
                                        header('Location: Announcements.php');
                                    }else{
                                        echo "<p>Incorrect password.</p>";
                                    }
                                }else{
                                    echo "<p>Your email is not verified. Please check your inbox to verify your email address.</p>";
                                }
                                
                            }else{
                                echo "<p>No user found with that email address.</p>";
                            }
                            
                        }else{
                            echo "<p>Failed to execute query.</p>";
                        }
                    }
                }
                
            ?>
        </div>
    </div>
</body>
</html>