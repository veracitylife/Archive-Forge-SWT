<?php
/**
 * Admin Page Handler
 * 
 * Handles the plugin's admin interface and settings pages with modern PHP patterns,
 * improved error handling, and responsive design.
 * 
 * @package SpunWebArchiveForge
 * @subpackage Includes
 * @author Ryan Dickie Thompson
 * @copyright 2024 Spun Web Technology
 * @license GPL-2.0-or-later
 * @since 2.0.0
 * @version 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin Page Class
 *
 * Manages the plugin's admin interface, settings, and queue operations.
 *
 * @since 2.0.0
 */
class SWAP_Admin_Page {
    
    /**
     * Archive API instance
     *
     * @since 2.0.0
     * @var SWAP_Archive_API|null
     */
    private ?SWAP_Archive_API $archive_api = null;
    
    /**
     * Queue instance
     *
     * @since 2.0.0
     * @var SWAP_Archive_Queue|null
     */
    private ?SWAP_Archive_Queue $queue = null;
    
    /**
     * Current tab
     *
     * @since 2.0.0
     * @var string
     */
    private string $current_tab = 'api';
    
    /**
     * Valid tabs
     *
     * @since 2.0.0
     * @var array<string, string>
     */
    private array $valid_tabs = [
        'api' => 'API Settings',
        'auto' => 'Auto Submission',
        'queue' => 'Queue Management',
        'history' => 'Submission History',
        'shortcodes' => 'Shortcode Reference',
        'display' => 'Display Settings'
    ];
    
    /**
     * Settings sections
     *
     * @since 2.0.0
     * @var array<string, array>
     */
    private array $settings_sections = [];
    
    /**
     * Constructor
     *
     * @since 2.0.0
     * @param SWAP_Archive_API|null   $archive_api Archive API instance
     * @param SWAP_Archive_Queue|null $queue       Queue instance
     */
    public function __construct(?SWAP_Archive_API $archive_api = null, ?SWAP_Archive_Queue $queue = null) {
        $this->archive_api = $archive_api;
        $this->queue = $queue;
        
        $this->init_hooks();
        $this->init_settings_sections();
    }
    
    /**
     * Initialize hooks
     *
     * @since 2.0.0
     * @return void
     */
    private function init_hooks(): void {
        // Register AJAX handlers for queue management
        add_action('wp_ajax_swap_process_queue', [$this, 'ajax_process_queue']);
        add_action('wp_ajax_swap_clear_completed', [$this, 'ajax_clear_completed']);
        add_action('wp_ajax_swap_clear_failed', [$this, 'ajax_clear_failed']);
        add_action('wp_ajax_swap_refresh_queue_stats', [$this, 'ajax_refresh_queue_stats']);
        add_action('wp_ajax_swap_manage_queue', [$this, 'ajax_manage_queue']);
        
        // Register AJAX handlers for admin functionality
        add_action('wp_ajax_swap_test_api_credentials', [$this, 'ajax_test_api_credentials']);
        add_action('wp_ajax_swap_submit_single_post', [$this, 'ajax_submit_single_post']);
        add_action('wp_ajax_swap_get_submission_status', [$this, 'ajax_get_submission_status']);
        add_action('wp_ajax_swap_validate_now', [$this, 'ajax_validate_now']);
        
        // Admin enqueue scripts
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }
    
    /**
     * Initialize settings sections
     *
     * @since 2.0.0
     * @return void
     */
    private function init_settings_sections(): void {
        $this->settings_sections = [
            'api' => [
                'title' => __('API Settings', 'spun-web-archive-forge'),
                'description' => __('Configure Archive.org API settings and submission methods.', 'spun-web-archive-forge'),
                'callback' => [$this, 'render_api_settings']
            ],
            'auto' => [
                'title' => __('Auto Submission', 'spun-web-archive-forge'),
                'description' => __('Configure automatic submission settings for posts and pages.', 'spun-web-archive-forge'),
                'callback' => [$this, 'render_auto_settings']
            ],
            'queue' => [
                'title' => __('Queue Management', 'spun-web-archive-forge'),
                'description' => __('Manage the archive submission queue and processing settings.', 'spun-web-archive-forge'),
                'callback' => [$this, 'render_queue_settings']
            ],
            'history' => [
                'title' => __('Submission History', 'spun-web-archive-forge'),
                'description' => __('View and manage submission history and statistics.', 'spun-web-archive-forge'),
                'callback' => [$this, 'render_history_settings']
            ],
            'shortcodes' => [
                'title' => __('Shortcode Reference', 'spun-web-archive-forge'),
                'description' => __('Available shortcodes for displaying archive information on your site.', 'spun-web-archive-forge'),
                'callback' => [$this, 'render_shortcode_reference']
            ],
            'display' => [
                'title' => __('Display Settings', 'spun-web-archive-forge'),
                'description' => __('Configure how archive information is displayed on your site.', 'spun-web-archive-forge'),
                'callback' => [$this, 'render_display_settings']
            ]
        ];
    }
    
    /**
     * Enqueue admin scripts and styles
     *
     * @since 2.0.0
     * @param string $hook_suffix Current admin page hook suffix
     * @return void
     */
    public function enqueue_admin_scripts(string $hook_suffix): void {
        // Only load on our admin page
        if (strpos($hook_suffix, 'spun-web-archive-forge') === false) {
            return;
        }
        
        wp_enqueue_style(
            'swap-admin-style',
            plugin_dir_url(dirname(__FILE__)) . 'assets/css/admin.css',
            [],
            SWAP_VERSION
        );
        
        wp_enqueue_script(
            'swap-admin-script',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/admin.js',
            ['jquery'],
            SWAP_VERSION,
            true
        );
        
        wp_localize_script('swap-admin-script', 'swapAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('swap_queue_management'),
            'strings' => [
                'processing' => __('Processing...', 'spun-web-archive-forge'),
                'success' => __('Operation completed successfully!', 'spun-web-archive-forge'),
                'error' => __('An error occurred. Please try again.', 'spun-web-archive-forge'),
                'confirmClear' => __('Are you sure you want to clear these items?', 'spun-web-archive-forge')
            ]
        ]);
    }
    
    /**
     * Render the admin page
     *
     * @since 2.0.0
     * @return void
     */
    public function render(): void {
        try {
            // Handle form submissions
            if ($this->should_process_form()) {
                $this->process_form_submission();
            }
            
            // Determine current tab
            $this->current_tab = $this->get_current_tab();
            
            // Get current settings
            $settings = $this->get_all_settings();
            
            $this->render_admin_page($settings);
            
        } catch (Exception $e) {
            $this->render_error_page($e->getMessage());
        }
    }
    
    /**
     * Check if form should be processed
     *
     * @since 2.0.0
     * @return bool
     */
    private function should_process_form(): bool {
        return isset($_POST['submit']) && 
               wp_verify_nonce($_POST['_wpnonce'] ?? '', 'swap_settings') &&
               current_user_can('manage_options');
    }
    
    /**
     * Process form submission
     *
     * @since 2.0.0
     * @return void
     */
    private function process_form_submission(): void {
        try {
            $this->save_settings();
            add_settings_error(
                'swap_settings', 
                'settings_updated', 
                __('Settings saved successfully!', 'spun-web-archive-forge'), 
                'updated'
            );
        } catch (Exception $e) {
            add_settings_error(
                'swap_settings', 
                'settings_error', 
                sprintf(__('Error saving settings: %s', 'spun-web-archive-forge'), $e->getMessage()), 
                'error'
            );
        }
    }
    
    /**
     * Get current tab
     *
     * @since 2.0.0
     * @return string
     */
    private function get_current_tab(): string {
        $tab = sanitize_text_field($_GET['tab'] ?? 'api');
        return array_key_exists($tab, $this->valid_tabs) ? $tab : 'api';
    }
    
    /**
     * Get all settings
     *
     * @since 2.0.0
     * @return array<string, array>
     */
    private function get_all_settings(): array {
        return [
            'api' => get_option('swap_api_settings', []),
            'auto' => get_option('swap_auto_settings', []),
            'queue' => get_option('swap_queue_settings', []),
            'display' => get_option('swap_display_settings', [])
        ];
    }
    
    /**
     * Render the main admin page
     *
     * @since 2.0.0
     * @param array $settings All plugin settings
     * @return void
     */
    private function render_admin_page(array $settings): void {
        ?>
        <div class="wrap swap-admin-wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?> <span class="swap-version-badge">v<?php echo esc_html(SWAP_VERSION); ?></span></h1>
            
            <div class="swap-branding-info">
                <p><strong>ARCHIVE FORGE SWT</strong> by <a href="https://spunwebtechnology.com/spun-web-archive-pro-wordpress-wayback-archive/" target="_blank">Spun Web Technology</a></p>
                <div class="swap-support-links">
                    <span>📞 <a href="tel:+18882646790">+1 (888) 264-6790</a></span> |
                    <span>💬 <a href="http://web.libera.chat/#spunwebtechnology" target="_blank">@spun_web on IRC</a></span> |
                    <span>🌐 <a href="https://spunwebtechnology.com/spun-web-archive-pro-wordpress-wayback-archive/" target="_blank">spunwebtechnology.com</a></span> |
                    <span>✉️ <a href="mailto:support@spunwebtechnology.com">support@spunwebtechnology.com</a></span>
                </div>
            </div>
            
            <?php settings_errors('swap_settings'); ?>
            
            <div class="swap-admin-container">
                <div class="swap-admin-content">
                    <?php $this->render_tab_navigation(); ?>
                    
                    <form method="post" action="">
                        <?php wp_nonce_field('swap_settings'); ?>
                        
                        <div class="swap-tab-content">
                            <?php $this->render_current_tab_content($settings); ?>
                        </div>
                        
                        <?php submit_button(); ?>
                    </form>
                </div>
                
                <div class="swap-admin-sidebar">
                    <?php $this->render_sidebar($settings); ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render tab navigation
     *
     * @since 2.0.0
     * @return void
     */
    private function render_tab_navigation(): void {
        ?>
        <nav class="nav-tab-wrapper wp-clearfix">
            <?php foreach ($this->valid_tabs as $tab_key => $tab_label): ?>
                <a href="<?php echo esc_url(add_query_arg('tab', $tab_key)); ?>" 
                   class="nav-tab <?php echo $this->current_tab === $tab_key ? 'nav-tab-active' : ''; ?>">
                    <?php echo esc_html(__($tab_label, 'spun-web-archive-forge')); ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <?php
    }
    
    /**
     * Render current tab content
     *
     * @since 2.0.0
     * @param array $settings All plugin settings
     * @return void
     */
    private function render_current_tab_content(array $settings): void {
        // Debug: Check if current tab exists in settings sections
        if (!isset($this->settings_sections[$this->current_tab])) {
            echo '<div class="notice notice-error"><p>Error: Tab "' . esc_html($this->current_tab) . '" not found in settings sections.</p></div>';
            return;
        }
        
        $section = $this->settings_sections[$this->current_tab];
        
        echo '<div class="swap-tab-section">';
        echo '<h2>' . esc_html($section['title']) . '</h2>';
        echo '<p class="description">' . esc_html($section['description']) . '</p>';
        
        // Check if callback method exists
        if (is_callable($section['callback'])) {
            call_user_func($section['callback'], $settings);
        } else {
            echo '<div class="notice notice-error"><p>Error: Callback method not callable for tab "' . esc_html($this->current_tab) . '".</p></div>';
        }
        
        echo '</div>';
    }
    
    /**
     * Render API settings tab
     *
     * @since 2.0.0
     * @param array $settings All plugin settings
     * @return void
     */
    private function render_api_settings(array $settings): void {
        $api_settings = $settings['api'];
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="submission_method"><?php _e('Submission Method', 'spun-web-archive-forge'); ?></label>
                </th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><?php _e('Submission Method', 'spun-web-archive-forge'); ?></legend>
                        <label>
                            <input type="radio" name="swap_api_settings[submission_method]" value="simple" 
                                   <?php checked(($api_settings['submission_method'] ?? 'simple'), 'simple'); ?> />
                            <?php _e('Simple Submission', 'spun-web-archive-forge'); ?>
                        </label>
                        <p class="description"><?php _e('Submit URLs directly to Archive.org without authentication. No API credentials required.', 'spun-web-archive-forge'); ?></p>
                        
                        <label>
                            <input type="radio" name="swap_api_settings[submission_method]" value="api" 
                                   <?php checked(($api_settings['submission_method'] ?? 'simple'), 'api'); ?> />
                            <?php _e('API Submission', 'spun-web-archive-forge'); ?>
                        </label>
                        <p class="description"><?php _e('Use Archive.org S3 API for authenticated submissions. Requires API credentials.', 'spun-web-archive-forge'); ?></p>
                    </fieldset>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="archive_username"><?php _e('Archive.org Username', 'spun-web-archive-forge'); ?></label>
                </th>
                <td>
                    <input type="text" id="archive_username" name="swap_api_settings[archive_username]" 
                           value="<?php echo esc_attr($api_settings['archive_username'] ?? ''); ?>" 
                           class="regular-text" placeholder="@username" />
                    <p class="description">
                        <?php _e('Your Archive.org username (with @ symbol) for displaying your archive page link.', 'spun-web-archive-forge'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="api_endpoint"><?php _e('API Endpoint', 'spun-web-archive-forge'); ?></label>
                </th>
                <td>
                    <input type="url" id="api_endpoint" name="swap_api_settings[endpoint]" 
                           value="<?php echo esc_attr($api_settings['endpoint'] ?? 'https://web.archive.org/save/'); ?>" 
                           class="regular-text" />
                    <p class="description">
                        <?php _e('Archive.org API endpoint URL. Leave default unless instructed otherwise.', 'spun-web-archive-forge'); ?>
                    </p>
                </td>
            </tr>
        </table>
        
        <?php $this->render_api_credentials_section($api_settings); ?>
        <?php
    }
    
    /**
     * Render API credentials section
     *
     * @since 2.0.0
     * @param array $api_settings API settings
     * @return void
     */
    private function render_api_credentials_section(array $api_settings): void {
        ?>
        <div class="swap-credentials-section">
            <h3><?php _e('API Credentials', 'spun-web-archive-forge'); ?></h3>
            
            <?php if (class_exists('SWAP_Credentials_Page')): ?>
                <p><?php _e('API credentials are managed through the centralized credentials system.', 'spun-web-archive-forge'); ?></p>
                <p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=spun-web-archive-forge-credentials')); ?>" class="button button-secondary">
                        <?php _e('Manage Credentials', 'spun-web-archive-forge'); ?>
                    </a>
                    <button type="button" id="test-api-connection" class="button button-secondary">
                        <?php _e('Test Connection', 'spun-web-archive-forge'); ?>
                    </button>
                </p>
                <div id="api-test-result" class="notice" style="display: none;"></div>
            <?php else: ?>
                <div class="notice notice-warning inline">
                    <p><?php _e('Centralized credentials system not available. Please ensure all plugin components are properly installed.', 'spun-web-archive-forge'); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render auto submission settings tab
     *
     * @since 2.0.0
     * @param array $settings All plugin settings
     * @return void
     */
    private function render_auto_settings(array $settings): void {
        $auto_settings = $settings['auto'];
        $post_types = get_post_types(['public' => true], 'objects');
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="auto_enabled"><?php _e('Enable Auto Submission', 'spun-web-archive-forge'); ?></label>
                </th>
                <td>
                    <input type="checkbox" id="auto_enabled" name="swap_auto_settings[enabled]" 
                           value="1" <?php checked(!empty($auto_settings['enabled'])); ?> />
                    <label for="auto_enabled"><?php _e('Automatically submit new posts and pages to Archive.org', 'spun-web-archive-forge'); ?></label>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label><?php _e('Post Types to Archive', 'spun-web-archive-forge'); ?></label>
                </th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><?php _e('Post Types to Archive', 'spun-web-archive-forge'); ?></legend>
                        <?php foreach ($post_types as $post_type): ?>
                            <label>
                                <input type="checkbox" name="swap_auto_settings[post_types][]" 
                                       value="<?php echo esc_attr($post_type->name); ?>"
                                       <?php checked(in_array($post_type->name, $auto_settings['post_types'] ?? [])); ?> />
                                <?php echo esc_html($post_type->label); ?>
                            </label><br>
                        <?php endforeach; ?>
                    </fieldset>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="submit_updates"><?php _e('Submit Updates', 'spun-web-archive-forge'); ?></label>
                </th>
                <td>
                    <input type="checkbox" id="submit_updates" name="swap_auto_settings[submit_updates]" 
                           value="1" <?php checked(!empty($auto_settings['submit_updates'])); ?> />
                    <label for="submit_updates"><?php _e('Also submit when posts are updated', 'spun-web-archive-forge'); ?></label>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="submission_delay"><?php _e('Submission Delay (minutes)', 'spun-web-archive-forge'); ?></label>
                </th>
                <td>
                    <input type="number" id="submission_delay" name="swap_auto_settings[delay]" 
                           value="<?php echo esc_attr($auto_settings['delay'] ?? 5); ?>" 
                           min="0" max="1440" class="small-text" />
                    <p class="description">
                        <?php _e('Delay before submitting to archive (0 for immediate submission).', 'spun-web-archive-forge'); ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Render queue management settings tab
     *
     * @since 2.0.0
     * @param array $settings All plugin settings
     * @return void
     */
    private function render_queue_settings(array $settings): void {
        $queue_settings = $settings['queue'];
        $queue_stats = $this->get_queue_statistics();
        ?>
        <div class="swap-queue-stats">
            <h3><?php _e('Queue Statistics', 'spun-web-archive-forge'); ?></h3>
            <div class="swap-stats-grid">
                <div class="swap-stat-item">
                    <span class="swap-stat-number"><?php echo esc_html($queue_stats['pending']); ?></span>
                    <span class="swap-stat-label"><?php _e('Pending', 'spun-web-archive-forge'); ?></span>
                </div>
                <div class="swap-stat-item">
                    <span class="swap-stat-number"><?php echo esc_html($queue_stats['processing']); ?></span>
                    <span class="swap-stat-label"><?php _e('Processing', 'spun-web-archive-forge'); ?></span>
                </div>
                <div class="swap-stat-item">
                    <span class="swap-stat-number"><?php echo esc_html($queue_stats['completed']); ?></span>
                    <span class="swap-stat-label"><?php _e('Completed', 'spun-web-archive-forge'); ?></span>
                </div>
                <div class="swap-stat-item">
                    <span class="swap-stat-number"><?php echo esc_html($queue_stats['failed']); ?></span>
                    <span class="swap-stat-label"><?php _e('Failed', 'spun-web-archive-forge'); ?></span>
                </div>
            </div>
        </div>
        
        <div class="swap-queue-actions">
            <h3><?php _e('Queue Actions', 'spun-web-archive-forge'); ?></h3>
            <p class="description"><?php _e('Manage your archive submission queue.', 'spun-web-archive-forge'); ?></p>
            
            <div class="swap-action-buttons">
                <button type="button" id="process-queue-btn" class="button button-primary">
                    <?php _e('Process Queue Now', 'spun-web-archive-forge'); ?>
                </button>
                <button type="button" id="validate-archives-btn" class="button button-secondary">
                    <?php _e('Validate Archives', 'spun-web-archive-forge'); ?>
                </button>
                <button type="button" id="clear-completed-btn" class="button">
                    <?php _e('Clear Completed', 'spun-web-archive-forge'); ?>
                </button>
                <button type="button" id="clear-failed-btn" class="button">
                    <?php _e('Clear Failed', 'spun-web-archive-forge'); ?>
                </button>
                <button type="button" id="refresh-stats-btn" class="button">
                    <?php _e('Refresh Stats', 'spun-web-archive-forge'); ?>
                </button>
            </div>
            
            <div id="validation-results" class="notice" style="display: none; margin-top: 10px;"></div>
            
            <div id="queue-operation-result" class="notice" style="display: none;"></div>
        </div>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="processing_interval"><?php _e('Processing Interval (seconds)', 'spun-web-archive-forge'); ?></label>
                </th>
                <td>
                    <input type="number" id="processing_interval" name="swap_queue_settings[processing_interval]" 
                           value="<?php echo esc_attr($queue_settings['processing_interval'] ?? 3600); ?>" 
                           min="300" max="86400" class="small-text" />
                    <p class="description">
                        <?php _e('How often the queue should be processed automatically (300-86400 seconds).', 'spun-web-archive-forge'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="max_attempts"><?php _e('Maximum Retry Attempts', 'spun-web-archive-forge'); ?></label>
                </th>
                <td>
                    <input type="number" id="max_attempts" name="swap_queue_settings[max_attempts]" 
                           value="<?php echo esc_attr($queue_settings['max_attempts'] ?? 3); ?>" 
                           min="1" max="10" class="small-text" />
                    <p class="description">
                        <?php _e('Number of times to retry failed submissions.', 'spun-web-archive-forge'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="auto_clear_completed"><?php _e('Auto-clear Completed', 'spun-web-archive-forge'); ?></label>
                </th>
                <td>
                    <input type="checkbox" id="auto_clear_completed" name="swap_queue_settings[auto_clear_completed]" 
                           value="1" <?php checked(!empty($queue_settings['auto_clear_completed'])); ?> />
                    <label for="auto_clear_completed"><?php _e('Automatically remove completed items after 7 days', 'spun-web-archive-forge'); ?></label>
                </td>
            </tr>
        </table>
        
        <?php $this->render_queue_items_table(); ?>
        <?php
    }
    
    /**
     * Render submission history tab
     *
     * @since 2.0.0
     * @param array $settings All plugin settings
     * @return void
     */
    private function render_history_settings(array $settings): void {
        ?>
        <div class="swap-history-section">
            <h3><?php _e('Recent Submissions', 'spun-web-archive-forge'); ?></h3>
            
            <div class="swap-history-management">
                <h4><?php _e('Queue Management', 'spun-web-archive-forge'); ?></h4>
                <p><?php _e('Use these options to manage your submission queue and history.', 'spun-web-archive-forge'); ?></p>
                
                <div class="swap-management-buttons">
                    <button type="button" class="button button-secondary" id="clear-pending-submissions" data-action="clear_pending">
                        <?php _e('Clear Pending Submissions', 'spun-web-archive-forge'); ?>
                    </button>
                    <button type="button" class="button button-secondary" id="clear-failed-submissions" data-action="clear_failed">
                        <?php _e('Clear Failed Submissions', 'spun-web-archive-forge'); ?>
                    </button>
                    <button type="button" class="button button-secondary button-danger" id="clear-entire-queue" data-action="clear_all">
                        <?php _e('Clear Entire Queue', 'spun-web-archive-forge'); ?>
                    </button>
                </div>
                
                <div id="queue-management-status" class="notice" style="display: none;"></div>
            </div>
            
            <?php $this->render_submissions_table(); ?>
        </div>
        
        <style>
        .swap-management-buttons {
            margin: 15px 0;
        }
        .swap-management-buttons .button {
            margin-right: 10px;
            margin-bottom: 5px;
        }
        .button-danger {
            color: #d63638 !important;
            border-color: #d63638 !important;
        }
        .button-danger:hover {
            background-color: #d63638 !important;
            color: #fff !important;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('.swap-management-buttons button').on('click', function() {
                var action = $(this).data('action');
                var button = $(this);
                var statusDiv = $('#queue-management-status');
                
                if (action === 'clear_all') {
                    if (!confirm('<?php _e('Are you sure you want to clear the entire queue? This action cannot be undone.', 'spun-web-archive-forge'); ?>')) {
                        return;
                    }
                }
                
                button.prop('disabled', true).text('<?php _e('Processing...', 'spun-web-archive-forge'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'swap_manage_queue',
                        queue_action: action,
                        nonce: '<?php echo wp_create_nonce('swap_manage_queue'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            statusDiv.removeClass('notice-error').addClass('notice-success').text(response.data.message).show();
                            // Reload the submissions table
                            location.reload();
                        } else {
                            statusDiv.removeClass('notice-success').addClass('notice-error').text(response.data.message || '<?php _e('An error occurred.', 'spun-web-archive-forge'); ?>').show();
                        }
                    },
                    error: function() {
                        statusDiv.removeClass('notice-success').addClass('notice-error').text('<?php _e('An error occurred while processing the request.', 'spun-web-archive-forge'); ?>').show();
                    },
                    complete: function() {
                        button.prop('disabled', false);
                        // Restore original button text
                        if (action === 'clear_pending') {
                            button.text('<?php _e('Clear Pending Submissions', 'spun-web-archive-forge'); ?>');
                        } else if (action === 'clear_failed') {
                            button.text('<?php _e('Clear Failed Submissions', 'spun-web-archive-forge'); ?>');
                        } else if (action === 'clear_all') {
                            button.text('<?php _e('Clear Entire Queue', 'spun-web-archive-forge'); ?>');
                        }
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render shortcode reference tab
     *
     * @since 2.0.0
     * @param array $settings All plugin settings
     * @return void
     */
    private function render_shortcode_reference(array $settings): void {
        ?>
        <div class="swap-shortcode-section">
            <h3><?php _e('Available Shortcodes', 'spun-web-archive-forge'); ?></h3>
            <p><?php _e('Use these shortcodes to display archive information on your posts, pages, or widgets.', 'spun-web-archive-forge'); ?></p>
            
            <div class="swap-shortcode-list">
                <div class="swap-shortcode-item">
                    <h4><code>[swap_archive_status]</code></h4>
                    <p><?php _e('Displays the archive status of the current post or page.', 'spun-web-archive-forge'); ?></p>
                    <p><strong><?php _e('Attributes:', 'spun-web-archive-forge'); ?></strong></p>
                    <ul>
                        <li><code>post_id</code> - <?php _e('Specific post ID (optional, defaults to current post)', 'spun-web-archive-forge'); ?></li>
                        <li><code>class</code> - <?php _e('CSS class for styling (optional)', 'spun-web-archive-forge'); ?></li>
                    </ul>
                    <p><strong><?php _e('Example:', 'spun-web-archive-forge'); ?></strong> <code>[swap_archive_status post_id="123" class="my-archive-status"]</code></p>
                </div>
                
                <div class="swap-shortcode-item">
                    <h4><code>[swap_archive_list]</code></h4>
                    <p><?php _e('Displays a list of archived content with links to Archive.org.', 'spun-web-archive-forge'); ?></p>
                    <p><strong><?php _e('Attributes:', 'spun-web-archive-forge'); ?></strong></p>
                    <ul>
                        <li><code>limit</code> - <?php _e('Number of items to display (default: 10)', 'spun-web-archive-forge'); ?></li>
                        <li><code>post_type</code> - <?php _e('Filter by post type (optional)', 'spun-web-archive-forge'); ?></li>
                        <li><code>class</code> - <?php _e('CSS class for styling (optional)', 'spun-web-archive-forge'); ?></li>
                    </ul>
                    <p><strong><?php _e('Example:', 'spun-web-archive-forge'); ?></strong> <code>[swap_archive_list limit="5" post_type="post"]</code></p>
                </div>
                
                <div class="swap-shortcode-item">
                    <h4><code>[swap_archive_count]</code></h4>
                    <p><?php _e('Displays the total number of archived items.', 'spun-web-archive-forge'); ?></p>
                    <p><strong><?php _e('Attributes:', 'spun-web-archive-forge'); ?></strong></p>
                    <ul>
                        <li><code>post_type</code> - <?php _e('Filter by post type (optional)', 'spun-web-archive-forge'); ?></li>
                        <li><code>status</code> - <?php _e('Filter by status: completed, pending, failed (optional)', 'spun-web-archive-forge'); ?></li>
                    </ul>
                    <p><strong><?php _e('Example:', 'spun-web-archive-forge'); ?></strong> <code>[swap_archive_count post_type="page" status="completed"]</code></p>
                </div>
            </div>
            
            <div class="swap-shortcode-tips">
                <h3><?php _e('Usage Tips', 'spun-web-archive-forge'); ?></h3>
                <ul>
                    <li><?php _e('Shortcodes can be used in posts, pages, text widgets, and most theme areas that support shortcodes.', 'spun-web-archive-forge'); ?></li>
                    <li><?php _e('Use CSS classes to style the output to match your theme.', 'spun-web-archive-forge'); ?></li>
                    <li><?php _e('Combine multiple shortcodes to create comprehensive archive information displays.', 'spun-web-archive-forge'); ?></li>
                    <li><?php _e('Test shortcodes in a preview environment before publishing.', 'spun-web-archive-forge'); ?></li>
                </ul>
            </div>
        </div>
        
        <style>
        .swap-shortcode-item {
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .swap-shortcode-item h4 {
            margin-top: 0;
            color: #0073aa;
        }
        .swap-shortcode-item code {
            background: #fff;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: Consolas, Monaco, monospace;
        }
        .swap-shortcode-item ul {
            margin-left: 20px;
        }
        .swap-shortcode-tips {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            border-radius: 4px;
            padding: 15px;
            margin-top: 20px;
        }
        .swap-shortcode-tips h3 {
            margin-top: 0;
            color: #0073aa;
        }
        </style>
        <?php
    }
    
    /**
     * Render display settings tab
     *
     * @since 2.0.0
     * @param array $settings All plugin settings
     * @return void
     */
    private function render_display_settings(array $settings): void {
        $display_settings = $settings['display'];
        ?>
        <div class="swap-display-section">
            <h3><?php _e('Display Configuration', 'spun-web-archive-forge'); ?></h3>
            <p><?php _e('Configure how archive information is displayed on your website.', 'spun-web-archive-forge'); ?></p>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="author_url"><?php _e('Plugin Author URL', 'spun-web-archive-forge'); ?></label>
                    </th>
                    <td>
                        <input type="url" id="author_url" name="swap_display_settings[author_url]" 
                               value="<?php echo esc_attr($display_settings['author_url'] ?? 'https://spunwebtechnology.com/spun-web-archive-pro-wordpress-wayback-archive/'); ?>" 
                               class="regular-text" />
                        <p class="description">
                            <?php _e('URL to the plugin author\'s website. This will be used for attribution links.', 'spun-web-archive-forge'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="footer_text"><?php _e('Footer Link Text', 'spun-web-archive-forge'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="footer_text" name="swap_display_settings[footer_text]" 
                               value="<?php echo esc_attr($display_settings['footer_text'] ?? __('Archived with Internet Archive', 'spun-web-archive-forge')); ?>" 
                               class="regular-text" />
                        <p class="description">
                            <?php _e('Text to display for the archive link in the footer.', 'spun-web-archive-forge'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="footer_position"><?php _e('Footer Link Position', 'spun-web-archive-forge'); ?></label>
                    </th>
                    <td>
                        <select id="footer_position" name="swap_display_settings[footer_position]">
                            <option value="left" <?php selected(($display_settings['footer_position'] ?? 'center'), 'left'); ?>>
                                <?php _e('Left', 'spun-web-archive-forge'); ?>
                            </option>
                            <option value="center" <?php selected(($display_settings['footer_position'] ?? 'center'), 'center'); ?>>
                                <?php _e('Center', 'spun-web-archive-forge'); ?>
                            </option>
                            <option value="right" <?php selected(($display_settings['footer_position'] ?? 'center'), 'right'); ?>>
                                <?php _e('Right', 'spun-web-archive-forge'); ?>
                            </option>
                        </select>
                        <p class="description">
                            <?php _e('Position of the footer link on your website.', 'spun-web-archive-forge'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label><?php _e('Footer Display Options', 'spun-web-archive-forge'); ?></label>
                    </th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><?php _e('Footer Display Options', 'spun-web-archive-forge'); ?></legend>
                            
                            <label>
                                <input type="checkbox" name="swap_display_settings[show_user_archive_link]" 
                                       value="1" <?php checked(!empty($display_settings['show_user_archive_link'])); ?> />
                                <?php _e('Show User Archive Link', 'spun-web-archive-forge'); ?>
                            </label>
                            <p class="description">
                                <?php _e('Display a link to the user\'s Internet Archive page in the footer.', 'spun-web-archive-forge'); ?>
                            </p>
                            <br>
                            
                            <label>
                                <input type="checkbox" name="swap_display_settings[show_plugin_author_link]" 
                                       value="1" <?php checked(!empty($display_settings['show_plugin_author_link'])); ?> />
                                <?php _e('Show Plugin Author Link', 'spun-web-archive-forge'); ?>
                            </label>
                            <p class="description">
                                <?php _e('Display a link to the plugin author\'s website in the footer.', 'spun-web-archive-forge'); ?>
                            </p>
                        </fieldset>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="archive_username"><?php _e('Archive.org Username', 'spun-web-archive-forge'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="archive_username" name="swap_display_settings[archive_username]" 
                               value="<?php echo esc_attr($display_settings['archive_username'] ?? ''); ?>" 
                               class="regular-text" />
                        <p class="description">
                            <?php _e('Your Archive.org username for display purposes. This is used for widget and footer links.', 'spun-web-archive-forge'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }
    
    /**
     * Render sidebar
     *
     * @since 2.0.0
     * @param array $settings All plugin settings
     * @return void
     */
    private function render_sidebar(array $settings): void {
        ?>
        <div class="swap-sidebar-section">
            <h3><?php _e('Getting Started', 'spun-web-archive-forge'); ?></h3>
            <ol>
                <li><?php _e('Configure your submission method in API Settings', 'spun-web-archive-forge'); ?></li>
                <li><?php _e('Set up auto submission for new content', 'spun-web-archive-forge'); ?></li>
                <li><?php _e('Monitor queue processing and statistics', 'spun-web-archive-forge'); ?></li>
                <li><?php _e('Review submission history and results', 'spun-web-archive-forge'); ?></li>
            </ol>
        </div>
        
        <div class="swap-sidebar-section">
            <h3><?php _e('Archive Statistics', 'spun-web-archive-forge'); ?></h3>
            <?php $this->render_archive_stats(); ?>
        </div>
        
        <div class="swap-sidebar-section">
            <h3><?php _e('Support', 'spun-web-archive-forge'); ?></h3>
            <p><?php _e('Need help? Check our documentation or contact support.', 'spun-web-archive-forge'); ?></p>
            <p>
                <a href="https://spunwebtechnology.com/spun-web-archive-pro-wordpress-wayback-archive/" 
                   class="button button-secondary" target="_blank">
                    <?php _e('Documentation', 'spun-web-archive-forge'); ?>
                </a>
            </p>
        </div>
        <?php
    }
    
    /**
     * Render error page
     *
     * @since 2.0.0
     * @param string $error_message Error message to display
     * @return void
     */
    private function render_error_page(string $error_message): void {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <div class="notice notice-error">
                <p><?php printf(__('Error: %s', 'spun-web-archive-forge'), esc_html($error_message)); ?></p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get queue statistics
     *
     * @since 2.0.0
     * @return array<string, int>
     */
    private function get_queue_statistics(): array {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'swap_archive_queue';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            return [
                'pending' => 0,
                'processing' => 0,
                'completed' => 0,
                'failed' => 0,
                'total' => 0
            ];
        }
        
        $stats = [
            'pending' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table_name} WHERE status = %s", 'pending')),
            'processing' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table_name} WHERE status = %s", 'processing')),
            'completed' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table_name} WHERE status = %s", 'completed')),
            'failed' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table_name} WHERE status = %s", 'failed'))
        ];
        
        $stats['total'] = array_sum($stats);
        
        return $stats;
    }
    
    /**
     * Save settings
     *
     * @since 2.0.0
     * @return void
     * @throws Exception If settings validation fails
     */
    private function save_settings(): void {
        if (!current_user_can('manage_options')) {
            throw new Exception(__('Insufficient permissions to save settings.', 'spun-web-archive-forge'));
        }
        
        // Save API settings
        if (isset($_POST['swap_api_settings'])) {
            $api_settings = $this->sanitize_api_settings($_POST['swap_api_settings']);
            update_option('swap_api_settings', $api_settings);
        }
        
        // Save auto submission settings
        if (isset($_POST['swap_auto_settings'])) {
            $auto_settings = $this->sanitize_auto_settings($_POST['swap_auto_settings']);
            update_option('swap_auto_settings', $auto_settings);
        }
        
        // Save queue settings
        if (isset($_POST['swap_queue_settings'])) {
            $queue_settings = $this->sanitize_queue_settings($_POST['swap_queue_settings']);
            update_option('swap_queue_settings', $queue_settings);
        }
        
        // Save display settings
        if (isset($_POST['swap_display_settings'])) {
            $display_settings = $this->sanitize_display_settings($_POST['swap_display_settings']);
            update_option('swap_display_settings', $display_settings);
        }
    }
    
    /**
     * Sanitize API settings
     *
     * @since 2.0.0
     * @param array $input Raw input data
     * @return array Sanitized settings
     */
    private function sanitize_api_settings(array $input): array {
        return [
            'submission_method' => in_array($input['submission_method'] ?? '', ['simple', 'api']) 
                ? $input['submission_method'] 
                : 'simple',
            'endpoint' => esc_url_raw($input['endpoint'] ?? 'https://web.archive.org/save/'),
            'archive_username' => sanitize_text_field($input['archive_username'] ?? '')
        ];
    }
    
    /**
     * Sanitize auto submission settings
     *
     * @since 2.0.0
     * @param array $input Raw input data
     * @return array Sanitized settings
     */
    private function sanitize_auto_settings(array $input): array {
        $post_types = get_post_types(['public' => true]);
        $selected_post_types = isset($input['post_types']) && is_array($input['post_types'])
            ? array_intersect($input['post_types'], array_keys($post_types))
            : [];
        
        return [
            'enabled' => !empty($input['enabled']),
            'post_types' => $selected_post_types,
            'submit_updates' => !empty($input['submit_updates']),
            'delay' => max(0, min(1440, intval($input['delay'] ?? 5)))
        ];
    }
    
    /**
     * Sanitize queue settings
     *
     * @since 2.0.0
     * @param array $input Raw input data
     * @return array Sanitized settings
     */
    private function sanitize_queue_settings(array $input): array {
        return [
            'processing_interval' => max(300, min(86400, intval($input['processing_interval'] ?? 3600))),
            'max_attempts' => max(1, min(10, intval($input['max_attempts'] ?? 3))),
            'auto_clear_completed' => !empty($input['auto_clear_completed'])
        ];
    }
    
    /**
     * Sanitize display settings
     *
     * @since 2.0.0
     * @param array $input Raw input data
     * @return array Sanitized settings
     */
    private function sanitize_display_settings(array $input): array {
        $valid_positions = ['left', 'center', 'right'];
        
        return [
            'author_url' => esc_url_raw($input['author_url'] ?? 'https://spunwebtechnology.com/spun-web-archive-pro-wordpress-wayback-archive/'),
            'footer_text' => sanitize_text_field($input['footer_text'] ?? __('Archived with Internet Archive', 'spun-web-archive-forge')),
            'footer_position' => in_array($input['footer_position'] ?? 'center', $valid_positions) 
                ? $input['footer_position'] 
                : 'center',
            'show_user_archive_link' => !empty($input['show_user_archive_link']),
            'show_plugin_author_link' => !empty($input['show_plugin_author_link']),
            'archive_username' => sanitize_text_field($input['archive_username'] ?? '')
        ];
    }
    
    /**
     * Render submissions table
     *
     * @since 2.0.0
     * @return void
     */
    private function render_submissions_table(): void {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'swap_submissions_history';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            echo '<p>' . __('No submissions found. The submissions table has not been created yet.', 'spun-web-archive-forge') . '</p>';
            return;
        }
        
        // Pagination
        $per_page = 20;
        $current_page = max(1, intval($_GET['paged'] ?? 1));
        $offset = ($current_page - 1) * $per_page;
        
        // Get total count
        $total_items = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $total_pages = ceil($total_items / $per_page);
        
        // Get submissions
        $submissions = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, s.post_url as url, p.post_title, p.post_type 
             FROM $table_name s 
             LEFT JOIN $wpdb->posts p ON s.post_id = p.ID 
             ORDER BY s.submission_date DESC 
             LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ));
        
        if (empty($submissions)) {
            echo '<p>' . __('No submissions found.', 'spun-web-archive-forge') . '</p>';
            return;
        }
        
        $this->render_submissions_table_content($submissions, $current_page, $total_pages, $total_items);
    }
    
    /**
     * Render queue items table
     *
     * @since 2.0.0
     * @return void
     */
    private function render_queue_items_table(): void {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'swap_archive_queue';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            echo '<div class="swap-queue-items">';
            echo '<h3>' . __('Queue Items', 'spun-web-archive-forge') . '</h3>';
            echo '<p>' . __('No queue items found. The queue table has not been created yet.', 'spun-web-archive-forge') . '</p>';
            echo '</div>';
            return;
        }
        
        // Pagination
        $per_page = 20;
        $current_page = max(1, intval($_GET['paged'] ?? 1));
        $offset = ($current_page - 1) * $per_page;
        
        // Get total count
        $total_items = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $total_pages = ceil($total_items / $per_page);
        
        // Get queue items
        $queue_items = $wpdb->get_results($wpdb->prepare(
            "SELECT q.*, p.post_title, p.post_type 
             FROM $table_name q 
             LEFT JOIN $wpdb->posts p ON q.post_id = p.ID 
             ORDER BY q.created_at DESC 
             LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ));
        
        echo '<div class="swap-queue-items">';
        echo '<h3>' . __('Queue Items', 'spun-web-archive-forge') . '</h3>';
        
        if (empty($queue_items)) {
            echo '<p>' . __('No items in queue.', 'spun-web-archive-forge') . '</p>';
            echo '</div>';
            return;
        }
        
        $this->render_queue_items_table_content($queue_items, $current_page, $total_pages, $total_items);
        echo '</div>';
    }
    
    /**
     * Render queue items table content
     *
     * @since 2.0.0
     * @param array $queue_items Queue items data
     * @param int   $current_page Current page number
     * @param int   $total_pages Total pages
     * @param int   $total_items Total items
     * @return void
     */
    private function render_queue_items_table_content(array $queue_items, int $current_page, int $total_pages, int $total_items): void {
        ?>
        <div class="tablenav top">
            <div class="alignleft actions">
                <span class="displaying-num">
                    <?php printf(_n('%s item', '%s items', $total_items, 'spun-web-archive-forge'), number_format_i18n($total_items)); ?>
                </span>
            </div>
            <?php if ($total_pages > 1): ?>
            <div class="tablenav-pages">
                <?php
                echo paginate_links([
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => __('&laquo;'),
                    'next_text' => __('&raquo;'),
                    'total' => $total_pages,
                    'current' => $current_page
                ]);
                ?>
            </div>
            <?php endif; ?>
        </div>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col"><?php _e('Post/Page', 'spun-web-archive-forge'); ?></th>
                    <th scope="col"><?php _e('URL', 'spun-web-archive-forge'); ?></th>
                    <th scope="col"><?php _e('Status', 'spun-web-archive-forge'); ?></th>
                    <th scope="col"><?php _e('Attempts', 'spun-web-archive-forge'); ?></th>
                    <th scope="col"><?php _e('Created', 'spun-web-archive-forge'); ?></th>
                    <th scope="col"><?php _e('Last Attempt', 'spun-web-archive-forge'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($queue_items as $item): ?>
                <tr>
                    <td>
                        <?php if ($item->post_title): ?>
                            <strong>
                                <a href="<?php echo esc_url(get_edit_post_link($item->post_id)); ?>">
                                    <?php echo esc_html($item->post_title); ?>
                                </a>
                            </strong>
                            <br>
                            <small><?php echo esc_html(ucfirst($item->post_type)); ?> (ID: <?php echo esc_html($item->post_id); ?>)</small>
                        <?php else: ?>
                            <em><?php _e('Post not found', 'spun-web-archive-forge'); ?></em>
                            <br>
                            <small>ID: <?php echo esc_html($item->post_id); ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($item->post_url): ?>
                            <a href="<?php echo esc_url($item->post_url); ?>" target="_blank" rel="noopener">
                                <?php echo esc_html($item->post_url); ?>
                            </a>
                        <?php else: ?>
                            <em><?php _e('No URL', 'spun-web-archive-forge'); ?></em>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                        $status_class = '';
                        $status_text = '';
                        switch ($item->status) {
                            case 'pending':
                                $status_class = 'status-pending';
                                $status_text = __('Pending', 'spun-web-archive-forge');
                                break;
                            case 'processing':
                                $status_class = 'status-processing';
                                $status_text = __('Processing', 'spun-web-archive-forge');
                                break;
                            case 'completed':
                                $status_class = 'status-completed';
                                $status_text = __('Completed', 'spun-web-archive-forge');
                                break;
                            case 'failed':
                                $status_class = 'status-failed';
                                $status_text = __('Failed', 'spun-web-archive-forge');
                                break;
                            default:
                                $status_class = 'status-unknown';
                                $status_text = esc_html($item->status);
                        }
                        ?>
                        <span class="status <?php echo esc_attr($status_class); ?>">
                            <?php echo esc_html($status_text); ?>
                        </span>
                    </td>
                    <td>
                        <?php echo esc_html($item->attempts ?? 0); ?>
                    </td>
                    <td>
                        <?php echo esc_html($item->created_at ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($item->created_at)) : '-'); ?>
                    </td>
                    <td>
                        <?php echo esc_html($item->last_attempt ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($item->last_attempt)) : '-'); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="tablenav bottom">
            <?php if ($total_pages > 1): ?>
            <div class="tablenav-pages">
                <?php
                echo paginate_links([
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => __('&laquo;'),
                    'next_text' => __('&raquo;'),
                    'total' => $total_pages,
                    'current' => $current_page
                ]);
                ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render submissions table content
     *
     * @since 2.0.0
     * @param array $submissions Submission data
     * @param int   $current_page Current page number
     * @param int   $total_pages Total pages
     * @param int   $total_items Total items
     * @return void
     */
    private function render_submissions_table_content(array $submissions, int $current_page, int $total_pages, int $total_items): void {
        ?>
        <div class="tablenav top">
            <div class="alignleft actions">
                <span class="displaying-num">
                    <?php printf(_n('%s item', '%s items', $total_items, 'spun-web-archive-forge'), number_format_i18n($total_items)); ?>
                </span>
            </div>
            <?php if ($total_pages > 1): ?>
            <div class="tablenav-pages">
                <?php
                echo paginate_links([
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => __('&laquo;'),
                    'next_text' => __('&raquo;'),
                    'total' => $total_pages,
                    'current' => $current_page
                ]);
                ?>
            </div>
            <?php endif; ?>
        </div>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col"><?php _e('Post/Page', 'spun-web-archive-forge'); ?></th>
                    <th scope="col"><?php _e('URL', 'spun-web-archive-forge'); ?></th>
                    <th scope="col"><?php _e('Status', 'spun-web-archive-forge'); ?></th>
                    <th scope="col"><?php _e('Archive URL', 'spun-web-archive-forge'); ?></th>
                    <th scope="col"><?php _e('Submitted', 'spun-web-archive-forge'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($submissions as $submission): ?>
                <tr>
                    <td>
                        <?php if ($submission->post_title): ?>
                            <strong>
                                <a href="<?php echo esc_url(get_edit_post_link($submission->post_id)); ?>">
                                    <?php echo esc_html($submission->post_title); ?>
                                </a>
                            </strong>
                            <br>
                            <small><?php echo esc_html(ucfirst($submission->post_type)); ?> (ID: <?php echo esc_html($submission->post_id); ?>)</small>
                        <?php else: ?>
                            <em><?php _e('Post not found', 'spun-web-archive-forge'); ?></em>
                            <br>
                            <small>ID: <?php echo esc_html($submission->post_id); ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="<?php echo esc_url($submission->url); ?>" target="_blank">
                            <?php echo esc_html(wp_trim_words($submission->url, 8, '...')); ?>
                        </a>
                    </td>
                    <td>
                        <?php $this->render_submission_status($submission->status); ?>
                    </td>
                    <td>
                        <?php if ($submission->archive_url): ?>
                            <a href="<?php echo esc_url($submission->archive_url); ?>" target="_blank">
                                <?php _e('View Archive', 'spun-web-archive-forge'); ?>
                            </a>
                        <?php else: ?>
                            <em><?php _e('Not available', 'spun-web-archive-forge'); ?></em>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($submission->submission_date))); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
    
    /**
     * Render submission status
     *
     * @since 2.0.0
     * @param string $status Submission status
     * @return void
     */
    private function render_submission_status(string $status): void {
        $status_map = [
            'success' => ['class' => 'status-success', 'text' => __('Success', 'spun-web-archive-forge')],
            'failed' => ['class' => 'status-failed', 'text' => __('Failed', 'spun-web-archive-forge')],
            'pending' => ['class' => 'status-pending', 'text' => __('Pending', 'spun-web-archive-forge')]
        ];
        
        $status_info = $status_map[$status] ?? ['class' => 'status-unknown', 'text' => esc_html($status)];
        
        printf(
            '<span class="submission-status %s">%s</span>',
            esc_attr($status_info['class']),
            esc_html($status_info['text'])
        );
    }
    
    /**
     * Render archive statistics
     *
     * @since 2.0.0
     * @return void
     */
    private function render_archive_stats(): void {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'swap_submissions_history';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            echo '<p>' . __('No submissions yet.', 'spun-web-archive-forge') . '</p>';
            return;
        }
        
        $stats = [
            'total' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name"),
            'successful' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE status = %s", 'success')),
            'failed' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE status = %s", 'failed')),
            'pending' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE status = %s", 'pending'))
        ];
        
        ?>
        <ul class="swap-stats-list">
            <li><?php printf(__('Total: %d', 'spun-web-archive-forge'), $stats['total']); ?></li>
            <li><?php printf(__('Successful: %d', 'spun-web-archive-forge'), $stats['successful']); ?></li>
            <li><?php printf(__('Failed: %d', 'spun-web-archive-forge'), $stats['failed']); ?></li>
            <li><?php printf(__('Pending: %d', 'spun-web-archive-forge'), $stats['pending']); ?></li>
        </ul>
        <?php
    }
    
    /**
     * AJAX handler to process queue manually
     *
     * @since 2.0.0
     * @return void
     */
    public function ajax_process_queue(): void {
        check_ajax_referer('swap_queue_management', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'spun-web-archive-forge'));
        }
        
        try {
            // Get the queue instance
            if (!$this->queue) {
                $this->queue = new SWAP_Archive_Queue();
            }
            
            // Process pending items
            $results = $this->queue->process_queue();
            
            wp_send_json_success([
                'message' => sprintf(__('Processed %d items from queue (%d successful, %d failed)', 'spun-web-archive-forge'), 
                    $results['processed'], $results['successful'], $results['failed']),
                'processed' => $results['processed'],
                'successful' => $results['successful'],
                'failed' => $results['failed'],
                'errors' => $results['errors']
            ]);
        } catch (Exception $e) {
            wp_send_json_error(__('Error processing queue: ', 'spun-web-archive-forge') . $e->getMessage());
        }
    }
    
    /**
     * AJAX handler to clear completed items
     *
     * @since 2.0.0
     * @return void
     */
    public function ajax_clear_completed(): void {
        check_ajax_referer('swap_queue_management', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'spun-web-archive-forge'));
        }
        
        try {
            global $wpdb;
            $table_name = $wpdb->prefix . 'swap_archive_queue';
            
            $deleted = $wpdb->delete($table_name, ['status' => 'completed']);
            
            wp_send_json_success([
                'message' => sprintf(__('Cleared %d completed items', 'spun-web-archive-forge'), $deleted),
                'deleted' => $deleted
            ]);
        } catch (Exception $e) {
            wp_send_json_error(__('Error clearing completed items: ', 'spun-web-archive-forge') . $e->getMessage());
        }
    }
    
    /**
     * AJAX handler to clear failed items
     *
     * @since 2.0.0
     * @return void
     */
    public function ajax_clear_failed(): void {
        check_ajax_referer('swap_queue_management', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'spun-web-archive-forge'));
        }
        
        try {
            global $wpdb;
            $table_name = $wpdb->prefix . 'swap_archive_queue';
            
            $deleted = $wpdb->delete($table_name, ['status' => 'failed']);
            
            wp_send_json_success([
                'message' => sprintf(__('Cleared %d failed items', 'spun-web-archive-forge'), $deleted),
                'deleted' => $deleted
            ]);
        } catch (Exception $e) {
            wp_send_json_error(__('Error clearing failed items: ', 'spun-web-archive-forge') . $e->getMessage());
        }
    }
    
    /**
     * AJAX handler to refresh queue stats
     *
     * @since 2.0.0
     * @return void
     */
    public function ajax_refresh_queue_stats(): void {
        check_ajax_referer('swap_queue_management', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'spun-web-archive-forge'));
        }
        
        try {
            $stats = $this->get_queue_statistics();
            
            // Get recent items
            global $wpdb;
            $table_name = $wpdb->prefix . 'swap_archive_queue';
            
            $recent_items = $wpdb->get_results($wpdb->prepare(
                "SELECT post_id, url, status, created_at, processed_at 
                 FROM {$table_name} 
                 ORDER BY created_at DESC 
                 LIMIT %d", 
                5
            ));
            
            wp_send_json_success([
                'stats' => $stats,
                'recent_items' => $recent_items
            ]);
        } catch (Exception $e) {
            wp_send_json_error(__('Error refreshing stats: ', 'spun-web-archive-forge') . $e->getMessage());
        }
    }
    
    /**
     * AJAX handler for queue management operations
     *
     * @since 2.0.0
     * @return void
     */
    public function ajax_manage_queue(): void {
        check_ajax_referer('swap_manage_queue', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'spun-web-archive-forge'));
        }
        
        $queue_action = sanitize_text_field($_POST['queue_action'] ?? '');
        
        if (empty($queue_action)) {
            wp_send_json_error(__('Invalid action', 'spun-web-archive-forge'));
        }
        
        try {
            global $wpdb;
            $submissions_table = $wpdb->prefix . 'swap_submissions_history';
            $queue_table = $wpdb->prefix . 'swap_archive_queue';
            $deleted = 0;
            $message = '';
            
            switch ($queue_action) {
                case 'clear_pending':
                    // Clear pending submissions from both tables
                    $deleted_submissions = $wpdb->delete($submissions_table, ['status' => 'pending']);
                    $deleted_queue = $wpdb->delete($queue_table, ['status' => 'pending']);
                    $deleted = $deleted_submissions + $deleted_queue;
                    $message = sprintf(__('Cleared %d pending submissions', 'spun-web-archive-forge'), $deleted);
                    break;
                    
                case 'clear_failed':
                    // Clear failed submissions from both tables
                    $deleted_submissions = $wpdb->delete($submissions_table, ['status' => 'failed']);
                    $deleted_queue = $wpdb->delete($queue_table, ['status' => 'failed']);
                    $deleted = $deleted_submissions + $deleted_queue;
                    $message = sprintf(__('Cleared %d failed submissions', 'spun-web-archive-forge'), $deleted);
                    break;
                    
                case 'clear_all':
                    // Clear entire queue and submissions history
            // Harden SQL identifiers by wrapping table names in backticks
            $deleted_submissions = $wpdb->query("DELETE FROM `{$submissions_table}`");
            $deleted_queue = $wpdb->query("DELETE FROM `{$queue_table}`");
                    $deleted = $deleted_submissions + $deleted_queue;
                    $message = sprintf(__('Cleared entire queue (%d items)', 'spun-web-archive-forge'), $deleted);
                    break;
                    
                default:
                    wp_send_json_error(__('Invalid queue action', 'spun-web-archive-forge'));
                    return;
            }
            
            wp_send_json_success([
                'message' => $message,
                'deleted' => $deleted,
                'action' => $queue_action
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error(__('Error managing queue: ', 'spun-web-archive-forge') . $e->getMessage());
        }
    }
    
    /**
     * AJAX handler to test API credentials
     *
     * @since 2.0.0
     * @return void
     */
    public function ajax_test_api_credentials(): void {
        check_ajax_referer('swap_test_credentials', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'spun-web-archive-forge'));
        }
        
        $access_key = sanitize_text_field($_POST['access_key'] ?? '');
        $secret_key = sanitize_text_field($_POST['secret_key'] ?? '');
        
        if (empty($access_key) || empty($secret_key)) {
            wp_send_json_error(__('Both Access Key and Secret Key are required.', 'spun-web-archive-forge'));
        }
        
        // Test API connection
        $test_url = 'https://s3.us.archive.org/';
        $auth_header = 'LOW ' . $access_key . ':' . $secret_key;
        
        $response = wp_remote_get($test_url, [
            'headers' => [
                'Authorization' => $auth_header,
                'User-Agent' => 'Spun Web Archive Forge/' . SWAP_VERSION
            ],
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            wp_send_json_error(__('Connection failed: ', 'spun-web-archive-forge') . $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code === 200 || $response_code === 403) {
            wp_send_json_success(__('API credentials are valid! Connection successful.', 'spun-web-archive-forge'));
        } elseif ($response_code === 401) {
            wp_send_json_error(__('Invalid API credentials. Please check your Access Key and Secret Key.', 'spun-web-archive-forge'));
        } else {
            wp_send_json_error(sprintf(__('Unexpected response code: %d', 'spun-web-archive-forge'), $response_code));
        }
    }
    
    /**
     * AJAX handler to submit single post
     *
     * @since 2.0.0
     * @return void
     */
    public function ajax_submit_single_post(): void {
        check_ajax_referer('swap_submit_post', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'spun-web-archive-forge'));
        }
        
        $post_id = intval($_POST['post_id'] ?? 0);
        
        if (!$post_id) {
            wp_send_json_error(__('Invalid post ID', 'spun-web-archive-forge'));
        }
        
        $post = get_post($post_id);
        if (!$post) {
            wp_send_json_error(__('Post not found', 'spun-web-archive-forge'));
        }
        
        try {
            // Initialize archive API if not already done
            if (!$this->archive_api) {
                $this->archive_api = new SWAP_Archive_API();
            }
            
            $result = $this->archive_api->submit_to_archive($post_id);
            
            if ($result['success']) {
                wp_send_json_success([
                    'message' => __('Post submitted to archive successfully!', 'spun-web-archive-forge'),
                    'archive_url' => $result['archive_url'] ?? ''
                ]);
            } else {
                wp_send_json_error($result['message'] ?? __('Submission failed', 'spun-web-archive-forge'));
            }
        } catch (Exception $e) {
            wp_send_json_error(__('Error submitting post: ', 'spun-web-archive-forge') . $e->getMessage());
        }
    }
    
    /**
     * AJAX handler to get submission status
     *
     * @since 2.0.0
     * @return void
     */
    public function ajax_get_submission_status(): void {
        check_ajax_referer('swap_submission_status', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'spun-web-archive-forge'));
        }
        
        $post_id = intval($_POST['post_id'] ?? 0);
        
        if (!$post_id) {
            wp_send_json_error(__('Invalid post ID', 'spun-web-archive-forge'));
        }
        
        try {
            global $wpdb;
            $table_name = $wpdb->prefix . 'swap_submissions_history';
            
            $submission = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE post_id = %d ORDER BY submission_date DESC LIMIT 1",
                $post_id
            ));
            
            if ($submission) {
                wp_send_json_success([
                    'status' => $submission->status,
                    'archive_url' => $submission->archive_url,
                    'submission_date' => $submission->submission_date,
                    'error_message' => $submission->error_message
                ]);
            } else {
                wp_send_json_success([
                    'status' => 'not_submitted',
                    'message' => __('No submission found for this post', 'spun-web-archive-forge')
                ]);
            }
        } catch (Exception $e) {
            wp_send_json_error(__('Error retrieving submission status: ', 'spun-web-archive-forge') . $e->getMessage());
        }
    }
    
    /**
     * AJAX handler to validate archives manually
     *
     * @since 2.0.0
     * @return void
     */
    public function ajax_validate_now(): void {
        check_ajax_referer('swap_validate_now', '_ajax_nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['error' => 'forbidden'], 403);
        }
        
        try {
            if (class_exists('SWP_Archiver')) {
                $archiver = new SWP_Archiver();
                $result = $archiver->sweep_stuck_processing( 0, 100 ); // force sweep now (no age gate)
                wp_send_json_success( $result );
            } else {
                wp_send_json_error(['error' => 'SWP_Archiver class not found']);
            }
        } catch (Exception $e) {
            wp_send_json_error(['error' => 'Validation failed: ' . $e->getMessage()]);
        }
    }
}
