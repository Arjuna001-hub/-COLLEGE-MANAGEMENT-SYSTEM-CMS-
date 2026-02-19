<?php
// forgot-password.php
require_once 'config/database.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize($_POST['email']);
    
    $result = $conn->query("SELECT * FROM users WHERE email = '$email'");
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        // In a real application, send email here
        $message = "Password reset link has been sent to your email. (Demo: Your password is: " . $user['password'] . ")";
    } else {
        $error = "Email not found!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-4" style="margin-top: 100px;">
                <div class="card">
                    <div class="card-body p-4">
                        <h4 class="text-center mb-4">Forgot Password</h4>
                        
                        <?php if($message): ?>
                            <div class="alert alert-success"><?php echo $message; ?></div>
                        <?php endif; ?>
                        <?php if($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Reset Password</button>
                            <p class="text-center mt-3">
                                <a href="login.php">Back to Login</a>
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>