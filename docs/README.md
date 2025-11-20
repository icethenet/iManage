# Documentation Index

## 📚 Main Documentation

### Getting Started
- **[README.md](../README.md)** - Project overview, features, and quick start
- **[INSTALLATION.md](../INSTALLATION.md)** - Detailed installation guide
- **[CROP_TOOL_QUICKSTART.md](../CROP_TOOL_QUICKSTART.md)** - Quick guide to the crop tool

### Platform & Compatibility
- **[CROSS_PLATFORM.md](CROSS_PLATFORM.md)** - Windows, Linux, and macOS compatibility guide
- **[Database Documentation](../database/README.md)** - Schema, maintenance, and backups

## 🔒 Security Documentation

- **[SECURITY_HARDENING_SUMMARY.md](SECURITY_HARDENING_SUMMARY.md)** - Complete security overview
- **[SECURITY_AUDIT.md](SECURITY_AUDIT.md)** - Security audit report
- **[UPLOADS_ACL.md](UPLOADS_ACL.md)** - File upload security and access control

## ✨ Feature Documentation

- **[SHARE_LINK_FEATURE.md](SHARE_LINK_FEATURE.md)** - Secure sharing system guide
- **[CROP_TOOL_TIER1.md](CROP_TOOL_TIER1.md)** - Advanced crop tool documentation

## 📋 Release Notes

Located in `release-notes/` folder:

- **[MOBILEFIRST.txt](release-notes/MOBILEFIRST.txt)** - Mobile-first CSS implementation (Nov 2025)
- **[SECURITY_FIX_NOV_2025.txt](release-notes/SECURITY_FIX_NOV_2025.txt)** - Critical security patches (Nov 2025)
- **[PLATFORM_COMPATIBILITY.txt](release-notes/PLATFORM_COMPATIBILITY.txt)** - Cross-platform compatibility notes
- **[DATABASE_SETUP_COMPLETE.txt](release-notes/DATABASE_SETUP_COMPLETE.txt)** - Database setup completion
- **[CROP_TOOL_BUILD_STATUS.txt](release-notes/CROP_TOOL_BUILD_STATUS.txt)** - Crop tool build notes
- **[FIX_MODAL_GALLERY_LOADING.txt](release-notes/FIX_MODAL_GALLERY_LOADING.txt)** - Modal gallery fixes

## 🏗️ Architecture

```
iManage/
├── app/
│   ├── Controllers/     # Request handlers
│   ├── Models/          # Database models
│   └── Utils/           # Helper classes
├── config/              # Configuration files
├── database/            # Schema and migrations
├── docs/                # Documentation (you are here)
│   └── release-notes/   # Version history & changes
├── public/              # Web root
│   ├── css/            # Stylesheets
│   ├── js/             # JavaScript
│   └── uploads/        # User uploads
└── tools/              # Development scripts
```

## 🛠️ Development Tools

Testing and maintenance scripts in `tools/` directory:

- `test_schema.php` - Validate database schema
- `test_security_simple.php` - Run security audit
- `test_share_link.php` - Test share functionality
- `verify_crop_tool.php` - Validate crop tool
- `export_schema.php` - Export current schema

## 🤝 Contributing

When contributing:
1. Update relevant documentation
2. Add release notes for significant changes
3. Test on multiple platforms if code affects file paths
4. Run security tests before committing

## 📝 Documentation Standards

- Use Markdown for all documentation
- Include code examples where applicable
- Keep release notes in chronological order
- Update this index when adding new docs
