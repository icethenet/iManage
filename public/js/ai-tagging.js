console.log('[AI DEBUG] ai-tagging.js loaded');
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
    if (mobilenetModel) return mobilenetModel;
    if (isModelLoading) {
        // Wait for existing load to complete
        while (isModelLoading) {
            await new Promise(resolve => setTimeout(resolve, 100));
        }
        return mobilenetModel;
    }
    
    try {
        isModelLoading = true;
        console.log('🤖 Loading AI model...');
        mobilenetModel = await mobilenet.load();
        console.log('✅ AI model loaded successfully');
        return mobilenetModel;
    } catch (error) {
        console.error('❌ Failed to load AI model:', error);
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
        console.log('generateImageTags called with:', imageSource);
        
        const model = await loadMobileNet();
        if (!model) {
            console.warn('AI model not available');
            return [];
        }
        
        console.log('Model loaded successfully');
        
        let imgElement;
        
        // Handle File object (create temporary image element)
        if (imageSource instanceof File) {
            console.log('Source is File object');
            imgElement = await createImageElement(imageSource);
        } else if (typeof imageSource === 'string') {
            console.log('Source is URL string:', imageSource);
            // If it's a URL string, create an image element
            imgElement = await createImageElementFromUrl(imageSource);
        } else {
            console.log('Source is image element, cloning with CORS');
            // Already an image element - clone it with CORS
            imgElement = await createImageElementFromUrl(imageSource.src);
        }
        
        console.log('Image element created:', imgElement.src);
        console.log('🔍 Analyzing image with AI...');
        
        const predictions = await model.classify(imgElement);
        console.log('Raw predictions:', predictions);
        
        // Extract top 3 predictions with confidence > 10%
        const tags = predictions
            .filter(pred => pred.probability > 0.1)
            .slice(0, 3)
            .map(pred => {
                // Clean up tag name (remove technical prefixes, simplify)
                return pred.className
                    .split(',')[0] // Take first synonym
                    .trim()
                    .toLowerCase()
                    .replace(/_/g, ' '); // Replace underscores with spaces
            });
        
        console.log('✅ Generated tags:', tags);
        
        // Clean up temporary element
        if (imgElement.parentNode) {
            imgElement.remove();
        }
        
        return tags;
    } catch (error) {
        console.error('❌ Error generating tags:', error);
        console.error('Error stack:', error.stack);
        return [];
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
        img.crossOrigin = 'anonymous';
        
        img.onload = () => {
            resolve(img);
        };
        
        img.onerror = () => {
            reject(new Error('Failed to load image from URL'));
        };
        
        // Always use proxy for /uploads/ images, handle absolute/relative URLs and spaces
        const uploadsPattern = /\/uploads\/(.+)/;
        const match = url.match(uploadsPattern);
        if (match && match[1]) {
            let path = match[1];
            // Decode URI components, then re-encode for proxy
            path = decodeURIComponent(path);
            img.src = 'image-proxy.php?path=' + encodeURIComponent(path);
        } else {
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
            reject(new Error('Failed to load image'));
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
        const imageElement = document.querySelector('#imageModal img');
        
        if (!imageElement) {
            alert('No image found');
            return;
        }
        
        // Disable button and show loading state
        aiTagBtn.disabled = true;
        aiTagBtn.innerHTML = '🔄 Analyzing...';
        
        try {
            // Generate tags
            const tags = await generateImageTags(imageElement);
            
            if (tags.length === 0) {
                alert('Could not generate tags. Please try again.');
                return;
            }
            
            // Get current description
            const descriptionField = document.getElementById('editDescription');
            const currentDesc = descriptionField.value.trim();
            
            // Format tags
            const tagString = tags.map(tag => `#${tag}`).join(' ');
            
            // Append or set tags
            if (currentDesc) {
                descriptionField.value = currentDesc + '\n\nAI Tags: ' + tagString;
            } else {
                descriptionField.value = 'AI Tags: ' + tagString;
            }
            
            // Show success message
            alert(`Generated ${tags.length} AI tags:\n${tags.join(', ')}`);
            
        } catch (error) {
            console.error('Error:', error);
            alert('Failed to generate tags. Please try again.');
        } finally {
            // Restore button
            aiTagBtn.disabled = false;
            aiTagBtn.innerHTML = '🤖 Generate AI Tags';
        }
    });
    
    // Insert after description field
    const descField = metadataSection.querySelector('#modalImageDescription');
    if (descField && descField.parentNode) {
        descField.parentNode.insertBefore(aiTagBtn, descField.nextSibling);
    }
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
        loadMobileNet
    };
}
