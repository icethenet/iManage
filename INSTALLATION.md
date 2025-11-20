# Installation Guide - Image Management System

## Prerequisites
- PHP 8.0+ with GD extension
- MySQL 5.7+ or MariaDB 10.3+
- Apache with mod_rewrite (or Nginx with proper configuration)
- **Works on both Windows and Linux**

## Installation Steps

### 1. Web Installer (Easiest)

1. **Access the installer**:
   ```
   http://localhost/imanage/public/install.php
   ```

2. **Step 1: Database Connection**
   - Host: `localhost`
   - Username: `root` (or your MySQL user)
   - Password: (your MySQL password)
   - Click "Test Connection & Continue"

3. **Step 2: Create Database**
   - Database Name: `image_gallery` (or your choice)
   - Check "Include sample data" for demo
   - Click "Create Database & Continue"
   
   This will:
   - Create the database
   - Create all tables (users, images, folders)
   - Add share_token column for sharing feature
   - Create default admin user (username: `admin`, password: `admin123`)
   - Create sample folders

4. **Step 3: Verify Installation**
   - Click "Verify Installation"
   - Confirm tables were created
   - Click "Complete Installation"

5. **First Login**:
   ```
   http://localhost/imanage/public/
   ```
   - Username: `admin`
   - Password: `admin123`
   - **IMPORTANT**: Change this password after first login!

### 2. Manual Installation

If you prefer command-line setup:

```bash
# 1. Create database
mysql -u root -p -e "CREATE DATABASE image_gallery CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2. Import schema
mysql -u root -p image_gallery < database/schema.sql

# 3. Create config file manually (or use installer)
cp config/database.php.example config/database.php
# Edit config/database.php with your credentials
```

### 3. Set Up File Permissions

**Windows** (PowerShell as Administrator):
```powershell
# Grant write access to uploads folder
icacls "public\uploads" /grant "NT AUTHORITY\SYSTEM:(OI)(CI)F" /T

# Verify permissions
icacls "public\uploads"
```

**Linux/Unix**:
```bash
# Grant write access to uploads folder
chmod -R 775 public/uploads
chown -R www-data:www-data public/uploads

# Or if using a different web server user (e.g., apache, nginx)
sudo chown -R apache:apache public/uploads
# Or for your user + web server group
sudo chown -R $USER:www-data public/uploads
```

### 4. Verify Installation

```bash
# Test database connection and schema
php tools/test_schema.php

# Test security (all tests should pass)
php tools/test_security_simple.php

# Test share links (if you have images)
php tools/test_share_link.php
```

## Post-Installation

### Change Admin Password
1. Log in as admin
2. Go to user settings
3. Change password immediately

### Upload First Image
1. Click "Upload" tab
2. Select image (JPG, PNG, GIF, WebP • Max 5MB)
3. Add title and description
4. Choose folder or create new one
5. Click "Upload Image"

### Test Image Manipulation
1. Open uploaded image
2. Try tools: crop, rotate, resize, effects
3. Click "Apply" to save changes
4. Use "Revert to Original" to undo

### Test Share Links
1. Open any image
2. Check "Share this image publicly"
3. Copy the generated share link
4. Open link in incognito/different browser
5. Verify image displays without login

## Database Schema Details

### Tables Created
- **users** - User accounts (admin created by default)
- **folders** - Image organization (4 default folders)
- **images** - Image metadata with sharing support

### New Columns (Nov 2025)
- `images.shared` - Boolean flag for public sharing
- `images.share_token` - Unique token for share URLs

### Default Data
- **Admin User**: username `admin`, password `admin123`
- **Folders**: Default, Vacation, Projects, Nature

## Configuration Files

### database.php (auto-generated)
```php
<?php
return [
    'host' => 'localhost',
    'database' => 'image_gallery',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
];
```

### app.php (existing)
Contains application settings:
- Upload directory
- Max file size
- Allowed file types
- Image quality settings

## Troubleshooting

### Installer Issues

**Problem**: "Schema file not found"
```bash
# Solution: Verify schema exists
ls database/schema.sql
```

**Problem**: "Connection failed"
- Check MySQL is running
- Verify credentials
- Test: `mysql -u root -p`

**Problem**: "Permission denied on uploads"
```powershell
# Run as Administrator
icacls "public\uploads" /grant "NT AUTHORITY\SYSTEM:(OI)(CI)F" /T
```

### Runtime Issues

**Problem**: "share_token column not found"
```bash
# Add missing column
php tools/add_share_token_column.php
```

**Problem**: "Can't save manipulated images"
- Check filesystem permissions
- Verify upload directories exist
- Run: `php tools/check_file_paths.php`

**Problem**: "Authentication required" for share links
- This is correct! Share links use: `share.php?share={token}`
- Regular index.php requires login

## Features Included

### Core Features
✅ Image upload (JPG, PNG, GIF, WebP)
✅ Folder organization
✅ Search functionality
✅ User authentication
✅ Multi-user support

### Image Manipulation
✅ Crop (interactive canvas tool)
✅ Resize (maintain aspect ratio)
✅ Rotate (90° increments)
✅ Flip (horizontal/vertical)
✅ Effects (grayscale, brightness, contrast, sharpen)
✅ Color overlay (new!)

### Sharing
✅ Public share links
✅ Unique share tokens
✅ Copy to clipboard button
✅ Standalone share view page
✅ Read-only access for shared links

### Security
✅ Authentication required for modifications
✅ Authorization (users own their images)
✅ Session-based security
✅ Bcrypt password hashing
✅ Protected API endpoints

## Next Steps

1. **Change admin password** (critical!)
2. **Create additional users** if needed
3. **Upload and organize images**
4. **Test all features** (crop, share, etc.)
5. **Set up backups** (database + uploads folder)

## Support

- Database info: `database/README.md`
- Security info: `SECURITY_FIX_NOV_2025.txt`
- Crop tool: `CROP_TOOL_QUICKSTART.md`
- Share feature: `docs/SHARE_LINK_FEATURE.md`

## Quick Reference

| Task | Command/URL |
|------|-------------|
| Install | `http://localhost/imanage/public/install.php` |
| Login | `http://localhost/imanage/public/` |
| Test Schema | `php tools/test_schema.php` |
| Test Security | `php tools/test_security_simple.php` |
| Add Share Column | `php tools/add_share_token_column.php` |
| Backup DB | `mysqldump -u root -p image_gallery > backup.sql` |
| Restore DB | `mysql -u root -p image_gallery < backup.sql` |

---

**Installation complete!** Enjoy your image management system with sharing, manipulation, and security features.
