<?php
/**
 * Check users table structure
 */

require_once __DIR__ . '/../app/Database.php';

try {
    $db = Database::getInstance();
    
    echo "🔍 Checking users table structure...\n\n";
    
    $stmt = $db->query('DESCRIBE users');
    
    echo "Columns in users table:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  - " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    
    echo "\n";
    
    // Check if is_admin exists
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'is_admin'");
    if ($stmt->rowCount() > 0) {
        echo "✅ is_admin column exists\n";
    } else {
        echo "❌ is_admin column NOT FOUND\n\n";
        echo "Looking for alternative admin columns...\n";
        
        // Check for role or admin_level
        $stmt = $db->query("SHOW COLUMNS FROM users WHERE Field IN ('role', 'admin', 'admin_level', 'user_role')");
        if ($stmt->rowCount() > 0) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "  Found: " . $row['Field'] . "\n";
            }
        } else {
            echo "  No admin-related columns found\n";
            echo "\n💡 Need to add is_admin column\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
