<?php
// admin/add_student.php
require_once '../config/database.php';

if (!isLoggedIn() || !hasRole('admin')) {
    redirect('../login.php');
}

$page_title = 'Add Student';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // User data
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    $email = sanitize($_POST['email']);
    $full_name = sanitize($_POST['full_name']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    
    // Student data
    $date_of_birth = $_POST['date_of_birth'];
    $enrollment_date = $_POST['enrollment_date'];
    $course = sanitize($_POST['course']);
    $semester = intval($_POST['semester']);
    $batch_year = intval($_POST['batch_year']);
    $parent_name = sanitize($_POST['parent_name']);
    $parent_phone = sanitize($_POST['parent_phone']);
    $parent_email = sanitize($_POST['parent_email']);
    $emergency_contact = sanitize($_POST['emergency_contact']);
    $blood_group = sanitize($_POST['blood_group']);
    
    // Check if username exists
    $check = $conn->query("SELECT id FROM users WHERE username = '$username'");
    if ($check->num_rows > 0) {
        $error = "Username already exists!";
    } else {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Insert into users table
            $sql = "INSERT INTO users (username, password, email, role, full_name, phone, address) 
                    VALUES ('$username', '$password', '$email', 'student', '$full_name', '$phone', '$address')";
            $conn->query($sql);
            $user_id = $conn->insert_id;
            
            // Generate student ID
            $student_id = generateUniqueId('STU', 'students', 'student_id');
            
            // Insert into students table
            $sql = "INSERT INTO students (user_id, student_id, date_of_birth, enrollment_date, course, semester, 
                    batch_year, parent_name, parent_phone, parent_email, emergency_contact, blood_group) 
                    VALUES ($user_id, '$student_id', '$date_of_birth', '$enrollment_date', '$course', $semester, 
                    $batch_year, '$parent_name', '$parent_phone', '$parent_email', '$emergency_contact', '$blood_group')";
            $conn->query($sql);
            
            $conn->commit();
            $message = "Student added successfully! Student ID: " . $student_id;
            
            // Clear form
            $_POST = array();
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error adding student: " . $conn->error;
        }
    }
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
            <h4 class="mb-0">Add New Student</h4>
            <small class="text-muted">Create a new student record</small>
        </div>
        
        <div>
            <a href="students.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Students
            </a>
        </div>
    </div>
    
    <!-- Messages -->
    <?php if($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <!-- Add Student Form -->
    <div class="card">
        <div class="card-body">
            <form method="POST" onsubmit="return validateForm()">
                <h5 class="mb-3">Login Information</h5>
                <div class="row mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Username *</label>
                        <input type="text" class="form-control" name="username" value="<?php echo $_POST['username'] ?? ''; ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Password *</label>
                        <input type="password" class="form-control" name="password" id="password" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Confirm Password *</label>
                        <input type="password" class="form-control" id="confirm_password" required>
                    </div>
                </div>
                
                <h5 class="mb-3">Personal Information</h5>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Full Name *</label>
                        <input type="text" class="form-control" name="full_name" value="<?php echo $_POST['full_name'] ?? ''; ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Email *</label>
                        <input type="email" class="form-control" name="email" value="<?php echo $_POST['email'] ?? ''; ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" name="phone" value="<?php echo $_POST['phone'] ?? ''; ?>">
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" class="form-control" name="date_of_birth" value="<?php echo $_POST['date_of_birth'] ?? ''; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Blood Group</label>
                        <select class="form-select" name="blood_group">
                            <option value="">Select</option>
                            <option value="A+">A+</option>
                            <option value="A-">A-</option>
                            <option value="B+">B+</option>
                            <option value="B-">B-</option>
                            <option value="O+">O+</option>
                            <option value="O-">O-</option>
                            <option value="AB+">AB+</option>
                            <option value="AB-">AB-</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Address</label>
                        <textarea class="form-control" name="address" rows="1"><?php echo $_POST['address'] ?? ''; ?></textarea>
                    </div>
                </div>
                
                <h5 class="mb-3">Academic Information</h5>
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-label">Enrollment Date</label>
                        <input type="date" class="form-control" name="enrollment_date" value="<?php echo $_POST['enrollment_date'] ?? date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Course</label>
                        <input type="text" class="form-control" name="course" value="<?php echo $_POST['course'] ?? ''; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Semester</label>
                        <select class="form-select" name="semester">
                            <option value="">Select</option>
                            <?php for($i=1; $i<=8; $i++): ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?>th Semester</option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Batch Year</label>
                        <input type="number" class="form-control" name="batch_year" value="<?php echo $_POST['batch_year'] ?? date('Y'); ?>">
                    </div>
                </div>
                
                <h5 class="mb-3">Parent/Guardian Information</h5>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Parent Name</label>
                        <input type="text" class="form-control" name="parent_name" value="<?php echo $_POST['parent_name'] ?? ''; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Parent Phone</label>
                        <input type="text" class="form-control" name="parent_phone" value="<?php echo $_POST['parent_phone'] ?? ''; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Parent Email</label>
                        <input type="email" class="form-control" name="parent_email" value="<?php echo $_POST['parent_email'] ?? ''; ?>">
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Emergency Contact</label>
                        <input type="text" class="form-control" name="emergency_contact" value="<?php echo $_POST['emergency_contact'] ?? ''; ?>">
                    </div>
                </div>
                
                <div class="text-end">
                    <button type="reset" class="btn btn-secondary">Reset</button>
                    <button type="submit" class="btn btn-primary">Add Student</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function validateForm() {
    var password = document.getElementById('password').value;
    var confirm = document.getElementById('confirm_password').value;
    
    if (password != confirm) {
        alert('Passwords do not match!');
        return false;
    }
    return true;
}
</script>

<?php include '../includes/footer.php'; ?>