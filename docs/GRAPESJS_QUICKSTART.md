# GrapesJS Custom Landing Pages - Quick Start Guide

## 🚀 Getting Started in 5 Minutes

### Step 1: Setup Database (One-Time)
```bash
# Start MySQL if not running
# Then create the landing_pages table:
php tools/create_landing_pages_table.php
```

**Expected Output:**
```
Creating landing_pages table...
✅ landing_pages table created successfully!
✅ Table verified: landing_pages exists
```

### Step 2: Verify Installation
```bash
php tools/verify_grapesjs_integration.php
```

**Expected Output:**
```
=== GrapesJS Landing Pages - Verification ===
✅ landing_pages table exists
✅ GrapesJS editor page exists
✅ API endpoints configured
✅ VERIFICATION PASSED - Ready to use!
```

### Step 3: Create Your First Custom Landing Page

#### 3.1 Login to iManage
```
http://localhost/imanage/public/
Username: admin
Password: admin123
```

#### 3.2 Upload and Share an Image
1. Click "📤 Upload" button
2. Select an image from your computer
3. After upload, find the image in your gallery
4. Click the "🔗 Share" button
5. Copy the share link (looks like: `share.php?share=abc123...`)

#### 3.3 Enter Design Mode
1. Visit the share link while logged in
2. Look for "🎨 Design Landing Page" button (bottom right)
3. Click it to open the GrapesJS editor

#### 3.4 Build Your Page
1. **Add Hero Section**
   - Drag "🎯 Hero Section" from left sidebar
   - Click on text to edit
   - Use Styles panel to change colors

2. **Add Gallery Grid**
   - Drag "🖼️ Gallery Grid" below hero
   - Click grid to select
   - Adjust in Styles panel

3. **Add Text Section**
   - Drag "📝 Text Section"
   - Edit text inline
   - Style with fonts/colors

4. **Add Footer**
   - Drag "📧 Footer" to bottom
   - Customize text

#### 3.5 Save Your Work
1. Click "💾 Save" button (top right)
2. Wait for "Design saved successfully! ✅" message
3. Click "❌ Exit" to return to share page

#### 3.6 View Your Custom Page
1. Your custom landing page is now live!
2. Share the link with anyone
3. They'll see your beautiful custom design

## 🎨 Editor Interface Guide

### Left Sidebar - Design Tools
```
┌─────────────────────┐
│ DESIGN TOOLS        │
├─────────────────────┤
│ Blocks Tab          │ ← Drag components
│ - 🎯 Hero Section   │
│ - 🖼️ Gallery Grid   │
│ - 📝 Text Section   │
│ - 📧 Footer         │
├─────────────────────┤
│ Layers Tab          │ ← View structure
│ Styles Tab          │ ← Customize CSS
└─────────────────────┘
```

### Top Action Bar
```
┌─────────────────────────────────────┐
│ 🧱 Blocks | 📋 Layers | 🎨 Styles  │
├─────────────────────────────────────┤
│          💾 Save  👁️ Preview  ❌ Exit │
└─────────────────────────────────────┘
```

### Main Canvas
- Click any element to select
- Double-click text to edit
- Drag to reposition
- Delete key to remove

## 🎯 Quick Tips

### Design Shortcuts
- **Ctrl+Z** - Undo
- **Ctrl+Y** - Redo
- **Delete** - Remove selected element
- **Ctrl+C / Ctrl+V** - Copy/paste (in some blocks)

### Best Practices
✅ **Save often** - Click Save every few minutes
✅ **Preview frequently** - Check how it looks without UI
✅ **Mobile first** - Consider mobile users
✅ **Keep it simple** - Don't overcrowd
✅ **High quality images** - Use good resolution

### Common Mistakes
❌ Forgetting to save before exiting
❌ Using too many different fonts/colors
❌ Not checking mobile responsiveness
❌ Leaving placeholder images

## 📱 Example Layout Structure

```html
┌────────────────────────────────┐
│ HERO SECTION                   │
│ Large title + subtitle         │
│ Gradient background            │
└────────────────────────────────┘
┌────────────────────────────────┐
│ TEXT SECTION                   │
│ Description of your gallery    │
└────────────────────────────────┘
┌─────────┬─────────┬─────────┐
│ Image 1 │ Image 2 │ Image 3 │
│         │         │         │
├─────────┼─────────┼─────────┤
│ Image 4 │ Image 5 │ Image 6 │
│         │         │         │
└─────────┴─────────┴─────────┘
┌────────────────────────────────┐
│ FOOTER                         │
│ Copyright © 2025              │
└────────────────────────────────┘
```

## 🔧 Troubleshooting

### Problem: Design Mode Button Not Showing
**Solution:**
1. Ensure you're logged in
2. Verify you own the shared image
3. Hard refresh page (Ctrl+F5)
4. Check browser console for errors

### Problem: Can't Save Design
**Solution:**
1. Check browser console for API errors
2. Verify MySQL is running
3. Confirm landing_pages table exists
4. Try logging out and back in

### Problem: Styles Not Applying
**Solution:**
1. Click element first to select it
2. Make sure you're in Styles tab
3. Some styles need specific units (px, %, em)
4. Try adding `!important` for overrides

### Problem: Page Looks Different After Save
**Solution:**
1. Clear browser cache (Ctrl+Shift+Delete)
2. Hard refresh (Ctrl+F5)
3. Check CSS conflicts in style.css
4. Verify custom CSS was saved (check database)

## 📚 Next Steps

### Learn More
- Read full documentation: `docs/CUSTOM_LANDING_PAGES.md`
- Check implementation details: `docs/GRAPES_JS_IMPLEMENTATION.md`
- Visit GrapesJS docs: https://grapesjs.com/docs/

### Customize Further
1. Add more custom blocks (edit `grapesjs-manager.js`)
2. Create page templates for reuse
3. Integrate your actual gallery images
4. Add contact forms or interactive elements

### Get Support
- Check browser console for errors
- Review error logs in `logs/api_errors.log`
- Open GitHub issue for bugs
- Check main README.md for general help

## 🎉 You're All Set!

You now have a powerful visual page builder for your shared galleries. Create beautiful, custom landing pages that showcase your images in style!

**Happy Designing! 🚀**

---

**Quick Reference:**
- Editor URL: `design-landing.php?share={token}`
- Share URL: `share.php?share={token}`
- Verify: `php tools/verify_grapesjs_integration.php`
- Docs: `docs/CUSTOM_LANDING_PAGES.md`
