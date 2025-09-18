# AutoPost Movies WordPress Plugin

A production-ready WordPress plugin that automatically posts upcoming popular movies and TV series using comprehensive API integrations with TMDB, Wikipedia, IMDb, and YouTube.

## Features

### 🎬 Data Sources & APIs
- **TMDB API Integration**: Fetch comprehensive metadata including title, release date, plot, poster images, and unique IDs
- **Wikipedia API**: Optional integration for detailed plot summaries (first paragraph)
- **IMDb API**: Optional integration for additional plot and information
- **YouTube API**: Automatic trailer discovery and embedding

### ⚙️ Admin Panel & Automation
- User-friendly admin panel under "Settings → AutoPost Movies"
- API key configuration for all supported services
- Flexible cron scheduling (hourly, daily, weekly)
- Toggle switches for data sources (Plot and Info)
- Content order selection (plot first vs info first)
- Manual add/edit/delete entries
- Comprehensive log viewer for API calls and errors

### 📝 Auto Post Creation
- Classic Editor format compatibility
- Smart content organization with plot and info sections
- Featured Image from URL (FIFU) integration
- Automatic category and tag assignment
- Duplicate prevention using TMDB unique IDs
- Retry logic with error handling
- Transient caching for improved performance

### 🔧 Custom Fields & Shortcodes
- Lightweight custom fields system (no ACF dependency)
- PHP 7.0+ compatibility
- Rich shortcode library for content enhancement
- Auto Post button in Classic Editor
- Configurable additional buttons

## Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.0 or higher
- **Memory**: 64MB minimum (128MB recommended)
- **Dependencies**: None (optional FIFU plugin for enhanced image handling)

## Installation

### Method 1: Manual Installation

1. Download the plugin files
2. Upload to your WordPress `/wp-content/plugins/` directory
3. Activate the plugin through the WordPress admin panel
4. Navigate to "Settings → AutoPost Movies" to configure

### Method 2: ZIP Installation

1. Create a ZIP file of the plugin directory
2. Go to WordPress Admin → Plugins → Add New
3. Click "Upload Plugin" and select your ZIP file
4. Activate the plugin after installation

## Setup Guide

### Step 1: API Configuration

#### TMDB API Key (Required)
1. Visit [TMDB API Settings](https://www.themoviedb.org/settings/api)
2. Create a free account if you don't have one
3. Request an API key
4. Copy the API key to "Settings → AutoPost Movies → TMDB API Key"
5. Click "Test" to verify the connection

#### YouTube API Key (Optional)
1. Go to [Google Cloud Console](https://console.developers.google.com/)
2. Create a new project or select existing one
3. Enable the YouTube Data API v3
4. Create credentials (API Key)
5. Copy the API key to "Settings → AutoPost Movies → YouTube API Key"
6. Click "Test" to verify the connection

### Step 2: Data Source Configuration

1. **Wikipedia Integration**: Enable to use Wikipedia summaries for plot content
2. **IMDb Integration**: Enable for additional movie information (experimental)
3. **Plot Source**: Choose between TMDB or Wikipedia for plot content
4. **Info Source**: Choose between TMDB or IMDb for movie information

### Step 3: Content Settings

1. **Content Order**: Select whether plot or info appears first in posts
2. **FIFU Integration**: Enable if you have the Featured Image from URL plugin
3. **Max Posts per Run**: Set limit to prevent server overload (recommended: 5)
4. **Custom Info Template**: Create custom information templates using placeholders

### Step 4: Automation Setup

1. **Cron Schedule**: Choose how often the plugin should run
   - Hourly: Every hour
   - Twice Daily: Every 12 hours
   - Daily: Once per day (recommended)
   - Weekly: Once per week

2. **Manual Execution**: Use "Run Now" button to test the automation

### Step 5: Optional Enhancements

#### Featured Image from URL (FIFU) Plugin
1. Install the FIFU plugin from WordPress repository
2. Enable FIFU integration in AutoPost Movies settings
3. Poster images will automatically be set as featured images

#### Additional Buttons
1. Configure custom buttons in the admin panel
2. Add unlimited clickable buttons to posts
3. Buttons appear automatically in generated content

## Usage

### Automatic Operation
Once configured, the plugin will:
1. Fetch popular movies and TV series from TMDB
2. Store them in the database with "pending" status
3. Process pending items during cron runs
4. Create WordPress posts with rich content
5. Set featured images and metadata
6. Log all activities for monitoring

### Manual Operation

#### Adding Single Items
1. Go to any post edit screen
2. Use the "Auto Post Movie/TV Series" meta box
3. Enter TMDB ID and select type (movie/TV)
4. Click "Auto Post" to create immediately

#### Bulk Management
1. Navigate to "Settings → AutoPost Movies → Manage Entries"
2. Add new entries by TMDB ID
3. View pending, posted, and error items
4. Monitor processing status

## Shortcodes

The plugin provides numerous shortcodes for content enhancement:

### Basic Shortcodes
- `[autopost_movies_wikipedia_info]` - Display Wikipedia information
- `[autopost_movies_custom_info]` - Display custom information template
- `[autopost_movies_auto_links]` - Auto-generated links (TMDB, IMDb, Google, YouTube)
- `[autopost_movies_trailer_button]` - Trailer button with optional URL
- `[autopost_movies_info_table]` - Formatted information table
- `[autopost_movies_rating]` - Rating badge display
- `[autopost_movies_poster]` - Movie/TV poster image

### Advanced Shortcodes
- `[autopost_movies_button text="Custom Text" url="https://example.com"]` - Custom buttons
- `[autopost_movies_trailer_button url="custom-url" text="Watch Now"]` - Custom trailer button
- `[autopost_movies_poster size="large" align="center"]` - Customized poster display

## Custom Fields

The plugin creates the following custom fields for each post:

- `autopost_movies_tmdb_id` - TMDB unique identifier
- `autopost_movies_imdb_id` - IMDb identifier
- `autopost_movies_type` - Movie or TV series
- `autopost_movies_trailer_url` - Trailer URL
- `autopost_movies_release_date` - Release/air date
- `autopost_movies_year` - Release year
- `autopost_movies_genres` - Comma-separated genres
- `autopost_movies_rating` - TMDB rating
- `autopost_movies_runtime` - Runtime in minutes (movies)
- `autopost_movies_episodes` - Number of episodes (TV)
- `autopost_movies_seasons` - Number of seasons (TV)
- `autopost_movies_poster_url` - Poster image URL

## ACF Compatibility

For users with Advanced Custom Fields, the plugin provides a JSON export of field configurations:

1. Go to "Settings → AutoPost Movies → Tools"
2. Copy the ACF field configuration
3. Import into ACF if desired

Note: ACF is not required - the plugin has its own lightweight field system.

## Troubleshooting

### Common Issues

#### "TMDB API key not configured" Error
- Ensure you have entered a valid TMDB API key
- Test the API connection using the "Test" button
- Check that your server can make external HTTP requests

#### "Cron not working" Issues
- Verify WP-Cron is not disabled in wp-config.php
- Check if your server supports scheduled tasks
- Use the "Run Now" button to test manual execution

#### "No posts being created" Problems
- Check the logs in "Settings → AutoPost Movies → Logs"
- Verify there are pending items in "Manage Entries"
- Ensure sufficient server memory and execution time

#### Featured Images Not Appearing
- Install and activate the FIFU plugin
- Enable FIFU integration in plugin settings
- Check that poster URLs are valid and accessible

### Performance Optimization

#### Server Resources
- Increase PHP memory limit if needed (128MB recommended)
- Set appropriate max execution time (60+ seconds)
- Use object caching if available

#### API Rate Limits
- TMDB allows 40 requests per 10 seconds
- Plugin implements caching to minimize API calls
- Adjust "Max Posts per Run" to manage load

#### Database Optimization
- Plugin creates indexed tables for performance
- Logs are automatically maintained
- Use "Clear Logs" periodically to free space

## Security

The plugin follows WordPress security best practices:

- All inputs are sanitized and validated
- Nonce verification for admin actions
- Capability checks for user permissions
- SQL queries use prepared statements
- Output is properly escaped

## Support

### Documentation
- Comprehensive inline help in admin panel
- Detailed error logging and reporting
- Export/import functionality for settings

### Debugging
- Enable WordPress debug mode for detailed error reporting
- Check plugin logs for API and processing issues
- Use browser developer tools for JavaScript issues

## License

This plugin is released under the GPL v2 or later license, making it free to use, modify, and distribute.

## Changelog

### Version 1.0.0
- Initial release
- TMDB API integration
- Wikipedia API integration
- YouTube API integration
- Custom fields system
- Comprehensive admin panel
- Shortcode library
- Cron automation
- FIFU compatibility
- PHP 7.0+ compatibility

## Credits

- TMDB for movie and TV data
- Wikipedia for plot summaries
- YouTube for trailer content
- WordPress community for best practices

---

**Note**: This plugin requires active internet connectivity for API integrations. Ensure your server can make outbound HTTP requests to the required services.