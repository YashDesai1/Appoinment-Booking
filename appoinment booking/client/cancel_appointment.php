<?php
session_start();
if (!isset($_SESSION['client_id'])) {
    header("Location: login.php");
}
include '../db.php';

$message = '';

if (isset($_GET['cancel'])) {
    $id = $_GET['cancel'];
    $client_id = $_SESSION['client_id'];
    $sql = "UPDATE appointments SET status='cancelled' WHERE id=? AND client_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id, $client_id);
    if ($stmt->execute()) {
        $message = "Appointment cancelled successfully";
    } else {
        $message = "Error cancelling appointment";
    }
}

$client_id = $_SESSION['client_id'];
$appointments = $conn->query("SELECT a.*, d.name AS doctor_name, s.date, s.start_time, s.end_time FROM appointments a JOIN doctors d ON a.doctor_id = d.id JOIN schedules s ON a.schedule_id = s.id WHERE a.client_id = $client_id AND a.status != 'cancelled'");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cancel Appointment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Client Dashboard</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>
    <div class="container mt-5">
        <h2>Cancel Appointment</h2>
        <?php if ($message) echo "<div class='alert alert-info'>$message</div>"; ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Doctor</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($appointment = $appointments->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo $appointment['doctor_name']; ?></td>
                    <td><?php echo $appointment['date']; ?></td>
                    <td><?php echo $appointment['start_time'] . ' - ' . $appointment['end_time']; ?></td>
                    <td><?php echo ucfirst($appointment['status']); ?></td>
                    <td>
                        <a href="?cancel=<?php echo $appointment['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Cancel</a>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</body>
</html>