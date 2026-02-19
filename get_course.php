<?php
// admin/get_course.php
require_once '../config/database.php';

if (!isLoggedIn() || !hasRole('admin')) {
    http_response_code(403);
    die(json_encode(['error' => 'Unauthorized']));
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $result = $conn->query("SELECT * FROM courses WHERE id = $id");
    
    if ($result->num_rows > 0) {
        $course = $result->fetch_assoc();
        header('Content-Type: application/json');
        echo json_encode($course);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Course not found']);
    }
}
?>