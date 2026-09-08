<?php
session_start();
if (!isset($_SESSION['client_id'])) {
    header("Location: login.php");
    exit;
}
include '../db.php';

$client_id = $_SESSION['client_id'];
$message = '';
$messageType = '';

// Get client information
$client = $conn->query("SELECT * FROM clients WHERE id = $client_id")->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address'] ?? '');
    $dob = $_POST['date_of_birth'] ?? null;
    $gender = $_POST['gender'] ?? null;
    
    // Update profile
    $stmt = $conn->prepare("UPDATE clients SET name = ?, phone = ?, address = ?, date_of_birth = ?, gender = ? WHERE id = ?");
    $stmt->bind_param("sssssi", $name, $phone, $address, $dob, $gender, $client_id);
    
    if ($stmt->execute()) {
        $message = "Profile updated successfully!";
        $messageType = "success";
        // Refresh client data
        $client = $conn->query("SELECT * FROM clients WHERE id = $client_id")->fetch_assoc();
    } else {
        $message = "Error updating profile. Please try again.";
        $messageType = "danger";
    }
    
    // Handle password change
    if (!empty($_POST['current_password']) && !empty($_POST['new_password'])) {
        if (password_verify($_POST['current_password'], $client['password'])) {
            if (strlen($_POST['new_password']) >= 6) {
                $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                $conn->query("UPDATE clients SET password = '$new_password' WHERE id = $client_id");
                $message .= " Password changed successfully!";
            } else {
                $message = "New password must be at least 6 characters.";
                $messageType = "warning";
            }
        } else {
            $message = "Current password is incorrect.";
            $messageType = "danger";
        }
    }
}

// Get appointment stats
$total_appointments = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE client_id = $client_id")->fetch_assoc()['count'];
$completed_appointments = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE client_id = $client_id AND status = 'completed'")->fetch_assoc()['count'];

$initials = strtoupper(substr($client['name'], 0, 1));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - MedAppoint</title>
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .profile-header {
            background: var(--primary-gradient);
            border-radius: var(--radius-lg);
            padding: 2.5rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .profile-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 400px;
            height: 400px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: var(--primary-color);
            font-weight: 700;
            border: 4px solid rgba(255,255,255,0.3);
            position: relative;
            z-index: 1;
        }
        
        .profile-info {
            position: relative;
            z-index: 1;
        }
        
        .profile-info h1 {
            margin: 0;
            color: white;
            font-size: 1.75rem;
        }
        
        .profile-info p {
            margin: 0.5rem 0 0;
            color: rgba(255,255,255,0.9);
        }
        
        .profile-stats {
            display: flex;
            gap: 2rem;
            margin-top: 1rem;
        }
        
        .profile-stat {
            text-align: center;
        }
        
        .profile-stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
        }
        
        .profile-stat-label {
            font-size: 0.85rem;
            color: rgba(255,255,255,0.8);
        }
        
        .profile-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        
        .profile-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
        }
        
        .profile-card h3 {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--glass-border);
        }
        
        .profile-card h3 i {
            color: var(--primary-color);
        }
        
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .form-group label {
            display: block;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-group .form-control,
        .form-group .form-select {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            color: var(--text-primary);
            padding: 0.75rem 1rem;
            border-radius: var(--radius-md);
        }
        
        .form-group .form-control:focus,
        .form-group .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.25);
        }
        
        .form-group .form-select option {
            background: #1a1a2e;
        }
        
        .btn-update {
            width: 100%;
            padding: 0.875rem;
            margin-top: 0.5rem;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
            text-decoration: none;
            transition: var(--transition-normal);
        }
        
        .back-link:hover {
            color: var(--primary-color);
        }
        
        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            
            .profile-content {
                grid-template-columns: 1fr;
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
        <a href="dashboard.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> mb-4">
                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-avatar"><?php echo $initials; ?></div>
            <div class="profile-info">
                <h1><?php echo htmlspecialchars($client['name']); ?></h1>
                <p><i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($client['email']); ?></p>
                <div class="profile-stats">
                    <div class="profile-stat">
                        <div class="profile-stat-number"><?php echo $total_appointments; ?></div>
                        <div class="profile-stat-label">Appointments</div>
                    </div>
                    <div class="profile-stat">
                        <div class="profile-stat-number"><?php echo $completed_appointments; ?></div>
                        <div class="profile-stat-label">Completed</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Content -->
        <div class="profile-content">
            <!-- Personal Info -->
            <div class="profile-card">
                <h3><i class="fas fa-user"></i> Personal Information</h3>
                <form method="POST">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($client['name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($client['email']); ?>" disabled>
                        <small class="text-muted">Email cannot be changed</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($client['phone'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="date" name="date_of_birth" class="form-control" value="<?php echo $client['date_of_birth'] ?? ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Gender</label>
                        <select name="gender" class="form-select">
                            <option value="">Select Gender</option>
                            <option value="male" <?php echo ($client['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                            <option value="female" <?php echo ($client['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                            <option value="other" <?php echo ($client['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($client['address'] ?? ''); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-update">
                        <i class="fas fa-save me-2"></i>Update Profile
                    </button>
                </form>
            </div>

            <!-- Change Password -->
            <div class="profile-card">
                <h3><i class="fas fa-lock"></i> Change Password</h3>
                <form method="POST">
                    <input type="hidden" name="name" value="<?php echo htmlspecialchars($client['name']); ?>">
                    <input type="hidden" name="phone" value="<?php echo htmlspecialchars($client['phone'] ?? ''); ?>">
                    
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" name="current_password" class="form-control" placeholder="Enter current password">
                    </div>
                    
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" class="form-control" placeholder="Enter new password" minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" placeholder="Confirm new password">
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-update">
                        <i class="fas fa-key me-2"></i>Change Password
                    </button>
                </form>
                
                <div class="mt-4 p-3" style="background: rgba(102, 126, 234, 0.1); border-radius: var(--radius-md);">
                    <h5 class="mb-3"><i class="fas fa-shield-alt me-2 text-primary"></i>Security Tips</h5>
                    <ul class="mb-0 ps-3" style="color: var(--text-secondary);">
                        <li>Use at least 8 characters</li>
                        <li>Include uppercase and lowercase letters</li>
                        <li>Add numbers and special characters</li>
                        <li>Don't use personal information</li>
                    </ul>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
