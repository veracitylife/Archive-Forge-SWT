<?php
/**
 * Shortcode Handler Class
 * 
 * Handles shortcodes for displaying Internet Archive links in content
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
 * Shortcode Handler class for archive-related shortcodes
 */
class SWAP_Shortcode_Handler {
    
    /**
     * Submissions history instance
     * 
     * @var SWAP_Submissions_History|null
     */
    private ?SWAP_Submissions_History $submissions_history = null;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Initialize submissions history if class exists
        if (class_exists('SWAP_Submissions_History')) {
            $this->submissions_history = new SWAP_Submissions_History();
        }
        
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     * 
     * @return void
     */
    private function init_hooks(): void {
        add_action('init', [$this, 'register_shortcodes']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_shortcode_styles']);
    }
    
    /**
     * Register all shortcodes
     * 
     * @return void
     */
    public function register_shortcodes(): void {
        add_shortcode('archive-link', [$this, 'archive_link_shortcode']);
        add_shortcode('archive-status', [$this, 'archive_status_shortcode']);
        add_shortcode('archive-list', [$this, 'archive_list_shortcode']);
        add_shortcode('archive-count', [$this, 'archive_count_shortcode']);
    }
    
    /**
     * Archive link shortcode handler
     * 
     * Usage: [archive-link post_id="123" text="View Archive" class="my-class"]
     * 
     * @param array $atts Shortcode attributes
     * @param string|null $content Shortcode content
     * @return string Shortcode output
     */
    public function archive_link_shortcode($atts, $content = null): string {
        if (!$this->submissions_history) {
            return $this->get_error_message(__('Archive system not available', 'spun-web-archive-forge'));
        }
        
        $atts = shortcode_atts([
            'post_id' => get_the_ID(),
            'text' => __('View Archived Version', 'spun-web-archive-forge'),
            'class' => 'swap-archive-link',
            'target' => '_blank',
            'show_icon' => 'true',
            'show_date' => 'false',
            'date_format' => '',
            'no_archive_text' => '',
            'style' => 'button' // button, link, badge
        ], $atts, 'archive-link');
        
        // Validate post ID
        $post_id = (int) $atts['post_id'];
        if (!$post_id || !get_post($post_id)) {
            return $this->get_error_message(__('Invalid post ID', 'spun-web-archive-forge'));
        }
        
        // Get latest submission
        $submission = $this->submissions_history->get_latest_submission($post_id);
        
        if (!$submission || $submission->status !== 'success' || empty($submission->archive_url)) {
            if (!empty($atts['no_archive_text'])) {
                return '<span class="swap-no-archive">' . esc_html($atts['no_archive_text']) . '</span>';
            }
            return '';
        }
        
        return $this->render_archive_link($submission, $atts, $content);
    }
    
    /**
     * Archive status shortcode handler
     * 
     * Usage: [archive-status post_id="123" show_date="true"]
     * 
     * @param array $atts Shortcode attributes
     * @return string Shortcode output
     */
    public function archive_status_shortcode($atts): string {
        if (!$this->submissions_history) {
            return $this->get_error_message(__('Archive system not available', 'spun-web-archive-forge'));
        }
        
        $atts = shortcode_atts([
            'post_id' => get_the_ID(),
            'show_date' => 'true',
            'show_url' => 'false',
            'date_format' => '',
            'class' => 'swap-archive-status'
        ], $atts, 'archive-status');
        
        // Validate post ID
        $post_id = (int) $atts['post_id'];
        if (!$post_id || !get_post($post_id)) {
            return $this->get_error_message(__('Invalid post ID', 'spun-web-archive-forge'));
        }
        
        // Get latest submission
        $submission = $this->submissions_history->get_latest_submission($post_id);
        
        if (!$submission) {
            return '<span class="' . esc_attr($atts['class']) . ' swap-status-none">' . 
                   __('Not archived', 'spun-web-archive-forge') . '</span>';
        }
        
        return $this->render_archive_status($submission, $atts);
    }
    
    /**
     * Archive list shortcode handler
     * 
     * Usage: [archive-list limit="5" show_dates="true" post_type="post"]
     * 
     * @param array $atts Shortcode attributes
     * @return string Shortcode output
     */
    public function archive_list_shortcode($atts): string {
        if (!$this->submissions_history) {
            return $this->get_error_message(__('Archive system not available', 'spun-web-archive-forge'));
        }
        
        $atts = shortcode_atts([
            'limit' => 10,
            'show_dates' => 'true',
            'show_status' => 'false',
            'post_type' => 'any',
            'order' => 'DESC',
            'class' => 'swap-archive-list',
            'style' => 'list' // list, grid, compact
        ], $atts, 'archive-list');
        
        $limit = max(1, min(50, (int) $atts['limit']));
        
        // Get recent successful submissions
        $submissions = $this->submissions_history->get_recent_successful_submissions($limit);
        
        if (empty($submissions)) {
            return '<p class="swap-no-archives">' . __('No archived content found.', 'spun-web-archive-forge') . '</p>';
        }
        
        return $this->render_archive_list($submissions, $atts);
    }
    
    /**
     * Archive count shortcode handler
     * 
     * Usage: [archive-count post_type="post" status="success"]
     * 
     * @param array $atts Shortcode attributes
     * @return string Shortcode output
     */
    public function archive_count_shortcode($atts): string {
        if (!$this->submissions_history) {
            return $this->get_error_message(__('Archive system not available', 'spun-web-archive-forge'));
        }
        
        $atts = shortcode_atts([
            'post_type' => 'any',
            'status' => 'success',
            'format' => 'number', // number, text
            'text_template' => '%d archived posts'
        ], $atts, 'archive-count');
        
        // Get count based on status
        $count = $this->get_archive_count($atts['status'], $atts['post_type']);
        
        if ($atts['format'] === 'text') {
            return sprintf($atts['text_template'], $count);
        }
        
        return (string) $count;
    }
    
    /**
     * Render archive link
     * 
     * @param object $submission Submission data
     * @param array $atts Shortcode attributes
     * @param string|null $content Shortcode content
     * @return string Rendered link
     */
    private function render_archive_link(object $submission, array $atts, ?string $content): string {
        $text = $content ?: $atts['text'];
        $show_icon = filter_var($atts['show_icon'], FILTER_VALIDATE_BOOLEAN);
        $show_date = filter_var($atts['show_date'], FILTER_VALIDATE_BOOLEAN);
        
        $classes = ['swap-archive-link'];
        if (!empty($atts['class'])) {
            $classes[] = $atts['class'];
        }
        if (!empty($atts['style'])) {
            $classes[] = 'swap-style-' . $atts['style'];
        }
        
        $output = '<a href="' . esc_url($submission->archive_url) . '" ';
        $output .= 'class="' . esc_attr(implode(' ', $classes)) . '" ';
        
        if ($atts['target']) {
            $output .= 'target="' . esc_attr($atts['target']) . '" ';
        }
        
        if ($atts['target'] === '_blank') {
            $output .= 'rel="noopener noreferrer" ';
        }
        
        $output .= 'title="' . esc_attr(__('View archived version on Internet Archive', 'spun-web-archive-forge')) . '">';
        
        if ($show_icon) {
            $output .= '<span class="swap-archive-icon">📚</span> ';
        }
        
        $output .= esc_html($text);
        $output .= '</a>';
        
        if ($show_date && !empty($submission->submission_date)) {
            $date_format = !empty($atts['date_format']) ? $atts['date_format'] : get_option('date_format');
            $date = date_i18n($date_format, strtotime($submission->submission_date));
            $output .= ' <small class="swap-archive-date">(' . sprintf(__('Archived: %s', 'spun-web-archive-forge'), $date) . ')</small>';
        }
        
        return $output;
    }
    
    /**
     * Render archive status
     * 
     * @param object $submission Submission data
     * @param array $atts Shortcode attributes
     * @return string Rendered status
     */
    private function render_archive_status(object $submission, array $atts): string {
        $show_date = filter_var($atts['show_date'], FILTER_VALIDATE_BOOLEAN);
        $show_url = filter_var($atts['show_url'], FILTER_VALIDATE_BOOLEAN);
        
        $status_class = 'swap-status-' . esc_attr($submission->status);
        $classes = [$atts['class'], $status_class];
        
        $output = '<span class="' . esc_attr(implode(' ', $classes)) . '">';
        
        // Status text
        $status_text = ucfirst($submission->status);
        if ($submission->status === 'success') {
            $status_text = __('Archived', 'spun-web-archive-forge');
        } elseif ($submission->status === 'failed') {
            $status_text = __('Failed', 'spun-web-archive-forge');
        } elseif ($submission->status === 'pending') {
            $status_text = __('Pending', 'spun-web-archive-forge');
        }
        
        $output .= esc_html($status_text);
        
        // Date
        if ($show_date && !empty($submission->submission_date)) {
            $date_format = !empty($atts['date_format']) ? $atts['date_format'] : get_option('date_format');
            $date = date_i18n($date_format, strtotime($submission->submission_date));
            $output .= ' <small>(' . $date . ')</small>';
        }
        
        // URL
        if ($show_url && !empty($submission->archive_url)) {
            $output .= ' <a href="' . esc_url($submission->archive_url) . '" target="_blank" rel="noopener noreferrer">';
            $output .= __('View', 'spun-web-archive-forge') . '</a>';
        }
        
        $output .= '</span>';
        
        return $output;
    }
    
    /**
     * Render archive list
     * 
     * @param array $submissions Submission data
     * @param array $atts Shortcode attributes
     * @return string Rendered list
     */
    private function render_archive_list(array $submissions, array $atts): string {
        $show_dates = filter_var($atts['show_dates'], FILTER_VALIDATE_BOOLEAN);
        $show_status = filter_var($atts['show_status'], FILTER_VALIDATE_BOOLEAN);
        
        $classes = ['swap-archive-list'];
        if (!empty($atts['class'])) {
            $classes[] = $atts['class'];
        }
        if (!empty($atts['style'])) {
            $classes[] = 'swap-style-' . $atts['style'];
        }
        
        $output = '<div class="' . esc_attr(implode(' ', $classes)) . '">';
        
        if ($atts['style'] === 'grid') {
            $output .= '<div class="swap-archive-grid">';
        } else {
            $output .= '<ul class="swap-archive-items">';
        }
        
        foreach ($submissions as $submission) {
            if (empty($submission->archive_url)) {
                continue;
            }
            
            $post = get_post($submission->post_id);
            if (!$post || $post->post_status !== 'publish') {
                continue;
            }
            
            if ($atts['style'] === 'grid') {
                $output .= '<div class="swap-archive-grid-item">';
            } else {
                $output .= '<li class="swap-archive-item">';
            }
            
            // Post title and link
            $output .= '<h4 class="swap-archive-title">';
            $output .= '<a href="' . esc_url(get_permalink($submission->post_id)) . '">';
            $output .= esc_html($post->post_title);
            $output .= '</a>';
            $output .= '</h4>';
            
            // Archive link
            $output .= '<div class="swap-archive-link-container">';
            $output .= '<a href="' . esc_url($submission->archive_url) . '" ';
            $output .= 'target="_blank" rel="noopener noreferrer" ';
            $output .= 'class="swap-archive-link">';
            $output .= '<span class="swap-archive-icon">📚</span> ';
            $output .= __('View Archive', 'spun-web-archive-forge');
            $output .= '</a>';
            $output .= '</div>';
            
            // Metadata
            if ($show_dates || $show_status) {
                $output .= '<div class="swap-archive-meta">';
                
                if ($show_dates && !empty($submission->submission_date)) {
                    $date = date_i18n(get_option('date_format'), strtotime($submission->submission_date));
                    $output .= '<span class="swap-archive-date">';
                    $output .= '<small>' . sprintf(__('Archived: %s', 'spun-web-archive-forge'), $date) . '</small>';
                    $output .= '</span>';
                }
                
                if ($show_status) {
                    $status_class = 'swap-status-' . esc_attr($submission->status);
                    $output .= '<span class="swap-archive-status ' . $status_class . '">';
                    $output .= '<small>' . esc_html(ucfirst($submission->status)) . '</small>';
                    $output .= '</span>';
                }
                
                $output .= '</div>';
            }
            
            if ($atts['style'] === 'grid') {
                $output .= '</div>';
            } else {
                $output .= '</li>';
            }
        }
        
        if ($atts['style'] === 'grid') {
            $output .= '</div>';
        } else {
            $output .= '</ul>';
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Get archive count
     * 
     * @param string $status Status filter
     * @param string $post_type Post type filter
     * @return int Archive count
     */
    private function get_archive_count(string $status, string $post_type): int {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'swap_submissions_history';
        
        $sql = "SELECT COUNT(DISTINCT post_id) FROM {$table_name} WHERE 1=1";
        $params = [];
        
        if ($status !== 'any') {
            $sql .= " AND status = %s";
            $params[] = $status;
        }
        
        if ($post_type !== 'any') {
            $sql .= " AND post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish')";
            $params[] = $post_type;
        } else {
            $sql .= " AND post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_status = 'publish')";
        }
        
        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }
        
        return (int) $wpdb->get_var($sql);
    }
    
    /**
     * Get error message
     * 
     * @param string $message Error message
     * @return string Formatted error message
     */
    private function get_error_message(string $message): string {
        if (current_user_can('manage_options')) {
            return '<span class="swap-shortcode-error" style="color: #dc3232; font-style: italic;">[' . esc_html($message) . ']</span>';
        }
        return '';
    }
    
    /**
     * Enqueue shortcode styles
     * 
     * @return void
     */
    public function enqueue_shortcode_styles(): void {
        // Check if any shortcodes are being used on this page
        global $post;
        if (!$post || !has_shortcode($post->post_content, 'archive-link') && 
            !has_shortcode($post->post_content, 'archive-status') && 
            !has_shortcode($post->post_content, 'archive-list') && 
            !has_shortcode($post->post_content, 'archive-count')) {
            return;
        }
        
        wp_add_inline_style('wp-block-library', $this->get_shortcode_css());
    }
    
    /**
     * Get shortcode CSS
     * 
     * @return string CSS styles
     */
    private function get_shortcode_css(): string {
        return '
        /* Archive Link Styles */
        .swap-archive-link {
            display: inline-block;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        
        .swap-archive-link.swap-style-button {
            padding: 8px 16px;
            background: #0073aa;
            color: white !important;
            border-radius: 4px;
            font-weight: 500;
        }
        
        .swap-archive-link.swap-style-button:hover {
            background: #005a87;
            color: white !important;
        }
        
        .swap-archive-link.swap-style-badge {
            padding: 4px 8px;
            background: #f0f0f0;
            border: 1px solid #ddd;
            border-radius: 12px;
            font-size: 12px;
            color: #333 !important;
        }
        
        .swap-archive-link.swap-style-badge:hover {
            background: #e0e0e0;
            border-color: #ccc;
        }
        
        .swap-archive-icon {
            margin-right: 4px;
        }
        
        .swap-archive-date {
            color: #666;
            font-size: 0.9em;
        }
        
        /* Archive Status Styles */
        .swap-archive-status {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .swap-status-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .swap-status-failed {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .swap-status-pending {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .swap-status-none {
            background: #f8f9fa;
            color: #6c757d;
            border: 1px solid #dee2e6;
        }
        
        /* Archive List Styles */
        .swap-archive-list ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .swap-archive-item {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 4px;
            background: #fafafa;
        }
        
        .swap-archive-title {
            margin: 0 0 10px 0;
            font-size: 16px;
        }
        
        .swap-archive-title a {
            text-decoration: none;
            color: inherit;
        }
        
        .swap-archive-title a:hover {
            text-decoration: underline;
        }
        
        .swap-archive-link-container {
            margin: 10px 0;
        }
        
        .swap-archive-meta {
            margin-top: 10px;
            font-size: 12px;
            color: #666;
        }
        
        .swap-archive-meta span {
            margin-right: 15px;
        }
        
        /* Grid Style */
        .swap-style-grid .swap-archive-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .swap-archive-grid-item {
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 4px;
            background: #fafafa;
        }
        
        /* Compact Style */
        .swap-style-compact .swap-archive-item {
            padding: 8px;
            margin-bottom: 8px;
        }
        
        .swap-style-compact .swap-archive-title {
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        /* Error Messages */
        .swap-shortcode-error {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
        }
        
        .swap-no-archive,
        .swap-no-archives {
            font-style: italic;
            color: #666;
        }
        ';
    }
    
    /**
     * Initialize the shortcode handler
     * 
     * @return SWAP_Shortcode_Handler
     */
    public static function init(): SWAP_Shortcode_Handler {
        return new self();
    }
}