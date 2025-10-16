<?php
// Minimal unit test for credentials encryption and migration

// Provide WordPress-like stubs
define('ABSPATH', __DIR__);

$GLOBALS['options'] = [];

function get_option($key, $default = false) {
    return $GLOBALS['options'][$key] ?? $default;
}

function update_option($key, $value, $autoload = false) {
    $GLOBALS['options'][$key] = $value;
    return true;
}

function wp_salt($scheme = 'auth') {
    return 'UNIT_TEST_SALT_' . $scheme;
}

// Include classes
require_once __DIR__ . '/../includes/class-credentials-page.php';
require_once __DIR__ . '/../includes/class-database-migration.php';

// Helper to assert
function assert_true($cond, $message) {
    echo ($cond ? "PASS: " : "FAIL: ") . $message . "\n";
}

// Test 1: Encrypted secret retrieval
$secret = 'SK-PLAINTEXT-TEST';
$algo = 'aes-256-cbc';
$ivlen = openssl_cipher_iv_length($algo);
$iv = random_bytes($ivlen);
$key = hash('sha256', wp_salt('auth'), true);
$ciphertext = openssl_encrypt($secret, $algo, $key, OPENSSL_RAW_DATA, $iv);

update_option(SWAP_Credentials_Page::ENCRYPTED_SECRET_OPTION, [
    'ciphertext' => base64_encode($ciphertext),
    'iv' => base64_encode($iv),
]);

$decrypted = SWAP_Credentials_Page::get_secret_key();
assert_true($decrypted === $secret, 'Encrypted secret decrypts correctly');

// Test 2: Migration from plaintext to encrypted
update_option('swap_api_credentials', [
    'access_key' => 'AK-TEST',
    'secret_key' => 'SK-PLAINTEXT-MIGRATE'
]);

// Clear encrypted option to force migration
update_option(SWAP_Credentials_Page::ENCRYPTED_SECRET_OPTION, []);

// Set DB version to 1.3 so migration to 1.4 runs
update_option('swap_db_version', '1.3');

// Minimal wpdb stub for constructor type-hint
class wpdb {
    public $prefix = 'wp_';
    public $last_error = '';
    public function query($sql) { return true; }
    public function prepare($sql, $args) { return $sql; }
    public function get_results($sql) { return []; }
}

$migration = new SWAP_Database_Migration(new wpdb());
$results = $migration->maybe_migrate();

$encrypted = get_option(SWAP_Credentials_Page::ENCRYPTED_SECRET_OPTION, []);
$creds = get_option('swap_api_credentials', []);

assert_true(!empty($encrypted['ciphertext']) && !empty($encrypted['iv']), 'Migration created encrypted secret');
assert_true(!isset($creds['secret_key']), 'Migration removed plaintext secret');

// Validate that credentials page now returns the original secret
$decrypted_after = SWAP_Credentials_Page::get_secret_key();
assert_true(!empty($decrypted_after), 'Decrypted secret available after migration');

echo "\nMigration results:\n";
foreach ($results as $version => $info) {
    echo "- $version: " . ($info['success'] ? 'success' : 'failed') . " (" . round($info['execution_time'], 4) . "s)\n";
}

echo "\nAll tests completed.\n";
?>