<?php
/**
 * Archive Page Widget
 * 
 * Widget to display user's Internet Archive page (@username) in website sidebars.
 * 
 * @package Spun_Web_Archive_Forge
 * @subpackage Includes
 * @author Ryan Dickie Thompson
 * @copyright 2024 Spun Web Technology
 * @license GPL-2.0-or-later
 * @since 0.3.6
 * @version 0.6.1
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SWAP_Archive_Widget extends WP_Widget {
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct(
            'swap_archive_widget',
            __('Internet Archive Page', 'spun-web-archive-forge'),
            array(
                'description' => __('Display a link to your Internet Archive page.', 'spun-web-archive-forge'),
                'classname' => 'swap-archive-widget'
            )
        );
    }
    
    /**
     * Widget output
     */
    public function widget($args, $instance) {
        // Get plugin settings
        $display_settings = get_option('swap_display_settings', array());
        $queue_settings = get_option('swap_queue_settings', array());
        $api_settings = get_option('swap_api_settings', array());
        
        // Check if archive link visibility is hidden
        if (isset($queue_settings['archive_link_visibility']) && $queue_settings['archive_link_visibility'] === 'hidden') {
            return;
        }
        
        // Get username from API settings (preferred), queue settings, or fallback to display settings
        $username = $api_settings['archive_username'] ?? $queue_settings['archive_username'] ?? $display_settings['archive_username'] ?? '';
        
        // Debug logging
        error_log('SWAP Widget Debug - API username: ' . ($api_settings['archive_username'] ?? 'not set'));
        error_log('SWAP Widget Debug - Queue username: ' . ($queue_settings['archive_username'] ?? 'not set'));
        error_log('SWAP Widget Debug - Display username: ' . ($display_settings['archive_username'] ?? 'not set'));
        error_log('SWAP Widget Debug - Final username: ' . $username);
        error_log('SWAP Widget Debug - Archive link visibility: ' . ($queue_settings['archive_link_visibility'] ?? 'not set'));
        
        if (empty($username)) {
            error_log('SWAP Widget Debug - No username found, widget not displaying');
            return; // Don't display if no username is set
        }
        
        // Clean username (remove @ if present)
        $username = ltrim($username, '@');
        
        if (empty($username)) {
            return;
        }
        
        // Get widget settings
        $title = !empty($instance['title']) ? $instance['title'] : __('My Internet Archive', 'spun-web-archive-forge');
		$show_title = isset($instance['show_title']) ? $instance['show_title'] : true;
		$link_text = !empty($instance['link_text']) ? $instance['link_text'] : __('View My Archive', 'spun-web-archive-forge');
        $show_description = isset($instance['show_description']) ? $instance['show_description'] : true;
        $custom_description = !empty($instance['custom_description']) ? $instance['custom_description'] : '';
        
        // Build archive URL - ensure proper formatting
        $archive_url = 'https://archive.org/details/@' . urlencode(trim($username));
        
        // Debug logging for URL construction
        error_log('SWAP Widget Debug - Final archive URL: ' . $archive_url);
        error_log('SWAP Widget Debug - Username after processing: ' . $username);
        
        echo $args['before_widget'];
        
        if ($show_title && $title) {
            echo $args['before_title'] . apply_filters('widget_title', $title) . $args['after_title'];
        }
        
        echo '<div class="swap-archive-widget-content">';
        
        if ($show_description) {
            if (!empty($custom_description)) {
                echo '<p class="swap-archive-description">' . esc_html($custom_description) . '</p>';
            } else {
                echo '<p class="swap-archive-description">' . 
                     sprintf(
                         __('Visit my collection of archived web pages on the Internet Archive.', 'spun-web-archive-forge')
                     ) . 
                     '</p>';
            }
        }
        
        echo '<p class="swap-archive-link">';
        echo '<a href="' . esc_url($archive_url) . '" target="_blank" rel="noopener noreferrer" class="swap-archive-link-button">';
        echo esc_html($link_text);
        echo ' <span class="swap-external-icon">↗</span>';
        echo '</a>';
        echo '</p>';
        
        // Check if plugin author link should be shown
        if (!empty($display_settings['show_plugin_author_link'])) {
            $author_url = $display_settings['author_url'] ?? 'https://spunwebtechnology.com/spun-web-archive-pro-wordpress-wayback-archive/';
            echo '<p class="swap-plugin-author-link">';
            echo '<a href="' . esc_url($author_url) . '" target="_blank" rel="noopener noreferrer" class="swap-plugin-author-link-button">';
            echo __('Powered by Spun Web Archive Forge', 'spun-web-archive-forge');
            echo '</a>';
            echo '</p>';
        }
        
        echo '</div>';
        
        echo $args['after_widget'];
        
        // Add some basic styling
        if (!wp_style_is('swap-widget-style', 'enqueued')) {
            echo '<style>
                .swap-archive-widget-content {
                    text-align: center;
                }
                .swap-archive-description {
                    margin-bottom: 15px;
                    font-size: 14px;
                    line-height: 1.5;
                }
                .swap-archive-link-button {
                    display: inline-block;
                    padding: 8px 16px;
                    background-color: #0073aa;
                    color: white !important;
                    text-decoration: none;
                    border-radius: 4px;
                    font-weight: 500;
                    transition: background-color 0.3s ease;
                }
                .swap-archive-link-button:hover {
                    background-color: #005a87;
                    color: white !important;
                }
                .swap-external-icon {
                    font-size: 12px;
                    margin-left: 4px;
                }
                .swap-plugin-author-link {
                    margin-top: 10px;
                }
                .swap-plugin-author-link-button {
                    display: inline-block;
                    padding: 4px 8px;
                    background-color: #f0f0f0;
                    color: #666 !important;
                    text-decoration: none;
                    border-radius: 3px;
                    font-size: 11px;
                    font-weight: normal;
                    transition: all 0.3s ease;
                }
                .swap-plugin-author-link-button:hover {
                    background-color: #e0e0e0;
                    color: #333 !important;
                }
            </style>';
        }
    }
    
    /**
     * Widget form in admin
     */
    public function form($instance) {
        // Get plugin settings
        $display_settings = get_option('swap_display_settings', array());
        $queue_settings = get_option('swap_queue_settings', array());
        $api_settings = get_option('swap_api_settings', array());
        
        // Get username from API settings (preferred), queue settings, or fallback to display settings
        $username = $api_settings['archive_username'] ?? $queue_settings['archive_username'] ?? $display_settings['archive_username'] ?? '';
        
        // Widget instance settings
        $title = !empty($instance['title']) ? $instance['title'] : __('My Internet Archive', 'spun-web-archive-forge');
        $show_title = isset($instance['show_title']) ? $instance['show_title'] : true;
        $link_text = !empty($instance['link_text']) ? $instance['link_text'] : __('View My Archive', 'spun-web-archive-forge');
        $show_description = isset($instance['show_description']) ? $instance['show_description'] : true;
        $custom_description = !empty($instance['custom_description']) ? $instance['custom_description'] : '';
        
        ?>
        <?php if (empty($username)): ?>
            <div style="padding: 10px; background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; margin-bottom: 15px;">
                <p style="margin: 0; color: #856404;">
                    <strong><?php _e('Configuration Required:', 'spun-web-archive-forge'); ?></strong><br>
                    <?php printf(
                        __('Please set your Archive.org username in the <a href="%s">plugin settings</a> first.', 'spun-web-archive-forge'),
                        admin_url('admin.php?page=spun-web-archive-forge')
                    ); ?>
                </p>
            </div>
        <?php endif; ?>
        
        <p>
            <input class="checkbox" type="checkbox" <?php checked($show_title); ?> id="<?php echo $this->get_field_id('show_title'); ?>" name="<?php echo $this->get_field_name('show_title'); ?>" />
            <label for="<?php echo $this->get_field_id('show_title'); ?>"><?php _e('Show widget title', 'spun-web-archive-forge'); ?></label>
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'spun-web-archive-forge'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('link_text'); ?>"><?php _e('Link Text:', 'spun-web-archive-forge'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('link_text'); ?>" name="<?php echo $this->get_field_name('link_text'); ?>" type="text" value="<?php echo esc_attr($link_text); ?>" />
        </p>
        
        <p>
            <input class="checkbox" type="checkbox" <?php checked($show_description); ?> id="<?php echo $this->get_field_id('show_description'); ?>" name="<?php echo $this->get_field_name('show_description'); ?>" />
            <label for="<?php echo $this->get_field_id('show_description'); ?>"><?php _e('Show description', 'spun-web-archive-forge'); ?></label>
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('custom_description'); ?>"><?php _e('Custom Description (optional):', 'spun-web-archive-forge'); ?></label>
            <textarea class="widefat" rows="3" id="<?php echo $this->get_field_id('custom_description'); ?>" name="<?php echo $this->get_field_name('custom_description'); ?>"><?php echo esc_textarea($custom_description); ?></textarea>
            <small><?php _e('Leave empty to use default description.', 'spun-web-archive-forge'); ?></small>
        </p>
        
        <?php if (!empty($username)): ?>
            <div style="padding: 10px; background-color: #d1ecf1; border: 1px solid #bee5eb; border-radius: 4px; margin-top: 15px;">
                <p style="margin: 0; color: #0c5460;">
                    <strong><?php _e('Preview URL:', 'spun-web-archive-forge'); ?></strong><br>
                    <a href="https://archive.org/details/@<?php echo urlencode($username); ?>" target="_blank" rel="noopener noreferrer">
                        https://archive.org/details/@<?php echo esc_html($username); ?>
                    </a>
                </p>
            </div>
        <?php endif; ?>
        <?php
    }
    
    /**
     * Update widget settings
     */
    public function update($new_instance, $old_instance) {
        $instance = array();
        
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['show_title'] = isset($new_instance['show_title']) ? (bool) $new_instance['show_title'] : false;
        $instance['link_text'] = (!empty($new_instance['link_text'])) ? sanitize_text_field($new_instance['link_text']) : __('View My Archive', 'spun-web-archive-forge');
        $instance['show_description'] = isset($new_instance['show_description']) ? (bool) $new_instance['show_description'] : false;
        $instance['custom_description'] = (!empty($new_instance['custom_description'])) ? sanitize_textarea_field($new_instance['custom_description']) : '';
        
        return $instance;
    }
}

/**
 * Register the widget
 */
function swap_register_archive_widget() {
    register_widget('SWAP_Archive_Widget');
}
add_action('widgets_init', 'swap_register_archive_widget');
