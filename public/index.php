<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Management System</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/gallery.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header">
            <h1>Image Management System</h1>
            <p>Upload, organize, and manipulate your images</p>
        </header>

        <!-- Navigation -->
        <nav class="nav">
            <div class="nav-left user-is-logged-in">
                <a href="#" class="nav-link active" data-view="gallery">Gallery</a>
                <a href="#" class="nav-link" data-view="upload">Upload</a>
                <a href="#" class="nav-link" data-view="folders">Folders</a>
            </div>
            <div class="nav-left user-is-logged-out">
                <a href="#" class="nav-link active" data-view="gallery">Gallery</a>
                <a href="#" class="nav-link" data-view="login">Login</a>
                <a href="#" class="nav-link" data-view="register">Register</a>
            </div>
            <div class="nav-right user-is-logged-in">
                <span class="username-display" id="username-display"></span>
                <a href="#" id="logout-link" class="nav-link">Logout</a>
            </div>
            <div class="nav-right">
                <input type="text" id="searchInput" placeholder="Search images..." class="search-input">
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Gallery View -->
            <section id="gallery-view" class="view active">
                <div class="gallery-header">
                    <h2>Image Gallery</h2>
                    <div class="folder-filter">
                        <label for="folderSelect">Folder:</label>
                        <select id="folderSelect">
                            <option value="">All Folders</option>
                        </select>
                    </div>
                </div>

                <div id="gallery" class="gallery">
                    <!-- Images will be loaded here -->
                </div>

                <div class="pagination">
                    <button id="prevBtn" class="btn btn-secondary">Previous</button>
                    <span id="pageInfo">Page 1</span>
                    <button id="nextBtn" class="btn btn-secondary">Next</button>
                </div>
            </section>

            <!-- Upload View -->
            <section id="upload-view" class="view">
                <div class="upload-container">
                    <h2>Upload Images</h2>
                    
                    <form id="uploadForm" class="upload-form">
                        <div class="form-group">
                            <label for="imageFile">Select Image:</label>
                            <div class="file-input-wrapper">
                                <input type="file" id="imageFile" name="image" accept="image/*" required>
                                <div class="file-input-display">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="17 8 12 3 7 8"></polyline>
                                        <line x1="12" y1="3" x2="12" y2="15"></line>
                                    </svg>
                                    <span class="file-name">Click to select image or drag and drop</span>
                                    <small style="display: block; margin-top: 8px; color: #999;">JPG, PNG, GIF, WebP • Max 5MB</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="imageTitle">Title:</label>
                            <input type="text" id="imageTitle" name="title" placeholder="Image title">
                        </div>

                        <div class="form-group">
                            <label for="imageDescription">Description:</label>
                            <textarea id="imageDescription" name="description" placeholder="Image description"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="imageTags">Tags:</label>
                            <input type="text" id="imageTags" name="tags" placeholder="Separate tags with commas">
                        </div>

                        <div class="form-group">
                            <label for="imageFolder">Folder:</label>
                            <select id="imageFolder" name="folder">
                                <option value="default">Default</option>
                            </select>
                        </div>

                        <!-- Hidden section to create a subfolder -->
                        <div id="createSubfolderSection" class="form-group" style="display: none; background-color: #f0f2ff; padding: 15px; border-radius: 6px;">
                            <label for="newSubfolderName" style="font-weight: bold;">Create a new subfolder to upload into:</label>
                            <p style="font-size: 12px; margin-bottom: 10px;">You don't have any subfolders yet. Create one here.</p>
                            <input type="text" id="newSubfolderName" name="new_subfolder_name" placeholder="New subfolder name">
                            <input type="hidden" id="newSubfolderDesc" name="new_subfolder_description" value="">
                            <button type="button" id="createSubfolderBtn" class="btn btn-secondary btn-sm" style="margin-top: 10px;">Create Subfolder</button>
                            <div id="subfolderStatus" style="margin-top: 10px; font-size: 12px;"></div>
                        </div>

                        <button type="submit" class="btn btn-primary">Upload Image</button>
                    </form>

                    <div id="uploadStatus" class="upload-status" style="display:none;"></div>
                </div>
            </section>

            <!-- Folders View -->
            <section id="folders-view" class="view">
                <div class="folders-container">
                    <h2>Manage Folders</h2>
                    
                    <div class="create-folder">
                        <input type="text" id="newFolderName" placeholder="Folder name">
                        <input type="text" id="newFolderDesc" placeholder="Folder description">
                        <button class="btn btn-primary" onclick="createFolder()">Create Folder</button>
                    </div>

                    <div id="foldersList" class="folders-list">
                        <!-- Folders will be loaded here -->
                    </div>
                </div>
            </section>

            <!-- Login View -->
            <section id="login-view" class="view">
                <div class="auth-container">
                    <h2>Login</h2>
                    <form id="loginForm" class="auth-form">
                        <div class="form-group">
                            <label for="loginUsername">Username</label>
                            <input type="text" id="loginUsername" name="username" required>
                        </div>
                        <div class="form-group">
                            <label for="loginPassword">Password</label>
                            <input type="password" id="loginPassword" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Login</button>
                        <div id="loginStatus" class="auth-status"></div>
                    </form>
                </div>
            </section>

            <!-- Register View -->
            <section id="register-view" class="view">
                <div class="auth-container">
                    <h2>Register</h2>
                    <form id="registerForm" class="auth-form">
                        <div class="form-group"><label for="registerUsername">Username</label><input type="text" id="registerUsername" name="username" required></div>
                        <div class="form-group"><label for="registerPassword">Password</label><input type="password" id="registerPassword" name="password" minlength="8" required></div>
                        <div class="form-group"><label for="confirmPassword">Confirm Password</label><input type="password" id="confirmPassword" name="confirm_password" minlength="8" required></div>
                        <button type="submit" class="btn btn-primary">Register</button>
                        <div id="registerStatus" class="auth-status"></div>
                    </form>
                </div>
            </section>
        </main>
    </div>

    <!-- Image Modal -->
    <div id="imageModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div class="modal-body">
                <div class="modal-image" style="position: relative; display: inline-block;">
                    <img id="modalImage" src="" alt="" style="display: block;">
                    <div id="cropCanvasContainer" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: none; z-index: 10; pointer-events: all;"></div>
                </div>
                <div id="modalImageSpinner" class="spinner-overlay" style="display: none;">
                    <div class="spinner-inner"></div>
                </div>
                <div class="modal-details">
                    <h2 id="modalTitle"></h2>
                    <p id="modalDescription"></p>
                    
                    <div class="modal-info">
                        <div class="info-item">
                            <span class="label">Dimensions:</span>
                            <span id="modalDimensions"></span>
                        </div>
                        <div class="info-item">
                            <span class="label">File Size:</span>
                            <span id="modalFileSize"></span>
                        </div>
                        <div class="info-item">
                            <span class="label">Created:</span>
                            <span id="modalCreated"></span>
                        </div>
                    </div>

                    <div class="modal-share-section">
                        <label class="share-label">
                            <input type="checkbox" id="shareImageCheckbox">
                            <span class="share-text">Share this image publicly</span>
                        </label>
                        <small>Anyone with the link will be able to view a shared image.</small>
                        
                        <div id="shareLinkContainer" style="display: none; margin-top: 10px; padding: 10px; background: #e8f5e9; border-radius: 4px;">
                            <label style="font-weight: 600; display: block; margin-bottom: 5px; color: #2e7d32;">Share Link:</label>
                            <div style="display: flex; gap: 8px; align-items: center;">
                                <input type="text" id="shareLinkInput" readonly style="flex: 1; padding: 8px; border: 1px solid #4caf50; border-radius: 4px; font-size: 14px; background: white;">
                                <button id="copyShareLinkBtn" class="btn btn-sm" style="background: #4caf50; color: white; white-space: nowrap;">Copy Link</button>
                            </div>
                            <small style="color: #2e7d32; display: block; margin-top: 5px;">Share this link to let others view this image</small>
                        </div>
                    </div>

                    <div class="modal-tools">
                        <h3>Image Tools</h3>
                        <div class="tool-section">
                           <label>Resize:</label>
                            <div class="input-group resize-group">{
                                <div class="input-fields">
                                <input type="number" id="resizeWidth" placeholder="Width" min="1" style="width: 80px; font-size: 16px;">
                                <input type="number" id="resizeHeight" placeholder="Height" min="1" style="width: 80px; font-size: 16px;">
                                </div>
                                <a href="#" id="lockAspectRatio" class="aspect-ratio-lock" style="font-size: 12px;">
                                   <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.72"></path>
                                        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.72-1.72"></path>
                                   </svg>
                                </a>
                            </div>
                            <button id="resize-btn" class="btn btn-sm">Resize</button>
                        </div>

                       <div class="tool-section">
                            <label>Crop:</label>
                            <div class="input-group" style="margin-bottom: 10px;">
                                <button id="crop-interactive-btn" class="btn btn-sm">Select Area to Crop</button>
                                <button id="crop-cancel-btn" class="btn btn-sm" style="display: none; background-color: #f44336;">Cancel Crop</button>
                                <button id="crop-apply-btn" class="btn btn-sm" style="display: none; background-color: #4CAF50;">Apply Crop</button>
                            </div>
                        </div>

                        <div class="tool-section">
                            <label>Effects:</label>
                            <button id="rotate-btn" class="btn btn-sm">Rotate 90°</button>
                            <button id="grayscale-btn" class="btn btn-sm">Grayscale</button>
                            <button id="flip-h-btn" class="btn btn-sm">Flip H</button>
                            <button id="flip-v-btn" class="btn btn-sm">Flip V</button>
                        </div>

                        <div class="tool-section">
                            <label>Adjust:</label>
                            <label>Brightness:</label>
                            <input type="range" id="brightnessSlider" min="-100" max="100" value="0">
                            <button id="apply-brightness-btn" class="btn btn-sm">Apply</button>

                            <label>Contrast:</label>
                            <input type="range" id="contrastSlider" min="-100" max="100" value="0">
                            <button id="apply-contrast-btn" class="btn btn-sm">Apply</button>
                        </div>

                        <div class="tool-section">
                            <label>Color Overlay:</label>
                            <div class="input-group" style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                <input type="color" id="overlayColorPicker" value="#ff0000" style="width: 50px; height: 30px; cursor: pointer;">
                                <input type="range" id="overlayOpacitySlider" min="0" max="100" value="30" style="flex: 1;">
                                <span id="overlayOpacityValue" style="min-width: 40px;">30%</span>
                            </div>
                            <button id="apply-overlay-btn" class="btn btn-sm">Apply Color Overlay</button>
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button id="revert-image-btn" class="btn btn-warning">Revert to Original</button>
                        <button id="download-image-btn" class="btn btn-secondary">Download</button>
                        <button id="delete-image-btn" class="btn btn-danger">Delete</button>
                    </div>

                    <div class="status-message-container">
                        <!-- This paragraph will show status messages for various actions -->
                        <p id="modal-status-message" style="margin-top: 10px; font-weight: bold;"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Spinner -->
    <div id="loadingSpinner" class="spinner">
        <div class="spinner-inner"></div>
    </div>

    <script src="js/app.js"></script>
    <script src="js/crop-tool.js"></script>
    <script src="js/gallery.js"></script>
    <script src="js/editor.js"></script>
    <script src="js/auth.js"></script>

    <script>
        /**
         * This script adds the "Revert to Original" functionality to the image modal.
         * It is designed to work with the existing functions in your other JS files.
         */

        // We need to modify the existing openImageModal function to activate our new button.
        // This code waits for the original function to be loaded, then wraps it.
        document.addEventListener('DOMContentLoaded', () => {
            // Ensure the original function exists before we try to wrap it.
            // This also ensures API_BASE is defined, likely in app.js
            if (typeof openImageModal === 'function') {
                const originalOpenImageModal = openImageModal;

                // Redefine the global function
                window.openImageModal = async function(imageId) {
                    // Call the original function first to populate the modal
                    await originalOpenImageModal(imageId);

                    // Set the imageId on the modal's main image element so other scripts (editor.js) can find it.
                    const modalImage = document.getElementById('modalImage');
                    if (modalImage) {
                        modalImage.dataset.imageId = imageId;
                    }

                    // Now, find our new button and prepare it
                    const revertButton = document.getElementById('revert-image-btn');
                    if (revertButton) {
                        revertButton.dataset.imageId = imageId;
                        // Re-attach listener to ensure it's fresh for the new imageId
                        attachRevertListener(revertButton); 
                    }

                    // Clear any old status messages
                    const statusMessage = document.getElementById('modal-status-message');
                    if (statusMessage) {
                        statusMessage.textContent = '';
                    }
                    const modalSpinner = document.getElementById('modalImageSpinner');
                    if (modalSpinner) {
                        statusMessage.textContent = '';
                    }
                };
            }

            // Lock aspect ratio
            function setupAspectRatioLock(lockButtonId, widthInputId, heightInputId) {
                const lockButton = document.getElementById(lockButtonId);
                if (!lockButton) return;

                lockButton.addEventListener('click', function handler(e) {
                    e.preventDefault();
                    const img = document.getElementById('modalImage');
                    const widthInput = document.getElementById(widthInputId);
                    const heightInput = document.getElementById(heightInputId);

                    const originalWidth = img.naturalWidth;
                    const originalHeight = img.naturalHeight;
                    if (originalWidth === 0 || originalHeight === 0) return; // Avoid division by zero
                    const aspectRatio = originalWidth / originalHeight;

                    const updateHeight = () => {
                        const newWidth = parseInt(widthInput.value);
                        if (!isNaN(newWidth) && newWidth > 0) {
                            heightInput.value = Math.round(newWidth / aspectRatio);
                        }
                    };

                    updateHeight();
                    widthInput.addEventListener('input', updateHeight);

                    // Visually disable the button after it's been clicked
                    this.style.pointerEvents = 'none';
                    this.style.opacity = '0.5';
                });
            }

            setupAspectRatioLock('lockAspectRatio', 'resizeWidth', 'resizeHeight');

        });

        /**
         * Attaches the revert logic to the button inside the modal.
         */
        function attachRevertListener(button) {
            // This is a crucial step: It removes any old click listeners to prevent
            // the event from firing multiple times if the modal is opened repeatedly.
            const newButton = button.cloneNode(true);
            button.parentNode.replaceChild(newButton, button);

            newButton.addEventListener('click', async (event) => {
                const imageId = event.target.dataset.imageId;
                const statusMessage = document.getElementById('modal-status-message'); // Use the specific modal status message
                const modalSpinner = document.getElementById('modalImageSpinner');
                const imageElementInModal = document.getElementById('modalImage');

                if (!confirm('Are you sure you want to revert this image? All manipulations will be lost.')) {
                    return; // User clicked 'Cancel'
                }

                newButton.disabled = true;
                statusMessage.textContent = 'Reverting...';
                if (modalSpinner) modalSpinner.style.display = 'flex'; // Show spinner
                statusMessage.style.color = 'black';

                try {
                    const response = await fetch(`${API_BASE}?action=revert&id=${imageId}`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }
                    });
                    const result = await response.json();

                    if (response.ok && result.success) {
                        statusMessage.textContent = 'Image successfully reverted!';
                        statusMessage.style.color = 'green';
                        // Force the browser to reload the image by adding a timestamp
                        if (imageElementInModal) {
                            imageElementInModal.src = `${imageElementInModal.src.split('?')[0]}?t=${new Date().getTime()}`;
                        }
                    } else {
                        statusMessage.textContent = `Error: ${result.error || 'An unknown error occurred.'}`;
                        statusMessage.style.color = 'red';
                    }
                } finally {
                    newButton.disabled = false;
                    if (modalSpinner) modalSpinner.style.display = 'none'; // Hide spinner
                }
            });
        }
    </script>
</body>
</html>
