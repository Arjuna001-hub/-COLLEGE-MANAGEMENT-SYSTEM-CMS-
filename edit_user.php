<?php
// admin/edit_user.php
require_once '../config/database.php';

if (!isLoggedIn() || !hasRole('admin')) {
    redirect('../login.php');
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user = getUserById($id);

if (!$user) {
    redirect('users.php');
}

$page_title = 'Edit User - ' . $user['full_name'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $full_name = sanitize($_POST['full_name']);
    $role = sanitize($_POST['role']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    
    $sql = "UPDATE users SET 
            username = '$username',
            email = '$email',
            full_name = '$full_name',
            role = '$role',
            phone = '$phone',
            address = '$address'
            WHERE id = $id";
    
    if (!empty($_POST['password'])) {
        $password = $_POST['password'];
        $sql = "UPDATE users SET 
                username = '$username',
                password = '$password',
                email = '$email',
                full_name = '$full_name',
                role = '$role',
                phone = '$phone',
                address = '$address'
                WHERE id = $id";
    }
    
    if ($conn->query($sql)) {
        $message = "User updated successfully!";
        $user = getUserById($id); // Refresh user data
    } else {
        $error = "Error updating user: " . $conn->error;
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
            <h4 class="mb-0">Edit User</h4>
            <small class="text-muted">Update user information</small>
        </div>
        
        <div>
            <a href="users.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Users
            </a>
            <a href="view_user.php?id=<?php echo $id; ?>" class="btn btn-info">
                <i class="fas fa-eye"></i> View User
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
    
    <!-- Edit User Form -->
    <div class="card">
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Username *</label>
                        <input type="text" class="form-control" name="username" value="<?php echo $user['username']; ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password">
                        <small class="text-muted">Leave blank to keep current password</small>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Full Name *</label>
                        <input type="text" class="form-control" name="full_name" value="<?php echo $user['full_name']; ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" class="form-control" name="email" value="<?php echo $user['email']; ?>" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Role *</label>
                        <select class="form-select" name="role" required>
                            <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                            <option value="faculty" <?php echo $user['role'] == 'faculty' ? 'selected' : ''; ?>>Faculty</option>
                            <option value="student" <?php echo $user['role'] == 'student' ? 'selected' : ''; ?>>Student</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" name="phone" value="<?php echo $user['phone']; ?>">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <textarea class="form-control" name="address" rows="2"><?php echo $user['address']; ?></textarea>
                </div>
                
                <div class="text-end">
                    <a href="users.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>