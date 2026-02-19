<?php
// student/view_grades.php
require_once '../config/database.php';
require_once '../includes/auth.php';

// Check authentication first
if (!isLoggedIn() || !hasRole('student')) {
    $_SESSION['error'] = 'Please login to access your grades';
    redirect('../login.php');
    exit();
}

$page_title = 'My Grades';
$user_id = (int)$_SESSION['user_id']; // Cast to integer for security

// Initialize variables
$student = null;
$courses = [];
$grades_result = null;
$course_id = 0;
$gpa = 0;
$grade_distribution = [];

try {
    // Get student details with error handling
    $student = getStudentByUserId($user_id);
    
    if (!$student) {
        throw new Exception('Student record not found');
    }
    
    // Validate and sanitize course_id parameter
    $course_id = isset($_GET['course_id']) ? filter_var($_GET['course_id'], FILTER_VALIDATE_INT) : 0;
    if ($course_id === false || $course_id < 0) {
        $course_id = 0;
    }
    
    // Get student's courses
    $courses = getStudentCourses($student['id']) ?: [];
    
    // Prepare the grades query with proper SQL injection protection
    if ($course_id > 0) {
        // Verify the course belongs to the student
        $course_valid = false;
        foreach ($courses as $course) {
            if ($course['id'] == $course_id) {
                $course_valid = true;
                break;
            }
        }
        
        if (!$course_valid) {
            $_SESSION['error'] = 'Invalid course selected';
             redirect('view_grades.php');
            exit();
        }
        
        $stmt = $conn->prepare("
            SELECT g.*, c.course_name, c.credits, a.title as assignment_title
            FROM grades g
            JOIN courses c ON g.course_id = c.id
            LEFT JOIN assignments a ON g.assignment_id = a.id
            WHERE g.student_id = ? AND g.course_id = ?
            ORDER BY g.submission_date DESC
        ");
        $stmt->bind_param("ii", $student['id'], $course_id);
    } else {
        $stmt = $conn->prepare("
            SELECT g.*, c.course_name, c.credits, a.title as assignment_title
            FROM grades g
            JOIN courses c ON g.course_id = c.id
            LEFT JOIN assignments a ON g.assignment_id = a.id
            WHERE g.student_id = ?
            ORDER BY g.submission_date DESC
        ");
        $stmt->bind_param("i", $student['id']);
    }
    
    $stmt->execute();
    $grades_result = $stmt->get_result();
    
    // Calculate GPA using the result set
    if ($grades_result && $grades_result->num_rows > 0) {
        $total_points = 0;
        $total_credits = 0;
        
        // Grade point mapping
        $grade_points = [
            'A+' => 4.0, 'A' => 4.0, 'A-' => 3.7,
            'B+' => 3.3, 'B' => 3.0, 'B-' => 2.7,
            'C+' => 2.3, 'C' => 2.0, 'C-' => 1.7,
            'D+' => 1.3, 'D' => 1.0, 'F' => 0.0
        ];
        
        // Reset pointer to beginning
        $grades_result->data_seek(0);
        
        while ($grade = $grades_result->fetch_assoc()) {
            $credits = isset($grade['credits']) ? (float)$grade['credits'] : 3.0;
            $grade_point = isset($grade_points[$grade['grade']]) ? $grade_points[$grade['grade']] : 0;
            
            $total_points += $grade_point * $credits;
            $total_credits += $credits;
        }
        
        $gpa = $total_credits > 0 ? round($total_points / $total_credits, 2) : 0;
        
        // Reset pointer again for display
        $grades_result->data_seek(0);
    }
    
    // Get grade distribution for chart (if no course filter)
    if (!$course_id) {
        $dist_stmt = $conn->prepare("
            SELECT c.course_name, AVG(g.marks_obtained * 100.0 / g.total_marks) as avg_percentage
            FROM grades g
            JOIN courses c ON g.course_id = c.id
            WHERE g.student_id = ?
            GROUP BY g.course_id, c.course_name
            ORDER BY c.course_name
        ");
        $dist_stmt->bind_param("i", $student['id']);
        $dist_stmt->execute();
        $dist_result = $dist_stmt->get_result();
        
        while ($row = $dist_result->fetch_assoc()) {
            $grade_distribution[] = [
                'course_name' => htmlspecialchars($row['course_name'], ENT_QUOTES, 'UTF-8'),
                'avg_percentage' => round($row['avg_percentage'], 2)
            ];
        }
        $dist_stmt->close();
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    error_log("View Grades Error: " . $e->getMessage());
    $_SESSION['error'] = 'An error occurred while loading your grades. Please try again.';
    
    // Create empty result set
    $grades_result = $conn->query("SELECT 1 WHERE 1=0");
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content" id="mainContent">
    <!-- Top Bar -->
    <div class="top-bar">
        <button class="menu-toggle" onclick="toggleSidebar()" aria-label="Toggle menu">
            <i class="fas fa-bars"></i>
        </button>
        
        <div>
            <h4 class="mb-0">My Grades</h4>
            <small class="text-muted">View your academic performance</small>
        </div>
    </div>
    
    <!-- Display Session Messages -->
    <?php if(isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php 
        echo htmlspecialchars($_SESSION['success']);
        unset($_SESSION['success']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <?php if(isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php 
        echo htmlspecialchars($_SESSION['error']);
        unset($_SESSION['error']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <!-- GPA Card and Filter -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-white bg-primary">
                <div class="card-body text-center">
                    <h5 class="card-title">Current GPA</h5>
                    <h1 class="display-4"><?php echo number_format($gpa, 2); ?></h1>
                    <p class="mb-0">Overall Performance</p>
                    <?php if($gpa >= 3.5): ?>
                        <span class="badge bg-light text-dark mt-2">Excellent</span>
                    <?php elseif($gpa >= 3.0): ?>
                        <span class="badge bg-light text-dark mt-2">Good</span>
                    <?php elseif($gpa >= 2.0): ?>
                        <span class="badge bg-light text-dark mt-2">Satisfactory</span>
                    <?php elseif($gpa > 0): ?>
                        <span class="badge bg-light text-dark mt-2">Needs Improvement</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-8">
                            <label for="course_id" class="form-label">Filter by Course</label>
                            <select class="form-select" id="course_id" name="course_id" onchange="this.form.submit()">
                                <option value="0">All Courses</option>
                                <?php foreach($courses as $course): ?>
                                <option value="<?php echo (int)$course['id']; ?>" <?php echo $course_id == $course['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($course['course_name'] ?? ''); ?> 
                                    (<?php echo htmlspecialchars($course['course_code'] ?? ''); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php if($course_id > 0): ?>
                        <div class="col-md-4 d-flex align-items-end">
                            <a href="view_grades.php" class="btn btn-secondary w-100">
                                <i class="fas fa-times"></i> Clear Filter
                            </a>
                        </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Grades Summary Cards (when course filter is active) -->
    <?php if($course_id > 0 && $grades_result && $grades_result->num_rows > 0): 
        // Calculate course statistics
        $grades_result->data_seek(0);
        $course_grades = [];
        $total_percentage = 0;
        $count = 0;
        
        while($grade = $grades_result->fetch_assoc()) {
            $percentage = ($grade['marks_obtained'] / $grade['total_marks']) * 100;
            $course_grades[] = $percentage;
            $total_percentage += $percentage;
            $count++;
        }
        
        $avg_percentage = $count > 0 ? $total_percentage / $count : 0;
        $highest = !empty($course_grades) ? max($course_grades) : 0;
        $lowest = !empty($course_grades) ? min($course_grades) : 0;
        
        // Reset pointer
        $grades_result->data_seek(0);
    ?>
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Average</h6>
                        <h3 class="mb-0"><?php echo number_format($avg_percentage, 1); ?>%</h3>
                    </div>
                    <div class="bg-info bg-opacity-10 p-3 rounded-circle">
                        <i class="fas fa-chart-line text-info fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Highest</h6>
                        <h3 class="mb-0"><?php echo number_format($highest, 1); ?>%</h3>
                    </div>
                    <div class="bg-success bg-opacity-10 p-3 rounded-circle">
                        <i class="fas fa-arrow-up text-success fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Lowest</h6>
                        <h3 class="mb-0"><?php echo number_format($lowest, 1); ?>%</h3>
                    </div>
                    <div class="bg-warning bg-opacity-10 p-3 rounded-circle">
                        <i class="fas fa-arrow-down text-warning fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Assignments</h6>
                        <h3 class="mb-0"><?php echo $count; ?></h3>
                    </div>
                    <div class="bg-primary bg-opacity-10 p-3 rounded-circle">
                        <i class="fas fa-tasks text-primary fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Grades Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Grade Details</h5>
            <button class="btn btn-sm btn-success" onclick="exportToCSV()">
                <i class="fas fa-download"></i> Export CSV
            </button>
        </div>
        <div class="card-body">
            <?php if($grades_result && $grades_result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover datatable" id="gradesTable">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Assignment/Assessment</th>
                            <th>Marks Obtained</th>
                            <th>Total Marks</th>
                            <th>Percentage</th>
                            <th>Grade</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $grades_result->data_seek(0);
                        while($grade = $grades_result->fetch_assoc()): 
                            $percentage = ($grade['marks_obtained'] / $grade['total_marks']) * 100;
                            
                            // Determine status based on percentage
                            if ($percentage >= 90) $status = 'Excellent';
                            elseif ($percentage >= 80) $status = 'Very Good';
                            elseif ($percentage >= 70) $status = 'Good';
                            elseif ($percentage >= 60) $status = 'Satisfactory';
                            elseif ($percentage >= 50) $status = 'Pass';
                            else $status = 'Needs Improvement';
                            
                            $status_class = $percentage >= 60 ? 'success' : ($percentage >= 50 ? 'warning' : 'danger');
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($grade['course_name'] ?? ''); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($grade['assignment_title'] ?? 'Final Grade'); ?></td>
                            <td class="fw-bold"><?php echo (float)$grade['marks_obtained']; ?></td>
                            <td><?php echo (float)$grade['total_marks']; ?></td>
                            <td style="min-width: 150px;">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height: 8px;">
                                        <div class="progress-bar bg-<?php echo $status_class; ?>" 
                                             style="width: <?php echo $percentage; ?>%"></div>
                                    </div>
                                    <small class="fw-bold"><?php echo number_format($percentage, 1); ?>%</small>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-<?php 
                                    $grade_val = $grade['grade'] ?? '';
                                    if (in_array($grade_val, ['A+', 'A', 'A-'])) echo 'success';
                                    elseif (in_array($grade_val, ['B+', 'B', 'B-'])) echo 'info';
                                    elseif (in_array($grade_val, ['C+', 'C', 'C-'])) echo 'primary';
                                    elseif (in_array($grade_val, ['D+', 'D'])) echo 'warning';
                                    else echo 'danger';
                                ?>">
                                    <?php echo htmlspecialchars($grade_val); ?>
                                </span>
                            </td>
                            <td><?php echo isset($grade['submission_date']) ? htmlspecialchars(formatDate($grade['submission_date'])) : 'N/A'; ?></td>
                            <td><span class="badge bg-<?php echo $status_class; ?>"><?php echo $status; ?></span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-graduation-cap fa-4x text-muted mb-3"></i>
                <h5>No Grades Available</h5>
                <p class="text-muted">There are no grades to display at the moment.</p>
                <?php if($course_id > 0): ?>
                <a href="view_grades.php" class="btn btn-primary">View All Courses</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Grade Distribution Chart -->
    <?php if(!$course_id && !empty($grade_distribution)): ?>
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">Grade Distribution by Course</h5>
        </div>
        <div class="card-body">
            <div style="height: 300px;">
                <canvas id="gradeChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('gradeChart').getContext('2d');
        
        // Prepare data from PHP
        const labels = <?php echo json_encode(array_column($grade_distribution, 'course_name')); ?>;
        const values = <?php echo json_encode(array_column($grade_distribution, 'avg_percentage')); ?>;
        
        // Generate colors
        const colors = [
            '#4cc9f0', '#f72585', '#7209b7', '#f9c74f', '#43aa8b',
            '#ff9f1c', '#e71d36', '#2ec4b6', '#e3646b', '#0077b6'
        ];
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Average Percentage',
                    data: values,
                    backgroundColor: colors.slice(0, values.length),
                    borderRadius: 5,
                    barPercentage: 0.6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `Average: ${context.raw}%`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        title: {
                            display: true,
                            text: 'Percentage (%)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    });
    </script>
    <?php endif; ?>
    
    <!-- Performance Legend -->
    <div class="card mt-4">
        <div class="card-body">
            <h6>Grade Legend</h6>
            <div class="row">
                <div class="col-md-2 col-6">
                    <span class="badge bg-success">A+, A, A-</span> 90-100%
                </div>
                <div class="col-md-2 col-6">
                    <span class="badge bg-info">B+, B, B-</span> 80-89%
                </div>
                <div class="col-md-2 col-6">
                    <span class="badge bg-primary">C+, C, C-</span> 70-79%
                </div>
                <div class="col-md-2 col-6">
                    <span class="badge bg-warning">D+, D</span> 60-69%
                </div>
                <div class="col-md-2 col-6">
                    <span class="badge bg-danger">F</span> Below 60%
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Export to CSV Script -->
<script>
function exportToCSV() {
    const table = document.getElementById('gradesTable');
    if (!table) {
        alert('No data to export');
        return;
    }
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    for (let i = 0; i < rows.length; i++) {
        const row = [], cols = rows[i].querySelectorAll('td, th');
        
        for (let j = 0; j < cols.length; j++) {
            // Clean the text content (remove HTML tags, extra spaces)
            let text = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, ' ').replace(/\s+/g, ' ').trim();
            row.push('"' + text + '"');
        }
        csv.push(row.join(','));
    }
    
    // Download CSV file
    const csvFile = new Blob([csv.join('\n')], { type: 'text/csv' });
    const downloadLink = document.createElement('a');
    downloadLink.download = 'my_grades_<?php echo date('Y-m-d'); ?>.csv';
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = 'none';
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}
</script>

<style>
.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    height: 100%;
}
.stat-card h3 {
    font-size: 1.8rem;
    font-weight: 600;
}
.progress {
    background-color: #e9ecef;
    border-radius: 10px;
}
.progress-bar {
    border-radius: 10px;
    transition: width 0.6s ease;
}
</style>

<?php include '../includes/footer.php';
include '../includes/sidebar.php';
 ?>
