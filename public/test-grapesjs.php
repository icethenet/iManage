<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GrapesJS Test - Check Installation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .test-box {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .success { border-left: 5px solid #4caf50; }
        .error { border-left: 5px solid #f44336; }
        .info { border-left: 5px solid #2196f3; }
        h1 { color: #333; }
        h2 { color: #667eea; margin-top: 0; }
        code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 5px;
        }
        .button:hover { background: #5568d3; }
        pre {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>🎨 GrapesJS Landing Pages - Installation Test</h1>
    
    <?php
    require_once __DIR__ . '/../app/Database.php';
    
    $allGood = true;
    
    // Test 1: Database Table
    echo '<div class="test-box">';
    echo '<h2>1. Database Table Check</h2>';
    try {
        $db = Database::getInstance();
        $stmt = $db->query("SHOW TABLES LIKE 'landing_pages'");
        if ($stmt->fetch()) {
            echo '<p class="success">✅ <strong>landing_pages</strong> table exists</p>';
        } else {
            echo '<p class="error">❌ <strong>landing_pages</strong> table NOT found</p>';
            echo '<p>Run: <code>php setup-grapesjs.php</code></p>';
            $allGood = false;
        }
    } catch (Exception $e) {
        echo '<p class="error">❌ Database error: ' . htmlspecialchars($e->getMessage()) . '</p>';
        $allGood = false;
    }
    echo '</div>';
    
    // Test 2: Required Files
    echo '<div class="test-box">';
    echo '<h2>2. Required Files Check</h2>';
    $files = [
        'design-landing.php' => 'GrapesJS Editor',
        'js/grapesjs-manager.js' => 'Manager Class'
    ];
    foreach ($files as $file => $name) {
        if (file_exists(__DIR__ . '/' . $file)) {
            echo '<p class="success">✅ ' . htmlspecialchars($name) . ': <code>' . $file . '</code></p>';
        } else {
            echo '<p class="error">❌ Missing: <code>' . $file . '</code></p>';
            $allGood = false;
        }
    }
    echo '</div>';
    
    // Test 3: Shared Images
    echo '<div class="test-box">';
    echo '<h2>3. Shared Images Check</h2>';
    try {
        $stmt = $db->query("SELECT id, original_name, share_token, user_id FROM images WHERE share_token IS NOT NULL LIMIT 5");
        $images = $stmt->fetchAll();
        
        if (count($images) > 0) {
            echo '<p class="success">✅ Found ' . count($images) . ' shared image(s)</p>';
            echo '<p><strong>Test with these links:</strong></p>';
            echo '<ul>';
            foreach ($images as $img) {
                $shareUrl = 'share.php?share=' . urlencode($img['share_token']);
                echo '<li>';
                echo htmlspecialchars($img['original_name']) . '<br>';
                echo '<a href="' . $shareUrl . '" class="button" target="_blank">View Share Page</a>';
                echo '<small>(Owner ID: ' . $img['user_id'] . ')</small>';
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p class="info">ℹ️ No shared images found</p>';
            echo '<p><strong>To create a shared image:</strong></p>';
            echo '<ol>';
            echo '<li>Login to <a href="index.php">iManage</a></li>';
            echo '<li>Click the 🔗 Share button on any image</li>';
            echo '<li>Come back here to test</li>';
            echo '</ol>';
        }
    } catch (Exception $e) {
        echo '<p class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
    echo '</div>';
    
    // Test 4: Button Appearance
    echo '<div class="test-box info">';
    echo '<h2>4. How to See the Design Button</h2>';
    echo '<p>The <strong>🎨 Design Landing Page</strong> button will appear when:</p>';
    echo '<ol>';
    echo '<li>✅ You are <strong>logged in</strong> to iManage</li>';
    echo '<li>✅ You are viewing a <strong>share page</strong></li>';
    echo '<li>✅ You <strong>own</strong> the shared image</li>';
    echo '</ol>';
    echo '<p><strong>Where to look:</strong></p>';
    echo '<ul>';
    echo '<li>The button appears at the <strong>BOTTOM-RIGHT corner</strong> of the page</li>';
    echo '<li>It\'s a floating button with a gradient purple background</li>';
    echo '<li>It says "🎨 Design Landing Page"</li>';
    echo '</ul>';
    echo '</div>';
    
    // Test 5: API Endpoints
    echo '<div class="test-box">';
    echo '<h2>5. API Endpoints Check</h2>';
    $apiContent = file_get_contents(__DIR__ . '/api.php');
    if (strpos($apiContent, 'saveLandingPage') !== false) {
        echo '<p class="success">✅ <code>saveLandingPage</code> endpoint found</p>';
    } else {
        echo '<p class="error">❌ <code>saveLandingPage</code> endpoint missing</p>';
        $allGood = false;
    }
    if (strpos($apiContent, 'loadLandingPage') !== false) {
        echo '<p class="success">✅ <code>loadLandingPage</code> endpoint found</p>';
    } else {
        echo '<p class="error">❌ <code>loadLandingPage</code> endpoint missing</p>';
        $allGood = false;
    }
    echo '</div>';
    
    // Final Status
    echo '<div class="test-box ' . ($allGood ? 'success' : 'error') . '">';
    if ($allGood) {
        echo '<h2>✅ All Tests Passed!</h2>';
        echo '<p><strong>Next steps:</strong></p>';
        echo '<ol>';
        echo '<li>Make sure you\'re <a href="index.php">logged in</a></li>';
        echo '<li>Visit a share link above</li>';
        echo '<li>Look for the button at the bottom-right</li>';
        echo '</ol>';
        echo '<p><strong>Still don\'t see it?</strong></p>';
        echo '<p>Open browser DevTools (F12), go to Console tab, and check for errors. Then refresh with Ctrl+F5.</p>';
    } else {
        echo '<h2>❌ Setup Incomplete</h2>';
        echo '<p>Please fix the errors above and try again.</p>';
    }
    echo '</div>';
    ?>
    
    <div class="test-box info">
        <h2>📚 Documentation</h2>
        <ul>
            <li><a href="../docs/GRAPESJS_QUICKSTART.md">Quick Start Guide</a></li>
            <li><a href="../docs/CUSTOM_LANDING_PAGES.md">Full Documentation</a></li>
        </ul>
    </div>
</body>
</html>
