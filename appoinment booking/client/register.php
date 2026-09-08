<?php
session_start();
include '../db.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($name) || empty($email) || empty($phone) || empty($password)) {
        $message = "All fields are required";
        $messageType = "danger";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address";
        $messageType = "danger";
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters";
        $messageType = "danger";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match";
        $messageType = "danger";
    } else {
        // Check if email already exists
        $check_sql = "SELECT id FROM clients WHERE email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $message = "Email already registered. Please login.";
            $messageType = "warning";
        } else {
            // Insert new client
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert_sql = "INSERT INTO clients (name, email, phone, password) VALUES (?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("ssss", $name, $email, $phone, $hashed_password);
            
            if ($insert_stmt->execute()) {
                header("Location: login.php?registered=1");
                exit;
            } else {
                $message = "Registration failed. Please try again.";
                $messageType = "danger";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - MedAppoint</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .register-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .register-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            padding: 3rem;
            width: 100%;
            max-width: 500px;
            box-shadow: var(--glass-shadow);
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .register-icon {
            width: 80px;
            height: 80px;
            background: var(--primary-gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }
        
        .register-icon i {
            font-size: 2rem;
            color: white;
        }
        
        .register-header h2 {
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }
        
        .register-header p {
            color: var(--text-secondary);
            margin: 0;
        }
        
        .form-floating {
            margin-bottom: 1rem;
        }
        
        .form-floating > .form-control {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            color: var(--text-primary);
            height: calc(3.5rem + 2px);
            padding: 1rem 0.75rem;
        }
        
        .form-floating > label {
            color: var(--text-muted);
            padding: 1rem 0.75rem;
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
        
        .input-icon {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            pointer-events: none;
        }
        
        .password-strength {
            height: 4px;
            border-radius: 2px;
            margin-top: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .password-strength.weak {
            background: linear-gradient(to right, #ef4444 33%, transparent 33%);
        }
        
        .password-strength.medium {
            background: linear-gradient(to right, #f59e0b 66%, transparent 66%);
        }
        
        .password-strength.strong {
            background: linear-gradient(to right, #10b981 100%, transparent 100%);
        }
        
        .strength-text {
            font-size: 0.75rem;
            margin-top: 0.25rem;
        }
        
        .strength-text.weak { color: #ef4444; }
        .strength-text.medium { color: #f59e0b; }
        .strength-text.strong { color: #10b981; }
        
        .terms-check {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            margin: 1.5rem 0;
        }
        
        .terms-check input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--primary-color);
            margin-top: 2px;
        }
        
        .terms-check label {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .terms-check a {
            color: var(--primary-color);
        }
        
        .btn-register {
            width: 100%;
            padding: 1rem;
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--text-secondary);
        }
        
        .login-link a {
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
    </style>
</head>
<body>
    <a href="../index.php" class="back-home">
        <i class="fas fa-arrow-left"></i> Back to Home
    </a>
    
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <div class="register-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h2>Create Account</h2>
                <p>Join MedAppoint today</p>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> mb-4">
                    <i class="fas fa-<?php echo $messageType === 'danger' ? 'exclamation-circle' : 'info-circle'; ?> me-2"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <form method="post" id="registerForm">
                <div class="form-floating">
                    <input type="text" class="form-control" id="name" name="name" placeholder="Full Name" required 
                           value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                    <label for="name"><i class="fas fa-user me-2"></i>Full Name</label>
                </div>
                
                <div class="form-floating">
                    <input type="email" class="form-control" id="email" name="email" placeholder="Email" required
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    <label for="email"><i class="fas fa-envelope me-2"></i>Email Address</label>
                </div>
                
                <div class="form-floating">
                    <input type="tel" class="form-control" id="phone" name="phone" placeholder="Phone" required
                           value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                    <label for="phone"><i class="fas fa-phone me-2"></i>Phone Number</label>
                </div>
                
                <div class="form-floating">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required minlength="6">
                    <label for="password"><i class="fas fa-lock me-2"></i>Password</label>
                </div>
                <div class="password-strength" id="passwordStrength"></div>
                <small class="strength-text" id="strengthText"></small>
                
                <div class="form-floating mt-3">
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
                    <label for="confirm_password"><i class="fas fa-lock me-2"></i>Confirm Password</label>
                </div>
                
                <div class="terms-check">
                    <input type="checkbox" id="terms" name="terms" required>
                    <label for="terms">
                        I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary btn-register">
                    <i class="fas fa-user-plus me-2"></i>Create Account
                </button>
            </form>
            
            <div class="login-link">
                Already have an account? <a href="login.php">Login here</a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password strength checker
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('passwordStrength');
        const strengthText = document.getElementById('strengthText');
        
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            if (password.length >= 6) strength++;
            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            strengthBar.className = 'password-strength';
            strengthText.className = 'strength-text';
            
            if (password.length === 0) {
                strengthText.textContent = '';
            } else if (strength <= 2) {
                strengthBar.classList.add('weak');
                strengthText.classList.add('weak');
                strengthText.textContent = 'Weak password';
            } else if (strength <= 3) {
                strengthBar.classList.add('medium');
                strengthText.classList.add('medium');
                strengthText.textContent = 'Medium password';
            } else {
                strengthBar.classList.add('strong');
                strengthText.classList.add('strong');
                strengthText.textContent = 'Strong password';
            }
        });
        
        // Confirm password validation
        const confirmPassword = document.getElementById('confirm_password');
        const form = document.getElementById('registerForm');
        
        form.addEventListener('submit', function(e) {
            if (passwordInput.value !== confirmPassword.value) {
                e.preventDefault();
                alert('Passwords do not match!');
                confirmPassword.focus();
            }
        });
    </script>
</body>
</html>
