<?php
// includes/functions.php
// Only keep database-related helper functions here, NOT auth functions

// Function to sanitize input
function sanitize($data) {
    global $conn;
    return mysqli_real_escape_string($conn, htmlspecialchars(trim($data)));
}

// Function to get student by user_id
function getStudentByUserId($user_id) {
    global $conn;
    $user_id = intval($user_id);
    if ($user_id <= 0) return null;
    
    $result = $conn->query("SELECT s.*, u.full_name, u.email, u.phone, u.address 
                           FROM students s 
                           JOIN users u ON s.user_id = u.id 
                           WHERE s.user_id = $user_id");
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return null;
}

// Function to get faculty by user_id
function getFacultyByUserId($user_id) {
    global $conn;
    $user_id = intval($user_id);
    if ($user_id <= 0) return null;
    
    $result = $conn->query("SELECT f.*, u.full_name, u.email, u.phone, u.address 
                           FROM faculty f 
                           JOIN users u ON f.user_id = u.id 
                           WHERE f.user_id = $user_id");
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return null;
}

// Function to get course by id
function getCourseById($id) {
    global $conn;
    $id = intval($id);
    if ($id <= 0) return null;
    
    $result = $conn->query("SELECT * FROM courses WHERE id = $id");
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return null;
}

// Function to get all courses
function getAllCourses() {
    global $conn;
    $result = $conn->query("SELECT * FROM courses ORDER BY course_name");
    $courses = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $courses[] = $row;
        }
    }
    return $courses;
}

// Function to get students by course
function getStudentsByCourse($course_id) {
    global $conn;
    $course_id = intval($course_id);
    if ($course_id <= 0) return [];
    
    $result = $conn->query("SELECT s.*, u.full_name, u.email 
                           FROM students s 
                           JOIN student_courses sc ON s.id = sc.student_id 
                           JOIN users u ON s.user_id = u.id 
                           WHERE sc.course_id = $course_id AND sc.status = 'enrolled'");
    $students = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
    }
    return $students;
}

// Function to get faculty courses
function getFacultyCourses($faculty_id) {
    global $conn;
    $faculty_id = intval($faculty_id);
    if ($faculty_id <= 0) return [];
    
    $result = $conn->query("SELECT c.*, ca.semester, ca.room, ca.schedule 
                           FROM courses c 
                           JOIN course_assignments ca ON c.id = ca.course_id 
                           WHERE ca.faculty_id = $faculty_id");
    $courses = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $courses[] = $row;
        }
    }
    return $courses;
}

// Function to get student courses
function getStudentCourses($student_id) {
    global $conn;
    $student_id = intval($student_id);
    if ($student_id <= 0) return [];
    
    $result = $conn->query("SELECT c.*, sc.grade, sc.status, sc.enrollment_date 
                           FROM courses c 
                           JOIN student_courses sc ON c.id = sc.course_id 
                           WHERE sc.student_id = $student_id");
    $courses = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $courses[] = $row;
        }
    }
    return $courses;
}

// Function to get attendance
function getAttendance($student_id, $course_id = null, $start_date = null, $end_date = null) {
    global $conn;
    $student_id = intval($student_id);
    if ($student_id <= 0) return [];
    
    $sql = "SELECT a.*, c.course_name, u.full_name as marked_by_name 
            FROM attendance a 
            JOIN courses c ON a.course_id = c.id 
            LEFT JOIN faculty f ON a.marked_by = f.id 
            LEFT JOIN users u ON f.user_id = u.id 
            WHERE a.student_id = $student_id";
    
    if ($course_id) {
        $sql .= " AND a.course_id = " . intval($course_id);
    }
    if ($start_date) {
        $sql .= " AND a.date >= '$start_date'";
    }
    if ($end_date) {
        $sql .= " AND a.date <= '$end_date'";
    }
    
    $sql .= " ORDER BY a.date DESC";
    
    $result = $conn->query($sql);
    $attendance = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $attendance[] = $row;
        }
    }
    return $attendance;
}

// Function to calculate attendance percentage
function getAttendancePercentage($student_id, $course_id = null) {
    global $conn;
    $student_id = intval($student_id);
    if ($student_id <= 0) return 0;
    
    $sql = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present
            FROM attendance 
            WHERE student_id = $student_id";
    
    if ($course_id) {
        $sql .= " AND course_id = " . intval($course_id);
    }
    
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $data = $result->fetch_assoc();
        if ($data['total'] > 0) {
            return round(($data['present'] / $data['total']) * 100, 2);
        }
    }
    return 0;
}

// Function to get fees by student
function getStudentFees($student_id) {
    global $conn;
    $student_id = intval($student_id);
    if ($student_id <= 0) return [];
    
    $result = $conn->query("SELECT * FROM fees WHERE student_id = $student_id ORDER BY due_date DESC");
    $fees = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $fees[] = $row;
        }
    }
    return $fees;
}

// Function to get total fees
function getTotalFees($student_id) {
    global $conn;
    $student_id = intval($student_id);
    if ($student_id <= 0) return 0;
    
    $result = $conn->query("SELECT SUM(amount) as total FROM fees WHERE student_id = $student_id");
    if ($result && $result->num_rows > 0) {
        $data = $result->fetch_assoc();
        return $data['total'] ?: 0;
    }
    return 0;
}

// Function to get paid fees
function getPaidFees($student_id) {
    global $conn;
    $student_id = intval($student_id);
    if ($student_id <= 0) return 0;
    
    $result = $conn->query("SELECT SUM(amount) as total FROM fees WHERE student_id = $student_id AND status = 'paid'");
    if ($result && $result->num_rows > 0) {
        $data = $result->fetch_assoc();
        return $data['total'] ?: 0;
    }
    return 0;
}

// Function to generate unique ID
function generateUniqueId($prefix, $table, $field) {
    global $conn;
    $id = $prefix . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    $result = $conn->query("SELECT COUNT(*) as count FROM $table WHERE $field = '$id'");
    if ($result) {
        $row = $result->fetch_assoc();
        if ($row['count'] > 0) {
            return generateUniqueId($prefix, $table, $field);
        }
    }
    
    return $id;
}

// Function to format date
function formatDate($date, $format = 'd M Y') {
    if (empty($date) || $date == '0000-00-00' || $date == '1970-01-01') {
        return 'N/A';
    }
    return date($format, strtotime($date));
}

// Function to get time ago
function timeAgo($timestamp) {
    if (empty($timestamp)) {
        return 'Unknown';
    }
    
    $time_ago = strtotime($timestamp);
    $current_time = time();
    $time_difference = $current_time - $time_ago;
    $seconds = $time_difference;
    
    $minutes = round($seconds / 60);
    $hours = round($seconds / 3600);
    $days = round($seconds / 86400);
    $weeks = round($seconds / 604800);
    $months = round($seconds / 2629440);
    $years = round($seconds / 31553280);
    
    if ($seconds <= 60) {
        return "Just Now";
    } else if ($minutes <= 60) {
        return ($minutes == 1) ? "1 minute ago" : "$minutes minutes ago";
    } else if ($hours <= 24) {
        return ($hours == 1) ? "1 hour ago" : "$hours hours ago";
    } else if ($days <= 7) {
        return ($days == 1) ? "yesterday" : "$days days ago";
    } else if ($weeks <= 4.3) {
        return ($weeks == 1) ? "1 week ago" : "$weeks weeks ago";
    } else if ($months <= 12) {
        return ($months == 1) ? "1 month ago" : "$months months ago";
    } else {
        return ($years == 1) ? "1 year ago" : "$years years ago";
    }
}

// Function to upload file
function uploadFile($file, $target_dir = '../assets/uploads/') {
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['success' => false, 'message' => 'No file uploaded'];
    }
    
    $target_file = $target_dir . basename($file["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    // Check if image file is a actual image or fake image
    if(isset($_POST["submit"])) {
        $check = getimagesize($file["tmp_name"]);
        if($check === false) {
            return ['success' => false, 'message' => 'File is not an image.'];
        }
    }
    
    // Check file size (5MB max)
    if ($file["size"] > 5000000) {
        return ['success' => false, 'message' => 'File is too large. Max size 5MB.'];
    }
    
    // Allow certain file formats
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
    && $imageFileType != "gif" && $imageFileType != "pdf" && $imageFileType != "doc" && $imageFileType != "docx") {
        return ['success' => false, 'message' => 'Only JPG, JPEG, PNG, GIF, PDF, DOC & DOCX files are allowed.'];
    }
    
    // Generate unique filename
    $new_filename = uniqid() . '.' . $imageFileType;
    $target_file = $target_dir . $new_filename;
    
    // Create directory if it doesn't exist
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return ['success' => true, 'filename' => $new_filename];
    } else {
        return ['success' => false, 'message' => 'Error uploading file.'];
    }
}
?>