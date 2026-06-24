<?php

include 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['trx_attempt'])) {
    $_SESSION['trx_attempt'] = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">

<title>Transaction TAC Verification</title>

<script src="https://cdn.tailwindcss.com"></script>

</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen">

<div class="bg-white p-8 rounded shadow-lg w-full max-w-md border-t-8 border-red-700">

    <div class="text-center mb-5">

        <div class="text-red-600 text-4xl mb-3">
            🔐
        </div>

        <h2 class="text-2xl font-bold text-center mb-2">
            Transaction TAC Verification
        </h2>

        <p class="text-center text-sm text-gray-500">
            Enter the TAC sent to your registered email to authorize this payment.
        </p>

    </div>

    <div class="bg-blue-50 border border-blue-200 text-blue-800 p-4 rounded mb-5 text-xs">

        <strong>Security Notice:</strong>
        This TAC is used only for payment verification. It is different from your login OTP.

    </div>

    <p class="text-center text-red-500 font-bold mb-4">
        TAC expires in
        <span id="timer">60</span>s
    </p>

    <form method="POST" action="complete_booking.php">

        <input
            type="text"
            name="input_otp"
            required
            maxlength="6"
            placeholder="Enter TAC"
            autocomplete="off"
            class="w-full border-2 p-4 text-center mb-5 text-xl font-black tracking-[10px] focus:border-red-500 outline-none rounded"
        >

        <button
            type="submit"
            class="w-full bg-red-600 text-white py-4 rounded font-bold uppercase tracking-widest hover:bg-red-700 transition"
        >
            Verify Transaction
        </button>

    </form>

    <p class="text-center text-[10px] text-gray-400 mt-5">
        Do not share this TAC with anyone.
    </p>

</div>

<script>

let time = 60;

let countdown = setInterval(() => {

    time--;

    document.getElementById("timer").innerText = time;

    if (time <= 0) {

        clearInterval(countdown);

        alert("TAC expired. Please request payment verification again.");

        window.location.href = "payment.php";
    }

}, 1000);

</script>

</body>
</html>