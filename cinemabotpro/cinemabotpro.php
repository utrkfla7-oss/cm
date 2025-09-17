<?php
/**
 * Plugin Name: CinemaBot Pro - Ultimate Movie & TV Chatbot
 * Plugin URI: https://example.com/cinemabotpro
 * Description: AI-powered multilingual chatbot for movie and TV content with advanced features, dynamic avatars, and user memory system.
 * Version: 1.0.0
 * Author: CinemaBot Team
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cinemabotpro
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.3
 * Requires PHP: 7.0
 * Network: false
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CINEMABOTPRO_VERSION', '1.0.0');
define('CINEMABOTPRO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CINEMABOTPRO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CINEMABOTPRO_PLUGIN_FILE', __FILE__);
define('CINEMABOTPRO_TEXT_DOMAIN', 'cinemabotpro');

// Main plugin class
class CinemaBotPro {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Load core files
        $this->load_dependencies();
        
        // Initialize components
        $this->init_hooks();
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
    }
    
    private function load_dependencies() {
        require_once CINEMABOTPRO_PLUGIN_DIR . 'includes/class-chatbot.php';
        require_once CINEMABOTPRO_PLUGIN_DIR . 'includes/class-avatar-system.php';
        require_once CINEMABOTPRO_PLUGIN_DIR . 'includes/class-user-memory.php';
        require_once CINEMABOTPRO_PLUGIN_DIR . 'includes/class-ai-engine.php';
        require_once CINEMABOTPRO_PLUGIN_DIR . 'includes/class-content-crawler.php';
        require_once CINEMABOTPRO_PLUGIN_DIR . 'includes/class-post-types.php';
        require_once CINEMABOTPRO_PLUGIN_DIR . 'includes/class-security.php';
        require_once CINEMABOTPRO_PLUGIN_DIR . 'includes/class-analytics.php';
        require_once CINEMABOTPRO_PLUGIN_DIR . 'admin/class-admin.php';
        require_once CINEMABOTPRO_PLUGIN_DIR . 'includes/class-api.php';
    }
    
    private function init_hooks() {
        // Initialize chatbot
        new CinemaBotPro_Chatbot();
        
        // Initialize avatar system
        new CinemaBotPro_Avatar_System();
        
        // Initialize user memory
        new CinemaBotPro_User_Memory();
        
        // Initialize AI engine
        new CinemaBotPro_AI_Engine();
        
        // Initialize content crawler
        new CinemaBotPro_Content_Crawler();
        
        // Initialize custom post types
        new CinemaBotPro_Post_Types();
        
        // Initialize security
        new CinemaBotPro_Security();
        
        // Initialize analytics
        new CinemaBotPro_Analytics();
        
        // Initialize admin
        if (is_admin()) {
            new CinemaBotPro_Admin();
        }
        
        // Initialize API
        new CinemaBotPro_API();
        
        // Register shortcodes
        add_shortcode('cinemabot', array($this, 'chatbot_shortcode'));
        add_shortcode('cinemabot_search', array($this, 'search_shortcode'));
    }
    
    public function load_textdomain() {
        load_plugin_textdomain(
            CINEMABOTPRO_TEXT_DOMAIN,
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
    }
    
    public function enqueue_scripts() {
        // Enqueue CSS
        wp_enqueue_style(
            'cinemabotpro-style',
            CINEMABOTPRO_PLUGIN_URL . 'assets/css/style.css',
            array(),
            CINEMABOTPRO_VERSION
        );
        
        wp_enqueue_style(
            'cinemabotpro-chatbot',
            CINEMABOTPRO_PLUGIN_URL . 'assets/css/chatbot.css',
            array(),
            CINEMABOTPRO_VERSION
        );
        
        // Enqueue JavaScript
        wp_enqueue_script(
            'cinemabotpro-main',
            CINEMABOTPRO_PLUGIN_URL . 'assets/js/main.js',
            array('jquery'),
            CINEMABOTPRO_VERSION,
            true
        );
        
        wp_enqueue_script(
            'cinemabotpro-chatbot',
            CINEMABOTPRO_PLUGIN_URL . 'assets/js/chatbot.js',
            array('jquery', 'cinemabotpro-main'),
            CINEMABOTPRO_VERSION,
            true
        );
        
        // Localize script with settings
        wp_localize_script('cinemabotpro-chatbot', 'cinemabotpro_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cinemabotpro_nonce'),
            'rest_url' => rest_url('cinemabotpro/v1/'),
            'rest_nonce' => wp_create_nonce('wp_rest'),
            'language' => get_locale(),
            'settings' => $this->get_frontend_settings()
        ));
    }
    
    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'cinemabotpro') === false) {
            return;
        }
        
        wp_enqueue_style(
            'cinemabotpro-admin',
            CINEMABOTPRO_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            CINEMABOTPRO_VERSION
        );
        
        wp_enqueue_script(
            'cinemabotpro-admin',
            CINEMABOTPRO_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            CINEMABOTPRO_VERSION,
            true
        );
    }
    
    private function get_frontend_settings() {
        return array(
            'avatar_rotation_interval' => get_option('cinemabotpro_avatar_rotation', 30),
            'auto_language_detection' => get_option('cinemabotpro_auto_lang_detect', 1),
            'animation_speed' => get_option('cinemabotpro_animation_speed', 'medium'),
            'memory_enabled' => get_option('cinemabotpro_memory_enabled', 1),
            'gdpr_compliance' => get_option('cinemabotpro_gdpr_enabled', 1)
        );
    }
    
    public function chatbot_shortcode($atts) {
        $atts = shortcode_atts(array(
            'avatar' => 'auto',
            'language' => 'auto',
            'theme' => 'default',
            'position' => 'bottom-right',
            'width' => '350',
            'height' => '500'
        ), $atts, 'cinemabot');
        
        ob_start();
        include CINEMABOTPRO_PLUGIN_DIR . 'templates/chatbot.php';
        return ob_get_clean();
    }
    
    public function search_shortcode($atts) {
        $atts = shortcode_atts(array(
            'type' => 'movies',
            'layout' => 'grid',
            'limit' => 10
        ), $atts, 'cinemabot_search');
        
        ob_start();
        include CINEMABOTPRO_PLUGIN_DIR . 'templates/search.php';
        return ob_get_clean();
    }
    
    public function activate() {
        // Create database tables
        $this->create_tables();
        
        // Set default options
        $this->set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Chat history table
        $chat_table = $wpdb->prefix . 'cinemabotpro_chats';
        $chat_sql = "CREATE TABLE $chat_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            session_id varchar(255) NOT NULL,
            message text NOT NULL,
            response text NOT NULL,
            language varchar(10) NOT NULL,
            avatar varchar(255) NOT NULL,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY session_id (session_id)
        ) $charset_collate;";
        
        // User preferences table
        $prefs_table = $wpdb->prefix . 'cinemabotpro_user_prefs';
        $prefs_sql = "CREATE TABLE $prefs_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            preference_key varchar(255) NOT NULL,
            preference_value longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_pref (user_id, preference_key)
        ) $charset_collate;";
        
        // Analytics table
        $analytics_table = $wpdb->prefix . 'cinemabotpro_analytics';
        $analytics_sql = "CREATE TABLE $analytics_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) DEFAULT NULL,
            session_id varchar(255) NOT NULL,
            event_type varchar(50) NOT NULL,
            event_data longtext,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY event_type (event_type),
            KEY timestamp (timestamp)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($chat_sql);
        dbDelta($prefs_sql);
        dbDelta($analytics_sql);
    }
    
    private function set_default_options() {
        $defaults = array(
            'cinemabotpro_enabled' => 1,
            'cinemabotpro_avatar_rotation' => 30,
            'cinemabotpro_auto_lang_detect' => 1,
            'cinemabotpro_animation_speed' => 'medium',
            'cinemabotpro_memory_enabled' => 1,
            'cinemabotpro_gdpr_enabled' => 1,
            'cinemabotpro_openai_api_key' => '',
            'cinemabotpro_default_language' => 'en',
            'cinemabotpro_supported_languages' => array('en', 'bn', 'hi', 'banglish'),
            'cinemabotpro_avatar_pack' => 'default',
            'cinemabotpro_chat_theme' => 'modern',
            'cinemabotpro_max_message_length' => 500,
            'cinemabotpro_rate_limit' => 30,
            'cinemabotpro_content_crawling' => 1,
            'cinemabotpro_recommendations' => 1
        );
        
        foreach ($defaults as $option => $value) {
            if (get_option($option) === false) {
                add_option($option, $value);
            }
        }
    }
}

// Initialize the plugin
function cinemabotpro_init() {
    return CinemaBotPro::get_instance();
}

// Start the plugin
cinemabotpro_init();