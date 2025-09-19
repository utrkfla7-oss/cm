=== Movie TV Classic Manager ===
Contributors: movietvmanager
Tags: movies, tv shows, classic editor, tmdb, fifu, shortcodes, meta boxes
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Manual movie/TV show management with Classic Editor integration, TMDB API support, and FIFU compatibility.

== Description ==

Movie TV Classic Manager is a comprehensive WordPress plugin designed for manual movie and TV show content management. It provides seamless integration with the Classic Editor, TMDB API for automatic data fetching, and full compatibility with the Featured Image from URL (FIFU) plugin.

**Key Features:**

* **Classic Editor Integration**: TinyMCE button for easy shortcode insertion
* **Manual Content Management**: Complete control over movie and TV show data entry
* **TMDB API Integration**: Automatic fetching of movie and TV show details
* **FIFU Compatibility**: Seamless integration with Featured Image from URL plugin
* **Custom Shortcodes**: Multiple display formats for frontend content
* **Meta Boxes**: Comprehensive data entry forms for movies and TV shows
* **ACF Ready**: Export/import ready field groups for Advanced Custom Fields
* **Responsive Design**: Mobile-friendly frontend displays

**Post Types:**
* Movies (mtcm_movie)
* TV Shows (mtcm_tv_show)

**Taxonomies:**
* Genres (mtcm_genre)
* Release Years (mtcm_year)

**Shortcodes:**
* `[mtcm_movie id="123"]` - Display single movie
* `[mtcm_tv_show id="456"]` - Display single TV show
* `[mtcm_movie_list limit="5"]` - Display list of movies
* `[mtcm_tv_list limit="5"]` - Display list of TV shows

**TinyMCE Integration:**
The plugin adds a Movie/TV button to the Classic Editor toolbar, allowing you to:
* Insert movie shortcodes with various display options
* Insert TV show shortcodes with customizable parameters
* Search and import content directly from TMDB
* Create movie and TV show lists with filtering options

**TMDB Integration:**
* Search movies and TV shows by title
* Automatically fetch detailed information including cast, crew, ratings, and more
* Import poster and backdrop images
* Cache API responses for improved performance
* Test API connection from settings page

**Display Options:**
* Full display with all details and poster
* Compact display for lists and summaries
* Poster-only display for galleries
* Customizable columns for grid layouts

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/movie-tv-classic-manager` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Navigate to Settings > Movie TV Manager to configure the plugin.
4. (Optional) Enter your TMDB API key to enable automatic data fetching.
5. (Optional) Install and activate the Featured Image from URL (FIFU) plugin for enhanced poster management.

== Frequently Asked Questions ==

= Do I need a TMDB API key? =

The TMDB API key is optional but highly recommended. Without it, you'll need to manually enter all movie and TV show data. With an API key, you can automatically fetch comprehensive information from The Movie Database.

You can get a free API key from https://www.themoviedb.org/settings/api

= Is the plugin compatible with the Gutenberg editor? =

This plugin is specifically designed for the Classic Editor. It does not provide Gutenberg blocks, but you can still use the shortcodes manually in Gutenberg's Classic block or HTML blocks.

= Can I use this plugin without the FIFU plugin? =

Yes, the plugin works independently. However, installing the Featured Image from URL (FIFU) plugin will provide enhanced poster image management, allowing you to set featured images directly from URLs.

= How do I add custom CSS styling? =

The plugin includes default CSS classes that you can override in your theme. Key classes include:
* `.mtcm-movie` - Movie container
* `.mtcm-tv-show` - TV show container
* `.mtcm-movie-full` - Full movie display
* `.mtcm-movie-compact` - Compact movie display
* `.mtcm-poster-image` - Poster images

= Can I import/export field configurations? =

Yes, the plugin includes ACF field group JSON files in the `acf-fields` directory. You can import these into Advanced Custom Fields if you prefer using ACF for field management.

== Screenshots ==

1. Classic Editor TinyMCE button for inserting movie/TV shortcodes
2. Movie details meta box with comprehensive data entry fields
3. TMDB integration panel with search and import functionality
4. Plugin settings page with API configuration options
5. Frontend movie display in full format
6. Frontend TV show display in compact format
7. Movie list display with multiple columns

== Changelog ==

= 1.0.0 =
* Initial release
* Classic Editor TinyMCE button integration
* Movie and TV show custom post types
* Comprehensive meta boxes for data entry
* TMDB API integration with search and import
* FIFU plugin compatibility
* Frontend shortcodes with multiple display options
* ACF field group exports included
* Responsive CSS styling
* Admin settings page with API configuration

== Upgrade Notice ==

= 1.0.0 =
Initial release of Movie TV Classic Manager.

== Credits ==

* The Movie Database (TMDB) for providing the comprehensive movie and TV show API
* Featured Image from URL (FIFU) plugin for seamless image URL integration
* Advanced Custom Fields (ACF) for inspiring the field group structure

== Support ==

For support and feature requests, please visit the plugin's GitHub repository or contact the development team.

== Requirements ==

* WordPress 5.0 or higher
* PHP 7.0 or higher
* Classic Editor plugin (for TinyMCE integration)
* Optional: TMDB API key for automatic data fetching
* Optional: Featured Image from URL (FIFU) plugin for enhanced poster management
* Optional: Advanced Custom Fields (ACF) plugin for enhanced field management

== Technical Details ==

**Database Tables:**
The plugin uses WordPress custom post types and meta fields. No additional database tables are created.

**API Endpoints:**
* TMDB API v3 for movie and TV show data
* WordPress REST API for internal communication

**Caching:**
* TMDB API responses are cached using WordPress transients
* Cache duration: 1 hour for search results, 24 hours for detailed data
* Cache can be disabled in plugin settings

**Security:**
* All user inputs are sanitized and validated
* AJAX requests use WordPress nonces for security
* API keys are stored securely in WordPress options

**Performance:**
* Minimal database queries
* Efficient caching system
* Lazy loading for images
* Optimized CSS and JavaScript

== Development ==

This plugin follows WordPress coding standards and best practices:
* PSR-4 autoloading structure
* Proper sanitization and validation
* Internationalization ready
* Hook-based architecture
* Modular file organization