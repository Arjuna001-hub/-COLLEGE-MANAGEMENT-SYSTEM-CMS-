<?php
// admin/get_user.php
require_once '../config/database.php';

if (!isLoggedIn() || !hasRole('admin')) {
    http_response_code(403);
    die(json_encode(['error' => 'Unauthorized']));
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $result = $conn->query("SELECT * FROM users WHERE id = $id");
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        header('Content-Type: application/json');
        echo json_encode($user);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
    }
}
?>