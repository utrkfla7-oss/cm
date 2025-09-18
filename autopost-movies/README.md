# AutoPost Movies WordPress Plugin

## Overview

AutoPost Movies is a production-ready WordPress plugin that automates the posting of upcoming popular movies and TV series. It integrates with multiple APIs to gather comprehensive information and creates well-formatted posts automatically.

## Features

### Data Sources & APIs
- **TMDB API**: Primary source for movie/TV metadata, posters, release dates, plots, and trailers
- **Wikipedia API**: Optional integration for additional plot information (first paragraph)
- **IMDb API**: Optional integration via OMDb API for additional movie information
- **YouTube API**: Optional integration for trailer embedding

### Admin Panel & Automation
- User-friendly admin panel under **Settings → AutoPost Movies**
- API key configuration for all supported services
- Cron schedule controls (hourly, daily, weekly)
- Content source toggles (TMDB plot, Wikipedia info, IMDb data)
- Content order configuration (plot first vs info first)
- Manual post creation and management
- Comprehensive logging system with viewer

### Auto Post Creation
- Classic Editor compatible post creation
- Featured image integration (FIFU plugin compatibility)
- Custom fields via ACF (Advanced Custom Fields)
- Shortcodes for flexible content display
- Duplicate prevention via TMDB ID tracking

### Error Handling & Performance
- Retry logic with exponential backoff
- Admin email notifications for errors
- Transient caching for API responses
- Comprehensive logging system

## Installation

### Prerequisites
1. WordPress 5.0 or higher
2. PHP 7.0 or higher
3. TMDB API key (required)
4. Optional: Advanced Custom Fields (ACF) plugin
5. Optional: Featured Image from URL (FIFU) plugin

### Installation Steps

1. **Upload Plugin**
   ```bash
   # Extract the ZIP file to your WordPress plugins directory
   wp-content/plugins/autopost-movies/
   ```

2. **Activate Plugin**
   - Go to WordPress Admin → Plugins
   - Find "AutoPost Movies" and click "Activate"

3. **Configure Settings**
   - Navigate to Settings → AutoPost Movies
   - Enter your TMDB API key (required)
   - Configure optional API keys (YouTube, IMDb)
   - Set your preferred automation schedule
   - Configure content sources and order

4. **Import ACF Fields** (Optional but recommended)
   - Install Advanced Custom Fields plugin
   - Go to Custom Fields → Tools
   - Import the provided `acf-export-autopost-movies.json` file

## Configuration

### API Keys

#### TMDB API Key (Required)
1. Visit [TMDB API Settings](https://www.themoviedb.org/settings/api)
2. Create an account if needed
3. Request an API key
4. Enter the key in Settings → AutoPost Movies

#### YouTube API Key (Optional)
1. Visit [Google Developers Console](https://console.developers.google.com/)
2. Create a new project or select existing
3. Enable YouTube Data API v3
4. Create credentials (API key)
5. Enter the key in plugin settings

#### IMDb API Key (Optional)
1. Visit [OMDb API](http://www.omdbapi.com/apikey.aspx)
2. Request a free API key
3. Enter the key in plugin settings

### Automation Settings

#### Cron Schedule
- **Hourly**: Runs every hour
- **Daily**: Runs once per day
- **Weekly**: Runs once per week

#### Posts Per Run
Configure how many posts to create in each automated run (1-20).

#### Content Sources
Toggle which data sources to include:
- TMDB Plot
- Wikipedia First Paragraph
- IMDb Plot and Info

#### Post Settings
- Post status (Published or Draft)
- Default category
- Featured image from URL (requires FIFU plugin)

## Usage

### Automated Operation
Once configured, the plugin runs automatically based on your cron schedule:
1. Fetches upcoming movies from TMDB
2. Fetches popular TV series from TMDB
3. Checks for duplicates using TMDB ID
4. Gathers additional information from enabled sources
5. Creates formatted posts with custom fields
6. Sets featured images (if FIFU is installed)
7. Logs all activities

### Manual Operation
Use the admin panel to:
- Run manual sync anytime
- View activity logs
- Monitor statistics
- Clear logs when needed

### Shortcodes

The plugin provides several shortcodes for flexible content display:

#### Wikipedia Info
```
[apm_wikipedia_info title="Movie Title"]
```

#### Custom Info Section
```
[apm_custom_info]Your custom content here[/apm_custom_info]
```

#### Trailer Button
```
[apm_trailer_button url="https://youtube.com/watch?v=..." text="Watch Trailer"]
```

#### Clickable Link
```
[apm_clickable_link url="https://example.com"]Link text[/apm_clickable_link]
```

## Custom Fields

When ACF is installed and the field group is imported, the following custom fields are available:

### General Fields
- `apm_tmdb_id`: TMDB unique identifier
- `apm_imdb_id`: IMDb identifier
- `apm_media_type`: Movie or TV series
- `apm_year`: Release year
- `apm_tmdb_rating`: TMDB rating (0-10)
- `apm_genres`: Comma-separated genres

### Movie-Specific Fields
- `apm_release_date`: Movie release date
- `apm_runtime`: Runtime in minutes

### TV-Specific Fields
- `apm_first_air_date`: First air date
- `apm_seasons`: Number of seasons
- `apm_episodes`: Total episodes

### Media Fields
- `apm_poster_url`: Poster image URL
- `apm_backdrop_url`: Backdrop image URL
- `apm_trailer_url`: YouTube trailer URL

## Troubleshooting

### Common Issues

#### No Posts Being Created
1. Check TMDB API key is valid
2. Verify cron jobs are running (`wp-cron.php`)
3. Check logs for error messages
4. Ensure WordPress has write permissions

#### API Connection Errors
1. Verify API keys are correct
2. Check server can make outbound HTTP requests
3. Review error logs for specific issues
4. Test API connections in plugin settings

#### Duplicate Posts
The plugin prevents duplicates using TMDB IDs. If duplicates appear:
1. Check if posts were created manually
2. Verify database integrity
3. Review logs for errors during tracking

#### Missing Featured Images
1. Ensure FIFU plugin is installed and active
2. Check image URLs are accessible
3. Verify WordPress media upload permissions

### Debug Mode
Enable WordPress debug mode to see detailed error messages:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### Log Files
Plugin logs are stored in the database and viewable through the admin panel. For additional debugging, check WordPress error logs.

## Extending the Plugin

### Hooks and Filters

The plugin provides WordPress-standard hooks for extensibility:

#### Actions
- `apm_before_post_creation`: Before creating a post
- `apm_after_post_creation`: After creating a post
- `apm_before_api_request`: Before making API requests
- `apm_after_api_request`: After API requests complete

#### Filters
- `apm_post_content`: Modify post content before saving
- `apm_post_title`: Modify post title before saving
- `apm_api_request_args`: Modify API request arguments
- `apm_cron_schedules`: Add custom cron schedules

### Example Customizations

#### Custom Post Content
```php
add_filter('apm_post_content', function($content, $data, $type) {
    // Add custom content sections
    $content .= "\n\n<h3>Custom Section</h3>";
    $content .= "<p>Additional information...</p>";
    return $content;
}, 10, 3);
```

#### Custom Cron Schedule
```php
add_filter('apm_cron_schedules', function($schedules) {
    $schedules['every_6_hours'] = array(
        'interval' => 21600,
        'display' => 'Every 6 Hours'
    );
    return $schedules;
});
```

## Support

### Documentation
- Plugin documentation: Available in the plugin directory
- ACF documentation: [advancedcustomfields.com](https://www.advancedcustomfields.com/)
- TMDB API docs: [developers.themoviedb.org](https://developers.themoviedb.org/)

### Community
- WordPress.org support forums
- Plugin GitHub repository (if available)

### Professional Support
For custom development or professional support, contact the plugin developers.

## License

This plugin is licensed under the GPL v2 or later.

## Changelog

### Version 1.0.0
- Initial release
- TMDB API integration
- Wikipedia API integration
- IMDb API integration (via OMDb)
- YouTube API integration
- Automated post creation
- Admin panel with full configuration
- Comprehensive logging system
- ACF integration
- FIFU integration
- Shortcode system
- Duplicate prevention
- Error handling and retry logic