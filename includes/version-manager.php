<?php
/**
 * Automated Version Management System
 * 
 * This script automatically increments the plugin version by 0.0.1 every time
 * code changes are made and updates all relevant files to maintain consistency.
 * 
 * @package SpunWebArchiveForge
 * @author Ryan Dickie Thompson
 * @since 1.0.4
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH') && php_sapi_name() !== 'cli') {
    exit('Direct access not allowed');
}

class SWAP_Version_Manager {
    
    /**
     * Plugin root directory
     * @var string
     */
    private $plugin_dir;
    
    /**
     * Current version
     * @var string
     */
    private $current_version;
    
    /**
     * New version after increment
     * @var string
     */
    private $new_version;
    
    /**
     * Files that contain version references
     * @var array
     */
    private $version_files = [
        'spun-web-archive-forge.php' => [
            'patterns' => [
                '/(\* Version: )(\d+\.\d+\.\d+)/' => 'plugin_header',
                '/(define\(\'SWAP_VERSION\', \')(\d+\.\d+\.\d+)(\'\);)/' => 'constant'
            ]
        ],
        'README.md' => [
            'patterns' => [
                '/(\*\*Version:\*\* )(\d+\.\d+\.\d+)/' => 'readme_version'
            ]
        ],
        'DEVELOPER-README.md' => [
            'patterns' => [
                '/(\*\*Version:\*\* )(\d+\.\d+\.\d+)/' => 'dev_readme_version'
            ]
        ],
        'docs/COMPREHENSIVE-DOCUMENTATION.md' => [
            'patterns' => [
                '/(\*\*Version:\*\* )(\d+\.\d+\.\d+)/' => 'docs_version'
            ]
        ],
        'docs/USER-GUIDE.md' => [
            'patterns' => [
                '/(\*Plugin Version: )(\d+\.\d+\.\d+)(\*)/' => 'user_guide_version'
            ]
        ],
        'docs/DEVELOPER-GUIDE.md' => [
            'patterns' => [
                '/(\*Plugin Version: )(\d+\.\d+\.\d+)(\*)/' => 'dev_guide_version'
            ]
        ],
        'docs/FEATURES.md' => [
            'patterns' => [
                '/(Spun Web Archive Forge v)(\d+\.\d+\.\d+)/' => 'features_version'
            ]
        ],
        'test-wordpress-compatibility.php' => [
            'patterns' => [
                '/(\$plugin_version = \')(\d+\.\d+\.\d+)(\';)/' => 'test_version',
                '/(\* @since )(\d+\.\d+\.\d+)/' => 'test_since'
            ]
        ],
        'wordpress-compatibility-report.json' => [
            'patterns' => [
                '/("plugin_version": ")(\d+\.\d+\.\d+)(")/' => 'json_version'
            ]
        ],
        '.wordpress-stubs.php' => [
            'patterns' => [
                '/(\* @updated )(\d+\.\d+\.\d+)/' => 'stubs_updated'
            ]
        ],
        'Spun Web Archive Slim Elite/.wordpress-stubs.php' => [
            'patterns' => [
                '/(\* @updated )(\d+\.\d+\.\d+)/' => 'slim_stubs_updated'
            ]
        ],
        'setup-dev.php' => [
            'patterns' => [
                '/(\* @since )(\d+\.\d+\.\d+)/' => 'setup_since'
            ]
        ],
        'Spun Web Archive Slim Elite/setup-dev.php' => [
            'patterns' => [
                '/(\* @since )(\d+\.\d+\.\d+)/' => 'slim_setup_since'
            ]
        ]
    ];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->plugin_dir = dirname(__FILE__);
        $this->detect_current_version();
    }
    
    /**
     * Detect current version from main plugin file
     */
    private function detect_current_version() {
        $main_file = $this->plugin_dir . '/spun-web-archive-forge.php';
        
        if (!file_exists($main_file)) {
            throw new Exception('Main plugin file not found');
        }
        
        $content = file_get_contents($main_file);
        
        // Extract version from plugin header
        if (preg_match('/\* Version: (\d+\.\d+\.\d+)/', $content, $matches)) {
            $this->current_version = $matches[1];
        } else {
            throw new Exception('Could not detect current version');
        }
    }
    
    /**
     * Increment version by 0.0.1
     */
    public function increment_version() {
        $version_parts = explode('.', $this->current_version);
        
        if (count($version_parts) !== 3) {
            throw new Exception('Invalid version format: ' . $this->current_version);
        }
        
        // Increment patch version
        $version_parts[2] = (int)$version_parts[2] + 1;
        
        $this->new_version = implode('.', $version_parts);
        
        return $this->new_version;
    }
    
    /**
     * Update all version references in files
     */
    public function update_all_files() {
        $updated_files = [];
        
        foreach ($this->version_files as $file_path => $config) {
            $full_path = $this->plugin_dir . '/' . $file_path;
            
            if (!file_exists($full_path)) {
                continue; // Skip non-existent files
            }
            
            $content = file_get_contents($full_path);
            $original_content = $content;
            
            foreach ($config['patterns'] as $pattern => $type) {
                $content = preg_replace_callback($pattern, function($matches) {
                    if (count($matches) >= 3) {
                        return $matches[1] . $this->new_version . (isset($matches[3]) ? $matches[3] : '');
                    }
                    return $matches[0];
                }, $content);
            }
            
            if ($content !== $original_content) {
                file_put_contents($full_path, $content);
                $updated_files[] = $file_path;
            }
        }
        
        return $updated_files;
    }
    
    /**
     * Add changelog entry for the new version
     */
    public function add_changelog_entry($change_description = 'Automated version increment') {
        $changelog_file = $this->plugin_dir . '/CHANGELOG.md';
        
        if (!file_exists($changelog_file)) {
            return false;
        }
        
        $content = file_get_contents($changelog_file);
        $date = date('Y-m-d');
        
        $new_entry = "## [{$this->new_version}] - {$date}\n\n";
        $new_entry .= "### Changed\n";
        $new_entry .= "- **Automated Version Update** - {$change_description}\n";
        $new_entry .= "- **Version Consistency** - Updated all version references from {$this->current_version} to {$this->new_version} across the entire plugin\n\n";
        
        // Insert after the "All notable changes..." line
        $content = preg_replace(
            '/(All notable changes to this project will be documented in this file\.\s*\n\n)/',
            "$1{$new_entry}",
            $content
        );
        
        file_put_contents($changelog_file, $content);
        
        return true;
    }
    
    /**
     * Run the complete version increment process
     */
    public function run($change_description = 'Code changes made') {
        try {
            echo "🔄 Starting automated version increment...\n";
            echo "📋 Current version: {$this->current_version}\n";
            
            // Increment version
            $this->increment_version();
            echo "🆕 New version: {$this->new_version}\n";
            
            // Update all files
            $updated_files = $this->update_all_files();
            echo "📝 Updated " . count($updated_files) . " files:\n";
            foreach ($updated_files as $file) {
                echo "   ✅ {$file}\n";
            }
            
            // Add changelog entry
            if ($this->add_changelog_entry($change_description)) {
                echo "📖 Added changelog entry\n";
            }
            
            echo "✅ Version increment completed successfully!\n";
            echo "🎯 Plugin version updated from {$this->current_version} to {$this->new_version}\n";
            
            return [
                'success' => true,
                'old_version' => $this->current_version,
                'new_version' => $this->new_version,
                'updated_files' => $updated_files
            ];
            
        } catch (Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get current version
     */
    public function get_current_version() {
        return $this->current_version;
    }
    
    /**
     * Get new version
     */
    public function get_new_version() {
        return $this->new_version;
    }
}

// CLI execution
if (php_sapi_name() === 'cli') {
    $manager = new SWAP_Version_Manager();
    
    $description = isset($argv[1]) ? $argv[1] : 'Code changes made';
    $result = $manager->run($description);
    
    exit($result['success'] ? 0 : 1);
}