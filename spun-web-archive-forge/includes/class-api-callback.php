<?php
/**
 * API Callback Handler
 *
 * Handles API test callbacks and provides detailed feedback
 *
 * @package SpunWebArchiveElite
 * @since 0.2.8
 * @version 0.6.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class SWAP_API_Callback {
    
    /**
     * Initialize the callback handler
     */
    public function __construct() {
        add_action('wp_ajax_swap_api_callback', array($this, 'handle_callback'));
        add_action('wp_ajax_nopriv_swap_api_callback', array($this, 'handle_public_callback'));
        add_action('init', array($this, 'handle_url_callback'));
    }
    
    /**
     * Handle AJAX callback for API tests
     */
    public function handle_callback() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'swap_ajax_nonce')) {
            wp_send_json_error(__('Security check failed', 'spun-web-archive-forge'), 403);
	}

	if (!current_user_can('manage_options')) {
		wp_send_json_error(__('Insufficient permissions', 'spun-web-archive-forge'), 403);
        }
        
        $callback_type = sanitize_text_field($_POST['callback_type'] ?? '');
        $test_id = sanitize_text_field($_POST['test_id'] ?? '');
        
        switch ($callback_type) {
            case 'api_test_status':
                $this->get_api_test_status($test_id);
                break;
            case 'api_test_details':
                $this->get_api_test_details($test_id);
                break;
            case 'api_connection_log':
                $this->get_connection_log($test_id);
                break;
            default:
                wp_send_json_error(__('Invalid callback type', 'spun-web-archive-forge'));
        }
    }
    
    /**
     * Handle public callback (for webhook-style notifications)
     */
    public function handle_public_callback() {
        // Verify callback token
        $token = sanitize_text_field($_GET['token'] ?? '');
        $stored_token = get_option('swap_callback_token', '');
        
        if (empty($token) || $token !== $stored_token) {
            wp_send_json_error(__('Invalid callback token', 'spun-web-archive-forge'), 403);
        }
        
        $this->process_external_callback();
    }
    
    /**
     * Handle URL-based callbacks
     */
    public function handle_url_callback() {
        if (!isset($_GET['swap_callback'])) {
            return;
        }
        
        $callback_action = sanitize_text_field($_GET['swap_callback']);
        $token = sanitize_text_field($_GET['token'] ?? '');
        
        // Only process if this is actually a callback request (has both callback and token parameters)
        if (empty($token)) {
            return; // Not a callback request, just ignore
        }
        
        // Verify token
        $stored_token = get_option('swap_callback_token', '');
        if ($token !== $stored_token) {
            wp_die(__('Invalid callback token', 'spun-web-archive-forge'), 403);
        }
        
        switch ($callback_action) {
            case 'api_test_result':
                $this->display_api_test_result();
                break;
            case 'connection_status':
                $this->display_connection_status();
                break;
            default:
                wp_die(__('Invalid callback action', 'spun-web-archive-forge'), 400);
        }
    }
    
    /**
     * Get API test status
     */
    private function get_api_test_status($test_id) {
        $test_data = get_transient('swap_api_test_' . $test_id);
        
        if (!$test_data) {
            wp_send_json_error(__('Test not found or expired', 'spun-web-archive-forge'));
        }
        
        wp_send_json_success(array(
            'status' => $test_data['status'],
            'message' => $test_data['message'],
            'timestamp' => $test_data['timestamp'],
            'details' => $test_data['details'] ?? array()
        ));
    }
    
    /**
     * Get detailed API test information
     */
    private function get_api_test_details($test_id) {
        $test_data = get_transient('swap_api_test_' . $test_id);
        
        if (!$test_data) {
            wp_send_json_error(__('Test not found or expired', 'spun-web-archive-forge'));
        }
        
        $details = array(
            'test_id' => $test_id,
            'status' => $test_data['status'],
            'message' => $test_data['message'],
            'timestamp' => $test_data['timestamp'],
            'response_code' => $test_data['response_code'] ?? null,
            'response_time' => $test_data['response_time'] ?? null,
            'endpoint_tested' => $test_data['endpoint'] ?? '',
            'headers_sent' => $test_data['headers'] ?? array(),
            'error_details' => $test_data['error_details'] ?? null
        );
        
        wp_send_json_success($details);
    }
    
    /**
     * Get connection log
     */
    private function get_connection_log($test_id) {
        $log_data = get_transient('swap_connection_log_' . $test_id);
        
        if (!$log_data) {
            wp_send_json_error(__('Log not found or expired', 'spun-web-archive-forge'));
        }
        
        wp_send_json_success($log_data);
    }
    
    /**
     * Process external callback
     */
    private function process_external_callback() {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data) {
            wp_send_json_error(__('Invalid callback data', 'spun-web-archive-forge'));
        }
        
        // Log the callback
        error_log('SWAP: External callback received: ' . print_r($data, true));
        
        // Process based on callback type
        $callback_type = $data['type'] ?? '';
        
        switch ($callback_type) {
            case 'api_status_update':
                $this->process_api_status_update($data);
                break;
            case 'submission_complete':
                $this->process_submission_complete($data);
                break;
            default:
                wp_send_json_error(__('Unknown callback type', 'spun-web-archive-forge'));
        }
        
        wp_send_json_success(__('Callback processed', 'spun-web-archive-forge'));
    }
    
    /**
     * Process API status update
     */
    private function process_api_status_update($data) {
        $test_id = $data['test_id'] ?? '';
        $status = $data['status'] ?? '';
        $message = $data['message'] ?? '';
        
        if (empty($test_id)) {
            return;
        }
        
        // Update test status
        $test_data = get_transient('swap_api_test_' . $test_id) ?: array();
        $test_data['status'] = $status;
        $test_data['message'] = $message;
        $test_data['updated_at'] = current_time('timestamp');
        
        set_transient('swap_api_test_' . $test_id, $test_data, HOUR_IN_SECONDS);
    }
    
    /**
     * Process submission complete callback
     */
    private function process_submission_complete($data) {
        $submission_id = $data['submission_id'] ?? '';
        $status = $data['status'] ?? '';
        $archive_url = $data['archive_url'] ?? '';
        
        if (empty($submission_id)) {
            return;
        }
        
        // Update submission status in database
        global $wpdb;
        $table_name = $wpdb->prefix . 'swap_submissions_history';
        
        $wpdb->update(
            $table_name,
            array(
                'status' => $status,
                'archive_url' => $archive_url,
                'updated_at' => current_time('mysql')
            ),
            array('id' => $submission_id),
            array('%s', '%s', '%s'),
            array('%d')
        );
    }
    
    /**
     * Display API test result page
     */
    private function display_api_test_result() {
        $test_id = sanitize_text_field($_GET['test_id'] ?? '');
        $test_data = get_transient('swap_api_test_' . $test_id);
        
        if (!$test_data) {
            wp_die(__('Test not found or expired', 'spun-web-archive-forge'), 404);
        }
        
        // Display result page
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title><?php _e('API Test Result', 'spun-web-archive-forge'); ?></title>
            <style>
                body { font-family: Arial, sans-serif; margin: 40px; }
                .result-container { max-width: 600px; margin: 0 auto; }
                .success { color: #28a745; }
                .error { color: #dc3545; }
                .details { background: #f8f9fa; padding: 20px; border-radius: 5px; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class="result-container">
                <h1><?php _e('API Test Result', 'spun-web-archive-forge'); ?></h1>
                <div class="<?php echo $test_data['status'] === 'success' ? 'success' : 'error'; ?>">
                    <h2><?php echo esc_html($test_data['message']); ?></h2>
                </div>
                <div class="details">
                    <h3><?php _e('Test Details', 'spun-web-archive-forge'); ?></h3>
                    <p><strong><?php _e('Test ID:', 'spun-web-archive-forge'); ?></strong> <?php echo esc_html($test_id); ?></p>
                    <p><strong><?php _e('Timestamp:', 'spun-web-archive-forge'); ?></strong> <?php echo date('Y-m-d H:i:s', $test_data['timestamp']); ?></p>
                    <?php if (isset($test_data['response_code'])): ?>
                        <p><strong><?php _e('Response Code:', 'spun-web-archive-forge'); ?></strong> <?php echo esc_html($test_data['response_code']); ?></p>
                    <?php endif; ?>
                    <?php if (isset($test_data['response_time'])): ?>
                        <p><strong><?php _e('Response Time:', 'spun-web-archive-forge'); ?></strong> <?php echo esc_html($test_data['response_time']); ?>ms</p>
                    <?php endif; ?>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
    
    /**
     * Display connection status page
     */
    private function display_connection_status() {
        $status = get_option('swap_api_connection_status', 'unknown');
        $last_test = get_option('swap_api_last_test', 0);
        
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title><?php _e('API Connection Status', 'spun-web-archive-forge'); ?></title>
            <style>
                body { font-family: Arial, sans-serif; margin: 40px; }
                .status-container { max-width: 600px; margin: 0 auto; }
                .connected { color: #28a745; }
                .disconnected { color: #dc3545; }
                .unknown { color: #ffc107; }
            </style>
        </head>
        <body>
            <div class="status-container">
                <h1><?php _e('API Connection Status', 'spun-web-archive-forge'); ?></h1>
                <div class="<?php echo esc_attr($status); ?>">
                    <h2><?php 
                        switch ($status) {
                            case 'connected':
                                _e('Connected', 'spun-web-archive-forge');
                                break;
                            case 'disconnected':
                                _e('Disconnected', 'spun-web-archive-forge');
                                break;
                            default:
                                _e('Unknown', 'spun-web-archive-forge');
                        }
                    ?></h2>
                </div>
                <?php if ($last_test): ?>
                    <p><strong><?php _e('Last Test:', 'spun-web-archive-forge'); ?></strong> <?php echo date('Y-m-d H:i:s', $last_test); ?></p>
                <?php endif; ?>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
    
    /**
     * Generate callback token
     */
    public static function generate_callback_token() {
        $token = wp_generate_password(32, false);
        update_option('swap_callback_token', $token);
        return $token;
    }
    
    /**
     * Get callback URL
     */
    public static function get_callback_url($action = 'api_test_result', $params = array()) {
        $token = get_option('swap_callback_token', '');
        if (empty($token)) {
            $token = self::generate_callback_token();
        }
        
        $params['swap_callback'] = $action;
        $params['token'] = $token;
        
        return add_query_arg($params, home_url('/'));
    }
}
