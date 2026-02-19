<?php
// config/database.php
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'college_management_system');

// Create connection with error reporting
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    die("Connection failed: " . $e->getMessage());
}

// Set timezone
date_default_timezone_set('Asia/Kathmandu');

// Site configuration
define('SITE_NAME', 'College Management System');
define('SITE_URL', 'http://localhost/cms');

// Include files - ONLY ONCE
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
?>