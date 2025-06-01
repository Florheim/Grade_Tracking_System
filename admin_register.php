<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
include 'config.php';

$error = '';
$message = '';
$username_prefill = '';
$email_prefill = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['admin_username'] ?? '');
    $email = trim($_POST['admin_email'] ?? '');
    $password = $_POST['admin_password'] ?? '';
    $username_prefill = htmlspecialchars($username);
    $email_prefill = htmlspecialchars($email);
    if (empty($username) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        $stmt_check = $conn->prepare("SELECT id FROM admins WHERE username = ?");
        if ($stmt_check === false) {
            $error = "Database error during username check: " . $conn->error;
        } else {
            $stmt_check->bind_param("s", $username);
            $stmt_check->execute();
            $stmt_check->store_result();

            if ($stmt_check->num_rows > 0) {
                $error = "Username already exists. Please choose a different one.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt_insert = $conn->prepare("INSERT INTO admins (username,  password) VALUES (?,  ?)");
                if ($stmt_insert === false) {
                    $error = "Database error during admin registration: " . $conn->error;
                } else {
                    $stmt_insert->bind_param("ss", $username, $hashed_password);

                    if ($stmt_insert->execute()) {
                        $message = "Admin registered successfully! You can now log in.";
                        $username_prefill = '';
                        $email_prefill = '';
                    } else {
                        $error = "Error registering admin: " . $stmt_insert->error;
                    }
                    $stmt_insert->close();
                }
            }
            $stmt_check->close();
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
    <title>Admin Create</title>
</head>
<body style="background-image: url('assets/Grade\ Track.jpg'); background-size: cover; background-repeat: no-repeat;">
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

        .container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 400px;
        }

        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }

        form label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        form input[type="text"],
        form input[type="email"],
        form input[type="password"] {
            width: calc(100% - 22px);
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        form button {
            width: 100%;
            padding: 10px;
            background-color: #274B07;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        form button:hover {
            background-color: #00C923;
        }

        .error-message {
            color: red;
            text-align: center;
            margin-bottom: 15px;
        }

        .success-message {
            color: green;
            text-align: center;
            margin-bottom: 15px;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
        }

        .login-link a {
            color: #007bff;
            text-decoration: none;
        }

        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
    <div class="container">
        <h2>ADMIN CREATE</h2>

        <?php if (!empty($error)): ?>
            <p class="error-message"><?php echo $error; ?></p>
        <?php endif; ?>

        <?php if (!empty($message)): ?>
            <p class="success-message"><?php echo $message; ?></p>
        <?php endif; ?>

        <form action="admin_register.php" method="post">
            <label for="username">Username:</label>
            <input type="text" id="username" name="admin_username" value="<?php echo $username_prefill; ?>" required><br>

            <label for="email">Email:</label>
            <input type="email" id="email" name="admin_email" value="<?php echo $email_prefill; ?>" required><br>

            <label for="password">Password:</label>
            <input type="password" id="password" name="admin_password" required><br>

            <button type="submit">Register</button>
        </form>

        <div class="login-link">
            Already have an account? <a href="admin_login.php">Login here</a>
        </div>
    </div>
</body>
</html>