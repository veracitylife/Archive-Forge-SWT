<?php
/**
 * Uninstall Page Handler
 * 
 * Handles the plugin's uninstall interface with user confirmation.
 * 
 * @package SpunWebArchiveElite
 * @subpackage Includes
 * @author Ryan Dickie Thompson
 * @copyright 2024 Spun Web Technology
 * @license GPL-2.0-or-later
 * @since 0.3.9
 * @version 0.6.1
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SWAP_Uninstall_Page {
    
    /**
     * WordPress database instance
     */
    private $wpdb;
    
    /**
     * Initialize the uninstall page
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_swap_uninstall_plugin', array($this, 'handle_uninstall_ajax'));
    }
    
    /**
     * Add admin menu item
     */
    public function add_admin_menu() {
        add_submenu_page(
            'options-general.php',
            __('Uninstall Spun Web Archive Forge', 'spun-web-archive-forge'),
            __('Uninstall Archive Forge', 'spun-web-archive-forge'),
            'manage_options',
            'swap-uninstall',
            array($this, 'render_page')
        );
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts($hook) {
        if ($hook !== 'settings_page_swap-uninstall') {
            return;
        }
        
        wp_enqueue_script(
            'swap-uninstall',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/uninstall.js',
            array('jquery'),
            SWAP_VERSION,
            true
        );
        
        wp_localize_script('swap-uninstall', 'swapUninstall', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('swap_uninstall_nonce'),
            'confirmText' => __('Are you absolutely sure you want to completely remove Spun Web Archive Forge and ALL its data?', 'spun-web-archive-forge'),
            'warningText' => __('This action cannot be undone!', 'spun-web-archive-forge'),
            'processingText' => __('Removing plugin data...', 'spun-web-archive-forge'),
            'successText' => __('Plugin data has been successfully removed. You can now safely delete the plugin.', 'spun-web-archive-forge'),
            'errorText' => __('An error occurred during uninstall. Please try again.', 'spun-web-archive-forge')
        ));
        
        wp_localize_script('swap-uninstall', 'swapUninstallL10n', array(
            'uninstallPlugin' => __('Uninstall Plugin', 'spun-web-archive-forge'),
            'removeAllData' => __('Remove All Plugin Data', 'spun-web-archive-forge'),
            'confirmKeepData' => __('Are you sure you want to uninstall the plugin? Your data will be preserved and can be restored if you reinstall the plugin later.', 'spun-web-archive-forge'),
            'confirmRemoveData' => __('⚠️ FINAL WARNING ⚠️\n\nYou are about to PERMANENTLY DELETE all Spun Web Archive Forge data including:\n• All settings and configuration\n• Complete submission history\n• API credentials\n• All post metadata\n\nThis action CANNOT be undone!', 'spun-web-archive-forge'),
            'warningRemoveData' => __('Are you absolutely certain you want to proceed with complete data removal?', 'spun-web-archive-forge'),
            'finalWarningRemoveData' => __('🚨 LAST CHANCE 🚨\n\nThis is your final opportunity to cancel.\n\nClick OK to PERMANENTLY DELETE all plugin data.\nClick Cancel to abort this operation.\n\nThere is NO way to recover this data once deleted!', 'spun-web-archive-forge')
        ));
        
        wp_enqueue_style(
            'swap-uninstall',
            plugin_dir_url(dirname(__FILE__)) . 'assets/css/admin.css',
            array(),
            SWAP_VERSION
        );
    }
    
    /**
     * Render the uninstall page
     */
    public function render_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="swap-uninstall-container">
                <div class="notice notice-warning">
                    <p><strong><?php _e('Warning: This will permanently remove all plugin data!', 'spun-web-archive-forge'); ?></strong></p>
                </div>
                
                <div class="card">
                    <h2><?php _e('Complete Plugin Removal', 'spun-web-archive-forge'); ?></h2>
                    <p><?php _e('This tool allows you to completely remove Spun Web Archive Forge and all its associated data from your WordPress installation.', 'spun-web-archive-forge'); ?></p>
                    
                    <h3><?php _e('What will be removed:', 'spun-web-archive-forge'); ?></h3>
                    <ul class="swap-removal-list">
                        <li><?php _e('All plugin settings and configuration', 'spun-web-archive-forge'); ?></li>
                        <li><?php _e('Submission history database table', 'spun-web-archive-forge'); ?></li>
                        <li><?php _e('All post metadata created by the plugin', 'spun-web-archive-forge'); ?></li>
                        <li><?php _e('API credentials and connection settings', 'spun-web-archive-forge'); ?></li>
                        <li><?php _e('Scheduled tasks and cron jobs', 'spun-web-archive-forge'); ?></li>
                        <li><?php _e('All cached data and transients', 'spun-web-archive-forge'); ?></li>
                        <li><?php _e('User preferences and widget settings', 'spun-web-archive-forge'); ?></li>
                    </ul>
                    
                    <div class="swap-uninstall-form">
                        <h3><?php _e('Uninstall Options', 'spun-web-archive-forge'); ?></h3>
                        <p class="description">
                            <?php _e('Choose how you want to uninstall the plugin. You can keep your data for future use or remove everything completely.', 'spun-web-archive-forge'); ?>
                        </p>
                        
                        <div class="swap-uninstall-options">
                            <h4><?php _e('Data Handling Options', 'spun-web-archive-forge'); ?></h4>
                            
                            <label class="swap-option-label swap-keep-data">
                                <input type="radio" name="swap_uninstall_option" value="keep_data" id="swap-keep-data" checked />
                                <div class="swap-option-content">
                                    <strong><?php _e('Keep Data (Recommended)', 'spun-web-archive-forge'); ?></strong>
                                    <p><?php _e('Remove plugin files only. Preserve all settings, submission history, and configuration for future reinstallation.', 'spun-web-archive-forge'); ?></p>
                                    <ul class="swap-preserve-list">
                                        <li><?php _e('✅ Settings and configuration preserved', 'spun-web-archive-forge'); ?></li>
                                        <li><?php _e('✅ Submission history maintained', 'spun-web-archive-forge'); ?></li>
                                        <li><?php _e('✅ API credentials saved', 'spun-web-archive-forge'); ?></li>
                                        <li><?php _e('✅ Post metadata retained', 'spun-web-archive-forge'); ?></li>
                                    </ul>
                                </div>
                            </label>
                            
                            <label class="swap-option-label swap-remove-data">
                                <input type="radio" name="swap_uninstall_option" value="remove_data" id="swap-remove-data" />
                                <div class="swap-option-content">
                                    <strong><?php _e('Remove All Data', 'spun-web-archive-forge'); ?></strong>
                                    <p><?php _e('Completely remove plugin files and all associated data. This action cannot be undone.', 'spun-web-archive-forge'); ?></p>
                                    <ul class="swap-removal-list">
                                        <li><?php _e('❌ All settings deleted', 'spun-web-archive-forge'); ?></li>
                                        <li><?php _e('❌ Submission history removed', 'spun-web-archive-forge'); ?></li>
                                        <li><?php _e('❌ API credentials deleted', 'spun-web-archive-forge'); ?></li>
                                        <li><?php _e('❌ All post metadata removed', 'spun-web-archive-forge'); ?></li>
                                    </ul>
                                </div>
                            </label>
                        </div>
                        
                        <div id="swap-warning-box" class="swap-warning-box" style="display: none;">
                            <p><strong><?php _e('⚠️ DANGER ZONE ⚠️', 'spun-web-archive-forge'); ?></strong></p>
                            <p><?php _e('You have selected to remove ALL plugin data. This will permanently delete all database entries, settings, and files for the Spun Web Archive Forge plugin. This action cannot be undone!', 'spun-web-archive-forge'); ?></p>
                        </div>
                        
                        <div id="swap-confirmation-section" style="display: none;">
                            <label class="swap-confirmation-label">
                                <input type="checkbox" id="swap-confirm-removal" />
                                <strong><?php _e('Yes, I understand this will permanently delete all plugin data and this action cannot be undone.', 'spun-web-archive-forge'); ?></strong>
                            </label>
                        </div>
                        
                        <div class="swap-uninstall-actions">
                            <button type="button" id="swap-uninstall-button" class="button button-primary button-large">
                                <?php _e('Uninstall Plugin', 'spun-web-archive-forge'); ?>
                            </button>
                            <a href="<?php echo esc_url(admin_url('plugins.php')); ?>" class="button button-secondary button-large">
                                <?php _e('Cancel', 'spun-web-archive-forge'); ?>
                            </a>
                        </div>
                        
                        <div id="swap-uninstall-progress" class="swap-progress-container" style="display: none;">
                            <div class="swap-progress-bar">
                                <div class="swap-progress-fill"></div>
                            </div>
                            <p class="swap-progress-text"><?php _e('Removing plugin data...', 'spun-web-archive-forge'); ?></p>
                        </div>
                        
                        <div id="swap-uninstall-result" class="swap-result-container" style="display: none;">
                            <!-- Result message will be inserted here -->
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <h3><?php _e('Alternative: Standard Plugin Deletion', 'spun-web-archive-forge'); ?></h3>
                    <p><?php _e('If you only want to deactivate the plugin temporarily or remove it without deleting all data, you can use the standard WordPress plugin deletion process:', 'spun-web-archive-forge'); ?></p>
                    <ol>
                        <li><?php _e('Go to Plugins → Installed Plugins', 'spun-web-archive-forge'); ?></li>
                        <li><?php _e('Deactivate "Spun Web Archive Forge"', 'spun-web-archive-forge'); ?></li>
                        <li><?php _e('Click "Delete" to remove plugin files (data will be preserved)', 'spun-web-archive-forge'); ?></li>
                    </ol>
                    <p class="description">
                        <?php _e('Note: Standard deletion preserves your settings and data for future reinstallation.', 'spun-web-archive-forge'); ?>
                    </p>
                </div>
            </div>
        </div>
        
        <style>
        .swap-uninstall-container {
            max-width: 800px;
        }
        
        .swap-removal-list {
            background: #f9f9f9;
            padding: 15px 20px;
            border-left: 4px solid #dc3232;
            margin: 15px 0;
        }
        
        .swap-removal-list li {
            margin: 5px 0;
        }
        
        .swap-warning-box {
            background: #fef7f1;
            border: 2px solid #dc3232;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            text-align: center;
        }
        
        .swap-warning-box p {
            margin: 5px 0;
            color: #dc3232;
        }
        
        .swap-confirmation-label {
            display: block;
            margin: 20px 0;
            padding: 15px;
            background: #fff;
            border: 2px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .swap-confirmation-label:hover {
            border-color: #0073aa;
        }
        
        .swap-confirmation-label input[type="checkbox"] {
            margin-right: 10px;
            transform: scale(1.2);
        }
        
        .swap-uninstall-actions {
            margin: 30px 0;
            text-align: center;
        }
        
        .swap-uninstall-actions .button {
            margin: 0 10px;
        }
        
        .swap-progress-container {
            margin: 20px 0;
            text-align: center;
        }
        
        .swap-progress-bar {
            width: 100%;
            height: 20px;
            background: #f0f0f0;
            border-radius: 10px;
            overflow: hidden;
            margin: 10px 0;
        }
        
        .swap-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #0073aa, #005a87);
            width: 0%;
            transition: width 0.3s ease;
            animation: progress-animation 2s infinite;
        }
        
        @keyframes progress-animation {
            0% { width: 0%; }
            50% { width: 70%; }
            100% { width: 100%; }
        }
        
        .swap-progress-text {
            font-weight: bold;
            color: #0073aa;
        }
        
        .swap-result-container {
            margin: 20px 0;
            padding: 15px;
            border-radius: 5px;
        }
        
        .swap-result-container.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .swap-result-container.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        </style>
        <?php
    }
    
    /**
     * Handle AJAX uninstall request
     */
    public function handle_uninstall_ajax() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'swap_uninstall_nonce')) {
            wp_die(__('Security check failed.', 'spun-web-archive-forge'));
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'spun-web-archive-forge'));
        }
        
        // Get the uninstall option
        $uninstall_option = sanitize_text_field($_POST['uninstall_option'] ?? 'keep_data');
        
        // Set preference for main uninstall.php if user chooses to keep data
        if ($uninstall_option === 'keep_data') {
            update_option('swap_keep_data_on_uninstall', true);
        } else {
            delete_option('swap_keep_data_on_uninstall');
        }
        
        // Perform the uninstall based on user choice
        $result = $this->perform_uninstall($uninstall_option);
        
        if ($result['success']) {
            wp_send_json_success($result['message']);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * Perform the actual uninstall process
     */
    private function perform_uninstall($uninstall_option = 'keep_data') {
        try {
            if ($uninstall_option === 'remove_data') {
                // Only remove data if explicitly requested
                
                // Remove database tables
                $submissions_table = $this->wpdb->prefix . 'swap_submissions_history';
                $queue_table = $this->wpdb->prefix . 'swap_archive_queue';
                $this->wpdb->query("DROP TABLE IF EXISTS `{$submissions_table}`");
                $this->wpdb->query("DROP TABLE IF EXISTS `{$queue_table}`");
            
            // Remove all plugin options
            $options_to_remove = array(
                'swap_api_settings',
                'swap_auto_settings',
                'swap_queue_settings',
                'swap_display_settings',
                'swap_api_credentials',
                'swap_callback_token',
                'swap_api_connection_status',
                'swap_api_last_test',
                'swap_plugin_version'
            );
            
            foreach ($options_to_remove as $option) {
                delete_option($option);
            }
            
            // Remove all post meta data created by the plugin
            $meta_keys_to_remove = array(
                '_swap_archive_status',
                '_swap_archive_url',
                '_swap_last_submitted',
                '_swap_submission_count',
                '_swap_auto_submit',
                '_swap_archive_date',
                '_swap_archive_error',
                '_swap_exclude_from_archive'
            );
            
                foreach ($meta_keys_to_remove as $meta_key) {
                    delete_post_meta_by_key($meta_key);
                }
                
                // Clear any scheduled cron jobs
                wp_clear_scheduled_hook('swap_process_queue');
                wp_clear_scheduled_hook('swap_retry_failed');
                
                // Remove any transients
                $this->wpdb->query($this->wpdb->prepare("DELETE FROM {$this->wpdb->options} WHERE option_name LIKE %s", '_transient_swap_%'));
                $this->wpdb->query($this->wpdb->prepare("DELETE FROM {$this->wpdb->options} WHERE option_name LIKE %s", '_transient_timeout_swap_%'));
                
                // Remove user meta data
                $this->wpdb->query($this->wpdb->prepare("DELETE FROM {$this->wpdb->usermeta} WHERE meta_key LIKE %s", 'swap_%'));
                
                // Clear any cached data
                wp_cache_flush();
                
                return array(
                    'success' => true,
                    'message' => __('All plugin data has been successfully removed. You can now safely delete the plugin from the Plugins page.', 'spun-web-archive-forge')
                );
                
            } else {
                // Keep data option - only clean up transients for better performance
                $this->wpdb->query($this->wpdb->prepare("DELETE FROM {$this->wpdb->options} WHERE option_name LIKE %s", '_transient_swap_%'));
                $this->wpdb->query($this->wpdb->prepare("DELETE FROM {$this->wpdb->options} WHERE option_name LIKE %s", '_transient_timeout_swap_%'));
                
                return array(
                    'success' => true,
                    'message' => __('Plugin files will be removed but your data has been preserved. You can reinstall the plugin later to restore your settings and history.', 'spun-web-archive-forge')
                );
            }
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => sprintf(__('Error during uninstall: %s', 'spun-web-archive-forge'), $e->getMessage())
            );
        }
    }
}

// Initialize the uninstall page
new SWAP_Uninstall_Page();
