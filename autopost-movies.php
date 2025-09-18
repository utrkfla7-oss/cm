<?php
/**
 * AutoPost Movies with TMDB API Integration
 * 
 * This module handles automatic movie posting using The Movie Database (TMDB) API
 * with proper error handling, caching, and retry mechanisms.
 */

if (!defined('ABSPATH')) exit;

class CMPlayer_AutoPost_Movies {
    
    private $tmdb_api_key;
    private $tmdb_base_url = 'https://api.themoviedb.org/3';
    private $cache_expiry = 3600; // 1 hour cache
    private $max_retries = 3;
    private $retry_delay = 2; // seconds
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_ajax_cmplayer_test_tmdb_api', array($this, 'test_tmdb_api'));
        add_action('wp_ajax_cmplayer_run_autopost_now', array($this, 'run_autopost_now'));
        add_action('wp_ajax_cmplayer_clear_tmdb_cache', array($this, 'clear_tmdb_cache_ajax'));
        add_action('cmplayer_autopost_cron', array($this, 'run_autopost'));
        
        // Schedule cron job if not already scheduled
        if (!wp_next_scheduled('cmplayer_autopost_cron')) {
            wp_schedule_event(time(), 'hourly', 'cmplayer_autopost_cron');
        }
    }
    
    public function init() {
        $this->tmdb_api_key = get_option('cmplayer_tmdb_api_key', '');
    }
    
    /**
     * Validate TMDB API key
     */
    public function validate_api_key($api_key = null) {
        if (!$api_key) {
            $api_key = $this->tmdb_api_key;
        }
        
        if (empty($api_key)) {
            $this->log_error('TMDB API key is empty');
            return false;
        }
        
        $url = $this->tmdb_base_url . '/configuration?api_key=' . $api_key;
        $response = $this->make_api_request($url, 'GET', array(), false); // Don't use cache for validation
        
        if (is_wp_error($response)) {
            $this->log_error('TMDB API validation failed: ' . $response->get_error_message());
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code === 200) {
            $this->log_info('TMDB API key validated successfully');
            return true;
        } else {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            $error_message = isset($data['status_message']) ? $data['status_message'] : 'Unknown error';
            $this->log_error('TMDB API validation failed: HTTP ' . $response_code . ' - ' . $error_message);
            return false;
        }
    }
    
    /**
     * Make API request with retry logic and caching
     */
    private function make_api_request($url, $method = 'GET', $args = array(), $use_cache = true) {
        $cache_key = 'cmplayer_tmdb_' . md5($url . serialize($args));
        
        // Try to get from cache first
        if ($use_cache) {
            $cached_response = get_transient($cache_key);
            if ($cached_response !== false) {
                $this->log_info('Using cached TMDB response for: ' . $url);
                return $cached_response;
            }
        }
        
        $defaults = array(
            'timeout' => 15,
            'user-agent' => 'CMPlayer WordPress Plugin/1.0.0',
            'headers' => array(
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            )
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $retries = 0;
        while ($retries < $this->max_retries) {
            $response = wp_remote_request($url, $args);
            
            if (!is_wp_error($response)) {
                $response_code = wp_remote_retrieve_response_code($response);
                
                if ($response_code === 200) {
                    // Cache successful response
                    if ($use_cache) {
                        set_transient($cache_key, $response, $this->cache_expiry);
                    }
                    $this->log_info('TMDB API request successful: ' . $url);
                    return $response;
                } elseif ($response_code === 429) {
                    // Rate limit hit, wait longer before retry
                    $this->log_warning('TMDB API rate limit hit, retrying in ' . ($this->retry_delay * 2) . ' seconds');
                    sleep($this->retry_delay * 2);
                } elseif ($response_code >= 500) {
                    // Server error, retry
                    $this->log_warning('TMDB API server error (HTTP ' . $response_code . '), retrying in ' . $this->retry_delay . ' seconds');
                    sleep($this->retry_delay);
                } else {
                    // Client error, don't retry
                    $body = wp_remote_retrieve_body($response);
                    $data = json_decode($body, true);
                    $error_message = isset($data['status_message']) ? $data['status_message'] : 'Unknown error';
                    $this->log_error('TMDB API client error: HTTP ' . $response_code . ' - ' . $error_message);
                    return new WP_Error('tmdb_client_error', 'TMDB API error: ' . $error_message, array('response_code' => $response_code));
                }
            } else {
                $this->log_warning('TMDB API request failed: ' . $response->get_error_message() . ', retrying in ' . $this->retry_delay . ' seconds');
                sleep($this->retry_delay);
            }
            
            $retries++;
        }
        
        $this->log_error('TMDB API request failed after ' . $this->max_retries . ' retries: ' . $url);
        $this->notify_admin_of_failure($url);
        return new WP_Error('tmdb_max_retries', 'TMDB API request failed after maximum retries');
    }
    
    /**
     * Get popular movies from TMDB
     */
    public function get_popular_movies($page = 1) {
        if (!$this->validate_api_key()) {
            return new WP_Error('invalid_api_key', 'Invalid or missing TMDB API key');
        }
        
        $url = $this->tmdb_base_url . '/movie/popular?api_key=' . $this->tmdb_api_key . '&page=' . $page;
        $response = $this->make_api_request($url);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data || !isset($data['results'])) {
            return new WP_Error('invalid_response', 'Invalid response from TMDB API');
        }
        
        return $data['results'];
    }
    
    /**
     * Search movies by query
     */
    public function search_movies($query, $page = 1) {
        if (!$this->validate_api_key()) {
            return new WP_Error('invalid_api_key', 'Invalid or missing TMDB API key');
        }
        
        $url = $this->tmdb_base_url . '/search/movie?api_key=' . $this->tmdb_api_key . '&query=' . urlencode($query) . '&page=' . $page;
        $response = $this->make_api_request($url);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data || !isset($data['results'])) {
            return new WP_Error('invalid_response', 'Invalid search response from TMDB API');
        }
        
        return $data['results'];
    }
    
    /**
     * Get movie details by ID
     */
    public function get_movie_details($movie_id) {
        if (!$this->validate_api_key()) {
            return new WP_Error('invalid_api_key', 'Invalid or missing TMDB API key');
        }
        
        $url = $this->tmdb_base_url . '/movie/' . $movie_id . '?api_key=' . $this->tmdb_api_key . '&append_to_response=videos,credits';
        $response = $this->make_api_request($url);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data || !isset($data['id'])) {
            return new WP_Error('invalid_response', 'Invalid movie details response from TMDB API');
        }
        
        return $data;
    }
    
    /**
     * Auto-post movies functionality
     */
    public function run_autopost() {
        if (!get_option('cmplayer_autopost_enabled', false)) {
            $this->log_info('AutoPost is disabled, skipping');
            return;
        }
        
        $this->log_info('Starting AutoPost movies process');
        
        $movies = $this->get_popular_movies();
        if (is_wp_error($movies)) {
            $this->log_error('Failed to get popular movies: ' . $movies->get_error_message());
            return;
        }
        
        $max_posts = get_option('cmplayer_autopost_max_posts', 5);
        $posted_count = 0;
        
        foreach ($movies as $movie) {
            if ($posted_count >= $max_posts) {
                break;
            }
            
            // Check if movie already exists
            if ($this->movie_exists($movie['id'])) {
                continue;
            }
            
            $movie_details = $this->get_movie_details($movie['id']);
            if (is_wp_error($movie_details)) {
                $this->log_error('Failed to get movie details for ID ' . $movie['id'] . ': ' . $movie_details->get_error_message());
                continue;
            }
            
            $post_id = $this->create_movie_post($movie_details);
            if ($post_id) {
                $posted_count++;
                $this->log_info('Successfully created post for movie: ' . $movie_details['title'] . ' (Post ID: ' . $post_id . ')');
            }
        }
        
        $this->log_info('AutoPost completed. Posted ' . $posted_count . ' movies.');
    }
    
    /**
     * Check if movie already exists as a post
     */
    private function movie_exists($tmdb_id) {
        $existing = get_posts(array(
            'post_type' => 'post',
            'meta_query' => array(
                array(
                    'key' => 'cmplayer_tmdb_id',
                    'value' => $tmdb_id,
                    'compare' => '='
                )
            ),
            'posts_per_page' => 1
        ));
        
        return !empty($existing);
    }
    
    /**
     * Create WordPress post from movie data
     */
    private function create_movie_post($movie_data) {
        $post_data = array(
            'post_title' => sanitize_text_field($movie_data['title']),
            'post_content' => wp_kses_post($movie_data['overview']),
            'post_status' => get_option('cmplayer_autopost_status', 'draft'),
            'post_type' => 'post',
            'post_category' => array(get_option('cmplayer_autopost_category', 1))
        );
        
        $post_id = wp_insert_post($post_data);
        
        if ($post_id && !is_wp_error($post_id)) {
            // Add movie metadata
            update_post_meta($post_id, 'cmplayer_tmdb_id', $movie_data['id']);
            update_post_meta($post_id, 'cmplayer_release_date', $movie_data['release_date']);
            update_post_meta($post_id, 'cmplayer_rating', $movie_data['vote_average']);
            update_post_meta($post_id, 'cmplayer_runtime', isset($movie_data['runtime']) ? $movie_data['runtime'] : '');
            update_post_meta($post_id, 'cmplayer_genres', isset($movie_data['genres']) ? wp_json_encode($movie_data['genres']) : '');
            
            // Set featured image if available
            if (!empty($movie_data['poster_path'])) {
                $this->set_featured_image($post_id, $movie_data['poster_path']);
            }
            
            return $post_id;
        }
        
        return false;
    }
    
    /**
     * Set featured image from TMDB poster
     */
    private function set_featured_image($post_id, $poster_path) {
        $image_url = 'https://image.tmdb.org/t/p/w500' . $poster_path;
        
        $response = wp_remote_get($image_url);
        if (is_wp_error($response)) {
            return false;
        }
        
        $image_data = wp_remote_retrieve_body($response);
        $filename = basename($poster_path);
        
        $upload = wp_upload_bits($filename, null, $image_data);
        if ($upload['error']) {
            return false;
        }
        
        $attachment = array(
            'post_mime_type' => $upload['type'],
            'post_title' => sanitize_file_name($filename),
            'post_content' => '',
            'post_status' => 'inherit'
        );
        
        $attachment_id = wp_insert_attachment($attachment, $upload['file'], $post_id);
        
        if ($attachment_id) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attachment_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
            wp_update_attachment_metadata($attachment_id, $attachment_data);
            set_post_thumbnail($post_id, $attachment_id);
            return true;
        }
        
        return false;
    }
    
    /**
     * Test TMDB API connection (AJAX handler)
     */
    public function test_tmdb_api() {
        check_ajax_referer('cmplayer_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $api_key = sanitize_text_field($_POST['api_key']);
        
        if (empty($api_key)) {
            wp_send_json_error('API key is required');
        }
        
        $test_result = $this->validate_api_key($api_key);
        
        if ($test_result) {
            wp_send_json_success('TMDB API key is valid and working!');
        } else {
            wp_send_json_error('TMDB API key validation failed. Please check your key and try again.');
        }
    }
    
    /**
     * Run AutoPost now (AJAX handler)
     */
    public function run_autopost_now() {
        check_ajax_referer('cmplayer_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        ob_start();
        $this->run_autopost();
        $output = ob_get_clean();
        
        wp_send_json_success('AutoPost completed successfully. Check the logs for details.');
    }
    
    /**
     * Clear TMDB cache (AJAX handler)
     */
    public function clear_tmdb_cache_ajax() {
        check_ajax_referer('cmplayer_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $this->clear_cache();
        wp_send_json_success('TMDB cache cleared successfully.');
    }
    
    /**
     * Clear TMDB cache
     */
    public function clear_cache() {
        global $wpdb;
        
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_cmplayer_tmdb_%' OR option_name LIKE '_transient_timeout_cmplayer_tmdb_%'");
        
        $this->log_info('TMDB cache cleared');
        return true;
    }
    
    /**
     * Log info message
     */
    private function log_info($message) {
        if (get_option('cmplayer_autopost_logging', true)) {
            error_log('[CMPlayer AutoPost INFO] ' . $message);
        }
    }
    
    /**
     * Log warning message
     */
    private function log_warning($message) {
        if (get_option('cmplayer_autopost_logging', true)) {
            error_log('[CMPlayer AutoPost WARNING] ' . $message);
        }
    }
    
    /**
     * Log error message
     */
    private function log_error($message) {
        if (get_option('cmplayer_autopost_logging', true)) {
            error_log('[CMPlayer AutoPost ERROR] ' . $message);
        }
    }
    
    /**
     * Notify admin of API failures
     */
    private function notify_admin_of_failure($url) {
        $last_notification = get_option('cmplayer_last_failure_notification', 0);
        $notification_interval = 3600; // 1 hour
        
        if ((time() - $last_notification) > $notification_interval) {
            $admin_email = get_option('admin_email');
            $subject = 'CMPlayer AutoPost: TMDB API Failure';
            $message = "The TMDB API has failed repeatedly for the URL: {$url}\n\n";
            $message .= "Please check your TMDB API key and try again.\n\n";
            $message .= "You can test your API key in the CMPlayer settings page.";
            
            wp_mail($admin_email, $subject, $message);
            update_option('cmplayer_last_failure_notification', time());
            
            $this->log_info('Admin notified of TMDB API failures');
        }
    }
}

// Initialize the AutoPost Movies functionality
new CMPlayer_AutoPost_Movies();