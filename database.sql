CREATE DATABASE IF NOT EXISTS `doctor_appointment_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `doctor_appointment_db`;

-- Users Table
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `phone` VARCHAR(20) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'doctor', 'patient') NOT NULL DEFAULT 'patient',
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Doctors Table
CREATE TABLE IF NOT EXISTS `doctors` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE,
  `specialization` VARCHAR(100) NOT NULL,
  `qualification` VARCHAR(100) NOT NULL,
  `consultation_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `experience` INT NOT NULL DEFAULT 0,
  `available_days` VARCHAR(100) NOT NULL DEFAULT 'Mon,Tue,Wed,Thu,Fri',
  `available_time` VARCHAR(100) NOT NULL DEFAULT '09:00 AM - 05:00 PM',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Patients Table
CREATE TABLE IF NOT EXISTS `patients` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE,
  `date_of_birth` DATE NULL,
  `gender` ENUM('Male', 'Female', 'Other') NULL,
  `address` TEXT NULL,
  `blood_group` VARCHAR(10) NULL,
  `emergency_contact` VARCHAR(20) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Appointments Table
CREATE TABLE IF NOT EXISTS `appointments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `patient_id` INT NOT NULL,
  `doctor_id` INT NOT NULL,
  `appointment_date` DATE NOT NULL,
  `appointment_time` TIME NOT NULL,
  `reason` TEXT NOT NULL,
  `notes` TEXT NULL,
  `status` ENUM('Pending', 'Confirmed', 'Completed', 'Canceled by Patient', 'Canceled by Doctor', 'Rejected') NOT NULL DEFAULT 'Pending',
  `canceled_by` VARCHAR(50) NULL,
  `cancellation_reason` TEXT NULL,
  `canceled_at` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `doctor_slot_unique` (`doctor_id`, `appointment_date`, `appointment_time`)
) ENGINE=InnoDB;

-- Prescriptions Table
CREATE TABLE IF NOT EXISTS `prescriptions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `prescription_number` VARCHAR(50) NOT NULL UNIQUE,
  `appointment_id` INT NOT NULL UNIQUE,
  `patient_id` INT NOT NULL,
  `doctor_id` INT NOT NULL,
  `diagnosis` TEXT NOT NULL,
  `symptoms` TEXT NOT NULL,
  `doctor_notes` TEXT NULL,
  `prescription_date` DATE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`appointment_id`) REFERENCES `appointments`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Prescription Medicines Table
CREATE TABLE IF NOT EXISTS `prescription_medicines` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `prescription_id` INT NOT NULL,
  `medicine_name` VARCHAR(150) NOT NULL,
  `dosage` VARCHAR(100) NOT NULL,
  `frequency` VARCHAR(100) NOT NULL,
  `duration` VARCHAR(100) NOT NULL,
  `instructions` VARCHAR(255) NULL,
  FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Bills Table
CREATE TABLE IF NOT EXISTS `bills` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `bill_number` VARCHAR(50) NOT NULL UNIQUE,
  `appointment_id` INT NOT NULL UNIQUE,
  `patient_id` INT NOT NULL,
  `doctor_id` INT NOT NULL,
  `consultation_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `medicine_charges` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `test_charges` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `other_charges` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `tax` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `grand_total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `payment_status` ENUM('Pending', 'Paid', 'Partially Paid') NOT NULL DEFAULT 'Pending',
  `payment_method` ENUM('Cash', 'Card', 'UPI', 'Online') NOT NULL DEFAULT 'Cash',
  `bill_date` DATE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`appointment_id`) REFERENCES `appointments`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Default Demo Data (Password for all accounts: admin123 / password123)
-- Hash generated via password_hash('password123', PASSWORD_DEFAULT)
INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password`, `role`, `status`) VALUES
(1, 'System Administrator', 'admin@medicare.com', '9876543210', '$2y$10$wU05Nq3E2xVp7Qj67lC32eG4e69b0B7vO5pTzQYk.VfH6Vf4K1iQW', 'admin', 'active'),
(2, 'Dr. Sarah Jenkins', 'sarah.doctor@medicare.com', '9876543211', '$2y$10$wU05Nq3E2xVp7Qj67lC32eG4e69b0B7vO5pTzQYk.VfH6Vf4K1iQW', 'doctor', 'active'),
(3, 'Dr. Robert Chen', 'robert.doctor@medicare.com', '9876543212', '$2y$10$wU05Nq3E2xVp7Qj67lC32eG4e69b0B7vO5pTzQYk.VfH6Vf4K1iQW', 'doctor', 'active'),
(4, 'John Doe', 'john.patient@medicare.com', '9876543213', '$2y$10$wU05Nq3E2xVp7Qj67lC32eG4e69b0B7vO5pTzQYk.VfH6Vf4K1iQW', 'patient', 'active');

INSERT INTO `doctors` (`id`, `user_id`, `specialization`, `qualification`, `consultation_fee`, `experience`, `available_days`, `available_time`) VALUES
(1, 2, 'Cardiologist', 'MD, FACC', 150.00, 12, 'Mon,Tue,Wed,Thu,Fri', '09:00 AM - 01:00 PM'),
(2, 3, 'General Physician', 'MBBS, MD', 80.00, 8, 'Mon,Wed,Fri,Sat', '02:00 PM - 07:00 PM');

INSERT INTO `patients` (`id`, `user_id`, `date_of_birth`, `gender`, `address`, `blood_group`, `emergency_contact`) VALUES
(1, 4, '1990-05-15', 'Male', '742 Evergreen Terrace, Springfield', 'O+', '9876543299');
