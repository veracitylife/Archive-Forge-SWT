# Spun Web Archive Forge - Comprehensive Documentation

**Version:** 1.0.7  
**Last Updated:** January 2025  
**Plugin URI:** https://github.com/yourusername/spun-web-archive-forge  
**Author:** Your Name  
**License:** GPL v2 or later  

---

## Table of Contents

1. [Overview](#overview)
2. [User Guide](#user-guide)
3. [Developer Guide](#developer-guide)
4. [Features Overview](#features-overview)
5. [Installation & Setup](#installation--setup)
6. [Configuration](#configuration)
7. [Usage Instructions](#usage-instructions)
8. [Troubleshooting](#troubleshooting)
9. [API Reference](#api-reference)
10. [Security](#security)
11. [Performance](#performance)
12. [Changelog](#changelog)

---

# Overview

Spun Web Archive Forge is a comprehensive WordPress plugin that automatically submits your website content to the Internet Archive (Archive.org) for permanent preservation. This plugin ensures your valuable content is safely archived and accessible for future generations.

## Key Benefits

- **Automatic Archiving**: Set it and forget it - your content gets archived automatically
- **Manual Control**: Submit individual posts or pages on demand
- **Queue Management**: Intelligent background processing with retry logic
- **Comprehensive Tracking**: Monitor all submissions with detailed status reports
- **Developer Friendly**: Extensive hooks and filters for customization
- **Security First**: Built with WordPress security best practices

## System Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher (recommended: 8.1+)
- **MySQL**: 5.6 or higher
- **Memory**: 128MB minimum (256MB recommended)
- **Network**: Outbound HTTPS connections required

---

# User Guide

## Getting Started

### Installation

#### Method 1: WordPress Admin Dashboard
1. Navigate to **Plugins > Add New**
2. Click **Upload Plugin**
3. Choose the plugin ZIP file
4. Click **Install Now**
5. Activate the plugin

#### Method 2: FTP Upload
1. Extract the plugin ZIP file
2. Upload the folder to `/wp-content/plugins/`
3. Activate through the WordPress admin

#### Method 3: WP-CLI
```bash
wp plugin install spun-web-archive-forge.zip --activate
```

### Initial Setup

1. **Get Archive.org Credentials**
   - Visit [Archive.org](https://archive.org)
   - Create a free account
   - Go to your account settings
   - Generate API access keys

2. **Configure Plugin**
   - Go to **Archive Forge > Settings**
   - Enter your Archive.org credentials
   - Test the connection
   - Configure auto-submission settings

## Configuration Options

### API Settings

- **Access Key**: Your Archive.org access key
- **Secret Key**: Your Archive.org secret key
- **Request Timeout**: How long to wait for API responses (default: 30 seconds)
- **Retry Attempts**: Number of retry attempts for failed submissions (default: 3)

### Auto Submission Settings

- **Enable Auto Submission**: Automatically submit new posts
- **Post Types**: Select which post types to auto-submit
- **Excluded Categories**: Categories to exclude from auto-submission
- **Delay Submission**: Wait time before submitting new posts

### Queue Management

- **Batch Size**: Number of items to process at once
- **Processing Interval**: How often to process the queue
- **Max Concurrent**: Maximum concurrent API requests
- **Cleanup Settings**: Automatic cleanup of old records

## Using the Plugin

### Individual Post Submission

1. **From Post Editor**:
   - Edit any post or page
   - Look for the "Archive Submission" meta box
   - Click "Submit to Archive"

2. **From Posts List**:
   - Go to Posts or Pages
   - Hover over any post
   - Click "Submit to Archive" in row actions

3. **Bulk Submission**:
   - Select multiple posts
   - Choose "Submit to Archive" from bulk actions
   - Click Apply

### Monitoring Submissions

1. **Archive Status Column**:
   - Posts and Pages lists show archive status
   - Color-coded indicators (green=success, yellow=pending, red=failed)

2. **Submission History**:
   - Go to **Archive Forge > History**
   - View all submission attempts
   - Filter by status, date, or post type

3. **Queue Management**:
   - Go to **Archive Forge > Queue**
   - Monitor pending submissions
   - Manually process or remove items

### Widgets and Shortcodes

#### Archive Widget
Display archive links in your sidebar:
1. Go to **Appearance > Widgets**
2. Add "Archive Links" widget
3. Configure display options

#### Shortcodes
- `[archive_link]` - Display archive link for current post
- `[archive_status]` - Show archive status
- `[archive_date]` - Display archive date
- `[recent_archives]` - List recently archived posts

## Troubleshooting

### Common Issues

#### Connection Problems
- **Error**: "Failed to connect to Archive.org"
- **Solution**: Check your API credentials and internet connection

#### Submission Failures
- **Error**: "Submission failed"
- **Solution**: Check the error details in submission history

#### Queue Not Processing
- **Error**: Items stuck in queue
- **Solution**: Check WordPress cron is working properly

### Debug Mode
Enable debug mode for detailed logging:
1. Add to wp-config.php: `define('WP_DEBUG', true);`
2. Check debug.log for detailed error messages

### Performance Issues
- Reduce batch size in queue settings
- Increase processing interval
- Check server resources

---

# Developer Guide

## Architecture Overview

Spun Web Archive Forge follows a modular, object-oriented architecture with clear separation of concerns. The plugin is built using WordPress best practices and modern PHP features.

### Core Classes

#### Main Plugin Class: `Spun_Web_Archive_Forge`
The main plugin class that orchestrates all functionality:

```php
class Spun_Web_Archive_Forge {
    private static $instance = null;
    private $admin_page;
    private $auto_submitter;
    private $archive_queue;
    // ... other components
}
```

**Key Responsibilities:**
- Plugin initialization and setup
- Component instantiation and dependency injection
- Hook registration and management
- Plugin lifecycle management

#### API Handler: `SWAP_Archive_API`
Manages all communication with the Archive.org API:

```php
class SWAP_Archive_API {
    public function submit_url($url, $options = []);
    public function check_availability($url);
    public function get_archived_url($url);
}
```

**Key Features:**
- RESTful API communication
- Error handling and retry logic
- Rate limiting and throttling
- Response parsing and validation

#### Admin Interface: `SWAP_Admin_Page`
Handles the WordPress admin interface:

```php
class SWAP_Admin_Page {
    public function render_settings_page();
    public function handle_form_submission();
    public function enqueue_admin_assets();
}
```

**Key Features:**
- Tabbed interface design
- Form handling and validation
- AJAX functionality
- Settings management

#### Auto Submitter: `SWAP_Auto_Submitter`
Manages automatic submission of content:

```php
class SWAP_Auto_Submitter {
    public function handle_post_publish($post_id);
    public function should_auto_submit($post);
    public function add_to_queue($post_id);
}
```

**Key Features:**
- Post publication hooks
- Content filtering and validation
- Queue integration
- Scheduling and delays

#### Queue Manager: `SWAP_Archive_Queue`
Handles background processing and queue management:

```php
class SWAP_Archive_Queue {
    public function add_item($post_id, $priority = 'normal');
    public function process_queue($batch_size = 10);
    public function retry_failed_items();
}
```

**Key Features:**
- Priority-based queuing
- Background processing
- Retry logic with exponential backoff
- Batch processing optimization

## WordPress Hooks and Filters

### Action Hooks

#### Core WordPress Hooks
```php
// Plugin initialization
add_action('init', [$this, 'init_plugin']);
add_action('admin_init', [$this, 'admin_init']);

// Post management
add_action('publish_post', [$this, 'handle_post_publish']);
add_action('publish_page', [$this, 'handle_page_publish']);

// Admin interface
add_action('admin_menu', [$this, 'add_admin_menu']);
add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);

// AJAX handlers
add_action('wp_ajax_swap_submit_post', [$this, 'ajax_submit_post']);
add_action('wp_ajax_swap_test_connection', [$this, 'ajax_test_connection']);

// Cron jobs
add_action('swap_process_queue', [$this, 'process_queue_cron']);
add_action('swap_cleanup_old_records', [$this, 'cleanup_cron']);
```

#### Custom Plugin Hooks
```php
// Before submission
do_action('swap_before_submission', $post_id, $url);

// After successful submission
do_action('swap_after_successful_submission', $post_id, $archive_url);

// After failed submission
do_action('swap_after_failed_submission', $post_id, $error);

// Queue processing
do_action('swap_before_queue_processing', $batch_size);
do_action('swap_after_queue_processing', $processed_count);
```

### Filter Hooks

#### Content Filtering
```php
// Modify submission URL
$url = apply_filters('swap_submission_url', $url, $post_id);

// Modify submission options
$options = apply_filters('swap_submission_options', $options, $post_id);

// Filter auto-submission eligibility
$should_submit = apply_filters('swap_should_auto_submit', $should_submit, $post);
```

#### Queue Management
```php
// Modify queue item priority
$priority = apply_filters('swap_queue_item_priority', $priority, $post_id);

// Modify batch size
$batch_size = apply_filters('swap_queue_batch_size', $batch_size);

// Modify retry attempts
$retry_attempts = apply_filters('swap_retry_attempts', $retry_attempts, $item);
```

#### Display and Output
```php
// Modify archive link HTML
$link_html = apply_filters('swap_archive_link_html', $link_html, $post_id);

// Modify status display
$status_html = apply_filters('swap_status_display_html', $status_html, $status);
```

## Database Schema

### Submissions Table: `wp_swap_submissions`
```sql
CREATE TABLE wp_swap_submissions (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    post_id bigint(20) NOT NULL,
    url varchar(2048) NOT NULL,
    archive_url varchar(2048) DEFAULT NULL,
    status varchar(20) NOT NULL DEFAULT 'pending',
    submitted_at datetime NOT NULL,
    completed_at datetime DEFAULT NULL,
    error_message text DEFAULT NULL,
    retry_count int(11) DEFAULT 0,
    PRIMARY KEY (id),
    KEY post_id (post_id),
    KEY status (status),
    KEY submitted_at (submitted_at)
);
```

### Queue Table: `wp_swap_queue`
```sql
CREATE TABLE wp_swap_queue (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    post_id bigint(20) NOT NULL,
    url varchar(2048) NOT NULL,
    priority varchar(10) NOT NULL DEFAULT 'normal',
    status varchar(20) NOT NULL DEFAULT 'pending',
    created_at datetime NOT NULL,
    scheduled_at datetime DEFAULT NULL,
    attempts int(11) DEFAULT 0,
    last_error text DEFAULT NULL,
    PRIMARY KEY (id),
    KEY post_id (post_id),
    KEY priority (priority),
    KEY status (status),
    KEY scheduled_at (scheduled_at)
);
```

## API Integration

### Archive.org Save Page Now API

The plugin integrates with the Archive.org Save Page Now API v2:

```php
class SWAP_Archive_API {
    private $api_base = 'https://web.archive.org/save/';
    
    public function submit_url($url, $options = []) {
        $endpoint = $this->api_base . $url;
        
        $args = [
            'method' => 'POST',
            'headers' => [
                'Authorization' => $this->get_auth_header(),
                'Content-Type' => 'application/x-www-form-urlencoded'
            ],
            'body' => $this->prepare_request_body($options),
            'timeout' => $this->get_timeout()
        ];
        
        return wp_remote_request($endpoint, $args);
    }
}
```

### Authentication
```php
private function get_auth_header() {
    $access_key = get_option('swap_access_key');
    $secret_key = get_option('swap_secret_key');
    
    return 'LOW ' . $access_key . ':' . $secret_key;
}
```

### Error Handling
```php
private function handle_api_response($response) {
    if (is_wp_error($response)) {
        return [
            'success' => false,
            'error' => $response->get_error_message()
        ];
    }
    
    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    if ($status_code === 200) {
        return [
            'success' => true,
            'archive_url' => $this->extract_archive_url($body)
        ];
    }
    
    return [
        'success' => false,
        'error' => "API returned status code: {$status_code}"
    ];
}
```

## Development Setup

### Local Development Environment

1. **WordPress Development Setup**:
   ```bash
   # Using Local by Flywheel, XAMPP, or similar
   # Ensure WordPress 5.0+ and PHP 7.4+
   ```

2. **Plugin Development**:
   ```bash
   cd wp-content/plugins/
   git clone [repository-url] spun-web-archive-forge
   cd spun-web-archive-forge
   ```

3. **Development Tools**:
   ```bash
   # Install Composer dependencies
   composer install
   
   # Install Node.js dependencies
   npm install
   
   # Run development build
   npm run dev
   ```

### Code Quality Tools

#### PHPStan Configuration (`phpstan.neon`)
```yaml
parameters:
    level: 8
    paths:
        - includes/
        - spun-web-archive-forge.php
    excludePaths:
        - tests/
    ignoreErrors:
        - '#Call to an undefined method#'
```

#### WordPress Coding Standards
```bash
# Install PHPCS and WordPress standards
composer global require "squizlabs/php_codesniffer=*"
composer global require wp-coding-standards/wpcs

# Run code sniff
phpcs --standard=WordPress includes/
```

## Customization Examples

### Custom Submission Logic
```php
// Add custom submission criteria
add_filter('swap_should_auto_submit', function($should_submit, $post) {
    // Don't submit posts with specific meta
    if (get_post_meta($post->ID, '_no_archive', true)) {
        return false;
    }
    
    // Only submit posts with featured images
    if (!has_post_thumbnail($post->ID)) {
        return false;
    }
    
    return $should_submit;
}, 10, 2);
```

### Custom Queue Priority
```php
// Set high priority for important categories
add_filter('swap_queue_item_priority', function($priority, $post_id) {
    $categories = get_the_category($post_id);
    
    foreach ($categories as $category) {
        if ($category->slug === 'breaking-news') {
            return 'high';
        }
    }
    
    return $priority;
}, 10, 2);
```

### Custom Archive Link Display
```php
// Customize archive link appearance
add_filter('swap_archive_link_html', function($html, $post_id) {
    $archive_url = get_post_meta($post_id, '_swap_archive_url', true);
    
    if ($archive_url) {
        return sprintf(
            '<a href="%s" class="custom-archive-link" target="_blank">📚 View Archive</a>',
            esc_url($archive_url)
        );
    }
    
    return $html;
}, 10, 2);
```

## Testing

### Unit Testing
```php
class Test_Archive_API extends WP_UnitTestCase {
    public function test_submit_url() {
        $api = new SWAP_Archive_API();
        $result = $api->submit_url('https://example.com');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }
}
```

### Integration Testing
```bash
# Run WordPress unit tests
phpunit

# Run integration tests
wp eval-file tests/integration-tests.php
```

### Manual Testing Checklist
- [ ] Plugin activation/deactivation
- [ ] Settings page functionality
- [ ] Individual post submission
- [ ] Auto-submission on publish
- [ ] Queue processing
- [ ] Widget display
- [ ] Shortcode rendering
- [ ] Error handling
- [ ] Performance under load

## Security Considerations

### Input Validation
```php
// Sanitize all user inputs
$access_key = sanitize_text_field($_POST['access_key']);
$secret_key = sanitize_text_field($_POST['secret_key']);

// Validate URLs
if (!filter_var($url, FILTER_VALIDATE_URL)) {
    return new WP_Error('invalid_url', 'Invalid URL provided');
}
```

### Capability Checks
```php
// Check user permissions
if (!current_user_can('manage_options')) {
    wp_die(__('Insufficient permissions', 'spun-web-archive-forge'));
}
```

### Nonce Verification
```php
// Verify nonces for all forms
if (!wp_verify_nonce($_POST['_wpnonce'], 'swap_settings')) {
    wp_die(__('Security check failed', 'spun-web-archive-forge'));
}
```

### Data Sanitization
```php
// Escape all output
echo esc_html($status);
echo esc_url($archive_url);
echo esc_attr($css_class);
```

---

# Features Overview

## Core Features

### 🚀 Automatic Content Archiving
- **Auto-Submit New Posts**: Automatically submit new posts and pages to the Internet Archive when published
- **Custom Post Type Support**: Works with all public post types including custom post types
- **Selective Archiving**: Choose which post types and categories to auto-archive
- **Delayed Submission**: Option to delay submission for last-minute edits

### 📝 Individual Post Control
- **On-Demand Submission**: Submit individual posts/pages with a single click
- **Row Actions Integration**: "Submit to Archive" links in Posts and Pages admin lists
- **Meta Box Controls**: Archive submission controls directly in the post editor
- **Bulk Operations**: Submit multiple posts at once using bulk actions

### 🔄 Intelligent Queue System
- **Background Processing**: All submissions processed in the background without affecting site performance
- **Priority Queuing**: High, normal, and low priority submission queues
- **Retry Logic**: Automatic retry for failed submissions with exponential backoff
- **Rate Limiting**: Respects Archive.org API limits to prevent blocking

### 📊 Comprehensive Tracking
- **Real-Time Status**: Live tracking of submission status (pending, processing, completed, failed)
- **Detailed History**: Complete submission history with timestamps and error logs
- **Archive URLs**: Direct links to archived versions on Archive.org
- **Statistics Dashboard**: Overview of total, successful, failed, and pending submissions

## Admin Interface

### 🎛️ Tabbed Settings Interface
- **API Settings**: Configure Internet Archive credentials and connection settings
- **Auto Submission**: Control automatic submission behavior and post type selection
- **Queue Management**: Monitor and control the submission queue
- **Submission History**: View detailed logs and statistics

### 📋 API Credentials Management
- **Secure Storage**: Encrypted storage of Archive.org API credentials
- **Connection Testing**: Built-in API connection testing with detailed error reporting
- **Credential Validation**: Real-time validation of access keys and secret keys
- **Error Diagnostics**: Comprehensive error messages for troubleshooting

### 🔧 Advanced Configuration
- **Timeout Settings**: Configurable request timeouts for different server environments
- **Retry Attempts**: Customizable number of retry attempts for failed submissions
- **Batch Size**: Adjustable queue processing batch sizes
- **Cleanup Options**: Automatic cleanup of old submission records

### 📱 Responsive Design
- **Mobile-Friendly**: Fully responsive admin interface works on all devices
- **Modern UI**: Clean, intuitive interface following WordPress design standards
- **Accessibility**: WCAG compliant with proper ARIA labels and keyboard navigation
- **Dark Mode Support**: Adapts to WordPress admin color schemes

## Security Features

### 🔒 Data Protection
- **Input Sanitization**: All user input properly sanitized
- **Output Escaping**: All output properly escaped to prevent XSS
- **SQL Injection Prevention**: Prepared statements for all database queries
- **Nonce Verification**: CSRF protection for all forms and AJAX requests

### 👤 Access Control
- **Capability Checks**: Proper WordPress capability checks throughout
- **Role-Based Access**: Different features available based on user roles
- **Permission Validation**: Granular permission checking for all operations
- **Audit Logging**: Security event logging for compliance

## Performance Features

### ⚡ Optimization
- **Background Processing**: All heavy operations run in background
- **Caching**: Intelligent caching of API responses and data
- **Database Optimization**: Efficient database queries and indexing
- **Asset Minification**: Minified CSS and JavaScript for faster loading

### 📊 Resource Management
- **Memory Efficiency**: Optimized memory usage for large sites
- **CPU Optimization**: Efficient algorithms to minimize CPU usage
- **Bandwidth Conservation**: Minimal bandwidth usage for API calls
- **Server Load**: Designed to minimize server load impact

## Compatibility

### 🌐 WordPress Compatibility
- **Version Support**: WordPress 5.0 to 6.7.1+
- **Multisite**: Full WordPress multisite network support
- **Classic Editor**: Compatible with both Classic and Block editors
- **Custom Post Types**: Works with all custom post types and fields

### 🔧 PHP Compatibility
- **PHP Versions**: PHP 7.4 to PHP 8.2+
- **Modern Features**: Uses modern PHP features while maintaining compatibility
- **Error Handling**: Graceful degradation for older PHP versions
- **Performance**: Optimized for latest PHP versions

---

# Installation & Setup

## Prerequisites

Before installing Spun Web Archive Forge, ensure your system meets these requirements:

- WordPress 5.0 or higher
- PHP 7.4 or higher (PHP 8.1+ recommended)
- MySQL 5.6 or higher
- 128MB PHP memory limit (256MB recommended)
- Outbound HTTPS connections enabled

## Installation Methods

### Method 1: WordPress Admin Dashboard (Recommended)

1. **Download the Plugin**
   - Download the latest version from the official source
   - Ensure you have the `.zip` file

2. **Upload via Admin**
   - Log into your WordPress admin dashboard
   - Navigate to **Plugins > Add New**
   - Click **Upload Plugin**
   - Choose the plugin ZIP file
   - Click **Install Now**

3. **Activate**
   - Click **Activate Plugin** after installation
   - You'll see a success message

### Method 2: FTP Upload

1. **Extract Files**
   - Extract the plugin ZIP file on your computer
   - You should see a folder named `spun-web-archive-forge`

2. **Upload via FTP**
   - Connect to your website via FTP
   - Navigate to `/wp-content/plugins/`
   - Upload the entire plugin folder

3. **Activate**
   - Go to your WordPress admin
   - Navigate to **Plugins > Installed Plugins**
   - Find "Spun Web Archive Forge" and click **Activate**

### Method 3: WP-CLI

If you have WP-CLI installed:

```bash
# Install and activate
wp plugin install spun-web-archive-forge.zip --activate

# Or install from directory
wp plugin install /path/to/spun-web-archive-forge --activate
```

## Initial Configuration

### Step 1: Get Archive.org Credentials

1. **Create Archive.org Account**
   - Visit [archive.org](https://archive.org)
   - Click "Sign up" to create a free account
   - Verify your email address

2. **Generate API Keys**
   - Log into your Archive.org account
   - Go to your account settings
   - Look for "API" or "Developer" section
   - Generate your access key and secret key
   - **Important**: Keep these credentials secure

### Step 2: Configure Plugin Settings

1. **Access Settings**
   - In WordPress admin, go to **Archive Forge > Settings**
   - You'll see a tabbed interface

2. **API Settings Tab**
   - Enter your **Access Key** from Archive.org
   - Enter your **Secret Key** from Archive.org
   - Set **Request Timeout** (default: 30 seconds)
   - Set **Retry Attempts** (default: 3)
   - Click **Test Connection** to verify credentials

3. **Auto Submission Tab**
   - Enable **Auto Submit New Posts** if desired
   - Select which **Post Types** to auto-submit
   - Choose **Excluded Categories** (if any)
   - Set **Submission Delay** (optional)

4. **Save Settings**
   - Click **Save Changes** on each tab
   - Verify you see success messages

### Step 3: Test the Setup

1. **Test Individual Submission**
   - Go to **Posts** or **Pages**
   - Find any published post
   - Click **Submit to Archive** in row actions
   - Monitor the submission in **Archive Forge > History**

2. **Verify Queue Processing**
   - Go to **Archive Forge > Queue**
   - Check that items are being processed
   - Look for any error messages

## Post-Installation Checklist

- [ ] Plugin activated successfully
- [ ] Archive.org credentials configured and tested
- [ ] Auto-submission settings configured
- [ ] Test submission completed successfully
- [ ] Queue processing working
- [ ] No error messages in logs

## Troubleshooting Installation

### Common Installation Issues

#### Plugin Won't Activate
- **Cause**: PHP version too old or missing requirements
- **Solution**: Check PHP version and server requirements

#### Settings Won't Save
- **Cause**: Insufficient permissions or security plugin interference
- **Solution**: Check user capabilities and disable security plugins temporarily

#### API Connection Fails
- **Cause**: Incorrect credentials or firewall blocking
- **Solution**: Verify credentials and check server firewall settings

### Getting Help

If you encounter issues during installation:

1. Check the **Archive Forge > Documentation** page
2. Enable WordPress debug mode for detailed error messages
3. Check your server error logs
4. Contact support with specific error messages

---

# Configuration

## API Settings

### Archive.org Credentials

The most critical configuration is setting up your Archive.org API credentials:

#### Access Key
- **Purpose**: Identifies your Archive.org account
- **Format**: Usually a long alphanumeric string
- **Security**: Never share this key publicly

#### Secret Key
- **Purpose**: Authenticates your API requests
- **Format**: Another long alphanumeric string
- **Security**: Keep this absolutely private

#### Getting Your Credentials
1. Log into [archive.org](https://archive.org)
2. Go to your account settings
3. Find the API or Developer section
4. Generate new credentials if needed
5. Copy both keys to the plugin settings

### Connection Settings

#### Request Timeout
- **Default**: 30 seconds
- **Range**: 10-120 seconds
- **Purpose**: How long to wait for Archive.org to respond
- **Recommendation**: 
  - Slow connections: 60+ seconds
  - Fast connections: 30 seconds
  - Very fast connections: 15 seconds

#### Retry Attempts
- **Default**: 3 attempts
- **Range**: 1-10 attempts
- **Purpose**: How many times to retry failed submissions
- **Recommendation**:
  - Stable connections: 3 attempts
  - Unstable connections: 5+ attempts
  - Testing: 1 attempt

### Testing Your Configuration

Always test your API configuration:

1. Enter your credentials
2. Click **Test Connection**
3. Wait for the response
4. Look for success or error messages

**Success Response**: "Connection successful! Your credentials are valid."
**Error Response**: Detailed error message explaining the issue

## Auto Submission Settings

### Enable Auto Submission

When enabled, the plugin automatically submits content to Archive.org when published.

#### Benefits of Auto Submission
- Ensures all content is archived
- No manual intervention required
- Immediate archiving upon publication
- Consistent archiving workflow

#### When to Disable
- High-traffic sites (to avoid API limits)
- Sites with frequent updates
- When you prefer manual control
- During testing phases

### Post Type Selection

Choose which content types to automatically archive:

#### Standard Post Types
- **Posts**: Blog posts and articles
- **Pages**: Static pages and content
- **Attachments**: Media files (if supported)

#### Custom Post Types
- **Products**: E-commerce products
- **Events**: Event listings
- **Portfolio**: Portfolio items
- **Any custom type**: The plugin detects all public post types

#### Configuration Tips
- Start with just "Posts" for testing
- Add more types gradually
- Monitor API usage with multiple types
- Consider your Archive.org account limits

### Category Exclusions

Exclude specific categories from auto-submission:

#### Common Exclusions
- **Private**: Personal or internal content
- **Draft**: Work-in-progress content
- **Test**: Testing and development content
- **Temporary**: Short-term announcements

#### How to Configure
1. Select categories from the dropdown
2. Hold Ctrl/Cmd to select multiple
3. Save settings
4. Test with posts in excluded categories

### Submission Delay

Add a delay before auto-submission:

#### Why Use Delays
- Allow time for last-minute edits
- Prevent archiving of quickly deleted posts
- Reduce immediate server load
- Give time for content review

#### Recommended Delays
- **No delay**: For final, reviewed content
- **5 minutes**: For quick edit window
- **1 hour**: For thorough review process
- **24 hours**: For editorial approval workflow

## Queue Management Settings

### Processing Configuration

#### Batch Size
- **Default**: 10 items
- **Range**: 1-50 items
- **Purpose**: How many items to process at once
- **Impact**: 
  - Larger batches: Faster processing, more server load
  - Smaller batches: Slower processing, less server load

#### Processing Interval
- **Default**: Every hour
- **Options**: Every 15 minutes, hourly, twice daily, daily
- **Purpose**: How often to process the queue
- **Considerations**:
  - More frequent: Faster archiving, more API calls
  - Less frequent: Slower archiving, fewer API calls

#### Concurrent Requests
- **Default**: 3 concurrent requests
- **Range**: 1-10 requests
- **Purpose**: How many API calls to make simultaneously
- **Server Impact**: Higher numbers increase server load

### Queue Priorities

#### Priority Levels
- **High**: Processed first, important content
- **Normal**: Standard processing order
- **Low**: Processed last, less important content

#### Automatic Priority Assignment
Configure rules for automatic priority assignment:
- Breaking news categories → High priority
- Regular posts → Normal priority
- Archive pages → Low priority

### Cleanup Settings

#### Automatic Cleanup
- **Completed Items**: Remove after 30 days
- **Failed Items**: Remove after 7 days
- **Old Queue Items**: Remove after 24 hours

#### Manual Cleanup
- Clear all completed items
- Remove failed items
- Reset queue status

## Display Settings

### Admin Interface

#### Column Display
- Show archive status in post lists
- Display archive links
- Show submission dates
- Color-code status indicators

#### Meta Box Settings
- Show in post editor
- Display current status
- Show submission history
- Enable manual submission

### Frontend Display

#### Widget Configuration
- Archive links widget
- Recent archives widget
- Custom styling options
- Display preferences

#### Shortcode Settings
- Default link text
- CSS classes
- Target window behavior
- Conditional display

## Advanced Configuration

### Performance Optimization

#### Caching Settings
- Enable response caching
- Cache duration settings
- Clear cache options

#### Database Optimization
- Index optimization
- Query optimization
- Cleanup schedules

### Security Settings

#### Access Control
- User role permissions
- Capability requirements
- Admin access restrictions

#### Data Protection
- Credential encryption
- Secure transmission
- Privacy settings

### Developer Options

#### Debug Mode
- Enable detailed logging
- Error reporting levels
- Debug output options

#### Hook Configuration
- Custom hook priorities
- Filter modifications
- Action customizations

## Configuration Best Practices

### Initial Setup
1. Start with minimal settings
2. Test thoroughly before expanding
3. Monitor API usage closely
4. Adjust based on performance

### Ongoing Management
1. Regular credential rotation
2. Monitor queue performance
3. Adjust batch sizes as needed
4. Review exclusion rules periodically

### Troubleshooting Configuration
1. Test each setting individually
2. Check error logs regularly
3. Monitor API response times
4. Verify credential validity

---

# Usage Instructions

## Individual Post Submission

### From the Post Editor

1. **Edit Your Post**
   - Go to **Posts > All Posts** or **Pages > All Pages**
   - Click **Edit** on any published post or page
   - Or create a new post and publish it

2. **Find the Archive Meta Box**
   - Look for "Archive Submission" meta box (usually in the sidebar)
   - If not visible, check **Screen Options** at the top and enable it

3. **Submit to Archive**
   - Click the **"Submit to Archive"** button
   - You'll see a loading indicator
   - Wait for the success or error message

4. **Monitor Status**
   - The meta box will update with submission status
   - Check **Archive Forge > History** for detailed logs

### From Posts/Pages List

1. **Navigate to Content Lists**
   - Go to **Posts > All Posts** or **Pages > All Pages**
   - You'll see all your content in a table format

2. **Use Row Actions**
   - Hover over any post title
   - Click **"Submit to Archive"** in the row actions
   - The page will reload with a status message

3. **Check Archive Status Column**
   - Look for the "Archive Status" column
   - Color-coded indicators show current status:
     - 🟢 Green: Successfully archived
     - 🟡 Yellow: Pending submission
     - 🔴 Red: Submission failed
     - ⚪ Gray: Not submitted

### Bulk Submission

1. **Select Multiple Posts**
   - Go to **Posts > All Posts** or **Pages > All Pages**
   - Check the boxes next to posts you want to archive
   - Or use "Select All" to choose all posts

2. **Choose Bulk Action**
   - From the "Bulk Actions" dropdown, select **"Submit to Archive"**
   - Click **"Apply"**

3. **Monitor Progress**
   - You'll see a progress indicator
   - Items are added to the queue for processing
   - Check **Archive Forge > Queue** to monitor progress

## Automatic Submission

### Enabling Auto-Submission

1. **Configure Settings**
   - Go to **Archive Forge > Settings**
   - Click the **"Auto Submission"** tab
   - Check **"Enable Auto Submission"**
   - Save changes

2. **Select Post Types**
   - Choose which content types to auto-submit
   - Start with just "Posts" for testing
   - Add more types as needed

3. **Set Exclusions**
   - Select categories to exclude from auto-submission
   - Common exclusions: Private, Draft, Test categories

### How Auto-Submission Works

1. **Trigger Events**
   - Activates when you publish new content
   - Also works when updating post status to "Published"
   - Respects your delay settings

2. **Processing Flow**
   - Content is checked against your rules
   - Eligible posts are added to the submission queue
   - Queue processes items in the background

3. **Status Updates**
   - You'll see status updates in the admin
   - Check submission history for details
   - Error notifications appear if issues occur

## Queue Management

### Monitoring the Queue

1. **Access Queue Dashboard**
   - Go to **Archive Forge > Queue**
   - View all pending and processing items

2. **Queue Information**
   - **Pending**: Items waiting to be processed
   - **Processing**: Items currently being submitted
   - **Completed**: Successfully processed items
   - **Failed**: Items that encountered errors

3. **Queue Actions**
   - **Process Now**: Force immediate processing
   - **Clear Completed**: Remove finished items
   - **Retry Failed**: Attempt failed items again

### Queue Priorities

#### Setting Priorities
- **High**: Important content, processed first
- **Normal**: Standard content, regular processing
- **Low**: Less important content, processed last

#### Automatic Priority Rules
Configure automatic priority assignment:
- Breaking news → High priority
- Regular posts → Normal priority
- Archive content → Low priority

### Manual Queue Management

1. **Individual Item Actions**
   - Change item priority
   - Remove from queue
   - Force immediate processing
   - View error details

2. **Bulk Queue Actions**
   - Process multiple items
   - Change priorities in bulk
   - Clear completed items
   - Retry failed submissions

## Monitoring and History

### Submission History

1. **Access History**
   - Go to **Archive Forge > History**
   - View all submission attempts

2. **History Information**
   - Submission date and time
   - Post title and URL
   - Archive URL (if successful)
   - Status and error messages
   - Retry attempts

3. **Filtering and Search**
   - Filter by status (success, failed, pending)
   - Search by post title or URL
   - Filter by date range
   - Export data to CSV

### Status Indicators

#### In Post Lists
- **Green checkmark**: Successfully archived
- **Yellow clock**: Submission pending
- **Red X**: Submission failed
- **Gray dash**: Not submitted

#### In Submission History
- **Success**: Green background, archive URL provided
- **Failed**: Red background, error message shown
- **Pending**: Yellow background, processing status
- **Retry**: Orange background, retry count shown

### Error Handling

#### Common Errors
- **API Connection Failed**: Check credentials and internet connection
- **Rate Limit Exceeded**: Wait and retry, or reduce submission frequency
- **Invalid URL**: Check post permalink settings
- **Timeout**: Increase timeout settings or check server performance

#### Error Resolution
1. **Check Error Details**
   - View specific error messages in history
   - Look for patterns in failed submissions

2. **Retry Failed Items**
   - Use bulk retry option in queue
   - Or retry individual items

3. **Adjust Settings**
   - Increase timeout for slow connections
   - Reduce batch size for server limitations
   - Check API credentials if authentication fails

## Widgets and Shortcodes

### Archive Links Widget

1. **Add Widget**
   - Go to **Appearance > Widgets**
   - Find "Archive Links" widget
   - Drag to desired widget area

2. **Configure Widget**
   - Set widget title
   - Choose display options
   - Configure link behavior
   - Save widget settings

3. **Widget Display**
   - Shows archive links for current post
   - Only displays on archived content
   - Customizable appearance

### Using Shortcodes

#### Basic Shortcodes
```
[archive_link] - Display archive link for current post
[archive_status] - Show current archive status
[archive_date] - Display when post was archived
[recent_archives] - List recently archived posts
```

#### Shortcode Attributes
```
[archive_link text="View Archive" target="_blank" class="my-archive-link"]
[archive_status show_icon="true" show_text="false"]
[recent_archives count="5" category="news"]
```

#### Adding Shortcodes
1. **In Post Content**
   - Edit any post or page
   - Add shortcode where you want it to appear
   - Publish or update the post

2. **In Widgets**
   - Use Text widget
   - Add shortcode to widget content
   - Save widget

3. **In Theme Files**
   ```php
   echo do_shortcode('[archive_link]');
   ```

## Best Practices

### Submission Strategy
1. **Start Small**: Begin with manual submissions to test
2. **Monitor Usage**: Watch your Archive.org API usage
3. **Gradual Automation**: Slowly enable auto-submission
4. **Regular Monitoring**: Check submission history regularly

### Performance Optimization
1. **Batch Size**: Start with small batches (5-10 items)
2. **Processing Frequency**: Begin with hourly processing
3. **Concurrent Requests**: Start with 1-2 concurrent requests
4. **Monitor Server Load**: Watch for performance impacts

### Content Strategy
1. **Quality Over Quantity**: Archive your best content first
2. **Categorize Wisely**: Use exclusions for temporary content
3. **Update Strategy**: Decide whether to re-archive updated posts
4. **Cleanup Regularly**: Remove old, irrelevant submissions

---

# Troubleshooting

## Common Issues and Solutions

### Connection Problems

#### Issue: "Failed to connect to Archive.org"
**Symptoms:**
- Error message when testing API connection
- All submissions failing immediately
- Timeout errors in submission history

**Possible Causes:**
- Incorrect API credentials
- Firewall blocking outbound connections
- Server network configuration issues
- Archive.org API temporarily unavailable

**Solutions:**
1. **Verify Credentials**
   ```
   - Double-check your Access Key and Secret Key
   - Ensure no extra spaces or characters
   - Try generating new credentials on Archive.org
   - Test credentials on Archive.org website directly
   ```

2. **Check Network Connectivity**
   ```
   - Verify your server can make HTTPS requests
   - Test with: curl -I https://web.archive.org
   - Check firewall settings for outbound connections
   - Contact hosting provider if needed
   ```

3. **Server Configuration**
   ```
   - Ensure PHP curl extension is installed
   - Check PHP allow_url_fopen setting
   - Verify SSL certificate validation is working
   - Test with different timeout values
   ```

#### Issue: "SSL Certificate Verification Failed"
**Solutions:**
- Update server SSL certificates
- Check PHP OpenSSL extension
- Contact hosting provider for SSL issues

### Submission Failures

#### Issue: "Rate limit exceeded"
**Symptoms:**
- Submissions failing with rate limit errors
- Temporary blocks on API access
- Slow processing of queue items

**Solutions:**
1. **Reduce Submission Frequency**
   ```
   - Increase processing interval (hourly → daily)
   - Reduce batch size (10 → 5 items)
   - Decrease concurrent requests (3 → 1)
   ```

2. **Implement Delays**
   ```
   - Add submission delays for auto-submission
   - Spread out bulk submissions over time
   - Use priority queuing for important content
   ```

3. **Monitor API Usage**
   ```
   - Check Archive.org account usage limits
   - Consider upgrading account if needed
   - Track daily/monthly submission counts
   ```

#### Issue: "Invalid URL format"
**Symptoms:**
- Specific posts failing to submit
- URL-related error messages
- Inconsistent submission success

**Solutions:**
1. **Check Permalink Settings**
   ```
   - Go to Settings > Permalinks
   - Ensure permalinks are set to a SEO-friendly format
   - Avoid default "?p=123" format
   - Test with different permalink structures
   ```

2. **Validate URLs**
   ```
   - Check for special characters in URLs
   - Ensure URLs are publicly accessible
   - Test URLs in browser before submission
   - Check for redirect loops
   ```

### Queue Processing Issues

#### Issue: "Queue not processing"
**Symptoms:**
- Items stuck in "pending" status
- No movement in queue for extended periods
- Manual processing works but automatic doesn't

**Solutions:**
1. **Check WordPress Cron**
   ```
   - Install WP Crontrol plugin to debug
   - Verify wp-cron.php is accessible
   - Check for cron job conflicts
   - Consider server-level cron if needed
   ```

2. **Server Resources**
   ```
   - Check PHP memory limits
   - Monitor server CPU usage
   - Verify script execution time limits
   - Check for conflicting plugins
   ```

3. **Plugin Configuration**
   ```
   - Reduce batch size temporarily
   - Increase processing interval
   - Check error logs for specific issues
   - Test manual queue processing
   ```

#### Issue: "Memory limit exceeded"
**Solutions:**
- Increase PHP memory limit in wp-config.php
- Reduce queue batch size
- Optimize database queries
- Contact hosting provider for limits

### Display and Interface Issues

#### Issue: "Archive status not showing"
**Symptoms:**
- Missing archive status column in post lists
- Meta box not appearing in post editor
- Widget not displaying properly

**Solutions:**
1. **Check Screen Options**
   ```
   - Click "Screen Options" at top of admin pages
   - Ensure "Archive Status" column is checked
   - Enable meta box in post editor screen options
   ```

2. **User Permissions**
   ```
   - Verify user has proper capabilities
   - Check role-based access settings
   - Test with administrator account
   ```

3. **Theme Compatibility**
   ```
   - Test with default WordPress theme
   - Check for theme conflicts
   - Verify widget areas are properly registered
   ```

#### Issue: "Shortcodes not working"
**Solutions:**
- Verify shortcode syntax is correct
- Check if shortcodes are enabled in widgets
- Test in different post types
- Clear any caching plugins

### Performance Issues

#### Issue: "Site running slowly after plugin activation"
**Symptoms:**
- Increased page load times
- Admin dashboard sluggishness
- High server resource usage

**Solutions:**
1. **Optimize Settings**
   ```
   - Reduce queue batch size
   - Increase processing intervals
   - Limit concurrent API requests
   - Disable auto-submission temporarily
   ```

2. **Database Optimization**
   ```
   - Enable automatic cleanup of old records
   - Manually clean submission history
   - Optimize database tables
   - Check for slow queries
   ```

3. **Caching Configuration**
   ```
   - Enable object caching if available
   - Configure page caching properly
   - Exclude admin pages from caching
   - Clear all caches after changes
   ```

## Debugging Tools

### Enable Debug Mode

1. **WordPress Debug Mode**
   ```php
   // Add to wp-config.php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   define('WP_DEBUG_DISPLAY', false);
   ```

2. **Plugin Debug Mode**
   ```php
   // Add to wp-config.php
   define('SWAP_DEBUG', true);
   ```

3. **Check Debug Logs**
   ```
   - Look in /wp-content/debug.log
   - Check server error logs
   - Monitor real-time with tail -f debug.log
   ```

### Testing Tools

#### Connection Testing
```php
// Test API connection manually
$api = new SWAP_Archive_API();
$result = $api->test_connection();
var_dump($result);
```

#### Queue Testing
```php
// Process queue manually
$queue = new SWAP_Archive_Queue();
$result = $queue->process_queue(1);
var_dump($result);
```

#### Database Testing
```php
// Check database tables
global $wpdb;
$submissions = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}swap_submissions LIMIT 10");
var_dump($submissions);
```

### Log Analysis

#### Common Log Patterns
```
[ERROR] SWAP: API connection failed - Invalid credentials
[WARNING] SWAP: Rate limit exceeded, retrying in 60 seconds
[INFO] SWAP: Successfully submitted post ID 123
[DEBUG] SWAP: Queue processing started with batch size 10
```

#### Log Locations
- WordPress: `/wp-content/debug.log`
- Server: `/var/log/apache2/error.log` or `/var/log/nginx/error.log`
- Plugin: Custom log files in `/wp-content/uploads/swap-logs/`

## Getting Help

### Before Contacting Support

1. **Gather Information**
   - WordPress version
   - PHP version
   - Plugin version
   - Error messages (exact text)
   - Steps to reproduce the issue

2. **Try Basic Troubleshooting**
   - Deactivate other plugins temporarily
   - Switch to default theme
   - Check with different user account
   - Test on staging site if available

3. **Check Documentation**
   - Review this troubleshooting guide
   - Check the FAQ section
   - Look for similar issues in forums

### Support Channels

1. **Plugin Documentation**
   - Built-in help in **Archive Forge > Documentation**
   - Online documentation and guides
   - Video tutorials and walkthroughs

2. **Community Support**
   - WordPress.org plugin forums
   - Community discussions and solutions
   - User-contributed fixes and tips

3. **Direct Support**
   - Contact plugin developers
   - Submit detailed bug reports
   - Request feature enhancements

### Providing Useful Information

When seeking help, include:
- Exact error messages
- Steps to reproduce the issue
- System information (WordPress, PHP, server)
- Screenshots of error screens
- Relevant log entries
- What you've already tried

---

# API Reference

## Overview

Spun Web Archive Forge provides a comprehensive API for developers to integrate with and extend the plugin's functionality. The API includes both WordPress hooks (actions and filters) and direct PHP class methods.

## WordPress Hooks

### Action Hooks

#### Core Plugin Actions

##### `swap_plugin_loaded`
Fired when the plugin is fully loaded and initialized.

```php
add_action('swap_plugin_loaded', function() {
    // Plugin is ready for use
    error_log('Spun Web Archive Forge is loaded');
});
```

##### `swap_before_submission`
Fired before a URL is submitted to Archive.org.

```php
add_action('swap_before_submission', function($post_id, $url, $options) {
    // Log submission attempt
    error_log("Submitting post {$post_id}: {$url}");
}, 10, 3);
```

**Parameters:**
- `$post_id` (int): WordPress post ID
- `$url` (string): URL being submitted
- `$options` (array): Submission options

##### `swap_after_successful_submission`
Fired after a successful submission to Archive.org.

```php
add_action('swap_after_successful_submission', function($post_id, $archive_url, $submission_data) {
    // Send notification email
    wp_mail('admin@site.com', 'Post Archived', "Post {$post_id} archived at {$archive_url}");
}, 10, 3);
```

**Parameters:**
- `$post_id` (int): WordPress post ID
- `$archive_url` (string): Archive.org URL
- `$submission_data` (array): Complete submission data

##### `swap_after_failed_submission`
Fired after a failed submission attempt.

```php
add_action('swap_after_failed_submission', function($post_id, $error_message, $attempt_count) {
    // Log error for monitoring
    error_log("Submission failed for post {$post_id}: {$error_message} (Attempt {$attempt_count})");
}, 10, 3);
```

**Parameters:**
- `$post_id` (int): WordPress post ID
- `$error_message` (string): Error description
- `$attempt_count` (int): Number of attempts made

#### Queue Management Actions

##### `swap_before_queue_processing`
Fired before queue processing begins.

```php
add_action('swap_before_queue_processing', function($batch_size) {
    // Prepare for queue processing
    wp_cache_flush(); // Clear cache before processing
}, 10, 1);
```

##### `swap_after_queue_processing`
Fired after queue processing completes.

```php
add_action('swap_after_queue_processing', function($processed_count, $success_count, $failed_count) {
    // Log processing results
    error_log("Processed {$processed_count} items: {$success_count} success, {$failed_count} failed");
}, 10, 3);
```

##### `swap_queue_item_added`
Fired when an item is added to the queue.

```php
add_action('swap_queue_item_added', function($queue_id, $post_id, $priority) {
    // Track queue additions
    update_option('swap_total_queued', get_option('swap_total_queued', 0) + 1);
}, 10, 3);
```

#### Admin Interface Actions

##### `swap_admin_page_loaded`
Fired when the admin page is loaded.

```php
add_action('swap_admin_page_loaded', function($page_slug) {
    // Add custom admin notices
    if ($page_slug === 'swap-settings') {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-info"><p>Custom admin notice</p></div>';
        });
    }
}, 10, 1);
```

##### `swap_settings_saved`
Fired when plugin settings are saved.

```php
add_action('swap_settings_saved', function($settings) {
    // Validate custom settings
    if (isset($settings['custom_option'])) {
        // Perform additional validation
    }
}, 10, 1);
```

### Filter Hooks

#### Content Filtering

##### `swap_submission_url`
Filter the URL before submission to Archive.org.

```php
add_filter('swap_submission_url', function($url, $post_id) {
    // Add tracking parameters
    return add_query_arg('utm_source', 'archive', $url);
}, 10, 2);
```

**Parameters:**
- `$url` (string): Original URL
- `$post_id` (int): WordPress post ID

**Return:** Modified URL string

##### `swap_submission_options`
Filter submission options sent to Archive.org.

```php
add_filter('swap_submission_options', function($options, $post_id) {
    // Add custom options for specific posts
    if (has_tag('important', $post_id)) {
        $options['capture_all'] = 1;
        $options['capture_outlinks'] = 1;
    }
    return $options;
}, 10, 2);
```

**Parameters:**
- `$options` (array): Submission options
- `$post_id` (int): WordPress post ID

**Return:** Modified options array

##### `swap_should_auto_submit`
Filter whether a post should be auto-submitted.

```php
add_filter('swap_should_auto_submit', function($should_submit, $post) {
    // Don't auto-submit password protected posts
    if (!empty($post->post_password)) {
        return false;
    }
    
    // Don't submit posts with specific meta
    if (get_post_meta($post->ID, '_no_archive', true)) {
        return false;
    }
    
    return $should_submit;
}, 10, 2);
```

**Parameters:**
- `$should_submit` (bool): Whether to auto-submit
- `$post` (WP_Post): WordPress post object

**Return:** Boolean decision

#### Queue Management Filters

##### `swap_queue_item_priority`
Filter the priority assigned to queue items.

```php
add_filter('swap_queue_item_priority', function($priority, $post_id) {
    // High priority for breaking news
    $categories = get_the_category($post_id);
    foreach ($categories as $category) {
        if ($category->slug === 'breaking-news') {
            return 'high';
        }
    }
    
    // Low priority for old posts
    $post_date = get_the_date('U', $post_id);
    if ($post_date < strtotime('-1 year')) {
        return 'low';
    }
    
    return $priority;
}, 10, 2);
```

##### `swap_queue_batch_size`
Filter the number of items processed in each batch.

```php
add_filter('swap_queue_batch_size', function($batch_size) {
    // Reduce batch size during peak hours
    $current_hour = date('H');
    if ($current_hour >= 9 && $current_hour <= 17) {
        return min($batch_size, 5);
    }
    
    return $batch_size;
}, 10, 1);
```

##### `swap_retry_attempts`
Filter the number of retry attempts for failed submissions.

```php
add_filter('swap_retry_attempts', function($attempts, $queue_item) {
    // More retries for high priority items
    if ($queue_item->priority === 'high') {
        return $attempts + 2;
    }
    
    // Fewer retries for low priority items
    if ($queue_item->priority === 'low') {
        return max(1, $attempts - 1);
    }
    
    return $attempts;
}, 10, 2);
```

#### Display Filters

##### `swap_archive_link_html`
Filter the HTML output for archive links.

```php
add_filter('swap_archive_link_html', function($html, $post_id, $archive_url) {
    // Custom archive link with icon
    return sprintf(
        '<a href="%s" class="archive-link" target="_blank" rel="noopener">
            <span class="archive-icon">📚</span> View Archive
        </a>',
        esc_url($archive_url)
    );
}, 10, 3);
```

##### `swap_status_display_html`
Filter the HTML output for status displays.

```php
add_filter('swap_status_display_html', function($html, $status, $post_id) {
    $icons = [
        'success' => '✅',
        'pending' => '⏳',
        'failed' => '❌',
        'not_submitted' => '⚪'
    ];
    
    $icon = isset($icons[$status]) ? $icons[$status] : '❓';
    
    return sprintf(
        '<span class="swap-status swap-status-%s">%s %s</span>',
        esc_attr($status),
        $icon,
        esc_html(ucfirst(str_replace('_', ' ', $status)))
    );
}, 10, 3);
```

## PHP Class Methods

### Main Plugin Class: `Spun_Web_Archive_Elite`

#### `get_instance()`
Get the singleton instance of the main plugin class.

```php
$plugin = Spun_Web_Archive_Elite::get_instance();
```

#### `get_component($component_name)`
Get a specific plugin component.

```php
$api = $plugin->get_component('archive_api');
$queue = $plugin->get_component('archive_queue');
$admin = $plugin->get_component('admin_page');
```

### Archive API Class: `SWAP_Archive_API`

#### `submit_url($url, $options = [])`
Submit a URL to Archive.org.

```php
$api = new SWAP_Archive_API();
$result = $api->submit_url('https://example.com/post', [
    'capture_all' => 1,
    'capture_outlinks' => 1
]);

if ($result['success']) {
    echo "Archived at: " . $result['archive_url'];
} else {
    echo "Error: " . $result['error'];
}
```

**Parameters:**
- `$url` (string): URL to submit
- `$options` (array): Submission options

**Returns:** Array with 'success' boolean and 'archive_url' or 'error'

#### `check_availability($url)`
Check if a URL is available in Archive.org.

```php
$api = new SWAP_Archive_API();
$result = $api->check_availability('https://example.com/post');

if ($result['available']) {
    echo "Available at: " . $result['archive_url'];
    echo "Archived on: " . $result['timestamp'];
}
```

#### `test_connection()`
Test the API connection with current credentials.

```php
$api = new SWAP_Archive_API();
$result = $api->test_connection();

if ($result['success']) {
    echo "Connection successful";
} else {
    echo "Connection failed: " . $result['error'];
}
```

### Queue Management Class: `SWAP_Archive_Queue`

#### `add_item($post_id, $priority = 'normal')`
Add an item to the submission queue.

```php
$queue = new SWAP_Archive_Queue();
$queue_id = $queue->add_item(123, 'high');

if ($queue_id) {
    echo "Added to queue with ID: " . $queue_id;
}
```

#### `process_queue($batch_size = 10)`
Process items in the queue.

```php
$queue = new SWAP_Archive_Queue();
$result = $queue->process_queue(5);

echo "Processed: " . $result['processed'];
echo "Successful: " . $result['successful'];
echo "Failed: " . $result['failed'];
```

#### `get_queue_stats()`
Get queue statistics.

```php
$queue = new SWAP_Archive_Queue();
$stats = $queue->get_queue_stats();

echo "Pending: " . $stats['pending'];
echo "Processing: " . $stats['processing'];
echo "Completed: " . $stats['completed'];
echo "Failed: " . $stats['failed'];
```

#### `retry_failed_items($limit = 10)`
Retry failed queue items.

```php
$queue = new SWAP_Archive_Queue();
$retried = $queue->retry_failed_items(5);

echo "Retried {$retried} failed items";
```

### Submission Tracker Class: `SWAP_Submission_Tracker`

#### `get_submission_status($post_id)`
Get the submission status for a post.

```php
$tracker = new SWAP_Submission_Tracker();
$status = $tracker->get_submission_status(123);

echo "Status: " . $status['status'];
if ($status['archive_url']) {
    echo "Archive URL: " . $status['archive_url'];
}
```

#### `get_submission_history($post_id)`
Get submission history for a post.

```php
$tracker = new SWAP_Submission_Tracker();
$history = $tracker->get_submission_history(123);

foreach ($history as $submission) {
    echo "Attempt: " . $submission->submitted_at;
    echo "Status: " . $submission->status;
}
```

#### `record_submission($post_id, $data)`
Record a submission attempt.

```php
$tracker = new SWAP_Submission_Tracker();
$tracker->record_submission(123, [
    'url' => 'https://example.com/post',
    'status' => 'success',
    'archive_url' => 'https://web.archive.org/web/...',
    'submitted_at' => current_time('mysql')
]);
```

## REST API Endpoints

### Get Submission Status
```
GET /wp-json/swap/v1/submission/{post_id}
```

**Response:**
```json
{
    "post_id": 123,
    "status": "success",
    "archive_url": "https://web.archive.org/web/20240101000000/https://example.com/post",
    "submitted_at": "2024-01-01 12:00:00",
    "attempts": 1
}
```

### Submit Post to Archive
```
POST /wp-json/swap/v1/submit
```

**Parameters:**
- `post_id` (int): WordPress post ID
- `priority` (string): Queue priority (high, normal, low)

**Response:**
```json
{
    "success": true,
    "message": "Post added to submission queue",
    "queue_id": 456
}
```

### Get Queue Status
```
GET /wp-json/swap/v1/queue
```

**Response:**
```json
{
    "pending": 15,
    "processing": 2,
    "completed": 1250,
    "failed": 8,
    "total": 1275
}
```

---

# Security

## Security Measures

### Data Protection
- **Input Sanitization**: All user input is properly sanitized using WordPress functions
- **Output Escaping**: All output is escaped to prevent XSS attacks
- **SQL Injection Prevention**: All database queries use prepared statements
- **CSRF Protection**: Nonce verification for all forms and AJAX requests

### Access Control
- **Capability Checks**: Proper WordPress capability verification throughout
- **Role-Based Access**: Features restricted based on user roles and capabilities
- **Permission Validation**: Granular permission checking for all operations
- **Admin-Only Features**: Sensitive features restricted to administrators

### API Security
- **Credential Encryption**: API credentials stored securely in WordPress database
- **Secure Transmission**: All API communications use HTTPS
- **Rate Limiting**: Built-in protection against API abuse
- **Error Handling**: Secure error messages that don't expose sensitive information

### Code Security
- **WordPress Standards**: Follows WordPress security coding standards
- **Regular Updates**: Security patches and updates provided regularly
- **Vulnerability Scanning**: Regular security audits and vulnerability assessments
- **Secure Defaults**: Secure configuration options by default

---

# Performance

## Performance Optimizations

### Background Processing
- **Asynchronous Operations**: All heavy operations run in background
- **Queue Management**: Intelligent queue processing to prevent server overload
- **Batch Processing**: Efficient batch processing of multiple items
- **Resource Management**: Careful management of server resources

### Database Optimization
- **Efficient Queries**: Optimized database queries with proper indexing
- **Query Caching**: Intelligent caching of frequently accessed data
- **Cleanup Procedures**: Automatic cleanup of old data to maintain performance
- **Index Optimization**: Proper database indexing for fast lookups

### Memory Management
- **Memory Efficiency**: Optimized memory usage for large sites
- **Garbage Collection**: Proper cleanup of variables and objects
- **Resource Limits**: Respect for PHP memory and execution time limits
- **Scalability**: Designed to handle high-traffic websites

### Caching Integration
- **Object Caching**: Compatible with WordPress object caching
- **Page Caching**: Proper integration with page caching plugins
- **API Response Caching**: Intelligent caching of API responses
- **Cache Invalidation**: Proper cache invalidation when needed

---

# Changelog

## Version 1.0.7 (Current)
**Release Date:** January 2025

### Enhanced
- **Database Handling** - Improved database access patterns by replacing global $wpdb usage with class properties
- **Code Quality** - Enhanced object-oriented design and dependency injection patterns
- **Widget Registration** - Fixed duplicate widget registration issues
- **Version Management** - Updated to stable 1.0.7 release

### Fixed
- **SWAP_Submissions_History** - Replaced global $wpdb with $this->wpdb property
- **SWAP_Post_Actions** - Added proper $wpdb property initialization
- **SWAP_Submission_Tracker** - Enhanced database handling with class properties
- **SWAP_Uninstall_Page** - Improved database operations for uninstall functionality

## Version 0.9.6
**Release Date:** January 2025

### New Features
- Enhanced queue management with priority levels
- Improved error handling and retry logic
- Advanced admin interface with tabbed design
- Comprehensive submission tracking and history
- Widget and shortcode support for frontend display

### Improvements
- Better API integration with Archive.org
- Optimized database queries and performance
- Enhanced security measures and validation
- Improved user interface and user experience
- Better documentation and help system

### Bug Fixes
- Fixed queue processing issues
- Resolved API timeout problems
- Corrected display issues in admin interface
- Fixed compatibility issues with various themes
- Resolved memory usage optimization

### Security Updates
- Enhanced input validation and sanitization
- Improved access control and permissions
- Better error handling and logging
- Updated security measures throughout

## Version 0.9.5
**Release Date:** December 2024

### Features Added
- Auto-submission functionality
- Basic queue management
- Admin interface improvements
- Initial widget support

### Improvements
- Better error handling
- Performance optimizations
- Enhanced API integration
- Improved user interface

## Version 0.9.0
**Release Date:** November 2024

### Initial Release
- Basic Archive.org integration
- Manual post submission
- Simple admin interface
- Core functionality implementation

---

# Support and Resources

## Documentation Resources
- **User Guide**: Comprehensive guide for end users
- **Developer Guide**: Technical documentation for developers
- **API Reference**: Complete API documentation
- **Troubleshooting**: Common issues and solutions

## Community Support
- **WordPress.org Forums**: Community support and discussions
- **GitHub Repository**: Source code and issue tracking
- **Documentation Wiki**: Community-maintained documentation
- **Video Tutorials**: Step-by-step video guides

## Professional Support
- **Priority Support**: Direct access to developers
- **Custom Development**: Tailored solutions for specific needs
- **Training Services**: Professional training and onboarding
- **Consultation**: Expert advice and best practices

## Additional Resources
- **Archive.org Documentation**: Official Archive.org API documentation
- **WordPress Codex**: WordPress development resources
- **PHP Documentation**: PHP language reference
- **Security Guidelines**: WordPress security best practices

---

*This documentation is maintained by the Spun Web Archive Forge development team. For the most up-to-date information, please visit our official documentation website.*

**Last Updated:** January 2025  
**Version:** 1.0.7  
**License:** GPL v2 or later