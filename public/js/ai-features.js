/**
 * AI Features Module
 * Provides AI capabilities for image analysis:
 * 1. Color Palette Extraction (Vibrant.js)
 * 2. OCR Text Recognition (Tesseract.js)
 */

/**
 * 1. COLOR PALETTE EXTRACTION
 * Extract dominant colors from an image
 */
async function extractColorPalette(imageSource) {
    try {
        
        // ALWAYS create a fresh image element through the proxy to avoid CORS taint
        let imgElement;
        
        if (imageSource instanceof HTMLImageElement) {
            // Extract the src and create a fresh element through proxy
            console.log('Creating fresh element from image src to avoid CORS taint');
            imgElement = await createImageElementForAnalysis(imageSource.src);
        } else if (imageSource instanceof File) {
            console.log('Creating element from File');
            imgElement = await createImageElementForAnalysis(imageSource);
        } else if (typeof imageSource === 'string') {
            console.log('Creating element from URL');
            imgElement = await createImageElementForAnalysis(imageSource);
        } else {
            throw new Error('Unsupported image source type');
        }
        
        
        // Check if Vibrant is available
        if (typeof Vibrant === 'undefined') {
            throw new Error('Vibrant.js library not loaded');
        }
        
        console.log('Creating Vibrant instance...');
        const vibrant = new Vibrant(imgElement);
        console.log('Calling getPalette...');
        const palette = await vibrant.getPalette();
        console.log('Palette received:', palette);
        
        const colors = {
            vibrant: palette.Vibrant?.hex || null,
            darkVibrant: palette.DarkVibrant?.hex || null,
            lightVibrant: palette.LightVibrant?.hex || null,
            muted: palette.Muted?.hex || null,
            darkMuted: palette.DarkMuted?.hex || null,
            lightMuted: palette.LightMuted?.hex || null
        };
        
        return colors;
    } catch (error) {
        console.error('❌ Color palette extraction failed:', error);
        console.error('Error details:', error.message);
        console.error('Stack:', error.stack);
        return null;
    }
}

/**
 * Helper to create image element for analysis
 * Uses proxy for same-origin /uploads/ images
 */
async function createImageElementForAnalysis(source) {
    if (source instanceof File) {
        // File object - use blob URL
        return new Promise((resolve, reject) => {
            const img = new Image();
            const url = URL.createObjectURL(source);
            img.onload = () => {
                URL.revokeObjectURL(url);
                resolve(img);
            };
            img.onerror = () => {
                URL.revokeObjectURL(url);
                reject(new Error('Failed to load image from file'));
            };
            img.src = url;
        });
    } else if (typeof source === 'string') {
        // URL string - check if it needs proxy
        return new Promise((resolve, reject) => {
            const img = new Image();
            
            // Check if it's an /uploads/ image that needs proxy
            const uploadsPattern = /\/uploads\/(.+)/;
            const match = source.match(uploadsPattern);
            
            if (match && match[1]) {
                // Use proxy for authentication
                const path = match[1];
                img.src = 'image-proxy.php?path=' + path;
                console.log('Using proxy for color extraction:', img.src);
            } else {
                // External image - use CORS
                img.crossOrigin = 'anonymous';
                img.src = source;
            }
            
            img.onload = () => resolve(img);
            img.onerror = () => reject(new Error('Failed to load image from URL'));
        });
    } else {
        throw new Error('Invalid source type for image analysis');
    }
}

/**
 * 2. OCR TEXT RECOGNITION
 * Extract text from images
 */
async function extractText(imageSource) {
    try {
        
        // Create fresh image element through proxy for consistent CORS handling
        let imgElement;
        if (imageSource instanceof HTMLImageElement) {
            console.log('OCR: Creating fresh element to avoid CORS');
            imgElement = await createImageElementForAnalysis(imageSource.src);
        } else if (imageSource instanceof File) {
            imgElement = await createImageElementForAnalysis(imageSource);
        } else if (typeof imageSource === 'string') {
            imgElement = await createImageElementForAnalysis(imageSource);
        } else {
            throw new Error('Unsupported image source type');
        }
        
        // Tesseract can work with image elements directly
        const result = await Tesseract.recognize(imgElement, 'eng', {
            logger: m => {
                if (m.status === 'recognizing text') {
                    console.log(`OCR Progress: ${Math.round(m.progress * 100)}%`);
                }
            }
        });
        
        const text = result.data.text.trim();
        
        return {
            text: text,
            confidence: result.data.confidence,
            words: result.data.words.length
        };
    } catch (error) {
        console.error('❌ Text extraction failed:', error);
        return null;
    }
}

/**
 * 3. COMPREHENSIVE IMAGE ANALYSIS
            objectCount: predictions.length,
            objects: predictions.map(p => ({
                class: p.class,
                confidence: Math.round(p.score * 100),
                bbox: p.bbox
            }))
        };
        
        console.log(`✅ Detected ${result.objectCount} object(s)`);
        return result;
    } catch (error) {
        console.error('❌ Object detection failed:', error);
        return null;
    }
}

/**
 * HELPER FUNCTIONS
 */
// Use the implementations from ai-tagging.js
// Don't duplicate image loading logic - it causes bugs!

// REMOVED - Use the proper implementation from ai-tagging.js instead
// This was causing CORS errors and not using the proxy!

/**
 * COMPREHENSIVE ANALYSIS
 * Run all AI analyses at once
 */
async function analyzeImageComprehensive(imageSource) {
    console.log('🚀 Starting comprehensive AI analysis...');
    
    const results = {
        timestamp: new Date().toISOString(),
        analyses: {}
    };
    
    // Run all analyses in parallel
    const [tags, colors, text] = await Promise.all([
        window.AITagging?.generateImageTags?.(imageSource).catch(e => {
            console.warn('Tagging skipped:', e);
            return null;
        }),
        extractColorPalette(imageSource).catch(e => {
            console.warn('Color extraction skipped:', e);
            return null;
        }),
        extractText(imageSource).catch(e => {
            console.warn('OCR skipped:', e);
            return null;
        })
    ]);
    
    // Store results
    if (tags) results.analyses.tags = tags;
    if (colors) results.analyses.colors = colors;
    if (text) results.analyses.text = text;
    
    return results;
}

// === UI FUNCTIONS ===
// These handle the button clicks and display results

/**
 * UI INTEGRATION
 * Add AI feature buttons to image modal
 */
function addAIFeatureButtons() {
    // Wait for modal to appear
    const observer = new MutationObserver((mutations) => {
        const modal = document.getElementById('imageModal');
        if (modal && modal.style.display !== 'none') {
            const modalTools = modal.querySelector('.modal-tools');
            if (modalTools && !document.getElementById('ai-features-container')) {
                // Insert AI buttons after modal-tools
                createAIButtonsUI(modalTools);
            }
        }
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['style']
    });
}

function createAIButtonsUI(modalToolsDiv) {
    const aiContainer = document.createElement('div');
    aiContainer.id = 'ai-features-container';
    aiContainer.style.cssText = `
        margin-top: 15px;
        padding: 15px;
        background: #f9f9f9;
        border-radius: 8px;
        border: 1px solid #ddd;
    `;
    
    const title = document.createElement('h3');
    title.textContent = '🤖 AI Features';
    title.style.cssText = 'margin: 0 0 10px 0; font-size: 16px; color: #333;';
    aiContainer.appendChild(title);
    
    const buttonGrid = document.createElement('div');
    buttonGrid.style.cssText = 'display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px;';
    
    const buttons = [
        { id: 'ai-colors', icon: '🎨', label: 'Colors', action: showColors },
        { id: 'ai-text', icon: '📝', label: 'OCR', action: showText }
    ];
    
    buttons.forEach(btn => {
        const button = document.createElement('button');
        button.id = btn.id;
        button.className = 'ai-feature-btn';
        button.innerHTML = `${btn.icon} ${btn.label}`;
        button.style.cssText = `
            padding: 10px 12px;
            font-size: 13px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        `;
        button.onmouseover = () => {
            button.style.transform = 'translateY(-2px)';
            button.style.boxShadow = '0 4px 8px rgba(0,0,0,0.2)';
        };
        button.onmouseout = () => {
            button.style.transform = 'translateY(0)';
            button.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';
        };
        button.onclick = () => btn.action();
        buttonGrid.appendChild(button);
    });
    
    aiContainer.appendChild(buttonGrid);
    
    // Add results area
    const resultsDiv = document.createElement('div');
    resultsDiv.id = 'ai-results';
    resultsDiv.style.cssText = `
        margin-top: 10px;
        padding: 10px;
        background: white;
        border-radius: 4px;
        font-size: 12px;
        max-height: 200px;
        overflow-y: auto;
        display: none;
        border: 1px solid #ddd;
    `;
    aiContainer.appendChild(resultsDiv);
    
    // Insert after modal-tools section
    modalToolsDiv.parentNode.insertBefore(aiContainer, modalToolsDiv.nextSibling);
}

// Button action handlers
async function showColors() {
    showAILoading('Extracting colors...');
    const img = getCurrentModalImage();
    if (!img) {
        showAIResult('❌ No image found');
        return;
    }
    
    try {
        const colors = await extractColorPalette(img);
        
        if (colors) {
            let html = '<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px;">';
            let colorCount = 0;
            Object.entries(colors).forEach(([name, hex]) => {
                if (hex) {
                    colorCount++;
                    html += `
                        <div style="text-align: center;">
                            <div style="width: 100%; height: 40px; background: ${hex}; border-radius: 4px; margin-bottom: 4px;"></div>
                            <div style="font-size: 10px;">${name.replace(/([A-Z])/g, ' $1').trim()}</div>
                            <div style="font-size: 9px; color: #666;">${hex}</div>
                        </div>
                    `;
                }
            });
            html += '</div>';
            showAIResult(html, true);
        } else {
            showAIResult('❌ Could not extract colors');
        }
    } catch (error) {
        console.error('Error extracting colors:', error.message);
        showAIResult('❌ Error: ' + error.message);
    }
}

async function showText() {
    showAILoading('Extracting text (this may take 10-20 seconds)...');
    const img = getCurrentModalImage();
    if (!img) return;
    
    const result = await extractText(img);
    if (result && result.text) {
        const desc = document.getElementById('editDescription');
        if (desc && result.text.length < 500) {
            desc.value += (desc.value ? '\n\n' : '') + 'Extracted Text:\n' + result.text;
        }
        showAIResult(`✅ Extracted ${result.words} words (${Math.round(result.confidence)}% confidence):<br><br><pre style="white-space: pre-wrap; max-height: 150px; overflow-y: auto;">${result.text}</pre>`, true);
    } else {
        showAIResult('No text detected');
    }
}

// Helper functions
function getCurrentModalImage() {
    const modal = document.getElementById('imageModal');
    if (!modal) {
        console.error('Modal not found');
        return null;
    }
    const img = modal.querySelector('.modal-image img');
    if (!img) {
        console.error('Image not found in modal. Modal HTML:', modal.innerHTML.substring(0, 200));
        return null;
    }
    console.log('Found modal image:', img.src);
    return img;
}

function showAILoading(message) {
    const resultsDiv = document.getElementById('ai-results');
    if (resultsDiv) {
        resultsDiv.style.display = 'block';
        resultsDiv.innerHTML = `<div style="text-align: center; padding: 10px;">⏳ ${message}</div>`;
    }
}

function showAIResult(content, isHtml = false) {
    const resultsDiv = document.getElementById('ai-results');
    if (resultsDiv) {
        resultsDiv.style.display = 'block';
        if (isHtml) {
            resultsDiv.innerHTML = content;
        } else {
            resultsDiv.textContent = content;
        }
    }
}

// Initialize on page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', addAIFeatureButtons);
} else {
    addAIFeatureButtons();
}

// Export for use in other scripts
window.AIFeatures = {
    extractColorPalette,
    extractText,
    analyzeImageComprehensive
};
