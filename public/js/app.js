// Global variables
// Use a relative API base to work from the same directory as `index.html`.
// This is more robust when the app is hosted under a subpath.
const API_BASE = './api.php';
let currentPage = 1;
let currentFolder = '';
let currentMediaType = ''; // '', 'image', or 'video'
let totalPages = 1;
let currentImageId = null;

// Initialize app
document.addEventListener('DOMContentLoaded', function() {
    // Wait for i18n to be ready
    if (typeof i18n !== 'undefined' && i18n.getCurrentLanguage()) {
        initializeApp();
    } else {
        document.addEventListener('i18nReady', initializeApp);
    }
});

function initializeApp() {
    // No need to call updatePageText() here - i18n.init() already did it
    
    setupEventListeners();
    // Note: loadFolders() and loadImages() are called from auth.js after login status is confirmed
    document.getElementById('createSubfolderBtn')?.addEventListener('click', createSubfolderFromUpload);
    // Register Service Worker for PWA capabilities
    if ('serviceWorker' in navigator) {
        const swPath = 'service-worker.js'; // Adjust if app served from subdirectory
        navigator.serviceWorker.register(swPath).catch(err => {
            console.warn('Service worker registration failed:', err);
        });
    }
}

function setupEventListeners() {
    // Hamburger menu toggle
    const hamburgerMenu = document.getElementById('hamburgerMenu');
    const nav = document.querySelector('.nav');
    if (hamburgerMenu && nav) {
        hamburgerMenu.addEventListener('click', function() {
            this.classList.toggle('active');
            nav.classList.toggle('mobile-open');
        });
        
        // Close menu when clicking a nav link on mobile
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth < 768) {
                    hamburgerMenu.classList.remove('active');
                    nav.classList.remove('mobile-open');
                }
            });
        });
    }
    
    // Navigation
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            switchView(this.dataset.view);
        });
    });

    // Language selector
    const languageSelect = document.getElementById('languageSelect');
    if (languageSelect) {
        // Set current language on load
        languageSelect.value = i18n.getCurrentLanguage();
        
        // Handle language change
        languageSelect.addEventListener('change', async function(e) {
            const language = this.value;
            await i18n.setLanguage(language);
            // Reload the page to fully apply language changes
            window.location.reload();
        });
        
        // Listen for i18n changes from other tabs/windows
        document.addEventListener('i18nChanged', function() {
            languageSelect.value = i18n.getCurrentLanguage();
            i18n.updatePageText();
        });
    }

    // Gallery controls
    document.getElementById('folderSelect').addEventListener('change', function() {
        currentFolder = this.value;
        currentPage = 1;
        loadImages();
    });

    // Media type filter buttons
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            // Remove active class from all buttons
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            // Add active class to clicked button
            this.classList.add('active');
            // Update current media type
            currentMediaType = this.dataset.type;
            // Reset to first page and reload
            currentPage = 1;
            loadImages();
        });
    });

    document.getElementById('prevBtn').addEventListener('click', previousPage);
    document.getElementById('nextBtn').addEventListener('click', nextPage);

    // Search
    document.getElementById('searchInput').addEventListener('keyup', debounce(searchImages, 500));

    // Page Designer Button
    const pageDesignerBtn = document.getElementById('pageDesignerBtn');
    if (pageDesignerBtn) {
        pageDesignerBtn.addEventListener('click', function() {
            window.location.href = 'my-pages.php';
        });
    }

    // Upload form
    // Disabled legacy single-file handler (multi-file logic now lives in upload.js)
    // document.getElementById('uploadForm').addEventListener('submit', handleImageUpload);

    // File input
    const imageFileInput = document.getElementById('imageFile');
    const fileInputWrapper = document.querySelector('.file-input-wrapper');
    
    // Click to open file browser
    fileInputWrapper.addEventListener('click', function(e) {
        if (e.target !== imageFileInput && e.target.tagName !== 'INPUT') {
            imageFileInput.click();
        }
    });
    
    // File selected
    imageFileInput.addEventListener('change', function() {
        const fileName = this.files[0]?.name || 'No file selected';
        const fileSize = this.files[0]?.size || 0;
        
        // Format file size
        let sizeText = '';
        if (fileSize > 0) {
            if (fileSize > 1024 * 1024) {
                sizeText = ` (${(fileSize / (1024 * 1024)).toFixed(2)} MB)`;
            } else if (fileSize > 1024) {
                sizeText = ` (${(fileSize / 1024).toFixed(2)} KB)`;
            } else {
                sizeText = ` (${fileSize} bytes)`;
            }
        }
        
        document.querySelector('.file-name').textContent = fileName + sizeText;
    });
    
    // Drag and drop
    fileInputWrapper.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.add('dragover');
    });
    
    fileInputWrapper.addEventListener('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.remove('dragover');
    });
    
    fileInputWrapper.addEventListener('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.remove('dragover');
        
        const files = e.dataTransfer.files;
        if (files && files.length > 0) {
            // Set the files to the input
            imageFileInput.files = files;
            
            // Trigger change event
            const event = new Event('change', { bubbles: true });
            imageFileInput.dispatchEvent(event);
        }
    });

    // Modal
    document.querySelector('.close').addEventListener('click', closeModal);
    window.addEventListener('click', function(e) {
        const modal = document.getElementById('imageModal');
        if (e.target === modal) closeModal();
    });
    
    // Metadata editing
    document.getElementById('editMetadataBtn')?.addEventListener('click', enterMetadataEditMode);
    document.getElementById('saveMetadataBtn')?.addEventListener('click', saveMetadata);
    document.getElementById('cancelMetadataBtn')?.addEventListener('click', exitMetadataEditMode);
    
    // AI tag generation button in metadata edit section
    document.getElementById('generateAITagsBtn')?.addEventListener('click', async function(e) {
        e.preventDefault();
        console.log('🤖 Generate AI Tags button clicked');
        
        const button = this;
        const originalText = button.innerHTML;
        
        try {
            // Disable button during processing
            button.disabled = true;
            button.innerHTML = '🔄 Generating tags...';
            
            // Get the current image element
            const img = document.getElementById('modalImage');
            if (!img || !img.src) {
                alert('No image loaded');
                return;
            }
            
            console.log('Calling AI tagging for image:', img.src);
            
            // Call the AI tagging function
            const tags = await window.AITagging?.generateImageTags?.(img);
            
            console.log('AI tags received:', tags);
            
            if (tags && tags.length > 0) {
                // Get existing tags
                const tagsField = document.getElementById('editTags');
                const existingTags = tagsField.value ? 
                    tagsField.value.split(',').map(t => t.trim()).filter(t => t) : [];
                
                // Merge with new tags (avoid duplicates)
                const newTags = tags.filter(tag => !existingTags.includes(tag));
                const allTags = [...existingTags, ...newTags];
                
                // Update field
                tagsField.value = allTags.join(', ');
                
                // Visual feedback
                tagsField.style.transition = 'background-color 0.3s';
                tagsField.style.backgroundColor = '#d4edda';
                setTimeout(() => {
                    tagsField.style.backgroundColor = '';
                }, 1000);
                
                // Success message
                alert(`✅ Added ${newTags.length} AI-generated tag(s):\n${newTags.join(', ')}\n\nClick "Save Changes" below to save.`);
            } else {
                alert('❌ Could not generate tags. Please try again.');
            }
        } catch (error) {
            console.error('Error generating AI tags:', error);
            alert('❌ Error generating tags: ' + error.message);
        } finally {
            // Restore button
            button.disabled = false;
            button.innerHTML = originalText;
        }
    });
}

// Attach listener for the share checkbox
document.getElementById('shareImageCheckbox')?.addEventListener('change', handleShareToggle);

// Attach listener for copy share link button
document.getElementById('copyShareLinkBtn')?.addEventListener('click', function() {
    const shareLinkInput = document.getElementById('shareLinkInput');
    const btn = this;
    
    shareLinkInput.select();
    shareLinkInput.setSelectionRange(0, 99999); // For mobile devices
    
    navigator.clipboard.writeText(shareLinkInput.value).then(() => {
        const originalText = btn.textContent;
        btn.textContent = 'Copied!';
        btn.style.background = '#2e7d32';
        
        setTimeout(() => {
            btn.textContent = originalText;
            btn.style.background = '#4caf50';
        }, 2000);
    }).catch(err => {
        console.error('Failed to copy:', err);
        alert('Failed to copy link. Please copy manually.');
    });
});

function switchView(view) {
    // Hide all views
    document.querySelectorAll('.view').forEach(v => v.classList.remove('active'));
    
    // Show selected view
    document.getElementById(`${view}-view`).classList.add('active');

    // Update nav
    document.querySelectorAll('.nav-link').forEach(link => {
        link.classList.remove('active');
        if (link.dataset.view === view) link.classList.add('active');
    });

    // Load data for specific views
    if (view === 'gallery') {
        loadImages();
    } else if (view === 'folders') {
        loadFolders();
    } else if (view === 'settings') {
        if (typeof loadSettings === 'function') {
            loadSettings();
        }
    } else if (view === 'admin') {
        if (typeof loadAdmin === 'function') {
            loadAdmin();
        }
    }
}

function showLoading(show = true) {
    const spinner = document.getElementById('loadingSpinner');
    if (show) {
        spinner.classList.add('active');
    } else {
        spinner.classList.remove('active');
    }
}

// Gallery Functions
function loadImages() {
    showLoading(true);

    // If filtering for pages only, skip image API call
    if (currentMediaType === 'page') {
        fetch(`${API_BASE}?action=getlandingpages`)
            .then(r => r.json())
            .then(pagesData => {
                if (pagesData.success) {
                    displayGallery(pagesData.pages, { current_page: 1, total_pages: 1 });
                    totalPages = 1;
                } else {
                    document.getElementById('gallery').innerHTML = 
                        '<div class="gallery-empty"><p>No pages found</p></div>';
                }
            })
            .catch(error => {
                console.error('Error loading pages:', error);
                document.getElementById('gallery').innerHTML = 
                    '<div class="gallery-empty"><p>Error loading pages.</p></div>';
            })
            .finally(() => showLoading(false));
        return;
    }

    let url = `${API_BASE}?action=list&page=${currentPage}`;
    if (currentFolder) url += `&folder=${currentFolder}`;
    if (currentMediaType) url += `&type=${currentMediaType}`;

    // Fetch images and landing pages
    Promise.all([
        fetch(url).then(r => r.json()),
        fetch(`${API_BASE}?action=getlandingpages`).then(r => r.json())
    ])
        .then(([imagesData, pagesData]) => {
            if (imagesData.success) {
                let items = imagesData.data;
                // Add landing pages at the top only when showing all items
                if (currentMediaType === '' && pagesData.success && pagesData.pages.length > 0) {
                    items = [...pagesData.pages, ...items];
                }
                displayGallery(items, imagesData.pagination);
                totalPages = imagesData.pagination.total_pages;
            } else {
                console.error('API returned error:', imagesData.error);
                document.getElementById('gallery').innerHTML = 
                    `<div class="gallery-empty"><p>Error: ${imagesData.error || 'Failed to load images'}</p></div>`;
            }
        })
        .catch(error => {
            console.error('Error loading gallery:', error);
            document.getElementById('gallery').innerHTML = 
                '<div class="gallery-empty"><p>Error loading gallery. Please try refreshing the page.</p></div>';
        })
        .finally(() => showLoading(false));
}

function displayGallery(items, pagination) {
    const gallery = document.getElementById('gallery');
    
    if (items.length === 0) {
        gallery.innerHTML = '<div class="gallery-empty"><p>No images found</p></div>';
        return;
    }

    gallery.innerHTML = items.map(item => {
        // Landing page card
        if (item.type === 'landing_page') {
            const statusBadge = item.is_active ? '<span class="page-status active">●Active</span>' : '<span class="page-status inactive">●Inactive</span>';
            const editButton = item.can_edit ? `<button class="btn btn-sm" onclick="window.location.href='${item.edit_url}'; return false;">✏️ Edit</button>` : '';
            
            // Use preview image if available, otherwise use gradient background
            const backgroundStyle = item.preview_image 
                ? `background-image: url('${item.preview_image}'); background-size: cover; background-position: center;`
                : `background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);`;
            
            return `
            <div class="gallery-item landing-page-card" data-pageid="${item.id}" style="${backgroundStyle}">
                ${!item.preview_image ? '<div class="landing-page-icon">📄</div>' : '<div class="page-preview-overlay"></div>'}
                <div class="landing-page-info">
                    <h3 class="landing-page-title">${item.title}</h3>
                    ${statusBadge}
                    <p class="landing-page-updated">Updated: ${new Date(item.updated_at).toLocaleDateString()}</p>
                </div>
                <div class="gallery-item-overlay">
                    ${editButton}
                    <button class="btn btn-sm" onclick="window.open('${item.view_url}', '_blank'); return false;">🔍 View</button>
                    <button class="btn btn-sm" onclick="copyToClipboard('${item.view_url}'); return false;">🔗 Copy Link</button>
                </div>
            </div>
        `;
        }
        
        // Regular image card
        const thumbnailUrl = `${item.thumbnail_url}?t=${new Date().getTime()}`;
        const isVideo = item.file_type === 'video';
        const videoIcon = isVideo ? '<div class="video-indicator">▶️ VIDEO</div>' : '';
        
        const designButton = (item.shared && item.share_token) 
            ? `<button class="btn btn-sm btn-design" onclick="event.stopPropagation(); window.location.href='design-landing.php?share=${item.share_token}'; return false;">🎨 Design</button>`
            : '';
        
        return `
        <div class="gallery-item ${isVideo ? 'is-video' : ''}" 
             data-imageid="${item.id}"
             data-filename="${item.title || item.original_name}"
             data-filesize="${item.file_size || ''}"
             data-uploaddate="${item.uploaded_at || ''}"
             onclick="openImageModal(${item.id})">
            <div class="gallery-item-folder-label">${item.folder}</div>
            <img src="${thumbnailUrl}" alt="${item.title || item.original_name}">
            ${videoIcon}
            <div class="gallery-item-overlay">
                <button class="btn btn-sm" onclick="openImageModal(${item.id}); return false;">✏️ Edit</button>
                <button class="btn btn-sm" onclick="event.stopPropagation(); if(window.lightbox) window.lightbox.openFromGallery(this.closest('.gallery-item')); return false;">🔍 View</button>
                ${designButton}
                <button class="btn btn-sm btn-danger" onclick="deleteImageQuick(${item.id}); return false;">Delete</button>
            </div>
        </div>
    `;
    }).join('');
    
    // Update pagination if provided
    if (pagination) {
        updatePagination(pagination);
    }
}

// Helper function to copy to clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('Link copied to clipboard!');
    }).catch(err => {
        console.error('Failed to copy:', err);
        prompt('Copy this link:', text);
    });
}

function updatePagination(pagination) {
    document.getElementById('pageInfo').textContent = 
        `Page ${pagination.current_page} of ${pagination.total_pages}`;
    document.getElementById('prevBtn').disabled = pagination.current_page === 1;
    document.getElementById('nextBtn').disabled = pagination.current_page === pagination.total_pages;
}

function previousPage() {
    if (currentPage > 1) {
        currentPage--;
        loadImages();
        window.scrollTo(0, 0);
    }
}

function nextPage() {
    if (currentPage < totalPages) {
        currentPage++;
        loadImages();
        window.scrollTo(0, 0);
    }
}

function searchImages(query) {
    currentPage = 1;
    showLoading(true);

    let url = `${API_BASE}?action=list&page=1`;
    if (query.trim()) {
        url += `&search=${encodeURIComponent(query)}`;
    }

    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayGallery(data.data);
                updatePagination(data.pagination);
                totalPages = data.pagination.total_pages;
            }
        })
        .catch(error => console.error('Error searching:', error))
        .finally(() => showLoading(false));
}

// Modal Functions
async function openImageModal(imageId) {
    currentImageId = imageId;
    showLoading(true);

    try {
        const response = await fetch(`${API_BASE}?action=get&id=${imageId}`);
        const data = await response.json();

        if (data.success) {
            const image = data.data;
            const isVideo = image.file_type === 'video';
            const modalImage = document.getElementById('modalImage');
            const modalImageContainer = modalImage.parentElement;
            
            // Clear previous content
            const existingVideo = modalImageContainer.querySelector('video');
            if (existingVideo) {
                existingVideo.remove();
            }
            
            if (isVideo) {
                // Hide image, show video player
                modalImage.style.display = 'none';
                const video = document.createElement('video');
                video.controls = true;
                video.style.maxWidth = '100%';
                video.style.maxHeight = '70vh';
                video.style.display = 'block';
                video.src = image.original_url;
                modalImageContainer.insertBefore(video, modalImage);
                
                // Hide image manipulation tools for videos
                document.querySelector('.modal-tools').style.display = 'none';
            } else {
                // Show image, hide any video
                modalImage.style.display = 'block';
                modalImage.src = image.original_url;
                document.querySelector('.modal-tools').style.display = 'block';
            }
            
            document.getElementById('modalTitle').textContent = image.title || image.original_name;
            document.getElementById('modalDescription').textContent = image.description || 'No description';
            document.getElementById('modalTags').textContent = image.tags ? `Tags: ${image.tags}` : '';
            document.getElementById('modalDimensions').textContent = `${image.width}x${image.height}px`;
            document.getElementById('modalFileSize').textContent = `${(image.file_size / 1024 / 1024).toFixed(2)}MB`;
            document.getElementById('modalCreated').textContent = new Date(image.created_at).toLocaleDateString();

            // Auto-fill tool dimensions with the image's current size (only for images)
            if (!isVideo) {
                document.getElementById('resizeWidth').value = image.width;
                document.getElementById('resizeHeight').value = image.height;

                // Reset sliders and any live preview filters from previous images
                document.getElementById('brightnessSlider').value = 0;
                document.getElementById('contrastSlider').value = 0;
                document.getElementById('modalImage').style.filter = 'none';
            }

            // Set the share checkbox status
            document.getElementById('shareImageCheckbox').checked = !!parseInt(image.shared);

            // Show share link if image is shared and has a token
            const shareLinkContainer = document.getElementById('shareLinkContainer');
            const shareLinkInput = document.getElementById('shareLinkInput');
            if (image.shared && image.share_token) {
                const shareUrl = `${window.location.origin}${window.location.pathname.replace('index.php', 'share.php')}?share=${image.share_token}`;
                shareLinkInput.value = shareUrl;
                shareLinkContainer.style.display = 'block';
            } else {
                shareLinkContainer.style.display = 'none';
            }

            document.getElementById('imageModal').classList.add('active');

            // EXIF display logic (only for images)
            const exifWrapper = document.getElementById('exifToggleWrapper');
            const exifSection = document.getElementById('exifSection');
            const exifTableBody = document.querySelector('#exifTable tbody');
            const toggleExifBtn = document.getElementById('toggleExifBtn');
            exifSection.style.display = 'none';
            toggleExifBtn.textContent = 'Show EXIF';
            if (!isVideo && image.exif && typeof image.exif === 'object') {
                exifWrapper.style.display = 'block';
                exifTableBody.innerHTML = '';
                Object.keys(image.exif).forEach(key => {
                    const val = image.exif[key];
                    const row = document.createElement('tr');
                    const kCell = document.createElement('td');
                    const vCell = document.createElement('td');
                    kCell.textContent = key;
                    vCell.textContent = val;
                    kCell.style.fontWeight = '600';
                    kCell.style.padding = '4px 6px';
                    vCell.style.padding = '4px 6px';
                    vCell.style.wordBreak = 'break-word';
                    row.appendChild(kCell);
                    row.appendChild(vCell);
                    row.style.borderTop = '1px solid #eee';
                    exifTableBody.appendChild(row);
                });
                toggleExifBtn.onclick = function(e){
                    e.stopPropagation();
                    if (exifSection.style.display === 'none') {
                        exifSection.style.display = 'block';
                        toggleExifBtn.textContent = 'Hide EXIF';
                    } else {
                        exifSection.style.display = 'none';
                        toggleExifBtn.textContent = 'Show EXIF';
                    }
                };
            } else {
                exifWrapper.style.display = 'none';
                exifTableBody.innerHTML = '';
            }
        }
    } catch (error) {
        console.error('Error loading image:', error);
    } finally {
        showLoading(false);
    }
}

function closeModal() {
    document.getElementById('imageModal').classList.remove('active');
    currentImageId = null;
    // Reset to view mode when closing
    exitMetadataEditMode();
}

function exitMetadataEditMode() {
    document.getElementById('metadataViewMode').style.display = 'block';
    document.getElementById('metadataEditMode').style.display = 'none';
    document.getElementById('editMetadataBtn').style.display = 'inline-block';
}

function enterMetadataEditMode() {
    console.log('📝 Entering edit mode...');
    
    // Get current values from display
    const title = document.getElementById('modalTitle').textContent;
    const description = document.getElementById('modalDescription').textContent;
    const tagsText = document.getElementById('modalTags').textContent;
    const tags = tagsText.replace('Tags: ', '').trim();
    
    console.log('Current tags from display:', tags);
    
    // Populate edit fields
    document.getElementById('editTitle').value = title;
    document.getElementById('editDescription').value = description === 'No description' ? '' : description;
    document.getElementById('editTags').value = tags;
    
    console.log('Edit fields populated');
    
    // Toggle visibility
    document.getElementById('metadataViewMode').style.display = 'none';
    document.getElementById('metadataEditMode').style.display = 'block';
    document.getElementById('editMetadataBtn').style.display = 'none';
    
    console.log('✅ Edit mode visible');
}

async function saveMetadata() {
    if (!currentImageId) return;
    
    const title = document.getElementById('editTitle').value.trim();
    const description = document.getElementById('editDescription').value.trim();
    const tags = document.getElementById('editTags').value.trim();
    
    console.log('💾 Saving metadata...');
    console.log('Title:', title);
    console.log('Description:', description);
    console.log('Tags:', tags);
    
    showLoading(true);
    
    try {
        const response = await fetch(`${API_BASE}?action=update&id=${currentImageId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                title: title,
                description: description,
                tags: tags
            })
        });
        
        const data = await response.json();
        console.log('Save response:', data);
        
        if (data.success) {
            // Update display
            document.getElementById('modalTitle').textContent = title || 'Untitled';
            document.getElementById('modalDescription').textContent = description || 'No description';
            document.getElementById('modalTags').textContent = tags ? `Tags: ${tags}` : '';
            
            console.log('✅ Metadata saved successfully!');
            console.log('Updated display - Tags:', tags);
            
            // Exit edit mode
            exitMetadataEditMode();
            
            // Refresh gallery
            loadImages();
            
            // Show success message
            alert('✅ Saved! Tags: ' + (tags || 'none'));
        } else {
            alert('Error updating metadata: ' + (data.error || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error saving metadata:', error);
        alert('Error saving metadata');
    } finally {
        showLoading(false);
    }
}

function downloadImage() {
    if (currentImageId) {
        window.location.href = `${API_BASE}?action=download&id=${currentImageId}`;
    }
}

function deleteImage() {
    if (confirm('Are you sure you want to delete this image?')) {
        showLoading(true);

        fetch(`${API_BASE}?action=delete&id=${currentImageId}`, { method: 'DELETE' })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeModal();
                    loadImages();
                    alert('Image deleted successfully');
                } else {
                    alert('Error deleting image');
                }
            })
            .catch(error => console.error('Error deleting:', error))
            .finally(() => showLoading(false));
    }
}

function deleteImageQuick(imageId) {
    if (confirm('Are you sure you want to delete this image?')) {
        showLoading(true);

        fetch(`${API_BASE}?action=delete&id=${imageId}`, { method: 'DELETE' })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadImages();
                } else {
                    alert('Error deleting image');
                }
            })
            .catch(error => console.error('Error deleting:', error))
            .finally(() => showLoading(false));
    }
}

async function handleShareToggle(e) {
    const isChecked = e.target.checked;
    const imageId = currentImageId;
    const statusMessage = document.getElementById('modal-status-message');
    const shareLinkContainer = document.getElementById('shareLinkContainer');
    const shareLinkInput = document.getElementById('shareLinkInput');

    if (!imageId) return;

    statusMessage.textContent = 'Updating...';
    statusMessage.style.color = 'black';

    try {
        const response = await fetch(`${API_BASE}?action=update&id=${imageId}`, {
            method: 'POST', // Using POST as some servers don't like PUT
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ shared: isChecked })
        });

        const result = await response.json();

        if (result.success) {
            statusMessage.textContent = isChecked ? 'Image is now shared.' : 'Image is now private.';
            statusMessage.style.color = 'green';
            
            // Show/hide share link
            if (isChecked && result.share_token) {
                const shareUrl = `${window.location.origin}${window.location.pathname.replace('index.php', 'share.php')}?share=${result.share_token}`;
                shareLinkInput.value = shareUrl;
                shareLinkContainer.style.display = 'block';
            } else {
                shareLinkContainer.style.display = 'none';
            }
        } else {
            statusMessage.textContent = `Error: ${result.error || 'Could not update status.'}`;
            statusMessage.style.color = 'red';
            e.target.checked = !isChecked; // Revert checkbox on failure
        }
    } catch (error) {
        console.error('Share toggle error:', error);
    }
}

// Upload Function
function handleImageUpload(e) {
    e.preventDefault();
    showLoading(true);

    const formData = new FormData(document.getElementById('uploadForm'));

    // Offline handling: queue uploads if offline
    if (!navigator.onLine && window.offlineUploadQueue) {
        window.offlineUploadQueue.queueFormData(formData);
        const statusDiv = document.getElementById('uploadStatus');
        statusDiv.className = 'upload-status info';
        statusDiv.textContent = 'Offline: upload queued and will sync when online.';
        statusDiv.style.display = 'block';
        document.getElementById('uploadForm').reset();
        document.querySelector('.file-name').textContent = 'No file selected';
        showLoading(false);
        return;
    }
    
    fetch(`${API_BASE}?action=upload`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        const statusDiv = document.getElementById('uploadStatus');
        statusDiv.style.display = 'block';

        if (data.success) {
            statusDiv.className = 'upload-status success';
            statusDiv.textContent = 'Image uploaded successfully!';
            document.getElementById('uploadForm').reset();
            document.querySelector('.file-name').textContent = 'No file selected';

            setTimeout(() => {
                switchView('gallery');
                loadImages();
            }, 1500);
        } else {
            statusDiv.className = 'upload-status error';
            statusDiv.textContent = data.error || 'Error uploading image';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        const statusDiv = document.getElementById('uploadStatus');
        statusDiv.className = 'upload-status error';
        statusDiv.textContent = 'Error uploading image';
        statusDiv.style.display = 'block';
    })
    .finally(() => showLoading(false));
}

// Folders Functions
function loadFolders() {
    const url = `${API_BASE}?action=list_folders`;
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data && data.success) {
                displayFolderSelect(data.data);
                displayFoldersList(data.data);
            } else {
                // Gracefully handle authentication required (user not logged in or session expired)
                if (data.error && (data.error.includes('Authentication') || data.error.includes('Session expired') || data.error.includes('required'))) {
                    // Silently set default folder option for guest/expired session users
                    const select = document.getElementById('imageFolder');
                    if (select) {
                        select.innerHTML = '<option value="default">Default</option>';
                    }
                    // Don't log errors for expected authentication failures
                } else {
                    console.warn('Unexpected folders response', data);
                }
            }
        })
        .catch(error => console.error('Error loading folders:', error));
}

function displayFolderSelect(folders) {
    const select = document.getElementById('imageFolder');
    const createSubfolderSection = document.getElementById('createSubfolderSection');

    // Always start with a clean slate and add default option
    select.innerHTML = '<option value="default">Default</option>';
    
    if (folders.length === 0) {
        // If no subfolders exist, show the create subfolder section
        createSubfolderSection.style.display = 'block';
        select.style.display = 'block'; // Keep select visible with default option
    } else {
        // If folders exist, hide the create section and show the dropdown
        createSubfolderSection.style.display = 'none';
        select.style.display = 'block';
        // Add user's subfolders
        folders.forEach(folder => select.innerHTML += `<option value="${folder.name}">${folder.name}</option>`);
    }

    // Also update folder filter
    const filterSelect = document.getElementById('folderSelect');
    const currentValue = filterSelect.value;
    filterSelect.innerHTML = '<option value="">All Folders</option>';
    
    folders.forEach(folder => {
        filterSelect.innerHTML += `<option value="${folder.name}">${folder.name}</option>`;
    });
    
    filterSelect.value = currentValue;
}

function displayFoldersList(folders) {
    const list = document.getElementById('foldersList');
    list.innerHTML = folders.map(folder => `
        <div class="folder-item">
            <h3>${folder.name}</h3>
            <p>${folder.description || 'No description'}</p>
            <div class="folder-item-actions">
                ${folder.name !== 'default' ? `
                    <button class="btn btn-danger btn-sm" onclick="deleteFolder('${folder.name}')">Delete</button>
                ` : ''}
            </div>
        </div>
    `).join('');
}

function createFolder() {
    const name = document.getElementById('newFolderName').value.trim();
    const description = document.getElementById('newFolderDesc').value.trim();

    if (!name) {
        alert('Please enter a folder name');
        return;
    }

    showLoading(true);

    fetch(`${API_BASE}?action=create_folder`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, description, parent_id: null }) // Specify parent_id as null for top-level folders
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('newFolderName').value = '';
            document.getElementById('newFolderDesc').value = '';
            loadFolders();
            alert('Folder created successfully');
        } else {
            alert(data.error || 'Error creating folder');
        }
    })
    .catch(error => console.error('Error:', error))
    .finally(() => showLoading(false));
}

function createSubfolderFromUpload() {
    const nameInput = document.getElementById('newSubfolderName');
    const name = nameInput.value.trim();
    const statusDiv = document.getElementById('subfolderStatus');

    if (!name) {
        statusDiv.textContent = 'Please enter a subfolder name.';
        statusDiv.style.color = 'red';
        return;
    }

    statusDiv.textContent = 'Creating...';
    statusDiv.style.color = 'black';

    fetch(`${API_BASE}?action=create_folder`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name: name, description: '', parent_id: null })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            statusDiv.textContent = `Subfolder "${name}" created successfully!`;
            statusDiv.style.color = 'green';
            nameInput.value = ''; // Clear the input
            loadFolders(); // Reload the folder list to show the new folder in the dropdown
        } else {
            statusDiv.textContent = data.error || 'Error creating subfolder.';
            statusDiv.style.color = 'red';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        statusDiv.textContent = 'A network error occurred.';
        statusDiv.style.color = 'red';
    });
}

function deleteFolder(name) {
    if (confirm(`Are you sure you want to delete the "${name}" folder?`)) {
        showLoading(true);

        fetch(`${API_BASE}?action=delete_folder&name=${name}`, { method: 'DELETE' })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadFolders();
                } else {
                    alert(data.error || 'Error deleting folder');
                }
            })
            .catch(error => console.error('Error:', error))
            .finally(() => showLoading(false));
    }
}

// Utility Functions
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Escape HTML to prevent XSS when inserting strings into the DOM
function escapeHtml(unsafe) {
    if (unsafe === null || unsafe === undefined) return '';
    return String(unsafe)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

// Safe helper to set innerHTML for lists built from data by escaping interpolated values
function setInnerHTMLSafe(element, htmlString) {
    // We still use innerHTML for templates but ensure dynamic values are escaped by the caller.
    element.innerHTML = htmlString;
}

// Patch usages that appended raw values: replace with safe assembly where possible
// Replace folder dropdown population to escape folder names
const _origDisplayFolderSelect = displayFolderSelect;
displayFolderSelect = function(folders) {
    const select = document.getElementById('imageFolder');
    const createSubfolderSection = document.getElementById('createSubfolderSection');

    select.innerHTML = '';
    if (folders.length === 0) {
        createSubfolderSection.style.display = 'block';
        select.style.display = 'none';
    } else {
        createSubfolderSection.style.display = 'none';
        select.style.display = 'block';
    }

    folders.forEach(folder => {
        const opt = document.createElement('option');
        opt.value = folder.name;
        opt.textContent = folder.name;
        select.appendChild(opt);
    });

    const filterSelect = document.getElementById('folderSelect');
    const currentValue = filterSelect.value;
    filterSelect.innerHTML = '';
    const emptyOpt = document.createElement('option');
    emptyOpt.value = '';
    emptyOpt.textContent = 'All Folders';
    filterSelect.appendChild(emptyOpt);

    folders.forEach(folder => {
        const opt = document.createElement('option');
        opt.value = folder.name;
        opt.textContent = folder.name;
        filterSelect.appendChild(opt);
    });

    filterSelect.value = currentValue;
};

// Patch displayFoldersList to use safe DOM methods
const _origDisplayFoldersList = displayFoldersList;
displayFoldersList = function(folders) {
    const list = document.getElementById('foldersList');
    list.innerHTML = '';
    folders.forEach(folder => {
        const item = document.createElement('div');
        item.className = 'folder-item';

        const h3 = document.createElement('h3');
        h3.textContent = folder.name;
        item.appendChild(h3);

        const p = document.createElement('p');
        p.textContent = folder.description || 'No description';
        item.appendChild(p);

        const actions = document.createElement('div');
        actions.className = 'folder-item-actions';
        if (folder.name !== 'default') {
            const btn = document.createElement('button');
            btn.className = 'btn btn-danger btn-sm';
            btn.textContent = 'Delete';
            btn.addEventListener('click', function() { deleteFolder(folder.name); });
            actions.appendChild(btn);
        }
        item.appendChild(actions);
        list.appendChild(item);
    });
};
