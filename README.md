# CMPlayer AutoPost Movies - TMDB API Integration

## Overview

This module provides automatic movie posting functionality using The Movie Database (TMDB) API. It includes robust error handling, caching mechanisms, and retry logic to ensure reliable operation.

## Features

### ✅ Key Fixes Implemented

1. **TMDB API Integration:**
   - Full TMDB API integration with proper authentication
   - API key validation with detailed error reporting
   - Support for popular movies and movie details endpoints
   - Proper API endpoint configuration

2. **Error Handling:**
   - Comprehensive error logging with different log levels (INFO, WARNING, ERROR)
   - Retry logic for API calls with exponential backoff
   - Rate limit detection and handling
   - Admin email notifications for repeated failures
   - Detailed error messages with HTTP status codes

3. **Testing:**
   - Built-in API key testing functionality
   - Admin interface for testing TMDB API connection
   - Validation for both valid and invalid API keys
   - Test script for independent validation

### 🚀 Additional Improvements

1. **Enhanced Caching:**
   - WordPress transients for API response caching
   - Configurable cache expiry (default: 1 hour)
   - Cache clearing functionality
   - Efficient cache key generation

2. **Compatibility:**
   - PHP 7.0+ compatible code
   - WordPress 5+ hooks and functions
   - Proper WordPress coding standards
   - Security best practices (nonces, sanitization)

## Installation & Configuration

### 1. Enable the Plugin
The AutoPost Movies functionality is integrated into the CMPlayer plugin. Make sure the plugin is activated in WordPress.

### 2. Get TMDB API Key
1. Visit [TMDB API Settings](https://www.themoviedb.org/settings/api)
2. Create a free account if needed
3. Generate your API key

### 3. Configure Settings
1. Go to **CMPlayer > Settings** in WordPress admin
2. Scroll down to "AutoPost Movies (TMDB API)" section
3. Enter your TMDB API key
4. Click "Test API Key" to validate
5. Configure other settings:
   - Enable AutoPost: Check to enable automatic posting
   - Max Posts per Run: Number of movies to post per hour (1-20)
   - Post Status: Draft, Published, or Pending Review
   - Default Category: Select category for auto-posted movies
   - Enable Logging: Detailed logging for debugging

## Usage

### Automatic Posting
Once configured and enabled, the system will:
- Run hourly via WordPress cron
- Fetch popular movies from TMDB
- Check if movies already exist (prevents duplicates)
- Create new posts with movie information
- Set featured images from TMDB posters
- Add metadata (rating, release date, genres, etc.)

### Manual Operations
From the admin panel, you can:
- **Test API Key**: Validate your TMDB API configuration
- **Run AutoPost Now**: Manually trigger the posting process
- **Clear TMDB Cache**: Clear cached API responses

## API Endpoints

The plugin provides REST API endpoints:

- `GET /wp-json/cmplayer/v1/movies/{id}` - Get movie details
- `GET /wp-json/cmplayer/v1/movies/search?q=query` - Search movies (coming soon)

## Error Handling & Logging

### Log Levels
- **INFO**: Normal operations (API success, posts created)
- **WARNING**: Recoverable issues (rate limits, retries)
- **ERROR**: Critical failures (invalid API key, max retries exceeded)

### Admin Notifications
- Email notifications sent to admin when API fails repeatedly
- Notifications limited to once per hour to prevent spam
- Clear error messages in admin interface

### Retry Logic
- Maximum 3 retry attempts for failed requests
- 2-second delay between retries
- Special handling for rate limits (longer delay)
- Exponential backoff for server errors

## Caching System

### Cache Keys
- Unique cache keys based on URL and parameters
- Pattern: `cmplayer_tmdb_{md5_hash}`
- Automatic cleanup of expired cache

### Cache Management
- 1-hour default expiry
- Admin option to clear all TMDB cache
- Successful responses cached automatically
- Validation requests bypass cache

## Security Features

- API key sanitization and validation
- WordPress nonces for admin actions
- Capability checks for admin functions
- Secure data storage in WordPress options
- Input sanitization for all user data

## Troubleshooting

### Common Issues

1. **API Key Invalid**
   - Verify key at TMDB settings page
   - Check for extra spaces or characters
   - Use "Test API Key" button

2. **No Movies Posted**
   - Check if AutoPost is enabled
   - Verify API key is valid
   - Check WordPress cron is working
   - Review error logs

3. **Rate Limiting**
   - TMDB has rate limits (40 requests per 10 seconds)
   - Plugin handles this automatically with delays
   - Consider reducing max posts per run

4. **Cache Issues**
   - Use "Clear TMDB Cache" if needed
   - Cache cleared automatically on plugin updates

### Log File Location
Logs are written to WordPress error log. Check:
- `/wp-content/debug.log` (if WP_DEBUG_LOG enabled)
- Server error logs
- Look for entries starting with `[CMPlayer AutoPost]`

## Compatibility

### PHP Requirements
- PHP 7.0 or higher
- cURL extension (for API requests)
- JSON extension
- WordPress 5.0+

### WordPress Features Used
- REST API
- Cron system
- Transients (caching)
- Custom post meta
- Admin hooks
- AJAX handlers

## Future Enhancements

Planned features for future versions:
- Movie search functionality
- Genre-based filtering
- Custom post types for movies
- Advanced scheduling options
- Integration with video players
- Bulk import tools

## Support

For issues or questions:
1. Check error logs first
2. Test API key functionality
3. Verify WordPress cron is working
4. Review configuration settings

The plugin includes comprehensive error handling and logging to help diagnose any issues quickly.