# AutoPost Movies Plugin - Setup Documentation

## Quick Start Guide

### Prerequisites
- WordPress 5.0+
- PHP 7.0+
- TMDB API key (free)
- Server with internet connectivity

### Installation Steps

1. **Download and Install**
   - Upload `autopost-movies-plugin.zip` to WordPress
   - Or extract files to `/wp-content/plugins/autopost-movies/`
   - Activate the plugin

2. **Configure TMDB API**
   - Get free API key from https://www.themoviedb.org/settings/api
   - Go to Settings → AutoPost Movies
   - Enter API key and test connection

3. **Set Schedule**
   - Choose automation frequency (daily recommended)
   - Click "Update Schedule"

4. **Optional Enhancements**
   - Install FIFU plugin for better image handling
   - Configure YouTube API for trailer discovery
   - Enable Wikipedia for enhanced plot content

### First Run Test

1. Go to Settings → AutoPost Movies → Automation
2. Click "Run Now" to test
3. Check Logs tab for results
4. Verify posts in WordPress admin

## API Keys Setup

### TMDB API Key (Required)
1. Create account at https://www.themoviedb.org/
2. Go to Settings → API
3. Request API key (instant approval)
4. Copy 32-character key to plugin settings

### YouTube API Key (Optional)
1. Go to https://console.developers.google.com/
2. Create project → Enable YouTube Data API v3
3. Create credentials → API Key
4. Restrict key to YouTube Data API v3
5. Copy key to plugin settings

## Configuration Options

### Data Sources
- **Plot Source**: TMDB (default) or Wikipedia
- **Info Source**: TMDB (default) or IMDb
- **Content Order**: Plot first or Info first

### Automation Settings
- **Schedule**: Hourly, Daily (recommended), or Weekly
- **Max Posts**: 5 per run (recommended)
- **Data Sources**: Enable/disable APIs as needed

### Content Customization
- **Custom Info Template**: Use placeholders like {title}, {year}, {genres}
- **Additional Buttons**: Add custom clickable buttons
- **FIFU Integration**: Auto-set featured images from URLs

## Post Format

Generated posts include:
- **Title**: Movie/TV series name
- **Plot Section**: From TMDB or Wikipedia
- **Information Table**: Genres, rating, runtime, etc.
- **Trailer Section**: YouTube embed and button
- **Custom Buttons**: Additional links and actions
- **Featured Image**: Poster via FIFU plugin

## Custom Fields Created

Each post gets these meta fields:
- `autopost_movies_tmdb_id`
- `autopost_movies_imdb_id`
- `autopost_movies_type` (movie/tv)
- `autopost_movies_trailer_url`
- `autopost_movies_year`
- `autopost_movies_genres`
- `autopost_movies_rating`
- `autopost_movies_poster_url`

## Available Shortcodes

Basic usage:
```
[autopost_movies_trailer_button]
[autopost_movies_wikipedia_info]
[autopost_movies_auto_links]
[autopost_movies_info_table]
[autopost_movies_rating]
[autopost_movies_poster]
```

Advanced usage:
```
[autopost_movies_button text="Watch Now" url="https://example.com"]
[autopost_movies_trailer_button text="View Trailer" url="custom-url"]
[autopost_movies_poster size="large" align="center"]
```

## Manual Usage

### Adding Single Items
1. Edit any post
2. Use "Auto Post Movie/TV Series" meta box
3. Enter TMDB ID and select type
4. Click "Auto Post"

### Bulk Management
1. Settings → AutoPost Movies → Manage Entries
2. Add entries by TMDB ID
3. Monitor pending/posted/error status

## Troubleshooting

### Common Issues

**No posts created:**
- Check TMDB API key is valid
- Verify cron is working ("Run Now" test)
- Check logs for error messages

**Images not showing:**
- Install FIFU plugin
- Enable FIFU in plugin settings
- Check poster URLs are accessible

**Cron not running:**
- Verify WP-Cron is enabled
- Check server supports scheduled tasks
- Use manual "Run Now" for testing

### Performance Tips

- Set reasonable "Max Posts per Run" (5 recommended)
- Use daily schedule instead of hourly
- Enable caching plugins if available
- Monitor server resources

### Security Notes

- API keys are stored securely in WordPress options
- All user inputs are sanitized
- Admin-only access to configuration
- Regular security updates recommended

## Best Practices

1. **Start Small**: Begin with daily schedule and 5 posts max
2. **Monitor Logs**: Check regularly for API issues
3. **Test Thoroughly**: Use manual runs before automation
4. **Backup Regularly**: Include database and plugin settings
5. **Update APIs**: Keep API keys current and valid

## Advanced Configuration

### Custom Info Template Example
```
Released in {year}
Genres: {genres}
TMDB Rating: {rating}/10
TMDB ID: {tmdb_id}
```

### Additional Buttons Example
- Text: "Watch on Netflix"
- URL: "https://netflix.com/search?q={title}"

### ACF Integration
Export field configuration from Tools tab for ACF compatibility.

## Support Resources

- Plugin logs: Settings → AutoPost Movies → Logs
- WordPress debug: Enable WP_DEBUG in wp-config.php
- Server logs: Check PHP error logs
- API status: Use test buttons in admin panel

## Uninstallation

To remove completely:
1. Deactivate plugin
2. Delete plugin files
3. Remove database tables (if desired):
   - `wp_autopost_movies`
   - `wp_autopost_movies_logs`
4. Clean up options (if desired):
   - All options starting with `autopost_movies_`

---

For technical support or feature requests, please check the plugin documentation or contact the development team.