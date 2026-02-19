<?php
// admin/courses.php
require_once '../config/database.php';

if (!isLoggedIn() || !hasRole('admin')) {
    redirect('../login.php');
}

$page_title = 'Course Management';
$message = '';
$error = '';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if ($conn->query("DELETE FROM courses WHERE id = $id")) {
        $message = "Course deleted successfully!";
    } else {
        $error = "Error deleting course: " . $conn->error;
    }
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    $course_code = sanitize($_POST['course_code']);
    $course_name = sanitize($_POST['course_name']);
    $credits = intval($_POST['credits']);
    $semester = intval($_POST['semester']);
    $department = sanitize($_POST['department']);
    $description = sanitize($_POST['description']);
    
    if ($action == 'add') {
        $sql = "INSERT INTO courses (course_code, course_name, credits, semester, department, description) 
                VALUES ('$course_code', '$course_name', $credits, $semester, '$department', '$description')";
        
        if ($conn->query($sql)) {
            $message = "Course added successfully!";
        } else {
            $error = "Error adding course: " . $conn->error;
        }
    } elseif ($action == 'edit') {
        $sql = "UPDATE courses SET 
                course_code = '$course_code',
                course_name = '$course_name',
                credits = $credits,
                semester = $semester,
                department = '$department',
                description = '$description'
                WHERE id = $id";
        
        if ($conn->query($sql)) {
            $message = "Course updated successfully!";
        } else {
            $error = "Error updating course: " . $conn->error;
        }
    }
}

// Get all courses
$courses = $conn->query("SELECT * FROM courses ORDER BY course_code");

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
            <h4 class="mb-0">Course Management</h4>
            <small class="text-muted">Manage courses</small>
        </div>
        
        <div>
            <button class="btn btn-primary" onclick="openAddModal()">
                <i class="fas fa-plus"></i> Add New Course
            </button>
        </div>
    </div>
    
    <!-- Messages -->
    <?php if($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <!-- Courses Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover datatable">
                    <thead>
                        <tr>
                            <th>Course Code</th>
                            <th>Course Name</th>
                            <th>Credits</th>
                            <th>Semester</th>
                            <th>Department</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($course = $courses->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $course['course_code']; ?></td>
                            <td><?php echo $course['course_name']; ?></td>
                            <td><?php echo $course['credits']; ?></td>
                            <td><?php echo $course['semester']; ?></td>
                            <td><?php echo $course['department']; ?></td>
                            <td>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-warning" onclick="editCourse(<?php echo $course['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteCourse(<?php echo $course['id']; ?>)">
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

<!-- Add/Edit Course Modal -->
<div class="modal fade" id="courseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add New Course</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" id="action" value="add">
                    <input type="hidden" name="id" id="courseId" value="">
                    
                    <div class="mb-3">
                        <label class="form-label">Course Code</label>
                        <input type="text" class="form-control" name="course_code" id="courseCode" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Course Name</label>
                        <input type="text" class="form-control" name="course_name" id="courseName" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Credits</label>
                            <input type="number" class="form-control" name="credits" id="credits" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Semester</label>
                            <input type="number" class="form-control" name="semester" id="semester" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <input type="text" class="form-control" name="department" id="department" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" id="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Course</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add New Course';
    document.getElementById('action').value = 'add';
    document.getElementById('courseId').value = '';
    document.getElementById('courseCode').value = '';
    document.getElementById('courseName').value = '';
    document.getElementById('credits').value = '';
    document.getElementById('semester').value = '';
    document.getElementById('department').value = '';
    document.getElementById('description').value = '';
    
    new bootstrap.Modal(document.getElementById('courseModal')).show();
}

function editCourse(id) {
    fetch('get_course.php?id=' + id)
        .then(response => response.json())
        .then(course => {
            document.getElementById('modalTitle').textContent = 'Edit Course';
            document.getElementById('action').value = 'edit';
            document.getElementById('courseId').value = course.id;
            document.getElementById('courseCode').value = course.course_code;
            document.getElementById('courseName').value = course.course_name;
            document.getElementById('credits').value = course.credits;
            document.getElementById('semester').value = course.semester;
            document.getElementById('department').value = course.department;
            document.getElementById('description').value = course.description;
            
            new bootstrap.Modal(document.getElementById('courseModal')).show();
        });
}

function deleteCourse(id) {
    if (confirm('Are you sure you want to delete this course?')) {
        window.location.href = 'courses.php?delete=' + id;
    }
}
</script>

<?php include '../includes/footer.php'; ?>