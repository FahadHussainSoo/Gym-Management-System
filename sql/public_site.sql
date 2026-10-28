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
-- --------------------------------------------------------
ALTER TABLE `membership_plans`
  ADD COLUMN IF NOT EXISTS `description` VARCHAR(255) DEFAULT NULL AFTER `plan_name`,
  ADD COLUMN IF NOT EXISTS `features` TEXT DEFAULT NULL COMMENT 'Pipe-separated list, e.g. Gym Access|Locker|1 Free Class',
  ADD COLUMN IF NOT EXISTS `is_featured` TINYINT(1) NOT NULL DEFAULT 0;

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
