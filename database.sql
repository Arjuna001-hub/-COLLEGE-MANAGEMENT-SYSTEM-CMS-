-- Create database
CREATE DATABASE IF NOT EXISTS college_management_system;
USE college_management_system;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(50) NOT NULL, -- Using plain password as requested
    email VARCHAR(100) UNIQUE NOT NULL,
    role ENUM('admin', 'faculty', 'student') NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    profile_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Students table
CREATE TABLE students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE,
    student_id VARCHAR(20) UNIQUE NOT NULL,
    date_of_birth DATE,
    enrollment_date DATE,
    course VARCHAR(100),
    semester INT,
    batch_year INT,
    parent_name VARCHAR(100),
    parent_phone VARCHAR(20),
    parent_email VARCHAR(100),
    emergency_contact VARCHAR(20),
    blood_group VARCHAR(5),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Faculty table
CREATE TABLE faculty (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE,
    faculty_id VARCHAR(20) UNIQUE NOT NULL,
    designation VARCHAR(100),
    qualification VARCHAR(200),
    joining_date DATE,
    specialization VARCHAR(200),
    experience_years INT,
    department VARCHAR(100),
    office_hours TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Courses table
CREATE TABLE courses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_code VARCHAR(20) UNIQUE NOT NULL,
    course_name VARCHAR(100) NOT NULL,
    credits INT,
    semester INT,
    department VARCHAR(100),
    description TEXT,
    syllabus TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Course assignments (which faculty teaches which course)
CREATE TABLE course_assignments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    faculty_id INT,
    course_id INT,
    academic_year VARCHAR(20),
    semester VARCHAR(20),
    schedule TEXT,
    room VARCHAR(50),
    FOREIGN KEY (faculty_id) REFERENCES faculty(id),
    FOREIGN KEY (course_id) REFERENCES courses(id)
);

-- Student enrollment in courses
CREATE TABLE student_courses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT,
    course_id INT,
    enrollment_date DATE,
    status ENUM('enrolled', 'completed', 'dropped') DEFAULT 'enrolled',
    grade VARCHAR(2),
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (course_id) REFERENCES courses(id)
);

-- Attendance table
CREATE TABLE attendance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT,
    course_id INT,
    date DATE,
    status ENUM('present', 'absent', 'late', 'holiday') NOT NULL,
    marked_by INT,
    marked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    remarks TEXT,
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (course_id) REFERENCES courses(id),
    FOREIGN KEY (marked_by) REFERENCES faculty(id)
);

-- Assignments table
CREATE TABLE assignments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_id INT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    due_date DATE,
    total_marks INT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id),
    FOREIGN KEY (created_by) REFERENCES faculty(id)
);

-- Submissions table
CREATE TABLE submissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    assignment_id INT,
    student_id INT,
    submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    file_path VARCHAR(255),
    marks_obtained INT,
    feedback TEXT,
    status ENUM('submitted', 'graded', 'late') DEFAULT 'submitted',
    FOREIGN KEY (assignment_id) REFERENCES assignments(id),
    FOREIGN KEY (student_id) REFERENCES students(id)
);

-- Fee management
CREATE TABLE fees (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT,
    fee_type ENUM('tuition', 'exam', 'library', 'lab', 'hostel', 'transport', 'other') NOT NULL,
    amount DECIMAL(10,2),
    due_date DATE,
    paid_date DATE,
    status ENUM('paid', 'pending', 'overdue', 'partial') DEFAULT 'pending',
    payment_method VARCHAR(50),
    transaction_id VARCHAR(100),
    receipt_number VARCHAR(50),
    paid_amount DECIMAL(10,2),
    remarks TEXT,
    FOREIGN KEY (student_id) REFERENCES students(id)
);

-- Announcements
CREATE TABLE announcements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    content TEXT,
    posted_by INT,
    posted_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    target_audience ENUM('all', 'students', 'faculty', 'admin') DEFAULT 'all',
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    expiry_date DATE,
    attachments VARCHAR(255),
    FOREIGN KEY (posted_by) REFERENCES users(id)
);

-- Events
CREATE TABLE events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    event_date DATE,
    event_time TIME,
    venue VARCHAR(200),
    organized_by VARCHAR(100),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Library records
CREATE TABLE library (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT,
    book_title VARCHAR(200),
    book_author VARCHAR(100),
    issue_date DATE,
    return_date DATE,
    status ENUM('issued', 'returned', 'overdue') DEFAULT 'issued',
    fine_amount DECIMAL(10,2) DEFAULT 0,
    FOREIGN KEY (student_id) REFERENCES students(id)
);

-- Insert sample data with plain passwords
INSERT INTO users (username, password, email, role, full_name, phone, address) VALUES
('admin', 'admin123', 'admin@college.edu', 'admin', 'System Administrator,E.r.A.k Sharma', '9876543210', 'Kathmandu'),
('john_faculty', 'faculty123', 'john.smith@college.edu', 'faculty', 'Dr. John Smith', '9876543211', 'Lalitpur'),
('jane_faculty', 'faculty456', 'jane.doe@college.edu', 'faculty', 'Prof. Jane Doe', '9876543212', 'Bhaktapur'),
('sarah_student', 'student123', 'sarah.j@student.edu', 'student', 'Sarah Johnson', '9876543213', 'Kathmandu'),
('mike_student', 'student456', 'mike.b@student.edu', 'student', 'Mike Brown', '9876543214', 'Patan');

-- Insert faculty details
INSERT INTO faculty (user_id, faculty_id, designation, qualification, joining_date, specialization, experience_years, department) VALUES
(2, 'FAC001', 'Professor', 'Ph.D. Computer Science', '2020-01-15', 'Data Structures, Algorithms', 10, 'Computer Science'),
(3, 'FAC002', 'Associate Professor', 'M.Tech', '2021-03-10', 'Database Systems, Web Development', 8, 'Information Technology');

-- Insert student details
INSERT INTO students (user_id, student_id, date_of_birth, enrollment_date, course, semester, batch_year, parent_name, parent_phone, blood_group) VALUES
(4, 'STU001', '2002-05-15', '2023-09-01', 'Computer Science', 3, 2023, 'Robert Johnson', '9876543215', 'O+'),
(5, 'STU002', '2001-11-20', '2023-09-01', 'Information Technology', 3, 2023, 'David Brown', '9876543216', 'A+');

-- Insert courses
INSERT INTO courses (course_code, course_name, credits, semester, department, description) VALUES
('CS101', 'Data Structures', 4, 3, 'Computer Science', 'Introduction to data structures and algorithms'),
('CS102', 'Database Management Systems', 4, 3, 'Computer Science', 'Fundamentals of database design and SQL'),
('IT101', 'Web Development', 3, 3, 'Information Technology', 'HTML, CSS, JavaScript and PHP'),
('CS103', 'Operating Systems', 4, 4, 'Computer Science', 'Concepts of operating systems'),
('IT102', 'Computer Networks', 3, 4, 'Information Technology', 'Networking fundamentals');

-- Assign courses to faculty
INSERT INTO course_assignments (faculty_id, course_id, academic_year, semester, room) VALUES
(1, 1, '2024-2025', 'Fall', 'Room 301'),
(1, 2, '2024-2025', 'Fall', 'Room 302'),
(2, 3, '2024-2025', 'Fall', 'Lab 101');

-- Enroll students in courses
INSERT INTO student_courses (student_id, course_id, enrollment_date, status) VALUES
(1, 1, '2024-09-01', 'enrolled'),
(1, 2, '2024-09-01', 'enrolled'),
(1, 3, '2024-09-01', 'enrolled'),
(2, 1, '2024-09-01', 'enrolled'),
(2, 2, '2024-09-01', 'enrolled'),
(2, 3, '2024-09-01', 'enrolled');

-- Insert sample fees
INSERT INTO fees (student_id, fee_type, amount, due_date, status) VALUES
(1, 'tuition', 25000.00, '2024-10-15', 'paid'),
(1, 'library', 2000.00, '2024-10-15', 'pending'),
(2, 'tuition', 25000.00, '2024-10-15', 'pending');

-- Insert announcements
INSERT INTO announcements (title, content, posted_by, target_audience, priority) VALUES
('Welcome to New Semester', 'Welcome all students to the Fall 2024 semester. Classes start from September 1st.', 1, 'all', 'high'),
('Faculty Meeting', 'There will be a faculty meeting on August 30th at 10 AM in Conference Room.', 1, 'faculty', 'normal');


-- Fix passwords if needed
UPDATE users SET password = 'faculty123' WHERE username = 'john_faculty';
UPDATE users SET password = 'faculty456' WHERE username = 'jane_faculty';
UPDATE users SET password = 'student123' WHERE username = 'sarah_student';
UPDATE users SET password = 'student456' WHERE username = 'mike_student';
UPDATE users SET password = 'admin123' WHERE username = 'admin';