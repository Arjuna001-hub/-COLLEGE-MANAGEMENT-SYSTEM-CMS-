<?php
// admin/delete_user.php
require_once '../config/database.php';

if (!isLoggedIn() || !hasRole('admin')) {
    redirect('../login.php');
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id) {
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Get user role before deleting
        $user = getUserById($id);
        
        // Delete role-specific records first
        if ($user['role'] == 'student') {
            $conn->query("DELETE FROM students WHERE user_id = $id");
        } elseif ($user['role'] == 'faculty') {
            $conn->query("DELETE FROM faculty WHERE user_id = $id");
        }
        
        // Delete user
        $conn->query("DELETE FROM users WHERE id = $id");
        
        $conn->commit();
        $_SESSION['message'] = "User deleted successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Error deleting user: " . $conn->error;
    }
}

redirect('users.php');
?>