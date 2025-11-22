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
    </style>
</head>
<body>
    <div class="panel__top">
        <div class="panel__basic-actions"></div>
        <div class="panel__devices"></div>
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
        
        async function savePage() {
            try {
                console.log('💾 Starting save...');
                let html = editor.getHtml();
                const css = editor.getCss();
                const projectData = editor.getProjectData();
                const title = document.querySelector('h1')?.textContent || 'Untitled Page';
                
                // Add gallery loader script if page contains imanage galleries
                if (html.includes('imanage-gallery-grid') || html.includes('imanage-gallery-masonry') || html.includes('imanage-gallery-slider')) {
                    console.log('📸 Adding gallery script to saved HTML');
                    const galleryScript = '<script>' +
                    '(function() {' +
                        'console.log(\'🖼️ Gallery script started\');' +
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
                                        'gallery.innerHTML = styleTag ? styleTag.outerHTML : \'\';' +
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
    </script>
</body>
</html>
