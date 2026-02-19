<?php
// admin/users.php
require_once '../config/database.php';

if (!isLoggedIn() || !hasRole('admin')) {
    redirect('../login.php');
}

$page_title = 'User Management';
$message = '';
$error = '';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Get user role before deleting
        $user = getUserById($id);
        
        // Delete role-specific records first
        if ($user['role'] == 'student') {
            $conn->query("DELETE FROM students WHERE user_id = $id");
        } elseif ($user['role'] == 'faculty') {
            $conn->query("DELETE FROM faculty WHERE user_id = $id");
        }
        
        // Delete user
        $conn->query("DELETE FROM users WHERE id = $id");
        
        $conn->commit();
        $message = "User deleted successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error deleting user: " . $conn->error;
    }
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $full_name = sanitize($_POST['full_name']);
    $role = sanitize($_POST['role']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    
    if ($action == 'add') {
        $password = $_POST['password'];
        
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
                
                // Create role-specific record
                if ($role == 'student') {
                    $student_id = generateUniqueId('STU', 'students', 'student_id');
                    $conn->query("INSERT INTO students (user_id, student_id) VALUES ($user_id, '$student_id')");
                } elseif ($role == 'faculty') {
                    $faculty_id = generateUniqueId('FAC', 'faculty', 'faculty_id');
                    $conn->query("INSERT INTO faculty (user_id, faculty_id) VALUES ($user_id, '$faculty_id')");
                }
                
                $message = "User added successfully!";
            } else {
                $error = "Error adding user: " . $conn->error;
            }
        }
    } elseif ($action == 'edit') {
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
        } else {
            $error = "Error updating user: " . $conn->error;
        }
    }
}

// Get all users
$users = $conn->query("SELECT * FROM users ORDER BY created_at DESC");

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
            <h4 class="mb-0">User Management</h4>
            <small class="text-muted">Manage system users</small>
        </div>
        
        <div>
            <button class="btn btn-primary" onclick="openAddModal()">
                <i class="fas fa-plus"></i> Add New User
            </button>
        </div>
    </div>
    
    <!-- Messages -->
    <?php if($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <!-- Users Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover datatable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Phone</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($user = $users->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo $user['username']; ?></td>
                            <td><?php echo $user['full_name']; ?></td>
                            <td><?php echo $user['email']; ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $user['role'] == 'admin' ? 'danger' : 
                                        ($user['role'] == 'faculty' ? 'warning' : 'success'); 
                                ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td><?php echo $user['phone']; ?></td>
                            <td><?php echo formatDate($user['created_at']); ?></td>
                            <td>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-warning" onclick="editUser(<?php echo $user['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteUser(<?php echo $user['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <a href="view_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit User Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="userForm">
                <div class="modal-body">
                    <input type="hidden" name="action" id="action" value="add">
                    <input type="hidden" name="id" id="userId" value="">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" id="username" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" id="password">
                            <small class="text-muted">Leave blank to keep current password when editing</small>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="full_name" id="fullName" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="email" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role" id="role" required>
                                <option value="">Select Role</option>
                                <option value="admin">Admin</option>
                                <option value="faculty">Faculty</option>
                                <option value="student">Student</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="phone" id="phone">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea class="form-control" name="address" id="address" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add New User';
    document.getElementById('action').value = 'add';
    document.getElementById('userId').value = '';
    document.getElementById('username').value = '';
    document.getElementById('password').value = '';
    document.getElementById('fullName').value = '';
    document.getElementById('email').value = '';
    document.getElementById('role').value = '';
    document.getElementById('phone').value = '';
    document.getElementById('address').value = '';
    document.getElementById('password').required = true;
    
    new bootstrap.Modal(document.getElementById('userModal')).show();
}

function editUser(id) {
    fetch('get_user.php?id=' + id)
        .then(response => response.json())
        .then(user => {
            document.getElementById('modalTitle').textContent = 'Edit User';
            document.getElementById('action').value = 'edit';
            document.getElementById('userId').value = user.id;
            document.getElementById('username').value = user.username;
            document.getElementById('password').value = '';
            document.getElementById('fullName').value = user.full_name;
            document.getElementById('email').value = user.email;
            document.getElementById('role').value = user.role;
            document.getElementById('phone').value = user.phone;
            document.getElementById('address').value = user.address;
            document.getElementById('password').required = false;
            
            new bootstrap.Modal(document.getElementById('userModal')).show();
        });
}

function deleteUser(id) {
    if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
        window.location.href = 'users.php?delete=' + id;
    }
}
</script>

<?php include '../includes/footer.php'; ?>