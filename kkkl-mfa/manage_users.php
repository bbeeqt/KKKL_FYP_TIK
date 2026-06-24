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

$message = "";

if (isset($_GET['unlock'])) {

    $user_id = $_GET['unlock'];

    $stmt = $conn->prepare("
        UPDATE users
        SET login_attempts = 0,
            lockout_until = NULL,
            is_locked = 0
        WHERE id = ?
    ");

    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        $message = "User account unlocked successfully.";
    }
}

if (isset($_GET['make_admin'])) {

    $user_id = $_GET['make_admin'];

    $stmt = $conn->prepare("
        UPDATE users
        SET role = 'admin'
        WHERE id = ?
    ");

    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        $message = "User role changed to admin.";
    }
}

if (isset($_GET['make_user'])) {

    $user_id = $_GET['make_user'];

    $stmt = $conn->prepare("
        UPDATE users
        SET role = 'user'
        WHERE id = ?
    ");

    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        $message = "Account role changed to user.";
    }
}

$users = $conn->query("
    SELECT *
    FROM users
    ORDER BY id DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>User Management</title>
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

            <a href="booking_overview.php" class="block hover:bg-red-800 px-4 py-3 rounded">
                Booking Overview
            </a>

            <a href="manage_users.php" class="block bg-red-800 px-4 py-3 rounded font-bold">
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
            User Management
        </h2>

        <?php if ($message): ?>
            <div class="bg-green-50 border-l-4 border-green-600 text-green-700 p-4 mb-6">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded shadow overflow-x-auto">

            <table class="w-full text-sm">

                <thead class="bg-gray-800 text-white">
                    <tr>
                        <th class="p-3 text-left">User ID</th>
                        <th class="p-3 text-left">Full Name</th>
                        <th class="p-3 text-left">Email / OTP Email</th>
                        <th class="p-3 text-left">Phone</th>
                        <th class="p-3 text-left">Role</th>
                        <th class="p-3 text-left">MFA Status</th>
                        <th class="p-3 text-left">Attempts</th>
                        <th class="p-3 text-left">Action</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if ($users && $users->num_rows > 0): ?>

                        <?php while($row = $users->fetch_assoc()): ?>

                            <tr class="border-b hover:bg-gray-50">

                                <td class="p-3 font-bold">
                                    #<?php echo $row['id']; ?>
                                </td>

                                <td class="p-3">
                                    <?php echo htmlspecialchars($row['fullname'] ?? '-'); ?>
                                </td>

                                <td class="p-3">
                                    <p class="font-bold">
                                        <?php echo htmlspecialchars(maskEmail($row['email'] ?? '-')); ?>
                                    </p>

                                    <p class="text-xs text-gray-500">
                                        OTP:
                                        <?php echo htmlspecialchars(maskEmail($row['otp_email'] ?? $row['email'] ?? '-')); ?>
                                    </p>
                                </td>

                                <td class="p-3">
                                    <?php echo htmlspecialchars(maskPhone($row['phone_number'] ?? '-')); ?>
                                </td>

                                <td class="p-3">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold
                                        <?php echo $row['role'] == 'admin' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700'; ?>">
                                        <?php echo htmlspecialchars($row['role'] ?? 'user'); ?>
                                    </span>
                                </td>

                                <td class="p-3">
                                    <?php if (!empty($row['is_locked']) && $row['is_locked'] == 1): ?>

                                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700">
                                            Locked
                                        </span>

                                        <p class="text-[10px] text-gray-500 mt-1">
                                            Until:
                                            <?php echo $row['lockout_until'] ?? '-'; ?>
                                        </p>

                                    <?php else: ?>

                                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700">
                                            Active
                                        </span>

                                    <?php endif; ?>
                                </td>

                                <td class="p-3 text-xs">

                                    <?php
                                    $attempt = $row['login_attempts'];

                                    if (!empty($row['is_locked']) && $row['is_locked'] == 1) {

                                        echo "Account locked";

                                    } elseif ($attempt == 0) {

                                        echo "0";

                                    } elseif ($attempt == 1) {

                                        echo "1";

                                    } elseif ($attempt == 2) {

                                        echo "2";

                                    } else {

                                        echo $attempt . " = Failed attempts";
                                    }
                                    ?>

                                </td>

                                <td class="p-3 space-y-2">

                                    <?php if (!empty($row['is_locked']) && $row['is_locked'] == 1): ?>
                                        <a href="manage_users.php?unlock=<?php echo $row['id']; ?>"
                                           onclick="return confirm('Unlock this account?')"
                                           class="inline-block bg-green-600 text-white px-3 py-2 rounded text-xs font-bold">
                                            Unlock
                                        </a>
                                    <?php endif; ?>

                                    <?php if (($row['role'] ?? 'user') == 'user'): ?>

                                        <a href="manage_users.php?make_admin=<?php echo $row['id']; ?>"
                                           onclick="return confirm('Make this user admin?')"
                                           class="inline-block bg-red-600 text-white px-3 py-2 rounded text-xs font-bold">
                                            Make Admin
                                        </a>

                                    <?php else: ?>

                                        <?php if ($row['id'] != $_SESSION['user_id']): ?>
                                            <a href="manage_users.php?make_user=<?php echo $row['id']; ?>"
                                               onclick="return confirm('Change this admin to normal user?')"
                                               class="inline-block bg-blue-600 text-white px-3 py-2 rounded text-xs font-bold">
                                                Make User
                                            </a>
                                        <?php endif; ?>

                                    <?php endif; ?>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="8" class="p-8 text-center text-gray-500">
                                No users found.
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