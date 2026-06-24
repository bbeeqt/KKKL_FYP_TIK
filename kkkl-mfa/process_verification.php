<?php

include 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_POST['add_insurance'])) {

    $_SESSION['temp_booking']['insurance']
        = $_POST['add_insurance'];
}

/*
|--------------------------------------------------------------------------
| SAVE REDIRECT FLOW
|--------------------------------------------------------------------------
*/

$_SESSION['redirect_after_login'] = 'payment.php';

/*
|--------------------------------------------------------------------------
| NOT LOGIN
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['authenticated']) ||
    $_SESSION['authenticated'] !== true
) {

    header("Location: login.php?msg=auth_required");

    exit();

} else {

    header("Location: payment.php");

    exit();
}
?>