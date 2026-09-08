<?php
session_start();
if (!isset($_SESSION['client_id'])) {
    header("Location: login.php");
    exit;
}
include '../db.php';

// Get search and filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$specialization_filter = isset($_GET['specialization']) ? trim($_GET['specialization']) : '';

// Get all specializations for filter
$specializations = $conn->query("SELECT DISTINCT specialization FROM doctors ORDER BY specialization");

// Build query
$query = "SELECT d.*, 
          (SELECT AVG(rating) FROM reviews WHERE doctor_id = d.id) as avg_rating,
          (SELECT COUNT(*) FROM reviews WHERE doctor_id = d.id) as review_count
          FROM doctors d WHERE 1=1";

if ($search) {
    $search_param = "%" . $conn->real_escape_string($search) . "%";
    $query .= " AND (d.name LIKE '$search_param' OR d.specialization LIKE '$search_param')";
}

if ($specialization_filter) {
    $spec_param = $conn->real_escape_string($specialization_filter);
    $query .= " AND d.specialization = '$spec_param'";
}

$query .= " ORDER BY d.name";
$doctors = $conn->query($query);

// Get client info
$client_id = $_SESSION['client_id'];
$client = $conn->query("SELECT name FROM clients WHERE id = $client_id")->fetch_assoc();
$initials = strtoupper(substr($client['name'], 0, 1));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Doctors - MedAppoint</title>
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
            max-width: 1400px;
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
        
        .header-nav {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .nav-link-custom {
            color: var(--text-secondary);
            padding: 0.5rem 1rem;
            border-radius: var(--radius-md);
            transition: var(--transition-normal);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .nav-link-custom:hover,
        .nav-link-custom.active {
            color: var(--text-primary);
            background: var(--glass-bg);
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding-left: 1rem;
            border-left: 1px solid var(--glass-border);
        }
        
        .user-avatar {
            width: 42px;
            height: 42px;
            background: var(--primary-gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
        
        .btn-logout {
            background: rgba(239, 68, 68, 0.1);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.2);
            padding: 0.5rem 1rem;
            border-radius: var(--radius-md);
            text-decoration: none;
            transition: var(--transition-normal);
        }
        
        .btn-logout:hover {
            background: rgba(239, 68, 68, 0.2);
            color: #f87171;
        }
        
        .page-main {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .page-title {
            margin-bottom: 2rem;
        }
        
        .page-title h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .page-title h1 span {
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        /* Search & Filter Section */
        .search-section {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .search-form {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .search-input-group {
            flex: 1;
            min-width: 250px;
            position: relative;
        }
        
        .search-input-group i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }
        
        .search-input-group input {
            width: 100%;
            padding: 0.875rem 1rem 0.875rem 2.75rem;
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-md);
            color: var(--text-primary);
            transition: var(--transition-normal);
        }
        
        .search-input-group input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.25);
            outline: none;
        }
        
        .filter-select {
            min-width: 200px;
            padding: 0.875rem 1rem;
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-md);
            color: var(--text-primary);
        }
        
        .filter-select option {
            background: #1a1a2e;
        }
        
        /* Doctor Cards */
        .doctors-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 1.5rem;
        }
        
        .doctor-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            overflow: hidden;
            transition: var(--transition-normal);
        }
        
        .doctor-card:hover {
            transform: translateY(-8px);
            border-color: rgba(102, 126, 234, 0.4);
            box-shadow: 0 20px 40px rgba(102, 126, 234, 0.2);
        }
        
        .doctor-header {
            background: var(--primary-gradient);
            padding: 1.5rem;
            text-align: center;
            position: relative;
        }
        
        .doctor-avatar {
            width: 90px;
            height: 90px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 2.5rem;
            color: var(--primary-color);
            border: 4px solid rgba(255,255,255,0.3);
        }
        
        .doctor-header h3 {
            color: white;
            margin: 0;
            font-size: 1.25rem;
        }
        
        .doctor-specialization {
            color: rgba(255,255,255,0.9);
            font-size: 0.9rem;
            margin-top: 0.25rem;
        }
        
        .doctor-body {
            padding: 1.5rem;
        }
        
        .doctor-info {
            margin-bottom: 1rem;
        }
        
        .doctor-info-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
            color: var(--text-secondary);
        }
        
        .doctor-info-item i {
            width: 20px;
            color: var(--primary-color);
        }
        
        .doctor-rating {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .stars {
            color: #fbbf24;
        }
        
        .rating-count {
            color: var(--text-muted);
            font-size: 0.85rem;
        }
        
        .doctor-actions {
            display: flex;
            gap: 0.75rem;
        }
        
        .doctor-actions .btn {
            flex: 1;
        }
        
        /* No Results */
        .no-results {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--glass-bg);
            border-radius: var(--radius-lg);
        }
        
        .no-results i {
            font-size: 4rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
        }
        
        .no-results h3 {
            margin-bottom: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .header-nav {
                display: none;
            }
            
            .search-form {
                flex-direction: column;
            }
            
            .doctors-grid {
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
            
            <nav class="header-nav">
                <a href="dashboard.php" class="nav-link-custom">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="view_doctors.php" class="nav-link-custom active">
                    <i class="fas fa-user-md"></i> Doctors
                </a>
                <a href="view_status.php" class="nav-link-custom">
                    <i class="fas fa-clipboard-list"></i> My Appointments
                </a>
            </nav>
            
            <div class="user-menu">
                <div class="user-avatar"><?php echo $initials; ?></div>
                <a href="../logout.php" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="page-main">
        <div class="page-title">
            <h1>Find Your <span>Doctor</span></h1>
            <p class="text-secondary">Browse our network of qualified healthcare professionals</p>
        </div>

        <!-- Search & Filter -->
        <div class="search-section">
            <form class="search-form" method="GET">
                <div class="search-input-group">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" placeholder="Search by name or specialization..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <select name="specialization" class="filter-select">
                    <option value="">All Specializations</option>
                    <?php while ($spec = $specializations->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($spec['specialization']); ?>"
                                <?php echo $specialization_filter === $spec['specialization'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($spec['specialization']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search me-2"></i>Search
                </button>
                
                <?php if ($search || $specialization_filter): ?>
                    <a href="view_doctors.php" class="btn btn-secondary">
                        <i class="fas fa-times me-2"></i>Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Doctors Grid -->
        <?php if ($doctors && $doctors->num_rows > 0): ?>
            <div class="doctors-grid">
                <?php while ($doctor = $doctors->fetch_assoc()): ?>
                    <div class="doctor-card">
                        <div class="doctor-header">
                            <div class="doctor-avatar">
                                <i class="fas fa-user-md"></i>
                            </div>
                            <h3>Dr. <?php echo htmlspecialchars($doctor['name']); ?></h3>
                            <div class="doctor-specialization">
                                <?php echo htmlspecialchars($doctor['specialization']); ?>
                            </div>
                        </div>
                        
                        <div class="doctor-body">
                            <div class="doctor-info">
                                <div class="doctor-info-item">
                                    <i class="fas fa-envelope"></i>
                                    <span><?php echo htmlspecialchars($doctor['email']); ?></span>
                                </div>
                                <div class="doctor-info-item">
                                    <i class="fas fa-phone"></i>
                                    <span><?php echo htmlspecialchars($doctor['phone']); ?></span>
                                </div>
                            </div>
                            
                            <div class="doctor-rating">
                                <div class="stars">
                                    <?php 
                                    $rating = round($doctor['avg_rating'] ?? 0);
                                    for ($i = 1; $i <= 5; $i++) {
                                        echo $i <= $rating ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                                    }
                                    ?>
                                </div>
                                <span class="rating-count">(<?php echo $doctor['review_count'] ?? 0; ?> reviews)</span>
                            </div>
                            
                            <div class="doctor-actions">
                                <a href="doctor_profile.php?id=<?php echo $doctor['id']; ?>" class="btn btn-secondary">
                                    <i class="fas fa-eye me-1"></i>Profile
                                </a>
                                <a href="book_appointment.php?doctor_id=<?php echo $doctor['id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-calendar-plus me-1"></i>Book
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-results">
                <i class="fas fa-user-md"></i>
                <h3>No Doctors Found</h3>
                <p class="text-secondary">Try adjusting your search or filter criteria</p>
                <a href="view_doctors.php" class="btn btn-primary mt-3">
                    <i class="fas fa-refresh me-2"></i>View All Doctors
                </a>
            </div>
        <?php endif; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
