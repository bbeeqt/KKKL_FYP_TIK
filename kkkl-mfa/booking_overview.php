<?php
include 'db.php';

function maskEmail($email) {
    if (!$email || $email === '-') return '-';
    $parts = explode("@", $email);
    $name = $parts[0];
    $domain = $parts[1] ?? '';
    $masked_name = substr($name, 0, 2) . str_repeat('*', max(0, strlen($name) - 2));
    return $masked_name . '@' . $domain;
}

function maskPhone($phone) {
    if (!$phone || $phone === '-') return '-';
    return str_repeat('*', max(0, strlen($phone) - 4)) . substr($phone, -4);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (
    !isset($_SESSION['authenticated']) ||
    $_SESSION['authenticated'] !== true ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    header("Location: login.php");
    exit();
}

$bookings = $conn->query("
    SELECT 
        bookings.id AS booking_id,
        bookings.seat_numbers,
        bookings.total_price,
        bookings.status,
        bookings.created_at,
        bookings.insurance,
        bookings.payment_method,

        users.fullname,
        users.email,
        users.phone_number,

        bus_tickets.depart_from,
        bus_tickets.arrive_to,
        bus_tickets.departure_date,
        bus_tickets.departure_time,
        bus_tickets.bus_type

    FROM bookings

    LEFT JOIN users
    ON bookings.user_id = users.id

    LEFT JOIN bus_tickets
    ON bookings.ticket_id = bus_tickets.id

    ORDER BY bookings.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Booking Overview</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body class="bg-gray-100">

<div class="flex min-h-screen">

    <aside class="w-64 bg-[#bc0000] text-white p-6">

        <h1 class="text-2xl font-bold mb-10 uppercase">
            Admin Panel
        </h1>

        <nav class="space-y-3">

            <a href="admin_dashboard.php" class="block hover:bg-red-800 px-4 py-3 rounded">
                Dashboard
            </a>

            <a href="manage_schedules.php" class="block hover:bg-red-800 px-4 py-3 rounded">
                Manage Schedules
            </a>

            <a href="booking_overview.php" class="block bg-red-800 px-4 py-3 rounded font-bold">
                Booking Overview
            </a>

            <a href="manage_users.php" class="block hover:bg-red-800 px-4 py-3 rounded">
                User Management
            </a>

            <a href="audit_logs.php" class="block hover:bg-red-800 px-4 py-3 rounded">
                Audit Logs
            </a>

            <a href="logout.php" class="block hover:bg-red-800 px-4 py-3 rounded">
                Logout
            </a>

        </nav>

    </aside>

    <main class="flex-1 p-10">

        <h2 class="text-3xl font-bold text-gray-800 mb-6">
            Booking Overview
        </h2>

        <div class="bg-white rounded shadow overflow-x-auto">

            <table class="w-full text-sm">

                <thead class="bg-gray-800 text-white">
                    <tr>
                        <th class="p-3 text-left">Booking ID</th>
                        <th class="p-3 text-left">Passenger</th>
                        <th class="p-3 text-left">Route</th>
                        <th class="p-3 text-left">Trip Date</th>
                        <th class="p-3 text-left">Seats</th>
                        <th class="p-3 text-left">Payment</th>
                        <th class="p-3 text-left">Total</th>
                        <th class="p-3 text-left">Status</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if ($bookings && $bookings->num_rows > 0): ?>

                        <?php while($row = $bookings->fetch_assoc()): ?>

                            <tr class="border-b hover:bg-gray-50">

                                <td class="p-3 font-bold">
                                    #<?php echo $row['booking_id']; ?>
                                    <p class="text-[10px] text-gray-400 font-normal">
                                        <?php echo $row['created_at']; ?>
                                    </p>
                                </td>

                                <td class="p-3">
                                    <p class="font-bold">
                                        <?php echo htmlspecialchars($row['fullname'] ?? 'Unknown'); ?>
                                    </p>

                                    <p class="text-xs text-gray-500">
                                        <?php echo htmlspecialchars(maskEmail($row['email'] ?? '-')); ?>
                                    </p>

                                    <p class="text-xs text-gray-500">
                                        <?php echo htmlspecialchars(maskPhone($row['phone_number'] ?? '-')); ?>
                                    </p>
                                </td>

                                <td class="p-3">
                                    <p class="font-bold text-blue-800">
                                        <?php echo htmlspecialchars($row['depart_from']); ?>
                                        →
                                        <?php echo htmlspecialchars($row['arrive_to']); ?>
                                    </p>

                                    <p class="text-xs text-gray-500">
                                        <?php echo htmlspecialchars($row['bus_type']); ?>
                                    </p>
                                </td>

                                <td class="p-3">
                                    <p>
                                        <?php echo date("d M Y", strtotime($row['departure_date'])); ?>
                                    </p>

                                    <p class="text-xs text-gray-500">
                                        <?php echo date("h:i A", strtotime($row['departure_time'])); ?>
                                    </p>
                                </td>

                                <td class="p-3 font-bold">
                                    <?php echo htmlspecialchars($row['seat_numbers']); ?>
                                </td>

                                <td class="p-3">
                                    <p>
                                        <?php echo htmlspecialchars($row['payment_method'] ?? '-'); ?>
                                    </p>

                                    <p class="text-xs text-gray-500">
                                        Insurance:
                                        <?php echo htmlspecialchars($row['insurance'] ?? '-'); ?>
                                    </p>
                                </td>

                                <td class="p-3 font-bold text-red-600">
                                    RM <?php echo number_format($row['total_price'], 2); ?>
                                </td>

                                <td class="p-3">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold
                                        <?php echo $row['status'] == 'paid' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'; ?>">
                                        <?php echo htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="8" class="p-8 text-center text-gray-500">
                                No bookings found.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </main>

</div>

</body>
</html>