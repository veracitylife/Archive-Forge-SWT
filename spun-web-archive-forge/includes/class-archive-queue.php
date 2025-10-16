<?php
/**
 * Archive Queue Manager
 * 
 * Handles the archive submission queue functionality including database operations,
 * cron job processing, and queue management.
 * 
 * @package SpunWebArchiveElite
 * @subpackage Includes
 * @author Ryan Dickie Thompson
 * @copyright 2024 Spun Web Technology
 * @license GPL-2.0-or-later
 * @since 0.3.6
 * @version 0.6.1
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SWAP_Archive_Queue {
    
    /**
     * Database table name
     */
    private $table_name;
    
    /**
     * WordPress database instance
     */
    private $wpdb;
    
    /**
     * Maximum retry attempts
     */
    private $max_retry_attempts = 3;
    
    /**
     * Auto submitter instance
     */
    private $auto_submitter;
    
    /**
     * Submissions history instance
     */
    private $submissions_history;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Check memory usage before initialization
        if (function_exists('swap_check_memory_usage') && !swap_check_memory_usage('SWAP_Archive_Queue::__construct')) {
            error_log('SWAP: Archive Queue initialization aborted due to high memory usage');
            return;
        }
        
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'swap_archive_queue';
        
        // Ensure table exists
        $this->ensure_table_exists();
        
        // Initialize submissions history only (avoid circular dependency with auto_submitter)
        $this->submissions_history = new SWAP_Submissions_History();
        
        // Get queue settings and set max retry attempts
        $qs = get_option('swap_queue_settings', []);
        $this->max_retry_attempts = max(1, min(10, intval($qs['max_attempts'] ?? 3)));
        
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Schedule cron job
        add_action('wp', array($this, 'schedule_cron_job'));
        
        // Handle cron job execution
        add_action('swap_process_queue', array($this, 'process_queue'));
        
        // Add custom cron interval
        add_filter('cron_schedules', array($this, 'add_cron_interval'));
    }
    
    /**
     * Ensure the archive queue table exists
     */
    private function ensure_table_exists() {
        if (!$this->table_exists()) {
            $this->create_table();
        }
    }
    
    /**
     * Check if the archive queue table exists
     */
    private function table_exists() {
        $result = $this->wpdb->get_var($this->wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $this->table_name
        ));
        return !empty($result);
    }
    
    /**
     * Create database table for archive queue
     */
    public function create_table() {
        $charset_collate = $this->wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$this->table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            post_url text NOT NULL,
            post_title text NOT NULL,
            post_type varchar(20) NOT NULL DEFAULT 'post',
            status varchar(20) NOT NULL DEFAULT 'pending',
            attempts int(11) NOT NULL DEFAULT 0,
            last_attempt datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            archived_at datetime DEFAULT NULL,
            error_message text DEFAULT NULL,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Log table creation
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("SWAP: Archive queue table created/updated: {$this->table_name}");
        }
    }
    
    /**
     * Get auto submitter instance (lazy loading to avoid circular dependency)
     * 
     * @return SWAP_Auto_Submitter
     */
    private function get_auto_submitter() {
        if (!$this->auto_submitter) {
            // Pass dependencies to avoid circular dependency
            $this->auto_submitter = new SWAP_Auto_Submitter(null, $this, $this->submissions_history);
        }
        return $this->auto_submitter;
    }
    
    /**
     * Add custom cron interval for hourly processing
     */
    public function add_cron_interval($schedules) {
        $schedules['swap_hourly'] = array(
            'interval' => 3600, // 1 hour in seconds
            'display'  => __('Every Hour (SWAP)', 'spun-web-archive-forge')
        );
        return $schedules;
    }
    
    /**
     * Schedule the cron job if not already scheduled
     */
    public function schedule_cron_job() {
        if (!wp_next_scheduled('swap_process_queue')) {
            wp_schedule_event(time(), 'swap_hourly', 'swap_process_queue');
        }
    }
    
    /**
     * Add post to archive queue
     */
    public function add_to_queue($post_id, $priority = false) {
        
        $post = get_post($post_id);
        if (!$post || $post->post_status !== 'publish') {
            return false;
        }
        
        // Check if already in queue
        if ($this->is_in_queue($post_id)) {
            return false;
        }
        
        $post_url = get_permalink($post_id);
        
        $data = array(
            'post_id' => $post_id,
            'post_url' => $post_url,
            'post_title' => $post->post_title,
            'post_type' => $post->post_type,
            'status' => 'pending',
            'created_at' => current_time('mysql')
        );
        
        $result = $this->wpdb->insert($this->table_name, $data);
        
        if ($result !== false) {
            // Update post meta to indicate it's queued
            update_post_meta($post_id, '_swap_queue_status', 'queued');
            update_post_meta($post_id, '_swap_queued_at', current_time('mysql'));
            
            // Also add to submissions history table for immediate visibility
            $submissions_table = $this->wpdb->prefix . 'swap_submissions_history';
            
            // Check if already exists in submissions history to prevent duplicates
            $existing = $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT id FROM $submissions_table WHERE post_id = %d",
                $post_id
            ));
            
            if (!$existing) {
                $this->wpdb->insert(
                    $submissions_table,
                    array(
                        'post_id' => $post_id,
                        'post_url' => $post_url,
                        'status' => 'pending',
                        'submission_date' => current_time('mysql')
                    ),
                    array('%d', '%s', '%s', '%s')
                );
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if post is in queue
     */
    public function is_in_queue($post_id) {
        $count = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE post_id = %d AND status IN ('pending', 'processing')",
            $post_id
        ));
        
        return $count > 0;
    }
    
    /**
     * Check if post is archived
     */
    public function is_archived($post_id) {
        $count = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE post_id = %d AND status = 'completed'",
            $post_id
        ));
        
        return $count > 0;
    }
    
    /**
     * Process the archive queue
     */
    /**
     * Process pending items in the queue
     * 
     * @param int $limit Maximum number of items to process
     * @return array Processing results
     */
    public function process_queue(int $limit = 10): array {
        // Check memory usage before processing
        if (function_exists('swap_check_memory_usage') && !swap_check_memory_usage('SWAP_Archive_Queue::process_queue')) {
            return [
                'processed' => 0,
                'successful' => 0,
                'failed' => 0,
                'errors' => ['Memory usage too high - processing aborted']
            ];
        }
        
        $results = [
            'processed' => 0,
            'successful' => 0,
            'failed' => 0,
            'errors' => []
        ];
        
        // Check if we have pending archives to process
        if (!$this->has_pending_archives()) {
            return $results;
        }
        
        // Limit batch size based on memory usage - smaller batches for high memory usage
        $memory_usage = memory_get_usage(true);
        $memory_limit = wp_convert_hr_to_bytes(ini_get('memory_limit'));
        if ($memory_limit > 0) {
            $usage_percentage = ($memory_usage / $memory_limit) * 100;
            if ($usage_percentage > 70) {
                $limit = min($limit, 5); // Reduce batch size if memory usage is high
            } elseif ($usage_percentage > 50) {
                $limit = min($limit, 8);
            }
        }
        
        // Process in smaller chunks to prevent memory exhaustion
        $chunk_size = min($limit, 5); // Process maximum 5 items at a time
        $total_processed = 0;
        
        while ($total_processed < $limit) {
            $current_chunk_size = min($chunk_size, $limit - $total_processed);
            
            // Get pending items in small chunks for better memory management
            $pending_items = $this->wpdb->get_results($this->wpdb->prepare(
                "SELECT * FROM {$this->table_name} 
                 WHERE status = 'pending' 
                 ORDER BY created_at ASC 
                 LIMIT %d",
                $current_chunk_size
            ));
            
            if (empty($pending_items)) {
                break; // No more items to process
            }
            
            // Process this chunk
            $chunk_results = $this->process_queue_chunk($pending_items);
            
            // Merge results efficiently to reduce memory footprint
            $results['processed'] += $chunk_results['processed'];
            $results['successful'] += $chunk_results['successful'];
            $results['failed'] += $chunk_results['failed'];
            
            // Use array_push instead of array_merge for better memory efficiency
            if (!empty($chunk_results['errors'])) {
                foreach ($chunk_results['errors'] as $error) {
                    $results['errors'][] = $error;
                }
            }
            
            $total_processed += $chunk_results['processed'];
            
            // Check memory usage after each chunk
            if (function_exists('swap_check_memory_usage') && !swap_check_memory_usage('SWAP_Archive_Queue::process_queue_chunk')) {
                $results['errors'][] = 'Memory usage too high - processing stopped early';
                break;
            }
            
            // Clear any temporary variables to free memory
            unset($pending_items, $chunk_results);
            
            // If we processed fewer items than requested, we're done
            if (count($pending_items ?? []) < $current_chunk_size) {
                break;
            }
        }
        
        return $results;
    }
    
    /**
     * Process a chunk of queue items
     * 
     * @param array $pending_items Items to process
     * @return array Processing results
     */
    private function process_queue_chunk(array $pending_items): array {
        $results = [
            'processed' => 0,
            'successful' => 0,
            'failed' => 0,
            'errors' => []
        ];
        
        // Prepare batch updates for better performance
        $batch_updates = [
            'processing' => [],
            'completed' => [],
            'failed' => []
        ];
        
        foreach ($pending_items as $item) {
            $results['processed']++;
            
            // Mark as processing first
            $batch_updates['processing'][] = $item->id;
            
            try {
                // Attempt submission
                $submission_result = $this->get_auto_submitter()->submit_immediately($item->post_id, $item->post_url);
                
                if ($submission_result && isset($submission_result['success']) && $submission_result['success']) {
                    // Successful submission
                    $results['successful']++;
                    $batch_updates['completed'][] = [
                        'id' => $item->id,
                        'archived_at' => current_time('mysql'),
                        'error_message' => null
                    ];
                    
                    // Update submissions history
                    $this->update_submissions_history_by_post_id($item->post_id, 'completed', $submission_result);
                    
                } else {
                    // Failed submission - increment attempts
                    $new_attempts = $item->attempts + 1;
                    $error_message = isset($submission_result['error']) ? $submission_result['error'] : 'Unknown error';
                    
                    if ($new_attempts >= $this->max_retry_attempts) {
                        // Max attempts reached - mark as failed
                        $results['failed']++;
                        $batch_updates['failed'][] = [
                            'id' => $item->id,
                            'attempts' => $new_attempts,
                            'error_message' => $error_message
                        ];
                        
                        $this->update_submissions_history_by_post_id($item->post_id, 'failed', $submission_result);
                    } else {
                        // Retry later - update attempts and reset to pending
                        $this->wpdb->update(
                            $this->table_name,
                            [
                                'status' => 'pending',
                                'attempts' => $new_attempts,
                                'last_attempt' => current_time('mysql'),
                                'error_message' => $error_message
                            ],
                            ['id' => $item->id],
                            ['%s', '%d', '%s', '%s'],
                            ['%d']
                        );
                    }
                    
                    $results['errors'][] = "Post {$item->post_id}: {$error_message}";
                }
                
            } catch (Exception $e) {
                $results['failed']++;
                $error_message = $e->getMessage();
                $results['errors'][] = "Post {$item->post_id}: {$error_message}";
                
                // Handle exception - increment attempts
                $new_attempts = $item->attempts + 1;
                if ($new_attempts >= $this->max_retry_attempts) {
                    $batch_updates['failed'][] = [
                        'id' => $item->id,
                        'attempts' => $new_attempts,
                        'error_message' => $error_message
                    ];
                } else {
                    $this->wpdb->update(
                        $this->table_name,
                        [
                            'status' => 'pending',
                            'attempts' => $new_attempts,
                            'last_attempt' => current_time('mysql'),
                            'error_message' => $error_message
                        ],
                        ['id' => $item->id],
                        ['%s', '%d', '%s', '%s'],
                        ['%d']
                    );
                }
            }
        }
        
        // Execute batch updates for better performance
        $this->execute_batch_updates($batch_updates);
        
        return $results;
    }
    
    /**
     * Execute batch updates for better performance
     * 
     * @param array $batch_updates Array of updates grouped by status
     * @return void
     */
    private function execute_batch_updates(array $batch_updates): void {
        // Mark items as processing
        if (!empty($batch_updates['processing'])) {
            $ids = implode(',', array_map('intval', $batch_updates['processing']));
            $this->wpdb->query(
                "UPDATE {$this->table_name} 
                 SET status = 'processing', last_attempt = '" . current_time('mysql') . "' 
                 WHERE id IN ({$ids})"
            );
        }
        
        // Mark completed items
        if (!empty($batch_updates['completed'])) {
            foreach ($batch_updates['completed'] as $update) {
                $this->wpdb->update(
                    $this->table_name,
                    [
                        'status' => 'completed',
                        'archived_at' => $update['archived_at'],
                        'error_message' => null
                    ],
                    ['id' => $update['id']],
                    ['%s', '%s', '%s'],
                    ['%d']
                );
            }
        }
        
        // Mark failed items
        if (!empty($batch_updates['failed'])) {
            foreach ($batch_updates['failed'] as $update) {
                $this->wpdb->update(
                    $this->table_name,
                    [
                        'status' => 'failed',
                        'attempts' => $update['attempts'],
                        'last_attempt' => current_time('mysql'),
                        'error_message' => $update['error_message']
                    ],
                    ['id' => $update['id']],
                    ['%s', '%d', '%s', '%s'],
                    ['%d']
                );
            }
        }
    }
    
    /**
     * Get queue statistics
     */
    public function get_queue_stats() {
        $stats = array();
        
        $stats['pending'] = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'pending'"
        );
        
        $stats['processing'] = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'processing'"
        );
        
        $stats['completed'] = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'completed'"
        );
        
        $stats['failed'] = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'failed'"
        );
        
        $stats['total'] = $stats['pending'] + $stats['processing'] + $stats['completed'] + $stats['failed'];
        
        return $stats;
    }
    
    /**
     * Get recent queue items
     */
    public function get_recent_items($limit = 10) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
             ORDER BY created_at DESC 
             LIMIT %d",
            $limit
        ));
    }
    
    /**
     * Clear completed items older than specified days
     */
    public function cleanup_old_items($days = 30) {
        $this->wpdb->query($this->wpdb->prepare(
            "DELETE FROM {$this->table_name} 
             WHERE status = 'completed' 
             AND archived_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ));
    }
    
    /**
     * Check if there are pending archives to process
     * 
     * @return bool True if there are pending archives
     */
    public function has_pending_archives() {
        $count = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'pending'"
        );
        
        return $count > 0;
    }
    
    /**
     * Check for archived versions of pending submissions
     */
    private function check_pending_archives() {
        // Get submissions history instance
        $submissions_history = new SWAP_Submissions_History();
        
        // Get all submissions without archive URLs
        $submissions = $submissions_history->get_submissions(array(
            'archive_url' => '',
            'per_page' => 50 // Check up to 50 at a time to avoid timeout
        ));
        
        if (empty($submissions)) {
            return;
        }
        
        // Initialize Archive API
        $archive_api = new SWAP_Archive_API();
        
        foreach ($submissions as $submission) {
            // Get the post URL
            $post_url = get_permalink($submission->post_id);
            if (!$post_url) {
                continue;
            }
            
            // Check if archived version exists
            $availability_data = $archive_api->check_availability($post_url);
            
            if ($availability_data && isset($availability_data['archived_snapshots']['closest']['url'])) {
                $archive_url = $availability_data['archived_snapshots']['closest']['url'];
                
                // Update the submission with the archive URL
                $submissions_history->update_submission_status(
                    $submission->id,
                    $submission->status, // Keep current status
                    array('archive_url' => $archive_url)
                );
                
                // Log the update
                error_log("SWAP: Found archive URL for post {$submission->post_id}: {$archive_url}");
            }
            
            // Add a small delay to be respectful to archive.org
            usleep(500000); // 0.5 second delay
        }
    }
    
    /**
     * Update submissions history by post ID
     */
    private function update_submissions_history_by_post_id($post_id, $status, $submission_result = array()) {
        // Get the latest submission for this post
        $submission = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT id FROM {$this->wpdb->prefix}swap_submissions_history 
             WHERE post_id = %d 
             ORDER BY submission_date DESC 
             LIMIT 1",
            $post_id
        ));
        
        if ($submission) {
            $archive_url = '';
            $error_message = '';
            $response_data = '';
            
            if ($status === 'completed' && isset($submission_result['archive_url'])) {
                $archive_url = $submission_result['archive_url'];
            }
            
            if ($status === 'failed' && isset($submission_result['error'])) {
                $error_message = $submission_result['error'];
            }
            
            if (!empty($submission_result)) {
                $response_data = wp_json_encode($submission_result);
            }
            
            // Update the submission status
            $this->submissions_history->update_submission_status(
                $submission->id,
                $status,
                $archive_url,
                $error_message,
                $response_data
            );
        }
    }
    
    /**
     * Remove table on uninstall
     */
    public function drop_table() {
        $this->wpdb->query("DROP TABLE IF EXISTS {$this->table_name}");
    }
}
