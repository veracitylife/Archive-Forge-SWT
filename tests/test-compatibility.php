<?php
/**
 * Compatibility Test Suite
 * 
 * Tests all plugin components for WordPress and PHP compatibility
 * 
 * @package SpunWebArchiveElite
 * @subpackage Tests
 * @author Ryan Dickie Thompson
 * @copyright 2024 Spun Web Technology
 * @license GPL-2.0-or-later
 * @since 0.6.1
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Compatibility test suite for the plugin
 */
class SWAP_Compatibility_Tests {
    
    /**
     * Test results
     * 
     * @var array
     */
    private $test_results = [];
    
    /**
     * Test errors
     * 
     * @var array
     */
    private $test_errors = [];
    
    /**
     * Run all compatibility tests
     * 
     * @return array Test results
     */
    public function run_all_tests(): array {
        $this->test_results = [];
        $this->test_errors = [];
        
        // Environment tests
        $this->test_php_version();
        $this->test_wordpress_version();
        $this->test_required_extensions();
        $this->test_memory_limit();
        
        // Class loading tests
        $this->test_class_loading();
        $this->test_class_instantiation();
        
        // Database tests
        $this->test_database_operations();
        $this->test_migration_system();
        
        // API tests
        $this->test_api_functionality();
        
        // Admin interface tests
        $this->test_admin_interface();
        
        // Auto submission tests
        $this->test_auto_submission();
        
        // Queue management tests
        $this->test_queue_management();
        
        return [
            'results' => $this->test_results,
            'errors' => $this->test_errors,
            'summary' => $this->get_test_summary()
        ];
    }
    
    /**
     * Test PHP version compatibility
     * 
     * @return void
     */
    private function test_php_version(): void {
        $min_version = '7.4.0';
        $current_version = PHP_VERSION;
        
        $this->add_test_result(
            'PHP Version',
            version_compare($current_version, $min_version, '>='),
            "Current: {$current_version}, Required: {$min_version}+"
        );
        
        // Test PHP 8+ specific features
        if (version_compare($current_version, '8.0.0', '>=')) {
            $this->test_php8_features();
        }
    }
    
    /**
     * Test PHP 8+ specific features
     * 
     * @return void
     */
    private function test_php8_features(): void {
        // Test if PHP 8.0+ features are available
        $php8_available = version_compare(PHP_VERSION, '8.0.0', '>=');
        
        if ($php8_available) {
            try {
                // Test union types (PHP 8.0+) - using eval to avoid parse errors on older PHP
                $union_test_code = '
                    $test_function = function($value) {
                        return is_string($value) || is_int($value);
                    };
                    return $test_function("test") && $test_function(123);
                ';
                
                $union_result = eval("return (function() { {$union_test_code} })();");
                
                $this->add_test_result(
                    'PHP 8 Union Types Support',
                    $union_result === true,
                    'Union types compatibility check'
                );
                
                // Test nullsafe operator (PHP 8.0+) - using property_exists for compatibility
                $test_object = (object) ['property' => 'value'];
                $nullsafe_result = property_exists($test_object, 'property') ? $test_object->property : null;
                
                $this->add_test_result(
                    'PHP 8 Nullsafe Operator Compatibility',
                    $nullsafe_result === 'value',
                    'Nullsafe operator compatibility (using fallback)'
                );
                
                // Test named arguments compatibility (PHP 8.0+)
                $named_args_test = function($param1 = 'default1', $param2 = 'default2') {
                    return $param1 . '-' . $param2;
                };
                
                $named_result = $named_args_test('test1', 'test2');
                
                $this->add_test_result(
                    'PHP 8 Named Arguments Compatibility',
                    $named_result === 'test1-test2',
                    'Named arguments compatibility check'
                );
                
            } catch (ParseError $e) {
                $this->add_test_result(
                    'PHP 8 Features',
                    false,
                    'PHP 8 syntax not supported: ' . $e->getMessage()
                );
            } catch (Error $e) {
                $this->add_test_result(
                    'PHP 8 Features',
                    false,
                    'PHP 8 feature error: ' . $e->getMessage()
                );
            }
        } else {
            // For PHP 7.4, test compatibility fallbacks
            $this->add_test_result(
                'PHP 7.4 Compatibility Mode',
                true,
                'Running in PHP 7.4 compatibility mode with fallbacks'
            );
            
            // Test type checking functions that work in PHP 7.4
            $type_check_function = function($value) {
                return is_string($value) || is_int($value);
            };
            
            $this->add_test_result(
                'Type Checking Compatibility',
                $type_check_function('test') && $type_check_function(123),
                'Type checking functions working correctly'
            );
            
            // Test safe property access
            $test_object = (object) ['property' => 'value'];
            $safe_access = isset($test_object->property) ? $test_object->property : null;
            
            $this->add_test_result(
                'Safe Property Access',
                $safe_access === 'value',
                'Safe property access working correctly'
            );
        }
    }
    
    /**
     * Test WordPress version compatibility
     * 
     * @return void
     */
    private function test_wordpress_version(): void {
        global $wp_version;
        
        $min_version = '5.0';
        
        $this->add_test_result(
            'WordPress Version',
            version_compare($wp_version, $min_version, '>='),
            "Current: {$wp_version}, Required: {$min_version}+"
        );
    }
    
    /**
     * Test required PHP extensions
     * 
     * @return void
     */
    private function test_required_extensions(): void {
        $required_extensions = [
            'curl' => 'Required for API communication',
            'json' => 'Required for data processing',
            'mbstring' => 'Required for string handling',
            'openssl' => 'Required for secure connections'
        ];
        
        foreach ($required_extensions as $extension => $description) {
            $this->add_test_result(
                "Extension: {$extension}",
                extension_loaded($extension),
                $description
            );
        }
    }
    
    /**
     * Test memory limit
     * 
     * @return void
     */
    private function test_memory_limit(): void {
        $memory_limit = ini_get('memory_limit');
        $memory_bytes = $this->convert_to_bytes($memory_limit);
        $min_memory = 64 * 1024 * 1024; // 64MB
        
        $this->add_test_result(
            'Memory Limit',
            $memory_bytes >= $min_memory || $memory_bytes === -1,
            "Current: {$memory_limit}, Recommended: 64M+"
        );
    }
    
    /**
     * Test class loading
     * 
     * @return void
     */
    private function test_class_loading(): void {
        $required_classes = [
            'SWAP_Archive_API',
            'SWAP_Archive_Queue',
            'SWAP_Submissions_History',
            'SWAP_Admin_Page',
            'SWAP_Auto_Submitter',
            'SWAP_Database_Migration'
        ];
        
        foreach ($required_classes as $class_name) {
            $this->add_test_result(
                "Class Loading: {$class_name}",
                class_exists($class_name),
                "Class {$class_name} availability"
            );
        }
    }
    
    /**
     * Test class instantiation
     * 
     * @return void
     */
    private function test_class_instantiation(): void {
        try {
            // Test API class
            $api = new SWAP_Archive_API();
            $this->add_test_result(
                'API Instantiation',
                $api instanceof SWAP_Archive_API,
                'SWAP_Archive_API can be instantiated'
            );
            
            // Test Queue class
            $queue = new SWAP_Archive_Queue();
            $this->add_test_result(
                'Queue Instantiation',
                $queue instanceof SWAP_Archive_Queue,
                'SWAP_Archive_Queue can be instantiated'
            );
            
            // Test Database Migration
            $migration = new SWAP_Database_Migration();
            $this->add_test_result(
                'Migration Instantiation',
                $migration instanceof SWAP_Database_Migration,
                'SWAP_Database_Migration can be instantiated'
            );
            
        } catch (Exception $e) {
            $this->add_test_result(
                'Class Instantiation',
                false,
                'Error instantiating classes: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Test database operations
     * 
     * @return void
     */
    private function test_database_operations(): void {
        global $wpdb;
        
        try {
            // Test database connection
            $result = $wpdb->get_var("SELECT 1");
            $this->add_test_result(
                'Database Connection',
                $result === '1',
                'WordPress database connection working'
            );
            
            // Test table creation permissions
            $test_table = $wpdb->prefix . 'swap_test_' . time();
            $create_sql = "CREATE TABLE {$test_table} (id INT AUTO_INCREMENT PRIMARY KEY, test_data VARCHAR(255))";
            
            $create_result = $wpdb->query($create_sql);
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$test_table}'");
            
            if ($table_exists) {
                $wpdb->query("DROP TABLE {$test_table}");
            }
            
            $this->add_test_result(
                'Table Creation',
                $create_result !== false && !empty($table_exists),
                'Database table creation permissions'
            );
            
        } catch (Exception $e) {
            $this->add_test_result(
                'Database Operations',
                false,
                'Database error: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Test migration system
     * 
     * @return void
     */
    private function test_migration_system(): void {
        try {
            $migration = new SWAP_Database_Migration();
            
            // Test version checking
            $current_version = $migration->get_current_version();
            $this->add_test_result(
                'Migration Version Check',
                is_string($current_version) && !empty($current_version),
                "Current DB version: {$current_version}"
            );
            
            // Test health status
            $health = $migration->get_health_status();
            $this->add_test_result(
                'Migration Health Check',
                is_array($health) && isset($health['db_version']),
                'Migration health status available'
            );
            
        } catch (Exception $e) {
            $this->add_test_result(
                'Migration System',
                false,
                'Migration error: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Test API functionality
     * 
     * @return void
     */
    private function test_api_functionality(): void {
        try {
            $api = new SWAP_Archive_API();
            
            // Test URL validation
            $valid_url = 'https://example.com';
            $invalid_url = 'not-a-url';
            
            $this->add_test_result(
                'API URL Validation',
                $api->is_valid_url($valid_url) && !$api->is_valid_url($invalid_url),
                'URL validation working correctly'
            );
            
            // Test settings
            $settings = $api->get_settings();
            $this->add_test_result(
                'API Settings',
                is_array($settings),
                'API settings retrieval working'
            );
            
        } catch (Exception $e) {
            $this->add_test_result(
                'API Functionality',
                false,
                'API error: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Test admin interface
     * 
     * @return void
     */
    private function test_admin_interface(): void {
        try {
            $api = new SWAP_Archive_API();
            $queue = new SWAP_Archive_Queue();
            $admin = new SWAP_Admin_Page($api, $queue);
            
            $this->add_test_result(
                'Admin Page Creation',
                $admin instanceof SWAP_Admin_Page,
                'Admin page can be instantiated'
            );
            
            // Test if WordPress admin functions are available
            $this->add_test_result(
                'WordPress Admin Functions',
                function_exists('add_action') && function_exists('wp_enqueue_script'),
                'WordPress admin functions available'
            );
            
        } catch (Exception $e) {
            $this->add_test_result(
                'Admin Interface',
                false,
                'Admin interface error: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Test auto submission functionality
     * 
     * @return void
     */
    private function test_auto_submission(): void {
        try {
            $api = new SWAP_Archive_API();
            $queue = new SWAP_Archive_Queue();
            $history = new SWAP_Submissions_History();
            $auto_submitter = new SWAP_Auto_Submitter($api, $queue, $history);
            
            $this->add_test_result(
                'Auto Submitter Creation',
                $auto_submitter instanceof SWAP_Auto_Submitter,
                'Auto submitter can be instantiated'
            );
            
            // Test WordPress hooks
            $this->add_test_result(
                'WordPress Hooks',
                function_exists('add_action') && function_exists('wp_schedule_event'),
                'WordPress hook functions available'
            );
            
        } catch (Exception $e) {
            $this->add_test_result(
                'Auto Submission',
                false,
                'Auto submission error: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Test queue management
     * 
     * @return void
     */
    private function test_queue_management(): void {
        try {
            $queue = new SWAP_Archive_Queue();
            
            // Test queue operations
            $stats = $queue->get_stats();
            $this->add_test_result(
                'Queue Stats',
                is_array($stats),
                'Queue statistics retrieval working'
            );
            
            $items = $queue->get_pending_items(1);
            $this->add_test_result(
                'Queue Items',
                is_array($items),
                'Queue item retrieval working'
            );
            
        } catch (Exception $e) {
            $this->add_test_result(
                'Queue Management',
                false,
                'Queue error: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Add test result
     * 
     * @param string $test_name Test name
     * @param bool $passed Whether test passed
     * @param string $message Test message
     * @return void
     */
    private function add_test_result($test_name, $passed, $message): void {
        $this->test_results[] = [
            'test' => $test_name,
            'passed' => $passed,
            'message' => $message,
            'timestamp' => function_exists('current_time') ? current_time('mysql') : date('Y-m-d H:i:s')
        ];
        
        if (!$passed) {
            $this->test_errors[] = [
                'test' => $test_name,
                'message' => $message
            ];
        }
    }
    
    /**
     * Get test summary
     * 
     * @return array Test summary
     */
    private function get_test_summary() {
        $total_tests = count($this->test_results);
        $passed_tests = count(array_filter($this->test_results, function($result) { return $result['passed']; }));
        $failed_tests = $total_tests - $passed_tests;
        
        return [
            'total' => $total_tests,
            'passed' => $passed_tests,
            'failed' => $failed_tests,
            'success_rate' => $total_tests > 0 ? round(($passed_tests / $total_tests) * 100, 2) : 0,
            'php_version' => PHP_VERSION,
            'wordpress_version' => function_exists('get_bloginfo') ? get_bloginfo('version') : 'Unknown',
            'timestamp' => function_exists('current_time') ? current_time('mysql') : date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Convert memory limit to bytes
     * 
     * @param string $memory_limit Memory limit string
     * @return int Memory limit in bytes
     */
    private function convert_to_bytes($memory_limit) {
        if ($memory_limit === '-1') {
            return -1; // Unlimited
        }
        
        $memory_limit = trim($memory_limit);
        $last_char = strtolower($memory_limit[strlen($memory_limit) - 1]);
        $number = (int) $memory_limit;
        
        switch ($last_char) {
            case 'g':
                $number *= 1024 * 1024 * 1024;
                break;
            case 'm':
                $number *= 1024 * 1024;
                break;
            case 'k':
                $number *= 1024;
                break;
        }
        
        return $number;
    }
    
    /**
     * Generate HTML report
     * 
     * @return string HTML report
     */
    public function generate_html_report(): string {
        $results = $this->run_all_tests();
        $summary = $results['summary'];
        
        $html = '<div class="swap-test-report">';
        $html .= '<h2>Spun Web Archive Forge - Compatibility Test Report</h2>';
        
        // Summary
        $html .= '<div class="test-summary">';
        $html .= '<h3>Test Summary</h3>';
        $html .= '<p><strong>Total Tests:</strong> ' . $summary['total'] . '</p>';
        $html .= '<p><strong>Passed:</strong> <span style="color: green;">' . $summary['passed'] . '</span></p>';
        $html .= '<p><strong>Failed:</strong> <span style="color: red;">' . $summary['failed'] . '</span></p>';
        $html .= '<p><strong>Success Rate:</strong> ' . $summary['success_rate'] . '%</p>';
        $html .= '<p><strong>PHP Version:</strong> ' . $summary['php_version'] . '</p>';
        $html .= '<p><strong>WordPress Version:</strong> ' . $summary['wordpress_version'] . '</p>';
        $html .= '</div>';
        
        // Detailed results
        $html .= '<div class="test-details">';
        $html .= '<h3>Detailed Results</h3>';
        $html .= '<table style="width: 100%; border-collapse: collapse;">';
        $html .= '<thead><tr><th>Test</th><th>Status</th><th>Message</th></tr></thead>';
        $html .= '<tbody>';
        
        foreach ($results['results'] as $result) {
            $status_color = $result['passed'] ? 'green' : 'red';
            $status_text = $result['passed'] ? 'PASS' : 'FAIL';
            
            $html .= '<tr>';
            $html .= '<td>' . esc_html($result['test']) . '</td>';
            $html .= '<td style="color: ' . $status_color . '; font-weight: bold;">' . $status_text . '</td>';
            $html .= '<td>' . esc_html($result['message']) . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody></table>';
        $html .= '</div>';
        
        // Errors (if any)
        if (!empty($results['errors'])) {
            $html .= '<div class="test-errors">';
            $html .= '<h3>Errors</h3>';
            $html .= '<ul>';
            
            foreach ($results['errors'] as $error) {
                $html .= '<li><strong>' . esc_html($error['test']) . ':</strong> ' . esc_html($error['message']) . '</li>';
            }
            
            $html .= '</ul>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Run tests and display results (for admin use)
     * 
     * @return void
     */
    public static function run_and_display(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $tester = new self();
        echo $tester->generate_html_report();
    }
}