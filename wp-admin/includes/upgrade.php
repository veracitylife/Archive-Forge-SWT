<?php
/**
 * Mock WordPress upgrade.php file for testing
 */

if (!function_exists('dbDelta')) {
    function dbDelta($queries = '', $execute = true) {
        echo "✓ Mock dbDelta executed\n";
        return array();
    }
}