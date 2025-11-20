<?php
/**
 * Check and fix users table structure
 */

$config = require __DIR__ . '/../config/database.php';

try {
    $pdo = new PDO(
        "mysql:host=" . $config['host'] . ";dbname=" . $config['database'],
        $config['username'],
        $config['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database: " . $config['database'] . "\n\n";
    
    // Check current table structure
    echo "Current users table columns:\n";
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $existingColumns = [];
    foreach ($columns as $column) {
        echo "  - {$column['Field']} ({$column['Type']})\n";
        $existingColumns[] = $column['Field'];
    }
    
    echo "\n";
    
    // Required columns with their definitions
    $requiredColumns = [
        'email' => "ADD COLUMN email VARCHAR(255) DEFAULT NULL AFTER username",
        'last_login' => "ADD COLUMN last_login DATETIME DEFAULT NULL AFTER created_at",
        'oauth_provider' => "ADD COLUMN oauth_provider VARCHAR(50) DEFAULT NULL AFTER password_hash",
        'oauth_id' => "ADD COLUMN oauth_id VARCHAR(255) DEFAULT NULL AFTER oauth_provider",
        'oauth_token' => "ADD COLUMN oauth_token TEXT DEFAULT NULL AFTER oauth_id",
        'oauth_refresh_token' => "ADD COLUMN oauth_refresh_token TEXT DEFAULT NULL AFTER oauth_token",
        'avatar_url' => "ADD COLUMN avatar_url VARCHAR(500) DEFAULT NULL AFTER oauth_refresh_token"
    ];
    
    $modified = false;
    foreach ($requiredColumns as $columnName => $alterSQL) {
        if (!in_array($columnName, $existingColumns)) {
            echo "Adding missing column: $columnName\n";
            $pdo->exec("ALTER TABLE users $alterSQL");
            $modified = true;
        }
    }
    
    // Add indexes if they don't exist
    if (!in_array('email', $existingColumns)) {
        echo "Adding index on email column\n";
        $pdo->exec("ALTER TABLE users ADD INDEX idx_email (email)");
    }
    
    if ($modified) {
        echo "\n✓ Table structure updated successfully!\n";
    } else {
        echo "✓ All required columns exist.\n";
    }
    
    echo "\nDone!\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
