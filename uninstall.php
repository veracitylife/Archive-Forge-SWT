<?php
/**
 * Uninstall script for Spun Web Archive Forge
 * 
 * This file is executed when the plugin is deleted from WordPress admin.
 * It removes all plugin data including database tables, options, and metadata.
 *
 * @package SpunWebArchiveElite
 * @since 0.2.4
 */

// If uninstall not called from WordPress, then exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Security check - ensure this is a legitimate uninstall
if (!current_user_can('activate_plugins')) {
    return;
}

// Check if the plugin file exists to prevent unauthorized access
$plugin_file = WP_PLUGIN_DIR . '/spun-web-archive-forge/spun-web-archive-forge.php';
if (!file_exists($plugin_file)) {
    return;
}

/**
 * Remove plugin data based on user preference
 * 
 * Note: This uninstall script runs when the plugin is deleted through WordPress admin.
 * For more control over data retention, use the plugin's custom uninstall page
 * from the admin menu before deleting the plugin.
 */
function swap_uninstall_cleanup() {
    // Check if user has set a preference to keep data
    // This option can be set by the custom uninstall page
    $keep_data = get_option('swap_keep_data_on_uninstall', false);
    
    if ($keep_data) {
        // Only clean up transients and temporary data
        swap_cleanup_transients_only();
        return;
    }
    
    // Default behavior: remove all data
    global $wpdb;
    
    // Remove database tables with proper escaping
    $submissions_table = $wpdb->prefix . 'swap_submissions_history';
    $queue_table = $wpdb->prefix . 'swap_archive_queue';
    $wpdb->query("DROP TABLE IF EXISTS `{$submissions_table}`");
    $wpdb->query("DROP TABLE IF EXISTS `{$queue_table}`");
    
    // Remove all plugin options
    delete_option('swap_api_settings');
    delete_option('swap_auto_settings');
    delete_option('swap_queue_settings');
    delete_option('swap_display_settings');
    delete_option('swap_api_credentials');
    delete_option('swap_callback_token');
    delete_option('swap_api_connection_status');
    delete_option('swap_api_last_test');
    delete_option('swap_plugin_version');
    
    // Remove all post meta data created by the plugin
    delete_post_meta_by_key('_swap_archive_status');
    delete_post_meta_by_key('_swap_archive_url');
    delete_post_meta_by_key('_swap_last_submitted');
    delete_post_meta_by_key('_swap_submission_count');
    delete_post_meta_by_key('_swap_auto_submit');
    delete_post_meta_by_key('_swap_archive_date');
    delete_post_meta_by_key('_swap_archive_error');
    delete_post_meta_by_key('_swap_exclude_from_archive');
    
    // Clear any scheduled cron jobs
    wp_clear_scheduled_hook('swap_process_queue');
    wp_clear_scheduled_hook('swap_retry_failed');
    
    // Remove any transients using prepared statements for security
    $wpdb->query($wpdb->prepare("DELETE FROM $wpdb->options WHERE option_name LIKE %s", '_transient_swap_%'));
    $wpdb->query($wpdb->prepare("DELETE FROM $wpdb->options WHERE option_name LIKE %s", '_transient_timeout_swap_%'));
    
    // Remove user meta data (if any) using prepared statement
    $wpdb->query($wpdb->prepare("DELETE FROM $wpdb->usermeta WHERE meta_key LIKE %s", 'swap_%'));
    
    // Clear any cached data
    wp_cache_flush();
    
    // Log the uninstall (if WP_DEBUG is enabled)
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Spun Web Archive Forge: Plugin data successfully removed during uninstall');
    }
}

/**
 * Clean up only transients and temporary data
 * Used when user chooses to keep their data
 */
function swap_cleanup_transients_only() {
    global $wpdb;
    
    // Remove any transients using prepared statements for security
    $wpdb->query($wpdb->prepare("DELETE FROM $wpdb->options WHERE option_name LIKE %s", '_transient_swap_%'));
    $wpdb->query($wpdb->prepare("DELETE FROM $wpdb->options WHERE option_name LIKE %s", '_transient_timeout_swap_%'));
    
    // Clear any cached data
    wp_cache_flush();
    
    // Log the cleanup (if WP_DEBUG is enabled)
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Spun Web Archive Forge: Transients cleaned up during uninstall (data preserved)');
    }
}

// Execute cleanup
swap_uninstall_cleanup();

// Clean up the preference option after use
delete_option('swap_keep_data_on_uninstall');

// Final security check - ensure we're still in WordPress context
if (!function_exists('add_action')) {
    exit;
}
