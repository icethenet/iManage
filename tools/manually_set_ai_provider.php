<?php
/**
 * Manually set AI provider (for testing)
 * Usage: php tools/manually_set_ai_provider.php [ollama|lmstudio|gemini|openai]
 */

require_once __DIR__ . '/../app/Database.php';

$provider = $argv[1] ?? null;

if (!$provider || !in_array($provider, ['ollama', 'lmstudio', 'gemini', 'openai'])) {
    echo "Usage: php tools/manually_set_ai_provider.php [ollama|lmstudio|gemini|openai]\n";
    echo "\nExamples:\n";
    echo "  php tools/manually_set_ai_provider.php ollama\n";
    echo "  php tools/manually_set_ai_provider.php gemini\n";
    exit(1);
}

try {
    $db = Database::getInstance();
    
    echo "🔧 Setting AI provider to: $provider\n\n";
    
    // Default settings for each provider
    $settings = [
        'ai_provider' => $provider,
        'ollama_endpoint' => 'http://localhost:11434',
        'ollama_model' => 'llama3.2',
        'lmstudio_endpoint' => 'http://localhost:1234',
        'gemini_api_key' => '',
        'openai_api_key' => ''
    ];
    
    foreach ($settings as $key => $value) {
        // Check if exists
        $stmt = $db->prepare("SELECT id FROM system_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $exists = $stmt->fetch();
        
        if ($exists) {
            // Update
            $stmt = $db->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$value, $key]);
            echo "✓ Updated $key = " . ($value ?: '(empty)') . "\n";
        } else {
            // Insert
            $stmt = $db->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?)");
            $stmt->execute([$key, $value]);
            echo "✓ Inserted $key = " . ($value ?: '(empty)') . "\n";
        }
    }
    
    echo "\n✅ AI provider set to: $provider\n";
    
    if ($provider === 'ollama') {
        echo "\n📝 Next steps:\n";
        echo "  1. Make sure Ollama is installed: https://ollama.ai\n";
        echo "  2. Run: ollama serve\n";
        echo "  3. Run: ollama pull llama3.2\n";
        echo "  4. Test the AI Text Spinner in GrapesJS\n";
    } elseif ($provider === 'lmstudio') {
        echo "\n📝 Next steps:\n";
        echo "  1. Download LM Studio: https://lmstudio.ai\n";
        echo "  2. Load a model in LM Studio\n";
        echo "  3. Start the local server (green button)\n";
        echo "  4. Test the AI Text Spinner in GrapesJS\n";
    } elseif ($provider === 'gemini') {
        echo "\n📝 Next steps:\n";
        echo "  1. Get free API key: https://makersuite.google.com/app/apikey\n";
        echo "  2. Go to Admin > Settings > AI Integration\n";
        echo "  3. Paste the API key in Gemini API Key field\n";
        echo "  4. Click Save All Settings\n";
        echo "  5. Test the AI Text Spinner in GrapesJS\n";
    } elseif ($provider === 'openai') {
        echo "\n📝 Next steps:\n";
        echo "  1. Get API key: https://platform.openai.com/api-keys\n";
        echo "  2. Go to Admin > Settings > AI Integration\n";
        echo "  3. Paste the API key in OpenAI API Key field\n";
        echo "  4. Click Save All Settings\n";
        echo "  5. Test the AI Text Spinner in GrapesJS\n";
    }
    
    echo "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

