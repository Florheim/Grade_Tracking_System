<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

require_once 'config.php';

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit;
}

$student_id = $_SESSION['student_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_code = trim($_POST['subject_code'] ?? '');
    $subject_name = trim($_POST['subject_name'] ?? '');
    $academic_year = trim($_POST['academic_year'] ?? '');
    $semester = trim($_POST['semester'] ?? '');

    if (empty($subject_code) || empty($subject_name) || empty($academic_year) || empty($semester)) {
        $_SESSION['error'] = "Please fill in all fields to add a subject.";
        header("Location: dashboard.php");
        exit;
    }

    $subject_id = null; 

    $stmt_check_subj = $conn->prepare("SELECT id FROM subjects WHERE subject_code = ? AND subject_name = ?");
    if ($stmt_check_subj === false) {
        $_SESSION['error'] = "Database prepare error (subject check): " . $conn->error;
        header("Location: dashboard.php");
        exit;
    }
    $stmt_check_subj->bind_param("ss", $subject_code, $subject_name);
    $stmt_check_subj->execute();
    $stmt_check_subj->store_result(); 

    if ($stmt_check_subj->num_rows > 0) {
        $stmt_check_subj->bind_result($subject_id);
        $stmt_check_subj->fetch();
    } else {
        $insert_subj = $conn->prepare("INSERT INTO subjects (subject_code, subject_name) VALUES (?, ?)");
        if ($insert_subj === false) {
            $_SESSION['error'] = "Database prepare error (subject insert): " . $conn->error;
            $stmt_check_subj->close(); 
            header("Location: dashboard.php");
            exit;
        }
        $insert_subj->bind_param("ss", $subject_code, $subject_name);
        if ($insert_subj->execute()) {
            $subject_id = $insert_subj->insert_id; 
        } else {
            $_SESSION['error'] = "Error adding new subject: " . $insert_subj->error;
            $insert_subj->close(); 
            $stmt_check_subj->close(); 
            header("Location: dashboard.php");
            exit;
        }
        $insert_subj->close(); 
    }
    $stmt_check_subj->close();

    if ($subject_id === null) {
        $_SESSION['error'] = "Could not determine subject ID after check or insert.";
        header("Location: dashboard.php");
        exit;
    }

    $check_enroll = $conn->prepare("SELECT id FROM enrollments WHERE student_id = ? AND subject_id = ? AND academic_year = ? AND semester = ?");
    if ($check_enroll === false) {
        $_SESSION['error'] = "Database prepare error (enrollment check): " . $conn->error;
        header("Location: dashboard.php");
        exit;
    }
    $check_enroll->bind_param("iiss", $student_id, $subject_id, $academic_year, $semester);
    $check_enroll->execute();
    $check_enroll->store_result();

    if ($check_enroll->num_rows == 0) {
        $check_enroll->close(); 

        $insert_enroll = $conn->prepare("INSERT INTO enrollments (student_id, subject_id, academic_year, semester) VALUES (?, ?, ?, ?)");
        if ($insert_enroll === false) {
            $_SESSION['error'] = "Database prepare error (enrollment insert): " . $conn->error;
            header("Location: dashboard.php");
            exit;
        }
        $insert_enroll->bind_param("iiss", $student_id, $subject_id, $academic_year, $semester);

        if ($insert_enroll->execute()) {
            $enrollment_id = $conn->insert_id; 
            $insert_enroll->close(); 

            $insert_grade = $conn->prepare("INSERT INTO grades (enrollment_id, status) VALUES (?, 'Not Available')");
            if ($insert_grade === false) {
                $_SESSION['error'] = "Database prepare error (grade insert): " . $conn->error;
                header("Location: dashboard.php");
                exit;
            }
            $insert_grade->bind_param("i", $enrollment_id);
            if ($insert_grade->execute()) {
                $_SESSION['success'] = "Subject '{$subject_name}' added and enrolled successfully!";
            } else {
                $_SESSION['error'] = "Subject enrolled, but failed to create grade record: " . $insert_grade->error;
            }
            $insert_grade->close(); 
        } else {
            $_SESSION['error'] = "Error enrolling in subject: " . $insert_enroll->error;
        }
    } else {
        $_SESSION['error'] = "You are already enrolled in '{$subject_name}' for {$semester}, {$academic_year}.";
        $check_enroll->close(); 
    }

    $conn->close();
    header("Location: dashboard.php");
    exit; 

} else {
    header("Location: dashboard.php");
    exit;
}
?>