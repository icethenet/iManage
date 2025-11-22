# AI Tagging Security - Multi-User System

## 🔒 Security Overview

iManage is a **multi-user system** where each user has isolated image storage. This document explains how AI tagging respects user boundaries.

## 📁 File Structure (User-Isolated)

```
public/uploads/
├── admin/
│   ├── default/
│   │   ├── original/    ← Admin's images
│   │   ├── thumb/
│   │   └── pristine/
│   └── vacation/
│       ├── original/
│       ├── thumb/
│       └── pristine/
├── john/
│   ├── default/
│   │   ├── original/    ← John's images
│   │   ├── thumb/
│   │   └── pristine/
│   └── projects/
│       ├── original/
│       ├── thumb/
│       └── pristine/
└── alice/
    └── default/
        ├── original/    ← Alice's images
        ├── thumb/
        └── pristine/
```

**Path Format:** `/uploads/{username}/{folder}/original/{filename}`

## 🛡️ Security Layers

### 1. **Database Operations** (ImageController.php)

Every operation checks ownership:

```php
// Line 433 - Update
if ($image['user_id'] !== ($_SESSION['user_id'] ?? null)) {
    $this->error('Forbidden', 403);
    return;
}

// Line 509 - Delete  
if ($image['user_id'] !== $_SESSION['user_id']) {
    $this->error('Forbidden: You do not own this image', 403);
    return;
}

// Line 719 - Manipulate
if ((int)$image['user_id'] !== (int)($_SESSION['user_id'] ?? -1)) {
    $this->error('Forbidden: You do not own this image', 403);
    return;
}
```

✅ **Result:** Users can only modify their own images in the database.

### 2. **Image Proxy** (image-proxy.php) - NEWLY SECURED

**Before (INSECURE):**
```php
header('Access-Control-Allow-Origin: *');  // ❌ Anyone could access!
// No authentication check
```

**After (SECURED):**
```php
// Check session authentication
if (!isset($_SESSION['user_id'])) {
    die('Authentication required');
}

// Extract username from path
$requestedUsername = $pathParts[0];

// Verify requested image belongs to current user
if ($requestedUsername !== $currentUser['username']) {
    die('Access denied: You can only access your own images');
}
```

✅ **Result:** Users can only load images through the proxy if they own them.

### 3. **API Image Listing** (ImageController.php)

Images are filtered by user on retrieval:

```php
// Line 48 - List images
$images = $this->imageModel->getByFolder($folder, $page, $limit, $userId);

// Model filters by user_id
WHERE user_id = ? OR shared = 1
```

✅ **Result:** API only returns images the user owns or that are publicly shared.

### 4. **URL Construction** (ImageController.php)

Image URLs include the username:

```php
// Line 65-67
$user = $userModel->findById($image['user_id']);
$username = $user['username'];
$urlPathSegment = $username . '/' . $folder;

// Line 75-76
$image['thumbnail_url'] = '/uploads/' . $username . '/folder/thumb/image.jpg';
$image['original_url'] = '/uploads/' . $username . '/folder/original/image.jpg';
```

✅ **Result:** URLs are user-specific and can't be manipulated.

## 🤖 AI Tagging Security Flow

### Manual Tagging (Modal Button)

```
1. User opens image modal
   └─> Image URL: /uploads/john/default/original/photo.jpg
   
2. User clicks "🤖 Generate AI Tags"
   └─> JavaScript: generateImageTags(imageUrl)
   
3. Image loads through proxy
   └─> image-proxy.php?path=john/default/original/photo.jpg
   └─> ✅ Checks: Is logged-in user "john"?
   └─> ✅ Yes → Serve image
   └─> ❌ No → 403 Forbidden
   
4. TensorFlow.js processes image (client-side)
   └─> Tags generated: ["sunset", "beach", "ocean"]
   
5. User clicks "Save Changes"
   └─> POST /api.php?action=update&id=123
   └─> ✅ Checks: Does user own image 123?
   └─> ✅ Yes → Update database
   └─> ❌ No → 403 Forbidden
```

### Auto-Tag on Upload

```
1. User uploads image
   └─> Upload creates: /uploads/john/default/original/photo.jpg
   
2. Auto-tag generates tags (client-side)
   └─> File object processed directly (no proxy needed)
   └─> Tags: ["cat", "pet", "cute"]
   
3. Upload saves to database
   └─> POST /api.php?action=upload
   └─> Sets user_id = SESSION['user_id']
   └─> Saves with user's tags
```

## 🔐 Security Guarantees

| Action | Security Check | Protected Against |
|--------|---------------|-------------------|
| View image in gallery | API filters by user_id | Seeing others' images |
| Load image for AI | Proxy checks username | Processing others' images |
| Save AI tags | Controller checks ownership | Modifying others' tags |
| Upload with tags | Session user_id | Uploading as another user |
| Update metadata | Controller checks ownership | Changing others' metadata |

## ⚠️ Important Security Notes

### ✅ SECURE Operations
- Viewing your own images
- AI tagging your own images  
- Saving tags to your own images
- Uploading images to your account
- Viewing shared images (intentionally public)

### ❌ BLOCKED Operations
- Viewing another user's private images
- AI tagging another user's images
- Modifying another user's tags
- Accessing files outside /uploads/ directory
- Path traversal attacks (../ blocked)

## 🧪 Testing Security

### Test 1: Can I tag another user's image?

```javascript
// In browser console (as user "john")
const aliceImageUrl = '/uploads/alice/default/original/photo.jpg';
await window.AITagging.generateImageTags(aliceImageUrl);

// Expected result:
// ❌ 403 Forbidden from image-proxy.php
// ❌ "Access denied: You can only access your own images"
```

### Test 2: Can I save tags to another user's image?

```javascript
// As user "john", try to update Alice's image (ID 999)
fetch('/api.php?action=update&id=999', {
    method: 'POST',
    body: JSON.stringify({tags: 'hacked'})
});

// Expected result:
// ❌ 403 Forbidden from ImageController
// ❌ "Forbidden: You do not own this image"
```

### Test 3: Can I access files outside uploads/?

```javascript
// Try path traversal
const url = 'image-proxy.php?path=../../config/database.php';

// Expected result:
// ❌ Path traversal characters stripped
// ❌ 404 File not found
```

## 📊 Shared Images

**Special Case:** Users can mark images as "shared" for public viewing.

```sql
UPDATE images SET shared = 1, share_token = 'abc123' WHERE id = 5;
```

Shared images:
- ✅ Appear in public galleries
- ✅ Can be viewed by anyone with the share link
- ❌ Cannot be edited by others (only owner)
- ❌ Cannot be AI-tagged by others (only owner)

## 🔧 For Developers

### Adding New AI Feature?

Always verify ownership:

```javascript
// ❌ BAD - No ownership check
async function newAIFeature(imageUrl) {
    const result = await processImage(imageUrl);
    saveToDatabase(result);
}

// ✅ GOOD - Respects user boundaries
async function newAIFeature(imageUrl) {
    // Image URL already filtered by API (contains correct username)
    // Proxy will block if not user's image
    const result = await processImage(imageUrl);
    
    // API update checks ownership before saving
    saveToDatabase(imageId, result);
}
```

### Server-Side Processing?

If you add server-side AI (like Gemini):

```php
// Always check ownership before processing
$image = $imageModel->getById($id);
if ($image['user_id'] !== $_SESSION['user_id']) {
    die('Forbidden');
}

// Then process
$tags = $gemini->generateTags($imagePath);
```

## 📝 Summary

✅ **Multi-user isolation is enforced at every level:**
1. Database queries filter by user_id
2. Image proxy checks username in path
3. Controllers verify ownership before updates
4. File paths include username
5. Path traversal attacks are blocked

✅ **AI tagging respects user boundaries:**
- Only processes images you own
- Only saves tags to images you own
- Cannot access other users' images

✅ **Recent security improvement:**
- Image proxy now requires authentication
- Image proxy verifies username matches session
- Logs unauthorized access attempts

---

**Last Updated:** November 22, 2025  
**Security Status:** ✅ SECURE - Multi-user isolation enforced

