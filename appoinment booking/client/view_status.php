<?php
session_start();
if (!isset($_SESSION['client_id'])) {
    header("Location: login.php");
    exit;
}
include '../db.php';

$client_id = $_SESSION['client_id'];

// Handle cancellation
if (isset($_POST['cancel_id'])) {
    $cancel_id = intval($_POST['cancel_id']);
    $conn->query("UPDATE appointments SET status = 'cancelled' WHERE id = $cancel_id AND client_id = $client_id");
}

// Get filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Build query
$query = "
    SELECT a.*, d.name as doctor_name, d.specialization, s.date, s.start_time, s.end_time
    FROM appointments a
    LEFT JOIN doctors d ON a.doctor_id = d.id
    LEFT JOIN schedules s ON a.schedule_id = s.id
    WHERE a.client_id = $client_id
";

if ($filter !== 'all') {
    $filter_clean = $conn->real_escape_string($filter);
    $query .= " AND a.status = '$filter_clean'";
}

$query .= " ORDER BY a.booked_at DESC";
$appointments = $conn->query($query);

// Get client info
$client = $conn->query("SELECT name FROM clients WHERE id = $client_id")->fetch_assoc();
$initials = strtoupper(substr($client['name'], 0, 1));

// Count by status
$counts = [
    'all' => $conn->query("SELECT COUNT(*) as c FROM appointments WHERE client_id = $client_id")->fetch_assoc()['c'],
    'pending' => $conn->query("SELECT COUNT(*) as c FROM appointments WHERE client_id = $client_id AND status = 'pending'")->fetch_assoc()['c'],
    'approved' => $conn->query("SELECT COUNT(*) as c FROM appointments WHERE client_id = $client_id AND status = 'approved'")->fetch_assoc()['c'],
    'completed' => $conn->query("SELECT COUNT(*) as c FROM appointments WHERE client_id = $client_id AND status = 'completed'")->fetch_assoc()['c'],
    'cancelled' => $conn->query("SELECT COUNT(*) as c FROM appointments WHERE client_id = $client_id AND status = 'cancelled'")->fetch_assoc()['c']
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments - MedAppoint</title>
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
            max-width: 1400px;
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
        
        .header-nav {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .nav-link-custom {
            color: var(--text-secondary);
            padding: 0.5rem 1rem;
            border-radius: var(--radius-md);
            transition: var(--transition-normal);
            text-decoration: none;
        }
        
        .nav-link-custom:hover,
        .nav-link-custom.active {
            color: var(--text-primary);
            background: var(--glass-bg);
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding-left: 1rem;
            border-left: 1px solid var(--glass-border);
        }
        
        .user-avatar {
            width: 42px;
            height: 42px;
            background: var(--primary-gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
        
        .page-main {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .page-title h1 span {
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        /* Filter Tabs */
        .filter-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        
        .filter-tab {
            padding: 0.75rem 1.25rem;
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-md);
            color: var(--text-secondary);
            text-decoration: none;
            transition: var(--transition-normal);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .filter-tab:hover {
            border-color: var(--primary-color);
            color: var(--text-primary);
        }
        
        .filter-tab.active {
            background: var(--primary-gradient);
            border-color: transparent;
            color: white;
        }
        
        .filter-tab .count {
            background: rgba(255,255,255,0.2);
            padding: 0.15rem 0.5rem;
            border-radius: var(--radius-xl);
            font-size: 0.8rem;
        }
        
        .filter-tab.active .count {
            background: rgba(255,255,255,0.3);
        }
        
        /* Appointment Cards */
        .appointments-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .appointment-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: var(--transition-normal);
        }
        
        .appointment-card:hover {
            border-color: rgba(102, 126, 234, 0.4);
        }
        
        .appointment-info {
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }
        
        .doctor-avatar {
            width: 60px;
            height: 60px;
            background: var(--primary-gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }
        
        .appointment-details h4 {
            margin: 0 0 0.25rem;
            font-size: 1.1rem;
        }
        
        .appointment-details .specialization {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        .appointment-meta {
            display: flex;
            gap: 1.5rem;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }
        
        .appointment-meta span {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .appointment-meta i {
            color: var(--primary-color);
        }
        
        .appointment-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        /* Status Badges */
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: var(--radius-xl);
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .status-pending {
            background: rgba(245, 158, 11, 0.2);
            color: #fbbf24;
        }
        
        .status-approved {
            background: rgba(16, 185, 129, 0.2);
            color: #34d399;
        }
        
        .status-completed {
            background: rgba(59, 130, 246, 0.2);
            color: #60a5fa;
        }
        
        .status-rejected {
            background: rgba(239, 68, 68, 0.2);
            color: #f87171;
        }
        
        .status-cancelled {
            background: rgba(107, 114, 128, 0.2);
            color: #9ca3af;
        }
        
        /* No Results */
        .no-appointments {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--glass-bg);
            border-radius: var(--radius-lg);
        }
        
        .no-appointments i {
            font-size: 4rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
        }
        
        /* Cancel Modal */
        .modal-content {
            background: var(--bg-dark);
            border: 1px solid var(--glass-border);
        }
        
        .modal-header {
            border-bottom: 1px solid var(--glass-border);
        }
        
        .modal-footer {
            border-top: 1px solid var(--glass-border);
        }
        
        @media (max-width: 768px) {
            .appointment-card {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }
            
            .appointment-info {
                flex-direction: column;
            }
            
            .appointment-meta {
                justify-content: center;
            }
            
            .header-nav {
                display: none;
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
            
            <nav class="header-nav">
                <a href="dashboard.php" class="nav-link-custom">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="view_doctors.php" class="nav-link-custom">
                    <i class="fas fa-user-md"></i> Doctors
                </a>
                <a href="view_status.php" class="nav-link-custom active">
                    <i class="fas fa-clipboard-list"></i> My Appointments
                </a>
            </nav>
            
            <div class="user-menu">
                <div class="user-avatar"><?php echo $initials; ?></div>
                <a href="../logout.php" class="btn btn-danger btn-sm">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="page-main">
        <div class="page-title mb-4">
            <h1>My <span>Appointments</span></h1>
            <p class="text-secondary">Track and manage all your healthcare appointments</p>
        </div>

        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <a href="?filter=all" class="filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">
                <i class="fas fa-list"></i> All
                <span class="count"><?php echo $counts['all']; ?></span>
            </a>
            <a href="?filter=pending" class="filter-tab <?php echo $filter === 'pending' ? 'active' : ''; ?>">
                <i class="fas fa-clock"></i> Pending
                <span class="count"><?php echo $counts['pending']; ?></span>
            </a>
            <a href="?filter=approved" class="filter-tab <?php echo $filter === 'approved' ? 'active' : ''; ?>">
                <i class="fas fa-check"></i> Approved
                <span class="count"><?php echo $counts['approved']; ?></span>
            </a>
            <a href="?filter=completed" class="filter-tab <?php echo $filter === 'completed' ? 'active' : ''; ?>">
                <i class="fas fa-check-double"></i> Completed
                <span class="count"><?php echo $counts['completed']; ?></span>
            </a>
            <a href="?filter=cancelled" class="filter-tab <?php echo $filter === 'cancelled' ? 'active' : ''; ?>">
                <i class="fas fa-times"></i> Cancelled
                <span class="count"><?php echo $counts['cancelled']; ?></span>
            </a>
        </div>

        <!-- Appointments List -->
        <?php if ($appointments && $appointments->num_rows > 0): ?>
            <div class="appointments-list">
                <?php while ($apt = $appointments->fetch_assoc()): ?>
                    <div class="appointment-card">
                        <div class="appointment-info">
                            <div class="doctor-avatar">
                                <i class="fas fa-user-md"></i>
                            </div>
                            <div class="appointment-details">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="badge bg-primary">#APT-<?php echo str_pad($apt['id'], 6, '0', STR_PAD_LEFT); ?></span>
                                    <h4 class="mb-0">Dr. <?php echo htmlspecialchars($apt['doctor_name']); ?></h4>
                                </div>
                                <div class="specialization"><?php echo htmlspecialchars($apt['specialization']); ?></div>
                                <div class="appointment-meta">
                                    <span>
                                        <i class="fas fa-calendar"></i>
                                        <?php echo $apt['date'] ? date('M d, Y', strtotime($apt['date'])) : 'N/A'; ?>
                                    </span>
                                    <span>
                                        <i class="fas fa-clock"></i>
                                        <?php 
                                        if ($apt['start_time'] && $apt['end_time']) {
                                            echo date('h:i A', strtotime($apt['start_time'])) . ' - ' . date('h:i A', strtotime($apt['end_time']));
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="appointment-actions">
                            <span class="status-badge status-<?php echo $apt['status']; ?>">
                                <?php echo ucfirst($apt['status']); ?>
                            </span>
                            
                            <a href="download_appointment.php?id=<?php echo $apt['id']; ?>" class="btn btn-outline-info btn-sm" title="Download Receipt">
                                <i class="fas fa-download me-1"></i>Receipt
                            </a>
                            
                            <?php if ($apt['status'] === 'pending' || $apt['status'] === 'approved'): ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to cancel this appointment?');">
                                    <input type="hidden" name="cancel_id" value="<?php echo $apt['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="fas fa-times me-1"></i>Cancel
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <?php if ($apt['status'] === 'completed'): ?>
                                <a href="doctor_profile.php?id=<?php echo $apt['doctor_id']; ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-star me-1"></i>Review
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-appointments">
                <i class="fas fa-calendar-times"></i>
                <h3>No Appointments Found</h3>
                <p class="text-secondary">You don't have any <?php echo $filter !== 'all' ? $filter : ''; ?> appointments yet.</p>
                <a href="view_doctors.php" class="btn btn-primary mt-3">
                    <i class="fas fa-calendar-plus me-2"></i>Book an Appointment
                </a>
            </div>
        <?php endif; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
