<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Shared Image - GrapesJS Ready</title>
    <link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
    <link rel="stylesheet" href="css/gallery.css?v=<?= time() ?>">
    <style id="custom-landing-styles">
        /* Custom landing page styles will be injected here */
    </style>
    <style>
        body {
            background: #f5f5f5;
            margin: 0;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .share-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .share-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        .share-header h1 {
            margin: 0 0 10px 0;
            color: #333;
        }
        .share-header p {
            color: #666;
            margin: 0;
        }
        .share-image-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }
        .share-image {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        .share-info {
            width: 100%;
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        .info-item {
            display: flex;
            flex-direction: column;
        }
        .info-label {
            font-weight: 600;
            color: #667eea;
            font-size: 0.9em;
            margin-bottom: 5px;
        }
        .info-value {
            color: #333;
            font-size: 1em;
        }
        .error-message {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        .error-message h2 {
            color: #f44336;
            margin-bottom: 10px;
        }
        .loading {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .design-mode-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            padding: 15px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            transition: all 0.3s;
            z-index: 1000;
        }
        
        .design-mode-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }
        
        .design-mode-btn:active {
            transform: translateY(-1px);
        }
        
        #custom-landing-container {
            display: none;
        }
        
        #custom-landing-container.active {
            display: block;
        }
        
        #default-share-container.hidden {
            display: none !important;
        }
    </style>
</head>
<body>
    <!-- Custom Landing Page Container (if exists) -->
    <div id="custom-landing-container"></div>
    
    <!-- Default Share View Container -->
    <div id="default-share-container" class="share-container">
        <div id="loadingState" class="loading">
            <div class="spinner"></div>
            <p>Loading shared image...</p>
        </div>
        
        <div id="errorState" class="error-message" style="display: none;">
            <h2>Image Not Found</h2>
            <p>This shared link is invalid or the image is no longer shared.</p>
        </div>

        <div id="imageContent" style="display: none;">
            <div class="share-header">
                <h1 id="imageTitle">Shared Image</h1>
                <p id="imageDescription"></p>
            </div>
            
            <div class="share-image-container">
                <img id="sharedImage" class="share-image" alt="Shared image">
                
                <div class="share-info">
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Dimensions</span>
                            <span class="info-value" id="imageDimensions">-</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">File Size</span>
                            <span class="info-value" id="imageSize">-</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Uploaded</span>
                            <span class="info-value" id="imageDate">-</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Design Mode Button (shown only for logged-in owner) -->
    <button id="designModeBtn" class="design-mode-btn" style="display: none;">
        🎨 Design Landing Page
    </button>

    <script>
        const API_BASE = './api.php';
        
        // Get share token from URL
        const urlParams = new URLSearchParams(window.location.search);
        const shareToken = urlParams.get('share');
        let hasCustomLanding = false;

        if (!shareToken) {
            showError();
        } else {
            // First check if there's a custom landing page
            checkCustomLanding(shareToken);
            loadSharedImage(shareToken);
        }

        async function checkCustomLanding(token) {
            try {
                const response = await fetch(`${API_BASE}?action=loadLandingPage&token=${token}`);
                const data = await response.json();

                if (data.success && data.design && data.design.html_content) {
                    hasCustomLanding = true;
                    displayCustomLanding(data.design);
                }
            } catch (error) {
                console.error('Error checking custom landing:', error);
            }
        }

        function displayCustomLanding(design) {
            // Hide default container
            document.getElementById('default-share-container').classList.add('hidden');
            
            // Show custom landing
            const container = document.getElementById('custom-landing-container');
            container.innerHTML = design.html_content;
            container.classList.add('active');
            
            // Inject custom CSS
            if (design.css_content) {
                document.getElementById('custom-landing-styles').textContent = design.css_content;
            }
            
            // Update page title
            if (design.page_title) {
                document.title = design.page_title;
            }
        }

        async function loadSharedImage(token) {
            try {
                const response = await fetch(`${API_BASE}?action=shared&token=${token}`);
                const data = await response.json();

                if (data.success && data.data) {
                    displayImage(data.data);
                } else {
                    showError();
                }
            } catch (error) {
                console.error('Error loading shared image:', error);
                showError();
            }
        }

        function displayImage(image) {
            document.getElementById('loadingState').style.display = 'none';
            document.getElementById('imageContent').style.display = 'block';

            document.getElementById('imageTitle').textContent = image.title || image.original_name || 'Shared Image';
            document.getElementById('imageDescription').textContent = image.description || '';
            document.getElementById('sharedImage').src = image.original_url;
            document.getElementById('sharedImage').alt = image.title || 'Shared image';
            document.getElementById('imageDimensions').textContent = `${image.width} × ${image.height} px`;
            document.getElementById('imageSize').textContent = `${(image.file_size / 1024 / 1024).toFixed(2)} MB`;
            document.getElementById('imageDate').textContent = new Date(image.created_at).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }

        function showError() {
            document.getElementById('loadingState').style.display = 'none';
            document.getElementById('errorState').style.display = 'block';
        }
        
        // Check if user is logged in and is the owner
        async function checkDesignPermission() {
            console.log('[GrapesJS] Checking design permission for token:', shareToken);
            
            try {
                const statusResponse = await fetch(`${API_BASE}?action=check_status`);
                const statusData = await statusResponse.json();
                console.log('[GrapesJS] Login status:', statusData);
                
                // Fix property name: API returns 'logged_in' not 'loggedIn'
                if (statusData.success && statusData.logged_in) {
                    console.log('[GrapesJS] User is logged in, checking ownership...');
                    
                    // Check if user owns this shared image
                    const imageResponse = await fetch(`${API_BASE}?action=shared&token=${shareToken}`);
                    const imageData = await imageResponse.json();
                    console.log('[GrapesJS] Image data:', imageData);
                    
                    // Fix property name: API returns user.id not userId
                    const currentUserId = statusData.user?.id;
                    const imageOwnerId = imageData.data?.user_id;
                    
                    console.log('[GrapesJS] Comparing - Image owner:', imageOwnerId, 'Current user:', currentUserId);
                    console.log('[GrapesJS] Types - Image owner type:', typeof imageOwnerId, 'Current user type:', typeof currentUserId);
                    
                    // Use == instead of === to handle string vs number comparison
                    if (imageData.success && imageData.data && imageOwnerId == currentUserId) {
                        // Show design button for owner
                        console.log('[GrapesJS] ✅ User owns this image! Showing design button');
                        const designBtn = document.getElementById('designModeBtn');
                        designBtn.style.display = 'block';
                        designBtn.addEventListener('click', () => {
                            console.log('[GrapesJS] Design button clicked, redirecting...');
                            window.location.href = `design-landing.php?share=${shareToken}`;
                        });
                    } else {
                        console.log('[GrapesJS] ❌ User does not own this image or data missing');
                        console.log('[GrapesJS] Debug - imageData:', imageData);
                        console.log('[GrapesJS] Debug - statusData:', statusData);
                    }
                } else {
                    console.log('[GrapesJS] ❌ User is not logged in');
                    console.log('[GrapesJS] Debug - statusData:', statusData);
                }
            } catch (error) {
                console.error('[GrapesJS] Error checking design permission:', error);
            }
        }
        
        // Check permissions after page loads
        console.log('[GrapesJS] Scheduling permission check...');
        setTimeout(checkDesignPermission, 500);
    </script>
</body>
</html>
