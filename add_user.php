<?php
// admin/add_user.php
require_once '../config/database.php';

if (!isLoggedIn() || !hasRole('admin')) {
    redirect('../login.php');
}

$page_title = 'Add User';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    $email = sanitize($_POST['email']);
    $full_name = sanitize($_POST['full_name']);
    $role = sanitize($_POST['role']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    
    // Check if username exists
    $check = $conn->query("SELECT id FROM users WHERE username = '$username'");
    if ($check->num_rows > 0) {
        $error = "Username already exists!";
    } else {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Insert into users table
            $sql = "INSERT INTO users (username, password, email, role, full_name, phone, address) 
                    VALUES ('$username', '$password', '$email', '$role', '$full_name', '$phone', '$address')";
            $conn->query($sql);
            $user_id = $conn->insert_id;
            
            // Create role-specific record
            if ($role == 'student') {
                $student_id = generateUniqueId('STU', 'students', 'student_id');
                $sql = "INSERT INTO students (user_id, student_id) VALUES ($user_id, '$student_id')";
                $conn->query($sql);
                $message = "Student added successfully! Student ID: " . $student_id;
            } elseif ($role == 'faculty') {
                $faculty_id = generateUniqueId('FAC', 'faculty', 'faculty_id');
                $sql = "INSERT INTO faculty (user_id, faculty_id) VALUES ($user_id, '$faculty_id')";
                $conn->query($sql);
                $message = "Faculty added successfully! Faculty ID: " . $faculty_id;
            } else {
                $message = "Admin added successfully!";
            }
            
            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error adding user: " . $conn->error;
        }
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content" id="mainContent">
    <!-- Top Bar -->
    <div class="top-bar">
        <button class="menu-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        
        <div>
            <h4 class="mb-0">Add New User</h4>
            <small class="text-muted">Create a new user account</small>
        </div>
        
        <div>
            <a href="users.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Users
            </a>
        </div>
    </div>
    
    <!-- Messages -->
    <?php if($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <!-- Add User Form -->
    <div class="card">
        <div class="card-body">
            <form method="POST" onsubmit="return validateForm()">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Username *</label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Password *</label>
                        <input type="password" class="form-control" name="password" id="password" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Confirm Password *</label>
                        <input type="password" class="form-control" id="confirm_password" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Full Name *</label>
                        <input type="text" class="form-control" name="full_name" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Role *</label>
                        <select class="form-select" name="role" required>
                            <option value="">Select Role</option>
                            <option value="admin">Admin</option>
                            <option value="faculty">Faculty</option>
                            <option value="student">Student</option>
                        </select>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" name="phone">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Address</label>
                        <input type="text" class="form-control" name="address">
                    </div>
                </div>
                
                <div class="text-end">
                    <button type="reset" class="btn btn-secondary">Reset</button>
                    <button type="submit" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
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

<?php include '../includes/footer.php'; ?>