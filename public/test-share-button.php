<?php
/**
 * Emergency Button Test - Bypasses ALL caching and security
 * This will FORCE show the button to test if CSS/positioning works
 */

session_start();

// Get share token from URL or use default
$shareToken = $_GET['token'] ?? '01b72796cf745bfab51c3b5e54cfa066';

// Force no cache headers
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

$cacheBuster = time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Button Test - <?= $cacheBuster ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            color: white;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 40px;
            border-radius: 20px;
        }
        
        .status {
            background: rgba(0, 0, 0, 0.3);
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        
        .status h3 {
            margin-bottom: 10px;
            color: #ffd700;
        }
        
        .success { color: #4ade80; }
        .error { color: #f87171; }
        .warning { color: #fbbf24; }
        
        /* THE BUTTON - Exact copy from share.php */
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
            z-index: 99999;
            display: block !important;
            opacity: 1 !important;
            visibility: visible !important;
        }
        
        .design-mode-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }
        
        code {
            background: rgba(0, 0, 0, 0.5);
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
        }
        
        .test-result {
            margin: 10px 0;
            padding: 10px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔬 GrapesJS Button Emergency Test</h1>
        <p style="margin: 20px 0;">Cache Buster: <code><?= $cacheBuster ?></code></p>
        
        <div class="status">
            <h3>📊 Session Status</h3>
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="test-result success">
                    ✅ Logged in as: <code><?= htmlspecialchars($_SESSION['username']) ?></code> 
                    (ID: <?= $_SESSION['user_id'] ?>)
                </div>
            <?php else: ?>
                <div class="test-result error">
                    ❌ NOT logged in
                </div>
            <?php endif; ?>
        </div>
        
        <div class="status">
            <h3>🎯 What This Page Tests</h3>
            <div class="test-result">
                <p><strong>1. Button Visibility</strong></p>
                <p>The button is forced to display with <code>display: block !important</code></p>
                <p>Location: <strong>Bottom-right corner</strong></p>
                <p>Style: Purple gradient, floating</p>
            </div>
            <div class="test-result">
                <p><strong>2. Click Functionality</strong></p>
                <p>Clicking button opens: <code>design-landing.php?share=<?= htmlspecialchars($shareToken) ?></code></p>
            </div>
        </div>
        
        <div class="status">
            <h3>👁️ Can You See The Button?</h3>
            <div class="test-result">
                <p><strong>YES</strong> → Button CSS works! Issue is with permission check in real share.php</p>
                <p><strong>NO</strong> → Browser/CSS problem or browser extension blocking it</p>
            </div>
        </div>
        
        <div class="status">
            <h3>🔧 Console Tests (Press F12)</h3>
            <p>Run these commands in the console:</p>
            <div class="test-result">
                <code>document.getElementById('designModeBtn')</code><br>
                <small>Should return the button element</small>
            </div>
            <div class="test-result">
                <code>getComputedStyle(document.getElementById('designModeBtn')).display</code><br>
                <small>Should return "block"</small>
            </div>
            <div class="test-result">
                <code>document.getElementById('designModeBtn').getBoundingClientRect()</code><br>
                <small>Should show button position on screen</small>
            </div>
        </div>
        
        <h2 style="margin-top: 40px;">📍 Look at Bottom-Right Corner →</h2>
        <p>You should see a purple gradient button that says "🎨 Design Landing Page"</p>
    </div>
    
    <!-- THE BUTTON - FORCED VISIBLE -->
    <button id="designModeBtn" class="design-mode-btn" onclick="window.location.href='design-landing.php?share=<?= htmlspecialchars($shareToken) ?>'">
        🎨 Design Landing Page
    </button>
    
    <script>
        console.log('='.repeat(60));
        console.log('🔬 BUTTON TEST PAGE LOADED - Cache Buster:', <?= $cacheBuster ?>);
        console.log('='.repeat(60));
        
        const btn = document.getElementById('designModeBtn');
        
        if (btn) {
            console.log('✅ Button element EXISTS in DOM');
            console.log('Button ID:', btn.id);
            console.log('Button class:', btn.className);
            console.log('Button text:', btn.textContent.trim());
            
            const styles = window.getComputedStyle(btn);
            console.log('📊 Button Computed Styles:');
            console.log('  - display:', styles.display);
            console.log('  - visibility:', styles.visibility);
            console.log('  - opacity:', styles.opacity);
            console.log('  - position:', styles.position);
            console.log('  - z-index:', styles.zIndex);
            console.log('  - bottom:', styles.bottom);
            console.log('  - right:', styles.right);
            
            const rect = btn.getBoundingClientRect();
            console.log('📏 Button Position:');
            console.log('  - top:', rect.top);
            console.log('  - right:', rect.right);
            console.log('  - bottom:', rect.bottom);
            console.log('  - left:', rect.left);
            console.log('  - width:', rect.width);
            console.log('  - height:', rect.height);
            
            // Check if visible in viewport
            const isVisible = (
                rect.top >= 0 &&
                rect.left >= 0 &&
                rect.bottom <= window.innerHeight &&
                rect.right <= window.innerWidth
            );
            
            if (isVisible) {
                console.log('✅ Button IS in viewport');
            } else {
                console.log('⚠️ Button is OUTSIDE viewport');
                console.log('Window size:', window.innerWidth, 'x', window.innerHeight);
            }
            
        } else {
            console.error('❌ CRITICAL: Button element NOT FOUND in DOM!');
        }
        
        console.log('='.repeat(60));
        
        // Test click handler
        btn.addEventListener('click', function(e) {
            console.log('🖱️ Button clicked!');
            console.log('Target URL:', this.onclick);
        });
    </script>
</body>
</html>
