<?php
// faculty/mark_attendance.php
require_once '../config/database.php';

if (!isLoggedIn() || !hasRole('faculty')) {
    redirect('../login.php');
}

$page_title = 'Mark Attendance';
$user_id = $_SESSION['user_id'];
$faculty = getFacultyByUserId($user_id);

$message = '';
$error = '';

// Get course if selected
$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Handle attendance submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $course_id = intval($_POST['course_id']);
    $date = $_POST['date'];
    
    // Check if attendance already marked for this date
    $check = $conn->query("SELECT id FROM attendance WHERE course_id = $course_id AND date = '$date' LIMIT 1");
    if ($check->num_rows > 0) {
        $error = "Attendance already marked for this date!";
    } else {
        $success_count = 0;
        foreach ($_POST['attendance'] as $student_id => $status) {
            $sql = "INSERT INTO attendance (student_id, course_id, date, status, marked_by) 
                    VALUES ($student_id, $course_id, '$date', '$status', {$faculty['id']})";
            if ($conn->query($sql)) {
                $success_count++;
            }
        }
        $message = "Attendance marked for $success_count students!";
    }
}

// Get students for the course
$students = [];
if ($course_id) {
    $students = $conn->query("
        SELECT s.id, s.student_id, u.full_name 
        FROM students s
        JOIN student_courses sc ON s.id = sc.student_id
        JOIN users u ON s.user_id = u.id
        WHERE sc.course_id = $course_id AND sc.status = 'enrolled'
        ORDER BY u.full_name
    ");
}

// Get faculty's courses for dropdown
$courses = $conn->query("
    SELECT c.* 
    FROM courses c
    JOIN course_assignments ca ON c.id = ca.course_id
    WHERE ca.faculty_id = {$faculty['id']}
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
            <h4 class="mb-0">Mark Attendance</h4>
            <small class="text-muted">Record student attendance</small>
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
    
    <!-- Course Selection -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-5">
                    <label class="form-label">Select Course</label>
                    <select class="form-select" name="course_id" required>
                        <option value="">Choose a course...</option>
                        <?php while($course = $courses->fetch_assoc()): ?>
                        <option value="<?php echo $course['id']; ?>" <?php echo $course_id == $course['id'] ? 'selected' : ''; ?>>
                            <?php echo $course['course_code'] . ' - ' . $course['course_name']; ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Date</label>
                    <input type="date" class="form-control" name="date" value="<?php echo $date; ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">Load Students</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Attendance Form -->
    <?php if($course_id && $students && $students->num_rows > 0): ?>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Mark Attendance for <?php echo formatDate($date); ?></h5>
            <div>
                <button class="btn btn-sm btn-success" onclick="markAllPresent()">All Present</button>
                <button class="btn btn-sm btn-danger" onclick="markAllAbsent()">All Absent</button>
            </div>
        </div>
        <div class="card-body">
            <form method="POST" id="attendanceForm">
                <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
                <input type="hidden" name="date" value="<?php echo $date; ?>">
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Student ID</th>
                                <th>Student Name</th>
                                <th>Attendance Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $count = 1;
                            while($student = $students->fetch_assoc()): 
                            ?>
                            <tr>
                                <td><?php echo $count++; ?></td>
                                <td><?php echo $student['student_id']; ?></td>
                                <td><?php echo $student['full_name']; ?></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <input type="radio" class="btn-check" 
                                               name="attendance[<?php echo $student['id']; ?>]" 
                                               id="present_<?php echo $student['id']; ?>" 
                                               value="present" checked>
                                        <label class="btn btn-outline-success btn-sm" for="present_<?php echo $student['id']; ?>">
                                            Present
                                        </label>
                                        
                                        <input type="radio" class="btn-check" 
                                               name="attendance[<?php echo $student['id']; ?>]" 
                                               id="absent_<?php echo $student['id']; ?>" 
                                               value="absent">
                                        <label class="btn btn-outline-danger btn-sm" for="absent_<?php echo $student['id']; ?>">
                                            Absent
                                        </label>
                                        
                                        <input type="radio" class="btn-check" 
                                               name="attendance[<?php echo $student['id']; ?>]" 
                                               id="late_<?php echo $student['id']; ?>" 
                                               value="late">
                                        <label class="btn btn-outline-warning btn-sm" for="late_<?php echo $student['id']; ?>">
                                            Late
                                        </label>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="text-end mt-3">
                    <button type="submit" class="btn btn-primary">Save Attendance</button>
                </div>
            </form>
        </div>
    </div>
    <?php elseif($course_id): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i> No students enrolled in this course.
    </div>
    <?php endif; ?>
</div>

<script>
function markAllPresent() {
    document.querySelectorAll('input[value="present"]').forEach(radio => {
        radio.checked = true;
    });
}

function markAllAbsent() {
    document.querySelectorAll('input[value="absent"]').forEach(radio => {
        radio.checked = true;
    });
}
</script>

<?php include '../includes/footer.php'; ?>