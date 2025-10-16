<?php
/**
 * Debug script to check database tables and their contents
 */

// Mock WordPress environment for testing
class MockWPDB {
    public $prefix = 'wp_';
    
    public function __construct() {
        // This is just for testing table names
    }
    
    public function get_charset_collate() {
        return 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
    }
}

// Mock global $wpdb
$wpdb = new MockWPDB();

echo "=== Database Table Analysis ===\n\n";

// Check submissions history table structure
echo "1. Submissions History Table Structure:\n";
echo "Table name: " . $wpdb->prefix . "swap_submissions_history\n";
echo "Expected columns:\n";
echo "- id (mediumint AUTO_INCREMENT)\n";
echo "- post_id (bigint)\n";
echo "- post_title (text)\n";
echo "- post_url (text)\n";
echo "- submission_url (text)\n";
echo "- archive_url (text)\n";
echo "- status (varchar(20) DEFAULT 'pending')\n";
echo "- submission_date (datetime DEFAULT CURRENT_TIMESTAMP)\n";
echo "- last_checked (datetime)\n";
echo "- error_message (text)\n";
echo "- response_data (longtext)\n\n";

// Check archive queue table structure
echo "2. Archive Queue Table Structure:\n";
echo "Table name: " . $wpdb->prefix . "swap_archive_queue\n";
echo "Expected columns:\n";
echo "- id (mediumint AUTO_INCREMENT)\n";
echo "- post_id (bigint)\n";
echo "- post_url (text)\n";
echo "- post_title (text)\n";
echo "- post_type (varchar(20) DEFAULT 'post')\n";
echo "- status (varchar(20) DEFAULT 'pending')\n";
echo "- attempts (int DEFAULT 0)\n";
echo "- last_attempt (datetime)\n";
echo "- created_at (datetime DEFAULT CURRENT_TIMESTAMP)\n";
echo "- archived_at (datetime)\n";
echo "- error_message (text)\n\n";

echo "3. Expected Flow:\n";
echo "- User submits post to queue\n";
echo "- Entry added to swap_archive_queue table\n";
echo "- Entry also added to swap_submissions_history table with 'pending' status\n";
echo "- Cron job processes queue item\n";
echo "- History table updated with final status (success/failed)\n\n";

echo "4. Potential Issues:\n";
echo "- Tables might not exist (migration issue)\n";
echo "- Submissions history table might be empty\n";
echo "- Database permissions issue\n";
echo "- WordPress environment not properly initialized\n\n";

echo "=== End Analysis ===\n";
?>