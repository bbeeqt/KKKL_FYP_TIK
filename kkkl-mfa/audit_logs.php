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

$logs = $conn->query("
    SELECT
        login_logs.*,
        users.fullname,
        users.role

    FROM login_logs

    LEFT JOIN users
    ON login_logs.user_id = users.id

    ORDER BY login_logs.attempt_time DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Audit Logs</title>

<script src="https://cdn.tailwindcss.com"></script>

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
rel="stylesheet">

</head>

<body class="bg-gray-100">

<div class="flex min-h-screen">

    <!-- SIDEBAR -->

    <aside class="w-64 bg-[#bc0000] text-white p-6">

        <h1 class="text-2xl font-bold mb-10 uppercase">
            Admin Panel
        </h1>

        <nav class="space-y-3">

            <a href="admin_dashboard.php"
               class="block hover:bg-red-800 px-4 py-3 rounded">
                Dashboard
            </a>

            <a href="manage_schedules.php"
               class="block hover:bg-red-800 px-4 py-3 rounded">
                Manage Schedules
            </a>

            <a href="booking_overview.php"
               class="block hover:bg-red-800 px-4 py-3 rounded">
                Booking Overview
            </a>

            <a href="manage_users.php"
               class="block hover:bg-red-800 px-4 py-3 rounded">
                User Management
            </a>

            <a href="audit_logs.php"
               class="block bg-red-800 px-4 py-3 rounded font-bold">
                Audit Logs
            </a>

            <a href="logout.php"
               class="block hover:bg-red-800 px-4 py-3 rounded">
                Logout
            </a>

        </nav>

    </aside>

    <!-- CONTENT -->

    <main class="flex-1 p-10">

        <h2 class="text-3xl font-bold text-gray-800 mb-6">
            Audit Logs
        </h2>

        <div class="bg-white rounded shadow overflow-x-auto">

            <table class="w-full text-sm">

                <thead class="bg-gray-800 text-white">

                    <tr>
                        <th class="p-3 text-left">Log ID</th>
                        <th class="p-3 text-left">User</th>
                        <th class="p-3 text-left">Role</th>
                        <th class="p-3 text-left">Identifier</th>
                        <th class="p-3 text-left">IP Address</th>
                        <th class="p-3 text-left">Status</th>
                        <th class="p-3 text-left">Timestamp</th>
                    </tr>

                </thead>

                <tbody>

                    <?php if ($logs && $logs->num_rows > 0): ?>

                        <?php while($row = $logs->fetch_assoc()): ?>

                            <tr class="border-b hover:bg-gray-50">

                                <td class="p-3 font-bold">
                                    #<?php echo $row['id']; ?>
                                </td>

                                <td class="p-3">

                                    <p class="font-bold">
                                        <?php echo htmlspecialchars($row['fullname'] ?? 'Unknown'); ?>
                                    </p>

                                    <?php if (empty($row['fullname'])): ?>

                                        <p class="text-xs text-gray-400">
                                            Unregistered / Invalid Login
                                        </p>

                                    <?php endif; ?>

                                </td>

                                <td class="p-3">

                                    <?php if (!empty($row['role'])): ?>

                                        <span class="px-3 py-1 rounded-full text-xs font-bold
                                        <?php echo $row['role'] == 'admin'
                                            ? 'bg-red-100 text-red-700'
                                            : 'bg-blue-100 text-blue-700'; ?>">

                                            <?php echo htmlspecialchars($row['role']); ?>

                                        </span>

                                    <?php else: ?>

                                        <span class="text-gray-400 text-xs">
                                            -
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td class="p-3">
                                    <?php echo htmlspecialchars($row['identifier']); ?>
                                </td>

                                <td class="p-3">
                                    <?php echo htmlspecialchars($row['ip_address']); ?>
                                </td>

                                <td class="p-3">

                                    <?php
                                    $status = $row['status'];

                                    $status_class = 'bg-gray-100 text-gray-700';

                                    if ($status == 'SUCCESS') {
                                        $status_class = 'bg-green-100 text-green-700';
                                    }

                                    if ($status == 'FAILED') {
                                        $status_class = 'bg-red-100 text-red-700';
                                    }

                                    if ($status == 'LOCKED') {
                                        $status_class = 'bg-yellow-100 text-yellow-700';
                                    }

                                    if ($status == 'USER_NOT_FOUND') {
                                        $status_class = 'bg-purple-100 text-purple-700';
                                    }
                                    ?>

                                    <span class="px-3 py-1 rounded-full text-xs font-bold <?php echo $status_class; ?>">

                                        <?php echo htmlspecialchars($status); ?>

                                    </span>

                                </td>

                                <td class="p-3">
                                    <?php echo htmlspecialchars($row['attempt_time']); ?>
                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>

                            <td colspan="7"
                                class="p-8 text-center text-gray-500">

                                No audit logs found.

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