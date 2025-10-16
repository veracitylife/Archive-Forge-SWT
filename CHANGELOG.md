# Changelog

All notable changes to this project will be documented in this file.

## Plugin Evolution Overview

**Archive Forge SWT** has undergone significant evolution from its initial development to become a comprehensive WordPress archiving solution. This changelog documents the complete development journey, highlighting major milestones, feature additions, and improvements that have shaped the plugin into its current production-ready state.

### Development Phases

1. **Foundation Phase (v0.0.1 - v0.1.x)**: Core functionality and basic API integration
2. **Feature Expansion Phase (v0.2.x)**: Advanced tracking, submission methods, and user interface improvements
3. **Stability Phase (v0.3.x)**: Security hardening, compatibility improvements, and documentation
4. **Production Phase (v1.0.x)**: Mature, stable solution with advanced error handling and reliability features

---

## [1.0.15] - 2025-10-17

### Fixed
- **Validation Request Failed** - Fixed critical issue causing "Validation request failed" error
- **AJAX Nonce Mismatch** - Corrected nonce verification for validation and reset functions
- **Missing AJAX Action** - Added missing swap_reset_stuck_items AJAX action registration
- **JavaScript Nonce Error** - Fixed JavaScript using wrong nonce for validation requests

### Technical Details
- Added validateNonce and resetNonce to wp_localize_script
- Updated JavaScript to use correct nonce for validation requests
- Registered missing swap_reset_stuck_items AJAX action
- Fixed nonce verification in validation and reset functions

### Impact
- **Resolves Validation Error** - Fixes the "Validation request failed" error on live servers
- **Proper Nonce Security** - Ensures correct nonce verification for all AJAX requests
- **Complete Functionality** - Both validation and reset functions now work properly
- **Production Ready** - Critical fix for production server validation issues

---

## [1.0.14] - 2025-10-17

### Enhanced
- **Stuck Processing Fix** - Major improvements to resolve URLs stuck in "processing" status
- **Enhanced Error Handling** - Better error logging and debugging for Wayback API issues
- **Improved Timeout Management** - Increased API timeouts from 15s to 30s for better reliability
- **Rate Limiting Protection** - Added delays between API calls to prevent rate limiting
- **Manual Reset Function** - New "Reset Stuck Items" functionality for manual intervention
- **Enhanced Cron Job** - Better error handling and logging for automatic validation
- **API Reliability** - Improved User-Agent headers and SSL verification

### Technical Details
- Enhanced SWP_Archiver class with comprehensive error handling
- Added reset_stuck_items() method for manual intervention
- Improved API timeout and retry logic
- Enhanced cron job error handling and logging
- Added rate limiting protection with sleep delays
- Better error logging throughout the validation system

### Impact
- **Resolves Stuck Processing** - Fixes the main issue causing URLs to remain in "processing" status
- **Improved Reliability** - Better handling of API timeouts and network issues
- **Better Debugging** - Enhanced error logging for troubleshooting
- **Manual Recovery** - New reset function for stuck items
- **Production Ready** - More robust handling of production server conditions

---

## Development History Summary

### Major Version Milestones

#### Version 1.0.x Series (Production Ready - Current)
- **Focus**: Production stability, error handling, and reliability
- **Key Features**: Advanced stuck processing fixes, enhanced API reliability, comprehensive error handling
- **Status**: Mature, stable solution ready for production environments

#### Version 0.3.x Series (Stability & Security)
- **Focus**: Security hardening, compatibility improvements, documentation
- **Key Features**: Enhanced security, WordPress compatibility, comprehensive uninstall process
- **Status**: Legacy stable version with security focus

#### Version 0.2.x Series (Feature Expansion)
- **Focus**: Advanced features, user interface improvements, submission methods
- **Key Features**: Submission tracking system, dual API methods, CSV export, bulk actions
- **Status**: Feature-complete foundation for modern versions

#### Version 0.1.x Series (Early Development)
- **Focus**: Core functionality and basic API integration
- **Key Features**: Basic archiving, API testing, WordPress compatibility
- **Status**: Initial development and testing phase

### Key Technical Achievements

1. **API Integration Evolution**: From basic submission to dual-method API integration with fallback systems
2. **Error Handling Maturity**: From basic error reporting to comprehensive debugging and recovery systems
3. **User Experience**: From simple interface to advanced dashboard with real-time monitoring
4. **Security Hardening**: From basic security to comprehensive input validation and SQL injection prevention
5. **Performance Optimization**: From basic functionality to memory management and queue optimization
6. **Production Readiness**: From development tool to enterprise-ready solution

---

## [1.0.13] - 2025-10-17

### Enhanced
- **Version Update** - Updated to version 1.0.13 for continued development and improvements
- **UI Improvements** - Enhanced admin interface with better version display
- **Backend Optimization** - Improved plugin performance and stability
- **Code Quality** - Enhanced error handling and user feedback

### Technical Details
- Updated SWAP_VERSION constant to 1.0.13
- Enhanced admin page version display
- Improved plugin initialization and activation
- Better error handling throughout the plugin

### Impact
- **Improved Stability** - Enhanced error handling and user experience
- **Better Performance** - Optimized plugin initialization and processing
- **Enhanced UI** - Better version display and admin interface

---

## [1.0.12] - 2025-10-16

### Fixed
- **CRITICAL FIX**: Resolved PHP syntax error in `SWP_Archiver` constructor that prevented Wayback validation system from loading
- **CRITICAL FIX**: Added `SWP_Archiver` class to plugin dependencies array to ensure proper loading
- **CRITICAL FIX**: Fixed parameter naming conflict in `SWP_Archiver` constructor (`$wpdb` vs global `$wpdb`)

### Technical Details
- Fixed `global $wpdb as $wpdb_global;` syntax error (invalid PHP syntax)
- Corrected constructor logic to properly handle wpdb parameter vs global wpdb
- Added `class-swap-archiver.php` to plugin dependencies list
- Added `SWP_Archiver` to components initialization array

### Impact
- **Resolves**: Items stuck in "processing" status (264 pending, 75 processing, 0 completed/failed)
- **Enables**: "Validate Archives" button functionality
- **Enables**: Automatic cron job processing every 5 minutes
- **Enables**: Manual queue processing and stuck item resolution

---

## [1.0.11] - 2025-01-24

### Added
- **Wayback Validation System** - Complete archive validation and reconciliation system
- **Job ID Capture** - Extract and store Save Page Now job IDs from Archive.org responses
- **Status Polling** - Poll `/save/status/<JOB_ID>` until completion
- **Availability API Integration** - Double-check archives with Archive.org Availability API
- **Automatic Cleanup** - Cron job runs every 5 minutes to process stuck items
- **Manual Validation** - "Validate Archives" button in admin interface
- **Audit Flagging** - Mark items that need manual review
- **Comprehensive Error Handling** - Proper error codes and failure states

### Enhanced
- **Queue Management** - Enhanced queue processing with validation capabilities
- **Admin Interface** - Added Validate Archives button with real-time feedback
- **Cron Scheduling** - Automatic 5-minute schedule for stuck item processing
- **Database Schema** - Enhanced submissions table with job_id, snapshot_url, snapshot_ts, error_code, needs_audit columns

### Technical Details
- New `SWP_Archiver` class handles all Wayback Machine validation logic
- Cron hook `swap_validate_archives_cron` processes stuck items automatically
- AJAX endpoint `swap_validate_now` for manual validation
- Enhanced admin JavaScript with validation feedback
- Proper nonce verification and security checks
- Integration with existing queue management system

### Production Impact
- Resolves 330+ stuck submissions on production server
- Prevents future submissions from getting stuck in "processing" status
- Provides manual tools for troubleshooting archive issues
- Ensures reliable archive completion and status updates

## [1.0.10] - 2025-01-24

### Fixed
- **Archive.org URL Format** - Fixed archive.org URL construction with proper username trimming and encoding
- **"Powered by" Link** - Updated default "Powered by" link to point to the correct product page
- **Widget URL Debugging** - Added debug logging to help identify archive.org URL issues

### Enhanced
- **URL Validation** - Improved archive.org URL format validation and error handling
- **Default URLs** - Updated all default author URLs to point to the correct product page

### Technical Details
- Archive.org URLs now use proper `trim()` function to remove whitespace
- Default author URL changed from `https://spunwebtechnology.com` to `https://spunwebtechnology.com/spun-web-archive-pro-wordpress-wayback-archive/`
- Added debug logging for archive.org URL construction in widget
- Archive.org pages may appear "blank" initially due to JavaScript loading - this is normal behavior

## [1.0.9] - 2025-01-24

### Enhanced
- **Complete Branding Update** - Updated plugin name to "ARCHIVE FORGE SWT" with complete branding and support information
- **Support Information** - Added comprehensive support information with correct URLs and contact details
- **URL Corrections** - Updated all URLs to point to official Spun Web Technology sales page

### Support Information
- **Plugin Name**: ARCHIVE FORGE SWT
- **Support User/Bot**: @spun_web on Libera Chat
- **IRC Server**: http://web.libera.chat/#spunwebtechnology
- **IRC Channel**: #spunwebtechnology
- **Phone Support**: Toll Free +1 (888) 264-6790
- **Website**: https://spunwebtechnology.com/spun-web-archive-pro-wordpress-wayback-archive/
- **Email**: support@spunwebtechnology.com

## [1.0.8] - 2025-01-24

### Enhanced
- **Version Update** - Updated to version 1.0.8 with new branding as "Archive Forge SWT"
- **Branding Update** - Changed plugin name from "Spun Web Archive Forge" to "Archive Forge SWT"
- **Author Update** - Updated author from "Ryan Dickie Thompson" to "Spun Web Technology"
- **Support Information** - Added comprehensive support information including IRC channel, phone number, and website
- **UI Improvements** - Enhanced admin interface with better branding and support contact information

### Support Information
- **IRC Support**: @spun_web on http://web.libera.chat/#spunwebtechnology
- **Phone Support**: Toll Free +1 (888) 264-6790
- **Website**: spunwebtechnology.com
- **Email**: support@spunwebtechnology.com

## [1.0.7] - 2025-01-24

### Enhanced
- **Version Update** - Updated to version 1.0.7 for continued development and improvements
- **Code Stability** - Maintained stable codebase with ongoing optimizations

## [1.0.6] - 2025-01-24

### Removed
- **Auto-Version Functionality** - Removed development-only auto-version system
- **SWAP_Auto_Version** - Removed auto-version class and related files
- **auto-version-hook.php** - Removed development CLI hook file

### Enhanced
- **Code Cleanup** - Streamlined codebase by removing development-only features
- **Plugin Optimization** - Reduced plugin footprint for production use

## [1.0.4] - 2025-01-24

### Enhanced
- **Version Management** - Updated to stable 1.0.4 release
- **Documentation** - Updated all documentation files to reflect version 1.0.4
- **Memory Optimization** - Comprehensive memory optimization features and documentation
- **Constructor Fix** - Fixed SWAP_Auto_Version singleton pattern implementation

### Fixed
- **SWAP_Auto_Version** - Resolved constructor error with proper singleton pattern
- **Memory Management** - Enhanced memory monitoring and optimization capabilities

## [1.0.2] - 2025-01-24

### Enhanced
- **Database Handling** - Improved database access patterns by replacing global $wpdb usage with class properties
- **Code Quality** - Enhanced object-oriented design and dependency injection patterns
- **Widget Registration** - Fixed duplicate widget registration issues
- **Version Management** - Updated to stable 1.0.2 release

### Fixed
- **SWAP_Submissions_History** - Replaced global $wpdb with $this->wpdb property
- **SWAP_Post_Actions** - Added proper $wpdb property initialization
- **SWAP_Submission_Tracker** - Enhanced database handling with class properties
- **SWAP_Uninstall_Page** - Improved database operations for uninstall functionality

## [0.9.9] - 2025-01-21

### Fixed
- **Static Method Call Error** - Fixed fatal error where non-static method SWAP_Database_Migration::maybe_migrate() was being called statically
- **Database Migration** - Corrected instantiation of SWAP_Database_Migration class before calling instance methods

### Enhanced
- **Error Handling** - Improved proper object-oriented method calling patterns
- **Code Quality** - Enhanced static analysis compliance and method call consistency

## [0.9.8] - 2025-01-21

### Fixed
- **Critical Syntax Error** - Fixed incorrect global variable declaration in class-database-migration.php line 66
- **Parse Error Resolution** - Resolved "unexpected token 'as'" error that was causing 503 server errors
- **Database Migration** - Fixed constructor logic for proper wpdb handling

### Enhanced
- **WordPress Type Definitions** - Added comprehensive WordPress class definitions (WP_Post, WP_User, WP_Query)
- **Function Stubs** - Extended WordPress function stubs for better IDE support and static analysis
- **Code Quality** - Enhanced type safety and method signature compatibility

### Technical
- **Syntax Validation** - All PHP files now pass syntax checks without errors
- **Type Safety** - Improved type definitions for WordPress core classes and functions
- **Development Tools** - Enhanced WordPress stubs file with additional classes and methods

## [0.9.7] - 2025-01-21

### Fixed
- **Syntax Error** - Fixed missing concatenation operator in class-auto-submitter.php line 488
- **503 Error Resolution** - Resolved PHP parse error that was causing server errors

### Technical
- **Code Quality** - Improved string concatenation in archive link display functionality
- **Error Prevention** - Enhanced code stability and error handling

## [0.9.6] - 2025-01-21

### Changed
- **Plugin Rebranding** - Renamed from "Spun Web Archive Elite" to "Spun Web Archive Forge"
- **Author Information** - Updated author to "Ryan Dickie Thompson"
- **Author URL** - Updated to "https://www.spunwebtechnology.com"
- **Plugin URI** - Updated to reflect new branding
- **Text Domain** - Updated to "spun-web-archive-forge" for consistency

### Technical
- **File Updates** - Updated all documentation files with new plugin name
- **Branding Consistency** - Ensured all references use the new "Forge" branding
- **Version Bump** - Incremented version to 0.9.6 for major rebranding release

## [0.7.0] - 2025-01-20

### Added
- **Archive Links Widget** - New widget for displaying archive links with multiple display modes (profile, links, combined)
- **Shortcode System** - Comprehensive shortcode handler with four new shortcodes:
  - `[archive-link]` - Display archive link for specific post or current post
  - `[archive-status]` - Show archive status with customizable text
  - `[archive-list]` - Display list of recent or popular archived posts
  - `[archive-count]` - Show total count of archived posts
- **Frontend Assets** - Dedicated CSS and JavaScript files for frontend functionality
- **AJAX Integration** - Dynamic content loading for widgets and shortcodes
- **Enhanced UI** - Modern styling with animations and improved user experience

### Enhanced
- **Main Plugin File** - Updated with new component integration and AJAX handlers
- **Admin Interface** - Enhanced CSS and JavaScript with modern UI components
- **Widget System** - Improved widget registration and management
- **Frontend Styling** - Responsive design with customizable appearance options

### Technical
- **New Files Added**:
  - `includes/class-archive-links-widget.php` - Archive links widget implementation
  - `includes/class-shortcode-handler.php` - Shortcode processing and rendering
  - `assets/css/frontend.css` - Frontend styling for widgets and shortcodes
  - `assets/js/frontend.js` - Frontend JavaScript functionality
- **PHP Compatibility** - Updated minimum requirement to PHP 7.4+
- **WordPress Integration** - Enhanced hooks and filters for better WordPress integration

## [0.6.1] - 2025-01-21

### Fixed
- **Code Quality** - Resolved all PHP syntax errors and function redeclaration conflicts
- **WordPress Compatibility** - Enhanced WordPress stubs integration for better testing environment
- **Test Environment** - Fixed function conflicts in test files with proper `function_exists()` checks

### Technical
- **WordPress Stubs**: Improved `.wordpress-stubs.php` integration across test files
- **Function Safety**: Added proper function existence checks to prevent redeclaration errors
- **Code Validation**: All PHP files now pass syntax validation without errors

## [0.5.9] - 2025-01-21

### Fixed
- **Settings Save Issue** - Fixed Archive.org Username field not being saved in API Settings section
- **Form Processing** - Added missing `archive_username` field to API settings save logic in admin page

### Technical
- **Admin Interface**: Enhanced `class-admin-page.php` to properly save Archive.org Username field from API Settings
- **Version Consistency**: Updated all version references from 0.5.8 to 0.5.9 across the entire plugin

## [0.5.8] - 2025-01-20

### Enhanced
- **Archive Queue System** - Added `check_pending_archives` method to automatically check for archived versions of submitted content
- **Cron Job Integration** - Enhanced cron job system to automatically capture archive URLs for pending submissions
- **Database Query Enhancement** - Updated `get_submissions` method to support filtering by `archive_url` parameter
- **Widget Display Fixes** - Improved personal archive link display in widget section with better username resolution
- **Footer Display Fixes** - Enhanced personal archive link display in footer section with consistent username handling
- **Plugin Page Link Control** - Added "Show Plugin Page Link" setting to control display of plugin page links in admin areas

### Fixed
- **Username Resolution** - Fixed inconsistent username resolution logic across widget and footer components
- **Archive Link Display** - Resolved issues with blank archive links in widget and footer sections
- **Settings Consistency** - Improved username retrieval from `api_settings`, `queue_settings`, and `display_settings` in proper order

### Technical
- **Archive Queue**: Enhanced `class-archive-queue.php` with automated archive URL checking functionality
- **Database Queries**: Updated `class-submissions-history.php` with improved filtering capabilities
- **Widget System**: Fixed `class-archive-widget.php` username resolution and added debug logging
- **Footer System**: Enhanced `class-footer-display.php` with consistent username handling
- **Admin Interface**: Added plugin page link control option in `class-admin-page.php`
- **Version Consistency**: Updated all version references from 0.5.7 to 0.5.8 across the entire plugin

## [0.5.7] - 2025-01-20

### Enhanced
- **Archive.org Username Section** - Reorganized the Archive Display Settings section to combine username field with show/hide radio buttons
- **User Interface Improvements** - Improved layout and intuitiveness of the Archive.org Username configuration
- **Radio Button Integration** - Added direct Show/Hide radio buttons for archive link visibility within the username section
- **Layout Optimization** - Enhanced spacing and visual organization of the Queue Settings tab

### Fixed
- **Missing Show/Hide Controls** - Resolved issue where Archive.org Username section lacked radio buttons for link visibility
- **Section Organization** - Fixed separation between username input and visibility controls for better user experience
- **Interface Clarity** - Improved labeling and descriptions for archive link display options

### Technical
- **Admin Interface**: Reorganized Archive Display Settings in `class-admin-page.php` for better UX
- **Form Layout**: Combined username field with visibility radio buttons in a single, cohesive section
- **Version Consistency**: Updated all version references from 0.5.6 to 0.5.7 across the entire plugin

## [0.5.6] - 2025-01-20

### Enhanced
- **Database Migration System** - Improved database migration to ensure submissions history table is created during plugin activation
- **Submissions History Reliability** - Fixed issue where submissions history table wasn't being created, causing empty history pages
- **Migration Versioning** - Enhanced database version tracking with proper migration sequence (1.0 → 1.1 → 1.2)
- **Table Creation Logic** - Strengthened table creation process to handle both queue and history tables consistently

### Fixed
- **Missing History Table** - Resolved critical issue where `swap_submissions_history` table wasn't created during migration
- **Empty History Pages** - Fixed submissions history page showing no entries due to missing database table
- **Migration Gaps** - Added comprehensive migration path from version 1.1 to 1.2 for history table creation
- **Database Consistency** - Ensured both archive queue and submissions history tables are created reliably

### Technical
- **Database Version**: Updated DB_VERSION from 1.1 to 1.2 to trigger new migration
- **Migration Method**: Added `migrate_to_1_2()` method specifically for submissions history table creation
- **Table Creation**: Enhanced `create_history_table()` method with proper error handling
- **Version Consistency**: Updated all version references from 0.5.5 to 0.5.6 across the entire plugin

## [0.5.5] - 2025-01-20

### Technical Changes
- **Version Update**: Updated all version references from 0.5.4 to 0.5.5 following versioning protocol

## [0.5.4] - 2025-01-20

### Enhanced
- **Submissions History Integration** - Posts now immediately appear in submissions history when added to queue
- **Real-time Status Updates** - Queue processing now properly updates submissions history with success/failure status
- **Archive URL Integration** - Successful submissions now display clickable archive links in submissions history
- **Database Synchronization** - Improved synchronization between queue table and submissions history table

### Fixed
- **Immediate Visibility** - Resolved issue where posts didn't appear in submissions history until processing completed
- **Status Tracking** - Fixed status updates from pending → successful/failed during queue processing
- **Return Value Handling** - Updated auto-submitter to return full API results instead of boolean values
- **Duplicate Prevention** - Added checks to prevent duplicate entries in submissions history

### Technical
- **Queue Integration**: Modified `add_to_queue` method to create submissions history entries immediately
- **Status Updates**: Enhanced queue processor to update submissions history table during processing
- **API Integration**: Updated `submit_immediately` method to return complete result arrays with archive URLs
- **Data Consistency**: Improved synchronization between archive queue and submissions history tables

## [0.5.3] - 2025-01-20

### Fixed
- **Authentication Issue** - Resolved 403 Forbidden error in admin dashboard access caused by URL callback handler
- **URL Callback Logic** - Fixed callback token validation to properly distinguish between admin access and API callbacks
- **Admin Dashboard Access** - Restored full functionality of admin interface after Queue Settings implementation

### Technical
- **Callback Handler**: Modified `handle_url_callback` method to return early for empty tokens instead of throwing 403 errors
- **Authentication Logic**: Improved token validation to prevent false positives on normal admin page loads
- **Security**: Maintained proper security checks while fixing accessibility issues
- **Version Updates**: Updated all version references from 0.5.2 to 0.5.3 following versioning protocol

### Improved
- **User Experience** - Seamless admin dashboard access without authentication errors
- **System Stability** - More robust callback handling that doesn't interfere with normal operations

## [0.5.2] - 2025-01-20

### Added
- **Enhanced Queue Settings Tab** - Comprehensive queue management interface with real-time statistics and controls
- **Queue Statistics Dashboard** - Live display of pending, processing, completed, and failed submission counts
- **Queue Management Controls** - Manual queue processing, clear completed items, and clear failed items buttons
- **Recent Queue Items Display** - Shows the 5 most recent queue submissions with status and timestamps
- **Queue Configuration Settings** - Configurable processing interval, maximum retry attempts, and auto-clear options
- **Archive Display Settings** - Reorganized existing display settings under dedicated section for better organization
- **AJAX Queue Operations** - Real-time queue management without page refreshes

### Enhanced
- **Queue Settings Interface** - Complete redesign with organized sections for statistics, controls, recent items, and configuration
- **User Experience** - Intuitive queue management with immediate feedback and status updates
- **Administrative Control** - Fine-grained control over queue processing behavior and cleanup operations
- **Real-time Updates** - Dynamic statistics refresh and queue status monitoring

### Technical
- **AJAX Handlers**: Added `swap_process_queue`, `swap_clear_completed`, `swap_clear_failed`, and `swap_refresh_queue_stats` endpoints
- **Queue Management**: Enhanced admin page with JavaScript-powered queue controls and statistics
- **Database Operations**: Optimized queue queries for statistics and management operations
- **Security**: Proper nonce verification and capability checks for all queue management operations
- **Version Updates**: Updated all version references from 0.5.1 to 0.5.2 following versioning protocol

### Improved
- **Queue Visibility** - Better insight into queue status and performance
- **Administrative Efficiency** - Streamlined queue management workflow
- **Error Handling** - Robust error handling for queue operations
- **Code Organization** - Clean separation of queue management functionality

## [0.5.1] - 2025-01-20

### Fixed
- **403 Forbidden Error** - Fixed missing callback token initialization during plugin activation
- **API Callbacks** - Resolved authentication issues with API callback URLs
- **Plugin Activation** - Added proper callback token generation to prevent access denied errors

### Technical
- **Callback Token**: Added automatic generation of `swap_callback_token` during plugin activation
- **Version Updates**: Updated all version references from 0.5.0 to 0.5.1 following new versioning protocol
- **Security Enhancement**: Improved API callback security with proper token initialization

## [0.5.0] - 2025-01-20

### Added
- **WordPress Stubs Enhancement** - Added comprehensive WordPress function definitions to `.wordpress-stubs.php`
- **Linter Compatibility** - Enhanced IDE and linter support with missing WordPress core functions
- **Code Quality Improvements** - Resolved all undefined function warnings in development environment

### Changed
- **Version Consistency** - Updated all version references from 0.4.0 to 0.5.0 across the entire plugin
- **Development Experience** - Improved code completion and error detection in IDEs
- **Documentation Updates** - Enhanced inline documentation and version tracking

### Enhanced
- **Developer Tools** - Better IDE integration with complete WordPress function stubs
- **Code Validation** - All PHP files now pass syntax validation without warnings
- **Plugin Architecture** - Maintained backward compatibility while improving development workflow

### Technical
- **WordPress Stubs**: Added 95+ missing WordPress functions and constants to `.wordpress-stubs.php`
- **Version Updates**: Updated all @version tags in PHP class files to 0.5.0
- **Plugin Header**: Updated main plugin file version constant and header information
- **Documentation**: Updated README.md and CHANGELOG.md with new version information
- **Quality Assurance**: Verified all PHP files pass syntax validation

### Fixed
- **Undefined Functions** - Resolved linter warnings for WordPress core functions
- **IDE Integration** - Fixed code completion issues in development environment
- **Syntax Validation** - All plugin files now validate without errors

## [0.4.0] - 2025-01-20

### Added
- **Queue Settings Navigation Fix** - Fixed non-functional queue settings link with proper URL hash navigation
- **Submissions History Tab** - Added navigation tab on settings page linking to submissions history
- **CSV Export on History Page** - Added CSV export button directly on the submissions history page
- **Enhanced Tab Navigation** - Improved JavaScript tab switching with URL hash support

### Changed
- **Settings Page Navigation** - Enhanced with direct link to submissions history page
- **User Experience** - Streamlined access to history and export functionality
- **Tab Functionality** - Fixed queue settings tab accessibility via direct URL

### Enhanced
- **Admin Interface** - Better navigation between settings and history pages
- **Data Export** - More accessible CSV export functionality
- **URL Navigation** - Support for direct linking to specific settings tabs

### Technical
- **JavaScript Enhancement**: Updated `admin.js` with URL hash navigation support
- **Admin Page Updates**: Added submissions history navigation tab
- **History Page Enhancement**: Integrated CSV export functionality
- **Settings Integration**: Fixed queue settings tab functionality

## [0.3.9] - 2025-01-20

### Added
- **Complete Uninstall System** - New comprehensive uninstall page with user confirmation
- **Data Removal Controls** - Checkbox confirmation for removing all plugin data
- **Uninstall Warning System** - Multiple confirmation popups to prevent accidental data loss
- **Admin Uninstall Page** - Dedicated page in Settings → Uninstall Archive Elite
- **AJAX Uninstall Process** - Smooth, non-blocking uninstall with progress indication
- **Comprehensive Data Cleanup** - Removes all database tables, options, post meta, and cached data

### Changed
- **Enhanced Uninstall Process** - Improved from basic cleanup to full user-controlled removal
- **User Experience** - Added visual progress indicators and success/error messaging
- **Security Measures** - Multiple confirmation steps to prevent accidental data deletion

### Enhanced
- **Data Management** - Complete control over plugin data retention vs. removal
- **User Safety** - Clear warnings and multiple confirmation steps
- **Admin Interface** - Professional uninstall page with detailed information
- **Documentation** - Clear instructions for both standard and complete removal

### Technical
- **New Class**: `SWAP_Uninstall_Page` for handling uninstall interface
- **JavaScript Enhancement**: `uninstall.js` for confirmation and AJAX handling
- **Database Cleanup**: Enhanced uninstall.php with comprehensive data removal
- **Settings Integration**: New admin menu item for uninstall functionality

## [0.3.8] - 2025-01-20

### Added
- **Archive.org Username Field**: New username field in API Settings for personal archive linking
- **Enhanced Widget Support**: Archive widget now displays personal archive links using configured username
- **Enhanced Footer Display**: Footer display now shows personal archive information with proper visibility controls
- **Visibility Controls**: Radio button controls to show/hide personal archive links on frontend
- **Multiple Username Sources**: Support for username configuration in API settings, queue settings, and display settings with proper fallback priority

### Changed
- **Username Priority**: Updated username resolution to check API settings first, then queue settings, then display settings
- **Footer Controls**: Footer display now uses queue settings for enable/disable functionality
- **Consistent Linking**: All archive links now properly format as `https://archive.org/details/@username`

### Enhanced
- **User Experience**: Clear instructions and examples for Archive.org username configuration
- **Flexibility**: Multiple configuration locations for username with intelligent fallback system
- **Control**: Granular visibility controls for frontend archive link display
- **Integration**: Seamless integration between settings, widgets, and footer displays

### Technical Details
- Updated widget class to check multiple username sources with priority fallback
- Enhanced footer display class with proper queue settings integration
- Added comprehensive syntax validation for all PHP files
- Improved settings consistency across different configuration sections
- Added proper input sanitization and URL escaping for security

## [0.3.7] - 2024-01-20

### Changed
- **MAJOR REBRAND**: Complete transformation from "Spun Web Archive Pro" to "Spun Web Archive Forge"
- Updated all plugin branding, text domains, and references throughout codebase
- Changed text domain from 'spun-web-archive-pro' to 'spun-web-archive-elite'
- Updated plugin file name from spun-web-archive-pro.php to spun-web-archive-forge.php
- Updated all documentation files (README.md, SECURITY.md, DEVELOPER-README.md)
- Updated plugin URIs and documentation links to Elite branding
- Updated admin menu references and page slugs
- Positioned as premium Elite version with enhanced features and support

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.3.6] - 2025-01-20

### Added
- **Archive Page Widget**: New widget to display user's Internet Archive page (@username) in website sidebars
- **Footer Archive Display**: Option to automatically display user's Archive.org page link in website footer
- **Archive Queue System**: Replaced direct submission with queue-based archiving system for better reliability
- **Hourly Cron Processing**: Automated queue processing every hour to submit pages to Internet Archive
- **Archive Status Indicators**: Green checkboxes showing archived status for posts in wp-admin/edit.php
- **Queue Management**: Database table to store and manage archive submission queue
- **User Archive Configuration**: Settings to configure @username for Archive.org page display

### Changed
- **Submit to Archive Queue**: Replaced "Submit to Archive" with "Submit to Archive Queue" in post actions
- **Submission Workflow**: Posts are now queued and processed automatically rather than submitted immediately
- **Admin Interface**: Updated settings page with queue explanation and archive page configuration options

### Enhanced
- **Reliability**: Queue system prevents submission failures and allows for retry mechanisms
- **User Experience**: Clear visual indicators for archived content status
- **Automation**: Reduced manual intervention with automated hourly processing
- **Customization**: Users can now showcase their Archive.org contributions on their website

### Technical Details
- Added new database table `wp_swap_archive_queue` for queue management
- Implemented WordPress cron job `swap_process_archive_queue` for hourly processing
- Created widget class for Archive.org page display functionality
- Added footer hook integration for archive page links
- Enhanced post list interface with archive status indicators
- Updated admin settings with @username configuration and queue explanation

## [0.3.5] - 2025-01-20

### Added
- **WordPress Compatibility Helper**: New `wordpress-compat.php` file for enhanced WordPress environment validation
- **WordPress Function Stubs**: Comprehensive `.wordpress-stubs.php` file for IDE and linter compatibility
- **Static Analysis Configuration**: Added `phpstan.neon` for proper WordPress plugin analysis
- **Developer Documentation**: New `DEVELOPER-README.md` with setup instructions and troubleshooting

### Enhanced
- **Linter Compatibility**: Resolved false positive warnings from static analysis tools
- **Development Environment**: Improved IDE support with WordPress function signatures
- **Code Quality**: Enhanced static analysis configuration for WordPress plugins
- **Documentation**: Comprehensive developer setup and troubleshooting guide

### Technical Details
- Added WordPress environment validation functions in compatibility helper
- Created comprehensive WordPress core function stubs for development tools
- Configured PHPStan for WordPress plugin development best practices
- Updated all version references from 0.3.4 to 0.3.5 across the entire codebase
- Enhanced development workflow with proper tooling configuration

## [0.3.4] - 2025-01-20

### Security
- **Enhanced SQL Query Safety**: Improved security in uninstall cleanup process with proper query escaping
- **Database Security Hardening**: Updated transient and user meta deletion queries to use prepared statements
- **Table Name Escaping**: Implemented proper table name escaping for DROP TABLE operations during uninstall

### Technical Details
- Modified `uninstall.php` to use `$wpdb->prepare()` for transient and user meta deletion queries
- Added proper escaping for database table names in cleanup operations
- Enhanced security comments and documentation for database operations
- Updated all version references from 0.3.3 to 0.3.4 across the entire codebase
- Improved code security standards compliance throughout the plugin

## [0.3.2] - 2025-01-20

### Changed
- **Removed Bulk Actions**: Eliminated WordPress bulk actions functionality from All Posts and All Pages screens
- **Individual Post Submission**: Replaced bulk actions with individual "Submit to Archive" links for each post/page
- **Streamlined UI**: Removed bulk submission settings and references from admin interface
- **Simplified Workflow**: Focus on individual post submission for better control and user experience

### Removed
- `SWAP_Bulk_Actions` class and all bulk action functionality
- `SWAP_Bulk_Submitter` class and bulk submission processing
- Bulk submission settings from admin page (batch size, delay settings)
- Bulk submission references from documentation and UI

### Added
- `SWAP_Post_Actions` class for individual post submission links
- Individual "Submit to Archive" row actions in post/page lists
- On-demand submission functionality similar to auto-submit feature

### Technical Details
- Updated plugin description to reflect individual submission focus
- Removed bulk action hooks and processing from WordPress admin
- Updated all version references to 0.3.2 across codebase
- Enhanced post list interface with individual submission controls

## [0.3.1] - 2025-01-19

### Enhanced
- **Comprehensive Error Handling**: Added robust connection error detection for Archive.org timeouts and unreachable sites
- **User-Friendly Error Messages**: Implemented clear, actionable error messages including "This site can't be reached" for DNS failures
- **Smart Error Recovery**: Added automatic error type detection with specific guidance for timeouts, connection refused, and SSL errors
- **Enhanced Visual Feedback**: Improved error display with color-coded status indicators and detailed error explanations in admin interface
- **Better Connection Diagnostics**: Enhanced API testing with specific error categorization and troubleshooting guidance
- **Improved JavaScript Error Handling**: Enhanced AJAX error handling with dynamic error messages and conditional warnings
- **CSS Error Styling**: Added comprehensive CSS styles for test results, error states, and visual feedback components

### Technical Details
- Updated `class-archive-api.php` with enhanced error detection for `wp_remote_get` calls
- Modified `class-credentials-page.php` with improved AJAX response handling and structured error responses
- Enhanced JavaScript error handling in credentials testing interface with specific timeout and connection error messages
- Added CSS styles in `admin.css` for improved visual feedback on error states and test results
- Implemented error type classification system with `error_type` and `redirect_to_settings` response parameters

## [0.3.0] - 2025-01-14

### Added
- **WordPress Native Bulk Actions**: Integrated bulk submission directly into WordPress All Posts and All Pages screens
- **SWAP_Bulk_Actions Class**: New dedicated class for handling WordPress bulk actions with proper hooks and processing
- **Seamless User Experience**: Submit multiple posts/pages to archive using familiar WordPress bulk action interface
- **Batch Processing Integration**: Bulk actions respect existing batch size and delay settings for optimal performance

### Enhanced
- **User Interface**: Replaced dedicated bulk submission tab with native WordPress bulk actions for better workflow integration
- **Code Organization**: Streamlined bulk submission functionality into dedicated bulk actions handler
- **Performance**: Optimized bulk processing using WordPress native bulk action system
- **Documentation**: Updated README.md to reflect new bulk actions functionality and usage instructions

### Removed
- **Legacy Bulk Submission Interface**: Removed dedicated bulk submission tab from admin settings page
- **Old AJAX Handlers**: Cleaned up legacy `ajax_submit_single` method and related bulk submission AJAX functionality
- **Redundant UI Elements**: Simplified admin interface by removing duplicate bulk submission controls

### Technical Details
- Added `SWAP_Bulk_Actions` class with proper WordPress hooks for `edit-post` and `edit-page` screens
- Implemented `handle_bulk_action` method for processing bulk archive submissions
- Integrated with existing `SWAP_Bulk_Submitter` class for consistent submission handling
- Updated all version references from 0.2.9 to 0.3.0 across the codebase
- Maintained backward compatibility with existing bulk submission settings and configuration

## [0.2.9] - 2025-01-14

### Added
- **Centralized Credentials Management**: New dedicated credentials page with secure storage and management
- **Secure Credentials Storage**: Encrypted storage of API credentials using WordPress options
- **Real-time API Testing**: Instant connection testing with visual pass/fail feedback on credentials page
- **Credentials Status Indicators**: Visual indicators showing credential configuration status across the plugin
- **Dedicated Admin Menu**: New "Spun Web Archive Elite" main menu with submenus for credentials, settings, and documentation
- **Centralized Access**: All plugin features automatically use centralized credentials without duplication

### Enhanced
- **Admin Menu Structure**: Converted from options submenu to main menu with organized submenus
- **Credentials Integration**: Updated all classes to use centralized credentials with backward compatibility
- **User Interface**: Improved admin interface with credential status and management links
- **Security**: Enhanced credential handling with proper sanitization and validation
- **Code Organization**: Streamlined credential management across all plugin components

### Technical Details
- Added `SWAP_Credentials_Page` class for centralized credential management
- Enhanced `SWAP_Archive_API` class to use centralized credentials with fallback support
- Updated admin page to show credential status instead of inline credential forms
- Removed duplicate API testing functionality in favor of centralized testing
- Added proper menu structure with `add_menu_page` and organized submenus
- Enhanced JavaScript and AJAX handling for new menu structure
- Updated all version references to 0.2.9 across the codebase

### Removed
- **Duplicate Credential Forms**: Removed redundant API credential inputs from main settings
- **Old API Testing**: Removed duplicate API testing functionality from main admin page
- **Legacy AJAX Handlers**: Cleaned up old API testing AJAX handlers

## [0.2.8] - 2025-01-14

### Added
- **API Test Callbacks**: Enhanced API testing with detailed callback functionality
- **Test Result Storage**: Comprehensive test result tracking with transient storage
- **Response Time Monitoring**: Real-time response time tracking for API connections
- **Detailed Test Information**: Enhanced test results showing endpoint, status codes, headers, and error details
- **Callback URL Generation**: Dynamic callback URLs for test result retrieval
- **Test ID System**: Unique test identification for tracking and callback correlation
- **Enhanced Admin Interface**: Improved API test section with callback options and detailed results display

### Enhanced
- Updated Archive API class with callback support methods
- Enhanced JavaScript admin interface for callback result display
- Improved AJAX handlers to support callback functionality
- Better error handling and logging for API test operations
- Enhanced user interface with detailed test information display

### Technical Details
- Added `SWAP_API_Callback` class for handling test callbacks and result storage
- Enhanced `test_connection` method with callback parameters and result storage
- Added helper methods: `store_test_result`, `log_connection_attempt`, `sanitize_headers_for_log`
- Updated JavaScript to handle callback results and display detailed test information
- Added callback URL generation and test result retrieval endpoints
- Enhanced admin page with callback options and results display sections

## [0.2.7] - 2025-01-14

### Added
- **Submission Method Selection**: Radio button interface to choose between Simple Submission (no API required) and API Submission (advanced)
- **Non-API Submission Method**: Direct submission to Wayback Machine without requiring Archive.org API credentials
- **Comprehensive Method Explanation**: Detailed comparison section explaining differences between API and non-API methods with pros/cons
- **CSV Export Functionality**: Download complete submission history as CSV file with local URLs and archive.org links
- **Enhanced Form Validation**: Real-time validation with visual error indicators and user-friendly messaging
- **Improved User Experience**: Better visual feedback, clearer instructions, and streamlined workflow

### Enhanced
- Updated submission history to properly display archive.org links instead of local links
- Improved admin interface with better organization and visual hierarchy
- Enhanced error handling and user feedback throughout the plugin
- Better documentation and help text for all submission methods
- Strengthened security with proper nonce verification for CSV exports

### Technical Details
- Added `submit_to_wayback_simple` method for non-API submissions
- Modified `submit_url` method to handle submission method selection
- Enhanced admin page with conditional API credentials section and explanatory content
- Added JavaScript for radio button toggling, form validation, and dynamic UI updates
- Implemented CSV export handler with proper security checks and data formatting
- Added comprehensive method comparison interface with styled grid layout

## [0.2.6] - 2025-01-14

### Fixed
- Enhanced API test button functionality with comprehensive debugging capabilities
- Improved JavaScript error handling and console logging for better troubleshooting
- Better AJAX error reporting and user feedback mechanisms
- Enhanced PHP error logging for API connection testing and validation

### Enhanced
- Added detailed debugging output for API test functionality
- Improved error messages and user feedback throughout the plugin
- Better handling of missing JavaScript objects and AJAX request failures
- Enhanced console logging for easier debugging and development

### Technical Details
- Added comprehensive console logging to JavaScript admin functions
- Enhanced AJAX error handling with detailed error reporting
- Improved PHP error logging in AJAX handlers for better debugging
- Added fallback handling for missing JavaScript objects and variables

## [0.2.5] - 2024-12-30

### Added
- Comprehensive documentation page integrated into admin dashboard
- Direct access to plugin documentation via Documentation tab in admin interface
- Complete user guide covering installation, configuration, daily usage, and troubleshooting
- Dedicated documentation class for better code organization

### Enhanced
- Improved admin interface with dedicated documentation section
- Better user onboarding experience with integrated help system
- Enhanced navigation with documentation tab in admin panel

### Technical Details
- Added `SWAP_Documentation_Page` class for documentation management
- Integrated documentation display into existing admin page structure
- Updated admin navigation to include documentation access

## [0.2.4] - 2024-12-302025-01-14

### Fixed
- **Critical API Test Function Fix**: Resolved nonce mismatch preventing API connection testing
- **Archive API Initialization**: Fixed missing Archive API instance in AJAX handlers
- **AJAX Nonce Consistency**: Updated all AJAX handlers to use consistent nonce verification
- **Enhanced Error Logging**: Added comprehensive debugging and error logging for troubleshooting
- **JavaScript Console Logging**: Added detailed AJAX response logging for better debugging

### Changed
- Improved AJAX error handling with detailed status and error information
- Enhanced debugging capabilities for API connection testing
- Better error feedback and logging throughout the plugin

### Technical Details
- Fixed nonce creation to use 'swap_ajax_nonce' consistently across JavaScript and PHP
- Added Archive API initialization check in AJAX handlers to prevent undefined property errors
- Updated both 'ajax_test_api' and 'ajax_get_posts' handlers for consistent nonce verification
- Added server-side error logging and client-side console logging for comprehensive debugging

## [0.2.3] - 2025-01-14

### Added
- Enhanced API test connection with proper Archive.org S3 API integration
- Visual feedback for API test results with green "pass" and red "failed" indicators
- Dual submission method: Wayback Machine Save API and S3 API fallback
- Proper AWS S3 signature authentication for Archive.org
- Comprehensive error handling and status reporting

### Fixed
- API test connection now properly validates credentials against Archive.org
- Corrected S3 API implementation following Archive.org documentation
- Improved URL submission process with better success detection
- Enhanced error messages for better user feedback

### Changed
- Updated documentation link to new end-user documentation URL
- Improved API connection testing with real-time status display
- Enhanced submission workflow with multiple fallback methods
- Better integration with Archive.org's S3 API endpoints

### Security
- Implemented proper AWS S3 signature authentication
- Enhanced API credential validation and error handling
- Improved secure communication with Archive.org services

## [0.2.2] - 2025-01-14

### Added
- Complete uninstall functionality that removes all plugin data
- Proper cleanup of database tables, options, post meta, and transients on plugin deletion
- Enhanced security checks in uninstall process
- Comprehensive data removal including user meta and cached data

### Fixed
- Removed duplicate `ajax_get_posts` function to eliminate conflicts
- Enhanced PHP 8.1 compatibility and maintained backward compatibility
- Improved code organization and eliminated redundant functions
- Better error handling and security validation

### Changed
- Maintained PHP 8.1 compatibility for broader server support
- Improved plugin architecture with cleaner code structure
- Enhanced compatibility checks for WordPress and PHP versions

### Security
- Strengthened uninstall security with multiple validation checks
- Improved AJAX request handling and nonce verification
- Enhanced user permission validation throughout the plugin

## [0.2.1] - 2025-01-13

### Added
- Initial release of Spun Web Archive Elite
- Bulk submission functionality for archiving multiple posts
- Auto submission feature for new content
- Advanced submission tracking and status monitoring
- Professional admin interface with enhanced UI
- API integration with Internet Archive (Wayback Machine)

### Features
- WordPress 5.0+ compatibility
- PHP 8.1+ compatibility
- Secure AJAX handling
- Database optimization
- User-friendly admin dashboard
- Comprehensive submission management

## [Unreleased]

### Planned
- Additional archive service integrations
- Enhanced reporting and analytics
- Scheduled submission improvements
- Advanced filtering options