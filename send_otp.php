<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php'; 
require 'config.php';

function sendOTP($to, $otp) {
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'fwizard0719@gmail.com';
        $mail->Password   = 'clnl ohtf njzq wgyy';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('fwizard0719@gmail.com', 'OTP EMAIL VERIFICATION');
        $mail->addAddress($to);
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Grade Tracking System OTP Code';
        $mail->Body    = "Your OTP code is: <b>$otp</b>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>