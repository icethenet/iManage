<?php
/**
 * Check if local AI tools (Ollama, LM Studio) are installed and running
 */

echo "🔍 Checking for local AI tools...\n\n";

// Check for Ollama
echo "━━━ OLLAMA ━━━\n";
$ollamaInstalled = false;
$ollamaRunning = false;

// Windows check
if (PHP_OS_FAMILY === 'Windows') {
    $output = shell_exec('where ollama 2>nul');
    if ($output) {
        $ollamaInstalled = true;
        echo "✅ Ollama is installed: " . trim($output) . "\n";
    } else {
        echo "❌ Ollama not found\n";
        echo "   Download: https://ollama.com/download/windows\n";
        echo "   Or run: winget install Ollama.Ollama\n";
    }
} else {
    $output = shell_exec('which ollama 2>/dev/null');
    if ($output) {
        $ollamaInstalled = true;
        echo "✅ Ollama is installed: " . trim($output) . "\n";
    } else {
        echo "❌ Ollama not found\n";
        echo "   Install: curl -fsSL https://ollama.com/install.sh | sh\n";
    }
}

// Check if Ollama is running
if ($ollamaInstalled) {
    $ch = curl_init('http://localhost:11434/api/tags');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $ollamaRunning = true;
        echo "✅ Ollama server is running\n";
        
        $data = json_decode($response, true);
        if (!empty($data['models'])) {
            echo "📦 Installed models:\n";
            foreach ($data['models'] as $model) {
                echo "   - " . $model['name'] . "\n";
            }
        } else {
            echo "⚠️  No models installed\n";
            echo "   Run: ollama pull llama3.2\n";
        }
    } else {
        echo "❌ Ollama server not running\n";
        echo "   Start it: ollama serve\n";
    }
}

echo "\n━━━ LM STUDIO ━━━\n";
$lmstudioRunning = false;

// Check if LM Studio server is running
$ch = curl_init('http://localhost:1234/v1/models');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 2);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $lmstudioRunning = true;
    echo "✅ LM Studio server is running\n";
    
    $data = json_decode($response, true);
    if (!empty($data['data'])) {
        echo "📦 Loaded models:\n";
        foreach ($data['data'] as $model) {
            echo "   - " . $model['id'] . "\n";
        }
    }
} else {
    echo "❌ LM Studio not running\n";
    echo "   Download: https://lmstudio.ai\n";
    echo "   After install:\n";
    echo "   1. Download a model (Llama 3.2 3B recommended)\n";
    echo "   2. Load the model\n";
    echo "   3. Start the local server (green button)\n";
}

echo "\n━━━ SUMMARY ━━━\n";

if ($ollamaRunning) {
    echo "✅ Ollama is ready to use!\n";
    echo "   Run: php tools/manually_set_ai_provider.php ollama\n";
} elseif ($lmstudioRunning) {
    echo "✅ LM Studio is ready to use!\n";
    echo "   Run: php tools/manually_set_ai_provider.php lmstudio\n";
} else {
    echo "💡 EASIEST OPTION: Use Google Gemini (FREE)\n";
    echo "   1. Get API key: https://makersuite.google.com/app/apikey\n";
    echo "   2. Run: php tools/manually_set_ai_provider.php gemini\n";
    echo "   3. Add API key in Admin > Settings > AI Integration\n";
    echo "   4. Click Save All Settings\n";
}

echo "\n";

