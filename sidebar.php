<?php
// includes/sidebar.php
$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['user_role'] ?? '';
?>
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <i class="fas fa-graduation-cap" style="font-size: 2.5em;"></i>
        <h3><?php echo SITE_NAME; ?></h3>
        <p><?php echo ucfirst($role); ?> Panel</p>
    </div>
    
    <div class="nav flex-column">
        <?php if($role == 'admin'): ?>
            <a href="<?php echo SITE_URL; ?>/admin/dashboard.php" class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/users.php" class="nav-link <?php echo $current_page == 'users.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span>Users</span>
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/students.php" class="nav-link <?php echo $current_page == 'students.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-graduate"></i>
                <span>Students</span>
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/faculty.php" class="nav-link <?php echo $current_page == 'faculty.php' ? 'active' : ''; ?>">
                <i class="fas fa-chalkboard-teacher"></i>
                <span>Faculty</span>
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/courses.php" class="nav-link <?php echo $current_page == 'courses.php' ? 'active' : ''; ?>">
                <i class="fas fa-book"></i>
                <span>Courses</span>
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/fees.php" class="nav-link <?php echo $current_page == 'fees.php' ? 'active' : ''; ?>">
                <i class="fas fa-money-bill-wave"></i>
                <span>Fees</span>
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/attendance.php" class="nav-link <?php echo $current_page == 'attendance.php' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-check"></i>
                <span>Attendance</span>
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/announcements.php" class="nav-link <?php echo $current_page == 'announcements.php' ? 'active' : ''; ?>">
                <i class="fas fa-bullhorn"></i>
                <span>Announcements</span>
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/reports.php" class="nav-link <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i>
                <span>Reports</span>
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/settings.php" class="nav-link <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
            
        <?php elseif($role == 'faculty'): ?>
            <a href="<?php echo SITE_URL; ?>/faculty/dashboard.php" class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="<?php echo SITE_URL; ?>/faculty/my_courses.php" class="nav-link <?php echo $current_page == 'my_courses.php' ? 'active' : ''; ?>">
                <i class="fas fa-book"></i>
                <span>My Courses</span>
            </a>
            <a href="<?php echo SITE_URL; ?>/faculty/mark_attendance.php" class="nav-link <?php echo $current_page == 'mark_attendance.php' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-check"></i>
                <span>Mark Attendance</span>
            </a>
            <a href="<?php echo SITE_URL; ?>/faculty/view_attendance.php" class="nav-link <?php echo $current_page == 'view_attendance.php' ? 'active' : ''; ?>">
                <i class="fas fa-eye"></i>
                <span>View Attendance</span>
            </a>
            <a href="<?php echo SITE_URL; ?>/faculty/assignments.php" class="nav-link <?php echo $current_page == 'assignments.php' ? 'active' : ''; ?>">
                <i class="fas fa-tasks"></i>
                <span>Assignments</span>
            </a>
            <a href="<?php echo SITE_URL; ?>/faculty/grades.php" class="nav-link <?php echo $current_page == 'grades.php' ? 'active' : ''; ?>">
                <i class="fas fa-star"></i>
                <span>Grades</span>
            </a>
            <a href="<?php echo SITE_URL; ?>/faculty/profile.php" class="nav-link <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                <i class="fas fa-user"></i>
                <span>Profile</span>
            </a>
            <a href="<?php echo SITE_URL; ?>/faculty/settings.php" class="nav-link <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
            
        <?php elseif($role == 'student'): ?>
            <a href="<?php echo SITE_URL; ?>/student/dashboard.php" class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="<?php echo SITE_URL; ?>/student/my_courses.php" class="nav-link <?php echo $current_page == 'my_courses.php' ? 'active' : ''; ?>">
                <i class="fas fa-book"></i>
                <span>My Courses</span>
            </a>
            <a href="<?php echo SITE_URL; ?>/student/view_attendance.php" class="nav-link <?php echo $current_page == 'view_attendance.php' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-check"></i>
                <span>Attendance</span>
            </a>
            <a href="<?php echo SITE_URL; ?>/student/view_grades.php" class="nav-link <?php echo $current_page == 'view_grades.php' ? 'active' : ''; ?>">
                <i class="fas fa-star"></i>
                <span>Grades</span>
            </a>
            <a href="<?php echo SITE_URL; ?>/student/fees.php" class="nav-link <?php echo $current_page == 'fees.php' ? 'active' : ''; ?>">
                <i class="fas fa-money-bill-wave"></i>
                <span>Fees</span>
            </a>
            <a href="<?php echo SITE_URL; ?>/student/assignments.php" class="nav-link <?php echo $current_page == 'assignments.php' ? 'active' : ''; ?>">
                <i class="fas fa-tasks"></i>
                <span>Assignments</span>
            </a>
            <a href="<?php echo SITE_URL; ?>/student/profile.php" class="nav-link <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                <i class="fas fa-user"></i>
                <span>Profile</span>
            </a>
            <a href="<?php echo SITE_URL; ?>/student/settings.php" class="nav-link <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
        <?php endif; ?>
        
        <a href="<?php echo SITE_URL; ?>/logout.php" class="nav-link">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</div>