<?php
session_start();
include 'config.php'; 

$error = '';
$message = '';
$email_for_verification = $_SESSION['email_for_otp_verification_grade_app'] ?? (isset($_GET['email']) ? trim($_GET['email']) : '');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email_from_post = trim($_POST['email'] ?? '');
    $email_to_use = !empty($email_from_post) ? $email_from_post : $email_for_verification;

    if (isset($_POST['otp1'], $_POST['otp2'], $_POST['otp3'], $_POST['otp4'], $_POST['otp5'], $_POST['otp6'])) {
        $otp = implode('', [
            trim($_POST['otp1']),
            trim($_POST['otp2']),
            trim($_POST['otp3']),
            trim($_POST['otp4']),
            trim($_POST['otp5']),
            trim($_POST['otp6'])
        ]);

        if (empty($email_to_use) || empty($otp) || strlen($otp) !== 6 || !ctype_digit($otp)) {
            $error = "Please enter a valid 6-digit OTP and ensure your email is present.";
        } else {
            $stmt = $conn->prepare("SELECT id FROM students WHERE email=? AND otp_code=?");
            if ($stmt === false) {
                $error = "Database query preparation failed: " . $conn->error;
            } else {
                $stmt->bind_param("ss", $email_to_use, $otp);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $stmt_update = $conn->prepare("UPDATE students SET is_verified=1, otp_code=NULL WHERE email=?");
                    if ($stmt_update === false) {
                        $error = "Database update preparation failed: " . $conn->error;
                    } else {
                        $stmt_update->bind_param("s", $email_to_use);
                        if ($stmt_update->execute()) {
                            $message = "Account verified successfully! You can now log in.";
                            unset($_SESSION['email_for_otp_verification_grade_app']);
                            header("Location: login.php?status=verified&message=" . urlencode($message));
                            exit;
                        } else {
                            $error = "Error updating verification status: " . $stmt_update->error;
                        }
                        $stmt_update->close();
                    }
                } else {
                    $error = "Invalid OTP. Please check the code and try again.";
                }
                $stmt->close();
            }
        }
    } else {
        $error = "Please enter all 6 digits of the OTP.";
    }
}

if ($conn && !$conn->connect_error) {
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - Grade Tracking System</title>
    <style>
        body {
            border: 1px solid #ccc;
            border-radius: 8px; 
            text-align: center;
            width: 550px;
            margin: 80px auto; 
            padding: 30px; 
            font-size: 18px;
            font-family: 'Arial', sans-serif;
            background-color: #ffffff; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.1); 
        }

        h2 {
            color: #c30010;
            margin-bottom: 25px;
            font-size: 2.2em;
        }

        input[type="email"] {
            width: 90%; 
            padding: 12px; 
            margin-bottom: 20px; 
            border: 1px solid #c30010;
            border-radius: 5px; 
            text-align: center;
            font-size: 1.1em;
            color: #c30010;
            background-color: #ffebeb; 
            font-weight: bold;
        }

        .otp-box {
            width: 55px; 
            height: 55px;
            font-size: 1.8em;
            text-align: center;
            margin: 0 5px; 
            border: 2px solid #c30010;
            border-radius: 8px; 
            outline: none; 
            transition: border-color 0.3s ease, box-shadow 0.3s ease; 
        }

        .otp-box:focus {
            border-color: #ff2c2c; 
            box-shadow: 0 0 8px rgba(255, 44, 44, 0.4); 
        }

        .otp-container {
            display: flex;
            justify-content: center;
            margin-bottom: 30px; 
        }

        button {
            width: 90%; 
            padding: 12px;
            background-color: #c30010;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: bold;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        button:hover {
            background-color: #ff2c2c;
            transform: translateY(-2px); 
        }
        .error-message {
            color: #dc3545; 
            margin-bottom: 15px;
            font-weight: bold;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px;
            border-radius: 5px;
        }
        .success-message {
            color: #28a745; 
            margin-bottom: 15px;
            font-weight: bold;
            background-color: #d4edda; 
            border: 1px solid #c3e6cb;
            padding: 10px;
            border-radius: 5px;
        }
    </style>
</head>
<body style="background-image: url('assets/Grade\ Track.jpg'); background-size: cover; background-repeat: no-repeat;">
    <h2>Verify Your OTP</h2>

    <?php if (!empty($error)): ?>
        <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <?php if (!empty($message)): ?>
        <p class="success-message"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <form action="verify_otp.php" method="POST"> <input type="email" name="email" value="<?php echo htmlspecialchars($email_for_verification); ?>" required readonly><br>
        <div class="otp-container">
            <input type="text" name="otp1" class="otp-box" maxlength="1" pattern="\d" inputmode="numeric" required>
            <input type="text" name="otp2" class="otp-box" maxlength="1" pattern="\d" inputmode="numeric" required>
            <input type="text" name="otp3" class="otp-box" maxlength="1" pattern="\d" inputmode="numeric" required>
            <input type="text" name="otp4" class="otp-box" maxlength="1" pattern="\d" inputmode="numeric" required>
            <input type="text" name="otp5" class="otp-box" maxlength="1" pattern="\d" inputmode="numeric" required>
            <input type="text" name="otp6" class="otp-box" maxlength="1" pattern="\d" inputmode="numeric" required>
        </div>
        <button type="submit">Verify Account</button>
    </form>
</body>
</html>