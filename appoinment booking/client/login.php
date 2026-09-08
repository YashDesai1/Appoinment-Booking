<?php
session_start();
include '../db.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM clients WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $client = $result->fetch_assoc();
        if (password_verify($password, $client['password'])) {
            $_SESSION['client_id'] = $client['id'];
            header("Location: dashboard.php");
            exit;
        } else {
            $message = "Invalid password";
            $messageType = "danger";
        }
    } else {
        $message = "Email not found";
        $messageType = "danger";
    }
}

if (isset($_GET['registered'])) {
    $message = "Registration successful! Please login.";
    $messageType = "success";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MedAppoint</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .login-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .login-wrapper {
            display: flex;
            width: 100%;
            max-width: 900px;
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--glass-shadow);
        }
        
        .login-branding {
            flex: 1;
            background: var(--primary-gradient);
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .login-branding::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 50%);
            animation: pulse 4s ease-in-out infinite;
        }
        
        .login-branding h1 {
            font-size: 2rem;
            margin-bottom: 1rem;
            position: relative;
            z-index: 1;
        }
        
        .login-branding p {
            color: rgba(255,255,255,0.9);
            position: relative;
            z-index: 1;
        }
        
        .branding-icon {
            font-size: 5rem;
            margin-bottom: 2rem;
            position: relative;
            z-index: 1;
        }
        
        .login-form-section {
            flex: 1;
            padding: 3rem;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-icon {
            width: 70px;
            height: 70px;
            background: var(--primary-gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }
        
        .login-icon i {
            font-size: 1.75rem;
            color: white;
        }
        
        .login-header h2 {
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .form-floating {
            margin-bottom: 1rem;
        }
        
        .form-floating > .form-control {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            color: var(--text-primary);
            height: calc(3.5rem + 2px);
        }
        
        .form-floating > label {
            color: var(--text-muted);
        }
        
        .form-floating > .form-control:focus {
            background: rgba(255, 255, 255, 0.1);
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.25);
            color: var(--text-primary);
        }
        
        .form-floating > .form-control:focus ~ label,
        .form-floating > .form-control:not(:placeholder-shown) ~ label {
            color: var(--primary-color);
            background: transparent;
        }
        
        .form-floating > label {
            background: transparent !important;
        }
        
        .btn-login {
            width: 100%;
            padding: 1rem;
            font-size: 1.1rem;
            font-weight: 600;
            margin-top: 1rem;
        }
        
        .divider {
            display: flex;
            align-items: center;
            margin: 1.5rem 0;
            color: var(--text-muted);
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--glass-border);
        }
        
        .divider span {
            padding: 0 1rem;
            font-size: 0.9rem;
        }
        
        .register-link {
            text-align: center;
            color: var(--text-secondary);
        }
        
        .register-link a {
            color: var(--primary-color);
            font-weight: 500;
        }
        
        .back-home {
            position: fixed;
            top: 2rem;
            left: 2rem;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition-normal);
        }
        
        .back-home:hover {
            color: var(--primary-color);
        }
        
        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 1rem 0;
        }
        
        .remember-forgot label {
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }
        
        .remember-forgot input[type="checkbox"] {
            accent-color: var(--primary-color);
        }
        
        .remember-forgot a {
            color: var(--primary-color);
            font-size: 0.9rem;
        }
        
        @media (max-width: 768px) {
            .login-branding {
                display: none;
            }
            
            .login-wrapper {
                max-width: 450px;
            }
        }
    </style>
</head>
<body>
    <a href="../index.php" class="back-home">
        <i class="fas fa-arrow-left"></i> Back to Home
    </a>
    
    <div class="login-page">
        <div class="login-wrapper">
            <div class="login-branding">
                <i class="fas fa-hospital-alt branding-icon"></i>
                <h1>Welcome Back!</h1>
                <p>Access your healthcare dashboard. Manage appointments, view doctors, and take control of your health journey.</p>
            </div>
            
            <div class="login-form-section">
                <div class="login-header">
                    <div class="login-icon">
                        <i class="fas fa-sign-in-alt"></i>
                    </div>
                    <h2>Client Login</h2>
                    <p class="text-secondary">Enter your credentials to continue</p>
                </div>
                
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?>">
                        <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <form method="post">
                    <div class="form-floating">
                        <input type="email" class="form-control" id="email" name="email" placeholder="Email" required>
                        <label for="email"><i class="fas fa-envelope me-2"></i>Email Address</label>
                    </div>
                    
                    <div class="form-floating">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                        <label for="password"><i class="fas fa-lock me-2"></i>Password</label>
                    </div>
                    
                    <div class="remember-forgot">
                        <label>
                            <input type="checkbox" name="remember"> Remember me
                        </label>
                        <a href="#">Forgot Password?</a>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-login">
                        <i class="fas fa-sign-in-alt me-2"></i>Login
                    </button>
                </form>
                
                <div class="divider">
                    <span>OR</span>
                </div>
                
                <div class="register-link">
                    Don't have an account? <a href="register.php">Register here</a>
                    <div class="mt-2 text-muted" style="font-size: 0.85rem;">
                        <i class="fas fa-info-circle me-1"></i> Demo user: <strong>john@example.com</strong> / <strong>john123</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>