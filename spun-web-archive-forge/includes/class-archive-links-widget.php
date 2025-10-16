<?php
/**
 * Archive Links Widget Class
 * 
 * WordPress widget for displaying Internet Archive links for individual posts/pages
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
 * Archive Links Widget class for displaying Internet Archive links for content
 */
class SWAP_Archive_Links_Widget extends WP_Widget {
    
    /**
     * Submissions history instance
     * 
     * @var SWAP_Submissions_History|null
     */
    private ?SWAP_Submissions_History $submissions_history = null;
    
    /**
     * Widget constructor
     */
    public function __construct() {
        parent::__construct(
            'swap_archive_links',
            __('Archive Content Links', 'spun-web-archive-forge'),
            array(
                'description' => __('Display links to archived versions of your content on the Internet Archive.', 'spun-web-archive-forge'),
            )
        );
        
        // Initialize submissions history if class exists
        if (class_exists('SWAP_Submissions_History')) {
            $this->submissions_history = new SWAP_Submissions_History();
        }
        
        // Enqueue widget styles
        add_action('wp_enqueue_scripts', [$this, 'enqueue_widget_styles']);
    }
    
    /**
     * Display the widget on the frontend
     * 
     * @param array $args Widget arguments
     * @param array $instance Widget instance settings
     * @return void
     */
    public function widget($args, $instance): void {
        // Check if submissions history is available
        if (!$this->submissions_history) {
            return;
        }
        
        // Extract widget settings
        $title = !empty($instance['title']) ? $instance['title'] : __('Archived Content', 'spun-web-archive-forge');
        $display_mode = $instance['display_mode'] ?? 'current_post';
        $show_date = $instance['show_date'] ?? true;
        $show_status = $instance['show_status'] ?? false;
        $max_items = (int) ($instance['max_items'] ?? 5);
        $link_text = $instance['link_text'] ?? __('View Archived Version', 'spun-web-archive-forge');
        $no_archive_text = $instance['no_archive_text'] ?? __('No archived version available', 'spun-web-archive-forge');
        
        // Get archive data based on display mode
        $archive_data = $this->get_archive_data($display_mode, $max_items);
        
        // Only display widget if there's content to show
        if (empty($archive_data) && $display_mode === 'current_post') {
            return;
        }
        
        echo $args['before_widget'];
        
        // Display title
        if (!empty($title)) {
            echo $args['before_title'] . apply_filters('widget_title', $title) . $args['after_title'];
        }
        
        echo '<div class="swap-archive-links-widget-content">';
        
        if (!empty($archive_data)) {
            $this->render_archive_links($archive_data, [
                'show_date' => $show_date,
                'show_status' => $show_status,
                'link_text' => $link_text,
                'display_mode' => $display_mode
            ]);
        } else {
            echo '<p class="swap-no-archive">' . esc_html($no_archive_text) . '</p>';
        }
        
        echo '</div>';
        echo $args['after_widget'];
    }
    
    /**
     * Display the widget form in the admin
     * 
     * @param array $instance Widget instance settings
     * @return void
     */
    public function form($instance): void {
        // Default values
        $title = $instance['title'] ?? __('Archived Content', 'spun-web-archive-forge');
        $display_mode = $instance['display_mode'] ?? 'current_post';
        $show_date = $instance['show_date'] ?? true;
        $show_status = $instance['show_status'] ?? false;
        $max_items = $instance['max_items'] ?? 5;
        $link_text = $instance['link_text'] ?? __('View Archived Version', 'spun-web-archive-forge');
        $no_archive_text = $instance['no_archive_text'] ?? __('No archived version available', 'spun-web-archive-forge');
        ?>
        
        <?php if (!class_exists('SWAP_Submissions_History')): ?>
            <div style="padding: 10px; background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; margin-bottom: 15px;">
                <p style="margin: 0; color: #856404;">
                    <strong><?php esc_html_e('Plugin Not Fully Loaded:', 'spun-web-archive-forge'); ?></strong><br>
                    <?php esc_html_e('The archive submission system is not available. Please ensure the plugin is properly activated.', 'spun-web-archive-forge'); ?>
                </p>
            </div>
        <?php endif; ?>
        
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">
                <?php esc_html_e('Title:', 'spun-web-archive-forge'); ?>
            </label>
            <input class="widefat" 
                   id="<?php echo esc_attr($this->get_field_id('title')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" 
                   type="text" 
                   value="<?php echo esc_attr($title); ?>">
        </p>
        
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('display_mode')); ?>">
                <?php esc_html_e('Display Mode:', 'spun-web-archive-forge'); ?>
            </label>
            <select class="widefat" 
                    id="<?php echo esc_attr($this->get_field_id('display_mode')); ?>" 
                    name="<?php echo esc_attr($this->get_field_name('display_mode')); ?>">
                <option value="current_post" <?php selected($display_mode, 'current_post'); ?>>
                    <?php esc_html_e('Current Post Only', 'spun-web-archive-forge'); ?>
                </option>
                <option value="recent_archives" <?php selected($display_mode, 'recent_archives'); ?>>
                    <?php esc_html_e('Recent Archives', 'spun-web-archive-forge'); ?>
                </option>
                <option value="popular_archives" <?php selected($display_mode, 'popular_archives'); ?>>
                    <?php esc_html_e('Most Viewed Archives', 'spun-web-archive-forge'); ?>
                </option>
            </select>
        </p>
        
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('max_items')); ?>">
                <?php esc_html_e('Maximum Items:', 'spun-web-archive-forge'); ?>
            </label>
            <input class="tiny-text" 
                   id="<?php echo esc_attr($this->get_field_id('max_items')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('max_items')); ?>" 
                   type="number" 
                   min="1" 
                   max="20" 
                   value="<?php echo esc_attr($max_items); ?>">
            <br><small><?php esc_html_e('Only applies to Recent Archives and Most Viewed Archives modes', 'spun-web-archive-forge'); ?></small>
        </p>
        
        <p>
            <input class="checkbox" 
                   type="checkbox" 
                   <?php checked($show_date); ?> 
                   id="<?php echo esc_attr($this->get_field_id('show_date')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('show_date')); ?>">
            <label for="<?php echo esc_attr($this->get_field_id('show_date')); ?>">
                <?php esc_html_e('Show Archive Date', 'spun-web-archive-forge'); ?>
            </label>
        </p>
        
        <p>
            <input class="checkbox" 
                   type="checkbox" 
                   <?php checked($show_status); ?> 
                   id="<?php echo esc_attr($this->get_field_id('show_status')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('show_status')); ?>">
            <label for="<?php echo esc_attr($this->get_field_id('show_status')); ?>">
                <?php esc_html_e('Show Archive Status', 'spun-web-archive-forge'); ?>
            </label>
        </p>
        
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('link_text')); ?>">
                <?php esc_html_e('Link Text:', 'spun-web-archive-forge'); ?>
            </label>
            <input class="widefat" 
                   id="<?php echo esc_attr($this->get_field_id('link_text')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('link_text')); ?>" 
                   type="text" 
                   value="<?php echo esc_attr($link_text); ?>">
        </p>
        
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('no_archive_text')); ?>">
                <?php esc_html_e('No Archive Text:', 'spun-web-archive-forge'); ?>
            </label>
            <input class="widefat" 
                   id="<?php echo esc_attr($this->get_field_id('no_archive_text')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('no_archive_text')); ?>" 
                   type="text" 
                   value="<?php echo esc_attr($no_archive_text); ?>">
        </p>
        
        <?php
    }
    
    /**
     * Update widget settings
     * 
     * @param array $new_instance New widget settings
     * @param array $old_instance Old widget settings
     * @return array Updated settings
     */
    public function update($new_instance, $old_instance): array {
        $instance = [];
        
        $instance['title'] = !empty($new_instance['title']) ? 
            sanitize_text_field($new_instance['title']) : '';
        
        $instance['display_mode'] = in_array($new_instance['display_mode'], 
            ['current_post', 'recent_archives', 'popular_archives']) ? 
            $new_instance['display_mode'] : 'current_post';
        
        $instance['show_date'] = !empty($new_instance['show_date']);
        $instance['show_status'] = !empty($new_instance['show_status']);
        
        $instance['max_items'] = max(1, min(20, (int) $new_instance['max_items']));
        
        $instance['link_text'] = !empty($new_instance['link_text']) ? 
            sanitize_text_field($new_instance['link_text']) : 
            __('View Archived Version', 'spun-web-archive-forge');
        
        $instance['no_archive_text'] = !empty($new_instance['no_archive_text']) ? 
            sanitize_text_field($new_instance['no_archive_text']) : 
            __('No archived version available', 'spun-web-archive-forge');
        
        return $instance;
    }
    
    /**
     * Get archive data based on display mode
     * 
     * @param string $display_mode Display mode
     * @param int $max_items Maximum items to return
     * @return array Archive data
     */
    private function get_archive_data(string $display_mode, int $max_items): array {
        if (!$this->submissions_history) {
            return [];
        }
        
        switch ($display_mode) {
            case 'current_post':
                return $this->get_current_post_archive();
                
            case 'recent_archives':
                return $this->get_recent_archives($max_items);
                
            case 'popular_archives':
                return $this->get_popular_archives($max_items);
                
            default:
                return [];
        }
    }
    
    /**
     * Get archive data for current post
     * 
     * @return array Archive data
     */
    private function get_current_post_archive(): array {
        if (!is_singular()) {
            return [];
        }
        
        $post_id = get_the_ID();
        if (!$post_id) {
            return [];
        }
        
        $submission = $this->submissions_history->get_latest_submission($post_id);
        
        if (!$submission || $submission->status !== 'success' || empty($submission->archive_url)) {
            return [];
        }
        
        return [[
            'post_id' => $post_id,
            'post_title' => get_the_title($post_id),
            'post_url' => get_permalink($post_id),
            'archive_url' => $submission->archive_url,
            'submission_date' => $submission->submission_date,
            'status' => $submission->status
        ]];
    }
    
    /**
     * Get recent archives
     * 
     * @param int $limit Maximum number of items
     * @return array Archive data
     */
    private function get_recent_archives(int $limit): array {
        $submissions = $this->submissions_history->get_recent_successful_submissions($limit);
        
        $archives = [];
        foreach ($submissions as $submission) {
            if (empty($submission->archive_url)) {
                continue;
            }
            
            $post = get_post($submission->post_id);
            if (!$post || $post->post_status !== 'publish') {
                continue;
            }
            
            $archives[] = [
                'post_id' => $submission->post_id,
                'post_title' => $post->post_title,
                'post_url' => get_permalink($submission->post_id),
                'archive_url' => $submission->archive_url,
                'submission_date' => $submission->submission_date,
                'status' => $submission->status
            ];
        }
        
        return $archives;
    }
    
    /**
     * Get popular archives (most viewed)
     * 
     * @param int $limit Maximum number of items
     * @return array Archive data
     */
    private function get_popular_archives(int $limit): array {
        // For now, return recent archives as we don't track view counts
        // This could be enhanced with analytics integration
        return $this->get_recent_archives($limit);
    }
    
    /**
     * Render archive links
     * 
     * @param array $archive_data Archive data
     * @param array $options Display options
     * @return void
     */
    private function render_archive_links(array $archive_data, array $options): void {
        $show_date = $options['show_date'] ?? true;
        $show_status = $options['show_status'] ?? false;
        $link_text = $options['link_text'] ?? __('View Archived Version', 'spun-web-archive-forge');
        $display_mode = $options['display_mode'] ?? 'current_post';
        
        echo '<ul class="swap-archive-links">';
        
        foreach ($archive_data as $archive) {
            echo '<li class="swap-archive-item">';
            
            // Post title (for multi-post modes)
            if ($display_mode !== 'current_post') {
                echo '<h4 class="swap-archive-post-title">';
                echo '<a href="' . esc_url($archive['post_url']) . '">';
                echo esc_html($archive['post_title']);
                echo '</a>';
                echo '</h4>';
            }
            
            // Archive link
            echo '<div class="swap-archive-link-container">';
            echo '<a href="' . esc_url($archive['archive_url']) . '" ';
            echo 'class="swap-archive-link" ';
            echo 'target="_blank" ';
            echo 'rel="noopener noreferrer" ';
            echo 'title="' . esc_attr(__('View archived version on Internet Archive', 'spun-web-archive-forge')) . '">';
            echo '<span class="swap-archive-icon">📚</span> ';
            echo esc_html($link_text);
            echo '</a>';
            echo '</div>';
            
            // Archive metadata
            if ($show_date || $show_status) {
                echo '<div class="swap-archive-meta">';
                
                if ($show_date && !empty($archive['submission_date'])) {
                    $date = date_i18n(get_option('date_format'), strtotime($archive['submission_date']));
                    echo '<span class="swap-archive-date">';
                    echo '<small>' . sprintf(__('Archived: %s', 'spun-web-archive-forge'), $date) . '</small>';
                    echo '</span>';
                }
                
                if ($show_status) {
                    $status_class = 'swap-status-' . esc_attr($archive['status']);
                    echo '<span class="swap-archive-status ' . $status_class . '">';
                    echo '<small>' . esc_html(ucfirst($archive['status'])) . '</small>';
                    echo '</span>';
                }
                
                echo '</div>';
            }
            
            echo '</li>';
        }
        
        echo '</ul>';
    }
    
    /**
     * Enqueue widget styles
     * 
     * @return void
     */
    public function enqueue_widget_styles(): void {
        if (is_active_widget(false, false, $this->id_base)) {
            wp_add_inline_style('wp-block-library', $this->get_widget_css());
        }
    }
    
    /**
     * Get widget CSS
     * 
     * @return string CSS styles
     */
    private function get_widget_css(): string {
        return '
        .swap-archive-links-widget-content {
            font-size: 14px;
        }
        
        .swap-archive-links {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        
        .swap-archive-item {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .swap-archive-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .swap-archive-post-title {
            margin: 0 0 5px 0;
            font-size: 14px;
            font-weight: bold;
        }
        
        .swap-archive-post-title a {
            text-decoration: none;
            color: inherit;
        }
        
        .swap-archive-post-title a:hover {
            text-decoration: underline;
        }
        
        .swap-archive-link {
            display: inline-block;
            padding: 5px 10px;
            background: #f0f0f0;
            border: 1px solid #ddd;
            border-radius: 3px;
            text-decoration: none;
            color: #333;
            font-size: 12px;
            transition: all 0.2s ease;
        }
        
        .swap-archive-link:hover {
            background: #e0e0e0;
            border-color: #ccc;
            text-decoration: none;
        }
        
        .swap-archive-icon {
            margin-right: 3px;
        }
        
        .swap-archive-meta {
            margin-top: 5px;
            font-size: 11px;
            color: #666;
        }
        
        .swap-archive-date {
            margin-right: 10px;
        }
        
        .swap-status-success {
            color: #46b450;
        }
        
        .swap-status-failed {
            color: #dc3232;
        }
        
        .swap-status-pending {
            color: #ffb900;
        }
        
        .swap-no-archive {
            font-style: italic;
            color: #666;
            margin: 0;
        }
        ';
    }
    
    /**
     * Register the widget
     * 
     * @return void
     */
    public static function register(): void {
        register_widget('SWAP_Archive_Links_Widget');
    }
}

/**
 * Register the archive links widget
 * Note: Widget registration is handled by the main plugin file
 */
function swap_register_archive_links_widget(): void {
    register_widget('SWAP_Archive_Links_Widget');
}
// Widget registration is handled by the main plugin file to avoid duplicates
// add_action('widgets_init', 'swap_register_archive_links_widget');