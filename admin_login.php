<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

include 'config.php';

$error = '';
$username_prefill = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $username_prefill = htmlspecialchars($username);

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        $stmt = $conn->prepare("SELECT id, username, password FROM admins WHERE username = ?");
        if ($stmt === false) {
            $error = "Database prepare failed: " . $conn->error;
        } else {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $admin = $result->fetch_assoc();
                if (password_verify($password, $admin['password'])) {
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    session_regenerate_id(true);
                    header("Location: admin_dashboard.php");
                    exit;
                } else {
                    $error = "Incorrect password.";
                }
            } else {
                $error = "Admin account not found.";
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
    <title>Admin Login</title>
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
        .form-group input[type="text"],
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
        <h2>Admin Login</h2>
        <?php if (!empty($error)): ?>
            <p class="error-message"><?php echo $error; ?></p>
        <?php endif; ?>
        <form action="admin_login.php" method="POST">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" value="<?php echo $username_prefill; ?>" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <button type="submit">Login</button>
            </div>
        </form>
    </div>
</body>
</html>