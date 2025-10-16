<?php
/**
 * WordPress Compatibility Helper
 * 
 * Provides compatibility checks and fallbacks for WordPress functions
 * to help with static analysis and ensure robust operation.
 * 
 * @package SpunWebArchiveElite
 * @subpackage Includes
 * @author Ryan Dickie Thompson
 * @copyright 2024 Spun Web Technology
 * @license GPL-2.0-or-later
 * @since 0.3.5
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Provide fallback for wp_trim_words if not available
 */
if (!function_exists('wp_trim_words')) {
    function wp_trim_words($text, $num_words = 55, $more = null) {
        if (null === $more) {
            $more = '&hellip;';
        }
        
        $original_text = $text;
        $text = wp_strip_all_tags($text);
        $words_array = preg_split('/[\n\r\t ]+/', $text, $num_words + 1, PREG_SPLIT_NO_EMPTY);
        
        if (count($words_array) > $num_words) {
            array_pop($words_array);
            $text = implode(' ', $words_array);
            $text = $text . $more;
        } else {
            $text = implode(' ', $words_array);
        }
        
        return $text;
    }
}

/**
 * Provide fallback for wp_strip_all_tags if not available
 */
if (!function_exists('wp_strip_all_tags')) {
    function wp_strip_all_tags($string, $remove_breaks = false) {
        $string = preg_replace('@<(script|style)[^>]*?>.*?</\\1>@si', '', $string);
        $string = strip_tags($string);
        
        if ($remove_breaks) {
            $string = preg_replace('/[\r\n\t ]+/', ' ', $string);
        }
        
        return trim($string);
    }
}

/**
 * Verify WordPress environment is properly loaded
 * 
 * @return bool True if WordPress is properly loaded
 */
function swap_verify_wordpress_environment() {
    // Check for essential WordPress functions
    $required_functions = [
        'wp_verify_nonce',
        'get_option',
        'update_option',
        'esc_html',
        'esc_attr',
        'esc_url',
        'sanitize_text_field',
        'current_user_can',
        'wp_die',
        'add_action',
        'add_filter',
        'wp_enqueue_script',
        'wp_enqueue_style',
        'wp_trim_words'
    ];
    
    foreach ($required_functions as $function) {
        if (!function_exists($function)) {
            return false;
        }
    }
    
    // Check for essential WordPress constants
    $required_constants = [
        'ABSPATH',
        'WP_CONTENT_DIR',
        'WP_PLUGIN_DIR'
    ];
    
    foreach ($required_constants as $constant) {
        if (!defined($constant)) {
            return false;
        }
    }
    
    return true;
}

/**
 * Safe wrapper for WordPress functions with fallbacks
 * 
 * @param string $function Function name to call
 * @param array $args Arguments to pass to function
 * @param mixed $fallback Fallback value if function doesn't exist
 * @return mixed Function result or fallback
 */
function swap_safe_wp_function($function, $args = [], $fallback = null) {
    if (function_exists($function)) {
        return call_user_func_array($function, $args);
    }
    
    return $fallback;
}

/**
 * Initialize WordPress compatibility checks
 */
function swap_init_wordpress_compat() {
    if (!swap_verify_wordpress_environment()) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Spun Web Archive Forge: WordPress environment not properly loaded');
        }
        return false;
    }
    
    return true;
}

// Initialize compatibility checks when this file is loaded
add_action('init', 'swap_init_wordpress_compat', 1);
