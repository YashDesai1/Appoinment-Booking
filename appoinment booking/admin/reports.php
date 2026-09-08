<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
include '../db.php';

// Get date range
$start_date = isset($_GET['start']) ? $_GET['start'] : date('Y-m-01');
$end_date = isset($_GET['end']) ? $_GET['end'] : date('Y-m-t');

// Get statistics
$total_doctors = $conn->query("SELECT COUNT(*) as count FROM doctors")->fetch_assoc()['count'];
$total_clients = $conn->query("SELECT COUNT(*) as count FROM clients")->fetch_assoc()['count'];
$total_appointments = $conn->query("SELECT COUNT(*) as count FROM appointments")->fetch_assoc()['count'];

// Appointment stats
$appointment_stats = $conn->query("
    SELECT 
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
    FROM appointments
")->fetch_assoc();

// Top doctors by appointments
$top_doctors = $conn->query("
    SELECT d.name, d.specialization, COUNT(a.id) as appointment_count,
           AVG(r.rating) as avg_rating
    FROM doctors d
    LEFT JOIN appointments a ON d.id = a.doctor_id
    LEFT JOIN reviews r ON d.id = r.doctor_id
    GROUP BY d.id
    ORDER BY appointment_count DESC
    LIMIT 5
");

// Recent registrations
$recent_clients = $conn->query("
    SELECT name, email, created_at FROM clients 
    ORDER BY created_at DESC LIMIT 5
");

// Monthly appointments chart data
$monthly_data = $conn->query("
    SELECT 
        DATE_FORMAT(booked_at, '%Y-%m') as month,
        COUNT(*) as count
    FROM appointments 
    WHERE booked_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(booked_at, '%Y-%m')
    ORDER BY month
");

$chart_labels = [];
$chart_values = [];
while ($row = $monthly_data->fetch_assoc()) {
    $chart_labels[] = date('M Y', strtotime($row['month'] . '-01'));
    $chart_values[] = $row['count'];
}

// Specialization distribution
$specialization_data = $conn->query("
    SELECT specialization, COUNT(*) as count 
    FROM doctors 
    GROUP BY specialization
    ORDER BY count DESC
");

$spec_labels = [];
$spec_values = [];
while ($row = $specialization_data->fetch_assoc()) {
    $spec_labels[] = $row['specialization'];
    $spec_values[] = $row['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        .admin-sidebar {
            width: 280px;
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border-right: 1px solid var(--glass-border);
            padding: 1.5rem;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
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
            margin-bottom: 0.25rem;
        }
        
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: var(--primary-gradient);
            color: white;
        }
        
        .sidebar-section {
            font-size: 0.75rem;
            text-transform: uppercase;
            color: var(--text-muted);
            padding: 1rem 1rem 0.5rem;
            margin-top: 1rem;
        }
        
        .admin-main {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
        }
        
        .page-header {
            margin-bottom: 2rem;
        }
        
        .page-header h1 span {
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        /* Stats Overview */
        .stats-overview {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
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
        .stat-icon.clients { background: linear-gradient(135deg, #10b981, #059669); }
        .stat-icon.appointments { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .stat-icon.reviews { background: linear-gradient(135deg, #ef4444, #dc2626); }
        
        .stat-content h3 {
            font-size: 1.75rem;
            margin: 0;
        }
        
        .stat-content p {
            margin: 0;
            color: var(--text-secondary);
        }
        
        /* Charts Grid */
        .charts-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .chart-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
        }
        
        .chart-card h4 {
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .chart-card h4 i {
            color: var(--primary-color);
        }
        
        /* Status Cards */
        .status-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .status-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-md);
            padding: 1.25rem;
            text-align: center;
        }
        
        .status-card h4 {
            font-size: 1.5rem;
            margin: 0 0 0.25rem;
        }
        
        .status-card p {
            margin: 0;
            font-size: 0.85rem;
        }
        
        .status-card.pending h4 { color: #fbbf24; }
        .status-card.approved h4 { color: #34d399; }
        .status-card.completed h4 { color: #60a5fa; }
        .status-card.rejected h4 { color: #f87171; }
        .status-card.cancelled h4 { color: #9ca3af; }
        
        /* Tables */
        .data-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .data-card h4 {
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .data-card h4 i {
            color: var(--primary-color);
        }
        
        .simple-table {
            width: 100%;
        }
        
        .simple-table th {
            text-align: left;
            padding: 0.75rem;
            color: var(--text-secondary);
            font-weight: 500;
            border-bottom: 1px solid var(--glass-border);
        }
        
        .simple-table td {
            padding: 0.75rem;
            border-bottom: 1px solid var(--glass-border);
        }
        
        .simple-table tr:last-child td {
            border-bottom: none;
        }
        
        @media (max-width: 1200px) {
            .stats-overview {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .charts-grid {
                grid-template-columns: 1fr;
            }
            
            .status-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        @media (max-width: 992px) {
            .admin-sidebar {
                display: none;
            }
            .admin-main {
                margin-left: 0;
            }
        }
        
        @media (max-width: 768px) {
            .stats-overview {
                grid-template-columns: 1fr;
            }
            
            .status-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="sidebar-brand">
                <i class="fas fa-hospital-alt"></i>
                <span>MedAppoint</span>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li class="sidebar-section">Management</li>
                <li><a href="manage_doctors.php"><i class="fas fa-user-md"></i> Doctors</a></li>
                <li><a href="manage_schedules.php"><i class="fas fa-calendar-alt"></i> Schedules</a></li>
                <li><a href="view_appointments.php"><i class="fas fa-clipboard-list"></i> Appointments</a></li>
                <li><a href="manage_clients.php"><i class="fas fa-users"></i> Clients</a></li>
                <li class="sidebar-section">Analytics</li>
                <li><a href="reports.php" class="active"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li class="sidebar-section">Account</li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <main class="admin-main">
            <div class="page-header">
                <h1>Reports & <span>Analytics</span></h1>
                <p class="text-secondary">Comprehensive overview of your healthcare system</p>
            </div>
            
            <!-- Stats Overview -->
            <div class="stats-overview">
                <div class="stat-card">
                    <div class="stat-icon doctors">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $total_doctors; ?></h3>
                        <p>Total Doctors</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon clients">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $total_clients; ?></h3>
                        <p>Registered Clients</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon appointments">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $total_appointments; ?></h3>
                        <p>Total Appointments</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon reviews">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-content">
                        <?php $total_reviews = $conn->query("SELECT COUNT(*) as c FROM reviews")->fetch_assoc()['c']; ?>
                        <h3><?php echo $total_reviews; ?></h3>
                        <p>Patient Reviews</p>
                    </div>
                </div>
            </div>
            
            <!-- Appointment Status -->
            <div class="status-grid">
                <div class="status-card pending">
                    <h4><?php echo $appointment_stats['pending'] ?? 0; ?></h4>
                    <p class="text-secondary">Pending</p>
                </div>
                <div class="status-card approved">
                    <h4><?php echo $appointment_stats['approved'] ?? 0; ?></h4>
                    <p class="text-secondary">Approved</p>
                </div>
                <div class="status-card completed">
                    <h4><?php echo $appointment_stats['completed'] ?? 0; ?></h4>
                    <p class="text-secondary">Completed</p>
                </div>
                <div class="status-card rejected">
                    <h4><?php echo $appointment_stats['rejected'] ?? 0; ?></h4>
                    <p class="text-secondary">Rejected</p>
                </div>
                <div class="status-card cancelled">
                    <h4><?php echo $appointment_stats['cancelled'] ?? 0; ?></h4>
                    <p class="text-secondary">Cancelled</p>
                </div>
            </div>
            
            <!-- Charts -->
            <div class="charts-grid">
                <div class="chart-card">
                    <h4><i class="fas fa-chart-line"></i> Appointment Trends</h4>
                    <canvas id="appointmentChart" height="100"></canvas>
                </div>
                
                <div class="chart-card">
                    <h4><i class="fas fa-chart-pie"></i> Specializations</h4>
                    <canvas id="specializationChart" height="200"></canvas>
                </div>
            </div>
            
            <!-- Tables -->
            <div class="row">
                <div class="col-md-6">
                    <div class="data-card">
                        <h4><i class="fas fa-trophy"></i> Top Doctors</h4>
                        <table class="simple-table">
                            <thead>
                                <tr>
                                    <th>Doctor</th>
                                    <th>Appointments</th>
                                    <th>Rating</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($doc = $top_doctors->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong>Dr. <?php echo htmlspecialchars($doc['name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($doc['specialization']); ?></small>
                                    </td>
                                    <td><?php echo $doc['appointment_count']; ?></td>
                                    <td>
                                        <?php if ($doc['avg_rating']): ?>
                                            <span style="color: #fbbf24;">
                                                <i class="fas fa-star"></i> <?php echo number_format($doc['avg_rating'], 1); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="data-card">
                        <h4><i class="fas fa-user-plus"></i> Recent Registrations</h4>
                        <table class="simple-table">
                            <thead>
                                <tr>
                                    <th>Client</th>
                                    <th>Joined</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($client = $recent_clients->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($client['name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($client['email']); ?></small>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($client['created_at'])); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Appointment Trends Chart
        new Chart(document.getElementById('appointmentChart'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chart_labels); ?>,
                datasets: [{
                    label: 'Appointments',
                    data: <?php echo json_encode($chart_values); ?>,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(255,255,255,0.1)'
                        },
                        ticks: {
                            color: 'rgba(255,255,255,0.7)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: 'rgba(255,255,255,0.7)'
                        }
                    }
                }
            }
        });
        
        // Specialization Chart
        new Chart(document.getElementById('specializationChart'), {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($spec_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($spec_values); ?>,
                    backgroundColor: [
                        '#667eea',
                        '#10b981',
                        '#f59e0b',
                        '#ef4444',
                        '#8b5cf6',
                        '#06b6d4'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: 'rgba(255,255,255,0.7)',
                            padding: 15
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>