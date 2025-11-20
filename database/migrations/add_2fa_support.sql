-- Add 2FA support to users table
ALTER TABLE `users` 
ADD COLUMN `two_factor_enabled` TINYINT(1) DEFAULT 0 AFTER `avatar_url`,
ADD COLUMN `two_factor_method` ENUM('totp', 'email') DEFAULT 'totp' AFTER `two_factor_enabled`,
ADD COLUMN `two_factor_secret` VARCHAR(255) DEFAULT NULL AFTER `two_factor_method`,
ADD COLUMN `two_factor_backup_codes` TEXT DEFAULT NULL AFTER `two_factor_secret`;

-- Create system settings table for global 2FA enforcement
CREATE TABLE IF NOT EXISTS `system_settings` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default 2FA setting
INSERT INTO `system_settings` (`setting_key`, `setting_value`) 
VALUES ('require_2fa', '0') 
ON DUPLICATE KEY UPDATE `setting_key`=`setting_key`;
