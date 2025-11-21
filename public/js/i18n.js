/**
 * i18n - Internationalization Module
 * Handles multi-language support on the frontend
 */

class I18n {
    constructor() {
        this.translations = {};
        this.currentLanguage = 'en';
        this.supportedLanguages = ['en', 'es', 'fr', 'de', 'zh'];
        this.languageNames = {
            'en': 'English',
            'es': 'Español',
            'fr': 'Français',
            'de': 'Deutsch',
            'zh': '简体中文'
        };
        this.init();
    }

    /**
     * Initialize i18n system
     */
    async init() {
        // Detect language
        this.currentLanguage = this.detectLanguage();
        
        // Load translations
        await this.loadTranslations();
        
        // Store in localStorage
        localStorage.setItem('language', this.currentLanguage);
        
        // Update HTML lang attribute
        document.documentElement.lang = this.currentLanguage;
        
        // Trigger custom event
        document.dispatchEvent(new CustomEvent('i18nReady', {
            detail: { language: this.currentLanguage }
        }));
    }

    /**
     * Detect user's preferred language
     */
    detectLanguage() {
        // Priority 1: URL parameter (?lang=es)
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('lang')) {
            const lang = urlParams.get('lang');
            if (this.supportedLanguages.includes(lang)) {
                return lang;
            }
        }

        // Priority 2: localStorage
        const stored = localStorage.getItem('language');
        if (stored && this.supportedLanguages.includes(stored)) {
            return stored;
        }

        // Priority 3: Browser language
        const browserLang = this.detectBrowserLanguage();
        if (browserLang && this.supportedLanguages.includes(browserLang)) {
            return browserLang;
        }

        // Default to English
        return 'en';
    }

    /**
     * Detect language from browser Accept-Language
     */
    detectBrowserLanguage() {
        const lang = navigator.language || navigator.userLanguage;
        if (!lang) return null;

        // Extract language code (e.g., 'es' from 'es-ES')
        const baseLanguage = lang.split('-')[0].toLowerCase();
        
        if (this.supportedLanguages.includes(baseLanguage)) {
            return baseLanguage;
        }

        return null;
    }

    /**
     * Load translations from server
     */
    async loadTranslations() {
        try {
            const response = await fetch(`./api.php?action=get_translations&lang=${this.currentLanguage}`);
            if (response.ok) {
                try {
                    this.translations = await response.json();
                } catch (e) {
                    console.warn('Failed to parse translations JSON:', e);
                    this.translations = {};
                }
            } else {
                console.warn('Failed to load translations (status ' + response.status + '), using defaults');
                this.translations = {};
            }
        } catch (error) {
            console.error('Error loading translations:', error);
            this.translations = {};
        }
    }

    /**
     * Get translation for key
     */
    t(key, defaultValue = null) {
        const keys = key.split('.');
        let value = this.translations;

        for (const k of keys) {
            if (value && typeof value === 'object' && k in value) {
                value = value[k];
            } else {
                return defaultValue !== null ? defaultValue : key;
            }
        }

        return value;
    }

    /**
     * Set current language and reload translations
     */
    async setLanguage(language) {
        if (!this.supportedLanguages.includes(language)) {
            console.warn(`Language ${language} not supported`);
            return false;
        }

        try {
            // Notify server
            const response = await fetch('./api.php?action=set_language', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ language })
            });

            if (response.ok) {
                this.currentLanguage = language;
                localStorage.setItem('language', language);
                
                // Reload translations
                await this.loadTranslations();
                
                // Update HTML lang attribute
                document.documentElement.lang = language;
                
                // Trigger custom event
                document.dispatchEvent(new CustomEvent('i18nChanged', {
                    detail: { language: this.currentLanguage }
                }));
                
                // Re-render UI
                this.updatePageText();
                
                return true;
            }
        } catch (error) {
            console.error('Error setting language:', error);
        }

        return false;
    }

    /**
     * Get current language
     */
    getCurrentLanguage() {
        return this.currentLanguage;
    }

    /**
     * Get language name
     */
    getLanguageName(code) {
        return this.languageNames[code] || code;
    }

    /**
     * Get all supported languages
     */
    getSupportedLanguages() {
        return this.supportedLanguages.map(code => ({
            code,
            name: this.getLanguageName(code)
        }));
    }

    /**
     * Update page text for all elements with data-i18n attribute
     */
    updatePageText() {
        // Update text content
        document.querySelectorAll('[data-i18n]').forEach(element => {
            const key = element.getAttribute('data-i18n');
            const translation = this.t(key);
            
            if (element.tagName === 'INPUT' || element.tagName === 'TEXTAREA') {
                if (element.getAttribute('placeholder')) {
                    element.setAttribute('placeholder', translation);
                } else {
                    element.value = translation;
                }
            } else {
                element.textContent = translation;
            }
        });

        // Update attributes
        document.querySelectorAll('[data-i18n-attr]').forEach(element => {
            const attrs = element.getAttribute('data-i18n-attr').split(',');
            attrs.forEach(attr => {
                const key = `${attr}:${element.getAttribute(`data-i18n-${attr}`)}`;
                const translation = this.t(key);
                if (translation !== key) {
                    element.setAttribute(attr, translation);
                }
            });
        });

        // Trigger event to allow other components to update
        document.dispatchEvent(new CustomEvent('i18nUpdated', {
            detail: { language: this.currentLanguage }
        }));
    }
}

// Create global instance
const i18n = new I18n();

/**
 * Shorthand for i18n.t()
 */
function t(key, defaultValue = null) {
    return i18n.t(key, defaultValue);
}

/**
 * Update UI text for a section
 */
function updateTextForSection(selector = 'body') {
    const section = document.querySelector(selector);
    if (!section) return;

    section.querySelectorAll('[data-i18n]').forEach(element => {
        const key = element.getAttribute('data-i18n');
        const translation = i18n.t(key);
        
        if (element.tagName === 'INPUT' || element.tagName === 'TEXTAREA') {
            if (element.hasAttribute('placeholder')) {
                element.setAttribute('placeholder', translation);
            }
        } else {
            element.textContent = translation;
        }
    });
}
