<?php
/**
 * Fix admin session for logged-in user
 * This updates the session without requiring logout/login
 */

session_start();

require_once __DIR__ . '/../app/Database.php';

try {
    if (!isset($_SESSION['user_id'])) {
        echo "❌ No user logged in\n";
        echo "   Please log in first, then run this script\n";
        exit(1);
    }
    
    $userId = $_SESSION['user_id'];
    
    $db = Database::getInstance();
    $stmt = $db->prepare("SELECT username, email, is_admin FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "❌ User not found\n";
        exit(1);
    }
    
    echo "👤 Current user: " . $user['username'] . "\n";
    echo "📧 Email: " . ($user['email'] ?: '(not set)') . "\n";
    echo "👑 Admin: " . ($user['is_admin'] ? 'YES' : 'NO') . "\n\n";
    
    // Update session
    $_SESSION['is_admin'] = $user['is_admin'] ? true : false;
    
    if ($user['is_admin']) {
        echo "✅ Session updated - you are now admin!\n";
        echo "\n🎉 You can now save AI settings in Admin panel.\n";
        echo "   Refresh your browser and try again.\n";
    } else {
        echo "⚠️  Your account is not set as admin\n";
        echo "\nTo make yourself admin, run:\n";
        echo "  UPDATE users SET is_admin = 1 WHERE id = $userId;\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

