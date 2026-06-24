<?php
include 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$depart_from = $_GET['depart_from'] ?? '';
$arrive_to = $_GET['arrive_to'] ?? '';
$travel_date = $_GET['travel_date'] ?? '';

$stmt = $conn->prepare("
    SELECT *
    FROM bus_tickets
    WHERE depart_from = ?
    AND arrive_to = ?
    AND departure_date = ?
    AND departure_date >= CURDATE()
    AND status = 'available'
");

$stmt->bind_param("sss", $depart_from, $arrive_to, $travel_date);

$stmt->execute();

$results = $stmt->get_result();

$current_date = strtotime($travel_date);

$prev_date = date("Y-m-d", strtotime("-1 day", $current_date));
$next_date = date("Y-m-d", strtotime("+1 day", $current_date));

$min_date = date("Y-m-d");
$max_date = date("Y-m-d", strtotime("+3 days"));
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Search Results - KKKL Group</title>

<script src="https://cdn.tailwindcss.com"></script>

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
rel="stylesheet">

<style>

body {
    font-family: Arial, sans-serif;
}

.bg-kkkl-red {
    background-color: #bc0000;
}

.text-kkkl-red {
    color: #bc0000;
}

.seat-checkbox {
    display: none;
}

.seat-label {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    width: 40px;

    height: 40px;

    border: 1px solid #ccc;

    border-radius: 4px;

    cursor: pointer;

    font-size: 12px;

    font-weight: bold;

    background-color: white;

    transition: all 0.2s;
}

.seat-checkbox:checked + .seat-label {

    background-color: #bc0000;

    color: white;

    border-color: #bc0000;
}

.seat-label.taken {

    background-color: #9ca3af;

    color: white;

    cursor: not-allowed;

    border-color: #9ca3af;
}

.driver-box {

    width: 64px;

    height: 48px;

    border: 1px solid #d1d5db;

    background-color: #e5e7eb;

    border-radius: 6px;

    display: flex;

    flex-direction: column;

    align-items: center;

    justify-content: center;

    font-size: 9px;

    font-weight: bold;

    color: #374151;
}

</style>

</head>

<body class="bg-gray-100 pb-20">

<div class="max-w-5xl mx-auto mt-8 px-4">

    <h2 class="text-xl font-bold text-blue-800 mb-4">

        <?php echo htmlspecialchars($depart_from); ?>

        →

        <?php echo htmlspecialchars($arrive_to); ?>

    </h2>

    <div class="bg-white border rounded flex overflow-hidden mb-6 shadow-sm">

        <?php if ($prev_date >= $min_date): ?>

            <a href="search_results.php?depart_from=<?php echo urlencode($depart_from); ?>&arrive_to=<?php echo urlencode($arrive_to); ?>&travel_date=<?php echo $prev_date; ?>"
               class="px-3 bg-gray-100 border-r hover:bg-gray-200 flex items-center">

                <i class="fas fa-chevron-left text-gray-500"></i>

            </a>

        <?php else: ?>

            <div class="px-3 bg-gray-100 border-r opacity-40 flex items-center">

                <i class="fas fa-chevron-left text-gray-500"></i>

            </div>

        <?php endif; ?>

        <div class="flex-1 py-3 text-center border-b-4 border-red-600 text-red-600 font-bold text-sm bg-red-50">

            <?php echo date("d M Y", strtotime($travel_date)); ?>

        </div>

        <?php if ($next_date <= $max_date): ?>

            <a href="search_results.php?depart_from=<?php echo urlencode($depart_from); ?>&arrive_to=<?php echo urlencode($arrive_to); ?>&travel_date=<?php echo $next_date; ?>"
               class="px-3 bg-gray-100 border-l hover:bg-gray-200 flex items-center">

                <i class="fas fa-chevron-right text-gray-500"></i>

            </a>

        <?php else: ?>

            <div class="px-3 bg-gray-100 border-l opacity-40 flex items-center">

                <i class="fas fa-chevron-right text-gray-500"></i>

            </div>

        <?php endif; ?>

    </div>

    <?php if ($results->num_rows > 0): ?>

        <div class="space-y-4">

            <?php while($row = $results->fetch_assoc()): ?>

                <?php

                $held_seats = [];

                $hold_query = $conn->prepare("
                    SELECT seat_number
                    FROM seat_holds
                    WHERE ticket_id = ?
                    AND hold_expiry > NOW()
                ");

                $hold_query->bind_param("i", $row['id']);

                $hold_query->execute();

                $hold_result = $hold_query->get_result();

                while($hold = $hold_result->fetch_assoc()) {

                    $held_seats[] = (int)$hold['seat_number'];
                }

                $booking_query = $conn->prepare("
                    SELECT seat_numbers
                    FROM bookings
                    WHERE ticket_id = ?
                    AND status = 'paid'
                ");

                $booking_query->bind_param("i", $row['id']);

                $booking_query->execute();

                $booking_result = $booking_query->get_result();

                while($booking = $booking_result->fetch_assoc()) {

                    $booked_seats = explode(",", $booking['seat_numbers']);

                    foreach($booked_seats as $seat) {

                        if (trim($seat) !== '') {

                            $held_seats[] = (int)trim($seat);
                        }
                    }
                }

                $held_seats = array_unique($held_seats);

                $admin_seat_limit = (int)$row['available_seats'];

                if ($admin_seat_limit > 15) {
                    $admin_seat_limit = 15;
                }

                if ($admin_seat_limit < 1) {
                    $admin_seat_limit = 0;
                }

                $taken_count = count($held_seats);

                $remaining_seats = max($admin_seat_limit - $taken_count, 0);

                ?>

                <div class="bg-white border rounded shadow-sm">

                    <div class="p-6 flex flex-col md:flex-row items-center justify-between">

                        <div class="flex items-center gap-8 flex-1 w-full">

                            <div class="text-center w-24">

                                <div class="font-bold text-red-800 border p-1 mb-1 tracking-widest bg-gray-100">
                                    KKKL
                                </div>

                                <p class="text-[10px] text-gray-500 leading-tight">
                                    KKKL Express
                                </p>

                            </div>

                            <div>
                                <p class="text-sm font-bold text-blue-700">
                                    Bus No: <?php echo htmlspecialchars($row['bus_number']); ?>
                                </p>

                                <div class="text-xl font-bold text-gray-800">

                                    <?php echo date("h:i A", strtotime($row['departure_time'])); ?>

                                </div>

                                <div>

                                    <p class="text-sm text-gray-600">

                                        <?php echo htmlspecialchars($row['depart_from']); ?>

                                    </p>

                                    <p class="text-sm text-gray-600">

                                        <?php echo htmlspecialchars($row['arrive_to']); ?>

                                    </p>

                                </div>
                            </div>

                        </div>

                        <div class="text-right">

                            <p class="text-xl font-bold text-gray-800 mb-1">

                                RM <?php echo number_format($row['price'], 2); ?>

                            </p>

                            <p class="text-[10px] text-gray-500 mb-2">

                                <?php echo $remaining_seats; ?> seat(s) available

                            </p>

                            <?php if ($remaining_seats > 0): ?>

                                <button type="button"
                                        onclick="toggleSeats(<?php echo $row['id']; ?>)"
                                        class="bg-[#ff5a00] text-white px-8 py-2 rounded text-sm font-bold shadow">

                                    Select

                                </button>

                            <?php else: ?>

                                <button type="button"
                                        disabled
                                        class="bg-gray-400 text-white px-8 py-2 rounded text-sm font-bold shadow">

                                    Sold Out

                                </button>

                            <?php endif; ?>

                        </div>

                    </div>

                    <div id="seat-selection-<?php echo $row['id']; ?>"
                         class="hidden border-t bg-gray-50 p-6">

                        <form action="insurance.php"
                              method="POST"
                              onsubmit="return validateSeats(<?php echo $row['id']; ?>)"
                              class="flex flex-col md:flex-row gap-8 justify-center items-center">

                            <input type="hidden"
                                   name="ticket_id"
                                   value="<?php echo $row['id']; ?>">

                            <input type="hidden"
                                   name="price"
                                   value="<?php echo $row['price']; ?>">

                            <div class="bg-white p-4 border rounded shadow-inner">

                                <div class="mb-4 flex justify-end">

                                    <div class="driver-box">

                                        <i class="fas fa-bus text-lg"></i>

                                        DRIVER

                                    </div>

                                </div>

                                <div class="grid grid-cols-2 gap-2">

                                    <?php for($i = 1; $i <= $admin_seat_limit; $i++): ?>

                                        <?php $is_taken = in_array($i, $held_seats); ?>

                                        <div>

                                            <input
                                                type="checkbox"
                                                name="selected_seats[]"
                                                value="<?php echo $i; ?>"
                                                id="seat-<?php echo $row['id']; ?>-<?php echo $i; ?>"
                                                class="seat-checkbox"
                                                <?php echo $is_taken ? 'disabled' : ''; ?>
                                            >

                                            <label
                                                for="seat-<?php echo $row['id']; ?>-<?php echo $i; ?>"
                                                class="seat-label <?php echo $is_taken ? 'taken' : ''; ?>"
                                            >

                                                <?php echo $i; ?>

                                            </label>

                                        </div>

                                    <?php endfor; ?>

                                </div>

                            </div>

                            <button type="submit"
                                    class="bg-[#ff5a00] text-white px-8 py-3 rounded text-sm font-bold shadow">

                                Continue Booking

                            </button>

                        </form>

                    </div>

                </div>

            <?php endwhile; ?>

        </div>

    <?php else: ?>

        <div class="bg-white p-10 rounded shadow text-center">

            <i class="fas fa-ticket-alt text-5xl text-gray-300 mb-4"></i>

            <h3 class="text-lg font-bold text-gray-700 mb-2">
                No Tickets Available
            </h3>

            <p class="text-gray-500 text-sm">
                No available tickets found for this date.
            </p>

        </div>

    <?php endif; ?>

</div>

<script>

function toggleSeats(id) {

    document.getElementById(
        'seat-selection-' + id
    ).classList.toggle('hidden');
}

function validateSeats(id) {

    const checked = document.querySelectorAll(
        '#seat-selection-' + id + ' input[type="checkbox"]:checked'
    );

    if (checked.length === 0) {

        showPopup("Please select at least one seat.");

        return false;
    }

    return true;
}

function showPopup(message) {

    document.getElementById("popupMessage").innerText = message;

    document.getElementById("customPopup").classList.remove("hidden");

    document.getElementById("customPopup").classList.add("flex");
}

function closePopup() {

    document.getElementById("customPopup").classList.add("hidden");

    document.getElementById("customPopup").classList.remove("flex");
}

</script>

<div id="customPopup"
     class="fixed inset-0 bg-black bg-opacity-40 hidden items-center justify-center z-50">

    <div class="bg-white rounded-lg shadow-xl w-full max-w-sm p-6 border-t-4 border-red-700 text-center">

        <div class="text-red-600 text-3xl mb-3">

            <i class="fas fa-exclamation-circle"></i>

        </div>

        <h3 class="font-bold text-lg text-gray-800 mb-2">

            Notice

        </h3>

        <p id="popupMessage"
           class="text-sm text-gray-600 mb-5"></p>

        <button onclick="closePopup()"
                class="bg-[#bc0000] text-white px-8 py-2 rounded font-bold text-sm">

            OK

        </button>

    </div>

</div>

</body>
</html>