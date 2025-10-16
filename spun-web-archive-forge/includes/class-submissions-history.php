<?php
/**
 * Submissions History Page Class
 *
 * Handles the display and management of archive submission history
 *
 * @package Spun_Web_Archive_Forge
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SWAP_Submissions_History {
    
    /**
     * Database table name for submissions
     */
    private $table_name;
    
    /**
     * WordPress database instance
     */
    private $wpdb;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'swap_submissions_history';
        
        // Hook for AJAX status checking
        add_action('wp_ajax_swap_check_submission_status', array($this, 'ajax_check_submission_status'));
        add_action('wp_ajax_swap_refresh_all_statuses', array($this, 'ajax_refresh_all_statuses'));
    }
    
    /**
     * Create the submissions table
     */
    public static function create_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'swap_submissions_history';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            post_title text NOT NULL,
            post_url text NOT NULL,
            submission_url text,
            archive_url text,
            status varchar(20) DEFAULT 'pending',
            submission_date datetime DEFAULT CURRENT_TIMESTAMP,
            last_checked datetime,
            error_message text,
            response_data longtext,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY status (status),
            KEY submission_date (submission_date)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Add a new submission record
     */
    public function add_submission($post_id, $post_title, $post_url, $submission_url = '') {
        $result = $this->wpdb->insert(
            $this->table_name,
            array(
                'post_id' => $post_id,
                'post_title' => sanitize_text_field($post_title),
                'post_url' => esc_url_raw($post_url),
                'submission_url' => esc_url_raw($submission_url),
                'status' => 'pending',
                'submission_date' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s')
        );
        
        return $result !== false ? $this->wpdb->insert_id : false;
    }
    
    /**
     * Update submission status
     */
    public function update_submission_status($id, $status, $archive_url = '', $error_message = '', $response_data = '') {
        $update_data = array(
            'status' => $status,
            'last_checked' => current_time('mysql')
        );
        
        if (!empty($archive_url)) {
            $update_data['archive_url'] = esc_url_raw($archive_url);
        }
        
        if (!empty($error_message)) {
            $update_data['error_message'] = sanitize_text_field($error_message);
        }
        
        if (!empty($response_data)) {
            $update_data['response_data'] = $response_data;
        }
        
        return $this->wpdb->update(
            $this->table_name,
            $update_data,
            array('id' => $id),
            array('%s', '%s', '%s', '%s', '%s'),
            array('%d')
        );
    }
    
    /**
     * Get all submissions with pagination and memory optimization
     */
    public function get_submissions($args = array()) {
        // Check memory usage before processing
        if (function_exists('swap_check_memory_usage') && !swap_check_memory_usage('SWAP_Submissions_History::get_submissions')) {
            error_log('SWAP: Submissions query aborted due to high memory usage');
            return [];
        }
        
        // Handle both old and new parameter formats
        if (is_numeric($args)) {
            // Old format: get_submissions($page, $per_page, $status_filter)
            $page = $args;
            $per_page = func_num_args() > 1 ? func_get_arg(1) : 20;
            $status_filter = func_num_args() > 2 ? func_get_arg(2) : '';
            $args = array(
                'page' => $page,
                'per_page' => $per_page,
                'status' => $status_filter
            );
        }
        
        // Default arguments with memory-conscious limits
        $defaults = array(
            'page' => 1,
            'per_page' => 20,
            'status' => '',
            'archive_url' => null
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Enforce memory-safe limits
        $max_per_page = 100; // Maximum items per page to prevent memory issues
        $memory_usage = memory_get_usage(true);
        $memory_limit = wp_convert_hr_to_bytes(ini_get('memory_limit'));
        
        if ($memory_limit > 0) {
            $usage_percentage = ($memory_usage / $memory_limit) * 100;
            if ($usage_percentage > 70) {
                $max_per_page = 25; // Reduce limit if memory usage is high
            } elseif ($usage_percentage > 50) {
                $max_per_page = 50;
            }
        }
        
        $args['per_page'] = min($args['per_page'], $max_per_page);
        
        $offset = ($args['page'] - 1) * $args['per_page'];
        
        $where_conditions = array();
        
        if (!empty($args['status']) && $args['status'] !== 'all') {
            $where_conditions[] = $this->wpdb->prepare("status = %s", $args['status']);
        }
        
        if ($args['archive_url'] === '') {
            // Filter for empty archive URLs
            $where_conditions[] = "(archive_url IS NULL OR archive_url = '')";
        } elseif (!is_null($args['archive_url'])) {
            $where_conditions[] = $this->wpdb->prepare("archive_url = %s", $args['archive_url']);
        }
        
        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }
        
        $sql = "SELECT * FROM {$this->table_name} {$where_clause} ORDER BY submission_date DESC LIMIT %d OFFSET %d";
        
        $results = $this->wpdb->get_results($this->wpdb->prepare($sql, $args['per_page'], $offset));
        
        // Check memory usage after query
        if (function_exists('swap_check_memory_usage') && !swap_check_memory_usage('SWAP_Submissions_History::get_submissions_after_query')) {
            error_log('SWAP: High memory usage detected after submissions query');
        }
        
        return $results;
    }
    
    /**
     * Get total submissions count
     */
    public function get_total_submissions($status_filter = '') {
        $where_clause = '';
        if (!empty($status_filter) && $status_filter !== 'all') {
            $where_clause = $this->wpdb->prepare("WHERE status = %s", $status_filter);
        }
        
        $sql = "SELECT COUNT(*) FROM {$this->table_name} {$where_clause}";
        
        return $this->wpdb->get_var($sql);
    }
    
    /**
     * Get submission statistics
     */
    public function get_submission_stats() {
        $stats = array(
            'total' => 0,
            'pending' => 0,
            'success' => 0,
            'failed' => 0
        );
        
        $results = $this->wpdb->get_results("SELECT status, COUNT(*) as count FROM {$this->table_name} GROUP BY status");
        
        foreach ($results as $result) {
            $stats['total'] += $result->count;
            $stats[$result->status] = $result->count;
        }
        
        return $stats;
    }
    
    /**
     * Check if post was recently submitted (within the last hour)
     */
    public function is_recently_submitted($post_id) {
        $recent_submission = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT id FROM {$this->table_name} 
             WHERE post_id = %d 
             AND submission_date > DATE_SUB(NOW(), INTERVAL 1 HOUR)
             ORDER BY submission_date DESC 
             LIMIT 1",
            $post_id
        ));
        
        return !empty($recent_submission);
    }
    
    /**
     * Get submissions for a specific post
     */
    public function get_post_submissions($post_id) {
        $results = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE post_id = %d ORDER BY submission_date DESC",
            $post_id
        ));
        
        return $results;
    }
    
    /**
     * Get latest submission for a specific post
     */
    public function get_latest_submission($post_id) {
        $result = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE post_id = %d ORDER BY submission_date DESC LIMIT 1",
            $post_id
        ));
        
        return $result;
    }
    
    /**
     * Render the submissions history page
     */
    public function render() {
        // Handle bulk actions
        if (isset($_POST['action']) && $_POST['action'] === 'bulk_refresh') {
            $this->handle_bulk_refresh();
        }
        
        // Get current page and filters
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'all';
        $per_page = 20;
        
        // Get submissions and stats
        $submissions = $this->get_submissions($current_page, $per_page, $status_filter);
        $total_submissions = $this->get_total_submissions($status_filter);
        $stats = $this->get_submission_stats();
        
        // Calculate pagination
        $total_pages = ceil($total_submissions / $per_page);
        
        ?>
        <div class="wrap">
            <h1><?php _e('Archive Submissions History', 'spun-web-archive-forge'); ?></h1>
            
            <!-- Statistics Cards -->
            <div class="swap-stats-cards" style="display: flex; gap: 20px; margin: 20px 0;">
                <div class="swap-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; flex: 1;">
                    <h3 style="margin: 0 0 10px 0; color: #1d2327;"><?php echo number_format($stats['total']); ?></h3>
                    <p style="margin: 0; color: #646970;"><?php _e('Total Submissions', 'spun-web-archive-forge'); ?></p>
                </div>
                <div class="swap-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; flex: 1;">
                    <h3 style="margin: 0 0 10px 0; color: #00a32a;"><?php echo number_format($stats['success'] ?? 0); ?></h3>
                    <p style="margin: 0; color: #646970;"><?php _e('Successful', 'spun-web-archive-forge'); ?></p>
                </div>
                <div class="swap-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; flex: 1;">
                    <h3 style="margin: 0 0 10px 0; color: #d63638;"><?php echo number_format($stats['failed'] ?? 0); ?></h3>
                    <p style="margin: 0; color: #646970;"><?php _e('Failed', 'spun-web-archive-forge'); ?></p>
                </div>
                <div class="swap-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; flex: 1;">
                    <h3 style="margin: 0 0 10px 0; color: #dba617;"><?php echo number_format($stats['pending'] ?? 0); ?></h3>
                    <p style="margin: 0; color: #646970;"><?php _e('Pending', 'spun-web-archive-forge'); ?></p>
                </div>
            </div>
            
            <!-- Filters and Actions -->
            <div class="tablenav top">
                <div class="alignleft actions">
                    <form method="get" style="display: inline-block;">
                        <input type="hidden" name="page" value="spun-web-archive-forge-history" />
			<select name="status" onchange="this.form.submit()">
				<option value="all" <?php selected($status_filter, 'all'); ?>><?php _e('All Statuses', 'spun-web-archive-forge'); ?></option>
				<option value="pending" <?php selected($status_filter, 'pending'); ?>><?php _e('Pending', 'spun-web-archive-forge'); ?></option>
				<option value="success" <?php selected($status_filter, 'success'); ?>><?php _e('Successful', 'spun-web-archive-forge'); ?></option>
				<option value="failed" <?php selected($status_filter, 'failed'); ?>><?php _e('Failed', 'spun-web-archive-forge'); ?></option>
                        </select>
                    </form>
                    
                    <button type="button" class="button" id="refresh-all-statuses">
                        <?php _e('Refresh All Statuses', 'spun-web-archive-forge'); ?>
                    </button>
                    
                    <a href="<?php echo esc_url(add_query_arg(array('action' => 'swap_export_csv', 'nonce' => wp_create_nonce('swap_export_csv')), admin_url('admin.php'))); ?>" 
                       class="button" style="margin-left: 10px;">
                        <?php _e('Export CSV', 'spun-web-archive-forge'); ?>
                    </a>
                </div>
                
                <?php if ($total_pages > 1): ?>
                <div class="tablenav-pages">
                    <?php
                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => __('&laquo;'),
                        'next_text' => __('&raquo;'),
                        'total' => $total_pages,
                        'current' => $current_page
                    ));
                    ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Submissions Table -->
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col" style="width: 40%;"><?php _e('Post Title', 'spun-web-archive-forge'); ?></th>
                        <th scope="col" style="width: 15%;"><?php _e('Status', 'spun-web-archive-forge'); ?></th>
                        <th scope="col" style="width: 20%;"><?php _e('Submission Date', 'spun-web-archive-forge'); ?></th>
                        <th scope="col" style="width: 15%;"><?php _e('Last Checked', 'spun-web-archive-forge'); ?></th>
                        <th scope="col" style="width: 10%;"><?php _e('Actions', 'spun-web-archive-forge'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($submissions)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 40px;">
                            <?php _e('No submissions found.', 'spun-web-archive-forge'); ?>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($submissions as $submission): ?>
                    <tr data-submission-id="<?php echo esc_attr($submission->id); ?>">
                        <td>
                            <strong><?php echo esc_html($submission->post_title); ?></strong>
                            <div style="margin-top: 8px;">
                                <div style="margin-bottom: 4px;">
                                    <strong style="color: #2271b1;"><?php _e('Live Page:', 'spun-web-archive-forge'); ?></strong>
                                    <a href="<?php echo esc_url($submission->post_url); ?>" target="_blank" style="margin-left: 5px;">
                                        <?php echo esc_html($submission->post_url); ?>
                                    </a>
                                </div>
                                <?php if (!empty($submission->archive_url)): ?>
                                <div>
                                    <strong style="color: #d63638;"><?php _e('Archived Page:', 'spun-web-archive-forge'); ?></strong>
                                    <a href="<?php echo esc_url($submission->archive_url); ?>" target="_blank" style="margin-left: 5px;">
                                        <?php echo esc_html($submission->archive_url); ?>
                                    </a>
                                </div>
                                <?php else: ?>
                                <div>
                                    <strong style="color: #d63638;"><?php _e('Archived Page:', 'spun-web-archive-forge'); ?></strong>
                                    <span style="color: #646970; margin-left: 5px; font-style: italic;">
                                        <?php _e('Not yet available', 'spun-web-archive-forge'); ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <?php echo $this->render_status_indicator($submission->status, $submission->error_message); ?>
                        </td>
                        <td>
                            <?php echo esc_html(mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $submission->submission_date)); ?>
                        </td>
                        <td>
                            <?php 
                            if ($submission->last_checked) {
                                echo esc_html(mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $submission->last_checked));
                            } else {
                                echo '<span style="color: #646970;">' . __('Never', 'spun-web-archive-forge') . '</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <button type="button" class="button button-small refresh-status" data-submission-id="<?php echo esc_attr($submission->id); ?>">
                                <?php _e('Refresh', 'spun-web-archive-forge'); ?>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <!-- Bottom pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?php
                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => __('&laquo;'),
                        'next_text' => __('&raquo;'),
                        'total' => $total_pages,
                        'current' => $current_page
                    ));
                    ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Individual status refresh
            $('.refresh-status').on('click', function() {
                var button = $(this);
                var submissionId = button.data('submission-id');
                
                button.prop('disabled', true).text('<?php _e('Checking...', 'spun-web-archive-forge'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'swap_check_submission_status',
                        submission_id: submissionId,
                        nonce: '<?php echo wp_create_nonce('swap_check_status'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('<?php _e('Error checking status', 'spun-web-archive-forge'); ?>');
                        }
                    },
                    complete: function() {
                        button.prop('disabled', false).text('<?php _e('Refresh', 'spun-web-archive-forge'); ?>');
                    }
                });
            });
            
            // Bulk status refresh
            $('#refresh-all-statuses').on('click', function() {
                var button = $(this);
                
                if (!confirm('<?php _e('This will check the status of all pending submissions. This may take a while. Continue?', 'spun-web-archive-forge'); ?>')) {
                    return;
                }
                
                button.prop('disabled', true).text('<?php _e('Refreshing...', 'spun-web-archive-forge'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'swap_refresh_all_statuses',
                        nonce: '<?php echo wp_create_nonce('swap_refresh_all'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('<?php _e('Error refreshing statuses', 'spun-web-archive-forge'); ?>');
                        }
                    },
                    complete: function() {
                        button.prop('disabled', false).text('<?php _e('Refresh All Statuses', 'spun-web-archive-forge'); ?>');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render status indicator
     */
    private function render_status_indicator($status, $error_message = '') {
        $indicators = array(
            'pending' => array(
                'color' => '#dba617',
                'icon' => '⏳',
                'text' => __('Pending', 'spun-web-archive-forge')
            ),
            'success' => array(
                'color' => '#00a32a',
                'icon' => '✅',
                'text' => __('Success', 'spun-web-archive-forge')
            ),
            'failed' => array(
                'color' => '#d63638',
                'icon' => '❌',
                'text' => __('Failed', 'spun-web-archive-forge')
            )
        );
        
        $indicator = isset($indicators[$status]) ? $indicators[$status] : $indicators['pending'];
        
        $output = '<span style="color: ' . esc_attr($indicator['color']) . '; font-weight: bold;">';
        $output .= $indicator['icon'] . ' ' . $indicator['text'];
        $output .= '</span>';
        
        if ($status === 'failed' && !empty($error_message)) {
            $output .= '<br><small style="color: #d63638;">' . esc_html($error_message) . '</small>';
        }
        
        return $output;
    }
    
    /**
     * AJAX handler for checking individual submission status
     */
    public function ajax_check_submission_status() {
        check_ajax_referer('swap_check_status', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'spun-web-archive-forge'));
        }
        
        $submission_id = intval($_POST['submission_id']);
        
        // Get submission details
        $submission = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $submission_id
        ));
        
        if (!$submission) {
            wp_send_json_error(__('Submission not found', 'spun-web-archive-forge'));
        }
        
        // Check status with Internet Archive
        $this->check_submission_status($submission);
        
        wp_send_json_success();
    }
    
    /**
     * AJAX handler for refreshing all statuses
     */
    public function ajax_refresh_all_statuses() {
        check_ajax_referer('swap_refresh_all', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'spun-web-archive-forge'));
        }
        
        // Get all pending submissions
        $pending_submissions = $this->wpdb->get_results(
            "SELECT * FROM {$this->table_name} WHERE status = 'pending' ORDER BY submission_date DESC LIMIT 50"
        );
        
        foreach ($pending_submissions as $submission) {
            $this->check_submission_status($submission);
            // Small delay to avoid overwhelming the API
            usleep(500000); // 0.5 seconds
        }
        
        wp_send_json_success();
    }
    
    /**
     * Check submission status with Internet Archive
     */
    private function check_submission_status($submission) {
        if (empty($submission->submission_url)) {
            return;
        }
        
        // Try to get the archived URL
        $archive_url = $this->get_archive_url($submission->post_url);
        
        if ($archive_url) {
            $this->update_submission_status($submission->id, 'success', $archive_url);
        } else {
            // Check if enough time has passed (submissions can take time to process)
            $submission_time = strtotime($submission->submission_date);
            $hours_passed = (time() - $submission_time) / 3600;
            
            if ($hours_passed > 24) {
                // Mark as failed if more than 24 hours have passed
                $this->update_submission_status($submission->id, 'failed', '', __('Archive not found after 24 hours', 'spun-web-archive-forge'));
            }
            // Otherwise, keep as pending
        }
    }
    
    /**
     * Get archive URL from Internet Archive
     */
    private function get_archive_url($url) {
        // Prefer centralized API client for availability checks
        if (class_exists('SWAP_Archive_API')) {
            try {
                $api = new SWAP_Archive_API();
                $result = $api->check_availability($url);
                if (!empty($result['success']) && !empty($result['archive_url'])) {
                    return $result['archive_url'];
                }
            } catch (Exception $e) {
                // Fallback to direct request on exception
            }
        }

        // Fallback: direct HTTPS request to Wayback availability API
        $api_url = 'https://archive.org/wayback/available?url=' . urlencode($url);

        $response = wp_remote_get($api_url, array(
            'timeout' => 30,
            'user-agent' => 'Spun Web Archive Forge WordPress Plugin'
        ));

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['archived_snapshots']['closest']['url'])) {
            return $data['archived_snapshots']['closest']['url'];
        }

        return false;
    }
    
    /**
     * Handle bulk refresh action
     */
    private function handle_bulk_refresh() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // This would be called from the form submission
        // Implementation would be similar to ajax_refresh_all_statuses
    }
}
