-- ============================================================
-- LakbayCash — Full Database Schema
-- ============================================================

SET NAMES utf8mb4;
SET time_zone = '+08:00';
SET foreign_key_checks = 0;

CREATE DATABASE IF NOT EXISTS `lakbaycash`
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE `lakbaycash`;

-- ─── Users ───────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `mobile`          VARCHAR(20)  NOT NULL UNIQUE,
  `name`            VARCHAR(120) NOT NULL,
  `email`           VARCHAR(180) NOT NULL UNIQUE,
  `otp_code`        VARCHAR(10)  DEFAULT NULL,
  `credit_limit`    DECIMAL(10,2) DEFAULT NULL,
  `onboarding_step` TINYINT UNSIGNED DEFAULT 0,
  `onboarding_done` TINYINT(1) DEFAULT 0,
  `status`          ENUM('active','suspended') DEFAULT 'active',
  `created_at`      DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Personal Information ────────────────────────────────────
CREATE TABLE IF NOT EXISTS `personal_info` (
  `id`                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`            INT UNSIGNED NOT NULL UNIQUE,
  `first_name`         VARCHAR(80)  NOT NULL,
  `middle_name`        VARCHAR(80)  DEFAULT NULL,
  `last_name`          VARCHAR(80)  NOT NULL,
  `gender`             ENUM('Male','Female','Prefer not to say') NOT NULL,
  `nationality`        VARCHAR(60)  DEFAULT 'Filipino',
  `date_of_birth`      DATE         NOT NULL,
  `complete_address`   TEXT         NOT NULL,
  `street`             VARCHAR(200) NOT NULL,
  `city`               VARCHAR(100) NOT NULL,
  `province`           VARCHAR(100) NOT NULL,
  `region`             VARCHAR(100) NOT NULL,
  `zip_code`           VARCHAR(10)  NOT NULL,
  `facebook_link`      VARCHAR(255) DEFAULT NULL,
  `num_dependents`     TINYINT UNSIGNED DEFAULT 0,
  `created_at`         DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at`         DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Dependents ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `dependents` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`      INT UNSIGNED NOT NULL,
  `dep_name`     VARCHAR(120) NOT NULL,
  `birthday`     DATE         NOT NULL,
  `phone`        VARCHAR(20)  NOT NULL,
  `facebook_link`     VARCHAR(255) DEFAULT NULL,
  `sort_order`   TINYINT UNSIGNED DEFAULT 1,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Employment ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `employment` (
  `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`          INT UNSIGNED NOT NULL UNIQUE,
  `occupation_type`  VARCHAR(80)  NOT NULL,
  `industry`         VARCHAR(80)  NOT NULL,
  `payday`           VARCHAR(50)  NOT NULL,
  `amount_of_pay`    DECIMAL(12,2) NOT NULL,
  `bank_statement`   VARCHAR(255) DEFAULT NULL,
  `proof_of_billing` VARCHAR(255) DEFAULT NULL,
  `occupation_proof` VARCHAR(255) DEFAULT NULL,
  `created_at`       DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Identity Verification ───────────────────────────────────
CREATE TABLE IF NOT EXISTS `identity_verification` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`     INT UNSIGNED NOT NULL UNIQUE,
  `id_front`    VARCHAR(255) DEFAULT NULL,
  `id_back`     VARCHAR(255) DEFAULT NULL,
  `face_scan`   VARCHAR(255) DEFAULT NULL,
  `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Disbursal Method ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `disbursal_method` (
  `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`          INT UNSIGNED NOT NULL UNIQUE,
  `method`           ENUM('ewallet','bank') NOT NULL,
  `ewallet_number`   VARCHAR(30)  DEFAULT NULL,
  `ewallet_name`     VARCHAR(120) DEFAULT NULL,
  `ewallet_provider` VARCHAR(60)  DEFAULT NULL,
  `bank_name`        VARCHAR(100) DEFAULT NULL,
  `card_number`      VARCHAR(25)  DEFAULT NULL,
  `cvv`              VARCHAR(10)  DEFAULT NULL,
  `expiry_date`      VARCHAR(10)  DEFAULT NULL,
  `created_at`       DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Emergency Contacts ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS `emergency_contacts` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`      INT UNSIGNED NOT NULL,
  `contact_name` VARCHAR(120) NOT NULL,
  `phone`        VARCHAR(20)  NOT NULL,
  `relationship` VARCHAR(80)  NOT NULL,
  `sort_order`   TINYINT UNSIGNED DEFAULT 1,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Loans ───────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `loans` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`         INT UNSIGNED NOT NULL,
  `amount`          DECIMAL(10,2) NOT NULL,
  `term_months`     TINYINT UNSIGNED NOT NULL,
  `interest_rate`   DECIMAL(5,4) DEFAULT 0.0400,
  `total_repayable` DECIMAL(10,2) NOT NULL,
  `monthly_payment` DECIMAL(10,2) NOT NULL,
  `disbursal_method` ENUM('ewallet','bank') NOT NULL,
  `status`          ENUM('pending','approved','rejected') DEFAULT 'pending',
  `auto_evaluated`  TINYINT(1) DEFAULT 0,
  `admin_note`      TEXT DEFAULT NULL,
  `submitted_at`    DATETIME DEFAULT CURRENT_TIMESTAMP,
  `evaluated_at`    DATETIME DEFAULT NULL,
  `updated_at`      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Admin Users ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `admins` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username`     VARCHAR(60) NOT NULL UNIQUE,
  `password`     VARCHAR(255) NOT NULL,
  `full_name`    VARCHAR(120) NOT NULL,
  `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── App Settings ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `app_settings` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(80) NOT NULL UNIQUE,
  `setting_val` TEXT DEFAULT NULL,
  `updated_at`  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Seed: Default Admin (password: Admin@2024!) ─────────────
INSERT IGNORE INTO `admins` (`username`, `password`, `full_name`)
VALUES ('superadmin', '$2y$12$Pf5gTJlGvPLqXVVdK7sHNeqB5UmHOqMYAFyKQ3UvYd.H4F.L7mMXi', 'Super Admin');

-- ─── Seed: Default App Settings ──────────────────────────────
INSERT IGNORE INTO `app_settings` (`setting_key`, `setting_val`) VALUES
  ('qr_agreement_image', 'qr/placeholder-qr.svg'),
  ('loan_min_limit',     '500'),
  ('loan_max_limit',     '6000'),
  ('interest_rate',      '0.04'),
  ('auto_approve_delay', '300');

SET foreign_key_checks = 1;
