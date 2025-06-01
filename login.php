<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

include 'config.php';

$error = '';
$email_prefill = '';

if (isset($_SESSION['login_error'])) {
    $error = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
}
if (isset($_SESSION['login_email_prefill'])) {
    $email_prefill = $_SESSION['login_email_prefill'];
    unset($_SESSION['login_email_prefill']);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $email_prefill = htmlspecialchars($email);

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        $stmt = $conn->prepare("SELECT id, email, password, name FROM students WHERE email=?");
        if ($stmt === false) {
            $error = "Database prepare failed: " . $conn->error;
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['student_id'] = $user['id'];
                $_SESSION['student_name'] = $user['name'];
                session_regenerate_id(true);
                header("Location: dashboard.php");
                exit;
            } else {
                $error = "Invalid credentials.";
            }
            $stmt->close();
        }
    }
    $conn->close();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .login-container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 300px;
            text-align: center;
        }

        h2 {
            color: #333;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input[type="email"],
        .form-group input[type="password"] {
            width: calc(100% - 22px);
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .form-group button {
            width: 100%;
            padding: 10px;
            background-color: #274B07;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
        }

        .form-group button:hover {
            background-color: #00C923;
        }

        .error-message {
            color: red;
            margin-bottom: 15px;
        }
    </style>
</head>
<body style="background-image: url('assets/Grade\ Track.jpg'); background-size: cover; background-repeat: no-repeat;">
    <div class="login-container">
        <h2>STUDENT LOGIN</h2>
        <?php if (!empty($error)): ?>
            <p class="error-message"><?php echo $error; ?></p>
        <?php endif; ?>
        <form action="login.php" method="post">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" name="email" id="email" value="<?php echo $email_prefill; ?>" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" name="password" id="password" required>
            </div>
            <div class="form-group">
                <button type="submit">Login</button>
            </div>
        </form>
        <p class="login-link">No Account? <a href="register.php">Create</a></p>
    </div>
</body>
</html>