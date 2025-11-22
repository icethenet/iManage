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