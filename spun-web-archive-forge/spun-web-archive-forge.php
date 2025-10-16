<?php
/**
 * Plugin Name: ARCHIVE FORGE SWT
 * Plugin URI: https://spunwebtechnology.com/spun-web-archive-pro-wordpress-wayback-archive/
 * Description: Professional WordPress plugin for automatically submitting content to the Internet Archive (Wayback Machine). Includes individual post submission, auto submission, and advanced archiving tools. Compatible with PHP 7.4-8.1+.
 * Version: 1.0.15
 * Author: Spun Web Technology
 * Author URI: https://spunwebtechnology.com/spun-web-archive-pro-wordpress-wayback-archive/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: spun-web-archive-forge
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.8.2
 * Requires PHP: 7.4
 * Network: false
 * Update URI: https://spunwebtechnology.com/spun-web-archive-pro-wordpress-wayback-archive/
 * Requires Plugins: 
 *
 * @package SpunWebArchiveForge
 * @author Ryan Dickie Thompson
 * @copyright 2024 Spun Web Technology
 * @license GPL-2.0-or-later
 * @since 0.0.1
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Self-heal: Clean stale active_plugins entries for old copied/disabled directories
// This prevents WordPress from trying to include non-existent copies on subsequent requests
add_action('plugins_loaded', function() {
    // Only run in admin or CLI contexts to avoid overhead for visitors
    if (!is_admin() && php_sapi_name() !== 'cli') {
        return;
    }

    $active_plugins = get_option('active_plugins', array());
    if (!is_array($active_plugins) || empty($active_plugins)) {
        return;
    }

    $updated = false;
    foreach ($active_plugins as $index => $plugin_path) {
        // Target previous duplicate folder names
        $is_old_copy = stripos($plugin_path, 'Spun Web Archive Forge - Copy/') !== false
            || stripos($plugin_path, 'Spun Web Archive Forge (disabled)/') !== false;

        if ($is_old_copy) {
            $full_path = trailingslashit(WP_PLUGIN_DIR) . $plugin_path;
            if (!file_exists($full_path)) {
                unset($active_plugins[$index]);
                $updated = true;
            }
        }
    }

    if ($updated) {
        update_option('active_plugins', array_values($active_plugins));
        if (function_exists('wp_cache_delete')) {
            wp_cache_delete('alloptions', 'options');
        }
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Spun Web Archive Forge: cleaned stale active_plugins entries for old copies');
        }
    }
}, 1);

// WordPress version compatibility check
if (version_compare($GLOBALS['wp_version'], '5.0', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>';
        printf(
            /* translators: %s: required WordPress version */
            esc_html__('Spun Web Archive Forge requires WordPress %s or higher. Please update WordPress.', 'spun-web-archive-forge'),
            '5.0'
        );
        echo '</p></div>';
    });
    return;
}

// PHP version compatibility check - Updated to support PHP 7.4+
if (version_compare(PHP_VERSION, '7.4', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>';
        printf(
            /* translators: %s: required PHP version */
            esc_html__('Spun Web Archive Forge requires PHP %s or higher. Please update PHP.', 'spun-web-archive-forge'),
            '7.4'
        );
        echo '</p></div>';
    });
    return;
}

// Define plugin constants
define('SWAP_VERSION', '1.0.15');
define('SWAP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SWAP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SWAP_PLUGIN_FILE', __FILE__);
define('SWAP_AUTHOR', 'Spun Web Technology');
define('SWAP_AUTHOR_URI', 'https://spunwebtechnology.com/spun-web-archive-pro-wordpress-wayback-archive/');
define('SWAP_SUPPORT_EMAIL', 'support@spunwebtechnology.com');

// Memory usage safeguards
if (!defined('SWAP_MEMORY_LIMIT')) {
    define('SWAP_MEMORY_LIMIT', '256M');
}

// Set memory limit if current limit is too low
$current_limit = ini_get('memory_limit');
if ($current_limit && $current_limit !== '-1') {
    $current_bytes = wp_convert_hr_to_bytes($current_limit);
    $required_bytes = wp_convert_hr_to_bytes(SWAP_MEMORY_LIMIT);
    
    if ($current_bytes < $required_bytes) {
        @ini_set('memory_limit', SWAP_MEMORY_LIMIT);
    }
}

// Add memory usage monitoring
if (!function_exists('swap_check_memory_usage')) {
    /**
     * Check current memory usage and prevent exhaustion
     * 
     * @param string $context Context where check is performed
     * @return bool True if memory usage is safe
     */
    function swap_check_memory_usage($context = '') {
        $memory_usage = memory_get_usage(true);
        $memory_limit = wp_convert_hr_to_bytes(ini_get('memory_limit'));
        
        // If memory limit is unlimited, return true
        if ($memory_limit === -1) {
            return true;
        }
        
        $usage_percentage = ($memory_usage / $memory_limit) * 100;
        
        // Log warning if usage is above 80%
        if ($usage_percentage > 80) {
            error_log(sprintf(
                'SWAP Memory Warning: %s - Memory usage at %.1f%% (%s of %s)',
                $context,
                $usage_percentage,
                size_format($memory_usage),
                size_format($memory_limit)
            ));
        }
        
        // Return false if usage is above 90%
        return $usage_percentage < 90;
    }
}

/**
 * Main Spun Web Archive Forge Class
 * 
 * @since 0.7.0
 * @package SpunWebArchiveForge
 */
class SpunWebArchiveForge {
    
    /**
     * Single instance of the class
     * 
     * @var SpunWebArchiveForge|null
     */
    private static $instance = null;
    
    /**
     * Archive.org API settings
     * 
     * @var array
     */
    private $api_settings = array();
    
    /**
     * Archive API instance
     * 
     * @var SWAP_Archive_API|null
     */
    private $archive_api;
    
    /**
     * Admin page instance
     * 
     * @var SWAP_Admin_Page|null
     */
    private $admin_page;
    
    /**
     * Auto submitter instance
     * 
     * @var SWAP_Auto_Submitter|null
     */
    private $auto_submitter;
    
    /**
     * Archive queue instance
     * 
     * @var SWAP_Archive_Queue|null
     */
    private $archive_queue;
    
    /**
     * Post actions instance
     * 
     * @var SWAP_Post_Actions|null
     */
    private $post_actions;
    
    /**
     * Admin columns instance
     * 
     * @var SWAP_Admin_Columns|null
     */
    private $admin_columns;
    
    /**
     * Submission tracker instance
     * 
     * @var SWAP_Submission_Tracker|null
     */
    private $submission_tracker;
    
    /**
     * Documentation page instance
     * 
     * @var SWAP_Documentation_Page|null
     */
    private $documentation_page;
    
    /**
     * API callback instance
     * 
     * @var SWAP_API_Callback|null
     */
    private $api_callback;
    
    /**
     * Credentials page instance
     * 
     * @var SWAP_Credentials_Page|null
     */
    private $credentials_page;
    
    /**
     * Footer display instance
     * 
     * @var SWAP_Footer_Display|null
     */
    private $footer_display;
    
    /**
     * Submissions history instance
     * 
     * @var SWAP_Submissions_History|null
     */
    private $submissions_history;
    
    /**
     * Uninstall page instance
     * 
     * @var SWAP_Uninstall_Page|null
     */
    private $uninstall_page;
    
    /**
     * Shortcode handler instance
     * 
     * @var SWAP_Shortcode_Handler|null
     */
    private $shortcode_handler;
    

    
    /**
     * Memory dashboard instance
     * 
     * @var SWAP_Memory_Dashboard|null
     */
    private $memory_dashboard;
    
    /**
     * Get single instance of the plugin
     * 
     * @since 0.7.0
     * @return SpunWebArchiveForge
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor - Initialize the plugin
     * 
     * @since 0.7.0
     */
    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
    }
    
    /**
     * Initialize WordPress hooks and filters
     * 
     * @since 0.7.0
     * @return void
     */
    private function init_hooks() {
        // Core initialization
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Plugin action links
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_plugin_action_links'));
        add_filter('plugin_row_meta', array($this, 'add_plugin_row_meta'), 10, 2);
        
        // Auto submission hooks
        add_action('publish_post', array($this, 'auto_submit_post'), 10, 2);
        add_action('publish_page', array($this, 'auto_submit_page'), 10, 2);
        add_action('post_updated', array($this, 'auto_submit_updated_content'), 10, 3);
        
        // Scheduled tasks
        add_action('swap_process_queue', array($this, 'process_submission_queue'));
        add_action('swap_retry_failed', array($this, 'retry_failed_submissions'));
        
        // Widget registration
        add_action('widgets_init', array($this, 'register_widgets'));
        
        // Frontend assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        
        // AJAX handlers for frontend
        add_action('wp_ajax_swap_refresh_widget', array($this, 'ajax_refresh_widget'));
        add_action('wp_ajax_nopriv_swap_refresh_widget', array($this, 'ajax_refresh_widget'));
        add_action('wp_ajax_swap_get_archive_data', array($this, 'ajax_get_archive_data'));
        add_action('wp_ajax_nopriv_swap_get_archive_data', array($this, 'ajax_get_archive_data'));
        
        // Plugin lifecycle hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Load plugin dependencies and class files
     * 
     * @since 0.7.0
     * @return void
     */
    private function load_dependencies() {
        $includes_dir = SWAP_PLUGIN_DIR . 'includes/';
        
        // Core dependency files
        $dependencies = array(
            'wordpress-compat.php',           // WordPress compatibility helper (load first)
            'class-memory-utils.php',         // Memory monitoring utilities (load early)
            'class-memory-dashboard.php',     // Memory dashboard interface
            'class-archive-api.php',          // Archive.org API integration
            'class-auto-submitter.php',       // Automatic submission handler
            'class-archive-queue.php',        // Submission queue management
            'class-database-migration.php',   // Database schema management
            'class-post-actions.php',         // Individual post actions
            'class-admin-page.php',           // Main admin interface
            'class-admin-columns.php',        // Admin list table columns
            'class-submission-tracker.php',   // Submission tracking
            'class-documentation-page.php',   // Documentation interface
            'class-api-callback.php',         // API callback handling
            'class-credentials-page.php',     // Credentials management
            'class-archive-widget.php',       // Archive widget
            'class-archive-links-widget.php', // Archive links widget
            'class-shortcode-handler.php',    // Shortcode handler
            'class-footer-display.php',       // Footer display
            'class-submissions-history.php',  // Submissions history
            'class-swap-archiver.php',        // Wayback validation system
            'class-uninstall-page.php'        // Uninstall interface
        );
        
        // Load each dependency with error handling
        foreach ($dependencies as $file) {
            $file_path = $includes_dir . $file;
            
            if (file_exists($file_path)) {
                require_once $file_path;
            } else {
                // Log missing file error
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log(sprintf(
                        'Spun Web Archive Forge: Missing dependency file: %s',
                        $file_path
                    ));
                }
                
                // Show admin notice for missing critical files
                add_action('admin_notices', function() use ($file) {
                    echo '<div class="notice notice-error"><p>';
                    printf(
                        /* translators: %s: missing file name */
                        esc_html__('Spun Web Archive Forge: Missing required file: %s', 'spun-web-archive-forge'),
                        esc_html($file)
                    );
                    echo '</p></div>';
                });
            }
        }
    }
    
    /**
     * Initialize the plugin components and settings
     * 
     * @since 0.7.0
     * @return void
     */
    public function init() {
        // Load text domain for internationalization
        load_plugin_textdomain(
            'spun-web-archive-forge',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
        
        // Run database migrations if needed
        $this->run_database_migrations();
        
        // Load API settings with defaults
        $this->api_settings = get_option('swap_api_settings', array(
            'access_key' => '',
            'secret_key' => '',
            'callback_token' => '',
            'api_timeout' => 30,
            'retry_attempts' => 3
        ));
        
        // Initialize all plugin components
        $this->initialize_components();
    }
    
    /**
     * Run database migrations if the migration class exists
     * 
     * @since 0.7.0
     * @return void
     */
    private function run_database_migrations() {
        if (class_exists('SWAP_Database_Migration')) {
            try {
                $migration = new SWAP_Database_Migration();
                $migration->maybe_migrate();
            } catch (Exception $e) {
                $this->log_error('Database migration failed: ' . $e->getMessage());
                $this->show_admin_error('Database migration failed. Please check error logs.');
            }
        }
    }
    
    /**
     * Initialize all plugin components with error handling
     * 
     * @since 0.7.0
     * @return void
     */
    private function initialize_components() {
        // Initialize components in dependency order
        // First, initialize basic components without dependencies
        try {
            if (class_exists('SWAP_Archive_API')) {
                $this->archive_api = new SWAP_Archive_API();
            }
            if (class_exists('SWAP_Archive_Queue')) {
                $this->archive_queue = new SWAP_Archive_Queue();
            }
            if (class_exists('SWAP_Submissions_History')) {
                $this->submissions_history = new SWAP_Submissions_History();
            }
        } catch (Exception $e) {
            $this->log_error('Failed to initialize core components: ' . $e->getMessage());
        }
        
        // Then initialize components that depend on the basic ones
        try {
            if (class_exists('SWAP_Auto_Submitter') && isset($this->archive_api) && isset($this->archive_queue) && isset($this->submissions_history)) {
                $this->auto_submitter = new SWAP_Auto_Submitter($this->archive_api, $this->archive_queue, $this->submissions_history);
            }
            if (class_exists('SWAP_Admin_Page') && isset($this->archive_api) && isset($this->archive_queue)) {
                $this->admin_page = new SWAP_Admin_Page($this->archive_api, $this->archive_queue);
            }
        } catch (Exception $e) {
            $this->log_error('Failed to initialize dependent components: ' . $e->getMessage());
        }
        
        // Initialize remaining components
        $components = array(
            'post_actions' => array(
                'class' => 'SWAP_Post_Actions',
                'args' => array()
            ),
            'admin_columns' => array(
                'class' => 'SWAP_Admin_Columns',
                'args' => array()
            ),
            'submission_tracker' => array(
                'class' => 'SWAP_Submission_Tracker',
                'args' => array()
            ),
            'documentation_page' => array(
                'class' => 'SWAP_Documentation_Page',
                'args' => array()
            ),
            'api_callback' => array(
                'class' => 'SWAP_API_Callback',
                'args' => array()
            ),
            'credentials_page' => array(
                'class' => 'SWAP_Credentials_Page',
                'args' => array()
            ),
            'footer_display' => array(
                'class' => 'SWAP_Footer_Display',
                'args' => array()
            ),
            'uninstall_page' => array(
                'class' => 'SWAP_Uninstall_Page',
                'args' => array()
            ),
            'shortcode_handler' => array(
                'class' => 'SWAP_Shortcode_Handler',
                'args' => array()
            ),
            'memory_dashboard' => array(
                'class' => 'SWAP_Memory_Dashboard',
                'args' => array()
            )
        );
        
        // Initialize remaining components
        foreach ($components as $property => $config) {
            try {
                if (class_exists($config['class'])) {
                    $this->{$property} = new $config['class'](...$config['args']);
                } else {
                    $this->log_error("Class {$config['class']} not found");
                }
            } catch (Exception $e) {
                $this->log_error("Failed to initialize {$config['class']}: " . $e->getMessage());
                $this->show_admin_error("Failed to initialize {$config['class']}. Please check error logs.");
            }
        }
    }
    
    /**
     * Log error message if WP_DEBUG is enabled
     * 
     * @since 0.7.0
     * @param string $message Error message to log
     * @return void
     */
    private function log_error($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Spun Web Archive Forge: ' . $message);
        }
    }
    
    /**
     * Show admin error notice
     * 
     * @since 0.7.0
     * @param string $message Error message to display
     * @return void
     */
    private function show_admin_error($message) {
        add_action('admin_notices', function() use ($message) {
            echo '<div class="notice notice-error"><p>';
            printf(
                /* translators: %s: error message */
                esc_html__('Spun Web Archive Forge Error: %s', 'spun-web-archive-forge'),
                esc_html($message)
            );
            echo '</p></div>';
        });
    }
    

    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on our admin page
        if ($hook !== 'tools_page_spun-web-archive-forge') {
            return;
        }
        
        wp_enqueue_style(
            'swap-admin-css',
            plugin_dir_url(__FILE__) . 'assets/css/admin.css',
            array(),
            SWAP_VERSION
        );
        
        wp_enqueue_script(
            'swap-admin-js',
            plugin_dir_url(__FILE__) . 'assets/js/admin.js',
            array('jquery'),
            SWAP_VERSION,
            true
        );
        
        wp_localize_script('swap-admin-js', 'swap_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('swap_ajax_nonce'),
            'strings' => array(
                'testing' => __('Testing connection...', 'spun-web-archive-forge'),
				'error' => __('Connection failed', 'spun-web-archive-forge'),
				'success' => __('Connection successful', 'spun-web-archive-forge')
            )
        ));
    }
    
    /**
     * Enqueue frontend scripts and styles
     * 
     * @since 0.7.0
     * @return void
     */
    public function enqueue_frontend_scripts() {
        // Enqueue frontend CSS
        wp_enqueue_style(
            'swap-frontend-css',
            SWAP_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            SWAP_VERSION
        );
        
        // Enqueue frontend JavaScript
        wp_enqueue_script(
            'swap-frontend-js',
            SWAP_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            SWAP_VERSION,
            true
        );
        
        // Localize script for AJAX
        wp_localize_script('swap-frontend-js', 'swapFrontend', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('swap_frontend_nonce'),
            'strings' => array(
                'loading' => __('Loading...', 'spun-web-archive-forge'),
                'error' => __('Error loading data', 'spun-web-archive-forge'),
                'noData' => __('No archive data available', 'spun-web-archive-forge')
            )
        ));
    }
    
    /**
     * Register widgets
     * 
     * @since 0.7.0
     * @return void
     */
    public function register_widgets() {
        // Register the original archive widget
        if (function_exists('swap_register_archive_widget')) {
            swap_register_archive_widget();
        }
        
        // Register the new archive links widget
        if (class_exists('SWAP_Archive_Links_Widget')) {
            register_widget('SWAP_Archive_Links_Widget');
        }
    }
    
    // API testing is now handled by the centralized credentials page
    
    /**
     * AJAX handler for getting posts
     */

    
    // Individual post submission is handled by SWAP_Post_Actions class
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu page
        $main_page = add_menu_page(
            __('Spun Web Archive Forge', 'spun-web-archive-forge'),
            __('Web Archive Forge', 'spun-web-archive-forge'),
            'manage_options',
            'spun-web-archive-forge',
            array($this, 'admin_page_callback'),
            'dashicons-archive',
            30
        );
        
        // Add submenu for main settings (duplicate of main page)
        add_submenu_page(
            'spun-web-archive-forge',
            __('Settings', 'spun-web-archive-forge'),
            __('Settings', 'spun-web-archive-forge'),
            'manage_options',
            'spun-web-archive-forge',
            array($this, 'admin_page_callback')
        );
        
        // Add submenu for API Credentials
        add_submenu_page(
            'spun-web-archive-forge',
			__('API Credentials', 'spun-web-archive-forge'),
			__('API Credentials', 'spun-web-archive-forge'),
            'manage_options',
            'spun-web-archive-forge-credentials',
            array($this, 'credentials_page_callback')
        );
        
        // Add submenu for Documentation
        add_submenu_page(
            'spun-web-archive-forge',
            __('Documentation', 'spun-web-archive-forge'),
            __('Documentation', 'spun-web-archive-forge'),
            'manage_options',
            'spun-web-archive-forge-docs',
            array($this, 'documentation_page_callback')
        );
        
        // Add submenu for Submissions History
        add_submenu_page(
            'spun-web-archive-forge',
            __('Submissions History', 'spun-web-archive-forge'),
            __('Submissions History', 'spun-web-archive-forge'),
            'manage_options',
            'spun-web-archive-forge-history',
            array($this, 'submissions_history_page_callback')
        );
    }
    
    /**
     * Add plugin action links
     */
    public function add_plugin_action_links($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=spun-web-archive-forge') . '">' . __('Settings', 'spun-web-archive-forge') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    
    /**
     * Add plugin row meta
     */
    public function add_plugin_row_meta($links, $file) {
        if (plugin_basename(__FILE__) === $file) {
            $row_meta = array(
                'docs' => '<a href="https://spunwebtechnology.com/spun-web-archive-forge-end-user-documentation/" target="_blank">' . __('Documentation', 'spun-web-archive-forge') . '</a>',
                'support' => '<a href="mailto:' . SWAP_SUPPORT_EMAIL . '">' . __('Support', 'spun-web-archive-forge') . '</a>'
            );
            return array_merge($links, $row_meta);
        }
        return $links;
    }
    
    /**
     * Admin page callback
     */
    public function admin_page_callback() {
        $admin_page = new SWAP_Admin_Page($this->archive_api, $this->archive_queue);
        $admin_page->render();
    }
    
    /**
     * Credentials page callback
     */
    public function credentials_page_callback() {
        $credentials_page = new SWAP_Credentials_Page();
        $credentials_page->render_page();
    }
    
    /**
     * Documentation page callback
     */
    public function documentation_page_callback() {
        $documentation_page = new SWAP_Documentation_Page();
        $documentation_page->render();
    }
    
    /**
     * Submissions history page callback
     */
    public function submissions_history_page_callback() {
        $this->submissions_history->render();
    }
    
    /**
     * Initialize admin settings
     */
    public function admin_init() {
        register_setting('swap_settings', 'swap_api_settings');
        register_setting('swap_settings', 'swap_auto_settings');

        
        // Handle CSV export
        if (isset($_GET['action']) && $_GET['action'] === 'swap_export_csv') {
            $this->handle_csv_export();
        }
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function admin_scripts($hook) {
        // Check if we're on any of our plugin pages
        $plugin_pages = array(
            'toplevel_page_spun-web-archive-forge',
            'web-archive-forge_page_spun-web-archive-forge-credentials',
            'web-archive-forge_page_spun-web-archive-forge-docs'
        );
        
        if (!in_array($hook, $plugin_pages)) {
            return;
        }
        
        wp_enqueue_script(
            'swap-admin',
            SWAP_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            SWAP_VERSION,
            true
        );
        
        wp_enqueue_style(
            'swap-admin',
            SWAP_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            SWAP_VERSION
        );
        
        wp_localize_script('swap-admin', 'swap_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('swap_ajax_nonce'),
            'strings' => array(
                'testing' => __('Testing API connection...', 'spun-web-archive-forge'),
                'success' => __('API connection successful!', 'spun-web-archive-forge'),
                'error' => __('API connection failed. Please check your credentials.', 'spun-web-archive-forge'),
                'submitting' => __('Submitting to archive...', 'spun-web-archive-forge'),
                'submitted' => __('Successfully submitted to archive!', 'spun-web-archive-forge'),
                'failed' => __('Submission failed. Please try again.', 'spun-web-archive-forge'),
                'credentials_testing' => __('Testing credentials...', 'spun-web-archive-forge'),
                'credentials_pass' => __('PASS', 'spun-web-archive-forge'),
                'credentials_fail' => __('FAIL', 'spun-web-archive-forge'),
                'credentials_saved' => __('Credentials saved successfully!', 'spun-web-archive-forge')
            )
        ));
    }
    
    /**
     * Auto submit new post
     */
    public function auto_submit_post($post_id, $post) {
        if ($this->should_auto_submit('post')) {
            $auto_submitter = new SWAP_Auto_Submitter();
            $auto_submitter->submit_content($post_id, 'post');
        }
    }
    
    /**
     * Auto submit new page
     */
    public function auto_submit_page($post_id, $post) {
        if ($this->should_auto_submit('page')) {
            $auto_submitter = new SWAP_Auto_Submitter();
            $auto_submitter->submit_content($post_id, 'page');
        }
    }
    
    /**
     * Auto submit updated content
     */
    public function auto_submit_updated_content($post_id, $post_after, $post_before) {
        $auto_settings = get_option('swap_auto_settings', array());
        
        if (isset($auto_settings['submit_updates']) && $auto_settings['submit_updates']) {
            if ($this->should_auto_submit($post_after->post_type)) {
                $auto_submitter = new SWAP_Auto_Submitter();
                $auto_submitter->submit_content($post_id, $post_after->post_type);
            }
        }
    }
    
    /**
     * Check if content should be auto-submitted
     */
    private function should_auto_submit($post_type) {
        $auto_settings = get_option('swap_auto_settings', array());
        
        if (!isset($auto_settings['enabled']) || !$auto_settings['enabled']) {
            return false;
        }
        
        if (!isset($auto_settings['post_types']) || !is_array($auto_settings['post_types'])) {
            return false;
        }
        
        return in_array($post_type, $auto_settings['post_types']);
    }
    

    
    // Post loading is now handled by individual post actions
    
    /**
     * Process submission queue
     */
    public function process_submission_queue() {
        if (isset($this->archive_queue)) {
            $this->archive_queue->process_queue();
        }
    }
    
    /**
     * Retry failed submissions
     */
    public function retry_failed_submissions() {
        if (isset($this->auto_submitter)) {
            $this->auto_submitter->retry_failed_submissions();
        }
    }
    
    // Individual submissions are now handled by SWAP_Post_Actions class
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Check if this is an upgrade and clean transients if needed
        $this->handle_plugin_upgrade();
        
        // Create database tables if needed
        $this->create_tables();
        
        // Create submissions history table
        SWAP_Submissions_History::create_table();
        
        // Create archive queue table with proper schema
        if (class_exists('SWAP_Archive_Queue')) {
            $queue_manager = new SWAP_Archive_Queue();
            $queue_manager->create_table();
        }
        
        // Set default options
        $default_api_settings = array(
            'api_key' => '',
            'api_secret' => '',
            'endpoint' => 'https://web.archive.org/save/'
        );
        add_option('swap_api_settings', $default_api_settings);
        
        $default_auto_settings = array(
            'enabled' => false,
            'post_types' => array('post', 'page'),
            'submit_updates' => false,
            'delay' => 60
        );
        add_option('swap_auto_settings', $default_auto_settings);
        
        // Generate callback token for API callbacks
        if (!get_option('swap_callback_token')) {
            SWAP_API_Callback::generate_callback_token();
        }
        
        // Schedule cron jobs
        if (!wp_next_scheduled('swap_process_queue')) {
            wp_schedule_event(time(), 'hourly', 'swap_process_queue');
        }
        
        if (!wp_next_scheduled('swap_retry_failed')) {
            wp_schedule_event(time(), 'daily', 'swap_retry_failed');
        }
        
        // Update plugin version
        update_option('swap_plugin_version', SWAP_VERSION);
    }
    
    /**
     * Handle CSV export of submission history
     */
    private function handle_csv_export() {
        // Security checks
        if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'swap_export_csv')) {
            wp_die(__('Security check failed', 'spun-web-archive-forge'));
	}

	if (!current_user_can('manage_options')) {
		wp_die(__('Insufficient permissions', 'spun-web-archive-forge'));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'swap_submissions_history';
        
        // Get all submissions
        $submissions = $wpdb->get_results(
            "SELECT s.*, p.post_title, p.post_type 
             FROM $table_name s 
             LEFT JOIN $wpdb->posts p ON s.post_id = p.ID 
             ORDER BY s.submitted_at DESC"
        );
        
        // Set headers for CSV download
        $filename = 'spun-web-archive-submissions-' . date('Y-m-d-H-i-s') . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Open output stream
        $output = fopen('php://output', 'w');
        
        // Add CSV headers
        fputcsv($output, array(
            __('Post Title', 'spun-web-archive-forge'),
            __('URL', 'spun-web-archive-forge'),
            __('Archive.org URL', 'spun-web-archive-forge'),
            __('Status', 'spun-web-archive-forge'),
            __('Submission Date', 'spun-web-archive-forge')
        ));
        
        // Add data rows
        foreach ($submissions as $submission) {
            $archive_url = !empty($submission->archive_url) ? $submission->archive_url : __('Not available', 'spun-web-archive-forge');
            
            fputcsv($output, array(
                $submission->post_title ?: __('Unknown', 'spun-web-archive-forge'),
                $submission->url,
                $archive_url,
                $submission->status,
                $submission->submitted_at
            ));
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear any scheduled events
        wp_clear_scheduled_hook('swap_process_queue');
        wp_clear_scheduled_hook('swap_retry_failed');
    }
    
    /**
     * Handle plugin upgrade - clean transients and cached data
     */
    private function handle_plugin_upgrade() {
        $current_version = get_option('swap_plugin_version', '0.0.0');
        
        // If this is an upgrade (version changed), clean transients
        if (version_compare($current_version, SWAP_VERSION, '<')) {
            $this->cleanup_transients_on_upgrade();
            
            // Log upgrade if debug is enabled
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf(
                    'Spun Web Archive Forge upgraded from %s to %s - transients cleaned',
                    $current_version,
                    SWAP_VERSION
                ));
            }
        }
    }
    
    /**
     * Clean transients and cached data during plugin upgrade
     */
    private function cleanup_transients_on_upgrade() {
        global $wpdb;
        
        // Delete all plugin-specific transients
        $transients = array(
            'swap_api_test_result',
            'swap_queue_stats',
            'swap_submission_stats',
            'swap_api_connection_status',
            'swap_recent_submissions'
        );
        
        foreach ($transients as $transient) {
            delete_transient($transient);
        }
        
        // Clean any transients that start with our prefix
        $wpdb->query($wpdb->prepare("
            DELETE FROM {$wpdb->options} 
            WHERE option_name LIKE %s 
            OR option_name LIKE %s
        ", '_transient_swap_%', '_transient_timeout_swap_%'));
        
        // Clear any cached data
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
    }
    
    /**
     * AJAX handler for refreshing widgets
     * 
     * @since 0.7.0
     * @return void
     */
    public function ajax_refresh_widget() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'swap_frontend_nonce')) {
            wp_die('Security check failed');
        }
        
        $widget_id = sanitize_text_field($_POST['widget_id'] ?? '');
        $widget_type = sanitize_text_field($_POST['widget_type'] ?? '');
        
        if (empty($widget_id) || empty($widget_type)) {
            wp_send_json_error('Invalid widget parameters');
        }
        
        // Get widget instance and render
        $widget_data = array();
        
        if ($widget_type === 'archive_links' && class_exists('SWAP_Archive_Links_Widget')) {
            $widget = new SWAP_Archive_Links_Widget();
            $instance = get_option('widget_' . $widget_id, array());
            
            ob_start();
            $widget->widget(array(), $instance);
            $widget_data['html'] = ob_get_clean();
        }
        
        wp_send_json_success($widget_data);
    }
    
    /**
     * AJAX handler for getting archive data
     * 
     * @since 0.7.0
     * @return void
     */
    public function ajax_get_archive_data() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'swap_frontend_nonce')) {
            wp_die('Security check failed');
        }
        
        $post_id = intval($_POST['post_id'] ?? 0);
        $type = sanitize_text_field($_POST['type'] ?? 'current');
        
        if (empty($post_id) && $type === 'current') {
            wp_send_json_error('Invalid post ID');
        }
        
        // Get archive data using submissions history
        if (isset($this->submissions_history)) {
            $data = array();
            
            switch ($type) {
                case 'current':
                    $data = $this->submissions_history->get_post_submissions($post_id);
                    break;
                case 'recent':
                    $data = $this->submissions_history->get_recent_submissions(5);
                    break;
                case 'popular':
                    $data = $this->submissions_history->get_popular_submissions(5);
                    break;
            }
            
            wp_send_json_success($data);
        }
        
        wp_send_json_error('Archive data not available');
    }
    
    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'swap_submissions_history';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            url varchar(255) NOT NULL,
            status varchar(50) NOT NULL,
            archive_url varchar(255) DEFAULT '',
            submitted_at datetime DEFAULT CURRENT_TIMESTAMP,
            response_data text,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

// Initialize the plugin
function swap_init() {
    return SpunWebArchiveForge::get_instance();
}

// Start the plugin
add_action('plugins_loaded', 'swap_init');

// Register a 5-min schedule for archive validation
add_filter('cron_schedules', function($schedules){
    if ( ! isset($schedules['every_five_minutes']) ) {
        $schedules['every_five_minutes'] = ['interval' => 300, 'display' => 'Every 5 Minutes'];
    }
    return $schedules;
});

// On activation, schedule the validation cron
register_activation_hook( __FILE__, function(){
    if ( ! wp_next_scheduled('swap_validate_archives_cron') ) {
        wp_schedule_event( time() + 60, 'every_five_minutes', 'swap_validate_archives_cron' );
    }
});

// On deactivation, clear scheduled event
register_deactivation_hook( __FILE__, function(){
    $timestamp = wp_next_scheduled('swap_validate_archives_cron');
    if ( $timestamp ) {
        wp_unschedule_event( $timestamp, 'swap_validate_archives_cron' );
    }
});

// Cron callback for archive validation
add_action('swap_validate_archives_cron', function(){
    if (class_exists('SWP_Archiver')) {
        $archiver = new SWP_Archiver();
        $archiver->sweep_stuck_processing( 15, 50 );
    }
});
