<?php

include 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}



if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $_SESSION['temp_booking'] = [

        'ticket_id' => $_POST['ticket_id'],

        'price' => $_POST['price'],

        'seat' => $_POST['selected_seats'] ?? []
    ];
}


if (!isset($_SESSION['temp_booking'])) {

    header("Location: dashboard.php");

    exit();
}


if (!isset($_SESSION['authenticated'])) {

    $_SESSION['redirect_after_login'] = 'insurance.php';

    header("Location: login.php");

    exit();
}


$user_id = $_SESSION['user_id'];

$ticket_id = $_SESSION['temp_booking']['ticket_id'];
$check_ticket = $conn->prepare("
    SELECT departure_date
    FROM bus_tickets
    WHERE id = ?
");

$check_ticket->bind_param("i", $ticket_id);
$check_ticket->execute();
$ticket_date = $check_ticket->get_result()->fetch_assoc();

if (!$ticket_date || $ticket_date['departure_date'] < date("Y-m-d")) {
    unset($_SESSION['temp_booking']);

    echo "
    <script>
        alert('This trip date has already passed. Please select another trip.');
        window.location.href='dashboard.php';
    </script>
    ";

    exit();
}

$selected_seats = $_SESSION['temp_booking']['seat'];

$hold_expiry = date(
    "Y-m-d H:i:s",
    strtotime("+5 minutes")
);

foreach ($selected_seats as $seat) {


    $check = $conn->prepare("
        SELECT *
        FROM seat_holds
        WHERE ticket_id = ?
        AND seat_number = ?
        AND hold_expiry > NOW()
    ");

    $check->bind_param(
        "is",
        $ticket_id,
        $seat
    );

    $check->execute();

    $existing = $check->get_result();

    if ($existing->num_rows > 0) {

        echo "
        <script>
            alert('Seat $seat is currently taken.');
            window.location.href='dashboard.php';
        </script>
        ";

        exit();
    }


    $insert = $conn->prepare("
        INSERT INTO seat_holds
        (
            ticket_id,
            seat_number,
            user_id,
            hold_expiry
        )
        VALUES (?, ?, ?, ?)
    ");

    $insert->bind_param(
        "isis",
        $ticket_id,
        $seat,
        $user_id,
        $hold_expiry
    );

    $insert->execute();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Travel Protection - KKKL Group</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
    rel="stylesheet">
</head>

<body class="bg-gray-100">

<header class="bg-[#bc0000] text-white py-4 px-10 shadow-md">

    <div class="max-w-6xl mx-auto flex justify-between items-center">

        <h1 class="text-2xl font-bold tracking-widest uppercase">
            KKKL Group
        </h1>

        <div class="text-sm uppercase font-bold">
            Step 3: Insurance
        </div>

    </div>

</header>

<main class="max-w-2xl mx-auto mt-12 px-4">

    <div class="bg-white border rounded shadow-lg overflow-hidden">

        <div class="bg-blue-50 p-6 border-b flex items-center justify-between">

            <div>

                <h2 class="text-blue-800 text-xl font-bold italic">
                    Travel Protection
                </h2>

                <p class="text-xs text-gray-500">
                    Secure your journey for just RM 1.00
                </p>

            </div>

            <div class="text-blue-800 font-bold text-lg">
                RM 1.00
            </div>

        </div>

        <div class="p-8">

            <div class="text-sm text-gray-600 space-y-4">

                <p class="font-bold text-gray-800 uppercase text-xs">
                    Benefits Include:
                </p>

                <ul class="list-none space-y-2">

                    <li>
                        <i class="fas fa-check-circle text-green-500 mr-2"></i>
                        Accidental Death & Disablement
                    </li>

                    <li>
                        <i class="fas fa-check-circle text-green-500 mr-2"></i>
                        Medical Expenses Reimbursment
                    </li>

                    <li>
                        <i class="fas fa-check-circle text-green-500 mr-2"></i>
                        Trip Cancellation / Delay
                    </li>

                </ul>

            </div>

            <div class="mt-10 flex flex-col gap-3">

                <form action="process_verification.php" method="POST">

                    <input type="hidden"
                           name="add_insurance"
                           value="yes">

                    <button type="submit"
                    class="w-full bg-blue-800 text-white py-3 font-bold uppercase hover:bg-blue-900 transition shadow-md">

                        Yes, Add Protection

                    </button>

                </form>

                <form action="process_verification.php" method="POST">

                    <input type="hidden"
                           name="add_insurance"
                           value="no">

                    <button type="submit"
                    class="w-full border border-gray-300 text-gray-500 py-3 font-bold uppercase hover:bg-gray-50 transition">

                        No, I'll take the risk

                    </button>

                </form>

            </div>

        </div>

    </div>

</main>

</body>
</html>
