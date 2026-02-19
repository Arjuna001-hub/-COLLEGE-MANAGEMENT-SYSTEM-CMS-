<?php
// faculty/view_attendance.php
require_once '../config/database.php';

if (!isLoggedIn() || !hasRole('faculty')) {
    redirect('../login.php');
}

$page_title = 'View Attendance';
$user_id = $_SESSION['user_id'];
$faculty = getFacultyByUserId($user_id);

$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get faculty's courses
$courses = $conn->query("
    SELECT c.* 
    FROM courses c
    JOIN course_assignments ca ON c.id = ca.course_id
    WHERE ca.faculty_id = {$faculty['id']}
");

// Get attendance records
$attendance_records = [];
if ($course_id) {
    $attendance_records = $conn->query("
        SELECT a.*, s.student_id, u.full_name as student_name,
               c.course_name
        FROM attendance a
        JOIN students s ON a.student_id = s.id
        JOIN users u ON s.user_id = u.id
        JOIN courses c ON a.course_id = c.id
        WHERE a.course_id = $course_id 
        AND a.date BETWEEN '$start_date' AND '$end_date'
        ORDER BY a.date DESC, u.full_name ASC
    ");
}

// Get attendance summary
$summary = [];
if ($course_id) {
    $summary = $conn->query("
        SELECT 
            COUNT(DISTINCT a.student_id) as total_students,
            COUNT(*) as total_records,
            SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
            SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
            SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_count
        FROM attendance a
        WHERE a.course_id = $course_id 
        AND a.date BETWEEN '$start_date' AND '$end_date'
    ")->fetch_assoc();
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
            <h4 class="mb-0">View Attendance</h4>
            <small class="text-muted">View and analyze attendance records</small>
        </div>
    </div>
    
    <!-- Filter Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Course</label>
                    <select class="form-select" name="course_id" required>
                        <option value="">Select Course</option>
                        <?php while($course = $courses->fetch_assoc()): ?>
                        <option value="<?php echo $course['id']; ?>" <?php echo $course_id == $course['id'] ? 'selected' : ''; ?>>
                            <?php echo $course['course_name']; ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Start Date</label>
                    <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">End Date</label>
                    <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">View</button>
                </div>
            </form>
        </div>
    </div>
    
    <?php if($course_id && $summary): ?>
    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <h6 class="text-muted">Total Students</h6>
                <h3><?php echo $summary['total_students'] ?: 0; ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <h6 class="text-muted">Total Records</h6>
                <h3><?php echo $summary['total_records'] ?: 0; ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <h6 class="text-muted">Average Attendance</h6>
                <h3>
                    <?php 
                    $avg = $summary['total_records'] > 0 ? 
                        round(($summary['present_count'] / $summary['total_records']) * 100, 2) : 0;
                    echo $avg . '%';
                    ?>
                </h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <h6 class="text-muted">Attendance Rate</h6>
                <div class="progress mt-2" style="height: 10px;">
                    <div class="progress-bar bg-success" style="width: <?php echo $avg; ?>%"></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Attendance Chart -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Attendance Overview</h5>
        </div>
        <div class="card-body">
            <canvas id="attendanceChart" height="100"></canvas>
        </div>
    </div>
    
    <!-- Attendance Records -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Attendance Records</h5>
            <button class="btn btn-sm btn-success" onclick="exportAttendance()">
                <i class="fas fa-file-excel"></i> Export
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover datatable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>Status</th>
                            <th>Marked By</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($record = $attendance_records->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo formatDate($record['date']); ?></td>
                            <td><?php echo $record['student_id']; ?></td>
                            <td><?php echo $record['student_name']; ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $record['status'] == 'present' ? 'success' : 
                                        ($record['status'] == 'late' ? 'warning' : 'danger'); 
                                ?>">
                                    <?php echo ucfirst($record['status']); ?>
                                </span>
                            </td>
                            <td><?php echo $_SESSION['full_name']; ?></td>
                            <td><?php echo date('h:i A', strtotime($record['marked_at'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
    // Attendance Chart
    const ctx = document.getElementById('attendanceChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: [<?php 
                $dates = $conn->query("
                    SELECT DISTINCT date 
                    FROM attendance 
                    WHERE course_id = $course_id 
                    AND date BETWEEN '$start_date' AND '$end_date'
                    ORDER BY date
                ");
                $date_labels = [];
                $present_data = [];
                while($date = $dates->fetch_assoc()) {
                    $date_labels[] = "'" . formatDate($date['date'], 'M d') . "'";
                    
                    $count = $conn->query("
                        SELECT COUNT(*) as count 
                        FROM attendance 
                        WHERE course_id = $course_id AND date = '{$date['date']}' AND status = 'present'
                    ")->fetch_assoc()['count'];
                    $present_data[] = $count;
                }
                echo implode(', ', $date_labels);
            ?>],
            datasets: [{
                label: 'Present Students',
                data: [<?php echo implode(', ', $present_data); ?>],
                borderColor: '#4cc9f0',
                backgroundColor: 'rgba(76, 201, 240, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
    
    function exportAttendance() {
        window.location.href = 'export_attendance.php?course_id=<?php echo $course_id; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>';
    }
    </script>
    
    <?php elseif($course_id): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> No attendance records found for the selected period.
    </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>