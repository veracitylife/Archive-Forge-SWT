<?php
/**
 * Memory Utility Class
 * 
 * Provides memory monitoring and optimization utilities for the plugin
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
 * Memory utility class for monitoring and optimizing memory usage
 */
class SWAP_Memory_Utils {
    
    /**
     * Memory threshold percentages
     */
    private const MEMORY_WARNING_THRESHOLD = 75; // 75% of memory limit
    private const MEMORY_CRITICAL_THRESHOLD = 85; // 85% of memory limit
    private const MEMORY_ABORT_THRESHOLD = 90; // 90% of memory limit
    
    /**
     * Default memory limits in MB
     */
    private const DEFAULT_MEMORY_LIMIT = 128;
    private const MIN_MEMORY_LIMIT = 64;
    
    /**
     * Memory usage log option key
     */
    private const MEMORY_LOG_OPTION = 'swap_memory_usage_log';
    
    /**
     * Maximum log entries to keep
     */
    private const MAX_LOG_ENTRIES = 100;
    
    /**
     * Check if memory usage is within safe limits
     * 
     * @param string $context Context identifier for logging
     * @param int $threshold_percentage Custom threshold percentage (optional)
     * @return bool True if memory usage is safe, false if too high
     */
    public static function check_memory_usage(string $context = '', int $threshold_percentage = null): bool {
        $threshold = $threshold_percentage ?? self::MEMORY_ABORT_THRESHOLD;
        $current_usage = self::get_memory_usage_percentage();
        
        if ($current_usage >= $threshold) {
            self::log_memory_warning($context, $current_usage, $threshold);
            return false;
        }
        
        return true;
    }
    
    /**
     * Get current memory usage as percentage of limit
     * 
     * @return float Memory usage percentage
     */
    public static function get_memory_usage_percentage(): float {
        $current_usage = memory_get_usage(true);
        $memory_limit = self::get_memory_limit();
        
        if ($memory_limit <= 0) {
            return 0.0;
        }
        
        return ($current_usage / $memory_limit) * 100;
    }
    
    /**
     * Get current memory usage in MB
     * 
     * @param bool $real_usage Get real usage (true) or emalloc usage (false)
     * @return float Memory usage in MB
     */
    public static function get_memory_usage_mb(bool $real_usage = true): float {
        return memory_get_usage($real_usage) / 1024 / 1024;
    }
    
    /**
     * Get peak memory usage in MB
     * 
     * @param bool $real_usage Get real usage (true) or emalloc usage (false)
     * @return float Peak memory usage in MB
     */
    public static function get_peak_memory_usage_mb(bool $real_usage = true): float {
        return memory_get_peak_usage($real_usage) / 1024 / 1024;
    }
    
    /**
     * Get memory limit in bytes
     * 
     * @return int Memory limit in bytes
     */
    public static function get_memory_limit(): int {
        $memory_limit = ini_get('memory_limit');
        
        if ($memory_limit === '-1') {
            // No memory limit set, use default
            return self::DEFAULT_MEMORY_LIMIT * 1024 * 1024;
        }
        
        return self::parse_memory_limit($memory_limit);
    }
    
    /**
     * Get memory limit in MB
     * 
     * @return float Memory limit in MB
     */
    public static function get_memory_limit_mb(): float {
        return self::get_memory_limit() / 1024 / 1024;
    }
    
    /**
     * Parse memory limit string to bytes
     * 
     * @param string $memory_limit Memory limit string (e.g., "128M", "1G")
     * @return int Memory limit in bytes
     */
    private static function parse_memory_limit(string $memory_limit): int {
        $memory_limit = trim($memory_limit);
        $last_char = strtolower(substr($memory_limit, -1));
        $value = (int) substr($memory_limit, 0, -1);
        
        switch ($last_char) {
            case 'g':
                $value *= 1024;
                // Fall through
            case 'm':
                $value *= 1024;
                // Fall through
            case 'k':
                $value *= 1024;
                break;
            default:
                $value = (int) $memory_limit;
        }
        
        return max($value, self::MIN_MEMORY_LIMIT * 1024 * 1024);
    }
    
    /**
     * Calculate dynamic batch size based on memory usage
     * 
     * @param int $default_size Default batch size
     * @param int $min_size Minimum batch size
     * @param int $max_size Maximum batch size
     * @return int Optimized batch size
     */
    public static function calculate_dynamic_batch_size(
        int $default_size = 50,
        int $min_size = 10,
        int $max_size = 200
    ): int {
        $memory_usage = self::get_memory_usage_percentage();
        
        if ($memory_usage < 50) {
            // Low memory usage, can use larger batches
            return min($max_size, $default_size * 2);
        } elseif ($memory_usage < 70) {
            // Moderate memory usage, use default
            return $default_size;
        } elseif ($memory_usage < 85) {
            // High memory usage, reduce batch size
            return max($min_size, intval($default_size * 0.5));
        } else {
            // Critical memory usage, use minimum batch size
            return $min_size;
        }
    }
    
    /**
     * Force garbage collection and return memory freed
     * 
     * @return float Memory freed in MB
     */
    public static function force_garbage_collection(): float {
        $memory_before = memory_get_usage(true);
        
        // Force garbage collection
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
        
        $memory_after = memory_get_usage(true);
        $memory_freed = ($memory_before - $memory_after) / 1024 / 1024;
        
        return max(0, $memory_freed);
    }
    
    /**
     * Log memory warning
     * 
     * @param string $context Context where warning occurred
     * @param float $current_usage Current memory usage percentage
     * @param float $threshold Threshold that was exceeded
     * @return void
     */
    private static function log_memory_warning(string $context, float $current_usage, float $threshold): void {
        $log_entry = [
            'timestamp' => current_time('mysql'),
            'context' => $context,
            'memory_usage_percent' => round($current_usage, 2),
            'memory_usage_mb' => round(self::get_memory_usage_mb(), 2),
            'memory_limit_mb' => round(self::get_memory_limit_mb(), 2),
            'threshold_percent' => $threshold,
            'severity' => self::get_severity_level($current_usage)
        ];
        
        // Add to WordPress error log
        error_log(sprintf(
            'SWAP Memory Warning [%s]: %.2f%% usage (%.2fMB/%.2fMB) exceeded %d%% threshold',
            $context,
            $current_usage,
            self::get_memory_usage_mb(),
            self::get_memory_limit_mb(),
            $threshold
        ));
        
        // Store in plugin log
        self::add_memory_log_entry($log_entry);
    }
    
    /**
     * Get severity level based on memory usage
     * 
     * @param float $memory_usage Memory usage percentage
     * @return string Severity level
     */
    private static function get_severity_level(float $memory_usage): string {
        if ($memory_usage >= self::MEMORY_ABORT_THRESHOLD) {
            return 'critical';
        } elseif ($memory_usage >= self::MEMORY_CRITICAL_THRESHOLD) {
            return 'high';
        } elseif ($memory_usage >= self::MEMORY_WARNING_THRESHOLD) {
            return 'warning';
        } else {
            return 'info';
        }
    }
    
    /**
     * Add entry to memory usage log
     * 
     * @param array $log_entry Log entry data
     * @return void
     */
    private static function add_memory_log_entry(array $log_entry): void {
        $log = get_option(self::MEMORY_LOG_OPTION, []);
        
        // Keep only last entries to prevent log from growing too large
        if (count($log) >= self::MAX_LOG_ENTRIES) {
            $log = array_slice($log, -(self::MAX_LOG_ENTRIES - 1));
        }
        
        $log[] = $log_entry;
        update_option(self::MEMORY_LOG_OPTION, $log);
    }
    
    /**
     * Get memory usage log
     * 
     * @param int $limit Maximum number of entries to return
     * @return array Memory log entries
     */
    public static function get_memory_log(int $limit = 50): array {
        $log = get_option(self::MEMORY_LOG_OPTION, []);
        
        if (count($log) > $limit) {
            return array_slice($log, -$limit);
        }
        
        return $log;
    }
    
    /**
     * Clear memory usage log
     * 
     * @return void
     */
    public static function clear_memory_log(): void {
        delete_option(self::MEMORY_LOG_OPTION);
    }
    
    /**
     * Get memory statistics
     * 
     * @return array Memory statistics
     */
    public static function get_memory_stats(): array {
        return [
            'current_usage_mb' => round(self::get_memory_usage_mb(), 2),
            'current_usage_percent' => round(self::get_memory_usage_percentage(), 2),
            'peak_usage_mb' => round(self::get_peak_memory_usage_mb(), 2),
            'memory_limit_mb' => round(self::get_memory_limit_mb(), 2),
            'available_mb' => round(self::get_memory_limit_mb() - self::get_memory_usage_mb(), 2),
            'warning_threshold' => self::MEMORY_WARNING_THRESHOLD,
            'critical_threshold' => self::MEMORY_CRITICAL_THRESHOLD,
            'abort_threshold' => self::MEMORY_ABORT_THRESHOLD,
            'status' => self::get_memory_status()
        ];
    }
    
    /**
     * Get current memory status
     * 
     * @return string Memory status (safe, warning, critical, abort)
     */
    public static function get_memory_status(): string {
        $usage = self::get_memory_usage_percentage();
        
        if ($usage >= self::MEMORY_ABORT_THRESHOLD) {
            return 'abort';
        } elseif ($usage >= self::MEMORY_CRITICAL_THRESHOLD) {
            return 'critical';
        } elseif ($usage >= self::MEMORY_WARNING_THRESHOLD) {
            return 'warning';
        } else {
            return 'safe';
        }
    }
    
    /**
     * Monitor memory usage during a callback execution
     * 
     * @param callable $callback Function to execute
     * @param string $context Context identifier
     * @return mixed Result of callback execution
     * @throws Exception If memory usage becomes critical during execution
     */
    public static function monitor_execution(callable $callback, string $context = ''): mixed {
        $start_memory = memory_get_usage(true);
        $start_time = microtime(true);
        
        try {
            $result = $callback();
            
            $end_memory = memory_get_usage(true);
            $end_time = microtime(true);
            $memory_used = ($end_memory - $start_memory) / 1024 / 1024;
            $execution_time = $end_time - $start_time;
            
            // Log execution statistics
            self::log_execution_stats($context, $memory_used, $execution_time);
            
            return $result;
            
        } catch (Exception $e) {
            $end_memory = memory_get_usage(true);
            $memory_used = ($end_memory - $start_memory) / 1024 / 1024;
            
            error_log(sprintf(
                'SWAP Memory Monitor [%s]: Exception occurred after using %.2fMB - %s',
                $context,
                $memory_used,
                $e->getMessage()
            ));
            
            throw $e;
        }
    }
    
    /**
     * Log execution statistics
     * 
     * @param string $context Execution context
     * @param float $memory_used Memory used in MB
     * @param float $execution_time Execution time in seconds
     * @return void
     */
    private static function log_execution_stats(string $context, float $memory_used, float $execution_time): void {
        if ($memory_used > 10 || $execution_time > 5) { // Log if significant resource usage
            error_log(sprintf(
                'SWAP Memory Monitor [%s]: Used %.2fMB memory in %.2fs',
                $context,
                $memory_used,
                $execution_time
            ));
        }
    }
    
    /**
     * Get current memory usage in MB
     * 
     * @return int Memory usage in MB
     */
    public static function get_current_memory_usage(): int {
        return intval(memory_get_usage(true) / 1024 / 1024);
    }
    
    /**
     * Get peak memory usage in MB
     * 
     * @return int Peak memory usage in MB
     */
    public static function get_peak_memory_usage(): int {
        return intval(memory_get_peak_usage(true) / 1024 / 1024);
    }
    
    /**
     * Get dynamic batch size based on current memory usage
     * 
     * @param int $base_size Base batch size
     * @return int Adjusted batch size
     */
    public static function get_dynamic_batch_size(int $base_size): int {
        $usage_percent = self::get_memory_usage_percentage();
        
        if ($usage_percent > 80) {
            return max(1, intval($base_size * 0.25)); // Reduce to 25%
        } elseif ($usage_percent > 70) {
            return max(1, intval($base_size * 0.5));  // Reduce to 50%
        } elseif ($usage_percent > 60) {
            return max(1, intval($base_size * 0.75)); // Reduce to 75%
        }
        
        return $base_size; // No reduction needed
    }
}

// Global convenience function for memory checking
if (!function_exists('swap_check_memory_usage')) {
    /**
     * Global convenience function for memory checking
     * 
     * @param string $context Context identifier
     * @param int $threshold_percentage Custom threshold percentage
     * @return bool True if memory usage is safe
     */
    function swap_check_memory_usage(string $context = '', int $threshold_percentage = null): bool {
        return SWAP_Memory_Utils::check_memory_usage($context, $threshold_percentage);
    }
}

if (!function_exists('swap_get_memory_stats')) {
    /**
     * Global convenience function for getting memory statistics
     * 
     * @return array Memory statistics
     */
    function swap_get_memory_stats(): array {
        return SWAP_Memory_Utils::get_memory_stats();
    }
}