/**
 * Theme Manager - Dark Mode, Color Schemes, and Accent Colors
 */
class ThemeManager {
    constructor() {
        this.themes = {
            light: {
                name: 'Light',
                primary: '#f8f9fa',
                secondary: '#ffffff',
                text: '#212529',
                textSecondary: '#6c757d',
                border: '#dee2e6',
                shadow: 'rgba(0,0,0,0.1)'
            },
            dark: {
                name: 'Dark',
                primary: '#1a1a1a',
                secondary: '#2d2d2d',
                text: '#e9ecef',
                textSecondary: '#adb5bd',
                border: '#495057',
                shadow: 'rgba(0,0,0,0.3)'
            }
        };

        this.colorSchemes = {
            blue: { name: 'Ocean Blue', color: '#007bff', hover: '#0056b3' },
            purple: { name: 'Purple Haze', color: '#6f42c1', hover: '#5a2d91' },
            green: { name: 'Forest Green', color: '#28a745', hover: '#1e7e34' },
            red: { name: 'Ruby Red', color: '#dc3545', hover: '#a71d2a' },
            orange: { name: 'Sunset Orange', color: '#fd7e14', hover: '#e8590c' },
            teal: { name: 'Teal Wave', color: '#20c997', hover: '#199d76' },
            pink: { name: 'Pink Blossom', color: '#e83e8c', hover: '#c42367' },
            indigo: { name: 'Deep Indigo', color: '#6610f2', hover: '#510bc4' }
        };

        this.currentTheme = localStorage.getItem('theme') || 'light';
        this.currentScheme = localStorage.getItem('colorScheme') || 'blue';
        this.customAccent = localStorage.getItem('customAccent') || null;

        this.init();
    }

    init() {
        this.applyTheme();
        this.applyColorScheme();
        
        // Wait for DOM to be ready before creating controls
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.createThemeControls());
        } else {
            this.createThemeControls();
        }
    }

    applyTheme() {
        const theme = this.themes[this.currentTheme];
        const root = document.documentElement;

        root.style.setProperty('--bg-primary', theme.primary);
        root.style.setProperty('--bg-secondary', theme.secondary);
        root.style.setProperty('--text-primary', theme.text);
        root.style.setProperty('--text-secondary', theme.textSecondary);
        root.style.setProperty('--border-color', theme.border);
        root.style.setProperty('--shadow-color', theme.shadow);

        document.body.setAttribute('data-theme', this.currentTheme);
    }

    applyColorScheme() {
        const root = document.documentElement;
        const accent = this.customAccent || this.colorSchemes[this.currentScheme].color;
        const hover = this.customAccent 
            ? this.darkenColor(this.customAccent, 20) 
            : this.colorSchemes[this.currentScheme].hover;

        root.style.setProperty('--accent-color', accent);
        root.style.setProperty('--accent-hover', hover);
        
        document.body.setAttribute('data-scheme', this.currentScheme);
    }

    toggleTheme() {
        this.currentTheme = this.currentTheme === 'light' ? 'dark' : 'light';
        localStorage.setItem('theme', this.currentTheme);
        this.applyTheme();
        this.updateThemeIcon();
    }

    setColorScheme(scheme) {
        this.currentScheme = scheme;
        this.customAccent = null; // Clear custom accent when selecting preset
        localStorage.setItem('colorScheme', scheme);
        localStorage.removeItem('customAccent');
        this.applyColorScheme();
        this.updateSchemeButtons();
    }

    setCustomAccent(color) {
        this.customAccent = color;
        localStorage.setItem('customAccent', color);
        this.applyColorScheme();
    }

    darkenColor(hex, percent) {
        const num = parseInt(hex.replace('#', ''), 16);
        const amt = Math.round(2.55 * percent);
        const R = (num >> 16) - amt;
        const G = (num >> 8 & 0x00FF) - amt;
        const B = (num & 0x0000FF) - amt;
        return '#' + (0x1000000 + (R < 255 ? R < 1 ? 0 : R : 255) * 0x10000 +
            (G < 255 ? G < 1 ? 0 : G : 255) * 0x100 +
            (B < 255 ? B < 1 ? 0 : B : 255))
            .toString(16).slice(1);
    }

    createThemeControls() {
        // Attach to existing theme buttons in the nav
        const themeToggleBtn = document.getElementById('themeToggleBtn');
        const themeSettingsBtn = document.getElementById('themeSettingsBtn');
        
        if (!themeToggleBtn || !themeSettingsBtn) {
            console.warn('ThemeManager: Theme buttons not found in DOM, retrying...');
            setTimeout(() => this.createThemeControls(), 500);
            return;
        }

        // Don't attach multiple times
        if (themeToggleBtn.onclick) {
            return; // Already attached
        }

        // Attach toggle functionality
        themeToggleBtn.onclick = () => this.toggleTheme();
        
        // Attach settings functionality
        themeSettingsBtn.onclick = () => this.openThemeSettings();
        
        // Update icon based on current theme
        this.updateThemeIcon();
    }

    updateThemeIcon() {
        const btn = document.getElementById('themeToggleBtn');
        if (btn) {
            btn.innerHTML = this.currentTheme === 'dark' ? '☀️' : '🌙';
            btn.title = this.currentTheme === 'dark' ? 'Switch to Light Mode' : 'Switch to Dark Mode';
        }
    }

    updateSchemeButtons() {
        document.querySelectorAll('.scheme-btn').forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.scheme === this.currentScheme && !this.customAccent) {
                btn.classList.add('active');
            }
        });
    }

    openThemeSettings() {
        // Check if modal already exists
        let modal = document.getElementById('themeModal');
        if (!modal) {
            modal = this.createThemeModal();
            document.body.appendChild(modal);
        }
        modal.classList.add('active');
    }

    createThemeModal() {
        const modal = document.createElement('div');
        modal.id = 'themeModal';
        modal.className = 'modal';
        modal.innerHTML = `
            <div class="modal-content theme-modal-content">
                <div class="modal-header">
                    <h2>🎨 Theme Customization</h2>
                    <button class="close-btn" onclick="document.getElementById('themeModal').classList.remove('active')">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="theme-section">
                        <h3>Theme Mode</h3>
                        <div class="theme-mode-toggle">
                            <button class="theme-mode-btn ${this.currentTheme === 'light' ? 'active' : ''}" data-theme="light">
                                ☀️ Light
                            </button>
                            <button class="theme-mode-btn ${this.currentTheme === 'dark' ? 'active' : ''}" data-theme="dark">
                                🌙 Dark
                            </button>
                        </div>
                    </div>

                    <div class="theme-section">
                        <h3>Color Schemes</h3>
                        <div class="color-schemes">
                            ${Object.entries(this.colorSchemes).map(([key, scheme]) => `
                                <button class="scheme-btn ${key === this.currentScheme && !this.customAccent ? 'active' : ''}" 
                                        data-scheme="${key}"
                                        style="background: ${scheme.color};"
                                        title="${scheme.name}">
                                    ${scheme.name}
                                </button>
                            `).join('')}
                        </div>
                    </div>

                    <div class="theme-section">
                        <h3>Custom Accent Color</h3>
                        <div class="custom-accent">
                            <input type="color" 
                                   id="accentPicker" 
                                   value="${this.customAccent || this.colorSchemes[this.currentScheme].color}">
                            <button class="btn btn-primary" id="applyAccent">Apply Custom</button>
                            <button class="btn btn-secondary" id="resetAccent">Reset</button>
                        </div>
                    </div>

                    <div class="theme-preview">
                        <h3>Preview</h3>
                        <div class="preview-box">
                            <button class="btn btn-primary">Primary Button</button>
                            <button class="btn btn-secondary">Secondary Button</button>
                            <p>This is sample text in the current theme.</p>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Event listeners
        modal.querySelectorAll('.theme-mode-btn').forEach(btn => {
            btn.onclick = () => {
                this.currentTheme = btn.dataset.theme;
                localStorage.setItem('theme', this.currentTheme);
                this.applyTheme();
                modal.querySelectorAll('.theme-mode-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                this.updateThemeIcon();
            };
        });

        modal.querySelectorAll('.scheme-btn').forEach(btn => {
            btn.onclick = () => {
                this.setColorScheme(btn.dataset.scheme);
            };
        });

        modal.querySelector('#applyAccent').onclick = () => {
            const color = modal.querySelector('#accentPicker').value;
            this.setCustomAccent(color);
            this.updateSchemeButtons();
        };

        modal.querySelector('#resetAccent').onclick = () => {
            this.customAccent = null;
            localStorage.removeItem('customAccent');
            this.applyColorScheme();
            this.updateSchemeButtons();
            modal.querySelector('#accentPicker').value = this.colorSchemes[this.currentScheme].color;
        };

        // Close on outside click
        modal.onclick = (e) => {
            if (e.target === modal) {
                modal.classList.remove('active');
            }
        };

        return modal;
    }
}

// Initialize theme manager when DOM is ready
// Initialize ThemeManager when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.themeManager = new ThemeManager();
    });
} else {
    window.themeManager = new ThemeManager();
}
