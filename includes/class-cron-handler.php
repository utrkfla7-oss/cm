<?php
/**
 * Cron Handler Class
 * Handles WordPress cron scheduling and execution
 */

if (!defined('ABSPATH')) {
    exit;
}

class AutoPost_Movies_Cron_Handler {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('autopost_movies_cron_hook', array($this, 'run_cron'));
        add_filter('cron_schedules', array($this, 'add_custom_schedules'));
        add_action('wp_ajax_autopost_movies_manual_cron', array($this, 'manual_cron_ajax'));
        add_action('init', array($this, 'maybe_reschedule_cron'));
    }
    
    /**
     * Add custom cron schedules
     */
    public function add_custom_schedules($schedules) {
        // Add hourly schedule if not exists
        if (!isset($schedules['hourly'])) {
            $schedules['hourly'] = array(
                'interval' => HOUR_IN_SECONDS,
                'display' => __('Once Hourly', 'autopost-movies')
            );
        }
        
        // Add twice daily schedule
        $schedules['twicedaily'] = array(
            'interval' => 12 * HOUR_IN_SECONDS,
            'display' => __('Twice Daily', 'autopost-movies')
        );
        
        // Add weekly schedule
        $schedules['weekly'] = array(
            'interval' => WEEK_IN_SECONDS,
            'display' => __('Once Weekly', 'autopost-movies')
        );
        
        return $schedules;
    }
    
    /**
     * Check if cron schedule needs to be updated
     */
    public function maybe_reschedule_cron() {
        $current_schedule = get_option('autopost_movies_cron_schedule', 'daily');
        $next_run = wp_next_scheduled('autopost_movies_cron_hook');
        
        // If no cron is scheduled or schedule changed, reschedule
        if (!$next_run || get_option('autopost_movies_cron_schedule_changed', false)) {
            $this->reschedule_cron($current_schedule);
            delete_option('autopost_movies_cron_schedule_changed');
        }
    }
    
    /**
     * Reschedule cron with new frequency
     */
    public function reschedule_cron($schedule = 'daily') {
        // Clear existing schedule
        wp_clear_scheduled_hook('autopost_movies_cron_hook');
        
        // Schedule new cron
        if (!wp_next_scheduled('autopost_movies_cron_hook')) {
            wp_schedule_event(time(), $schedule, 'autopost_movies_cron_hook');
            AutoPost_Movies::log('cron', "Scheduled cron with frequency: {$schedule}");
        }
    }
    
    /**
     * Run the cron job
     */
    public function run_cron() {
        AutoPost_Movies::log('cron', 'Starting cron job execution');
        
        // Check if TMDB API key is configured
        $api_key = get_option('autopost_movies_tmdb_api_key', '');
        if (empty($api_key)) {
            AutoPost_Movies::log('error', 'Cron job aborted: TMDB API key not configured');
            return;
        }
        
        try {
            $api_handler = new AutoPost_Movies_API_Handler();
            $api_handler->process_movies();
            
            AutoPost_Movies::log('cron', 'Cron job completed successfully');
            
        } catch (Exception $e) {
            AutoPost_Movies::log('error', 'Cron job failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Manual cron execution via AJAX
     */
    public function manual_cron_ajax() {
        // Check nonce and permissions
        if (!check_ajax_referer('autopost_movies_admin_nonce', 'nonce', false) || !current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'autopost-movies'));
        }
        
        AutoPost_Movies::log('cron', 'Manual cron execution started by user: ' . get_current_user_id());
        
        try {
            $this->run_cron();
            
            wp_send_json_success(array(
                'message' => __('Cron job executed successfully', 'autopost-movies'),
                'timestamp' => current_time('mysql')
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Cron job failed: ', 'autopost-movies') . $e->getMessage()
            ));
        }
    }
    
    /**
     * Get next scheduled run time
     */
    public function get_next_run_time() {
        $next_run = wp_next_scheduled('autopost_movies_cron_hook');
        
        if (!$next_run) {
            return false;
        }
        
        return $next_run;
    }
    
    /**
     * Get cron status information
     */
    public function get_cron_status() {
        $next_run = $this->get_next_run_time();
        $schedule = get_option('autopost_movies_cron_schedule', 'daily');
        
        $status = array(
            'scheduled' => (bool) $next_run,
            'next_run' => $next_run,
            'next_run_formatted' => $next_run ? date('Y-m-d H:i:s', $next_run) : null,
            'schedule' => $schedule,
            'timezone' => wp_timezone_string()
        );
        
        return $status;
    }
    
    /**
     * Get available cron schedules
     */
    public function get_available_schedules() {
        $schedules = wp_get_schedules();
        
        $available = array(
            'hourly' => __('Hourly', 'autopost-movies'),
            'twicedaily' => __('Twice Daily', 'autopost-movies'),
            'daily' => __('Daily', 'autopost-movies'),
            'weekly' => __('Weekly', 'autopost-movies')
        );
        
        return $available;
    }
    
    /**
     * Clear all cron schedules (for deactivation)
     */
    public function clear_all_schedules() {
        wp_clear_scheduled_hook('autopost_movies_cron_hook');
        AutoPost_Movies::log('cron', 'All cron schedules cleared');
    }
    
    /**
     * Test cron functionality
     */
    public function test_cron() {
        // Test if WP-Cron is working
        $test_hook = 'autopost_movies_test_cron';
        
        // Schedule a test event
        wp_schedule_single_event(time() + 60, $test_hook);
        
        // Check if it was scheduled
        $scheduled = wp_next_scheduled($test_hook);
        
        if ($scheduled) {
            wp_clear_scheduled_hook($test_hook);
            return array(
                'success' => true,
                'message' => __('WP-Cron appears to be working correctly', 'autopost-movies')
            );
        } else {
            return array(
                'success' => false,
                'message' => __('WP-Cron may not be working. Please check your server configuration.', 'autopost-movies')
            );
        }
    }
    
    /**
     * Get recent cron execution logs
     */
    public function get_recent_logs($limit = 10) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'autopost_movies_logs';
        
        $logs = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} 
                WHERE type IN ('cron', 'post_creation', 'error') 
                ORDER BY created_at DESC 
                LIMIT %d",
                $limit
            )
        );
        
        return $logs;
    }
    
    /**
     * Check if system supports cron
     */
    public function check_cron_support() {
        $issues = array();
        
        // Check if WP-Cron is disabled
        if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) {
            $issues[] = __('WP-Cron is disabled. You may need to set up a server cron job.', 'autopost-movies');
        }
        
        // Check if we can schedule events
        $test_time = time() + 300; // 5 minutes from now
        $test_hook = 'autopost_movies_cron_test';
        
        wp_schedule_single_event($test_time, $test_hook);
        $scheduled = wp_next_scheduled($test_hook);
        
        if (!$scheduled) {
            $issues[] = __('Unable to schedule cron events. Please check your WordPress configuration.', 'autopost-movies');
        } else {
            wp_clear_scheduled_hook($test_hook);
        }
        
        // Check if current user can manage options
        if (!current_user_can('manage_options')) {
            $issues[] = __('Insufficient permissions to manage cron settings.', 'autopost-movies');
        }
        
        return array(
            'supported' => empty($issues),
            'issues' => $issues
        );
    }
    
    /**
     * Get processing statistics
     */
    public function get_processing_stats() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'autopost_movies';
        
        $stats = array(
            'total' => 0,
            'pending' => 0,
            'posted' => 0,
            'errors' => 0,
            'movies' => 0,
            'tv_series' => 0
        );
        
        // Get total counts
        $results = $wpdb->get_results(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'posted' THEN 1 ELSE 0 END) as posted,
                SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as errors,
                SUM(CASE WHEN type = 'movie' THEN 1 ELSE 0 END) as movies,
                SUM(CASE WHEN type = 'tv' THEN 1 ELSE 0 END) as tv_series
            FROM {$table_name}"
        );
        
        if (!empty($results[0])) {
            $result = $results[0];
            $stats = array(
                'total' => (int) $result->total,
                'pending' => (int) $result->pending,
                'posted' => (int) $result->posted,
                'errors' => (int) $result->errors,
                'movies' => (int) $result->movies,
                'tv_series' => (int) $result->tv_series
            );
        }
        
        return $stats;
    }
}