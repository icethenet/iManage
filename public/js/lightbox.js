/**
 * Lightbox - Full-screen image viewer
 * Allows users to view images in a modal overlay with navigation
 */

class Lightbox {
    constructor() {
        this.lightbox = document.getElementById('lightbox');
        this.lightboxImage = document.getElementById('lightboxImage');
        this.lightboxTitle = document.getElementById('lightboxTitle');
        this.lightboxDetails = document.getElementById('lightboxDetails');
        this.closeBtn = document.getElementById('lightboxClose');
        this.prevBtn = document.getElementById('lightboxPrev');
        this.nextBtn = document.getElementById('lightboxNext');
        
        this.currentImages = [];
        this.currentIndex = 0;
        
        this.init();
    }
    
    init() {
        // Close button
        this.closeBtn.addEventListener('click', () => this.close());
        
        // Navigation buttons
        this.prevBtn.addEventListener('click', () => this.prev());
        this.nextBtn.addEventListener('click', () => this.next());
        
        // Click outside image to close
        this.lightbox.addEventListener('click', (e) => {
            if (e.target === this.lightbox) {
                this.close();
            }
        });
        
        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (!this.lightbox.classList.contains('active')) return;
            
            switch(e.key) {
                case 'Escape':
                    this.close();
                    break;
                case 'ArrowLeft':
                    this.prev();
                    break;
                case 'ArrowRight':
                    this.next();
                    break;
            }
        });
        
        // Attach to gallery images
        this.attachToGallery();
    }
    
    attachToGallery() {
        // Attach click handlers to all gallery images
        document.addEventListener('click', (e) => {
            const galleryItem = e.target.closest('.gallery-item');
            if (galleryItem && e.target.tagName === 'IMG') {
                e.preventDefault();
                this.openFromGallery(galleryItem);
            }
        });
    }
    
    openFromGallery(galleryItem) {
        // Get all visible gallery items
        const allGalleryItems = Array.from(document.querySelectorAll('.gallery-item'));
        
        // Build images array
        this.currentImages = allGalleryItems.map(item => {
            const img = item.querySelector('img');
            return {
                src: img.src.replace('/thumbnails/', '/original/'),
                thumbnail: img.src,
                title: item.dataset.filename || 'Image',
                size: item.dataset.filesize || '',
                date: item.dataset.uploaddate || '',
                id: item.dataset.imageid
            };
        });
        
        // Find current index
        this.currentIndex = allGalleryItems.indexOf(galleryItem);
        
        // Open lightbox
        this.show();
    }
    
    show() {
        if (this.currentImages.length === 0) return;
        
        const image = this.currentImages[this.currentIndex];
        
        // Update image
        this.lightboxImage.src = image.src;
        this.lightboxImage.alt = image.title;
        
        // Update caption
        this.lightboxTitle.textContent = image.title;
        
        let details = [];
        if (image.size) details.push(this.formatFileSize(image.size));
        if (image.date) details.push(this.formatDate(image.date));
        this.lightboxDetails.textContent = details.join(' • ');
        
        // Show/hide navigation buttons
        this.prevBtn.style.display = this.currentIndex > 0 ? 'block' : 'none';
        this.nextBtn.style.display = this.currentIndex < this.currentImages.length - 1 ? 'block' : 'none';
        
        // Show lightbox
        this.lightbox.style.display = 'flex';
        setTimeout(() => {
            this.lightbox.classList.add('active');
        }, 10);
        
        // Prevent body scroll
        document.body.style.overflow = 'hidden';
    }
    
    close() {
        this.lightbox.classList.remove('active');
        setTimeout(() => {
            this.lightbox.style.display = 'none';
        }, 300);
        
        // Restore body scroll
        document.body.style.overflow = '';
    }
    
    prev() {
        if (this.currentIndex > 0) {
            this.currentIndex--;
            this.show();
        }
    }
    
    next() {
        if (this.currentIndex < this.currentImages.length - 1) {
            this.currentIndex++;
            this.show();
        }
    }
    
    formatFileSize(bytes) {
        if (!bytes || bytes === 'N/A') return '';
        const sizes = ['B', 'KB', 'MB', 'GB'];
        if (bytes === 0) return '0 B';
        const i = Math.floor(Math.log(bytes) / Math.log(1024));
        return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i];
    }
    
    formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
    }
}

// Initialize lightbox when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.lightbox = new Lightbox();
});
