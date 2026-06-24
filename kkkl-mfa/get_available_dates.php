<?php
include 'db.php';

$depart_from = $_GET['depart_from'] ?? '';
$arrive_to = $_GET['arrive_to'] ?? '';

$stmt = $conn->prepare("
    SELECT DISTINCT departure_date
    FROM bus_tickets
    WHERE depart_from = ?
    AND arrive_to = ?
    AND departure_date >= CURDATE()
    AND status = 'available'
");

$stmt->bind_param("ss", $depart_from, $arrive_to);
$stmt->execute();

$result = $stmt->get_result();

$dates = [];

while($row = $result->fetch_assoc()) {
    $dates[] = $row['departure_date'];
}

header('Content-Type: application/json');
echo json_encode($dates);
?>