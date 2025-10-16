<?php
/**
 * Memory Monitoring Dashboard Class
 * 
 * Provides admin interface for monitoring memory usage and performance
 * 
 * @package SpunWebArchiveElite
 * @subpackage Includes
 * @author Ryan Dickie Thompson
 * @copyright 2024 Spun Web Technology
 * @license GPL-2.0-or-later
 * @since 0.6.1
 * @version 0.6.1
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Memory monitoring dashboard class
 */
class SWAP_Memory_Dashboard {
    
    /**
     * Page slug for the dashboard
     */
    private const PAGE_SLUG = 'swap-memory-dashboard';
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_swap_refresh_memory_stats', array($this, 'ajax_refresh_memory_stats'));
        add_action('wp_ajax_swap_clear_memory_log', array($this, 'ajax_clear_memory_log'));
    }
    
    /**
     * Add admin menu page
     * 
     * @return void
     */
    public function add_admin_menu(): void {
        add_submenu_page(
            'swap-admin',
            __('Memory Monitor', 'spun-web-archive-forge'),
            __('Memory Monitor', 'spun-web-archive-forge'),
            'manage_options',
            self::PAGE_SLUG,
            array($this, 'render_dashboard_page')
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     * 
     * @param string $hook Current admin page hook
     * @return void
     */
    public function enqueue_scripts(string $hook): void {
        if (strpos($hook, self::PAGE_SLUG) === false) {
            return;
        }
        
        wp_enqueue_script(
            'swap-memory-dashboard',
            SWAP_PLUGIN_URL . 'assets/js/memory-dashboard.js',
            array('jquery'),
            SWAP_VERSION,
            true
        );
        
        wp_localize_script('swap-memory-dashboard', 'swapMemoryDashboard', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('swap_memory_dashboard'),
            'refreshInterval' => 30000, // 30 seconds
            'strings' => array(
                'refreshing' => __('Refreshing...', 'spun-web-archive-forge'),
                'error' => __('Error loading data', 'spun-web-archive-forge'),
                'confirmClear' => __('Are you sure you want to clear the memory log?', 'spun-web-archive-forge')
            )
        ));
        
        wp_enqueue_style(
            'swap-memory-dashboard',
            SWAP_PLUGIN_URL . 'assets/css/memory-dashboard.css',
            array(),
            SWAP_VERSION
        );
    }
    
    /**
     * Render the dashboard page
     * 
     * @return void
     */
    public function render_dashboard_page(): void {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        $memory_stats = SWAP_Memory_Utils::get_memory_stats();
        $memory_log = SWAP_Memory_Utils::get_memory_log(20);
        
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Memory Monitor', 'spun-web-archive-forge'); ?></h1>
            
            <div class="swap-memory-dashboard">
                <!-- Current Memory Status -->
                <div class="swap-memory-status-cards">
                    <div class="swap-card swap-memory-current">
                        <h3><?php esc_html_e('Current Memory Usage', 'spun-web-archive-forge'); ?></h3>
                        <div class="swap-memory-gauge">
                            <div class="swap-gauge-container">
                                <div class="swap-gauge-fill" style="width: <?php echo esc_attr($memory_stats['current_usage_percent']); ?>%"></div>
                                <div class="swap-gauge-text">
                                    <?php echo esc_html($memory_stats['current_usage_percent']); ?>%
                                </div>
                            </div>
                            <div class="swap-memory-details">
                                <span><?php echo esc_html($memory_stats['current_usage_mb']); ?>MB / <?php echo esc_html($memory_stats['memory_limit_mb']); ?>MB</span>
                            </div>
                        </div>
                        <div class="swap-memory-status swap-status-<?php echo esc_attr($memory_stats['status']); ?>">
                            <?php echo esc_html(ucfirst($memory_stats['status'])); ?>
                        </div>
                    </div>
                    
                    <div class="swap-card swap-memory-peak">
                        <h3><?php esc_html_e('Peak Memory Usage', 'spun-web-archive-forge'); ?></h3>
                        <div class="swap-stat-value">
                            <?php echo esc_html($memory_stats['peak_usage_mb']); ?>MB
                        </div>
                        <div class="swap-stat-label">
                            <?php esc_html_e('Since page load', 'spun-web-archive-forge'); ?>
                        </div>
                    </div>
                    
                    <div class="swap-card swap-memory-available">
                        <h3><?php esc_html_e('Available Memory', 'spun-web-archive-forge'); ?></h3>
                        <div class="swap-stat-value">
                            <?php echo esc_html($memory_stats['available_mb']); ?>MB
                        </div>
                        <div class="swap-stat-label">
                            <?php esc_html_e('Remaining', 'spun-web-archive-forge'); ?>
                        </div>
                    </div>
                </div>
                
                <!-- Memory Thresholds -->
                <div class="swap-card swap-memory-thresholds">
                    <h3><?php esc_html_e('Memory Thresholds', 'spun-web-archive-forge'); ?></h3>
                    <div class="swap-threshold-list">
                        <div class="swap-threshold-item">
                            <span class="swap-threshold-label"><?php esc_html_e('Warning:', 'spun-web-archive-forge'); ?></span>
                            <span class="swap-threshold-value"><?php echo esc_html($memory_stats['warning_threshold']); ?>%</span>
                        </div>
                        <div class="swap-threshold-item">
                            <span class="swap-threshold-label"><?php esc_html_e('Critical:', 'spun-web-archive-forge'); ?></span>
                            <span class="swap-threshold-value"><?php echo esc_html($memory_stats['critical_threshold']); ?>%</span>
                        </div>
                        <div class="swap-threshold-item">
                            <span class="swap-threshold-label"><?php esc_html_e('Abort:', 'spun-web-archive-forge'); ?></span>
                            <span class="swap-threshold-value"><?php echo esc_html($memory_stats['abort_threshold']); ?>%</span>
                        </div>
                    </div>
                </div>
                
                <!-- Memory Log -->
                <div class="swap-card swap-memory-log">
                    <div class="swap-card-header">
                        <h3><?php esc_html_e('Memory Usage Log', 'spun-web-archive-forge'); ?></h3>
                        <div class="swap-card-actions">
                            <button type="button" class="button" id="swap-refresh-memory-stats">
                                <?php esc_html_e('Refresh', 'spun-web-archive-forge'); ?>
                            </button>
                            <button type="button" class="button" id="swap-clear-memory-log">
                                <?php esc_html_e('Clear Log', 'spun-web-archive-forge'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <div class="swap-memory-log-container">
                        <?php if (empty($memory_log)): ?>
                            <p class="swap-no-data"><?php esc_html_e('No memory warnings logged yet.', 'spun-web-archive-forge'); ?></p>
                        <?php else: ?>
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Time', 'spun-web-archive-forge'); ?></th>
                                        <th><?php esc_html_e('Context', 'spun-web-archive-forge'); ?></th>
                                        <th><?php esc_html_e('Usage', 'spun-web-archive-forge'); ?></th>
                                        <th><?php esc_html_e('Severity', 'spun-web-archive-forge'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_reverse($memory_log) as $entry): ?>
                                        <tr>
                                            <td>
                                                <?php echo esc_html(date('M j, Y H:i:s', strtotime($entry['timestamp']))); ?>
                                            </td>
                                            <td>
                                                <code><?php echo esc_html($entry['context']); ?></code>
                                            </td>
                                            <td>
                                                <?php echo esc_html($entry['memory_usage_percent']); ?>% 
                                                (<?php echo esc_html($entry['memory_usage_mb']); ?>MB)
                                            </td>
                                            <td>
                                                <span class="swap-severity swap-severity-<?php echo esc_attr($entry['severity']); ?>">
                                                    <?php echo esc_html(ucfirst($entry['severity'])); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Memory Optimization Tips -->
                <div class="swap-card swap-memory-tips">
                    <h3><?php esc_html_e('Memory Optimization Tips', 'spun-web-archive-forge'); ?></h3>
                    <ul class="swap-tips-list">
                        <li><?php esc_html_e('Increase PHP memory limit if frequently hitting thresholds', 'spun-web-archive-forge'); ?></li>
                        <li><?php esc_html_e('Process large datasets in smaller batches', 'spun-web-archive-forge'); ?></li>
                        <li><?php esc_html_e('Clear memory log regularly to prevent database bloat', 'spun-web-archive-forge'); ?></li>
                        <li><?php esc_html_e('Monitor peak usage during high-traffic periods', 'spun-web-archive-forge'); ?></li>
                        <li><?php esc_html_e('Consider upgrading server resources if consistently high usage', 'spun-web-archive-forge'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <style>
        .swap-memory-dashboard {
            max-width: 1200px;
        }
        
        .swap-memory-status-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .swap-card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        
        .swap-card h3 {
            margin: 0 0 15px 0;
            font-size: 14px;
            font-weight: 600;
            color: #23282d;
        }
        
        .swap-memory-gauge {
            text-align: center;
        }
        
        .swap-gauge-container {
            position: relative;
            background: #f1f1f1;
            border-radius: 10px;
            height: 20px;
            margin-bottom: 10px;
            overflow: hidden;
        }
        
        .swap-gauge-fill {
            height: 100%;
            background: linear-gradient(90deg, #4caf50 0%, #ff9800 70%, #f44336 90%);
            transition: width 0.3s ease;
        }
        
        .swap-gauge-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-weight: 600;
            font-size: 12px;
            color: #23282d;
        }
        
        .swap-memory-details {
            font-size: 12px;
            color: #666;
        }
        
        .swap-memory-status {
            margin-top: 10px;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
        }
        
        .swap-status-safe { background: #d4edda; color: #155724; }
        .swap-status-warning { background: #fff3cd; color: #856404; }
        .swap-status-critical { background: #f8d7da; color: #721c24; }
        .swap-status-abort { background: #f5c6cb; color: #721c24; }
        
        .swap-stat-value {
            font-size: 24px;
            font-weight: 600;
            color: #23282d;
            margin-bottom: 5px;
        }
        
        .swap-stat-label {
            font-size: 12px;
            color: #666;
        }
        
        .swap-threshold-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .swap-threshold-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .swap-threshold-label {
            font-weight: 500;
        }
        
        .swap-threshold-value {
            font-weight: 600;
            color: #0073aa;
        }
        
        .swap-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .swap-card-header h3 {
            margin: 0;
        }
        
        .swap-card-actions {
            display: flex;
            gap: 10px;
        }
        
        .swap-memory-log-container {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .swap-no-data {
            text-align: center;
            color: #666;
            font-style: italic;
            margin: 20px 0;
        }
        
        .swap-severity {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .swap-severity-info { background: #d1ecf1; color: #0c5460; }
        .swap-severity-warning { background: #fff3cd; color: #856404; }
        .swap-severity-high { background: #f8d7da; color: #721c24; }
        .swap-severity-critical { background: #f5c6cb; color: #721c24; }
        
        .swap-tips-list {
            margin: 0;
            padding-left: 20px;
        }
        
        .swap-tips-list li {
            margin-bottom: 8px;
            color: #666;
        }
        </style>
        <?php
    }
    
    /**
     * AJAX handler for refreshing memory stats
     * 
     * @return void
     */
    public function ajax_refresh_memory_stats(): void {
        check_ajax_referer('swap_memory_dashboard', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions'));
        }
        
        $memory_stats = SWAP_Memory_Utils::get_memory_stats();
        $memory_log = SWAP_Memory_Utils::get_memory_log(20);
        
        wp_send_json_success(array(
            'stats' => $memory_stats,
            'log' => $memory_log
        ));
    }
    
    /**
     * AJAX handler for clearing memory log
     * 
     * @return void
     */
    public function ajax_clear_memory_log(): void {
        check_ajax_referer('swap_memory_dashboard', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions'));
        }
        
        SWAP_Memory_Utils::clear_memory_log();
        
        wp_send_json_success(array(
            'message' => __('Memory log cleared successfully', 'spun-web-archive-forge')
        ));
    }
}