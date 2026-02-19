<?php
// faculty/dashboard.php
require_once '../config/database.php';

// Use auth functions
requireAuth('../login.php');
requireFaculty();

$page_title = 'Faculty Dashboard';
$user_id = $_SESSION['user_id'];

// Refresh session
refreshSession();

// Get faculty details
$faculty = getFacultyByUserId($user_id);

// Check if faculty exists
if (!$faculty) {
    $_SESSION['error'] = "Faculty record not found.";
    redirect('../login.php');
}

$faculty_id = $faculty['id'];

// Get assigned courses
$courses = getFacultyCourses($faculty_id);

// Get today's schedule
$today = date('Y-m-d');
$today_schedule = $conn->query("
    SELECT c.*, ca.room, ca.schedule 
    FROM course_assignments ca
    JOIN courses c ON ca.course_id = c.id
    WHERE ca.faculty_id = $faculty_id
");

if (!$today_schedule) {
    $today_schedule = $conn->query("SELECT 1 as dummy WHERE 1=0");
}

// Get recent attendance marked
$recent_attendance = $conn->query("
    SELECT a.*, c.course_name, s.student_id, u.full_name as student_name
    FROM attendance a
    JOIN courses c ON a.course_id = c.id
    JOIN students s ON a.student_id = s.id
    JOIN users u ON s.user_id = u.id
    WHERE a.marked_by = $faculty_id
    ORDER BY a.marked_at DESC
    LIMIT 5
");

if (!$recent_attendance) {
    $recent_attendance = $conn->query("SELECT 1 as dummy WHERE 1=0");
}

// Get pending assignments to grade
$pending_assignments = $conn->query("
    SELECT sub.*, a.title as assignment_title, c.course_name, u.full_name as student_name
    FROM submissions sub
    JOIN assignments a ON sub.assignment_id = a.id
    JOIN courses c ON a.course_id = c.id
    JOIN students s ON sub.student_id = s.id
    JOIN users u ON s.user_id = u.id
    WHERE a.created_by = $faculty_id AND sub.status = 'submitted'
    ORDER BY sub.submission_date ASC
    LIMIT 5
");

if (!$pending_assignments) {
    $pending_assignments = $conn->query("SELECT 1 as dummy WHERE 1=0");
}

// Generate CSRF token
$csrf_token = generateCSRFToken();

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
            <h4 class="mb-0">Welcome, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Faculty'); ?></h4>
            <small class="text-muted"><?php echo htmlspecialchars($faculty['designation'] ?? 'Faculty'); ?> - <?php echo htmlspecialchars($faculty['department'] ?? ''); ?></small>
        </div>
        
        <div class="user-menu">
            <div class="dropdown">
                <button class="btn btn-link text-dark position-relative" data-bs-toggle="dropdown">
                    <i class="fas fa-bell"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        <?php echo $pending_assignments->num_rows; ?>
                    </span>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Total Courses</h6>
                        <h3 class="mb-0"><?php echo count($courses); ?></h3>
                    </div>
                    <div class="bg-primary bg-opacity-10 p-3 rounded-circle">
                        <i class="fas fa-book text-primary fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Total Students</h6>
                        <h3 class="mb-0">
                            <?php
                            $total_students = 0;
                            foreach($courses as $course) {
                                $count = $conn->query("SELECT COUNT(*) as count FROM student_courses WHERE course_id = {$course['id']}")->fetch_assoc()['count'];
                                $total_students += $count;
                            }
                            echo $total_students;
                            ?>
                        </h3>
                    </div>
                    <div class="bg-success bg-opacity-10 p-3 rounded-circle">
                        <i class="fas fa-users text-success fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Classes Today</h6>
                        <h3 class="mb-0"><?php echo $today_schedule->num_rows; ?></h3>
                    </div>
                    <div class="bg-warning bg-opacity-10 p-3 rounded-circle">
                        <i class="fas fa-clock text-warning fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Pending Grades</h6>
                        <h3 class="mb-0"><?php echo $pending_assignments->num_rows; ?></h3>
                    </div>
                    <div class="bg-danger bg-opacity-10 p-3 rounded-circle">
                        <i class="fas fa-star text-danger fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Today's Schedule -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Today's Schedule (<?php echo date('l, F j, Y'); ?>)</h5>
                </div>
                <div class="card-body">
                    <?php if($today_schedule && $today_schedule->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th>Course Code</th>
                                    <th>Room</th>
                                    <th>Schedule</th>
                                    <th>Students</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($class = $today_schedule->fetch_assoc()): 
                                    $student_count = $conn->query("SELECT COUNT(*) as count FROM student_courses WHERE course_id = {$class['id']}")->fetch_assoc()['count'];
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($class['course_name']); ?></td>
                                    <td><?php echo htmlspecialchars($class['course_code']); ?></td>
                                    <td><?php echo htmlspecialchars($class['room'] ?? 'TBA'); ?></td>
                                    <td><?php echo htmlspecialchars($class['schedule'] ?? 'Regular'); ?></td>
                                    <td><span class="badge bg-info"><?php echo $student_count; ?> students</span></td>
                                    <td>
                                        <a href="mark_attendance.php?course_id=<?php echo $class['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-calendar-check"></i> Attendance
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p class="text-muted mb-0">No classes scheduled for today.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- My Courses -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">My Courses</h5>
                    <a href="my_courses.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach($courses as $course): 
                            $student_count = $conn->query("SELECT COUNT(*) as count FROM student_courses WHERE course_id = {$course['id']}")->fetch_assoc()['count'];
                        ?>
                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($course['course_name']); ?></h5>
                                    <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($course['course_code']); ?></h6>
                                    <p class="card-text">
                                        <small class="d-block"><i class="fas fa-users"></i> <?php echo $student_count; ?> Students</small>
                                        <small class="d-block"><i class="fas fa-star"></i> Credits: <?php echo $course['credits']; ?></small>
                                    </p>
                                    <div class="mt-3">
                                        <a href="mark_attendance.php?course_id=<?php echo $course['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-calendar-check"></i>
                                        </a>
                                        <a href="assignments.php?course_id=<?php echo $course['id']; ?>" class="btn btn-sm btn-success">
                                            <i class="fas fa-tasks"></i>
                                        </a>
                                        <a href="grades.php?course_id=<?php echo $course['id']; ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-star"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Pending Assignments and Recent Activity -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Pending Assignments to Grade</h5>
                </div>
                <div class="card-body">
                    <?php if($pending_assignments && $pending_assignments->num_rows > 0): ?>
                    <div class="list-group">
                        <?php while($pending = $pending_assignments->fetch_assoc()): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($pending['student_name']); ?></h6>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($pending['assignment_title']); ?> - <?php echo htmlspecialchars($pending['course_name']); ?>
                                    </small>
                                </div>
                                <div>
                                    <small class="text-warning d-block">
                                        <?php echo timeAgo($pending['submission_date']); ?>
                                    </small>
                                    <a href="grade_submission.php?id=<?php echo $pending['id']; ?>" class="btn btn-sm btn-primary mt-1">
                                        Grade
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-muted mb-0">No pending assignments to grade.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Recent Attendance Marked</h5>
                </div>
                <div class="card-body">
                    <?php if($recent_attendance && $recent_attendance->num_rows > 0): ?>
                    <div class="list-group">
                        <?php while($att = $recent_attendance->fetch_assoc()): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($att['student_name']); ?></h6>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($att['course_name']); ?> - <?php echo formatDate($att['date']); ?>
                                    </small>
                                </div>
                                <span class="badge bg-<?php 
                                    echo $att['status'] == 'present' ? 'success' : 
                                        ($att['status'] == 'late' ? 'warning' : 'danger'); 
                                ?>">
                                    <?php echo ucfirst($att['status']); ?>
                                </span>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-muted mb-0">No attendance marked recently.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>