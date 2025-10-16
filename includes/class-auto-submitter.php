<?php
/**
 * Auto Submitter Handler
 * 
 * Handles automatic submission of new posts and pages to the Internet Archive.
 * 
 * @package SpunWebArchiveElite
 * @subpackage Includes
 * @author Ryan Dickie Thompson
 * @copyright 2024 Spun Web Technology
 * @license GPL-2.0-or-later
 * @since 0.0.1
 * @version 0.6.1
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Auto Submitter class for handling automatic post archiving
 */
class SWAP_Auto_Submitter {
    
    /**
     * Archive API instance
     * 
     * @var SWAP_Archive_API
     */
    private SWAP_Archive_API $archive_api;
    
    /**
     * Archive Queue instance
     * 
     * @var SWAP_Archive_Queue
     */
    private SWAP_Archive_Queue $archive_queue;
    
    /**
     * Submissions History instance
     * 
     * @var SWAP_Submissions_History
     */
    private SWAP_Submissions_History $submissions_history;
    
    /**
     * Auto submission settings
     * 
     * @var array
     */
    private array $auto_settings;
    
    /**
     * Constructor
     * 
     * @param SWAP_Archive_API|null $archive_api Archive API instance
     * @param SWAP_Archive_Queue|null $archive_queue Archive Queue instance
     * @param SWAP_Submissions_History|null $submissions_history Submissions History instance
     */
    public function __construct(
        ?SWAP_Archive_API $archive_api = null,
        ?SWAP_Archive_Queue $archive_queue = null,
        ?SWAP_Submissions_History $submissions_history = null
    ) {
        // Check memory usage before initialization
        if (function_exists('swap_check_memory_usage') && !swap_check_memory_usage('SWAP_Auto_Submitter::__construct')) {
            error_log('SWAP: Auto Submitter initialization aborted due to high memory usage');
            return;
        }
        
        $this->archive_api = $archive_api ?? new SWAP_Archive_API();
        $this->archive_queue = $archive_queue ?? new SWAP_Archive_Queue();
        $this->submissions_history = $submissions_history ?? new SWAP_Submissions_History();
        
        $this->load_settings();
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     * 
     * @return void
     */
    private function init_hooks(): void {
        // Post publishing hooks
        add_action('publish_post', [$this, 'handle_post_publish'], 10, 2);
        add_action('publish_page', [$this, 'handle_post_publish'], 10, 2);
        
        // Custom post type support
        add_action('transition_post_status', [$this, 'handle_post_status_transition'], 10, 3);
        
        // Delayed submission hook
        add_action('swap_delayed_submission', [$this, 'process_delayed_submission'], 10, 2);
        
        // Admin interface hooks
        add_action('add_meta_boxes', [$this, 'add_archive_meta_box']);
        add_action('save_post', [$this, 'save_meta_box_data']);
        
        // Admin columns
        add_filter('manage_posts_columns', [$this, 'add_archive_status_column']);
        add_filter('manage_pages_columns', [$this, 'add_archive_status_column']);
        add_action('manage_posts_custom_column', [$this, 'display_archive_status_column'], 10, 2);
        add_action('manage_pages_custom_column', [$this, 'display_archive_status_column'], 10, 2);
        
        // AJAX handlers
        add_action('wp_ajax_swap_submit_single', [$this, 'ajax_submit_single']);
        add_action('wp_ajax_swap_retry_failed', [$this, 'ajax_retry_failed']);
    }
    
    /**
     * Load auto submission settings
     * 
     * @return void
     */
    private function load_settings(): void {
        $this->auto_settings = get_option('swap_auto_settings', [
            'enabled' => false,
            'post_types' => ['post', 'page'],
            'submit_updates' => false,
            'delay' => 0,
            'retry_failed' => true,
            'max_retries' => 3
        ]);
    }
    
    /**
     * Handle post publish event
     * 
     * @param int $post_id Post ID
     * @param WP_Post $post Post object
     * @return bool Success status
     */
    public function handle_post_publish(int $post_id, WP_Post $post): bool {
        if (!$this->should_auto_submit($post_id, $post->post_type)) {
            return false;
        }
        
        return $this->submit_content($post_id, $post->post_type);
    }
    
    /**
     * Handle post status transitions
     * 
     * @param string $new_status New post status
     * @param string $old_status Old post status
     * @param WP_Post $post Post object
     * @return bool Success status
     */
    public function handle_post_status_transition(string $new_status, string $old_status, WP_Post $post): bool {
        // Only handle transitions to published status
        if ($new_status !== 'publish' || $old_status === 'publish') {
            return false;
        }
        
        if (!$this->should_auto_submit($post->ID, $post->post_type)) {
            return false;
        }
        
        return $this->submit_content($post->ID, $post->post_type);
    }
    
    /**
     * Submit content to archive
     * 
     * @param int $post_id Post ID
     * @param string $post_type Post type
     * @return bool Success status
     */
    public function submit_content(int $post_id, string $post_type = 'post'): bool {
        try {
            // Validate post
            $post = get_post($post_id);
            if (!$post || $post->post_status !== 'publish') {
                throw new InvalidArgumentException("Post {$post_id} is not valid or not published");
            }
            
            // Get post URL
            $url = get_permalink($post_id);
            if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
                throw new InvalidArgumentException("Invalid URL for post {$post_id}");
            }
            
            // Check if already submitted recently
            if ($this->is_recently_submitted($post_id)) {
                return false;
            }
            
            $delay = (int) ($this->auto_settings['delay'] ?? 0);
            
            if ($delay > 0) {
                return $this->schedule_delayed_submission($post_id, $url, $delay);
            } else {
                return $this->submit_immediately($post_id, $url);
            }
            
        } catch (Exception $e) {
            error_log("SWAP Auto Submitter Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Schedule delayed submission
     * 
     * @param int $post_id Post ID
     * @param string $url Post URL
     * @param int $delay Delay in seconds
     * @return bool Success status
     */
    private function schedule_delayed_submission(int $post_id, string $url, int $delay): bool {
        $scheduled_time = time() + $delay;
        
        // Schedule the event
        $scheduled = function_exists('wp_schedule_single_event') ? 
            wp_schedule_single_event($scheduled_time, 'swap_delayed_submission', [$post_id, $url]) : false;
        
        if ($scheduled) {
            // Log pending submission
            $this->log_submission($post_id, $url, 'pending', '', 'Scheduled for delayed submission');
            
            // Update post meta
            update_post_meta($post_id, '_swap_archive_status', 'pending');
            update_post_meta($post_id, '_swap_scheduled_time', $scheduled_time);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Submit immediately to archive
     * 
     * @param int $post_id Post ID
     * @param string $url Post URL
     * @return bool Success status
     */
    public function submit_immediately(int $post_id, string $url): bool {
        try {
            // Prepare submission options
            $options = [
                'capture_all' => 'on',
                'capture_outlinks' => 'off',
                'capture_screenshot' => 'off'
            ];
            
            // Submit to archive
            $result = $this->archive_api->submit_url($url, $options);
            
            if ($result['success']) {
                $this->handle_successful_submission($post_id, $url, $result);
                return true;
            } else {
                $this->handle_failed_submission($post_id, $url, $result);
                return false;
            }
            
        } catch (Exception $e) {
            $error_message = "Submission error: " . $e->getMessage();
            $this->handle_failed_submission($post_id, $url, ['error' => $error_message]);
            return false;
        }
    }
    
    /**
     * Handle successful submission
     * 
     * @param int $post_id Post ID
     * @param string $url Post URL
     * @param array $result Submission result
     * @return void
     */
    private function handle_successful_submission(int $post_id, string $url, array $result): void {
        $archive_url = $result['archive_url'] ?? '';
        
        // Log successful submission
        $this->log_submission($post_id, $url, 'success', $archive_url, function_exists('wp_json_encode') ? wp_json_encode($result) : json_encode($result));
        
        // Update post meta
        update_post_meta($post_id, '_swap_archive_status', 'archived');
        update_post_meta($post_id, '_swap_archive_url', $archive_url);
        update_post_meta($post_id, '_swap_archive_date', function_exists('current_time') ? current_time('mysql') : date('Y-m-d H:i:s'));
        delete_post_meta($post_id, '_swap_scheduled_time');
        
        // Trigger action for other plugins
        if (function_exists('do_action')) {
            do_action('swap_content_archived', $post_id, $url, $archive_url);
        }
    }
    
    /**
     * Handle failed submission
     * 
     * @param int $post_id Post ID
     * @param string $url Post URL
     * @param array $result Submission result
     * @return void
     */
    private function handle_failed_submission(int $post_id, string $url, array $result): void {
        $error_message = $result['error'] ?? 'Unknown error';
        
        // Log failed submission
        $this->log_submission($post_id, $url, 'failed', '', function_exists('wp_json_encode') ? wp_json_encode($result) : json_encode($result));
        
        // Update post meta
        update_post_meta($post_id, '_swap_archive_status', 'failed');
        update_post_meta($post_id, '_swap_archive_error', $error_message);
        delete_post_meta($post_id, '_swap_scheduled_time');
        
        // Schedule retry if enabled
        if ($this->auto_settings['retry_failed'] ?? false) {
            $this->schedule_retry($post_id, $url);
        }
        
        // Trigger action for failed submissions
        if (function_exists('do_action')) {
            do_action('swap_content_archive_failed', $post_id, $url, $error_message);
        }
    }
    
    /**
     * Schedule retry for failed submission
     * 
     * @param int $post_id Post ID
     * @param string $url Post URL
     * @return void
     */
    private function schedule_retry(int $post_id, string $url): void {
        $retry_count = (int) get_post_meta($post_id, '_swap_retry_count', true);
        $max_retries = (int) ($this->auto_settings['max_retries'] ?? 3);
        
        if ($retry_count < $max_retries) {
            $retry_delay = 300 * ($retry_count + 1); // Exponential backoff: 5min, 10min, 15min
            $retry_time = time() + $retry_delay;
            
            if (function_exists('wp_schedule_single_event')) {
                wp_schedule_single_event($retry_time, 'swap_delayed_submission', [$post_id, $url]);
            }
            update_post_meta($post_id, '_swap_retry_count', $retry_count + 1);
            update_post_meta($post_id, '_swap_scheduled_time', $retry_time);
        }
    }
    
    /**
     * Check if post was recently submitted
     * 
     * @param int $post_id Post ID
     * @return bool True if recently submitted
     */
    private function is_recently_submitted(int $post_id): bool {
        return $this->submissions_history->is_recently_submitted($post_id);
    }
    
    /**
     * Log submission to database
     * 
     * @param int $post_id Post ID
     * @param string $url Post URL
     * @param string $status Submission status
     * @param string $archive_url Archive URL
     * @param string $response_data Response data
     * @return void
     */
    private function log_submission(
        int $post_id, 
        string $url, 
        string $status, 
        string $archive_url = '', 
        string $response_data = ''
    ): void {
        $this->submissions_history->add_submission(
            $post_id,
            $url,
            $archive_url,
            $status,
            $response_data
        );
    }
    
    /**
     * Process delayed submission
     * 
     * @param int $post_id Post ID
     * @param string $url Post URL
     * @return bool Success status
     */
    public function process_delayed_submission(int $post_id, string $url): bool {
        // Validate post still exists and is published
        $post = get_post($post_id);
        if (!$post || $post->post_status !== 'publish') {
            return false;
        }
        
        // Submit to archive
        return $this->submit_immediately($post_id, $url);
    }
    
    /**
     * Check if content should be auto-submitted
     * 
     * @param int $post_id Post ID
     * @param string $post_type Post type
     * @return bool True if should be submitted
     */
    public function should_auto_submit(int $post_id, string $post_type): bool {
        // Check if auto submission is enabled
        if (!($this->auto_settings['enabled'] ?? false)) {
            return false;
        }
        
        // Check if post type is enabled
        $enabled_post_types = $this->auto_settings['post_types'] ?? [];
        if (!is_array($enabled_post_types) || !in_array($post_type, $enabled_post_types, true)) {
            return false;
        }
        
        // Check if post is excluded via meta
        if (get_post_meta($post_id, '_swap_exclude_from_archive', true)) {
            return false;
        }
        
        // Allow filtering
        return function_exists('apply_filters') ? 
            apply_filters('swap_should_auto_submit', true, $post_id, $post_type) : true;
    }
    
    /**
     * Get submission status for a post
     * 
     * @param int $post_id Post ID
     * @return array|false Submission status or false if not found
     */
    public function get_submission_status(int $post_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'swap_submissions_history';
        
        $submission = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} 
             WHERE post_id = %d 
             ORDER BY submission_date DESC 
             LIMIT 1",
            $post_id
        ));
        
        if ($submission) {
            return [
                'status' => $submission->status,
                'archive_url' => $submission->archive_url,
                'submission_date' => $submission->submission_date,
                'response_data' => $submission->error_message
            ];
        }
        
        return false;
    }
    
    /**
     * Add archive status column to post list
     * 
     * @param array $columns Existing columns
     * @return array Modified columns
     */
    public function add_archive_status_column(array $columns): array {
        $columns['archive_status'] = function_exists('__') ? 
            __('Archive Status', 'spun-web-archive-forge') : 'Archive Status';
        return $columns;
    }
    
    /**
     * Display archive status in post list column
     * 
     * @param string $column Column name
     * @param int $post_id Post ID
     * @return void
     */
    public function display_archive_status_column(string $column, int $post_id): void {
        if ($column !== 'archive_status') {
            return;
        }
        
        $status = $this->get_submission_status($post_id);
        
        if ($status) {
            switch ($status['status']) {
                case 'success':
                    $archived_text = function_exists('esc_html__') ? 
                        esc_html__('Archived', 'spun-web-archive-forge') : 'Archived';
                    $view_archive_text = function_exists('esc_html__') ? 
                        esc_html__('View Archive', 'spun-web-archive-forge') : 'View Archive';
                    echo '<span style="color: green;">✓ ' . $archived_text . '</span>';
                    if ($status['archive_url']) {
                        echo '<br><a href="' . esc_url($status['archive_url']) . '" target="_blank" style="font-size: 11px;">' . 
                             $view_archive_text . '</a>';
                    }
                    break;
                case 'failed':
                    $failed_text = function_exists('esc_html__') ? 
                        esc_html__('Failed', 'spun-web-archive-forge') : 'Failed';
                    echo '<span style="color: red;">✗ ' . $failed_text . '</span>';
                    break;
                case 'pending':
                    $pending_text = function_exists('esc_html__') ? 
                        esc_html__('Pending', 'spun-web-archive-forge') : 'Pending';
                    echo '<span style="color: orange;">⏳ ' . $pending_text . '</span>';
                    break;
                default:
                    echo '<span style="color: #666;">—</span>';
            }
        } else {
            echo '<span style="color: #666;">—</span>';
        }
    }
    
    /**
     * Add archive meta box to post edit screens
     */
    public function add_archive_meta_box(): void {
        // Check memory usage before processing
        if (function_exists('swap_check_memory_usage') && !swap_check_memory_usage('SWAP_Auto_Submitter::add_archive_meta_box')) {
            error_log('SWAP: Meta box addition aborted due to high memory usage');
            return;
        }
        
        $post_types = function_exists('get_post_types') ? 
            get_post_types(['public' => true]) : ['post', 'page'];
        
        $processed_count = 0;
        foreach ($post_types as $post_type) {
            // Monitor memory usage in loop
            if ($processed_count > 0 && $processed_count % 10 === 0) {
                if (function_exists('swap_check_memory_usage') && !swap_check_memory_usage('SWAP_Auto_Submitter::add_archive_meta_box_loop')) {
                    error_log('SWAP: Meta box loop stopped due to high memory usage after processing ' . $processed_count . ' post types');
                    break;
                }
            }
            
            if (function_exists('add_meta_box')) {
                add_meta_box(
                    'swap_archive_status',
                    function_exists('__') ? __('Archive Status', 'spun-web-archive-forge') : 'Archive Status',
                    [$this, 'render_archive_meta_box'],
                    $post_type,
                    'side',
                    'default'
                );
            }
            
            $processed_count++;
        }
    }
    
    /**
     * Render archive meta box
     * 
     * @param WP_Post $post Post object
     * @return void
     */
    public function render_archive_meta_box(WP_Post $post): void {
        $status = $this->get_submission_status($post->ID);
        $excluded = get_post_meta($post->ID, '_swap_exclude_from_archive', true);
        
        if (function_exists('wp_nonce_field')) {
            wp_nonce_field('swap_meta_box', 'swap_meta_box_nonce');
        }
        
        echo '<div class="swap-meta-box">';
        
        if ($status) {
            $status_label = function_exists('esc_html__') ? 
                esc_html__('Status:', 'spun-web-archive-forge') : 'Status:';
            echo '<p><strong>' . $status_label . '</strong> ';
            switch ($status['status']) {
                case 'success':
                    $archived_text = function_exists('esc_html__') ? 
                        esc_html__('Archived', 'spun-web-archive-forge') : 'Archived';
                    echo '<span style="color: green;">' . $archived_text . '</span>';
                    break;
                case 'failed':
                    $failed_text = function_exists('esc_html__') ? 
                        esc_html__('Failed', 'spun-web-archive-forge') : 'Failed';
                    echo '<span style="color: red;">' . $failed_text . '</span>';
                    break;
                case 'pending':
                    $pending_text = function_exists('esc_html__') ? 
                        esc_html__('Pending', 'spun-web-archive-forge') : 'Pending';
                    echo '<span style="color: orange;">' . $pending_text . '</span>';
                    break;
            }
            echo '</p>';
            
            if ($status['archive_url']) {
                $view_archive_text = function_exists('esc_html__') ? 
                    esc_html__('View in Archive', 'spun-web-archive-forge') : 'View in Archive';
                echo '<p><a href="' . esc_url($status['archive_url']) . '" target="_blank" class="button button-secondary">' . 
                     $view_archive_text . '</a></p>';
            }
            
            $last_submitted_text = function_exists('sprintf') && function_exists('esc_html__') ? 
                sprintf(esc_html__('Last submitted: %s', 'spun-web-archive-forge'), esc_html($status['submission_date'])) :
                'Last submitted: ' . esc_html($status['submission_date']);
            echo '<p><small>' . $last_submitted_text . '</small></p>';
        } else {
            $not_submitted_text = function_exists('esc_html__') ? 
                esc_html__('Not yet submitted to archive.', 'spun-web-archive-forge') : 'Not yet submitted to archive.';
            echo '<p>' . $not_submitted_text . '</p>';
        }
        
        echo '<p>';
        echo '<label><input type="checkbox" name="swap_exclude_from_archive" value="1" ' . checked($excluded, true, false) . ' /> ';
        echo esc_html__('Exclude from automatic archiving', 'spun-web-archive-forge') . '</label>';
        echo '</p>';
        
        if ($post->post_status === 'publish') {
            echo '<p><button type="button" class="button button-secondary" onclick="swapSubmitSingle(' . $post->ID . ')">' . 
                 esc_html__('Submit to Archive Now', 'spun-web-archive-forge') . '</button></p>';
        }
        
        echo '</div>';
    }
    
    /**
     * Save meta box data
     * 
     * @param int $post_id Post ID
     * @return void
     */
    public function save_meta_box_data(int $post_id): void {
        if (!isset($_POST['swap_meta_box_nonce']) || 
            !function_exists('wp_verify_nonce') || 
            !wp_verify_nonce($_POST['swap_meta_box_nonce'], 'swap_meta_box')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!function_exists('current_user_can') || !current_user_can('edit_post', $post_id)) {
            return;
        }
        
        $excluded = isset($_POST['swap_exclude_from_archive']);
        update_post_meta($post_id, '_swap_exclude_from_archive', $excluded);
    }
    
    /**
     * AJAX handler for single post submission
     * 
     * @return void
     */
    public function ajax_submit_single(): void {
        if (function_exists('check_ajax_referer')) {
            check_ajax_referer('swap_admin_nonce', 'nonce');
        }
        
        if (!function_exists('current_user_can') || !current_user_can('edit_posts')) {
            $error_msg = function_exists('__') ? 
                __('Insufficient permissions', 'spun-web-archive-forge') : 'Insufficient permissions';
            if (function_exists('wp_die')) {
                wp_die($error_msg);
            } else {
                die($error_msg);
            }
        }
        
        $post_id = (int) ($_POST['post_id'] ?? 0);
        if (!$post_id) {
            $error_msg = function_exists('__') ? 
                __('Invalid post ID', 'spun-web-archive-forge') : 'Invalid post ID';
            if (function_exists('wp_die')) {
                wp_die($error_msg);
            } else {
                die($error_msg);
            }
        }
        
        $post = get_post($post_id);
        if (!$post) {
            $error_msg = function_exists('__') ? 
                __('Post not found', 'spun-web-archive-forge') : 'Post not found';
            if (function_exists('wp_die')) {
                wp_die($error_msg);
            } else {
                die($error_msg);
            }
        }
        
        $url = get_permalink($post_id);
        $result = $this->submit_immediately($post_id, $url);
        
        $success_msg = function_exists('__') ? 
            __('Post submitted successfully', 'spun-web-archive-forge') : 'Post submitted successfully';
        $fail_msg = function_exists('__') ? 
            __('Submission failed', 'spun-web-archive-forge') : 'Submission failed';
        
        if (function_exists('wp_send_json')) {
            wp_send_json([
                'success' => $result,
                'message' => $result ? $success_msg : $fail_msg
            ]);
        } else {
            echo json_encode([
                'success' => $result,
                'message' => $result ? $success_msg : $fail_msg
            ]);
            exit;
        }
    }
    
    /**
     * AJAX handler for retrying failed submissions
     * 
     * @return void
     */
    public function ajax_retry_failed(): void {
        if (function_exists('check_ajax_referer')) {
            check_ajax_referer('swap_admin_nonce', 'nonce');
        }
        
        if (!function_exists('current_user_can') || !current_user_can('manage_options')) {
            $error_msg = function_exists('__') ? 
                __('Insufficient permissions', 'spun-web-archive-forge') : 'Insufficient permissions';
            if (function_exists('wp_die')) {
                wp_die($error_msg);
            } else {
                die($error_msg);
            }
        }
        
        $limit = (int) ($_POST['limit'] ?? 10);
        $results = $this->retry_failed_submissions($limit);
        
        $message = function_exists('sprintf') && function_exists('__') ? 
            sprintf(__('Retried %d failed submissions', 'spun-web-archive-forge'), count($results)) :
            'Retried ' . count($results) . ' failed submissions';
        
        if (function_exists('wp_send_json')) {
            wp_send_json([
                'success' => true,
                'results' => $results,
                'message' => $message
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'results' => $results,
                'message' => $message
            ]);
            exit;
        }
    }
    
    /**
     * Retry failed submissions
     * 
     * @param int $limit Maximum number of submissions to retry
     * @return array Retry results
     */
    public function retry_failed_submissions(int $limit = 10): array {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'swap_submissions_history';
        
        // Get failed submissions from the last 24 hours
        $failed_submissions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} 
             WHERE status = 'failed' 
             AND submission_date > DATE_SUB(NOW(), INTERVAL 24 HOUR)
             ORDER BY submission_date DESC 
             LIMIT %d",
            $limit
        ));
        
        $retry_results = [];
        
        foreach ($failed_submissions as $submission) {
            // Validate post still exists
            $post = get_post($submission->post_id);
            if (!$post || $post->post_status !== 'publish') {
                continue;
            }
            
            // Retry submission
            $result = $this->submit_immediately($submission->post_id, $submission->post_url);
            $retry_results[] = [
                'post_id' => $submission->post_id,
                'url' => $submission->post_url,
                'success' => $result,
                'result' => $result
            ];
            
            // Add delay between retries
            sleep(2);
        }
        
        return $retry_results;
    }
}
