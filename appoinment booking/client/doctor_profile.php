<?php
session_start();
if (!isset($_SESSION['client_id'])) {
    header("Location: login.php");
    exit;
}
include '../db.php';

$doctor_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($doctor_id <= 0) {
    header("Location: view_doctors.php");
    exit;
}

$client_id = $_SESSION['client_id'];

// Get doctor details
$doctor = $conn->query("SELECT * FROM doctors WHERE id = $doctor_id")->fetch_assoc();

if (!$doctor) {
    header("Location: view_doctors.php");
    exit;
}

// Handle review submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_review'])) {
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);
    $appointment_id = intval($_POST['appointment_id']);
    
    if ($rating >= 1 && $rating <= 5) {
        // Check if already reviewed
        $check = $conn->query("SELECT id FROM reviews WHERE client_id = $client_id AND doctor_id = $doctor_id AND appointment_id = $appointment_id");
        if ($check->num_rows == 0) {
            $stmt = $conn->prepare("INSERT INTO reviews (client_id, doctor_id, appointment_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiiis", $client_id, $doctor_id, $appointment_id, $rating, $comment);
            if ($stmt->execute()) {
                $message = "Thank you for your review!";
            }
        } else {
            $message = "You have already reviewed this appointment.";
        }
    }
}

// Get doctor stats
$stats = $conn->query("
    SELECT 
        AVG(rating) as avg_rating,
        COUNT(*) as total_reviews,
        SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
        SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
        SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
        SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
        SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
    FROM reviews WHERE doctor_id = $doctor_id
")->fetch_assoc();

// Get reviews
$reviews = $conn->query("
    SELECT r.*, c.name as client_name 
    FROM reviews r 
    LEFT JOIN clients c ON r.client_id = c.id 
    WHERE r.doctor_id = $doctor_id 
    ORDER BY r.created_at DESC 
    LIMIT 10
");

// Get available schedules
$schedules = $conn->query("
    SELECT * FROM schedules 
    WHERE doctor_id = $doctor_id 
    AND date >= CURDATE() 
    AND available = 1 
    ORDER BY date, start_time 
    LIMIT 10
");

// Get completed appointments for review (by this client)
$completed_appointments = $conn->query("
    SELECT a.id, s.date 
    FROM appointments a 
    LEFT JOIN schedules s ON a.schedule_id = s.id
    WHERE a.client_id = $client_id 
    AND a.doctor_id = $doctor_id 
    AND a.status = 'completed'
    AND a.id NOT IN (SELECT appointment_id FROM reviews WHERE appointment_id IS NOT NULL)
");

// Client info
$client = $conn->query("SELECT name FROM clients WHERE id = $client_id")->fetch_assoc();
$initials = strtoupper(substr($client['name'], 0, 1));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dr. <?php echo htmlspecialchars($doctor['name']); ?> - MedAppoint</title>
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
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
            text-decoration: none;
        }
        
        .back-link:hover {
            color: var(--primary-color);
        }
        
        /* Doctor Profile Card */
        .doctor-profile-card {
            background: var(--primary-gradient);
            border-radius: var(--radius-lg);
            padding: 2.5rem;
            margin-bottom: 2rem;
            display: flex;
            gap: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .doctor-profile-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 400px;
            height: 400px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
        }
        
        .doctor-avatar-large {
            width: 150px;
            height: 150px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: var(--primary-color);
            border: 4px solid rgba(255,255,255,0.3);
            position: relative;
            z-index: 1;
            flex-shrink: 0;
        }
        
        .doctor-info-main {
            position: relative;
            z-index: 1;
        }
        
        .doctor-info-main h1 {
            color: white;
            margin: 0 0 0.5rem;
            font-size: 2rem;
        }
        
        .doctor-specialization {
            color: rgba(255,255,255,0.9);
            font-size: 1.1rem;
            margin-bottom: 1rem;
        }
        
        .doctor-meta {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
        }
        
        .doctor-meta-item {
            color: rgba(255,255,255,0.9);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .rating-summary {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .rating-summary .stars {
            color: #fbbf24;
            font-size: 1.25rem;
        }
        
        .rating-summary .rating-text {
            color: white;
            font-weight: 600;
        }
        
        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }
        
        .content-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .content-card h3 {
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--glass-border);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .content-card h3 i {
            color: var(--primary-color);
        }
        
        /* Rating Bars */
        .rating-breakdown {
            margin-bottom: 1.5rem;
        }
        
        .rating-bar-row {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.5rem;
        }
        
        .rating-bar-label {
            width: 60px;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .rating-bar {
            flex: 1;
            height: 8px;
            background: var(--glass-border);
            border-radius: 4px;
            overflow: hidden;
        }
        
        .rating-bar-fill {
            height: 100%;
            background: var(--primary-gradient);
            border-radius: 4px;
        }
        
        .rating-bar-count {
            width: 30px;
            text-align: right;
            color: var(--text-muted);
            font-size: 0.85rem;
        }
        
        /* Reviews List */
        .review-item {
            padding: 1.25rem;
            background: rgba(255,255,255,0.03);
            border-radius: var(--radius-md);
            margin-bottom: 1rem;
            border: 1px solid var(--glass-border);
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }
        
        .reviewer-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .reviewer-avatar {
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
        
        .reviewer-name {
            font-weight: 500;
        }
        
        .review-date {
            color: var(--text-muted);
            font-size: 0.85rem;
        }
        
        .review-stars {
            color: #fbbf24;
        }
        
        .review-comment {
            color: var(--text-secondary);
            line-height: 1.6;
        }
        
        /* Schedule Card */
        .schedule-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: rgba(255,255,255,0.03);
            border-radius: var(--radius-md);
            margin-bottom: 0.75rem;
            border: 1px solid var(--glass-border);
        }
        
        .schedule-date {
            font-weight: 600;
        }
        
        .schedule-time {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        /* Review Form */
        .review-form {
            background: rgba(102, 126, 234, 0.1);
            border-radius: var(--radius-md);
            padding: 1.5rem;
        }
        
        .star-rating {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .star-rating input {
            display: none;
        }
        
        .star-rating label {
            font-size: 2rem;
            color: var(--glass-border);
            cursor: pointer;
            transition: var(--transition-fast);
        }
        
        .star-rating label:hover,
        .star-rating label:hover ~ label,
        .star-rating input:checked ~ label {
            color: #fbbf24;
        }
        
        .star-rating {
            flex-direction: row-reverse;
            justify-content: flex-end;
        }
        
        @media (max-width: 992px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .doctor-profile-card {
                flex-direction: column;
                text-align: center;
            }
            
            .doctor-avatar-large {
                margin: 0 auto;
            }
            
            .doctor-meta {
                justify-content: center;
            }
            
            .rating-summary {
                justify-content: center;
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
        <a href="view_doctors.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Doctors
        </a>
        
        <?php if ($message): ?>
            <div class="alert alert-success mb-4">
                <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Doctor Profile Card -->
        <div class="doctor-profile-card">
            <div class="doctor-avatar-large">
                <i class="fas fa-user-md"></i>
            </div>
            <div class="doctor-info-main">
                <h1>Dr. <?php echo htmlspecialchars($doctor['name']); ?></h1>
                <div class="doctor-specialization">
                    <i class="fas fa-stethoscope me-2"></i><?php echo htmlspecialchars($doctor['specialization']); ?>
                </div>
                <div class="doctor-meta">
                    <div class="doctor-meta-item">
                        <i class="fas fa-envelope"></i>
                        <?php echo htmlspecialchars($doctor['email']); ?>
                    </div>
                    <div class="doctor-meta-item">
                        <i class="fas fa-phone"></i>
                        <?php echo htmlspecialchars($doctor['phone']); ?>
                    </div>
                    <?php if (!empty($doctor['experience_years'])): ?>
                    <div class="doctor-meta-item">
                        <i class="fas fa-briefcase"></i>
                        <?php echo $doctor['experience_years']; ?> years experience
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($doctor['consultation_fee'])): ?>
                    <div class="doctor-meta-item">
                        <i class="fas fa-rupee-sign"></i>
                        ₹<?php echo number_format($doctor['consultation_fee']); ?> per visit
                    </div>
                    <?php endif; ?>
                </div>
                <div class="rating-summary">
                    <div class="stars">
                        <?php 
                        $avg = round($stats['avg_rating'] ?? 0);
                        for ($i = 1; $i <= 5; $i++) {
                            echo $i <= $avg ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                        }
                        ?>
                    </div>
                    <span class="rating-text">
                        <?php echo $stats['avg_rating'] ? number_format($stats['avg_rating'], 1) : 'No'; ?> 
                        (<?php echo $stats['total_reviews'] ?? 0; ?> reviews)
                    </span>
                </div>
            </div>
        </div>

        <div class="content-grid">
            <div>
                <!-- About -->
                <?php if (!empty($doctor['bio'])): ?>
                <div class="content-card">
                    <h3><i class="fas fa-info-circle"></i> About</h3>
                    <p class="text-secondary"><?php echo nl2br(htmlspecialchars($doctor['bio'])); ?></p>
                </div>
                <?php endif; ?>

                <!-- Reviews -->
                <div class="content-card">
                    <h3><i class="fas fa-star"></i> Patient Reviews</h3>
                    
                    <?php if ($stats['total_reviews'] > 0): ?>
                    <div class="rating-breakdown">
                        <?php
                        $ratings = [
                            5 => $stats['five_star'] ?? 0,
                            4 => $stats['four_star'] ?? 0,
                            3 => $stats['three_star'] ?? 0,
                            2 => $stats['two_star'] ?? 0,
                            1 => $stats['one_star'] ?? 0
                        ];
                        $total = $stats['total_reviews'];
                        foreach ($ratings as $star => $count):
                            $percent = $total > 0 ? ($count / $total) * 100 : 0;
                        ?>
                        <div class="rating-bar-row">
                            <div class="rating-bar-label"><?php echo $star; ?> stars</div>
                            <div class="rating-bar">
                                <div class="rating-bar-fill" style="width: <?php echo $percent; ?>%"></div>
                            </div>
                            <div class="rating-bar-count"><?php echo $count; ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($reviews && $reviews->num_rows > 0): ?>
                        <?php while ($review = $reviews->fetch_assoc()): ?>
                        <div class="review-item">
                            <div class="review-header">
                                <div class="reviewer-info">
                                    <div class="reviewer-avatar">
                                        <?php echo strtoupper(substr($review['client_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div class="reviewer-name"><?php echo htmlspecialchars($review['client_name']); ?></div>
                                        <div class="review-date"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></div>
                                    </div>
                                </div>
                                <div class="review-stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="<?php echo $i <= $review['rating'] ? 'fas' : 'far'; ?> fa-star"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <?php if (!empty($review['comment'])): ?>
                            <div class="review-comment"><?php echo htmlspecialchars($review['comment']); ?></div>
                            <?php endif; ?>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-center text-secondary py-4">No reviews yet. Be the first to review!</p>
                    <?php endif; ?>
                </div>

                <!-- Write Review -->
                <?php if ($completed_appointments && $completed_appointments->num_rows > 0): ?>
                <div class="content-card">
                    <h3><i class="fas fa-pen"></i> Write a Review</h3>
                    <div class="review-form">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label text-secondary">Select Appointment</label>
                                <select name="appointment_id" class="form-select" required>
                                    <?php while ($apt = $completed_appointments->fetch_assoc()): ?>
                                    <option value="<?php echo $apt['id']; ?>">
                                        Appointment on <?php echo date('M d, Y', strtotime($apt['date'])); ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-secondary">Your Rating</label>
                                <div class="star-rating">
                                    <input type="radio" name="rating" id="star5" value="5" required>
                                    <label for="star5"><i class="fas fa-star"></i></label>
                                    <input type="radio" name="rating" id="star4" value="4">
                                    <label for="star4"><i class="fas fa-star"></i></label>
                                    <input type="radio" name="rating" id="star3" value="3">
                                    <label for="star3"><i class="fas fa-star"></i></label>
                                    <input type="radio" name="rating" id="star2" value="2">
                                    <label for="star2"><i class="fas fa-star"></i></label>
                                    <input type="radio" name="rating" id="star1" value="1">
                                    <label for="star1"><i class="fas fa-star"></i></label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-secondary">Your Review (Optional)</label>
                                <textarea name="comment" class="form-control" rows="3" placeholder="Share your experience..."></textarea>
                            </div>
                            <button type="submit" name="submit_review" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>Submit Review
                            </button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div>
                <!-- Book Appointment -->
                <div class="content-card">
                    <h3><i class="fas fa-calendar-plus"></i> Book Appointment</h3>
                    
                    <?php if ($schedules && $schedules->num_rows > 0): ?>
                        <?php while ($slot = $schedules->fetch_assoc()): ?>
                        <div class="schedule-item">
                            <div>
                                <div class="schedule-date"><?php echo date('D, M d', strtotime($slot['date'])); ?></div>
                                <div class="schedule-time">
                                    <?php echo date('h:i A', strtotime($slot['start_time'])); ?> - 
                                    <?php echo date('h:i A', strtotime($slot['end_time'])); ?>
                                </div>
                            </div>
                            <a href="book_appointment.php?doctor_id=<?php echo $doctor_id; ?>&schedule_id=<?php echo $slot['id']; ?>" 
                               class="btn btn-primary btn-sm">
                                Book
                            </a>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-center text-secondary py-3">No available slots</p>
                    <?php endif; ?>
                    
                    <a href="book_appointment.php?doctor_id=<?php echo $doctor_id; ?>" class="btn btn-primary w-100 mt-3">
                        <i class="fas fa-calendar-alt me-2"></i>View All Slots
                    </a>
                </div>

                <!-- Quick Info -->
                <div class="content-card">
                    <h3><i class="fas fa-info"></i> Quick Info</h3>
                    <ul class="list-unstyled mb-0">
                        <li class="d-flex justify-content-between py-2 border-bottom" style="border-color: var(--glass-border) !important;">
                            <span class="text-secondary">Specialization</span>
                            <strong><?php echo htmlspecialchars($doctor['specialization']); ?></strong>
                        </li>
                        <li class="d-flex justify-content-between py-2 border-bottom" style="border-color: var(--glass-border) !important;">
                            <span class="text-secondary">Experience</span>
                            <strong><?php echo $doctor['experience_years'] ?? 'N/A'; ?> years</strong>
                        </li>
                        <li class="d-flex justify-content-between py-2 border-bottom" style="border-color: var(--glass-border) !important;">
                            <span class="text-secondary">Consultation Fee</span>
                            <strong>₹<?php echo number_format($doctor['consultation_fee'] ?? 0); ?></strong>
                        </li>
                        <li class="d-flex justify-content-between py-2">
                            <span class="text-secondary">Total Reviews</span>
                            <strong><?php echo $stats['total_reviews'] ?? 0; ?></strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
