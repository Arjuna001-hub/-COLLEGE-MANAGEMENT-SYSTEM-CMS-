<?php
// login.php
require_once 'config/database.php';

// If already logged in, redirect to respective dashboard
if (isLoggedIn()) {
    redirect(getDashboardUrl());
}

$error = '';
$role = isset($_GET['role']) ? $_GET['role'] : '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $role = isset($_POST['role']) ? $_POST['role'] : '';
    
    // Debug - Remove in production
    error_log("Login attempt - Username: $username, Role: $role");
    
    if (empty($username) || empty($password) || empty($role)) {
        $error = "All fields are required!";
    } else {
        $user = loginUser($conn, $username, $password, $role);
        
        if ($user) {
            // Login successful
            $_SESSION['success'] = "Welcome back, " . $user['full_name'];
            
            // Redirect based on role
            switch ($user['role']) {
                case 'admin':
                    redirect('admin/dashboard.php');
                    break;
                case 'faculty':
                    redirect('faculty/dashboard.php');
                    break;
                case 'student':
                    redirect('student/dashboard.php');
                    break;
                default:
                    redirect('login.php');
            }
        } else {
            $error = "Invalid username or password!";
            // Debug - Remove in production
            error_log("Login failed for username: $username");
        }
    }
}

$page_title = 'Login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .role-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .role-card {
            background: white;
            border-radius: 20px;
            padding: 40px 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }
        
        .role-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        }
        
        .role-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
        }
        
        .role-card.admin::before { background: linear-gradient(90deg, #667eea, #764ba2); }
        .role-card.faculty::before { background: linear-gradient(90deg, #f093fb, #f5576c); }
        .role-card.student::before { background: linear-gradient(90deg, #4facfe, #00f2fe); }
        
        .role-icon {
            width: 100px;
            height: 100px;
            margin: 0 auto 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3em;
            color: white;
        }
        
        .role-icon.admin { background: linear-gradient(135deg, #667eea, #764ba2); }
        .role-icon.faculty { background: linear-gradient(135deg, #f093fb, #f5576c); }
        .role-icon.student { background: linear-gradient(135deg, #4facfe, #00f2fe); }
        
        .login-form-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            max-width: 400px;
            margin: 0 auto;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1em;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .login-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            cursor: pointer;
        }
        
        .back-btn:hover {
            text-decoration: underline;
        }
        
        .demo-credentials {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            font-size: 0.9em;
        }
        
        .alert {
            border-radius: 10px;
            padding: 12px 15px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="container">
            <?php if(empty($role)): ?>
                <!-- Role Selection -->
                <div class="text-center text-white mb-5">
                    <h1 class="display-4 fw-bold"><i class="fas fa-graduation-cap"></i> <?php echo SITE_NAME; ?></h1>
                    <p class="lead">Select your role to continue</p>
                </div>
                
                <div class="role-cards">
                    <div class="role-card admin" onclick="window.location.href='?role=admin'">
                        <div class="role-icon admin">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <h3>Administrator</h3>
                        <p class="text-muted">Full system access and management</p>
                        <small class="text-muted">admin / admin123</small>
                    </div>
                    
                    <div class="role-card faculty" onclick="window.location.href='?role=faculty'">
                        <div class="role-icon faculty">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <h3>Faculty</h3>
                        <p class="text-muted">Manage courses, attendance, and grades</p>
                        <small class="text-muted">john_faculty / faculty123</small>
                    </div>
                    
                    <div class="role-card student" onclick="window.location.href='?role=student'">
                        <div class="role-icon student">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <h3>Student</h3>
                        <p class="text-muted">View attendance, grades, and fee details</p>
                        <small class="text-muted">sarah_student / student123</small>
                    </div>
                </div>
            <?php else: ?>
                <!-- Login Form -->
                <div class="login-form-container">
                    <a class="back-btn" onclick="window.location.href='login.php'">
                        <i class="fas fa-arrow-left"></i> Back to roles
                    </a>
                    
                    <div class="login-header">
                        <div class="role-icon <?php echo $role; ?> mb-3" style="width: 80px; height: 80px; font-size: 2em; margin: 0 auto 15px;">
                            <?php if($role == 'admin'): ?>
                                <i class="fas fa-user-shield"></i>
                            <?php elseif($role == 'faculty'): ?>
                                <i class="fas fa-chalkboard-teacher"></i>
                            <?php else: ?>
                                <i class="fas fa-user-graduate"></i>
                            <?php endif; ?>
                        </div>
                        <h2><?php echo ucfirst($role); ?> Login</h2>
                        <p>Enter your credentials to access the dashboard</p>
                    </div>
                    
                    <?php if($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="login.php">
                        <input type="hidden" name="role" value="<?php echo $role; ?>">
                        
                        <div class="form-group">
                            <input type="text" name="username" placeholder="Username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <input type="password" name="password" placeholder="Password" required>
                        </div>
                        
                        <button type="submit" class="login-btn">
                            Login as <?php echo ucfirst($role); ?>
                        </button>
                    </form>
                    
                    <div class="demo-credentials">
                        <p class="mb-1"><strong>Demo Credentials:</strong></p>
                        <?php if($role == 'admin'): ?>
                            <p class="mb-0">Username: <strong>admin</strong> | Password: <strong>admin123</strong></p>
                        <?php elseif($role == 'faculty'): ?>
                            <p class="mb-0">Username: <strong>john_faculty</strong> | Password: <strong>faculty123</strong></p>
                            <p class="mb-0">Username: <strong>jane_faculty</strong> | Password: <strong>faculty456</strong></p>
                        <?php else: ?>
                            <p class="mb-0">Username: <strong>sarah_student</strong> | Password: <strong>student123</strong></p>
                            <p class="mb-0">Username: <strong>mike_student</strong> | Password: <strong>student456</strong></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function selectRole(role) {
            window.location.href = '?role=' + role;
        }
    </script>
</body>
</html>