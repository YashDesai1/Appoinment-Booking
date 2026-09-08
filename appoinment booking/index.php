<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedAppoint - Online Appointment Scheduling System</title>
    <meta name="description" content="Book appointments with top healthcare professionals easily. Modern, efficient healthcare management.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Hero Section */
        .hero-section {
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
            padding: 100px 0;
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
        }
        
        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }
        
        .hero-title span {
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .hero-subtitle {
            font-size: 1.25rem;
            color: var(--text-secondary);
            margin-bottom: 2rem;
            max-width: 500px;
        }
        
        .hero-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .hero-image {
            position: relative;
            z-index: 1;
        }
        
        .hero-image i {
            font-size: 20rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            opacity: 0.8;
            animation: float 4s ease-in-out infinite;
        }
        
        /* Floating Elements */
        .floating-element {
            position: absolute;
            border-radius: 50%;
            background: var(--primary-gradient);
            opacity: 0.1;
            animation: float 6s ease-in-out infinite;
        }
        
        .floating-element:nth-child(1) {
            width: 300px;
            height: 300px;
            top: 10%;
            right: 5%;
            animation-delay: 0s;
        }
        
        .floating-element:nth-child(2) {
            width: 200px;
            height: 200px;
            bottom: 20%;
            left: 10%;
            animation-delay: 2s;
        }
        
        .floating-element:nth-child(3) {
            width: 150px;
            height: 150px;
            top: 50%;
            left: 30%;
            animation-delay: 4s;
        }
        
        /* Stats Section */
        .stats-section {
            padding: 80px 0;
            background: var(--glass-bg);
            border-top: 1px solid var(--glass-border);
            border-bottom: 1px solid var(--glass-border);
        }
        
        .stat-card {
            text-align: center;
            padding: 2rem;
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: block;
        }
        
        .stat-label {
            color: var(--text-secondary);
            font-size: 1rem;
            margin-top: 0.5rem;
        }
        
        /* Features Section */
        .features-section {
            padding: 100px 0;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 4rem;
        }
        
        .section-title h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .section-title p {
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .feature-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            padding: 2.5rem;
            height: 100%;
            transition: var(--transition-normal);
            position: relative;
            overflow: hidden;
        }
        
        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--primary-gradient);
            transform: scaleX(0);
            transition: var(--transition-normal);
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            border-color: rgba(102, 126, 234, 0.4);
        }
        
        .feature-card:hover::before {
            transform: scaleX(1);
        }
        
        .feature-icon {
            width: 70px;
            height: 70px;
            background: var(--primary-gradient);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
        }
        
        .feature-icon i {
            font-size: 1.75rem;
            color: white;
        }
        
        .feature-card h4 {
            font-size: 1.25rem;
            margin-bottom: 1rem;
        }
        
        .feature-card p {
            font-size: 0.95rem;
            margin: 0;
        }
        
        /* Portal Cards Section */
        .portals-section {
            padding: 100px 0;
            background: var(--glass-bg);
            border-top: 1px solid var(--glass-border);
        }
        
        .portal-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            padding: 3rem 2rem;
            text-align: center;
            transition: var(--transition-normal);
            height: 100%;
        }
        
        .portal-card:hover {
            transform: translateY(-15px);
            border-color: rgba(102, 126, 234, 0.5);
            box-shadow: 0 30px 60px rgba(102, 126, 234, 0.2);
        }
        
        .portal-card .portal-icon {
            width: 100px;
            height: 100px;
            background: var(--primary-gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }
        
        .portal-card .portal-icon i {
            font-size: 2.5rem;
            color: white;
        }
        
        .portal-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .portal-card p {
            margin-bottom: 1.5rem;
        }
        
        .portal-card .btn-group-portal {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        
        /* CTA Section */
        .cta-section {
            padding: 100px 0;
            text-align: center;
        }
        
        .cta-box {
            background: var(--primary-gradient);
            border-radius: var(--radius-lg);
            padding: 4rem;
            position: relative;
            overflow: hidden;
        }
        
        .cta-box::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 50%);
            animation: pulse 4s ease-in-out infinite;
        }
        
        .cta-box h2 {
            position: relative;
            z-index: 1;
            margin-bottom: 1rem;
        }
        
        .cta-box p {
            position: relative;
            z-index: 1;
            color: rgba(255,255,255,0.9);
            margin-bottom: 2rem;
        }
        
        .cta-box .btn {
            position: relative;
            z-index: 1;
        }
        
        .btn-white {
            background: white;
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .btn-white:hover {
            background: rgba(255,255,255,0.9);
            color: var(--secondary-color);
            transform: translateY(-2px);
        }
        
        /* Footer */
        .main-footer {
            background: rgba(0,0,0,0.3);
            padding: 60px 0 30px;
            border-top: 1px solid var(--glass-border);
        }
        
        .footer-brand {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .footer-brand i {
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .footer-links h5 {
            font-size: 1rem;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }
        
        .footer-links ul {
            list-style: none;
            padding: 0;
        }
        
        .footer-links li {
            margin-bottom: 0.5rem;
        }
        
        .footer-links a {
            color: var(--text-secondary);
            transition: var(--transition-fast);
        }
        
        .footer-links a:hover {
            color: var(--primary-color);
        }
        
        .footer-bottom {
            border-top: 1px solid var(--glass-border);
            padding-top: 2rem;
            margin-top: 3rem;
            text-align: center;
        }
        
        @media (max-width: 992px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-image i {
                font-size: 12rem;
            }
        }
        
        @media (max-width: 768px) {
            .hero-section {
                padding: 60px 0;
                text-align: center;
            }
            
            .hero-subtitle {
                margin: 0 auto 2rem;
            }
            
            .hero-buttons {
                justify-content: center;
            }
            
            .hero-image {
                margin-top: 3rem;
            }
            
            .stat-card {
                padding: 1.5rem;
            }
            
            .stat-number {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Floating Background Elements -->
    <div class="floating-element"></div>
    <div class="floating-element"></div>
    <div class="floating-element"></div>
    
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-hospital-alt"></i>
                MedAppoint
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin/login.php">Admin</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-primary ms-2" href="client/login.php">Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="hero-content animate-fadeInUp">
                        <h1 class="hero-title">
                            Book Your <span>Healthcare</span> Appointments Online
                        </h1>
                        <p class="hero-subtitle">
                            Experience seamless medical appointment scheduling. Connect with top doctors, manage your health journey efficiently.
                        </p>
                        <div class="hero-buttons">
                            <a href="client/register.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-user-plus"></i> Get Started
                            </a>
                            <a href="client/login.php" class="btn btn-glass btn-lg">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <div class="hero-image">
                        <i class="fas fa-user-md"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row">
                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <span class="stat-number" data-count="500">500+</span>
                        <p class="stat-label">Happy Patients</p>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <span class="stat-number" data-count="50">50+</span>
                        <p class="stat-label">Expert Doctors</p>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <span class="stat-number" data-count="20">20+</span>
                        <p class="stat-label">Specializations</p>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <span class="stat-number" data-count="1000">1000+</span>
                        <p class="stat-label">Appointments</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section" id="features">
        <div class="container">
            <div class="section-title">
                <h2>Why Choose <span class="text-gradient">MedAppoint?</span></h2>
                <p>Modern healthcare scheduling made simple, secure, and efficient.</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <h4>Easy Booking</h4>
                        <p>Book appointments with just a few clicks. Select your preferred doctor, date, and time slot.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-user-md"></i>
                        </div>
                        <h4>Expert Doctors</h4>
                        <p>Access a wide network of qualified healthcare professionals across various specializations.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-bell"></i>
                        </div>
                        <h4>Instant Updates</h4>
                        <p>Get real-time appointment confirmations and status updates instantly.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h4>Secure Platform</h4>
                        <p>Your health data is protected with industry-standard security measures.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h4>24/7 Access</h4>
                        <p>Book appointments anytime, anywhere. Our platform is always available.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <h4>Ratings & Reviews</h4>
                        <p>Make informed decisions with doctor ratings and patient reviews.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>



    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-box">
                <h2>Ready to Get Started?</h2>
                <p>Join thousands of patients who trust MedAppoint for their healthcare needs.</p>
                <a href="client/register.php" class="btn btn-white btn-lg">
                    <i class="fas fa-arrow-right"></i> Create Free Account
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="footer-brand">
                        <i class="fas fa-hospital-alt"></i>
                        MedAppoint
                    </div>
                    <p class="text-secondary">Modern healthcare appointment scheduling platform. Book appointments with ease.</p>
                </div>
                <div class="col-md-2 mb-4 footer-links">
                    <h5>Quick Links</h5>
                    <ul>
                        <li><a href="#features">Features</a></li>
                        <li><a href="client/login.php">Login</a></li>
                    </ul>
                </div>
                <div class="col-md-2 mb-4 footer-links">
                    <h5>Portals</h5>
                    <ul>
                        <li><a href="admin/login.php">Admin</a></li>
                        <li><a href="client/login.php">Client</a></li>
                        <li><a href="client/register.php">Register</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-4 footer-links">
                    <h5>Contact</h5>
                    <ul>
                        <li><i class="fas fa-envelope me-2"></i> support@medappoint.com</li>
                        <li><i class="fas fa-phone me-2"></i> +91 1234567890</li>
                        <li><i class="fas fa-map-marker-alt me-2"></i> India</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p class="mb-0">&copy; 2026 MedAppoint. All rights reserved. <i class="fas fa-heart text-danger"></i></p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Navbar background on scroll
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.style.background = 'rgba(15, 15, 26, 0.95)';
            } else {
                navbar.style.background = 'var(--glass-bg)';
            }
        });
        
        // Animate elements on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);
        
        document.querySelectorAll('.feature-card, .portal-card, .stat-card').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(el);
        });
    </script>
</body>
</html>