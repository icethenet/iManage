/**
 * Tag Suggestions and Management
 * Provides autocomplete suggestions based on existing tags
 */

(function() {
    'use strict';
    
    let allTags = new Set();
    let suggestionsCache = [];
    
    /**
     * Initialize tag suggestions
     */
    function initTagSuggestions() {
        // Load existing tags from the database
        loadExistingTags();
        
        // Add suggestions to tag input fields
        const tagInputs = ['editTags', 'bulkTags'];
        tagInputs.forEach(inputId => {
            const input = document.getElementById(inputId);
            if (input) {
                setupTagInput(input);
            }
        });
        
        // Observe for when modal opens (for editTags)
        observeModal();
    }
    
    /**
     * Load all existing tags from images
     */
    async function loadExistingTags() {
        try {
            const response = await fetch(`${API_BASE}?action=list`);
            const data = await response.json();
            
            if (data.success && data.data) {
                // Extract all tags from images
                data.data.forEach(image => {
                    if (image.tags) {
                        const tags = image.tags.split(',').map(t => t.trim()).filter(t => t);
                        tags.forEach(tag => allTags.add(tag.toLowerCase()));
                    }
                });
                
                suggestionsCache = Array.from(allTags).sort();
            }
        } catch (error) {
            console.warn('Could not load existing tags:', error);
        }
    }
    
    /**
     * Setup tag input with autocomplete
     */
    function setupTagInput(input) {
        const wrapper = document.createElement('div');
        wrapper.style.position = 'relative';
        wrapper.style.width = '100%';
        
        // Wrap input
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);
        
        // Create suggestions dropdown
        const dropdown = document.createElement('div');
        dropdown.className = 'tag-suggestions-dropdown';
        dropdown.style.cssText = `
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 4px 4px;
            max-height: 200px;
            overflow-y: auto;
            display: none;
            z-index: 1000;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        `;
        wrapper.appendChild(dropdown);
        
        // Handle input
        let currentFocus = -1;
        
        input.addEventListener('input', function() {
            const value = this.value;
            const cursorPos = this.selectionStart;
            
            // Get current tag being typed
            const beforeCursor = value.substring(0, cursorPos);
            const lastComma = beforeCursor.lastIndexOf(',');
            const currentTag = beforeCursor.substring(lastComma + 1).trim().toLowerCase();
            
            if (currentTag.length < 2) {
                dropdown.style.display = 'none';
                return;
            }
            
            // Filter suggestions
            const matches = suggestionsCache.filter(tag => 
                tag.includes(currentTag) && tag !== currentTag
            ).slice(0, 10);
            
            if (matches.length === 0) {
                dropdown.style.display = 'none';
                return;
            }
            
            // Show suggestions
            dropdown.innerHTML = matches.map((tag, index) => `
                <div class="tag-suggestion-item" data-index="${index}" data-tag="${tag}" style="
                    padding: 8px 12px;
                    cursor: pointer;
                    border-bottom: 1px solid #eee;
                    transition: background 0.2s;
                " onmouseover="this.style.background='#f0f2ff'" onmouseout="this.style.background='white'">
                    🏷️ ${highlightMatch(tag, currentTag)}
                </div>
            `).join('');
            
            dropdown.style.display = 'block';
            currentFocus = -1;
            
            // Add click handlers
            dropdown.querySelectorAll('.tag-suggestion-item').forEach(item => {
                item.addEventListener('click', function() {
                    insertTag(input, this.dataset.tag, cursorPos);
                    dropdown.style.display = 'none';
                });
            });
        });
        
        // Handle keyboard navigation
        input.addEventListener('keydown', function(e) {
            if (dropdown.style.display === 'none') return;
            
            const items = dropdown.querySelectorAll('.tag-suggestion-item');
            
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                currentFocus++;
                if (currentFocus >= items.length) currentFocus = 0;
                setActive(items, currentFocus);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                currentFocus--;
                if (currentFocus < 0) currentFocus = items.length - 1;
                setActive(items, currentFocus);
            } else if (e.key === 'Enter' && currentFocus > -1) {
                e.preventDefault();
                items[currentFocus].click();
            } else if (e.key === 'Escape') {
                dropdown.style.display = 'none';
            }
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!wrapper.contains(e.target)) {
                dropdown.style.display = 'none';
            }
        });
    }
    
    /**
     * Highlight matching part of tag
     */
    function highlightMatch(tag, query) {
        const index = tag.toLowerCase().indexOf(query);
        if (index === -1) return tag;
        
        return tag.substring(0, index) +
               `<strong style="color: #667eea;">${tag.substring(index, index + query.length)}</strong>` +
               tag.substring(index + query.length);
    }
    
    /**
     * Set active suggestion item
     */
    function setActive(items, index) {
        items.forEach((item, i) => {
            if (i === index) {
                item.style.background = '#f0f2ff';
                item.scrollIntoView({ block: 'nearest' });
            } else {
                item.style.background = 'white';
            }
        });
    }
    
    /**
     * Insert tag at cursor position
     */
    function insertTag(input, tag, cursorPos) {
        const value = input.value;
        const beforeCursor = value.substring(0, cursorPos);
        const afterCursor = value.substring(cursorPos);
        
        // Find where current tag starts
        const lastComma = beforeCursor.lastIndexOf(',');
        const beforeTag = beforeCursor.substring(0, lastComma + 1);
        
        // Construct new value
        const newValue = beforeTag + (beforeTag.endsWith(',') ? ' ' : '') + tag + ', ' + afterCursor.trim();
        input.value = newValue;
        
        // Set cursor after inserted tag
        const newCursorPos = beforeTag.length + tag.length + 2;
        input.setSelectionRange(newCursorPos, newCursorPos);
        input.focus();
        
        // Add to known tags
        allTags.add(tag.toLowerCase());
    }
    
    /**
     * Observe modal for dynamic tag input
     */
    function observeModal() {
        const observer = new MutationObserver(function() {
            const modal = document.getElementById('imageModal');
            if (modal && modal.style.display === 'block') {
                const editTags = document.getElementById('editTags');
                if (editTags && !editTags.parentNode.classList.contains('tag-input-enhanced')) {
                    setupTagInput(editTags);
                    editTags.parentNode.classList.add('tag-input-enhanced');
                }
            }
        });
        
        const modal = document.getElementById('imageModal');
        if (modal) {
            observer.observe(modal, {
                attributes: true,
                attributeFilter: ['style']
            });
        }
    }
    
    /**
     * Get popular tags
     */
    function getPopularTags(limit = 10) {
        // In a real implementation, this would count tag frequency
        return suggestionsCache.slice(0, limit);
    }
    
    /**
     * Add a new tag to suggestions
     */
    function addTagToSuggestions(tag) {
        const normalizedTag = tag.trim().toLowerCase();
        if (normalizedTag && !allTags.has(normalizedTag)) {
            allTags.add(normalizedTag);
            suggestionsCache = Array.from(allTags).sort();
        }
    }
    
    // Initialize on page load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTagSuggestions);
    } else {
        initTagSuggestions();
    }
    
    // Export for use in other scripts
    window.TagSuggestions = {
        addTagToSuggestions,
        getPopularTags,
        loadExistingTags
    };
})();

