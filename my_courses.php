<?php
// faculty/my_courses.php
require_once '../config/database.php';

if (!isLoggedIn() || !hasRole('faculty')) {
    redirect('../login.php');
}

$page_title = 'My Courses';
$user_id = $_SESSION['user_id'];
$faculty = getFacultyByUserId($user_id);

// Get all assigned courses
$courses = getFacultyCourses($faculty['id']);

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
            <h4 class="mb-0">My Courses</h4>
            <small class="text-muted">View and manage your courses</small>
        </div>
    </div>
    
    <!-- Courses Grid -->
    <div class="row">
        <?php if(count($courses) > 0): ?>
            <?php foreach($courses as $course): 
                $student_count = $conn->query("SELECT COUNT(*) as count FROM student_courses WHERE course_id = {$course['id']}")->fetch_assoc()['count'];
                $assignment_count = $conn->query("SELECT COUNT(*) as count FROM assignments WHERE course_id = {$course['id']}")->fetch_assoc()['count'];
            ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0"><?php echo $course['course_code']; ?></h5>
                    </div>
                    <div class="card-body">
                        <h6 class="card-subtitle mb-3"><?php echo $course['course_name']; ?></h6>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span><i class="fas fa-users"></i> Students</span>
                                <span class="badge bg-info"><?php echo $student_count; ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span><i class="fas fa-tasks"></i> Assignments</span>
                                <span class="badge bg-success"><?php echo $assignment_count; ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span><i class="fas fa-star"></i> Credits</span>
                                <span class="badge bg-warning"><?php echo $course['credits']; ?></span>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <a href="view_course_students.php?id=<?php echo $course['id']; ?>" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-users"></i> View Students
                            </a>
                            <a href="mark_attendance.php?course_id=<?php echo $course['id']; ?>" class="btn btn-outline-success btn-sm">
                                <i class="fas fa-calendar-check"></i> Mark Attendance
                            </a>
                            <a href="assignments.php?course_id=<?php echo $course['id']; ?>" class="btn btn-outline-warning btn-sm">
                                <i class="fas fa-tasks"></i> Manage Assignments
                            </a>
                            <a href="grades.php?course_id=<?php echo $course['id']; ?>" class="btn btn-outline-info btn-sm">
                                <i class="fas fa-star"></i> Manage Grades
                            </a>
                        </div>
                    </div>
                    <div class="card-footer text-muted">
                        <small>Room: <?php echo $course['room'] ?? 'TBA'; ?> | Schedule: <?php echo $course['schedule'] ?? 'Regular'; ?></small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> You have no courses assigned for this semester.
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>