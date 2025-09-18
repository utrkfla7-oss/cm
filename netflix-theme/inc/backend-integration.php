<?php
/**
 * Backend Integration for Netflix Theme
 * 
 * @package Netflix_Theme
 */

if (!defined('ABSPATH')) exit;

/**
 * Backend API Integration Class
 */
class Netflix_Backend_Integration {
    
    private $backend_url;
    private $api_key;
    
    public function __construct() {
        $this->backend_url = get_theme_mod('netflix_backend_url', 'http://localhost:3001');
        $this->api_key = get_theme_mod('netflix_api_key', '');
        
        add_action('init', array($this, 'init'));
        add_action('wp_ajax_netflix_sync_content', array($this, 'sync_content'));
        add_action('wp_ajax_netflix_import_tmdb', array($this, 'import_from_tmdb'));
        add_action('wp_ajax_netflix_get_streaming_url', array($this, 'get_streaming_url'));
    }
    
    public function init() {
        // Add REST API endpoints
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }
    
    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        register_rest_route('netflix/v1', '/sync', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_sync_request'),
            'permission_callback' => array($this, 'check_api_permissions'),
        ));
        
        register_rest_route('netflix/v1', '/content/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_content_data'),
            'permission_callback' => '__return_true',
        ));
        
        register_rest_route('netflix/v1', '/user/subscription', array(
            'methods' => array('GET', 'POST'),
            'callback' => array($this, 'handle_subscription'),
            'permission_callback' => array($this, 'check_user_permissions'),
        ));
    }
    
    /**
     * Check API permissions
     */
    public function check_api_permissions() {
        return current_user_can('manage_options');
    }
    
    /**
     * Check user permissions
     */
    public function check_user_permissions() {
        return is_user_logged_in();
    }
    
    /**
     * Handle sync request from backend
     */
    public function handle_sync_request($request) {
        $data = $request->get_json_params();
        
        if (empty($data['content_type']) || empty($data['content_data'])) {
            return new WP_Error('missing_data', 'Content type and data required', array('status' => 400));
        }
        
        $result = $this->create_or_update_content($data['content_type'], $data['content_data']);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'post_id' => $result,
            'message' => 'Content synced successfully'
        ));
    }
    
    /**
     * Get content data for API
     */
    public function get_content_data($request) {
        $post_id = $request['id'];
        $post = get_post($post_id);
        
        if (!$post) {
            return new WP_Error('not_found', 'Content not found', array('status' => 404));
        }
        
        $data = array(
            'id' => $post->ID,
            'title' => $post->post_title,
            'description' => $post->post_content,
            'excerpt' => $post->post_excerpt,
            'type' => $post->post_type,
            'status' => $post->post_status,
            'date' => $post->post_date,
            'modified' => $post->post_modified,
            'meta' => array(),
            'taxonomies' => array(),
        );
        
        // Get meta data
        $meta_fields = array(
            'video_url', 'trailer_url', 'duration', 'imdb_id', 'tmdb_id',
            'director', 'creator', 'cast', 'subtitles', 'season',
            'episode_number', 'seasons', 'tv_show_id', 'premium_only'
        );
        
        foreach ($meta_fields as $field) {
            $value = get_post_meta($post_id, '_netflix_' . $field, true);
            if (!empty($value)) {
                $data['meta'][$field] = $value;
            }
        }
        
        // Get taxonomies
        $taxonomies = array('genre', 'release_year', 'content_rating');
        foreach ($taxonomies as $taxonomy) {
            $terms = get_the_terms($post_id, $taxonomy);
            if ($terms && !is_wp_error($terms)) {
                $data['taxonomies'][$taxonomy] = wp_list_pluck($terms, 'name');
            }
        }
        
        // Get featured image
        if (has_post_thumbnail($post_id)) {
            $data['featured_image'] = get_the_post_thumbnail_url($post_id, 'full');
        }
        
        return rest_ensure_response($data);
    }
    
    /**
     * Handle subscription requests
     */
    public function handle_subscription($request) {
        $user_id = get_current_user_id();
        
        if ($request->get_method() === 'GET') {
            $subscription = get_user_meta($user_id, 'netflix_subscription', true);
            $subscription_data = get_user_meta($user_id, 'netflix_subscription_data', true);
            
            return rest_ensure_response(array(
                'subscription' => $subscription ?: 'free',
                'data' => $subscription_data ?: array(),
            ));
        }
        
        if ($request->get_method() === 'POST') {
            $plan = $request->get_param('plan');
            $payment_data = $request->get_param('payment_data');
            
            // Here you would integrate with payment processor
            // For now, we'll just update the user's subscription
            
            update_user_meta($user_id, 'netflix_subscription', $plan);
            update_user_meta($user_id, 'netflix_subscription_data', array(
                'plan' => $plan,
                'start_date' => current_time('mysql'),
                'payment_data' => $payment_data,
            ));
            
            return rest_ensure_response(array(
                'success' => true,
                'message' => 'Subscription updated successfully',
                'subscription' => $plan,
            ));
        }
    }
    
    /**
     * Sync content from backend
     */
    public function sync_content() {
        check_ajax_referer('netflix_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $response = wp_remote_get($this->backend_url . '/api/wp/content', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
            ),
            'timeout' => 30,
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error('Failed to connect to backend: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (empty($data['content'])) {
            wp_send_json_error('No content received from backend');
        }
        
        $synced_count = 0;
        foreach ($data['content'] as $content_item) {
            $result = $this->create_or_update_content($content_item['type'], $content_item);
            if (!is_wp_error($result)) {
                $synced_count++;
            }
        }
        
        wp_send_json_success("Synced {$synced_count} items from backend");
    }
    
    /**
     * Import content from TMDb
     */
    public function import_from_tmdb() {
        check_ajax_referer('netflix_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $tmdb_id = sanitize_text_field($_POST['tmdb_id']);
        $content_type = sanitize_text_field($_POST['content_type']); // movie or tv
        
        $response = wp_remote_post($this->backend_url . '/api/imdb/import', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'tmdb_id' => $tmdb_id,
                'type' => $content_type,
            )),
            'timeout' => 60,
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error('Failed to import from TMDb: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (empty($data['success'])) {
            wp_send_json_error('Import failed: ' . ($data['message'] ?? 'Unknown error'));
        }
        
        wp_send_json_success('Content imported successfully');
    }
    
    /**
     * Get streaming URL from backend
     */
    public function get_streaming_url() {
        check_ajax_referer('netflix_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        $quality = sanitize_text_field($_POST['quality'] ?? 'auto');
        
        $response = wp_remote_post($this->backend_url . '/api/videos/stream', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'content_id' => $post_id,
                'quality' => $quality,
                'user_id' => get_current_user_id(),
            )),
            'timeout' => 30,
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error('Failed to get streaming URL: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (empty($data['streaming_url'])) {
            wp_send_json_error('No streaming URL received');
        }
        
        wp_send_json_success($data);
    }
    
    /**
     * Create or update content from backend data
     */
    private function create_or_update_content($content_type, $content_data) {
        // Check if post already exists by backend ID or TMDb ID
        $existing_post = null;
        
        if (!empty($content_data['backend_id'])) {
            $existing_posts = get_posts(array(
                'meta_key' => '_netflix_backend_id',
                'meta_value' => $content_data['backend_id'],
                'post_type' => array('movie', 'tv_show', 'episode'),
                'numberposts' => 1,
            ));
            
            if (!empty($existing_posts)) {
                $existing_post = $existing_posts[0];
            }
        }
        
        if (!$existing_post && !empty($content_data['tmdb_id'])) {
            $existing_posts = get_posts(array(
                'meta_key' => '_netflix_tmdb_id',
                'meta_value' => $content_data['tmdb_id'],
                'post_type' => array('movie', 'tv_show', 'episode'),
                'numberposts' => 1,
            ));
            
            if (!empty($existing_posts)) {
                $existing_post = $existing_posts[0];
            }
        }
        
        // Prepare post data
        $post_data = array(
            'post_title' => sanitize_text_field($content_data['title']),
            'post_content' => wp_kses_post($content_data['description'] ?? ''),
            'post_excerpt' => sanitize_text_field($content_data['synopsis'] ?? ''),
            'post_type' => $content_type,
            'post_status' => 'publish',
        );
        
        if ($existing_post) {
            $post_data['ID'] = $existing_post->ID;
            $post_id = wp_update_post($post_data);
        } else {
            $post_id = wp_insert_post($post_data);
        }
        
        if (is_wp_error($post_id)) {
            return $post_id;
        }
        
        // Update meta data
        $meta_fields = array(
            'backend_id', 'video_url', 'trailer_url', 'duration', 'imdb_id', 'tmdb_id',
            'director', 'creator', 'cast', 'subtitles', 'season', 'episode_number',
            'seasons', 'tv_show_id', 'premium_only', 'release_date', 'budget',
            'revenue', 'runtime', 'vote_average', 'vote_count'
        );
        
        foreach ($meta_fields as $field) {
            if (isset($content_data[$field])) {
                update_post_meta($post_id, '_netflix_' . $field, $content_data[$field]);
            }
        }
        
        // Set featured image from poster URL
        if (!empty($content_data['poster_url']) && !has_post_thumbnail($post_id)) {
            $this->set_featured_image_from_url($post_id, $content_data['poster_url']);
        }
        
        // Set taxonomies
        if (!empty($content_data['genres'])) {
            $this->set_content_terms($post_id, 'genre', $content_data['genres']);
        }
        
        if (!empty($content_data['release_year'])) {
            $this->set_content_terms($post_id, 'release_year', array($content_data['release_year']));
        }
        
        if (!empty($content_data['content_rating'])) {
            $this->set_content_terms($post_id, 'content_rating', array($content_data['content_rating']));
        }
        
        return $post_id;
    }
    
    /**
     * Set featured image from URL
     */
    private function set_featured_image_from_url($post_id, $image_url) {
        $upload_dir = wp_upload_dir();
        $image_data = file_get_contents($image_url);
        
        if ($image_data === false) {
            return false;
        }
        
        $filename = basename($image_url);
        if (empty($filename)) {
            $filename = 'poster-' . $post_id . '.jpg';
        }
        
        $file = $upload_dir['path'] . '/' . $filename;
        file_put_contents($file, $image_data);
        
        $wp_filetype = wp_check_filetype($filename, null);
        $attachment = array(
            'post_mime_type' => $wp_filetype['type'],
            'post_title' => sanitize_file_name($filename),
            'post_content' => '',
            'post_status' => 'inherit'
        );
        
        $attach_id = wp_insert_attachment($attachment, $file, $post_id);
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $file);
        wp_update_attachment_metadata($attach_id, $attach_data);
        
        set_post_thumbnail($post_id, $attach_id);
        
        return $attach_id;
    }
    
    /**
     * Set content terms for taxonomies
     */
    private function set_content_terms($post_id, $taxonomy, $terms) {
        if (!is_array($terms)) {
            $terms = array($terms);
        }
        
        $term_ids = array();
        foreach ($terms as $term_name) {
            $term = get_term_by('name', $term_name, $taxonomy);
            if (!$term) {
                $term_result = wp_insert_term($term_name, $taxonomy);
                if (!is_wp_error($term_result)) {
                    $term_ids[] = $term_result['term_id'];
                }
            } else {
                $term_ids[] = $term->term_id;
            }
        }
        
        if (!empty($term_ids)) {
            wp_set_post_terms($post_id, $term_ids, $taxonomy);
        }
    }
}

// Initialize backend integration
new Netflix_Backend_Integration();

/**
 * AJAX handlers for frontend
 */

/**
 * Add to My List
 */
function netflix_add_to_list() {
    check_ajax_referer('netflix_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('You must be logged in');
    }
    
    $user_id = get_current_user_id();
    $post_id = intval($_POST['post_id']);
    
    $my_list = get_user_meta($user_id, 'netflix_my_list', true);
    if (!is_array($my_list)) {
        $my_list = array();
    }
    
    if (!in_array($post_id, $my_list)) {
        $my_list[] = $post_id;
        update_user_meta($user_id, 'netflix_my_list', $my_list);
        wp_send_json_success('Added to My List');
    } else {
        wp_send_json_error('Already in My List');
    }
}
add_action('wp_ajax_netflix_add_to_list', 'netflix_add_to_list');

/**
 * Remove from My List
 */
function netflix_remove_from_list() {
    check_ajax_referer('netflix_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('You must be logged in');
    }
    
    $user_id = get_current_user_id();
    $post_id = intval($_POST['post_id']);
    
    $my_list = get_user_meta($user_id, 'netflix_my_list', true);
    if (is_array($my_list)) {
        $key = array_search($post_id, $my_list);
        if ($key !== false) {
            unset($my_list[$key]);
            update_user_meta($user_id, 'netflix_my_list', array_values($my_list));
            wp_send_json_success('Removed from My List');
        }
    }
    
    wp_send_json_error('Not in My List');
}
add_action('wp_ajax_netflix_remove_from_list', 'netflix_remove_from_list');

/**
 * Track video analytics
 */
function netflix_track_analytics() {
    check_ajax_referer('netflix_nonce', 'nonce');
    
    $event = sanitize_text_field($_POST['event']);
    $post_id = intval($_POST['post_id']);
    $additional_data = $_POST['data'] ?? array();
    
    // Update view count
    if ($event === 'video_play') {
        $views = get_post_meta($post_id, '_netflix_views', true);
        $views = intval($views) + 1;
        update_post_meta($post_id, '_netflix_views', $views);
        
        // Update user watch history
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            $watch_history = get_user_meta($user_id, 'netflix_watch_history', true);
            if (!is_array($watch_history)) {
                $watch_history = array();
            }
            
            $watch_history[$post_id] = array(
                'timestamp' => current_time('timestamp'),
                'progress' => $additional_data['progress'] ?? 0,
            );
            
            update_user_meta($user_id, 'netflix_watch_history', $watch_history);
        }
    }
    
    // You can expand this to send data to backend analytics
    
    wp_send_json_success('Analytics tracked');
}
add_action('wp_ajax_netflix_track_analytics', 'netflix_track_analytics');
add_action('wp_ajax_nopriv_netflix_track_analytics', 'netflix_track_analytics');

/**
 * Search content
 */
function netflix_search_content() {
    check_ajax_referer('netflix_nonce', 'nonce');
    
    $query = sanitize_text_field($_POST['query']);
    $type = sanitize_text_field($_POST['type'] ?? 'all');
    
    $post_types = array('movie', 'tv_show');
    if ($type !== 'all') {
        $post_types = array($type);
    }
    
    $args = array(
        'post_type' => $post_types,
        'posts_per_page' => 20,
        's' => $query,
        'post_status' => 'publish',
    );
    
    $search_query = new WP_Query($args);
    
    $results = array();
    if ($search_query->have_posts()) {
        while ($search_query->have_posts()) {
            $search_query->the_post();
            $results[] = array(
                'id' => get_the_ID(),
                'title' => get_the_title(),
                'type' => get_post_type(),
                'url' => get_permalink(),
                'poster' => get_the_post_thumbnail_url(get_the_ID(), 'netflix-thumb'),
            );
        }
        wp_reset_postdata();
    }
    
    wp_send_json_success($results);
}
add_action('wp_ajax_netflix_search_content', 'netflix_search_content');
add_action('wp_ajax_nopriv_netflix_search_content', 'netflix_search_content');