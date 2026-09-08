<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
include '../db.php';

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $doctor_id = $_POST['doctor_id'];
    $date = $_POST['date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $sql = "INSERT INTO schedules (doctor_id, date, start_time, end_time) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isss", $doctor_id, $date, $start_time, $end_time);
    if ($stmt->execute()) {
        $message = "Schedule added successfully";
        $message_type = "success";
    } else {
        $message = "Error adding schedule";
        $message_type = "danger";
    }
} elseif (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $sql = "DELETE FROM schedules WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = "Schedule deleted successfully";
        $message_type = "success";
    } else {
        $message = "Error deleting schedule";
        $message_type = "danger";
    }
}

$doctors = $conn->query("SELECT * FROM doctors");
$schedules = $conn->query("SELECT s.*, d.name AS doctor_name, d.specialization FROM schedules s JOIN doctors d ON s.doctor_id = d.id ORDER BY s.date DESC");

$admin_name = isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : 'Admin';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Schedules - MedAppoint</title>
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
        
        /* Form Styles */
        .form-control, .form-select {
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--glass-border);
            color: var(--text-primary);
            border-radius: var(--radius-md);
            padding: 0.75rem 1rem;
        }
        
        .form-control:focus, .form-select:focus {
            background: rgba(255,255,255,0.08);
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15);
            color: var(--text-primary);
        }
        
        .form-label {
            color: var(--text-secondary);
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        
        /* Table Styles */
        .schedules-table {
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
        
        /* Sticky Table Header */
        .table thead th {
            position: sticky;
            top: 0;
            z-index: 10;
            background: #1e1e2f !important;
        }
        
        .status-badge {
            padding: 0.35rem 0.75rem;
            border-radius: var(--radius-xl);
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .status-available { background: rgba(16, 185, 129, 0.2); color: #34d399; }
        .status-unavailable { background: rgba(239, 68, 68, 0.2); color: #f87171; }
        
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
                    <a href="manage_schedules.php" class="active">
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
                    <h1>Manage <span>Schedules</span></h1>
                    <p class="text-secondary mb-0">Add and manage doctor schedules</p>
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
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <!-- Add Schedule Form -->
                <div class="col-lg-4 mb-4">
                    <div class="content-card">
                        <div class="content-card-header">
                            <h4><i class="fas fa-calendar-plus"></i> Add Schedule</h4>
                        </div>
                        <form method="post">
                            <div class="mb-3">
                                <label class="form-label">Doctor</label>
                                <select name="doctor_id" class="form-select" required>
                                    <option value="">Select Doctor</option>
                                    <?php while ($doctor = $doctors->fetch_assoc()) { ?>
                                    <option value="<?php echo $doctor['id']; ?>">Dr. <?php echo htmlspecialchars($doctor['name']); ?> - <?php echo htmlspecialchars($doctor['specialization']); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Date</label>
                                <input type="date" name="date" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Start Time</label>
                                <input type="time" name="start_time" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">End Time</label>
                                <input type="time" name="end_time" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-plus me-2"></i>Add Schedule
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Schedules List -->
                <div class="col-lg-8 mb-4">
                    <div class="content-card">
                        <div class="content-card-header">
                            <h4><i class="fas fa-list"></i> Schedules List</h4>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <small class="text-secondary">
                                <i class="fas fa-info-circle me-1"></i>
                                Total <?php echo $schedules->num_rows; ?> schedules
                            </small>
                        </div>
                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Doctor</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($schedules && $schedules->num_rows > 0): ?>
                                        <?php while ($schedule = $schedules->fetch_assoc()) { ?>
                                        <tr>
                                            <td>
                                                <strong>Dr. <?php echo htmlspecialchars($schedule['doctor_name']); ?></strong><br>
                                                <small class="text-secondary"><?php echo htmlspecialchars($schedule['specialization']); ?></small>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($schedule['date'])); ?></td>
                                            <td>
                                                <?php echo date('h:i A', strtotime($schedule['start_time'])); ?> - 
                                                <?php echo date('h:i A', strtotime($schedule['end_time'])); ?>
                                            </td>
                                            <td>
                                                <span class="status-badge <?php echo $schedule['available'] ? 'status-available' : 'status-unavailable'; ?>">
                                                    <?php echo $schedule['available'] ? 'Available' : 'Booked'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="?delete=<?php echo $schedule['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this schedule?')">
                                                    <i class="fas fa-trash"></i> Delete
                                                </a>
                                            </td>
                                        </tr>
                                        <?php } ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-5">
                                                <i class="fas fa-calendar-times" style="font-size: 3rem; color: var(--text-muted);"></i>
                                                <h5 class="mt-3">No Schedules Found</h5>
                                                <p class="text-secondary">Add a new schedule to get started.</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
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