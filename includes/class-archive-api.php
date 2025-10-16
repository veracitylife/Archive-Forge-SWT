<?php
/**
 * Archive.org API Handler
 * 
 * Handles communication with Archive.org S3 API for submitting URLs
 * to the Wayback Machine with modern PHP patterns and comprehensive error handling.
 * 
 * @package SpunWebArchiveElite
 * @subpackage Includes
 * @author Ryan Dickie Thompson
 * @copyright 2024 Spun Web Technology
 * @license GPL-2.0-or-later
 * @since 0.7.0
 * @version 0.7.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Archive.org API Handler Class
 * 
 * @since 0.7.0
 */
class SWAP_Archive_API {
    
    /**
     * API credentials
     * 
     * @since 0.7.0
     * @var string
     */
    private $api_key = '';
    
    /**
     * API secret
     * 
     * @since 0.7.0
     * @var string
     */
    private $api_secret = '';
    
    /**
     * API timeout in seconds
     * 
     * @since 0.7.0
     * @var int
     */
    private $timeout = 60;
    
    /**
     * Maximum retry attempts
     * 
     * @since 0.7.0
     * @var int
     */
    private $max_retries = 3;
    
    /**
     * API endpoints
     * 
     * @since 0.7.0
     * @var array<string, string>
     */
    private $endpoints = array(
        'save' => 'https://web.archive.org/save/',
        'availability' => 'https://archive.org/wayback/available',
        's3_test' => 'https://s3.us.archive.org/'
    );
    
    /**
     * Constructor
     * 
     * @since 0.7.0
     * @param array $config Configuration array with api_key, api_secret, timeout, max_retries
     */
    public function __construct(array $config = array()) {
        $this->load_configuration($config);
        
        // Apply centralized credentials (with decryption support) if still missing
        if ((empty($this->api_key) || empty($this->api_secret)) && class_exists('SWAP_Credentials_Page')) {
            $key = SWAP_Credentials_Page::get_access_key();
            $secret = SWAP_Credentials_Page::get_secret_key();
            if (!empty($key)) {
                $this->api_key = $key;
            }
            if (!empty($secret)) {
                $this->api_secret = $secret;
            }
        }
        
        $this->validate_configuration();
    }
    
    /**
     * Load configuration from array or WordPress options
     * 
     * @since 0.7.0
     * @param array $config Configuration array
     * @return void
     */
    private function load_configuration(array $config): void {
        // Load from provided config
        $this->api_key = $config['api_key'] ?? '';
        $this->api_secret = $config['api_secret'] ?? '';
        $this->timeout = (int) ($config['timeout'] ?? 60);
        $this->max_retries = (int) ($config['max_retries'] ?? 3);
        
        // Load from WordPress options if not provided
        if (empty($this->api_key) || empty($this->api_secret)) {
            $this->load_credentials_from_options();
        }
        
        // Ensure reasonable limits
        $this->timeout = max(10, min(300, $this->timeout));
        $this->max_retries = max(1, min(10, $this->max_retries));
    }
    
    /**
     * Load credentials from WordPress options
     * 
     * @since 0.7.0
     * @return void
     */
    private function load_credentials_from_options(): void {
        // Try new centralized credentials first
        if (class_exists('SWAP_Credentials_Page')) {
            $this->api_key = SWAP_Credentials_Page::get_access_key() ?: $this->api_key;
            $this->api_secret = SWAP_Credentials_Page::get_secret_key() ?: $this->api_secret;
        }
        
        // Fallback to legacy settings
        if (empty($this->api_key) || empty($this->api_secret)) {
            $api_settings = get_option('swap_api_settings', array());
            $this->api_key = $this->api_key ?: ($api_settings['api_key'] ?? '');
            $this->api_secret = $this->api_secret ?: ($api_settings['api_secret'] ?? '');
        }
    }
    
    /**
     * Validate configuration
     * 
     * @since 0.7.0
     * @return void
     * @throws InvalidArgumentException If configuration is invalid
     */
    private function validate_configuration(): void {
        if ($this->timeout < 10 || $this->timeout > 300) {
            throw new InvalidArgumentException('Timeout must be between 10 and 300 seconds');
        }
        
        if ($this->max_retries < 1 || $this->max_retries > 10) {
            throw new InvalidArgumentException('Max retries must be between 1 and 10');
        }
    }
    
    /**
     * Test API connection with comprehensive error handling
     * 
     * @since 0.7.0
     * @param string|null $api_key Optional API key for testing
     * @param string|null $api_secret Optional API secret for testing
     * @param bool $callback_enabled Whether to enable callback tracking
     * @return array{success: bool, message: string, test_id?: string, error_type?: string, response_time?: float}
     */
    public function test_connection(?string $api_key = null, ?string $api_secret = null, bool $callback_enabled = false): array {
        $test_id = $callback_enabled ? uniqid('test_', true) : null;
        $start_time = microtime(true);
        
        // Use provided credentials or instance credentials
        $test_key = $api_key ?: $this->api_key;
        $test_secret = $api_secret ?: $this->api_secret;
        
        if (empty($test_key) || empty($test_secret)) {
            $result = array(
                'success' => false,
                'message' => __('API key and secret are required.', 'spun-web-archive-forge'),
                'test_id' => $test_id,
                'error_type' => 'missing_credentials'
            );
            
            if ($callback_enabled) {
                $this->store_test_result($test_id, $result, array(
                    'error_type' => 'missing_credentials',
                    'response_time' => round((microtime(true) - $start_time) * 1000, 2)
                ));
            }
            
            return $result;
        }
        
        try {
            // Test S3 API connection
            $response = $this->make_s3_test_request($test_key, $test_secret);
            $response_time = round((microtime(true) - $start_time) * 1000, 2);
            
            if ($response['success']) {
                $result = array(
                    'success' => true,
                    'message' => __('API connection successful!', 'spun-web-archive-forge'),
                    'test_id' => $test_id,
                    'response_time' => $response_time
                );
                
                if ($callback_enabled) {
                    $this->store_test_result($test_id, $result, $response['details']);
                    update_option('swap_api_connection_status', 'connected');
                    update_option('swap_api_last_test', current_time('timestamp'));
                }
                
                return $result;
            } else {
                $result = array(
                    'success' => false,
                    'message' => $response['message'],
                    'test_id' => $test_id,
                    'error_type' => $response['error_type'],
                    'response_time' => $response_time
                );
                
                if ($callback_enabled) {
                    $this->store_test_result($test_id, $result, $response['details']);
                }
                
                return $result;
            }
            
        } catch (Exception $e) {
            $response_time = round((microtime(true) - $start_time) * 1000, 2);
            
            $result = array(
                'success' => false,
                'message' => sprintf(__('Connection test failed: %s', 'spun-web-archive-forge'), $e->getMessage()),
                'test_id' => $test_id,
                'error_type' => 'exception',
                'response_time' => $response_time
            );
            
            if ($callback_enabled) {
                $this->store_test_result($test_id, $result, array(
                    'exception' => $e->getMessage(),
                    'response_time' => $response_time
                ));
            }
            
            return $result;
        }
    }
    
    /**
     * Make S3 test request
     * 
     * @since 0.7.0
     * @param string $api_key API key
     * @param string $api_secret API secret
     * @return array{success: bool, message: string, error_type?: string, details: array}
     */
    private function make_s3_test_request(string $api_key, string $api_secret): array {
        $date = gmdate('D, d M Y H:i:s T');
        $string_to_sign = "GET\n\n\n{$date}\n/";
        $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $api_secret, true));
        
        $headers = array(
            'Date' => $date,
            'Authorization' => 'AWS ' . $api_key . ':' . $signature,
            'User-Agent' => 'SpunWebArchiveForge/' . SWAP_VERSION
        );
        
        $response = wp_remote_get($this->endpoints['s3_test'], array(
            'headers' => $headers,
            'timeout' => 15,
            'sslverify' => true
        ));
        
        if (is_wp_error($response)) {
            return $this->handle_wp_error($response);
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        $details = array(
            'response_code' => $response_code,
            'endpoint' => $this->endpoints['s3_test'],
            'headers' => $headers,
            'response_body_length' => strlen($response_body)
        );
        
        if ($response_code === 200) {
            return array(
                'success' => true,
                'message' => __('API connection successful!', 'spun-web-archive-forge'),
                'details' => $details
            );
        } elseif ($response_code === 403) {
            return array(
                'success' => false,
                'message' => __('Authentication failed. Please check your API credentials.', 'spun-web-archive-forge'),
                'error_type' => 'authentication_failed',
                'details' => $details
            );
        } else {
            return array(
                'success' => false,
                'message' => sprintf(__('API test failed with response code: %d', 'spun-web-archive-forge'), $response_code),
                'error_type' => 'api_error',
                'details' => $details
            );
        }
    }
    
    /**
     * Handle WordPress HTTP API errors
     * 
     * @since 0.7.0
     * @param WP_Error $error WordPress error object
     * @return array{success: bool, message: string, error_type: string, details: array}
     */
    private function handle_wp_error(WP_Error $error): array {
        $error_code = $error->get_error_code();
        $error_message = $error->get_error_message();
        
        $error_mappings = array(
            'timeout' => array(
                'message' => __('Archive.org connection timed out. The site may be temporarily unavailable. Please try again later.', 'spun-web-archive-forge'),
                'type' => 'timeout'
            ),
            'dns' => array(
                'message' => __('Archive.org cannot be reached. Please check your internet connection and try again.', 'spun-web-archive-forge'),
                'type' => 'dns_failure'
            ),
            'connect' => array(
                'message' => __('Cannot connect to Archive.org. The site may be temporarily unavailable. Please try again later.', 'spun-web-archive-forge'),
                'type' => 'connection_refused'
            ),
            'ssl' => array(
                'message' => __('SSL/Certificate error connecting to Archive.org. Please check your server configuration.', 'spun-web-archive-forge'),
                'type' => 'ssl_error'
            )
        );
        
        foreach ($error_mappings as $key => $mapping) {
            if (strpos($error_code, $key) !== false || strpos($error_message, $key) !== false) {
                return array(
                    'success' => false,
                    'message' => $mapping['message'],
                    'error_type' => $mapping['type'],
                    'details' => array(
                        'error_code' => $error_code,
                        'error_message' => $error_message
                    )
                );
            }
        }
        
        return array(
            'success' => false,
            'message' => sprintf(__('Connection failed: %s', 'spun-web-archive-forge'), $error_message),
            'error_type' => 'wp_error',
            'details' => array(
                'error_code' => $error_code,
                'error_message' => $error_message
            )
        );
    }
    
    /**
     * Store test result for callback tracking
     * 
     * @since 0.7.0
     * @param string $test_id Test ID
     * @param array $result Test result
     * @param array $details Additional details
     * @return void
     */
    private function store_test_result(string $test_id, array $result, array $details): void {
        $test_data = array(
            'test_id' => $test_id,
            'timestamp' => current_time('timestamp'),
            'result' => $result,
            'details' => $details
        );
        
        // Store in transient for 1 hour
        set_transient('swap_api_test_' . $test_id, $test_data, HOUR_IN_SECONDS);
        
        // Log if debug mode is enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('SWAP API Test: ' . wp_json_encode($test_data));
        }
    }
    
    /**
     * Submit URL to Archive.org
     * 
     * @since 0.7.0
     * @param string $url URL to submit
     * @param array $options Submission options
     * @return array{success: bool, archive_url?: string, error?: string, error_type?: string}
     */
    public function submit_url(string $url, array $options = array()): array {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return array(
                'success' => false,
                'error' => __('Invalid URL provided.', 'spun-web-archive-forge'),
                'error_type' => 'invalid_url'
            );
        }
        
        // Try authenticated submission first if credentials are available
        if (!empty($this->api_key) && !empty($this->api_secret)) {
            $result = $this->submit_authenticated($url, $options);
            if ($result['success']) {
                return $result;
            }
        }
        
        // Fallback to simple submission
        return $this->submit_simple($url, $options);
    }
    
    /**
     * Submit URL with authentication
     * 
     * @since 0.7.0
     * @param string $url URL to submit
     * @param array $options Submission options
     * @return array{success: bool, archive_url?: string, error?: string, error_type?: string}
     */
    private function submit_authenticated(string $url, array $options): array {
        $save_endpoint = $this->endpoints['save'] . $url;
        
        $headers = array(
            'Authorization' => 'LOW ' . $this->api_key . ':' . $this->api_secret,
            'User-Agent' => 'SpunWebArchiveForge/' . SWAP_VERSION
        );
        
        return $this->make_submission_request($save_endpoint, $headers, $options);
    }
    
    /**
     * Submit URL without authentication (simple method)
     * 
     * @since 0.7.0
     * @param string $url URL to submit
     * @param array $options Submission options
     * @return array{success: bool, archive_url?: string, error?: string, error_type?: string}
     */
    private function submit_simple(string $url, array $options): array {
        $save_endpoint = $this->endpoints['save'] . $url;
        
        $headers = array(
            'User-Agent' => 'SpunWebArchiveForge/' . SWAP_VERSION
        );
        
        return $this->make_submission_request($save_endpoint, $headers, $options);
    }
    
    /**
     * Make submission request to Archive.org
     * 
     * @since 0.7.0
     * @param string $endpoint API endpoint
     * @param array $headers Request headers
     * @param array $options Request options
     * @return array{success: bool, archive_url?: string, error?: string, error_type?: string}
     */
    private function make_submission_request(string $endpoint, array $headers, array $options): array {
        $response = wp_remote_get($endpoint, array(
            'headers' => $headers,
            'timeout' => $this->timeout,
            'sslverify' => true
        ));
        
        if (is_wp_error($response)) {
            $error_data = $this->handle_wp_error($response);
            return array(
                'success' => false,
                'error' => $error_data['message'],
                'error_type' => $error_data['error_type']
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_headers = wp_remote_retrieve_headers($response);
        
        if ($response_code >= 200 && $response_code < 400) {
            $archive_url = $this->extract_archive_url($response_headers, $endpoint);
            
            return array(
                'success' => true,
                'archive_url' => $archive_url,
                'response_code' => $response_code,
                'method' => !empty($this->api_key) ? 'authenticated' : 'simple'
            );
        }
        
        return array(
            'success' => false,
            'error' => sprintf(__('Archive submission failed with code: %d', 'spun-web-archive-forge'), $response_code),
            'error_type' => 'api_error',
            'response_code' => $response_code
        );
    }
    
    /**
     * Extract archive URL from response headers
     * 
     * @since 0.7.0
     * @param array $headers Response headers
     * @param string $original_url Original submission URL
     * @return string Archive URL
     */
    private function extract_archive_url(array $headers, string $original_url): string {
        // Check for archive URL in response headers
        if (isset($headers['content-location'])) {
            return $headers['content-location'];
        }
        
        if (isset($headers['location'])) {
            return $headers['location'];
        }
        
        // Construct likely archive URL as fallback
        $url = str_replace($this->endpoints['save'], '', $original_url);
        return 'https://web.archive.org/web/' . date('YmdHis') . '/' . $url;
    }
    
    /**
     * Check if URL is available in Wayback Machine
     * 
     * @since 0.7.0
     * @param string $url URL to check
     * @return array{available: bool, archive_url?: string, timestamp?: string}
     */
    public function check_availability(string $url): array {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return array('available' => false);
        }
        
        $endpoint = add_query_arg('url', urlencode($url), $this->endpoints['availability']);
        
        $response = wp_remote_get($endpoint, array(
            'timeout' => 15,
            'sslverify' => true
        ));
        
        if (is_wp_error($response)) {
            return array('available' => false);
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['archived_snapshots']['closest']['available']) && $data['archived_snapshots']['closest']['available']) {
            return array(
                'available' => true,
                'archive_url' => $data['archived_snapshots']['closest']['url'],
                'timestamp' => $data['archived_snapshots']['closest']['timestamp']
            );
        }
        
        return array('available' => false);
    }
    
    /**
     * Get API credentials status
     * 
     * @since 0.7.0
     * @return array{has_credentials: bool, api_key_set: bool, api_secret_set: bool}
     */
    public function get_credentials_status(): array {
        return array(
            'has_credentials' => !empty($this->api_key) && !empty($this->api_secret),
            'api_key_set' => !empty($this->api_key),
            'api_secret_set' => !empty($this->api_secret)
        );
    }
    
    /**
     * Update API credentials
     * 
     * @since 0.7.0
     * @param string $api_key API key
     * @param string $api_secret API secret
     * @return void
     */
    public function update_credentials(string $api_key, string $api_secret): void {
        $this->api_key = $api_key;
        $this->api_secret = $api_secret;
    }
}
