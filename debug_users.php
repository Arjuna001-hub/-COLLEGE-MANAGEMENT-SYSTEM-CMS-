<?php
// debug_users.php
require_once 'config/database.php';

echo "<h2>Database User Check</h2>";

// Check all users
$result = $conn->query("SELECT id, username, password, role, full_name FROM users");
echo "<h3>All Users in Database:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Username</th><th>Password</th><th>Role</th><th>Full Name</th></tr>";

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['username'] . "</td>";
        echo "<td>" . $row['password'] . "</td>";
        echo "<td>" . $row['role'] . "</td>";
        echo "<td>" . $row['full_name'] . "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='5'>No users found!</td></tr>";
}
echo "</table>";

// Check faculty records
echo "<h3>Faculty Records:</h3>";
$faculty = $conn->query("SELECT f.*, u.username, u.full_name FROM faculty f JOIN users u ON f.user_id = u.id");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Faculty ID</th><th>Username</th><th>Name</th><th>Department</th></tr>";

if ($faculty && $faculty->num_rows > 0) {
    while($row = $faculty->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['faculty_id'] . "</td>";
        echo "<td>" . $row['username'] . "</td>";
        echo "<td>" . $row['full_name'] . "</td>";
        echo "<td>" . ($row['department'] ?? 'N/A') . "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='5'>No faculty records found!</td></tr>";
}
echo "</table>";

// Check student records
echo "<h3>Student Records:</h3>";
$students = $conn->query("SELECT s.*, u.username, u.full_name FROM students s JOIN users u ON s.user_id = u.id");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Student ID</th><th>Username</th><th>Name</th><th>Course</th></tr>";

if ($students && $students->num_rows > 0) {
    while($row = $students->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['student_id'] . "</td>";
        echo "<td>" . $row['username'] . "</td>";
        echo "<td>" . $row['full_name'] . "</td>";
        echo "<td>" . ($row['course'] ?? 'N/A') . "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='5'>No student records found!</td></tr>";
}
echo "</table>";

// Test login for faculty
echo "<h3>Test Login for john_faculty:</h3>";
$test = $conn->query("SELECT * FROM users WHERE username = 'john_faculty' AND password = 'faculty123' AND role = 'faculty'");
if ($test && $test->num_rows > 0) {
    echo "<p style='color:green'>✓ john_faculty login credentials are correct!</p>";
} else {
    echo "<p style='color:red'>✗ john_faculty login credentials are INCORRECT!</p>";
}

// Test login for sarah_student
echo "<h3>Test Login for sarah_student:</h3>";
$test = $conn->query("SELECT * FROM users WHERE username = 'sarah_student' AND password = 'student123' AND role = 'student'");
if ($test && $test->num_rows > 0) {
    echo "<p style='color:green'>✓ sarah_student login credentials are correct!</p>";
} else {
    echo "<p style='color:red'>✗ sarah_student login credentials are INCORRECT!</p>";
}

echo "<p><a href='login.php'>Go to Login Page</a></p>";
?>