<?php
/**
 * API Handler Class
 * Handles all external API integrations (TMDB, Wikipedia, IMDb, YouTube)
 */

if (!defined('ABSPATH')) {
    exit;
}

class AutoPost_Movies_API_Handler {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('autopost_movies_cron_hook', array($this, 'process_movies'));
    }
    
    /**
     * Get TMDB API key
     */
    private function get_tmdb_api_key() {
        return get_option('autopost_movies_tmdb_api_key', '');
    }
    
    /**
     * Get YouTube API key
     */
    private function get_youtube_api_key() {
        return get_option('autopost_movies_youtube_api_key', '');
    }
    
    /**
     * Fetch popular movies from TMDB
     */
    public function fetch_popular_movies($page = 1) {
        $api_key = $this->get_tmdb_api_key();
        if (empty($api_key)) {
            AutoPost_Movies::log('error', 'TMDB API key not configured');
            return false;
        }
        
        $url = "https://api.themoviedb.org/3/movie/popular?api_key={$api_key}&page={$page}";
        $cache_key = 'autopost_movies_popular_' . $page;
        
        // Check cache first
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }
        
        $response = wp_remote_get($url, array('timeout' => 30));
        
        if (is_wp_error($response)) {
            AutoPost_Movies::log('error', 'TMDB API request failed: ' . $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data || !isset($data['results'])) {
            AutoPost_Movies::log('error', 'Invalid TMDB API response', $body);
            return false;
        }
        
        // Cache for 6 hours
        set_transient($cache_key, $data, 6 * HOUR_IN_SECONDS);
        
        AutoPost_Movies::log('tmdb', 'Fetched popular movies page ' . $page, $data);
        
        return $data;
    }
    
    /**
     * Fetch popular TV series from TMDB
     */
    public function fetch_popular_tv($page = 1) {
        $api_key = $this->get_tmdb_api_key();
        if (empty($api_key)) {
            AutoPost_Movies::log('error', 'TMDB API key not configured');
            return false;
        }
        
        $url = "https://api.themoviedb.org/3/tv/popular?api_key={$api_key}&page={$page}";
        $cache_key = 'autopost_movies_tv_popular_' . $page;
        
        // Check cache first
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }
        
        $response = wp_remote_get($url, array('timeout' => 30));
        
        if (is_wp_error($response)) {
            AutoPost_Movies::log('error', 'TMDB TV API request failed: ' . $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data || !isset($data['results'])) {
            AutoPost_Movies::log('error', 'Invalid TMDB TV API response', $body);
            return false;
        }
        
        // Cache for 6 hours
        set_transient($cache_key, $data, 6 * HOUR_IN_SECONDS);
        
        AutoPost_Movies::log('tmdb', 'Fetched popular TV series page ' . $page, $data);
        
        return $data;
    }
    
    /**
     * Get movie/TV details from TMDB
     */
    public function get_tmdb_details($tmdb_id, $type = 'movie') {
        $api_key = $this->get_tmdb_api_key();
        if (empty($api_key)) {
            return false;
        }
        
        $url = "https://api.themoviedb.org/3/{$type}/{$tmdb_id}?api_key={$api_key}&append_to_response=videos,external_ids";
        $cache_key = "autopost_movies_details_{$type}_{$tmdb_id}";
        
        // Check cache first
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }
        
        $response = wp_remote_get($url, array('timeout' => 30));
        
        if (is_wp_error($response)) {
            AutoPost_Movies::log('error', "TMDB details API request failed for {$type} {$tmdb_id}: " . $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data || isset($data['status_code'])) {
            AutoPost_Movies::log('error', "Invalid TMDB details response for {$type} {$tmdb_id}", $body);
            return false;
        }
        
        // Cache for 24 hours
        set_transient($cache_key, $data, 24 * HOUR_IN_SECONDS);
        
        AutoPost_Movies::log('tmdb', "Fetched {$type} details for ID {$tmdb_id}", $data);
        
        return $data;
    }
    
    /**
     * Get Wikipedia summary
     */
    public function get_wikipedia_summary($title) {
        if (!get_option('autopost_movies_wikipedia_enabled')) {
            return false;
        }
        
        $cache_key = 'autopost_movies_wiki_' . md5($title);
        
        // Check cache first
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }
        
        $encoded_title = urlencode($title);
        $url = "https://en.wikipedia.org/api/rest_v1/page/summary/{$encoded_title}";
        
        $response = wp_remote_get($url, array(
            'timeout' => 15,
            'headers' => array(
                'User-Agent' => 'AutoPost Movies WordPress Plugin/1.0.0'
            )
        ));
        
        if (is_wp_error($response)) {
            AutoPost_Movies::log('error', 'Wikipedia API request failed: ' . $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data || isset($data['type']) && $data['type'] === 'disambiguation') {
            AutoPost_Movies::log('wikipedia', "Wikipedia summary not found or ambiguous for: {$title}");
            return false;
        }
        
        $summary = isset($data['extract']) ? $data['extract'] : false;
        
        // Cache for 7 days
        set_transient($cache_key, $summary, 7 * DAY_IN_SECONDS);
        
        if ($summary) {
            AutoPost_Movies::log('wikipedia', "Fetched Wikipedia summary for: {$title}");
        }
        
        return $summary;
    }
    
    /**
     * Search YouTube for trailer
     */
    public function search_youtube_trailer($movie_title, $year = null) {
        $api_key = $this->get_youtube_api_key();
        if (empty($api_key)) {
            return false;
        }
        
        $search_query = $movie_title . ' trailer';
        if ($year) {
            $search_query .= ' ' . $year;
        }
        
        $cache_key = 'autopost_movies_youtube_' . md5($search_query);
        
        // Check cache first
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }
        
        $url = "https://www.googleapis.com/youtube/v3/search?" . http_build_query(array(
            'part' => 'snippet',
            'q' => $search_query,
            'type' => 'video',
            'maxResults' => 1,
            'key' => $api_key
        ));
        
        $response = wp_remote_get($url, array('timeout' => 15));
        
        if (is_wp_error($response)) {
            AutoPost_Movies::log('error', 'YouTube API request failed: ' . $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data || !isset($data['items']) || empty($data['items'])) {
            AutoPost_Movies::log('youtube', "No YouTube trailer found for: {$search_query}");
            return false;
        }
        
        $video_id = $data['items'][0]['id']['videoId'];
        $trailer_url = "https://www.youtube.com/watch?v={$video_id}";
        
        // Cache for 30 days
        set_transient($cache_key, $trailer_url, 30 * DAY_IN_SECONDS);
        
        AutoPost_Movies::log('youtube', "Found YouTube trailer for: {$search_query}", $trailer_url);
        
        return $trailer_url;
    }
    
    /**
     * Process movies for auto-posting
     */
    public function process_movies() {
        global $wpdb;
        
        $max_posts = intval(get_option('autopost_movies_max_posts_per_run', 5));
        
        // Fetch popular movies
        $movies_data = $this->fetch_popular_movies();
        if ($movies_data && isset($movies_data['results'])) {
            $this->store_movies($movies_data['results'], 'movie');
        }
        
        // Fetch popular TV series
        $tv_data = $this->fetch_popular_tv();
        if ($tv_data && isset($tv_data['results'])) {
            $this->store_movies($tv_data['results'], 'tv');
        }
        
        // Process pending entries
        $table_name = $wpdb->prefix . 'autopost_movies';
        $pending_items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE status = 'pending' ORDER BY created_at ASC LIMIT %d",
                $max_posts
            )
        );
        
        foreach ($pending_items as $item) {
            $this->process_single_item($item);
        }
    }
    
    /**
     * Store movies/TV series in database
     */
    private function store_movies($items, $type) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'autopost_movies';
        
        foreach ($items as $item) {
            // Check if already exists
            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM {$table_name} WHERE tmdb_id = %d",
                    $item['id']
                )
            );
            
            if (!$exists) {
                $title = $type === 'movie' ? $item['title'] : $item['name'];
                $release_date = $type === 'movie' ? $item['release_date'] : $item['first_air_date'];
                $poster_url = !empty($item['poster_path']) ? 'https://image.tmdb.org/t/p/w500' . $item['poster_path'] : null;
                
                $wpdb->insert(
                    $table_name,
                    array(
                        'tmdb_id' => $item['id'],
                        'title' => $title,
                        'type' => $type,
                        'release_date' => $release_date,
                        'poster_url' => $poster_url,
                        'plot' => $item['overview'],
                        'status' => 'pending'
                    ),
                    array('%d', '%s', '%s', '%s', '%s', '%s', '%s')
                );
                
                AutoPost_Movies::log('tmdb', "Stored new {$type}: {$title} (TMDB ID: {$item['id']})");
            }
        }
    }
    
    /**
     * Process a single item for posting
     */
    private function process_single_item($item) {
        global $wpdb;
        
        try {
            // Get detailed information from TMDB
            $details = $this->get_tmdb_details($item->tmdb_id, $item->type);
            if (!$details) {
                throw new Exception('Failed to fetch TMDB details');
            }
            
            // Get additional info if enabled
            $wikipedia_summary = '';
            if (get_option('autopost_movies_wikipedia_enabled')) {
                $wikipedia_summary = $this->get_wikipedia_summary($item->title);
            }
            
            // Get trailer URL
            $trailer_url = '';
            if (!empty($details['videos']['results'])) {
                foreach ($details['videos']['results'] as $video) {
                    if ($video['type'] === 'Trailer' && $video['site'] === 'YouTube') {
                        $trailer_url = "https://www.youtube.com/watch?v={$video['key']}";
                        break;
                    }
                }
            }
            
            if (empty($trailer_url)) {
                $year = !empty($item->release_date) ? date('Y', strtotime($item->release_date)) : null;
                $trailer_url = $this->search_youtube_trailer($item->title, $year);
            }
            
            // Get IMDb ID if available
            $imdb_id = '';
            if (isset($details['external_ids']['imdb_id'])) {
                $imdb_id = $details['external_ids']['imdb_id'];
            }
            
            // Update the database record with additional info
            $wpdb->update(
                $wpdb->prefix . 'autopost_movies',
                array(
                    'imdb_id' => $imdb_id,
                    'trailer_url' => $trailer_url,
                    'status' => 'pending'
                ),
                array('id' => $item->id),
                array('%s', '%s', '%s'),
                array('%d')
            );
            
            // Create the post
            $post_creator = new AutoPost_Movies_Post_Creator();
            $post_id = $post_creator->create_post($item, $details, $wikipedia_summary, $trailer_url);
            
            if ($post_id) {
                // Update status to posted
                $wpdb->update(
                    $wpdb->prefix . 'autopost_movies',
                    array('status' => 'posted', 'post_id' => $post_id),
                    array('id' => $item->id),
                    array('%s', '%d'),
                    array('%d')
                );
                
                AutoPost_Movies::log('post_creation', "Successfully created post for: {$item->title} (Post ID: {$post_id})");
            } else {
                throw new Exception('Failed to create post');
            }
            
        } catch (Exception $e) {
            // Update status to error
            $wpdb->update(
                $wpdb->prefix . 'autopost_movies',
                array('status' => 'error'),
                array('id' => $item->id),
                array('%s'),
                array('%d')
            );
            
            AutoPost_Movies::log('error', "Failed to process {$item->title}: " . $e->getMessage(), $item);
        }
    }
    
    /**
     * Manual search for movies/TV by TMDB ID
     */
    public function search_by_tmdb_id($tmdb_id, $type = 'movie') {
        $details = $this->get_tmdb_details($tmdb_id, $type);
        if (!$details) {
            return false;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'autopost_movies';
        
        // Check if already exists
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table_name} WHERE tmdb_id = %d",
                $tmdb_id
            )
        );
        
        if ($exists) {
            return array('error' => 'Movie/TV series already exists in database');
        }
        
        // Store the item
        $title = $type === 'movie' ? $details['title'] : $details['name'];
        $release_date = $type === 'movie' ? $details['release_date'] : $details['first_air_date'];
        $poster_url = !empty($details['poster_path']) ? 'https://image.tmdb.org/t/p/w500' . $details['poster_path'] : null;
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'tmdb_id' => $tmdb_id,
                'title' => $title,
                'type' => $type,
                'release_date' => $release_date,
                'poster_url' => $poster_url,
                'plot' => $details['overview'],
                'status' => 'pending'
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result) {
            AutoPost_Movies::log('tmdb', "Manually added {$type}: {$title} (TMDB ID: {$tmdb_id})");
            return array('success' => true, 'title' => $title);
        } else {
            return array('error' => 'Failed to store in database');
        }
    }
}