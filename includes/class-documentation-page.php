<?php
/**
 * Documentation Page Class
 *
 * Handles the display of comprehensive plugin documentation
 *
 * @package Spun_Web_Archive_Forge
 * @since 0.2.5
 * @version 0.6.1
 * @updated 0.3.5 - Enhanced WordPress environment validation and linter compatibility
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SWAP_Documentation_Page {
    
    /**
     * Render the documentation page (instance method)
     */
    public function render() {
        self::display_documentation();
    }
    
    /**
     * Display the documentation page
     */
    public static function display_documentation() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="swap-documentation">
                <div class="swap-doc-header">
                    <h2>Spun Web Archive Forge - Complete Documentation</h2>
                    <p class="description">Professional WordPress plugin for automatically submitting content to the Internet Archive (Wayback Machine).</p>
                </div>

                <div class="swap-doc-content">
                    
                    <div class="swap-doc-section">
                        <h3>📋 Overview</h3>
                        <p>Spun Web Archive Forge submits your WordPress URLs to the Internet Archive (Wayback Machine). It can auto submit new posts when you publish, and it can bulk submit your back catalog. It tracks status, retries failures, and shows logs and history in wp-admin.</p>
                    </div>

                    <div class="swap-doc-section">
                        <h3>🆕 Version 0.3.3 Improvements</h3>
                        <ul>
                            <li><strong>Individual Post Submission:</strong> Replaced bulk actions with individual "Submit to Archive" links for each post/page</li>
                            <li><strong>Streamlined Interface:</strong> Removed bulk submission settings and references for a cleaner admin experience</li>
                            <li><strong>On-Demand Submission:</strong> Submit posts individually directly from the All Posts/Pages screens</li>
                            <li><strong>Simplified Workflow:</strong> Focus on individual post control for better user experience and precision</li>
                            <li><strong>Enhanced Post Actions:</strong> New SWAP_Post_Actions class provides seamless integration with WordPress row actions</li>
                        </ul>
                    </div>

                    <div class="swap-doc-section">
                        <h3>🔄 Version 0.3.1 Improvements</h3>
                        <ul>
                            <li><strong>Enhanced Error Handling:</strong> Comprehensive connection error detection for Archive.org timeouts and unreachable sites</li>
                            <li><strong>User-Friendly Error Messages:</strong> Clear, actionable error messages including "This site can't be reached" for DNS failures</li>
                            <li><strong>Smart Error Recovery:</strong> Automatic error type detection with specific guidance for timeouts, connection refused, and SSL errors</li>
                            <li><strong>Enhanced Visual Feedback:</strong> Improved error display with color-coded status indicators and detailed error explanations</li>
                            <li><strong>Better Connection Diagnostics:</strong> Enhanced API testing with specific error categorization and troubleshooting guidance</li>
                        </ul>
                    </div>



                    <div class="swap-doc-section">
                        <h3>✨ Highlights</h3>
                        <ul>
                            <li><strong>Automatic submission on publish</strong> - Optional delay configuration</li>
                            <li><strong>Individual post submission</strong> - Submit posts one-by-one from All Posts/Pages screens</li>
                            <li><strong>Direct Archive.org S3 API integration</strong> - Enhanced reliability</li>
                            <li><strong>Submission history, statuses, and logs</strong> - Complete tracking in admin</li>
                            <li><strong>Status in the post editor</strong> - And in Posts or Pages list</li>
                            <li><strong>Works with posts, pages, and custom post types</strong></li>
                            <li><strong>Background processing</strong> - With WordPress cron</li>
                            <li><strong>CSV export</strong> - For reports and audits</li>
                            <li><strong>Hooks and filters</strong> - For developers</li>
                        </ul>
                    </div>

                    <div class="swap-doc-section">
                        <h3>⚙️ Requirements</h3>
                        <ul>
                            <li>WordPress 5.0 or higher</li>
                            <li>PHP 8.1 or higher (compatible with PHP 8.2)</li>
                            <li>MySQL 5.6 or higher</li>
                            <li>Archive.org account with S3 API keys</li>
                            <li>cURL enabled on the server</li>
                            <li>Tested up to WordPress 6.7.1</li>
                        </ul>
                    </div>

                    <div class="swap-doc-section">
                        <h3>🚀 Installation</h3>
                        <ol>
                            <li>Download the plugin</li>
                            <li>Upload the folder to <code>/wp-content/plugins/</code></li>
                            <li>Activate in Plugins</li>
                            <li>Open <strong>Spun Web Archive Forge</strong> in the admin menu to configure</li>
                        </ol>
                    </div>

                    <div class="swap-doc-section">
                        <h3>🔗 Connect Archive.org</h3>
                        <ol>
                            <li>Create or sign in to your <a href="https://archive.org" target="_blank">Archive.org account</a></li>
                            <li>Generate S3 API keys in your <a href="https://archive.org/account/s3.php" target="_blank">Archive.org account</a></li>
                            <li>In WordPress, go to <strong>Spun Web Archive Forge → API Credentials</strong></li>
                            <li>Paste Key and Secret, then click <strong>Test Connection</strong></li>
                        </ol>
                        <p><strong>💡 Tip:</strong> Keep keys private and restrict settings access to admins.</p>
                    </div>

                    <div class="swap-doc-section">
                        <h3>⚡ Configure Behavior</h3>
                        
                        <h4>Automatic Submission</h4>
                        <ul>
                            <li><strong>Enable Auto Submission:</strong> On or off</li>
                            <li><strong>Submission Delay:</strong> Minutes to wait after publish</li>
                            <li><strong>Post Types:</strong> Which content to auto submit</li>
                            <li><strong>Retry Settings:</strong> Attempts and delay between attempts</li>
                        </ul>
                        <p>The plugin queues, submits, and retries in the background.</p>



                        <h4>Background Jobs</h4>
                        <p>Processing runs with WordPress cron. Keep it enabled on your host.</p>
                    </div>

                    <div class="swap-doc-section">
                        <h3>📝 Daily Use</h3>
                        
                        <h4>Auto Mode - Set and Forget</h4>
                        <ol>
                            <li>Publish as usual</li>
                            <li>The plugin queues the URL</li>
                            <li>It submits after your chosen delay</li>
                            <li>It retries on failure</li>
                            <li>See status in the Posts or Pages list and inside the editor</li>
                        </ol>

                        <h4>Individual Submission Mode</h4>
                        <ol>
                            <li>Go to <strong>Posts → All Posts</strong> or <strong>Pages → All Pages</strong></li>
                            <li>Hover over any post/page row to reveal row actions</li>
                            <li>Click <strong>Submit to Archive</strong> link for individual submission</li>
                            <li>Monitor submission status in the posts list and submission history</li>
                            <li>Each post is submitted individually with immediate feedback</li>
                        </ol>
                    </div>

                    <div class="swap-doc-section">
                        <h3>📊 Monitoring and History</h3>
                        <ul>
                            <li><strong>Posts list column:</strong> Quick status glance with submission status</li>
                            <li><strong>Editor meta box:</strong> Last result and history for individual posts</li>
                            <li><strong>Submission History:</strong> Complete records with timestamps, URLs, and archive links in admin menu</li>

                            <li><strong>Color states:</strong> Success (green), Failed (red), Pending (yellow)</li>
                        </ul>
                    </div>

                    <div class="swap-doc-section">
                        <h3>🎛️ What You Will See in Admin</h3>
                        <ul>
                            <li>A main menu <strong>Spun Web Archive Forge</strong> with organized submenus</li>
                            <li>API credentials, auto settings, submission history</li>
                            <li><strong>Submit to Archive</strong> individual links in Posts and Pages screens</li>
                            <li>Submission tracking and status monitoring throughout WordPress admin</li>
                            <li>Counts of total, successful, failed, and pending submissions</li>
                        </ul>
                    </div>

                    <div class="swap-doc-section">
                        <h3>💾 Data and Storage</h3>
                        <p>Creates one table: <code>wp_swap_submissions</code> to track attempts and states.</p>
                        <p>Uninstall can remove all plugin data (tables, options, post meta, user meta, cached data), with validation and permission checks.</p>
                    </div>

                    <div class="swap-doc-section">
                        <h3>🔒 Security Notes</h3>
                        <p>Nonces, AJAX handling, and permission checks have been improved. Keep WordPress, PHP, and the plugin current. Limit settings access to trusted admins.</p>
                    </div>

                    <div class="swap-doc-section">
                        <h3>🔧 Developer Hooks</h3>
                        
                        <h4>Actions</h4>
                        <ul>
                            <li><code>swap_before_submission</code></li>
                            <li><code>swap_after_submission</code></li>
                            <li><code>swap_submission_success</code></li>
                            <li><code>swap_submission_failed</code></li>
                        </ul>

                        <h4>Filters</h4>
                        <ul>
                            <li><code>swap_submission_url</code></li>
                            <li><code>swap_submission_data</code></li>
                            <li><code>swap_retry_attempts</code></li>
                            <li><code>swap_submission_delay</code></li>
                        </ul>
                    </div>

                    <div class="swap-doc-section">
                        <h3>🛠️ Troubleshooting</h3>
                        
                        <h4>API Connection Failed</h4>
                        <p>Check Archive.org Key and Secret. Confirm cURL and outbound HTTPS are allowed by the host.</p>

                        <h4>Nothing is Processing</h4>
                        <p>Confirm WordPress cron is running. Re-save plugin settings. Check the plugin's logs and the Submission History tab.</p>

                        <h4>Individual Submissions Feel Slow</h4>
                        <p>Check your server resources and Internet Archive API response times. Individual submissions are processed one at a time for better reliability.</p>

                        <h4>Detailed Errors</h4>
                        <p>Add to <code>wp-config.php</code>:</p>
                        <pre><code>define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);</code></pre>
                    </div>

                    <div class="swap-doc-section">
                        <h3>📋 Quick Checklist</h3>
                        <ul>
                            <li>✅ Plugin installed and activated</li>
                            <li>✅ Archive.org S3 keys saved and connection test passed</li>
                            <li>✅ Auto submission configured, or individual submission links available</li>
                            <li>✅ WP cron enabled on your host</li>
                            <li>✅ Status column visible in Posts or Pages</li>
                            <li>✅ "Submit to Archive" individual links available in All Posts/Pages screens</li>
                        </ul>
                    </div>

                    <div class="swap-doc-section">
                        <h3>📄 License and Credits</h3>
                        <ul>
                            <li><strong>License:</strong> GPL v2 or later</li>
                            <li><strong>Author:</strong> Ryan Dickie Thompson, Spun Web Technology</li>
                            <li><strong>Support:</strong> <a href="mailto:support@spunwebtechnology.com">support@spunwebtechnology.com</a></li>
                        </ul>
                    </div>

                </div>
            </div>
        </div>

        <style>
        .swap-documentation {
            max-width: 1000px;
            margin: 20px 0;
        }
        .swap-doc-header {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 4px solid #0073aa;
        }
        .swap-doc-header h2 {
            margin: 0 0 10px 0;
            color: #0073aa;
        }
        .swap-doc-section {
            background: #fff;
            padding: 25px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-left: 4px solid #00a32a;
        }
        .swap-doc-section h3 {
            margin-top: 0;
            color: #1d2327;
            font-size: 1.3em;
        }
        .swap-doc-section h4 {
            color: #0073aa;
            margin-top: 20px;
            margin-bottom: 10px;
        }
        .swap-doc-section ul, .swap-doc-section ol {
            margin-left: 20px;
        }
        .swap-doc-section li {
            margin-bottom: 8px;
            line-height: 1.5;
        }
        .swap-doc-section code {
            background: #f1f1f1;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        .swap-doc-section pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            border-left: 3px solid #0073aa;
        }
        .swap-doc-section pre code {
            background: none;
            padding: 0;
        }
        .swap-doc-section a {
            color: #0073aa;
            text-decoration: none;
        }
        .swap-doc-section a:hover {
            text-decoration: underline;
        }
        </style>
        <?php
    }
}
