# Database Setup and Schema Information

## Quick Start

### Option 1: Web Installer (Recommended)
1. Open `http://localhost/imanage/public/install.php` in your browser
2. Follow the 3-step installation wizard:
   - **Step 1**: Test database connection
   - **Step 2**: Create database and tables
   - **Step 3**: Verify installation
3. Default admin credentials will be created

### Option 2: Manual Setup
```sql
-- Create database
CREATE DATABASE image_gallery CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Import schema
mysql -u root -p image_gallery < database/schema.sql
```

## Database Schema

### Tables Overview

#### `users` - User accounts
- `id` - Primary key
- `username` - Unique username (varchar 50)
- `password_hash` - Bcrypt password hash (varchar 255)
- `created_at` - Account creation timestamp

#### `folders` - Image folder organization
- `id` - Primary key
- `user_id` - Owner (foreign key to users.id)
- `parent_id` - Parent folder for nested structure (nullable)
- `name` - Folder name (varchar 100, unique)
- `description` - Optional description (text)
- `created_at` - Creation timestamp
- `updated_at` - Last modified timestamp

#### `images` - Image metadata and references
- `id` - Primary key
- `user_id` - Owner (foreign key to users.id)
- `title` - Image title (varchar 255)
- `description` - Optional description (longtext)
- `filename` - Stored filename (varchar 255, unique)
- `original_name` - Original upload filename (varchar 255)
- `mime_type` - MIME type (image/jpeg, image/png, etc.)
- `file_size` - File size in bytes (int)
- `width` - Image width in pixels (int)
- `height` - Image height in pixels (int)
- `folder` - Folder name (varchar 100, default 'default')
- `tags` - Comma-separated tags (varchar 500)
- `created_at` - Upload timestamp
- `updated_at` - Last modified timestamp
- **`shared`** - Public sharing flag (tinyint, default 0) **[NEW]**
- **`share_token`** - Unique share token (varchar 64) **[NEW]**

### Indexes
- `images.idx_folder` - Fast folder lookups
- `images.idx_created_at` - Fast date sorting
- `images.idx_share_token` - Fast share token lookups **[NEW]**
- `images.ft_title_description` - Full-text search on title and description

## Default Data

### Admin User
- **Username**: `admin`
- **Password**: `admin123`
- **Note**: Change this password immediately after installation!

### Default Folders
- `Default` - Default folder for uncategorized images
- `Vacation` - Summer vacation photos
- `Projects` - Work and personal projects
- `Nature` - Landscape and wildlife photography

## Recent Changes (November 2025)

### Share Link Feature
Added columns to support public image sharing:
- `images.shared` - Boolean flag (0=private, 1=public)
- `images.share_token` - Unique 32-character hex token for share URLs
- `idx_share_token` - Index for fast token lookups

**Migration**: If upgrading from older version, run:
```bash
php tools/add_share_token_column.php
```

## File Storage Structure

Images are stored in the filesystem:
```
public/uploads/
  {username}/
    {folder}/
      original/     - Full-size manipulated images
      pristine/     - Original uploaded backups
      thumb/        - Thumbnails (generated automatically)
```

Example:
```
public/uploads/
  admin/
    Vacation/
      original/sample_001.jpg
      pristine/sample_001.jpg
      thumb/sample_001.jpg
```

## Database Configuration

Configuration file: `config/database.php`

Example:
```php
<?php
return [
    'host' => 'localhost',
    'database' => 'image_gallery',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
];
```

## Backup and Maintenance

### Backup Database
```bash
mysqldump -u root -p image_gallery > backup_$(date +%Y%m%d).sql
```

### Backup Files

**Windows PowerShell:**
```powershell
Compress-Archive -Path public\uploads -DestinationPath "uploads_backup_$(Get-Date -Format 'yyyyMMdd').zip"
```

**Linux/Unix:**
```bash
tar -czf uploads_backup_$(date +%Y%m%d).tar.gz public/uploads
```

### Restore Database
```bash
mysql -u root -p image_gallery < backup_20251119.sql
```

## Security Features

### Authentication
- All destructive operations (delete, manipulate, update) require authentication
- Password hashing using PHP's `password_hash()` (bcrypt)
- Session-based authentication with 30-minute timeout

### Authorization
- Users can only modify their own images
- Ownership verified on every destructive operation
- Returns 401 (Unauthorized) if not logged in
- Returns 403 (Forbidden) if logged in but doesn't own resource

### Public Sharing
- Share tokens are cryptographically random (32 chars, 256 bits)
- Only images with `shared=1` are accessible via share links
- Share links are read-only (no modification allowed)
- Share tokens can be regenerated to invalidate old links

## API Endpoints

### Protected (Require Authentication + Ownership)
- `DELETE /api.php?action=delete&id={id}`
- `POST /api.php?action=manipulate&id={id}`
- `POST /api.php?action=update&id={id}`
- `POST /api.php?action=revert&id={id}`

### Public (No Authentication)
- `GET /api.php?action=shared&token={token}` - View shared image
- `GET /api.php?action=list` - List public/own images
- `GET /api.php?action=download&id={id}` - Download image

## Testing

### Verify Installation
```bash
php tools/test_security_simple.php
```

### Export Current Schema
```bash
php tools/export_schema.php > current_schema.sql
```

## Troubleshooting

### Connection Issues
1. Verify MySQL is running: `mysql -u root -p`
2. Check credentials in `config/database.php`
3. Ensure database exists: `SHOW DATABASES;`

### Missing Share Token Column
If you get errors about `share_token`:
```bash
php tools/add_share_token_column.php
```

### Permission Issues

**Windows:**
```powershell
icacls "public\uploads" /grant "NT AUTHORITY\SYSTEM:(OI)(CI)F" /T
```

**Linux:**
```bash
chmod -R 775 public/uploads
chown -R www-data:www-data public/uploads
```

## Support

For issues or questions:
1. Check `docs/` folder for feature documentation
2. Review `SECURITY_FIX_NOV_2025.txt` for security info
3. See `CROP_TOOL_QUICKSTART.md` for crop tool usage
