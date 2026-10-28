-- =========================================================
-- TRAINER MANAGEMENT MODULE - SQL SCHEMA (Step 7)
-- Gym Management System
-- =========================================================

-- --------------------------------------------------------
-- Table: trainers
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `trainers` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `trainer_code` VARCHAR(20) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `gender` ENUM('Male','Female','Other') DEFAULT 'Male',
  `dob` DATE DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `address` TEXT DEFAULT NULL,
  `profile_picture` VARCHAR(255) DEFAULT 'assets/img/default-avatar.png',
  `specialization` VARCHAR(150) DEFAULT NULL,
  `experience_years` DECIMAL(4,1) DEFAULT 0,
  `certification` VARCHAR(255) DEFAULT NULL,
  `monthly_salary` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `joining_date` DATE NOT NULL,
  `status` ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `trainer_code` (`trainer_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: trainer_schedule
-- Weekly recurring schedule slots per trainer
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `trainer_schedule` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `trainer_id` INT(11) NOT NULL,
  `day_of_week` ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `session_type` VARCHAR(100) DEFAULT NULL,
  `notes` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `trainer_id` (`trainer_id`),
  CONSTRAINT `fk_schedule_trainer` FOREIGN KEY (`trainer_id`) REFERENCES `trainers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: trainer_salary
-- Monthly salary / payout records per trainer
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `trainer_salary` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `trainer_id` INT(11) NOT NULL,
  `month_year` VARCHAR(7) NOT NULL COMMENT 'Format: YYYY-MM',
  `base_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `bonus` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `deduction` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `net_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `payment_date` DATE DEFAULT NULL,
  `payment_status` ENUM('Paid','Pending') NOT NULL DEFAULT 'Pending',
  `remarks` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `trainer_id` (`trainer_id`),
  UNIQUE KEY `trainer_month` (`trainer_id`, `month_year`),
  CONSTRAINT `fk_salary_trainer` FOREIGN KEY (`trainer_id`) REFERENCES `trainers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: trainer_attendance
-- Daily attendance log per trainer
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `trainer_attendance` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `trainer_id` INT(11) NOT NULL,
  `attendance_date` DATE NOT NULL,
  `status` ENUM('Present','Absent','Half Day','Leave') NOT NULL DEFAULT 'Present',
  `check_in` TIME DEFAULT NULL,
  `check_out` TIME DEFAULT NULL,
  `remarks` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `trainer_id` (`trainer_id`),
  UNIQUE KEY `trainer_date` (`trainer_id`, `attendance_date`),
  CONSTRAINT `fk_attendance_trainer` FOREIGN KEY (`trainer_id`) REFERENCES `trainers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Alter: members
-- Link each member to an assigned trainer (nullable)
-- --------------------------------------------------------
ALTER TABLE `members`
  ADD COLUMN IF NOT EXISTS `assigned_trainer_id` INT(11) DEFAULT NULL AFTER `membership_plan_id`,
  ADD CONSTRAINT `fk_member_trainer` FOREIGN KEY (`assigned_trainer_id`) REFERENCES `trainers`(`id`) ON DELETE SET NULL;

-- --------------------------------------------------------
-- Sample data (optional - safe to remove)
-- --------------------------------------------------------
-- INSERT INTO trainers (trainer_code, full_name, gender, phone, specialization, experience_years, monthly_salary, joining_date, status)
-- VALUES ('TRN0001', 'Alex Rivera', 'Male', '9998887771', 'Strength & Conditioning', 5.5, 1200.00, CURDATE(), 'Active');
