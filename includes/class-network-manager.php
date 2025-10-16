<?php
/**
 * Enhanced Network Connectivity
 * 
 * Based on MickeyKay/archiver patterns for robust network handling
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
 * Enhanced Network Connectivity Class
 * 
 * @since 1.0.16
 */
class SWAP_Network_Manager {
    
    /**
     * Connection timeout
     * 
     * @var int
     */
    private $timeout = 30;
    
    /**
     * Maximum retries
     * 
     * @var int
     */
    private $max_retries = 3;
    
    /**
     * Retry delay
     * 
     * @var int
     */
    private $retry_delay = 5;
    
    /**
     * Constructor
     * 
     * @since 1.0.16
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     * 
     * @since 1.0.16
     * @return void
     */
    private function init_hooks() {
        // Add network health check to admin bar
        add_action('admin_bar_menu', array($this, 'add_network_status'), 200);
        
        // Schedule network health checks
        add_action('init', array($this, 'schedule_health_checks'));
        add_action('swap_network_health_check', array($this, 'perform_health_check'));
    }
    
    /**
     * Test Archive.org connectivity
     * 
     * @since 1.0.16
     * @return array Connectivity test result
     */
    public function test_archive_connectivity() {
        $endpoints = array(
            'wayback_save' => 'https://web.archive.org/save/',
            'wayback_available' => 'https://web.archive.org/wayback/available',
            'archive_s3' => 'https://s3.us.archive.org/',
            'archive_org' => 'https://archive.org/'
        );
        
        $results = array(
            'overall_status' => 'unknown',
            'endpoints' => array(),
            'recommendations' => array()
        );
        
        $successful_endpoints = 0;
        $total_endpoints = count($endpoints);
        
        foreach ($endpoints as $name => $url) {
            $result = $this->test_endpoint($url);
            $results['endpoints'][$name] = $result;
            
            if ($result['success']) {
                $successful_endpoints++;
            }
        }
        
        // Determine overall status
        if ($successful_endpoints === $total_endpoints) {
            $results['overall_status'] = 'excellent';
        } elseif ($successful_endpoints >= $total_endpoints * 0.75) {
            $results['overall_status'] = 'good';
        } elseif ($successful_endpoints >= $total_endpoints * 0.5) {
            $results['overall_status'] = 'fair';
        } else {
            $results['overall_status'] = 'poor';
        }
        
        // Generate recommendations
        $results['recommendations'] = $this->generate_recommendations($results);
        
        return $results;
    }
    
    /**
     * Test individual endpoint
     * 
     * @since 1.0.16
     * @param string $url URL to test
     * @return array Test result
     */
    private function test_endpoint($url) {
        $start_time = microtime(true);
        
        $response = wp_remote_get(
            $url,
            array(
                'timeout' => $this->timeout,
                'headers' => array(
                    'User-Agent' => 'SpunWebArchiveForge/' . SWAP_VERSION
                ),
                'sslverify' => true
            )
        );
        
        $end_time = microtime(true);
        $response_time = round(($end_time - $start_time) * 1000, 2); // Convert to milliseconds
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'error' => $response->get_error_message(),
                'response_time' => $response_time,
                'status_code' => null
            );
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $success = $status_code >= 200 && $status_code < 400;
        
        return array(
            'success' => $success,
            'error' => $success ? null : "HTTP {$status_code}",
            'response_time' => $response_time,
            'status_code' => $status_code
        );
    }
    
    /**
     * Generate recommendations based on test results
     * 
     * @since 1.0.16
     * @param array $results Test results
     * @return array Recommendations
     */
    private function generate_recommendations($results) {
        $recommendations = array();
        
        foreach ($results['endpoints'] as $name => $result) {
            if (!$result['success']) {
                switch ($name) {
                    case 'wayback_save':
                        $recommendations[] = 'Wayback Machine Save API is unreachable. Check firewall settings and VPN configuration.';
                        break;
                    case 'wayback_available':
                        $recommendations[] = 'Wayback Machine Availability API is unreachable. This may affect archive verification.';
                        break;
                    case 'archive_s3':
                        $recommendations[] = 'Archive.org S3 API is unreachable. This may affect authenticated submissions.';
                        break;
                    case 'archive_org':
                        $recommendations[] = 'Archive.org main site is unreachable. Check internet connectivity.';
                        break;
                }
            }
            
            if ($result['response_time'] > 10000) { // 10 seconds
                $recommendations[] = "Slow response time ({$result['response_time']}ms) for {$name}. Consider increasing timeout settings.";
            }
        }
        
        if ($results['overall_status'] === 'poor') {
            $recommendations[] = 'Overall connectivity is poor. Consider using a VPN or checking network configuration.';
        }
        
        return $recommendations;
    }
    
    /**
     * Make request with retry logic
     * 
     * @since 1.0.16
     * @param string $url URL to request
     * @param array $args Request arguments
     * @return array|WP_Error Response or error
     */
    public function request_with_retry($url, $args = array()) {
        $attempts = 0;
        $last_error = null;
        
        // Set default arguments
        $default_args = array(
            'timeout' => $this->timeout,
            'headers' => array(
                'User-Agent' => 'SpunWebArchiveForge/' . SWAP_VERSION
            ),
            'sslverify' => true
        );
        
        $args = wp_parse_args($args, $default_args);
        
        while ($attempts < $this->max_retries) {
            $response = wp_remote_get($url, $args);
            
            if (!is_wp_error($response)) {
                $status_code = wp_remote_retrieve_response_code($response);
                
                if ($status_code >= 200 && $status_code < 400) {
                    return $response;
                }
                
                $last_error = "HTTP {$status_code}";
            } else {
                $last_error = $response->get_error_message();
            }
            
            $attempts++;
            
            if ($attempts < $this->max_retries) {
                sleep($this->retry_delay);
            }
        }
        
        return new WP_Error(
            'max_retries_exceeded',
            "Request failed after {$this->max_retries} attempts. Last error: {$last_error}"
        );
    }
    
    /**
     * Add network status to admin bar
     * 
     * @since 1.0.16
     * @param WP_Admin_Bar $wp_admin_bar Admin bar instance
     * @return void
     */
    public function add_network_status($wp_admin_bar) {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $status = get_transient('swap_network_status');
        
        if ($status === false) {
            $status = $this->test_archive_connectivity();
            set_transient('swap_network_status', $status, 300); // 5 minutes
        }
        
        $icon = $this->get_status_icon($status['overall_status']);
        $title = "Network: {$status['overall_status']}";
        
        $wp_admin_bar->add_node(array(
            'id'    => 'swap-network-status',
            'title' => $icon . ' ' . $title,
            'href'  => admin_url('admin.php?page=spun-web-archive-forge'),
            'meta'  => array(
                'title' => 'Archive.org Network Connectivity Status'
            )
        ));
        
        // Add refresh option
        $wp_admin_bar->add_node(array(
            'id'     => 'swap-refresh-network',
            'parent' => 'swap-network-status',
            'title'  => '🔄 Refresh Status',
            'href'   => wp_nonce_url(
                add_query_arg('swap_refresh_network', '1', admin_url('admin.php?page=spun-web-archive-forge')),
                'swap_refresh_network'
            ),
            'meta'   => array(
                'title' => 'Refresh network connectivity status'
            )
        ));
    }
    
    /**
     * Get status icon based on status
     * 
     * @since 1.0.16
     * @param string $status Status level
     * @return string Status icon
     */
    private function get_status_icon($status) {
        switch ($status) {
            case 'excellent':
                return '🟢';
            case 'good':
                return '🟡';
            case 'fair':
                return '🟠';
            case 'poor':
                return '🔴';
            default:
                return '⚪';
        }
    }
    
    /**
     * Schedule network health checks
     * 
     * @since 1.0.16
     * @return void
     */
    public function schedule_health_checks() {
        if (!wp_next_scheduled('swap_network_health_check')) {
            wp_schedule_event(time(), 'hourly', 'swap_network_health_check');
        }
    }
    
    /**
     * Perform network health check
     * 
     * @since 1.0.16
     * @return void
     */
    public function perform_health_check() {
        $status = $this->test_archive_connectivity();
        set_transient('swap_network_status', $status, 3600); // 1 hour
        
        // Log status if poor
        if ($status['overall_status'] === 'poor') {
            $this->log_error('Network connectivity is poor: ' . implode(', ', $status['recommendations']));
        }
    }
    
    /**
     * Get network statistics
     * 
     * @since 1.0.16
     * @return array Network statistics
     */
    public function get_network_stats() {
        $stats = get_option('swap_network_stats', array(
            'total_requests' => 0,
            'successful_requests' => 0,
            'failed_requests' => 0,
            'average_response_time' => 0,
            'last_check' => null
        ));
        
        return $stats;
    }
    
    /**
     * Update network statistics
     * 
     * @since 1.0.16
     * @param bool $success Whether request was successful
     * @param float $response_time Response time in milliseconds
     * @return void
     */
    public function update_stats($success, $response_time) {
        $stats = $this->get_network_stats();
        
        $stats['total_requests']++;
        
        if ($success) {
            $stats['successful_requests']++;
        } else {
            $stats['failed_requests']++;
        }
        
        // Update average response time
        $total_time = $stats['average_response_time'] * ($stats['total_requests'] - 1);
        $stats['average_response_time'] = ($total_time + $response_time) / $stats['total_requests'];
        
        $stats['last_check'] = current_time('mysql');
        
        update_option('swap_network_stats', $stats);
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
            error_log('SWAP Network Error: ' . $message);
        }
    }
}
