<?php
/**
 * Check if 2FA columns exist in users table
 */

require_once __DIR__ . '/../app/Database.php';

try {
    $db = Database::getInstance();
    
    echo "Checking users table structure...\n\n";
    
    $stmt = $db->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $twoFactorColumns = [];
    
    foreach ($columns as $column) {
        echo sprintf("%-30s %-20s\n", $column['Field'], $column['Type']);
        
        if (strpos($column['Field'], 'two_factor') !== false) {
            $twoFactorColumns[] = $column['Field'];
        }
    }
    
    echo "\n";
    
    if (empty($twoFactorColumns)) {
        echo "❌ No 2FA columns found!\n";
        echo "Run: php tools/add_2fa_support.php\n";
    } else {
        echo "✅ Found 2FA columns:\n";
        foreach ($twoFactorColumns as $col) {
            echo "  - $col\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

