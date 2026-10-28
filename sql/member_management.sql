-- =========================================================
-- MEMBER MANAGEMENT MODULE - SQL SCHEMA (Step 6)
-- Gym Management System
-- =========================================================

-- --------------------------------------------------------
-- Table: members
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `members` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `member_code` VARCHAR(20) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `gender` ENUM('Male','Female','Other') DEFAULT 'Male',
  `dob` DATE DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `address` TEXT DEFAULT NULL,
  `profile_picture` VARCHAR(255) DEFAULT 'assets/img/default-avatar.png',
  `membership_plan_id` INT(11) DEFAULT NULL,
  `join_date` DATE NOT NULL,
  `expiry_date` DATE DEFAULT NULL,
  `status` ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `member_code` (`member_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: member_medical_info
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `member_medical_info` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `member_id` INT(11) NOT NULL,
  `blood_group` VARCHAR(5) DEFAULT NULL,
  `height_cm` DECIMAL(5,2) DEFAULT NULL,
  `weight_kg` DECIMAL(5,2) DEFAULT NULL,
  `medical_conditions` TEXT DEFAULT NULL,
  `allergies` TEXT DEFAULT NULL,
  `medications` TEXT DEFAULT NULL,
  `doctor_name` VARCHAR(100) DEFAULT NULL,
  `doctor_phone` VARCHAR(20) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `member_id` (`member_id`),
  CONSTRAINT `fk_medical_member` FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: member_emergency_contacts
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `member_emergency_contacts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `member_id` INT(11) NOT NULL,
  `contact_name` VARCHAR(100) NOT NULL,
  `relationship` VARCHAR(50) DEFAULT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `alt_phone` VARCHAR(20) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `member_id` (`member_id`),
  CONSTRAINT `fk_emergency_member` FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: membership_plans (referenced by members, minimal
-- version included so this module works standalone)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `membership_plans` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `plan_name` VARCHAR(100) NOT NULL,
  `duration_months` INT(11) NOT NULL DEFAULT 1,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `membership_plans` (`plan_name`, `duration_months`, `price`) VALUES
('Monthly', 1, 30.00),
('Quarterly', 3, 80.00),
('Half-Yearly', 6, 150.00),
('Annual', 12, 280.00)
ON DUPLICATE KEY UPDATE plan_name = VALUES(plan_name);

-- --------------------------------------------------------
-- Table: membership_history
-- Logs every membership action (join, renew, upgrade,
-- downgrade, freeze, cancel) for a member
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `membership_history` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `member_id` INT(11) NOT NULL,
  `plan_id` INT(11) DEFAULT NULL,
  `action` ENUM('Joined','Renewed','Upgraded','Downgraded','Frozen','Cancelled','Status Changed') NOT NULL,
  `start_date` DATE DEFAULT NULL,
  `end_date` DATE DEFAULT NULL,
  `remarks` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `member_id` (`member_id`),
  CONSTRAINT `fk_history_member` FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Sample data (optional - safe to remove)
-- --------------------------------------------------------
-- INSERT INTO members (member_code, full_name, gender, dob, email, phone, address, membership_plan_id, join_date, expiry_date, status)
-- VALUES ('GYM0001', 'John Carter', 'Male', '1995-04-12', 'john@example.com', '9876543210', '123 Main St', 1, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 MONTH), 'Active');
