# Usage Guide - Spun Web Archive Forge

This guide covers how to use all the features of the Spun Web Archive Forge plugin.

## Table of Contents
1. [Basic Setup](#basic-setup)
2. [Archive Widgets](#archive-widgets)
3. [Archive Shortcodes](#archive-shortcodes)
4. [Admin Interface](#admin-interface)
5. [Automatic Archiving](#automatic-archiving)
6. [Manual Archiving](#manual-archiving)
7. [Monitoring and History](#monitoring-and-history)
8. [Customization](#customization)

## Basic Setup

### 1. Install and Activate
1. Upload the plugin to your WordPress site
2. Activate through the WordPress admin panel
3. Navigate to **Tools > Spun Web Archive Forge**

### 2. Configure API Settings
1. Go to **Tools > Spun Web Archive Forge > API Credentials**
2. Enter your Archive.org credentials:
   - **Username**: Your Archive.org username
   - **Password**: Your Archive.org password
3. Click **Save Settings**

### 3. Configure Submission Settings
1. Go to **Tools > Spun Web Archive Forge > Settings**
2. Configure automatic submission options:
   - **Auto-submit on publish**: Archive posts when published
   - **Auto-submit on update**: Archive posts when updated
   - **Post types**: Select which post types to archive
   - **Submission delay**: Set delay between submissions

## Archive Widgets

### Archive Links Widget
Display archive information in your sidebar or widget areas.

#### Adding the Widget
1. Go to **Appearance > Widgets**
2. Find "Archive Links" widget
3. Drag to desired widget area
4. Configure settings:
   - **Title**: Widget title (optional)
   - **Display Mode**: Choose from:
     - **Profile**: Show user's archive profile link
     - **Links**: Show archive links for current post
     - **Combined**: Show both profile and post links
   - **Show Count**: Display number of archived posts
   - **Custom CSS Class**: Add custom styling class

#### Widget Display Modes

**Profile Mode:**
- Shows link to user's Archive.org profile
- Displays total archived posts count
- Perfect for author pages or general archive promotion

**Links Mode:**
- Shows archive links for the current post/page
- Displays archive status and date
- Ideal for individual post pages

**Combined Mode:**
- Shows both profile and post-specific information
- Most comprehensive display option
- Great for general use across the site

## Archive Shortcodes

### [archive-link] Shortcode
Display archive link for specific posts.

#### Basic Usage
```
[archive-link]
```
Shows archive link for current post.

#### Advanced Usage
```
[archive-link post_id="123" text="View Archive" class="my-archive-link" target="_blank"]
```

#### Parameters
- `post_id`: Specific post ID (default: current post)
- `text`: Link text (default: "View Archive")
- `class`: CSS class for styling
- `target`: Link target (_blank, _self, etc.)

### [archive-status] Shortcode
Display archive status information.

#### Basic Usage
```
[archive-status]
```

#### Advanced Usage
```
[archive-status post_id="123" archived_text="✓ Archived" not_archived_text="Not archived" class="status-badge"]
```

#### Parameters
- `post_id`: Specific post ID (default: current post)
- `archived_text`: Text when post is archived
- `not_archived_text`: Text when post is not archived
- `class`: CSS class for styling

### [archive-list] Shortcode
Display list of archived posts.

#### Basic Usage
```
[archive-list]
```
Shows 5 most recent archived posts.

#### Advanced Usage
```
[archive-list type="popular" limit="10" show_date="true" show_status="true" class="archive-listing"]
```

#### Parameters
- `type`: List type (recent, popular)
- `limit`: Number of posts to show (default: 5)
- `show_date`: Show archive date (true/false)
- `show_status`: Show archive status (true/false)
- `class`: CSS class for styling

### [archive-count] Shortcode
Display total count of archived posts.

#### Basic Usage
```
[archive-count]
```

#### Advanced Usage
```
[archive-count text="Total archived: {count} posts" class="archive-counter"]
```

#### Parameters
- `text`: Custom text with {count} placeholder
- `class`: CSS class for styling

## Admin Interface

### Main Dashboard
Access via **Tools > Spun Web Archive Forge**

Features:
- Quick submission form
- Recent submissions overview
- Archive statistics
- System status

### API Credentials
Configure your Archive.org account settings.

### Settings
Configure automatic archiving behavior:
- Post types to archive
- Submission timing
- Queue management
- Display options

### Documentation
Built-in help and usage examples.

### Submissions History
View and manage all archive submissions:
- Filter by status, date, post type
- Retry failed submissions
- View archive URLs
- Export submission data

## Automatic Archiving

### Setup
1. Configure API credentials
2. Enable auto-submission in Settings
3. Select post types to archive
4. Set submission delay if needed

### How It Works
- Posts are queued for archiving when published/updated
- Background process submits to Archive.org
- Status is tracked and updated automatically
- Failed submissions can be retried

### Post Types
Configure which content types to archive:
- Posts
- Pages
- Custom post types
- Specific categories/tags

## Manual Archiving

### Individual Posts
1. Edit any post/page
2. Find "Archive Actions" meta box
3. Click "Submit to Archive"
4. Monitor status in Submissions History

### Bulk Operations
1. Go to Posts/Pages list
2. Select multiple items
3. Choose "Submit to Archive" bulk action
4. Confirm submission

### Quick Submit
Use the quick submit form on the main plugin page:
1. Enter post URL or ID
2. Click "Submit Now"
3. View immediate feedback

## Monitoring and History

### Submissions History
Track all archive activities:
- **Status**: Pending, Processing, Completed, Failed
- **Archive URL**: Direct link to archived version
- **Submission Date**: When submitted to Archive.org
- **Post Information**: Title, type, author

### Status Indicators
- 🟢 **Completed**: Successfully archived
- 🟡 **Processing**: Being processed by Archive.org
- 🔵 **Pending**: Queued for submission
- 🔴 **Failed**: Submission failed (can retry)

### Filtering and Search
- Filter by status, date range, post type
- Search by post title or URL
- Export data for external analysis

## Customization

### Styling Widgets and Shortcodes
Add custom CSS to your theme:

```css
/* Archive widget styling */
.swap-archive-widget {
    border: 1px solid #ddd;
    padding: 15px;
    border-radius: 5px;
}

/* Archive link styling */
.swap-archive-link {
    background: #0073aa;
    color: white;
    padding: 5px 10px;
    text-decoration: none;
    border-radius: 3px;
}

/* Archive status styling */
.swap-archive-status.archived {
    color: #46b450;
    font-weight: bold;
}

.swap-archive-status.not-archived {
    color: #dc3232;
}

/* Archive list styling */
.swap-archive-list {
    list-style: none;
    padding: 0;
}

.swap-archive-list li {
    padding: 5px 0;
    border-bottom: 1px solid #eee;
}
```

### Custom Templates
Override widget templates by copying files to your theme:
1. Create `/spun-web-archive-forge/` folder in your theme
2. Copy widget template files
3. Customize as needed

### Hooks and Filters
For developers, the plugin provides various hooks:

```php
// Modify archive submission data
add_filter('swap_submission_data', 'my_custom_submission_data');

// Custom archive URL processing
add_action('swap_archive_completed', 'my_archive_completion_handler');

// Modify widget output
add_filter('swap_widget_output', 'my_custom_widget_output');
```

## Troubleshooting

### Common Issues

**Archive submissions failing:**
- Check API credentials
- Verify internet connectivity
- Check Archive.org service status

**Widgets not displaying:**
- Ensure widget is properly configured
- Check if posts are actually archived
- Verify display mode settings

**Shortcodes not working:**
- Check shortcode syntax
- Ensure post has archive data
- Verify plugin is active

### Debug Mode
Enable WordPress debug mode to see detailed error messages:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### Support
- Check plugin documentation
- Review submissions history for error details
- Contact support with specific error messages

## Best Practices

1. **Regular Monitoring**: Check submissions history regularly
2. **Backup Settings**: Export plugin settings before major changes
3. **Test First**: Try manual submissions before enabling auto-archive
4. **Performance**: Use submission delays for high-traffic sites
5. **Styling**: Test widget/shortcode appearance across different themes
6. **Updates**: Keep plugin updated for latest features and fixes

This guide covers the main features of Spun Web Archive Forge. For technical details, see the Developer README.