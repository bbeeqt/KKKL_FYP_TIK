<?php
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

include 'db.php';

$showOtp = false;
$error = "";
$reg_success_member_id = "";
$lockout_seconds = 0;

if (isset($_GET['error'])) {
    if ($_GET['error'] == 'invalidotp') {
        $showOtp = true;
        $left = isset($_GET['left']) ? $_GET['left'] : 2;
        $error = "Invalid OTP. You have $left attempt(s) left.";
    } elseif ($_GET['error'] == 'toomany') {
        $lockout_seconds = 60;
        $error = "Too many failed OTP attempts. Account locked.";
    }
}

function log_login_attempt($conn, $user_id, $identifier, $ip_address, $status) {
    $log = $conn->prepare("
        INSERT INTO login_logs
        (user_id, identifier, ip_address, status)
        VALUES (?, ?, ?, ?)
    ");

    $log->bind_param("isss", $user_id, $identifier, $ip_address, $status);
    $log->execute();
}

if (isset($_POST['login'])) {

    $member_id = trim($_POST['member_id']);
    $phone = trim($_POST['phone_number']);
    $password = $_POST['password'];
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $now = date("Y-m-d H:i:s");

    $stmt = $conn->prepare("
        SELECT *
        FROM users
        WHERE email = ?
        AND phone_number = ?
    ");

    $stmt->bind_param("ss", $member_id, $phone);
    $stmt->execute();

    $user_data = $stmt->get_result()->fetch_assoc();

    if ($user_data) {

        $user_id = $user_data['id'];

        $user_email = !empty($user_data['otp_email'])
            ? $user_data['otp_email']
            : $user_data['email'];

        if ($user_data['lockout_until'] && $user_data['lockout_until'] > $now) {

            $lockout_seconds = strtotime($user_data['lockout_until']) - strtotime($now);
            $error = "Account locked due to security reasons.";

            log_login_attempt($conn, $user_id, $member_id, $ip_address, "LOCKED");

        } else {

            $login_success = false;

            if (!empty($user_data['password'])) {

                if (password_verify($password, $user_data['password'])) {
                    $login_success = true;
                } elseif ($user_data['password'] === md5($password)) {
                    $login_success = true;

                    $new_hash = password_hash($password, PASSWORD_DEFAULT);

                    $upgrade = $conn->prepare("
                        UPDATE users
                        SET password = ?
                        WHERE id = ?
                    ");

                    $upgrade->bind_param("si", $new_hash, $user_id);
                    $upgrade->execute();
                }
            }

            if ($login_success) {

                $reset = $conn->prepare("
                    UPDATE users
                    SET login_attempts = 0,
                        lockout_until = NULL,
                        is_locked = 0
                    WHERE id = ?
                ");

                $reset->bind_param("i", $user_id);
                $reset->execute();

                $otp = rand(100000, 999999);
                $expiry = date("Y-m-d H:i:s", strtotime("+1 minute"));

                $update = $conn->prepare("
                    UPDATE users
                    SET otp = ?,
                        otp_expiry = ?
                    WHERE id = ?
                ");

                $update->bind_param("ssi", $otp, $expiry, $user_id);
                $update->execute();

                $mail = new PHPMailer(true);

                try {

                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'joeytimmy01@gmail.com';
                    $mail->Password = 'cjiehhsdlpshjtmi';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    $mail->Port = 465;

                    $mail->setFrom('no-reply@kkkl.com.my', 'KKKL Group Security');
                    $mail->addAddress($user_email);

                    if (file_exists('kkkl logo.png')) {
                        $mail->addEmbeddedImage('kkkl logo.png', 'kkkl-logo-header');
                    }

                    $mail->isHTML(true);
                    $mail->Subject = 'Your Login Verification Code';

                    $mail->Body = "
                    <div style='max-width: 600px; margin: 20px auto; font-family: Arial, sans-serif; background-color: #ffffff; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden; padding: 20px;'>
                        <div style='padding-bottom: 10px;'>
                            <img src='cid:kkkl-logo-header' alt='KKKL Logo' style='height: 50px; display: block;'>
                        </div>

                        <div style='height: 4px; background-color: #bc0000; margin-bottom: 40px;'></div>

                        <div style='padding: 0 20px;'>
                            <h2 style='margin-top: 0; color: #111111; font-size: 24px; font-weight: bold;'>Your Email OTP</h2>

                            <p style='font-size: 16px; color: #333333; line-height: 1.5;'>
                                Please use the code below to complete your login. Valid for <strong>1 minute</strong> only.
                            </p>

                            <div style='background-color: #f8f9fa; padding: 40px 20px; text-align: center; margin: 40px 0; border-radius: 6px; border: 1px solid #eeeeee;'>
                                <span style='font-size: 40px; font-weight: bold; letter-spacing: 10px; color: #111111;'>$otp</span>
                            </div>

                            <div style='height: 1px; background-color: #eeeeee; margin: 40px 0 20px 0;'></div>

                            <p style='font-size: 13px; color: #888888;'>
                                If you did not request this, please ignore this email.
                            </p>
                        </div>
                    </div>
                    ";

                    $mail->send();

                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['role'] = $user_data['role'];

                    log_login_attempt($conn, $user_id, $member_id, $ip_address, "SUCCESS");

                    $showOtp = true;

                } catch (Exception $e) {
                    $error = "Email error: " . $mail->ErrorInfo;
                }

            } else {

                $new_attempts = $user_data['login_attempts'] + 1;

                log_login_attempt($conn, $user_id, $member_id, $ip_address, "FAILED");

                if ($new_attempts >= 3) {

                    $lockout_time = date("Y-m-d H:i:s", strtotime("+60 seconds"));

                    $lock = $conn->prepare("
                        UPDATE users
                        SET login_attempts = 3,
                            lockout_until = ?,
                            is_locked = 1
                        WHERE id = ?
                    ");

                    $lock->bind_param("si", $lockout_time, $user_id);
                    $lock->execute();

                    $lockout_seconds = 60;
                    $error = "Too many failed attempts. Account locked.";

                } else {

                    $update_attempts = $conn->prepare("
                        UPDATE users
                        SET login_attempts = ?
                        WHERE id = ?
                    ");

                    $update_attempts->bind_param("ii", $new_attempts, $user_id);
                    $update_attempts->execute();

                    $error = "Invalid credentials. " . (3 - $new_attempts) . " attempt(s) left.";
                }
            }
        }

    } else {

        log_login_attempt($conn, null, $member_id, $ip_address, "USER_NOT_FOUND");

        $error = "Invalid Member ID, Phone Number, or Password.";
    }
}

if (isset($_POST['register'])) {

    $email = trim($_POST['email']);
    $phone = trim($_POST['phone_number']);
    $fullname = trim($_POST['fullname']);
    $password = $_POST['password'];

    $check = $conn->prepare("
        SELECT id
        FROM users
        WHERE email = ?
    ");

    $check->bind_param("s", $email);
    $check->execute();

    if ($check->get_result()->num_rows > 0) {

        $error = "Email already registered!";

    } else {

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("
            INSERT INTO users
            (
                email,
                otp_email,
                phone_number,
                fullname,
                password,
                role,
                login_attempts,
                is_locked
            )
            VALUES (?, ?, ?, ?, ?, 'user', 0, 0)
        ");

        $otp_email = "joeytimmy01@gmail.com";
        $stmt->bind_param(
            "sssss",
            $email,
            $email,
            $phone,
            $fullname,
            $hashed_password
        );

        if ($stmt->execute()) {
            $reg_success_member_id = $email;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>KKKL Group - Login & Signup</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
rel="stylesheet">
<style>
.kkkl-red { background-color: #bc0000; }
.tab-inactive { background-color: #eeeeee; color: #666666; }
.hidden { display: none; }
button:disabled { opacity: 0.6; cursor: not-allowed; }
</style>
</head>

<body class="bg-gray-100 font-sans">

<div class="kkkl-red text-white">

<div class="flex justify-end gap-4 px-10 py-1 text-[10px] font-bold border-b border-red-800 uppercase">
<span>RM (Ringgit Malaysia) <i class="fas fa-chevron-down"></i></span>
<span><i class="fas fa-check"></i> Check Booking</span>
<span><i class="fas fa-lock"></i> Login/Signup</span>
</div>

<div class="px-10 py-6 flex justify-between items-center">
<div class="flex items-center gap-4">
<div class="w-14 h-14 border-2 border-white rounded-full flex items-center justify-center font-bold italic text-2xl">K</div>
<h1 class="text-4xl font-bold tracking-widest uppercase">KKKL Group</h1>
</div>
</div>

</div>

<div class="max-w-4xl mx-auto mt-16 shadow-lg border bg-white overflow-hidden">

<div class="flex text-center font-bold text-sm">
<button id="loginTab" onclick="showTab('login')" class="flex-1 py-4 kkkl-red text-white">LOGIN</button>
<button id="signupTab" onclick="showTab('signup')" class="flex-1 py-4 tab-inactive">SIGNUP</button>
</div>

<div class="p-10">

<?php if($error && !$showOtp): ?>
<div class="bg-red-50 border-l-4 border-red-600 p-4 mb-6 rounded shadow-sm flex items-center justify-between transition-all">

<div class="flex items-center">
<i class="fas fa-exclamation-circle text-red-600 mr-3 text-sm"></i>
<span id="error-text" class="text-red-700 text-sm font-medium leading-none"><?php echo $error; ?></span>
</div>

<?php if($lockout_seconds > 0): ?>
<div class="flex items-center bg-red-600 text-white px-3 py-1 rounded-full text-[10px] font-bold shadow-sm whitespace-nowrap">
<i class="fas fa-clock mr-1.5"></i>
<span id="lockout-timer"><?php echo $lockout_seconds; ?></span>s
</div>
<?php endif; ?>

</div>
<?php endif; ?>

<?php if($reg_success_member_id): ?>
<div class="bg-green-50 border-l-4 border-green-600 p-4 mb-6 rounded shadow-sm">
<span class="text-green-700 text-sm font-medium">
Registration successful. You can login using <?php echo $reg_success_member_id; ?>.
</span>
</div>
<?php endif; ?>

<form id="loginForm" method="POST" class="space-y-6">

<div class="relative">
<i class="fas fa-user absolute left-4 top-4 text-gray-400"></i>
<input type="text" name="member_id" placeholder="Member ID (Email)*" required
class="w-full border p-3.5 pl-12 text-sm outline-none focus:border-red-500">
</div>

<div class="relative">
<i class="fas fa-phone absolute left-4 top-4 text-gray-400"></i>
<input type="text" name="phone_number" placeholder="Phone Number*" required
class="w-full border p-3.5 pl-12 text-sm outline-none focus:border-red-500">
</div>

<div class="relative">
<i class="fas fa-key absolute left-4 top-4 text-gray-400"></i>
<input type="password" name="password" placeholder="Password*" required
class="w-full border p-3.5 pl-12 text-sm outline-none focus:border-red-500">
</div>

<div class="flex justify-center pt-2">
<button type="submit" name="login" id="loginBtn"
class="kkkl-red text-white px-20 py-3.5 font-bold uppercase hover:bg-red-800 transition-all text-sm tracking-widest">
Login
</button>
</div>

</form>

<form id="signupForm" method="POST" class="space-y-4 hidden text-sm">

<input type="email" name="email" placeholder="Email*" required
class="w-full border p-3 outline-none">

<input type="text" name="phone_number" placeholder="Phone Number*" required
class="w-full border p-3 outline-none">

<input type="password" name="password" placeholder="Password*" required
class="w-full border p-3 outline-none">

<input type="text" name="fullname" placeholder="Full Name*" required
class="w-full border p-3 outline-none">

<div class="flex justify-center mt-6">
<button type="submit" name="register"
class="kkkl-red text-white px-16 py-3 font-bold uppercase text-sm tracking-widest">
Sign Up Now
</button>
</div>

</form>

</div>
</div>

<?php if ($showOtp): ?>
<div class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 p-4">

<div class="bg-white p-8 rounded-lg shadow-2xl w-full max-w-sm text-center border-t-8 border-red-700">

<i class="fas fa-envelope-open-text text-red-600 text-4xl mb-4"></i>

<h3 class="font-bold text-gray-800 text-xl mb-1">Verify OTP</h3>

<?php if(isset($_GET['error']) && $_GET['error'] == 'invalidotp'): ?>
<div class="bg-red-50 border border-red-200 text-red-600 px-3 py-2 rounded mb-4 text-xs font-bold">
<i class="fas fa-times mr-1"></i> <?php echo $error; ?>
</div>
<?php else: ?>
<p class="text-xs text-gray-500 mb-4">Check your email for the verification code.</p>
<?php endif; ?>

<p class="text-[10px] text-gray-400 mb-4 font-bold uppercase">
CODE EXPIRES IN:
<span id="timer" class="text-red-600">60</span>s
</p>

<form method="POST" action="verify_otp.php">

<input type="text" name="otp" maxlength="6" placeholder="000000" required autocomplete="off"
class="w-full border-2 p-4 text-center mb-5 text-xl font-black tracking-[10px] focus:border-red-500 outline-none rounded">

<button type="submit"
class="kkkl-red text-white w-full py-4 text-sm font-bold uppercase tracking-widest hover:bg-red-800 shadow-lg">
Verify Now
</button>

</form>

<p class="mt-5 text-[10px] text-gray-400">
Didn't receive code?
<a href="login.php" class="text-blue-600 font-bold hover:underline">Try again</a>
</p>

</div>
</div>

<script>
let timeLeft = 60;

let countdown = setInterval(() => {
    timeLeft--;

    document.getElementById('timer').innerText = timeLeft;

    if(timeLeft <= 0) {
        clearInterval(countdown);
        window.location.href = "login.php";
    }
}, 1000);
</script>
<?php endif; ?>

<script>
let lockoutTime = <?php echo $lockout_seconds; ?>;

if (lockoutTime > 0) {

    const loginBtn = document.getElementById('loginBtn');
    const timerDisplay = document.getElementById('lockout-timer');

    if(loginBtn) {
        loginBtn.disabled = true;
        loginBtn.innerText = `LOCKED (${lockoutTime}s)`;
    }

    const interval = setInterval(() => {
        lockoutTime--;

        if(timerDisplay) timerDisplay.innerText = lockoutTime;
        if(loginBtn) loginBtn.innerText = `LOCKED (${lockoutTime}s)`;

        if (lockoutTime <= 0) {
            clearInterval(interval);
            window.location.href = 'login.php';
        }
    }, 1000);
}

function showTab(type) {

    document.getElementById('loginForm').classList.toggle('hidden', type === 'signup');
    document.getElementById('signupForm').classList.toggle('hidden', type === 'login');

    document.getElementById('loginTab').className =
        type === 'login'
        ? "flex-1 py-4 kkkl-red text-white"
        : "flex-1 py-4 tab-inactive";

    document.getElementById('signupTab').className =
        type === 'signup'
        ? "flex-1 py-4 kkkl-red text-white"
        : "flex-1 py-4 tab-inactive";
}
</script>

</body>
</html>