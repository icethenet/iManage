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

## 🚀 Quick Start

### Requirements
- PHP 8.0 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Apache/Nginx with mod_rewrite
- GD Library (for image manipulation)

### Installation

**Option 1: Web Installer (Recommended)**
```bash
# Clone the repository
git clone https://github.com/icethenet/iManage.git
cd iManage

# Configure your web server to point to the 'public' directory
# Navigate to: http://localhost/install.php
# Follow the installation wizard
```

**Option 2: Manual Setup**
```bash
# 1. Clone and configure database
git clone https://github.com/icethenet/iManage.git
cd iManage

# 2. Create database
mysql -u root -p
CREATE DATABASE imanage CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit;

# 3. Import schema
mysql -u root -p imanage < database/schema.sql

# 4. Configure database connection
cp config/database.php.example config/database.php
# Edit config/database.php with your credentials

# 5. Set permissions
chmod 755 public/uploads
chmod 755 logs

# 6. Configure web server
# Point document root to: /path/to/iManage/public
```

### Default Login
- **Username:** `admin`
- **Password:** `admin123`
- ⚠️ **Change immediately after first login!**

## 📖 Documentation

### Main Guides
- **[Installation Guide](INSTALLATION.md)** - Detailed setup instructions
- **[Cross-Platform Guide](docs/CROSS_PLATFORM.md)** - Windows/Linux/macOS specifics
- **[Database Documentation](database/README.md)** - Schema and maintenance

### Feature Documentation
- **[Share Link Feature](docs/SHARE_LINK_FEATURE.md)** - Secure sharing system
- **[Crop Tool Guide](CROP_TOOL_QUICKSTART.md)** - Interactive cropping tutorial
- **[Security Overview](docs/SECURITY_HARDENING_SUMMARY.md)** - Security features

### Release Notes
- **[Mobile-First CSS](MOBILEFIRST.txt)** - Responsive design implementation
- **[Security Fixes](SECURITY_FIX_NOV_2025.txt)** - November 2025 security updates
- **[Platform Compatibility](PLATFORM_COMPATIBILITY.txt)** - Cross-platform notes

## 🏗️ Architecture

```
iManage/
├── app/
│   ├── Controllers/     # Request handlers (User, Image, Folder)
│   ├── Models/          # Database models
│   └── Utils/           # Image manipulation, upload handling
├── config/
│   ├── app.php          # Application settings
│   └── database.php     # Database credentials (not in repo)
├── database/
│   └── schema.sql       # Database schema
├── public/              # Web root (point your server here)
│   ├── index.php        # Main application
│   ├── api.php          # API endpoint
│   ├── share.php        # Public share viewer
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
POST /api.php?action=login
POST /api.php?action=logout
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

## 📧 Support

For issues, questions, or suggestions:
- Open an issue on GitHub
- Check the [documentation](docs/)
- Review [security guidelines](docs/SECURITY_HARDENING_SUMMARY.md)

## 🗺️ Roadmap

- [ ] Batch image operations
- [ ] Image metadata (EXIF) display
- [ ] Advanced filters (blur, sepia, vignette)
- [ ] Drag-and-drop upload
- [ ] Progressive Web App (PWA)
- [ ] Video thumbnail support
- [ ] Multi-language support

---

**Made with ❤️ for photographers, designers, and content creators**
