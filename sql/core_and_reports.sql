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
CREATE INDEX idx_payments_date ON payments (payment_date);
CREATE INDEX idx_attendance_checkin ON attendance (check_in);
CREATE INDEX idx_sales_date ON product_sales (sale_date);
CREATE INDEX idx_expenses_date ON expenses (expense_date);

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
