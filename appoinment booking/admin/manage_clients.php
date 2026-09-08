<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
include '../db.php';

// Pagination settings
$items_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

// Get total records
$total_result = $conn->query("SELECT COUNT(*) as total FROM clients");
$total_records = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $items_per_page);
if ($page > $total_pages && $total_pages > 0) $page = $total_pages;

$offset = ($page - 1) * $items_per_page;
$start_entry = $total_records > 0 ? $offset + 1 : 0;
$end_entry = min($offset + $items_per_page, $total_records);

$clients = $conn->query("SELECT * FROM clients ORDER BY id DESC LIMIT $offset, $items_per_page");
$admin_name = isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : 'Admin';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Clients - MedAppoint</title>
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
        
        /* Table Styles */
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
        
        .client-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .client-avatar {
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
        
        .client-details h6 {
            margin: 0;
            font-size: 0.95rem;
        }
        
        .client-details small {
            color: var(--text-muted);
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
                    <a href="dashboard.php">
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
                    <a href="manage_clients.php" class="active">
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
                    <h1>Manage <span>Clients</span></h1>
                    <p class="text-secondary mb-0">View all registered client records</p>
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
            
            <!-- Clients Table -->
            <div class="content-card">
                <div class="content-card-header">
                    <h4><i class="fas fa-users"></i> Client Records</h4>
                    <span class="badge bg-primary"><?php echo $clients->num_rows; ?> Total</span>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Registered</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($clients && $clients->num_rows > 0): ?>
                                <?php while ($client = $clients->fetch_assoc()) { ?>
                                <tr>
                                    <td>
                                        <div class="client-info">
                                            <div class="client-avatar">
                                                <?php echo strtoupper(substr($client['name'], 0, 1)); ?>
                                            </div>
                                            <div class="client-details">
                                                <h6><?php echo htmlspecialchars($client['name']); ?></h6>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <i class="fas fa-envelope me-2 text-secondary"></i>
                                        <?php echo htmlspecialchars($client['email']); ?>
                                    </td>
                                    <td>
                                        <i class="fas fa-phone me-2 text-secondary"></i>
                                        <?php echo htmlspecialchars($client['phone']); ?>
                                    </td>
                                    <td>
                                        <?php echo isset($client['created_at']) ? date('M d, Y', strtotime($client['created_at'])) : 'N/A'; ?>
                                    </td>
                                </tr>
                                <?php } ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5">
                                        <i class="fas fa-users" style="font-size: 3rem; color: var(--text-muted);"></i>
                                        <h5 class="mt-3">No Clients Found</h5>
                                        <p class="text-secondary">No clients have registered yet.</p>
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
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('show');
        }
    </script>
</body>
</html>