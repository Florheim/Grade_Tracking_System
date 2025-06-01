<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start(); 

require_once 'config.php'; 
require_once 'send_otp.php'; 

$error = ''; 
$message = ''; 
$student_id_no_prefill = '';
$email_prefill = '';
$name_prefill = '';
$course_prefill = ''; 
$available_courses = [
    'Bachelor of Science in Computer Science (BSCS)',
    'Bachelor of Science in Business Administration (BSBA)',
    'Bachelor of Science in Midwifery (BSM)',
    'Bachelor of Science in Nursing (BSN)',
    'Bachelor of Arts in English Language (ABEL)',
];


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id_no = trim($_POST['student_id_no'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $course = trim($_POST['course'] ?? ''); 
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $student_id_no_prefill = htmlspecialchars($student_id_no);
    $email_prefill = htmlspecialchars($email);
    $name_prefill = htmlspecialchars($name);
    $course_prefill = in_array($course, $available_courses) ? htmlspecialchars($course) : '';

    if (empty($student_id_no) || empty($email) || empty($name) || empty($course) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) { 
        $error = "Password must be at least 6 characters long.";
    } elseif (!in_array($course, $available_courses)) { 
        $error = "Please select a valid course from the list.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $otp_code = rand(100000, 999999); 

        $stmt_check = $conn->prepare("SELECT id FROM students WHERE student_id_no = ? OR email = ?");
        if ($stmt_check === false) {
            $error = "Database error during uniqueness check: " . $conn->error;
        } else {
            $stmt_check->bind_param("ss", $student_id_no, $email);
            $stmt_check->execute();
            $stmt_check->store_result(); 

            if ($stmt_check->num_rows > 0) {
                $error = "Student ID or Email already registered. Please use a different one or login.";
            } else {
                $stmt_insert = $conn->prepare("INSERT INTO students (student_id_no, email, name, course, password, otp_code, is_verified) VALUES (?, ?, ?, ?, ?, ?, 0)");
                if ($stmt_insert === false) {
                    $error = "Database error during registration: " . $conn->error;
                } else {
                    $stmt_insert->bind_param("ssssss", $student_id_no, $email, $name, $course, $hashed_password, $otp_code);

                    if ($stmt_insert->execute()) {
                        if (sendOTP($email, $otp_code)) {
                            $_SESSION['email_for_otp_verification_grade_app'] = $email;
                            $_SESSION['success_message_grade_app'] = "Registration successful! A 6-digit OTP has been sent to your email. Please verify your account.";
                            header("Location: verify_otp.php");
                            exit; 
                        } else {
                            $error = "Registration successful, but failed to send OTP email. Please check your email address or contact support.";
                            error_log("Failed to send OTP to $email during registration: " . $conn->error); // Log the server-side error
                        }
                    } else {
                        $error = "Error registering student: " . $stmt_insert->error;
                    }
                    $stmt_insert->close();
                }
            }
            $stmt_check->close();
        }
    }
    if ($conn && !$conn->connect_error) {
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Grade Tracking System</title>
    <style>
        /* Basic styling for the form, you can move this to your 'css/style.css' file */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .register-container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            width: 400px;
            text-align: center;
        }
        h2 {
            color: #333;
            margin-bottom: 25px;
        }
        .form-group {
            margin-bottom: 15px;
            text-align: left;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group select { 
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box; 
        }
        .form-group button {
            width: 100%;
            padding: 12px;
            background-color: #007bff; 
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 15px;
            transition: background-color 0.3s ease;
        }
        .form-group button:hover {
            background-color: #0056b3;
        }
        .error-message {
            color: #dc3545; 
            background-color: #f8d7da; 
            border: 1px solid #f5c6cb;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: left;
        }
        .login-link {
            margin-top: 20px;
            font-size: 0.95em;
            color: #666;
        }
        .login-link a {
            color: #007bff;
            text-decoration: none;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body style="background-image: url('assets/Grade\ Track.jpg'); background-size: cover; background-repeat: no-repeat;">
    <div class="register-container">
        <h2>Create Student Account</h2>

        <?php if (!empty($error)): ?>
            <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form action="register.php" method="post">
            <div class="form-group">
                <label for="student_id_no">Student ID No:</label>
                <input type="text" name="student_id_no" id="student_id_no" value="<?php echo $student_id_no_prefill; ?>" required autofocus>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" name="email" id="email" value="<?php echo $email_prefill; ?>" required>
            </div>
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" name="name" id="name" value="<?php echo $name_prefill; ?>" required>
            </div>
            <div class="form-group">
                <label for="course">Course:</label>
                <select id="course" name="course" required>
                    <option value="">Select your course</option>
                    <?php foreach ($available_courses as $course_option): ?>
                        <option value="<?= htmlspecialchars($course_option) ?>"
                            <?= ($course_prefill == $course_option) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($course_option) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" name="password" id="password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" name="confirm_password" id="confirm_password" required>
            </div>
            <div class="form-group">
                <button type="submit">Register</button>
            </div>
        </form>
        <p class="login-link">Already have an account? <a href="login.php">Login here</a></p>
        </div>
</body>
</html>