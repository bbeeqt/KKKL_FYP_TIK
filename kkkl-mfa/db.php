<?php

header("X-Frame-Options: SAMEORIGIN"); // Prevent Clickjacking 
header("X-Content-Type-Options: nosniff"); //Fix X-Content-Type-Options


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}



/*
|--------------------------------------------------------------------------
| SESSION TIMEOUT - 10 MINUTES
|--------------------------------------------------------------------------
*/

$timeout_duration = 600;

if (isset($_SESSION['LAST_ACTIVITY'])) {

    if (time() - $_SESSION['LAST_ACTIVITY'] > $timeout_duration) {

        session_unset();

        session_destroy();

        header("Location: login.php?error=session_timeout");

        exit();
    }
}

$_SESSION['LAST_ACTIVITY'] = time();

$conn = new mysqli(
    "127.0.0.1:3307",
    "root",
    "",
    "kkkl_mfa"
);

if ($conn->connect_error) {

    die("Connection failed: " . $conn->connect_error);
}

/*
|--------------------------------------------------------------------------
| AUTO REMOVE EXPIRED SEAT HOLDS
|--------------------------------------------------------------------------
*/

$conn->query("
    DELETE FROM seat_holds
    WHERE hold_expiry < NOW()
");

/*
|--------------------------------------------------------------------------
| AUTO MARK PAST BUS SCHEDULES AS UNAVAILABLE
|--------------------------------------------------------------------------
*/

$conn->query("
    UPDATE bus_tickets
    SET status = 'unavailable'
    WHERE departure_date < CURDATE()
    AND status = 'available'
");

?>