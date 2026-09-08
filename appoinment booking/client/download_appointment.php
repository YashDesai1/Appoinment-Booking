<?php
session_start();
if (!isset($_SESSION['client_id'])) {
    header("Location: login.php");
    exit;
}

include '../db.php';

$client_id = $_SESSION['client_id'];
$appointment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get appointment details
$query = $conn->prepare("SELECT a.*, d.name AS doctor_name, d.specialization, c.name AS client_name, c.email AS client_email, s.date, s.start_time, s.end_time 
    FROM appointments a 
    JOIN doctors d ON a.doctor_id = d.id 
    JOIN clients c ON a.client_id = c.id
    JOIN schedules s ON a.schedule_id = s.id 
    WHERE a.id = ? AND a.client_id = ?");
$query->bind_param("ii", $appointment_id, $client_id);
$query->execute();
$result = $query->get_result();

if ($result->num_rows == 0) {
    header("Location: view_status.php");
    exit;
}

$appointment = $result->fetch_assoc();

// Generate simple HTML PDF content
$timestamp = strtotime($appointment['booked_at']);
$booking_id = "#APT-" . str_pad($appointment_id, 6, '0', STR_PAD_LEFT);
$date = date('F d, Y', strtotime($appointment['date']));
$time = date('h:i A', strtotime($appointment['start_time']));

header('Content-Type: text/html');
header('Content-Disposition: attachment; filename="appointment_' . $appointment_id . '.html"');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Appointment Receipt</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; max-width: 600px; margin: 0 auto; }
        h1 { color: #4facfe; }
        .detail { margin: 15px 0; }
        .label { font-weight: bold; color: #666; }
    </style>
</head>
<body>
    <h1>Appointment Receipt</h1>
    <div class='detail'><span class='label'>Booking ID:</span> $booking_id</div>
    <div class='detail'><span class='label'>Patient:</span> {$appointment['client_name']}</div>
    <div class='detail'><span class='label'>Doctor:</span> Dr. {$appointment['doctor_name']}</div>
    <div class='detail'><span class='label'>Specialization:</span> {$appointment['specialization']}</div>
    <div class='detail'><span class='label'>Date:</span> $date</div>
    <div class='detail'><span class='label'>Time:</span> $time</div>
    <div class='detail'><span class='label'>Status:</span> " . ucfirst($appointment['status']) . "</div>
</body>
</html>";
?>

