<?php
/**
 * List available Gemini models
 */

require_once __DIR__ . '/../app/Database.php';

try {
    $db = Database::getInstance();
    
    // Get API key
    $stmt = $db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'gemini_api_key'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $apiKey = $result['setting_value'] ?? '';
    
    if (empty($apiKey)) {
        echo "❌ No Gemini API key found\n";
        exit(1);
    }
    
    $masked = substr($apiKey, 0, 10) . '...' . substr($apiKey, -6);
    echo "🔑 Using API key: $masked\n\n";
    
    echo "📋 Listing available models...\n";
    
    $ch = curl_init('https://generativelanguage.googleapis.com/v1/models?key=' . $apiKey);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        echo "❌ API Error (HTTP $httpCode)\n";
        echo "Response: $response\n";
        
        if (strpos($response, 'API_KEY_INVALID') !== false || strpos($response, 'INVALID_ARGUMENT') !== false) {
            echo "\n💡 Your API key might be invalid or expired.\n";
            echo "   Get a new one at: https://makersuite.google.com/app/apikey\n";
        }
        exit(1);
    }
    
    $data = json_decode($response, true);
    
    if (isset($data['models'])) {
        echo "✅ Found " . count($data['models']) . " models:\n\n";
        foreach ($data['models'] as $model) {
            $name = $model['name'] ?? 'Unknown';
            $supportedMethods = implode(', ', $model['supportedGenerationMethods'] ?? []);
            echo "  📦 " . $name . "\n";
            echo "     Methods: $supportedMethods\n\n";
        }
    } else {
        echo "⚠️  No models in response\n";
        echo "Response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

