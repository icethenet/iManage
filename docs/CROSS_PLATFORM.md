# Cross-Platform Compatibility

## ✅ Fully Compatible: Windows, Linux, macOS

This image management system is designed to work seamlessly across all major operating systems.

## Platform Support Matrix

| Feature | Windows | Linux | macOS |
|---------|---------|-------|-------|
| PHP Backend | ✅ | ✅ | ✅ |
| MySQL Database | ✅ | ✅ | ✅ |
| File Uploads | ✅ | ✅ | ✅ |
| Image Manipulation (GD) | ✅ | ✅ | ✅ |
| Web Installer | ✅ | ✅ | ✅ |
| Share Links | ✅ | ✅ | ✅ |
| Security Features | ✅ | ✅ | ✅ |

## Cross-Platform Code Design

### Path Handling
All filesystem operations use `DIRECTORY_SEPARATOR` constant which automatically adjusts:
- **Windows**: Backslash `\`
- **Linux/macOS**: Forward slash `/`

```php
// Example: Automatically works on all platforms
$path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads';
```

### Database
MySQL/MariaDB syntax is identical across platforms:
- No platform-specific SQL
- UTF-8 encoding (utf8mb4) works universally
- Schema file works on all systems

### PHP Extensions
Required extensions are available on all platforms:
- `pdo_mysql` - Database connectivity
- `gd` - Image manipulation
- `session` - Authentication
- `json` - API responses

## Installation by Platform

### Windows

**Requirements:**
- XAMPP, WAMP, or standalone Apache
- PHP 8.0+ with GD
- MySQL 5.7+

**Setup:**
```powershell
# Set permissions (PowerShell as Administrator)
icacls "public\uploads" /grant "NT AUTHORITY\SYSTEM:(OI)(CI)F" /T

# Start services
# (XAMPP Control Panel or services.msc)

# Access installer
http://localhost/imanage/public/install.php
```

**Web Server:**
- Apache with `mod_rewrite` enabled
- IIS with URL Rewrite module (optional)

### Linux

**Requirements:**
- Apache or Nginx
- PHP 8.0+ with GD, PDO, MySQL extensions
- MySQL 5.7+ or MariaDB 10.3+

**Setup (Ubuntu/Debian):**
```bash
# Install dependencies
sudo apt update
sudo apt install apache2 mysql-server php8.1 php8.1-gd php8.1-mysql php8.1-mbstring

# Enable Apache modules
sudo a2enmod rewrite
sudo systemctl restart apache2

# Set permissions
sudo chown -R www-data:www-data /var/www/html/imanage
sudo chmod -R 775 /var/www/html/imanage/public/uploads

# Access installer
http://localhost/imanage/public/install.php
```

**Setup (CentOS/RHEL):**
```bash
# Install dependencies
sudo yum install httpd mysql-server php php-gd php-pdo php-mysqlnd

# Enable Apache modules
sudo systemctl enable httpd
sudo systemctl start httpd

# Set SELinux context (if enabled)
sudo chcon -R -t httpd_sys_rw_content_t /var/www/html/imanage/public/uploads
sudo setsebool -P httpd_unified 1

# Set permissions
sudo chown -R apache:apache /var/www/html/imanage
sudo chmod -R 775 /var/www/html/imanage/public/uploads
```

### macOS

**Requirements:**
- Built-in Apache or MAMP
- PHP 8.0+ with GD (may need to install via Homebrew)
- MySQL 5.7+ or MariaDB

**Setup:**
```bash
# Install Homebrew (if not installed)
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"

# Install PHP and MySQL
brew install php@8.1 mysql

# Start services
brew services start mysql
brew services start php@8.1

# Enable Apache (already installed on macOS)
sudo apachectl start

# Set permissions
sudo chown -R _www:_www /Library/WebServer/Documents/imanage
sudo chmod -R 775 /Library/WebServer/Documents/imanage/public/uploads

# Access installer
http://localhost/imanage/public/install.php
```

## Web Server Configuration

### Apache (.htaccess)

Already configured in `public/.htaccess`:
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

Works identically on Windows and Linux.

### Nginx (if used instead of Apache)

Create `/etc/nginx/sites-available/imanage`:
```nginx
server {
    listen 80;
    server_name localhost;
    root /var/www/html/imanage/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location ~ /\.ht {
        deny all;
    }

    location ^~ /uploads/ {
        # Allow serving uploaded images
    }
}
```

## File System Differences

### Path Separators
**Handled automatically** - code uses `DIRECTORY_SEPARATOR`

### Line Endings
- **Windows**: CRLF (`\r\n`)
- **Linux/macOS**: LF (`\n`)

Git handles this automatically with `.gitattributes`:
```
* text=auto
*.php text eol=lf
*.sql text eol=lf
```

### Case Sensitivity
- **Windows**: Case-insensitive filesystem
- **Linux**: Case-sensitive filesystem

**Recommendation**: Always use consistent casing in filenames and paths.

## Database Compatibility

MySQL/MariaDB works identically across platforms:

```bash
# Windows
mysql -u root -p image_gallery < database/schema.sql

# Linux/macOS
mysql -u root -p image_gallery < database/schema.sql
```

Same commands, same results.

## PHP Configuration

Ensure these settings in `php.ini` (all platforms):
```ini
upload_max_filesize = 5M
post_max_size = 6M
memory_limit = 128M
file_uploads = On
extension=gd
extension=pdo_mysql
```

### Finding php.ini location:
```bash
php --ini
```

## Testing Cross-Platform

Test scripts work on all platforms:
```bash
# All platforms use same commands
php tools/test_schema.php
php tools/test_security_simple.php
php tools/test_share_link.php
```

## Known Platform Differences

### Session Storage
- **Windows**: `C:\Windows\Temp` by default
- **Linux**: `/var/lib/php/sessions` or `/tmp`
- **macOS**: `/var/tmp` or `/tmp`

Sessions work automatically on all platforms.

### Upload Directory
- **Windows**: `C:\www\www\imanage\public\uploads`
- **Linux**: `/var/www/html/imanage/public/uploads`
- **macOS**: `/Library/WebServer/Documents/imanage/public/uploads`

Paths are relative, so code works everywhere.

## Migration Between Platforms

### From Windows to Linux:
1. Export database: `mysqldump -u root -p image_gallery > backup.sql`
2. Copy files to Linux server
3. Fix line endings (if needed): `dos2unix *.php`
4. Set Linux permissions: `chmod -R 775 public/uploads`
5. Import database: `mysql -u root -p image_gallery < backup.sql`

### From Linux to Windows:
1. Export database: `mysqldump -u root -p image_gallery > backup.sql`
2. Copy files to Windows server
3. Set Windows permissions: `icacls public\uploads /grant Users:F /T`
4. Import database: `mysql -u root -p image_gallery < backup.sql`

## Docker Support

The application is Docker-ready:

```dockerfile
FROM php:8.1-apache

# Install extensions
RUN docker-php-ext-install pdo pdo_mysql gd

# Enable Apache modules
RUN a2enmod rewrite

# Copy application
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html/public/uploads
RUN chmod -R 775 /var/www/html/public/uploads
```

## Performance Notes

Performance is similar across platforms, but:
- **Linux** typically has slightly better PHP performance
- **Windows** with NTFS compression can be slower
- **macOS** APFS performs well

All differences are negligible for this application.

## Security Considerations

Security features work identically on all platforms:
- Session-based authentication
- Password hashing (bcrypt)
- SQL injection protection (PDO prepared statements)
- File upload validation

## Conclusion

✅ **100% Cross-Platform Compatible**

The codebase is designed with cross-platform compatibility in mind:
- Uses PHP's built-in constants for path handling
- Database queries are platform-agnostic
- No OS-specific system calls
- Tested on Windows (primary development)
- Compatible with Linux (production standard)
- Works on macOS (developer environments)

**No code changes needed** to move between platforms - just set appropriate file permissions and configure your web server!
