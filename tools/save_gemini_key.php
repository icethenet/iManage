<?php
/**
 * Save Gemini API key
 * Usage: php tools/save_gemini_key.php YOUR_API_KEY
 */

require_once __DIR__ . '/../app/Database.php';

$apiKey = $argv[1] ?? null;

if (!$apiKey) {
    echo "❌ Please provide your Gemini API key\n\n";
    echo "Usage: php tools/save_gemini_key.php YOUR_API_KEY\n\n";
    echo "Get your FREE API key at:\n";
    echo "https://makersuite.google.com/app/apikey\n\n";
    exit(1);
}

try {
    $db = Database::getInstance();
    
    // Check if setting exists
    $stmt = $db->prepare("SELECT id FROM system_settings WHERE setting_key = 'gemini_api_key'");
    $stmt->execute();
    $exists = $stmt->fetch();
    
    if ($exists) {
        $stmt = $db->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = 'gemini_api_key'");
        $stmt->execute([$apiKey]);
    } else {
        $stmt = $db->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('gemini_api_key', ?)");
        $stmt->execute([$apiKey]);
    }
    
    // Mask key for display
    $masked = substr($apiKey, 0, 10) . '...' . substr($apiKey, -6);
    
    echo "✅ Gemini API key saved: $masked\n\n";
    echo "🎉 AI Text Spinner is now ready!\n\n";
    echo "Test it:\n";
    echo "1. Go to Page Designer\n";
    echo "2. Click the 🪄 magic wand button\n";
    echo "3. Type some text\n";
    echo "4. Click 'Spin Text'\n\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

