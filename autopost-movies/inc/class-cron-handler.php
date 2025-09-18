<?php
/**
 * Cron Handler for AutoPost Movies plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class APM_Cron_Handler {
    
    public function __construct() {
        add_action('apm_fetch_movies', array($this, 'run_scheduled_fetch'));
        add_filter('cron_schedules', array($this, 'add_custom_cron_schedules'));
    }
    
    /**
     * Add custom cron schedules
     */
    public function add_custom_cron_schedules($schedules) {
        // Add weekly schedule if not exists
        if (!isset($schedules['weekly'])) {
            $schedules['weekly'] = array(
                'interval' => 604800, // 7 days
                'display' => __('Weekly', 'autopost-movies')
            );
        }
        
        return $schedules;
    }
    
    /**
     * Run scheduled fetch
     */
    public function run_scheduled_fetch() {
        $post_creator = new APM_Post_Creator();
        $result = $post_creator->fetch_and_create_posts();
        
        // Send admin notification if there were errors
        if (!empty($result['errors'])) {
            $this->send_admin_notification($result);
        }
    }
    
    /**
     * Send admin notification about errors
     */
    private function send_admin_notification($result) {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        
        $subject = sprintf(__('[%s] AutoPost Movies - Errors Detected', 'autopost-movies'), $site_name);
        
        $message = sprintf(
            __("AutoPost Movies encountered some errors during the scheduled run:\n\nCreated Posts: %d\nErrors: %d\n\nError Details:\n%s\n\nPlease check your API settings and logs.", 'autopost-movies'),
            $result['created_posts'],
            count($result['errors']),
            implode("\n", $result['errors'])
        );
        
        wp_mail($admin_email, $subject, $message);
    }
    
    /**
     * Get next scheduled run time
     */
    public function get_next_run() {
        return wp_next_scheduled('apm_fetch_movies');
    }
    
    /**
     * Reschedule cron job
     */
    public function reschedule($schedule) {
        // Clear existing schedule
        wp_clear_scheduled_hook('apm_fetch_movies');
        
        // Schedule new one
        wp_schedule_event(time(), $schedule, 'apm_fetch_movies');
    }
}