# Upload Directory ACL Setup (Windows)

## Overview

The `public/uploads/` directory must be writable by the Apache/PHP process. On Windows with Apache running under a service account, explicit permission grants are often required.

## Directory Structure

```
public/uploads/
├── <username>/
│   ├── original/          (original uploaded images)
│   ├── thumb/             (generated thumbnails)
│   ├── pristine/          (backups for revert)
│   └── <subfolder>/       (user-created folders)
│       ├── original/
│       ├── thumb/
│       └── pristine/
```

## Windows ACL Setup

### Option 1: GUI (File Explorer)

1. **Navigate to the directory:**
   - Right-click `C:\www\www\imanage\public\uploads`
   - Select **Properties**

2. **Open Security tab:**
   - Click the **Security** tab
   - Click **Edit** to modify permissions

3. **Add Apache user:**
   - Click **Add**
   - Type: `IIS APPPOOL\DefaultAppPool` (or your specific pool name)
   - Click **Check Names**, then **OK**

4. **Grant permissions:**
   - Select the Apache user
   - Check **Modify** (includes Read, Write, Delete)
   - Click **Apply** → **OK**

5. **Apply recursively:**
   - If prompted, select "Apply these permissions to this folder, subfolders and files"

### Option 2: Command Line (icacls)

Run as **Administrator**:

```powershell
icacls "C:\www\www\imanage\public\uploads" /grant "IIS APPPOOL\DefaultAppPool:(OI)(CI)M" /T
```

**Parameters:**
- `/grant` — Add permission grant
- `(OI)` — Object Inherit (applies to files)
- `(CI)` — Container Inherit (applies to subfolders)
- `M` — Modify permission
- `/T` — Apply recursively to all files and folders

### Option 3: Command Line (cacls) — Legacy

```cmd
cacls "C:\www\www\imanage\public\uploads" /E /G "IIS APPPOOL\DefaultAppPool:F" /T
```

**Parameters:**
- `/E` — Edit existing permissions
- `/G` — Grant permission
- `F` — Full Control
- `/T` — Apply recursively

## Verify Permissions

After applying ACLs, verify with:

```powershell
icacls "C:\www\www\imanage\public\uploads"
```

Look for a line like:
```
IIS APPPOOL\DefaultAppPool:(OI)(CI)M
```

## Troubleshooting

### Uploads Fail with Permission Denied

1. **Check Apache service account:**
   ```powershell
   Get-Service -Name Apache2.4 | Select-Object *User*
   ```
   Or open Apache Service Manager and verify the "Log On As" account.

2. **Verify ACL application:**
   ```powershell
   icacls "C:\www\www\imanage\public\uploads"
   ```

3. **Check PHP/Apache error logs:**
   - Apache: `C:\Apache24\logs\error.log`
   - PHP: Check `php_error.log` or PHP error logging in `php.ini`

4. **Reapply permissions:**
   If changes don't take effect, restart Apache:
   ```powershell
   Restart-Service Apache2.4 -Force
   ```

### Antivirus/Security Software

Some security tools may block write operations. Temporarily disable or whitelist the directory:
- Windows Defender Exclusions
- Third-party antivirus/antimalware

## Production Considerations

- **Ownership:** Keep the directory owned by a system account (e.g., SYSTEM or the Apache service account).
- **Permissions:** Use the minimum required (Modify, not Full Control).
- **Backups:** Regularly back up the `pristine/` directories for disaster recovery.
- **Cleanup:** Implement periodic cleanup for orphaned files in `original/` and `thumb/` directories.

## See Also

- [PHP: File Upload Documentation](https://www.php.net/manual/en/features.file-upload.php)
- [Windows icacls reference](https://learn.microsoft.com/en-us/windows-server/administration/windows-commands/icacls)
- [Apache Documentation](https://httpd.apache.org/)
