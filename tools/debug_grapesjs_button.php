<?php
/**
 * GrapesJS Button Diagnostic Tool
 * This will test every step of the permission check and button display
 */

require_once __DIR__ . '/../app/Database.php';

echo "🔍 GrapesJS Button Diagnostic Tool\n";
echo "=====================================\n\n";

// Test 1: Check if share exists
echo "1️⃣ Testing Share Token...\n";
$token = '01b72796cf745bfab51c3b5e54cfa066';
$db = Database::getInstance();

try {
    $stmt = $db->prepare("SELECT i.*, i.user_id, u.username 
                          FROM images i 
                          LEFT JOIN users u ON i.user_id = u.id 
                          WHERE i.share_token = ?");
    $stmt->execute([$token]);
    $image = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($image) {
        echo "   ✅ Share found!\n";
        echo "   - Image ID: {$image['id']}\n";
        echo "   - Owner ID: {$image['user_id']}\n";
        echo "   - Owner Username: {$image['username']}\n";
        echo "   - Image Name: {$image['name']}\n";
    } else {
        echo "   ❌ Share NOT found with token: $token\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "   ❌ Database error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n2️⃣ Testing Session/Login Status...\n";
session_start();
if (isset($_SESSION['user_id'])) {
    echo "   ✅ User is logged in\n";
    echo "   - Session User ID: {$_SESSION['user_id']}\n";
    echo "   - Session Username: {$_SESSION['username']}\n";
    
    // Test 3: Check ownership
    echo "\n3️⃣ Testing Ownership...\n";
    if ($_SESSION['user_id'] == $image['user_id']) {
        echo "   ✅ USER OWNS THIS IMAGE - BUTTON SHOULD SHOW!\n";
    } else {
        echo "   ❌ User does NOT own this image\n";
        echo "   - Logged in user: {$_SESSION['user_id']}\n";
        echo "   - Image owner: {$image['user_id']}\n";
        echo "   - Button will NOT show (this is correct behavior)\n";
    }
} else {
    echo "   ❌ No user logged in\n";
    echo "   - Button will NOT show until you log in\n";
}

// Test 4: Check if share.php has the button code
echo "\n4️⃣ Checking share.php for button code...\n";
$sharePhp = file_get_contents(__DIR__ . '/../public/share.php');
if (strpos($sharePhp, 'designModeBtn') !== false) {
    echo "   ✅ Button element found in share.php\n";
} else {
    echo "   ❌ Button element NOT found in share.php\n";
}

if (strpos($sharePhp, 'checkDesignPermission') !== false) {
    echo "   ✅ Permission check function found\n";
} else {
    echo "   ❌ Permission check function NOT found\n";
}

// Test 5: Create a test HTML file that bypasses all permission checks
echo "\n5️⃣ Creating simplified test page...\n";
$testHtml = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GrapesJS Button Test - No Permission Check</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #f5f5f5;
        }
        .test-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .design-mode-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 15px 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            transition: all 0.3s ease;
            z-index: 9999;
            display: block !important;
        }
        .design-mode-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 20px 0;
        }
        .success-box {
            background: #e8f5e9;
            border-left: 4px solid #4CAF50;
            padding: 15px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>🎨 GrapesJS Button Visibility Test</h1>
        
        <div class="success-box">
            <h3>✅ This page has NO permission checks</h3>
            <p>The button should be visible in the bottom-right corner regardless of login status.</p>
        </div>
        
        <div class="info-box">
            <h3>📍 What to Look For:</h3>
            <ul>
                <li>Purple gradient button at <strong>bottom-right corner</strong></li>
                <li>Text: "🎨 Design Landing Page"</li>
                <li>Button should be visible immediately</li>
                <li>Button should be clickable and link to editor</li>
            </ul>
        </div>
        
        <h3>Can you see the button? →</h3>
        <p>If YES: The button HTML/CSS works. Issue is with permission check in share.php</p>
        <p>If NO: There's a CSS issue or browser problem</p>
        
        <h3>Browser Console Check:</h3>
        <ol>
            <li>Press F12 to open console</li>
            <li>Look for any JavaScript errors (red text)</li>
            <li>Check if button element exists: <code>console.log(document.getElementById('designModeBtn'))</code></li>
        </ol>
    </div>
    
    <!-- THE BUTTON - Should be visible always on this test page -->
    <button id="designModeBtn" class="design-mode-btn" onclick="window.location.href='design-landing.php?share=01b72796cf745bfab51c3b5e54cfa066'">
        🎨 Design Landing Page
    </button>
    
    <script>
        console.log('🔍 Button Test Page Loaded');
        const btn = document.getElementById('designModeBtn');
        console.log('🎨 Button element:', btn);
        console.log('👁️ Button display:', window.getComputedStyle(btn).display);
        console.log('📍 Button position:', window.getComputedStyle(btn).position);
        console.log('🎯 Button z-index:', window.getComputedStyle(btn).zIndex);
        
        if (btn) {
            console.log('✅ Button exists in DOM');
            const rect = btn.getBoundingClientRect();
            console.log('📏 Button position:', {
                top: rect.top,
                right: rect.right,
                bottom: rect.bottom,
                left: rect.left,
                width: rect.width,
                height: rect.height
            });
        } else {
            console.error('❌ Button NOT found in DOM!');
        }
    </script>
</body>
</html>
HTML;

file_put_contents(__DIR__ . '/../public/test-button-visibility.php', $testHtml);
echo "   ✅ Test page created: public/test-button-visibility.php\n";

// Final recommendations
echo "\n" . str_repeat("=", 60) . "\n";
echo "📋 DIAGNOSTIC SUMMARY\n";
echo str_repeat("=", 60) . "\n\n";

if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $image['user_id']) {
    echo "✅ ALL CHECKS PASSED - Button SHOULD be visible on share.php\n\n";
    echo "🔧 NEXT STEPS:\n";
    echo "   1. Visit: http://localhost/imanage/public/test-button-visibility.php\n";
    echo "      → If you see button: CSS works, check JavaScript in share.php\n";
    echo "      → If no button: Browser/CSS issue\n\n";
    echo "   2. Visit: http://localhost/imanage/public/share.php?token=$token\n";
    echo "      → Open console (F12)\n";
    echo "      → Look for [GrapesJS] messages\n";
    echo "      → Check for JavaScript errors\n\n";
    echo "   3. In console, run: document.getElementById('designModeBtn')\n";
    echo "      → If null: Element not in DOM\n";
    echo "      → If object: Check its style.display value\n";
} else {
    echo "⚠️ PERMISSION ISSUE DETECTED\n\n";
    if (!isset($_SESSION['user_id'])) {
        echo "   ❌ You are NOT logged in\n";
        echo "   → Log in at: http://localhost/imanage/public/\n";
        echo "   → Then re-visit the share page\n";
    } else {
        echo "   ❌ You don't own this shared image\n";
        echo "   → Logged in as: {$_SESSION['username']} (ID: {$_SESSION['user_id']})\n";
        echo "   → Image owner: {$image['username']} (ID: {$image['user_id']})\n";
        echo "   → Button will NOT show (this is correct security behavior)\n";
        echo "   → Create a new share link from YOUR images to test\n";
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
