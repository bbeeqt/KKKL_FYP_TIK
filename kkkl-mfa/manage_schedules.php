<?php
include 'db.php';

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

$message = "";
$error_message = "";

/*
|--------------------------------------------------------------------------
| AUTO MARK PAST SCHEDULES AS UNAVAILABLE
|--------------------------------------------------------------------------
*/

$conn->query("
    UPDATE bus_tickets
    SET status = 'unavailable'
    WHERE departure_date < CURDATE()
    AND status = 'available'
");

if (isset($_POST['add_schedule'])) {

    $bus_number = trim($_POST['bus_number']);
    $depart_from = trim($_POST['depart_from']);
    $arrive_to = trim($_POST['arrive_to']);
    $departure_date = $_POST['departure_date'];
    $departure_time = $_POST['departure_time'];
    $price = $_POST['price'];
    $bus_type = $_POST['bus_type'];
    $available_seats = $_POST['available_seats'];
    $status = $_POST['status'];

    if ($available_seats > 15) {
        $available_seats = 15;
    }

    if ($available_seats < 1) {
        $available_seats = 1;
    }

    if ($departure_date < date("Y-m-d")) {
        $status = "unavailable";
    }

    
    if ($status === 'available') {
        $check_bus = $conn->prepare("SELECT id FROM bus_tickets WHERE bus_number = ? AND status = 'available'");
        $check_bus->bind_param("s", $bus_number);
        $check_bus->execute();
        if ($check_bus->get_result()->num_rows > 0) {
            $error_message = "Error: Bus number '$bus_number' is already scheduled and marked 'available'.";
        }
    }

    if (empty($error_message)) {
        $check_dup = $conn->prepare("SELECT id FROM bus_tickets WHERE bus_number = ? AND departure_date = ? AND departure_time = ?");
        $check_dup->bind_param("sss", $bus_number, $departure_date, $departure_time);
        $check_dup->execute();
        if ($check_dup->get_result()->num_rows > 0) {
            $error_message = "Error: An identical schedule entry for Bus '$bus_number' at this exact date and time already exists.";
        }
    }

    if (empty($error_message)) {
        $stmt = $conn->prepare("
            INSERT INTO bus_tickets
            (
                bus_number,
                depart_from,
                arrive_to,
                departure_date,
                departure_time,
                price,
                bus_type,
                available_seats,
                status
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "sssssdsis",
            $bus_number,
            $depart_from,
            $arrive_to,
            $departure_date,
            $departure_time,
            $price,
            $bus_type,
            $available_seats,
            $status
        );

        if ($stmt->execute()) {
            $message = "Schedule added successfully.";
        } else {
            $error_message = "Failed to add schedule due to a database error.";
        }
    }
}

if (isset($_POST['update_schedule'])) {

    $id = $_POST['id'];
    $bus_number = trim($_POST['bus_number']);
    $depart_from = trim($_POST['depart_from']);
    $arrive_to = trim($_POST['arrive_to']);
    $departure_date = $_POST['departure_date'];
    $departure_time = $_POST['departure_time'];
    $price = $_POST['price'];
    $bus_type = $_POST['bus_type'];
    $available_seats = $_POST['available_seats'];
    $status = $_POST['status'];

    if ($available_seats > 15) {
        $available_seats = 15;
    }

    if ($available_seats < 1) {
        $available_seats = 1;
    }

    if ($departure_date < date("Y-m-d")) {
        $status = "unavailable";
    }

    if ($status === 'available') {
        $check_bus = $conn->prepare("SELECT id FROM bus_tickets WHERE bus_number = ? AND status = 'available' AND id != ?");
        $check_bus->bind_param("si", $bus_number, $id);
        $check_bus->execute();
        if ($check_bus->get_result()->num_rows > 0) {
            $error_message = "Update Error: Bus number '$bus_number' is already active on another live 'available' route.";
        }
    }

    if (empty($error_message)) {
        $check_dup = $conn->prepare("SELECT id FROM bus_tickets WHERE bus_number = ? AND departure_date = ? AND departure_time = ? AND id != ?");
        $check_dup->bind_param("sssi", $bus_number, $departure_date, $departure_time, $id);
        $check_dup->execute();
        if ($check_dup->get_result()->num_rows > 0) {
            $error_message = "Update Error: An identical scheduling conflict (Bus, Date, Time) overlaps with another database entry.";
        }
    }

    if (empty($error_message)) {
        $stmt = $conn->prepare("
            UPDATE bus_tickets
            SET bus_number = ?,
                depart_from = ?,
                arrive_to = ?,
                departure_date = ?,
                departure_time = ?,
                price = ?,
                bus_type = ?,
                available_seats = ?,
                status = ?
            WHERE id = ?
        ");

        $stmt->bind_param(
            "sssssdsisi",
            $bus_number,
            $depart_from,
            $arrive_to,
            $departure_date,
            $departure_time,
            $price,
            $bus_type,
            $available_seats,
            $status,
            $id
        );

        if ($stmt->execute()) {
            $message = "Schedule updated successfully.";
        } else {
            $error_message = "Failed to update schedule due to a database error.";
        }
    }
}

if (isset($_GET['delete'])) {

    $id = $_GET['delete'];

    $stmt = $conn->prepare("
        DELETE FROM bus_tickets
        WHERE id = ?
    ");

    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $message = "Schedule deleted successfully.";
    }
}

$schedules = $conn->query("
    SELECT *
    FROM bus_tickets
    ORDER BY departure_date DESC, departure_time DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Schedules</title>
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

            <a href="manage_schedules.php" class="block bg-red-800 px-4 py-3 rounded font-bold">
                Manage Schedules
            </a>

            <a href="booking_overview.php" class="block hover:bg-red-800 px-4 py-3 rounded">
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
            Manage Bus Schedules
        </h2>

        <?php if ($message): ?>
            <div class="bg-green-50 border-l-4 border-green-600 text-green-700 p-4 mb-6">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="bg-red-50 border-l-4 border-red-600 text-red-700 p-4 mb-6">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div class="bg-white p-6 rounded shadow mb-8">

            <h3 class="font-bold text-lg mb-4">
                Add New Schedule
            </h3>

            <form method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4">

                <div>
                    <label class="block font-bold mb-2 text-sm text-gray-700">Bus Number</label>
                    <input type="text" name="bus_number" placeholder="Example: KKKL101" value="<?php echo isset($_POST['bus_number']) && !empty($error_message) ? htmlspecialchars($_POST['bus_number']) : ''; ?>" required class="border p-3 rounded w-full">
                </div>

                <div>
                    <label class="block font-bold mb-2 text-sm text-gray-700">Depart From</label>
                    <input type="text" name="depart_from" placeholder="Depart From" value="<?php echo isset($_POST['depart_from']) && !empty($error_message) ? htmlspecialchars($_POST['depart_from']) : ''; ?>" required class="border p-3 rounded w-full">
                </div>

                <div>
                    <label class="block font-bold mb-2 text-sm text-gray-700">Arrive To</label>
                    <input type="text" name="arrive_to" placeholder="Arrive To" value="<?php echo isset($_POST['arrive_to']) && !empty($error_message) ? htmlspecialchars($_POST['arrive_to']) : ''; ?>" required class="border p-3 rounded w-full">
                </div>

                <div>
                    <label class="block font-bold mb-2 text-sm text-gray-700">Departure Date</label>
                    <input type="date" name="departure_date" value="<?php echo isset($_POST['departure_date']) && !empty($error_message) ? htmlspecialchars($_POST['departure_date']) : ''; ?>" required class="border p-3 rounded w-full">
                </div>

                <div>
                    <label class="block font-bold mb-2 text-sm text-gray-700">Departure Time</label>
                    <input type="time" name="departure_time" value="<?php echo isset($_POST['departure_time']) && !empty($error_message) ? htmlspecialchars($_POST['departure_time']) : ''; ?>" required class="border p-3 rounded w-full">
                </div>

                <div>
                    <label class="block font-bold mb-2 text-sm text-gray-700">Price (RM)</label>
                    <input type="number" step="0.01" name="price" placeholder="Price" value="<?php echo isset($_POST['price']) && !empty($error_message) ? htmlspecialchars($_POST['price']) : ''; ?>" required class="border p-3 rounded w-full">
                </div>

                <div>
                    <label class="block font-bold mb-2 text-sm text-gray-700">Bus Type</label>
                    <select name="bus_type" required class="border p-3 rounded w-full">
                        <option value="STANDARD" <?php echo isset($_POST['bus_type']) && $_POST['bus_type'] == 'STANDARD' && !empty($error_message) ? 'selected' : ''; ?>>STANDARD</option>
                        <option value="EXECUTIVE" <?php echo isset($_POST['bus_type']) && $_POST['bus_type'] == 'EXECUTIVE' && !empty($error_message) ? 'selected' : ''; ?>>EXECUTIVE</option>
                    </select>
                </div>

                <div>
                    <label class="block font-bold mb-2 text-sm text-gray-700">Total Seats</label>
                    <input type="number" name="available_seats" placeholder="Seats" value="<?php echo isset($_POST['available_seats']) && !empty($error_message) ? htmlspecialchars($_POST['available_seats']) : ''; ?>" required min="1" max="15" class="border p-3 rounded w-full">
                </div>

                <div>
                    <label class="block font-bold mb-2 text-sm text-gray-700">Status</label>
                    <select name="status" required class="border p-3 rounded w-full">
                        <option value="available" <?php echo isset($_POST['status']) && $_POST['status'] == 'available' && !empty($error_message) ? 'selected' : ''; ?>>available</option>
                        <option value="unavailable" <?php echo isset($_POST['status']) && $_POST['status'] == 'unavailable' && !empty($error_message) ? 'selected' : ''; ?>>unavailable</option>
                    </select>
                </div>

                <button type="submit" name="add_schedule" class="bg-[#bc0000] text-white px-6 py-3 rounded font-bold md:col-span-4 mt-2">
                    Add Schedule
                </button>

            </form>

        </div>

        <div class="bg-white rounded shadow overflow-x-auto">

            <table class="w-full text-sm">

                <thead class="bg-gray-800 text-white">
                    <tr>
                        <th class="p-3 text-left">Bus Number</th>
                        <th class="p-3 text-left">Route</th>
                        <th class="p-3 text-left">Date</th>
                        <th class="p-3 text-left">Time</th>
                        <th class="p-3 text-left">Price</th>
                        <th class="p-3 text-left">Type</th>
                        <th class="p-3 text-left">Seats</th>
                        <th class="p-3 text-left">Status</th>
                        <th class="p-3 text-left">Action</th>
                    </tr>
                </thead>

                <tbody>

                    <?php while($row = $schedules->fetch_assoc()): ?>

                    <tr class="border-b">

                        <form method="POST">

                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">

                            <td class="p-3">
                                <input type="text" name="bus_number" value="<?php echo htmlspecialchars($row['bus_number']); ?>" class="border p-2 rounded w-28 font-bold text-blue-700">
                            </td>

                            <td class="p-3">
                                <input type="text" name="depart_from" value="<?php echo htmlspecialchars($row['depart_from']); ?>" class="border p-2 rounded w-full mb-1">
                                <input type="text" name="arrive_to" value="<?php echo htmlspecialchars($row['arrive_to']); ?>" class="border p-2 rounded w-full">
                            </td>

                            <td class="p-3">
                                <input type="date" name="departure_date" value="<?php echo $row['departure_date']; ?>" class="border p-2 rounded">
                            </td>

                            <td class="p-3">
                                <input type="time" name="departure_time" value="<?php echo $row['departure_time']; ?>" class="border p-2 rounded">
                            </td>

                            <td class="p-3">
                                <input type="number" step="0.01" name="price" value="<?php echo $row['price']; ?>" class="border p-2 rounded w-24">
                            </td>

                            <td class="p-3">
                                <select name="bus_type" class="border p-2 rounded">
                                    <option value="STANDARD" <?php echo $row['bus_type'] == 'STANDARD' ? 'selected' : ''; ?>>STANDARD</option>
                                    <option value="EXECUTIVE" <?php echo $row['bus_type'] == 'EXECUTIVE' ? 'selected' : ''; ?>>EXECUTIVE</option>
                                </select>
                            </td>

                            <td class="p-3">
                                <input type="number" name="available_seats" value="<?php echo $row['available_seats']; ?>" min="1" max="15" class="border p-2 rounded w-20">
                            </td>

                            <td class="p-3">
                                <select name="status" class="border p-2 rounded">
                                    <option value="available" <?php echo $row['status'] == 'available' ? 'selected' : ''; ?>>available</option>
                                    <option value="unavailable" <?php echo $row['status'] == 'unavailable' ? 'selected' : ''; ?>>unavailable</option>
                                </select>

                                <?php if ($row['departure_date'] < date("Y-m-d")): ?>
                                    <p class="text-[10px] text-red-600 mt-1 font-bold">
                                        Past schedule
                                    </p>
                                <?php endif; ?>
                            </td>

                            <td class="p-3 space-y-2">
                                <button type="submit" name="update_schedule" class="w-full bg-blue-600 text-white px-3 py-2 rounded text-xs font-bold block text-center">
                                    Update
                                </button>

                                <a href="manage_schedules.php?delete=<?php echo $row['id']; ?>"
                                   onclick="return confirm('Delete this schedule?')"
                                   class="w-full bg-red-600 text-white px-3 py-2 rounded text-xs font-bold block text-center">
                                    Delete
                                </a>
                            </td>

                        </form>

                    </tr>

                    <?php endwhile; ?>

                </tbody>

            </table>

        </div>

    </main>

</div>

</body>
</html>
