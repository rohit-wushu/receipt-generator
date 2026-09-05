-- Receipt Generator - MySQL schema
-- Import this file into a MySQL/MariaDB database before using the application.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- Table: users  (login for staff who issue receipts)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('admin','staff') NOT NULL DEFAULT 'staff',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default admin user, password: admin123 (CHANGE THIS AFTER FIRST LOGIN)
INSERT INTO `users` (`name`, `email`, `password_hash`, `role`)
VALUES ('Administrator', 'admin@example.com', '$2y$12$dUzSQTwVjsBtKTnz3f27GOrIiuozKVm.QWweWv1TxUcYQgytnCzKC', 'admin');
-- NOTE: the hash above corresponds to the password "admin123".

-- --------------------------------------------------------
-- Table: projects  (one row per builder/project letterhead)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `projects` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_name` VARCHAR(150) NOT NULL,
  `company_name` VARCHAR(150) NOT NULL,
  `company_tagline` VARCHAR(200) DEFAULT NULL,
  `location` VARCHAR(255) DEFAULT NULL,
  `office_address` VARCHAR(255) DEFAULT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,
  `website` VARCHAR(150) DEFAULT NULL,
  `logo_path` VARCHAR(255) DEFAULT NULL,
  `seal_path` VARCHAR(255) DEFAULT NULL,
  `signature_path` VARCHAR(255) DEFAULT NULL,
  `signatory_name` VARCHAR(150) DEFAULT 'Director',
  `primary_color` VARCHAR(7) NOT NULL DEFAULT '#163823',
  `secondary_color` VARCHAR(7) NOT NULL DEFAULT '#e07b28',
  `receipt_prefix` VARCHAR(50) NOT NULL DEFAULT 'RCT',
  `next_receipt_seq` INT UNSIGNED NOT NULL DEFAULT 1,
  `receipt_no_padding` TINYINT UNSIGNED NOT NULL DEFAULT 3,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: receipts
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `receipts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` INT UNSIGNED NOT NULL,
  `receipt_no` VARCHAR(100) NOT NULL,
  `receipt_date` DATE NOT NULL,
  `customer_name` VARCHAR(200) NOT NULL,
  `customer_address` VARCHAR(500) DEFAULT NULL,
  `unit_no` VARCHAR(50) DEFAULT NULL,
  `amount` DECIMAL(14,2) NOT NULL,
  `amount_words` VARCHAR(500) DEFAULT NULL,
  `payment_mode` ENUM('Cash','Cheque','UPI','Bank Transfer','Card','Other') NOT NULL DEFAULT 'UPI',
  `reference_no` VARCHAR(100) DEFAULT NULL,
  `remarks` VARCHAR(500) DEFAULT NULL,
  `pdf_path` VARCHAR(255) DEFAULT NULL,
  `jpg_path` VARCHAR(255) DEFAULT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_receipts_project_no` (`project_id`, `receipt_no`),
  KEY `idx_receipts_date` (`receipt_date`),
  CONSTRAINT `fk_receipts_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_receipts_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
