<?php
include 'db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header("Location: login.php");
    exit();
}

$booking = $_SESSION['temp_booking'] ?? null;
if (!$booking) {
    header("Location: dashboard.php");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM bus_tickets WHERE id = ?");
$stmt->bind_param("i", $booking['ticket_id']);
$stmt->execute();
$ticket = $stmt->get_result()->fetch_assoc();

$seats_array = is_array($booking['seat']) ? $booking['seat'] : explode(',', $booking['seat']);
$seat_count = count($seats_array);
$seat_display = implode(', ', $seats_array);

$ticket_total = $ticket['price'] * $seat_count;
$insurance_unit = ($booking['insurance'] == 'yes') ? 1.00 : 0.00;
$insurance_total = $insurance_unit * $seat_count;
$total_price = $ticket_total + $insurance_total;

$_SESSION['temp_booking']['total_to_pay'] = $total_price;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment - KKKL Group</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 pb-10">

    <header class="bg-[#bc0000] text-white py-4 px-10 shadow-md">
        <div class="max-w-6xl mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold uppercase">KKKL Group</h1>
        </div>
    </header>

    <main class="max-w-6xl mx-auto mt-8 px-4 grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white p-6 border rounded shadow-sm">
                <h2 class="font-bold text-gray-700 mb-4 uppercase text-sm border-b pb-2">Select Payment Method</h2>
                
                <form action="transaction_otp_process.php" method="POST" id="paymentForm">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                        <label class="border p-4 rounded flex flex-col items-center cursor-pointer hover:border-red-500 has-[:checked]:border-red-500 has-[:checked]:bg-red-50">
                            <input type="radio" name="payment_method" value="DuitNow" class="hidden" required>
                            <i class="fas fa-qrcode text-2xl mb-2"></i>
                            <span class="text-[10px] font-bold">DuitNow</span>
                        </label>
                        <label class="border p-4 rounded flex flex-col items-center cursor-pointer hover:border-red-500 has-[:checked]:border-red-500 has-[:checked]:bg-red-50">
                            <input type="radio" name="payment_method" value="E-Wallet" class="hidden">
                            <i class="fas fa-wallet text-2xl mb-2"></i>
                            <span class="text-[10px] font-bold">E-Wallet</span>
                        </label>
                        <label class="border p-4 rounded flex flex-col items-center cursor-pointer hover:border-red-500 has-[:checked]:border-red-500 has-[:checked]:bg-red-50">
                            <input type="radio" name="payment_method" value="Online Banking" class="hidden">
                            <i class="fas fa-university text-2xl mb-2"></i>
                            <span class="text-[10px] font-bold">Online Banking</span>
                        </label>
                        <label class="border p-4 rounded flex flex-col items-center cursor-pointer hover:border-red-500 has-[:checked]:border-red-500 has-[:checked]:bg-red-50">
                            <input type="radio" name="payment_method" value="Credit Card" class="hidden">
                            <i class="far fa-credit-card text-2xl mb-2"></i>
                            <span class="text-[10px] font-bold">Credit Card</span>
                        </label>
                    </div>

                    <div class="bg-blue-50 p-4 border border-blue-200 rounded mb-6 flex items-start gap-3">
                        <i class="fas fa-shield-alt text-blue-600 mt-1"></i>
                        <p class="text-[11px] text-blue-800">
                            <strong>Security Note:</strong> Clicking the button below will trigger a <strong>Transaction OTP</strong> to your registered email for additional security as part of our MFA protocol.
                        </p>
                    </div>

                    <label class="flex items-center gap-2 cursor-pointer mb-6">
                        <input type="checkbox" required class="w-4 h-4 text-red-600">
                        <span class="text-[10px] text-gray-500">I agree to the terms & conditions.</span>
                    </label>

                    <button type="submit" class="w-full bg-[#bc0000] text-white py-4 font-bold uppercase tracking-widest shadow-lg hover:bg-red-700 transition">
                        Proceed to Secure Verification
                    </button>
                </form>
            </div>
        </div>

        <div class="lg:col-span-1">
            <div class="bg-white border rounded shadow-sm overflow-hidden sticky top-20">
                <div class="bg-gray-800 text-white p-4 font-bold text-sm uppercase">Booking Summary</div>
                <div class="p-5 space-y-4">
                    <div>
                        <p class="text-[10px] font-bold text-red-600 uppercase">Trip Details</p>
                        <p class="text-xs font-bold"><?php echo date("d M Y", strtotime($ticket['departure_date'])); ?></p>
                        <p class="text-sm font-bold text-blue-800"><?php echo $ticket['depart_from']; ?> - <?php echo $ticket['arrive_to']; ?></p>
                        <p class="text-[10px] text-gray-700 mt-1"><strong>Seats:</strong> <?php echo $seat_display; ?> (<?php echo $seat_count; ?> Pax)</p>
                    </div>

                    <div class="border-t pt-4 space-y-2">
                        <div class="flex justify-between text-xs">
                            <span>Fare (RM <?php echo number_format($ticket['price'],2); ?> x <?php echo $seat_count; ?>)</span>
                            <span>RM <?php echo number_format($ticket_total, 2); ?></span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span>Insurance (RM <?php echo number_format($insurance_unit,2); ?> x <?php echo $seat_count; ?>)</span>
                            <span>RM <?php echo number_format($insurance_total, 2); ?></span>
                        </div>
                    </div>

                    <div class="border-t pt-4 flex justify-between items-center font-bold">
                        <span class="text-sm">TOTAL AMOUNT</span>
                        <span class="text-xl text-red-600">RM <?php echo number_format($total_price, 2); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
