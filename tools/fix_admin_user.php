<?php
/**
 * Set ONLY the "admin" user as admin, remove admin from all others
 */

require_once __DIR__ . '/../app/Database.php';

try {
    $db = Database::getInstance();
    
    echo "🔧 Fixing admin user permissions...\n\n";
    
    // First, remove admin from everyone
    echo "1. Removing admin from all users...\n";
    $db->query("UPDATE users SET is_admin = 0");
    echo "✅ Done\n\n";
    
    // Set ONLY 'admin' user as admin
    echo "2. Setting 'admin' user as admin...\n";
    $stmt = $db->prepare("UPDATE users SET is_admin = 1 WHERE username = 'admin'");
    $stmt->execute();
    
    // Check if it worked
    $stmt = $db->prepare("SELECT id, username FROM users WHERE username = 'admin'");
    $stmt->execute();
    $adminUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$adminUser) {
        echo "❌ ERROR: 'admin' user not found!\n";
        echo "\nAvailable users:\n";
        $stmt = $db->query("SELECT username FROM users");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "  - " . $row['username'] . "\n";
        }
        exit(1);
    }
    
    echo "✅ 'admin' user is now admin\n\n";
    
    // Show all users and their admin status
    echo "3. Current user permissions:\n";
    $stmt = $db->query("SELECT username, email, is_admin FROM users ORDER BY id");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $badge = $row['is_admin'] ? '👑 ADMIN' : '👤 Regular User';
        echo "  - " . str_pad($row['username'], 25) . " $badge\n";
    }
    
    echo "\n✅ Done! Log in as 'admin' to access admin panel.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

