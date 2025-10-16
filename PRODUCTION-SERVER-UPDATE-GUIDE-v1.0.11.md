# Production Server Update Guide - Archive Forge SWT v1.0.11

## ⚠️ IMPORTANT: Fixed Package Available
**Use this file:** `Archive-Forge-SWT-v1.0.11-FIXED.zip`  
**This fixes the "plugin does not have a valid header" error**

## Server Details
- **Server:** 66.94.125.162
- **SSH User:** Disrutparian
- **SSH Password:** =LbW[50jr^p86eL$
- **WP Admin:** https://disruptarian.com/blog/wp-admin
- **WP User:** admin_wf582umf
- **WP Password:** pHwI0Jeg8fhp~!0x

## What's New in v1.0.11

### 🚀 **Major Feature: Wayback Validation System**
- **Complete archive validation and reconciliation system**
- **Job ID capture** from Archive.org Save Page Now responses
- **Status polling** via `/save/status/<JOB_ID>` until completion
- **Availability API integration** for double-checking archives
- **Automatic cleanup** via cron job every 5 minutes
- **Manual validation** button in admin interface
- **Audit flagging** for items needing manual review
- **Comprehensive error handling** with proper error codes

### 🎯 **Production Impact**
- **Resolves 330+ stuck submissions** currently on production server
- **Prevents future submissions** from getting stuck in "processing" status
- **Provides manual tools** for troubleshooting archive issues
- **Ensures reliable archive completion** and status updates

## Update Methods

### Method 1: WordPress Admin Upload (Recommended)
1. **Download** `Archive-Forge-SWT-v1.0.11-FIXED.zip` from your local machine
2. **Login** to WordPress admin: https://disruptarian.com/blog/wp-admin
3. **Navigate** to Plugins → Add New → Upload Plugin
4. **Upload** the zip file
5. **Activate** the plugin (it will replace the existing version)

### Method 2: File Manager Upload
1. **Login** to cPanel or file manager
2. **Navigate** to `/public_html/blog/wp-content/plugins/`
3. **Upload** `Archive-Forge-SWT-v1.0.11-FIXED.zip`
4. **Extract** the zip file
5. **Replace** the existing `spun-web-archive-forge` folder

### Method 3: SSH Upload (Manual Commands)
```bash
# Connect to server (fix SSH config first)
ssh Disrutparian@66.94.125.162

# Find WordPress installation
find /home -name "wp-config.php" -type f | head -1

# Navigate to plugins directory (replace with actual path)
cd /path/to/wordpress/wp-content/plugins/

# Backup existing plugin
mv spun-web-archive-forge spun-web-archive-forge-backup-$(date +%Y%m%d-%H%M%S)

# Upload and extract new version
# (Upload Archive-Forge-SWT-v1.0.11-FIXED.zip via SCP or other method)
unzip Archive-Forge-SWT-v1.0.11-FIXED.zip
mv spun-web-archive-forge spun-web-archive-forge-new
mv spun-web-archive-forge-new spun-web-archive-forge

# Set proper permissions
chmod -R 755 spun-web-archive-forge
chown -R www-data:www-data spun-web-archive-forge
```

### Method 4: Fix SSH Configuration (If Needed)
If you get SSH config errors, fix the SSH config file:
```bash
# Edit SSH config
nano ~/.ssh/config

# Remove or fix these problematic lines:
# admin
# admin

# Or use SSH with specific options:
ssh -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no Disrutparian@66.94.125.162
```

## Post-Update Testing

### 1. Verify Version Display
- **Check** admin page shows "ARCHIVE FORGE SWT v1.0.11"
- **Verify** branding and support information is correct

### 2. Test New Validation Features
- **Navigate** to Archive Forge → Queue Management
- **Look for** "Validate Archives" button
- **Click** "Validate Archives" to process stuck items
- **Monitor** the validation results

### 3. Test Queue Processing
- **Check** queue statistics for stuck items
- **Run** "Process Queue" to test normal processing
- **Verify** items move from "processing" to "archived" or "failed"

### 4. Monitor Cron Job
- **Check** if cron job is scheduled (every 5 minutes)
- **Monitor** debug logs for automatic processing
- **Verify** stuck items are being processed automatically

## Expected Results

### Immediate Impact
- **330+ stuck submissions** will be processed via manual validation
- **New submissions** will be properly tracked with job IDs
- **Status updates** will be more reliable and accurate

### Ongoing Benefits
- **Automatic processing** of stuck items every 5 minutes
- **Better error handling** and reporting
- **Improved reliability** of archive completion
- **Manual troubleshooting tools** for edge cases

## Troubleshooting

### If Validation Button Doesn't Work
1. **Check** browser console for JavaScript errors
2. **Verify** AJAX nonce is valid (refresh page)
3. **Check** debug logs for PHP errors

### If Cron Job Doesn't Run
1. **Verify** WordPress cron is enabled
2. **Check** server cron configuration
3. **Monitor** debug logs for cron execution

### If Archives Still Don't Complete
1. **Check** Archive.org API credentials
2. **Verify** network connectivity to Archive.org
3. **Review** error codes in submission history

### If SSH Connection Fails
1. **Check** SSH config file for bad options
2. **Verify** server is accessible (ping 66.94.125.162)
3. **Try** alternative connection methods
4. **Use** WordPress admin upload instead

## Support Information

- **Plugin Name:** ARCHIVE FORGE SWT
- **Version:** 1.0.11
- **Support:** support@spunwebtechnology.com
- **Website:** https://spunwebtechnology.com/spun-web-archive-pro-wordpress-wayback-archive/
- **IRC:** http://web.libera.chat/#spunwebtechnology (@spun_web)
- **Phone:** +1 (888) 264-6790

## Files Included in v1.0.11

### Core Files
- `spun-web-archive-forge.php` (main plugin file)
- `uninstall.php` (cleanup script)

### New Features
- `includes/class-swap-archiver.php` (Wayback validation system)

### Updated Files
- `includes/class-admin-page.php` (Validate Archives button)
- `assets/js/admin.js` (validation AJAX handler)
- `CHANGELOG.md` (version 1.0.11 details)
- `README.md` (updated version info)

### Assets
- `assets/css/admin.css` (styling)
- `assets/css/frontend.css` (frontend styling)
- `assets/js/frontend.js` (frontend functionality)
- `assets/js/post-actions.js` (post submission)
- `assets/js/uninstall.js` (uninstall process)

### Documentation
- `docs/` (comprehensive documentation)
- `tests/` (compatibility tests)

---

**Ready to deploy!** The v1.0.11-FIXED package includes all necessary files and the complete Wayback validation system to resolve your production server's stuck submission issues.

## What's New in v1.0.11

### 🚀 **Major Feature: Wayback Validation System**
- **Complete archive validation and reconciliation system**
- **Job ID capture** from Archive.org Save Page Now responses
- **Status polling** via `/save/status/<JOB_ID>` until completion
- **Availability API integration** for double-checking archives
- **Automatic cleanup** via cron job every 5 minutes
- **Manual validation** button in admin interface
- **Audit flagging** for items needing manual review
- **Comprehensive error handling** with proper error codes

### 🎯 **Production Impact**
- **Resolves 330+ stuck submissions** currently on production server
- **Prevents future submissions** from getting stuck in "processing" status
- **Provides manual tools** for troubleshooting archive issues
- **Ensures reliable archive completion** and status updates

## Update Methods

### Method 1: WordPress Admin Upload (Recommended)
1. **Download** `Archive-Forge-SWT-v1.0.11.zip` from your local machine
2. **Login** to WordPress admin: https://disruptarian.com/blog/wp-admin
3. **Navigate** to Plugins → Add New → Upload Plugin
4. **Upload** the zip file
5. **Activate** the plugin (it will replace the existing version)

### Method 2: File Manager Upload
1. **Login** to cPanel or file manager
2. **Navigate** to `/public_html/blog/wp-content/plugins/`
3. **Upload** `Archive-Forge-SWT-v1.0.11.zip`
4. **Extract** the zip file
5. **Replace** the existing `spun-web-archive-forge` folder

### Method 3: SSH Upload (Advanced)
```bash
# Connect to server
ssh Disrutparian@66.94.125.162

# Navigate to plugins directory
cd /path/to/wordpress/wp-content/plugins/

# Backup existing plugin
mv spun-web-archive-forge spun-web-archive-forge-backup

# Upload and extract new version
# (Upload Archive-Forge-SWT-v1.0.11.zip via SCP or other method)
unzip Archive-Forge-SWT-v1.0.11.zip
mv Archive-Forge-SWT-v1.0.11 spun-web-archive-forge

# Set proper permissions
chmod -R 755 spun-web-archive-forge
chown -R www-data:www-data spun-web-archive-forge
```

## Post-Update Testing

### 1. Verify Version Display
- **Check** admin page shows "ARCHIVE FORGE SWT v1.0.11"
- **Verify** branding and support information is correct

### 2. Test New Validation Features
- **Navigate** to Archive Forge → Queue Management
- **Look for** "Validate Archives" button
- **Click** "Validate Archives" to process stuck items
- **Monitor** the validation results

### 3. Test Queue Processing
- **Check** queue statistics for stuck items
- **Run** "Process Queue" to test normal processing
- **Verify** items move from "processing" to "archived" or "failed"

### 4. Monitor Cron Job
- **Check** if cron job is scheduled (every 5 minutes)
- **Monitor** debug logs for automatic processing
- **Verify** stuck items are being processed automatically

## Expected Results

### Immediate Impact
- **330+ stuck submissions** will be processed via manual validation
- **New submissions** will be properly tracked with job IDs
- **Status updates** will be more reliable and accurate

### Ongoing Benefits
- **Automatic processing** of stuck items every 5 minutes
- **Better error handling** and reporting
- **Improved reliability** of archive completion
- **Manual troubleshooting tools** for edge cases

## Troubleshooting

### If Validation Button Doesn't Work
1. **Check** browser console for JavaScript errors
2. **Verify** AJAX nonce is valid (refresh page)
3. **Check** debug logs for PHP errors

### If Cron Job Doesn't Run
1. **Verify** WordPress cron is enabled
2. **Check** server cron configuration
3. **Monitor** debug logs for cron execution

### If Archives Still Don't Complete
1. **Check** Archive.org API credentials
2. **Verify** network connectivity to Archive.org
3. **Review** error codes in submission history

## Support Information

- **Plugin Name:** ARCHIVE FORGE SWT
- **Version:** 1.0.11
- **Support:** support@spunwebtechnology.com
- **Website:** https://spunwebtechnology.com/spun-web-archive-pro-wordpress-wayback-archive/
- **IRC:** http://web.libera.chat/#spunwebtechnology (@spun_web)
- **Phone:** +1 (888) 264-6790

## Files Included in v1.0.11

### Core Files
- `spun-web-archive-forge.php` (main plugin file)
- `uninstall.php` (cleanup script)

### New Features
- `includes/class-swap-archiver.php` (Wayback validation system)

### Updated Files
- `includes/class-admin-page.php` (Validate Archives button)
- `assets/js/admin.js` (validation AJAX handler)
- `CHANGELOG.md` (version 1.0.11 details)
- `README.md` (updated version info)

### Assets
- `assets/css/admin.css` (styling)
- `assets/css/frontend.css` (frontend styling)
- `assets/js/frontend.js` (frontend functionality)
- `assets/js/post-actions.js` (post submission)
- `assets/js/uninstall.js` (uninstall process)

### Documentation
- `docs/` (comprehensive documentation)
- `tests/` (compatibility tests)

---

**Ready to deploy!** The v1.0.11 package includes all necessary files and the complete Wayback validation system to resolve your production server's stuck submission issues.
