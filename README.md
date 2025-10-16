# ARCHIVE FORGE SWT

**Plugin URI:** https://spunwebtechnology.com/spun-web-archive-pro-wordpress-wayback-archive/  
**Version:** 1.0.14  
**Requires at least:** WordPress 5.0  
**Tested up to:** WordPress 6.8.2  
**Requires PHP:** 7.4  
**License:** GPL v2 or later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html
**Author:** Spun Web Technology  
**Author URI:** https://spunwebtechnology.com/spun-web-archive-pro-wordpress-wayback-archive/  
**Support Email:** support@spunwebtechnology.com  

## Recent Updates (v1.0.14)

### Major Stuck Processing Fix
The latest version (1.0.14) includes comprehensive fixes for URLs getting stuck in "processing" status:

- **Enhanced SWP_Archiver Class**: Complete overhaul of the Wayback validation system with better error handling
- **Improved API Timeouts**: Increased from 15s to 30s for better reliability with Archive.org services
- **Rate Limiting Protection**: Added intelligent delays between API calls to prevent rate limiting
- **Manual Reset Function**: New "Reset Stuck Items" functionality for manual intervention when needed
- **Enhanced Cron Job**: Better error handling and logging for automatic validation every 5 minutes
- **Comprehensive Error Logging**: Detailed debugging information for troubleshooting API issues
- **Production Ready**: More robust handling of production server conditions and network issues

### Plugin Evolution Summary

**Archive Forge SWT** has evolved significantly from its initial release to become a comprehensive WordPress archiving solution:

#### Version 1.0.x Series (Current)
- **v1.0.14**: Major stuck processing fixes, enhanced error handling, improved API reliability
- **v1.0.13**: UI improvements, backend optimization, enhanced version display
- **v1.0.12**: Queue processing enhancements, better status tracking
- **v1.0.11**: Memory optimization, performance improvements
- **v1.0.10**: Enhanced admin interface, better user experience

#### Version 0.3.x Series (Legacy)
- **v0.3.5**: WordPress compatibility improvements, development tools
- **v0.3.4**: Security enhancements, SQL query safety
- **v0.3.3**: Complete uninstall process, comprehensive documentation
- **v0.3.2**: Version consistency, code documentation
- **v0.3.1**: Improved error handling, user-friendly messages
- **v0.3.0**: WordPress native bulk actions, enhanced API integration

#### Version 0.2.x Series (Foundation)
- **v0.2.7**: Submission method selection, CSV export functionality
- **v0.2.6**: API test button fixes, enhanced debugging
- **v0.2.5**: Comprehensive documentation integration
- **v0.2.4**: Critical API test function fixes
- **v0.2.3**: Enhanced API integration, dual submission methods
- **v0.2.2**: Complete uninstall functionality, PHP 8.1 compatibility
- **v0.2.1**: PHP 8.1 compatibility, WordPress 6.7.1 support
- **v0.2.0**: Advanced submission tracking system overhaul

#### Version 0.1.x Series (Early Development)
- **v0.1.0**: Enhanced API testing, WordPress 6.7 compatibility
- **v0.0.1**: Initial development release with core functionality

### Key Milestones in Development

1. **Initial Release (v0.0.1)**: Core archiving functionality with basic API integration
2. **Tracking System (v0.2.0)**: Complete submission tracking and monitoring system
3. **API Enhancement (v0.2.3)**: Dual submission methods and enhanced reliability
4. **Security Focus (v0.3.4)**: Comprehensive security hardening
5. **Modern Architecture (v1.0.x)**: Production-ready with advanced error handling and reliability features

### Current Status
The plugin is now in its **production-ready phase** with v1.0.14 representing a mature, stable solution for WordPress content archiving. The latest version specifically addresses the most common production issue (stuck processing items) with comprehensive fixes and enhanced reliability features.

## Description

Archive your webpages and blog posts on WordPress to the Internet Archive Wayback Machine with Spun Web Archive Forge. This comprehensive WordPress plugin automatically submits your website content to the Internet Archive (Wayback Machine), ensuring your valuable content is preserved for future generations.

This professional version includes advanced features for individual post submission, automated archiving, and detailed submission tracking. Whether you're a blogger, content creator, or website owner, this plugin provides a seamless way to create permanent archives of your content with the world's largest digital preservation initiative.

## Support Information

**IRC Support:** @spun_web on http://web.libera.chat/#spunwebtechnology  
**Phone Support:** Toll Free +1 (888) 264-6790  
**Website:** https://spunwebtechnology.com/spun-web-archive-pro-wordpress-wayback-archive/  
**Email:** support@spunwebtechnology.com

## Why Archive Your Content?

The Internet Archive's Wayback Machine is a digital time capsule that preserves web content for future generations. By archiving your WordPress content, you:

- **Preserve Your Legacy**: Ensure your content remains accessible even if your website goes offline
- **Create Historical Records**: Document the evolution of your content and ideas over time
- **Improve SEO**: Archived content can provide additional backlinks and citation opportunities
- **Protect Against Data Loss**: Create an independent backup of your published content
- **Support Digital Preservation**: Contribute to the world's largest digital library and preservation effort
- **Enable Research**: Make your content available for academic research and historical study

### Forge Features

Spun Web Archive Forge includes all Pro features plus these premium enhancements:

- **Priority Support**: Direct email support with faster response times
- **Advanced Analytics**: Enhanced submission tracking and reporting
- **Premium Updates**: Priority access to new features and updates
- **Commercial License**: Full commercial usage rights for business websites
- **Extended Documentation**: Comprehensive guides and tutorials
- **Professional Branding**: Clean, professional interface without promotional content

### Key Features

- **Automatic Submission**: Automatically submit new posts and pages to the Internet Archive when published
- **Individual Post Submission**: Submit existing content directly from All Posts/Pages screens using individual "Submit to Archive" links
- **Enhanced Archive.org S3 API Integration**: Direct API connection with proper AWS S3 signature authentication
- **Centralized Credentials Management**: Secure, dedicated credentials page with encrypted storage and centralized access
- **Dual Submission Methods**: Wayback Machine Save API with S3 API fallback for maximum reliability
- **Visual API Test Connection**: Real-time connection testing with green "pass" and red "failed" indicators
- **API Test Callbacks**: Enhanced API testing with detailed callback information, response times, and status tracking
- **Enhanced Error Handling**: Comprehensive connection error detection with user-friendly messages for timeouts, DNS failures, and unreachable sites
- **Smart Error Recovery**: Automatic error type detection with specific guidance for different connection issues
- **Memory Optimization**: Advanced memory management with real-time monitoring, threshold alerts, and automatic optimization
- **Memory Dashboard**: Comprehensive memory usage tracking with visual indicators and performance metrics
- **Memory Utilities**: Built-in tools for memory analysis, cleanup, and optimization recommendations
- **Fixed Queue Settings Navigation**: Resolved non-functional queue settings link with proper URL hash navigation
- **Enhanced Settings Navigation**: Added direct navigation tab to submissions history page from main settings
- **Improved CSV Export Access**: CSV export functionality now available directly on submissions history page
- **Archive Widgets**: Two powerful widgets for displaying archive information on your site
- **Archive Shortcodes**: Flexible shortcodes for embedding archive links and status anywhere
- **Frontend Integration**: Beautiful, responsive frontend display with dark mode support
- **Flexible Configuration**: Comprehensive admin settings for customization
- **Advanced Submission Tracking**: Complete submission history with detailed status tracking
- **Post/Page Meta Boxes**: View submission history directly in the post editor
- **Dashboard Status Columns**: See archive status in posts/pages list view
- **Submission History Interface**: Dedicated admin tab to view all submission records
- **Retry Logic**: Automatic retry for failed submissions
- **Multiple Post Types**: Support for posts, pages, and custom post types
- **Scheduled Processing**: Background processing with WordPress cron
- **Admin Interface**: User-friendly tabbed dashboard with progress indicators

## Installation

1. Download the plugin files
2. Upload the `spun-web-archive-forge` folder to your `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Navigate to **Tools > Spun Web Archive Forge** to configure the plugin

## Configuration

### Archive.org API Setup

1. Create an account at [Archive.org](https://archive.org)
2. Generate your S3 API credentials:
   - Go to [Archive.org S3 API](https://archive.org/account/s3.php)
   - Create new access keys
3. In your WordPress admin, go to **Spun Web Archive Forge > API Credentials**
4. Enter your API Key and Secret in the secure credentials form
5. Click **Test Connection** to verify your credentials with real-time feedback
6. Once verified, your credentials are securely stored and available across all plugin features

### Auto Submission Settings

Configure automatic submission for new content:

- **Enable Auto Submission**: Toggle automatic archiving
- **Submission Delay**: Set delay (in minutes) before submission
- **Post Types**: Select which content types to auto-archive
- **Retry Settings**: Configure retry attempts and delays

### Individual Submission Settings

Configure individual post submission options:

- **Post Types**: Select which content types support individual submission
- **Submission Delay**: Set delay (in seconds) between individual submissions
- **Status Display**: Configure how submission status is displayed in post lists

## Usage

### Credentials Management

The plugin features a centralized credentials management system:

1. **Secure Storage**: API credentials are encrypted and stored securely in WordPress options
2. **Dedicated Interface**: Access credentials through **Spun Web Archive Forge > API Credentials**
3. **Real-time Testing**: Test your API connection with instant pass/fail feedback
4. **Centralized Access**: All plugin features automatically use the centralized credentials
5. **Status Indicators**: Visual indicators show whether credentials are configured and working
6. **Easy Management**: Update credentials in one place and they're available everywhere

### Automatic Submission

Once configured, the plugin will automatically:

1. Detect when new posts/pages are published
2. Queue them for submission based on your delay settings
3. Submit to the Internet Archive via API
4. Track submission status and retry if needed
5. Display archive status in your posts list

### Individual Post Submission

To archive existing content using individual submission links:

1. Go to **Posts > All Posts** or **Pages > All Pages** in your WordPress admin
2. For each post/page you want to archive, click the **Submit to Archive** link in the row actions
3. The post will be immediately queued for submission to the Internet Archive
4. Monitor submission status through the submission tracking system and status columns
5. Recently submitted posts will show "Recently Submitted" instead of the submission link

**Note:** Individual submissions use the same reliable submission process as the auto-submit feature, ensuring consistent archiving quality. Recently submitted posts will show "Recently Submitted" instead of the submit link to prevent duplicate submissions and provide clear visual feedback about the current status.

### Archive Widgets

The plugin includes two powerful widgets for displaying archive information on your website:

#### 1. Archive Profile Widget (`SWAP_Archive_Widget`)

Displays a link to your Internet Archive profile page:

- **Customizable Title**: Set a custom widget title
- **Link Text**: Customize the link text (default: "View Our Internet Archive")
- **Description**: Add optional description text below the link
- **Automatic Username Detection**: Uses configured username from API, queue, or display settings
- **Responsive Design**: Mobile-friendly with clean styling

**Usage:**
1. Go to **Appearance > Widgets** in your WordPress admin
2. Add the "Archive Profile" widget to your desired widget area
3. Configure the title, link text, and description
4. Save the widget

#### 2. Archive Links Widget (`SWAP_Archive_Links_Widget`)

Displays archive links for individual posts and pages:

- **Multiple Display Modes**:
  - **Current Post**: Shows archive links for the currently viewed post/page
  - **Recent Archives**: Displays recently archived content
  - **Popular Archives**: Shows most popular archived content
- **Customizable Settings**:
  - Widget title
  - Maximum number of items to display
  - Show/hide submission dates
  - Show/hide archive status
  - Custom link text
- **Smart Display**: Only shows when archive data is available
- **AJAX Refresh**: Dynamic content loading without page refresh

**Usage:**
1. Go to **Appearance > Widgets** in your WordPress admin
2. Add the "Archive Links" widget to your desired widget area
3. Configure display mode, title, and other options
4. Save the widget

### Archive Shortcodes

The plugin provides flexible shortcodes for embedding archive information anywhere in your content:

#### `[archive-link]` - Display Archive Link

Shows a link to the archived version of content:

```
[archive-link]
[archive-link post_id="123"]
[archive-link text="View Archive" class="my-archive-link"]
[archive-link post_id="123" text="See Archived Version" target="_blank"]
```

**Attributes:**
- `post_id` (optional): Specific post ID (defaults to current post)
- `text` (optional): Link text (default: "View Archive")
- `class` (optional): CSS class for styling
- `target` (optional): Link target (_blank, _self, etc.)

#### `[archive-status]` - Display Archive Status

Shows the current archive status of content:

```
[archive-status]
[archive-status post_id="123"]
[archive-status post_id="123" show_date="true"]
```

**Attributes:**
- `post_id` (optional): Specific post ID (defaults to current post)
- `show_date` (optional): Show submission date (true/false, default: false)

#### `[archive-list]` - Display Archive List

Shows a list of archived content:

```
[archive-list]
[archive-list type="recent" limit="5"]
[archive-list type="popular" limit="10" show_date="true"]
```

**Attributes:**
- `type` (optional): List type - "recent" or "popular" (default: "recent")
- `limit` (optional): Number of items to show (default: 5)
- `show_date` (optional): Show submission dates (true/false, default: false)
- `show_status` (optional): Show archive status (true/false, default: true)

#### `[archive-count]` - Display Archive Count

Shows the total number of archived items:

```
[archive-count]
[archive-count type="total"]
[archive-count type="successful"]
[archive-count type="failed"]
```

**Attributes:**
- `type` (optional): Count type - "total", "successful", "failed", or "pending" (default: "total")

### Frontend Styling

All widgets and shortcodes include:

- **Responsive Design**: Mobile-friendly layouts
- **Dark Mode Support**: Automatic dark mode detection and styling
- **Accessibility**: ARIA labels, keyboard navigation, and screen reader support
- **Custom CSS Classes**: Easy styling customization
- **Loading States**: Smooth loading animations
- **Error Handling**: Graceful fallbacks when data is unavailable

### Monitoring Submissions

- **Dashboard Columns**: View submission status directly in the Posts/Pages list with a dedicated "Archive Status" column
- **Post Editor Meta Boxes**: Check individual post archive history and status in the post editor sidebar
- **Submission History Tab**: Comprehensive submission records interface in the admin dashboard
- **Real-time Status Updates**: Live status tracking with color-coded indicators (Success, Failed, Pending)
- **Detailed Submission Records**: View submission timestamps, URLs, archive links, and response data
- **Pagination Support**: Browse through submission history with built-in pagination
- **Archive Statistics**: Quick overview of total, successful, failed, and pending submissions

## Features in Detail

### Smart Submission Queue

- Intelligent queuing system prevents API rate limiting
- Automatic retry logic for failed submissions
- Background processing doesn't slow down your site
- Detailed logging for troubleshooting

### Archive Status Tracking

- Real-time status updates (Pending, Archived, Failed)
- Submission timestamps and URLs
- Error logging and reporting
- Archive availability verification

### Admin Dashboard

- Clean, intuitive interface
- Individual submission tracking
- Real-time submission logs
- Archive statistics and reporting

### Developer Features

- WordPress coding standards compliant
- Extensive hooks and filters for customization
- Database optimization for large sites
- Comprehensive error handling

## Technical Requirements

- **WordPress:** 5.0 or higher
- **PHP:** 7.4 or higher
- **MySQL:** 5.6 or higher
- **Archive.org Account:** Required for API access
- **cURL:** Required for API communication

## Compatibility

This plugin is compatible with:

- **WordPress Multisite**: Full multisite network support
- **Popular Themes**: Works with any properly coded WordPress theme
- **Page Builders**: Compatible with Gutenberg, Elementor, Divi, and other page builders
- **SEO Plugins**: Works alongside Yoast SEO, RankMath, and other SEO plugins
- **Caching Plugins**: Compatible with WP Rocket, W3 Total Cache, and other caching solutions
- **Security Plugins**: Works with Wordfence, Sucuri, and other security plugins

### Tested With

- WordPress 6.0+
- PHP 8.0+
- MySQL 5.7+
- Popular hosting providers (WP Engine, SiteGround, Bluehost, etc.)

## Database Tables

The plugin creates one custom table:

- `wp_swap_submissions`: Tracks all submission attempts and statuses

## Hooks and Filters

### Actions

- `swap_before_submission`: Fired before submitting to archive
- `swap_after_submission`: Fired after submission attempt
- `swap_submission_success`: Fired on successful submission
- `swap_submission_failed`: Fired on failed submission

### Filters

- `swap_submission_url`: Modify URL before submission
- `swap_submission_data`: Modify submission data
- `swap_retry_attempts`: Customize retry attempts
- `swap_submission_delay`: Modify individual submission delay

## Memory Optimization

The plugin includes advanced memory management features to ensure optimal performance and prevent memory-related issues:

### Memory Dashboard

Access the memory dashboard through **Spun Web Archive Forge > Memory Dashboard** to monitor:

- **Current Memory Usage**: Real-time memory consumption tracking
- **Memory Limits**: PHP memory limit detection and recommendations
- **Peak Usage**: Historical memory usage peaks
- **Memory Efficiency**: Performance metrics and optimization suggestions
- **Visual Indicators**: Color-coded status indicators (green/yellow/red)

### Memory Utilities

Built-in memory management tools include:

- **Memory Analysis**: Detailed breakdown of memory usage by component
- **Automatic Cleanup**: Background memory optimization and garbage collection
- **Threshold Monitoring**: Configurable memory usage alerts
- **Performance Recommendations**: Actionable suggestions for memory optimization

### Memory Thresholds

The plugin automatically monitors memory usage and provides alerts when:

- Memory usage exceeds 80% of available limit (warning)
- Memory usage exceeds 90% of available limit (critical)
- Memory leaks are detected during processing
- Large queue processing may impact performance

### Technical Implementation

- **Singleton Pattern**: Prevents memory leaks from duplicate class instances
- **Efficient Database Queries**: Optimized queries to minimize memory footprint
- **Background Processing**: Memory-efficient queue processing
- **Resource Cleanup**: Automatic cleanup of temporary variables and objects

For detailed technical information about memory optimization features, see the [Memory Optimization Guide](MEMORY-OPTIMIZATION-GUIDE.md).

## Troubleshooting

### Common Issues

**API Connection Failed**
- Verify your Archive.org credentials
- Check your server's cURL configuration
- Ensure your server can make outbound HTTPS requests

**Submissions Not Processing**
- Check WordPress cron is working (`wp cron event list`)
- Verify plugin settings are saved correctly
- Review error logs in the admin dashboard

**Individual Submissions Feel Slow**
- Check server resources and API response times
- Individual submissions process one at a time for reliability
- Monitor submission status in the admin dashboard

### Debug Mode

Enable WordPress debug mode to see detailed error messages:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Frequently Asked Questions

### Is this plugin free?
Spun Web Archive Forge is a premium version available for $8.88. The Pro version is free and available on GitHub.

### Do I need an Archive.org account?
Yes, you need a free Archive.org account to generate API credentials for the plugin to work.

### How do I get Archive.org API credentials?
1. Create a free account at [Archive.org](https://archive.org)
2. Visit the [S3 API page](https://archive.org/account/s3.php)
3. Generate new access keys
4. Enter these credentials in the plugin's API Credentials page

### Will this slow down my website?
No, the plugin uses WordPress's built-in cron system to process submissions in the background, so it won't affect your site's performance.

### Can I archive existing content?
Yes, you can archive existing posts and pages using the individual "Submit to Archive" links in your WordPress admin.

### How long does it take for content to appear in the Wayback Machine?
Archive processing times vary, but content typically appears within a few minutes to several hours after submission.

### What happens if a submission fails?
The plugin includes automatic retry logic and detailed error reporting to help troubleshoot any issues.

### Can I archive custom post types?
Yes, the plugin supports posts, pages, and custom post types. You can configure which post types to archive in the settings.

### Is my API key secure?
Yes, API credentials are encrypted and stored securely using WordPress's built-in security features.

## Support

For technical support, feature requests, or bug reports:

- **Plugin Homepage:** [Spun Web Archive Forge](https://www.spunwebtechnology.com/spun-web-archive-forge-wordpress-wayback-archive/)
- **Documentation:** [Plugin Documentation](https://spunwebtechnology.com/spun-web-archive-forge-end-user-documentation/)
- **Email Support:** support@spunwebtechnology.com
- **Author Website:** [Spun Web Technology](https://spunwebtechnology.com)
- **Author:** Ryan Dickie Thompson

## Contributing

We welcome contributions to improve Spun Web Archive Forge! Here's how you can help:

### Reporting Issues
- Use the WordPress plugin support forum for general questions
- Report bugs with detailed reproduction steps
- Include your WordPress and PHP versions when reporting issues

### Development
- Fork the repository on GitHub
- Create a feature branch for your changes
- Follow WordPress coding standards
- Test your changes thoroughly
- Submit a pull request with a clear description

### Translation
- Help translate the plugin into your language
- Use WordPress.org's translation system
- Contact us if you'd like to become a translation editor

### Documentation
- Improve the documentation and help guides
- Add examples and use cases
- Suggest improvements to the user interface

## Changelog

### 0.3.5
**Enhanced**
- **WordPress Compatibility**: Added comprehensive WordPress environment validation and compatibility helper
- **Development Tools**: Improved IDE and linter support with WordPress function stubs and static analysis configuration
- **Code Quality**: Enhanced development workflow with proper tooling configuration for WordPress plugins
- **Documentation**: Added comprehensive developer documentation with setup instructions and troubleshooting
- **Linter Support**: Resolved false positive warnings from static analysis tools with proper WordPress plugin configuration

### 0.3.4
**Security**
- **Enhanced SQL Query Safety**: Improved security in uninstall cleanup process with proper query escaping
- **Database Security Hardening**: Updated transient and user meta deletion queries to use prepared statements
- **Table Name Escaping**: Implemented proper table name escaping for DROP TABLE operations during uninstall
- **Code Security Standards**: Enhanced security compliance throughout the plugin codebase
- **Version Consistency**: Updated all version references from 0.3.3 to 0.3.4 across the entire plugin

### 0.3.3
**Enhanced**
- **Complete Uninstall Process**: Enhanced cleanup process for complete data removal on plugin deletion
- **Comprehensive Documentation**: Improved README with detailed FAQ section and user guidance
- **Plugin Compatibility**: Added compatibility information for popular plugins and themes
- **System Requirements**: Updated requirements and testing information for better user guidance
- **User Experience**: Enhanced documentation with archiving benefits and detailed feature explanations

### 0.3.2
**Enhanced**
- **Version Consistency**: Improved version tracking across all plugin files
- **Code Documentation**: Enhanced inline comments and code documentation
- **Plugin Headers**: Updated plugin header information for better identification
- **Version Management**: Better version tracking and management system

### 0.3.1
**Enhanced**
- **Improved Error Handling**: Comprehensive connection error detection for Archive.org timeouts and unreachable sites
- **User-Friendly Error Messages**: Clear, actionable error messages including "This site can't be reached" for DNS failures
- **Smart Error Recovery**: Automatic error type detection with specific guidance for timeouts, connection refused, and SSL errors
- **Enhanced Visual Feedback**: Improved error display with color-coded status indicators and detailed error explanations
- **Better Connection Diagnostics**: Enhanced API testing with specific error categorization and troubleshooting guidance

### 0.3.0
**Added**
- **WordPress Native Bulk Actions**: Submit existing content directly from All Posts/Pages screens using WordPress bulk actions
- **Enhanced Archive.org S3 API Integration**: Direct API connection with proper AWS S3 signature authentication
- **Centralized Credentials Management**: Secure, dedicated credentials page with encrypted storage and centralized access
- **API Test Callbacks**: Enhanced API testing with detailed callback information, response times, and status tracking
- **Streamlined Admin Interface**: Improved tabbed navigation with better organization and visual hierarchy

**Enhanced**
- Updated submission workflow to use WordPress native bulk actions for better integration
- Improved API connection testing with real-time feedback and detailed diagnostics
- Enhanced security with centralized credential management and encrypted storage
- Better user experience with streamlined interface and clearer navigation

### 0.2.7
**Added**
- **Submission Method Selection**: Choose between Simple Submission (no API required) and API Submission (advanced) with radio button interface
- **Non-API Submission Method**: Direct submission to Wayback Machine without requiring Archive.org API credentials
- **Comprehensive Method Explanation**: Detailed comparison between API and non-API submission methods with pros/cons
- **CSV Export Functionality**: Download complete submission history as CSV with local URLs and archive.org links
- **Enhanced Form Validation**: Real-time validation with visual error indicators and user-friendly messaging
- **Improved User Experience**: Better visual feedback, clearer instructions, and streamlined workflow

**Enhanced**
- Updated submission history to properly display archive.org links
- Improved admin interface with better organization and visual hierarchy
- Enhanced error handling and user feedback throughout the plugin
- Better documentation and help text for all features

### 0.2.6
**Fixed**
- Enhanced API test button functionality with comprehensive debugging
- Improved JavaScript error handling and console logging
- Better AJAX error reporting and troubleshooting capabilities
- Enhanced PHP error logging for API connection testing

**Enhanced**
- Added detailed debugging output for API test functionality
- Improved error messages and user feedback
- Better handling of missing JavaScript objects and AJAX failures

### 0.2.5
**Added**
- Comprehensive documentation page integrated into admin dashboard
- Direct access to plugin documentation via Documentation tab
- Complete user guide covering installation, configuration, and usage

**Enhanced**
- Improved admin interface with dedicated documentation section
- Better user onboarding experience with integrated help

### 0.2.4
- **Critical API Test Function Fix** - Resolved nonce mismatch preventing API connection testing
- **Archive API Initialization** - Fixed missing Archive API instance in AJAX handlers  
- **Enhanced Error Logging** - Added comprehensive debugging and error logging for troubleshooting
- **AJAX Nonce Consistency** - Updated all AJAX handlers to use consistent nonce verification
- **Improved Debugging** - Added detailed AJAX response logging and error handling

### 0.2.3
- **Enhanced API Test Connection** - Proper Archive.org S3 API integration with real-time validation
- **Visual Connection Feedback** - Green "pass" and red "failed" indicators for API test results
- **Dual Submission Methods** - Wayback Machine Save API with S3 API fallback for maximum reliability
- **Improved S3 API Implementation** - Proper AWS S3 signature authentication following Archive.org documentation
- **Updated Documentation Link** - New end-user documentation URL for better support
- **Enhanced Error Handling** - Comprehensive error reporting and status messages
- **Better Archive Integration** - Improved URL submission process with multiple fallback methods

### 0.2.2
- **Complete Uninstall Functionality** - Removes all plugin data on deletion
- **Enhanced PHP 8.1 Compatibility** - Maintained backward compatibility for broader server support
- **Code Organization Improvements** - Eliminated duplicate functions and improved architecture
- **Security Enhancements** - Strengthened uninstall security and AJAX request handling

### 0.2.1
- **PHP 8.1 Compatibility** - Updated minimum PHP requirement to 8.1 for better performance and security
- **WordPress 6.7.1 Compatibility** - Tested and verified compatibility with latest WordPress version
- **Code Optimization** - Fixed deprecated `mysql2date` function, replaced with `wp_date` for better compatibility
- **Bug Fixes** - Resolved duplicate method issues and improved plugin activation reliability
- **Performance Improvements** - Optimized database queries and added proper indexing for better performance
- **Security Enhancements** - Updated security standards and improved input validation

### 0.2.0
- **NEW: Advanced Submission Tracking System** - Complete overhaul of submission monitoring
- **NEW: Submission History Interface** - Dedicated admin tab to view all submission records with pagination
- **NEW: Post/Page Meta Boxes** - View submission history directly in the post editor sidebar
- **NEW: Dashboard Status Columns** - Archive status column in Posts/Pages list view with sortable functionality
- **NEW: Submission Tracker Class** - Centralized submission tracking with database integration
- **Enhanced Admin Interface** - Improved tabbed navigation with dedicated submission history section
- **Database Integration** - Comprehensive submission logging with timestamps and status tracking
- **Real-time Status Updates** - Color-coded status indicators (Success, Failed, Pending)
- **Archive Statistics** - Quick overview dashboard showing submission counts and success rates
- **Improved User Experience** - Better visual feedback and submission monitoring capabilities

### 0.1.0
- Enhanced API test button with improved Archive.org S3 API connection testing
- Added "Settings" link to Dashboard Plugins page for easy access
- Configuration page automatically appears in WordPress Settings menu
- Updated plugin version display with hyperlink to plugin page
- WordPress 6.7 compatibility
- Improved security standards and error handling
- Enhanced user interface with better navigation links

### 0.0.1
- Initial development release
- Auto submission functionality for new posts and pages
- Bulk submission tools for existing content
- Admin configuration interface with modern UI
- Archive.org API integration with secure authentication
- Submission tracking and status monitoring
- Advanced retry mechanisms for failed submissions
- Comprehensive error handling and logging
- WordPress 6.7 compatibility
- Enhanced security measures and input validation

## Uninstall

Spun Web Archive Forge provides two uninstall options:

### Standard Plugin Deletion
For temporary removal or if you want to preserve your data:
1. Go to **Plugins → Installed Plugins**
2. Deactivate "Spun Web Archive Forge"
3. Click "Delete" to remove plugin files
4. Your settings and data will be preserved for future reinstallation

### Complete Data Removal
For permanent removal of all plugin data:
1. Go to **Settings → Uninstall Archive Elite**
2. Read the warning information carefully
3. Check the confirmation box: "Yes, I understand this will permanently delete all plugin data"
4. Click "Remove All Plugin Data"
5. Confirm in the popup dialogs

**⚠️ Warning:** Complete data removal will permanently delete:
- All plugin settings and configuration
- Submission history database table
- All post metadata created by the plugin
- API credentials and connection settings
- Scheduled tasks and cron jobs
- All cached data and transients
- User preferences and widget settings

This action cannot be undone!

## License

This plugin is licensed under the GPL v2 or later.
License URI: https://www.gnu.org/licenses/gpl-2.0.html

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

## Credits

**Author:** Ryan Dickie Thompson  
**Company:** Spun Web Technology  
**Website:** https://spunwebtechnology.com  
**Plugin URI:** https://spunwebtechnology.com/spun-web-archive-forge-wordpress-wayback-archive/  

Developed by Spun Web Technology - Your trusted partner for WordPress solutions and web archiving services.

---

**Note:** This plugin requires an Archive.org account and API credentials. The Internet Archive is a non-profit organization dedicated to preserving digital content for future generations.