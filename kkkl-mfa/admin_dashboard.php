<?php

include 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}



if (
    !isset($_SESSION['authenticated']) ||
    $_SESSION['authenticated'] !== true
) {

    header("Location: login.php");

    exit();
}

if (
    !isset($_SESSION['role']) ||
    $_SESSION['role'] != 'admin'
) {

    die("ACCESS DENIED");
}

/*


$total_users =
$conn->query("
    SELECT COUNT(*) as total
    FROM users
")->fetch_assoc()['total'];

$total_bookings =
$conn->query("
    SELECT COUNT(*) as total
    FROM bookings
")->fetch_assoc()['total'];

$total_tickets =
$conn->query("
    SELECT COUNT(*) as total
    FROM bus_tickets
")->fetch_assoc()['total'];

?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">

<title>Admin Dashboard</title>

<script src="https://cdn.tailwindcss.com"></script>

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
rel="stylesheet">

</head>

<body class="bg-gray-100">

<div class="flex min-h-screen">


    <aside class="w-64 bg-[#bc0000] text-white p-6">

        <h1 class="text-2xl font-bold mb-10 uppercase">
            Admin Panel
        </h1>

        <nav class="space-y-3">

            <a href="admin_dashboard.php"
               class="block bg-red-800 px-4 py-3 rounded font-bold">

                Dashboard

            </a>

            <a href="manage_schedules.php"
               class="block hover:bg-red-800 px-4 py-3 rounded">

                Manage Schedules

            </a>

            <a href="booking_overview.php"
               class="block hover:bg-red-800 px-4 py-3 rounded">

                Booking Overview

            </a>

            <a href="manage_users.php"
               class="block hover:bg-red-800 px-4 py-3 rounded">

                User Management

            </a>

            <a href="audit_logs.php"
               class="block hover:bg-red-800 px-4 py-3 rounded">

                Audit Logs

            </a>

            <a href="logout.php"
               class="block hover:bg-red-800 px-4 py-3 rounded">

                Logout

            </a>

        </nav>

    </aside>


    <main class="flex-1 p-10">

        <h2 class="text-3xl font-bold text-gray-800 mb-8">
            Admin Dashboard
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            <div class="bg-white p-6 rounded shadow">

                <p class="text-gray-500 text-sm">
                    Total Users
                </p>

                <h3 class="text-4xl font-bold mt-2">
                    <?php echo $total_users; ?>
                </h3>

            </div>

            <div class="bg-white p-6 rounded shadow">

                <p class="text-gray-500 text-sm">
                    Total Bookings
                </p>

                <h3 class="text-4xl font-bold mt-2">
                    <?php echo $total_bookings; ?>
                </h3>

            </div>

            <div class="bg-white p-6 rounded shadow">

                <p class="text-gray-500 text-sm">
                    Bus Schedules
                </p>

                <h3 class="text-4xl font-bold mt-2">
                    <?php echo $total_tickets; ?>
                </h3>

            </div>

        </div>

    </main>

</div>

</body>
</html>
