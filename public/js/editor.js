// Assuming API_BASE is defined in app.js or globally, e.g.:
// const API_BASE = './api.php';

document.addEventListener('DOMContentLoaded', () => {
    
    // Get common elements for modal operations
    const modalImage = document.getElementById('modalImage');
    const modalStatusMessage = document.getElementById('modal-status-message');
    const modalImageSpinner = document.getElementById('modalImageSpinner');

    // Helper function to show/hide spinner and status
    function setModalLoadingState(isLoading, message = '', isError = false) {
        if (modalImageSpinner) {
            modalImageSpinner.style.display = isLoading ? 'flex' : 'none';
        }
        if (modalStatusMessage) {
            modalStatusMessage.textContent = message;
            modalStatusMessage.style.color = isError ? 'red' : (isLoading ? 'black' : 'green');
        }
        // Disable/enable all manipulation buttons
        document.querySelectorAll('.modal-tools button, .modal-actions button').forEach(btn => {
            if (btn.id !== 'revert-image-btn') { // Revert button is handled by its own listener
                btn.disabled = isLoading;
                // Ensure proper visual state
                if (!isLoading) {
                    btn.style.pointerEvents = '';
                    btn.style.opacity = '';
                } else {
                    btn.style.pointerEvents = 'none';
                    btn.style.opacity = '0.6';
                }
            }
        });
    }

    /**
     * Handles image manipulation requests.
     * @param {string} operation The type of manipulation (e.g., 'resize', 'rotate').
     * @param {object} params Additional parameters for the operation.
     */
    async function manipulateImage(operation, params = {}) {
        const imageId = modalImage.dataset.imageId; // Get image ID from the currently displayed image
        if (!imageId) {
            setModalLoadingState(false, 'Error: No image ID found for manipulation.', true);
            return;
        }

        setModalLoadingState(true, `Applying ${operation}...`);

        try {
            const response = await fetch(`${API_BASE}?action=manipulate&id=${imageId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ operation, ...params })
            });

            const result = await response.json();

            if (response.ok && result.success) {
                setModalLoadingState(false, 'Manipulation successful!');
                // Update the image source to force a reload and show the new image
                modalImage.src = `${modalImage.src.split('?')[0]}?t=${new Date().getTime()}`;
                // Reset CSS filters after successful server-side manipulation
                modalImage.style.filter = 'none';
                // Reload gallery to show updated thumbnail
                if (typeof loadImages === 'function') {
                    loadImages();
                }
                // Clear success message after 2 seconds
                setTimeout(() => {
                    if (modalStatusMessage) modalStatusMessage.textContent = '';
                }, 2000);
            } else {
                setModalLoadingState(false, `Error: ${result.error || 'An unknown error occurred.'}`, true);
            }
        } catch (error) {
            console.error('Manipulation failed:', error);
            setModalLoadingState(false, 'A network error occurred. Please try again.', true);
        }
    }

    /**
     * Applies CSS filters for live preview of brightness and contrast.
     */
    function updateImagePreviewFilters() {
        const brightness = document.getElementById('brightnessSlider').value;
        const contrast = document.getElementById('contrastSlider').value;

        // CSS filter values are different from the GD library values.
        // Brightness: 0-200% (default 100). Contrast: 0-200% (default 100).
        const brightnessPercent = 100 + parseInt(brightness); // Map -100 to 100 -> 0 to 200
        const contrastPercent = 100 + parseInt(contrast);     // Map -100 to 100 -> 0 to 200

        modalImage.style.filter = `brightness(${brightnessPercent}%) contrast(${contrastPercent}%)`;
    }

    /**
     * Updates the overlay opacity display value
     */
    function updateOverlayOpacityDisplay() {
        const opacity = document.getElementById('overlayOpacitySlider').value;
        document.getElementById('overlayOpacityValue').textContent = opacity + '%';
    }

    // --- Attach Event Listeners for Manipulation Buttons ---

    document.getElementById('resize-btn')?.addEventListener('click', () => {
        const width = document.getElementById('resizeWidth').value;
        const height = document.getElementById('resizeHeight').value;
        manipulateImage('resize', { width: parseInt(width), height: parseInt(height) });
    });

    // --- Crop Tool State ---
    let currentCropTool = null;

    document.getElementById('crop-interactive-btn')?.addEventListener('click', () => {
        const imageId = modalImage.dataset.imageId;
        if (!imageId) {
            setModalLoadingState(false, 'Error: No image ID found for cropping.', true);
            return;
        }

        // Initialize crop tool
        currentCropTool = new CanvasCropTool(modalImage, 'cropCanvasContainer');
        currentCropTool.enter();

        // Show crop controls, hide original crop button
        document.getElementById('crop-interactive-btn').style.display = 'none';
        document.getElementById('crop-cancel-btn').style.display = 'inline-block';
        document.getElementById('crop-apply-btn').style.display = 'inline-block';
    });

    document.getElementById('crop-cancel-btn')?.addEventListener('click', () => {
        if (currentCropTool) {
            currentCropTool.exit();
            currentCropTool = null;
        }
        document.getElementById('crop-interactive-btn').style.display = 'inline-block';
        document.getElementById('crop-cancel-btn').style.display = 'none';
        document.getElementById('crop-apply-btn').style.display = 'none';
    });

    document.getElementById('crop-apply-btn')?.addEventListener('click', () => {
        if (!currentCropTool) return;
        
        const selection = currentCropTool.getSelection();
        currentCropTool.exit();
        currentCropTool = null;

        document.getElementById('crop-interactive-btn').style.display = 'inline-block';
        document.getElementById('crop-cancel-btn').style.display = 'none';
        document.getElementById('crop-apply-btn').style.display = 'none';

        // Send crop request with x, y, width, height
        manipulateImage('crop', { 
            x: selection.x, 
            y: selection.y, 
            width: selection.width, 
            height: selection.height 
        });
    });

    document.getElementById('rotate-btn')?.addEventListener('click', () => manipulateImage('rotate', { degrees: 90 }));
    document.getElementById('grayscale-btn')?.addEventListener('click', () => manipulateImage('grayscale'));
    document.getElementById('flip-h-btn')?.addEventListener('click', () => manipulateImage('flip_horizontal'));
    document.getElementById('flip-v-btn')?.addEventListener('click', () => manipulateImage('flip_vertical'));
    document.getElementById('sharpen-btn')?.addEventListener('click', () => manipulateImage('sharpen'));
    document.getElementById('sepia-btn')?.addEventListener('click', () => manipulateImage('sepia', { intensity: 80 }));
    document.getElementById('vignette-btn')?.addEventListener('click', () => manipulateImage('vignette', { strength: 50 }));

    // Blur slider
    const blurSlider = document.getElementById('blurSlider');
    const blurValue = document.getElementById('blurValue');
    if (blurSlider && blurValue) {
        blurSlider.addEventListener('input', (e) => {
            blurValue.textContent = e.target.value;
        });
    }
    document.getElementById('apply-blur-btn')?.addEventListener('click', () => {
        const radius = parseInt(document.getElementById('blurSlider')?.value || '2');
        manipulateImage('blur', { radius });
    });

    document.getElementById('revert-image-btn')?.addEventListener('click', async () => {
        const imageId = modalImage.dataset.imageId;
        if (!imageId) {
            setModalLoadingState(false, 'Error: No image ID found for revert.', true);
            return;
        }

        if (!confirm('Are you sure you want to revert this image to its original state? All changes will be lost.')) {
            return;
        }

        setModalLoadingState(true, 'Reverting image...');

        try {
            const response = await fetch(`${API_BASE}?action=revert&id=${imageId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();

            if (response.ok && result.success) {
                setModalLoadingState(false, 'Image reverted successfully!');
                // Update the image source to force a reload and show the reverted image
                modalImage.src = `${modalImage.src.split('?')[0]}?t=${new Date().getTime()}`;
                // Reset CSS filters
                modalImage.style.filter = 'none';
                // Reload gallery to show reverted thumbnail
                if (typeof loadImages === 'function') {
                    loadImages();
                }
            } else {
                setModalLoadingState(false, `Error: ${result.error || 'An unknown error occurred.'}`, true);
            }
        } catch (error) {
            console.error('Revert failed:', error);
            setModalLoadingState(false, 'A network error occurred. Please try again.', true);
        }
    });

    document.getElementById('apply-brightness-btn')?.addEventListener('click', () => {
        const level = document.getElementById('brightnessSlider').value;
        // Reset slider for next use after applying
        document.getElementById('brightnessSlider').value = 0;
        manipulateImage('brightness', { level: parseInt(level) });
    });

    document.getElementById('apply-contrast-btn')?.addEventListener('click', () => {
        const level = document.getElementById('contrastSlider').value;
        // Reset slider for next use after applying
        document.getElementById('contrastSlider').value = 0;
        manipulateImage('contrast', { level: parseInt(level) });
    });

    // --- Attach Event Listeners for Live Previews ---
    document.getElementById('brightnessSlider')?.addEventListener('input', updateImagePreviewFilters);
    document.getElementById('contrastSlider')?.addEventListener('input', updateImagePreviewFilters);
    document.getElementById('overlayOpacitySlider')?.addEventListener('input', updateOverlayOpacityDisplay);

    document.getElementById('apply-overlay-btn')?.addEventListener('click', () => {
        const color = document.getElementById('overlayColorPicker').value;
        const opacity = document.getElementById('overlayOpacitySlider').value;
        
        // Convert hex color to RGB
        const r = parseInt(color.substr(1, 2), 16);
        const g = parseInt(color.substr(3, 2), 16);
        const b = parseInt(color.substr(5, 2), 16);
        
        manipulateImage('color_overlay', { 
            red: r, 
            green: g, 
            blue: b, 
            opacity: parseInt(opacity)
        });
    });

    // --- Attach Event Listeners for Download and Delete Buttons ---

    document.getElementById('download-image-btn')?.addEventListener('click', () => {
        const imageId = modalImage.dataset.imageId;
        if (imageId) {
            window.location.href = `${API_BASE}?action=download&id=${imageId}`;
        } else {
            setModalLoadingState(false, 'Error: No image ID found for download.', true);
        }
    });

    document.getElementById('delete-image-btn')?.addEventListener('click', async () => {
        const imageId = modalImage.dataset.imageId;
        if (!imageId) {
            setModalLoadingState(false, 'Error: No image ID found for deletion.', true);
            return;
        }

        if (!confirm('Are you sure you want to delete this image? This action cannot be undone.')) {
            return;
        }

        setModalLoadingState(true, 'Deleting image...');

        try {
            const response = await fetch(`${API_BASE}?action=delete&id=${imageId}`, {
                method: 'DELETE'
            });
            const result = await response.json();

            if (response.ok && result.success) {
                setModalLoadingState(false, 'Image deleted successfully!');
                // Close the modal and refresh the gallery after deletion
                const imageModal = document.getElementById('imageModal');
                if (imageModal) imageModal.style.display = 'none';
                // Assuming a global function to refresh gallery exists
                if (typeof loadImages === 'function') {
                    loadImages(); 
                } else {
                    window.location.reload(); // Fallback to full page reload
                }
            } else {
                setModalLoadingState(false, `Error: ${result.error || 'An unknown error occurred.'}`, true);
            }
        } catch (error) {
            console.error('Deletion failed:', error);
            setModalLoadingState(false, 'A network error occurred. Please try again.', true);
        }
    });
});