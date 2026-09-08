<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
include '../db.php';

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_status'])) {
        $id = intval($_POST['id']);
        $status = $_POST['status'];
        $conn->query("UPDATE appointments SET status = '$status' WHERE id = $id");
    }
}

$admin_name = isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : 'Admin';

// Get filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Get counts for filter tabs
$counts = [
    'all' => $conn->query("SELECT COUNT(*) as c FROM appointments")->fetch_assoc()['c'],
    'pending' => $conn->query("SELECT COUNT(*) as c FROM appointments WHERE status = 'pending'")->fetch_assoc()['c'],
    'approved' => $conn->query("SELECT COUNT(*) as c FROM appointments WHERE status = 'approved'")->fetch_assoc()['c'],
    'rejected' => $conn->query("SELECT COUNT(*) as c FROM appointments WHERE status = 'rejected'")->fetch_assoc()['c'],
    'completed' => $conn->query("SELECT COUNT(*) as c FROM appointments WHERE status = 'completed'")->fetch_assoc()['c']
];

// Pagination settings
$items_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

// Get total for current filter
$filter_clean = $conn->real_escape_string($filter);
$count_query = "SELECT COUNT(*) as total FROM appointments a WHERE 1=1";
if ($filter !== 'all') {
    $count_query .= " AND a.status = '$filter_clean'";
}
$total_records = $conn->query($count_query)->fetch_assoc()['total'];
$total_pages = ceil($total_records / $items_per_page);
if ($page > $total_pages && $total_pages > 0) $page = $total_pages;

$offset = ($page - 1) * $items_per_page;
$start_entry = $total_records > 0 ? $offset + 1 : 0;
$end_entry = min($offset + $items_per_page, $total_records);

// Build final query with LIMIT
$query = "
    SELECT a.*, c.name as client_name, c.email as client_email, c.phone as client_phone,
           d.name as doctor_name, d.specialization, s.date, s.start_time, s.end_time
    FROM appointments a
    LEFT JOIN clients c ON a.client_id = c.id
    LEFT JOIN doctors d ON a.doctor_id = d.id
    LEFT JOIN schedules s ON a.schedule_id = s.id
    WHERE 1=1
";
if ($filter !== 'all') {
    $query .= " AND a.status = '$filter_clean'";
}
$query .= " ORDER BY a.booked_at DESC LIMIT $offset, $items_per_page";
$appointments = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Appointments - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .page-header h1 span {
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
        
        /* Table Styles */
        .appointments-table {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            overflow: hidden;
        }
        
        .table {
            margin: 0;
        }
        
        .table thead th {
            background: rgba(102, 126, 234, 0.1);
            color: var(--text-secondary);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
            padding: 1rem;
            border: none;
        }
        
        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid var(--glass-border);
            color: var(--text-primary);
        }
        
        .table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .table tbody tr:hover td {
            background: rgba(102, 126, 234, 0.05);
        }
        
        .client-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .client-avatar {
            width: 40px;
            height: 40px;
            background: var(--primary-gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
        
        .client-details h6 {
            margin: 0;
            font-size: 0.95rem;
        }
        
        .client-details small {
            color: var(--text-muted);
        }
        
        /* Status Badges */
        .status-badge {
            padding: 0.4rem 0.75rem;
            border-radius: var(--radius-xl);
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-pending { background: rgba(245, 158, 11, 0.2); color: #fbbf24; }
        .status-approved { background: rgba(16, 185, 129, 0.2); color: #34d399; }
        .status-rejected { background: rgba(239, 68, 68, 0.2); color: #f87171; }
        .status-completed { background: rgba(59, 130, 246, 0.2); color: #60a5fa; }
        .status-cancelled { background: rgba(107, 114, 128, 0.2); color: #9ca3af; }
        
        /* Action Buttons */
        .action-btns {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-action {
            padding: 0.4rem 0.75rem;
            font-size: 0.8rem;
            border-radius: var(--radius-sm);
        }
        
        /* Pagination Styles */
        .pagination .page-link {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            color: var(--text-secondary);
        }
        
        .pagination .page-link:hover {
            background: rgba(102, 126, 234, 0.2);
            border-color: var(--primary-color);
            color: var(--text-primary);
        }
        
        .pagination .page-item.active .page-link {
            background: var(--primary-gradient);
            border-color: transparent;
            color: white;
        }
        
        .pagination .page-item.disabled .page-link {
            background: rgba(255,255,255,0.03);
            border-color: var(--glass-border);
            color: var(--text-muted);
        }
        
        @media (max-width: 992px) {
            .admin-sidebar {
                display: none;
            }
            .admin-main {
                margin-left: 0;
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
                <li><a href="view_appointments.php" class="active"><i class="fas fa-clipboard-list"></i> Appointments</a></li>
                <li><a href="manage_clients.php"><i class="fas fa-users"></i> Clients</a></li>
                <li class="sidebar-section">Analytics</li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li class="sidebar-section">Account</li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <main class="admin-main">
            <div class="page-header">
                <div>
                    <h1>View <span>Appointments</span></h1>
                    <p class="text-secondary mb-0">Manage and approve appointment requests</p>
                </div>
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
                <a href="?filter=rejected" class="filter-tab <?php echo $filter === 'rejected' ? 'active' : ''; ?>">
                    <i class="fas fa-times"></i> Rejected
                    <span class="count"><?php echo $counts['rejected']; ?></span>
                </a>
            </div>
            
            <!-- Appointments Table -->
            <div class="appointments-table">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Appt ID</th>
                                <th>Client</th>
                                <th>Doctor</th>
                                <th>Date & Time</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($appointments && $appointments->num_rows > 0): ?>
                                <?php while ($apt = $appointments->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-primary">#APT-<?php echo str_pad($apt['id'], 6, '0', STR_PAD_LEFT); ?></span>
                                        </td>
                                        <td>
                                            <div class="client-info">
                                                <div class="client-avatar">
                                                    <?php echo strtoupper(substr($apt['client_name'] ?? 'N', 0, 1)); ?>
                                                </div>
                                                <div class="client-details">
                                                    <h6><?php echo htmlspecialchars($apt['client_name'] ?? 'N/A'); ?></h6>
                                                    <small><?php echo htmlspecialchars($apt['client_email'] ?? ''); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <strong>Dr. <?php echo htmlspecialchars($apt['doctor_name'] ?? 'N/A'); ?></strong><br>
                                            <small class="text-secondary"><?php echo htmlspecialchars($apt['specialization'] ?? ''); ?></small>
                                        </td>
                                        <td>
                                            <?php if ($apt['date']): ?>
                                                <strong><?php echo date('M d, Y', strtotime($apt['date'])); ?></strong><br>
                                                <small class="text-secondary">
                                                    <?php echo date('h:i A', strtotime($apt['start_time'])); ?> - 
                                                    <?php echo date('h:i A', strtotime($apt['end_time'])); ?>
                                                </small>
                                            <?php else: ?>
                                                N/A
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $apt['status']; ?>">
                                                <?php echo ucfirst($apt['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-btns">
                                                <?php if ($apt['status'] === 'pending'): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="id" value="<?php echo $apt['id']; ?>">
                                                        <input type="hidden" name="status" value="approved">
                                                        <button type="submit" name="update_status" class="btn btn-success btn-action">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    </form>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="id" value="<?php echo $apt['id']; ?>">
                                                        <input type="hidden" name="status" value="rejected">
                                                        <button type="submit" name="update_status" class="btn btn-danger btn-action">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </form>
                                                <?php elseif ($apt['status'] === 'approved'): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="id" value="<?php echo $apt['id']; ?>">
                                                        <input type="hidden" name="status" value="completed">
                                                        <button type="submit" name="update_status" class="btn btn-primary btn-action">
                                                            <i class="fas fa-check-double"></i> Complete
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <i class="fas fa-calendar-times" style="font-size: 3rem; color: var(--text-muted);"></i>
                                        <h5 class="mt-3">No Appointments Found</h5>
                                        <p class="text-secondary">No <?php echo $filter !== 'all' ? $filter : ''; ?> appointments at the moment.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_records > 0): ?>
                <div class="d-flex justify-content-between align-items-center mt-4 p-3" style="background: var(--glass-bg); border-radius: var(--radius-md); border: 1px solid var(--glass-border);">
                    <div class="text-secondary">
                        Showing <?php echo $start_entry; ?> to <?php echo $end_entry; ?> of <?php echo $total_records; ?> entries
                    </div>
                    <nav>
                        <ul class="pagination mb-0">
                            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?filter=<?php echo $filter; ?>&page=<?php echo $page - 1; ?>">Previous</a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                <a class="page-link" href="?filter=<?php echo $filter; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?filter=<?php echo $filter; ?>&page=<?php echo $page + 1; ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>