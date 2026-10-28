-- =========================================================
-- FORGE ATHLETIC / GYM MANAGEMENT SYSTEM — FINAL CONSOLIDATED SCHEMA
-- Phase 6 (Final)
--
-- This single file replaces the need to import 5 separate .sql files
-- in a specific order. It concatenates them in the exact dependency
-- order required by their FOREIGN KEY constraints:
--   1. member_management.sql
--   2. trainer_management.sql
--   3. core_and_reports.sql
--   4. public_site.sql
--   5. phase3_modules.sql (Workout / Diet / Class Schedule)
--
-- Create an empty database named gym_system first, then import this
-- single file (phpMyAdmin > Import, or: mysql -u root gym_system < gym_system_final.sql).
--
-- Default login after import: username = admin / password = Admin@123
-- (change this immediately via Forgot Password, or update the admins table)
-- =========================================================


-- ============================================================
-- SOURCE: sql/member_management.sql
-- ============================================================
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


-- ============================================================
-- SOURCE: sql/trainer_management.sql
-- ============================================================
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
-- Written as guarded dynamic SQL (not "ADD COLUMN IF NOT EXISTS")
-- because that clause needs MySQL 8.0.29+ / MariaDB 10.5+, and many
-- XAMPP installs ship older versions where it's a syntax error that
-- HALTS THE ENTIRE IMPORT partway through — silently skipping every
-- table defined further down this file (admins, payments, etc.).
-- --------------------------------------------------------
SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'members' AND COLUMN_NAME = 'assigned_trainer_id'
);
SET @sql := IF(@col_exists = 0,
  'ALTER TABLE `members` ADD COLUMN `assigned_trainer_id` INT(11) DEFAULT NULL AFTER `membership_plan_id`',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_exists := (
  SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'members' AND CONSTRAINT_NAME = 'fk_member_trainer'
);
SET @sql := IF(@fk_exists = 0,
  'ALTER TABLE `members` ADD CONSTRAINT `fk_member_trainer` FOREIGN KEY (`assigned_trainer_id`) REFERENCES `trainers`(`id`) ON DELETE SET NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- --------------------------------------------------------
-- Sample data (optional - safe to remove)
-- --------------------------------------------------------
-- INSERT INTO trainers (trainer_code, full_name, gender, phone, specialization, experience_years, monthly_salary, joining_date, status)
-- VALUES ('TRN0001', 'Alex Rivera', 'Male', '9998887771', 'Strength & Conditioning', 5.5, 1200.00, CURDATE(), 'Active');


-- ============================================================
-- SOURCE: sql/core_and_reports.sql
-- ============================================================
-- =========================================================
-- CORE + REPORTS SUPPORT SCHEMA (Step 15)
-- Gym Management System — database: gym_system
-- Run AFTER member_management.sql and trainer_management.sql
-- =========================================================

-- --------------------------------------------------------
-- Table: admins  (login / auth)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `username` VARCHAR(50) NOT NULL,
  `password` VARCHAR(255) NOT NULL,      -- password_hash() output
  `role` ENUM('super_admin','admin') NOT NULL DEFAULT 'admin',
  `profile_image` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
  `reset_token` VARCHAR(64) DEFAULT NULL,
  `reset_expires` DATETIME DEFAULT NULL,
  `last_login` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admins_email` (`email`),
  UNIQUE KEY `uq_admins_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default login: username = admin / password = Admin@123
-- (hash below is password_hash('Admin@123', PASSWORD_DEFAULT) — change it after first login)
INSERT INTO `admins` (`name`, `email`, `username`, `password`, `role`)
SELECT 'Super Admin', 'admin@forgeathletic.test', 'admin',
       '$2b$10$8tKEBa2M2a/mABsTF.1j5ediAvqIHGdcBRMe4ssRBlmTNnLjheU06', 'super_admin'
WHERE NOT EXISTS (SELECT 1 FROM `admins` WHERE `username` = 'admin');

-- --------------------------------------------------------
-- Table: settings  (single-row key/value gym configuration)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('gym_name', 'Forge Athletic'),
('gym_address', 'Plot 14, Shahrah-e-Faisal, Karachi'),
('gym_phone', '+92 300 123 4567'),
('gym_email', 'info@forgeathletic.test'),
('gym_logo', ''),
('theme_mode', 'light'),
('smtp_host', ''),
('smtp_port', '587'),
('smtp_username', ''),
('smtp_password', ''),
('smtp_encryption', 'tls')
ON DUPLICATE KEY UPDATE setting_key = VALUES(setting_key);

-- --------------------------------------------------------
-- Table: activity_logs
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `admin_id` INT(11) DEFAULT NULL,
  `action` VARCHAR(150) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `admin_id` (`admin_id`),
  CONSTRAINT `fk_log_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: notifications
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(150) NOT NULL,
  `message` VARCHAR(255) DEFAULT NULL,
  `type` ENUM('info','success','warning','danger') NOT NULL DEFAULT 'info',
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: payments  (revenue source — membership payments)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `member_id` INT(11) NOT NULL,
  `plan_id` INT(11) DEFAULT NULL,
  `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` ENUM('Cash','Card','Bank Transfer','Online') NOT NULL DEFAULT 'Cash',
  `payment_status` ENUM('Paid','Pending','Overdue') NOT NULL DEFAULT 'Paid',
  `payment_date` DATE NOT NULL,
  `notes` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `member_id` (`member_id`),
  CONSTRAINT `fk_payment_member` FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payment_plan` FOREIGN KEY (`plan_id`) REFERENCES `membership_plans`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: attendance  (member gym check-ins)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `attendance` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `member_id` INT(11) NOT NULL,
  `check_in` DATETIME NOT NULL,
  `check_out` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `member_id` (`member_id`),
  CONSTRAINT `fk_attendance_member` FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: products  (store / merchandise)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `products` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `category` VARCHAR(80) DEFAULT NULL,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `stock` INT(11) NOT NULL DEFAULT 0,
  `image` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `products` (`name`, `category`, `price`, `stock`) VALUES
('Olympic Barbell 20kg', 'Equipment', 32000.00, 8),
('Iron Plate Set (Pair)', 'Equipment', 18500.00, 15),
('Whey Protein 2kg', 'Nutrition', 9200.00, 40),
('Lifting Belt — Leather', 'Apparel', 6800.00, 22)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- --------------------------------------------------------
-- Table: product_sales
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `product_sales` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `product_id` INT(11) NOT NULL,
  `member_id` INT(11) DEFAULT NULL,
  `quantity` INT(11) NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(10,2) NOT NULL,
  `total` DECIMAL(10,2) NOT NULL,
  `sale_date` DATE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `fk_sale_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sale_member` FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: expenses
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `expenses` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `category` VARCHAR(80) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `expense_date` DATE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Helpful indexes for report queries
-- --------------------------------------------------------
-- Guarded so re-importing this file after fixing an earlier error
-- doesn't fail with "Duplicate key name" on these indexes.
SET @idx_exists := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payments' AND INDEX_NAME='idx_payments_date');
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX idx_payments_date ON payments (payment_date)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='attendance' AND INDEX_NAME='idx_attendance_checkin');
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX idx_attendance_checkin ON attendance (check_in)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='product_sales' AND INDEX_NAME='idx_sales_date');
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX idx_sales_date ON product_sales (sale_date)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='expenses' AND INDEX_NAME='idx_expenses_date');
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX idx_expenses_date ON expenses (expense_date)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- --------------------------------------------------------
-- Sample notifications (safe to delete)
-- --------------------------------------------------------
INSERT INTO notifications (title, message, type) VALUES
('Welcome to Forge Athletic Admin', 'Your gym management system is ready. Explore Reports and Settings from the sidebar.', 'info'),
('Low stock alert', 'Whey Protein 2kg is running low — 6 units left.', 'warning'),
('Backup reminder', 'It has been a while since your last database backup.', 'danger');

-- --------------------------------------------------------
-- OPTIONAL demo transactional data — only inserted if you
-- already have at least one member and one trainer in the
-- database. Safe to delete; purely so Reports have something
-- to display the first time you open them.
-- --------------------------------------------------------
INSERT INTO expenses (category, description, amount, expense_date)
SELECT * FROM (SELECT 'Utilities' AS category, 'Electricity bill' AS description, 45000.00 AS amount, CURDATE() AS expense_date) t
WHERE NOT EXISTS (SELECT 1 FROM expenses);

INSERT INTO expenses (category, description, amount, expense_date)
SELECT * FROM (SELECT 'Maintenance', 'Treadmill servicing', 12000.00, DATE_SUB(CURDATE(), INTERVAL 5 DAY)) t
WHERE (SELECT COUNT(*) FROM expenses) < 2;

INSERT INTO payments (member_id, plan_id, amount, payment_method, payment_status, payment_date)
SELECT m.id, m.membership_plan_id, COALESCE(p.price, 5000), 'Cash', 'Paid', m.join_date
FROM members m LEFT JOIN membership_plans p ON p.id = m.membership_plan_id
WHERE NOT EXISTS (SELECT 1 FROM payments WHERE payments.member_id = m.id)
LIMIT 20;

INSERT INTO attendance (member_id, check_in, check_out)
SELECT m.id, NOW() - INTERVAL FLOOR(RAND()*5) DAY, NULL
FROM members m
WHERE NOT EXISTS (SELECT 1 FROM attendance WHERE attendance.member_id = m.id)
LIMIT 20;

INSERT INTO product_sales (product_id, member_id, quantity, unit_price, total, sale_date)
SELECT pr.id, m.id, 1, pr.price, pr.price, CURDATE() - INTERVAL FLOOR(RAND()*10) DAY
FROM products pr JOIN members m ON m.id = (SELECT id FROM members ORDER BY id LIMIT 1)
WHERE NOT EXISTS (SELECT 1 FROM product_sales)
LIMIT 4;


-- ============================================================
-- SOURCE: sql/public_site.sql
-- ============================================================
-- =========================================================
-- PUBLIC MARKETING SITE - SQL SCHEMA UPDATE
-- Gym Management System — database: gym_system
-- Run AFTER member_management.sql, trainer_management.sql,
-- and core_and_reports.sql.
-- =========================================================

-- --------------------------------------------------------
-- Table: contact_messages ("Contact Us" form submissions)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `subject` VARCHAR(150) DEFAULT NULL,
  `message` TEXT NOT NULL,
  `status` ENUM('New','Read','Replied') NOT NULL DEFAULT 'New',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Enrich membership_plans so the public Pricing page has
-- real descriptions / feature lists / a "featured" plan to
-- highlight (columns are additive - safe to run even if the
-- table already has data from member_management.sql).
-- Written as guarded dynamic SQL for the same reason as the
-- `members.assigned_trainer_id` alter above.
-- --------------------------------------------------------
SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'membership_plans' AND COLUMN_NAME = 'description'
);
SET @sql := IF(@col_exists = 0,
  'ALTER TABLE `membership_plans`
     ADD COLUMN `description` VARCHAR(255) DEFAULT NULL AFTER `plan_name`,
     ADD COLUMN `features` TEXT DEFAULT NULL COMMENT ''Pipe-separated list, e.g. Gym Access|Locker|1 Free Class'',
     ADD COLUMN `is_featured` TINYINT(1) NOT NULL DEFAULT 0',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE `membership_plans` SET
  `description` = 'Perfect for getting started',
  `features` = 'Full gym floor access|Cardio & strength equipment|Locker facility',
  `is_featured` = 0
WHERE `plan_name` = 'Monthly';

UPDATE `membership_plans` SET
  `description` = 'Great value for regular training',
  `features` = 'Everything in Monthly|Group fitness classes|1 free trainer session',
  `is_featured` = 0
WHERE `plan_name` = 'Quarterly';

UPDATE `membership_plans` SET
  `description` = 'Our most popular plan',
  `features` = 'Everything in Quarterly|Personal trainer sessions|Diet consultation|Sauna access',
  `is_featured` = 1
WHERE `plan_name` = 'Half-Yearly';

UPDATE `membership_plans` SET
  `description` = 'Best long-term savings',
  `features` = 'Everything in Half-Yearly|Unlimited classes|Guest passes|Priority booking',
  `is_featured` = 0
WHERE `plan_name` = 'Annual';


-- ============================================================
-- SOURCE: sql/phase3_modules.sql
-- ============================================================
-- =========================================================
-- PHASE 3 SCHEMA — Workout, Diet, Class Schedule
-- (Membership uses existing membership_history + members tables.
--  Attendance uses the existing `attendance` table from core_and_reports.sql.)
-- Run after core_and_reports.sql and public_site.sql
-- =========================================================

-- --------------------------------------------------------
-- Table: workout_plans
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `workout_plans` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `member_id` INT(11) NOT NULL,
  `trainer_id` INT(11) DEFAULT NULL,
  `title` VARCHAR(150) NOT NULL,
  `goal` VARCHAR(150) DEFAULT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE DEFAULT NULL,
  `status` ENUM('Active','Completed','Cancelled') NOT NULL DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `member_id` (`member_id`),
  KEY `trainer_id` (`trainer_id`),
  CONSTRAINT `fk_workout_member` FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_workout_trainer` FOREIGN KEY (`trainer_id`) REFERENCES `trainers`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: workout_plan_exercises  (line items per plan)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `workout_plan_exercises` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `workout_plan_id` INT(11) NOT NULL,
  `day_of_week` ENUM('Mon','Tue','Wed','Thu','Fri','Sat','Sun') NOT NULL,
  `exercise_name` VARCHAR(150) NOT NULL,
  `sets` INT(11) DEFAULT NULL,
  `reps` VARCHAR(30) DEFAULT NULL,
  `notes` VARCHAR(255) DEFAULT NULL,
  `sort_order` INT(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `workout_plan_id` (`workout_plan_id`),
  CONSTRAINT `fk_exercise_plan` FOREIGN KEY (`workout_plan_id`) REFERENCES `workout_plans`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: diet_plans
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `diet_plans` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `member_id` INT(11) NOT NULL,
  `trainer_id` INT(11) DEFAULT NULL,
  `title` VARCHAR(150) NOT NULL,
  `goal` VARCHAR(150) DEFAULT NULL,
  `calorie_target` INT(11) DEFAULT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE DEFAULT NULL,
  `status` ENUM('Active','Completed','Cancelled') NOT NULL DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `member_id` (`member_id`),
  KEY `trainer_id` (`trainer_id`),
  CONSTRAINT `fk_diet_member` FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_diet_trainer` FOREIGN KEY (`trainer_id`) REFERENCES `trainers`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: diet_plan_meals  (line items per plan)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `diet_plan_meals` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `diet_plan_id` INT(11) NOT NULL,
  `meal_type` ENUM('Breakfast','Lunch','Dinner','Snack') NOT NULL,
  `food_items` VARCHAR(255) NOT NULL,
  `calories` INT(11) DEFAULT NULL,
  `sort_order` INT(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `diet_plan_id` (`diet_plan_id`),
  CONSTRAINT `fk_meal_plan` FOREIGN KEY (`diet_plan_id`) REFERENCES `diet_plans`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: class_schedule
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `class_schedule` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `class_name` VARCHAR(120) NOT NULL,
  `trainer_id` INT(11) DEFAULT NULL,
  `day_of_week` ENUM('Mon','Tue','Wed','Thu','Fri','Sat','Sun') NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `capacity` INT(11) NOT NULL DEFAULT 20,
  `room` VARCHAR(80) DEFAULT NULL,
  `status` ENUM('Active','Cancelled') NOT NULL DEFAULT 'Active',
  PRIMARY KEY (`id`),
  KEY `trainer_id` (`trainer_id`),
  CONSTRAINT `fk_class_trainer` FOREIGN KEY (`trainer_id`) REFERENCES `trainers`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Sample data (safe to delete) — only inserted if you already
-- have at least one member/trainer, so Workout/Diet/Schedule
-- pages aren't empty on first look.
-- --------------------------------------------------------
INSERT INTO class_schedule (class_name, trainer_id, day_of_week, start_time, end_time, capacity, room)
SELECT 'Strength Foundations', t.id, 'Mon', '06:00:00', '07:00:00', 16, 'Floor A'
FROM trainers t WHERE NOT EXISTS (SELECT 1 FROM class_schedule) LIMIT 1;

INSERT INTO class_schedule (class_name, trainer_id, day_of_week, start_time, end_time, capacity, room)
SELECT 'Olympic Lifting', t.id, 'Wed', '08:00:00', '09:00:00', 14, 'Floor B'
FROM trainers t WHERE (SELECT COUNT(*) FROM class_schedule) < 2 LIMIT 1;

INSERT INTO class_schedule (class_name, trainer_id, day_of_week, start_time, end_time, capacity, room)
SELECT 'Mobility & Recovery', t.id, 'Fri', '17:00:00', '18:00:00', 20, 'Studio'
FROM trainers t WHERE (SELECT COUNT(*) FROM class_schedule) < 3 LIMIT 1;

