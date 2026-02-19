<?php
// faculty/settings.php
require_once '../config/database.php';

if (!isLoggedIn() || !hasRole('faculty')) {
    redirect('../login.php');
}

$page_title = 'Settings';
$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);

$message = '';
$error = '';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verify current password
    $result = $conn->query("SELECT password FROM users WHERE id = $user_id");
    $current = $result->fetch_assoc();
    
    if ($current['password'] != $current_password) {
        $error = "Current password is incorrect!";
    } elseif ($new_password != $confirm_password) {
        $error = "New passwords do not match!";
    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters!";
    } else {
        $sql = "UPDATE users SET password = '$new_password' WHERE id = $user_id";
        if ($conn->query($sql)) {
            $message = "Password changed successfully!";
        } else {
            $error = "Error changing password: " . $conn->error;
        }
    }
}

// Handle notification preferences
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_preferences'])) {
    // Save preferences to database or session
    $message = "Notification preferences saved!";
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
            <h4 class="mb-0">Settings</h4>
            <small class="text-muted">Manage your account settings</small>
        </div>
    </div>
    
    <!-- Messages -->
    <?php if($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <!-- Settings Tabs -->
    <ul class="nav nav-tabs mb-4" id="settingsTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="password-tab" data-bs-toggle="tab" data-bs-target="#password" type="button">
                <i class="fas fa-lock"></i> Change Password
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="notifications-tab" data-bs-toggle="tab" data-bs-target="#notifications" type="button">
                <i class="fas fa-bell"></i> Notifications
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="preferences-tab" data-bs-toggle="tab" data-bs-target="#preferences" type="button">
                <i class="fas fa-sliders-h"></i> Preferences
            </button>
        </li>
    </ul>
    
    <!-- Tab Content -->
    <div class="tab-content" id="settingsTabContent">
        <!-- Change Password -->
        <div class="tab-pane fade show active" id="password" role="tabpanel">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Change Password</h5>
                </div>
                <div class="card-body">
                    <form method="POST" onsubmit="return validatePassword()">
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" class="form-control" name="current_password" id="current_password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-control" name="new_password" id="new_password" required>
                            <small class="text-muted">Minimum 6 characters</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" name="confirm_password" id="confirm_password" required>
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" name="change_password" class="btn btn-primary">
                                Change Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Notifications -->
        <div class="tab-pane fade" id="notifications" role="tabpanel">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Notification Preferences</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Email Notifications</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="email_assignments" checked>
                                <label class="form-check-label" for="email_assignments">
                                    New assignment submissions
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="email_attendance" checked>
                                <label class="form-check-label" for="email_attendance">
                                    Attendance reminders
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="email_announcements" checked>
                                <label class="form-check-label" for="email_announcements">
                                    College announcements
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">In-App Notifications</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="app_submissions" checked>
                                <label class="form-check-label" for="app_submissions">
                                    Show submission alerts
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="app_grades" checked>
                                <label class="form-check-label" for="app_grades">
                                    Grade posting confirmations
                                </label>
                            </div>
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" name="save_preferences" class="btn btn-primary">
                                Save Preferences
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Preferences -->
        <div class="tab-pane fade" id="preferences" role="tabpanel">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Display Preferences</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Default Dashboard View</label>
                            <select class="form-select">
                                <option>Compact</option>
                                <option selected>Detailed</option>
                                <option>Grid</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Items Per Page</label>
                            <select class="form-select">
                                <option>10</option>
                                <option selected>25</option>
                                <option>50</option>
                                <option>100</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Theme</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="theme" id="light" checked>
                                <label class="form-check-label" for="light">Light</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="theme" id="dark">
                                <label class="form-check-label" for="dark">Dark</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="theme" id="auto">
                                <label class="form-check-label" for="auto">Auto (System Default)</label>
                            </div>
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">Save Preferences</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function validatePassword() {
    var newPass = document.getElementById('new_password').value;
    var confirmPass = document.getElementById('confirm_password').value;
    
    if (newPass.length < 6) {
        alert('Password must be at least 6 characters long!');
        return false;
    }
    
    if (newPass != confirmPass) {
        alert('Passwords do not match!');
        return false;
    }
    
    return true;
}
</script>

<?php include '../includes/footer.php'; ?>