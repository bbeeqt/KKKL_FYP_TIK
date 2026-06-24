<?php
include 'db.php';

function maskEmail($email) {
    $parts = explode("@", $email);
    $name = $parts[0];
    $domain = $parts[1] ?? '';

    $masked_name = substr($name, 0, 2) . str_repeat('*', max(0, strlen($name) - 2));
    return $masked_name . '@' . $domain;
}

function maskPhone($phone) {

return str_repeat('*', max(0, strlen($phone) - 4)) . substr($phone, -4);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$booking_id = $_GET['booking_id'] ?? ($_SESSION['booking_final']['booking_id'] ?? null);

if (!$booking_id) {
    die("No booking ID found.");
}

$stmt = $conn->prepare("
    SELECT 
        bookings.*,
        bus_tickets.depart_from,
        bus_tickets.arrive_to,
        bus_tickets.departure_date,
        bus_tickets.departure_time,
        bus_tickets.bus_type,
        bus_tickets.bus_number,
        users.fullname,
        users.email,
        users.phone_number
    FROM bookings
    JOIN bus_tickets ON bookings.ticket_id = bus_tickets.id
    JOIN users ON bookings.user_id = users.id
    WHERE bookings.id = ?
");

$stmt->bind_param("i", $booking_id);
$stmt->execute();

$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    die("Booking not found.");
}

if (
    $data['user_id'] != $user_id &&
    ($_SESSION['role'] ?? '') !== 'admin'
) {
    die("Access denied.");
}

$ticket_code = "KKKL" . str_pad($data['id'], 6, "0", STR_PAD_LEFT);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>KKKL Ticket</title>

<script src="https://cdn.tailwindcss.com"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

<style>
body {
    font-family: Arial, sans-serif;
    background: #f3f4f6;
}

.ticket-shadow {
    box-shadow: 0 15px 40px rgba(0,0,0,0.15);
}

.gradient-red {
    background: linear-gradient(135deg, #bc0000, #8b0000);
}

.green-section {
    background: linear-gradient(135deg, #16a34a, #15803d);
}

@media print {

    body {
        background: white;
    }

    .no-print {
        display: none !important;
    }

    .ticket-shadow {
        box-shadow: none;
    }
}
</style>

</head>

<body class="min-h-screen flex flex-col items-center justify-center py-10">

<div class="w-full max-w-3xl">

    <div class="bg-white rounded-xl overflow-hidden ticket-shadow">

        <div class="gradient-red text-white px-8 py-6 flex justify-between items-start">

            <div>

                <h1 class="text-4xl font-bold tracking-widest uppercase">
                    KKKL Group
                </h1>

                <p class="mt-2 text-sm opacity-90">
                    Secure Bus Ticket Confirmation
                </p>

            </div>

            <div class="bg-white text-gray-700 rounded-lg px-5 py-4 text-center shadow-lg">

                <div class="text-5xl font-bold leading-none">
                    <?php echo date("d", strtotime($data['departure_date'])); ?>
                </div>

                <div class="uppercase font-bold text-sm mt-1">
                    <?php echo date("F", strtotime($data['departure_date'])); ?>
                </div>

                <div class="text-xs mt-2">
                    <?php echo date("h:i A", strtotime($data['departure_time'])); ?>
                </div>

            </div>

        </div>

        <div class="px-8 py-8">

            <div class="flex justify-between items-start gap-8">

                <div class="flex-1">

                    <h2 class="text-3xl font-bold text-gray-800 mb-3">
                        Booking Confirmation
                    </h2>

                    <p class="text-gray-600 mb-6">
                        Your KKKL Express booking has been successfully confirmed.
                    </p>

                    <div class="space-y-3 text-sm">

                        <p>
                            <span class="font-bold text-gray-700">Passenger:</span>
                            <?php echo htmlspecialchars($data['fullname']); ?>
                        </p>

                        <p>
                            <span class="font-bold text-gray-700">Email:</span>
                            <?php echo htmlspecialchars(maskEmail($data['email'])); ?>
                        </p>

                        <p>
                            <span class="font-bold text-gray-700">Phone:</span>
                            <?php echo htmlspecialchars(maskPhone($data['phone_number'])); ?>
                        </p>

                        <hr class="my-4">

                        <p>
                            <span class="font-bold text-gray-700">Bus Number:</span>
                            <?php echo htmlspecialchars($data['bus_number']); ?>
                        </p>

                        <p>
                            <span class="font-bold text-gray-700">Route:</span>
                            <?php echo htmlspecialchars($data['depart_from']); ?>
                            →
                            <?php echo htmlspecialchars($data['arrive_to']); ?>
                        </p>

                        <p>
                            <span class="font-bold text-gray-700">Bus Type:</span>
                            <?php echo htmlspecialchars($data['bus_type']); ?>
                        </p>

                        <p>
                            <span class="font-bold text-gray-700">Seat(s):</span>
                            <?php echo htmlspecialchars($data['seat_numbers']); ?>
                        </p>

                        <p>
                            <span class="font-bold text-gray-700">Boarding Gate:</span>
                            <?php echo htmlspecialchars($data['gate_no']); ?>
                        </p>

                        <p>
                            <span class="font-bold text-gray-700">Payment:</span>
                            <?php echo htmlspecialchars($data['payment_method']); ?>
                        </p>

                        <p>
                            <span class="font-bold text-gray-700">Insurance:</span>
                            <?php 
                            echo ($data['insurance'] == 'yes' || $data['insurance'] == 1) ? 'RM 1.00 per passenger' : 'No Insurance'; ?>
                        </p>

                        <p class="text-lg text-gray-700 font-bold pt-2">
                            TOTAL PAID:
                            RM <?php echo number_format($data['total_price'], 2); ?>
                        </p>

                    </div>

                </div>

                <div class="border rounded-xl p-5 text-center min-w-[180px]">

                    <div class="text-xs uppercase tracking-widest text-gray-500 font-bold">
                        Reference No
                    </div>

                    <div class="text-2xl font-black text-gray-700 mt-2">
                        <?php echo $ticket_code; ?>
                    </div>

                    <div class="mt-6">

                        <div class="text-xs uppercase tracking-widest text-gray-500 font-bold">
                            Booking Status
                        </div>

                        <div class="bg-gray-100 text-gray-700 font-bold px-4 py-2 rounded-full mt-2">
                            CONFIRMED
                        </div>

                    </div>

                </div>

            </div>

        </div>

        <div class="green-section text-white px-8 py-6 flex justify-between items-center">

            <div>

                <div class="uppercase text-xs tracking-widest opacity-80">
                    Secure Boarding Pass
                </div>

                <div class="text-4xl font-black tracking-widest mt-2">
                    <?php echo $ticket_code; ?>
                </div>

                <div class="text-sm opacity-90 mt-1">
                    Please arrive 30 minutes before departure.
                </div>

            </div>

            <div class="text-right">

                <div class="uppercase text-xs tracking-widest opacity-80">
                    Ticket Generated
                </div>

                <div class="font-bold mt-2">
                    <?php echo date("d M Y h:i A", strtotime($data['created_at'])); ?>
                </div>

            </div>

        </div>

    </div>

    <div class="flex justify-center gap-5 mt-8 no-print">

        <button
            onclick="window.print()"
            class="gradient-red text-white px-8 py-3 rounded-full font-bold shadow-lg hover:scale-105 transition-all duration-200">
            <i class="fas fa-print mr-2"></i>
            Print / Save PDF
        </button>

        <a href="dashboard.php"
           class="gradient-red text-white px-8 py-3 rounded-full font-bold shadow-lg hover:scale-105 transition-all duration-200 inline-flex items-center">
            <i class="fas fa-home mr-2"></i>
            Back to Dashboard
        </a>

    </div>

</div>

</body>
</html>