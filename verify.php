<!DOCTYPE html>
<html lang="en">
    <head>
        <link href="./CSS/verify.css" rel="stylesheet"/>
    </head>
    <body>
        <div class="form">
             <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                <input type="number" name="code" placeholder="Code"/>
                <input type="submit" value="Submit"/>
            </form>
        </div>
       
    </body>
</html>
<?php
    require "db.php";
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $verificationCode = $_POST['code'];
        if(!isset($verificationCode) || empty($verificaitonCode)){
            echo'Please enter the code';
        }
    
        $sql = "SELECT * FROM sk_admin WHERE verification_code = ? AND isVerified = 0";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $verificationCode);
        $stmt->execute();
        $result = $stmt->get_result();
    
        if ($result->num_rows > 0) {
                $sql = "UPDATE sk_admin SET isVerified = 1 WHERE verification_code = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $verificationCode);
        
                if ($stmt->execute()) {
                    echo "<p>Admin Account verified successfully! You can now <a href='SignIn.php'>Sign In</a>.</p>";
                } else {
                    echo "<p>Error: Could not verify your account. Please try again later.</p>";
                }
            
            
        } else {
            echo "<p>Invalid or expired verification code.</p>";
        }
    
        $stmt->close();
    }
    
    $conn->close();
?>
