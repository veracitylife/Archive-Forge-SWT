<?php
/**
 * Submission Tracker Handler
 * 
 * Handles tracking and display of submission records for posts and pages.
 * 
 * @package SpunWebArchiveElite
 * @subpackage Includes
 * @author Ryan Dickie Thompson
 * @copyright 2024 Spun Web Technology
 * @license GPL-2.0-or-later
 * @since 0.2.0
 * @version 0.6.1
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SWAP_Submission_Tracker {
    
    /**
     * WordPress database instance
     */
    private $wpdb;
    
    /**
     * Submissions history instance
     */
    private $submissions_history;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->submissions_history = new SWAP_Submissions_History();
        add_action('init', array($this, 'init'));
    }
    
    /**
     * Initialize hooks
     */
    public function init() {
        // Add meta boxes to post/page edit screens
        add_action('add_meta_boxes', array($this, 'add_submission_meta_box'));
        
        // Add columns to posts/pages list
        add_filter('manage_posts_columns', array($this, 'add_submission_column'));
        add_filter('manage_pages_columns', array($this, 'add_submission_column'));
        add_action('manage_posts_custom_column', array($this, 'display_submission_column'), 10, 2);
        add_action('manage_pages_custom_column', array($this, 'display_submission_column'), 10, 2);
        
        // Make column sortable
        add_filter('manage_edit-post_sortable_columns', array($this, 'make_submission_column_sortable'));
        add_filter('manage_edit-page_sortable_columns', array($this, 'make_submission_column_sortable'));
        
        // Handle sorting
        add_action('pre_get_posts', array($this, 'handle_submission_column_sorting'));
        
        // Add admin styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
    }
    
    /**
     * Add submission history meta box
     */
    public function add_submission_meta_box() {
        $post_types = get_post_types(array('public' => true), 'names');
        
        foreach ($post_types as $post_type) {
            add_meta_box(
                'swap_submission_history',
                __('Archive Submission History', 'spun-web-archive-forge'),
                array($this, 'display_submission_meta_box'),
                $post_type,
                'side',
                'default'
            );
        }
    }
    
    /**
     * Display submission history meta box
     */
    public function display_submission_meta_box($post) {
        $submissions = $this->get_post_submissions($post->ID);
        
        echo '<div class="swap-submission-history">';
        
        if (empty($submissions)) {
            echo '<p>' . __('No submissions found for this content.', 'spun-web-archive-forge') . '</p>';
        } else {
            echo '<div class="swap-submissions-list">';
            
            foreach ($submissions as $submission) {
                $status_class = 'swap-status-' . $submission->status;
                $status_text = $this->get_status_text($submission->status);
                
                echo '<div class="swap-submission-item ' . esc_attr($status_class) . '">';
                echo '<div class="swap-submission-status">';
                echo '<span class="swap-status-indicator"></span>';
                echo '<strong>' . esc_html($status_text) . '</strong>';
                echo '</div>';
                
                echo '<div class="swap-submission-date">';
                echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($submission->submission_date)));
                echo '</div>';
                
                if (!empty($submission->archive_url)) {
                    echo '<div class="swap-submission-link">';
                    echo '<a href="' . esc_url($submission->archive_url) . '" target="_blank" class="button button-small">';
                    echo __('View Archive', 'spun-web-archive-forge');
                    echo '</a>';
                    echo '</div>';
                }
                
                echo '</div>';
            }
            
            echo '</div>';
        }
        
        // Add current status summary
        $latest_submission = !empty($submissions) ? $submissions[0] : null;
        if ($latest_submission) {
            echo '<div class="swap-current-status">';
            echo '<h4>' . __('Current Status', 'spun-web-archive-forge') . '</h4>';
            echo '<p><strong>' . $this->get_status_text($latest_submission->status) . '</strong></p>';
            
            if ($latest_submission->status === 'success' && !empty($latest_submission->archive_url)) {
                echo '<p><a href="' . esc_url($latest_submission->archive_url) . '" target="_blank">';
                echo __('View in Wayback Machine', 'spun-web-archive-forge');
                echo '</a></p>';
            }
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    /**
     * Add submission status column
     */
    public function add_submission_column($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            
            // Add after title column
            if ($key === 'title') {
                $new_columns['swap_submission_status'] = __('Archive Status', 'spun-web-archive-forge');
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Display submission status column
     */
    public function display_submission_column($column, $post_id) {
        if ($column === 'swap_submission_status') {
            $latest_submission = $this->get_latest_submission($post_id);
            
            if ($latest_submission) {
                $status_class = 'swap-status-' . $latest_submission->status;
                $status_text = $this->get_status_text($latest_submission->status);
                
                echo '<div class="swap-column-status ' . esc_attr($status_class) . '">';
                echo '<span class="swap-status-indicator"></span>';
                echo '<span class="swap-status-text">' . esc_html($status_text) . '</span>';
                
                if ($latest_submission->status === 'success' && !empty($latest_submission->archive_url)) {
                    echo '<br><a href="' . esc_url($latest_submission->archive_url) . '" target="_blank" class="swap-archive-link">';
                    echo __('View Archive', 'spun-web-archive-forge');
                    echo '</a>';
                }
                
                // Show submission date
                echo '<br><small class="swap-submission-date">';
                echo esc_html(date_i18n(get_option('date_format'), strtotime($latest_submission->submission_date)));
                echo '</small>';
                
                echo '</div>';
            } else {
                echo '<div class="swap-column-status swap-status-none">';
                echo '<span class="swap-status-indicator"></span>';
                echo '<span class="swap-status-text">' . __('Not Submitted', 'spun-web-archive-forge') . '</span>';
                echo '</div>';
            }
        }
    }
    
    /**
     * Make submission column sortable
     */
    public function make_submission_column_sortable($columns) {
        $columns['swap_submission_status'] = 'swap_submission_status';
        return $columns;
    }
    
    /**
     * Handle submission column sorting
     */
    public function handle_submission_column_sorting($query) {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }
        
        if ($query->get('orderby') === 'swap_submission_status') {
            global $wpdb;
            
            $query->set('meta_key', '_swap_archive_status');
            $query->set('orderby', 'meta_value');
            
            // Join with submissions table for more accurate sorting
            add_filter('posts_join', array($this, 'join_submissions_table'));
            add_filter('posts_orderby', array($this, 'orderby_submission_status'));
            add_filter('posts_groupby', array($this, 'groupby_post_id'));
        }
    }
    
    /**
     * Join submissions table for sorting
     */
    public function join_submissions_table($join) {
        global $wpdb;
        
        $submissions_table = $wpdb->prefix . 'swap_submissions_history';
        $join .= " LEFT JOIN (
            SELECT post_id, MAX(submission_date) as latest_submission, status
            FROM {$submissions_table}
            GROUP BY post_id
        ) as latest_submissions ON $wpdb->posts.ID = latest_submissions.post_id";
        
        return $join;
    }
    
    /**
     * Order by submission status
     */
    public function orderby_submission_status($orderby) {
        global $wpdb;
        
        $order = isset($_GET['order']) && $_GET['order'] === 'desc' ? 'DESC' : 'ASC';
        $orderby = "latest_submissions.status {$order}, latest_submissions.latest_submission {$order}";
        
        return $orderby;
    }
    
    /**
     * Group by post ID
     */
    public function groupby_post_id($groupby) {
        if (!$groupby) {
            $groupby = "{$this->wpdb->posts}.ID";
        }
        
        return $groupby;
    }
    
    /**
     * Get submissions for a post
     */
    public function get_post_submissions($post_id) {
        return $this->submissions_history->get_post_submissions($post_id);
    }
    
    /**
     * Get latest submission for a post
     */
    public function get_latest_submission($post_id) {
        return $this->submissions_history->get_latest_submission($post_id);
    }
    
    /**
     * Get status text
     */
    private function get_status_text($status) {
        $status_texts = array(
            'success' => __('Archived', 'spun-web-archive-forge'),
            'failed' => __('Failed', 'spun-web-archive-forge'),
            'pending' => __('Pending', 'spun-web-archive-forge'),
            'processing' => __('Processing', 'spun-web-archive-forge')
        );
        
        return isset($status_texts[$status]) ? $status_texts[$status] : ucfirst($status);
    }
    
    /**
     * Enqueue admin styles
     */
    public function enqueue_admin_styles($hook) {
        if (in_array($hook, array('edit.php', 'post.php', 'post-new.php'))) {
            wp_add_inline_style('wp-admin', $this->get_admin_css());
        }
    }
    
    /**
     * Get admin CSS
     */
    private function get_admin_css() {
        return '
        .swap-submission-history {
            font-size: 13px;
        }
        
        .swap-submission-item {
            padding: 8px;
            margin-bottom: 8px;
            border: 1px solid #ddd;
            border-radius: 3px;
            background: #f9f9f9;
        }
        
        .swap-submission-status {
            display: flex;
            align-items: center;
            margin-bottom: 4px;
        }
        
        .swap-status-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 6px;
            display: inline-block;
        }
        
        .swap-status-success .swap-status-indicator {
            background-color: #46b450;
        }
        
        .swap-status-failed .swap-status-indicator {
            background-color: #dc3232;
        }
        
        .swap-status-pending .swap-status-indicator {
            background-color: #ffb900;
        }
        
        .swap-status-processing .swap-status-indicator {
            background-color: #00a0d2;
        }
        
        .swap-status-none .swap-status-indicator {
            background-color: #72777c;
        }
        
        .swap-submission-date {
            color: #666;
            font-size: 11px;
        }
        
        .swap-submission-link {
            margin-top: 4px;
        }
        
        .swap-current-status {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #ddd;
        }
        
        .swap-current-status h4 {
            margin: 0 0 6px 0;
            font-size: 12px;
            text-transform: uppercase;
            color: #666;
        }
        
        .swap-column-status {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }
        
        .swap-column-status .swap-status-indicator {
            margin-right: 4px;
        }
        
        .swap-archive-link {
            font-size: 11px;
            text-decoration: none;
            color: #0073aa;
        }
        
        .swap-archive-link:hover {
            color: #005177;
        }
        
        .column-swap_submission_status {
            width: 120px;
        }
        ';
    }
    
    /**
     * Get submission statistics
     */
    public function get_submission_stats() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'swap_submissions_history';
        
        $stats = $wpdb->get_row("
            SELECT 
                COUNT(*) as total_submissions,
                SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
            FROM {$table_name}
        ");
        
        return $stats;
    }
}
