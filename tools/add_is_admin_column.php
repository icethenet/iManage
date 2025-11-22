<?php
/**
 * Add is_admin column to users table
 */

require_once __DIR__ . '/../app/Database.php';

try {
    $db = Database::getInstance();
    
    echo "🔧 Adding is_admin column to users table...\n\n";
    
    // Check if column already exists
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'is_admin'");
    if ($stmt->rowCount() > 0) {
        echo "✅ is_admin column already exists\n";
        exit(0);
    }
    
    // Add the column
    echo "Adding column...\n";
    $db->query("ALTER TABLE users ADD COLUMN is_admin TINYINT(1) DEFAULT 0 AFTER email");
    echo "✅ Column added successfully\n\n";
    
    // Make the first user admin
    echo "Setting first user as admin...\n";
    $stmt = $db->query("SELECT id, username FROM users ORDER BY id LIMIT 1");
    $firstUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($firstUser) {
        $stmt = $db->prepare("UPDATE users SET is_admin = 1 WHERE id = ?");
        $stmt->execute([$firstUser['id']]);
        echo "✅ User '" . $firstUser['username'] . "' is now admin\n\n";
    } else {
        echo "⚠️  No users found in database\n\n";
    }
    
    // Show all admins
    $stmt = $db->query("SELECT id, username, email, is_admin FROM users");
    echo "Current users:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $adminBadge = $row['is_admin'] ? '👑 ADMIN' : '';
        echo "  - " . $row['username'] . " (" . $row['email'] . ") $adminBadge\n";
    }
    
    echo "\n✅ Done! You can now save AI settings in Admin panel.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

