<?php
/**
 * Error Recovery System
 * 
 * Based on MickeyKay/archiver patterns for comprehensive error handling
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
 * Error Recovery System Class
 * 
 * @since 1.0.16
 */
class SWAP_Error_Recovery {
    
    /**
     * Error log table name
     * 
     * @var string
     */
    private $log_table;
    
    /**
     * Recovery strategies
     * 
     * @var array
     */
    private $strategies = array();
    
    /**
     * Constructor
     * 
     * @since 1.0.16
     */
    public function __construct() {
        global $wpdb;
        $this->log_table = $wpdb->prefix . 'swap_error_log';
        
        $this->init_hooks();
        $this->init_strategies();
        $this->ensure_log_table();
    }
    
    /**
     * Initialize hooks
     * 
     * @since 1.0.16
     * @return void
     */
    private function init_hooks() {
        // Add error recovery to admin bar
        add_action('admin_bar_menu', array($this, 'add_error_recovery_menu'), 300);
        
        // Schedule error cleanup
        add_action('init', array($this, 'schedule_error_cleanup'));
        add_action('swap_error_cleanup', array($this, 'cleanup_old_errors'));
        
        // Handle error recovery requests
        add_action('wp_ajax_swap_recover_errors', array($this, 'ajax_recover_errors'));
    }
    
    /**
     * Initialize recovery strategies
     * 
     * @since 1.0.16
     * @return void
     */
    private function init_strategies() {
        $this->strategies = array(
            'database_table_missing' => array(
                'detect' => array($this, 'detect_missing_table'),
                'recover' => array($this, 'recover_missing_table')
            ),
            'network_connectivity' => array(
                'detect' => array($this, 'detect_network_issues'),
                'recover' => array($this, 'recover_network_issues')
            ),
            'api_credentials' => array(
                'detect' => array($this, 'detect_credential_issues'),
                'recover' => array($this, 'recover_credential_issues')
            ),
            'queue_processing' => array(
                'detect' => array($this, 'detect_queue_issues'),
                'recover' => array($this, 'recover_queue_issues')
            )
        );
    }
    
    /**
     * Ensure error log table exists
     * 
     * @since 1.0.16
     * @return void
     */
    private function ensure_log_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->log_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            error_type varchar(100) NOT NULL,
            error_message text NOT NULL,
            error_context text,
            recovery_attempted tinyint(1) NOT NULL DEFAULT 0,
            recovery_successful tinyint(1) NOT NULL DEFAULT 0,
            recovery_method varchar(100),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            recovered_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY error_type (error_type),
            KEY recovery_attempted (recovery_attempted),
            KEY created_at (created_at)
        ) {$charset_collate};";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Log error with automatic recovery attempt
     * 
     * @since 1.0.16
     * @param string $error_type Type of error
     * @param string $error_message Error message
     * @param array $context Additional context
     * @return bool True if recovery was attempted
     */
    public function log_error($error_type, $error_message, $context = array()) {
        global $wpdb;
        
        // Log the error
        $result = $wpdb->insert(
            $this->log_table,
            array(
                'error_type' => $error_type,
                'error_message' => $error_message,
                'error_context' => json_encode($context),
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            return false;
        }
        
        $error_id = $wpdb->insert_id;
        
        // Attempt automatic recovery
        $recovery_result = $this->attempt_recovery($error_type, $error_message, $context);
        
        // Update log with recovery result
        $wpdb->update(
            $this->log_table,
            array(
                'recovery_attempted' => 1,
                'recovery_successful' => $recovery_result['success'] ? 1 : 0,
                'recovery_method' => $recovery_result['method'],
                'recovered_at' => $recovery_result['success'] ? current_time('mysql') : null
            ),
            array('id' => $error_id),
            array('%d', '%d', '%s', '%s'),
            array('%d')
        );
        
        return $recovery_result['success'];
    }
    
    /**
     * Attempt automatic recovery
     * 
     * @since 1.0.16
     * @param string $error_type Type of error
     * @param string $error_message Error message
     * @param array $context Additional context
     * @return array Recovery result
     */
    private function attempt_recovery($error_type, $error_message, $context) {
        foreach ($this->strategies as $strategy_name => $strategy) {
            if (call_user_func($strategy['detect'], $error_type, $error_message, $context)) {
                $result = call_user_func($strategy['recover'], $error_type, $error_message, $context);
                
                if ($result['success']) {
                    return array(
                        'success' => true,
                        'method' => $strategy_name,
                        'message' => $result['message']
                    );
                }
            }
        }
        
        return array(
            'success' => false,
            'method' => 'none',
            'message' => 'No recovery strategy available'
        );
    }
    
    /**
     * Detect missing table errors
     * 
     * @since 1.0.16
     * @param string $error_type Type of error
     * @param string $error_message Error message
     * @param array $context Additional context
     * @return bool True if this is a missing table error
     */
    private function detect_missing_table($error_type, $error_message, $context) {
        return strpos($error_message, 'no such table') !== false ||
               strpos($error_message, 'Table') !== false && strpos($error_message, "doesn't exist") !== false;
    }
    
    /**
     * Recover from missing table errors
     * 
     * @since 1.0.16
     * @param string $error_type Type of error
     * @param string $error_message Error message
     * @param array $context Additional context
     * @return array Recovery result
     */
    private function recover_missing_table($error_type, $error_message, $context) {
        try {
            // Try to create missing tables
            if (class_exists('SWAP_Database_Migration')) {
                $migration = new SWAP_Database_Migration();
                $migration->maybe_migrate();
                
                return array(
                    'success' => true,
                    'message' => 'Database tables recreated successfully'
                );
            }
            
            return array(
                'success' => false,
                'message' => 'Database migration class not available'
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Database recovery failed: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Detect network connectivity issues
     * 
     * @since 1.0.16
     * @param string $error_type Type of error
     * @param string $error_message Error message
     * @param array $context Additional context
     * @return bool True if this is a network issue
     */
    private function detect_network_issues($error_type, $error_message, $context) {
        return strpos($error_message, 'Could not resolve host') !== false ||
               strpos($error_message, 'Connection timed out') !== false ||
               strpos($error_message, 'Network is unreachable') !== false;
    }
    
    /**
     * Recover from network connectivity issues
     * 
     * @since 1.0.16
     * @param string $error_type Type of error
     * @param string $error_message Error message
     * @param array $context Additional context
     * @return array Recovery result
     */
    private function recover_network_issues($error_type, $error_message, $context) {
        // Clear any cached network status
        delete_transient('swap_network_status');
        
        // Test connectivity
        if (class_exists('SWAP_Network_Manager')) {
            $network_manager = new SWAP_Network_Manager();
            $status = $network_manager->test_archive_connectivity();
            
            if ($status['overall_status'] !== 'poor') {
                return array(
                    'success' => true,
                    'message' => 'Network connectivity restored'
                );
            }
        }
        
        return array(
            'success' => false,
            'message' => 'Network connectivity still poor'
        );
    }
    
    /**
     * Detect API credential issues
     * 
     * @since 1.0.16
     * @param string $error_type Type of error
     * @param string $error_message Error message
     * @param array $context Additional context
     * @return bool True if this is a credential issue
     */
    private function detect_credential_issues($error_type, $error_message, $context) {
        return strpos($error_message, 'Unauthorized') !== false ||
               strpos($error_message, 'Invalid credentials') !== false ||
               strpos($error_message, 'Authentication failed') !== false;
    }
    
    /**
     * Recover from API credential issues
     * 
     * @since 1.0.16
     * @param string $error_type Type of error
     * @param string $error_message Error message
     * @param array $context Additional context
     * @return array Recovery result
     */
    private function recover_credential_issues($error_type, $error_message, $context) {
        // Clear cached credentials
        delete_transient('swap_api_credentials');
        
        // Test with fallback to simple submission
        if (class_exists('SWAP_Archive_API')) {
            $archive_api = new SWAP_Archive_API();
            
            // Test with a simple URL
            $result = $archive_api->submit_url('https://example.com');
            
            if ($result['success']) {
                return array(
                    'success' => true,
                    'message' => 'API credentials validated, using fallback method'
                );
            }
        }
        
        return array(
            'success' => false,
            'message' => 'API credentials still invalid'
        );
    }
    
    /**
     * Detect queue processing issues
     * 
     * @since 1.0.16
     * @param string $error_type Type of error
     * @param string $error_message Error message
     * @param array $context Additional context
     * @return bool True if this is a queue issue
     */
    private function detect_queue_issues($error_type, $error_message, $context) {
        return strpos($error_message, 'queue') !== false ||
               strpos($error_message, 'processing') !== false;
    }
    
    /**
     * Recover from queue processing issues
     * 
     * @since 1.0.16
     * @param string $error_type Type of error
     * @param string $error_message Error message
     * @param array $context Additional context
     * @return array Recovery result
     */
    private function recover_queue_issues($error_type, $error_message, $context) {
        try {
            // Clear any stuck queue items
            if (class_exists('SWAP_Enhanced_Queue')) {
                $queue = new SWAP_Enhanced_Queue();
                $stats = $queue->get_queue_stats();
                
                // Reset stuck items
                global $wpdb;
                $wpdb->query(
                    "UPDATE {$wpdb->prefix}swap_archive_queue 
                     SET status = 'pending', attempts = 0 
                     WHERE status = 'processing' AND processed_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)"
                );
                
                return array(
                    'success' => true,
                    'message' => 'Queue processing reset successfully'
                );
            }
            
            return array(
                'success' => false,
                'message' => 'Queue management class not available'
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Queue recovery failed: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Add error recovery menu to admin bar
     * 
     * @since 1.0.16
     * @param WP_Admin_Bar $wp_admin_bar Admin bar instance
     * @return void
     */
    public function add_error_recovery_menu($wp_admin_bar) {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $error_count = $this->get_recent_error_count();
        
        if ($error_count > 0) {
            $wp_admin_bar->add_node(array(
                'id'    => 'swap-error-recovery',
                'title' => '🔧 ' . $error_count . ' Errors',
                'href'  => '#',
                'meta'  => array(
                    'onclick' => 'swapRecoverErrors(); return false;',
                    'title' => 'Recover from recent errors'
                )
            ));
        }
    }
    
    /**
     * Get recent error count
     * 
     * @since 1.0.16
     * @return int Number of recent errors
     */
    private function get_recent_error_count() {
        global $wpdb;
        
        $count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->log_table} 
             WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
             AND recovery_successful = 0"
        );
        
        return intval($count);
    }
    
    /**
     * AJAX handler for error recovery
     * 
     * @since 1.0.16
     * @return void
     */
    public function ajax_recover_errors() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'swap_recover_errors')) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $recovered = $this->recover_all_errors();
        
        wp_send_json_success(array(
            'recovered' => $recovered,
            'message' => "Recovered {$recovered} errors"
        ));
    }
    
    /**
     * Recover all recent errors
     * 
     * @since 1.0.16
     * @return int Number of errors recovered
     */
    private function recover_all_errors() {
        global $wpdb;
        
        $errors = $wpdb->get_results(
            "SELECT * FROM {$this->log_table} 
             WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
             AND recovery_successful = 0"
        );
        
        $recovered = 0;
        
        foreach ($errors as $error) {
            $context = json_decode($error->error_context, true);
            $result = $this->attempt_recovery($error->error_type, $error->error_message, $context);
            
            if ($result['success']) {
                $recovered++;
            }
        }
        
        return $recovered;
    }
    
    /**
     * Schedule error cleanup
     * 
     * @since 1.0.16
     * @return void
     */
    public function schedule_error_cleanup() {
        if (!wp_next_scheduled('swap_error_cleanup')) {
            wp_schedule_event(time(), 'daily', 'swap_error_cleanup');
        }
    }
    
    /**
     * Cleanup old errors
     * 
     * @since 1.0.16
     * @return void
     */
    public function cleanup_old_errors() {
        global $wpdb;
        
        // Delete errors older than 30 days
        $wpdb->query(
            "DELETE FROM {$this->log_table} 
             WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );
    }
    
    /**
     * Get error statistics
     * 
     * @since 1.0.16
     * @return array Error statistics
     */
    public function get_error_stats() {
        global $wpdb;
        
        $stats = $wpdb->get_results(
            "SELECT 
                error_type,
                COUNT(*) as total_errors,
                SUM(recovery_attempted) as recovery_attempts,
                SUM(recovery_successful) as successful_recoveries
             FROM {$this->log_table} 
             WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
             GROUP BY error_type"
        );
        
        return $stats;
    }
}
