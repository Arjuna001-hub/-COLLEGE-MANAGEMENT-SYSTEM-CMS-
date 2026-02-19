<?php
// register.php
require_once 'config/database.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    $email = sanitize($_POST['email']);
    $full_name = sanitize($_POST['full_name']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    $role = 'student'; // Default role for registration
    
    // Check if username exists
    $check = $conn->query("SELECT id FROM users WHERE username = '$username'");
    if ($check->num_rows > 0) {
        $error = "Username already exists!";
    } else {
        // Insert user
        $sql = "INSERT INTO users (username, password, email, role, full_name, phone, address) 
                VALUES ('$username', '$password', '$email', '$role', '$full_name', '$phone', '$address')";
        
        if ($conn->query($sql)) {
            $user_id = $conn->insert_id;
            $student_id = generateUniqueId('STU', 'students', 'student_id');
            
            $sql = "INSERT INTO students (user_id, student_id) VALUES ($user_id, '$student_id')";
            $conn->query($sql);
            
            $success = "Registration successful! Your Student ID is: $student_id. You can now login.";
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', sans-serif;
        }
        .register-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .register-form {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            max-width: 600px;
            width: 100%;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
        }
        .register-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-form">
            <h2 class="text-center mb-4">Student Registration</h2>
            
            <?php if($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" onsubmit="return validateForm()">
                <div class="row">
                    <div class="col-md-6 form-group">
                        <input type="text" name="username" placeholder="Username" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <input type="password" name="password" id="password" placeholder="Password" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 form-group">
                        <input type="password" id="confirm_password" placeholder="Confirm Password" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <input type="email" name="email" placeholder="Email" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <input type="text" name="full_name" placeholder="Full Name" required>
                </div>
                
                <div class="row">
                    <div class="col-md-6 form-group">
                        <input type="text" name="phone" placeholder="Phone">
                    </div>
                    <div class="col-md-6 form-group">
                        <input type="text" name="address" placeholder="Address">
                    </div>
                </div>
                
                <button type="submit" class="register-btn">Register</button>
                
                <p class="text-center mt-3">
                    Already have an account? <a href="login.php">Login here</a>
                </p>
            </form>
        </div>
    </div>
    
    <script>
    function validateForm() {
        var password = document.getElementById('password').value;
        var confirm = document.getElementById('confirm_password').value;
        if (password != confirm) {
            alert('Passwords do not match!');
            return false;
        }
        return true;
    }
    </script>
</body>
</html>