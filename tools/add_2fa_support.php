<?php
/**
 * Add 2FA Support Migration
 * Run this script once to add 2FA columns to users table
 */

require_once __DIR__ . '/../app/Database.php';

try {
    $db = Database::getInstance();
    
    echo "Adding 2FA support to users table...\n";
    
    // Check if columns already exist
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'two_factor_enabled'");
    if ($stmt->rowCount() > 0) {
        echo "✓ 2FA columns already exist\n";
    } else {
        // Add 2FA columns
        $db->query("ALTER TABLE `users` 
            ADD COLUMN `two_factor_enabled` TINYINT(1) DEFAULT 0 AFTER `avatar_url`,
            ADD COLUMN `two_factor_method` ENUM('totp', 'email') DEFAULT 'totp' AFTER `two_factor_enabled`,
            ADD COLUMN `two_factor_secret` VARCHAR(255) DEFAULT NULL AFTER `two_factor_method`,
            ADD COLUMN `two_factor_backup_codes` TEXT DEFAULT NULL AFTER `two_factor_secret`");
        echo "✓ Added 2FA columns to users table\n";
    }
    
    // Create system_settings table
    $db->query("CREATE TABLE IF NOT EXISTS `system_settings` (
        `id` INT NOT NULL AUTO_INCREMENT,
        `setting_key` VARCHAR(100) NOT NULL UNIQUE,
        `setting_value` TEXT,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_setting_key` (`setting_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✓ Created system_settings table\n";
    
    // Insert default 2FA setting
    $stmt = $db->prepare("INSERT INTO `system_settings` (`setting_key`, `setting_value`) 
        VALUES ('require_2fa', '0') 
        ON DUPLICATE KEY UPDATE `setting_key`=`setting_key`");
    $stmt->execute();
    echo "✓ Added default 2FA setting\n";
    
    echo "\n✅ 2FA support migration completed successfully!\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
