<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
include '../db.php';

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_doctor'])) {
        $name = trim($_POST['name']);
        $specialization = trim($_POST['specialization']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $bio = trim($_POST['bio'] ?? '');
        $experience = intval($_POST['experience_years'] ?? 0);
        $fee = floatval($_POST['consultation_fee'] ?? 0);
        
        $stmt = $conn->prepare("INSERT INTO doctors (name, specialization, email, phone, bio, experience_years, consultation_fee) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssid", $name, $specialization, $email, $phone, $bio, $experience, $fee);
        
        if ($stmt->execute()) {
            $message = "Doctor added successfully!";
            $messageType = "success";
        } else {
            $message = "Error adding doctor: " . $conn->error;
            $messageType = "danger";
        }
    }
    
    if (isset($_POST['edit_doctor'])) {
        $id = intval($_POST['id']);
        $name = trim($_POST['name']);
        $specialization = trim($_POST['specialization']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $bio = trim($_POST['bio'] ?? '');
        $experience = intval($_POST['experience_years'] ?? 0);
        $fee = floatval($_POST['consultation_fee'] ?? 0);
        
        $stmt = $conn->prepare("UPDATE doctors SET name=?, specialization=?, email=?, phone=?, bio=?, experience_years=?, consultation_fee=? WHERE id=?");
        $stmt->bind_param("sssssidi", $name, $specialization, $email, $phone, $bio, $experience, $fee, $id);
        
        if ($stmt->execute()) {
            $message = "Doctor updated successfully!";
            $messageType = "success";
        } else {
            $message = "Error updating doctor.";
            $messageType = "danger";
        }
    }
    
    if (isset($_POST['delete_doctor'])) {
        $id = intval($_POST['id']);
        if ($conn->query("DELETE FROM doctors WHERE id = $id")) {
            $message = "Doctor deleted successfully!";
            $messageType = "success";
        } else {
            $message = "Error deleting doctor.";
            $messageType = "danger";
        }
    }
}

// Get all doctors
$doctors = $conn->query("SELECT d.*, 
    (SELECT AVG(rating) FROM reviews WHERE doctor_id = d.id) as avg_rating,
    (SELECT COUNT(*) FROM reviews WHERE doctor_id = d.id) as review_count,
    (SELECT COUNT(*) FROM appointments WHERE doctor_id = d.id) as appointment_count
    FROM doctors d ORDER BY d.name");

$admin_name = isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : 'Admin';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Doctors - Admin</title>
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
        
        .page-header h1 {
            margin: 0;
        }
        
        .page-header h1 span {
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        /* Doctor Cards Grid */
        .doctors-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }
        
        .doctor-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            overflow: hidden;
            transition: var(--transition-normal);
        }
        
        .doctor-card:hover {
            border-color: rgba(102, 126, 234, 0.4);
        }
        
        .doctor-card-header {
            background: var(--primary-gradient);
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .doctor-avatar {
            width: 60px;
            height: 60px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--primary-color);
        }
        
        .doctor-card-header h4 {
            margin: 0;
            color: white;
        }
        
        .doctor-card-header p {
            margin: 0;
            color: rgba(255,255,255,0.9);
            font-size: 0.9rem;
        }
        
        .doctor-card-body {
            padding: 1.5rem;
        }
        
        .doctor-info-row {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 0;
            color: var(--text-secondary);
            border-bottom: 1px solid var(--glass-border);
        }
        
        .doctor-info-row:last-child {
            border-bottom: none;
        }
        
        .doctor-info-row i {
            width: 20px;
            color: var(--primary-color);
        }
        
        .doctor-stats {
            display: flex;
            gap: 1rem;
            margin: 1rem 0;
        }
        
        .doctor-stat {
            flex: 1;
            text-align: center;
            padding: 0.75rem;
            background: rgba(102, 126, 234, 0.1);
            border-radius: var(--radius-md);
        }
        
        .doctor-stat-value {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .doctor-stat-label {
            font-size: 0.75rem;
            color: var(--text-muted);
        }
        
        .doctor-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .doctor-actions .btn {
            flex: 1;
        }
        
        /* Modal Styling */
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
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
            display: block;
        }
        
        .form-group .form-control,
        .form-group .form-select {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            color: var(--text-primary);
        }
        
        .form-group .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.25);
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
                <li><a href="manage_doctors.php" class="active"><i class="fas fa-user-md"></i> Doctors</a></li>
                <li><a href="manage_schedules.php"><i class="fas fa-calendar-alt"></i> Schedules</a></li>
                <li><a href="view_appointments.php"><i class="fas fa-clipboard-list"></i> Appointments</a></li>
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
                    <h1>Manage <span>Doctors</span></h1>
                    <p class="text-secondary mb-0">Add, edit, and manage doctor profiles</p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDoctorModal">
                    <i class="fas fa-plus me-2"></i>Add Doctor
                </button>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> mb-4">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <!-- Doctors Grid -->
            <div class="doctors-grid">
                <?php if ($doctors && $doctors->num_rows > 0): ?>
                    <?php while ($doc = $doctors->fetch_assoc()): ?>
                        <div class="doctor-card">
                            <div class="doctor-card-header">
                                <div class="doctor-avatar">
                                    <i class="fas fa-user-md"></i>
                                </div>
                                <div>
                                    <h4>Dr. <?php echo htmlspecialchars($doc['name']); ?></h4>
                                    <p><?php echo htmlspecialchars($doc['specialization']); ?></p>
                                </div>
                            </div>
                            <div class="doctor-card-body">
                                <div class="doctor-info-row">
                                    <i class="fas fa-envelope"></i>
                                    <span><?php echo htmlspecialchars($doc['email']); ?></span>
                                </div>
                                <div class="doctor-info-row">
                                    <i class="fas fa-phone"></i>
                                    <span><?php echo htmlspecialchars($doc['phone']); ?></span>
                                </div>
                                
                                <div class="doctor-stats">
                                    <div class="doctor-stat">
                                        <div class="doctor-stat-value"><?php echo $doc['appointment_count']; ?></div>
                                        <div class="doctor-stat-label">Appointments</div>
                                    </div>
                                    <div class="doctor-stat">
                                        <div class="doctor-stat-value">
                                            <?php echo $doc['avg_rating'] ? number_format($doc['avg_rating'], 1) : '-'; ?>
                                        </div>
                                        <div class="doctor-stat-label">Rating</div>
                                    </div>
                                    <div class="doctor-stat">
                                        <div class="doctor-stat-value"><?php echo $doc['review_count']; ?></div>
                                        <div class="doctor-stat-label">Reviews</div>
                                    </div>
                                </div>
                                
                                <div class="doctor-actions">
                                    <button class="btn btn-secondary btn-sm" onclick="editDoctor(<?php echo htmlspecialchars(json_encode($doc)); ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <form method="POST" style="flex: 1;" onsubmit="return confirm('Delete this doctor?');">
                                        <input type="hidden" name="id" value="<?php echo $doc['id']; ?>">
                                        <button type="submit" name="delete_doctor" class="btn btn-danger btn-sm w-100">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center py-5" style="grid-column: 1/-1;">
                        <i class="fas fa-user-md" style="font-size: 4rem; color: var(--text-muted);"></i>
                        <h3 class="mt-3">No Doctors Found</h3>
                        <p class="text-secondary">Add your first doctor to get started.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <!-- Add Doctor Modal -->
    <div class="modal fade" id="addDoctorModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Add New Doctor</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Specialization</label>
                            <input type="text" name="specialization" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" name="phone" class="form-control" required>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label>Experience (Years)</label>
                                    <input type="number" name="experience_years" class="form-control" min="0">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label>Consultation Fee (₹)</label>
                                    <input type="number" name="consultation_fee" class="form-control" min="0" step="0.01">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Bio</label>
                            <textarea name="bio" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_doctor" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Add Doctor
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Doctor Modal -->
    <div class="modal fade" id="editDoctorModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Doctor</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Specialization</label>
                            <input type="text" name="specialization" id="edit_specialization" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" id="edit_email" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" name="phone" id="edit_phone" class="form-control" required>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label>Experience (Years)</label>
                                    <input type="number" name="experience_years" id="edit_experience" class="form-control" min="0">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label>Consultation Fee (₹)</label>
                                    <input type="number" name="consultation_fee" id="edit_fee" class="form-control" min="0" step="0.01">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Bio</label>
                            <textarea name="bio" id="edit_bio" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="edit_doctor" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editDoctor(doctor) {
            document.getElementById('edit_id').value = doctor.id;
            document.getElementById('edit_name').value = doctor.name;
            document.getElementById('edit_specialization').value = doctor.specialization;
            document.getElementById('edit_email').value = doctor.email;
            document.getElementById('edit_phone').value = doctor.phone;
            document.getElementById('edit_experience').value = doctor.experience_years || 0;
            document.getElementById('edit_fee').value = doctor.consultation_fee || 0;
            document.getElementById('edit_bio').value = doctor.bio || '';
            
            new bootstrap.Modal(document.getElementById('editDoctorModal')).show();
        }
    </script>
</body>
</html>