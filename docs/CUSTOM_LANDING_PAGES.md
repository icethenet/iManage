# Custom Landing Pages with GrapesJS

## Overview

iManage now includes a powerful visual page builder using GrapesJS, allowing users to create custom landing pages for their shared image galleries. Instead of the default share page, users can design beautiful, branded pages with drag-and-drop simplicity.

## Features

### Visual Page Builder
- **Drag-and-Drop Interface** - Build pages visually without coding
- **Pre-built Blocks** - Hero sections, gallery grids, text sections, footers
- **Live Preview** - See changes in real-time as you design
- **Responsive Design** - Works perfectly on desktop, tablet, and mobile

### Custom Components
- 🎯 **Hero Section** - Eye-catching header with gradient backgrounds
- 🖼️ **Gallery Grid** - Responsive image grid layouts
- 📝 **Text Section** - Formatted text blocks with custom styling
- 📧 **Footer** - Professional footer sections

### Style Management
- Full CSS control via visual style panel
- Typography settings (fonts, sizes, weights, colors)
- Layout properties (margins, padding, dimensions)
- Decorations (backgrounds, borders, shadows)
- Color schemes and gradients

## How to Use

### Accessing Design Mode

1. **Create a Shared Image**
   - Upload an image in iManage
   - Click "Share" to generate a public share link
   - Copy the share link

2. **Open Share Page**
   - Visit your share link (e.g., `share.php?share=abc123...`)
   - If you're logged in as the owner, you'll see a "🎨 Design Landing Page" button

3. **Enter Design Mode**
   - Click the "Design Landing Page" button
   - GrapesJS editor will load with your canvas

### Using the Editor

#### Left Sidebar - Design Tools
- **Blocks Tab** - Drag pre-built components onto canvas
- **Layers Tab** - View and organize page structure
- **Styles Tab** - Customize selected element's CSS

#### Top Action Bar
- **💾 Save** - Save your design to database
- **👁️ Preview** - Preview without editing UI
- **❌ Exit** - Return to public share view

#### Building Your Page

1. **Start with Hero Section**
   - Drag "Hero Section" block from sidebar
   - Click to select, edit text in canvas
   - Use Styles panel to change colors/fonts

2. **Add Gallery Grid**
   - Drag "Gallery Grid" block
   - Replace placeholder images with your actual image URLs
   - Adjust grid columns in Styles panel

3. **Add Text Content**
   - Drag "Text Section" for descriptions
   - Edit text directly in canvas
   - Style typography via Styles panel

4. **Add Footer**
   - Drag "Footer" block to bottom
   - Customize text and colors

5. **Save Your Work**
   - Click "💾 Save" to store design
   - Exit to see your custom landing page live

### Tips & Best Practices

#### Design Tips
- **Keep it Simple** - Don't overcrowd with too many elements
- **Brand Consistency** - Use matching colors and fonts
- **Mobile First** - Check responsive behavior
- **High Quality Images** - Use good resolution images
- **Call to Action** - Guide visitors with clear messaging

#### Technical Tips
- **Save Often** - Click Save regularly to prevent data loss
- **Preview Mode** - Use preview to test without UI clutter
- **Browser Cache** - Hard refresh (Ctrl+F5) if changes don't appear
- **Undo/Redo** - Use Ctrl+Z / Ctrl+Y for quick edits

## Database Structure

Landing pages are stored in the `landing_pages` table:

```sql
CREATE TABLE landing_pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    share_token VARCHAR(64) UNIQUE,
    page_title VARCHAR(255) DEFAULT 'Shared Gallery',
    html_content LONGTEXT,
    css_content LONGTEXT,
    grapesjs_data LONGTEXT COMMENT 'JSON: GrapesJS project data',
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Data Storage
- **html_content** - Final rendered HTML
- **css_content** - Custom CSS styles
- **grapesjs_data** - Full GrapesJS project (for re-editing)
- **share_token** - Links to shared image

## API Endpoints

### Save Landing Page
```http
POST /api.php?action=saveLandingPage
Content-Type: application/json

{
    "token": "share_token_here",
    "html_content": "<html>...</html>",
    "css_content": ".class { color: red; }",
    "grapesjs_data": "{...}",
    "page_title": "My Gallery"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Saved"
}
```

### Load Landing Page
```http
GET /api.php?action=loadLandingPage&token=share_token_here
```

**Response:**
```json
{
    "success": true,
    "design": {
        "html_content": "<html>...</html>",
        "css_content": ".class { color: red; }",
        "grapesjs_data": "{...}",
        "page_title": "My Gallery"
    }
}
```

## Security

### Authentication Required
- Only logged-in users can access design mode
- Only the image owner sees "Design Landing Page" button
- API endpoints check user ownership before saving

### XSS Protection
- HTML/CSS sanitization on save
- Content Security Policy headers
- No inline scripts in user content

### Data Validation
- Share tokens validated against database
- User ownership verified for all operations
- Input sanitized before storage

## Installation

### 1. Create Database Table
```bash
# Run the migration script
php tools/create_landing_pages_table.php
```

### 2. Include GrapesJS Scripts
Scripts are loaded from CDN in `design-landing.php`:
- `grapesjs` - Core library
- `grapesjs-blocks-basic` - Basic blocks
- `grapesjs-plugin-forms` - Form components

### 3. Update Share Page
The share page now:
- Checks for custom landing pages
- Displays custom HTML/CSS if available
- Falls back to default view if not

## Files Created

### PHP Files
- `public/design-landing.php` - GrapesJS editor interface
- `tools/create_landing_pages_table.php` - Database migration

### JavaScript Files
- `public/js/grapesjs-manager.js` - GrapesJS wrapper class with custom blocks/commands

### Database Migrations
- `database/migrations/add_landing_pages_table.sql` - Table schema

### Updated Files
- `public/api.php` - Added saveLandingPage/loadLandingPage endpoints
- `public/share.php` - Added custom landing page display + design button
- `README.md` - Updated feature list

## Troubleshooting

### Problem: Design Mode Button Not Showing
**Solution:** 
- Ensure you're logged in
- Verify you own the shared image
- Check browser console for errors
- Hard refresh (Ctrl+F5)

### Problem: Changes Not Saving
**Solution:**
- Check browser console for API errors
- Verify database table exists (`landing_pages`)
- Ensure MySQL is running
- Check user authentication

### Problem: Custom Page Not Displaying
**Solution:**
- Verify landing page was saved (check database)
- Check `is_active` column is 1
- Clear browser cache
- Inspect `custom-landing-container` in DevTools

### Problem: Styles Not Applying
**Solution:**
- Check CSS injection in `#custom-landing-styles`
- Verify no conflicting styles in `style.css`
- Increase CSS specificity if needed
- Use `!important` for overrides (sparingly)

## Future Enhancements

Potential additions:
- **Image Gallery Integration** - Auto-populate gallery blocks with user's images
- **Template Library** - Pre-designed templates to start from
- **Export/Import** - Share designs between users
- **Version History** - Track design changes over time
- **Collaboration** - Multiple users editing same page
- **Custom Fonts** - Upload custom web fonts
- **Advanced Animations** - Add scroll animations and transitions
- **Form Builder** - Contact forms for visitors
- **Analytics** - Track visitor engagement

## Support

For issues or questions:
- Check this documentation
- Review [main README](../README.md)
- Open GitHub issue
- Check browser console for errors

---

**Happy Designing! 🎨**
