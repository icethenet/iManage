// AI Tagging module loaded
/**
 * AI Image Tagging with TensorFlow.js MobileNet
 * Automatically generates tags for uploaded images using client-side ML
 * Zero cost, runs entirely in browser
 */

let mobilenetModel = null;
let isModelLoading = false;

/**
 * Load MobileNet model (lazy loading - only when needed)
 */
async function loadMobileNet() {
    if (mobilenetModel) {
        return mobilenetModel;
    }
    
    if (isModelLoading) {
        // Wait for existing load to complete
        while (isModelLoading) {
            await new Promise(resolve => setTimeout(resolve, 100));
        }
        return mobilenetModel;
    }
    
    try {
        isModelLoading = true;
        
        // Check if mobilenet is available
        if (typeof mobilenet === 'undefined') {
            throw new Error('MobileNet library not loaded. Check if script is included in HTML.');
        }
        
        // Check if TensorFlow.js is available
        if (typeof tf === 'undefined') {
            throw new Error('TensorFlow.js not loaded. Check if script is included in HTML.');
        }
        
        mobilenetModel = await mobilenet.load();
        return mobilenetModel;
    } catch (error) {
        console.error('❌ Failed to load AI model:', error);
        console.error('Stack:', error.stack);
        alert('Failed to load AI model. Please refresh the page and try again.\n\nError: ' + error.message);
        return null;
    } finally {
        isModelLoading = false;
    }
}

/**
 * Generate AI tags for an image
 * @param {HTMLImageElement|File} imageSource - Image element or File object
 * @returns {Promise<string[]>} Array of predicted tags
 */
async function generateImageTags(imageSource) {
    try {
        const model = await loadMobileNet();
        if (!model) {
            console.warn('AI model not available');
            return [];
        }
        
        let imgElement;
        
        // Handle File object (create temporary image element)
        if (imageSource instanceof File) {
            imgElement = await createImageElement(imageSource);
        } else if (typeof imageSource === 'string') {
            try {
                // If it's a URL string, create an image element
                imgElement = await createImageElementFromUrl(imageSource);
            } catch (urlError) {
                console.error('Failed to create image from URL:', urlError.message);
                throw new Error('Failed to load image from URL: ' + urlError.message);
            }
        } else {
            // Already an image element - clone it with CORS
            imgElement = await createImageElementFromUrl(imageSource.src);
        }
        
        console.log('Image element created:', imgElement.src);
        console.log('🔍 Analyzing image with AI...');
        
        const predictions = await model.classify(imgElement);
        
        if (!predictions || predictions.length === 0) {
            console.error('Model returned no predictions');
            throw new Error('Model classification returned empty results');
        }
        
        // Extract top 3-5 predictions with confidence > 5%
        const tags = predictions
            .filter(pred => pred.probability > 0.05)
            .slice(0, 5)
            .map(pred => {
                // Clean up tag name
                return pred.className
                    .split(',')[0]
                    .trim()
                    .toLowerCase()
                    .replace(/_/g, ' ')
                    .replace(/\s+/g, ' ');
            });
        
        if (tags.length === 0) {
            // If all filtered out, return top prediction
            const fallbackTag = predictions[0].className
                .split(',')[0]
                .trim()
                .toLowerCase()
                .replace(/_/g, ' ');
            return [fallbackTag];
        }
        
        // Clean up temporary element
        if (imgElement.parentNode) {
            imgElement.remove();
        }
        
        return tags;
    } catch (error) {
        console.error('Error generating tags:', error.message);
        throw error;
    }
}

/**
 * Create image element from URL string with CORS proxy
 * @param {string} url - Image URL
 * @returns {Promise<HTMLImageElement>}
 */
function createImageElementFromUrl(url) {
    return new Promise((resolve, reject) => {
        const img = new Image();
        
        // DON'T use crossOrigin for same-origin images - it blocks session cookies!
        // Only use crossOrigin for external images
        const isSameOrigin = url.startsWith(window.location.origin) || url.startsWith('/');
        
        if (!isSameOrigin) {
            img.crossOrigin = 'anonymous';
        }
        
        // Set timeout in case image never loads
        const timeout = setTimeout(() => {
            reject(new Error('Image load timeout'));
        }, 10000);
        
        img.onload = () => {
            clearTimeout(timeout);
            resolve(img);
        };
        
        img.onerror = (e) => {
            clearTimeout(timeout);
            console.error('Failed to load image:', url);
            reject(new Error('Failed to load image. Check Network tab for 401/403 errors.'));
        };
        
        // Always use proxy for /uploads/ images to handle authentication
        const uploadsPattern = /\/uploads\/(.+)/;
        const match = url.match(uploadsPattern);
        if (match && match[1]) {
            let path = match[1];
            const proxyUrl = 'image-proxy.php?path=' + path;
            img.src = proxyUrl;
        } else {
            // External image - use CORS
            img.crossOrigin = 'anonymous';
            img.src = url;
        }
        
        img.style.display = 'none';
        document.body.appendChild(img);
    });
}

/**
 * Create image element from File object
 * @param {File} file - Image file
 * @returns {Promise<HTMLImageElement>}
 */
function createImageElement(file) {
    return new Promise((resolve, reject) => {
        const img = new Image();
        const url = URL.createObjectURL(file);
        
        img.onload = () => {
            URL.revokeObjectURL(url);
            resolve(img);
        };
        
        img.onerror = () => {
            URL.revokeObjectURL(url);
            reject(new Error('Failed to load image from file'));
        };
        
        img.src = url;
        img.style.display = 'none';
        document.body.appendChild(img);
    });
}

/**
 * Add AI tagging button to image modal
 */
function addAITaggingButton() {
    // Check if button already exists
    if (document.getElementById('aiTagBtn')) return;
    
    // Find the metadata section in the modal
    const metadataSection = document.querySelector('.modal-metadata');
    if (!metadataSection) return;
    
    // Create AI tag button
    const aiTagBtn = document.createElement('button');
    aiTagBtn.id = 'aiTagBtn';
    aiTagBtn.className = 'btn btn-primary';
    aiTagBtn.innerHTML = '🤖 Generate AI Tags';
    aiTagBtn.style.marginTop = '10px';
    aiTagBtn.style.width = '100%';
    
    aiTagBtn.addEventListener('click', async function() {
        const imageId = document.getElementById('modalImageId')?.value;
        const imageElement = document.querySelector('#imageModal .modal-image img');
        
        if (!imageElement) {
            showTagNotification('No image found in modal', 'error');
            console.error('Image element not found. Modal HTML:', document.getElementById('imageModal')?.innerHTML);
            return;
        }
        
        console.log('=== AI TAGGING STARTED ===');
        console.log('Image element found:', imageElement);
        console.log('Image src:', imageElement.src);
        console.log('Image dimensions:', imageElement.width + 'x' + imageElement.height);
        
        // Disable button and show loading state
        aiTagBtn.disabled = true;
        aiTagBtn.innerHTML = '🔄 Analyzing...';
        
        try {
            // Generate tags - pass the src URL instead of the element
            console.log('Calling generateImageTags with:', imageElement.src);
            const tags = await generateImageTags(imageElement.src);
            
            console.log('Tags returned:', tags);
            
            if (!tags || tags.length === 0) {
                showTagNotification('Could not generate tags. Please try again.', 'error');
                console.error('generateImageTags returned empty array');
                return;
            }
            
            // FIRST: Check if we're in edit mode, if not, enter it
            const editMode = document.getElementById('metadataEditMode');
            const isEditModeVisible = editMode && editMode.style.display !== 'none';
            
            if (!isEditModeVisible) {
                // Click the Edit Info button to show the edit form
                const editBtn = document.getElementById('editMetadataBtn');
                if (editBtn) {
                    console.log('Entering edit mode first...');
                    editBtn.click();
                }
            }
            
            // Wait a moment for the DOM to update
            setTimeout(() => {
                // Get current tags field
                const tagsField = document.getElementById('editTags');
                if (!tagsField) {
                    showTagNotification('Tags field not found', 'error');
                    return;
                }
                
                // Get existing tags
                const existingTags = tagsField.value ? 
                    tagsField.value.split(',').map(t => t.trim()).filter(t => t) : [];
                
                // Merge with new tags (avoid duplicates)
                const newTags = tags.filter(tag => !existingTags.includes(tag));
                const allTags = [...existingTags, ...newTags];
                
                // Update tags field
                tagsField.value = allTags.join(', ');
                
                // Visual feedback
                tagsField.style.transition = 'background-color 0.3s';
                tagsField.style.backgroundColor = '#d4edda';
                setTimeout(() => {
                    tagsField.style.backgroundColor = '';
                }, 1000);
                
                // Show success notification with save reminder
                showTagNotification(
                    `✅ Added ${newTags.length} new tag(s): ${newTags.join(', ')}\n\n📝 Click "Save" button below to save to database!`, 
                    'success'
                );
            }, 100);
            
        } catch (error) {
            console.error('Error:', error);
            showTagNotification('❌ Failed to generate tags: ' + error.message, 'error');
        } finally {
            // Restore button
            aiTagBtn.disabled = false;
            aiTagBtn.innerHTML = '🤖 Generate AI Tags';
        }
    });
    
    // Insert after tags field
    const tagsField = document.getElementById('editTags');
    if (tagsField && tagsField.parentNode) {
        tagsField.parentNode.appendChild(aiTagBtn);
    } else {
        // Fallback: insert after description field
        const descField = metadataSection.querySelector('#modalImageDescription');
        if (descField && descField.parentNode) {
            descField.parentNode.insertBefore(aiTagBtn, descField.nextSibling);
        }
    }
}

/**
 * Show notification for tag operations
 */
function showTagNotification(message, type = 'info') {
    // Remove existing notification
    const existing = document.getElementById('ai-tag-notification');
    if (existing) {
        existing.remove();
    }
    
    // Create notification
    const notification = document.createElement('div');
    notification.id = 'ai-tag-notification';
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 10000;
        max-width: 400px;
        animation: slideInRight 0.3s ease-out;
        ${type === 'success' ? 'background: #d4edda; color: #155724; border-left: 4px solid #28a745;' : ''}
        ${type === 'error' ? 'background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545;' : ''}
        ${type === 'info' ? 'background: #d1ecf1; color: #0c5460; border-left: 4px solid #17a2b8;' : ''}
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remove after 4 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease-in';
        setTimeout(() => notification.remove(), 300);
    }, 4000);
}

// Add CSS animations
if (!document.getElementById('ai-tag-styles')) {
    const style = document.createElement('style');
    style.id = 'ai-tag-styles';
    style.textContent = `
        @keyframes slideInRight {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
}

/**
 * Auto-tag images on upload (optional)
 */
async function autoTagOnUpload(file) {
    try {
        console.log('🤖 Auto-tagging on upload...');
        const tags = await generateImageTags(file);
        return tags.length > 0 ? tags.join(', ') : '';
    } catch (error) {
        console.error('Auto-tag failed:', error);
        return '';
    }
}

// Initialize AI tagging when modal opens
document.addEventListener('DOMContentLoaded', function() {
    // Observe modal for when it opens
    const observer = new MutationObserver(function(mutations) {
        const modal = document.getElementById('imageModal');
        if (modal && modal.style.display === 'block') {
            addAITaggingButton();
        }
    });
    
    const modal = document.getElementById('imageModal');
    if (modal) {
        observer.observe(modal, { 
            attributes: true, 
            attributeFilter: ['style'] 
        });
    }
});

// Export functions for use in other scripts
if (typeof window !== 'undefined') {
    window.AITagging = {
        generateImageTags,
        autoTagOnUpload,
        loadMobileNet,
        showTagNotification
    };
}
