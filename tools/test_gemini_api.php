<?php
/**
 * Test Gemini API connection
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
    
    echo "🧪 Testing Gemini API connection...\n";
    
    $testPrompt = "Say 'Hello, the API works!' in a friendly way.";
    
    $url = 'https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent?key=' . $apiKey;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'contents' => [
            ['parts' => [['text' => $testPrompt]]]
        ]
    ]));
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    echo "📡 Sending request to: https://generativelanguage.googleapis.com/...\n";
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    $curlErrno = curl_errno($ch);
    curl_close($ch);
    
    echo "📥 HTTP Status: $httpCode\n";
    
    if ($curlErrno) {
        echo "❌ cURL Error ($curlErrno): $curlError\n\n";
        echo "💡 Common fixes:\n";
        echo "  - Check your internet connection\n";
        echo "  - Check if firewall is blocking PHP\n";
        echo "  - Try: curl_setopt(CURLOPT_SSL_VERIFYPEER, false) (not recommended for production)\n";
        exit(1);
    }
    
    if ($httpCode === 0) {
        echo "❌ HTTP 0 means request failed to connect\n";
        echo "   cURL couldn't reach the API server\n\n";
        echo "💡 Possible causes:\n";
        echo "  - No internet connection\n";
        echo "  - Firewall blocking outbound HTTPS\n";
        echo "  - DNS resolution issue\n";
        echo "  - SSL certificate issue\n";
        exit(1);
    }
    
    if ($httpCode !== 200) {
        echo "❌ API Error (HTTP $httpCode)\n";
        echo "Response: $response\n\n";
        
        $data = json_decode($response, true);
        if (isset($data['error'])) {
            echo "Error message: " . ($data['error']['message'] ?? 'Unknown') . "\n";
            
            if (strpos($data['error']['message'] ?? '', 'API_KEY_INVALID') !== false) {
                echo "\n💡 Your API key is invalid. Get a new one at:\n";
                echo "   https://makersuite.google.com/app/apikey\n";
            }
        }
        exit(1);
    }
    
    echo "✅ Connection successful!\n\n";
    
    $data = json_decode($response, true);
    if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
        $aiResponse = $data['candidates'][0]['content']['parts'][0]['text'];
        echo "🤖 Gemini says: $aiResponse\n\n";
        echo "🎉 Gemini API is working correctly!\n";
        echo "   You can now use the AI Text Spinner in Page Designer.\n";
    } else {
        echo "⚠️  Unexpected response format\n";
        echo "Response: " . substr($response, 0, 500) . "...\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

