<?php
/**
 * Footer Display Handler
 * 
 * Handles displaying user's Internet Archive page link in the website footer.
 * 
 * @package SpunWebArchiveElite
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

class SWAP_Footer_Display {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Add footer display
        add_action('wp_footer', array($this, 'display_footer_link'));
        
        // Add admin footer display option
        add_action('admin_footer', array($this, 'display_admin_footer_link'));
    }
    
    /**
     * Display archive link in footer
     */
    public function display_footer_link() {
        // Get plugin settings
        $display_settings = get_option('swap_display_settings', array());
        $queue_settings = get_option('swap_queue_settings', array());
        $api_settings = get_option('swap_api_settings', array());
        
        // Check if any footer links should be displayed
        $show_user_link = !empty($display_settings['show_user_archive_link']);
        $show_author_link = !empty($display_settings['show_plugin_author_link']);
        
        if (!$show_user_link && !$show_author_link) {
            return;
        }
        
        // Get footer settings
        $footer_text = $display_settings['footer_text'] ?? __('Archived with Internet Archive', 'spun-web-archive-forge');
        $footer_position = $display_settings['footer_position'] ?? 'center';
        $author_url = $display_settings['author_url'] ?? 'https://spunwebtechnology.com/spun-web-archive-pro-wordpress-wayback-archive/';
        
        // Determine CSS classes
        $css_classes = array('swap-footer-archive');
        $css_classes[] = 'swap-footer-' . $footer_position;
        $css_classes[] = 'swap-footer-simple';
        
        ?>
        <div class="<?php echo esc_attr(implode(' ', $css_classes)); ?>">
            <p class="swap-footer-simple">
                <?php if ($show_user_link): ?>
                    <?php
                    // Get username from API settings (preferred), queue settings, or fallback to display settings
                    $username = $api_settings['archive_username'] ?? $queue_settings['archive_username'] ?? $display_settings['archive_username'] ?? '';
                    
                    if (!empty($username)) {
                        // Clean username (remove @ if present)
                        $username = ltrim($username, '@');
                        
                        if (!empty($username)) {
                            // Build archive URL - ensure proper formatting
                            $archive_url = 'https://archive.org/details/@' . urlencode(trim($username));
                            ?>
                            <a href="<?php echo esc_url($archive_url); ?>" target="_blank" rel="noopener noreferrer" class="swap-footer-link">
                                <?php echo esc_html($footer_text); ?>
                            </a>
                            <?php if ($show_author_link): ?>
                                <span class="swap-footer-separator"> | </span>
                            <?php endif; ?>
                            <?php
                        }
                    }
                    ?>
                <?php endif; ?>
                
                <?php if ($show_author_link): ?>
                    <a href="<?php echo esc_url($author_url); ?>" target="_blank" rel="noopener noreferrer" class="swap-footer-link">
                        <?php _e('Powered by Spun Web Archive Forge', 'spun-web-archive-forge'); ?>
                    </a>
                <?php endif; ?>
            </p>
        </div>
        
        <style>
            .swap-footer-archive {
                margin: 20px 0 10px 0;
                font-size: 13px;
                line-height: 1.4;
            }
            
            .swap-footer-center {
                text-align: center;
            }
            
            .swap-footer-left {
                text-align: left;
            }
            
            .swap-footer-right {
                text-align: right;
            }
            
            .swap-footer-simple p {
                margin: 0;
                color: #666;
            }
            
            .swap-footer-simple .swap-footer-link {
                color: #0073aa;
                text-decoration: none;
                transition: color 0.3s ease;
            }
            
            .swap-footer-simple .swap-footer-link:hover {
                color: #005a87;
                text-decoration: underline;
            }
            
            .swap-footer-separator {
                color: #999;
                margin: 0 5px;
            }
            
            @media (max-width: 768px) {
                .swap-footer-archive {
                    font-size: 12px;
                }
                
                .swap-footer-separator {
                    margin: 0 3px;
                }
            }
        </style>
        <?php
    }
    
    /**
     * Display archive link in admin footer (optional)
     */
    public function display_admin_footer_link() {
        // Only show on plugin pages
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'spun-web-archive') === false) {
            return;
        }
        
        // Get plugin settings
        $display_settings = get_option('swap_display_settings', array());
        $api_settings = get_option('swap_api_settings', array());
        $queue_settings = get_option('swap_queue_settings', array());
        
        // Check if plugin page link should be shown
        if (!($queue_settings['show_plugin_link'] ?? true)) {
            return;
        }
        
        // Get username from API settings (preferred), queue settings, or fallback to display settings
        $username = $api_settings['archive_username'] ?? $queue_settings['archive_username'] ?? $display_settings['archive_username'] ?? '';
        
        if (empty($username)) {
            return;
        }
        
        // Clean username (remove @ if present)
        $username = ltrim($username, '@');
        
        if (empty($username)) {
            return;
        }
        
        // Build archive URL - ensure proper formatting
        $archive_url = 'https://archive.org/details/@' . urlencode(trim($username));
        
        ?>
        <div style="text-align: center; margin-top: 20px; padding-top: 15px; border-top: 1px solid #ddd;">
            <p style="margin: 0; font-size: 12px; color: #666;">
                <?php printf(
                    __('View my archived content: <a href="%s" target="_blank" rel="noopener noreferrer">@%s on Internet Archive</a>', 'spun-web-archive-forge'),
                    esc_url($archive_url),
                    esc_html($username)
                ); ?>
            </p>
        </div>
        <?php
    }
    
    /**
     * Get available footer positions
     */
    public static function get_footer_positions() {
        return array(
            'left' => __('Left', 'spun-web-archive-forge'),
		'center' => __('Center', 'spun-web-archive-forge'),
		'right' => __('Right', 'spun-web-archive-forge')
        );
    }
    
    /**
     * Get available footer styles
     */
    public static function get_footer_styles() {
        return array(
            'simple' => __('Simple Link', 'spun-web-archive-forge')
        );
    }
}
