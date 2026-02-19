<?php
// faculty/grades.php
require_once '../config/database.php';

if (!isLoggedIn() || !hasRole('faculty')) {
    redirect('../login.php');
}

$page_title = 'Manage Grades';
$user_id = $_SESSION['user_id'];
$faculty = getFacultyByUserId($user_id);

$message = '';
$error = '';

$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
$assignment_id = isset($_GET['assignment_id']) ? intval($_GET['assignment_id']) : 0;

// Handle grade submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'save_grades') {
        $submission_id = intval($_POST['submission_id']);
        $marks_obtained = intval($_POST['marks_obtained']);
        $feedback = sanitize($_POST['feedback']);
        
        $sql = "UPDATE submissions SET 
                marks_obtained = $marks_obtained,
                feedback = '$feedback',
                status = 'graded'
                WHERE id = $submission_id";
        
        if ($conn->query($sql)) {
            $message = "Grade saved successfully!";
        } else {
            $error = "Error saving grade: " . $conn->error;
        }
    }
}

// Get faculty's courses
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
        SELECT * FROM assignments 
        WHERE course_id = $course_id 
        ORDER BY due_date DESC
    ");
}

// Get submissions for selected assignment
$submissions = [];
if ($assignment_id) {
    $submissions = $conn->query("
        SELECT s.*, u.full_name as student_name, s.student_id,
               a.title as assignment_title, a.total_marks
        FROM submissions s
        JOIN students st ON s.student_id = st.id
        JOIN users u ON st.user_id = u.id
        JOIN assignments a ON s.assignment_id = a.id
        WHERE s.assignment_id = $assignment_id
        ORDER BY u.full_name
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
            <h4 class="mb-0">Manage Grades</h4>
            <small class="text-muted">Grade student submissions</small>
        </div>
    </div>
    
    <!-- Messages -->
    <?php if($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <!-- Selection Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-5">
                    <label class="form-label">Select Course</label>
                    <select class="form-select" name="course_id" required onchange="this.form.submit()">
                        <option value="">Choose Course</option>
                        <?php while($course = $courses->fetch_assoc()): ?>
                        <option value="<?php echo $course['id']; ?>" <?php echo $course_id == $course['id'] ? 'selected' : ''; ?>>
                            <?php echo $course['course_name']; ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Select Assignment</label>
                    <select class="form-select" name="assignment_id" <?php echo !$course_id ? 'disabled' : ''; ?> required>
                        <option value="">Choose Assignment</option>
                        <?php 
                        if($course_id) {
                            $assignments->data_seek(0);
                            while($assignment = $assignments->fetch_assoc()): 
                        ?>
                        <option value="<?php echo $assignment['id']; ?>" <?php echo $assignment_id == $assignment['id'] ? 'selected' : ''; ?>>
                            <?php echo $assignment['title']; ?> (Due: <?php echo formatDate($assignment['due_date']); ?>)
                        </option>
                        <?php 
                            endwhile;
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">Load</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Grade Entry Table -->
    <?php if($assignment_id && $submissions && $submissions->num_rows > 0): ?>
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Grade Submissions</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>Submission Date</th>
                            <th>Status</th>
                            <th>Marks</th>
                            <th>Feedback</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($sub = $submissions->fetch_assoc()): ?>
                        <tr>
                            <form method="POST" class="grade-form">
                                <input type="hidden" name="action" value="save_grades">
                                <input type="hidden" name="submission_id" value="<?php echo $sub['id']; ?>">
                                
                                <td><?php echo $sub['student_id']; ?></td>
                                <td><?php echo $sub['student_name']; ?></td>
                                <td><?php echo formatDate($sub['submission_date']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $sub['status'] == 'graded' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($sub['status']); ?>
                                    </span>
                                </td>
                                <td style="width: 150px;">
                                    <div class="input-group">
                                        <input type="number" class="form-control form-control-sm" 
                                               name="marks_obtained" value="<?php echo $sub['marks_obtained']; ?>" 
                                               min="0" max="<?php echo $sub['total_marks']; ?>" required>
                                        <span class="input-group-text">/<?php echo $sub['total_marks']; ?></span>
                                    </div>
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm" 
                                           name="feedback" value="<?php echo $sub['feedback']; ?>">
                                </td>
                                <td>
                                    <button type="submit" class="btn btn-sm btn-success">
                                        <i class="fas fa-save"></i> Save
                                    </button>
                                </td>
                            </form>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php elseif($assignment_id): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> No submissions found for this assignment.
    </div>
    <?php endif; ?>
</div>

<script>
// Auto-save functionality
document.querySelectorAll('.grade-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('save_grade.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Grade saved successfully!', 'success');
            } else {
                showToast('Error saving grade: ' + data.error, 'danger');
            }
        })
        .catch(error => {
            showToast('Error saving grade', 'danger');
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>