<?php
/*
Plugin Name: Netflix Streaming Platform
Description: Full-featured Netflix-style video streaming platform with backend integration, IMDb import, multi-language support, and advanced video player.
Version: 2.0.0
Author: Netflix Platform Team
Text Domain: netflix-streaming
Domain Path: /languages
*/

if (!defined('ABSPATH')) exit;

// Plugin constants
define('NETFLIX_PLUGIN_VERSION', '2.0.0');
define('NETFLIX_PLUGIN_URL', plugin_dir_url(__FILE__));
define('NETFLIX_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Load text domain for internationalization
add_action('plugins_loaded', function() {
    load_plugin_textdomain('netflix-streaming', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

// Activation hook - create custom post types and flush rewrite rules
register_activation_hook(__FILE__, 'netflix_plugin_activate');
function netflix_plugin_activate() {
    netflix_create_custom_post_types();
    flush_rewrite_rules();
    
    // Create default options
    add_option('netflix_backend_url', 'http://localhost:3001');
    add_option('netflix_api_key', '');
    add_option('netflix_enable_imdb_sync', '1');
    add_option('netflix_auto_publish', '0');
    add_option('netflix_default_quality', '720p');
    add_option('netflix_enable_subtitles', '1');
    add_option('netflix_subtitle_languages', 'en,bn');
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'netflix_plugin_deactivate');
function netflix_plugin_deactivate() {
    flush_rewrite_rules();
}

// Load styles and scripts
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('netflix-style', NETFLIX_PLUGIN_URL . 'css/style.css', [], NETFLIX_PLUGIN_VERSION);
    wp_enqueue_script('netflix-player', NETFLIX_PLUGIN_URL . 'js/player.js', ['jquery'], NETFLIX_PLUGIN_VERSION, true);
    
    // Localize script with backend settings
    wp_localize_script('netflix-player', 'netflix_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'backend_url' => get_option('netflix_backend_url', 'http://localhost:3001'),
        'api_key' => get_option('netflix_api_key', ''),
        'nonce' => wp_create_nonce('netflix_nonce'),
        'user_id' => get_current_user_id(),
        'is_logged_in' => is_user_logged_in(),
        'enable_subtitles' => get_option('netflix_enable_subtitles', '1'),
        'subtitle_languages' => get_option('netflix_subtitle_languages', 'en,bn')
    ]);
});

// Load admin styles and scripts
add_action('admin_enqueue_scripts', function() {
    wp_enqueue_style('netflix-admin-style', NETFLIX_PLUGIN_URL . 'css/admin.css', [], NETFLIX_PLUGIN_VERSION);
    wp_enqueue_script('netflix-admin-script', NETFLIX_PLUGIN_URL . 'js/admin.js', ['jquery'], NETFLIX_PLUGIN_VERSION, true);
    
    wp_localize_script('netflix-admin-script', 'netflix_admin_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'backend_url' => get_option('netflix_backend_url', 'http://localhost:3001'),
        'api_key' => get_option('netflix_api_key', ''),
        'nonce' => wp_create_nonce('netflix_admin_nonce')
    ]);
});

// Include core files
require_once NETFLIX_PLUGIN_PATH . 'inc/custom-post-types.php';
require_once NETFLIX_PLUGIN_PATH . 'inc/backend-integration.php';
require_once NETFLIX_PLUGIN_PATH . 'inc/shortcodes.php';
require_once NETFLIX_PLUGIN_PATH . 'inc/ajax-handlers.php';
require_once NETFLIX_PLUGIN_PATH . 'inc/widget.php';

// Admin panel include
if (is_admin()) {
    require_once NETFLIX_PLUGIN_PATH . 'admin/admin-panel.php';
    require_once NETFLIX_PLUGIN_PATH . 'admin/import-manager.php';
    require_once NETFLIX_PLUGIN_PATH . 'admin/settings.php';
}

// Create custom post types
add_action('init', 'netflix_create_custom_post_types');

// Add custom meta boxes
add_action('add_meta_boxes', 'netflix_add_meta_boxes');

// Save custom fields
add_action('save_post', 'netflix_save_custom_fields');

// Add custom columns to post list
add_filter('manage_netflix_movie_posts_columns', 'netflix_movie_columns');
add_filter('manage_netflix_show_posts_columns', 'netflix_show_columns');
add_action('manage_netflix_movie_posts_custom_column', 'netflix_movie_column_content', 10, 2);
add_action('manage_netflix_show_posts_custom_column', 'netflix_show_column_content', 10, 2);

// Register REST API endpoints for backend communication
add_action('rest_api_init', function() {
    register_rest_route('netflix/v1', '/sync-content', [
        'methods' => 'POST',
        'callback' => 'netflix_sync_content_from_backend',
        'permission_callback' => function() {
            return current_user_can('manage_options');
        }
    ]);
    
    register_rest_route('netflix/v1', '/import-batch', [
        'methods' => 'POST',
        'callback' => 'netflix_start_batch_import',
        'permission_callback' => function() {
            return current_user_can('manage_options');
        }
    ]);
    
    register_rest_route('netflix/v1', '/content/(?P<type>movie|show)/(?P<id>\d+)', [
        'methods' => 'GET',
        'callback' => 'netflix_get_content_details',
        'permission_callback' => '__return_true'
    ]);
});

// Add Netflix menu item to admin
add_action('admin_menu', function() {
    add_menu_page(
        __('Netflix Platform', 'netflix-streaming'),
        __('Netflix Platform', 'netflix-streaming'),
        'manage_options',
        'netflix-platform',
        'netflix_admin_dashboard',
        'dashicons-video-alt3',
        25
    );
    
    add_submenu_page(
        'netflix-platform',
        __('Dashboard', 'netflix-streaming'),
        __('Dashboard', 'netflix-streaming'),
        'manage_options',
        'netflix-platform',
        'netflix_admin_dashboard'
    );
    
    add_submenu_page(
        'netflix-platform',
        __('Import Manager', 'netflix-streaming'),
        __('Import Manager', 'netflix-streaming'),
        'manage_options',
        'netflix-import',
        'netflix_import_manager'
    );
    
    add_submenu_page(
        'netflix-platform',
        __('Settings', 'netflix-streaming'),
        __('Settings', 'netflix-streaming'),
        'manage_options',
        'netflix-settings',
        'netflix_settings_page'
    );
    
    add_submenu_page(
        'netflix-platform',
        __('Player Settings', 'netflix-streaming'),
        __('Player Settings', 'netflix-streaming'),
        'manage_options',
        'netflix-player-settings',
        'cmplayer_admin_panel'  // Keep the existing CMPlayer admin panel
    );
});

// Create dashboard widget
add_action('wp_dashboard_setup', function() {
    wp_add_dashboard_widget(
        'netflix_dashboard_widget',
        __('Netflix Platform Status', 'netflix-streaming'),
        'netflix_dashboard_widget'
    );
});

// Dashboard widget content
function netflix_dashboard_widget() {
    $backend_url = get_option('netflix_backend_url', '');
    $api_key = get_option('netflix_api_key', '');
    
    echo '<div class="netflix-dashboard-widget">';
    echo '<h4>' . __('Backend Status', 'netflix-streaming') . '</h4>';
    
    if (empty($backend_url) || empty($api_key)) {
        echo '<p class="error">' . __('Backend not configured. Please check settings.', 'netflix-streaming') . '</p>';
    } else {
        echo '<p class="success">' . __('Backend configured', 'netflix-streaming') . '</p>';
        echo '<p><strong>' . __('URL:', 'netflix-streaming') . '</strong> ' . esc_html($backend_url) . '</p>';
    }
    
    // Show recent imports
    $recent_imports = get_posts([
        'post_type' => ['netflix_movie', 'netflix_show'],
        'posts_per_page' => 5,
        'meta_query' => [
            [
                'key' => '_netflix_imported',
                'value' => '1',
                'compare' => '='
            ]
        ]
    ]);
    
    if ($recent_imports) {
        echo '<h4>' . __('Recent Imports', 'netflix-streaming') . '</h4>';
        echo '<ul>';
        foreach ($recent_imports as $import) {
            echo '<li><a href="' . get_edit_post_link($import->ID) . '">' . esc_html($import->post_title) . '</a></li>';
        }
        echo '</ul>';
    }
    
    echo '<p><a href="' . admin_url('admin.php?page=netflix-platform') . '" class="button button-primary">' . __('Manage Platform', 'netflix-streaming') . '</a></p>';
    echo '</div>';
}

// Add custom user roles and capabilities
add_action('init', function() {
    // Add capabilities to existing roles
    $admin = get_role('administrator');
    if ($admin) {
        $admin->add_cap('manage_netflix_content');
        $admin->add_cap('import_netflix_content');
        $admin->add_cap('publish_netflix_movies');
        $admin->add_cap('publish_netflix_shows');
    }
    
    $editor = get_role('editor');
    if ($editor) {
        $editor->add_cap('manage_netflix_content');
        $editor->add_cap('publish_netflix_movies');
        $editor->add_cap('publish_netflix_shows');
    }
    
    // Create Netflix Manager role
    if (!get_role('netflix_manager')) {
        add_role('netflix_manager', __('Netflix Manager', 'netflix-streaming'), [
            'read' => true,
            'manage_netflix_content' => true,
            'import_netflix_content' => true,
            'publish_netflix_movies' => true,
            'publish_netflix_shows' => true,
            'edit_posts' => true,
            'edit_published_posts' => true,
            'delete_posts' => true,
            'delete_published_posts' => true,
            'upload_files' => true
        ]);
    }
});

// Auto-sync with backend on post save
add_action('save_post', function($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    
    $post_type = get_post_type($post_id);
    if (!in_array($post_type, ['netflix_movie', 'netflix_show'])) return;
    
    // Sync with backend if enabled
    if (get_option('netflix_enable_backend_sync', '1') === '1') {
        netflix_sync_post_to_backend($post_id);
    }
});

// Add theme support for featured images
add_action('after_setup_theme', function() {
    add_theme_support('post-thumbnails', ['netflix_movie', 'netflix_show']);
    add_image_size('netflix-poster', 300, 450, true);
    add_image_size('netflix-backdrop', 1280, 720, true);
    add_image_size('netflix-thumbnail', 200, 300, true);
});

// Custom image sizes for Netflix content
add_filter('image_size_names_choose', function($sizes) {
    return array_merge($sizes, [
        'netflix-poster' => __('Netflix Poster (300x450)', 'netflix-streaming'),
        'netflix-backdrop' => __('Netflix Backdrop (1280x720)', 'netflix-streaming'),
        'netflix-thumbnail' => __('Netflix Thumbnail (200x300)', 'netflix-streaming')
    ]);
});

// Initialize plugin
add_action('init', function() {
    // Check for backend connectivity
    if (is_admin() && get_option('netflix_api_key')) {
        netflix_check_backend_connection();
    }
});

// Helper function to check backend connection
function netflix_check_backend_connection() {
    $backend_url = get_option('netflix_backend_url', '');
    $api_key = get_option('netflix_api_key', '');
    
    if (empty($backend_url) || empty($api_key)) {
        return false;
    }
    
    $transient_key = 'netflix_backend_status';
    $status = get_transient($transient_key);
    
    if ($status === false) {
        $response = wp_remote_get($backend_url . '/api/wp/health', [
            'headers' => [
                'X-API-Key' => $api_key
            ],
            'timeout' => 10
        ]);
        
        if (is_wp_error($response)) {
            $status = 'error';
        } else {
            $code = wp_remote_retrieve_response_code($response);
            $status = ($code === 200) ? 'connected' : 'error';
        }
        
        set_transient($transient_key, $status, 300); // Cache for 5 minutes
    }
    
    return $status === 'connected';
}

// Add admin notices for configuration
add_action('admin_notices', function() {
    if (!get_option('netflix_api_key') && current_user_can('manage_options')) {
        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p>' . sprintf(
            __('Netflix Platform is not configured. Please <a href="%s">configure the backend settings</a> to enable full functionality.', 'netflix-streaming'),
            admin_url('admin.php?page=netflix-settings')
        ) . '</p>';
        echo '</div>';
    }
});

// Add quick action links to plugin page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=netflix-settings') . '">' . __('Settings', 'netflix-streaming') . '</a>';
    $dashboard_link = '<a href="' . admin_url('admin.php?page=netflix-platform') . '">' . __('Dashboard', 'netflix-streaming') . '</a>';
    
    array_unshift($links, $settings_link);
    array_unshift($links, $dashboard_link);
    
    return $links;
});