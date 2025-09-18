# AutoPost Movies Plugin - Installation Guide

## Quick Start

### 1. Installation
1. Download the `autopost-movies-plugin.zip` file
2. In WordPress Admin, go to **Plugins → Add New → Upload Plugin**
3. Choose the ZIP file and click **Install Now**
4. Click **Activate Plugin**

### 2. Required Setup
1. Get a TMDB API key from [https://www.themoviedb.org/settings/api](https://www.themoviedb.org/settings/api)
2. Go to **Settings → AutoPost Movies**
3. Enter your TMDB API key
4. Configure your automation schedule (Daily recommended)
5. Click **Save Changes**

### 3. Run First Sync
1. In the admin panel, click **Run Manual Sync**
2. Check the logs to ensure everything is working
3. Your first posts should be created!

## Detailed Configuration

### Dependencies (Optional but Recommended)

#### Advanced Custom Fields (ACF)
1. Install ACF plugin from WordPress repository
2. Go to **Custom Fields → Tools**
3. Import the file `autopost-movies/acf-export-autopost-movies.json`
4. This adds custom fields for movie/TV data

#### Featured Image from URL (FIFU)
1. Install FIFU plugin from WordPress repository
2. Enable it in **Settings → AutoPost Movies**
3. This allows automatic featured image setting from URLs

### API Configuration

#### TMDB API (Required)
- **Purpose**: Primary data source for movies and TV series
- **Cost**: Free
- **Setup**: 
  1. Create account at themoviedb.org
  2. Go to Settings → API
  3. Request API key
  4. Enter in plugin settings

#### YouTube API (Optional)
- **Purpose**: Trailer embedding
- **Cost**: Free (with limits)
- **Setup**:
  1. Visit Google Developers Console
  2. Create project and enable YouTube Data API v3
  3. Create API key
  4. Enter in plugin settings

#### IMDb API via OMDb (Optional)
- **Purpose**: Additional movie information
- **Cost**: Free tier available
- **Setup**:
  1. Visit omdbapi.com
  2. Request free API key
  3. Enter in plugin settings

### Content Configuration

#### Data Sources
Enable/disable content sources:
- **TMDB Plot**: Movie/TV plot from TMDB
- **Wikipedia Info**: First paragraph from Wikipedia
- **IMDb Info**: Additional plot and details from IMDb

#### Content Order
Choose display order:
- **Plot First**: Show plot before information
- **Info First**: Show information before plot

#### Post Settings
- **Status**: Publish immediately or save as drafts
- **Category**: Set default category for new posts
- **Posts Per Run**: How many posts to create each time (1-20)

### Automation Settings

#### Cron Schedule
- **Hourly**: Creates posts every hour
- **Daily**: Creates posts once daily (recommended)
- **Weekly**: Creates posts once weekly

#### WordPress Cron
Ensure WordPress cron is working:
```php
# Add to wp-config.php if using external cron
define('DISABLE_WP_CRON', true);
```

Then set up external cron job:
```bash
# Run every hour
0 * * * * wget -q -O - https://yoursite.com/wp-cron.php?doing_wp_cron >/dev/null 2>&1
```

### Testing

#### Manual Sync
Use the **Run Manual Sync** button to test:
1. Check if posts are created
2. Review log entries
3. Verify featured images (if FIFU enabled)
4. Check custom fields (if ACF enabled)

#### API Test
Test API connections:
1. Save settings with API keys
2. Check logs for connection errors
3. Verify TMDB data is fetched correctly

### Troubleshooting

#### No Posts Created
1. **Check TMDB API key**: Must be valid and active
2. **Verify cron**: WordPress cron must be running
3. **Check logs**: Look for error messages
4. **Test manually**: Use manual sync button

#### Duplicate Posts
- Plugin prevents duplicates using TMDB IDs
- If duplicates appear, check for manual post creation

#### API Errors
1. **Rate limits**: TMDB allows 40 requests per 10 seconds
2. **Invalid keys**: Verify all API keys are correct
3. **Network issues**: Check server connectivity

#### Missing Images
1. **FIFU not installed**: Featured images require FIFU plugin
2. **URL access**: Server must access image URLs
3. **Permissions**: WordPress needs media upload permissions

### Advanced Configuration

#### Custom Hooks
Add custom functionality:
```php
// Modify post content
add_filter('apm_post_content', function($content, $data, $type) {
    // Your custom content modifications
    return $content;
}, 10, 3);

// Before post creation
add_action('apm_before_post_creation', function($data, $type) {
    // Your custom actions
}, 10, 2);
```

#### Custom Cron Schedules
Add custom intervals:
```php
add_filter('cron_schedules', function($schedules) {
    $schedules['every_6_hours'] = array(
        'interval' => 21600,
        'display' => 'Every 6 Hours'
    );
    return $schedules;
});
```

### Maintenance

#### Log Management
- Logs are automatically cleaned (keeps last 1000 entries)
- Use **Clear Logs** button to manually clear
- Monitor for recurring errors

#### Performance
- Plugin uses transient caching (1-24 hours)
- API requests are rate-limited and cached
- Database queries are optimized

#### Updates
- Check for plugin updates regularly
- Backup site before major updates
- Test on staging site first

### Support

#### Common Issues
- **504 Gateway Timeout**: Reduce posts per run
- **Memory exhaustion**: Increase PHP memory limit
- **Cron not running**: Check WordPress cron status

#### Debug Mode
Enable for detailed errors:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

#### Log Files
- Plugin logs: Admin panel → View Logs
- WordPress logs: `/wp-content/debug.log`
- Server logs: Check with hosting provider

### Security

#### API Keys
- Keep API keys secure and private
- Use environment variables for sensitive data
- Regenerate keys if compromised

#### Permissions
- Plugin requires `manage_options` capability
- Files should not be world-writable
- Use HTTPS for API communications

### Performance Optimization

#### Server Requirements
- **PHP**: 7.0+ (7.4+ recommended)
- **Memory**: 256MB+ PHP memory limit
- **Timeout**: 60+ seconds execution time
- **Connectivity**: Outbound HTTPS access

#### Optimization Tips
- Use external cron instead of WP-Cron
- Enable object caching (Redis/Memcached)
- Optimize database regularly
- Monitor API rate limits

This completes the installation and configuration guide for the AutoPost Movies plugin.