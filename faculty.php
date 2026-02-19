<?php
// admin/faculty.php
require_once '../config/database.php';

if (!isLoggedIn() || !hasRole('admin')) {
    redirect('../login.php');
}

$page_title = 'Faculty Management';
$message = '';
$error = '';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Get user_id first
    $faculty = $conn->query("SELECT user_id FROM faculty WHERE id = $id")->fetch_assoc();
    
    if ($faculty) {
        $user_id = $faculty['user_id'];
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            $conn->query("DELETE FROM faculty WHERE id = $id");
            $conn->query("DELETE FROM users WHERE id = $user_id");
            
            $conn->commit();
            $message = "Faculty deleted successfully!";
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error deleting faculty: " . $conn->error;
        }
    }
}

// Get all faculty with user details
$faculty = $conn->query("
    SELECT f.*, u.full_name, u.email, u.phone, u.address, u.created_at 
    FROM faculty f 
    JOIN users u ON f.user_id = u.id 
    ORDER BY f.faculty_id
");

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
            <h4 class="mb-0">Faculty Management</h4>
            <small class="text-muted">Manage faculty members</small>
        </div>
        
        <div>
            <a href="add_faculty.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Faculty
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
    
    <!-- Faculty Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover datatable">
                    <thead>
                        <tr>
                            <th>Faculty ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>Designation</th>
                            <th>Qualification</th>
                            <th>Experience</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($f = $faculty->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $f['faculty_id']; ?></td>
                            <td><?php echo $f['full_name']; ?></td>
                            <td><?php echo $f['email']; ?></td>
                            <td><?php echo $f['department'] ?: 'N/A'; ?></td>
                            <td><?php echo $f['designation'] ?: 'N/A'; ?></td>
                            <td><?php echo $f['qualification'] ?: 'N/A'; ?></td>
                            <td><?php echo $f['experience_years'] ?: '0'; ?> years</td>
                            <td>
                                <div class="btn-group">
                                    <a href="view_faculty.php?id=<?php echo $f['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit_faculty.php?id=<?php echo $f['id']; ?>" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button onclick="deleteFaculty(<?php echo $f['id']; ?>)" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
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

<script>
function deleteFaculty(id) {
    if (confirm('Are you sure you want to delete this faculty member? This action cannot be undone.')) {
        window.location.href = 'faculty.php?delete=' + id;
    }
}
</script>

<?php include '../includes/footer.php'; ?>