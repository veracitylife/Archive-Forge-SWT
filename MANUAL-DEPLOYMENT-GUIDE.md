# Manual Production Server Deployment Guide
# Archive Forge SWT v1.0.11-FIXED

## 🚨 URGENT: Queue Processing Issue Diagnosis

**Current Problem**: 264 pending, 75 processing, 0 completed/failed
**Root Cause**: Items move to "processing" but never complete
**Solution**: Deploy v1.0.11-FIXED with Wayback validation system

## Server Details
- **Server**: 66.94.125.162
- **SSH User**: Disrutparian  
- **SSH Password**: =LbW[50jr^p86eL$
- **WP Admin**: https://disruptarian.com/blog/wp-admin
- **WP User**: admin_wf582umf
- **WP Password**: pHwI0Jeg8fhp~!0x

## Deployment Methods

### Method 1: WordPress Admin Upload (FASTEST)
1. **Download** `Archive-Forge-SWT-v1.0.11-FIXED.zip` to your computer
2. **Login** to WordPress: https://disruptarian.com/blog/wp-admin
3. **Go to** Plugins → Add New → Upload Plugin
4. **Upload** the zip file
5. **Activate** the plugin (replaces existing version)

### Method 2: File Manager Upload
1. **Login** to cPanel/hosting control panel
2. **Open** File Manager
3. **Navigate** to `/public_html/blog/wp-content/plugins/`
4. **Upload** `Archive-Forge-SWT-v1.0.11-FIXED.zip`
5. **Extract** the zip file
6. **Replace** existing `spun-web-archive-forge` folder

### Method 3: SSH Manual Commands (If SSH Works)
```bash
# Connect to server
ssh Disrutparian@66.94.125.162

# Find WordPress installation
find /home -name "wp-config.php" -type f | head -1

# Navigate to plugins directory (replace with actual path)
cd /path/to/wordpress/wp-content/plugins/

# Backup existing plugin
mv spun-web-archive-forge spun-web-archive-forge-backup-$(date +%Y%m%d-%H%M%S)

# Upload Archive-Forge-SWT-v1.0.11-FIXED.zip to /tmp/ first
# Then extract and install:
unzip /tmp/Archive-Forge-SWT-v1.0.11-FIXED.zip
chmod -R 755 spun-web-archive-forge
chown -R www-data:www-data spun-web-archive-forge
```

## Post-Deployment Testing

### 1. Verify Installation
- **Check** admin page shows "ARCHIVE FORGE SWT v1.0.11"
- **Look for** "Validate Archives" button in Queue Management

### 2. Test Validation System
- **Go to** Archive Forge → Queue Management
- **Click** "Validate Archives" button
- **Monitor** results - should process stuck items
- **Check** queue statistics for changes

### 3. Test Queue Processing
- **Click** "Process Queue Now" button
- **Verify** items move from pending → processing → completed/failed
- **Check** that items don't get stuck in processing

## Expected Results After Deployment

### Immediate Impact
- **75 stuck processing items** should be processed via "Validate Archives"
- **264 pending items** should process normally
- **Queue statistics** should show completed/failed items

### New Features Available
- ✅ **Job ID Capture** - Extracts job IDs from Archive.org responses
- ✅ **Status Polling** - Checks `/save/status/<JOB_ID>` until completion  
- ✅ **Availability API** - Double-checks archives with Archive.org API
- ✅ **Automatic Cleanup** - Cron job every 5 minutes
- ✅ **Manual Validation** - "Validate Archives" button
- ✅ **Audit Flagging** - Marks items needing review
- ✅ **Error Handling** - Comprehensive error codes

## Troubleshooting

### If "Validate Archives" Button Doesn't Work
1. **Check** browser console for JavaScript errors
2. **Refresh** page to get fresh nonce
3. **Check** debug logs for PHP errors
4. **Verify** SWP_Archiver class is loaded

### If Items Still Stay in Processing
1. **Check** Archive.org API credentials
2. **Test** network connectivity to Archive.org
3. **Verify** cron job is scheduled
4. **Check** debug logs for errors

### If Cron Job Doesn't Run
1. **Verify** WordPress cron is enabled
2. **Check** server cron configuration
3. **Monitor** debug logs for cron execution
4. **Test** manual validation button

## Diagnostic Script

Run this diagnostic script to understand the current state:

```php
// Upload queue-diagnostic.php to WordPress root and run via browser
// Or run via WP-CLI: wp eval-file queue-diagnostic.php
```

## Support Information

- **Plugin**: ARCHIVE FORGE SWT v1.0.11
- **Support**: support@spunwebtechnology.com
- **Website**: https://spunwebtechnology.com/spun-web-archive-pro-wordpress-wayback-archive/
- **IRC**: http://web.libera.chat/#spunwebtechnology (@spun_web)
- **Phone**: +1 (888) 264-6790

---

## 🎯 CRITICAL: This Update Will Fix Your Processing Issue

The v1.0.11-FIXED package includes the complete Wayback validation system that will:
- **Process your 75 stuck items** immediately
- **Prevent future items** from getting stuck
- **Provide manual tools** for troubleshooting
- **Ensure reliable completion** of archive submissions

**Deploy this update ASAP to resolve your queue processing issues!**

