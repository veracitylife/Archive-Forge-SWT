# WordPress Admin Deployment Instructions for Archive Forge SWT v1.0.12

## Production Server Update via WordPress Admin

**Server**: https://disruptarian.com/blog/wp-admin
**Username**: admin_wf582umf  
**Password**: pHwI0Jeg8fhp~!0x

### Method 1: WordPress Admin Upload (Recommended)

1. **Login to WordPress Admin**
   - Go to: https://disruptarian.com/blog/wp-admin
   - Username: admin_wf582umf
   - Password: pHwI0Jeg8fhp~!0x

2. **Navigate to Plugins**
   - Go to: Plugins → Installed Plugins
   - Find "ARCHIVE FORGE SWT" plugin
   - Click "Deactivate" if it's active

3. **Upload New Version**
   - Go to: Plugins → Add New → Upload Plugin
   - Click "Choose File" and select: `Archive-Forge-SWT-v1.0.12.zip`
   - Click "Install Now"
   - Click "Activate Plugin"

4. **Verify Installation**
   - Check that version shows "1.0.12"
   - Go to: Archive Forge → Queue Management
   - Look for "Validate Archives" button

### Method 2: File Manager Upload (Alternative)

1. **Access cPanel File Manager**
   - Login to hosting control panel
   - Open File Manager
   - Navigate to: `/public_html/blog/wp-content/plugins/`

2. **Backup Current Plugin**
   - Rename `spun-web-archive-forge` to `spun-web-archive-forge-backup`

3. **Upload New Version**
   - Upload `Archive-Forge-SWT-v1.0.12.zip`
   - Extract the zip file
   - Rename extracted folder to `spun-web-archive-forge`

4. **Set Permissions**
   - Right-click on `spun-web-archive-forge` folder
   - Set permissions to 755

### What's Fixed in v1.0.12

- **CRITICAL FIX**: Resolved PHP syntax error in `SWP_Archiver` constructor
- **CRITICAL FIX**: Added `SWP_Archiver` class to plugin dependencies array
- **CRITICAL FIX**: Fixed parameter naming conflict in `SWP_Archiver` constructor
- **Enables**: "Validate Archives" button functionality
- **Enables**: Automatic cron job processing every 5 minutes
- **Resolves**: Items stuck in "processing" status (264 pending, 75 processing)

### Post-Update Testing

1. **Test Queue Processing**
   - Go to: Archive Forge → Queue Management
   - Click "Validate Archives" button
   - Monitor processing of stuck items

2. **Verify Cron Job**
   - Check if cron job is scheduled (every 5 minutes)
   - Monitor debug logs for automatic processing

3. **Test New Features**
   - Test "Process Queue" functionality
   - Verify queue statistics update correctly
   - Check submission history for completed archives

### Expected Results

- **330+ stuck submissions** will be processed via manual validation
- **New submissions** will be properly tracked with job IDs
- **Status updates** will be more reliable and accurate
- **Automatic processing** of stuck items every 5 minutes

### Support Information

- **Plugin**: ARCHIVE FORGE SWT v1.0.12
- **Support**: support@spunwebtechnology.com
- **Website**: https://spunwebtechnology.com/spun-web-archive-pro-wordpress-wayback-archive/
- **IRC**: http://web.libera.chat/#spunwebtechnology (@spun_web)
- **Phone**: +1 (888) 264-6790
