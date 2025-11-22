<!DOCTYPE html>
<html>
<head>
    <title>Theme Button Diagnostic</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .box { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; }
        .success { border-left: 5px solid #4caf50; }
        .error { border-left: 5px solid #f44336; }
        pre { background: #f0f0f0; padding: 10px; overflow-x: auto; }
        button { padding: 10px 20px; margin: 5px; cursor: pointer; }
    </style>
</head>
<body>
    <h1>🎨 Theme Button Diagnostic</h1>
    
    <div class="box">
        <h2>Test Navigation Structure</h2>
        <p>Creating a test navigation similar to your app:</p>
        <nav class="nav" style="background: #333; padding: 10px; border-radius: 5px;">
            <div class="nav-left" style="display: inline-block;">
                <span style="color: white;">Gallery | Upload | Folders</span>
            </div>
            <div class="nav-right" style="display: inline-block; float: right;" id="testNav">
                <span style="color: white;">← Buttons should appear here</span>
            </div>
        </nav>
    </div>

    <div class="box success" id="result" style="display:none;">
        <h2>✅ Test Passed!</h2>
        <p>Theme buttons were created successfully. Check the navigation above.</p>
    </div>

    <div class="box error" id="error" style="display:none;">
        <h2>❌ Test Failed</h2>
        <p id="errorMsg"></p>
    </div>

    <div class="box">
        <h2>Steps to Fix in Main App:</h2>
        <ol>
            <li><strong>Clear Browser Cache:</strong> Press <code>Ctrl+Shift+Delete</code> → Check "Cached images and files" → Clear</li>
            <li><strong>Hard Refresh:</strong> Press <code>Ctrl+F5</code> on the main iManage page</li>
            <li><strong>Check Console:</strong> Press <code>F12</code> → Go to "Console" tab → Look for "ThemeManager" messages</li>
            <li><strong>Look for buttons:</strong> Should appear in top-right corner next to language selector/logout</li>
        </ol>
    </div>

    <div class="box">
        <h2>Manual Test:</h2>
        <button onclick="testThemeManager()">Run Theme Manager Test</button>
        <pre id="output"></pre>
    </div>

    <script src="js/theme.js?v=<?php echo time(); ?>"></script>
    <script>
        function log(msg) {
            const output = document.getElementById('output');
            output.textContent += msg + '\n';
            console.log(msg);
        }

        function testThemeManager() {
            document.getElementById('output').textContent = '';
            log('Starting ThemeManager test...\n');
            
            try {
                // Check if ThemeManager class exists
                if (typeof ThemeManager === 'undefined') {
                    throw new Error('ThemeManager class not found! Check if theme.js loaded.');
                }
                log('✅ ThemeManager class found');
                
                // Check if .nav-right exists
                const navRight = document.querySelector('.nav-right');
                if (!navRight) {
                    throw new Error('.nav-right element not found!');
                }
                log('✅ .nav-right element found');
                
                // Create instance
                const themeManager = new ThemeManager();
                log('✅ ThemeManager instance created');
                
                // Wait a moment for buttons to be created
                setTimeout(() => {
                    const themeToggle = document.querySelector('.theme-toggle');
                    const themeSettings = document.querySelector('.theme-settings');
                    
                    if (themeToggle && themeSettings) {
                        log('✅ Theme buttons created successfully!');
                        log('   - Theme Toggle button: ' + themeToggle.innerHTML);
                        log('   - Theme Settings button: ' + themeSettings.innerHTML);
                        document.getElementById('result').style.display = 'block';
                    } else {
                        throw new Error('Buttons not created. Toggle: ' + !!themeToggle + ', Settings: ' + !!themeSettings);
                    }
                }, 1000);
                
            } catch (err) {
                log('❌ Error: ' + err.message);
                document.getElementById('error').style.display = 'block';
                document.getElementById('errorMsg').textContent = err.message;
            }
        }

        // Auto-run test on page load
        window.addEventListener('load', () => {
            setTimeout(testThemeManager, 500);
        });
    </script>
</body>
</html>
