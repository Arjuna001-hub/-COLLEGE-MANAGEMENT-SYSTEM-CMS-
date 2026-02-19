<?php
// admin/attendance.php
require_once '../config/database.php';

if (!isLoggedIn() || !hasRole('admin')) {
    redirect('../login.php');
}

$page_title = 'Attendance Overview';
$message = '';
$error = '';

// Get date range
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

// Get all courses for filter
$courses = $conn->query("SELECT * FROM courses ORDER BY course_name");

// Get attendance statistics
$attendance_stats = $conn->query("
    SELECT 
        COUNT(DISTINCT student_id) as total_students,
        COUNT(*) as total_records,
        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count,
        SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_count
    FROM attendance 
    WHERE date BETWEEN '$start_date' AND '$end_date'
");

$stats = $attendance_stats->fetch_assoc();

// Get attendance by course
$attendance_by_course = $conn->query("
    SELECT 
        c.course_name,
        c.course_code,
        COUNT(a.id) as total,
        SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present,
        SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent,
        ROUND(SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as percentage
    FROM attendance a
    JOIN courses c ON a.course_id = c.id
    WHERE a.date BETWEEN '$start_date' AND '$end_date'
    GROUP BY a.course_id
    ORDER BY percentage DESC
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
            <h4 class="mb-0">Attendance Overview</h4>
            <small class="text-muted">Monitor student attendance</small>
        </div>
    </div>
    
    <!-- Filter Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Start Date</label>
                    <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">End Date</label>
                    <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Course</label>
                    <select class="form-select" name="course_id">
                        <option value="0">All Courses</option>
                        <?php while($course = $courses->fetch_assoc()): ?>
                        <option value="<?php echo $course['id']; ?>" <?php echo $course_id == $course['id'] ? 'selected' : ''; ?>>
                            <?php echo $course['course_name']; ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-primary">Apply Filter</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <h6 class="text-muted">Total Records</h6>
                <h3><?php echo $stats['total_records'] ?: 0; ?></h3>
                <small>Attendance entries</small>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card">
                <h6 class="text-muted">Present</h6>
                <h3 class="text-success"><?php echo $stats['present_count'] ?: 0; ?></h3>
                <small><?php echo $stats['total_records'] ? round(($stats['present_count'] / $stats['total_records']) * 100, 2) : 0; ?>% of total</small>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card">
                <h6 class="text-muted">Absent</h6>
                <h3 class="text-danger"><?php echo $stats['absent_count'] ?: 0; ?></h3>
                <small><?php echo $stats['total_records'] ? round(($stats['absent_count'] / $stats['total_records']) * 100, 2) : 0; ?>% of total</small>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card">
                <h6 class="text-muted">Late</h6>
                <h3 class="text-warning"><?php echo $stats['late_count'] ?: 0; ?></h3>
                <small><?php echo $stats['total_records'] ? round(($stats['late_count'] / $stats['total_records']) * 100, 2) : 0; ?>% of total</small>
            </div>
        </div>
    </div>
    
    <!-- Attendance by Course Chart -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Attendance by Course</h5>
                </div>
                <div class="card-body">
                    <canvas id="attendanceChart" height="300"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Attendance Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="distributionChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Attendance by Course Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Course-wise Attendance</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Total Records</th>
                            <th>Present</th>
                            <th>Absent</th>
                            <th>Attendance %</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($course = $attendance_by_course->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $course['course_name'] . ' (' . $course['course_code'] . ')'; ?></td>
                            <td><?php echo $course['total']; ?></td>
                            <td class="text-success"><?php echo $course['present']; ?></td>
                            <td class="text-danger"><?php echo $course['absent']; ?></td>
                            <td>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar bg-success" style="width: <?php echo $course['percentage']; ?>%">
                                        <?php echo $course['percentage']; ?>%
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if($course['percentage'] >= 75): ?>
                                    <span class="badge bg-success">Good</span>
                                <?php elseif($course['percentage'] >= 60): ?>
                                    <span class="badge bg-warning">Average</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Poor</span>
                                <?php endif; ?>
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
// Attendance Chart
const attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
new Chart(attendanceCtx, {
    type: 'bar',
    data: {
        labels: [<?php 
            $attendance_by_course->data_seek(0);
            $labels = [];
            $present_data = [];
            $absent_data = [];
            while($course = $attendance_by_course->fetch_assoc()) {
                $labels[] = "'" . $course['course_code'] . "'";
                $present_data[] = $course['present'];
                $absent_data[] = $course['absent'];
            }
            echo implode(', ', $labels);
        ?>],
        datasets: [{
            label: 'Present',
            data: [<?php echo implode(', ', $present_data); ?>],
            backgroundColor: '#4cc9f0',
            borderRadius: 5
        }, {
            label: 'Absent',
            data: [<?php echo implode(', ', $absent_data); ?>],
            backgroundColor: '#f72585',
            borderRadius: 5
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Distribution Chart
const distCtx = document.getElementById('distributionChart').getContext('2d');
new Chart(distCtx, {
    type: 'doughnut',
    data: {
        labels: ['Present', 'Absent', 'Late'],
        datasets: [{
            data: [
                <?php echo $stats['present_count'] ?: 0; ?>,
                <?php echo $stats['absent_count'] ?: 0; ?>,
                <?php echo $stats['late_count'] ?: 0; ?>
            ],
            backgroundColor: ['#4cc9f0', '#f72585', '#f8961e'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
</script>

<?php include '../includes/footer.php'; ?>