# Production Server Update Guide - Archive Forge SWT v1.0.10

## 🎯 **Update Target**
- **Server**: 66.94.125.162
- **User**: Disrutparian
- **Password**: =LbW[50jr^p86eL$
- **WordPress Admin**: https://disruptarian.com/blog/wp-admin
- **Admin User**: admin_wf582umf
- **Admin Pass**: pHwI0Jeg8fhp~!0x

## 📦 **Package Ready**
- **File**: Archive-Forge-SWT-v1.0.10-FIXED.zip (139 KB)
- **Status**: ✅ Ready for upload
- **Structure**: ✅ Correct WordPress plugin format

## 🔧 **Update Methods**

### Method 1: WordPress Admin Upload (Recommended)
1. **Login**: Go to https://disruptarian.com/blog/wp-admin
2. **Credentials**: admin_wf582umf / pHwI0Jeg8fhp~!0x
3. **Navigate**: Plugins > Add New > Upload Plugin
4. **Upload**: Archive-Forge-SWT-v1.0.10-FIXED.zip
5. **Activate**: Click "Activate Plugin"

### Method 2: SSH Manual Upload
1. **Connect**: SSH to 66.94.125.162 as Disrutparian
2. **Navigate**: Go to WordPress root directory
3. **Backup**: Create backup of current plugin
4. **Extract**: Extract zip file to wp-content/plugins/
5. **Activate**: Via WordPress admin

### Method 3: File Manager Upload
1. **Access**: cPanel File Manager or similar
2. **Navigate**: public_html/blog/wp-content/plugins/
3. **Upload**: Archive-Forge-SWT-v1.0.10-FIXED.zip
4. **Extract**: Extract in plugins directory
5. **Activate**: Via WordPress admin

## ✅ **Verification Checklist**

After update, verify:
- [ ] Version badge shows "v1.0.10"
- [ ] Branding shows "ARCHIVE FORGE SWT"
- [ ] All admin tabs are functional
- [ ] Archive.org URLs work correctly
- [ ] "Powered by" link points to correct URL
- [ ] Queue processing works
- [ ] API credentials page accessible

## 🚨 **Troubleshooting**

### If SSH fails:
- Use WordPress admin upload method
- Check server accessibility
- Verify credentials

### If plugin upload fails:
- Check file permissions
- Verify zip file integrity
- Try manual file extraction

### If activation fails:
- Check PHP version compatibility
- Review error logs
- Verify file structure

## 📋 **Files Updated in v1.0.10**

- spun-web-archive-forge.php (version & branding)
- includes/class-archive-widget.php (URL fixes)
- includes/class-footer-display.php (URL fixes)
- includes/class-admin-page.php (URL fixes)
- assets/js/admin.js (enhanced functionality)
- assets/css/admin.css (styling improvements)

## 🎯 **Expected Results**

After successful update:
- Plugin version: 1.0.10
- Branding: ARCHIVE FORGE SWT
- Support links: All correct URLs
- Archive widget: Fixed URL construction
- Admin interface: Fully functional tabs
- Queue management: Working properly

## 📞 **Support Information**

- **Plugin Name**: ARCHIVE FORGE SWT
- **Support**: @spun_web on IRC
- **Server**: http://web.libera.chat/#spunwebtechnology
- **Phone**: +1 (888) 264-6790
- **Website**: https://spunwebtechnology.com/spun-web-archive-pro-wordpress-wayback-archive/
- **Email**: support@spunwebtechnology.com
