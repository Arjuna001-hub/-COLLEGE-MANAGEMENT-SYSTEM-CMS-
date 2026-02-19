<?php
// includes/auth.php
// Authentication and authorization functions ONLY

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user has specific role
 */
function hasRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] == $role;
}

/**
 * Redirect to a URL
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Require authentication
 */
function requireAuth($login_url = '../login.php') {
    if (!isLoggedIn()) {
        $_SESSION['error'] = "Please login to access this page";
        redirect($login_url);
    }
}

/**
 * Require admin role
 */
function requireAdmin($redirect_url = '../login.php') {
    requireAuth($redirect_url);
    if (!hasRole('admin')) {
        $_SESSION['error'] = "Access denied. Admin privileges required.";
        redirect($redirect_url);
    }
}

/**
 * Require faculty role
 */
function requireFaculty($redirect_url = '../login.php') {
    requireAuth($redirect_url);
    if (!hasRole('faculty')) {
        $_SESSION['error'] = "Access denied. Faculty privileges required.";
        redirect($redirect_url);
    }
}

/**
 * Require student role
 */
function requireStudent($redirect_url = '../login.php') {
    requireAuth($redirect_url);
    if (!hasRole('student')) {
        $_SESSION['error'] = "Access denied. Student privileges required.";
        redirect($redirect_url);
    }
}

/**
 * Login user - FIXED VERSION
 */
function loginUser($conn, $username, $password, $role) {
    // Sanitize inputs
    $username = mysqli_real_escape_string($conn, trim($username));
    $password = trim($password); // Don't escape password as it's used in query
    $role = mysqli_real_escape_string($conn, trim($role));
    
    // Debug - Remove in production
    error_log("loginUser called - Username: $username, Role: $role");
    
    // Direct query with plain password (as per your requirement)
    $sql = "SELECT * FROM users WHERE username = '$username' AND password = '$password' AND role = '$role'";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['login_time'] = time();
        
        // Debug - Remove in production
        error_log("Login successful for user: " . $user['full_name']);
        
        return $user;
    }
    
    // Debug - Remove in production
    error_log("Login failed - No matching user found");
    if ($result) {
        error_log("Query returned " . $result->num_rows . " rows");
    } else {
        error_log("Query error: " . $conn->error);
    }
    
    return false;
}

/**
 * Logout user
 */
function logoutUser() {
    $_SESSION = array();
    
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    session_destroy();
}

/**
 * Get dashboard URL based on role
 */
function getDashboardUrl() {
    if (!isLoggedIn()) {
        return 'login.php';
    }
    
    switch ($_SESSION['user_role']) {
        case 'admin':
            return 'admin/dashboard.php';
        case 'faculty':
            return 'faculty/dashboard.php';
        case 'student':
            return 'student/dashboard.php';
        default:
            return 'login.php';
    }
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Refresh session timeout
 */
function refreshSession() {
    $_SESSION['login_time'] = time();
}

/**
 * Check if session expired
 */
function isSessionExpired($timeout = 1800) {
    if (isset($_SESSION['login_time'])) {
        return (time() - $_SESSION['login_time']) > $timeout;
    }
    return true;
}
?>