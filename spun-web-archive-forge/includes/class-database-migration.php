<?php
/**
 * Database Migration Class
 * 
 * Handles database schema updates and migrations for the plugin
 * 
 * @package SpunWebArchiveElite
 * @subpackage Includes
 * @author Ryan Dickie Thompson
 * @copyright 2024 Spun Web Technology
 * @license GPL-2.0-or-later
 * @since 0.0.1
 * @version 0.6.1
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// WordPress compatibility checks
if (!defined('DAY_IN_SECONDS')) {
    define('DAY_IN_SECONDS', 24 * 60 * 60);
}

if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        return $default;
    }
}

if (!function_exists('update_option')) {
    function update_option($option, $value) {
        return true;
    }
}

if (!function_exists('delete_option')) {
    function delete_option($option) {
        return true;
    }
}

if (!function_exists('current_time')) {
    function current_time($type, $gmt = 0) {
        switch ($type) {
            case 'mysql':
                return $gmt ? gmdate('Y-m-d H:i:s') : date('Y-m-d H:i:s');
            case 'timestamp':
                return $gmt ? time() : (time() + (get_option('gmt_offset') * HOUR_IN_SECONDS));
            default:
                return $gmt ? time() : (time() + (get_option('gmt_offset') * HOUR_IN_SECONDS));
        }
    }
}

if (!function_exists('get_post')) {
    function get_post($post = null, $output = OBJECT, $filter = 'raw') {
        if (is_numeric($post)) {
            // Return a mock post object for compatibility
            return (object) [
                'ID' => $post,
                'post_title' => 'Mock Post',
                'post_type' => 'post',
                'post_status' => 'publish'
            ];
        }
        return null;
    }
}

if (!function_exists('get_permalink')) {
    function get_permalink($post = 0, $leavename = false) {
        if (is_numeric($post)) {
            return home_url("/?p={$post}");
        }
        return home_url('/');
    }
}

if (!function_exists('home_url')) {
    function home_url($path = '', $scheme = null) {
        $url = 'http://localhost';
        if (!empty($path) && is_string($path)) {
            $url .= '/' . ltrim($path, '/');
        }
        return $url;
    }
}

if (!defined('OBJECT')) {
    define('OBJECT', 'OBJECT');
}

if (!defined('HOUR_IN_SECONDS')) {
    define('HOUR_IN_SECONDS', 60 * 60);
}

/**
 * Database Migration class for handling schema updates and data migrations
 */
class SWAP_Database_Migration {
    
    /**
     * Current database version
     */
    public const DB_VERSION = '1.4';
    
    /**
     * Database version option key
     */
    private const DB_VERSION_OPTION = 'swap_db_version';
    
    /**
     * Migration log option key
     */
    private const MIGRATION_LOG_OPTION = 'swap_migration_log';
    
    /**
     * Maximum backup retention days
     */
    private const BACKUP_RETENTION_DAYS = 30;
    
    /**
     * WordPress database instance
     * 
     * @var wpdb
     */
    private wpdb $wpdb;
    
    /**
     * Migration results
     * 
     * @var array
     */
    private array $migration_results = [];
    
    /**
     * Constructor
     * 
     * @param wpdb|null $wpdb WordPress database instance
     */
    public function __construct(?wpdb $wpdb = null) {
        if ($wpdb === null) {
            global $wpdb;
        }
        $this->wpdb = $wpdb;
    }
    
    /**
     * Run database migrations if needed
     * 
     * @return array Migration results
     */
    public function maybe_migrate(): array {
        $current_version = $this->get_current_version();
        $target_version = self::DB_VERSION;
        
        $this->log_migration_start($current_version, $target_version);
        
        try {
            // Run migrations in order
            $migrations = $this->get_migration_list();
            
            foreach ($migrations as $version => $migration_method) {
                if (version_compare($current_version, $version, '<')) {
                    $this->run_migration($version, $migration_method);
                }
            }
            
            // Update version if all migrations successful
            if (version_compare($current_version, $target_version, '<')) {
                $this->update_version($target_version);
            }
            
            // Clean up old backups
            $this->cleanup_old_backups();
            
            $this->log_migration_complete();
            
        } catch (Exception $e) {
            $this->log_migration_error($e);
            throw $e;
        }
        
        return $this->migration_results;
    }
    
    /**
     * Get list of available migrations
     * 
     * @return array Migration methods indexed by version
     */
    private function get_migration_list(): array {
        return [
            '1.1' => 'migrate_to_1_1',
            '1.2' => 'migrate_to_1_2',
            '1.3' => 'migrate_to_1_3',
            '1.4' => 'migrate_to_1_4'
        ];
    }
    
    /**
     * Run a specific migration
     * 
     * @param string $version Target version
     * @param string $method Migration method name
     * @return void
     * @throws Exception If migration fails
     */
    private function run_migration(string $version, string $method): void {
        if (!method_exists($this, $method)) {
            throw new Exception("Migration method {$method} not found");
        }
        
        $start_time = microtime(true);
        
        try {
            $this->$method();
            
            $execution_time = microtime(true) - $start_time;
            $this->migration_results[$version] = [
                'success' => true,
                'execution_time' => $execution_time,
                'message' => "Migration to {$version} completed successfully"
            ];
            
            $this->log_migration_step($version, true, $execution_time);
            
        } catch (Exception $e) {
            $execution_time = microtime(true) - $start_time;
            $this->migration_results[$version] = [
                'success' => false,
                'execution_time' => $execution_time,
                'error' => $e->getMessage()
            ];
            
            $this->log_migration_step($version, false, $execution_time, $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Migrate to version 1.1 - Fix queue table schema and ensure history table exists
     * 
     * @return void
     * @throws Exception If migration fails
     */
    private function migrate_to_1_1(): void {
        $queue_table = $this->wpdb->prefix . 'swap_archive_queue';
        $history_table = $this->wpdb->prefix . 'swap_submissions_history';
        
        // Handle queue table migration
        if ($this->table_exists($queue_table)) {
            $columns = $this->get_table_columns($queue_table);
            
            // Check if old schema detected
            if (in_array('url', $columns) && !in_array('post_url', $columns)) {
                $this->recreate_queue_table();
            }
        } else {
            $this->create_queue_table();
        }
        
        // Ensure submissions history table exists
        if (!$this->table_exists($history_table)) {
            $this->create_history_table();
        }
    }
    
    /**
     * Migrate to version 1.2 - Ensure submissions history table exists with proper indexes
     * 
     * @return void
     * @throws Exception If migration fails
     */
    private function migrate_to_1_2(): void {
        $history_table = $this->wpdb->prefix . 'swap_submissions_history';
        
        if (!$this->table_exists($history_table)) {
            $this->create_history_table();
        } else {
            // Add missing indexes if they don't exist
            $this->add_missing_indexes($history_table);
        }
    }
    
    /**
     * Migrate to version 1.3 - Add performance indexes and optimize tables
     * 
     * @return void
     * @throws Exception If migration fails
     */
    private function migrate_to_1_3(): void {
        $queue_table = $this->wpdb->prefix . 'swap_archive_queue';
        $history_table = $this->wpdb->prefix . 'swap_submissions_history';
        
        // Add performance indexes
        $this->add_performance_indexes($queue_table);
        $this->add_performance_indexes($history_table);
        
        // Optimize tables
        $this->optimize_table($queue_table);
        $this->optimize_table($history_table);
    }
    
    /**
     * Recreate the queue table with correct schema
     * 
     * @return void
     * @throws Exception If recreation fails
     */
    private function recreate_queue_table(): void {
        $table_name = $this->wpdb->prefix . 'swap_archive_queue';
        $backup_table = $this->wpdb->prefix . 'swap_archive_queue_backup_' . time();
        
        try {
            // Create backup of existing data
            $backup_result = $this->wpdb->query(
                "CREATE TABLE {$backup_table} AS SELECT * FROM {$table_name}"
            );
            
            if ($backup_result === false) {
                throw new Exception("Failed to create backup table: " . $this->wpdb->last_error);
            }
            
            // Drop old table
            $drop_result = $this->wpdb->query("DROP TABLE {$table_name}");
            if ($drop_result === false) {
                throw new Exception("Failed to drop old table: " . $this->wpdb->last_error);
            }
            
            // Create new table with correct schema
            $this->create_queue_table();
            
            // Migrate data from backup
            $this->migrate_queue_data($backup_table, $table_name);
            
            // Drop backup table after successful migration
            $this->wpdb->query("DROP TABLE {$backup_table}");
            
        } catch (Exception $e) {
            // If something went wrong, try to restore from backup
            if ($this->table_exists($backup_table) && !$this->table_exists($table_name)) {
                $this->wpdb->query("RENAME TABLE {$backup_table} TO {$table_name}");
            }
            throw $e;
        }
    }
    
    /**
     * Migrate data from backup queue table to new schema
     * 
     * @param string $backup_table Backup table name
     * @param string $target_table Target table name
     * @return void
     * @throws Exception If data migration fails
     */
    private function migrate_queue_data(string $backup_table, string $target_table): void {
        $old_data = $this->wpdb->get_results("SELECT * FROM {$backup_table}");
        
        if (empty($old_data)) {
        return; // No data to migrate
    }

    
        
        // Prepare batch insert data
        $batch_data = [];
        $batch_size = 100; // Process in batches of 100
        
        foreach ($old_data as $row) {
            $post = get_post($row->post_id);
            if (!$post) {
                continue; // Skip if post no longer exists
            }
            
            $batch_data[] = [
                'post_id' => $row->post_id,
                'post_url' => $row->url ?? get_permalink($row->post_id),
                'post_title' => $post->post_title,
                'post_type' => $post->post_type,
                'status' => $row->status,
                'attempts' => $row->retry_count ?? 0,
                'last_attempt' => $row->processed_at ?? null,
                'created_at' => $row->added_at ?? current_time('mysql'),
                'archived_at' => ($row->status === 'completed' && isset($row->processed_at)) ? $row->processed_at : null,
                'error_message' => $row->error_message ?? null
            ];
            
            // Insert in batches to improve performance
            if (count($batch_data) >= $batch_size) {
                $this->batch_insert($target_table, $batch_data);
                $batch_data = []; // Reset batch
            }
        }
        
        // Insert remaining data
        if (!empty($batch_data)) {
            $this->batch_insert($target_table, $batch_data);
        }
    }

    /**
     * Migrate to version 1.4 - Encrypt stored API secret and remove plaintext
     *
     * Ensures any previously stored plaintext secret in 'swap_api_credentials'
     * is encrypted and written to 'swap_api_secret_encrypted'. Removes plaintext
     * secret from the credentials option once migrated.
     *
     * @return void
     */
    private function migrate_to_1_4(): void {
        $credentials_option = 'swap_api_credentials';
        $encrypted_option = 'swap_api_secret_encrypted';

        $creds = get_option($credentials_option, array());
        $encrypted = get_option($encrypted_option, array());

        // If encrypted secret already exists, nothing to do
        if (!empty($encrypted['ciphertext']) && !empty($encrypted['iv'])) {
            return;
        }

        $plaintext = $creds['secret_key'] ?? '';
        if (empty($plaintext)) {
            return; // No plaintext to migrate
        }

        // Derive key from WordPress salt and encrypt using AES-256-CBC
        $algo = 'aes-256-cbc';
        $ivlen = function_exists('openssl_cipher_iv_length') ? openssl_cipher_iv_length($algo) : 16;
        $iv = function_exists('random_bytes') ? random_bytes($ivlen) : str_repeat("\0", $ivlen);
        $salt = function_exists('wp_salt') ? wp_salt('auth') : 'swap_default_salt';
        $key = hash('sha256', $salt, true);

        $ciphertext_raw = function_exists('openssl_encrypt')
            ? openssl_encrypt($plaintext, $algo, $key, OPENSSL_RAW_DATA, $iv)
            : '';

        if (!empty($ciphertext_raw)) {
            update_option($encrypted_option, array(
                'ciphertext' => base64_encode($ciphertext_raw),
                'iv' => base64_encode($iv),
            ), false);

            // Remove plaintext secret from credentials option
            unset($creds['secret_key']);
            update_option($credentials_option, $creds);
        }
    }
    
    /**
     * Perform batch insert for better performance
     * 
     * @param string $table_name Target table name
     * @param array $data Array of data rows to insert
     * @return void
     * @throws Exception If batch insert fails
     */
    private function batch_insert(string $table_name, array $data): void {
        if (empty($data)) {
            return;
        }
        
        // Build the SQL for batch insert
        $columns = array_keys($data[0]);
        $placeholders = '(' . implode(',', array_fill(0, count($columns), '%s')) . ')';
        $values_placeholders = implode(',', array_fill(0, count($data), $placeholders));
        
        $sql = "INSERT INTO {$table_name} (`" . implode('`, `', $columns) . "`) VALUES {$values_placeholders}";
        
        // Flatten the data array for prepare()
        $values = [];
        foreach ($data as $row) {
            foreach ($row as $value) {
                $values[] = $value;
            }
        }
        
        $prepared_sql = $this->wpdb->prepare($sql, $values);
        $result = $this->wpdb->query($prepared_sql);
        
        if ($result === false) {
            throw new Exception("Failed to batch insert data: " . $this->wpdb->last_error);
        }
    }
    
    /**
     * Create queue table with correct schema
     * 
     * @return void
     * @throws Exception If table creation fails
     */
    private function create_queue_table(): void {
        if (class_exists('SWAP_Archive_Queue')) {
            $queue_manager = new SWAP_Archive_Queue();
            $queue_manager->create_table();
        } else {
            throw new Exception('SWAP_Archive_Queue class not found');
        }
    }
    
    /**
     * Create submissions history table
     * 
     * @return void
     * @throws Exception If table creation fails
     */
    private function create_history_table(): void {
        if (class_exists('SWAP_Submissions_History')) {
            SWAP_Submissions_History::create_table();
        } else {
            throw new Exception('SWAP_Submissions_History class not found');
        }
    }
    
    /**
     * Add missing indexes to a table
     * 
     * @param string $table_name Table name
     * @return void
     */
    private function add_missing_indexes(string $table_name): void {
        $indexes = [
            'idx_post_id' => 'post_id',
            'idx_status' => 'status',
            'idx_submission_date' => 'submission_date'
        ];
        
        foreach ($indexes as $index_name => $column) {
            if (!$this->index_exists($table_name, $index_name)) {
                $this->wpdb->query(
                    "ALTER TABLE {$table_name} ADD INDEX {$index_name} ({$column})"
                );
            }
        }
    }
    
    /**
     * Add performance indexes to a table
     * 
     * @param string $table_name Table name
     * @return void
     */
    private function add_performance_indexes(string $table_name): void {
        if (strpos($table_name, 'queue') !== false) {
            $indexes = [
                'idx_status_created' => 'status, created_at',
                'idx_post_status' => 'post_id, status'
            ];
        } else {
            $indexes = [
                'idx_post_status_date' => 'post_id, status, submission_date',
                'idx_status_date' => 'status, submission_date'
            ];
        }
        
        foreach ($indexes as $index_name => $columns) {
            if (!$this->index_exists($table_name, $index_name)) {
                $this->wpdb->query(
                    "ALTER TABLE {$table_name} ADD INDEX {$index_name} ({$columns})"
                );
            }
        }
    }
    
    /**
     * Optimize a database table
     * 
     * @param string $table_name Table name
     * @return void
     */
    private function optimize_table(string $table_name): void {
        if ($this->table_exists($table_name)) {
            $this->wpdb->query("OPTIMIZE TABLE {$table_name}");
        }
    }
    
    /**
     * Check if a table exists
     * 
     * @param string $table_name Table name
     * @return bool True if table exists
     */
    private function table_exists(string $table_name): bool {
        $result = $this->wpdb->get_var($this->wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table_name
        ));
        
        return !empty($result);
    }
    
    /**
     * Get table column names
     * 
     * @param string $table_name Table name
     * @return array Column names
     */
    private function get_table_columns(string $table_name): array {
        $columns = $this->wpdb->get_results("DESCRIBE {$table_name}");
        return array_column($columns, 'Field');
    }
    
    /**
     * Check if an index exists on a table
     * 
     * @param string $table_name Table name
     * @param string $index_name Index name
     * @return bool True if index exists
     */
    private function index_exists(string $table_name, string $index_name): bool {
        $result = $this->wpdb->get_var($this->wpdb->prepare(
            "SHOW INDEX FROM {$table_name} WHERE Key_name = %s",
            $index_name
        ));
        
        return !empty($result);
    }
    
    /**
     * Clean up old backup tables
     * 
     * @return void
     */
    private function cleanup_old_backups(): void {
        $cutoff_time = time() - (self::BACKUP_RETENTION_DAYS * DAY_IN_SECONDS);
        
        $tables = $this->wpdb->get_results(
            "SHOW TABLES LIKE '{$this->wpdb->prefix}swap_%_backup_%'"
        );
        
        foreach ($tables as $table) {
            $table_name = array_values((array) $table)[0];
            
            // Extract timestamp from table name
            if (preg_match('/_backup_(\d+)$/', $table_name, $matches)) {
                $backup_time = (int) $matches[1];
                
                if ($backup_time < $cutoff_time) {
                    $this->wpdb->query("DROP TABLE {$table_name}");
                }
            }
        }
    }
    
    /**
     * Get current database version
     * 
     * @return string Current version
     */
    public function get_current_version(): string {
        return get_option(self::DB_VERSION_OPTION, '1.0');
    }
    
    /**
     * Update database version
     * 
     * @param string $version New version
     * @return void
     */
    private function update_version(string $version): void {
        update_option(self::DB_VERSION_OPTION, $version);
    }
    
    /**
     * Log migration start
     * 
     * @param string $from_version Starting version
     * @param string $to_version Target version
     * @return void
     */
    private function log_migration_start(string $from_version, string $to_version): void {
        $log_entry = [
            'timestamp' => current_time('mysql'),
            'type' => 'migration_start',
            'from_version' => $from_version,
            'to_version' => $to_version
        ];
        
        $this->add_log_entry($log_entry);
    }
    
    /**
     * Log migration step
     * 
     * @param string $version Version being migrated to
     * @param bool $success Success status
     * @param float $execution_time Execution time in seconds
     * @param string|null $error_message Error message if failed
     * @return void
     */
    private function log_migration_step(
        string $version, 
        bool $success, 
        float $execution_time, 
        ?string $error_message = null
    ): void {
        $log_entry = [
            'timestamp' => current_time('mysql'),
            'type' => 'migration_step',
            'version' => $version,
            'success' => $success,
            'execution_time' => $execution_time,
            'error_message' => $error_message
        ];
        
        $this->add_log_entry($log_entry);
    }
    
    /**
     * Log migration completion
     * 
     * @return void
     */
    private function log_migration_complete(): void {
        $log_entry = [
            'timestamp' => current_time('mysql'),
            'type' => 'migration_complete',
            'final_version' => self::DB_VERSION,
            'results' => $this->migration_results
        ];
        
        $this->add_log_entry($log_entry);
    }
    
    /**
     * Log migration error
     * 
     * @param Exception $exception Exception that occurred
     * @return void
     */
    private function log_migration_error(Exception $exception): void {
        $log_entry = [
            'timestamp' => current_time('mysql'),
            'type' => 'migration_error',
            'error_message' => $exception->getMessage(),
            'error_trace' => $exception->getTraceAsString()
        ];
        
        $this->add_log_entry($log_entry);
    }
    
    /**
     * Add entry to migration log
     * 
     * @param array $log_entry Log entry data
     * @return void
     */
    private function add_log_entry(array $log_entry): void {
        $log = get_option(self::MIGRATION_LOG_OPTION, []);
        
        // Keep only last 100 entries
        if (count($log) >= 100) {
            $log = array_slice($log, -99);
        }
        
        $log[] = $log_entry;
        update_option(self::MIGRATION_LOG_OPTION, $log);
    }
    
    /**
     * Log migration activity
     * 
     * @param string $message Log message
     * @param string $level Log level (info, warning, error)
     * @return void
     */
    private function log_migration(string $message, string $level = 'info'): void {
        // Check memory usage before logging
        if (function_exists('swap_check_memory_usage') && !swap_check_memory_usage('SWAP_Database_Migration::log_migration')) {
            error_log('SWAP: Migration logging skipped due to high memory usage');
            return;
        }
        
        $log_entry = [
            'timestamp' => function_exists('current_time') ? current_time('mysql') : date('Y-m-d H:i:s'),
            'level' => $level,
            'message' => $message
        ];
        
        $log = get_option(self::MIGRATION_LOG_OPTION, []);
        
        // Keep only last 100 entries - use more memory efficient approach
        if (count($log) >= 100) {
            // Remove oldest entries instead of using array_slice
            $log = array_splice($log, -99);
        }
        
        $log[] = $log_entry;
        update_option(self::MIGRATION_LOG_OPTION, $log);
        
        // Clear log variable to free memory
        unset($log);
    }
    
    /**
     * Get migration log
     * 
     * @param int $limit Maximum number of entries to return
     * @return array Migration log entries
     */
    public function get_migration_log(int $limit = 50): array {
        // Check memory usage before retrieving log
        if (function_exists('swap_check_memory_usage') && !swap_check_memory_usage('SWAP_Database_Migration::get_migration_log')) {
            error_log('SWAP: Migration log retrieval aborted due to high memory usage');
            return [];
        }
        
        $log = get_option(self::MIGRATION_LOG_OPTION, []);
        
        // Use more memory efficient approach for large logs
        if (count($log) > $limit) {
            $start_index = max(0, count($log) - $limit);
            $result = [];
            for ($i = $start_index; $i < count($log); $i++) {
                $result[] = $log[$i];
            }
            return $result;
        }
        
        return $log;
    }
    
    /**
     * Clear migration log
     * 
     * @return void
     */
    public function clear_migration_log(): void {
        delete_option(self::MIGRATION_LOG_OPTION);
    }
    
    /**
     * Get database health status
     * 
     * @return array Health status information
     */
    public function get_health_status(): array {
        $queue_table = $this->wpdb->prefix . 'swap_archive_queue';
        $history_table = $this->wpdb->prefix . 'swap_submissions_history';
        
        return [
            'db_version' => $this->get_current_version(),
            'target_version' => self::DB_VERSION,
            'needs_migration' => version_compare($this->get_current_version(), self::DB_VERSION, '<'),
            'tables' => [
                'queue' => [
                    'exists' => $this->table_exists($queue_table),
                    'row_count' => $this->get_table_row_count($queue_table)
                ],
                'history' => [
                    'exists' => $this->table_exists($history_table),
                    'row_count' => $this->get_table_row_count($history_table)
                ]
            ]
        ];
    }
    
    /**
     * Get table row count
     * 
     * @param string $table_name Table name
     * @return int Row count
     */
    private function get_table_row_count(string $table_name): int {
        if (!$this->table_exists($table_name)) {
            return 0;
        }
        
        return (int) $this->wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
    }
    
    /**
     * Static method for easy access to migration
     * 
     * @return array Migration results
     */
    public static function run(): array {
        $migration = new self();
        return $migration->maybe_migrate();
    }
}