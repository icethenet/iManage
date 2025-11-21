# Video Thumbnail Support

This feature enables uploading video files alongside images, with automatic thumbnail generation using FFmpeg.

## Features

- Upload videos (MP4, MOV, AVI, MKV, WebM)
- Automatic thumbnail extraction from video frames
- Fallback placeholder thumbnail if FFmpeg unavailable
- Display video files in gallery with video icon indicator
- Preserve original video files
- Support for video dimensions detection

## Requirements

### FFmpeg Installation

**Windows:**
1. Download FFmpeg from https://ffmpeg.org/download.html
2. Extract to `C:\ffmpeg\`
3. Add `C:\ffmpeg\bin` to system PATH
4. Verify: `ffmpeg -version` in PowerShell

**Linux (Ubuntu/Debian):**
```bash
sudo apt update
sudo apt install ffmpeg
```

**Linux (CentOS/RHEL):**
```bash
sudo yum install epel-release
sudo yum install ffmpeg
```

**macOS:**
```bash
brew install ffmpeg
```

## Database Migration

Run the migration to add file_type support:

```bash
# MySQL
mysql -u root -p imanage < database/migrations/add_file_type_column.sql

# Or via PHP
php -r "require 'app/Database.php'; \$db = Database::getInstance(); \$db->exec(file_get_contents('database/migrations/add_file_type_column.sql'));"
```

## Configuration

Video settings are configured in `config/app.php`:

```php
'video' => [
    'enabled' => true,  // Enable/disable video uploads
    'max_file_size' => 100 * 1024 * 1024,  // 100MB
    'allowed_types' => ['mp4', 'mov', 'avi', 'mkv', 'webm'],
    'allowed_mimes' => [
        'video/mp4',
        'video/quicktime',
        'video/x-msvideo',
        'video/x-matroska',
        'video/webm'
    ],
    'thumbnail_timestamp' => 1,  // Extract frame at 1 second
    'thumbnail_width' => 200,
    'thumbnail_height' => 200,
],
```

## Usage

1. **Upload a video** through the normal upload interface
2. **Thumbnail is automatically generated** from the first second of video
3. **View in gallery** - videos display with a play icon indicator
4. **Click to view** - opens video player in modal

## Without FFmpeg

If FFmpeg is not installed:
- Videos can still be uploaded
- A placeholder thumbnail with "VIDEO" text is generated
- All other functionality works normally

## File Structure

Videos are stored alongside images:
```
uploads/
  username/
    foldername/
      original/
        video_abc123.mp4    # Original video
      thumb/
        video_abc123.jpg    # Generated thumbnail
      pristine/
        video_abc123.mp4    # Pristine backup
```

## Troubleshooting

**Problem:** "FFmpeg not available"
→ Install FFmpeg and ensure it's in system PATH

**Problem:** Thumbnail generation fails
→ Check FFmpeg installation: `ffmpeg -version`
→ Check file permissions on upload directories
→ View error_log for FFmpeg output

**Problem:** Video file too large
→ Increase `video.max_file_size` in config/app.php
→ Update PHP settings: upload_max_filesize, post_max_size

**Problem:** Video doesn't play in modal
→ Ensure browser supports video format (MP4 recommended)
→ Check MIME type is correct

## Technical Details

### VideoThumbnailGenerator Class
- `isAvailable()` - Check if FFmpeg is installed
- `generateThumbnail()` - Extract frame from video
- `getVideoDuration()` - Get video length in seconds
- `getVideoDimensions()` - Get video resolution

### FFmpeg Command Used
```bash
ffmpeg -ss 1 -i input.mp4 -frames:v 1 -vf "scale=200:-1" -y output.jpg
```

- `-ss 1` - Seek to 1 second
- `-frames:v 1` - Extract single frame
- `-vf "scale=200:-1"` - Scale to 200px width, maintain aspect ratio

## Security

- File type validation via MIME detection
- Extension whitelist enforcement
- Size limits enforced
- Stored outside web root when possible
- Same security model as images
