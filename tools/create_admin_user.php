<?php
/**
 * Create Admin User
 * This script creates the default admin user if it doesn't exist
 */

require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/Models/User.php';

// Start output
echo "Creating admin user...\n\n";

try {
    $userModel = new User();
    
    // Check if admin already exists
    $existingAdmin = $userModel->findByUsername('admin');
    
    if ($existingAdmin) {
        echo "Admin user already exists!\n";
        echo "Username: admin\n";
        echo "User ID: " . $existingAdmin['id'] . "\n\n";
        echo "If you need to reset the password, delete this user first or update the password directly.\n";
        exit(0);
    }
    
    // Create admin user with password 'admin123'
    $userId = $userModel->create('admin', 'admin123');
    
    echo "✓ Admin user created successfully!\n\n";
    echo "Login credentials:\n";
    echo "  Username: admin\n";
    echo "  Password: admin123\n\n";
    echo "⚠️  IMPORTANT: Please change this password after your first login!\n";
    echo "   You can change it from the Settings page.\n\n";
    
} catch (Exception $e) {
    echo "✗ Error creating admin user: " . $e->getMessage() . "\n";
    exit(1);
}
