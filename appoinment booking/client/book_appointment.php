<?php
session_start();
if (!isset($_SESSION['client_id'])) {
    header("Location: login.php");
    exit;
}
include '../db.php';

$client_id = $_SESSION['client_id'];
$doctor_id = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 0;
$schedule_id = isset($_GET['schedule_id']) ? intval($_GET['schedule_id']) : 0;

$message = '';
$messageType = '';
$booking_success = false;
$booked_appointment = null;

// Get doctor info
if ($doctor_id > 0) {
    $doctor = $conn->query("SELECT * FROM doctors WHERE id = $doctor_id")->fetch_assoc();
}

// Handle booking
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book'])) {
    $schedule_id = intval($_POST['schedule_id']);
    $notes = trim($_POST['notes'] ?? '');
    
    // Get schedule info
    $schedule = $conn->query("SELECT * FROM schedules WHERE id = $schedule_id AND available = 1")->fetch_assoc();
    
    if ($schedule) {
        // Check if already booked
        $check = $conn->query("SELECT id FROM appointments WHERE client_id = $client_id AND schedule_id = $schedule_id AND status != 'cancelled'");
        
        if ($check->num_rows == 0) {
            $stmt = $conn->prepare("INSERT INTO appointments (client_id, doctor_id, schedule_id, notes) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiis", $client_id, $schedule['doctor_id'], $schedule_id, $notes);
            
            if ($stmt->execute()) {
                // Mark schedule as unavailable if max patients reached
                $conn->query("UPDATE schedules SET available = 0 WHERE id = $schedule_id");
                
                $new_apt_id = $stmt->insert_id ? $stmt->insert_id : $conn->insert_id;
                $booking_success = true;
                $booked_appointment = [
                    'id' => $new_apt_id,
                    'doctor_id' => $schedule['doctor_id'],
                    'date' => $schedule['date'],
                    'start_time' => $schedule['start_time'],
                    'end_time' => $schedule['end_time']
                ];
                
                // Get doctor name
                $doc = $conn->query("SELECT name, specialization FROM doctors WHERE id = {$schedule['doctor_id']}")->fetch_assoc();
                $booked_appointment['doctor_name'] = $doc['name'];
                $booked_appointment['specialization'] = $doc['specialization'];
                
                $message = "Appointment booked successfully!";
                $messageType = "success";
            } else {
                $message = "Error booking appointment. Please try again.";
                $messageType = "danger";
            }
        } else {
            $message = "You have already booked this slot.";
            $messageType = "warning";
        }
    } else {
        $message = "This slot is no longer available.";
        $messageType = "danger";
    }
}

// Get available schedules
if ($doctor_id > 0) {
    $schedules = $conn->query("
        SELECT * FROM schedules 
        WHERE doctor_id = $doctor_id 
        AND date >= CURDATE() 
        AND available = 1 
        ORDER BY date, start_time
    ");
} else {
    // Get all doctors with available schedules
    $doctors = $conn->query("
        SELECT DISTINCT d.* FROM doctors d 
        INNER JOIN schedules s ON d.id = s.doctor_id 
        WHERE s.date >= CURDATE() AND s.available = 1
        ORDER BY d.name
    ");
}

// Client info
$client = $conn->query("SELECT name FROM clients WHERE id = $client_id")->fetch_assoc();
$initials = strtoupper(substr($client['name'], 0, 1));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - MedAppoint</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .page-header {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--glass-border);
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
        }
        
        .logo i {
            font-size: 1.75rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .logo span {
            font-size: 1.25rem;
            font-weight: 700;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .page-main {
            max-width: 900px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
            text-decoration: none;
        }
        
        .back-link:hover {
            color: var(--primary-color);
        }
        
        .booking-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            padding: 2rem;
        }
        
        .booking-card h2 {
            margin-bottom: 0.5rem;
        }
        
        .booking-card h2 span {
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        /* Doctor Info */
        .doctor-info-bar {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: rgba(102, 126, 234, 0.1);
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
        }
        
        .doctor-info-bar .avatar {
            width: 50px;
            height: 50px;
            background: var(--primary-gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
        }
        
        .doctor-info-bar h4 {
            margin: 0;
        }
        
        .doctor-info-bar p {
            margin: 0;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        /* Schedule Grid */
        .schedule-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .schedule-option {
            position: relative;
        }
        
        .schedule-option input {
            position: absolute;
            opacity: 0;
        }
        
        .schedule-option label {
            display: block;
            padding: 1rem;
            background: rgba(255,255,255,0.03);
            border: 2px solid var(--glass-border);
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: var(--transition-normal);
            text-align: center;
        }
        
        .schedule-option label:hover {
            border-color: rgba(102, 126, 234, 0.5);
        }
        
        .schedule-option input:checked + label {
            border-color: var(--primary-color);
            background: rgba(102, 126, 234, 0.1);
        }
        
        .schedule-option .date {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .schedule-option .time {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        /* Success Card */
        .success-card {
            text-align: center;
            padding: 2rem;
        }
        
        .success-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #10b981, #059669);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }
        
        .success-icon i {
            font-size: 3rem;
            color: white;
        }
        
        .success-card h2 {
            color: #10b981;
            margin-bottom: 0.5rem;
        }
        
        .appointment-details {
            background: rgba(255,255,255,0.03);
            border-radius: var(--radius-md);
            padding: 1.5rem;
            margin: 1.5rem 0;
            text-align: left;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--glass-border);
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-row .label {
            color: var(--text-secondary);
        }
        
        .detail-row .value {
            font-weight: 500;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 1.5rem;
        }
        
        @media print {
            .page-header, .back-link, .action-buttons {
                display: none !important;
            }
            
            body {
                background: white !important;
                color: black !important;
            }
            
            .booking-card {
                border: 1px solid #ddd;
                background: white !important;
            }
            
            .success-icon {
                background: #10b981 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="page-header">
        <div class="header-content">
            <a href="dashboard.php" class="logo">
                <i class="fas fa-hospital-alt"></i>
                <span>MedAppoint</span>
            </a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="page-main">
        <a href="<?php echo $doctor_id ? "doctor_profile.php?id=$doctor_id" : "view_doctors.php"; ?>" class="back-link">
            <i class="fas fa-arrow-left"></i> Back
        </a>
        
        <?php if ($booking_success && $booked_appointment): ?>
        <!-- Success Card -->
        <div class="booking-card success-card" id="printArea">
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>
            <h2>Appointment Confirmed!</h2>
            <p class="text-secondary">Your appointment has been booked successfully.</p>
            
            <div class="appointment-details">
                <div class="detail-row">
                    <span class="label">Appointment ID</span>
                    <span class="value" style="font-weight: 700; color: var(--primary-color);">#APT-<?php echo str_pad($booked_appointment['id'], 6, '0', STR_PAD_LEFT); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Doctor</span>
                    <span class="value">Dr. <?php echo htmlspecialchars($booked_appointment['doctor_name']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Specialization</span>
                    <span class="value"><?php echo htmlspecialchars($booked_appointment['specialization']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Date</span>
                    <span class="value"><?php echo date('l, F d, Y', strtotime($booked_appointment['date'])); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Time</span>
                    <span class="value">
                        <?php echo date('h:i A', strtotime($booked_appointment['start_time'])); ?> - 
                        <?php echo date('h:i A', strtotime($booked_appointment['end_time'])); ?>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="label">Status</span>
                    <span class="value" style="color: #fbbf24;">Pending Approval</span>
                </div>
            </div>
            
            <div class="action-buttons">
                <button onclick="window.print()" class="btn btn-secondary">
                    <i class="fas fa-print me-2"></i>Print
                </button>
                <a href="view_status.php" class="btn btn-primary">
                    <i class="fas fa-clipboard-list me-2"></i>View Appointments
                </a>
            </div>
        </div>
        
        <?php else: ?>
        <!-- Booking Form -->
        <div class="booking-card">
            <h2>Book <span>Appointment</span></h2>
            <p class="text-secondary mb-4">Select an available time slot</p>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> mb-4">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($doctor_id > 0 && isset($doctor)): ?>
            <!-- Doctor Info -->
            <div class="doctor-info-bar">
                <div class="avatar">
                    <i class="fas fa-user-md"></i>
                </div>
                <div>
                    <h4>Dr. <?php echo htmlspecialchars($doctor['name']); ?></h4>
                    <p><?php echo htmlspecialchars($doctor['specialization']); ?></p>
                </div>
            </div>
            
            <form method="POST">
                <h5 class="mb-3"><i class="fas fa-clock me-2 text-primary"></i>Available Slots</h5>
                
                <?php if ($schedules && $schedules->num_rows > 0): ?>
                <div class="schedule-grid">
                    <?php while ($slot = $schedules->fetch_assoc()): ?>
                    <div class="schedule-option">
                        <input type="radio" name="schedule_id" id="slot_<?php echo $slot['id']; ?>" 
                               value="<?php echo $slot['id']; ?>" 
                               <?php echo $schedule_id == $slot['id'] ? 'checked' : ''; ?> required>
                        <label for="slot_<?php echo $slot['id']; ?>">
                            <div class="date"><?php echo date('D, M d', strtotime($slot['date'])); ?></div>
                            <div class="time">
                                <?php echo date('h:i A', strtotime($slot['start_time'])); ?> - 
                                <?php echo date('h:i A', strtotime($slot['end_time'])); ?>
                            </div>
                        </label>
                    </div>
                    <?php endwhile; ?>
                </div>
                
                <div class="mb-4">
                    <label class="form-label text-secondary">Notes (Optional)</label>
                    <textarea name="notes" class="form-control" rows="3" placeholder="Any specific concerns or symptoms..."></textarea>
                </div>
                
                <button type="submit" name="book" class="btn btn-primary w-100">
                    <i class="fas fa-calendar-check me-2"></i>Confirm Booking
                </button>
                <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-calendar-times" style="font-size: 3rem; color: var(--text-muted);"></i>
                    <p class="mt-3 text-secondary">No available slots for this doctor.</p>
                    <a href="view_doctors.php" class="btn btn-primary mt-2">Browse Other Doctors</a>
                </div>
                <?php endif; ?>
            </form>
            
            <?php else: ?>
            <!-- Select Doctor First -->
            <p class="text-secondary mb-4">Please select a doctor to view available slots.</p>
            <a href="view_doctors.php" class="btn btn-primary">
                <i class="fas fa-user-md me-2"></i>Browse Doctors
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
