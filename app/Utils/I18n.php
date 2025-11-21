<?php
/**
 * Language Manager (i18n)
 * Handles multi-language support across the application
 */

class I18n {
    
    private static $instance = null;
    private static $currentLanguage = 'en';
    private static $translations = [];
    private static $supportedLanguages = ['en', 'es', 'fr', 'de', 'zh'];

    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize language system
     */
    public static function init() {
        $instance = self::getInstance();
        
        // Get language from session, cookie, or query parameter
        $lang = self::detectLanguage();
        self::setLanguage($lang);
        
        return $instance;
    }

    /**
     * Detect user's preferred language
     */
    private static function detectLanguage() {
        // Priority 1: Query parameter (?lang=es)
        if (isset($_GET['lang']) && in_array($_GET['lang'], self::$supportedLanguages)) {
            return $_GET['lang'];
        }
        
        // Priority 2: Session
        if (isset($_SESSION['language']) && in_array($_SESSION['language'], self::$supportedLanguages)) {
            return $_SESSION['language'];
        }
        
        // Priority 3: Cookie
        if (isset($_COOKIE['language']) && in_array($_COOKIE['language'], self::$supportedLanguages)) {
            return $_COOKIE['language'];
        }
        
        // Priority 4: Browser Accept-Language
        $browserLang = self::detectBrowserLanguage();
        if ($browserLang && in_array($browserLang, self::$supportedLanguages)) {
            return $browserLang;
        }
        
        // Default to English
        return 'en';
    }

    /**
     * Detect language from browser Accept-Language header
     */
    private static function detectBrowserLanguage() {
        if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return null;
        }

        $accepted = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        
        // Parse Accept-Language header
        $languages = [];
        foreach (explode(',', $accepted) as $lang) {
            $parts = explode(';q=', trim($lang));
            $langCode = trim($parts[0]);
            $quality = isset($parts[1]) ? (float) $parts[1] : 1.0;
            
            // Extract language code (e.g., 'es' from 'es-ES')
            $langParts = explode('-', $langCode);
            $baseLanguage = strtolower($langParts[0]);
            
            $languages[$baseLanguage] = $quality;
        }

        // Sort by quality
        arsort($languages);

        // Return first supported language
        foreach (array_keys($languages) as $lang) {
            if (in_array($lang, self::$supportedLanguages)) {
                return $lang;
            }
        }

        return null;
    }

    /**
     * Set current language
     */
    public static function setLanguage($language) {
        if (!in_array($language, self::$supportedLanguages)) {
            $language = 'en';
        }

        self::$currentLanguage = $language;

        // Save to session
        session_start();
        $_SESSION['language'] = $language;
        
        // Save to cookie (30 days)
        setcookie('language', $language, time() + (30 * 24 * 60 * 60), '/');

        // Load translations
        self::loadTranslations($language);
    }

    /**
     * Get current language
     */
    public static function getCurrentLanguage() {
        return self::$currentLanguage;
    }

    /**
     * Load translation file
     */
    private static function loadTranslations($language) {
        $filePath = __DIR__ . '/i18n/' . $language . '.php';
        
        if (file_exists($filePath)) {
            self::$translations = require($filePath);
        } else {
            // Fallback to English if language file not found
            $filePath = __DIR__ . '/i18n/en.php';
            self::$translations = require($filePath);
        }
    }

    /**
     * Get translation for key
     * Supports nested keys with dot notation (e.g., 'login.title')
     */
    public static function get($key, $default = null) {
        $keys = explode('.', $key);
        $value = self::$translations;

        foreach ($keys as $k) {
            if (is_array($value) && isset($value[$k])) {
                $value = $value[$k];
            } else {
                return $default !== null ? $default : $key;
            }
        }

        return $value;
    }

    /**
     * Shorthand for get()
     */
    public static function t($key, $default = null) {
        return self::get($key, $default);
    }

    /**
     * Get all supported languages
     */
    public static function getSupportedLanguages() {
        return self::$supportedLanguages;
    }

    /**
     * Get language name (for display)
     */
    public static function getLanguageName($code) {
        $names = [
            'en' => 'English',
            'es' => 'Español',
            'fr' => 'Français',
            'de' => 'Deutsch',
            'zh' => '简体中文',
        ];

        return $names[$code] ?? $code;
    }

    /**
     * Get all translations as JSON (for JavaScript)
     */
    public static function getJSON($language = null) {
        if ($language === null) {
            $language = self::$currentLanguage;
        }

        $filePath = __DIR__ . '/i18n/' . $language . '.php';
        
        if (file_exists($filePath)) {
            $translations = require($filePath);
        } else {
            $translations = require(__DIR__ . '/i18n/en.php');
        }

        return json_encode($translations);
    }
}

/**
 * Shorthand functions for use in templates
 */
if (!function_exists('t')) {
    function t($key, $default = null) {
        return I18n::t($key, $default);
    }
}

if (!function_exists('lang')) {
    function lang() {
        return I18n::getCurrentLanguage();
    }
}

if (!function_exists('langName')) {
    function langName($code) {
        return I18n::getLanguageName($code);
    }
}
