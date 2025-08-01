# GSCMS Development Workflow

## Single Directory Setup

**Primary Development Directory:** `/var/www/html/GSCMS`

This project now uses a single directory approach for both development and Apache testing, eliminating the confusion of maintaining multiple copies.

## Development Commands

### 1. Apache Development Server (Recommended)
**URL:** `http://localhost/GSCMS/public/`

```bash
# Apache is already configured and running
# Access your application directly at:
curl http://localhost/GSCMS/public/dashboard
```

**Benefits:**
- ✅ Production-like environment
- ✅ Tests subdirectory URL routing  
- ✅ Tests .htaccess configurations
- ✅ Immediate feedback on Apache compatibility

### 2. PHP Development Server (Alternative)
**URL:** `http://localhost:8000`

```bash
# Start PHP dev server from the project root
cd /var/www/html/GSCMS
php -S localhost:8000 -t public/

# Or specify the path directly:
php -S localhost:8000 -t /var/www/html/GSCMS/public/
```

**Benefits:**
- ✅ Quick startup
- ✅ Good for rapid development
- ✅ Simpler debugging

## Git Workflow

```bash
# Navigate to project directory
cd /var/www/html/GSCMS

# Check status
git status

# Make changes and commit
git add .
git commit -m "Your commit message"

# Push to remote
git push origin main
```

## File Permissions

**Current Setup:**
- **Owner:** `www-data:www-data` (Apache compatibility)
- **Directories:** `755` (read/execute for others)
- **Files:** `644` (read for others)
- **Writable dirs:** `775` (`storage/`, `public/uploads/`)

```bash
# Fix permissions if needed
sudo chown -R www-data:www-data /var/www/html/GSCMS
sudo chmod -R 755 /var/www/html/GSCMS
sudo chmod -R 775 /var/www/html/GSCMS/storage
sudo chmod -R 775 /var/www/html/GSCMS/public/uploads
```

## Database Management

```bash
# Run from project root
cd /var/www/html/GSCMS

# Setup database
php database/console/setup.php

# Run migrations
php database/console/migrate.php

# Seed development data
php database/console/seed.php
```

## Testing

```bash
# Run PHPUnit tests
cd /var/www/html/GSCMS
vendor/bin/phpunit
```

## Admin Dashboard Testing

```bash
# Quick admin login for development
curl http://localhost/GSCMS/public/dev-login-admin

# Then access admin dashboard
curl http://localhost/GSCMS/public/admin/dashboard
```

## IDE Configuration

**Recommended Setup:**
1. Open `/var/www/html/GSCMS` as your project root in your IDE
2. Configure your IDE to use this directory for:
   - Git operations
   - Code search and navigation
   - File watching
   - Debugging

## Migration Complete ✅

**What Changed:**
- ✅ Single source of truth: `/var/www/html/GSCMS`
- ✅ Git repository fully functional
- ✅ Apache navigation links fixed
- ✅ Proper file permissions set
- ✅ No more file synchronization issues

**What's Removed:**
- ❌ `/mnt/c/dev/projects/GSCMS` (backed up to `/tmp/`)
- ❌ Dual directory confusion
- ❌ Manual file copying between directories

## Next Steps

1. **Update your IDE** to open `/var/www/html/GSCMS`
2. **Test the workflow** by making a small change
3. **Remove the backup** after confirming everything works: `sudo rm -rf /tmp/GSCMS_backup_*`
4. **Continue development** with confidence!

---

**Migration Date:** August 1, 2025  
**Status:** Complete ✅  
**Apache URL:** http://localhost/GSCMS/public/  
**Admin Dashboard:** http://localhost/GSCMS/public/admin/dashboard