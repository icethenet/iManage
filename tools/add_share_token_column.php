<?php
/**
 * Add share_token column to images table
 */

require_once __DIR__ . '/../app/Database.php';

try {
    $db = Database::getInstance();
    
    // Check if column exists
    $stmt = $db->query("SHOW COLUMNS FROM images LIKE 'share_token'");
    if ($stmt->rowCount() > 0) {
        echo "✓ share_token column already exists\n";
        exit(0);
    }
    
    // Add share_token column
    $stmt = $db->prepare("ALTER TABLE images ADD COLUMN share_token VARCHAR(64) DEFAULT NULL AFTER shared");
    $stmt->execute();
    echo "✓ Added share_token column to images table\n";
    
    // Add index for faster lookups
    $stmt = $db->prepare("CREATE INDEX idx_share_token ON images(share_token)");
    $stmt->execute();
    echo "✓ Created index on share_token column\n";
    
    echo "\nDatabase updated successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
