<?php
/**
 * Plugin Name: Movie TV Classic Manager
 * Description: Manual movie/TV show management features with Classic Editor integration, TMDB API support, and FIFU compatibility.
 * Version: 1.0.0
 * Author: Movie TV Classic Manager Team
 * Text Domain: movie-tv-classic-manager
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('MTCM_VERSION', '1.0.0');
define('MTCM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MTCM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('MTCM_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Load text domain for internationalization
add_action('plugins_loaded', 'mtcm_load_textdomain');
function mtcm_load_textdomain() {
    load_plugin_textdomain('movie-tv-classic-manager', false, dirname(plugin_basename(__FILE__)) . '/languages');
}

// Plugin activation hook
register_activation_hook(__FILE__, 'mtcm_activate');
function mtcm_activate() {
    // Create default options
    add_option('mtcm_tmdb_api_key', '');
    add_option('mtcm_fifu_support', '1');
    add_option('mtcm_default_poster_size', 'medium');
    
    // Create custom post types and flush rewrite rules
    mtcm_register_post_types();
    flush_rewrite_rules();
}

// Plugin deactivation hook
register_deactivation_hook(__FILE__, 'mtcm_deactivate');
function mtcm_deactivate() {
    flush_rewrite_rules();
}

// Initialize plugin
add_action('init', 'mtcm_init');
function mtcm_init() {
    // Register custom post types
    mtcm_register_post_types();
    
    // Add theme support for post thumbnails if not already supported
    if (!current_theme_supports('post-thumbnails')) {
        add_theme_support('post-thumbnails');
    }
    
    // Add custom image sizes
    add_image_size('mtcm-poster', 300, 450, true);
    add_image_size('mtcm-backdrop', 1280, 720, true);
    add_image_size('mtcm-thumbnail', 150, 225, true);
}

// Register custom post types
function mtcm_register_post_types() {
    // Movie post type
    register_post_type('mtcm_movie', array(
        'labels' => array(
            'name' => __('Movies', 'movie-tv-classic-manager'),
            'singular_name' => __('Movie', 'movie-tv-classic-manager'),
            'add_new' => __('Add New Movie', 'movie-tv-classic-manager'),
            'add_new_item' => __('Add New Movie', 'movie-tv-classic-manager'),
            'edit_item' => __('Edit Movie', 'movie-tv-classic-manager'),
            'new_item' => __('New Movie', 'movie-tv-classic-manager'),
            'view_item' => __('View Movie', 'movie-tv-classic-manager'),
            'search_items' => __('Search Movies', 'movie-tv-classic-manager'),
            'not_found' => __('No movies found', 'movie-tv-classic-manager'),
            'not_found_in_trash' => __('No movies found in trash', 'movie-tv-classic-manager'),
        ),
        'public' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-video-alt',
        'supports' => array('title', 'editor', 'excerpt', 'thumbnail', 'custom-fields'),
        'show_in_rest' => false, // Classic Editor only
        'rewrite' => array('slug' => 'movies'),
    ));

    // TV Show post type
    register_post_type('mtcm_tv_show', array(
        'labels' => array(
            'name' => __('TV Shows', 'movie-tv-classic-manager'),
            'singular_name' => __('TV Show', 'movie-tv-classic-manager'),
            'add_new' => __('Add New TV Show', 'movie-tv-classic-manager'),
            'add_new_item' => __('Add New TV Show', 'movie-tv-classic-manager'),
            'edit_item' => __('Edit TV Show', 'movie-tv-classic-manager'),
            'new_item' => __('New TV Show', 'movie-tv-classic-manager'),
            'view_item' => __('View TV Show', 'movie-tv-classic-manager'),
            'search_items' => __('Search TV Shows', 'movie-tv-classic-manager'),
            'not_found' => __('No TV shows found', 'movie-tv-classic-manager'),
            'not_found_in_trash' => __('No TV shows found in trash', 'movie-tv-classic-manager'),
        ),
        'public' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-format-video',
        'supports' => array('title', 'editor', 'excerpt', 'thumbnail', 'custom-fields'),
        'show_in_rest' => false, // Classic Editor only
        'rewrite' => array('slug' => 'tv-shows'),
    ));

    // Register taxonomies
    register_taxonomy('mtcm_genre', array('mtcm_movie', 'mtcm_tv_show'), array(
        'labels' => array(
            'name' => __('Genres', 'movie-tv-classic-manager'),
            'singular_name' => __('Genre', 'movie-tv-classic-manager'),
        ),
        'hierarchical' => false,
        'public' => true,
        'show_ui' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'genre'),
    ));

    register_taxonomy('mtcm_year', array('mtcm_movie', 'mtcm_tv_show'), array(
        'labels' => array(
            'name' => __('Release Years', 'movie-tv-classic-manager'),
            'singular_name' => __('Release Year', 'movie-tv-classic-manager'),
        ),
        'hierarchical' => false,
        'public' => true,
        'show_ui' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'year'),
    ));
}

// Admin enqueue scripts and styles
add_action('admin_enqueue_scripts', 'mtcm_admin_enqueue_scripts');
function mtcm_admin_enqueue_scripts($hook) {
    // Only load on post edit screens and settings page
    if (in_array($hook, array('post.php', 'post-new.php')) || strpos($hook, 'mtcm-settings') !== false) {
        wp_enqueue_style('mtcm-admin-style', MTCM_PLUGIN_URL . 'assets/css/admin-style.css', array(), MTCM_VERSION);
        wp_enqueue_script('mtcm-admin-script', MTCM_PLUGIN_URL . 'assets/js/admin-script.js', array('jquery'), MTCM_VERSION, true);
        
        wp_localize_script('mtcm-admin-script', 'mtcm_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mtcm_nonce'),
            'tmdb_api_key' => get_option('mtcm_tmdb_api_key', ''),
        ));
    }
}

// Frontend enqueue scripts and styles
add_action('wp_enqueue_scripts', 'mtcm_frontend_enqueue_scripts');
function mtcm_frontend_enqueue_scripts() {
    wp_enqueue_style('mtcm-frontend-style', MTCM_PLUGIN_URL . 'assets/css/frontend-style.css', array(), MTCM_VERSION);
}

// Include required files
require_once MTCM_PLUGIN_PATH . 'includes/admin-settings.php';
require_once MTCM_PLUGIN_PATH . 'includes/meta-boxes.php';
require_once MTCM_PLUGIN_PATH . 'includes/shortcodes.php';
require_once MTCM_PLUGIN_PATH . 'includes/tmdb-api.php';

// Classic Editor TinyMCE button integration
add_action('admin_init', 'mtcm_tinymce_integration');
function mtcm_tinymce_integration() {
    // Check if user can edit posts
    if (!current_user_can('edit_posts') && !current_user_can('edit_pages')) {
        return;
    }

    // Add TinyMCE button only if Classic Editor is active
    if (get_user_option('rich_editing') == 'true') {
        add_filter('mce_external_plugins', 'mtcm_add_tinymce_plugin');
        add_filter('mce_buttons', 'mtcm_register_tinymce_button');
    }
}

function mtcm_add_tinymce_plugin($plugin_array) {
    $plugin_array['mtcm_button'] = MTCM_PLUGIN_URL . 'assets/js/tinymce-button.js';
    return $plugin_array;
}

function mtcm_register_tinymce_button($buttons) {
    array_push($buttons, 'mtcm_button');
    return $buttons;
}

// Add TinyMCE button styles
add_action('admin_head', 'mtcm_tinymce_button_styles');
function mtcm_tinymce_button_styles() {
    echo '<style>
    .mce-i-mtcm-movie::before {
        font-family: dashicons;
        content: "\f126";
        font-size: 18px;
    }
    </style>';
}

// AJAX handler for TMDB search
add_action('wp_ajax_mtcm_tmdb_search', 'mtcm_handle_tmdb_search');
function mtcm_handle_tmdb_search() {
    check_ajax_referer('mtcm_nonce', 'nonce');
    
    $query = sanitize_text_field($_POST['query']);
    $type = sanitize_text_field($_POST['type']); // movie or tv
    
    $results = mtcm_search_tmdb($query, $type);
    
    wp_send_json_success($results);
}

// AJAX handler for TMDB details
add_action('wp_ajax_mtcm_tmdb_details', 'mtcm_handle_tmdb_details');
function mtcm_handle_tmdb_details() {
    check_ajax_referer('mtcm_nonce', 'nonce');
    
    $id = intval($_POST['id']);
    $type = sanitize_text_field($_POST['type']); // movie or tv
    
    $details = mtcm_get_tmdb_details($id, $type);
    
    wp_send_json_success($details);
}

// Add custom image sizes to media library
add_filter('image_size_names_choose', 'mtcm_custom_image_sizes');
function mtcm_custom_image_sizes($sizes) {
    return array_merge($sizes, array(
        'mtcm-poster' => __('Movie Poster (300x450)', 'movie-tv-classic-manager'),
        'mtcm-backdrop' => __('Movie Backdrop (1280x720)', 'movie-tv-classic-manager'),
        'mtcm-thumbnail' => __('Movie Thumbnail (150x225)', 'movie-tv-classic-manager'),
    ));
}

// FIFU compatibility - check if Featured Image from URL plugin is active
function mtcm_is_fifu_active() {
    return is_plugin_active('featured-image-from-url/featured-image-from-url.php') || 
           function_exists('fifu_activate');
}

// Helper function to set featured image from URL (FIFU compatible)
function mtcm_set_featured_image_from_url($post_id, $image_url) {
    if (empty($image_url)) {
        return false;
    }
    
    // If FIFU is active, use its functionality
    if (mtcm_is_fifu_active() && function_exists('fifu_dev_set_image')) {
        fifu_dev_set_image($post_id, $image_url);
        return true;
    }
    
    // Fallback: set as custom field for manual handling
    update_post_meta($post_id, '_mtcm_poster_url', $image_url);
    return true;
}

// Add plugin action links
add_filter('plugin_action_links_' . MTCM_PLUGIN_BASENAME, 'mtcm_plugin_action_links');
function mtcm_plugin_action_links($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=mtcm-settings') . '">' . __('Settings', 'movie-tv-classic-manager') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}