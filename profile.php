<?php
// faculty/profile.php
require_once '../config/database.php';

if (!isLoggedIn() || !hasRole('faculty')) {
    redirect('../login.php');
}

$page_title = 'My Profile';
$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);
$faculty = getFacultyByUserId($user_id);

$message = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $full_name = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    
    // Update users table
    $sql1 = "UPDATE users SET 
             full_name = '$full_name',
             email = '$email',
             phone = '$phone',
             address = '$address'
             WHERE id = $user_id";
    
    // Update faculty table
    $qualification = sanitize($_POST['qualification']);
    $specialization = sanitize($_POST['specialization']);
    $office_hours = sanitize($_POST['office_hours']);
    
    $sql2 = "UPDATE faculty SET 
             qualification = '$qualification',
             specialization = '$specialization',
             office_hours = '$office_hours'
             WHERE user_id = $user_id";
    
    if ($conn->query($sql1) && $conn->query($sql2)) {
        $_SESSION['full_name'] = $full_name;
        $message = "Profile updated successfully!";
        // Refresh data
        $user = getUserById($user_id);
        $faculty = getFacultyByUserId($user_id);
    } else {
        $error = "Error updating profile: " . $conn->error;
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
            <h4 class="mb-0">My Profile</h4>
            <small class="text-muted">View and update your profile information</small>
        </div>
    </div>
    
    <!-- Messages -->
    <?php if($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Profile Card -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="user-avatar mx-auto mb-3" style="width: 120px; height: 120px; font-size: 3em;">
                        <?php echo substr($user['full_name'], 0, 1); ?>
                    </div>
                    <h4><?php echo $user['full_name']; ?></h4>
                    <p class="text-muted"><?php echo $faculty['designation'] ?? 'Faculty'; ?></p>
                    <p class="text-muted"><?php echo $faculty['faculty_id']; ?></p>
                    
                    <div class="mt-3">
                        <span class="badge bg-primary"><?php echo $faculty['department'] ?? 'Not Assigned'; ?></span>
                        <span class="badge bg-success"><?php echo $faculty['experience_years'] ?? 0; ?> Years Experience</span>
                    </div>
                </div>
            </div>
            
            <!-- Quick Info -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">Quick Information</h6>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span><i class="fas fa-calendar"></i> Joined</span>
                            <span><?php echo $faculty['joining_date'] ? formatDate($faculty['joining_date']) : 'N/A'; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span><i class="fas fa-graduation-cap"></i> Qualification</span>
                            <span><?php echo $faculty['qualification'] ?: 'N/A'; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span><i class="fas fa-flask"></i> Specialization</span>
                            <span><?php echo $faculty['specialization'] ?: 'N/A'; ?></span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Edit Profile Form -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Edit Profile</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <h6 class="mb-3">Personal Information</h6>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name</label>
                                <input type="text" class="form-control" name="full_name" value="<?php echo $user['full_name']; ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" value="<?php echo $user['email']; ?>" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" class="form-control" name="phone" value="<?php echo $user['phone']; ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Address</label>
                                <input type="text" class="form-control" name="address" value="<?php echo $user['address']; ?>">
                            </div>
                        </div>
                        
                        <h6 class="mb-3 mt-4">Professional Information</h6>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Qualification</label>
                                <input type="text" class="form-control" name="qualification" value="<?php echo $faculty['qualification']; ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Specialization</label>
                                <input type="text" class="form-control" name="specialization" value="<?php echo $faculty['specialization']; ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Office Hours</label>
                            <textarea class="form-control" name="office_hours" rows="2"><?php echo $faculty['office_hours']; ?></textarea>
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>