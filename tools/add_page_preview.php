<?php
/**
 * Add preview_image column to landing_pages table
 */

require_once __DIR__ . '/../app/Database.php';

try {
    $db = Database::getInstance();
    
    echo "Adding preview_image column to landing_pages...\n";
    
    // Check if column already exists
    $stmt = $db->query("SHOW COLUMNS FROM landing_pages LIKE 'preview_image'");
    if ($stmt->rowCount() > 0) {
        echo "✓ preview_image column already exists\n";
    } else {
        $db->query("ALTER TABLE landing_pages ADD COLUMN preview_image VARCHAR(255) DEFAULT NULL AFTER page_title");
        echo "✓ Added preview_image column\n";
    }
    
    echo "\n✅ Migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

