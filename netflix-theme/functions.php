<?php
/**
 * Netflix Streaming Platform Theme Functions
 * 
 * @package Netflix_Theme
 * @version 2.0.0
 */

if (!defined('ABSPATH')) exit;

// Theme constants
define('NETFLIX_THEME_VERSION', '2.0.0');
define('NETFLIX_THEME_URI', get_template_directory_uri());
define('NETFLIX_THEME_PATH', get_template_directory());

/**
 * Theme Setup
 */
function netflix_theme_setup() {
    // Theme support
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script'
    ));
    add_theme_support('custom-logo');
    add_theme_support('custom-background');
    add_theme_support('post-formats', array(
        'aside',
        'image',
        'video',
        'quote',
        'link',
        'gallery',
        'audio'
    ));

    // Navigation menus
    register_nav_menus(array(
        'primary' => esc_html__('Primary Navigation', 'netflix-theme'),
        'footer' => esc_html__('Footer Navigation', 'netflix-theme'),
        'user' => esc_html__('User Account Menu', 'netflix-theme'),
    ));

    // Image sizes for Netflix-style thumbnails
    add_image_size('netflix-hero', 1920, 1080, true);
    add_image_size('netflix-poster', 300, 450, true);
    add_image_size('netflix-landscape', 400, 225, true);
    add_image_size('netflix-thumb', 250, 140, true);
}
add_action('after_setup_theme', 'netflix_theme_setup');

/**
 * Enqueue styles and scripts
 */
function netflix_theme_scripts() {
    // Main theme stylesheet
    wp_enqueue_style('netflix-theme-style', get_stylesheet_uri(), array(), NETFLIX_THEME_VERSION);
    
    // Additional CSS files
    wp_enqueue_style('netflix-video-player', NETFLIX_THEME_URI . '/assets/css/video-player.css', array(), NETFLIX_THEME_VERSION);
    wp_enqueue_style('netflix-components', NETFLIX_THEME_URI . '/assets/css/components.css', array(), NETFLIX_THEME_VERSION);
    
    // JavaScript files
    wp_enqueue_script('netflix-theme-js', NETFLIX_THEME_URI . '/assets/js/main.js', array('jquery'), NETFLIX_THEME_VERSION, true);
    wp_enqueue_script('netflix-video-player', NETFLIX_THEME_URI . '/assets/js/video-player.js', array('jquery'), NETFLIX_THEME_VERSION, true);
    wp_enqueue_script('netflix-api', NETFLIX_THEME_URI . '/assets/js/api.js', array('jquery'), NETFLIX_THEME_VERSION, true);
    
    // HLS.js for video streaming
    wp_enqueue_script('hls-js', 'https://cdn.jsdelivr.net/npm/hls.js@latest', array(), null, true);
    
    // Video.js for advanced video controls
    wp_enqueue_style('videojs-css', 'https://vjs.zencdn.net/8.6.1/video-js.css');
    wp_enqueue_script('videojs', 'https://vjs.zencdn.net/8.6.1/video.min.js', array(), null, true);
    
    // Font Awesome for icons
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css');
    
    // Google Fonts
    wp_enqueue_style('google-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

    // Localize script for AJAX
    wp_localize_script('netflix-theme-js', 'netflix_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('netflix_nonce'),
        'backend_url' => get_option('netflix_backend_url', 'http://localhost:3001'),
        'user_id' => get_current_user_id(),
        'is_user_logged_in' => is_user_logged_in()
    ));
}
add_action('wp_enqueue_scripts', 'netflix_theme_scripts');

/**
 * Custom Post Types
 */
function netflix_create_post_types() {
    // Movies
    register_post_type('movie', array(
        'labels' => array(
            'name' => esc_html__('Movies', 'netflix-theme'),
            'singular_name' => esc_html__('Movie', 'netflix-theme'),
            'add_new' => esc_html__('Add New Movie', 'netflix-theme'),
            'add_new_item' => esc_html__('Add New Movie', 'netflix-theme'),
            'edit_item' => esc_html__('Edit Movie', 'netflix-theme'),
        ),
        'public' => true,
        'has_archive' => true,
        'supports' => array('title', 'editor', 'thumbnail', 'custom-fields'),
        'menu_icon' => 'dashicons-video-alt3',
        'rewrite' => array('slug' => 'movies'),
        'show_in_rest' => true,
    ));

    // TV Shows
    register_post_type('tv_show', array(
        'labels' => array(
            'name' => esc_html__('TV Shows', 'netflix-theme'),
            'singular_name' => esc_html__('TV Show', 'netflix-theme'),
            'add_new' => esc_html__('Add New Show', 'netflix-theme'),
            'add_new_item' => esc_html__('Add New TV Show', 'netflix-theme'),
            'edit_item' => esc_html__('Edit TV Show', 'netflix-theme'),
        ),
        'public' => true,
        'has_archive' => true,
        'supports' => array('title', 'editor', 'thumbnail', 'custom-fields'),
        'menu_icon' => 'dashicons-playlist-video',
        'rewrite' => array('slug' => 'tv-shows'),
        'show_in_rest' => true,
    ));

    // Episodes
    register_post_type('episode', array(
        'labels' => array(
            'name' => esc_html__('Episodes', 'netflix-theme'),
            'singular_name' => esc_html__('Episode', 'netflix-theme'),
            'add_new' => esc_html__('Add New Episode', 'netflix-theme'),
            'add_new_item' => esc_html__('Add New Episode', 'netflix-theme'),
            'edit_item' => esc_html__('Edit Episode', 'netflix-theme'),
        ),
        'public' => true,
        'has_archive' => false,
        'supports' => array('title', 'editor', 'thumbnail', 'custom-fields'),
        'menu_icon' => 'dashicons-video-alt2',
        'rewrite' => array('slug' => 'episodes'),
        'show_in_rest' => true,
    ));
}
add_action('init', 'netflix_create_post_types');

/**
 * Custom Taxonomies
 */
function netflix_create_taxonomies() {
    // Genres
    register_taxonomy('genre', array('movie', 'tv_show'), array(
        'labels' => array(
            'name' => esc_html__('Genres', 'netflix-theme'),
            'singular_name' => esc_html__('Genre', 'netflix-theme'),
        ),
        'hierarchical' => true,
        'public' => true,
        'show_in_rest' => true,
        'rewrite' => array('slug' => 'genre'),
    ));

    // Release Year
    register_taxonomy('release_year', array('movie', 'tv_show'), array(
        'labels' => array(
            'name' => esc_html__('Release Years', 'netflix-theme'),
            'singular_name' => esc_html__('Release Year', 'netflix-theme'),
        ),
        'hierarchical' => false,
        'public' => true,
        'show_in_rest' => true,
        'rewrite' => array('slug' => 'year'),
    ));

    // Content Rating
    register_taxonomy('content_rating', array('movie', 'tv_show'), array(
        'labels' => array(
            'name' => esc_html__('Content Ratings', 'netflix-theme'),
            'singular_name' => esc_html__('Content Rating', 'netflix-theme'),
        ),
        'hierarchical' => false,
        'public' => true,
        'show_in_rest' => true,
        'rewrite' => array('slug' => 'rating'),
    ));
}
add_action('init', 'netflix_create_taxonomies');

/**
 * Custom Meta Boxes
 */
function netflix_add_meta_boxes() {
    add_meta_box(
        'netflix_movie_details',
        esc_html__('Movie Details', 'netflix-theme'),
        'netflix_movie_meta_callback',
        'movie',
        'normal',
        'high'
    );

    add_meta_box(
        'netflix_tv_show_details',
        esc_html__('TV Show Details', 'netflix-theme'),
        'netflix_tv_show_meta_callback',
        'tv_show',
        'normal',
        'high'
    );

    add_meta_box(
        'netflix_episode_details',
        esc_html__('Episode Details', 'netflix-theme'),
        'netflix_episode_meta_callback',
        'episode',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'netflix_add_meta_boxes');

/**
 * Movie Meta Box Callback
 */
function netflix_movie_meta_callback($post) {
    wp_nonce_field('netflix_meta_nonce', 'netflix_meta_nonce');
    
    $video_url = get_post_meta($post->ID, '_netflix_video_url', true);
    $trailer_url = get_post_meta($post->ID, '_netflix_trailer_url', true);
    $duration = get_post_meta($post->ID, '_netflix_duration', true);
    $imdb_id = get_post_meta($post->ID, '_netflix_imdb_id', true);
    $tmdb_id = get_post_meta($post->ID, '_netflix_tmdb_id', true);
    $director = get_post_meta($post->ID, '_netflix_director', true);
    $cast = get_post_meta($post->ID, '_netflix_cast', true);
    $subtitles = get_post_meta($post->ID, '_netflix_subtitles', true);
    $premium_only = get_post_meta($post->ID, '_netflix_premium_only', true);
    ?>
    <table class="form-table">
        <tr>
            <th><label for="netflix_video_url"><?php esc_html_e('Video URL', 'netflix-theme'); ?></label></th>
            <td><input type="url" id="netflix_video_url" name="netflix_video_url" value="<?php echo esc_attr($video_url); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="netflix_trailer_url"><?php esc_html_e('Trailer URL', 'netflix-theme'); ?></label></th>
            <td><input type="url" id="netflix_trailer_url" name="netflix_trailer_url" value="<?php echo esc_attr($trailer_url); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="netflix_duration"><?php esc_html_e('Duration (minutes)', 'netflix-theme'); ?></label></th>
            <td><input type="number" id="netflix_duration" name="netflix_duration" value="<?php echo esc_attr($duration); ?>" /></td>
        </tr>
        <tr>
            <th><label for="netflix_imdb_id"><?php esc_html_e('IMDb ID', 'netflix-theme'); ?></label></th>
            <td><input type="text" id="netflix_imdb_id" name="netflix_imdb_id" value="<?php echo esc_attr($imdb_id); ?>" /></td>
        </tr>
        <tr>
            <th><label for="netflix_tmdb_id"><?php esc_html_e('TMDb ID', 'netflix-theme'); ?></label></th>
            <td><input type="text" id="netflix_tmdb_id" name="netflix_tmdb_id" value="<?php echo esc_attr($tmdb_id); ?>" /></td>
        </tr>
        <tr>
            <th><label for="netflix_director"><?php esc_html_e('Director', 'netflix-theme'); ?></label></th>
            <td><input type="text" id="netflix_director" name="netflix_director" value="<?php echo esc_attr($director); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="netflix_cast"><?php esc_html_e('Cast (comma-separated)', 'netflix-theme'); ?></label></th>
            <td><textarea id="netflix_cast" name="netflix_cast" rows="3" class="large-text"><?php echo esc_textarea($cast); ?></textarea></td>
        </tr>
        <tr>
            <th><label for="netflix_subtitles"><?php esc_html_e('Subtitles (JSON format)', 'netflix-theme'); ?></label></th>
            <td><textarea id="netflix_subtitles" name="netflix_subtitles" rows="3" class="large-text"><?php echo esc_textarea($subtitles); ?></textarea></td>
        </tr>
        <tr>
            <th><label for="netflix_premium_only"><?php esc_html_e('Premium Only', 'netflix-theme'); ?></label></th>
            <td><input type="checkbox" id="netflix_premium_only" name="netflix_premium_only" value="1" <?php checked($premium_only, 1); ?> /></td>
        </tr>
    </table>
    <?php
}

/**
 * TV Show Meta Box Callback
 */
function netflix_tv_show_meta_callback($post) {
    wp_nonce_field('netflix_meta_nonce', 'netflix_meta_nonce');
    
    $trailer_url = get_post_meta($post->ID, '_netflix_trailer_url', true);
    $seasons = get_post_meta($post->ID, '_netflix_seasons', true);
    $imdb_id = get_post_meta($post->ID, '_netflix_imdb_id', true);
    $tmdb_id = get_post_meta($post->ID, '_netflix_tmdb_id', true);
    $creator = get_post_meta($post->ID, '_netflix_creator', true);
    $cast = get_post_meta($post->ID, '_netflix_cast', true);
    $premium_only = get_post_meta($post->ID, '_netflix_premium_only', true);
    ?>
    <table class="form-table">
        <tr>
            <th><label for="netflix_trailer_url"><?php esc_html_e('Trailer URL', 'netflix-theme'); ?></label></th>
            <td><input type="url" id="netflix_trailer_url" name="netflix_trailer_url" value="<?php echo esc_attr($trailer_url); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="netflix_seasons"><?php esc_html_e('Number of Seasons', 'netflix-theme'); ?></label></th>
            <td><input type="number" id="netflix_seasons" name="netflix_seasons" value="<?php echo esc_attr($seasons); ?>" /></td>
        </tr>
        <tr>
            <th><label for="netflix_imdb_id"><?php esc_html_e('IMDb ID', 'netflix-theme'); ?></label></th>
            <td><input type="text" id="netflix_imdb_id" name="netflix_imdb_id" value="<?php echo esc_attr($imdb_id); ?>" /></td>
        </tr>
        <tr>
            <th><label for="netflix_tmdb_id"><?php esc_html_e('TMDb ID', 'netflix-theme'); ?></label></th>
            <td><input type="text" id="netflix_tmdb_id" name="netflix_tmdb_id" value="<?php echo esc_attr($tmdb_id); ?>" /></td>
        </tr>
        <tr>
            <th><label for="netflix_creator"><?php esc_html_e('Creator', 'netflix-theme'); ?></label></th>
            <td><input type="text" id="netflix_creator" name="netflix_creator" value="<?php echo esc_attr($creator); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="netflix_cast"><?php esc_html_e('Cast (comma-separated)', 'netflix-theme'); ?></label></th>
            <td><textarea id="netflix_cast" name="netflix_cast" rows="3" class="large-text"><?php echo esc_textarea($cast); ?></textarea></td>
        </tr>
        <tr>
            <th><label for="netflix_premium_only"><?php esc_html_e('Premium Only', 'netflix-theme'); ?></label></th>
            <td><input type="checkbox" id="netflix_premium_only" name="netflix_premium_only" value="1" <?php checked($premium_only, 1); ?> /></td>
        </tr>
    </table>
    <?php
}

/**
 * Episode Meta Box Callback
 */
function netflix_episode_meta_callback($post) {
    wp_nonce_field('netflix_meta_nonce', 'netflix_meta_nonce');
    
    $video_url = get_post_meta($post->ID, '_netflix_video_url', true);
    $season = get_post_meta($post->ID, '_netflix_season', true);
    $episode_number = get_post_meta($post->ID, '_netflix_episode_number', true);
    $duration = get_post_meta($post->ID, '_netflix_duration', true);
    $tv_show_id = get_post_meta($post->ID, '_netflix_tv_show_id', true);
    $subtitles = get_post_meta($post->ID, '_netflix_subtitles', true);
    $premium_only = get_post_meta($post->ID, '_netflix_premium_only', true);
    
    // Get TV shows for dropdown
    $tv_shows = get_posts(array(
        'post_type' => 'tv_show',
        'numberposts' => -1,
        'post_status' => 'publish'
    ));
    ?>
    <table class="form-table">
        <tr>
            <th><label for="netflix_video_url"><?php esc_html_e('Video URL', 'netflix-theme'); ?></label></th>
            <td><input type="url" id="netflix_video_url" name="netflix_video_url" value="<?php echo esc_attr($video_url); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="netflix_tv_show_id"><?php esc_html_e('TV Show', 'netflix-theme'); ?></label></th>
            <td>
                <select id="netflix_tv_show_id" name="netflix_tv_show_id">
                    <option value=""><?php esc_html_e('Select TV Show', 'netflix-theme'); ?></option>
                    <?php foreach ($tv_shows as $show): ?>
                        <option value="<?php echo esc_attr($show->ID); ?>" <?php selected($tv_show_id, $show->ID); ?>>
                            <?php echo esc_html($show->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="netflix_season"><?php esc_html_e('Season', 'netflix-theme'); ?></label></th>
            <td><input type="number" id="netflix_season" name="netflix_season" value="<?php echo esc_attr($season); ?>" /></td>
        </tr>
        <tr>
            <th><label for="netflix_episode_number"><?php esc_html_e('Episode Number', 'netflix-theme'); ?></label></th>
            <td><input type="number" id="netflix_episode_number" name="netflix_episode_number" value="<?php echo esc_attr($episode_number); ?>" /></td>
        </tr>
        <tr>
            <th><label for="netflix_duration"><?php esc_html_e('Duration (minutes)', 'netflix-theme'); ?></label></th>
            <td><input type="number" id="netflix_duration" name="netflix_duration" value="<?php echo esc_attr($duration); ?>" /></td>
        </tr>
        <tr>
            <th><label for="netflix_subtitles"><?php esc_html_e('Subtitles (JSON format)', 'netflix-theme'); ?></label></th>
            <td><textarea id="netflix_subtitles" name="netflix_subtitles" rows="3" class="large-text"><?php echo esc_textarea($subtitles); ?></textarea></td>
        </tr>
        <tr>
            <th><label for="netflix_premium_only"><?php esc_html_e('Premium Only', 'netflix-theme'); ?></label></th>
            <td><input type="checkbox" id="netflix_premium_only" name="netflix_premium_only" value="1" <?php checked($premium_only, 1); ?> /></td>
        </tr>
    </table>
    <?php
}

/**
 * Save Meta Box Data
 */
function netflix_save_meta_boxes($post_id) {
    if (!isset($_POST['netflix_meta_nonce']) || !wp_verify_nonce($_POST['netflix_meta_nonce'], 'netflix_meta_nonce')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Save meta fields
    $meta_fields = array(
        'netflix_video_url',
        'netflix_trailer_url',
        'netflix_duration',
        'netflix_imdb_id',
        'netflix_tmdb_id',
        'netflix_director',
        'netflix_creator',
        'netflix_cast',
        'netflix_subtitles',
        'netflix_season',
        'netflix_episode_number',
        'netflix_seasons',
        'netflix_tv_show_id'
    );

    foreach ($meta_fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
        }
    }

    // Handle checkbox
    $premium_only = isset($_POST['netflix_premium_only']) ? 1 : 0;
    update_post_meta($post_id, '_netflix_premium_only', $premium_only);
}
add_action('save_post', 'netflix_save_meta_boxes');

/**
 * Theme Customizer
 */
function netflix_customize_register($wp_customize) {
    // Netflix Settings Section
    $wp_customize->add_section('netflix_settings', array(
        'title' => esc_html__('Netflix Settings', 'netflix-theme'),
        'priority' => 30,
    ));

    // Backend URL
    $wp_customize->add_setting('netflix_backend_url', array(
        'default' => 'http://localhost:3001',
        'sanitize_callback' => 'esc_url_raw',
    ));

    $wp_customize->add_control('netflix_backend_url', array(
        'label' => esc_html__('Backend API URL', 'netflix-theme'),
        'section' => 'netflix_settings',
        'type' => 'url',
    ));

    // API Keys Section
    $wp_customize->add_section('netflix_api_keys', array(
        'title' => esc_html__('API Keys', 'netflix-theme'),
        'priority' => 31,
    ));

    // TMDb API Key
    $wp_customize->add_setting('netflix_tmdb_api_key', array(
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field',
    ));

    $wp_customize->add_control('netflix_tmdb_api_key', array(
        'label' => esc_html__('TMDb API Key', 'netflix-theme'),
        'section' => 'netflix_api_keys',
        'type' => 'text',
    ));

    // OpenAI API Key
    $wp_customize->add_setting('netflix_openai_api_key', array(
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field',
    ));

    $wp_customize->add_control('netflix_openai_api_key', array(
        'label' => esc_html__('OpenAI API Key (for subtitles)', 'netflix-theme'),
        'section' => 'netflix_api_keys',
        'type' => 'text',
    ));
}
add_action('customize_register', 'netflix_customize_register');

/**
 * Netflix Shortcodes
 */
require_once NETFLIX_THEME_PATH . '/inc/shortcodes.php';

/**
 * Backend Integration
 */
require_once NETFLIX_THEME_PATH . '/inc/backend-integration.php';

/**
 * Admin Functions
 */
require_once NETFLIX_THEME_PATH . '/inc/admin-functions.php';

/**
 * Theme Options
 */
require_once NETFLIX_THEME_PATH . '/inc/theme-options.php';

/**
 * User Subscription Management
 */
require_once NETFLIX_THEME_PATH . '/inc/subscription-management.php';