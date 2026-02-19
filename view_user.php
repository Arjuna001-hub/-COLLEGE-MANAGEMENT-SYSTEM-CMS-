<?php
// admin/view_user.php
require_once '../config/database.php';

if (!isLoggedIn() || !hasRole('admin')) {
    redirect('../login.php');
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user = getUserById($id);

if (!$user) {
    redirect('users.php');
}

// Get role-specific details
$role_details = null;
if ($user['role'] == 'student') {
    $result = $conn->query("SELECT * FROM students WHERE user_id = $id");
    $role_details = $result->fetch_assoc();
} elseif ($user['role'] == 'faculty') {
    $result = $conn->query("SELECT * FROM faculty WHERE user_id = $id");
    $role_details = $result->fetch_assoc();
}

$page_title = 'View User - ' . $user['full_name'];

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
            <h4 class="mb-0">User Details</h4>
            <small class="text-muted">View user information</small>
        </div>
        
        <div>
            <a href="users.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Users
            </a>
            <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-warning">
                <i class="fas fa-edit"></i> Edit User
            </a>
        </div>
    </div>
    
    <!-- User Details -->
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="user-avatar mx-auto mb-3" style="width: 100px; height: 100px; font-size: 3em;">
                        <?php echo substr($user['full_name'], 0, 1); ?>
                    </div>
                    <h4><?php echo $user['full_name']; ?></h4>
                    <p class="text-muted"><?php echo ucfirst($user['role']); ?></p>
                    <span class="badge bg-<?php 
                        echo $user['role'] == 'admin' ? 'danger' : 
                            ($user['role'] == 'faculty' ? 'warning' : 'success'); 
                    ?>"><?php echo ucfirst($user['role']); ?></span>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Personal Information</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <tr>
                            <th style="width: 200px;">Username:</th>
                            <td><?php echo $user['username']; ?></td>
                        </tr>
                        <tr>
                            <th>Email:</th>
                            <td><?php echo $user['email']; ?></td>
                        </tr>
                        <tr>
                            <th>Phone:</th>
                            <td><?php echo $user['phone'] ?: 'Not provided'; ?></td>
                        </tr>
                        <tr>
                            <th>Address:</th>
                            <td><?php echo $user['address'] ?: 'Not provided'; ?></td>
                        </tr>
                        <tr>
                            <th>Joined:</th>
                            <td><?php echo formatDate($user['created_at'], 'd M Y, h:i A'); ?></td>
                        </tr>
                        <tr>
                            <th>Last Updated:</th>
                            <td><?php echo formatDate($user['updated_at'], 'd M Y, h:i A'); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <?php if($role_details): ?>
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo ucfirst($user['role']); ?> Details</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <?php foreach($role_details as $key => $value): ?>
                            <?php if(!in_array($key, ['id', 'user_id'])): ?>
                            <tr>
                                <th style="width: 200px;"><?php echo ucwords(str_replace('_', ' ', $key)); ?>:</th>
                                <td><?php echo $value ?: 'Not provided'; ?></td>
                            </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>