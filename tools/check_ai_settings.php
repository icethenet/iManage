<?php
/**
 * Check AI settings in database
 */

require_once __DIR__ . '/../app/Database.php';

try {
    $db = Database::getInstance();
    
    echo "🔍 Checking AI settings in database...\n\n";
    
    $stmt = $db->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key LIKE '%ai%' OR setting_key LIKE '%ollama%' OR setting_key LIKE '%lmstudio%' OR setting_key LIKE '%gemini%' OR setting_key LIKE '%openai%' ORDER BY setting_key");
    
    $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($settings)) {
        echo "❌ No AI settings found in database!\n";
        echo "   This means the settings were never saved.\n\n";
        echo "💡 To fix:\n";
        echo "   1. Go to Admin > Settings\n";
        echo "   2. Select an AI provider\n";
        echo "   3. Configure the provider settings\n";
        echo "   4. Click 'Save All Settings'\n";
        echo "   5. Check browser console for 'Saving AI settings:' message\n";
    } else {
        echo "Found " . count($settings) . " AI setting(s):\n\n";
        
        foreach ($settings as $setting) {
            $value = $setting['setting_value'];
            // Mask API keys for security
            if (strpos($setting['setting_key'], 'api_key') !== false && !empty($value)) {
                $value = substr($value, 0, 8) . '...' . substr($value, -4);
            }
            
            echo "  📌 " . $setting['setting_key'] . " = " . ($value ?: '(empty)') . "\n";
        }
        
        echo "\n";
        
        // Check provider specifically
        $stmt = $db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'ai_provider'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $provider = $result['setting_value'];
            echo "✅ Current AI Provider: " . ($provider ?: 'none') . "\n";
            
            if ($provider === 'ollama') {
                echo "   Ollama should be running at the configured endpoint\n";
                echo "   Test with: curl http://localhost:11434/api/generate\n";
            } elseif ($provider === 'lmstudio') {
                echo "   LM Studio should be running with a model loaded\n";
                echo "   Test with: curl http://localhost:1234/v1/models\n";
            } elseif ($provider === 'gemini') {
                echo "   Gemini API key should be configured\n";
            } elseif ($provider === 'openai') {
                echo "   OpenAI API key should be configured\n";
            } elseif (empty($provider) || $provider === 'none') {
                echo "⚠️  Provider is 'none' - you need to select one!\n";
            }
        } else {
            echo "❌ ai_provider setting not found in database\n";
        }
    }
    
    echo "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

