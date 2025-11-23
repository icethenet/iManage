<?php
session_start();

// Require authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$pageId = $_GET['id'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Designer - iManage</title>
    
    <!-- GrapesJS CDN -->
    <link rel="stylesheet" href="https://unpkg.com/grapesjs/dist/css/grapes.min.css">
    <link rel="stylesheet" href="https://unpkg.com/grapesjs-blocks-basic/dist/grapesjs-blocks-basic.min.css">
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    
    <style>
        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            overflow: hidden;
        }
        
        .panel__top {
            padding: 0;
            display: flex;
            position: relative;
            justify-content: space-between;
            align-items: center;
            background-color: #444;
            border-bottom: 1px solid #333;
            height: 40px;
            flex-wrap: nowrap;
            overflow: hidden;
        }
        
        .panel__title {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 0 10px;
            flex-grow: 1;
            max-width: 400px;
        }
        
        .panel__title label {
            color: #ddd;
            font-size: 12px;
            white-space: nowrap;
        }
        
        .panel__title input {
            flex: 1;
            padding: 5px 8px;
            border: 1px solid #555;
            background: #333;
            color: #fff;
            border-radius: 3px;
            font-size: 13px;
            min-width: 0;
        }
        
        .panel__title input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .panel__basic-actions {
            display: flex;
            gap: 2px;
            padding: 0 5px;
            flex-shrink: 0;
        }
        
        .panel__devices {
            display: flex;
            gap: 2px;
            flex-shrink: 0;
        }
        
        .panel__switcher {
            display: flex;
            gap: 2px;
            flex-shrink: 0;
        }
        
        .panel__actions {
            display: flex;
            gap: 2px;
            padding: 0 5px;
            flex-shrink: 0;
        }
        
        .editor-row {
            display: flex;
            justify-content: flex-start;
            align-items: stretch;
            flex-wrap: nowrap;
            height: calc(100vh - 40px);
        }
        
        .editor-canvas {
            flex-grow: 1;
            position: relative;
        }
        
        .panel__right {
            flex-basis: 250px;
            flex-shrink: 0;
            position: relative;
            overflow-y: auto;
            background-color: #444;
            border-left: 1px solid #333;
            max-width: 250px;
        }
        
        .panel__left {
            flex-basis: 250px;
            flex-shrink: 0;
            position: relative;
            overflow-y: auto;
            background-color: #444;
            border-right: 1px solid #333;
            max-width: 250px;
        }
        
        .layers-container, .styles-container, .traits-container, .blocks-container {
            padding: 10px;
            color: #fff;
        }
        
        #gjs {
            height: 100%;
            overflow: hidden;
        }
        
        /* Ensure canvas frame is visible and editable */
        .gjs-cv-canvas {
            background-color: #fff;
            width: 100% !important;
            height: 100% !important;
        }
        
        .gjs-frame {
            width: 100%;
            height: 100%;
        }
        
        /* Fix GrapesJS button sizing */
        .gjs-pn-btn {
            min-height: 30px;
            padding: 5px 10px;
        }
        
        /* Prevent GrapesJS from absolute positioning panels */
        .gjs-pn-panel {
            position: relative !important;
        }
        
        /* Override GrapesJS default panel positioning */
        .gjs-cv-canvas {
            width: 100% !important;
            left: 0 !important;
            right: 0 !important;
        }
        
        /* AI Text Spinner Modal */
        .ai-spinner-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 10000;
            align-items: center;
            justify-content: center;
        }
        
        .ai-spinner-modal.active {
            display: flex;
        }
        
        .ai-spinner-content {
            background: #2c2c2c;
            border-radius: 8px;
            padding: 25px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }
        
        .ai-spinner-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            color: #fff;
        }
        
        .ai-spinner-header h2 {
            margin: 0;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .ai-spinner-close {
            background: none;
            border: none;
            color: #fff;
            font-size: 24px;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
        }
        
        .ai-spinner-close:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .ai-spinner-mode-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #444;
        }
        
        .ai-mode-tab {
            padding: 10px 20px;
            background: none;
            border: none;
            color: #999;
            cursor: pointer;
            font-size: 14px;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .ai-mode-tab:hover {
            color: #ddd;
        }
        
        .ai-mode-tab.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }
        
        .ai-mode-content {
            display: none;
        }
        
        .ai-mode-content.active {
            display: block;
        }
        
        .ai-spinner-section {
            margin-bottom: 20px;
        }
        
        .ai-spinner-section label {
            display: block;
            color: #ddd;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .ai-spinner-section textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #555;
            background: #1a1a1a;
            color: #fff;
            border-radius: 4px;
            font-family: inherit;
            font-size: 14px;
            min-height: 100px;
            resize: vertical;
        }
        
        .ai-spinner-section input[type="text"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #555;
            background: #1a1a1a;
            color: #fff;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .ai-spinner-section select {
            width: 100%;
            padding: 10px;
            border: 1px solid #555;
            background: #1a1a1a;
            color: #fff;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .ai-spinner-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .ai-spinner-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }
        
        .ai-spinner-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .ai-spinner-btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .ai-spinner-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .ai-spinner-btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        .ai-spinner-btn-secondary {
            background: #444;
            color: #fff;
        }
        
        .ai-spinner-btn-secondary:hover {
            background: #555;
        }
        
        .ai-spinner-result {
            margin-top: 20px;
            padding: 15px;
            background: #1a1a1a;
            border: 1px solid #555;
            border-radius: 4px;
            color: #fff;
            display: none;
        }
        
        .ai-spinner-result.active {
            display: block;
        }
        
        .ai-spinner-result-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .ai-spinner-result-text {
            line-height: 1.6;
            white-space: pre-wrap;
        }
        
        .ai-spinner-loading {
            display: none;
            text-align: center;
            padding: 20px;
            color: #667eea;
        }
        
        .ai-spinner-loading.active {
            display: block;
        }
        
        .ai-spinner-loading i {
            font-size: 24px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- AI Text Spinner Modal -->
    <div id="aiSpinnerModal" class="ai-spinner-modal">
        <div class="ai-spinner-content">
            <div class="ai-spinner-header">
                <h2><i class="fa fa-magic"></i> AI Content Assistant</h2>
                <button class="ai-spinner-close" onclick="closeAISpinner()">&times;</button>
            </div>
            
            <!-- Mode Tabs -->
            <div class="ai-spinner-mode-tabs">
                <button class="ai-mode-tab active" onclick="switchAIMode('rewrite')">
                    <i class="fa fa-refresh"></i> Rewrite Text
                </button>
                <button class="ai-mode-tab" onclick="switchAIMode('generate')">
                    <i class="fa fa-lightbulb-o"></i> Generate Content
                </button>
            </div>
            
            <!-- Rewrite Mode -->
            <div id="rewriteMode" class="ai-mode-content active">
                <div class="ai-spinner-section">
                    <label for="originalText">Original Text</label>
                    <textarea id="originalText" placeholder="Paste or type your text here..."></textarea>
                </div>
                
                <div class="ai-spinner-options">
                    <div class="ai-spinner-section">
                        <label for="spinTone">Tone</label>
                        <select id="spinTone">
                            <option value="professional">Professional</option>
                            <option value="casual">Casual</option>
                            <option value="friendly">Friendly</option>
                            <option value="formal">Formal</option>
                            <option value="persuasive">Persuasive</option>
                            <option value="informative">Informative</option>
                        </select>
                    </div>
                    
                    <div class="ai-spinner-section">
                        <label for="spinLength">Length</label>
                        <select id="spinLength">
                            <option value="same">Same Length</option>
                            <option value="shorter">Shorter</option>
                            <option value="longer">Longer</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Generate Mode -->
            <div id="generateMode" class="ai-mode-content">
                <div class="ai-spinner-section">
                    <label for="generateTopic">Topic / Description</label>
                    <input type="text" id="generateTopic" placeholder="e.g., Benefits of organic food, How to train a puppy...">
                </div>
                
                <div class="ai-spinner-section">
                    <label for="generateInstructions">Additional Instructions (optional)</label>
                    <textarea id="generateInstructions" placeholder="e.g., Include 3 bullet points, mention health benefits, keep it under 200 words..." style="min-height: 60px;"></textarea>
                </div>
                
                <div class="ai-spinner-options">
                    <div class="ai-spinner-section">
                        <label for="generateTone">Tone</label>
                        <select id="generateTone">
                            <option value="professional">Professional</option>
                            <option value="casual">Casual</option>
                            <option value="friendly">Friendly</option>
                            <option value="formal">Formal</option>
                            <option value="persuasive">Persuasive</option>
                            <option value="informative">Informative</option>
                        </select>
                    </div>
                    
                    <div class="ai-spinner-section">
                        <label for="generateLength">Length</label>
                        <select id="generateLength">
                            <option value="short">Short (1-2 paragraphs)</option>
                            <option value="medium" selected>Medium (3-4 paragraphs)</option>
                            <option value="long">Long (5+ paragraphs)</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="ai-spinner-loading" id="aiSpinnerLoading">
                <i class="fa fa-spinner"></i>
                <p>Generating variations...</p>
            </div>
            
            <div class="ai-spinner-result" id="aiSpinnerResult">
                <div class="ai-spinner-result-header">
                    <strong>Rewritten Text:</strong>
                    <button class="ai-spinner-btn ai-spinner-btn-secondary" onclick="copySpunText()">
                        <i class="fa fa-copy"></i> Copy
                    </button>
                </div>
                <div class="ai-spinner-result-text" id="spunText"></div>
            </div>
            
            <div class="ai-spinner-actions">
                <button class="ai-spinner-btn ai-spinner-btn-secondary" onclick="closeAISpinner()">Cancel</button>
                <button class="ai-spinner-btn ai-spinner-btn-primary" onclick="spinText()" id="spinButton">
                    <i class="fa fa-magic"></i> Spin Text
                </button>
                <button class="ai-spinner-btn ai-spinner-btn-primary" onclick="applySpunText()" id="applyButton" style="display: none;">
                    <i class="fa fa-check"></i> Apply to Page
                </button>
            </div>
        </div>
    </div>
    

    <div class="panel__top">
        <div class="panel__basic-actions"></div>
        <div class="panel__devices"></div>
        <div class="panel__title">
            <label for="pageTitle">Page Title:</label>
            <input type="text" id="pageTitle" placeholder="Enter page title..." value="Untitled Page" onchange="updateDocumentTitle()">
        </div>
        <div class="panel__switcher"></div>
        <div class="panel__actions"></div>
    </div>
    
    <div class="editor-row">
        <div class="panel__left">
            <div class="blocks-container"></div>
        </div>
        
        <div class="editor-canvas">
            <div id="gjs"></div>
        </div>
        
        <div class="panel__right">
            <div class="layers-container"></div>
            <div class="styles-container"></div>
            <div class="traits-container"></div>
        </div>
    </div>
    
    <!-- GrapesJS Scripts -->
    <script src="https://unpkg.com/grapesjs"></script>
    <script src="https://unpkg.com/grapesjs-blocks-basic"></script>
    <!-- HTML2Canvas for page screenshots -->
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    
    <script>
        console.log('🚀 Script started');
        let pageId = <?= json_encode($pageId) ?>;
        let editor = null;
        
        console.log('📄 Page ID:', pageId);
        
        // Initialize GrapesJS
        console.log('⚙️ Initializing GrapesJS...');
        editor = grapesjs.init({
            container: '#gjs',
            height: '100%',
            width: 'auto',
            fromElement: false,
            storageManager: false,
            plugins: ['gjs-blocks-basic'],
            pluginsOpts: {
                'gjs-blocks-basic': {}
            },
            
            // Disable all default panels
            panels: { defaults: [] },
            
            blockManager: {
                appendTo: '.blocks-container'
            },
            
            canvas: {
                styles: [
                    'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap'
                ],
                scripts: []
            },
            
            canvas: {
                styles: [
                    'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap'
                ],
                scripts: []
            },
            layerManager: {
                appendTo: '.layers-container'
            },
            selectorManager: {
                appendTo: '.styles-container'
            },
            styleManager: {
                appendTo: '.styles-container',
                sectors: [{
                    name: 'General',
                    open: false,
                    buildProps: ['float', 'display', 'position', 'top', 'right', 'left', 'bottom']
                },{
                    name: 'Dimension',
                    open: false,
                    buildProps: ['width', 'height', 'max-width', 'min-height', 'margin', 'padding'],
                },{
                    name: 'Typography',
                    open: false,
                    buildProps: ['font-family', 'font-size', 'font-weight', 'letter-spacing', 'color', 'line-height', 'text-align'],
                },{
                    name: 'Decorations',
                    open: false,
                    buildProps: ['background-color', 'border-radius', 'border', 'box-shadow', 'background'],
                }]
            },
            traitManager: {
                appendTo: '.traits-container'
            },
            assetManager: {
                upload: false,
                autoAdd: true,
                assets: []
            }
        });
        
        // Customize panels
        const panelManager = editor.Panels;
        
        // Remove default panels to rebuild them
        panelManager.removePanel('devices-c');
        panelManager.removePanel('options');
        
        // Add custom toolbar panel
        panelManager.addPanel({
            id: 'panel-top',
            el: '.panel__top'
        });
        
        panelManager.addPanel({
            id: 'basic-actions',
            el: '.panel__basic-actions',
            buttons: [
                {
                    id: 'visibility',
                    active: true,
                    className: 'btn-toggle-borders',
                    label: '<i class="fa fa-clone"></i>',
                    command: 'sw-visibility',
                    context: 'sw-visibility',
                    attributes: { title: 'Toggle Borders' }
                },
                {
                    id: 'export',
                    className: 'btn-open-export',
                    label: '<i class="fa fa-code"></i>',
                    command: 'export-template',
                    attributes: { title: 'View Code' }
                }
            ]
        });
        
        panelManager.addPanel({
            id: 'panel-devices',
            el: '.panel__devices',
            buttons: [{
                id: 'device-desktop',
                label: '<i class="fa fa-desktop"></i>',
                command: 'set-device-desktop',
                active: true,
                togglable: false,
                attributes: { title: 'Desktop' }
            }, {
                id: 'device-tablet',
                label: '<i class="fa fa-tablet"></i>',
                command: 'set-device-tablet',
                togglable: false,
                attributes: { title: 'Tablet' }
            }, {
                id: 'device-mobile',
                label: '<i class="fa fa-mobile"></i>',
                command: 'set-device-mobile',
                togglable: false,
                attributes: { title: 'Mobile' }
            }]
        });
        
        panelManager.addPanel({
            id: 'panel-switcher',
            el: '.panel__switcher',
            buttons: []
        });
        
        panelManager.addPanel({
            id: 'panel-actions',
            el: '.panel__actions',
            buttons: [{
                id: 'undo',
                className: 'btn-undo',
                label: '<i class="fa fa-undo"></i>',
                command: 'core:undo',
                attributes: { title: 'Undo' }
            }, {
                id: 'redo',
                className: 'btn-redo',
                label: '<i class="fa fa-repeat"></i>',
                command: 'core:redo',
                attributes: { title: 'Redo' }
            }, {
                id: 'my-images',
                className: 'btn-my-images',
                label: '<i class="fa fa-image"></i>',
                command: 'open-assets',
                attributes: { title: 'My Gallery Images' }
            }, {
                id: 'ai-text-spinner',
                className: 'btn-ai-spinner',
                label: '<i class="fa fa-magic"></i>',
                command: 'open-ai-spinner',
                attributes: { title: 'AI Text Spinner' }
            }, {
                id: 'preview',
                className: 'btn-preview',
                label: '<i class="fa fa-eye"></i>',
                command: 'preview',
                togglable: true,
                attributes: { title: 'Toggle Preview' }
            }, {
                id: 'save-db',
                className: 'btn-save',
                label: '<i class="fa fa-floppy-o"></i>',
                command: 'save-db',
                attributes: { title: 'Save Design' }
            }, {
                id: 'exit-editor',
                className: 'btn-exit',
                label: '<i class="fa fa-sign-out"></i>',
                command: 'exit-editor',
                attributes: { title: 'Exit Editor' }
            }]
        });
        
        // Define device commands
        const commands = editor.Commands;
        commands.add('set-device-desktop', {
            run: editor => editor.setDevice('Desktop')
        });
        commands.add('set-device-tablet', {
            run: editor => editor.setDevice('Tablet')
        });
        commands.add('set-device-mobile', {
            run: editor => editor.setDevice('Mobile portrait')
        });
        
        commands.add('open-ai-spinner', {
            run: function() {
                openAISpinner();
            }
        });
        
        commands.add('save-db', {
            run: function() {
                savePage();
            }
        });
        
        commands.add('exit-editor', {
            run: function() {
                exitEditor();
            }
        });
        
        // Add custom gallery blocks
        const blockManager = editor.BlockManager;
        
        // Image Gallery Grid Block
        blockManager.add('image-gallery-grid', {
            label: '<i class="fa fa-th"></i><div>Gallery Grid</div>',
            category: 'iManage Gallery',
            content: `
                <div class="imanage-gallery-grid" data-gjs-type="imanage-gallery">
                    <style>
                        .imanage-gallery-grid {
                            display: grid;
                            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                            gap: 20px;
                            padding: 20px;
                        }
                        .imanage-gallery-grid img {
                            width: 100%;
                            height: 250px;
                            object-fit: cover;
                            border-radius: 8px;
                            transition: transform 0.3s;
                        }
                        .imanage-gallery-grid img:hover {
                            transform: scale(1.05);
                        }
                    </style>
                    <p style="text-align: center; color: #999;">Loading your images...</p>
                </div>
            `,
            attributes: { title: 'Dynamic image gallery grid from your uploads' }
        });
        
        // Image Gallery Masonry Block
        blockManager.add('image-gallery-masonry', {
            label: '<i class="fa fa-th-large"></i><div>Gallery Masonry</div>',
            category: 'iManage Gallery',
            content: `
                <div class="imanage-gallery-masonry" data-gjs-type="imanage-gallery">
                    <style>
                        .imanage-gallery-masonry {
                            column-count: 3;
                            column-gap: 20px;
                            padding: 20px;
                        }
                        .imanage-gallery-masonry img {
                            width: 100%;
                            margin-bottom: 20px;
                            border-radius: 8px;
                            break-inside: avoid;
                        }
                        @media (max-width: 768px) {
                            .imanage-gallery-masonry {
                                column-count: 2;
                            }
                        }
                        @media (max-width: 480px) {
                            .imanage-gallery-masonry {
                                column-count: 1;
                            }
                        }
                    </style>
                    <p style="text-align: center; color: #999;">Loading your images...</p>
                </div>
            `,
            attributes: { title: 'Dynamic masonry gallery from your uploads' }
        });
        
        // Image Gallery Slider Block
        blockManager.add('image-gallery-slider', {
            label: '<i class="fa fa-picture-o"></i><div>Gallery Slider</div>',
            category: 'iManage Gallery',
            content: `
                <div class="imanage-gallery-slider" data-gjs-type="imanage-gallery">
                    <style>
                        .imanage-gallery-slider {
                            position: relative;
                            max-width: 800px;
                            margin: 40px auto;
                            overflow: hidden;
                            border-radius: 12px;
                            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
                        }
                        .imanage-gallery-slider img {
                            width: 100%;
                            display: block;
                        }
                    </style>
                    <p style="text-align: center; color: #999; padding: 60px 20px;">Loading your images...</p>
                </div>
            `,
            attributes: { title: 'Dynamic image slider from your uploads' }
        });
        
        // Video Gallery Grid Block
        blockManager.add('video-gallery-grid', {
            label: '<i class="fa fa-film"></i><div>Video Grid</div>',
            category: 'iManage Gallery',
            content: `
                <div class="imanage-video-gallery-grid" data-gjs-type="imanage-video-gallery">
                    <style>
                        .imanage-video-gallery-grid {
                            display: grid;
                            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                            gap: 20px;
                            padding: 20px;
                        }
                        .imanage-video-gallery-grid video {
                            width: 100%;
                            height: 250px;
                            object-fit: cover;
                            border-radius: 8px;
                            background: #000;
                        }
                        .imanage-video-gallery-grid .video-item {
                            position: relative;
                            border-radius: 8px;
                            overflow: hidden;
                            background: #000;
                        }
                        .imanage-video-gallery-grid .video-item:hover {
                            transform: scale(1.02);
                            transition: transform 0.3s;
                        }
                    </style>
                    <p style="text-align: center; color: #999;">Loading your videos...</p>
                </div>
            `,
            attributes: { title: 'Dynamic video gallery grid from your uploads' }
        });
        
        // Video Gallery List Block
        blockManager.add('video-gallery-list', {
            label: '<i class="fa fa-list"></i><div>Video List</div>',
            category: 'iManage Gallery',
            content: `
                <div class="imanage-video-gallery-list" data-gjs-type="imanage-video-gallery">
                    <style>
                        .imanage-video-gallery-list {
                            max-width: 900px;
                            margin: 20px auto;
                            padding: 20px;
                        }
                        .imanage-video-gallery-list .video-item {
                            margin-bottom: 30px;
                            border-radius: 12px;
                            overflow: hidden;
                            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                            background: #000;
                        }
                        .imanage-video-gallery-list video {
                            width: 100%;
                            height: auto;
                            display: block;
                        }
                    </style>
                    <p style="text-align: center; color: #999;">Loading your videos...</p>
                </div>
            `,
            attributes: { title: 'Dynamic video list from your uploads' }
        });
        
        // Video Gallery Featured Block
        blockManager.add('video-gallery-featured', {
            label: '<i class="fa fa-star"></i><div>Featured Video</div>',
            category: 'iManage Gallery',
            content: `
                <div class="imanage-video-gallery-featured" data-gjs-type="imanage-video-gallery" data-max-videos="1">
                    <style>
                        .imanage-video-gallery-featured {
                            max-width: 1200px;
                            margin: 40px auto;
                            padding: 20px;
                        }
                        .imanage-video-gallery-featured video {
                            width: 100%;
                            height: auto;
                            border-radius: 12px;
                            box-shadow: 0 8px 32px rgba(0,0,0,0.2);
                        }
                    </style>
                    <p style="text-align: center; color: #999; padding: 60px 20px;">Loading featured video...</p>
                </div>
            `,
            attributes: { title: 'Featured video from your uploads' }
        });
        
        // Navigation Block
        blockManager.add('nav-menu', {
            label: '<i class="fa fa-bars"></i><div>Navigation Menu</div>',
            category: 'Navigation',
            content: `
                <nav class="nav-menu nav-style-default" data-gjs-type="nav-menu" data-nav-style="default" data-nav-align="center">
                    <style>
                        .nav-menu {
                            padding: 15px 30px;
                            border-radius: 8px;
                            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                            display: flex;
                            gap: 10px;
                            flex-wrap: wrap;
                            justify-content: center;
                            align-items: center;
                        }
                        
                        /* Default - Gradient Purple */
                        .nav-menu.nav-style-default {
                            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        }
                        .nav-menu.nav-style-default a {
                            color: white;
                            background: rgba(255, 255, 255, 0.2);
                            border: 1px solid rgba(255, 255, 255, 0.3);
                        }
                        .nav-menu.nav-style-default a:hover {
                            background: rgba(255, 255, 255, 0.3);
                        }
                        
                        /* Dark Style */
                        .nav-menu.nav-style-dark {
                            background: #2c3e50;
                        }
                        .nav-menu.nav-style-dark a {
                            color: #ecf0f1;
                            background: rgba(255, 255, 255, 0.1);
                            border: 1px solid rgba(255, 255, 255, 0.2);
                        }
                        .nav-menu.nav-style-dark a:hover {
                            background: rgba(255, 255, 255, 0.2);
                        }
                        
                        /* Light Style */
                        .nav-menu.nav-style-light {
                            background: #f8f9fa;
                            border: 1px solid #dee2e6;
                        }
                        .nav-menu.nav-style-light a {
                            color: #495057;
                            background: white;
                            border: 1px solid #dee2e6;
                        }
                        .nav-menu.nav-style-light a:hover {
                            background: #e9ecef;
                        }
                        
                        /* Blue Style */
                        .nav-menu.nav-style-blue {
                            background: linear-gradient(135deg, #2193b0 0%, #6dd5ed 100%);
                        }
                        .nav-menu.nav-style-blue a {
                            color: white;
                            background: rgba(255, 255, 255, 0.2);
                            border: 1px solid rgba(255, 255, 255, 0.3);
                        }
                        .nav-menu.nav-style-blue a:hover {
                            background: rgba(255, 255, 255, 0.3);
                        }
                        
                        /* Common link styles */
                        .nav-menu a {
                            text-decoration: none;
                            padding: 10px 20px;
                            border-radius: 5px;
                            font-weight: 500;
                            transition: all 0.3s ease;
                            display: inline-block;
                        }
                        .nav-menu a:hover {
                            transform: translateY(-2px);
                            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
                        }
                        
                        @media (max-width: 768px) {
                            .nav-menu {
                                flex-direction: column;
                                padding: 10px 15px;
                            }
                            .nav-menu a {
                                width: 100%;
                                text-align: center;
                            }
                        }
                    </style>
                    <a href="#home">Home</a>
                    <a href="#about">About</a>
                    <a href="#services">Services</a>
                    <a href="#contact">Contact</a>
                </nav>
            `,
            attributes: { title: 'Responsive navigation menu with auto-styled link buttons. Drag links into the nav to add more.' }
        });
        
        // Define custom component type for gallery
        editor.DomComponents.addType('imanage-gallery', {
            model: {
                defaults: {
                    traits: [
                        {
                            type: 'select',
                            label: 'Folder',
                            name: 'data-folder',
                            options: [
                                { value: '', name: 'All Images' }
                            ]
                        },
                        {
                            type: 'number',
                            label: 'Max Images',
                            name: 'data-max-images',
                            placeholder: '20',
                            min: 1,
                            max: 100
                        }
                    ],
                    'script-props': ['data-folder', 'data-max-images']
                }
            }
        });
        
        // Define custom component type for video gallery
        editor.DomComponents.addType('imanage-video-gallery', {
            model: {
                defaults: {
                    traits: [
                        {
                            type: 'select',
                            label: 'Folder',
                            name: 'data-folder',
                            options: [
                                { value: '', name: 'All Videos' }
                            ]
                        },
                        {
                            type: 'number',
                            label: 'Max Videos',
                            name: 'data-max-videos',
                            placeholder: '10',
                            min: 1,
                            max: 50
                        },
                        {
                            type: 'checkbox',
                            label: 'Show Controls',
                            name: 'data-show-controls',
                            valueTrue: 'true',
                            valueFalse: 'false'
                        },
                        {
                            type: 'checkbox',
                            label: 'Auto Play',
                            name: 'data-autoplay',
                            valueTrue: 'true',
                            valueFalse: 'false'
                        },
                        {
                            type: 'checkbox',
                            label: 'Muted',
                            name: 'data-muted',
                            valueTrue: 'true',
                            valueFalse: 'false'
                        }
                    ],
                    'script-props': ['data-folder', 'data-max-videos', 'data-show-controls', 'data-autoplay', 'data-muted']
                }
            }
        });
        
        // Define custom component type for navigation menu
        editor.DomComponents.addType('nav-menu', {
            model: {
                defaults: {
                    traits: [
                        {
                            type: 'select',
                            label: 'Style',
                            name: 'data-nav-style',
                            options: [
                                { value: 'default', name: 'Gradient Purple' },
                                { value: 'dark', name: 'Dark' },
                                { value: 'light', name: 'Light' },
                                { value: 'blue', name: 'Blue' }
                            ],
                            changeProp: 1
                        },
                        {
                            type: 'select',
                            label: 'Alignment',
                            name: 'data-nav-align',
                            options: [
                                { value: 'center', name: 'Center' },
                                { value: 'left', name: 'Left' },
                                { value: 'right', name: 'Right' }
                            ],
                            changeProp: 1
                        }
                    ],
                    droppable: 'a, [data-gjs-type="link"]' // Only accept links
                },
                init() {
                    this.on('change:data-nav-style', this.updateNavStyle);
                    this.on('change:data-nav-align', this.updateNavAlign);
                },
                updateNavStyle() {
                    const style = this.get('data-nav-style') || 'default';
                    const el = this.view?.el;
                    if (!el) return;
                    
                    // Remove existing style classes
                    el.classList.remove('nav-style-default', 'nav-style-dark', 'nav-style-light', 'nav-style-blue');
                    el.classList.add('nav-style-' + style);
                },
                updateNavAlign() {
                    const align = this.get('data-nav-align') || 'center';
                    const el = this.view?.el;
                    if (!el) return;
                    
                    el.style.justifyContent = align === 'left' ? 'flex-start' : align === 'right' ? 'flex-end' : 'center';
                }
            }
        });
        
        // Enhance the default link component to allow editing link text
        const defaultLinkType = editor.DomComponents.getType('link');
        editor.DomComponents.addType('link', {
            extend: 'link',
            model: {
                defaults: {
                    ...defaultLinkType.model.prototype.defaults,
                    traits: [
                        {
                            type: 'text',
                            label: 'Link Text',
                            name: 'link-text',
                            changeProp: 1
                        },
                        {
                            type: 'text',
                            label: 'URL',
                            name: 'href'
                        },
                        {
                            type: 'select',
                            label: 'Target',
                            name: 'target',
                            options: [
                                { value: '', name: 'Same window' },
                                { value: '_blank', name: 'New window' }
                            ]
                        }
                    ]
                },
                init() {
                    this.on('change:link-text', this.updateLinkText);
                    // Set initial text from content if not already set
                    if (!this.get('link-text')) {
                        const comps = this.get('components');
                        if (comps && comps.length > 0) {
                            const firstComp = comps.models ? comps.models[0] : comps.at(0);
                            if (firstComp && firstComp.get) {
                                const content = firstComp.get('content');
                                if (content && content !== 'Link') {
                                    this.set('link-text', content);
                                }
                            }
                        }
                    }
                },
                updateLinkText() {
                    const text = this.get('link-text');
                    if (text) {
                        this.components(text);
                    }
                }
            }
        });
        
        // Script to load images dynamically when page renders
        const galleryScript = function() {
            console.log('🖼️ Gallery script started');
            
            // Wait for DOM to be ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', loadGalleries);
            } else {
                loadGalleries();
            }
            
            async function loadGalleries() {
                console.log('🔍 Looking for galleries...');
                const galleries = document.querySelectorAll('[data-gjs-type="imanage-gallery"]');
                console.log('📊 Found galleries:', galleries.length);
                
                if (galleries.length === 0) {
                    console.warn('⚠️ No gallery elements found on page');
                    return;
                }
                
                for (let i = 0; i < galleries.length; i++) {
                    const gallery = galleries[i];
                    console.log(`🔍 Processing gallery ${i + 1}`);
                    const folder = gallery.getAttribute('data-folder') || '';
                    const maxImages = parseInt(gallery.getAttribute('data-max-images')) || 20;
                    
                    try {
                        console.log('📡 Fetching images from API...');
                        const response = await fetch('api.php?action=getmyimages');
                        console.log('📥 Response status:', response.status);
                        
                        const data = await response.json();
                        console.log('✅ API Response:', data);
                        
                        if (data.success && data.assets) {
                            let images = data.assets.filter(asset => asset.type === 'image');
                            console.log(`🖼️ Total images: ${images.length}`);
                            
                            // Filter by folder if specified
                            if (folder) {
                                images = images.filter(asset => asset.folder === folder);
                                console.log(`📁 After folder filter: ${images.length}`);
                            }
                            
                            // Limit number of images
                            images = images.slice(0, maxImages);
                            console.log(`✂️ After limit: ${images.length}`);
                            
                            // Clear loading message but preserve style tag
                            const styleTag = gallery.querySelector('style');
                            gallery.innerHTML = styleTag ? styleTag.outerHTML : '';
                            
                            // Add images
                            images.forEach((asset, imgIndex) => {
                                console.log(`➕ Adding image ${imgIndex + 1}: ${asset.name}`);
                                const img = document.createElement('img');
                                img.src = asset.src;
                                img.alt = asset.name;
                                img.loading = 'lazy';
                                gallery.appendChild(img);
                            });
                            
                            console.log(`✅ Gallery ${i + 1} loaded with ${images.length} images`);
                        } else {
                            console.warn('⚠️ No images in API response');
                            gallery.innerHTML = '<p style="text-align: center; color: orange;">No images found</p>';
                        }
                    } catch (error) {
                        console.error('❌ Failed to load gallery images:', error);
                        gallery.innerHTML = '<p style="text-align: center; color: red;">Failed to load images</p>';
                    }
                }
            }
        };
        
        // Function to inject and run gallery script in canvas
        function injectGalleryScript() {
            try {
                const canvasDoc = editor.Canvas.getDocument();
                const canvasWin = editor.Canvas.getWindow();
                
                // Remove old script if exists
                const oldScript = canvasDoc.getElementById('gallery-loader');
                if (oldScript) oldScript.remove();
                
                // Create and inject new script
                const script = canvasDoc.createElement('script');
                script.id = 'gallery-loader';
                script.innerHTML = `(${galleryScript.toString()})();`;
                canvasDoc.body.appendChild(script);
                
                console.log('✅ Gallery script injected into canvas');
            } catch (error) {
                console.error('❌ Failed to inject gallery script:', error);
            }
        }
        
        // Inject script when editor loads
        editor.on('load', () => {
            console.log('🎨 Editor loaded, injecting gallery script');
            setTimeout(injectGalleryScript, 500);
        });
        
        // Re-inject when components change
        editor.on('component:add', (component) => {
            if (component.get('type') === 'imanage-gallery') {
                console.log('📦 Gallery block added, re-injecting script');
                setTimeout(injectGalleryScript, 100);
            }
        });
        
        // Re-inject when canvas changes
        editor.on('canvas:content:updated', () => {
            console.log('🔄 Canvas content updated');
            setTimeout(injectGalleryScript, 100);
        });
        
        // Define custom commands
        commands.add('save-db', {
            run: () => {
                savePage();
            }
        });
        
        commands.add('exit-editor', {
            run: () => {
                exitEditor();
            }
        });
        
        // Load user's images into Asset Manager
        loadUserImages();
        
        // Load existing page if ID provided
        if (pageId) {
            loadPage(pageId);
        } else {
            // Add starter content for new pages
            editor.setComponents(`
                <div style="padding: 20px; text-align: center;">
                    <h1>Welcome to Your Page</h1>
                    <p>Drag blocks from the left panel to start building your page.</p>
                </div>
            `);
        }
        
        async function loadUserImages() {
            console.log('📸 Loading user images into Asset Manager...');
            try {
                const response = await fetch('api.php?action=getmyimages');
                const data = await response.json();
                
                if (data.success && data.assets) {
                    console.log(`✅ Loaded ${data.assets.length} images`);
                    console.log('🔍 First asset path:', data.assets[0]?.src);
                    
                    // Add images to Asset Manager
                    const assetManager = editor.AssetManager;
                    data.assets.forEach(asset => {
                        assetManager.add({
                            type: asset.type,
                            src: asset.src,
                            height: asset.type === 'image' ? 'auto' : 200,
                            name: asset.name
                        });
                    });
                } else {
                    console.warn('⚠️ No images found or failed to load');
                }
            } catch (error) {
                console.error('❌ Error loading images:', error);
            }
        }
        
        async function loadPage(id) {
            try {
                const response = await fetch(`api.php?action=loadCustomPage&id=${id}`);
                const data = await response.json();
                
                if (data.success && data.page) {
                    // Set page title
                    if (data.page.page_title) {
                        document.getElementById('pageTitle').value = data.page.page_title;
                    }
                    
                    if (data.page.grapesjs_data) {
                        editor.loadProjectData(JSON.parse(data.page.grapesjs_data));
                    } else if (data.page.html_content) {
                        editor.setComponents(data.page.html_content);
                        if (data.page.css_content) {
                            editor.setStyle(data.page.css_content);
                        }
                    }
                    console.log('✅ Page loaded successfully');
                }
            } catch (error) {
                console.error('❌ Load error:', error);
            }
        }
        
        function updateDocumentTitle() {
            const title = document.getElementById('pageTitle').value || 'Untitled Page';
            document.title = title + ' - Page Designer - iManage';
        }
        
        async function savePage() {
            try {
                console.log('💾 Starting save...');
                let html = editor.getHtml();
                const css = editor.getCss();
                const projectData = editor.getProjectData();
                const title = document.getElementById('pageTitle').value || 'Untitled Page';
                
                // Add gallery loader script if page contains imanage galleries
                if (html.includes('imanage-gallery-grid') || html.includes('imanage-gallery-masonry') || html.includes('imanage-gallery-slider')) {
                    console.log('📸 Adding gallery script to saved HTML');
                    const galleryScript = '<script>' +
                    '(function() {' +
                        'console.log(\'🖼️ Gallery script started\');' +
                        'function ensureGalleryStyles() {' +
                            'if (document.getElementById(\'imanage-gallery-styles\')) return;' +
                            'const style = document.createElement(\'style\');' +
                            'style.id = \'imanage-gallery-styles\';' +
                            'style.textContent = `' +
                                '.imanage-gallery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; padding: 20px; }' +
                                '.imanage-gallery-grid img { width: 100%; height: 250px; object-fit: cover; border-radius: 8px; transition: transform 0.3s; }' +
                                '.imanage-gallery-grid img:hover { transform: scale(1.05); }' +
                                '.imanage-gallery-masonry { column-count: 3; column-gap: 20px; padding: 20px; }' +
                                '.imanage-gallery-masonry img { width: 100%; margin-bottom: 20px; border-radius: 8px; break-inside: avoid; display: block; }' +
                                '@media (max-width: 768px) { .imanage-gallery-masonry { column-count: 2; } }' +
                                '@media (max-width: 480px) { .imanage-gallery-masonry { column-count: 1; } }' +
                                '.imanage-gallery-slider { position: relative; max-width: 800px; margin: 40px auto; overflow: hidden; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); background: #000; }' +
                                '.imanage-gallery-slider .slider-wrapper { position: relative; width: 100%; height: 500px; background: #000; }' +
                                '.imanage-gallery-slider .slider-slide { position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.5s ease-in-out; }' +
                                '.imanage-gallery-slider .slider-slide img { max-width: 100%; max-height: 100%; object-fit: contain; }' +
                                '.imanage-gallery-slider .slider-btn { position: absolute; top: 50%; transform: translateY(-50%); z-index: 10; background: rgba(0,0,0,0.5); color: white; border: none; padding: 15px 20px; font-size: 24px; cursor: pointer; border-radius: 4px; transition: background 0.3s; }' +
                                '.imanage-gallery-slider .slider-btn:hover { background: rgba(0,0,0,0.8); }' +
                                '.imanage-gallery-slider .slider-prev { left: 20px; }' +
                                '.imanage-gallery-slider .slider-next { right: 20px; }' +
                                '.imanage-gallery-slider .slider-counter { position: absolute; bottom: 20px; left: 50%; transform: translateX(-50%); z-index: 10; background: rgba(0,0,0,0.7); color: white; padding: 8px 16px; border-radius: 20px; font-size: 14px; }' +
                            '`;' +
                            'document.head.appendChild(style);' +
                            'console.log(\'✅ Gallery styles injected\');' +
                        '}' +
                        'function initSlider(container, images) {' +
                            'let currentSlide = 0;' +
                            'const slidesWrapper = document.createElement(\'div\');' +
                            'slidesWrapper.className = \'slider-wrapper\';' +
                            'slidesWrapper.style.cssText = \'position: relative; width: 100%; height: 500px; background: #000;\';' +
                            'images.forEach((asset, idx) => {' +
                                'const slide = document.createElement(\'div\');' +
                                'slide.className = \'slider-slide\';' +
                                'slide.style.cssText = \'position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; opacity: \' + (idx === 0 ? \'1\' : \'0\') + \'; transition: opacity 0.5s ease-in-out; pointer-events: \' + (idx === 0 ? \'auto\' : \'none\') + \';\';' +
                                'const img = document.createElement(\'img\');' +
                                'img.src = asset.src;' +
                                'img.alt = asset.name;' +
                                'img.style.cssText = \'max-width: 100%; max-height: 100%; object-fit: contain; cursor: pointer;\';' +
                                'img.onclick = function() { openLightbox(images, currentSlide); };' +
                                'slide.appendChild(img);' +
                                'slidesWrapper.appendChild(slide);' +
                            '});' +
                            'const prevBtn = document.createElement(\'button\');' +
                            'prevBtn.innerHTML = \'❮\';' +
                            'prevBtn.className = \'slider-btn slider-prev\';' +
                            'prevBtn.style.cssText = \'position: absolute; top: 50%; left: 20px; transform: translateY(-50%); z-index: 10; background: rgba(0,0,0,0.5); color: white; border: none; padding: 15px 20px; font-size: 24px; cursor: pointer; border-radius: 4px;\';' +
                            'prevBtn.onmouseover = function() { this.style.background = \'rgba(0,0,0,0.8)\'; };' +
                            'prevBtn.onmouseout = function() { this.style.background = \'rgba(0,0,0,0.5)\'; };' +
                            'const nextBtn = document.createElement(\'button\');' +
                            'nextBtn.innerHTML = \'❯\';' +
                            'nextBtn.className = \'slider-btn slider-next\';' +
                            'nextBtn.style.cssText = \'position: absolute; top: 50%; right: 20px; transform: translateY(-50%); z-index: 10; background: rgba(0,0,0,0.5); color: white; border: none; padding: 15px 20px; font-size: 24px; cursor: pointer; border-radius: 4px;\';' +
                            'nextBtn.onmouseover = function() { this.style.background = \'rgba(0,0,0,0.8)\'; };' +
                            'nextBtn.onmouseout = function() { this.style.background = \'rgba(0,0,0,0.5)\'; };' +
                            'const counter = document.createElement(\'div\');' +
                            'counter.className = \'slider-counter\';' +
                            'counter.style.cssText = \'position: absolute; bottom: 20px; left: 50%; transform: translateX(-50%); z-index: 10; background: rgba(0,0,0,0.7); color: white; padding: 8px 16px; border-radius: 20px; font-size: 14px;\';' +
                            'counter.textContent = \'1 / \' + images.length;' +
                            'function showSlide(index) {' +
                                'if (index < 0) index = images.length - 1;' +
                                'if (index >= images.length) index = 0;' +
                                'const slides = slidesWrapper.querySelectorAll(\'.slider-slide\');' +
                                'slides.forEach((slide, i) => {' +
                                    'if (i === index) {' +
                                        'slide.style.opacity = \'1\';' +
                                        'slide.style.pointerEvents = \'auto\';' +
                                    '} else {' +
                                        'slide.style.opacity = \'0\';' +
                                        'slide.style.pointerEvents = \'none\';' +
                                    '}' +
                                '});' +
                                'counter.textContent = (index + 1) + \' / \' + images.length;' +
                                'currentSlide = index;' +
                            '}' +
                            'prevBtn.onclick = function() { showSlide(currentSlide - 1); };' +
                            'nextBtn.onclick = function() { showSlide(currentSlide + 1); };' +
                            'slidesWrapper.appendChild(prevBtn);' +
                            'slidesWrapper.appendChild(nextBtn);' +
                            'slidesWrapper.appendChild(counter);' +
                            'container.appendChild(slidesWrapper);' +
                            'console.log(\'✅ Slider initialized with\', images.length, \'slides\');' +
                        '}' +
                        'if (document.readyState === \'loading\') {' +
                            'document.addEventListener(\'DOMContentLoaded\', loadGalleries);' +
                        '} else {' +
                            'loadGalleries();' +
                        '}' +
                        'async function loadGalleries() {' +
                            'console.log(\'🔍 Looking for galleries...\');' +
                            'const galleries = document.querySelectorAll(\'.imanage-gallery-grid, .imanage-gallery-masonry, .imanage-gallery-slider\');' +
                            'console.log(\'📊 Found galleries:\', galleries.length);' +
                            'if (galleries.length === 0) {' +
                                'console.warn(\'⚠️ No gallery elements found\');' +
                                'return;' +
                            '}' +
                            'ensureGalleryStyles();' +
                            'for (let i = 0; i < galleries.length; i++) {' +
                                'const gallery = galleries[i];' +
                                'console.log(\'🔍 Processing gallery\', i + 1);' +
                                'const folder = gallery.getAttribute(\'data-folder\') || \'\';' +
                                'const maxImages = parseInt(gallery.getAttribute(\'data-max-images\')) || 20;' +
                                'try {' +
                                    'console.log(\'📡 Fetching images...\');' +
                                    'const urlParams = new URLSearchParams(window.location.search);' +
                                    'const token = urlParams.get(\'token\') || \'\';' +
                                    'const response = await fetch(\'api.php?action=getpublicimages&token=\' + token);' +
                                    'console.log(\'📥 Response status:\', response.status);' +
                                    'const data = await response.json();' +
                                    'console.log(\'✅ API Response:\', data);' +
                                    'if (data.success && data.assets) {' +
                                        'let images = data.assets.filter(asset => asset.type === \'image\');' +
                                        'console.log(\'🖼️ Total images:\', images.length);' +
                                        'if (folder) {' +
                                            'images = images.filter(asset => asset.folder === folder);' +
                                        '}' +
                                        'images = images.slice(0, maxImages);' +
                                        'const styleTag = gallery.querySelector(\'style\');' +
                                        'const styleHTML = styleTag ? styleTag.outerHTML : \'\';' +
                                        'const isSlider = gallery.classList.contains(\'imanage-gallery-slider\');' +
                                        'gallery.innerHTML = \'\';' +
                                        'if (styleHTML) {' +
                                            'const tempDiv = document.createElement(\'div\');' +
                                            'tempDiv.innerHTML = styleHTML;' +
                                            'gallery.appendChild(tempDiv.firstChild);' +
                                        '}' +
                                        'if (isSlider) {' +
                                            'console.log(\'🎠 Initializing slider with\', images.length, \'images\');' +
                                            'initSlider(gallery, images);' +
                                        '} else {' +
                                            'images.forEach((asset, idx) => {' +
                                                'console.log(\'➕ Adding image\', idx + 1, asset.name);' +
                                                'const img = document.createElement(\'img\');' +
                                                'img.src = asset.src;' +
                                                'img.alt = asset.name;' +
                                                'img.loading = \'lazy\';' +
                                                'img.style.cursor = \'pointer\';' +
                                                'img.dataset.lightboxIndex = idx;' +
                                                'img.addEventListener(\'click\', function() {' +
                                                    'openLightbox(images, idx);' +
                                                '});' +
                                                'gallery.appendChild(img);' +
                                            '});' +
                                        '}' +
                                        'console.log(\'✅ Gallery loaded:\', images.length, \'images\');' +
                                    '} else {' +
                                        'console.warn(\'⚠️ No images in response\');' +
                                        'gallery.innerHTML = \'<p style="text-align:center;color:orange">No images</p>\';' +
                                    '}' +
                                '} catch (error) {' +
                                    'console.error(\'❌ Error:\', error);' +
                                    'gallery.innerHTML = \'<p style="text-align:center;color:red">Failed to load</p>\';' +
                                '}' +
                            '}' +
                        '}' +
                        'function openLightbox(images, startIndex) {' +
                            'let currentIndex = startIndex;' +
                            'const lightbox = document.createElement(\'div\');' +
                            'lightbox.id = \'imanage-lightbox\';' +
                            'lightbox.innerHTML = \'' +
                                '<div class="lightbox-overlay"></div>\' +' +
                                '\'<div class="lightbox-content">\' +' +
                                    '\'<button class="lightbox-close" aria-label="Close">&times;</button>\' +' +
                                    '\'<button class="lightbox-prev" aria-label="Previous">&lsaquo;</button>\' +' +
                                    '\'<img class="lightbox-image" src="" alt="">\' +' +
                                    '\'<button class="lightbox-next" aria-label="Next">&rsaquo;</button>\' +' +
                                    '\'<div class="lightbox-counter"></div>\' +' +
                                '\'</div>\';' +
                            'document.body.appendChild(lightbox);' +
                            'const img = lightbox.querySelector(\'.lightbox-image\');' +
                            'const counter = lightbox.querySelector(\'.lightbox-counter\');' +
                            'const closeBtn = lightbox.querySelector(\'.lightbox-close\');' +
                            'const prevBtn = lightbox.querySelector(\'.lightbox-prev\');' +
                            'const nextBtn = lightbox.querySelector(\'.lightbox-next\');' +
                            'const overlay = lightbox.querySelector(\'.lightbox-overlay\');' +
                            'function showImage(index) {' +
                                'currentIndex = index;' +
                                'img.src = images[index].src;' +
                                'img.alt = images[index].name;' +
                                'counter.textContent = (index + 1) + \' / \' + images.length;' +
                                'prevBtn.style.display = index > 0 ? \'block\' : \'none\';' +
                                'nextBtn.style.display = index < images.length - 1 ? \'block\' : \'none\';' +
                            '}' +
                            'function close() {' +
                                'lightbox.remove();' +
                                'document.removeEventListener(\'keydown\', handleKeyboard);' +
                            '}' +
                            'function handleKeyboard(e) {' +
                                'if (e.key === \'Escape\') close();' +
                                'else if (e.key === \'ArrowLeft\' && currentIndex > 0) showImage(currentIndex - 1);' +
                                'else if (e.key === \'ArrowRight\' && currentIndex < images.length - 1) showImage(currentIndex + 1);' +
                            '}' +
                            'closeBtn.addEventListener(\'click\', close);' +
                            'overlay.addEventListener(\'click\', close);' +
                            'prevBtn.addEventListener(\'click\', function() { showImage(currentIndex - 1); });' +
                            'nextBtn.addEventListener(\'click\', function() { showImage(currentIndex + 1); });' +
                            'document.addEventListener(\'keydown\', handleKeyboard);' +
                            'showImage(startIndex);' +
                        '}' +
                    '})();' +
                    '<\/script>';
                    
                    // Add lightbox CSS
                    const lightboxCSS = '<style>' +
                        '#imanage-lightbox { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 9999; }' +
                        '.lightbox-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); }' +
                        '.lightbox-content { position: relative; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; }' +
                        '.lightbox-image { max-width: 90%; max-height: 90vh; object-fit: contain; }' +
                        '.lightbox-close { position: absolute; top: 20px; right: 30px; font-size: 40px; color: white; background: none; border: none; cursor: pointer; z-index: 10001; font-weight: bold; line-height: 1; padding: 0; width: 40px; height: 40px; }' +
                        '.lightbox-close:hover { color: #ccc; }' +
                        '.lightbox-prev, .lightbox-next { position: absolute; top: 50%; transform: translateY(-50%); font-size: 60px; color: white; background: rgba(0,0,0,0.5); border: none; cursor: pointer; padding: 10px 20px; z-index: 10001; line-height: 1; }' +
                        '.lightbox-prev:hover, .lightbox-next:hover { background: rgba(0,0,0,0.8); }' +
                        '.lightbox-prev { left: 20px; }' +
                        '.lightbox-next { right: 20px; }' +
                        '.lightbox-counter { position: absolute; bottom: 20px; left: 50%; transform: translateX(-50%); color: white; background: rgba(0,0,0,0.7); padding: 8px 16px; border-radius: 4px; font-size: 14px; }' +
                    '<\/style>';
                    
                    html += galleryScript + lightboxCSS;
                }
                
                // Add video gallery loader script if page contains imanage video galleries
                if (html.includes('imanage-video-gallery-grid') || html.includes('imanage-video-gallery-list') || html.includes('imanage-video-gallery-featured')) {
                    console.log('🎬 Adding video gallery script to saved HTML');
                    const videoGalleryScript = '<script>' +
                    '(function() {' +
                        'console.log(\'🎬 Video gallery script started\');' +
                        'if (document.readyState === \'loading\') {' +
                            'document.addEventListener(\'DOMContentLoaded\', loadVideoGalleries);' +
                        '} else {' +
                            'loadVideoGalleries();' +
                        '}' +
                        'async function loadVideoGalleries() {' +
                            'console.log(\'🔍 Looking for video galleries...\');' +
                            'const galleries = document.querySelectorAll(\'.imanage-video-gallery-grid, .imanage-video-gallery-list, .imanage-video-gallery-featured\');' +
                            'console.log(\'📊 Found video galleries:\', galleries.length);' +
                            'if (galleries.length === 0) {' +
                                'console.warn(\'⚠️ No video gallery elements found\');' +
                                'return;' +
                            '}' +
                            'for (let i = 0; i < galleries.length; i++) {' +
                                'const gallery = galleries[i];' +
                                'console.log(\'🔍 Processing video gallery\', i + 1);' +
                                'const folder = gallery.getAttribute(\'data-folder\') || \'\';' +
                                'const maxVideos = parseInt(gallery.getAttribute(\'data-max-videos\')) || 10;' +
                                'const showControls = gallery.getAttribute(\'data-show-controls\') !== \'false\';' +
                                'const autoplay = gallery.getAttribute(\'data-autoplay\') === \'true\';' +
                                'const muted = gallery.getAttribute(\'data-muted\') === \'true\';' +
                                'try {' +
                                    'console.log(\'📡 Fetching videos...\');' +
                                    'const urlParams = new URLSearchParams(window.location.search);' +
                                    'const token = urlParams.get(\'token\') || \'\';' +
                                    'const response = await fetch(\'api.php?action=getpublicvideos&token=\' + token);' +
                                    'console.log(\'📥 Response status:\', response.status);' +
                                    'const data = await response.json();' +
                                    'console.log(\'✅ API Response:\', data);' +
                                    'if (data.success && data.assets) {' +
                                        'let videos = data.assets.filter(asset => asset.type === \'video\');' +
                                        'console.log(\'🎬 Total videos:\', videos.length);' +
                                        'if (folder) {' +
                                            'videos = videos.filter(asset => asset.folder === folder);' +
                                        '}' +
                                        'videos = videos.slice(0, maxVideos);' +
                                        'const styleTag = gallery.querySelector(\'style\');' +
                                        'gallery.innerHTML = styleTag ? styleTag.outerHTML : \'\';' +
                                        'videos.forEach((asset, idx) => {' +
                                            'console.log(\'➕ Adding video\', idx + 1, asset.name);' +
                                            'const videoItem = document.createElement(\'div\');' +
                                            'videoItem.className = \'video-item\';' +
                                            'const video = document.createElement(\'video\');' +
                                            'video.src = asset.src;' +
                                            'if (showControls) video.controls = true;' +
                                            'if (autoplay) video.autoplay = true;' +
                                            'if (muted) video.muted = true;' +
                                            'video.style.cursor = \'pointer\';' +
                                            'videoItem.appendChild(video);' +
                                            'gallery.appendChild(videoItem);' +
                                        '});' +
                                        'console.log(\'✅ Video gallery loaded:\', videos.length, \'videos\');' +
                                    '} else {' +
                                        'console.warn(\'⚠️ No videos in response\');' +
                                        'gallery.innerHTML = \'<p style="text-align:center;color:orange">No videos</p>\';' +
                                    '}' +
                                '} catch (error) {' +
                                    'console.error(\'❌ Error:\', error);' +
                                    'gallery.innerHTML = \'<p style="text-align:center;color:red">Failed to load videos</p>\';' +
                                '}' +
                            '}' +
                        '}' +
                    '})();' +
                    '<\/script>';
                    
                    html += videoGalleryScript;
                }
                
                // Capture screenshot of the canvas
                console.log('📸 Capturing page screenshot...');
                let screenshotData = null;
                try {
                    const canvas = await html2canvas(editor.Canvas.getBody(), {
                        backgroundColor: '#ffffff',
                        scale: 0.5, // Reduce size for faster processing
                        width: 1200,
                        height: 800,
                        logging: false
                    });
                    screenshotData = canvas.toDataURL('image/jpeg', 0.7);
                    console.log('✅ Screenshot captured, size:', (screenshotData.length / 1024).toFixed(2) + ' KB');
                } catch (screenshotError) {
                    console.warn('⚠️ Screenshot failed:', screenshotError);
                    // Continue without screenshot
                }
                
                console.log('📦 Preview data exists:', !!screenshotData);
                
                const payload = {
                    id: pageId,
                    html_content: html,
                    css_content: css,
                    grapesjs_data: JSON.stringify(projectData),
                    page_title: title,
                    preview_image: screenshotData
                };
                
                console.log('🚀 Sending to API...');
                const response = await fetch('api.php?action=saveCustomPage', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                
                const responseText = await response.text();
                console.log('📄 Response:', responseText);
                
                let data;
                try {
                    data = JSON.parse(responseText);
                } catch (e) {
                    console.error('❌ JSON parse error:', e);
                    alert('Server error: Invalid response format');
                    return;
                }
                
                if (data.success) {
                    alert('✅ Design saved successfully!');
                    if (!pageId && data.pageId) {
                        window.history.pushState({}, '', `page-designer.php?id=${data.pageId}`);
                        pageId = data.pageId;
                        console.log('🆔 New page ID:', pageId);
                    }
                } else {
                    const errorMsg = data.message || data.error || 'Unknown error';
                    console.error('❌ Save failed:', errorMsg);
                    alert('Failed to save: ' + errorMsg);
                }
            } catch (error) {
                console.error('💥 Save error:', error);
                alert('Save failed: ' + error.message);
            }
        }
        
        function exitEditor() {
            if (confirm('Exit editor? Any unsaved changes will be lost.')) {
                window.location.href = 'index.php';
            }
        }
        
        // AI Text Spinner Functions
        let selectedComponent = null;
        
        function openAISpinner() {
            const modal = document.getElementById('aiSpinnerModal');
            const originalText = document.getElementById('originalText');
            const result = document.getElementById('aiSpinnerResult');
            const applyButton = document.getElementById('applyButton');
            
            // Get selected component
            selectedComponent = editor.getSelected();
            
            // Try to get text from selected component
            if (selectedComponent) {
                const content = selectedComponent.view.el.textContent || '';
                originalText.value = content.trim();
            } else {
                originalText.value = '';
            }
            
            // Reset result
            result.classList.remove('active');
            applyButton.style.display = 'none';
            
            modal.classList.add('active');
        }
        
        function closeAISpinner() {
            const modal = document.getElementById('aiSpinnerModal');
            modal.classList.remove('active');
        }
        
        function switchAIMode(mode) {
            // Update tabs
            document.querySelectorAll('.ai-mode-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            event.target.closest('.ai-mode-tab').classList.add('active');
            
            // Update content
            document.querySelectorAll('.ai-mode-content').forEach(content => {
                content.classList.remove('active');
            });
            
            if (mode === 'rewrite') {
                document.getElementById('rewriteMode').classList.add('active');
            } else if (mode === 'generate') {
                document.getElementById('generateMode').classList.add('active');
            }
        }
        
        async function spinText() {
            const loading = document.getElementById('aiSpinnerLoading');
            const result = document.getElementById('aiSpinnerResult');
            const spinButton = document.getElementById('spinButton');
            const applyButton = document.getElementById('applyButton');
            const spunText = document.getElementById('spunText');
            
            // Determine which mode is active
            const isGenerateMode = document.getElementById('generateMode').classList.contains('active');
            
            let requestData = {};
            
            if (isGenerateMode) {
                // Generate mode
                const topic = document.getElementById('generateTopic').value.trim();
                const instructions = document.getElementById('generateInstructions').value.trim();
                const tone = document.getElementById('generateTone').value;
                const length = document.getElementById('generateLength').value;
                
                if (!topic) {
                    alert('Please enter a topic or description');
                    return;
                }
                
                requestData = {
                    mode: 'generate',
                    topic: topic,
                    instructions: instructions,
                    tone: tone,
                    length: length
                };
            } else {
                // Rewrite mode
                const originalText = document.getElementById('originalText').value.trim();
                const tone = document.getElementById('spinTone').value;
                const length = document.getElementById('spinLength').value;
                
                if (!originalText) {
                    alert('Please enter some text to rewrite');
                    return;
                }
                
                requestData = {
                    mode: 'rewrite',
                    text: originalText,
                    tone: tone,
                    length: length
                };
            }
            
            // Show loading
            loading.classList.add('active');
            result.classList.remove('active');
            spinButton.disabled = true;
            
            try {
                console.log('🤖 Sending request to spintext API...', requestData);
                const response = await fetch('api.php?action=spintext', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(requestData)
                });
                
                console.log('📥 Response status:', response.status);
                const data = await response.json();
                console.log('📦 Response data:', data);
                
                if (data.success) {
                    spunText.textContent = data.spun_text;
                    result.classList.add('active');
                    applyButton.style.display = 'flex';
                    console.log('✅ Text spun successfully');
                } else {
                    console.error('❌ API Error:', data.message || data.error);
                    const errorMsg = data.message || data.error || 'Failed to spin text';
                    
                    // Check if it's a Gemini API key error
                    if (errorMsg.includes('Gemini API key') || errorMsg.includes('gemini')) {
                        const getKeyMsg = 'Gemini API key not configured!\n\n' +
                            '1. Get a FREE API key from Google\n' +
                            '2. Go to Admin > Settings > AI Integration\n' +
                            '3. Paste your API key\n' +
                            '4. Click Save\n\n' +
                            'Click OK to open Google AI Studio now.';
                        
                        if (confirm(getKeyMsg)) {
                            window.open('https://makersuite.google.com/app/apikey', '_blank');
                        }
                    } else if (errorMsg.includes('OpenAI API key')) {
                        const getKeyMsg = 'OpenAI API key not configured!\n\n' +
                            '1. Get an API key from OpenAI\n' +
                            '2. Go to Admin > Settings > AI Integration\n' +
                            '3. Paste your API key\n' +
                            '4. Click Save\n\n' +
                            'Click OK to open OpenAI Platform now.';
                        
                        if (confirm(getKeyMsg)) {
                            window.open('https://platform.openai.com/api-keys', '_blank');
                        }
                    } else if (errorMsg.includes('Ollama') || errorMsg.includes('Is Ollama running')) {
                        const installMsg = 'Ollama not detected!\n\n' +
                            '1. Download Ollama for Windows\n' +
                            '2. Install it\n' +
                            '3. Run: ollama serve\n' +
                            '4. Run: ollama pull llama3.2\n\n' +
                            'Click OK to open Ollama download page.';
                        
                        if (confirm(installMsg)) {
                            window.open('https://ollama.com/download/windows', '_blank');
                        }
                    } else if (errorMsg.includes('LM Studio') || errorMsg.includes('Is LM Studio running')) {
                        const installMsg = 'LM Studio not running!\n\n' +
                            '1. Download LM Studio\n' +
                            '2. Load a model\n' +
                            '3. Start the local server\n\n' +
                            'Click OK to open LM Studio website.';
                        
                        if (confirm(installMsg)) {
                            window.open('https://lmstudio.ai', '_blank');
                        }
                    } else {
                        alert('Error: ' + errorMsg);
                    }
                }
            } catch (error) {
                console.error('❌ AI Spinner error:', error);
                alert('Failed to spin text: ' + error.message);
            } finally {
                loading.classList.remove('active');
                spinButton.disabled = false;
            }
        }
        
        function copySpunText() {
            const spunText = document.getElementById('spunText').textContent;
            navigator.clipboard.writeText(spunText).then(() => {
                alert('✅ Text copied to clipboard!');
            });
        }
        
        function applySpunText() {
            const spunText = document.getElementById('spunText').textContent;
            
            if (selectedComponent) {
                // Update the selected component's content
                selectedComponent.components(spunText);
                alert('✅ Text applied to selected element!');
                closeAISpinner();
            } else {
                // If no component selected, just copy to clipboard
                copySpunText();
            }
        }
        
        // Close modal on outside click
        document.getElementById('aiSpinnerModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeAISpinner();
            }
        });
    </script>
</body>
</html>
