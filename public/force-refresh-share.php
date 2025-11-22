<?php
/**
 * Force Refresh Share Page - Cache Buster
 * This page will redirect to your share page with aggressive cache-busting
 */

// Your share token
$shareToken = '01b72796cf745bfab51c3b5e54cfa066';

// Add random query parameter to force browser to reload
$cacheBuster = time() . rand(1000, 9999);

// Set aggressive no-cache headers
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forcing Fresh Page Load...</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 40px;
            border-radius: 15px;
            text-align: center;
        }
        h1 { margin-top: 0; }
        .btn {
            display: inline-block;
            padding: 15px 30px;
            margin: 10px;
            background: white;
            color: #667eea;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            transition: transform 0.2s;
        }
        .btn:hover { transform: scale(1.05); }
        .steps {
            text-align: left;
            margin: 30px 0;
            background: rgba(0, 0, 0, 0.2);
            padding: 20px;
            border-radius: 8px;
        }
        .steps li { margin: 10px 0; }
        code {
            background: rgba(0, 0, 0, 0.3);
            padding: 2px 8px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎨 GrapesJS Design Button - Cache Buster</h1>
        
        <p>The design button exists in your code but may be cached by your browser.</p>
        
        <div class="steps">
            <h3>Quick Fix - Try These In Order:</h3>
            <ol>
                <li><strong>Option 1:</strong> Click the button below (opens with cache-buster)</li>
                <li><strong>Option 2:</strong> Press <code>Ctrl + Shift + Delete</code> → Clear cached images and files → Clear data</li>
                <li><strong>Option 3:</strong> Close ALL browser windows, reopen, then visit share page</li>
                <li><strong>Option 4:</strong> Try a different browser (Chrome/Edge/Firefox)</li>
                <li><strong>Option 5:</strong> Open Incognito/Private window</li>
            </ol>
        </div>

        <h3>🚀 Launch Share Page (Fresh)</h3>
        <a href="share.php?token=<?= $shareToken ?>&_cb=<?= $cacheBuster ?>" class="btn">
            Open Share Page (Cache-Busted)
        </a>

        <h3>🎨 Or Go Direct to Editor</h3>
        <a href="design-landing.php?share=<?= $shareToken ?>" class="btn">
            Open GrapesJS Editor Directly
        </a>

        <div class="steps" style="margin-top: 30px;">
            <h3>What to Look For:</h3>
            <ul>
                <li>Open browser console (F12)</li>
                <li>Look for messages starting with <code>[GrapesJS]</code></li>
                <li>The design button appears at <strong>BOTTOM-RIGHT</strong> corner</li>
                <li>Purple gradient button that says "🎨 Design Landing Page"</li>
                <li>Button only shows if you're logged in AND own the image</li>
            </ul>
        </div>

        <div class="steps" style="background: rgba(255, 255, 255, 0.1);">
            <h3>📋 Your Share Token:</h3>
            <p><code><?= $shareToken ?></code></p>
            <small>Logged in user must own this shared image to see the design button</small>
        </div>
    </div>

    <script>
        // Force reload all cached resources
        if (window.performance && window.performance.navigation.type === 2) {
            window.location.reload(true);
        }
    </script>
</body>
</html>
