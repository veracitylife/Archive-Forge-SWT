<?php
/**
 * Enhanced Submission Verification
 * 
 * Based on MickeyKay/archiver patterns for reliable verification
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
 * Enhanced Submission Verification Class
 * 
 * @since 1.0.16
 */
class SWAP_Submission_Verifier {
    
    /**
     * Archive API instance
     * 
     * @var SWAP_Archive_API
     */
    private $archive_api;
    
    /**
     * Verification cache
     * 
     * @var array
     */
    private $cache = array();
    
    /**
     * Constructor
     * 
     * @since 1.0.16
     * @param SWAP_Archive_API $archive_api Archive API instance
     */
    public function __construct($archive_api) {
        $this->archive_api = $archive_api;
    }
    
    /**
     * Verify submission with multiple methods
     * 
     * @since 1.0.16
     * @param string $url URL to verify
     * @param int $timeout Timeout in seconds
     * @return array Verification result
     */
    public function verify_submission($url, $timeout = 30) {
        // Check cache first
        $cache_key = md5($url);
        if (isset($this->cache[$cache_key])) {
            $cached = $this->cache[$cache_key];
            if (time() - $cached['timestamp'] < 300) { // 5 minute cache
                return $cached['result'];
            }
        }
        
        $result = array(
            'success' => false,
            'is_archived' => false,
            'archive_url' => '',
            'verification_method' => '',
            'error' => ''
        );
        
        // Try multiple verification methods
        $methods = array(
            'wayback_api' => array($this, 'verify_via_wayback_api'),
            'availability_api' => array($this, 'verify_via_availability_api'),
            'direct_check' => array($this, 'verify_via_direct_check')
        );
        
        foreach ($methods as $method_name => $method) {
            try {
                $method_result = call_user_func($method, $url, $timeout);
                
                if ($method_result['success']) {
                    $result = array_merge($result, $method_result);
                    $result['verification_method'] = $method_name;
                    break;
                }
                
                // Log method failure but continue to next method
                $this->log_error("Verification method {$method_name} failed: " . $method_result['error']);
                
            } catch (Exception $e) {
                $this->log_error("Verification method {$method_name} exception: " . $e->getMessage());
                continue;
            }
        }
        
        // Cache result
        $this->cache[$cache_key] = array(
            'result' => $result,
            'timestamp' => time()
        );
        
        return $result;
    }
    
    /**
     * Verify via Wayback Machine API
     * 
     * @since 1.0.16
     * @param string $url URL to verify
     * @param int $timeout Timeout in seconds
     * @return array Verification result
     */
    private function verify_via_wayback_api($url, $timeout) {
        $api_url = 'https://web.archive.org/wayback/available';
        $params = array(
            'url' => $url,
            'timestamp' => date('YmdHis')
        );
        
        $response = wp_remote_get(
            $api_url . '?' . http_build_query($params),
            array(
                'timeout' => $timeout,
                'headers' => array(
                    'User-Agent' => 'SpunWebArchiveForge/' . SWAP_VERSION
                )
            )
        );
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'error' => 'Wayback API request failed: ' . $response->get_error_message()
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return array(
                'success' => false,
                'error' => 'Invalid JSON response from Wayback API'
            );
        }
        
        if (isset($data['archived_snapshots']['closest'])) {
            $snapshot = $data['archived_snapshots']['closest'];
            return array(
                'success' => true,
                'is_archived' => true,
                'archive_url' => $snapshot['url'],
                'last_archived' => $snapshot['timestamp']
            );
        }
        
        return array(
            'success' => true,
            'is_archived' => false,
            'archive_url' => '',
            'last_archived' => null
        );
    }
    
    /**
     * Verify via Archive.org Availability API
     * 
     * @since 1.0.16
     * @param string $url URL to verify
     * @param int $timeout Timeout in seconds
     * @return array Verification result
     */
    private function verify_via_availability_api($url, $timeout) {
        $api_url = 'https://archive.org/wayback/available';
        $params = array(
            'url' => $url
        );
        
        $response = wp_remote_get(
            $api_url . '?' . http_build_query($params),
            array(
                'timeout' => $timeout,
                'headers' => array(
                    'User-Agent' => 'SpunWebArchiveForge/' . SWAP_VERSION
                )
            )
        );
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'error' => 'Availability API request failed: ' . $response->get_error_message()
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return array(
                'success' => false,
                'error' => 'Invalid JSON response from Availability API'
            );
        }
        
        if (isset($data['archived_snapshots']['closest'])) {
            $snapshot = $data['archived_snapshots']['closest'];
            return array(
                'success' => true,
                'is_archived' => true,
                'archive_url' => $snapshot['url'],
                'last_archived' => $snapshot['timestamp']
            );
        }
        
        return array(
            'success' => true,
            'is_archived' => false,
            'archive_url' => '',
            'last_archived' => null
        );
    }
    
    /**
     * Verify via direct Wayback Machine check
     * 
     * @since 1.0.16
     * @param string $url URL to verify
     * @param int $timeout Timeout in seconds
     * @return array Verification result
     */
    private function verify_via_direct_check($url, $timeout) {
        // Try to access the URL via Wayback Machine
        $wayback_url = 'https://web.archive.org/web/' . $url;
        
        $response = wp_remote_head(
            $wayback_url,
            array(
                'timeout' => $timeout,
                'headers' => array(
                    'User-Agent' => 'SpunWebArchiveForge/' . SWAP_VERSION
                )
            )
        );
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'error' => 'Direct check request failed: ' . $response->get_error_message()
            );
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code === 200) {
            // Check if response contains Wayback Machine indicators
            $body = wp_remote_retrieve_body($response);
            
            if (strpos($body, 'wayback') !== false || strpos($body, 'archive.org') !== false) {
                return array(
                    'success' => true,
                    'is_archived' => true,
                    'archive_url' => $wayback_url,
                    'last_archived' => null
                );
            }
        }
        
        return array(
            'success' => true,
            'is_archived' => false,
            'archive_url' => '',
            'last_archived' => null
        );
    }
    
    /**
     * Verify submission with retry logic
     * 
     * @since 1.0.16
     * @param string $url URL to verify
     * @param int $max_retries Maximum number of retries
     * @param int $retry_delay Delay between retries in seconds
     * @return array Verification result
     */
    public function verify_with_retry($url, $max_retries = 3, $retry_delay = 5) {
        $attempts = 0;
        $last_error = '';
        
        while ($attempts < $max_retries) {
            $result = $this->verify_submission($url);
            
            if ($result['success']) {
                return $result;
            }
            
            $last_error = $result['error'];
            $attempts++;
            
            if ($attempts < $max_retries) {
                sleep($retry_delay);
            }
        }
        
        return array(
            'success' => false,
            'is_archived' => false,
            'archive_url' => '',
            'verification_method' => 'retry_failed',
            'error' => "Verification failed after {$max_retries} attempts. Last error: {$last_error}"
        );
    }
    
    /**
     * Get verification statistics
     * 
     * @since 1.0.16
     * @return array Verification statistics
     */
    public function get_verification_stats() {
        $stats = get_option('swap_verification_stats', array(
            'total_verifications' => 0,
            'successful_verifications' => 0,
            'failed_verifications' => 0,
            'method_usage' => array()
        ));
        
        return $stats;
    }
    
    /**
     * Update verification statistics
     * 
     * @since 1.0.16
     * @param string $method Verification method used
     * @param bool $success Whether verification was successful
     * @return void
     */
    private function update_stats($method, $success) {
        $stats = $this->get_verification_stats();
        
        $stats['total_verifications']++;
        
        if ($success) {
            $stats['successful_verifications']++;
        } else {
            $stats['failed_verifications']++;
        }
        
        if (!isset($stats['method_usage'][$method])) {
            $stats['method_usage'][$method] = 0;
        }
        $stats['method_usage'][$method]++;
        
        update_option('swap_verification_stats', $stats);
    }
    
    /**
     * Clear verification cache
     * 
     * @since 1.0.16
     * @return void
     */
    public function clear_cache() {
        $this->cache = array();
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
            error_log('SWAP Verification Error: ' . $message);
        }
    }
}
