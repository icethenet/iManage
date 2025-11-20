/**
 * Simple Canvas-Based Crop Tool
 * Allows users to select a rectangular area on an image and crop to that selection.
 * 
 * Usage:
 *   const cropTool = new CanvasCropTool(imageElement, canvasContainerId);
 *   cropTool.enter();  // Start crop mode
 *   const selection = cropTool.getSelection();  // Get {x, y, width, height}
 */

class CanvasCropTool {
    constructor(imageElement, canvasContainerId) {
        this.imageElement = imageElement;
        this.container = document.getElementById(canvasContainerId);
        
        this.canvas = document.createElement('canvas');
        this.ctx = this.canvas.getContext('2d');  // Enable alpha channel for transparency
        
        // Use the provided image element directly (already loaded in the modal)
        this.image = imageElement;
        
        // Selection state
        this.selection = null;
        this.isDragging = false;
        this.resizeHandle = null;
        
        // Track mouse position
        this.lastX = 0;
        this.lastY = 0;
        
        // UI constants
        this.HANDLE_SIZE = 10;
        this.SELECTION_COLOR = '#4CAF50';
        this.OVERLAY_ALPHA = 0.5;
        
        // Initialize immediately since the image is already loaded
        setTimeout(() => this.initCanvas(), 0);
    }
    
    initCanvas() {
        // Use natural dimensions from the actual image
        const naturalWidth = this.image.naturalWidth || this.image.width;
        const naturalHeight = this.image.naturalHeight || this.image.height;
        
        if (naturalWidth === 0 || naturalHeight === 0) {
            console.error('Image has no dimensions');
            return;
        }
        
        // Size canvas to match image natural dimensions
        this.canvas.width = naturalWidth;
        this.canvas.height = naturalHeight;
        
        // Get the image's computed style for exact sizing
        const imgStyle = window.getComputedStyle(this.image);
        const imgWidth = parseInt(imgStyle.width);
        const imgHeight = parseInt(imgStyle.height);
        
        // Match the displayed image size exactly
        this.canvas.style.width = imgWidth + 'px';
        this.canvas.style.height = imgHeight + 'px';
        this.canvas.style.cursor = 'crosshair';
        this.canvas.style.display = 'block';
        this.canvas.style.position = 'absolute';
        this.canvas.style.top = '0';
        this.canvas.style.left = '0';
        this.canvas.style.pointerEvents = 'auto';
        
        // Initialize selection to 60% of image centered
        const padding = naturalWidth * 0.2;
        this.selection = {
            x: padding,
            y: padding,
            width: naturalWidth - (padding * 2),
            height: naturalHeight - (padding * 2)
        };
        
        // Setup event listeners
        this.setupListeners();
        
        // Initial draw
        this.draw();
    }
    
    setupListeners() {
        this.canvas.addEventListener('mousedown', (e) => this.onMouseDown(e));
        this.canvas.addEventListener('mousemove', (e) => this.onMouseMove(e));
        this.canvas.addEventListener('mouseup', (e) => this.onMouseUp(e));
        this.canvas.addEventListener('mouseleave', (e) => this.onMouseUp(e));
        
        // Touch support
        this.canvas.addEventListener('touchstart', (e) => this.onTouchStart(e));
        this.canvas.addEventListener('touchmove', (e) => this.onTouchMove(e));
        this.canvas.addEventListener('touchend', (e) => this.onTouchEnd(e));
    }
    
    getScaleRatio() {
        // Calculate scale based on displayed canvas size vs natural size
        const rect = this.canvas.getBoundingClientRect();
        return this.canvas.width / rect.width;
    }
    
    clientToCanvasCoords(clientX, clientY) {
        const rect = this.canvas.getBoundingClientRect();
        const scale = this.getScaleRatio();
        return {
            x: (clientX - rect.left) * scale,
            y: (clientY - rect.top) * scale
        };
    }
    
    onMouseDown(e) {
        e.preventDefault();
        const { x, y } = this.clientToCanvasCoords(e.clientX, e.clientY);
        this.startDrag(x, y);
    }
    
    onMouseMove(e) {
        e.preventDefault();
        const { x, y } = this.clientToCanvasCoords(e.clientX, e.clientY);
        this.updateDrag(x, y);
    }
    
    onMouseUp(e) {
        e.preventDefault();
        this.endDrag();
    }
    
    onTouchStart(e) {
        e.preventDefault();
        const touch = e.touches[0];
        const { x, y } = this.clientToCanvasCoords(touch.clientX, touch.clientY);
        this.startDrag(x, y);
    }
    
    onTouchMove(e) {
        e.preventDefault();
        const touch = e.touches[0];
        const { x, y } = this.clientToCanvasCoords(touch.clientX, touch.clientY);
        this.updateDrag(x, y);
    }
    
    onTouchEnd(e) {
        e.preventDefault();
        this.endDrag();
    }
    
    startDrag(x, y) {
        this.lastX = x;
        this.lastY = y;
        
        // Determine what user clicked on
        this.resizeHandle = this.getResizeHandle(x, y);
        if (this.resizeHandle) {
            this.isDragging = true;
            return;
        }
        
        // Check if click is inside selection (move entire selection)
        if (this.pointInSelection(x, y)) {
            this.isDragging = true;
            this.resizeHandle = 'move';
            return;
        }
        
        // Otherwise, start new selection
        this.isDragging = true;
        this.selection = {
            x: x,
            y: y,
            width: 1,
            height: 1
        };
    }
    
    updateDrag(x, y) {
        if (!this.isDragging) return;
        
        const dx = x - this.lastX;
        const dy = y - this.lastY;
        
        if (this.resizeHandle === 'move') {
            // Move entire selection
            this.selection.x = Math.max(0, Math.min(this.image.width - this.selection.width, this.selection.x + dx));
            this.selection.y = Math.max(0, Math.min(this.image.height - this.selection.height, this.selection.y + dy));
        } else if (this.resizeHandle === 'nw') {
            // Top-left
            this.selection.x = Math.min(this.selection.x + dx, this.selection.x + this.selection.width - 1);
            this.selection.y = Math.min(this.selection.y + dy, this.selection.y + this.selection.height - 1);
            this.selection.width = Math.max(1, this.selection.width - dx);
            this.selection.height = Math.max(1, this.selection.height - dy);
        } else if (this.resizeHandle === 'ne') {
            // Top-right
            this.selection.width = Math.max(1, this.selection.width + dx);
            this.selection.y = Math.min(this.selection.y + dy, this.selection.y + this.selection.height - 1);
            this.selection.height = Math.max(1, this.selection.height - dy);
        } else if (this.resizeHandle === 'sw') {
            // Bottom-left
            this.selection.x = Math.min(this.selection.x + dx, this.selection.x + this.selection.width - 1);
            this.selection.width = Math.max(1, this.selection.width - dx);
            this.selection.height = Math.max(1, this.selection.height + dy);
        } else if (this.resizeHandle === 'se') {
            // Bottom-right
            this.selection.width = Math.max(1, this.selection.width + dx);
            this.selection.height = Math.max(1, this.selection.height + dy);
        } else {
            // Drawing new selection box from drag start to current
            const x0 = Math.min(this.lastX, x);
            const y0 = Math.min(this.lastY, y);
            const x1 = Math.max(this.lastX, x);
            const y1 = Math.max(this.lastY, y);
            
            this.selection = {
                x: x0,
                y: y0,
                width: x1 - x0,
                height: y1 - y0
            };
        }
        
        this.lastX = x;
        this.lastY = y;
        this.draw();
    }
    
    endDrag() {
        this.isDragging = false;
        this.resizeHandle = null;
    }
    
    getResizeHandle(x, y) {
        const h = this.HANDLE_SIZE;
        const s = this.selection;
        
        // Top-left
        if (Math.abs(x - s.x) < h && Math.abs(y - s.y) < h) return 'nw';
        // Top-right
        if (Math.abs(x - (s.x + s.width)) < h && Math.abs(y - s.y) < h) return 'ne';
        // Bottom-left
        if (Math.abs(x - s.x) < h && Math.abs(y - (s.y + s.height)) < h) return 'sw';
        // Bottom-right
        if (Math.abs(x - (s.x + s.width)) < h && Math.abs(y - (s.y + s.height)) < h) return 'se';
        
        return null;
    }
    
    pointInSelection(x, y) {
        const s = this.selection;
        return x >= s.x && x <= s.x + s.width && y >= s.y && y <= s.y + s.height;
    }
    
    draw() {
        const naturalWidth = this.image.naturalWidth || this.image.width;
        const naturalHeight = this.image.naturalHeight || this.image.height;
        
        // Clear canvas to transparent
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        
        // Don't draw the image - it's already visible underneath
        // Just draw the darkened overlay outside selection
        this.ctx.fillStyle = `rgba(0, 0, 0, ${this.OVERLAY_ALPHA})`;
        
        const s = this.selection;
        
        // Left
        this.ctx.fillRect(0, 0, s.x, naturalHeight);
        // Right
        this.ctx.fillRect(s.x + s.width, 0, naturalWidth - s.x - s.width, naturalHeight);
        // Top
        this.ctx.fillRect(s.x, 0, s.width, s.y);
        // Bottom
        this.ctx.fillRect(s.x, s.y + s.height, s.width, naturalHeight - s.y - s.height);
        
        // Draw selection border
        this.ctx.strokeStyle = this.SELECTION_COLOR;
        this.ctx.lineWidth = 3;
        this.ctx.strokeRect(s.x, s.y, s.width, s.height);
        
        // Draw grid lines (rule of thirds)
        this.ctx.strokeStyle = `rgba(76, 175, 80, 0.3)`;
        this.ctx.lineWidth = 1;
        const w = s.width / 3;
        const h = s.height / 3;
        // Vertical lines
        this.ctx.beginPath();
        this.ctx.moveTo(s.x + w, s.y);
        this.ctx.lineTo(s.x + w, s.y + s.height);
        this.ctx.moveTo(s.x + w * 2, s.y);
        this.ctx.lineTo(s.x + w * 2, s.y + s.height);
        this.ctx.stroke();
        // Horizontal lines
        this.ctx.beginPath();
        this.ctx.moveTo(s.x, s.y + h);
        this.ctx.lineTo(s.x + s.width, s.y + h);
        this.ctx.moveTo(s.x, s.y + h * 2);
        this.ctx.lineTo(s.x + s.width, s.y + h * 2);
        this.ctx.stroke();
        
        // Draw resize handles
        this.drawHandle(s.x, s.y);
        this.drawHandle(s.x + s.width, s.y);
        this.drawHandle(s.x, s.y + s.height);
        this.drawHandle(s.x + s.width, s.y + s.height);
    }
    
    drawHandle(x, y) {
        const h = this.HANDLE_SIZE;
        this.ctx.fillStyle = this.SELECTION_COLOR;
        this.ctx.fillRect(x - h/2, y - h/2, h, h);
        this.ctx.strokeStyle = '#fff';
        this.ctx.lineWidth = 1;
        this.ctx.strokeRect(x - h/2, y - h/2, h, h);
    }
    
    enter() {
        this.container.innerHTML = '';
        this.container.appendChild(this.canvas);
        this.container.style.display = 'block';
    }
    
    exit() {
        this.container.innerHTML = '';
        this.container.style.display = 'none';
    }
    
    getSelection() {
        return {
            x: Math.round(this.selection.x),
            y: Math.round(this.selection.y),
            width: Math.round(this.selection.width),
            height: Math.round(this.selection.height)
        };
    }
    
    reset() {
        const padding = this.image.width * 0.2;
        this.selection = {
            x: padding,
            y: padding,
            width: this.image.width - (padding * 2),
            height: this.image.height - (padding * 2)
        };
        this.draw();
    }
}
