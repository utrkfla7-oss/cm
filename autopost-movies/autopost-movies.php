<?php
/*
Plugin Name: AutoPost Movies
Description: Automates posting of upcoming popular movies and TV series using TMDB API and other sources
Version: 1.0.0
Author: AutoPost Movies Team
Text Domain: autopost-movies
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.0
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('APM_VERSION', '1.0.0');
define('APM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('APM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('APM_PLUGIN_FILE', __FILE__);

/**
 * Main plugin class
 */
class AutoPostMovies {
    
    /**
     * Initialize the plugin
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Initialize plugin functionality
     */
    public function init() {
        // Load text domain for translations
        load_plugin_textdomain('autopost-movies', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Include required files
        $this->includes();
        
        // Initialize admin if in admin area
        if (is_admin()) {
            $this->admin_init();
        }
        
        // Initialize cron jobs
        $this->init_cron();
        
        // Initialize REST API
        $this->init_api();
        
        // Register shortcodes
        $this->register_shortcodes();
        
        // Enqueue frontend styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_styles'));
    }
    
    /**
     * Include required files
     */
    private function includes() {
        require_once APM_PLUGIN_DIR . 'inc/class-logger.php';
        require_once APM_PLUGIN_DIR . 'inc/class-api-handler.php';
        require_once APM_PLUGIN_DIR . 'inc/class-post-creator.php';
        require_once APM_PLUGIN_DIR . 'inc/class-cron-handler.php';
        
        if (is_admin()) {
            require_once APM_PLUGIN_DIR . 'admin/class-admin.php';
        }
    }
    
    /**
     * Initialize admin functionality
     */
    private function admin_init() {
        new APM_Admin();
    }
    
    /**
     * Initialize cron functionality
     */
    private function init_cron() {
        new APM_Cron_Handler();
    }
    
    /**
     * Initialize REST API
     */
    private function init_api() {
        add_action('rest_api_init', array($this, 'register_api_endpoints'));
    }
    
    /**
     * Register REST API endpoints
     */
    public function register_api_endpoints() {
        register_rest_route('autopost-movies/v1', '/manual-sync', array(
            'methods' => 'POST',
            'callback' => array($this, 'manual_sync'),
            'permission_callback' => function() {
                return current_user_can('manage_options');
            }
        ));
        
        register_rest_route('autopost-movies/v1', '/logs', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_logs'),
            'permission_callback' => function() {
                return current_user_can('manage_options');
            }
        ));
    }
    
    /**
     * Manual sync endpoint
     */
    public function manual_sync($request) {
        $post_creator = new APM_Post_Creator();
        $result = $post_creator->fetch_and_create_posts();
        
        return new WP_REST_Response($result, 200);
    }
    
    /**
     * Get logs endpoint
     */
    public function get_logs($request) {
        $logger = new APM_Logger();
        $logs = $logger->get_logs(50); // Get last 50 logs
        
        return new WP_REST_Response($logs, 200);
    }
    
    /**
     * Register shortcodes
     */
    private function register_shortcodes() {
        add_shortcode('apm_wikipedia_info', array($this, 'shortcode_wikipedia_info'));
        add_shortcode('apm_custom_info', array($this, 'shortcode_custom_info'));
        add_shortcode('apm_trailer_button', array($this, 'shortcode_trailer_button'));
        add_shortcode('apm_clickable_link', array($this, 'shortcode_clickable_link'));
    }
    
    /**
     * Wikipedia info shortcode
     */
    public function shortcode_wikipedia_info($atts) {
        $atts = shortcode_atts(array(
            'title' => ''
        ), $atts);
        
        if (empty($atts['title'])) {
            return '';
        }
        
        $api_handler = new APM_API_Handler();
        $info = $api_handler->get_wikipedia_info($atts['title']);
        
        if ($info) {
            return '<div class="apm-wikipedia-info">' . wp_kses_post($info) . '</div>';
        }
        
        return '';
    }
    
    /**
     * Custom info shortcode
     */
    public function shortcode_custom_info($atts, $content = '') {
        return '<div class="apm-custom-info">' . do_shortcode($content) . '</div>';
    }
    
    /**
     * Trailer button shortcode
     */
    public function shortcode_trailer_button($atts) {
        $atts = shortcode_atts(array(
            'url' => '',
            'text' => __('Watch Trailer', 'autopost-movies')
        ), $atts);
        
        if (empty($atts['url'])) {
            return '';
        }
        
        return '<a href="' . esc_url($atts['url']) . '" class="apm-trailer-button" target="_blank">' . esc_html($atts['text']) . '</a>';
    }
    
    /**
     * Clickable link shortcode
     */
    public function shortcode_clickable_link($atts, $content = '') {
        $atts = shortcode_atts(array(
            'url' => ''
        ), $atts);
        
        if (empty($atts['url'])) {
            return $content;
        }
        
        return '<a href="' . esc_url($atts['url']) . '" target="_blank">' . do_shortcode($content) . '</a>';
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables if needed
        $this->create_tables();
        
        // Set default options
        $this->set_default_options();
        
        // Schedule cron events
        $this->schedule_cron_events();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled events
        $this->clear_cron_events();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Logs table
        $table_name = $wpdb->prefix . 'apm_logs';
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            level varchar(10) NOT NULL,
            message text NOT NULL,
            context longtext,
            PRIMARY KEY (id),
            KEY level (level),
            KEY timestamp (timestamp)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Movie/TV posts tracking table
        $table_name = $wpdb->prefix . 'apm_posts';
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            tmdb_id varchar(20) NOT NULL,
            post_id bigint(20) NOT NULL,
            media_type varchar(10) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY tmdb_id (tmdb_id),
            KEY post_id (post_id),
            KEY media_type (media_type)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Set default options
     */
    private function set_default_options() {
        $defaults = array(
            'apm_tmdb_api_key' => '',
            'apm_wikipedia_enabled' => 1,
            'apm_imdb_enabled' => 0,
            'apm_youtube_api_key' => '',
            'apm_cron_schedule' => 'daily',
            'apm_tmdb_plot_enabled' => 1,
            'apm_wikipedia_plot_enabled' => 1,
            'apm_imdb_plot_enabled' => 0,
            'apm_content_order' => 'plot_first',
            'apm_posts_per_run' => 5,
            'apm_post_status' => 'publish',
            'apm_post_category' => '',
            'apm_featured_image_enabled' => 1
        );
        
        foreach ($defaults as $key => $value) {
            if (false === get_option($key)) {
                add_option($key, $value);
            }
        }
    }
    
    /**
     * Schedule cron events
     */
    private function schedule_cron_events() {
        if (!wp_next_scheduled('apm_fetch_movies')) {
            $schedule = get_option('apm_cron_schedule', 'daily');
            wp_schedule_event(time(), $schedule, 'apm_fetch_movies');
        }
    }
    
    /**
     * Clear cron events
     */
    private function clear_cron_events() {
        wp_clear_scheduled_hook('apm_fetch_movies');
    }
    
    /**
     * Enqueue frontend styles
     */
    public function enqueue_frontend_styles() {
        wp_enqueue_style('apm-frontend', APM_PLUGIN_URL . 'assets/frontend.css', array(), APM_VERSION);
    }
}

// Initialize the plugin
new AutoPostMovies();