/**
 * GrapesJS Manager for Custom Landing Pages
 * Handles visual page editor initialization, save/load, and preview
 */

class GrapesJSManager {
    constructor() {
        this.editor = null;
        this.shareToken = null;
        this.isDesignMode = false;
        this.API_BASE = './api.php';
    }

    /**
     * Initialize GrapesJS editor with custom blocks and components
     */
    initEditor(containerId, shareToken = null) {
        this.shareToken = shareToken;
        
        this.editor = grapesjs.init({
            container: `#${containerId}`,
            height: '100vh',
            width: 'auto',
            storageManager: false, // We handle storage via API
            
            // Block Manager
            blockManager: {
                appendTo: '#blocks',
            },
            
            // Layer Manager
            layerManager: {
                appendTo: '#layers',
            },
            
            // Style Manager
            styleManager: {
                appendTo: '#styles',
                sectors: [{
                    name: 'General',
                    properties: [
                        'display',
                        'position',
                        'top',
                        'right',
                        'bottom',
                        'left',
                        'width',
                        'height',
                        'max-width',
                        'margin',
                        'padding'
                    ]
                }, {
                    name: 'Typography',
                    properties: [
                        'font-family',
                        'font-size',
                        'font-weight',
                        'letter-spacing',
                        'color',
                        'line-height',
                        'text-align',
                        'text-decoration',
                        'text-shadow'
                    ]
                }, {
                    name: 'Decorations',
                    properties: [
                        'background-color',
                        'background-image',
                        'background-position',
                        'background-size',
                        'border-radius',
                        'border',
                        'box-shadow',
                        'opacity'
                    ]
                }]
            },
            
            // Panels
            panels: {
                defaults: [
                    {
                        id: 'layers',
                        el: '#layers',
                    },
                    {
                        id: 'styles',
                        el: '#styles',
                    },
                    {
                        id: 'panel-switcher',
                        buttons: [{
                            id: 'show-blocks',
                            active: true,
                            label: '🧱 Blocks',
                            command: 'show-blocks',
                            togglable: false,
                        }, {
                            id: 'show-layers',
                            active: false,
                            label: '📋 Layers',
                            command: 'show-layers',
                            togglable: false,
                        }, {
                            id: 'show-styles',
                            active: false,
                            label: '🎨 Styles',
                            command: 'show-styles',
                            togglable: false,
                        }],
                    },
                    {
                        id: 'panel-actions',
                        el: '#panel-actions',
                        buttons: [{
                            id: 'save-design',
                            className: 'btn-save',
                            label: '💾 Save',
                            command: 'save-design',
                        }, {
                            id: 'preview',
                            className: 'btn-preview',
                            label: '👁️ Preview',
                            command: 'preview',
                        }, {
                            id: 'exit-design',
                            className: 'btn-exit',
                            label: '❌ Exit',
                            command: 'exit-design',
                        }]
                    }
                ]
            },
            
            // Canvas
            canvas: {
                styles: [
                    'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap',
                    './css/style.css'
                ]
            },
            
            // Plugins
            plugins: ['gjs-blocks-basic', 'grapesjs-plugin-forms'],
            pluginsOpts: {
                'gjs-blocks-basic': {},
                'grapesjs-plugin-forms': {}
            }
        });

        this.addCustomBlocks();
        this.addCustomCommands();
        this.loadDesign();
    }

    /**
     * Add custom blocks for image gallery components
     */
    addCustomBlocks() {
        const blockManager = this.editor.BlockManager;

        // Hero Section Block
        blockManager.add('hero-section', {
            label: '🎯 Hero Section',
            category: 'Gallery',
            content: `
                <section style="
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 80px 20px;
                    text-align: center;
                ">
                    <h1 style="font-size: 3em; margin: 0 0 20px 0; font-weight: 700;">My Gallery</h1>
                    <p style="font-size: 1.2em; opacity: 0.9;">Beautiful moments captured in time</p>
                </section>
            `
        });

        // Image Gallery Grid Block
        blockManager.add('gallery-grid', {
            label: '🖼️ Gallery Grid',
            category: 'Gallery',
            content: `
                <div class="gallery-container" style="
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                    gap: 20px;
                    padding: 40px 20px;
                    max-width: 1200px;
                    margin: 0 auto;
                ">
                    <div class="gallery-item" style="
                        border-radius: 8px;
                        overflow: hidden;
                        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
                        transition: transform 0.3s;
                    ">
                        <img src="https://via.placeholder.com/300x200" alt="Gallery Image" style="width: 100%; height: 200px; object-fit: cover;">
                    </div>
                    <div class="gallery-item" style="
                        border-radius: 8px;
                        overflow: hidden;
                        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
                        transition: transform 0.3s;
                    ">
                        <img src="https://via.placeholder.com/300x200" alt="Gallery Image" style="width: 100%; height: 200px; object-fit: cover;">
                    </div>
                    <div class="gallery-item" style="
                        border-radius: 8px;
                        overflow: hidden;
                        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
                        transition: transform 0.3s;
                    ">
                        <img src="https://via.placeholder.com/300x200" alt="Gallery Image" style="width: 100%; height: 200px; object-fit: cover;">
                    </div>
                </div>
            `
        });

        // Text Block with Style
        blockManager.add('styled-text', {
            label: '📝 Text Section',
            category: 'Gallery',
            content: `
                <div style="
                    max-width: 800px;
                    margin: 40px auto;
                    padding: 20px;
                    text-align: center;
                ">
                    <h2 style="font-size: 2em; margin-bottom: 15px; color: #333;">About This Gallery</h2>
                    <p style="font-size: 1.1em; color: #666; line-height: 1.6;">
                        Add your description here. Tell your story and share the context behind these beautiful images.
                    </p>
                </div>
            `
        });

        // Contact/Footer Block
        blockManager.add('footer-section', {
            label: '📧 Footer',
            category: 'Gallery',
            content: `
                <footer style="
                    background: #2c3e50;
                    color: white;
                    padding: 40px 20px;
                    text-align: center;
                ">
                    <p style="margin: 0 0 10px 0;">Powered by iManage</p>
                    <p style="margin: 0; opacity: 0.7; font-size: 0.9em;">© 2025 All Rights Reserved</p>
                </footer>
            `
        });
    }

    /**
     * Add custom commands for save/preview/exit
     */
    addCustomCommands() {
        const editor = this.editor;
        const manager = this;

        // Save Design Command
        editor.Commands.add('save-design', {
            run: async function() {
                await manager.saveDesign();
            }
        });

        // Preview Command
        editor.Commands.add('preview', {
            run: function() {
                editor.runCommand('preview');
            },
            stop: function() {
                editor.stopCommand('preview');
            }
        });

        // Exit Design Mode Command
        editor.Commands.add('exit-design', {
            run: function() {
                manager.exitDesignMode();
            }
        });

        // Show Blocks Panel
        editor.Commands.add('show-blocks', {
            run: function() {
                document.getElementById('blocks').style.display = 'block';
                document.getElementById('layers').style.display = 'none';
                document.getElementById('styles').style.display = 'none';
            }
        });

        // Show Layers Panel
        editor.Commands.add('show-layers', {
            run: function() {
                document.getElementById('blocks').style.display = 'none';
                document.getElementById('layers').style.display = 'block';
                document.getElementById('styles').style.display = 'none';
            }
        });

        // Show Styles Panel
        editor.Commands.add('show-styles', {
            run: function() {
                document.getElementById('blocks').style.display = 'none';
                document.getElementById('layers').style.display = 'none';
                document.getElementById('styles').style.display = 'block';
            }
        });
    }

    /**
     * Load existing design from server
     */
    async loadDesign() {
        if (!this.shareToken) {
            this.setDefaultTemplate();
            return;
        }

        try {
            const response = await fetch(`${this.API_BASE}?action=loadLandingPage&token=${this.shareToken}`);
            const data = await response.json();

            if (data.success && data.design) {
                // Load GrapesJS project data
                if (data.design.grapesjs_data) {
                    this.editor.loadProjectData(JSON.parse(data.design.grapesjs_data));
                } else if (data.design.html_content) {
                    // Fallback: load HTML directly
                    this.editor.setComponents(data.design.html_content);
                    if (data.design.css_content) {
                        this.editor.setStyle(data.design.css_content);
                    }
                }
                
                this.showNotification('Design loaded successfully', 'success');
            } else {
                this.setDefaultTemplate();
            }
        } catch (error) {
            console.error('Load design error:', error);
            this.setDefaultTemplate();
        }
    }

    /**
     * Set default template for new landing pages
     */
    setDefaultTemplate() {
        // Start with completely BLANK canvas for full creative freedom
        const defaultHTML = `
            <section style="padding: 100px 20px; text-align: center; min-height: 400px;">
                <h1>Start Designing Your Page</h1>
                <p>Drag blocks from the left panel to build your custom landing page</p>
            </section>
        `;
        
        this.editor.setComponents(defaultHTML);
    }

    /**
     * Save design to server
     */
    async saveDesign() {
        try {
            const html = this.editor.getHtml();
            const css = this.editor.getCss();
            const projectData = this.editor.getProjectData();

            const response = await fetch(`${this.API_BASE}?action=saveLandingPage`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    token: this.shareToken,
                    html_content: html,
                    css_content: css,
                    grapesjs_data: JSON.stringify(projectData),
                    page_title: document.querySelector('h1')?.textContent || 'Shared Gallery'
                })
            });

            const data = await response.json();
            
            if (data.success) {
                this.showNotification('Design saved successfully! ✅', 'success');
            } else {
                this.showNotification('Failed to save: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Save error:', error);
            this.showNotification('Save failed. Please try again.', 'error');
        }
    }

    /**
     * Exit design mode and return to normal view
     */
    exitDesignMode() {
        if (confirm('Exit design mode? Any unsaved changes will be lost.')) {
            window.location.href = `share.php?share=${this.shareToken}`;
        }
    }

    /**
     * Show notification message
     */
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `gjs-notification gjs-notification-${type}`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? '#4caf50' : type === 'error' ? '#f44336' : '#2196f3'};
            color: white;
            padding: 15px 25px;
            border-radius: 5px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            z-index: 10000;
            animation: slideIn 0.3s ease;
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    /**
     * Destroy editor instance
     */
    destroy() {
        if (this.editor) {
            this.editor.destroy();
            this.editor = null;
        }
    }
}

// Animation styles
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(style);

// Export for use in other scripts
window.GrapesJSManager = GrapesJSManager;
