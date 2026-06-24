<?php
include 'db.php';

if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header("Location: login.php");
    exit();
}

if (!function_exists('maskEmail')) {
    function maskEmail($email) {
        if (!$email || $email === '-') return '-';
        $parts = explode("@", $email);
        $name = $parts[0];
        $domain = $parts[1] ?? '';
        $masked_name = substr($name, 0, 2) . str_repeat('*', max(0, strlen($name) - 2));
        return $masked_name . '@' . $domain;
    }
}

if (!function_exists('maskPhone')) {
    function maskPhone($phone) {
        if (!$phone || $phone === '-') return '-';
        return str_repeat('*', max(0, strlen($phone) - 4)) . substr($phone, -4);
    }
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Account - KKKL Group</title>
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
    <div class="max-w-6xl mx-auto px-6">
        <ul class="flex text-[11px] font-bold text-gray-700 uppercase">
            <li class="px-5 py-4 hover:text-red-600"><a href="dashboard.php">Home</a></li>
            <li class="px-5 py-4 text-red-600 border-b-2 border-red-600">My Account</li>
        </ul>
    </div>
</nav>

<main class="max-w-6xl mx-auto mt-10 bg-white p-8 shadow-sm">

    <h1 class="text-4xl mb-4">My Account</h1>

    <div class="flex items-center gap-4 mb-10">
        <i class="fas fa-user-circle text-5xl text-gray-700"></i>
        <div>
            <p>Hello!</p>
            <p class="font-bold"><?php echo htmlspecialchars($user['fullname']); ?></p>
        </div>
    </div>

    <div class="flex justify-center gap-8 mb-12">
        <a href="my_bookings.php" class="border-2 border-red-600 text-red-600 px-8 py-2 rounded-full font-bold">
            <i class="fas fa-ticket-alt"></i> My Bookings
        </a>

        <a href="account.php" class="bg-kkkl-red text-white px-8 py-2 rounded-full font-bold shadow">
            <i class="fas fa-user"></i> My Profile
        </a>
    </div>

    <h2 class="font-bold text-gray-600 border-b border-gray-700 pb-3 mb-6">
        <i class="fas fa-user"></i> MEMBER DETAILS
    </h2>

    <div class="border">

        <div class="p-4 border-b">
            <p class="font-bold"><i class="fas fa-user"></i> Full Name</p>
            <p class="mt-4"><?php echo htmlspecialchars($user['fullname']); ?></p>
        </div>

        <div class="grid grid-cols-2">
            <div class="p-4 border-r bg-gray-100">
                <p class="font-bold"><i class="fas fa-phone"></i> Phone Number</p>
                <p class="mt-4"><?php echo htmlspecialchars(maskPhone($user['phone_number'] ?? '-')); ?></p>
            </div>

            <div class="p-4">
                <p class="font-bold"><i class="fas fa-envelope"></i> Email</p>
                <p class="mt-4"><?php echo htmlspecialchars(maskEmail($user['email'] ?? '-')); ?></p>
            </div>
        </div>

    </div>

</main>

</body>
</html>
