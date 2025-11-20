/**
 * Multiple File Upload with Drag & Drop
 * Handles file selection, preview, and upload
 */

let selectedFiles = [];

// Initialize upload functionality
function initUpload() {
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('imageFile');
    const uploadForm = document.getElementById('uploadForm');

    if (!dropZone || !fileInput || !uploadForm) return;

    // Click to select files
    dropZone.addEventListener('click', (e) => {
        // Don't trigger if clicking remove button or if click is on file input itself
        if (e.target.closest('.remove-file') || e.target === fileInput) return;
        e.preventDefault();
        e.stopPropagation();
        fileInput.click();
    });

    // File input change
    fileInput.addEventListener('change', (e) => {
        handleFiles(e.target.files);
        // Reset input to allow selecting same files again
        e.target.value = '';
    });

    // Prevent default behavior on file input click
    fileInput.addEventListener('click', (e) => {
        e.stopPropagation();
    });

    // Drag and drop events
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        e.stopPropagation();
        dropZone.classList.add('drag-over');
    });

    dropZone.addEventListener('dragleave', (e) => {
        e.preventDefault();
        e.stopPropagation();
        dropZone.classList.remove('drag-over');
    });

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        e.stopPropagation();
        dropZone.classList.remove('drag-over');
        
        const files = e.dataTransfer.files;
        handleFiles(files);
    });

    // Form submission
    uploadForm.addEventListener('submit', handleUploadSubmit);
}

/**
 * Handle selected files
 */
function handleFiles(files) {
    const fileArray = Array.from(files);
    const validFiles = [];

    fileArray.forEach(file => {
        // Validate file type
        if (!file.type.startsWith('image/')) {
            showUploadStatus(`${file.name} is not an image file`, 'error');
            return;
        }

        // Validate file size (5MB max)
        const maxSize = 5 * 1024 * 1024;
        if (file.size > maxSize) {
            showUploadStatus(`${file.name} exceeds 5MB limit`, 'error');
            return;
        }

        validFiles.push(file);
    });

    // Add valid files to selected files
    selectedFiles = [...selectedFiles, ...validFiles];
    updateFilePreview();
    updateFileInputDisplay();
}

/**
 * Update file preview grid
 */
function updateFilePreview() {
    const preview = document.getElementById('filePreview');
    if (!preview) return;

    preview.innerHTML = '';

    selectedFiles.forEach((file, index) => {
        const item = document.createElement('div');
        item.className = 'file-preview-item';

        // Create image preview (thumbnail)
        const img = document.createElement('img');
        img.style.opacity = '0';
        const reader = new FileReader();
        reader.onload = (e) => {
            const tempImg = new Image();
            tempImg.onload = function() {
                // Create canvas for thumbnail
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                
                // Calculate thumbnail dimensions (max 200x200)
                const maxSize = 200;
                let width = tempImg.width;
                let height = tempImg.height;
                
                if (width > height) {
                    if (width > maxSize) {
                        height = (height * maxSize) / width;
                        width = maxSize;
                    }
                } else {
                    if (height > maxSize) {
                        width = (width * maxSize) / height;
                        height = maxSize;
                    }
                }
                
                canvas.width = width;
                canvas.height = height;
                
                // Draw resized image
                ctx.drawImage(tempImg, 0, 0, width, height);
                
                // Set thumbnail as src
                img.src = canvas.toDataURL('image/jpeg', 0.7);
                img.style.opacity = '1';
            };
            tempImg.src = e.target.result;
        };
        reader.readAsDataURL(file);

        // Create file name
        const name = document.createElement('div');
        name.className = 'file-preview-name';
        name.textContent = file.name;
        name.title = file.name;

        // Create remove button
        const removeBtn = document.createElement('button');
        removeBtn.className = 'remove-file';
        removeBtn.innerHTML = '×';
        removeBtn.type = 'button';
        removeBtn.onclick = (e) => {
            e.stopPropagation();
            removeFile(index);
        };

        item.appendChild(img);
        item.appendChild(name);
        item.appendChild(removeBtn);
        preview.appendChild(item);
    });
}

/**
 * Remove file from selection
 */
function removeFile(index) {
    selectedFiles.splice(index, 1);
    updateFilePreview();
    updateFileInputDisplay();
}

/**
 * Update file input display text
 */
function updateFileInputDisplay() {
    const fileNameSpan = document.querySelector('.file-input-wrapper .file-name');
    if (!fileNameSpan) return;

    if (selectedFiles.length === 0) {
        fileNameSpan.textContent = 'Click to select images or drag and drop';
    } else if (selectedFiles.length === 1) {
        fileNameSpan.textContent = `1 image selected`;
    } else {
        fileNameSpan.textContent = `${selectedFiles.length} images selected`;
    }
}

/**
 * Handle form submission
 */
async function handleUploadSubmit(e) {
    e.preventDefault();

    if (selectedFiles.length === 0) {
        showUploadStatus('Please select at least one image', 'error');
        return;
    }

    const folder = document.getElementById('imageFolder').value;
    const progressContainer = document.getElementById('uploadProgress');
    progressContainer.innerHTML = '';

    // Upload files sequentially with progress
    let successCount = 0;
    let errorCount = 0;

    for (let i = 0; i < selectedFiles.length; i++) {
        const file = selectedFiles[i];
        const result = await uploadSingleFile(file, folder, i);
        
        if (result.success) {
            successCount++;
        } else {
            errorCount++;
        }
    }

    // Show summary
    if (errorCount === 0) {
        showUploadStatus(`Successfully uploaded ${successCount} image(s)`, 'success');
        
        // Clear selection after successful upload
        selectedFiles = [];
        updateFilePreview();
        updateFileInputDisplay();
        
        // Reload gallery
        setTimeout(() => {
            loadImages();
            progressContainer.innerHTML = '';
        }, 2000);
    } else {
        showUploadStatus(`Uploaded ${successCount} image(s), ${errorCount} failed`, 'warning');
    }
}

/**
 * Upload single file with progress
 */
async function uploadSingleFile(file, folder, index) {
    const progressContainer = document.getElementById('uploadProgress');
    
    // Create progress item
    const progressItem = document.createElement('div');
    progressItem.className = 'progress-item';
    progressItem.innerHTML = `
        <div class="progress-item-header">
            <span class="progress-item-name">${file.name}</span>
            <span class="progress-item-status">Uploading...</span>
        </div>
        <div class="progress-bar-container">
            <div class="progress-bar" style="width: 0%"></div>
        </div>
    `;
    progressContainer.appendChild(progressItem);

    const progressBar = progressItem.querySelector('.progress-bar');
    const statusSpan = progressItem.querySelector('.progress-item-status');

    try {
        // Create FormData
        const formData = new FormData();
        formData.append('image', file);
        formData.append('folder', folder);
        formData.append('title', file.name.replace(/\.[^/.]+$/, '')); // Filename without extension
        formData.append('description', '');
        formData.append('tags', '');

        // Upload with progress
        const response = await fetch(`${API_BASE}?action=upload`, {
            method: 'POST',
            body: formData
        });

        // Simulate progress (since fetch doesn't support upload progress directly)
        progressBar.style.width = '100%';

        const result = await response.json();

        if (result.success) {
            progressItem.classList.add('success');
            statusSpan.textContent = 'Complete';
            return { success: true };
        } else {
            throw new Error(result.error || 'Upload failed');
        }
    } catch (error) {
        progressItem.classList.add('error');
        statusSpan.textContent = 'Failed';
        console.error('Upload error:', error);
        return { success: false, error: error.message };
    }
}

/**
 * Show upload status message
 */
function showUploadStatus(message, type = 'info') {
    const statusDiv = document.getElementById('uploadStatus');
    if (!statusDiv) return;

    statusDiv.textContent = message;
    statusDiv.className = `upload-status ${type}`;
    statusDiv.style.display = 'block';

    // Auto-hide after 5 seconds
    setTimeout(() => {
        statusDiv.style.display = 'none';
    }, 5000);
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', initUpload);
