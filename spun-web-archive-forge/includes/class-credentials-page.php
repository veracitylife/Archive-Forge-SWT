<?php
/**
 * Spun Web Archive Forge - API Credentials Management Page
 *
 * This class handles the API credentials management interface for the plugin.
 * It provides secure storage, retrieval, and testing of Archive.org S3 API credentials.
 *
 * @package SpunWebArchiveForge
 * @version 0.6.1
 * @author  Spun
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class SWAP_Credentials_Page {
    /**
     * Encryption settings
     */
    const ENCRYPTED_SECRET_OPTION = 'swap_api_secret_encrypted';
    const ENCRYPTION_ALGO = 'aes-256-cbc';
    
    /**
     * Derive an encryption key from WP salts
     */
    private static function get_encryption_key() {
        $salt = wp_salt('auth');
        return hash('sha256', $salt, true);
    }
    
    /**
     * Encrypt a plaintext secret
     */
    private static function encrypt_secret($plaintext) {
        if (!function_exists('openssl_encrypt')) {
            return array('ciphertext' => '', 'iv' => '');
        }
        $ivlen = openssl_cipher_iv_length(self::ENCRYPTION_ALGO);
        $iv = random_bytes($ivlen);
        $key = self::get_encryption_key();
        $ciphertext = openssl_encrypt($plaintext, self::ENCRYPTION_ALGO, $key, OPENSSL_RAW_DATA, $iv);
        return array(
            'ciphertext' => base64_encode($ciphertext),
            'iv' => base64_encode($iv)
        );
    }
    
    /**
     * Decrypt a stored secret
     */
    private static function decrypt_secret($ciphertext_b64, $iv_b64) {
        if (!function_exists('openssl_decrypt') || empty($ciphertext_b64) || empty($iv_b64)) {
            return '';
        }
        $ciphertext = base64_decode($ciphertext_b64);
        $iv = base64_decode($iv_b64);
        $key = self::get_encryption_key();
        $plaintext = openssl_decrypt($ciphertext, self::ENCRYPTION_ALGO, $key, OPENSSL_RAW_DATA, $iv);
        return $plaintext ?: '';
    }
    
    /**
     * Option name for storing API credentials
     */
    const CREDENTIALS_OPTION = 'swap_api_credentials';
    
    /**
     * Initialize the credentials page
     */
    public function __construct() {
        add_action('admin_init', array($this, 'init_settings'));
        add_action('wp_ajax_swap_test_api_credentials', array($this, 'ajax_test_credentials'));
    }
    
    /**
     * Initialize settings for the credentials page
     */
    public function init_settings() {
        register_setting('swap_credentials_group', self::CREDENTIALS_OPTION, array(
            'sanitize_callback' => array($this, 'sanitize_credentials')
        ));
    }
    
    /**
     * Sanitize credentials before saving
     */
    public function sanitize_credentials($input) {
        $sanitized = array();
        
        if (isset($input['access_key'])) {
            $sanitized['access_key'] = sanitize_text_field($input['access_key']);
        }
        
        if (isset($input['secret_key'])) {
            $plaintext = sanitize_text_field($input['secret_key']);
            // Store encrypted secret only; do NOT persist plaintext anymore
            $enc = self::encrypt_secret($plaintext);
            if (!empty($enc['ciphertext']) && !empty($enc['iv'])) {
                update_option(self::ENCRYPTED_SECRET_OPTION, array(
                    'ciphertext' => $enc['ciphertext'],
                    'iv' => $enc['iv'],
                ), false);
            }
            // Ensure plaintext secret is not stored
            if (isset($sanitized['secret_key'])) {
                unset($sanitized['secret_key']);
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Render the credentials management page
     */
    public function render_page() {
        $credentials = $this->get_credentials();
        // If encrypted secret exists, prefer showing masked value
        $encrypted = get_option(self::ENCRYPTED_SECRET_OPTION, array());
        if (!empty($encrypted['ciphertext']) && !empty($encrypted['iv'])) {
            $credentials['secret_key'] = '********';
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="notice notice-info">
                <p><strong>Archive.org S3 API Credentials</strong></p>
                <p>Enter your Archive.org S3 API credentials below. You can obtain these from your 
                <a href="https://archive.org/account/s3.php" target="_blank">Archive.org S3 Keys page</a>.</p>
            </div>
            
            <form method="post" action="options.php" id="credentials-form">
                <?php settings_fields('swap_credentials_group'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="access_key">Access Key</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="access_key" 
                                   name="<?php echo self::CREDENTIALS_OPTION; ?>[access_key]" 
                                   value="<?php echo esc_attr($credentials['access_key'] ?? ''); ?>" 
                                   class="regular-text" 
                                   placeholder="Your Archive.org S3 Access Key" />
                            <p class="description">Your Archive.org S3 access key (public key)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="secret_key">Secret Key</label>
                        </th>
                        <td>
                            <input type="password" 
                                   id="secret_key" 
                                   name="<?php echo self::CREDENTIALS_OPTION; ?>[secret_key]" 
                                   value="<?php echo esc_attr($credentials['secret_key'] ?? ''); ?>" 
                                   class="regular-text" 
                                   placeholder="Your Archive.org S3 Secret Key" />
            <p class="description">Your Archive.org S3 secret key (private key)</p>
        </td>
                    </tr>
                </table>
                
                <?php submit_button('Save Credentials'); ?>
            </form>
            
            <hr>
            
            <h2>Test API Connection</h2>
            <p>Test your API credentials to ensure they work correctly with the Archive.org S3 API.</p>
            
            <div id="test-results" style="margin: 20px 0;"></div>
            
            <button type="button" id="test-credentials-btn" class="button button-secondary">
                Test API Connection
            </button>
            
            <div id="test-spinner" style="display: none; margin-left: 10px;">
                <span class="spinner is-active"></span> Testing connection...
            </div>
        </div>
        
        <style>
            .test-result {
                padding: 15px;
                border-radius: 4px;
                font-weight: bold;
                margin: 10px 0;
                display: inline-block;
                min-width: 100px;
                text-align: center;
            }
            
            .test-pass {
                background-color: #4CAF50;
                color: black;
                border: 2px solid #45a049;
            }
            
            .test-fail {
                background-color: #f44336;
                color: black;
                border: 2px solid #da190b;
            }
            
            .test-details {
                margin-top: 10px;
                padding: 10px;
                background-color: #f9f9f9;
                border-left: 4px solid #ddd;
                font-family: monospace;
                font-size: 12px;
            }
        </style>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#test-credentials-btn').on('click', function() {
                var $button = $(this);
                var $spinner = $('#test-spinner');
                var $results = $('#test-results');
                
                // Disable button and show spinner
                $button.prop('disabled', true);
                $spinner.show();
                $results.empty();
                
                // Get current form values
                var accessKey = $('#access_key').val();
                var secretKey = $('#secret_key').val();
                
                if (!accessKey || !secretKey) {
                    $results.html('<div class="test-result test-fail">FAIL</div><div class="test-details">Please enter both Access Key and Secret Key before testing.</div>');
                    $button.prop('disabled', false);
                    $spinner.hide();
                    return;
                }
                
                // Make AJAX request
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'swap_test_api_credentials',
                        access_key: accessKey,
                        secret_key: secretKey,
                        nonce: '<?php echo wp_create_nonce('swap_test_credentials'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $results.html('<div class="test-result test-success">SUCCESS</div><div class="test-details">' + response.data.message + '</div>');
                        } else {
                            var errorMessage = response.data.message;
                            var errorType = response.data.error_type || 'unknown';
                            
                            // Show error message
                            $results.html('<div class="test-result test-fail">FAIL</div><div class="test-details">' + errorMessage + '</div>');
                            
                            // If it's a connection error, show additional notice
                            if (errorType === 'timeout' || errorType === 'dns_failure' || errorType === 'connection_refused') {
                                $results.append('<div class="notice notice-warning inline"><p><strong>Note:</strong> This appears to be a connection issue with Archive.org. Please check your internet connection and try again later.</p></div>');
                            }
                            
                            // Handle redirection if specified
                            if (response.data.redirect_to_settings) {
                                setTimeout(function() {
                                    // Show a notice that we're staying on the settings page
                                    $results.append('<div class="notice notice-info inline"><p>Please check your connection and try again. You can update your credentials above if needed.</p></div>');
                                }, 2000);
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        var errorMsg = 'Connection error: ' + error;
                        if (status === 'timeout') {
                            errorMsg = 'This site can\'t be reached. Connection to Archive.org timed out. Please try again later.';
                        }
                        $results.html('<div class="test-result test-fail">FAIL</div><div class="test-details">' + errorMsg + '</div>');
                        $results.append('<div class="notice notice-warning inline"><p><strong>Note:</strong> This appears to be a connection issue. Please check your internet connection and try again later.</p></div>');
                    },
                    complete: function() {
                        $button.prop('disabled', false);
                        $spinner.hide();
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * AJAX handler for testing API credentials
     */
    public function ajax_test_credentials() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'swap_test_credentials')) {
            wp_die('Security check failed');
        }
        
        $access_key = sanitize_text_field($_POST['access_key']);
        $secret_key = sanitize_text_field($_POST['secret_key']);
        
        if (empty($access_key) || empty($secret_key)) {
            wp_send_json_error(array(
                'message' => 'Both Access Key and Secret Key are required.'
            ));
        }
        
        $test_result = $this->test_api_connection($access_key, $secret_key);
        
        if ($test_result['success']) {
            wp_send_json_success(array(
                'message' => $test_result['message']
            ));
        } else {
            $error_response = array(
                'message' => $test_result['message']
            );
            
            // Include error type and redirection flag if present
            if (isset($test_result['error_type'])) {
                $error_response['error_type'] = $test_result['error_type'];
            }
            if (isset($test_result['redirect_to_settings'])) {
                $error_response['redirect_to_settings'] = $test_result['redirect_to_settings'];
            }
            
            wp_send_json_error($error_response);
        }
    }
    
    /**
     * Test API connection with provided credentials
     */
    private function test_api_connection($access_key, $secret_key) {
        // Validate inputs
        if (empty($access_key) || empty($secret_key)) {
            return [
                'success' => false,
                'message' => __('Both Access Key and Secret Key are required.', 'spun-web-archive-forge')
            ];
        }
        
        // Test endpoint - we'll try to access a simple API endpoint
        $test_url = 'https://s3.us.archive.org/';
        
        // Create authorization header
        $auth_header = 'LOW ' . $access_key . ':' . $secret_key;
        
        // Make test request with proper error handling
        $response = wp_remote_get($test_url, array(
            'headers' => array(
                'Authorization' => $auth_header,
                'User-Agent' => 'Spun Web Archive Forge/' . SWAP_VERSION
            ),
            'timeout' => 30,
            'sslverify' => true
        ));
        
        if (is_wp_error($response)) {
            $error_code = $response->get_error_code();
            $error_message = $response->get_error_message();
            
            // Provide more specific error messages for common connection issues
            $user_message = '';
            $error_type = 'wp_error';
            
            if (strpos($error_code, 'timeout') !== false || strpos($error_message, 'timeout') !== false) {
                $user_message = __('Archive.org connection timed out. The site may be temporarily unavailable. Please try again later.', 'spun-web-archive-forge');
                $error_type = 'timeout';
            } elseif (strpos($error_message, 'resolve host') !== false || strpos($error_message, 'name resolution') !== false) {
                $user_message = __('This site can\'t be reached. Archive.org cannot be reached. Please check your internet connection and try again.', 'spun-web-archive-forge');
                $error_type = 'dns_failure';
            } elseif (strpos($error_message, 'connect') !== false) {
                $user_message = __('Cannot connect to Archive.org. The site may be temporarily unavailable. Please try again later.', 'spun-web-archive-forge');
                $error_type = 'connection_refused';
            } elseif (strpos($error_message, 'SSL') !== false || strpos($error_message, 'certificate') !== false) {
                $user_message = __('SSL/Certificate error connecting to Archive.org. Please check your server configuration.', 'spun-web-archive-forge');
                $error_type = 'ssl_error';
            } else {
                $user_message = sprintf(__('Connection failed: %s', 'spun-web-archive-forge'), $error_message);
            }
            
            return array(
                'success' => false,
                'message' => $user_message,
                'error_type' => $error_type,
                'redirect_to_settings' => true
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        // Check response
        if ($response_code === 200 || $response_code === 403) {
            // 200 = success, 403 = forbidden but credentials are valid (just no access to root)
            return array(
                'success' => true,
                'message' => 'API credentials are valid! Connection successful (HTTP ' . $response_code . ')'
            );
        } elseif ($response_code === 401) {
            return array(
                'success' => false,
                'message' => 'Invalid credentials. Please check your Access Key and Secret Key (HTTP 401)'
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Unexpected response from Archive.org API (HTTP ' . $response_code . ')'
            );
        }
    }
    
    /**
     * Get stored credentials
     */
    public static function get_credentials() {
        $creds = get_option(self::CREDENTIALS_OPTION, array(
            'access_key' => '',
            'secret_key' => ''
        ));
        // If encrypted secret is present, decrypt and provide transparently
        $encrypted = get_option(self::ENCRYPTED_SECRET_OPTION, array());
        if (!empty($encrypted['ciphertext']) && !empty($encrypted['iv'])) {
            $decrypted = self::decrypt_secret($encrypted['ciphertext'], $encrypted['iv']);
            if (!empty($decrypted)) {
                $creds['secret_key'] = $decrypted;
            }
        }
        return $creds;
    }
    
    /**
     * Check if credentials are configured
     */
    public static function has_credentials() {
        $credentials = self::get_credentials();
        return !empty($credentials['access_key']) && !empty($credentials['secret_key']);
    }
    
    /**
     * Get access key
     */
    public static function get_access_key() {
        $credentials = self::get_credentials();
        return $credentials['access_key'] ?? '';
    }
    
    /**
     * Get secret key
     */
    public static function get_secret_key() {
        $encrypted = get_option(self::ENCRYPTED_SECRET_OPTION, array());
        if (!empty($encrypted['ciphertext']) && !empty($encrypted['iv'])) {
            return self::decrypt_secret($encrypted['ciphertext'], $encrypted['iv']);
        }
        $credentials = self::get_credentials();
        return $credentials['secret_key'] ?? '';
    }
}
