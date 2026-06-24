<?php
include 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $_SESSION['temp_booking']['payment_method'] =
        $_POST['payment_method'];

    $user_id = $_SESSION['user_id'];

    $trx_otp = rand(100000, 999999);

    $expiry = date(
        "Y-m-d H:i:s",
        strtotime("+60 seconds")
    );

    $update = $conn->prepare("
        UPDATE users
        SET transaction_otp = ?,
            transaction_otp_expiry = ?
        WHERE id = ?
    ");

    $update->bind_param(
        "ssi",
        $trx_otp,
        $expiry,
        $user_id
    );

    $update->execute();

    $stmt = $conn->prepare("
        SELECT email, otp_email
        FROM users
        WHERE id = ?
    ");

    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $user = $stmt->get_result()->fetch_assoc();

    $send_to = !empty($user['otp_email'])
        ? $user['otp_email']
        : $user['email'];

    $mail = new PHPMailer(true);

    try {

        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'joeytimmy01@gmail.com';
        $mail->Password = 'cjiehhsdlpshjtmi';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        $mail->setFrom(
            'no-reply@kkkl.com.my',
            'KKKL Secure Payment'
        );

        $mail->addAddress($send_to);

        $mail->isHTML(true);
        $mail->Subject = 'Transaction TAC Verification';

        $mail->Body = "
        <div style='max-width: 600px; margin: 20px auto; font-family: Arial, sans-serif; background-color: #ffffff; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden; padding: 20px;'>

            <div style='height: 4px; background-color: #bc0000; margin-bottom: 40px;'></div>

            <div style='padding: 0 20px;'>

                <h2 style='margin-top: 0; color: #111111; font-size: 24px; font-weight: bold;'>
                    Secure Transaction TAC
                </h2>

                <p style='font-size: 16px; color: #333333; line-height: 1.5;'>
                    Please use the TAC below to authorize your payment.
                    Valid for <strong>60 seconds</strong> only.
                </p>

                <div style='background-color: #f8f9fa; padding: 40px 20px; text-align: center; margin: 40px 0; border-radius: 6px; border: 1px solid #eeeeee;'>
                    <span style='font-size: 40px; font-weight: bold; letter-spacing: 10px; color: #111111;'>
                        $trx_otp
                    </span>
                </div>

                <div style='height: 1px; background-color: #eeeeee; margin: 40px 0 20px 0;'></div>

                <p style='font-size: 13px; color: #888888;'>
                    If you did not request this transaction, please ignore this email.
                </p>

            </div>

        </div>
        ";

        $mail->send();

        header("Location: secure_verify.php");
        exit();

    } catch (Exception $e) {

        die("Email failed: " . $mail->ErrorInfo);
    }
}
?>