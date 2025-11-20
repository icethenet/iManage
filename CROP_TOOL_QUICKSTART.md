# Crop Tool - Quick Start Guide

## 🚀 Getting Started

The Tier 1 crop tool is **ready to use immediately**. No additional setup required.

### How to Use

1. **Open the Application**
   - Navigate to: `http://localhost/imanage/public/`
   - Login or register

2. **Upload or Open an Image**
   - Go to Upload tab and upload an image, OR
   - Click an existing image in the Gallery

3. **Start Cropping**
   - In the image modal, find the "Image Tools" section
   - Scroll to "Crop" subsection
   - Click **"Select Area to Crop"**

4. **Interactive Selection**
   - **Drag** anywhere on the image to create a new crop selection
   - **Drag corners** to resize the selection
   - **Drag inside** the selection to move it
   - See the **green border** showing your selection
   - See the **rule-of-thirds grid** for composition guidance
   - The **darkened areas** outside show what will be removed

5. **Apply the Crop**
   - Click **"Apply Crop"** button (green)
   - Wait for processing...
   - Image refreshes with cropped result

6. **Cancel Anytime**
   - Click **"Cancel Crop"** button (red) to abort
   - Original image is not affected

## 📋 Features at a Glance

| Feature | Status |
|---------|--------|
| Interactive canvas selection | ✅ |
| Drag to create selection | ✅ |
| Corner handles to resize | ✅ |
| Move selection by dragging inside | ✅ |
| Rule-of-thirds grid overlay | ✅ |
| Darkened area preview | ✅ |
| Touch support (mobile/tablet) | ✅ |
| Real-time visual feedback | ✅ |
| Precise coordinate cropping | ✅ |
| Status messages | ✅ |
| Easy cancel button | ✅ |

## 🔧 Technical Details

**Files Involved:**
- Frontend: `public/js/crop-tool.js` (329 lines, self-contained)
- Integration: `public/js/editor.js` (48 new lines)
- UI: `public/index.php` (updated crop section)
- Backend: Already supporting coordinate-based crop

**No Dependencies:**
- Pure JavaScript (no jQuery, no canvas library)
- HTML5 Canvas API
- Native browser APIs for touch/mouse

**Browser Compatibility:**
- Chrome/Edge (latest)
- Firefox (latest)
- Safari (latest)
- Mobile browsers with touch support

## 💡 Pro Tips

- **Default Selection**: Starts at 60% of image centered (good starting point)
- **Grid Lines**: Use the rule-of-thirds grid to compose your crop
- **Precision**: Select exactly what you want - no "good enough" crops
- **Corner Handles**: Grab the green squares at corners to fine-tune size
- **Re-position**: Click and drag inside your selection to move it around
- **Preview**: Darkened areas show exactly what will be cropped out

## 📊 File Structure

```
imanage/
├── public/
│   ├── js/
│   │   ├── crop-tool.js          ← NEW (11.5 KB)
│   │   ├── editor.js             ← UPDATED (crop integration)
│   │   └── app.js
│   └── index.php                 ← UPDATED (UI)
├── docs/
│   └── CROP_TOOL_TIER1.md        ← NEW (full documentation)
├── app/
│   └── Controllers/
│       └── ImageController.php   ← Uses existing crop support
└── tools/
    └── verify_crop_tool.php      ← NEW (verification test)
```

## ✅ Verification

Run the verification test:
```bash
cd c:\www\www\imanage
php tools/verify_crop_tool.php
```

Expected output:
```
✓ CROP TOOL VERIFIED SUCCESSFULLY!
✓ Crop from 200x200 to 100x100 works correctly
✓ Coordinate-based cropping (x, y, width, height) functional
```

## 🎯 What Happens Next

1. User selects crop area visually
2. Coordinates (x, y, width, height) sent to server
3. Server processes crop via GD library
4. Cropped image saved
5. Modal refreshes to show new result
6. Image history recorded

## 🚫 Limitations (Tier 1)

- No aspect ratio locking
- No preset sizes
- No live preview of final crop
- No crop history/undo

See `docs/CROP_TOOL_TIER1.md` for complete documentation and Tier 2/3 roadmap.

## ❓ Troubleshooting

**Issue**: Canvas not showing
- Solution: Make sure you clicked "Select Area to Crop" button
- Check browser console for errors (F12)

**Issue**: Crop not applying
- Solution: Check browser console for API errors
- Verify image has required permissions
- Try with a smaller/different image

**Issue**: Selection not visible
- Solution: Make sure you're dragging on the canvas area
- Try clicking and dragging to create new selection

## 📞 Next Steps

- **Want Tier 2?** Add aspect ratio locking, live preview, presets
- **Want Tier 3?** Add grid overlays, crop history, advanced constraints
- See `docs/CROP_TOOL_TIER1.md` for detailed roadmap

---

**Ready to crop!** 🎬 Open your browser and start using the crop tool now.
