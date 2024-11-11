<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="./CSS/verify.css" rel="stylesheet"/>
        <link rel="icon" href="./assets/logo.png" type="image/x-icon">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <title>Verify</title>
    </head>
    <body>
        <nav class="shadow p-4 d-flex bg-light">
            <img src="./assets/logo.png" style="width: 30px;">
            <h1 class="mb-0" style="font-size: 1.25rem;">SK Calamba Verify</h1>
        </nav>
        <div class="container p-4 d-flex justify-content-center" >
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" class="d-flex flex-column shadow p-4 mt-5 bg-light" style="border-radius: 20px; width: 100%; max-width: 500px;">
                <input type="number" name="code" placeholder="Code" class="mb-3 border-0 ps-3 pt-2 pb-2 pe-3" style="border-radius: 10px;outline: none;" required/>
                <input type="submit" value="Submit" class="mb-3 border-0 ps-3 pt-2 pb-2 pe-3 btn btn-primary"/>
            </form>
        </div>
        <!--<form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">-->
        <!--    <input type="number" name="code" placeholder="Code"/>-->
        <!--    <input type="submit" value="Submit"/>-->
        <!--</form>-->
    </body>
</html>
<?php
    require "db.php";
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $verificationCode = $_POST['code'];
        if(!isset($verificationCode) || empty($verificaitonCode)){
            echo'Please enter the code';
        }
    
        $sql = "SELECT * FROM sk_users WHERE verification_code = ? AND isVerified = 0";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $verificationCode);
        $stmt->execute();
        $result = $stmt->get_result();
    
        if ($result->num_rows > 0) {
                $sql = "UPDATE sk_users SET isVerified = 1 WHERE verification_code = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $verificationCode);
        
                if ($stmt->execute()) {
                    echo "<p>User Account verified successfully! You can now sign in to the app.</p>";
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