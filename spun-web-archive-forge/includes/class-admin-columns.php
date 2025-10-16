<?php
/**
 * Admin Columns Handler
 *
 * Adds archive status indicators to the admin posts list
 *
 * @package SpunWebArchiveElite
 * @version 0.6.1
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SWAP_Admin_Columns {
    
    private $archive_queue;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->archive_queue = new SWAP_Archive_Queue();
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Add columns to post types
        $post_types = get_post_types(array('public' => true), 'names');
        
        foreach ($post_types as $post_type) {
            add_filter("manage_{$post_type}_posts_columns", array($this, 'add_archive_column'));
            add_action("manage_{$post_type}_posts_custom_column", array($this, 'display_archive_column'), 10, 2);
        }
        
        // Add CSS for styling
        add_action('admin_head', array($this, 'add_column_styles'));
    }
    
    /**
     * Add archive status column
     */
    public function add_archive_column($columns) {
        // Insert the archive column before the date column
        $new_columns = array();
        
        foreach ($columns as $key => $value) {
            if ($key === 'date') {
                $new_columns['archive_status'] = __('Archive Status', 'spun-web-archive-forge');
            }
            $new_columns[$key] = $value;
        }
        
        return $new_columns;
    }
    
    /**
     * Display archive status column content
     */
    public function display_archive_column($column, $post_id) {
        if ($column !== 'archive_status') {
            return;
        }
        
        $post_url = get_permalink($post_id);
        if (!$post_url) {
            echo '<span class="swap-status-none">—</span>';
            return;
        }
        
        // Check if post is archived
        if ($this->archive_queue->is_archived($post_id)) {
            echo '<span class="swap-status-archived" title="' . esc_attr__('Successfully archived', 'spun-web-archive-forge') . '">✅ ' . __('Archived', 'spun-web-archive-forge') . '</span>';
            return;
        }
        
        // Check if post is in queue
        if ($this->archive_queue->is_in_queue($post_id)) {
            echo '<span class="swap-status-queued" title="' . esc_attr__('Waiting to be processed', 'spun-web-archive-forge') . '">🟡 ' . __('In Queue', 'spun-web-archive-forge') . '</span>';
            return;
        }
        
        // Not archived or queued
        echo '<span class="swap-status-none" title="' . esc_attr__('Not submitted to archive', 'spun-web-archive-forge') . '">—</span>';
    }
    
    /**
     * Add CSS styles for the column
     */
    public function add_column_styles() {
        $screen = get_current_screen();
        
        // Only add styles on post list pages
        if (!$screen || $screen->base !== 'edit') {
            return;
        }
        
        ?>
        <style>
        .column-archive_status {
            width: 120px;
        }
        
        .swap-status-archived {
            color: #46b450;
            font-weight: 600;
        }
        
        .swap-status-queued {
            color: #ffb900;
            font-weight: 600;
        }
        
        .swap-status-none {
            color: #8c8f94;
        }
        
        .swap-status-archived:before,
        .swap-status-queued:before,
        .swap-status-none:before {
            display: inline-block;
            margin-right: 4px;
        }
        </style>
        <?php
    }
}
