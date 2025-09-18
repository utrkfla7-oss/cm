<?php
/**
 * Admin Class
 * Handles admin interface and settings
 */

if (!defined('ABSPATH')) {
    exit;
}

class AutoPost_Movies_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_notices', array($this, 'admin_notices'));
        add_action('wp_ajax_autopost_movies_test_api', array($this, 'test_api_ajax'));
        add_action('wp_ajax_autopost_movies_clear_logs', array($this, 'clear_logs_ajax'));
        add_action('wp_ajax_autopost_movies_export_config', array($this, 'export_config_ajax'));
        add_action('wp_ajax_autopost_movies_import_config', array($this, 'import_config_ajax'));
    }
    
    /**
     * Admin initialization
     */
    public function admin_init() {
        // Handle form submissions
        if (isset($_POST['autopost_movies_save_settings'])) {
            $this->save_settings();
        }
        
        if (isset($_POST['autopost_movies_schedule_change'])) {
            $this->update_schedule();
        }
    }
    
    /**
     * Save settings
     */
    private function save_settings() {
        if (!check_admin_referer('autopost_movies_settings', 'autopost_movies_nonce')) {
            return;
        }
        
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // API Keys
        update_option('autopost_movies_tmdb_api_key', sanitize_text_field($_POST['autopost_movies_tmdb_api_key']));
        update_option('autopost_movies_youtube_api_key', sanitize_text_field($_POST['autopost_movies_youtube_api_key']));
        
        // Data Sources
        update_option('autopost_movies_wikipedia_enabled', isset($_POST['autopost_movies_wikipedia_enabled']) ? 1 : 0);
        update_option('autopost_movies_imdb_enabled', isset($_POST['autopost_movies_imdb_enabled']) ? 1 : 0);
        update_option('autopost_movies_plot_source', sanitize_text_field($_POST['autopost_movies_plot_source']));
        update_option('autopost_movies_info_source', sanitize_text_field($_POST['autopost_movies_info_source']));
        
        // Content Settings
        update_option('autopost_movies_content_order', sanitize_text_field($_POST['autopost_movies_content_order']));
        update_option('autopost_movies_fifu_enabled', isset($_POST['autopost_movies_fifu_enabled']) ? 1 : 0);
        update_option('autopost_movies_max_posts_per_run', intval($_POST['autopost_movies_max_posts_per_run']));
        
        // Custom info template
        update_option('autopost_movies_custom_info_template', wp_kses_post($_POST['autopost_movies_custom_info_template']));
        
        // Additional buttons
        $additional_buttons = array();
        if (isset($_POST['additional_button_text']) && is_array($_POST['additional_button_text'])) {
            for ($i = 0; $i < count($_POST['additional_button_text']); $i++) {
                if (!empty($_POST['additional_button_text'][$i]) && !empty($_POST['additional_button_url'][$i])) {
                    $additional_buttons[] = array(
                        'text' => sanitize_text_field($_POST['additional_button_text'][$i]),
                        'url' => esc_url_raw($_POST['additional_button_url'][$i])
                    );
                }
            }
        }
        update_option('autopost_movies_additional_buttons', $additional_buttons);
        
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible"><p>' . __('Settings saved successfully!', 'autopost-movies') . '</p></div>';
        });
    }
    
    /**
     * Update cron schedule
     */
    private function update_schedule() {
        if (!check_admin_referer('autopost_movies_schedule', 'autopost_movies_schedule_nonce')) {
            return;
        }
        
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $new_schedule = sanitize_text_field($_POST['autopost_movies_cron_schedule']);
        update_option('autopost_movies_cron_schedule', $new_schedule);
        update_option('autopost_movies_cron_schedule_changed', true);
        
        $cron_handler = new AutoPost_Movies_Cron_Handler();
        $cron_handler->reschedule_cron($new_schedule);
        
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible"><p>' . __('Cron schedule updated successfully!', 'autopost-movies') . '</p></div>';
        });
    }
    
    /**
     * Admin notices
     */
    public function admin_notices() {
        // Check if TMDB API key is configured
        $api_key = get_option('autopost_movies_tmdb_api_key', '');
        if (empty($api_key)) {
            echo '<div class="notice notice-warning">
                <p>' . sprintf(__('AutoPost Movies: Please <a href="%s">configure your TMDB API key</a> to enable automatic posting.', 'autopost-movies'), admin_url('options-general.php?page=autopost-movies')) . '</p>
            </div>';
        }
        
        // Check FIFU plugin compatibility
        if (get_option('autopost_movies_fifu_enabled') && !is_plugin_active('featured-image-from-url/featured-image-from-url.php')) {
            echo '<div class="notice notice-info">
                <p>' . __('AutoPost Movies: Featured Image from URL (FIFU) plugin is not active. Poster images will be stored as meta fields only.', 'autopost-movies') . '</p>
            </div>';
        }
    }
    
    /**
     * Test API AJAX handler
     */
    public function test_api_ajax() {
        if (!check_ajax_referer('autopost_movies_admin_nonce', 'nonce', false) || !current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'autopost-movies'));
        }
        
        $api_type = sanitize_text_field($_POST['api_type']);
        $api_handler = new AutoPost_Movies_API_Handler();
        
        switch ($api_type) {
            case 'tmdb':
                $result = $api_handler->fetch_popular_movies(1);
                if ($result && isset($result['results'])) {
                    wp_send_json_success(array(
                        'message' => sprintf(__('TMDB API working! Found %d popular movies.', 'autopost-movies'), count($result['results']))
                    ));
                } else {
                    wp_send_json_error(array('message' => __('TMDB API test failed. Please check your API key.', 'autopost-movies')));
                }
                break;
                
            case 'youtube':
                $result = $api_handler->search_youtube_trailer('Avengers Endgame', 2019);
                if ($result) {
                    wp_send_json_success(array(
                        'message' => __('YouTube API working! Test search successful.', 'autopost-movies')
                    ));
                } else {
                    wp_send_json_error(array('message' => __('YouTube API test failed. Please check your API key.', 'autopost-movies')));
                }
                break;
                
            case 'wikipedia':
                $result = $api_handler->get_wikipedia_summary('Avengers: Endgame');
                if ($result) {
                    wp_send_json_success(array(
                        'message' => __('Wikipedia API working! Test search successful.', 'autopost-movies')
                    ));
                } else {
                    wp_send_json_error(array('message' => __('Wikipedia API test failed or no results found.', 'autopost-movies')));
                }
                break;
                
            default:
                wp_send_json_error(array('message' => __('Invalid API type.', 'autopost-movies')));
        }
    }
    
    /**
     * Clear logs AJAX handler
     */
    public function clear_logs_ajax() {
        if (!check_ajax_referer('autopost_movies_admin_nonce', 'nonce', false) || !current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'autopost-movies'));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'autopost_movies_logs';
        
        $result = $wpdb->query("TRUNCATE TABLE {$table_name}");
        
        if ($result !== false) {
            wp_send_json_success(array('message' => __('Logs cleared successfully.', 'autopost-movies')));
        } else {
            wp_send_json_error(array('message' => __('Failed to clear logs.', 'autopost-movies')));
        }
    }
    
    /**
     * Export configuration AJAX handler
     */
    public function export_config_ajax() {
        if (!check_ajax_referer('autopost_movies_admin_nonce', 'nonce', false) || !current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'autopost-movies'));
        }
        
        $config = array(
            'version' => AUTOPOST_MOVIES_VERSION,
            'settings' => array(
                'autopost_movies_tmdb_api_key' => get_option('autopost_movies_tmdb_api_key', ''),
                'autopost_movies_youtube_api_key' => get_option('autopost_movies_youtube_api_key', ''),
                'autopost_movies_wikipedia_enabled' => get_option('autopost_movies_wikipedia_enabled', 0),
                'autopost_movies_imdb_enabled' => get_option('autopost_movies_imdb_enabled', 0),
                'autopost_movies_cron_schedule' => get_option('autopost_movies_cron_schedule', 'daily'),
                'autopost_movies_plot_source' => get_option('autopost_movies_plot_source', 'tmdb'),
                'autopost_movies_info_source' => get_option('autopost_movies_info_source', 'tmdb'),
                'autopost_movies_content_order' => get_option('autopost_movies_content_order', 'plot_first'),
                'autopost_movies_fifu_enabled' => get_option('autopost_movies_fifu_enabled', 1),
                'autopost_movies_max_posts_per_run' => get_option('autopost_movies_max_posts_per_run', 5),
                'autopost_movies_custom_info_template' => get_option('autopost_movies_custom_info_template', ''),
                'autopost_movies_additional_buttons' => get_option('autopost_movies_additional_buttons', array())
            ),
            'export_date' => current_time('mysql')
        );
        
        wp_send_json_success(array(
            'config' => $config,
            'filename' => 'autopost-movies-config-' . date('Y-m-d') . '.json'
        ));
    }
    
    /**
     * Import configuration AJAX handler
     */
    public function import_config_ajax() {
        if (!check_ajax_referer('autopost_movies_admin_nonce', 'nonce', false) || !current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'autopost-movies'));
        }
        
        $config_json = wp_unslash($_POST['config']);
        $config = json_decode($config_json, true);
        
        if (!$config || !isset($config['settings'])) {
            wp_send_json_error(array('message' => __('Invalid configuration file.', 'autopost-movies')));
        }
        
        // Import settings
        foreach ($config['settings'] as $key => $value) {
            update_option($key, $value);
        }
        
        // Update cron schedule if changed
        if (isset($config['settings']['autopost_movies_cron_schedule'])) {
            update_option('autopost_movies_cron_schedule_changed', true);
        }
        
        wp_send_json_success(array('message' => __('Configuration imported successfully.', 'autopost-movies')));
    }
    
    /**
     * Get processing statistics
     */
    public function get_statistics() {
        $cron_handler = new AutoPost_Movies_Cron_Handler();
        return $cron_handler->get_processing_stats();
    }
    
    /**
     * Get recent logs
     */
    public function get_recent_logs($limit = 20) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'autopost_movies_logs';
        
        $logs = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} ORDER BY created_at DESC LIMIT %d",
                $limit
            )
        );
        
        return $logs;
    }
    
    /**
     * Get pending items
     */
    public function get_pending_items($limit = 50) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'autopost_movies';
        
        $items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE status = 'pending' ORDER BY created_at DESC LIMIT %d",
                $limit
            )
        );
        
        return $items;
    }
    
    /**
     * Get posted items
     */
    public function get_posted_items($limit = 50) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'autopost_movies';
        
        $items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE status = 'posted' ORDER BY created_at DESC LIMIT %d",
                $limit
            )
        );
        
        return $items;
    }
    
    /**
     * Get error items
     */
    public function get_error_items($limit = 50) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'autopost_movies';
        
        $items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE status = 'error' ORDER BY created_at DESC LIMIT %d",
                $limit
            )
        );
        
        return $items;
    }
    
    /**
     * Get cron status
     */
    public function get_cron_status() {
        $cron_handler = new AutoPost_Movies_Cron_Handler();
        return $cron_handler->get_cron_status();
    }
    
    /**
     * Get available schedules
     */
    public function get_available_schedules() {
        $cron_handler = new AutoPost_Movies_Cron_Handler();
        return $cron_handler->get_available_schedules();
    }
}