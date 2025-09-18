<?php
/**
 * Plugin Name: AutoPost Movies
 * Plugin URI: https://github.com/utrkfla7-oss/cm
 * Description: Production-ready WordPress plugin to enable automatic posting of upcoming popular movies and TV series using comprehensive admin panel with TMDB, Wikipedia, IMDb, and YouTube integration.
 * Version: 1.0.0
 * Author: CM Team
 * Text Domain: autopost-movies
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.3
 * Requires PHP: 7.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('AUTOPOST_MOVIES_VERSION', '1.0.0');
define('AUTOPOST_MOVIES_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AUTOPOST_MOVIES_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AUTOPOST_MOVIES_PLUGIN_FILE', __FILE__);

/**
 * Main AutoPost Movies Class
 */
class AutoPost_Movies {
    
    /**
     * Instance of this class
     */
    private static $instance = null;
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        add_action('init', array($this, 'init'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create custom database tables if needed
        $this->create_tables();
        
        // Set default options
        $this->set_default_options();
        
        // Schedule cron events
        $this->schedule_cron();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled cron events
        $this->clear_cron();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain
        load_plugin_textdomain('autopost-movies', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Initialize components
        $this->load_dependencies();
        $this->init_components();
    }
    
    /**
     * Admin initialization
     */
    public function admin_init() {
        // Register settings
        $this->register_settings();
    }
    
    /**
     * Add admin menu
     */
    public function admin_menu() {
        add_options_page(
            __('AutoPost Movies', 'autopost-movies'),
            __('AutoPost Movies', 'autopost-movies'),
            'manage_options',
            'autopost-movies',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Enqueue frontend scripts
     */
    public function enqueue_scripts() {
        wp_enqueue_style('autopost-movies-style', AUTOPOST_MOVIES_PLUGIN_URL . 'assets/css/style.css', array(), AUTOPOST_MOVIES_VERSION);
        wp_enqueue_script('autopost-movies-script', AUTOPOST_MOVIES_PLUGIN_URL . 'assets/js/script.js', array('jquery'), AUTOPOST_MOVIES_VERSION, true);
        
        // Localize script
        wp_localize_script('autopost-movies-script', 'autopost_movies_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('autopost_movies_nonce')
        ));
    }
    
    /**
     * Enqueue admin scripts
     */
    public function admin_enqueue_scripts($hook) {
        if ('settings_page_autopost-movies' !== $hook) {
            return;
        }
        
        wp_enqueue_style('autopost-movies-admin-style', AUTOPOST_MOVIES_PLUGIN_URL . 'assets/css/admin.css', array(), AUTOPOST_MOVIES_VERSION);
        wp_enqueue_script('autopost-movies-admin-script', AUTOPOST_MOVIES_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), AUTOPOST_MOVIES_VERSION, true);
        
        // Localize admin script
        wp_localize_script('autopost-movies-admin-script', 'autopost_movies_admin_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('autopost_movies_admin_nonce')
        ));
    }
    
    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Movies/TV series table
        $table_name = $wpdb->prefix . 'autopost_movies';
        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            tmdb_id int(11) NOT NULL,
            imdb_id varchar(20) DEFAULT NULL,
            title varchar(255) NOT NULL,
            type enum('movie','tv') NOT NULL,
            release_date date DEFAULT NULL,
            poster_url text DEFAULT NULL,
            plot text DEFAULT NULL,
            trailer_url text DEFAULT NULL,
            status enum('pending','posted','error') DEFAULT 'pending',
            post_id int(11) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY tmdb_id (tmdb_id),
            KEY status (status),
            KEY type (type)
        ) $charset_collate;";
        
        // API logs table
        $logs_table = $wpdb->prefix . 'autopost_movies_logs';
        $logs_sql = "CREATE TABLE $logs_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            type enum('tmdb','wikipedia','imdb','youtube','post_creation','error') NOT NULL,
            message text NOT NULL,
            data longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY type (type),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        dbDelta($logs_sql);
    }
    
    /**
     * Set default options
     */
    private function set_default_options() {
        $defaults = array(
            'autopost_movies_tmdb_api_key' => '',
            'autopost_movies_wikipedia_enabled' => 0,
            'autopost_movies_imdb_enabled' => 0,
            'autopost_movies_youtube_api_key' => '',
            'autopost_movies_cron_schedule' => 'daily',
            'autopost_movies_plot_source' => 'tmdb',
            'autopost_movies_info_source' => 'tmdb',
            'autopost_movies_content_order' => 'plot_first',
            'autopost_movies_fifu_enabled' => 1,
            'autopost_movies_max_posts_per_run' => 5
        );
        
        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }
    
    /**
     * Schedule cron events
     */
    private function schedule_cron() {
        if (!wp_next_scheduled('autopost_movies_cron_hook')) {
            wp_schedule_event(time(), 'daily', 'autopost_movies_cron_hook');
        }
    }
    
    /**
     * Clear cron events
     */
    private function clear_cron() {
        wp_clear_scheduled_hook('autopost_movies_cron_hook');
    }
    
    /**
     * Load dependencies
     */
    private function load_dependencies() {
        require_once AUTOPOST_MOVIES_PLUGIN_DIR . 'includes/class-api-handler.php';
        require_once AUTOPOST_MOVIES_PLUGIN_DIR . 'includes/class-post-creator.php';
        require_once AUTOPOST_MOVIES_PLUGIN_DIR . 'includes/class-cron-handler.php';
        require_once AUTOPOST_MOVIES_PLUGIN_DIR . 'includes/class-custom-fields.php';
        require_once AUTOPOST_MOVIES_PLUGIN_DIR . 'includes/class-shortcodes.php';
        require_once AUTOPOST_MOVIES_PLUGIN_DIR . 'admin/class-admin.php';
    }
    
    /**
     * Initialize components
     */
    private function init_components() {
        new AutoPost_Movies_API_Handler();
        new AutoPost_Movies_Post_Creator();
        new AutoPost_Movies_Cron_Handler();
        new AutoPost_Movies_Custom_Fields();
        new AutoPost_Movies_Shortcodes();
        
        if (is_admin()) {
            new AutoPost_Movies_Admin();
        }
    }
    
    /**
     * Register settings
     */
    private function register_settings() {
        $settings = array(
            'autopost_movies_tmdb_api_key',
            'autopost_movies_wikipedia_enabled',
            'autopost_movies_imdb_enabled',
            'autopost_movies_youtube_api_key',
            'autopost_movies_cron_schedule',
            'autopost_movies_plot_source',
            'autopost_movies_info_source',
            'autopost_movies_content_order',
            'autopost_movies_fifu_enabled',
            'autopost_movies_max_posts_per_run'
        );
        
        foreach ($settings as $setting) {
            register_setting('autopost_movies_settings', $setting);
        }
    }
    
    /**
     * Admin page callback
     */
    public function admin_page() {
        require_once AUTOPOST_MOVIES_PLUGIN_DIR . 'admin/admin-page.php';
    }
    
    /**
     * Log function
     */
    public static function log($type, $message, $data = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'autopost_movies_logs';
        $wpdb->insert(
            $table_name,
            array(
                'type' => $type,
                'message' => $message,
                'data' => is_array($data) || is_object($data) ? json_encode($data) : $data,
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s')
        );
    }
}

// Initialize the plugin
AutoPost_Movies::get_instance();