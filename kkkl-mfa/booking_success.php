<?php

include 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['booking_final'])) {

    header("Location: dashboard.php");

    exit();
}

$data = $_SESSION['booking_final'];
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Booking Confirmed</title>

<script src="https://cdn.tailwindcss.com"></script>

</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center">

<div class="bg-white shadow-lg rounded-lg p-8 w-full max-w-md">

    <div class="text-center">

        <div class="text-green-600 text-5xl mb-4">
            ✓
        </div>

        <h1 class="text-2xl font-bold text-gray-800 mb-2">
            Booking Confirmed!
        </h1>

        <p class="text-gray-500 mb-6">
            Your ticket has been successfully booked.
        </p>

    </div>

    <div class="bg-gray-50 border rounded-lg p-4 space-y-3">

        <div class="flex justify-between">

            <span class="font-semibold text-gray-600">
                Booking ID:
            </span>

            <span>
                #<?php echo $data['booking_id']; ?>
            </span>

        </div>

        <div class="flex justify-between">

            <span class="font-semibold text-gray-600">
                Route:
            </span>

            <span>
                <?php echo $data['route']; ?>
            </span>

        </div>

        <div class="flex justify-between">

            <span class="font-semibold text-gray-600">
                Seats:
            </span>

            <span>
                <?php echo $data['seats']; ?>
            </span>

        </div>

        <div class="flex justify-between">

            <span class="font-semibold text-gray-600">
                Total:
            </span>

            <span class="font-bold text-red-600">
                RM <?php echo number_format($data['total'], 2); ?>
            </span>

        </div>

    </div>

    <div class="mt-6 space-y-3">

        <a href="generate_pdf.php"
           class="block w-full bg-red-600 hover:bg-red-700 text-white text-center py-3 rounded font-bold transition">

            Download Ticket PDF

        </a>

        <a href="dashboard.php"
           class="block w-full border border-gray-300 text-gray-700 text-center py-3 rounded font-bold hover:bg-gray-100 transition">

            Back To Dashboard

        </a>

    </div>

</div>

</body>
</html>