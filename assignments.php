<?php
// faculty/assignments.php
require_once '../config/database.php';

if (!isLoggedIn() || !hasRole('faculty')) {
    redirect('../login.php');
}

$page_title = 'Manage Assignments';
$user_id = $_SESSION['user_id'];
$faculty = getFacultyByUserId($user_id);

$message = '';
$error = '';

$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

// Handle Add Assignment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add_assignment') {
        $course_id = intval($_POST['course_id']);
        $title = sanitize($_POST['title']);
        $description = sanitize($_POST['description']);
        $due_date = $_POST['due_date'];
        $total_marks = intval($_POST['total_marks']);
        
        $sql = "INSERT INTO assignments (course_id, title, description, due_date, total_marks, created_by) 
                VALUES ($course_id, '$title', '$description', '$due_date', $total_marks, {$faculty['id']})";
        
        if ($conn->query($sql)) {
            $message = "Assignment added successfully!";
        } else {
            $error = "Error adding assignment: " . $conn->error;
        }
    }
    
    // Handle Delete Assignment
    if ($_POST['action'] == 'delete_assignment') {
        $id = intval($_POST['assignment_id']);
        if ($conn->query("DELETE FROM assignments WHERE id = $id")) {
            $message = "Assignment deleted successfully!";
        } else {
            $error = "Error deleting assignment: " . $conn->error;
        }
    }
}

// Get faculty's courses for dropdown
$courses = $conn->query("
    SELECT c.* 
    FROM courses c
    JOIN course_assignments ca ON c.id = ca.course_id
    WHERE ca.faculty_id = {$faculty['id']}
");

// Get assignments for selected course
$assignments = [];
if ($course_id) {
    $assignments = $conn->query("
        SELECT a.*, 
               COUNT(s.id) as total_submissions,
               SUM(CASE WHEN s.status = 'graded' THEN 1 ELSE 0 END) as graded_count
        FROM assignments a
        LEFT JOIN submissions s ON a.id = s.assignment_id
        WHERE a.course_id = $course_id
        GROUP BY a.id
        ORDER BY a.due_date DESC
    ");
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
            <h4 class="mb-0">Manage Assignments</h4>
            <small class="text-muted">Create and manage course assignments</small>
        </div>
    </div>
    
    <!-- Messages -->
    <?php if($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <!-- Course Selection -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <form method="GET" class="d-flex gap-2">
                        <select class="form-select" name="course_id" required>
                            <option value="">Select Course</option>
                            <?php while($course = $courses->fetch_assoc()): ?>
                            <option value="<?php echo $course['id']; ?>" <?php echo $course_id == $course['id'] ? 'selected' : ''; ?>>
                                <?php echo $course['course_name']; ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                        <button type="submit" class="btn btn-primary">View Assignments</button>
                    </form>
                </div>
                <?php if($course_id): ?>
                <div class="col-md-6 text-end">
                    <button class="btn btn-success" onclick="openAddAssignmentModal()">
                        <i class="fas fa-plus"></i> Add New Assignment
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php if($course_id): ?>
    <!-- Assignments List -->
    <div class="row">
        <?php if($assignments && $assignments->num_rows > 0): ?>
            <?php while($assignment = $assignments->fetch_assoc()): 
                $is_overdue = strtotime($assignment['due_date']) < time();
            ?>
            <div class="col-md-6 mb-4">
                <div class="card h-100 <?php echo $is_overdue ? 'border-danger' : ''; ?>">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><?php echo $assignment['title']; ?></h5>
                        <span class="badge bg-<?php echo $is_overdue ? 'danger' : 'success'; ?>">
                            <?php echo $is_overdue ? 'Overdue' : 'Active'; ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <p class="card-text"><?php echo nl2br($assignment['description']); ?></p>
                        
                        <div class="row mt-3">
                            <div class="col-6">
                                <small class="text-muted d-block">
                                    <i class="fas fa-calendar"></i> Due: <?php echo formatDate($assignment['due_date']); ?>
                                </small>
                                <small class="text-muted d-block">
                                    <i class="fas fa-star"></i> Total Marks: <?php echo $assignment['total_marks']; ?>
                                </small>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">
                                    <i class="fas fa-users"></i> Submissions: <?php echo $assignment['total_submissions']; ?>
                                </small>
                                <small class="text-muted d-block">
                                    <i class="fas fa-check-circle"></i> Graded: <?php echo $assignment['graded_count']; ?>
                                </small>
                            </div>
                        </div>
                        
                        <div class="progress mt-3" style="height: 5px;">
                            <?php 
                            $progress = $assignment['total_submissions'] > 0 ? 
                                ($assignment['graded_count'] / $assignment['total_submissions']) * 100 : 0;
                            ?>
                            <div class="progress-bar bg-success" style="width: <?php echo $progress; ?>%"></div>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent">
                        <div class="btn-group">
                            <a href="view_submissions.php?assignment_id=<?php echo $assignment['id']; ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-eye"></i> View Submissions
                            </a>
                            <a href="edit_assignment.php?id=<?php echo $assignment['id']; ?>" class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <button class="btn btn-sm btn-danger" onclick="deleteAssignment(<?php echo $assignment['id']; ?>)">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No assignments found for this course. Click "Add New Assignment" to create one.
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Add Assignment Modal -->
<div class="modal fade" id="addAssignmentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Assignment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_assignment">
                    <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Assignment Title</label>
                        <input type="text" class="form-control" name="title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="5" required></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Due Date</label>
                            <input type="date" class="form-control" name="due_date" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Total Marks</label>
                            <input type="number" class="form-control" name="total_marks" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Assignment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this assignment? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <form method="POST" id="deleteForm">
                    <input type="hidden" name="action" value="delete_assignment">
                    <input type="hidden" name="assignment_id" id="delete_assignment_id">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function openAddAssignmentModal() {
    new bootstrap.Modal(document.getElementById('addAssignmentModal')).show();
}

function deleteAssignment(id) {
    document.getElementById('delete_assignment_id').value = id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php include '../includes/footer.php'; ?>