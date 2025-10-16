<?php
/**
 * Admin Bar Integration
 * 
 * Adds Archive Forge functionality to WordPress admin bar
 * Based on improvements from MickeyKay/archiver project
 * 
 * @package SpunWebArchiveForge
 * @subpackage Includes
 * @author Ryan Dickie Thompson
 * @copyright 2024 Spun Web Technology
 * @license GPL-2.0-or-later
 * @since 1.0.15
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin Bar Integration Class
 * 
 * @since 1.0.15
 */
class SWAP_Admin_Bar {
    
    /**
     * Archive API instance
     * 
     * @since 1.0.15
     * @var SWAP_Archive_API|null
     */
    private $archive_api;
    
    /**
     * Constructor
     * 
     * @since 1.0.15
     * @param SWAP_Archive_API $archive_api Archive API instance
     */
    public function __construct($archive_api = null) {
        $this->archive_api = $archive_api;
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     * 
     * @since 1.0.15
     * @return void
     */
    private function init_hooks() {
        add_action('admin_bar_menu', array($this, 'add_admin_bar_items'), 100);
        add_action('wp_ajax_swap_trigger_archive', array($this, 'ajax_trigger_archive'));
        add_action('wp_ajax_swap_get_archive_status', array($this, 'ajax_get_archive_status'));
    }
    
    /**
     * Add items to admin bar
     * 
     * @since 1.0.15
     * @param WP_Admin_Bar $wp_admin_bar Admin bar instance
     * @return void
     */
    public function add_admin_bar_items($wp_admin_bar) {
        // Only show for users who can manage options
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Get current page URL
        $current_url = $this->get_current_page_url();
        if (!$current_url) {
            return;
        }
        
        // Add main Archive Forge menu
        $wp_admin_bar->add_node(array(
            'id'    => 'swap-archive-forge',
            'title' => '<span class="ab-icon dashicons-archive"></span> Archive Forge',
            'href'  => admin_url('admin.php?page=spun-web-archive-forge'),
            'meta'  => array(
                'title' => __('Archive Forge - Wayback Machine Integration', 'spun-web-archive-forge')
            )
        ));
        
        // Add current page archive status
        $archive_status = $this->get_archive_status($current_url);
        $status_icon = $archive_status['is_archived'] ? '✅' : '⏳';
        $status_text = $archive_status['is_archived'] ? 
            __('Archived', 'spun-web-archive-forge') : 
            __('Not Archived', 'spun-web-archive-forge');
        
        $wp_admin_bar->add_node(array(
            'id'     => 'swap-current-status',
            'parent' => 'swap-archive-forge',
            'title'  => $status_icon . ' ' . $status_text,
            'meta'   => array(
                'title' => sprintf(__('Current page: %s', 'spun-web-archive-forge'), $current_url)
            )
        ));
        
        // Add trigger archive button
        $wp_admin_bar->add_node(array(
            'id'     => 'swap-trigger-archive',
            'parent' => 'swap-archive-forge',
            'title'  => '🚀 ' . __('Archive This Page', 'spun-web-archive-forge'),
            'href'   => '#',
            'meta'   => array(
                'onclick' => 'swapTriggerArchive("' . esc_js($current_url) . '"); return false;',
                'title'   => __('Submit current page to Wayback Machine', 'spun-web-archive-forge')
            )
        ));
        
        // Add view archives link
        if ($archive_status['is_archived']) {
            $wp_admin_bar->add_node(array(
                'id'     => 'swap-view-archives',
                'parent' => 'swap-archive-forge',
                'title'  => '👁️ ' . __('View Archives', 'spun-web-archive-forge'),
                'href'   => $archive_status['archive_url'],
                'meta'   => array(
                    'target' => '_blank',
                    'title'  => __('View all archives for this page', 'spun-web-archive-forge')
                )
            ));
        }
        
        // Add settings link
        $wp_admin_bar->add_node(array(
            'id'     => 'swap-settings',
            'parent' => 'swap-archive-forge',
            'title'  => '⚙️ ' . __('Settings', 'spun-web-archive-forge'),
            'href'   => admin_url('admin.php?page=spun-web-archive-forge'),
            'meta'   => array(
                'title' => __('Archive Forge Settings', 'spun-web-archive-forge')
            )
        ));
        
        // Add JavaScript for AJAX functionality
        $this->enqueue_admin_bar_scripts();
    }
    
    /**
     * Get current page URL
     * 
     * @since 1.0.15
     * @return string|false Current page URL or false if not available
     */
    private function get_current_page_url() {
        if (is_admin()) {
            return false;
        }
        
        global $wp;
        return home_url($wp->request);
    }
    
    /**
     * Get archive status for URL
     * 
     * @since 1.0.15
     * @param string $url URL to check
     * @return array Archive status data
     */
    private function get_archive_status($url) {
        if (!$this->archive_api) {
            return array(
                'is_archived' => false,
                'archive_url' => '',
                'last_archived' => null
            );
        }
        
        // Check if URL is archived
        $status = $this->archive_api->check_availability($url);
        
        return array(
            'is_archived' => $status['success'] && $status['is_archived'],
            'archive_url' => $status['archive_url'] ?? '',
            'last_archived' => $status['last_archived'] ?? null
        );
    }
    
    /**
     * Enqueue admin bar scripts
     * 
     * @since 1.0.15
     * @return void
     */
    private function enqueue_admin_bar_scripts() {
        wp_add_inline_script('jquery', '
            function swapTriggerArchive(url) {
                var button = jQuery("#wp-admin-bar-swap-trigger-archive a");
                var originalText = button.text();
                
                button.text("⏳ Archiving...").prop("disabled", true);
                
                jQuery.post(ajaxurl, {
                    action: "swap_trigger_archive",
                    url: url,
                    nonce: "' . wp_create_nonce('swap_trigger_archive') . '"
                }, function(response) {
                    if (response.success) {
                        button.text("✅ Archived!");
                        setTimeout(function() {
                            button.text(originalText).prop("disabled", false);
                        }, 3000);
                    } else {
                        button.text("❌ Failed");
                        setTimeout(function() {
                            button.text(originalText).prop("disabled", false);
                        }, 3000);
                    }
                });
            }
        ');
    }
    
    /**
     * AJAX handler for triggering archive
     * 
     * @since 1.0.15
     * @return void
     */
    public function ajax_trigger_archive() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'swap_trigger_archive')) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $url = sanitize_url($_POST['url'] ?? '');
        if (empty($url)) {
            wp_send_json_error('Invalid URL');
        }
        
        if (!$this->archive_api) {
            wp_send_json_error('Archive API not available');
        }
        
        // Submit URL for archiving
        $result = $this->archive_api->submit_url($url);
        
        if ($result['success']) {
            wp_send_json_success(array(
                'message' => 'URL submitted for archiving',
                'archive_url' => $result['archive_url'] ?? ''
            ));
        } else {
            wp_send_json_error($result['error'] ?? 'Unknown error');
        }
    }
    
    /**
     * AJAX handler for getting archive status
     * 
     * @since 1.0.15
     * @return void
     */
    public function ajax_get_archive_status() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'swap_get_archive_status')) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $url = sanitize_url($_POST['url'] ?? '');
        if (empty($url)) {
            wp_send_json_error('Invalid URL');
        }
        
        if (!$this->archive_api) {
            wp_send_json_error('Archive API not available');
        }
        
        // Check archive status
        $status = $this->archive_api->check_availability($url);
        wp_send_json_success($status);
    }
}
