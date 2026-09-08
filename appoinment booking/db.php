<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "appointment_system";

// Attempt initial connection
$conn = @new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    // Try connecting without dbname to auto-create database if it doesn't exist
    $conn = new mysqli($servername, $username, $password);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    $sql = "CREATE DATABASE IF NOT EXISTS `$dbname`";
    $conn->query($sql);
    $conn->select_db($dbname);
}

// Function to initialize tables
if (!function_exists('init_appointment_system_db')) {
    function init_appointment_system_db($conn) {
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
        
        foreach ($tables as $t) {
            $conn->query($t);
        }
        
        // Insert default admin
        $admin_pass = password_hash("admin123", PASSWORD_DEFAULT);
        $conn->query("INSERT IGNORE INTO admins (username, password) VALUES ('admin', '$admin_pass')");
        
        // Insert sample doctors if none exist
        $check_docs = $conn->query("SELECT COUNT(*) as count FROM doctors");
        if ($check_docs && $check_docs->fetch_assoc()['count'] == 0) {
            $sample_doctors = [
                ['Dr. Sarah Johnson', 'Cardiologist', 'sarah.johnson@medappoint.com', '9876543210', 'Expert in heart diseases and cardiovascular care.', 15, 1500],
                ['Dr. Michael Chen', 'Dermatologist', 'michael.chen@medappoint.com', '9876543211', 'Specializing in skin care and cosmetic dermatology.', 10, 1200],
                ['Dr. Emily Williams', 'Pediatrician', 'emily.williams@medappoint.com', '9876543212', 'Dedicated to children\'s health and development.', 12, 1000],
                ['Dr. James Brown', 'Orthopedic Surgeon', 'james.brown@medappoint.com', '9876543213', 'Expert in bone and joint surgeries.', 18, 2000],
                ['Dr. Lisa Anderson', 'Neurologist', 'lisa.anderson@medappoint.com', '9876543214', 'Specialist in brain and nervous system disorders.', 14, 1800],
                ['Dr. Robert Taylor', 'General Physician', 'robert.taylor@medappoint.com', '9876543215', 'Comprehensive primary care for all ages.', 8, 800]
            ];
            
            $stmt = $conn->prepare("INSERT INTO doctors (name, specialization, email, phone, bio, experience_years, consultation_fee) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                foreach ($sample_doctors as $doc) {
                    $stmt->bind_param("sssssid", $doc[0], $doc[1], $doc[2], $doc[3], $doc[4], $doc[5], $doc[6]);
                    $stmt->execute();
                }
            }
            
            $doctors_result = $conn->query("SELECT id FROM doctors");
            if ($doctors_result) {
                while ($doctor = $doctors_result->fetch_assoc()) {
                    for ($i = 0; $i < 7; $i++) {
                        $date = date('Y-m-d', strtotime("+$i days"));
                        $conn->query("INSERT INTO schedules (doctor_id, date, start_time, end_time) VALUES ({$doctor['id']}, '$date', '09:00:00', '10:00:00')");
                        $conn->query("INSERT INTO schedules (doctor_id, date, start_time, end_time) VALUES ({$doctor['id']}, '$date', '10:00:00', '11:00:00')");
                        $conn->query("INSERT INTO schedules (doctor_id, date, start_time, end_time) VALUES ({$doctor['id']}, '$date', '14:00:00', '15:00:00')");
                        $conn->query("INSERT INTO schedules (doctor_id, date, start_time, end_time) VALUES ({$doctor['id']}, '$date', '15:00:00', '16:00:00')");
                    }
                }
            }
        }
    }
}

// Ensure tables exist
$check_tables = @$conn->query("SHOW TABLES LIKE 'admins'");
if (!$check_tables || $check_tables->num_rows == 0) {
    init_appointment_system_db($conn);
}
?>