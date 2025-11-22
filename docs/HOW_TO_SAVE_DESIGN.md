# 💾 How to Save Your GrapesJS Design

## Quick Answer

**Look for the top-right purple bar with 3 buttons:**

```
┌──────────────────────────────────────────┐
│  💾 Save Design  |  👁️ Preview  |  🚪 Exit  │ ← Click these!
└──────────────────────────────────────────┘
```

---

## Step-by-Step

### 1️⃣ Make Your Design
- Drag blocks from left sidebar
- Edit text by clicking
- Style elements in right panel

### 2️⃣ Save Your Work
```
Click: 💾 Save Design button (top-right)
Wait for: "Design saved successfully! ✅"
Done! Your design is now live
```

### 3️⃣ Preview (Optional)
```
Click: 👁️ Preview button
See: Your design in new tab
Close: Tab when done
```

### 4️⃣ Exit Editor
```
Click: 🚪 Exit button
Confirm: "Exit design mode?"
Returns to: Share page
```

---

## Save Button Location

**Visual Guide:**

```
┌─────────────────────────────────────────────────────────┐
│ Design Landing Page - iManage          💾 👁️ 🚪        │ ← HERE!
├─────────────────────────────────────────────────────────┤
│ Blocks │                                                 │
│────────│                                                 │
│ 🎯 Hero│          Your Design Canvas                    │
│ 🖼️ Grid│                                                 │
│ 📝 Text│          (Drag blocks here)                    │
│ 📧 Foot│                                                 │
└─────────────────────────────────────────────────────────┘
```

---

## What Happens When You Save?

```javascript
Your Design
    ↓
💾 Click Save
    ↓
Sends to API: /api.php?action=saveLandingPage
    ↓
Saves in Database:
  - HTML code
  - CSS styles  
  - GrapesJS data (for editing later)
  - Share token link
    ↓
✅ Success Message
    ↓
Your custom page is LIVE!
```

---

## Important Notes

### ✅ You CAN:
- Save as many times as you want
- Edit and re-save anytime
- Preview before saving
- Exit and come back to edit later

### ❌ Don't:
- Close browser without saving
- Click Exit without saving first
- Refresh page (unsaved changes lost)

### 💡 Pro Tip:
**Save frequently!** Click 💾 Save Design after every major change.

---

## Testing Your Saved Design

### As Owner (Logged In):
```
Visit: share.php?token=YOUR_TOKEN
See: Your custom landing page
Click: 🎨 Design button to edit again
```

### As Visitor (Not Logged In):
```
Visit: share.php?token=YOUR_TOKEN  
See: Your custom landing page
No: Design button (read-only)
```

---

## Troubleshooting

### "Failed to save" Error?

**Check:**
1. ✅ Are you logged in?
2. ✅ Do you own this shared image?
3. ✅ Is the share token valid?

**Fix:**
- Open browser console (F12)
- Look for red error messages
- Copy error text for debugging

### Save Button Not Working?

**Try:**
1. Hard refresh: `Ctrl + F5`
2. Clear cache: `Ctrl + Shift + Delete`
3. Check console for JavaScript errors

### Design Not Showing After Save?

**Solutions:**
1. Visit share page directly
2. Clear browser cache
3. Try incognito mode
4. Check database:
   ```sql
   SELECT * FROM landing_pages WHERE share_token = 'YOUR_TOKEN';
   ```

---

## Quick Reference Card

| Button | Purpose | When to Use |
|--------|---------|-------------|
| 💾 Save Design | Saves to database | After any changes |
| 👁️ Preview | Opens in new tab | Before saving to check |
| 🚪 Exit | Return to share page | When done designing |

**Workflow:**
```
Edit → Save → Preview → Edit → Save → Exit
```

---

## Need More Help?

- Full docs: `docs/CUSTOM_LANDING_PAGES.md`
- Quick start: `docs/GRAPESJS_QUICKSTART.md`
- Test page: `public/test-grapesjs.php`

---

**Remember: 💾 SAVE OFTEN! 💾**
