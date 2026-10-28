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
