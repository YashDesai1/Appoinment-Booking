-- MedAppoint Database Export
-- Database: `appointment_system`
-- --------------------------------------------------------

CREATE DATABASE IF NOT EXISTS `appointment_system` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `appointment_system`;

-- --------------------------------------------------------
-- Table structure for table `admins`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default admin account (username: admin, password: admin123)
INSERT INTO `admins` (`id`, `username`, `password`, `created_at`) VALUES
(1, 'admin', '$2y$10$0FL3J4hja/Yki4zuwaloMO.OrY.B/pL2aBD2hv77Bb2XrO6cRa6cm', CURRENT_TIMESTAMP)
ON DUPLICATE KEY UPDATE `id`=`id`;

-- --------------------------------------------------------
-- Table structure for table `doctors`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `doctors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `experience_years` int(11) DEFAULT 0,
  `consultation_fee` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample doctors data
INSERT INTO `doctors` (`id`, `name`, `specialization`, `email`, `phone`, `bio`, `experience_years`, `consultation_fee`, `created_at`) VALUES
(1, 'Dr. Sarah Johnson', 'Cardiologist', 'sarah.johnson@medappoint.com', '9876543210', 'Expert in heart diseases and cardiovascular care.', 15, 1500.00, CURRENT_TIMESTAMP),
(2, 'Dr. Michael Chen', 'Dermatologist', 'michael.chen@medappoint.com', '9876543211', 'Specializing in skin care and cosmetic dermatology.', 10, 1200.00, CURRENT_TIMESTAMP),
(3, 'Dr. Emily Williams', 'Pediatrician', 'emily.williams@medappoint.com', '9876543212', 'Dedicated to children\'s health and development.', 12, 1000.00, CURRENT_TIMESTAMP),
(4, 'Dr. James Brown', 'Orthopedic Surgeon', 'james.brown@medappoint.com', '9876543213', 'Expert in bone and joint surgeries.', 18, 2000.00, CURRENT_TIMESTAMP),
(5, 'Dr. Lisa Anderson', 'Neurologist', 'lisa.anderson@medappoint.com', '9876543214', 'Specialist in brain and nervous system disorders.', 14, 1800.00, CURRENT_TIMESTAMP),
(6, 'Dr. Robert Taylor', 'General Physician', 'robert.taylor@medappoint.com', '9876543215', 'Comprehensive primary care for all ages.', 8, 800.00, CURRENT_TIMESTAMP)
ON DUPLICATE KEY UPDATE `id`=`id`;

-- --------------------------------------------------------
-- Table structure for table `clients`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `clients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample client data (john@example.com / john123)
INSERT INTO `clients` (`id`, `name`, `email`, `phone`, `password`, `address`, `date_of_birth`, `gender`, `created_at`) VALUES
(1, 'John Doe', 'john@example.com', '9876543210', '$2y$10$NeH/ATagOTkNyM/XY44.9Oa1u2Jry8YtuBrkkTgdS.dXb8lgGbv9a', '123 Main Street', '1990-01-01', 'male', CURRENT_TIMESTAMP)
ON DUPLICATE KEY UPDATE `id`=`id`;

-- --------------------------------------------------------
-- Table structure for table `schedules`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `doctor_id` int(11) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `available` tinyint(1) DEFAULT 1,
  `max_patients` int(11) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `doctor_id` (`doctor_id`),
  CONSTRAINT `schedules_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table structure for table `appointments`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `appointments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) DEFAULT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `schedule_id` int(11) DEFAULT NULL,
  `status` enum('pending','approved','rejected','cancelled','completed') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `booked_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  KEY `doctor_id` (`doctor_id`),
  KEY `schedule_id` (`schedule_id`),
  CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_ibfk_3` FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table structure for table `reviews`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) DEFAULT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  KEY `doctor_id` (`doctor_id`),
  KEY `appointment_id` (`appointment_id`),
  CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table structure for table `notifications`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `user_type` enum('client','admin') DEFAULT 'client',
  `title` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
