# Critical Fix - Archive Forge SWT v1.0.15

## Issue Resolved: "Validation request failed" Error

**Problem**: On the live server with v1.0.14, clicking "Validate Archives" resulted in "Validation request failed" error.

**Root Cause**: AJAX nonce mismatch - JavaScript was using wrong nonce for validation requests.

## What Was Fixed

### 1. AJAX Nonce Mismatch
- **Issue**: JavaScript was using `swapAdmin.nonce` (for queue management) instead of the correct validation nonce
- **Fix**: Added separate `validateNonce` and `resetNonce` to wp_localize_script
- **Impact**: Validation requests now use correct nonce verification

### 2. Missing AJAX Action Registration
- **Issue**: `swap_reset_stuck_items` AJAX action was not registered
- **Fix**: Added `add_action('wp_ajax_swap_reset_stuck_items', [$this, 'ajax_reset_stuck_items']);`
- **Impact**: Reset stuck items functionality now works properly

### 3. JavaScript Nonce Error
- **Issue**: Validation function expected `swap_validate_now` nonce but received `swap_queue_management` nonce
- **Fix**: Updated JavaScript to use `swapAdmin.validateNonce` for validation requests
- **Impact**: Proper nonce verification for all AJAX requests

## Files Modified

1. **includes/class-admin-page.php**
   - Added missing AJAX action registration
   - Added separate nonces for validation and reset functions
   - Fixed nonce verification

2. **assets/js/admin.js**
   - Updated to use correct nonce for validation requests
   - Fixed AJAX data parameters

## Deployment Instructions

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
   - Click "Choose File" and select: `Archive-Forge-SWT-v1.0.15.zip`
   - Click "Install Now"
   - Click "Activate Plugin"

4. **Verify Fix**
   - Go to: Archive Forge → Queue Management
   - Click "Validate Archives" button
   - Should now work without "Validation request failed" error

### Method 2: File Manager Upload (Alternative)

1. **Access cPanel File Manager**
   - Login to hosting control panel
   - Open File Manager
   - Navigate to: `/public_html/blog/wp-content/plugins/`

2. **Backup Current Plugin**
   - Rename `spun-web-archive-forge` to `spun-web-archive-forge-backup`

3. **Upload New Version**
   - Upload `Archive-Forge-SWT-v1.0.15.zip`
   - Extract the zip file
   - Rename extracted folder to `spun-web-archive-forge`

4. **Set Permissions**
   - Right-click on `spun-web-archive-forge` folder
   - Set permissions to 755

## Testing the Fix

### 1. Test Validation Function
- Go to: Archive Forge → Queue Management
- Click "Validate Archives" button
- Should show processing results instead of error

### 2. Test Reset Function (if available)
- Look for "Reset Stuck Items" button
- Should work without errors

### 3. Check Browser Console
- Open browser developer tools (F12)
- Go to Console tab
- Click "Validate Archives" button
- Should see successful AJAX response instead of errors

## Expected Results

After deploying v1.0.15:

- ✅ **Validation Archives** button works without errors
- ✅ **Reset Stuck Items** button works (if present)
- ✅ **Proper nonce verification** for all AJAX requests
- ✅ **No more "Validation request failed" errors**
- ✅ **Complete validation functionality** restored

## Technical Details

### Nonce Security
- Each AJAX function now has its own nonce
- `validateNonce` for validation requests
- `resetNonce` for reset requests
- `nonce` for general queue management

### AJAX Actions Registered
- `swap_validate_now` - Validation function
- `swap_reset_stuck_items` - Reset stuck items function
- All other existing AJAX actions maintained

### JavaScript Updates
- Updated AJAX data parameters
- Proper nonce usage for each function
- Maintained backward compatibility

## Support Information

- **Plugin**: ARCHIVE FORGE SWT v1.0.15
- **Support**: support@spunwebtechnology.com
- **Website**: https://spunwebtechnology.com/spun-web-archive-pro-wordpress-wayback-archive/
- **IRC**: http://web.libera.chat/#spunwebtechnology (@spun_web)
- **Phone**: +1 (888) 264-6790

## Diagnostic Script

If issues persist, run the diagnostic script:
- Upload `live-server-validation-diagnostic.php` to your WordPress root
- Access via browser: `https://disruptarian.com/blog/live-server-validation-diagnostic.php`
- Review diagnostic output for specific issues

---

**Critical Fix**: This update resolves the validation error that was preventing users from validating stuck processing items on the live server.
