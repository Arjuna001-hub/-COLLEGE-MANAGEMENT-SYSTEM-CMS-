<?php
// admin/reports.php
require_once '../config/database.php';

if (!isLoggedIn() || !hasRole('admin')) {
    redirect('../login.php');
}

$page_title = 'Reports';
$report_type = isset($_GET['type']) ? $_GET['type'] : 'students';

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
            <h4 class="mb-0">Reports</h4>
            <small class="text-muted">Generate and view reports</small>
        </div>
    </div>
    
    <!-- Report Type Tabs -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link <?php echo $report_type == 'students' ? 'active' : ''; ?>" href="?type=students">
                <i class="fas fa-users"></i> Students Report
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $report_type == 'attendance' ? 'active' : ''; ?>" href="?type=attendance">
                <i class="fas fa-calendar-check"></i> Attendance Report
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $report_type == 'fees' ? 'active' : ''; ?>" href="?type=fees">
                <i class="fas fa-money-bill"></i> Fees Report
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $report_type == 'courses' ? 'active' : ''; ?>" href="?type=courses">
                <i class="fas fa-book"></i> Courses Report
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $report_type == 'faculty' ? 'active' : ''; ?>" href="?type=faculty">
                <i class="fas fa-chalkboard-teacher"></i> Faculty Report
            </a>
        </li>
    </ul>
    
    <!-- Report Content -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <?php echo ucfirst($report_type); ?> Report
            </h5>
            <div>
                <button class="btn btn-sm btn-success" onclick="exportReport('<?php echo $report_type; ?>')">
                    <i class="fas fa-file-excel"></i> Export CSV
                </button>
                <button class="btn btn-sm btn-danger" onclick="printReport()">
                    <i class="fas fa-print"></i> Print
                </button>
            </div>
        </div>
        <div class="card-body">
            <?php if($report_type == 'students'): ?>
                <?php
                $students = $conn->query("
                    SELECT s.student_id, u.full_name, u.email, u.phone, s.course, s.semester, 
                           s.batch_year, s.parent_name, s.parent_phone
                    FROM students s
                    JOIN users u ON s.user_id = u.id
                    ORDER BY s.student_id
                ");
                ?>
                <div class="table-responsive" id="reportTable">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Course</th>
                                <th>Semester</th>
                                <th>Batch Year</th>
                                <th>Parent Name</th>
                                <th>Parent Phone</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($student = $students->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $student['student_id']; ?></td>
                                <td><?php echo $student['full_name']; ?></td>
                                <td><?php echo $student['email']; ?></td>
                                <td><?php echo $student['phone']; ?></td>
                                <td><?php echo $student['course']; ?></td>
                                <td><?php echo $student['semester']; ?></td>
                                <td><?php echo $student['batch_year']; ?></td>
                                <td><?php echo $student['parent_name']; ?></td>
                                <td><?php echo $student['parent_phone']; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
            <?php elseif($report_type == 'attendance'): ?>
                <?php
                $attendance = $conn->query("
                    SELECT s.student_id, u.full_name, c.course_name, 
                           COUNT(a.id) as total_classes,
                           SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present,
                           ROUND(SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as percentage
                    FROM attendance a
                    JOIN students s ON a.student_id = s.id
                    JOIN users u ON s.user_id = u.id
                    JOIN courses c ON a.course_id = c.id
                    GROUP BY s.id, a.course_id
                    ORDER BY percentage DESC
                ");
                ?>
                <div class="table-responsive" id="reportTable">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Student Name</th>
                                <th>Course</th>
                                <th>Total Classes</th>
                                <th>Present</th>
                                <th>Attendance %</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($record = $attendance->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $record['student_id']; ?></td>
                                <td><?php echo $record['full_name']; ?></td>
                                <td><?php echo $record['course_name']; ?></td>
                                <td><?php echo $record['total_classes']; ?></td>
                                <td><?php echo $record['present']; ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $record['percentage'] >= 75 ? 'success' : ($record['percentage'] >= 60 ? 'warning' : 'danger'); ?>">
                                        <?php echo $record['percentage']; ?>%
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
            <?php elseif($report_type == 'fees'): ?>
                <?php
                $fees = $conn->query("
                    SELECT s.student_id, u.full_name, 
                           SUM(f.amount) as total_fees,
                           SUM(CASE WHEN f.status = 'paid' THEN f.amount ELSE 0 END) as paid_fees,
                           SUM(CASE WHEN f.status = 'pending' THEN f.amount ELSE 0 END) as pending_fees,
                           COUNT(CASE WHEN f.status = 'pending' THEN 1 END) as pending_count
                    FROM fees f
                    JOIN students s ON f.student_id = s.id
                    JOIN users u ON s.user_id = u.id
                    GROUP BY s.id
                    ORDER BY pending_fees DESC
                ");
                ?>
                <div class="table-responsive" id="reportTable">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Student Name</th>
                                <th>Total Fees</th>
                                <th>Paid Fees</th>
                                <th>Pending Fees</th>
                                <th>Pending Count</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($fee = $fees->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $fee['student_id']; ?></td>
                                <td><?php echo $fee['full_name']; ?></td>
                                <td>$<?php echo number_format($fee['total_fees'], 2); ?></td>
                                <td class="text-success">$<?php echo number_format($fee['paid_fees'], 2); ?></td>
                                <td class="text-danger">$<?php echo number_format($fee['pending_fees'], 2); ?></td>
                                <td><?php echo $fee['pending_count']; ?></td>
                                <td>
                                    <?php if($fee['pending_fees'] == 0): ?>
                                        <span class="badge bg-success">Clear</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">Pending</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
            <?php elseif($report_type == 'courses'): ?>
                <?php
                $courses = $conn->query("
                    SELECT c.*, 
                           COUNT(DISTINCT sc.student_id) as enrolled_students,
                           COUNT(DISTINCT ca.faculty_id) as assigned_faculty
                    FROM courses c
                    LEFT JOIN student_courses sc ON c.id = sc.course_id
                    LEFT JOIN course_assignments ca ON c.id = ca.course_id
                    GROUP BY c.id
                    ORDER BY c.course_code
                ");
                ?>
                <div class="table-responsive" id="reportTable">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Course Code</th>
                                <th>Course Name</th>
                                <th>Credits</th>
                                <th>Semester</th>
                                <th>Department</th>
                                <th>Enrolled Students</th>
                                <th>Assigned Faculty</th>
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
                                <td><?php echo $course['enrolled_students']; ?></td>
                                <td><?php echo $course['assigned_faculty']; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
            <?php elseif($report_type == 'faculty'): ?>
                <?php
                $faculty = $conn->query("
                    SELECT f.faculty_id, u.full_name, u.email, u.phone, 
                           f.department, f.designation, f.qualification, f.experience_years,
                           COUNT(DISTINCT ca.course_id) as assigned_courses
                    FROM faculty f
                    JOIN users u ON f.user_id = u.id
                    LEFT JOIN course_assignments ca ON f.id = ca.faculty_id
                    GROUP BY f.id
                    ORDER BY f.faculty_id
                ");
                ?>
                <div class="table-responsive" id="reportTable">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Faculty ID</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Department</th>
                                <th>Designation</th>
                                <th>Qualification</th>
                                <th>Experience</th>
                                <th>Courses</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($f = $faculty->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $f['faculty_id']; ?></td>
                                <td><?php echo $f['full_name']; ?></td>
                                <td><?php echo $f['email']; ?></td>
                                <td><?php echo $f['department']; ?></td>
                                <td><?php echo $f['designation']; ?></td>
                                <td><?php echo $f['qualification']; ?></td>
                                <td><?php echo $f['experience_years']; ?> years</td>
                                <td><?php echo $f['assigned_courses']; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function exportReport(type) {
    const table = document.getElementById('reportTable').querySelector('table');
    const rows = table.querySelectorAll('tr');
    const csv = [];
    
    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const rowData = Array.from(cols).map(col => {
            return '"' + col.textContent.replace(/"/g, '""') + '"';
        });
        csv.push(rowData.join(','));
    });
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = type + '_report_' + new Date().toISOString().slice(0,10) + '.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}

function printReport() {
    const table = document.getElementById('reportTable').innerHTML;
    const printWindow = window.open('', '_blank');
    
    printWindow.document.write(`
        <html>
            <head>
                <title>Report</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
                <style>
                    body { padding: 20px; }
                    @media print {
                        .no-print { display: none; }
                    }
                </style>
            </head>
            <body>
                <h2 class="mb-4"><?php echo ucfirst($report_type); ?> Report</h2>
                <p>Generated on: ${new Date().toLocaleDateString()}</p>
                ${table}
                <div class="no-print text-center mt-4">
                    <button class="btn btn-primary" onclick="window.print()">Print</button>
                    <button class="btn btn-secondary" onclick="window.close()">Close</button>
                </div>
            </body>
        </html>
    `);
    
    printWindow.document.close();
}
</script>

<?php include '../includes/footer.php'; ?>