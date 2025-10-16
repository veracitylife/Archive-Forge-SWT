# Spun Web Archive Forge - Developer Guide

## Table of Contents
1. [Architecture Overview](#architecture-overview)
2. [Plugin Structure](#plugin-structure)
3. [Core Classes](#core-classes)
4. [Hooks and Filters](#hooks-and-filters)
5. [Database Schema](#database-schema)
6. [API Integration](#api-integration)
7. [Development Setup](#development-setup)
8. [Customization](#customization)
9. [Testing](#testing)
10. [Security](#security)

## Architecture Overview

Spun Web Archive Forge follows a modular, object-oriented architecture with clear separation of concerns. The plugin is built using modern PHP patterns and WordPress best practices.

### Design Principles
- **Single Responsibility**: Each class has a specific, well-defined purpose
- **Dependency Injection**: Components are loosely coupled through dependency injection
- **Event-Driven**: Uses WordPress hooks system for extensibility
- **Security First**: All inputs are sanitized, outputs are escaped
- **Performance Optimized**: Efficient database queries and caching strategies

### Core Components
```
Spun_Web_Archive_Forge (Main Plugin Class)
├── SWAP_Archive_API (Archive.org API Integration)
├── SWAP_Admin_Page (Admin Interface)
├── SWAP_Auto_Submitter (Automatic Submission)
├── SWAP_Archive_Queue (Queue Management)
├── SWAP_Post_Actions (Individual Post Actions)
├── SWAP_Admin_Columns (Admin List Columns)
├── SWAP_Submission_Tracker (Submission Tracking)
├── SWAP_Documentation_Page (Documentation)
├── SWAP_API_Callback (API Callbacks)
├── SWAP_Credentials_Page (Credentials Management)
├── SWAP_Archive_Widget (Archive Widget)
├── SWAP_Archive_Links_Widget (Archive Links Widget)
├── SWAP_Shortcode_Handler (Shortcode Processing)
├── SWAP_Footer_Display (Footer Display)
├── SWAP_Submissions_History (History Management)
└── SWAP_Uninstall_Page (Uninstall Interface)
```

## Plugin Structure

```
spun-web-archive-forge/
├── spun-web-archive-forge.php          # Main plugin file
├── uninstall.php                       # Uninstall handler
├── README.md                           # Plugin documentation
├── CHANGELOG.md                        # Version history
├── SECURITY.md                         # Security documentation
├── DEVELOPER-README.md                 # Developer notes
├── phpstan.neon                        # Static analysis config
├── .wordpress-stubs.php                # WordPress stubs for IDE
├── assets/                             # Frontend assets
│   ├── css/
│   │   ├── admin.css                   # Admin styles
│   │   └── frontend.css                # Frontend styles
│   └── js/
│       ├── admin.js                    # Admin JavaScript
│       ├── frontend.js                 # Frontend JavaScript
│       ├── post-actions.js             # Post action handlers
│       └── uninstall.js                # Uninstall interface
├── includes/                           # Core classes
│   ├── class-admin-page.php            # Main admin interface
│   ├── class-archive-api.php           # Archive.org API client
│   ├── class-auto-submitter.php        # Automatic submission
│   ├── class-archive-queue.php         # Queue management
│   ├── class-post-actions.php          # Individual post actions
│   ├── class-admin-columns.php         # Admin list columns
│   ├── class-submission-tracker.php    # Submission tracking
│   ├── class-documentation-page.php    # Documentation page
│   ├── class-api-callback.php          # API callback handler
│   ├── class-credentials-page.php      # Credentials management
│   ├── class-archive-widget.php        # Archive widget
│   ├── class-archive-links-widget.php  # Archive links widget
│   ├── class-shortcode-handler.php     # Shortcode handler
│   ├── class-footer-display.php        # Footer display
│   ├── class-submissions-history.php   # History management
│   ├── class-uninstall-page.php        # Uninstall interface
│   ├── class-database-migration.php    # Database migrations
│   └── wordpress-compat.php            # WordPress compatibility
├── tests/                              # Test files
│   └── test-compatibility.php          # Compatibility tests
└── docs/                               # Documentation
    ├── USER-GUIDE.md                   # User documentation
    ├── DEVELOPER-GUIDE.md              # This file
    └── FEATURES.md                     # Features overview
```

## Core Classes

### Main Plugin Class: `Spun_Web_Archive_Forge`

The main plugin class follows the singleton pattern and orchestrates all plugin components.

```php
class Spun_Web_Archive_Forge {
    private static $instance = null;
    private $admin_page;
    private $auto_submitter;
    private $archive_queue;
    // ... other components

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
    }
}
```

### Archive API: `SWAP_Archive_API`

Handles all communication with the Internet Archive API.

```php
class SWAP_Archive_API {
    private $access_key;
    private $secret_key;
    private $base_url = 'https://web.archive.org/save/';

    public function submit_url($url, $options = array()) {
        // Submit URL to Archive.org
        // Returns: array with success status and archive URL
    }

    public function test_connection() {
        // Test API credentials
        // Returns: boolean success status
    }
}
```

### Admin Page: `SWAP_Admin_Page`

Manages the plugin's admin interface with tabbed navigation.

```php
class SWAP_Admin_Page {
    private $archive_api;
    private $queue;
    private $current_tab = 'api';
    private $valid_tabs = [
        'api' => 'API Settings',
        'auto' => 'Auto Submission',
        'queue' => 'Queue Management',
        'history' => 'Submission History'
    ];

    public function render() {
        // Render admin interface
    }
}
```

### Auto Submitter: `SWAP_Auto_Submitter`

Handles automatic submission of new content.

```php
class SWAP_Auto_Submitter {
    public function __construct() {
        add_action('publish_post', array($this, 'auto_submit_post'));
        add_action('publish_page', array($this, 'auto_submit_page'));
    }

    public function auto_submit_post($post_id, $post) {
        // Auto-submit published posts
    }
}
```

### Archive Queue: `SWAP_Archive_Queue`

Manages the submission queue with retry logic.

```php
class SWAP_Archive_Queue {
    public function add_to_queue($post_id, $url, $priority = 'normal') {
        // Add item to submission queue
    }

    public function process_queue($batch_size = 5) {
        // Process queued submissions
    }

    public function retry_failed($max_retries = 3) {
        // Retry failed submissions
    }
}
```

## Hooks and Filters

### WordPress Core Hooks Used

#### Actions
```php
// Plugin initialization
add_action('init', array($this, 'init'));
add_action('admin_init', array($this, 'admin_init'));
add_action('admin_menu', array($this, 'add_admin_menu'));

// Asset loading
add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));

// Content submission
add_action('publish_post', array($this, 'auto_submit_post'), 10, 2);
add_action('publish_page', array($this, 'auto_submit_page'), 10, 2);
add_action('post_updated', array($this, 'auto_submit_updated_content'), 10, 3);

// Scheduled tasks
add_action('swap_process_queue', array($this, 'process_submission_queue'));
add_action('swap_retry_failed', array($this, 'retry_failed_submissions'));

// Widget registration
add_action('widgets_init', array($this, 'register_widgets'));

// AJAX handlers
add_action('wp_ajax_swap_test_api', array($this, 'ajax_test_api'));
add_action('wp_ajax_swap_submit_post', array($this, 'ajax_submit_post'));
add_action('wp_ajax_swap_get_queue_status', array($this, 'ajax_get_queue_status'));
```

#### Filters
```php
// Plugin links
add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_plugin_action_links'));
add_filter('plugin_row_meta', array($this, 'add_plugin_row_meta'), 10, 2);

// Admin columns
add_filter('manage_posts_columns', array($this, 'add_archive_column'));
add_filter('manage_pages_columns', array($this, 'add_archive_column'));

// Post row actions
add_filter('post_row_actions', array($this, 'add_archive_row_action'), 10, 2);
add_filter('page_row_actions', array($this, 'add_archive_row_action'), 10, 2);

// Cron schedules
add_filter('cron_schedules', array($this, 'add_custom_cron_schedules'));
```

### Custom Hooks for Developers

#### Actions
```php
// Before submission
do_action('swap_before_submit', $post_id, $url, $options);

// After successful submission
do_action('swap_after_submit_success', $post_id, $url, $archive_url);

// After failed submission
do_action('swap_after_submit_failure', $post_id, $url, $error_message);

// Queue processing
do_action('swap_before_queue_process', $queue_items);
do_action('swap_after_queue_process', $processed_items, $results);

// Settings update
do_action('swap_settings_updated', $section, $old_settings, $new_settings);
```

#### Filters
```php
// Modify submission URL
$url = apply_filters('swap_submission_url', $url, $post_id);

// Modify API options
$options = apply_filters('swap_api_options', $options, $post_id, $url);

// Modify queue priority
$priority = apply_filters('swap_queue_priority', $priority, $post_id, $post_type);

// Modify retry attempts
$max_retries = apply_filters('swap_max_retries', $max_retries, $post_id, $error_count);

// Modify auto-submission eligibility
$should_auto_submit = apply_filters('swap_should_auto_submit', $should_auto_submit, $post_id, $post);

// Modify archive link display
$archive_link = apply_filters('swap_archive_link_html', $archive_link, $post_id, $archive_url);
```

## Database Schema

### Tables

#### `wp_swap_submissions`
Stores submission history and status.

```sql
CREATE TABLE wp_swap_submissions (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    post_id bigint(20) unsigned NOT NULL,
    url varchar(2048) NOT NULL,
    archive_url varchar(2048) DEFAULT NULL,
    status varchar(20) NOT NULL DEFAULT 'pending',
    submitted_at datetime NOT NULL,
    completed_at datetime DEFAULT NULL,
    error_message text DEFAULT NULL,
    retry_count int(11) NOT NULL DEFAULT 0,
    priority varchar(20) NOT NULL DEFAULT 'normal',
    user_id bigint(20) unsigned DEFAULT NULL,
    PRIMARY KEY (id),
    KEY post_id (post_id),
    KEY status (status),
    KEY submitted_at (submitted_at)
);
```

#### `wp_swap_queue`
Manages submission queue.

```sql
CREATE TABLE wp_swap_queue (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    post_id bigint(20) unsigned NOT NULL,
    url varchar(2048) NOT NULL,
    priority varchar(20) NOT NULL DEFAULT 'normal',
    status varchar(20) NOT NULL DEFAULT 'pending',
    created_at datetime NOT NULL,
    scheduled_at datetime DEFAULT NULL,
    attempts int(11) NOT NULL DEFAULT 0,
    last_error text DEFAULT NULL,
    PRIMARY KEY (id),
    KEY post_id (post_id),
    KEY status (status),
    KEY priority (priority),
    KEY scheduled_at (scheduled_at)
);
```

### Options
Plugin settings are stored in WordPress options table:

```php
// API settings
get_option('swap_api_settings', array(
    'access_key' => '',
    'secret_key' => '',
    'timeout' => 30,
    'max_retries' => 3
));

// Auto submission settings
get_option('swap_auto_settings', array(
    'enabled' => false,
    'post_types' => array('post', 'page'),
    'exclude_categories' => array(),
    'delay' => 0
));

// Queue settings
get_option('swap_queue_settings', array(
    'batch_size' => 5,
    'processing_interval' => 300,
    'max_queue_size' => 1000,
    'cleanup_days' => 30
));

// Display settings
get_option('swap_display_settings', array(
    'show_column' => true,
    'show_meta_box' => true,
    'show_row_actions' => true,
    'show_footer_link' => false
));
```

## API Integration

### Archive.org API

The plugin integrates with the Internet Archive's Save Page Now API.

#### Endpoint
```
POST https://web.archive.org/save/{URL}
```

#### Authentication
Uses S3-style authentication with access key and secret key.

#### Request Format
```php
$headers = array(
    'Authorization' => $this->generate_auth_header($url),
    'Content-Type' => 'application/x-www-form-urlencoded',
    'User-Agent' => 'SpunWebArchiveForge/' . SWAP_VERSION
);

$body = array(
    'url' => $url,
    'capture_all' => 'on',
    'capture_outlinks' => 'on',
    'capture_screenshot' => 'on'
);
```

#### Response Handling
```php
// Success response
{
    "url": "https://example.com",
    "job_id": "spn2-abc123",
    "message": "The URL has been successfully submitted for archiving."
}

// Error response
{
    "error": "Invalid URL",
    "status_ext": "error",
    "message": "The URL provided is not valid."
}
```

### Rate Limiting
The plugin implements rate limiting to respect Archive.org's API limits:

```php
// Default rate limits
private $rate_limits = array(
    'requests_per_minute' => 15,
    'requests_per_hour' => 200,
    'requests_per_day' => 1000
);
```

## Development Setup

### Prerequisites
- WordPress 5.0+
- PHP 7.4+ (recommended: PHP 8.1+)
- MySQL 5.6+
- Composer (for development dependencies)
- Node.js (for asset building)

### Local Development Environment

#### 1. Clone Repository
```bash
git clone https://github.com/your-repo/spun-web-archive-forge.git
cd spun-web-archive-forge
```

#### 2. Install Dependencies
```bash
# PHP dependencies (development)
composer install --dev

# Node.js dependencies (if using build tools)
npm install
```

#### 3. Configure Development Environment

Create a local WordPress installation and symlink the plugin:
```bash
# Symlink to WordPress plugins directory
ln -s /path/to/plugin /path/to/wordpress/wp-content/plugins/spun-web-archive-forge
```

#### 4. Development Tools

##### PHPStan (Static Analysis)
```bash
# Run static analysis
vendor/bin/phpstan analyse --configuration=phpstan.neon

# Or use the provided configuration
phpstan analyse
```

##### WordPress Coding Standards
```bash
# Install WPCS
composer global require "wp-coding-standards/wpcs"

# Run code sniffer
phpcs --standard=WordPress spun-web-archive-forge.php includes/
```

##### Unit Testing
```bash
# Run compatibility tests
php run-tests.php

# Run specific test
php test-admin-page.php
```

### IDE Configuration

#### VS Code
Add to `.vscode/settings.json`:
```json
{
    "php.validate.executablePath": "/path/to/php",
    "php.suggest.basic": false,
    "intelephense.stubs": ["wordpress"],
    "intelephense.environment.includePaths": [
        "/path/to/wordpress"
    ]
}
```

#### PhpStorm
1. Install WordPress plugin
2. Enable WordPress support in project settings
3. Configure WordPress installation path
4. Set up code style to WordPress standards

### Asset Development

#### CSS Development
```bash
# Watch for changes (if using build tools)
npm run watch:css

# Build for production
npm run build:css
```

#### JavaScript Development
```bash
# Watch for changes
npm run watch:js

# Build for production
npm run build:js
```

## Customization

### Extending the Plugin

#### Custom Submission Handler
```php
class My_Custom_Submitter {
    public function __construct() {
        add_filter('swap_before_submit', array($this, 'modify_submission'), 10, 3);
        add_action('swap_after_submit_success', array($this, 'handle_success'), 10, 3);
    }

    public function modify_submission($post_id, $url, $options) {
        // Modify submission parameters
        $options['custom_param'] = 'value';
        return $options;
    }

    public function handle_success($post_id, $url, $archive_url) {
        // Handle successful submission
        update_post_meta($post_id, '_custom_archive_data', array(
            'archived_at' => current_time('mysql'),
            'archive_url' => $archive_url
        ));
    }
}

new My_Custom_Submitter();
```

#### Custom Queue Priority
```php
add_filter('swap_queue_priority', function($priority, $post_id, $post_type) {
    // High priority for featured posts
    if (get_post_meta($post_id, '_featured_post', true)) {
        return 'high';
    }
    
    // Low priority for drafts
    if (get_post_status($post_id) === 'draft') {
        return 'low';
    }
    
    return $priority;
}, 10, 3);
```

#### Custom Auto-Submission Logic
```php
add_filter('swap_should_auto_submit', function($should_submit, $post_id, $post) {
    // Don't auto-submit private posts
    if ($post->post_status === 'private') {
        return false;
    }
    
    // Only auto-submit posts with specific meta
    if (!get_post_meta($post_id, '_enable_archive', true)) {
        return false;
    }
    
    return $should_submit;
}, 10, 3);
```

### Custom Widgets

#### Archive Status Widget
```php
class My_Archive_Status_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'my_archive_status',
            'Archive Status',
            array('description' => 'Display archive status for current post')
        );
    }

    public function widget($args, $instance) {
        global $post;
        
        if (!$post) return;
        
        $archive_url = get_post_meta($post->ID, '_swap_archive_url', true);
        
        echo $args['before_widget'];
        echo $args['before_title'] . 'Archive Status' . $args['after_title'];
        
        if ($archive_url) {
            echo '<p><a href="' . esc_url($archive_url) . '" target="_blank">View Archive</a></p>';
        } else {
            echo '<p>Not archived</p>';
        }
        
        echo $args['after_widget'];
    }
}

add_action('widgets_init', function() {
    register_widget('My_Archive_Status_Widget');
});
```

### Custom Shortcodes

#### Archive Link Shortcode
```php
add_shortcode('my_archive_link', function($atts) {
    $atts = shortcode_atts(array(
        'post_id' => get_the_ID(),
        'text' => 'View Archive',
        'class' => 'archive-link'
    ), $atts);
    
    $archive_url = get_post_meta($atts['post_id'], '_swap_archive_url', true);
    
    if (!$archive_url) {
        return '';
    }
    
    return sprintf(
        '<a href="%s" class="%s" target="_blank">%s</a>',
        esc_url($archive_url),
        esc_attr($atts['class']),
        esc_html($atts['text'])
    );
});
```

## Testing

### Unit Tests

#### Test Structure
```php
class SWAP_Test_Archive_API extends WP_UnitTestCase {
    private $api;
    
    public function setUp() {
        parent::setUp();
        $this->api = new SWAP_Archive_API();
    }
    
    public function test_url_validation() {
        $this->assertTrue($this->api->is_valid_url('https://example.com'));
        $this->assertFalse($this->api->is_valid_url('invalid-url'));
    }
    
    public function test_api_connection() {
        // Mock API credentials
        update_option('swap_api_settings', array(
            'access_key' => 'test_key',
            'secret_key' => 'test_secret'
        ));
        
        // Test connection (would need mocking for real tests)
        $result = $this->api->test_connection();
        $this->assertIsArray($result);
    }
}
```

#### Running Tests
```bash
# Run all tests
phpunit

# Run specific test class
phpunit tests/test-archive-api.php

# Run with coverage
phpunit --coverage-html coverage/
```

### Integration Tests

#### Test Admin Interface
```php
public function test_admin_page_rendering() {
    // Set up admin user
    $user_id = $this->factory->user->create(array('role' => 'administrator'));
    wp_set_current_user($user_id);
    
    // Test admin page
    $admin_page = new SWAP_Admin_Page();
    
    ob_start();
    $admin_page->render();
    $output = ob_get_clean();
    
    $this->assertStringContains('swap-admin-page', $output);
    $this->assertStringContains('API Settings', $output);
}
```

#### Test Queue Processing
```php
public function test_queue_processing() {
    $queue = new SWAP_Archive_Queue();
    
    // Add test items to queue
    $post_id = $this->factory->post->create();
    $queue->add_to_queue($post_id, 'https://example.com/test');
    
    // Process queue
    $results = $queue->process_queue(1);
    
    $this->assertCount(1, $results);
    $this->assertEquals('processed', $results[0]['status']);
}
```

### Manual Testing

#### Test Checklist
- [ ] Plugin activation/deactivation
- [ ] API credential validation
- [ ] Individual post submission
- [ ] Auto-submission functionality
- [ ] Queue processing
- [ ] Error handling
- [ ] Admin interface responsiveness
- [ ] Widget functionality
- [ ] Shortcode rendering
- [ ] Uninstall process

## Security

### Security Measures Implemented

#### Input Sanitization
```php
// Sanitize text input
$access_key = sanitize_text_field($_POST['access_key']);

// Sanitize URLs
$url = esc_url_raw($_POST['url']);

// Sanitize arrays
$post_types = array_map('sanitize_text_field', $_POST['post_types']);
```

#### Output Escaping
```php
// Escape HTML output
echo esc_html($message);

// Escape attributes
echo '<a href="' . esc_url($url) . '" class="' . esc_attr($class) . '">';

// Escape JavaScript
wp_localize_script('script', 'data', array(
    'message' => esc_js($message)
));
```

#### Nonce Verification
```php
// Generate nonce
wp_nonce_field('swap_settings', 'swap_nonce');

// Verify nonce
if (!wp_verify_nonce($_POST['swap_nonce'], 'swap_settings')) {
    wp_die('Security check failed');
}
```

#### Capability Checks
```php
// Check user capabilities
if (!current_user_can('manage_options')) {
    wp_die('Insufficient permissions');
}

// Check specific capabilities
if (!current_user_can('edit_post', $post_id)) {
    return new WP_Error('insufficient_permissions', 'Cannot edit this post');
}
```

#### SQL Injection Prevention
```php
// Use prepared statements
$wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}swap_submissions WHERE post_id = %d",
    $post_id
);

// Escape table names
$table_name = $wpdb->prefix . 'swap_submissions';
```

### Security Best Practices

#### 1. Validate All Input
```php
public function validate_settings($input) {
    $output = array();
    
    // Validate access key
    if (isset($input['access_key'])) {
        $output['access_key'] = sanitize_text_field($input['access_key']);
        if (empty($output['access_key'])) {
            add_settings_error('swap_settings', 'access_key', 'Access key is required');
        }
    }
    
    // Validate timeout
    if (isset($input['timeout'])) {
        $timeout = intval($input['timeout']);
        $output['timeout'] = ($timeout >= 5 && $timeout <= 300) ? $timeout : 30;
    }
    
    return $output;
}
```

#### 2. Secure AJAX Handlers
```php
public function ajax_submit_post() {
    // Verify nonce
    check_ajax_referer('swap_ajax_nonce', 'nonce');
    
    // Check capabilities
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    // Validate input
    $post_id = intval($_POST['post_id']);
    if (!$post_id || !get_post($post_id)) {
        wp_send_json_error('Invalid post ID');
    }
    
    // Process request
    $result = $this->submit_post($post_id);
    wp_send_json_success($result);
}
```

#### 3. Secure File Operations
```php
// Validate file paths
$file_path = wp_normalize_path($file_path);
if (strpos($file_path, ABSPATH) !== 0) {
    return new WP_Error('invalid_path', 'Invalid file path');
}

// Use WordPress filesystem API
$wp_filesystem = WP_Filesystem();
if (!$wp_filesystem) {
    return new WP_Error('filesystem_error', 'Cannot access filesystem');
}
```

### Security Audit Checklist

- [ ] All user input is sanitized
- [ ] All output is properly escaped
- [ ] Nonces are used for all forms
- [ ] Capability checks are in place
- [ ] SQL queries use prepared statements
- [ ] File operations are secure
- [ ] AJAX handlers are protected
- [ ] No sensitive data in logs
- [ ] Proper error handling
- [ ] Secure credential storage

---

*Last updated: January 2025*
*Plugin Version: 1.0.7*