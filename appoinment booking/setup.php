<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "appointment_system";

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) === TRUE) {
    echo "<div style='padding: 10px; margin: 5px; background: #d4edda; color: #155724; border-radius: 5px;'>✅ Database created successfully</div>";
} else {
    echo "<div style='padding: 10px; margin: 5px; background: #f8d7da; color: #721c24; border-radius: 5px;'>❌ Error creating database: " . $conn->error . "</div>";
}

$conn->select_db($dbname);

// Create tables
$tables = [
    "CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE,
        password VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS doctors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100),
        specialization VARCHAR(100),
        email VARCHAR(100) UNIQUE,
        phone VARCHAR(20),
        bio TEXT,
        experience_years INT DEFAULT 0,
        consultation_fee DECIMAL(10,2) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS clients (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100),
        email VARCHAR(100) UNIQUE,
        phone VARCHAR(20),
        password VARCHAR(255),
        address TEXT,
        date_of_birth DATE,
        gender ENUM('male', 'female', 'other'),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS schedules (
        id INT AUTO_INCREMENT PRIMARY KEY,
        doctor_id INT,
        date DATE,
        start_time TIME,
        end_time TIME,
        available BOOLEAN DEFAULT 1,
        max_patients INT DEFAULT 1,
        FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS appointments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT,
        doctor_id INT,
        schedule_id INT,
        status ENUM('pending', 'approved', 'rejected', 'cancelled', 'completed') DEFAULT 'pending',
        notes TEXT,
        booked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
        FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
        FOREIGN KEY (schedule_id) REFERENCES schedules(id) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT,
        doctor_id INT,
        appointment_id INT,
        rating INT CHECK (rating >= 1 AND rating <= 5),
        comment TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
        FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
        FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        user_type ENUM('client', 'admin') DEFAULT 'client',
        title VARCHAR(255),
        message TEXT,
        is_read BOOLEAN DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )"
];

$table_names = ['admins', 'doctors', 'clients', 'schedules', 'appointments', 'reviews', 'notifications'];

foreach ($tables as $index => $table) {
    if ($conn->query($table) === TRUE) {
        echo "<div style='padding: 10px; margin: 5px; background: #d4edda; color: #155724; border-radius: 5px;'>✅ Table '{$table_names[$index]}' created successfully</div>";
    } else {
        echo "<div style='padding: 10px; margin: 5px; background: #f8d7da; color: #721c24; border-radius: 5px;'>❌ Error creating table '{$table_names[$index]}': " . $conn->error . "</div>";
    }
}

// Insert default admin
$admin_pass = password_hash("admin123", PASSWORD_DEFAULT);
$sql = "INSERT IGNORE INTO admins (username, password) VALUES ('admin', '$admin_pass')";
if ($conn->query($sql)) {
    echo "<div style='padding: 10px; margin: 5px; background: #cce5ff; color: #004085; border-radius: 5px;'>ℹ️ Default admin account ready (username: admin, password: admin123)</div>";
}

// Insert sample doctors if none exist
$check_doctors = $conn->query("SELECT COUNT(*) as count FROM doctors");
$doctor_count = $check_doctors->fetch_assoc()['count'];

if ($doctor_count == 0) {
    $sample_doctors = [
        ['Dr. Sarah Johnson', 'Cardiologist', 'sarah.johnson@medappoint.com', '9876543210', 'Expert in heart diseases and cardiovascular care.', 15, 1500],
        ['Dr. Michael Chen', 'Dermatologist', 'michael.chen@medappoint.com', '9876543211', 'Specializing in skin care and cosmetic dermatology.', 10, 1200],
        ['Dr. Emily Williams', 'Pediatrician', 'emily.williams@medappoint.com', '9876543212', 'Dedicated to children\'s health and development.', 12, 1000],
        ['Dr. James Brown', 'Orthopedic Surgeon', 'james.brown@medappoint.com', '9876543213', 'Expert in bone and joint surgeries.', 18, 2000],
        ['Dr. Lisa Anderson', 'Neurologist', 'lisa.anderson@medappoint.com', '9876543214', 'Specialist in brain and nervous system disorders.', 14, 1800],
        ['Dr. Robert Taylor', 'General Physician', 'robert.taylor@medappoint.com', '9876543215', 'Comprehensive primary care for all ages.', 8, 800]
    ];
    
    $stmt = $conn->prepare("INSERT INTO doctors (name, specialization, email, phone, bio, experience_years, consultation_fee) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($sample_doctors as $doc) {
        $stmt->bind_param("sssssid", $doc[0], $doc[1], $doc[2], $doc[3], $doc[4], $doc[5], $doc[6]);
        $stmt->execute();
    }
    
    echo "<div style='padding: 10px; margin: 5px; background: #d4edda; color: #155724; border-radius: 5px;'>✅ Sample doctors added successfully</div>";
    
    // Add sample schedules for doctors
    $doctors_result = $conn->query("SELECT id FROM doctors");
    $today = date('Y-m-d');
    
    while ($doctor = $doctors_result->fetch_assoc()) {
        for ($i = 0; $i < 7; $i++) {
            $date = date('Y-m-d', strtotime("+$i days"));
            $conn->query("INSERT INTO schedules (doctor_id, date, start_time, end_time) VALUES ({$doctor['id']}, '$date', '09:00:00', '10:00:00')");
            $conn->query("INSERT INTO schedules (doctor_id, date, start_time, end_time) VALUES ({$doctor['id']}, '$date', '10:00:00', '11:00:00')");
            $conn->query("INSERT INTO schedules (doctor_id, date, start_time, end_time) VALUES ({$doctor['id']}, '$date', '14:00:00', '15:00:00')");
            $conn->query("INSERT INTO schedules (doctor_id, date, start_time, end_time) VALUES ({$doctor['id']}, '$date', '15:00:00', '16:00:00')");
        }
    }
    
    echo "<div style='padding: 10px; margin: 5px; background: #d4edda; color: #155724; border-radius: 5px;'>✅ Sample schedules added for next 7 days</div>";
}

echo "<br>";
echo "<div style='padding: 20px; margin: 10px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 10px; text-align: center;'>";
echo "<h2 style='margin: 0 0 10px 0;'>🎉 Setup Complete!</h2>";
echo "<p style='margin: 0;'>Your MedAppoint system is ready to use.</p>";
echo "<br>";
echo "<a href='index.php' style='display: inline-block; padding: 10px 25px; background: white; color: #667eea; text-decoration: none; border-radius: 5px; font-weight: bold;'>Go to Homepage →</a>";
echo "</div>";

$conn->close();
?>