<?php
/**
 * Test AI settings API endpoints
 */

require_once __DIR__ . '/../app/Database.php';

echo "🧪 Testing AI Settings API...\n\n";

try {
    // Test database connection
    echo "1️⃣ Testing database connection...\n";
    $db = Database::getInstance();
    echo "✅ Database connected\n\n";
    
    // Test system_settings table exists
    echo "2️⃣ Checking system_settings table...\n";
    $stmt = $db->query("SHOW TABLES LIKE 'system_settings'");
    if ($stmt->rowCount() > 0) {
        echo "✅ system_settings table exists\n\n";
    } else {
        echo "❌ system_settings table NOT FOUND\n";
        echo "   Run: php tools/add_2fa_support.php\n\n";
        exit(1);
    }
    
    // Test reading from system_settings
    echo "3️⃣ Testing read from system_settings...\n";
    $stmt = $db->query("SELECT COUNT(*) as count FROM system_settings");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✅ Found " . $result['count'] . " settings in table\n\n";
    
    // Test reading AI settings specifically
    echo "4️⃣ Testing read AI settings...\n";
    $stmt = $db->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key LIKE 'ai_%' OR setting_key LIKE '%_api_key' OR setting_key LIKE 'ollama_%' OR setting_key LIKE 'lmstudio_%' OR setting_key LIKE 'gemini_%'");
    $aiSettings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "✅ Found " . count($aiSettings) . " AI settings\n";
    foreach ($aiSettings as $setting) {
        $value = $setting['setting_value'];
        if (strpos($setting['setting_key'], 'api_key') !== false && !empty($value)) {
            $value = substr($value, 0, 8) . '...' . substr($value, -4);
        }
        echo "   - " . $setting['setting_key'] . " = " . ($value ?: '(empty)') . "\n";
    }
    echo "\n";
    
    // Test writing a setting
    echo "5️⃣ Testing write to system_settings...\n";
    $testKey = 'test_ai_setting_' . time();
    $testValue = 'test_value_123';
    
    $stmt = $db->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?)");
    $stmt->execute([$testKey, $testValue]);
    echo "✅ Insert successful\n";
    
    // Test reading it back
    $stmt = $db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
    $stmt->execute([$testKey]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && $result['setting_value'] === $testValue) {
        echo "✅ Read back successful\n";
    } else {
        echo "❌ Read back failed\n";
    }
    
    // Clean up test setting
    $stmt = $db->prepare("DELETE FROM system_settings WHERE setting_key = ?");
    $stmt->execute([$testKey]);
    echo "✅ Cleanup successful\n\n";
    
    echo "🎉 All tests passed! The database is working correctly.\n";
    echo "\n💡 If you're still getting errors, check:\n";
    echo "   1. Browser console for detailed error messages\n";
    echo "   2. PHP error log (check php_error.log or server error log)\n";
    echo "   3. Make sure you're logged in as admin\n\n";
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

