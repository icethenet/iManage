# Multi-Language Support (i18n) Documentation

## Overview

iManage now supports multi-language user interfaces with seamless language switching. Currently supported languages:

- **English** (en) - Default
- **Spanish** (es)
- **French** (fr)
- **German** (de)
- **Simplified Chinese** (zh)

## Architecture

The i18n system consists of three components:

### 1. Backend (PHP)

**Location:** `app/Utils/I18n.php`

The PHP class handles:
- Language detection from multiple sources (URL, session, cookie, browser headers)
- Translation file loading
- API endpoints for language management
- Session and cookie persistence

**Key Methods:**
- `I18n::init()` - Initialize language system
- `I18n::setLanguage($language)` - Set current language
- `I18n::t($key)` - Get translation
- `I18n::getJSON($language)` - Export translations as JSON for JavaScript

### 2. Language Files

**Location:** `i18n/` directory

Translation files follow a simple key-value structure:

```php
<?php
return [
    'app_title' => 'Image Management System',
    'nav_gallery' => 'Gallery',
    // ... more translations
];
```

**Files:**
- `i18n/en.php` - English (136 keys)
- `i18n/es.php` - Spanish (136 keys)
- `i18n/fr.php` - French (136 keys)
- `i18n/de.php` - German (136 keys)
- `i18n/zh.php` - Simplified Chinese (136 keys)

### 3. Frontend (JavaScript)

**Location:** `public/js/i18n.js`

JavaScript class handles:
- Client-side language detection
- Translation loading from API
- Dynamic UI text updates
- Language persistence in localStorage

**Key Methods:**
- `i18n.setLanguage(lang)` - Change language
- `i18n.t(key)` - Get translation
- `i18n.updatePageText()` - Refresh all UI text

## Language Detection Priority

The system detects the user's preferred language in the following order:

1. **URL Parameter** - `?lang=es` (highest priority)
2. **Session** - Persisted during user session
3. **Cookie** - `language` cookie (30 days expiration)
4. **Browser Accept-Language** - Automatic detection
5. **Default** - English (fallback)

## Usage

### For PHP (Backend)

Initialize i18n in your PHP files:

```php
// Initialize (automatically detects language)
I18n::init();

// Get translations
echo I18n::t('app_title');

// Set specific language
I18n::setLanguage('es');

// Get current language
$lang = I18n::getCurrentLanguage();
```

### For HTML (Views)

Use `data-i18n` attributes in HTML to mark translatable elements:

```html
<!-- Text content -->
<h1 data-i18n="app_title">Image Management System</h1>

<!-- Placeholder text -->
<input type="text" placeholder="Search images..." data-i18n="nav_search">

<!-- Button labels -->
<button data-i18n="upload_button">Upload Images</button>
```

### For JavaScript

Use the global `i18n` object:

```javascript
// Get translation
const text = i18n.t('app_title');

// Change language
await i18n.setLanguage('es');

// Get all supported languages
const langs = i18n.getSupportedLanguages();

// Listen for language changes
document.addEventListener('i18nChanged', function() {
    console.log('Language changed to:', i18n.getCurrentLanguage());
});
```

## API Endpoints

### Get Translations

```
GET /api.php?action=get_translations&lang=en
```

Returns all translations for a specific language as JSON.

### Set Language

```
POST /api.php?action=set_language
Body: { "language": "es" }
```

Sets the current language (saves to session and cookie).

### Get Supported Languages

```
GET /api.php?action=get_supported_languages
```

Returns list of supported language codes and names.

### Get Current Language

```
GET /api.php?action=get_current_language
```

Returns current language code and name.

## Adding New Languages

### Step 1: Create Translation File

Create a new file in `i18n/` with the language code (e.g., `i18n/pt.php` for Portuguese):

```php
<?php
/**
 * Portuguese Language File
 * Language: Portuguese (pt)
 */

return [
    'app_title' => 'Sistema de Gestão de Imagens',
    'app_subtitle' => 'Envie, organize e edite suas imagens',
    // ... all 136 keys
];
```

Ensure you translate ALL keys from an existing language file (copy English and translate each value).

### Step 2: Update I18n Class

Add the new language code to `I18n.php`:

```php
private static $supportedLanguages = ['en', 'es', 'fr', 'de', 'zh', 'pt'];
```

### Step 3: Update JavaScript

Add language to `public/js/i18n.js`:

```javascript
this.supportedLanguages = ['en', 'es', 'fr', 'de', 'zh', 'pt'];
this.languageNames = {
    'en': 'English',
    'es': 'Español',
    'fr': 'Français',
    'de': 'Deutsch',
    'zh': '简体中文',
    'pt': 'Português'
};
```

### Step 4: Update HTML Selector

Update the language select in `public/index.php`:

```html
<select id="languageSelect" class="language-select">
    <option value="en">English</option>
    <option value="es">Español</option>
    <option value="fr">Français</option>
    <option value="de">Deutsch</option>
    <option value="zh">简体中文</option>
    <option value="pt">Português</option>
</select>
```

## Translation Keys

Total: **136 translation keys** organized by feature

### Categories:

- **Header & Navigation** (11 keys) - `app_title`, `nav_gallery`, etc.
- **Gallery View** (7 keys) - `gallery_title`, `gallery_loading`, etc.
- **Upload View** (14 keys) - `upload_title`, `upload_success`, etc.
- **Login View** (11 keys) - `login_title`, `login_oauth_google`, etc.
- **Register View** (8 keys) - `register_title`, `register_weak_password`, etc.
- **Settings View** (18 keys) - `settings_profile`, `settings_2fa_enable`, etc.
- **Image Modal** (16 keys) - `modal_crop`, `modal_save_success`, etc.
- **Filters** (8 keys) - `filter_grayscale`, `filter_blur`, etc.
- **Folders** (7 keys) - `folder_create`, `folder_created`, etc.
- **Admin** (13 keys) - `admin_users`, `admin_storage`, etc.
- **Errors & Messages** (18 keys) - `error_not_found`, `success_operation`, etc.

## Performance Considerations

1. **Lazy Loading** - Translations are loaded from API only when needed
2. **Caching** - localStorage stores user's language preference
3. **Minimal Overhead** - i18n.js is ~6KB unminified, ~2KB minified
4. **Session Persistence** - Language choice persists across sessions

## Browser Compatibility

- **Modern Browsers** - Chrome, Firefox, Safari, Edge (all versions)
- **Mobile** - iOS Safari, Android Chrome, Mobile Firefox
- **Legacy** - IE11+ supported (with polyfills if needed)

## Troubleshooting

### Translations Not Loading

1. Check that `i18n.js` is loaded before `app.js`
2. Verify language files exist in `i18n/` directory
3. Check browser console for errors
4. Verify API endpoints are accessible

### Language Not Persisting

1. Check that cookies are enabled
2. Verify session is working (`check_status` endpoint)
3. Clear browser cache and localStorage
4. Ensure `set_language` API endpoint returns success

### Character Encoding Issues (Chinese)

1. Ensure PHP files are saved as UTF-8
2. Verify database collation is UTF-8
3. Check `<meta charset="UTF-8">` in HTML head
4. Verify HTTP headers include `Content-Type: application/json; charset=utf-8`

## Future Enhancements

- [ ] Server-side rendering with language-specific URLs
- [ ] RTL language support (Arabic, Hebrew)
- [ ] Date/Time formatting per language
- [ ] Number formatting per locale
- [ ] Pluralization support
- [ ] Translation management admin interface
- [ ] Community translation platform integration

## Translation Status

| Language | Status | Keys | Last Updated |
|----------|--------|------|--------------|
| English | ✅ Complete | 136 | Nov 2025 |
| Spanish | ✅ Complete | 136 | Nov 2025 |
| French | ✅ Complete | 136 | Nov 2025 |
| German | ✅ Complete | 136 | Nov 2025 |
| Chinese (Simplified) | ✅ Complete | 136 | Nov 2025 |

## Contributing Translations

To contribute new languages or improve existing translations:

1. Fork the repository
2. Create a new language file in `i18n/`
3. Follow the key structure from existing files
4. Ensure cultural appropriateness and accuracy
5. Submit a pull request with your translations

## Notes

- All text content in the application should use translation keys
- User-generated content (filenames, descriptions) should NOT be translated
- Admin interface uses same translation system as main app
- API error messages are intentionally English for security
