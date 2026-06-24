<?php

include 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_POST['otp'])) {

    $entered_otp = trim($_POST['otp']);

    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("
        SELECT otp,
               otp_expiry,
               login_attempts,
               role
        FROM users
        WHERE id = ?
    ");

    $stmt->bind_param("i", $user_id);

    $stmt->execute();

    $user = $stmt->get_result()->fetch_assoc();



    if (
        $user['otp'] == $entered_otp &&
        $user['otp_expiry'] > date("Y-m-d H:i:s")
    ) {

        $_SESSION['authenticated'] = true;

        $_SESSION['role'] = $user['role'];


        $reset = $conn->prepare("
            UPDATE users
            SET login_attempts = 0,
                lockout_until = NULL,
                is_locked = 0,
                otp = NULL,
                otp_expiry = NULL
            WHERE id = ?
        ");

        $reset->bind_param("i", $user_id);

        $reset->execute();


        if ($user['role'] == 'admin') {

            header("Location: admin_dashboard.php");

            exit();
        }

      

        if (isset($_SESSION['redirect_after_login'])) {

            $redirect = $_SESSION['redirect_after_login'];

            unset($_SESSION['redirect_after_login']);

            header("Location: $redirect");

        } else {

            header("Location: dashboard.php");
        }

        exit();

    } else {

       

        $attempts = $user['login_attempts'] + 1;

       

        if ($attempts >= 3) {

            $lock_time = date(
                "Y-m-d H:i:s",
                strtotime("+60 seconds")
            );

            $lock = $conn->prepare("
                UPDATE users
                SET login_attempts = 0,
                    lockout_until = ?,
                    is_locked = 1
                WHERE id = ?
            ");

            $lock->bind_param(
                "si",
                $lock_time,
                $user_id
            );

            $lock->execute();

            session_destroy();

            header("Location: login.php?error=toomany");

        } else {

          

            $update = $conn->prepare("
                UPDATE users
                SET login_attempts = ?
                WHERE id = ?
            ");

            $update->bind_param(
                "ii",
                $attempts,
                $user_id
            );

            $update->execute();

            $left = 3 - $attempts;

            header("Location: login.php?error=invalidotp&left=$left");
        }

        exit();
    }
}
?>
