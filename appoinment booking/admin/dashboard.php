<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
include '../db.php';

// Get statistics
$total_doctors = $conn->query("SELECT COUNT(*) as count FROM doctors")->fetch_assoc()['count'];
$total_clients = $conn->query("SELECT COUNT(*) as count FROM clients")->fetch_assoc()['count'];
$total_appointments = $conn->query("SELECT COUNT(*) as count FROM appointments")->fetch_assoc()['count'];
$pending_appointments = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE status = 'pending'")->fetch_assoc()['count'];
$approved_appointments = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE status = 'approved'")->fetch_assoc()['count'];
$completed_today = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE status = 'completed' AND DATE(booked_at) = CURDATE()")->fetch_assoc()['count'];
$today_appointments = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE DATE(booked_at) = CURDATE()")->fetch_assoc()['count'];

// Get top doctors
$top_doctors = $conn->query("
    SELECT d.*, COUNT(a.id) as total_appointments 
    FROM doctors d 
    LEFT JOIN appointments a ON d.id = a.doctor_id 
    GROUP BY d.id 
    ORDER BY total_appointments DESC 
    LIMIT 4
");

// Get recent appointments
$recent_appointments = $conn->query("
    SELECT a.*, c.name as client_name, d.name as doctor_name 
    FROM appointments a 
    LEFT JOIN clients c ON a.client_id = c.id 
    LEFT JOIN doctors d ON a.doctor_id = d.id 
    ORDER BY a.booked_at DESC LIMIT 5
");

// Get admin username
$admin_name = isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : 'Admin';

// Get weekly appointments for chart
$weekly_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $day_name = date('D', strtotime("-$i days"));
    $count = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE DATE(booked_at) = '$date'")->fetch_assoc()['count'];
    $weekly_data[] = ['day' => $day_name, 'count' => (int)$count];
}

// Get activity timeline
$activities = $conn->query("
    SELECT 
        a.id,
        a.status,
        a.booked_at,
        c.name as client_name,
        d.name as doctor_name,
        CASE 
            WHEN a.status = 'pending' THEN 'New appointment booked'
            WHEN a.status = 'approved' THEN 'Appointment approved'
            WHEN a.status = 'completed' THEN 'Appointment completed'
            WHEN a.status = 'rejected' THEN 'Appointment rejected'
            ELSE 'Status updated'
        END as activity_type
    FROM appointments a
    LEFT JOIN clients c ON a.client_id = c.id
    LEFT JOIN doctors d ON a.doctor_id = d.id
    ORDER BY a.booked_at DESC
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - MedAppoint</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .admin-sidebar {
            width: 280px;
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border-right: 1px solid var(--glass-border);
            padding: 1.5rem;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
        }
        
        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            margin-bottom: 2rem;
        }
        
        .sidebar-brand i {
            font-size: 2rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .sidebar-brand span {
            font-size: 1.25rem;
            font-weight: 700;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-menu li {
            margin-bottom: 0.5rem;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.875rem 1rem;
            color: var(--text-secondary);
            border-radius: var(--radius-md);
            transition: var(--transition-normal);
            text-decoration: none;
        }
        
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: var(--primary-gradient);
            color: white;
        }
        
        .sidebar-menu a i {
            width: 20px;
            text-align: center;
        }
        
        .sidebar-section {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            padding: 1rem 1rem 0.5rem;
            margin-top: 1rem;
        }
        
        /* Main Content */
        .admin-main {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
        }
        
        /* Top Header */
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--glass-border);
        }
        
        .admin-header h1 {
            font-size: 1.75rem;
            margin: 0;
        }
        
        .admin-header h1 span {
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .admin-user {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .admin-avatar {
            width: 45px;
            height: 45px;
            background: var(--primary-gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
        
        .admin-user-info {
            text-align: right;
        }
        
        .admin-user-info .name {
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .admin-user-info .role {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
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
            gap: 1rem;
            transition: var(--transition-normal);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            border-color: rgba(102, 126, 234, 0.4);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }
        
        .stat-icon.doctors { background: linear-gradient(135deg, #667eea, #764ba2); }
        .stat-icon.clients { background: linear-gradient(135deg, #f093fb, #f5576c); }
        .stat-icon.appointments { background: linear-gradient(135deg, #4facfe, #00f2fe); }
        .stat-icon.pending { background: linear-gradient(135deg, #f59e0b, #d97706); }
        
        .stat-info h3 {
            font-size: 1.75rem;
            margin: 0;
            color: var(--text-primary);
        }
        
        .stat-info p {
            margin: 0;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        /* Content Cards */
        .content-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .content-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--glass-border);
        }
        
        .content-card-header h4 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .content-card-header h4 i {
            color: var(--primary-color);
        }
        
        /* Today Summary */
        .today-summary {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .summary-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            background: rgba(255,255,255,0.03);
            border-radius: var(--radius-md);
            border-left: 3px solid;
        }
        
        .summary-item.appointments { border-color: #4facfe; }
        .summary-item.pending { border-color: #fbbf24; }
        .summary-item.completed { border-color: #34d399; }
        
        .summary-item .label {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .summary-item .value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
        }
        
        /* Top Doctors */
        .doctor-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        
        .doctor-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem;
            background: rgba(255,255,255,0.03);
            border-radius: var(--radius-md);
            transition: var(--transition-normal);
        }
        
        .doctor-item:hover {
            background: rgba(102, 126, 234, 0.1);
        }
        
        .doctor-avatar {
            width: 45px;
            height: 45px;
            background: var(--primary-gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
        
        .doctor-info h6 {
            margin: 0;
            color: var(--text-primary);
        }
        
        .doctor-info small {
            color: var(--text-muted);
        }
        
        .doctor-stats {
            margin-left: auto;
            text-align: right;
        }
        
        .doctor-stats .count {
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .doctor-stats small {
            color: var(--text-muted);
            display: block;
        }
        
        /* Analytics Chart */
        .chart-container {
            position: relative;
            height: 250px;
            width: 100%;
        }
        
        /* Activity Timeline */
        .activity-timeline {
            position: relative;
        }
        
        .timeline-item {
            display: flex;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            position: relative;
        }
        
        .timeline-item:last-child {
            border-bottom: none;
        }
        
        .timeline-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .timeline-icon.pending { background: rgba(245, 158, 11, 0.2); color: #fbbf24; }
        .timeline-icon.approved { background: rgba(16, 185, 129, 0.2); color: #34d399; }
        .timeline-icon.completed { background: rgba(59, 130, 246, 0.2); color: #60a5fa; }
        .timeline-icon.rejected { background: rgba(239, 68, 68, 0.2); color: #f87171; }
        
        .timeline-content {
            flex: 1;
        }
        
        .timeline-content h6 {
            margin: 0 0 0.25rem;
            color: var(--text-primary);
            font-size: 0.9rem;
        }
        
        .timeline-content p {
            margin: 0;
            color: var(--text-muted);
            font-size: 0.85rem;
        }
        
        .timeline-time {
            font-size: 0.75rem;
            color: var(--text-muted);
        }
        
        /* Status Badges */
        .status-badge {
            padding: 0.35rem 0.75rem;
            border-radius: var(--radius-xl);
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .status-pending { background: rgba(245, 158, 11, 0.2); color: #fbbf24; }
        .status-approved { background: rgba(16, 185, 129, 0.2); color: #34d399; }
        .status-rejected { background: rgba(239, 68, 68, 0.2); color: #f87171; }
        .status-cancelled { background: rgba(107, 114, 128, 0.2); color: #9ca3af; }
        
        /* Recent Table */
        .recent-table {
            width: 100%;
        }
        
        .recent-table th {
            color: var(--text-secondary);
            font-weight: 500;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 1rem;
            border-bottom: 1px solid var(--glass-border);
        }
        
        .recent-table td {
            padding: 1rem;
            color: var(--text-primary);
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        
        .recent-table tr:hover td {
            background: rgba(102, 126, 234, 0.05);
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .admin-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .admin-sidebar.show {
                transform: translateX(0);
            }
            
            .admin-main {
                margin-left: 0;
            }
            
            .mobile-toggle {
                display: block !important;
            }
        }
        
        .mobile-toggle {
            display: none;
            background: var(--primary-gradient);
            border: none;
            color: white;
            width: 45px;
            height: 45px;
            border-radius: var(--radius-md);
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <aside class="admin-sidebar" id="sidebar">
            <div class="sidebar-brand">
                <i class="fas fa-hospital-alt"></i>
                <span>MedAppoint</span>
            </div>
            
            <ul class="sidebar-menu">
                <li>
                    <a href="dashboard.php" class="active">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                </li>
                
                <li class="sidebar-section">Management</li>
                
                <li>
                    <a href="manage_doctors.php">
                        <i class="fas fa-user-md"></i>
                        Doctors
                    </a>
                </li>
                <li>
                    <a href="manage_schedules.php">
                        <i class="fas fa-calendar-alt"></i>
                        Schedules
                    </a>
                </li>
                <li>
                    <a href="view_appointments.php">
                        <i class="fas fa-clipboard-list"></i>
                        Appointments
                    </a>
                </li>
                <li>
                    <a href="manage_clients.php">
                        <i class="fas fa-users"></i>
                        Clients
                    </a>
                </li>
                
                <li class="sidebar-section">Analytics</li>
                
                <li>
                    <a href="reports.php">
                        <i class="fas fa-chart-bar"></i>
                        Reports
                    </a>
                </li>
                
                <li class="sidebar-section">Account</li>
                
                <li>
                    <a href="../logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <main class="admin-main">
            <div class="admin-header">
                <div>
                    <button class="mobile-toggle" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1>Welcome, <span><?php echo htmlspecialchars($admin_name); ?></span>!</h1>
                    <p class="text-secondary mb-0">Here's what's happening today</p>
                </div>
                <div class="admin-user">
                    <div class="admin-user-info">
                        <div class="name"><?php echo htmlspecialchars($admin_name); ?></div>
                        <div class="role">Administrator</div>
                    </div>
                    <div class="admin-avatar">
                        <?php echo strtoupper(substr($admin_name, 0, 1)); ?>
                    </div>
                </div>
            </div>
            
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon doctors">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_doctors; ?></h3>
                        <p>Total Doctors</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon clients">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_clients; ?></h3>
                        <p>Registered Clients</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon appointments">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_appointments; ?></h3>
                        <p>Total Appointments</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon pending">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $pending_appointments; ?></h3>
                        <p>Pending Approval</p>
                    </div>
                </div>
            </div>
            
            <!-- Analytics Row -->
            <div class="row mb-4">
                <!-- Weekly Appointments Chart -->
                <div class="col-lg-8 mb-4">
                    <div class="content-card">
                        <div class="content-card-header">
                            <h4><i class="fas fa-chart-bar"></i> Weekly Appointments</h4>
                            <span class="badge bg-primary">Last 7 Days</span>
                        </div>
                        <div class="chart-container">
                            <canvas id="appointmentsChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Activity Timeline -->
                <div class="col-lg-4 mb-4">
                    <div class="content-card">
                        <div class="content-card-header">
                            <h4><i class="fas fa-history"></i> Activity Timeline</h4>
                        </div>
                        <div class="activity-timeline">
                            <?php if ($activities && $activities->num_rows > 0): ?>
                                <?php while ($act = $activities->fetch_assoc()): ?>
                                <div class="timeline-item">
                                    <div class="timeline-icon <?php echo $act['status']; ?>">
                                        <?php 
                                        $icon = 'fas fa-clock';
                                        if ($act['status'] == 'pending') $icon = 'fas fa-clock';
                                        elseif ($act['status'] == 'approved') $icon = 'fas fa-check';
                                        elseif ($act['status'] == 'completed') $icon = 'fas fa-check-double';
                                        elseif ($act['status'] == 'rejected') $icon = 'fas fa-times';
                                        ?>
                                        <i class="<?php echo $icon; ?>"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <h6><?php echo htmlspecialchars($act['activity_type']); ?></h6>
                                        <p><?php echo htmlspecialchars($act['client_name'] ?? 'Unknown'); ?> → Dr. <?php echo htmlspecialchars($act['doctor_name'] ?? 'Unknown'); ?></p>
                                    </div>
                                    <div class="timeline-time">
                                        <?php echo date('M d, h:i A', strtotime($act['booked_at'])); ?>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p class="text-center text-secondary py-4">No recent activity</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- Today's Summary -->
                <div class="col-lg-4 mb-4">
                    <div class="content-card">
                        <div class="content-card-header">
                            <h4><i class="fas fa-calendar-day"></i> Today's Summary</h4>
                        </div>
                        <div class="today-summary">
                            <div class="summary-item appointments">
                                <div>
                                    <div class="label">Today's Appointments</div>
                                </div>
                                <div class="value"><?php echo $today_appointments; ?></div>
                            </div>
                            <div class="summary-item pending">
                                <div>
                                    <div class="label">Pending Approval</div>
                                </div>
                                <div class="value"><?php echo $pending_appointments; ?></div>
                            </div>
                            <div class="summary-item completed">
                                <div>
                                    <div class="label">Completed Today</div>
                                </div>
                                <div class="value"><?php echo $completed_today; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Appointments -->
                <div class="col-lg-8 mb-4">
                    <div class="content-card">
                        <div class="content-card-header">
                            <h4><i class="fas fa-clock"></i> Recent Appointments</h4>
                            <a href="view_appointments.php" class="btn btn-primary btn-sm">View All</a>
                        </div>
                        <div class="table-responsive">
                            <table class="recent-table">
                                <thead>
                                    <tr>
                                        <th>Client</th>
                                        <th>Doctor</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($recent_appointments && $recent_appointments->num_rows > 0): ?>
                                        <?php while ($apt = $recent_appointments->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($apt['client_name'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($apt['doctor_name'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <span class="status-badge status-<?php echo $apt['status']; ?>">
                                                        <?php echo ucfirst($apt['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($apt['booked_at'])); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-secondary">No appointments yet</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Top Doctors Row - Full Width Vertical -->
            <div class="row">
                <div class="col-lg-12 mb-4">
                    <div class="content-card">
                        <div class="content-card-header">
                            <h4><i class="fas fa-user-md"></i> Top Doctors</h4>
                            <a href="manage_doctors.php" class="btn btn-primary btn-sm">View All</a>
                        </div>
                        <div class="doctor-list" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem;">
                            <?php 
                            // Reset the pointer for top_doctors
                            $top_doctors = $conn->query("
                                SELECT d.*, COUNT(a.id) as total_appointments 
                                FROM doctors d 
                                LEFT JOIN appointments a ON d.id = a.doctor_id 
                                GROUP BY d.id 
                                ORDER BY total_appointments DESC 
                                LIMIT 6
                            ");
                            if ($top_doctors && $top_doctors->num_rows > 0): ?>
                                <?php while ($doc = $top_doctors->fetch_assoc()): ?>
                                <div class="doctor-item">
                                    <div class="doctor-avatar">
                                        <?php echo strtoupper(substr($doc['name'], 0, 1)); ?>
                                    </div>
                                    <div class="doctor-info">
                                        <h6><?php echo htmlspecialchars($doc['name']); ?></h6>
                                        <small><?php echo htmlspecialchars($doc['specialization']); ?></small>
                                    </div>
                                    <div class="doctor-stats">
                                        <span class="count"><?php echo $doc['total_appointments']; ?></span>
                                        <small>appointments</small>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p class="text-center text-secondary">No doctors yet</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('show');
        }
        
        // Weekly Appointments Chart
        const ctx = document.getElementById('appointmentsChart').getContext('2d');
        const weeklyData = <?php echo json_encode($weekly_data); ?>;
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: weeklyData.map(d => d.day),
                datasets: [{
                    label: 'Appointments',
                    data: weeklyData.map(d => d.count),
                    backgroundColor: 'rgba(102, 126, 234, 0.7)',
                    borderColor: 'rgba(102, 126, 234, 1)',
                    borderWidth: 2,
                    borderRadius: 8,
                    barThickness: 30
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#9ca3af',
                            stepSize: 1
                        },
                        grid: {
                            color: 'rgba(255,255,255,0.05)'
                        }
                    },
                    x: {
                        ticks: {
                            color: '#9ca3af'
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>