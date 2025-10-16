<?php
/**
 * Enhanced Queue Management
 * 
 * Based on MickeyKay/archiver patterns for robust queue handling
 * 
 * @package SpunWebArchiveForge
 * @subpackage Includes
 * @author Ryan Dickie Thompson
 * @copyright 2024 Spun Web Technology
 * @license GPL-2.0-or-later
 * @since 1.0.16
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enhanced Archive Queue Class
 * 
 * @since 1.0.16
 */
class SWAP_Enhanced_Queue {
    
    /**
     * Database instance
     * 
     * @var wpdb
     */
    private $wpdb;
    
    /**
     * Table name
     * 
     * @var string
     */
    private $table_name;
    
    /**
     * Constructor
     * 
     * @since 1.0.16
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'swap_archive_queue';
        
        // Ensure table exists
        $this->ensure_table_exists();
    }
    
    /**
     * Ensure queue table exists with fallback
     * 
     * @since 1.0.16
     * @return bool True if table exists or was created
     */
    private function ensure_table_exists() {
        // Check if table exists
        if ($this->table_exists()) {
            return true;
        }
        
        // Try to create table
        if ($this->create_table()) {
            return true;
        }
        
        // Fallback: Use WordPress options as queue
        $this->log_error('Queue table creation failed, using options fallback');
        return false;
    }
    
    /**
     * Check if table exists
     * 
     * @since 1.0.16
     * @return bool True if table exists
     */
    private function table_exists() {
        $result = $this->wpdb->get_var($this->wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $this->table_name
        ));
        
        return !empty($result);
    }
    
    /**
     * Create queue table with error handling
     * 
     * @since 1.0.16
     * @return bool True if created successfully
     */
    private function create_table() {
        $charset_collate = $this->wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            url varchar(255) NOT NULL,
            post_id bigint(20) DEFAULT NULL,
            status varchar(50) NOT NULL DEFAULT 'pending',
            priority int(11) NOT NULL DEFAULT 0,
            attempts int(11) NOT NULL DEFAULT 0,
            max_attempts int(11) NOT NULL DEFAULT 3,
            error_message text DEFAULT NULL,
            archive_url varchar(255) DEFAULT NULL,
            submitted_at datetime DEFAULT CURRENT_TIMESTAMP,
            processed_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY status (status),
            KEY priority (priority),
            KEY post_id (post_id),
            KEY url (url)
        ) {$charset_collate};";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        try {
            $result = dbDelta($sql);
            
            // Check if table was created successfully
            if ($this->table_exists()) {
                $this->log_success('Queue table created successfully');
                return true;
            }
            
            $this->log_error('Table creation failed: ' . print_r($result, true));
            return false;
            
        } catch (Exception $e) {
            $this->log_error('Table creation exception: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Add item to queue with fallback
     * 
     * @since 1.0.16
     * @param string $url URL to archive
     * @param int $post_id Post ID (optional)
     * @param int $priority Priority (higher = more important)
     * @return bool True if added successfully
     */
    public function add_to_queue($url, $post_id = null, $priority = 0) {
        // Try database first
        if ($this->table_exists()) {
            return $this->add_to_database_queue($url, $post_id, $priority);
        }
        
        // Fallback to WordPress options
        return $this->add_to_options_queue($url, $post_id, $priority);
    }
    
    /**
     * Add to database queue
     * 
     * @since 1.0.16
     * @param string $url URL to archive
     * @param int $post_id Post ID
     * @param int $priority Priority
     * @return bool True if added successfully
     */
    private function add_to_database_queue($url, $post_id, $priority) {
        $result = $this->wpdb->insert(
            $this->table_name,
            array(
                'url' => $url,
                'post_id' => $post_id,
                'priority' => $priority,
                'status' => 'pending',
                'created_at' => current_time('mysql')
            ),
            array('%s', '%d', '%d', '%s', '%s')
        );
        
        if ($result === false) {
            $this->log_error('Database queue insert failed: ' . $this->wpdb->last_error);
            return false;
        }
        
        return true;
    }
    
    /**
     * Add to options queue (fallback)
     * 
     * @since 1.0.16
     * @param string $url URL to archive
     * @param int $post_id Post ID
     * @param int $priority Priority
     * @return bool True if added successfully
     */
    private function add_to_options_queue($url, $post_id, $priority) {
        $queue = get_option('swap_fallback_queue', array());
        
        $queue[] = array(
            'url' => $url,
            'post_id' => $post_id,
            'priority' => $priority,
            'status' => 'pending',
            'created_at' => current_time('mysql'),
            'attempts' => 0
        );
        
        // Sort by priority
        usort($queue, function($a, $b) {
            return $b['priority'] - $a['priority'];
        });
        
        // Keep only last 100 items to prevent memory issues
        if (count($queue) > 100) {
            $queue = array_slice($queue, 0, 100);
        }
        
        return update_option('swap_fallback_queue', $queue);
    }
    
    /**
     * Process queue with error handling
     * 
     * @since 1.0.16
     * @param int $limit Number of items to process
     * @return array Processing results
     */
    public function process_queue($limit = 10) {
        $results = array(
            'processed' => 0,
            'success' => 0,
            'failed' => 0,
            'errors' => array()
        );
        
        // Try database queue first
        if ($this->table_exists()) {
            $results = $this->process_database_queue($limit);
        } else {
            $results = $this->process_options_queue($limit);
        }
        
        return $results;
    }
    
    /**
     * Process database queue
     * 
     * @since 1.0.16
     * @param int $limit Number of items to process
     * @return array Processing results
     */
    private function process_database_queue($limit) {
        $results = array(
            'processed' => 0,
            'success' => 0,
            'failed' => 0,
            'errors' => array()
        );
        
        // Get pending items
        $items = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
             WHERE status = 'pending' 
             AND attempts < max_attempts 
             ORDER BY priority DESC, created_at ASC 
             LIMIT %d",
            $limit
        ));
        
        foreach ($items as $item) {
            $results['processed']++;
            
            // Process item
            $result = $this->process_queue_item($item);
            
            if ($result['success']) {
                $results['success']++;
                $this->update_item_status($item->id, 'completed', $result['archive_url']);
            } else {
                $results['failed']++;
                $this->increment_attempts($item->id, $result['error']);
                $results['errors'][] = $result['error'];
            }
        }
        
        return $results;
    }
    
    /**
     * Process options queue (fallback)
     * 
     * @since 1.0.16
     * @param int $limit Number of items to process
     * @return array Processing results
     */
    private function process_options_queue($limit) {
        $results = array(
            'processed' => 0,
            'success' => 0,
            'failed' => 0,
            'errors' => array()
        );
        
        $queue = get_option('swap_fallback_queue', array());
        $processed_items = array();
        
        foreach (array_slice($queue, 0, $limit) as $index => $item) {
            $results['processed']++;
            
            // Process item
            $result = $this->process_queue_item((object)$item);
            
            if ($result['success']) {
                $results['success']++;
                $processed_items[] = $index; // Mark for removal
            } else {
                $results['failed']++;
                $queue[$index]['attempts']++;
                $queue[$index]['error'] = $result['error'];
                $results['errors'][] = $result['error'];
            }
        }
        
        // Remove processed items
        foreach (array_reverse($processed_items) as $index) {
            unset($queue[$index]);
        }
        
        update_option('swap_fallback_queue', array_values($queue));
        
        return $results;
    }
    
    /**
     * Process individual queue item
     * 
     * @since 1.0.16
     * @param object $item Queue item
     * @return array Processing result
     */
    private function process_queue_item($item) {
        // Initialize Archive API
        if (!class_exists('SWAP_Archive_API')) {
            return array(
                'success' => false,
                'error' => 'Archive API not available'
            );
        }
        
        $archive_api = new SWAP_Archive_API();
        
        // Submit URL
        $result = $archive_api->submit_url($item->url);
        
        return array(
            'success' => $result['success'],
            'archive_url' => $result['archive_url'] ?? '',
            'error' => $result['error'] ?? 'Unknown error'
        );
    }
    
    /**
     * Update item status
     * 
     * @since 1.0.16
     * @param int $id Item ID
     * @param string $status New status
     * @param string $archive_url Archive URL (optional)
     * @return bool True if updated successfully
     */
    private function update_item_status($id, $status, $archive_url = '') {
        $data = array(
            'status' => $status,
            'processed_at' => current_time('mysql')
        );
        
        if (!empty($archive_url)) {
            $data['archive_url'] = $archive_url;
        }
        
        return $this->wpdb->update(
            $this->table_name,
            $data,
            array('id' => $id),
            array('%s', '%s', '%s'),
            array('%d')
        ) !== false;
    }
    
    /**
     * Increment attempts counter
     * 
     * @since 1.0.16
     * @param int $id Item ID
     * @param string $error Error message
     * @return bool True if updated successfully
     */
    private function increment_attempts($id, $error) {
        return $this->wpdb->update(
            $this->table_name,
            array(
                'attempts' => $this->wpdb->get_var($this->wpdb->prepare(
                    "SELECT attempts FROM {$this->table_name} WHERE id = %d",
                    $id
                )) + 1,
                'error_message' => $error
            ),
            array('id' => $id),
            array('%d', '%s'),
            array('%d')
        ) !== false;
    }
    
    /**
     * Get queue statistics
     * 
     * @since 1.0.16
     * @return array Queue statistics
     */
    public function get_queue_stats() {
        if (!$this->table_exists()) {
            // Fallback to options
            $queue = get_option('swap_fallback_queue', array());
            return array(
                'total' => count($queue),
                'pending' => count(array_filter($queue, function($item) {
                    return $item['status'] === 'pending';
                })),
                'completed' => 0,
                'failed' => 0
            );
        }
        
        $stats = $this->wpdb->get_results(
            "SELECT status, COUNT(*) as count 
             FROM {$this->table_name} 
             GROUP BY status"
        );
        
        $result = array(
            'total' => 0,
            'pending' => 0,
            'completed' => 0,
            'failed' => 0
        );
        
        foreach ($stats as $stat) {
            $result['total'] += $stat->count;
            $result[$stat->status] = $stat->count;
        }
        
        return $result;
    }
    
    /**
     * Log error message
     * 
     * @since 1.0.16
     * @param string $message Error message
     * @return void
     */
    private function log_error($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('SWAP Queue Error: ' . $message);
        }
    }
    
    /**
     * Log success message
     * 
     * @since 1.0.16
     * @param string $message Success message
     * @return void
     */
    private function log_success($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('SWAP Queue Success: ' . $message);
        }
    }
}
