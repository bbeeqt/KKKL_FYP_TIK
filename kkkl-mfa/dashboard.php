<?php
include 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$departures = $conn->query("
    SELECT DISTINCT depart_from 
    FROM bus_tickets
    WHERE status = 'available'
    AND departure_date >= CURDATE()
    ORDER BY depart_from ASC
");

$destinations = $conn->query("
    SELECT DISTINCT arrive_to 
    FROM bus_tickets
    WHERE status = 'available'
    AND departure_date >= CURDATE()
    ORDER BY arrive_to ASC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>KKKL Group - Dashboard</title>

<script src="https://cdn.tailwindcss.com"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

<style>
body { font-family: 'Arial', sans-serif; }
.bg-kkkl-red { background-color: #bc0000; }
.text-kkkl-red { color: #bc0000; }
</style>
</head>

<body class="bg-gray-100">

<div class="bg-kkkl-red text-white py-1 px-4 text-[10px] font-bold">
    <div class="max-w-6xl mx-auto flex justify-end gap-4 uppercase">

        <div class="flex items-center gap-1 cursor-pointer">
            <span>RM (Ringgit Malaysia)</span>
            <i class="fas fa-chevron-down"></i>
        </div>

        <?php if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true): ?>

            <div class="flex items-center gap-1 cursor-pointer border-l border-red-800 pl-4">
                <a href="account.php" class="hover:underline flex items-center gap-1">
                    <i class="fas fa-user-circle"></i>
                    My Account
                </a>
            </div>

            <div class="flex items-center gap-1 border-l border-red-800 pl-4">
                <a href="logout.php" class="hover:underline flex items-center gap-1">
                    <i class="fas fa-lock"></i>
                    Logout
                </a>
            </div>

        <?php else: ?>

            <div class="flex items-center gap-1 border-l border-red-800 pl-4">
                <a href="login.php" class="hover:underline flex items-center gap-1">
                    <i class="fas fa-sign-in-alt"></i>
                    Login / Signup
                </a>
            </div>

        <?php endif; ?>

    </div>
</div>

<header class="bg-kkkl-red text-white py-6 px-10 border-t border-red-800 shadow-md">
    <div class="max-w-6xl mx-auto flex justify-between items-center">

        <div class="flex items-center gap-4">
            <div class="w-16 h-16 border-2 border-white rounded-full flex items-center justify-center font-bold italic text-3xl">
                K
            </div>

            <h1 class="text-5xl font-bold tracking-widest uppercase">
                KKKL Group
            </h1>
        </div>

        <div class="text-right text-xs space-y-1">
            <p>
                <i class="fas fa-phone mr-2"></i>
                012-708 2999(24H) / 010-201 8699
            </p>

            <p>
                <i class="fas fa-envelope mr-2"></i>
                enquiry@kkkl.com.my
            </p>
        </div>

    </div>
</header>

<nav class="bg-white border-b border-gray-300 shadow-sm sticky top-0 z-40">
    <div class="max-w-6xl mx-auto flex justify-between items-center px-6">

        <ul class="flex text-[11px] font-bold text-gray-700 uppercase">
            <li class="px-5 py-4 text-red-600 border-b-2 border-red-600">
                Home
            </li>

            <li class="px-5 py-4 hover:text-red-600 cursor-pointer">
                About Us <i class="fas fa-caret-down ml-1"></i>
            </li>

            <li class="px-5 py-4 hover:text-red-600 cursor-pointer">
                Our Services <i class="fas fa-caret-down ml-1"></i>
            </li>

            <li class="px-5 py-4 hover:text-red-600 cursor-pointer">
                Gallery
            </li>

            <li class="px-5 py-4 hover:text-red-600 cursor-pointer">
                Contact Us
            </li>

            <li class="px-5 py-4 hover:text-red-600 cursor-pointer">
                Mobile Apps
            </li>
        </ul>

        <div class="bg-blue-800 text-white p-1.5 rounded-sm cursor-pointer">
            <i class="fab fa-facebook-f px-1"></i>
        </div>

    </div>
</nav>

<main class="max-w-6xl mx-auto mt-8 px-4 pb-20">

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <div class="relative bg-white rounded shadow-sm border overflow-hidden">

            <div class="bg-kkkl-red text-white text-center py-2.5 text-2xl font-bold tracking-tight">
                WAJIB • COMPULSORY • 必须
            </div>

            <div class="p-8">
                <div class="border-2 border-red-100 rounded p-6 bg-red-50/30">

                    <p class="text-xs font-bold text-gray-800 mb-4">
                        <span class="text-kkkl-red underline">
                            REMINDER:
                        </span>
                        ALL EXPRESS BUS DRIVERS AND PASSENGERS ARE REQUIRED TO WEAR SEATBELTS EFFECTIVELY FROM 1 JULY 2025.
                    </p>

                    <ol class="text-[11px] text-gray-600 list-decimal pl-5 space-y-2">
                        <li>
                            JPJ IS MANDATING THE USE OF SEAT BELTS ON ALL EXPRESS AND TOUR BUSES STARTING 1 JULY.
                        </li>

                        <li>
                            FAILURE TO COMPLY WITH THIS DIRECTIVE WILL RESULT IN A
                            <span class="font-bold text-red-600">
                                RM300 FINE
                            </span>
                            IMPOSED ON THE DRIVER, PASSENGERS, AND THE BUS OPERATING COMPANY.
                        </li>
                    </ol>

                    <div class="mt-6 flex justify-center">
                        <i class="fas fa-user-slash text-blue-500 text-6xl opacity-80"></i>
                    </div>

                </div>
            </div>

        </div>

        <div class="bg-white rounded shadow-sm border overflow-hidden">

            <div class="bg-kkkl-red text-white text-center py-2.5 font-bold text-lg uppercase tracking-wider">
                Book Bus Ticket
            </div>

            <div class="p-8">

                <div class="flex gap-4 mb-8">
                    <button type="button" class="bg-gray-600 text-white px-6 py-1.5 rounded-sm text-xs font-bold uppercase">
                        Return
                    </button>

                    <button type="button" class="bg-kkkl-red text-white px-6 py-1.5 rounded-sm text-xs font-bold uppercase">
                        One way
                    </button>
                </div>

                <form action="search_results.php" method="GET" class="space-y-5">

                    <div class="grid grid-cols-2 gap-6">

                        <div>
                            <label class="text-[10px] font-bold text-gray-500 block uppercase mb-1">
                                Depart From
                            </label>

                            <select
                                name="depart_from"
                                id="depart_from"
                                required
                                class="w-full border border-gray-300 p-2.5 text-xs outline-none focus:border-red-500 bg-white">

                                <option value="">
                                    Select a Departure Point
                                </option>

                                <?php while($d = $departures->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($d['depart_from']); ?>">
                                        <?php echo htmlspecialchars($d['depart_from']); ?>
                                    </option>
                                <?php endwhile; ?>

                            </select>
                        </div>

                        <div>
                            <label class="text-[10px] font-bold text-gray-500 block uppercase mb-1">
                                Arrive To
                            </label>

                            <select
                                name="arrive_to"
                                id="arrive_to"
                                required
                                class="w-full border border-gray-300 p-2.5 text-xs outline-none focus:border-red-500 bg-white">

                                <option value="">
                                    Select a Destination
                                </option>

                                <?php while($a = $destinations->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($a['arrive_to']); ?>">
                                        <?php echo htmlspecialchars($a['arrive_to']); ?>
                                    </option>
                                <?php endwhile; ?>

                            </select>
                        </div>

                    </div>

                    <div>
                        <label class="text-[10px] font-bold text-gray-500 block uppercase mb-1">
                            Departure Date
                        </label>

                        <input
                            type="date"
                            id="travel_date"
                            name="travel_date"
                            required
                            min="<?php echo date('Y-m-d'); ?>"
                            class="w-full border border-gray-300 p-2.5 text-xs outline-none focus:border-red-500 bg-white">
                    </div>

                    <button
                        type="submit"
                        class="mt-8 bg-kkkl-red text-white px-10 py-3 flex items-center justify-center gap-2 font-bold text-xs uppercase shadow-md hover:bg-red-700 transition-colors">

                        <i class="fas fa-search"></i>
                        Search

                    </button>

                </form>

            </div>

        </div>

    </div>

    <section class="mt-16 text-center max-w-5xl mx-auto">

        <h2 class="text-kkkl-red font-bold text-2xl mb-6">
            KKKL Group
        </h2>

        <div class="space-y-4 text-sm text-gray-600 leading-relaxed text-justify px-4">
            <p>
                Since its humble start in 22 January 1983, K.K.K.L. Sdn. Bhd. has been dedicating express coach services to passengers. K.K.K.L Sdn. Bhd. is a fast growing express coach company.
            </p>

            <p>
                Today K.K.K.L. Sdn. Bhd. owns more than 100's express coaches.
            </p>
        </div>

    </section>

</main>

<script>
const departSelect = document.getElementById("depart_from");
const arriveSelect = document.getElementById("arrive_to");
const dateInput = document.getElementById("travel_date");

let availableDates = [];

function loadAvailableDates() {

    const depart = departSelect.value;
    const arrive = arriveSelect.value;

    availableDates = [];

    if (!depart || !arrive) {
        return;
    }

    fetch(`get_available_dates.php?depart_from=${encodeURIComponent(depart)}&arrive_to=${encodeURIComponent(arrive)}`)
        .then(response => response.json())
        .then(data => {
            availableDates = data;
        })
        .catch(() => {
            availableDates = [];
        });
}

function validateSelectedDate() {

    if (!departSelect.value || !arriveSelect.value) {
        showPopup("Please select departure and destination first.");
        dateInput.value = "";
        return;
    }

    if (availableDates.length === 0) {
        showPopup("No available tickets for this route.");
        dateInput.value = "";
        return;
    }

    if (!availableDates.includes(dateInput.value)) {
        showPopup("No tickets available for this selected date. Please choose another date.");
        dateInput.value = "";
    }
}

departSelect.addEventListener("change", function() {
    dateInput.value = "";
    loadAvailableDates();
});

arriveSelect.addEventListener("change", function() {
    dateInput.value = "";
    loadAvailableDates();
});

dateInput.addEventListener("change", validateSelectedDate);

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

<div id="customPopup" class="fixed inset-0 bg-black bg-opacity-40 hidden items-center justify-center z-50">

    <div class="bg-white rounded-lg shadow-xl w-full max-w-sm p-6 border-t-4 border-red-700 text-center">

        <div class="text-red-600 text-3xl mb-3">
            <i class="fas fa-exclamation-circle"></i>
        </div>

        <h3 class="font-bold text-lg text-gray-800 mb-2">
            Notice
        </h3>

        <p id="popupMessage" class="text-sm text-gray-600 mb-5"></p>

        <button onclick="closePopup()"
                class="bg-[#bc0000] text-white px-8 py-2 rounded font-bold text-sm">

            OK

        </button>

    </div>

</div>

</body>
</html>