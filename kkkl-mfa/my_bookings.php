<?php
include 'db.php';


if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$user_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();

$today = date("Y-m-d");

$upcoming_stmt = $conn->prepare("
    SELECT 
        bookings.*,
        bus_tickets.depart_from,
        bus_tickets.arrive_to,
        bus_tickets.departure_date,
        bus_tickets.departure_time,
        bus_tickets.bus_type
    FROM bookings
    JOIN bus_tickets ON bookings.ticket_id = bus_tickets.id
    WHERE bookings.user_id = ?
    AND bus_tickets.departure_date >= ?
    ORDER BY bus_tickets.departure_date ASC, bus_tickets.departure_time ASC
");

$upcoming_stmt->bind_param("is", $user_id, $today);
$upcoming_stmt->execute();
$upcoming = $upcoming_stmt->get_result();

$past_stmt = $conn->prepare("
    SELECT 
        bookings.*,
        bus_tickets.depart_from,
        bus_tickets.arrive_to,
        bus_tickets.departure_date,
        bus_tickets.departure_time,
        bus_tickets.bus_type
    FROM bookings
    JOIN bus_tickets ON bookings.ticket_id = bus_tickets.id
    WHERE bookings.user_id = ?
    AND bus_tickets.departure_date < ?
    ORDER BY bus_tickets.departure_date DESC, bus_tickets.departure_time DESC
");

$past_stmt->bind_param("is", $user_id, $today);
$past_stmt->execute();
$past = $past_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Bookings - KKKL Group</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<style>
body { font-family: Arial, sans-serif; }
.bg-kkkl-red { background-color: #bc0000; }
.text-kkkl-red { color: #bc0000; }
</style>
</head>

<body class="bg-gray-100">

<div class="bg-kkkl-red text-white py-1 px-4 text-[10px] font-bold">
    <div class="max-w-6xl mx-auto flex justify-end gap-4 uppercase">
        <span>RM (Ringgit Malaysia)</span>
        <a href="account.php" class="hover:underline"><i class="fas fa-user"></i> My Account</a>
        <a href="logout.php" class="hover:underline"><i class="fas fa-lock"></i> Logout</a>
    </div>
</div>

<header class="bg-kkkl-red text-white py-6 px-10 shadow-md">
    <div class="max-w-6xl mx-auto flex justify-between items-center">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 border-2 border-white rounded-full flex items-center justify-center font-bold italic text-3xl">K</div>
            <h1 class="text-5xl font-bold tracking-widest uppercase">KKKL Group</h1>
        </div>
    </div>
</header>

<nav class="bg-white border-b border-gray-300 shadow-sm">
    <div class="max-w-6xl mx-auto px-6 flex justify-between items-center">
        <ul class="flex text-[11px] font-bold text-gray-700 uppercase">
            <li class="px-5 py-4 hover:text-red-600"><a href="dashboard.php">Home</a></li>
            <li class="px-5 py-4 hover:text-red-600">About Us <i class="fas fa-caret-down ml-1"></i></li>
            <li class="px-5 py-4 hover:text-red-600">Our Services <i class="fas fa-caret-down ml-1"></i></li>
            <li class="px-5 py-4 hover:text-red-600">Gallery</li>
            <li class="px-5 py-4 hover:text-red-600">Contact Us</li>
            <li class="px-5 py-4 hover:text-red-600">Mobile Apps</li>
        </ul>

        <div class="bg-blue-800 text-white p-1.5 rounded-sm">
            <i class="fab fa-facebook-f px-1"></i>
        </div>
    </div>
</nav>

<main class="max-w-6xl mx-auto mt-10 bg-white p-8 shadow-sm min-h-[700px]">

    <h1 class="text-4xl mb-4">My Account</h1>

    <div class="flex items-center gap-4 mb-10">
        <i class="fas fa-user-circle text-5xl text-gray-700"></i>
        <div>
            <p>Hello!</p>
            <p class="font-bold"><?php echo htmlspecialchars($user['fullname']); ?></p>
        </div>
    </div>

    <div class="flex justify-center gap-8 mb-10">
        <a href="my_bookings.php"
           class="bg-kkkl-red text-white px-8 py-2 rounded-full font-bold shadow">
            <i class="far fa-credit-card"></i> My Bookings
        </a>

        <a href="account.php"
           class="border-2 border-red-600 text-red-600 px-8 py-2 rounded-full font-bold">
            <i class="fas fa-user"></i> My Profile
        </a>
    </div>

    <div class="border rounded-lg overflow-hidden">

        <div class="grid grid-cols-2 text-center">

            <button
                type="button"
                onclick="showBookingTab('upcoming')"
                id="upcomingTab"
                class="p-4 font-bold bg-red-700 text-white">
                Upcoming Trips
            </button>

            <button
                type="button"
                onclick="showBookingTab('past')"
                id="pastTab"
                class="p-4 font-bold bg-gray-100 text-gray-500">
                Past Bookings
                <p class="text-xs font-normal">
                    <i class="fas fa-info-circle"></i>
                    Showing the booking(s) made within the last 12 months.
                </p>
            </button>

        </div>

        <div id="upcomingContent" class="p-6 min-h-[220px]">

            <?php if ($upcoming->num_rows > 0): ?>

                <?php while($row = $upcoming->fetch_assoc()): ?>

                    <div class="border rounded p-4 mb-4 bg-gray-50">

                        <p class="font-bold text-red-700">
                            <?php echo htmlspecialchars($row['depart_from']); ?>
                            →
                            <?php echo htmlspecialchars($row['arrive_to']); ?>
                        </p>

                        <p class="text-sm text-gray-600 mt-1">
                            <?php echo date("d M Y", strtotime($row['departure_date'])); ?>,
                            <?php echo date("h:i A", strtotime($row['departure_time'])); ?>
                        </p>

                        <p class="text-sm mt-2">
                            <strong>Seats:</strong>
                            <?php echo htmlspecialchars($row['seat_numbers']); ?>
                        </p>

                        <p class="text-sm">
                            <strong>Total:</strong>
                            RM <?php echo number_format($row['total_price'], 2); ?>
                        </p>

                        <div class="mt-4">
                            <a href="generate_pdf.php?booking_id=<?php echo $row['id']; ?>"
                               class="bg-red-600 text-white px-4 py-2 rounded text-xs font-bold">
                                Download Ticket
                            </a>
                        </div>

                    </div>

                <?php endwhile; ?>

            <?php else: ?>

                <p class="text-gray-700 mb-5">
                    Currently you have no upcoming trips.
                </p>

                <a href="dashboard.php"
                   class="bg-red-600 text-white px-5 py-3 rounded font-bold inline-block">
                    Make Booking
                </a>

            <?php endif; ?>

        </div>

        <div id="pastContent" class="p-6 min-h-[220px] hidden">

            <?php if ($past->num_rows > 0): ?>

                <?php while($row = $past->fetch_assoc()): ?>

                    <div class="border rounded p-4 mb-4 bg-gray-50">

                        <p class="font-bold text-red-700">
                            <?php echo htmlspecialchars($row['depart_from']); ?>
                            →
                            <?php echo htmlspecialchars($row['arrive_to']); ?>
                        </p>

                        <p class="text-sm text-gray-600 mt-1">
                            <?php echo date("d M Y", strtotime($row['departure_date'])); ?>,
                            <?php echo date("h:i A", strtotime($row['departure_time'])); ?>
                        </p>

                        <p class="text-sm mt-2">
                            <strong>Seats:</strong>
                            <?php echo htmlspecialchars($row['seat_numbers']); ?>
                        </p>

                        <p class="text-sm">
                            <strong>Total:</strong>
                            RM <?php echo number_format($row['total_price'], 2); ?>
                        </p>

                        <p class="text-sm">
                            <strong>Status:</strong>
                            <?php echo htmlspecialchars($row['status']); ?>
                        </p>

                        <div class="mt-4">
                            <a href="generate_pdf.php?booking_id=<?php echo $row['id']; ?>"
                               class="border border-red-600 text-red-600 px-4 py-2 rounded text-xs font-bold">
                                View Ticket
                            </a>
                        </div>

                    </div>

                <?php endwhile; ?>

            <?php else: ?>

                <p class="text-gray-500 text-center mt-10">
                    No past bookings found.
                </p>

            <?php endif; ?>

        </div>

    </div>

</main>

<script>
function showBookingTab(type) {

    const upcomingTab = document.getElementById('upcomingTab');
    const pastTab = document.getElementById('pastTab');
    const upcomingContent = document.getElementById('upcomingContent');
    const pastContent = document.getElementById('pastContent');

    if (type === 'upcoming') {

        upcomingContent.classList.remove('hidden');
        pastContent.classList.add('hidden');

        upcomingTab.className = 'p-4 font-bold bg-red-700 text-white';
        pastTab.className = 'p-4 font-bold bg-gray-100 text-gray-500';

    } else {

        pastContent.classList.remove('hidden');
        upcomingContent.classList.add('hidden');

        pastTab.className = 'p-4 font-bold bg-red-700 text-white';
        upcomingTab.className = 'p-4 font-bold bg-gray-100 text-gray-500';
    }
}
</script>

</body>
</html>