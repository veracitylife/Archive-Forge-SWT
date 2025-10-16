<?php
/**
 * Post Actions Handler
 * 
 * Handles individual post submission actions on WordPress admin posts/pages lists.
 * 
 * @package SpunWebArchiveElite
 * @subpackage Includes
 * @author Ryan Dickie Thompson
 * @copyright 2024 Spun Web Technology
 * @license GPL-2.0-or-later
 * @since 0.3.2
 * @version 0.6.1
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SWAP_Post_Actions {
    
    /**
     * Auto submitter instance
     */
    private $auto_submitter;
    
    /**
     * Archive queue instance
     */
    private $archive_queue;
    
    /**
     * Submissions history instance
     */
    private $submissions_history;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Check memory usage before initialization
        if (function_exists('swap_check_memory_usage') && !swap_check_memory_usage('SWAP_Post_Actions::__construct')) {
            error_log('SWAP: Post Actions initialization aborted due to high memory usage');
            return;
        }
        
        // Initialize only non-circular dependencies
        $this->archive_queue = new SWAP_Archive_Queue();
        $this->submissions_history = new SWAP_Submissions_History();
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Add individual submission links to post row actions
        add_filter('post_row_actions', array($this, 'add_post_action'), 10, 2);
        add_filter('page_row_actions', array($this, 'add_post_action'), 10, 2);
        
        // Handle individual submission requests
        add_action('admin_action_swap_submit_post', array($this, 'handle_post_submission'));
        
        // Add admin notices for submission results
        add_action('admin_notices', array($this, 'show_submission_notices'));
        
        // Add AJAX handler for submission status
        add_action('wp_ajax_swap_post_submission', array($this, 'ajax_post_submission'));
        
        // Enqueue scripts for post actions
        add_action('admin_enqueue_scripts', array($this, 'enqueue_post_scripts'));
    }
    
    /**
     * Get auto submitter instance (lazy loading to avoid circular dependency)
     * 
     * @return SWAP_Auto_Submitter
     */
    private function get_auto_submitter() {
        if (!$this->auto_submitter) {
            // Pass dependencies to avoid circular dependency
            $this->auto_submitter = new SWAP_Auto_Submitter(null, $this->archive_queue, $this->submissions_history);
        }
        return $this->auto_submitter;
    }
    
    /**
     * Add individual submission action to post row actions
     */
    public function add_post_action($actions, $post) {
        // Only add for published posts
        if ($post->post_status !== 'publish') {
            return $actions;
        }
        
        // Check user permissions
        if (!current_user_can('manage_options')) {
            return $actions;
        }
        
        // Check archive status
        if ($this->archive_queue->is_archived($post->ID)) {
            $actions['swap_submit'] = '<span style="color: #46b450; font-weight: 500;">✓ ' . __('Archived', 'spun-web-archive-forge') . '</span>';
        } elseif ($this->archive_queue->is_in_queue($post->ID)) {
            $actions['swap_submit'] = '<span style="color: #ffb900;">' . __('In Queue', 'spun-web-archive-forge') . '</span>';
        } else {
            // Create submission URL with nonce
            $submit_url = wp_nonce_url(
                admin_url('admin.php?action=swap_submit_post&post=' . $post->ID),
                'swap_submit_post_' . $post->ID
            );
            
            $actions['swap_submit'] = '<a href="' . esc_url($submit_url) . '" class="swap-submit-link">' . 
                                     __('Submit to Archive Queue', 'spun-web-archive-forge') . '</a>';
        }
        
        return $actions;
    }
    
    /**
     * Handle individual post submission
     */
    public function handle_post_submission() {
        // Verify nonce
        $post_id = intval($_GET['post']);
        if (!wp_verify_nonce($_GET['_wpnonce'], 'swap_submit_post_' . $post_id)) {
            wp_die(__('Security check failed.', 'spun-web-archive-forge'));
        }
        
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action.', 'spun-web-archive-forge'));
        }
        
        // Validate post
        $post = get_post($post_id);
        if (!$post || $post->post_status !== 'publish') {
            wp_die(__('Invalid post or post is not published.', 'spun-web-archive-forge'));
        }
        
        // Add to archive queue instead of direct submission
        $result = $this->archive_queue->add_to_queue($post_id);
        
        // Determine redirect URL
        $redirect_url = admin_url('edit.php');
        if ($post->post_type === 'page') {
            $redirect_url = admin_url('edit.php?post_type=page');
        }
        
        // Add result parameters to redirect URL
        if ($result) {
            $redirect_url = add_query_arg(array(
                'swap_queued' => 1,
                'post_id' => $post_id
            ), $redirect_url);
        } else {
            $redirect_url = add_query_arg(array(
                'swap_queue_failed' => 1,
                'post_id' => $post_id
            ), $redirect_url);
        }
        
        // Redirect back to posts list
        wp_redirect($redirect_url);
        exit;
    }
    
    /**
     * Show admin notices for submission results
     */
    public function show_submission_notices() {
        if (!empty($_GET['swap_queued'])) {
            $post_id = intval($_GET['post_id']);
            $post_title = get_the_title($post_id);
            
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p>' . sprintf(
                __('"%s" has been added to the archive queue. It will be processed automatically within the next hour.', 'spun-web-archive-forge'),
                esc_html($post_title)
            ) . '</p>';
            echo '</div>';
        }
        
        if (!empty($_GET['swap_queue_failed'])) {
            $post_id = intval($_GET['post_id']);
            $post_title = get_the_title($post_id);
            
            echo '<div class="notice notice-error is-dismissible">';
            echo '<p>' . sprintf(
                __('Failed to add "%s" to the archive queue. The post may already be queued or archived.', 'spun-web-archive-forge'),
                esc_html($post_title)
            ) . '</p>';
            echo '</div>';
        }
    }
    
    /**
     * Check if post was recently submitted
     */
    private function is_recently_submitted($post_id) {
        return $this->submissions_history->is_recently_submitted($post_id);
    }
    
    /**
     * AJAX handler for post submission
     */
    public function ajax_post_submission() {
        check_ajax_referer('swap_post_submission', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'spun-web-archive-forge'));
        }
        
        $post_id = intval($_POST['post_id']);
        $post = get_post($post_id);
        
        if (!$post || $post->post_status !== 'publish') {
            wp_send_json_error(__('Invalid post or post is not published.', 'spun-web-archive-forge'));
        }
        
        // Add to archive queue
        $result = $this->archive_queue->add_to_queue($post_id);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => sprintf(
                    __('"%s" has been added to the archive queue and will be processed within the next hour.', 'spun-web-archive-forge'),
                    get_the_title($post_id)
                )
            ));
        } else {
            wp_send_json_error(sprintf(
                __('Failed to add "%s" to the archive queue. It may already be queued or archived.', 'spun-web-archive-forge'),
                get_the_title($post_id)
            ));
        }
    }
    
    /**
     * Enqueue scripts for post actions
     */
    public function enqueue_post_scripts($hook) {
        if ($hook !== 'edit.php') {
            return;
        }
        
        wp_enqueue_script(
            'swap-post-actions',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/post-actions.js',
            array('jquery'),
            SWAP_VERSION,
            true
        );
        
        wp_localize_script('swap-post-actions', 'swapPostActions', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('swap_post_submission'),
            'strings' => array(
                'submitting' => __('Submitting...', 'spun-web-archive-forge'),
                'submitted' => __('Submitted', 'spun-web-archive-forge'),
                'failed' => __('Failed', 'spun-web-archive-forge'),
                'confirm' => __('Are you sure you want to submit this post to the Internet Archive?', 'spun-web-archive-forge')
            )
        ));
    }
}
