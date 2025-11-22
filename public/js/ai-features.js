/**
 * Comprehensive AI Features Module
 * Combines multiple free AI capabilities:
 * 1. Color Palette Extraction (Vibrant.js)
 * 2. Face Detection (face-api.js)
 * 3. OCR Text Recognition (Tesseract.js)
 * 4. NSFW Content Filter (NSFWJS)
 * 5. Object Detection (COCO-SSD)
 */

// Model loading states
let cocoSsdModel = null;
let faceApiModelsLoaded = false;
let nsfwModel = null;
let isLoadingCoco = false;
let isLoadingFaceApi = false;
let isLoadingNsfw = false;

/**
 * 1. COLOR PALETTE EXTRACTION
 * Extract dominant colors from an image
 */
async function extractColorPalette(imageSource) {
    try {
        console.log('🎨 Extracting color palette...');
        
        let imgUrl;
        if (imageSource instanceof File) {
            imgUrl = URL.createObjectURL(imageSource);
        } else if (typeof imageSource === 'string') {
            imgUrl = imageSource;
        } else {
            imgUrl = imageSource.src;
        }
        
        const vibrant = new Vibrant(imgUrl);
        const palette = await vibrant.getPalette();
        
        const colors = {
            vibrant: palette.Vibrant?.hex || null,
            darkVibrant: palette.DarkVibrant?.hex || null,
            lightVibrant: palette.LightVibrant?.hex || null,
            muted: palette.Muted?.hex || null,
            darkMuted: palette.DarkMuted?.hex || null,
            lightMuted: palette.LightMuted?.hex || null
        };
        
        // Clean up object URL if we created one
        if (imageSource instanceof File) {
            URL.revokeObjectURL(imgUrl);
        }
        
        console.log('✅ Color palette extracted:', colors);
        return colors;
    } catch (error) {
        console.error('❌ Color palette extraction failed:', error);
        return null;
    }
}

/**
 * 2. FACE DETECTION
 * Detect faces and facial landmarks
 */
async function loadFaceApiModels() {
    if (faceApiModelsLoaded) return true;
    if (isLoadingFaceApi) {
        while (isLoadingFaceApi) {
            await new Promise(resolve => setTimeout(resolve, 100));
        }
        return faceApiModelsLoaded;
    }
    
    try {
        isLoadingFaceApi = true;
        console.log('👤 Loading face detection models...');
        
        const MODEL_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1.7.12/model';
        await Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
            faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
            faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL),
            faceapi.nets.faceExpressionNet.loadFromUri(MODEL_URL),
            faceapi.nets.ageGenderNet.loadFromUri(MODEL_URL)
        ]);
        
        faceApiModelsLoaded = true;
        console.log('✅ Face detection models loaded');
        return true;
    } catch (error) {
        console.error('❌ Failed to load face detection models:', error);
        return false;
    } finally {
        isLoadingFaceApi = false;
    }
}

async function detectFaces(imageSource) {
    try {
        const loaded = await loadFaceApiModels();
        if (!loaded) {
            console.warn('Face detection models not available');
            return null;
        }
        
        let imgElement;
        if (imageSource instanceof File) {
            imgElement = await createImageElementFromFile(imageSource);
        } else if (typeof imageSource === 'string') {
            imgElement = await createImageElementFromUrl(imageSource);
        } else {
            imgElement = imageSource;
        }
        
        console.log('👤 Detecting faces...');
        const detections = await faceapi
            .detectAllFaces(imgElement, new faceapi.TinyFaceDetectorOptions())
            .withFaceLandmarks()
            .withFaceExpressions()
            .withAgeAndGender();
        
        const result = {
            faceCount: detections.length,
            faces: detections.map(d => ({
                box: d.detection.box,
                expressions: d.expressions,
                age: Math.round(d.age),
                gender: d.gender,
                genderProbability: d.genderProbability
            }))
        };
        
        console.log(`✅ Detected ${result.faceCount} face(s)`);
        return result;
    } catch (error) {
        console.error('❌ Face detection failed:', error);
        return null;
    }
}

/**
 * 3. OCR TEXT RECOGNITION
 * Extract text from images
 */
async function extractText(imageSource) {
    try {
        console.log('📝 Extracting text from image...');
        
        let imgSource;
        if (imageSource instanceof File) {
            imgSource = imageSource;
        } else if (typeof imageSource === 'string') {
            imgSource = imageSource;
        } else {
            imgSource = imageSource.src;
        }
        
        const result = await Tesseract.recognize(imgSource, 'eng', {
            logger: m => {
                if (m.status === 'recognizing text') {
                    console.log(`OCR Progress: ${Math.round(m.progress * 100)}%`);
                }
            }
        });
        
        const text = result.data.text.trim();
        console.log(`✅ Extracted ${text.length} characters`);
        
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
 * 4. NSFW CONTENT FILTERING
 * Detect inappropriate content
 */
async function loadNsfwModel() {
    if (nsfwModel) return nsfwModel;
    if (isLoadingNsfw) {
        while (isLoadingNsfw) {
            await new Promise(resolve => setTimeout(resolve, 100));
        }
        return nsfwModel;
    }
    
    try {
        isLoadingNsfw = true;
        console.log('🔞 Loading NSFW detection model...');
        nsfwModel = await nsfwjs.load();
        console.log('✅ NSFW model loaded');
        return nsfwModel;
    } catch (error) {
        console.error('❌ Failed to load NSFW model:', error);
        return null;
    } finally {
        isLoadingNsfw = false;
    }
}

async function checkNsfwContent(imageSource) {
    try {
        const model = await loadNsfwModel();
        if (!model) {
            console.warn('NSFW model not available');
            return null;
        }
        
        let imgElement;
        if (imageSource instanceof File) {
            imgElement = await createImageElementFromFile(imageSource);
        } else if (typeof imageSource === 'string') {
            imgElement = await createImageElementFromUrl(imageSource);
        } else {
            imgElement = imageSource;
        }
        
        console.log('🔞 Checking content safety...');
        const predictions = await model.classify(imgElement);
        
        // Categories: Porn, Sexy, Hentai, Neutral, Drawing
        const result = {
            predictions: predictions,
            isNsfw: predictions.some(p => 
                (p.className === 'Porn' || p.className === 'Hentai') && p.probability > 0.5
            ),
            isSexy: predictions.find(p => p.className === 'Sexy')?.probability > 0.6,
            safetyScore: predictions.find(p => p.className === 'Neutral')?.probability || 0
        };
        
        console.log('✅ Content check complete:', result.isNsfw ? '⚠️ NSFW' : '✅ Safe');
        return result;
    } catch (error) {
        console.error('❌ NSFW check failed:', error);
        return null;
    }
}

/**
 * 5. OBJECT DETECTION
 * Detect objects and their locations
 */
async function loadCocoSsd() {
    if (cocoSsdModel) return cocoSsdModel;
    if (isLoadingCoco) {
        while (isLoadingCoco) {
            await new Promise(resolve => setTimeout(resolve, 100));
        }
        return cocoSsdModel;
    }
    
    try {
        isLoadingCoco = true;
        console.log('🎯 Loading object detection model...');
        cocoSsdModel = await cocoSsd.load();
        console.log('✅ Object detection model loaded');
        return cocoSsdModel;
    } catch (error) {
        console.error('❌ Failed to load object detection model:', error);
        return null;
    } finally {
        isLoadingCoco = false;
    }
}

async function detectObjects(imageSource) {
    try {
        const model = await loadCocoSsd();
        if (!model) {
            console.warn('Object detection model not available');
            return null;
        }
        
        let imgElement;
        if (imageSource instanceof File) {
            imgElement = await createImageElementFromFile(imageSource);
        } else if (typeof imageSource === 'string') {
            imgElement = await createImageElementFromUrl(imageSource);
        } else {
            imgElement = imageSource;
        }
        
        console.log('🎯 Detecting objects...');
        const predictions = await model.detect(imgElement);
        
        const result = {
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
function createImageElementFromFile(file) {
    return new Promise((resolve, reject) => {
        const img = new Image();
        img.onload = () => {
            URL.revokeObjectURL(img.src);
            resolve(img);
        };
        img.onerror = reject;
        img.src = URL.createObjectURL(file);
    });
}

function createImageElementFromUrl(url) {
    return new Promise((resolve, reject) => {
        const img = new Image();
        img.crossOrigin = 'anonymous';
        img.onload = () => resolve(img);
        img.onerror = reject;
        img.src = url;
    });
}

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
    const [tags, colors, faces, text, nsfw, objects] = await Promise.all([
        window.AITagging?.generateImageTags?.(imageSource).catch(e => {
            console.warn('Tagging skipped:', e);
            return null;
        }),
        extractColorPalette(imageSource).catch(e => {
            console.warn('Color extraction skipped:', e);
            return null;
        }),
        detectFaces(imageSource).catch(e => {
            console.warn('Face detection skipped:', e);
            return null;
        }),
        extractText(imageSource).catch(e => {
            console.warn('OCR skipped:', e);
            return null;
        }),
        checkNsfwContent(imageSource).catch(e => {
            console.warn('NSFW check skipped:', e);
            return null;
        }),
        detectObjects(imageSource).catch(e => {
            console.warn('Object detection skipped:', e);
            return null;
        })
    ]);
    
    results.analyses = { tags, colors, faces, text, nsfw, objects };
    
    console.log('✅ Comprehensive analysis complete:', results);
    return results;
}

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
    buttonGrid.style.cssText = 'display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px;';
    
    const buttons = [
        { id: 'ai-tags', icon: '🏷️', label: 'Auto Tags', action: generateTags },
        { id: 'ai-colors', icon: '🎨', label: 'Colors', action: showColors },
        { id: 'ai-faces', icon: '👤', label: 'Faces', action: showFaces },
        { id: 'ai-text', icon: '📝', label: 'OCR', action: showText },
        { id: 'ai-nsfw', icon: '🔞', label: 'Safety', action: showSafety },
        { id: 'ai-objects', icon: '🎯', label: 'Objects', action: showObjects }
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
async function generateTags() {
    showAILoading('Generating tags...');
    const img = getCurrentModalImage();
    if (!img) {
        showAIResult('❌ No image found');
        return;
    }
    
    console.log('Starting tag generation for:', img.src);
    
    try {
        const tags = await window.AITagging?.generateImageTags?.(img);
        console.log('Tags returned:', tags);
        
        if (tags && tags.length > 0) {
            // Add to the tags field (comma-separated)
            const tagsField = document.getElementById('editTags');
            if (tagsField) {
                const existingTags = tagsField.value ? tagsField.value.split(',').map(t => t.trim()) : [];
                const newTags = tags.filter(tag => !existingTags.includes(tag));
                const allTags = [...existingTags, ...newTags].filter(t => t);
                tagsField.value = allTags.join(', ');
            }
            showAIResult(`✅ Added ${tags.length} tags: ${tags.join(', ')}`);
        } else {
            showAIResult('❌ Could not generate tags - model returned empty');
        }
    } catch (error) {
        console.error('Error in generateTags:', error);
        showAIResult('❌ Error: ' + error.message);
    }
}

async function showColors() {
    showAILoading('Extracting colors...');
    const img = getCurrentModalImage();
    if (!img) return;
    
    const colors = await extractColorPalette(img.src);
    if (colors) {
        let html = '<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px;">';
        Object.entries(colors).forEach(([name, hex]) => {
            if (hex) {
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
}

async function showFaces() {
    showAILoading('Detecting faces...');
    const img = getCurrentModalImage();
    if (!img) return;
    
    const result = await detectFaces(img.src);
    if (result && result.faceCount > 0) {
        let html = `<strong>Found ${result.faceCount} face(s)</strong><br><br>`;
        result.faces.forEach((face, i) => {
            const topExpression = Object.entries(face.expressions)
                .sort((a, b) => b[1] - a[1])[0];
            html += `
                <div style="margin-bottom: 8px; padding: 8px; background: white; border-radius: 4px;">
                    <strong>Face ${i + 1}</strong><br>
                    Age: ~${face.age} years<br>
                    Gender: ${face.gender} (${Math.round(face.genderProbability * 100)}%)<br>
                    Expression: ${topExpression[0]} (${Math.round(topExpression[1] * 100)}%)
                </div>
            `;
        });
        showAIResult(html, true);
    } else {
        showAIResult('No faces detected');
    }
}

async function showText() {
    showAILoading('Extracting text (this may take 10-20 seconds)...');
    const img = getCurrentModalImage();
    if (!img) return;
    
    const result = await extractText(img.src);
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

async function showSafety() {
    showAILoading('Checking content safety...');
    const img = getCurrentModalImage();
    if (!img) return;
    
    const result = await checkNsfwContent(img.src);
    if (result) {
        let html = `<strong>Safety Check Results</strong><br><br>`;
        html += `Overall: ${result.isNsfw ? '⚠️ NSFW Content Detected' : '✅ Safe Content'}<br>`;
        html += `Safety Score: ${Math.round(result.safetyScore * 100)}%<br><br>`;
        html += '<strong>Breakdown:</strong><br>';
        result.predictions.forEach(p => {
            const bar = Math.round(p.probability * 100);
            html += `
                <div style="margin: 4px 0;">
                    ${p.className}: ${bar}%
                    <div style="background: #ddd; height: 8px; border-radius: 4px; overflow: hidden;">
                        <div style="background: ${p.probability > 0.5 ? '#e74c3c' : '#2ecc71'}; height: 100%; width: ${bar}%;"></div>
                    </div>
                </div>
            `;
        });
        showAIResult(html, true);
    } else {
        showAIResult('❌ Could not check content');
    }
}

async function showObjects() {
    showAILoading('Detecting objects...');
    const img = getCurrentModalImage();
    if (!img) return;
    
    const result = await detectObjects(img.src);
    if (result && result.objectCount > 0) {
        let html = `<strong>Found ${result.objectCount} object(s)</strong><br><br>`;
        result.objects.forEach((obj, i) => {
            html += `
                <div style="margin: 4px 0; padding: 4px 8px; background: white; border-radius: 4px;">
                    ${i + 1}. ${obj.class} (${obj.confidence}% confidence)
                </div>
            `;
        });
        showAIResult(html, true);
    } else {
        showAIResult('No objects detected');
    }
}

// Helper functions
function getCurrentModalImage() {
    const modal = document.getElementById('imageModal');
    if (!modal) return null;
    return modal.querySelector('.modal-image img');
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
    detectFaces,
    extractText,
    checkNsfwContent,
    detectObjects,
    analyzeImageComprehensive
};
