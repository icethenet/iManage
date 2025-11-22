<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Design Landing Page - iManage</title>
    
    <!-- GrapesJS CDN -->
    <link rel="stylesheet" href="https://unpkg.com/grapesjs/dist/css/grapes.min.css">
    <link rel="stylesheet" href="https://unpkg.com/grapesjs-blocks-basic/dist/grapesjs-blocks-basic.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow: hidden;
        }
        
        #gjs-container {
            width: 100%;
            height: 100vh;
            display: flex;
        }
        
        /* Left Sidebar for Blocks/Layers/Styles */
        .gjs-sidebar {
            width: 280px;
            background: #2c3e50;
            color: white;
            overflow-y: auto;
            border-right: 1px solid #34495e;
        }
        
        .gjs-sidebar-header {
            padding: 15px;
            background: #34495e;
            font-weight: 600;
            border-bottom: 1px solid #2c3e50;
        }
        
        #blocks, #layers, #styles {
            padding: 10px;
        }
        
        #layers, #styles {
            display: none;
        }
        
        /* Main Canvas Area */
        #gjs-editor {
            flex: 1;
            position: relative;
        }
        
        /* Top Action Bar */
        #panel-actions {
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 99999 !important;
            background: #667eea;
            padding: 12px;
            display: flex !important;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            border-radius: 8px;
        }
        
        #panel-actions button {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            font-size: 15px;
            display: inline-block !important;
        }
        
        .btn-save {
            background: #4caf50 !important;
            color: white !important;
        }
        
        .btn-save:hover {
            background: #45a049 !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .btn-preview {
            background: #2196f3 !important;
            color: white !important;
        }
        
        .btn-preview:hover {
            background: #0b7dda !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .btn-exit {
            background: #f44336 !important;
            color: white !important;
        }
        
        .btn-exit:hover {
            background: #da190b !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        /* Panel Switcher */
        #panel-switcher {
            position: absolute;
            top: 0;
            left: 280px;
            z-index: 100;
            background: #34495e;
            display: flex;
            border-bottom: 1px solid #2c3e50;
        }
        
        #panel-switcher button {
            padding: 12px 20px;
            border: none;
            background: transparent;
            color: white;
            cursor: pointer;
            transition: background 0.3s;
            font-size: 14px;
        }
        
        #panel-switcher button:hover {
            background: #2c3e50;
        }
        
        #panel-switcher button.active {
            background: #667eea;
        }
        
        /* Loading State */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            color: white;
            font-size: 18px;
        }
        
        .loading-spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* GrapesJS Custom Styling */
        .gjs-block {
            min-height: 60px;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
            background: #34495e;
            border: 2px solid transparent;
            transition: all 0.3s;
        }
        
        .gjs-block:hover {
            border-color: #667eea;
            transform: scale(1.05);
        }
        
        .gjs-block__media {
            font-size: 32px;
            margin-bottom: 5px;
        }
        
        .gjs-block-label {
            font-size: 13px;
            color: white;
        }
    </style>
</head>
<body>
    <div id="loading" class="loading-overlay">
        <div>
            <div class="loading-spinner"></div>
            <p>Loading GrapesJS Editor...</p>
        </div>
    </div>
    
    <div id="gjs-container">
        <!-- Left Sidebar -->
        <div class="gjs-sidebar">
            <div class="gjs-sidebar-header">DESIGN TOOLS</div>
            <div id="blocks"></div>
            <div id="layers"></div>
            <div id="styles"></div>
        </div>
        
        <!-- Action Buttons (Outside GrapesJS container to prevent removal) -->
        <div id="custom-actions" style="position: fixed; top: 10px; right: 10px; z-index: 99999; display: flex; gap: 10px;">
            <button class="btn-save" id="btnSave" title="Save your design">💾 Save Design</button>
            <button class="btn-preview" id="btnPreview" title="Preview in new tab">👁️ Preview</button>
            <button class="btn-exit" id="btnExit" title="Exit editor">🚪 Exit</button>
        </div>
        
        <!-- Main Canvas -->
        <div id="gjs-editor">
            <div id="panel-switcher"></div>
            <div id="panel-actions"></div>
        </div>
    </div>
    
    <!-- GrapesJS Scripts -->
    <script src="https://unpkg.com/grapesjs"></script>
    <script src="https://unpkg.com/grapesjs-blocks-basic"></script>
    <script src="https://unpkg.com/grapesjs-plugin-forms"></script>
    <script src="js/grapesjs-manager.js?v=<?= time() ?>"></script>
    
    <script>
        // Get share token from URL
        const urlParams = new URLSearchParams(window.location.search);
        const shareToken = urlParams.get('share');
        
        if (!shareToken) {
            alert('No share token provided!');
            window.location.href = 'index.php';
        }
        
        // Initialize GrapesJS Manager
        const manager = new GrapesJSManager();
        
        // Make manager globally accessible for button handlers
        window.gjsManager = manager;
        
        window.addEventListener('DOMContentLoaded', () => {
            console.log('🎨 GrapesJS Page: DOM Content Loaded');
            console.log('📦 Share Token:', shareToken);
            
            manager.initEditor('gjs-editor', shareToken);
            
            // Hide loading overlay
            setTimeout(() => {
                document.getElementById('loading').style.display = 'none';
                console.log('✅ Loading overlay hidden');
            }, 1000);
            
            // Panel switcher functionality
            document.querySelectorAll('#panel-switcher button').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('#panel-switcher button').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                });
            });
            
            // Set blocks as active by default
            const blocksBtn = document.querySelector('#panel-switcher button');
            if (blocksBtn) blocksBtn.classList.add('active');
            
            // Attach button handlers directly to HTML buttons
            document.getElementById('btnSave').addEventListener('click', async () => {
                console.log('💾 Save button clicked');
                await manager.saveDesign();
            });
            
            document.getElementById('btnPreview').addEventListener('click', () => {
                console.log('👁️ Preview button clicked');
                const html = manager.editor.getHtml();
                const css = manager.editor.getCss();
                const previewWindow = window.open('', '_blank');
                previewWindow.document.write(`
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <style>${css}</style>
                    </head>
                    <body>${html}</body>
                    </html>
                `);
                previewWindow.document.close();
            });
            
            document.getElementById('btnExit').addEventListener('click', () => {
                console.log('🚪 Exit button clicked');
                if (confirm('Exit design mode? Any unsaved changes will be lost.')) {
                    window.location.href = `share.php?token=${shareToken}`;
                }
            });
            
            console.log('✅ Button handlers attached successfully');
        });
        
        // Prevent accidental navigation
        window.addEventListener('beforeunload', (e) => {
            e.preventDefault();
            e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
        });
    </script>
</body>
</html>
