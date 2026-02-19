<?php
// admin/students.php
require_once '../config/database.php';

if (!isLoggedIn() || !hasRole('admin')) {
    redirect('../login.php');
}

$page_title = 'Student Management';
$message = '';
$error = '';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Get user_id first
    $student = $conn->query("SELECT user_id FROM students WHERE id = $id")->fetch_assoc();
    
    if ($student) {
        $user_id = $student['user_id'];
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            $conn->query("DELETE FROM students WHERE id = $id");
            $conn->query("DELETE FROM users WHERE id = $user_id");
            
            $conn->commit();
            $message = "Student deleted successfully!";
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error deleting student: " . $conn->error;
        }
    }
}

// Get all students with user details
$students = $conn->query("
    SELECT s.*, u.full_name, u.email, u.phone, u.address, u.created_at 
    FROM students s 
    JOIN users u ON s.user_id = u.id 
    ORDER BY s.student_id
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
            <h4 class="mb-0">Student Management</h4>
            <small class="text-muted">Manage student records</small>
        </div>
        
        <div>
            <a href="add_student.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Student
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
    
    <!-- Students Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover datatable">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Course</th>
                            <th>Semester</th>
                            <th>Parent Name</th>
                            <th>Parent Phone</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($student = $students->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $student['student_id']; ?></td>
                            <td><?php echo $student['full_name']; ?></td>
                            <td><?php echo $student['email']; ?></td>
                            <td><?php echo $student['course'] ?: 'Not assigned'; ?></td>
                            <td><?php echo $student['semester'] ?: 'N/A'; ?></td>
                            <td><?php echo $student['parent_name'] ?: 'N/A'; ?></td>
                            <td><?php echo $student['parent_phone'] ?: 'N/A'; ?></td>
                            <td>
                                <div class="btn-group">
                                    <a href="view_student.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit_student.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button onclick="deleteStudent(<?php echo $student['id']; ?>)" class="btn btn-sm btn-danger">
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
function deleteStudent(id) {
    if (confirm('Are you sure you want to delete this student? This action cannot be undone.')) {
        window.location.href = 'students.php?delete=' + id;
    }
}
</script>

<?php include '../includes/footer.php'; ?>