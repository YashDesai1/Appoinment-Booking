<?php
session_start();
if (!isset($_SESSION['client_id'])) {
    header("Location: login.php");
    exit;
}
include '../db.php';

// Get client information
$client_id = $_SESSION['client_id'];
$client_query = $conn->prepare("SELECT name, email, phone FROM clients WHERE id = ?");
$client_query->bind_param("i", $client_id);
$client_query->execute();
$client_result = $client_query->get_result();
$client = $client_result->fetch_assoc();

// Get appointment statistics
$total_appointments = $conn->query("SELECT COUNT(*) as total FROM appointments WHERE client_id = $client_id AND status != 'cancelled'")->fetch_assoc()['total'];
$confirmed_appointments = $conn->query("SELECT COUNT(*) as total FROM appointments WHERE client_id = $client_id AND status = 'approved'")->fetch_assoc()['total'];
$pending_appointments = $conn->query("SELECT COUNT(*) as total FROM appointments WHERE client_id = $client_id AND status = 'pending'")->fetch_assoc()['total'];

// Get upcoming appointments
$upcoming = $conn->query("
    SELECT a.*, d.name as doctor_name, d.specialization, s.date, s.start_time, s.end_time
    FROM appointments a
    LEFT JOIN doctors d ON a.doctor_id = d.id
    LEFT JOIN schedules s ON a.schedule_id = s.id
    WHERE a.client_id = $client_id AND a.status = 'approved' AND s.date >= CURDATE()
    ORDER BY s.date ASC LIMIT 3
");

$initials = strtoupper(substr($client['name'], 0, 1));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - MedAppoint</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .client-header {
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
            display: flex;
            align-items: center;
            gap: 0.5rem;
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
        
        .user-info .name {
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.9rem;
        }
        
        .user-info .email {
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        
        .btn-logout {
            background: rgba(239, 68, 68, 0.1);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.2);
            padding: 0.5rem 1rem;
            border-radius: var(--radius-md);
            text-decoration: none;
            transition: var(--transition-normal);
        }
        
        .btn-logout:hover {
            background: rgba(239, 68, 68, 0.2);
            color: #f87171;
        }
        
        /* Main Content */
        .client-main {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        /* Welcome Section */
        .welcome-section {
            margin-bottom: 2rem;
        }
        
        .welcome-section h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .welcome-section h1 span {
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        /* Stats Cards */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1.25rem;
            transition: var(--transition-normal);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            border-color: rgba(102, 126, 234, 0.4);
        }
        
        .stat-icon {
            width: 65px;
            height: 65px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }
        
        .stat-icon.total { background: linear-gradient(135deg, #667eea, #764ba2); }
        .stat-icon.confirmed { background: linear-gradient(135deg, #10b981, #059669); }
        .stat-icon.pending { background: linear-gradient(135deg, #f59e0b, #d97706); }
        
        .stat-content h3 {
            font-size: 2rem;
            margin: 0;
            color: var(--text-primary);
        }
        
        .stat-content p {
            margin: 0;
            color: var(--text-secondary);
        }
        
        /* Action Cards */
        .action-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .action-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            padding: 2rem;
            text-align: center;
            transition: var(--transition-normal);
            position: relative;
            overflow: hidden;
        }
        
        .action-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
            transform: scaleX(0);
            transition: var(--transition-normal);
        }
        
        .action-card:hover {
            transform: translateY(-10px);
            border-color: rgba(102, 126, 234, 0.4);
        }
        
        .action-card:hover::before {
            transform: scaleX(1);
        }
        
        .action-icon {
            width: 80px;
            height: 80px;
            background: var(--primary-gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }
        
        .action-icon i {
            font-size: 2rem;
            color: white;
        }
        
        .action-card h3 {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
        }
        
        .action-card p {
            margin-bottom: 1.5rem;
        }
        
        .action-card .btn {
            min-width: 160px;
        }
        
        /* Upcoming Section */
        .upcoming-section {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--glass-border);
        }
        
        .section-header h4 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .section-header h4 i {
            color: var(--primary-color);
        }
        
        .appointment-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-md);
            padding: 1.25rem;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: var(--transition-normal);
        }
        
        .appointment-card:hover {
            border-color: rgba(102, 126, 234, 0.4);
            background: rgba(102, 126, 234, 0.05);
        }
        
        .appointment-info h5 {
            margin: 0 0 0.25rem;
            font-size: 1rem;
        }
        
        .appointment-info p {
            margin: 0;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }
        
        .appointment-date {
            text-align: right;
        }
        
        .appointment-date .date {
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .appointment-date .time {
            font-size: 0.85rem;
            color: var(--primary-color);
        }
        
        .no-appointments {
            text-align: center;
            padding: 2rem;
            color: var(--text-secondary);
        }
        
        .no-appointments i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--text-muted);
        }
        
        @media (max-width: 768px) {
            .header-nav {
                display: none;
            }
            
            .user-info {
                display: none;
            }
            
            .client-main {
                padding: 1rem;
            }
            
            .welcome-section h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="client-header">
        <div class="header-content">
            <a href="dashboard.php" class="logo">
                <i class="fas fa-hospital-alt"></i>
                <span>MedAppoint</span>
            </a>
            
            <nav class="header-nav">
                <a href="dashboard.php" class="nav-link-custom active">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="view_doctors.php" class="nav-link-custom">
                    <i class="fas fa-user-md"></i> Doctors
                </a>
                <a href="view_status.php" class="nav-link-custom">
                    <i class="fas fa-clipboard-list"></i> My Appointments
                </a>
            </nav>
            
            <div class="user-menu">
                <div class="user-info">
                    <div class="name"><?php echo htmlspecialchars($client['name']); ?></div>
                    <div class="email"><?php echo htmlspecialchars($client['email']); ?></div>
                </div>
                <div class="user-avatar"><?php echo $initials; ?></div>
                <a href="../logout.php" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="client-main">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <h1>Welcome back, <span><?php echo htmlspecialchars($client['name']); ?></span>! </h1>
            <p class="text-secondary">Manage your healthcare appointments with ease.</p>
        </div>

        <!-- Stats Row -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $total_appointments; ?></h3>
                    <p>Total Appointments</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon confirmed">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $confirmed_appointments; ?></h3>
                    <p>Confirmed</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon pending">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $pending_appointments; ?></h3>
                    <p>Pending</p>
                </div>
            </div>
        </div>

        <!-- Action Cards -->
        <div class="action-cards">
            <div class="action-card">
                <div class="action-icon">
                    <i class="fas fa-user-md"></i>
                </div>
                <h3>Find Doctors</h3>
                <p>Browse our network of qualified healthcare professionals.</p>
                <a href="view_doctors.php" class="btn btn-primary">
                    <i class="fas fa-search me-2"></i>Browse Doctors
                </a>
            </div>
            
            <div class="action-card">
                <div class="action-icon">
                    <i class="fas fa-calendar-plus"></i>
                </div>
                <h3>Book Appointment</h3>
                <p>Schedule your next healthcare appointment.</p>
                <a href="view_doctors.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Book Now
                </a>
            </div>
            
            <div class="action-card">
                <div class="action-icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <h3>My Appointments</h3>
                <p>View and manage all your appointments.</p>
                <a href="view_status.php" class="btn btn-secondary">
                    <i class="fas fa-eye me-2"></i>View All
                </a>
            </div>
        </div>

        <!-- Upcoming Appointments -->
        <div class="upcoming-section">
            <div class="section-header">
                <h4><i class="fas fa-clock"></i> Upcoming Appointments</h4>
                <a href="view_status.php" class="btn btn-primary btn-sm">View All</a>
            </div>
            
            <?php if ($upcoming && $upcoming->num_rows > 0): ?>
                <?php while ($apt = $upcoming->fetch_assoc()): ?>
                    <div class="appointment-card">
                        <div class="appointment-info">
                            <h5>Dr. <?php echo htmlspecialchars($apt['doctor_name']); ?></h5>
                            <p><i class="fas fa-stethoscope me-2"></i><?php echo htmlspecialchars($apt['specialization']); ?></p>
                        </div>
                        <div class="appointment-date">
                            <div class="date"><?php echo date('M d, Y', strtotime($apt['date'])); ?></div>
                            <div class="time">
                                <?php echo date('h:i A', strtotime($apt['start_time'])); ?> - 
                                <?php echo date('h:i A', strtotime($apt['end_time'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-appointments">
                    <i class="fas fa-calendar-times"></i>
                    <p>No upcoming appointments</p>
                    <a href="view_doctors.php" class="btn btn-primary mt-2">Book an Appointment</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
