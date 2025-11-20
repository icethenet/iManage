<?php
require __DIR__ . '/../app/Database.php';
require __DIR__ . '/../app/Models/User.php';

$username = $argv[1] ?? 'John';
$password = $argv[2] ?? 'password123';

try {
    $userModel = new User();
    $user = $userModel->findByUsername($username);
    
    if (!$user) {
        echo "User not found: $username\n";
        exit(1);
    }
    
    echo "User found: {$user['username']} (ID: {$user['id']})\n";
    
    if (password_verify($password, $user['password_hash'])) {
        echo "Password verified successfully!\n";
        exit(0);
    } else {
        echo "Password verification FAILED!\n";
        exit(2);
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(3);
}
