# iManage - Image Management System

A powerful, mobile-first image management system with advanced editing capabilities, folder organization, and secure sharing features.

![PHP](https://img.shields.io/badge/PHP-8.0%2B-blue)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-orange)
![License](https://img.shields.io/badge/license-MIT-green)
![Platform](https://img.shields.io/badge/platform-Windows%20%7C%20Linux%20%7C%20macOS-lightgrey)

## ✨ Features

### Core Functionality
- 📤 **Upload & Organize** - Upload images and organize them into custom folders
- 🔍 **Smart Search** - Full-text search across image names and folders
- 🖼️ **Image Gallery** - Beautiful, responsive grid layout with hover previews
- 📱 **Mobile-First Design** - Optimized for all devices (320px to desktop)
- 🔐 **User Authentication** - Secure login with session management
- 🔑 **OAuth 2.0 Social Login** - Sign in with Google, Facebook, GitHub, or Microsoft

### Image Editing Tools
- ✂️ **Crop Tool** - Interactive canvas-based cropping with real-time preview
- 🔄 **Rotate & Flip** - 90° rotation and horizontal/vertical flip
- 📏 **Resize** - Scale images to specific dimensions
- 🎨 **Filters** - Grayscale, brightness, contrast, sharpen, color overlay
- 💾 **Non-Destructive** - Keep pristine copies, revert anytime

### Advanced Features
- 🔗 **Share Links** - Generate secure, shareable links for public viewing
- 🔒 **Security** - Authentication-based ownership, XSS protection, SQL injection prevention
- 🌐 **Cross-Platform** - Works identically on Windows, Linux, and macOS
- ⚡ **RESTful API** - JSON-based API for all operations
- 📦 **Auto Thumbnails** - Automatic thumbnail generation for fast loading
- 🎭 **OAuth Integration** - Support for Google, Facebook, GitHub, Microsoft login

## 🚀 Quick Start & Installation

### Requirements
- PHP 8.0+ (GD extension enabled)
- MySQL 5.7+ or MariaDB 10.3+
- Apache (mod_rewrite) or Nginx equivalent rewrite rules
- Works on Windows, Linux, macOS

### Option 1: Web Installer (Recommended)
1. Clone repo:
   ```bash
   git clone https://github.com/icethenet/iManage.git
   cd iManage
   ```
2. Point your web server's document root to `public/`.
3. Navigate to:
   ```
   http://localhost/imanage/public/install.php
   ```
4. Step 1 – Database Connection:
   - Host: `localhost`
   - User: `root` (or your DB user)
   - Password: (your password)
   - Click "Test Connection & Continue".
5. Step 2 – Create Database:
   - Database name: `imanage` (or your choice)
   - (Optional) Include sample data.
   - Wizard creates tables, default folders, admin user.
6. Step 3 – Verify:
   - Wizard confirms schema/tables.
   - Click "Complete Installation".
7. First Login:
   - Visit `http://localhost/imanage/public/`
   - Username: `admin` / Password: `admin123` (change immediately!)

### Option 2: Manual Installation
```bash
# Create database
mysql -u root -p -e "CREATE DATABASE imanage CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Import base schema
mysql -u root -p imanage < database/schema.sql

# (Optional) Run additional migrations
mysql -u root -p imanage < database/migrations/add_share_token_column.sql 2>/dev/null || true
mysql -u root -p imanage < database/migrations/add_oauth_support.sql 2>/dev/null || true

# Configure database connection
cp config/database.php.example config/database.php
# Edit credentials inside config/database.php
```

### File/Directory Permissions
Windows (PowerShell as Administrator):
```powershell
icacls "public\uploads" /grant "NT AUTHORITY\SYSTEM:(OI)(CI)F" /T
icacls "logs" /grant "NT AUTHORITY\SYSTEM:(OI)(CI)F" /T
```
Linux/macOS:
```bash
sudo chmod -R 775 public/uploads
sudo chown -R www-data:www-data public/uploads
sudo chmod -R 775 logs
sudo chown -R www-data:www-data logs
# Replace www-data with apache/nginx user if needed
```

### Post-Installation Checklist
1. Change admin password immediately.
2. (Optional) Enable OAuth providers (see below).
3. Upload a test image and verify manipulation tools.
4. Test share link creation and public access.
5. Back up database and `public/uploads/` regularly.

### Verification & Test Scripts
```bash
php tools/test_schema.php            # Validate DB schema
php tools/test_security_simple.php   # Basic security checks
php tools/test_share_link.php        # Share link functionality
php tools/verify_crop_tool.php       # Crop tool validation
```

### OAuth Social Login (Optional)
```bash
cp config/oauth.php.example config/oauth.php
mysql -u root -p imanage < database/migrations/add_oauth_support.sql
```
Then add provider keys to `config/oauth.php` and set `'enabled' => true`. Full guide: [OAuth Setup](docs/OAUTH_SETUP.md).

### Troubleshooting
Problem: "Schema file not found" → Verify `database/schema.sql` exists.
Problem: "Connection failed" → Check MySQL service + credentials (`mysql -u root -p`).
Problem: "Permission denied on uploads" → Re-run permission commands above.
Problem: "share_token column not found" → Run `php tools/add_share_token_column.php`.
Problem: "Can't save manipulated images" → Run `php tools/check_file_paths.php` and verify permissions.
Problem: OAuth redirect loop → Confirm `state` parameter stored in session and callback domain matches provider configuration.

### Quick Reference
| Task | Command/URL |
|------|-------------|
| Web Installer | `http://localhost/imanage/public/install.php` |
| Login | `http://localhost/imanage/public/` |
| Test Schema | `php tools/test_schema.php` |
| Test Security | `php tools/test_security_simple.php` |
| Add Share Column | `php tools/add_share_token_column.php` |
| Backup DB | `mysqldump -u root -p imanage > backup.sql` |
| Restore DB | `mysql -u root -p imanage < backup.sql` |
| Verify Crop Tool | `php tools/verify_crop_tool.php` |

### Database & Schema Notes
Recent additions include share token support and OAuth tables (see `database/migrations/`). Ensure migrations run after base schema import when enabling optional features.

### Default Admin Credentials
`admin` / `admin123` (change immediately). Use the settings area to update password.

## 📖 Documentation

### Main Guides
- **Cross-Platform Guide** (`docs/CROSS_PLATFORM.md`) - OS specifics
- **Database Documentation** (`database/README.md`) - Schema & maintenance

### Feature Documentation
- **Share Link Feature** (`docs/SHARE_LINK_FEATURE.md`) - Public sharing system
- **Crop Tool Guide** (`docs/CROP_TOOL_QUICKSTART.md`) - Cropping walkthrough
- **Security Overview** (`docs/SECURITY_HARDENING_SUMMARY.md`) - Hardening summary
- **OAuth Setup Guide** (`docs/OAUTH_SETUP.md`) - Provider configuration

### Release Notes
- **Mobile-First CSS** (`docs/release-notes/MOBILEFIRST.txt`)
- **Security Fixes (Nov 2025)** (`docs/release-notes/SECURITY_FIX_NOV_2025.txt`)
- **Platform Compatibility** (`docs/release-notes/PLATFORM_COMPATIBILITY.txt`)

## 🏗️ Architecture

```
iManage/
├── app/
│   ├── Controllers/     # Request handlers (User, Image, Folder, OAuth)
│   ├── Models/          # Database models
│   └── Utils/           # Image manipulation, upload handling, OAuth
├── config/
│   ├── app.php          # Application settings
│   ├── database.php     # Database credentials (not in repo)
│   └── oauth.php        # OAuth provider configuration (not in repo)
├── database/
│   ├── schema.sql       # Database schema
│   └── migrations/      # Database migrations (OAuth, etc.)
├── public/              # Web root (point your server here)
│   ├── index.php        # Main application
│   ├── api.php          # API endpoint
│   ├── share.php        # Public share viewer
│   ├── oauth-login.php  # OAuth initiation
│   ├── oauth-callback.php # OAuth callback handler
│   ├── css/             # Stylesheets
│   ├── js/              # JavaScript
│   └── uploads/         # User uploads (not in repo)
└── tools/               # Development & maintenance scripts
```

## 🎨 Mobile-First Design

The entire UI is built with mobile-first principles:
- Base styles target 320px+ screens
- Progressive enhancement for tablets (600px+) and desktops (1024px+)
- 44px minimum touch targets (iOS standard)
- Touch device detection for optimal interaction
- Responsive grids, modals, and navigation

## 🔒 Security Features

- ✅ **Authentication Required** - All destructive operations require login
- ✅ **Ownership Verification** - Users can only modify their own images
- ✅ **SQL Injection Protection** - PDO prepared statements throughout
- ✅ **XSS Prevention** - Proper output escaping
- ✅ **Session Security** - 30-minute timeout, httponly cookies
- ✅ **Password Hashing** - bcrypt with cost factor 12
- ✅ **Share Token Security** - Cryptographically secure random tokens
- ✅ **OAuth CSRF Protection** - State parameter validation for OAuth flow

## 🖼️ Image Operations

All operations preserve the original image in `pristine/` folder:

| Operation | Description |
|-----------|-------------|
| **Crop** | Interactive canvas-based cropping |
| **Resize** | Scale to specific width/height |
| **Rotate** | 90° clockwise rotation |
| **Flip Horizontal** | Mirror image left-right |
| **Flip Vertical** | Mirror image top-bottom |
| **Grayscale** | Convert to black & white |
| **Brightness** | Adjust image brightness (-100 to +100) |
| **Contrast** | Adjust image contrast (-100 to +100) |
| **Sharpen** | Apply sharpening filter |
| **Color Overlay** | Apply color tint with opacity |

## 🌐 Cross-Platform

Works identically on:
- **Windows** - XAMPP, WampServer, IIS
- **Linux** - Apache, Nginx, LAMP stack
- **macOS** - MAMP, built-in Apache

Code uses `DIRECTORY_SEPARATOR` throughout - no platform-specific paths.

## 🛠️ API Endpoints

### Authentication
```bash
POST /api.php?action=login               # Username/password login
POST /api.php?action=logout              # Logout
GET  /api.php?action=check_status        # Check login status
GET  /oauth-login.php?provider={name}    # OAuth social login
GET  /oauth-callback.php                 # OAuth callback (automatic)
```

### Images
```bash
GET    /api.php?action=images              # List all images
POST   /api.php?action=upload              # Upload image
PUT    /api.php?action=update&id={id}      # Update metadata
DELETE /api.php?action=delete&id={id}      # Delete image
POST   /api.php?action=manipulate&id={id}  # Apply filters
POST   /api.php?action=revert&id={id}      # Revert to pristine
```

### Sharing
```bash
GET /api.php?action=shared&token={token}   # Public share access
GET /share.php?token={token}               # Public share viewer
```

### Folders
```bash
GET    /api.php?action=folders             # List folders
POST   /api.php?action=createFolder        # Create folder
DELETE /api.php?action=deleteFolder&id={id} # Delete folder
```

## 🧪 Testing Tools

Located in `tools/` directory:
- `test_schema.php` - Validate database schema
- `test_security_simple.php` - Security audit
- `test_share_link.php` - Share functionality test
- `verify_crop_tool.php` - Crop tool validation

## 🤝 Contributing

Contributions are welcome! Please:
1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License - see LICENSE file for details.

## 🙏 Acknowledgments

- Built with vanilla JavaScript (no frameworks)
- Uses HTML5 Canvas API for image manipulation
- GD Library for server-side image processing
- Mobile-first CSS with progressive enhancement
- OAuth 2.0 integration with Google, Facebook, GitHub, Microsoft

## 📧 Support

For issues, questions, or suggestions:
- Open an issue on GitHub
- Check the [documentation](docs/)
- Review [security guidelines](docs/SECURITY_HARDENING_SUMMARY.md)

## 📱 Progressive Web App (PWA)

The app now includes baseline PWA support for installability and limited offline access.

Implemented:
- Web App Manifest (`public/manifest.json`)
- Service Worker (`public/service-worker.js`) with precache + runtime image caching
- Offline fallback page (`public/offline.html`)
- Theme color + manifest link in `index.php`
- Automatic registration in `public/js/app.js`
- Generated placeholder icons via `php tools/generate_pwa_icons.php` (files in `public/img/icons/`). Replace with branded assets when ready.

What Works Offline:
- Previously visited UI shell (HTML/CSS/JS)
- Cached images already viewed
- Offline page display when navigating without connectivity

Requires Online:
- Authentication, uploads, image manipulation (server-side GD), new API queries

How to Install:
1. Serve over HTTPS (localhost exempt).
2. Visit the site in Chrome/Edge/Firefox (Android) or Chrome (Desktop).
3. Use browser "Install App" prompt or three-dot menu > Install.

Next Enhancement Ideas:
- Add IndexedDB cache for recent image metadata
- Background Sync for queued uploads
- Versioned cache purge strategy
- Workbox integration for easier runtime strategies
- Push notifications for completed remote operations (optional)
 - Fine-grained image transformation caching layer

Icon Note: Add real PNG icons (`icon-192.png`, `icon-512.png`, `icon-512-maskable.png`) to `public/img/icons/` (placeholder README provided).

## 🗺️ Roadmap

- [x] OAuth 2.0 social login (Google, Facebook, GitHub, Microsoft)
- [x] Two-factor authentication (2FA)
- [x] Batch image operations
- [ ] Image metadata (EXIF) display
- [ ] Advanced filters (blur, sepia, vignette)
- [x] Drag-and-drop upload
- [x] Progressive Web App (PWA)
- [ ] Video thumbnail support
- [ ] Multi-language support

---

**Made with ❤️ for photographers, designers, and content creators**
