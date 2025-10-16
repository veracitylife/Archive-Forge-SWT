# Spun Web Archive Forge - User Guide

## Table of Contents
1. [Overview](#overview)
2. [Installation](#installation)
3. [Initial Setup](#initial-setup)
4. [Configuration](#configuration)
5. [Using the Plugin](#using-the-plugin)
6. [Features](#features)
7. [Troubleshooting](#troubleshooting)
8. [FAQ](#faq)

## Overview

Spun Web Archive Forge is a professional WordPress plugin that automatically submits your website content to the Internet Archive (Wayback Machine). This ensures your content is preserved for posterity and provides backup archival services.

### Key Benefits
- **Automatic Archiving**: Submit new posts automatically when published
- **Individual Control**: Submit specific posts/pages on demand
- **Status Tracking**: Monitor submission status and history
- **Error Handling**: Comprehensive error detection and recovery
- **Professional Interface**: Clean, intuitive admin interface

### System Requirements
- WordPress 5.0 or higher
- PHP 7.4 or higher (recommended: PHP 8.1+)
- MySQL 5.6 or higher
- cURL enabled on server
- Internet Archive account with S3 API credentials

## Installation

### Method 1: Manual Installation
1. Download the plugin ZIP file
2. Log into your WordPress admin dashboard
3. Navigate to **Plugins > Add New**
4. Click **Upload Plugin**
5. Choose the ZIP file and click **Install Now**
6. Click **Activate Plugin**

### Method 2: FTP Installation
1. Extract the plugin ZIP file
2. Upload the `spun-web-archive-forge` folder to `/wp-content/plugins/`
3. Log into WordPress admin
4. Navigate to **Plugins**
5. Find "Spun Web Archive Forge" and click **Activate**

### Post-Installation
After activation, you'll see a new menu item **Web Archive Elite** in your WordPress admin sidebar.

## Initial Setup

### Step 1: Get Internet Archive Credentials
1. Visit [archive.org](https://archive.org) and create an account
2. Go to your account settings
3. Navigate to the S3 API section
4. Generate your Access Key and Secret Key
5. Keep these credentials secure - you'll need them for setup

### Step 2: Configure API Credentials
1. In WordPress admin, go to **Web Archive Forge > API Credentials**
2. Enter your Internet Archive credentials:
   - **Access Key**: Your S3 access key
   - **Secret Key**: Your S3 secret key (stored encrypted; displayed masked after save)
3. Click **Test Connection** to verify credentials
4. To update the Secret Key later, re-enter it; the stored value is encrypted and never shown in plaintext
4. Save settings when test is successful

## Configuration

### API Settings
Navigate to **Web Archive Elite > Settings > API Settings**

#### Submission Method
- **Individual Submission**: Submit posts one at a time (recommended)
- **Batch Submission**: Submit multiple posts simultaneously

#### Connection Settings
- **Timeout**: Request timeout in seconds (default: 30)
- **Retry Attempts**: Number of retry attempts for failed submissions (default: 3)
- **Rate Limiting**: Delay between submissions to respect Archive.org limits

### Auto Submission Settings
Navigate to **Web Archive Elite > Settings > Auto Submission**

#### Auto Submit Options
- **Enable Auto Submit**: Automatically submit new posts when published
- **Post Types**: Select which post types to auto-submit
  - Posts
  - Pages
  - Custom post types
- **Post Status**: Submit only published posts
- **Exclude Categories**: Exclude specific categories from auto-submission

#### Scheduling
- **Immediate**: Submit immediately upon publication
- **Delayed**: Submit after a specified delay (useful for last-minute edits)
- **Scheduled**: Submit at specific times using WordPress cron

### Queue Management
Navigate to **Web Archive Elite > Settings > Queue Management**

#### Queue Settings
- **Queue Size**: Maximum number of items in submission queue
- **Processing Interval**: How often to process queue items
- **Retry Failed**: Automatically retry failed submissions
- **Cleanup**: Remove old completed/failed items from queue

## Using the Plugin

### Individual Post Submission

#### From Posts/Pages List
1. Go to **Posts > All Posts** or **Pages > All Pages**
2. Find the post you want to archive
3. Click **Submit to Archive** in the row actions
4. Monitor the submission status in the Archive Status column

#### From Post Editor
1. Open any post/page for editing
2. Look for the **Archive Submission** meta box
3. Click **Submit to Archive** button
4. View submission history and status

### Bulk Operations
1. Go to **Posts > All Posts**
2. Select multiple posts using checkboxes
3. Choose **Submit to Archive** from bulk actions dropdown
4. Click **Apply**

### Monitoring Submissions

#### Archive Status Column
- **Green**: Successfully archived
- **Yellow**: Pending submission
- **Red**: Failed submission
- **Gray**: Not submitted

#### Submission History
1. Navigate to **Web Archive Elite > Submission History**
2. View complete submission logs with:
   - Submission date and time
   - Post title and URL
   - Archive URL (if successful)
   - Status and error messages
   - Retry attempts

### Widgets and Shortcodes

#### Archive Widget
1. Go to **Appearance > Widgets**
2. Add **Archive Links Widget**
3. Configure display options:
   - Show archive links for current post
   - Display archive date
   - Custom link text

#### Archive Shortcode
Use `[archive_link]` shortcode in posts/pages:
```
[archive_link post_id="123" text="View Archive"]
[archive_link url="https://example.com" text="Archive This Page"]
```

## Features

### Core Features
- **Automatic Submission**: Submit new content automatically
- **Individual Control**: Submit specific posts on demand
- **Status Tracking**: Real-time submission status monitoring
- **Error Handling**: Comprehensive error detection and recovery
- **Queue Management**: Efficient submission queue processing

### Advanced Features
- **Custom Post Types**: Support for all post types
- **Category Exclusion**: Exclude specific categories
- **Retry Logic**: Automatic retry for failed submissions
- **Rate Limiting**: Respect Archive.org API limits
- **Detailed Logging**: Complete submission history and logs

### Admin Interface
- **Dashboard Widget**: Quick overview of submission statistics
- **Admin Columns**: Archive status in posts/pages lists
- **Meta Boxes**: Submission controls in post editor
- **Menu Integration**: Dedicated admin menu section

### Developer Features
- **Hooks and Filters**: Extensive customization options
- **API Integration**: Direct Archive.org API integration
- **Database Optimization**: Efficient data storage and retrieval
- **Security**: Comprehensive security measures

## Troubleshooting

### Common Issues

#### "Connection Failed" Error
**Symptoms**: Cannot connect to Archive.org
**Solutions**:
1. Check internet connection
2. Verify API credentials
3. Check server firewall settings
4. Increase timeout settings

#### "Invalid Credentials" Error
**Symptoms**: API test fails with authentication error
**Solutions**:
1. Verify Access Key and Secret Key
2. Check for extra spaces in credentials
3. Regenerate credentials at archive.org
4. Ensure account is in good standing

#### "Submission Timeout" Error
**Symptoms**: Submissions fail due to timeout
**Solutions**:
1. Increase timeout setting in API Settings
2. Check server performance
3. Submit during off-peak hours
4. Enable retry attempts

#### Posts Not Auto-Submitting
**Symptoms**: Auto-submission not working
**Solutions**:
1. Verify auto-submission is enabled
2. Check post type settings
3. Ensure post is published (not draft)
4. Check category exclusions
5. Verify WordPress cron is working

### Debug Mode
Enable debug mode for detailed error information:
1. Add to wp-config.php: `define('WP_DEBUG', true);`
2. Check error logs in `/wp-content/debug.log`
3. Review submission history for detailed error messages

### Performance Optimization
- **Queue Processing**: Adjust processing interval based on server capacity
- **Rate Limiting**: Increase delays if experiencing rate limit errors
- **Batch Size**: Reduce batch size for slower servers
- **Cleanup**: Regularly clean old submission records

## FAQ

### General Questions

**Q: Is this plugin free?**
A: Yes, Spun Web Archive Forge is free to use. You only need a free Internet Archive account.

**Q: Does this work with any WordPress theme?**
A: Yes, the plugin is theme-independent and works with any properly coded WordPress theme.

**Q: Can I archive custom post types?**
A: Yes, the plugin supports all public post types including custom post types.

**Q: How often should I archive my content?**
A: This depends on your content update frequency. Auto-submission ensures new content is archived immediately.

### Technical Questions

**Q: What happens if Archive.org is down?**
A: The plugin will retry failed submissions automatically. Items remain in the queue until successfully submitted.

**Q: Can I archive password-protected posts?**
A: Archive.org can only archive publicly accessible content. Password-protected posts will fail to archive.

**Q: Does this affect my website performance?**
A: No, submissions are processed in the background and don't affect frontend performance.

**Q: Can I archive external URLs?**
A: The plugin is designed for your WordPress content, but you can use the shortcode to create archive links for external URLs.

### Troubleshooting Questions

**Q: Why are my submissions failing?**
A: Common causes include invalid credentials, network issues, or Archive.org being temporarily unavailable. Check the submission history for specific error messages.

**Q: Can I resubmit failed items?**
A: Yes, you can resubmit individual posts or enable automatic retry for failed submissions.

**Q: How do I know if a post was successfully archived?**
A: Check the Archive Status column in your posts list or view the submission history for detailed logs.

### Support

For additional support:
1. Check the plugin documentation
2. Review submission history for error details
3. Enable debug mode for detailed logging
4. Contact support with specific error messages

---

*Last updated: January 2025*
*Plugin Version: 1.0.7*