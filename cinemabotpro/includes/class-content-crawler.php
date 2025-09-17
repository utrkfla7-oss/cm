<?php
/**
 * CinemaBot Pro Content Crawler
 * 
 * Handles real-time content crawling and database synchronization
 * from various movie and TV show databases.
 */

class CinemaBotPro_Content_Crawler {
    
    private $tmdb_api_key;
    private $omdb_api_key;
    private $crawl_enabled;
    private $crawl_interval;
    
    public function __construct() {
        $this->tmdb_api_key = get_option('cinemabotpro_tmdb_api_key', '');
        $this->omdb_api_key = get_option('cinemabotpro_omdb_api_key', '');
        $this->crawl_enabled = get_option('cinemabotpro_content_crawling', 1);
        $this->crawl_interval = get_option('cinemabotpro_crawl_interval', 'hourly');
        
        // Schedule content crawling
        if ($this->crawl_enabled && !wp_next_scheduled('cinemabotpro_content_crawl')) {
            wp_schedule_event(time(), $this->crawl_interval, 'cinemabotpro_content_crawl');
        }
        
        add_action('cinemabotpro_content_crawl', array($this, 'run_scheduled_crawl'));
        
        // AJAX handlers
        add_action('wp_ajax_cinemabotpro_search_content', array($this, 'handle_search_content_ajax'));
        add_action('wp_ajax_nopriv_cinemabotpro_search_content', array($this, 'handle_search_content_ajax'));
        add_action('wp_ajax_cinemabotpro_get_content_details', array($this, 'handle_get_content_details_ajax'));
        add_action('wp_ajax_nopriv_cinemabotpro_get_content_details', array($this, 'handle_get_content_details_ajax'));
        add_action('wp_ajax_cinemabotpro_manual_crawl', array($this, 'handle_manual_crawl_ajax'));
    }
    
    /**
     * Run scheduled content crawl
     */
    public function run_scheduled_crawl() {
        if (!$this->crawl_enabled) {
            return;
        }
        
        // Crawl trending content
        $this->crawl_trending_content();
        
        // Crawl popular content
        $this->crawl_popular_content();
        
        // Update existing content data
        $this->update_existing_content();
        
        // Clean up old cached data
        $this->cleanup_old_cache();
        
        // Log crawl completion
        $this->log_crawl_activity('scheduled_crawl_completed');
    }
    
    /**
     * Crawl trending content from TMDB
     */
    private function crawl_trending_content() {
        if (empty($this->tmdb_api_key)) {
            return;
        }
        
        $endpoints = array(
            'trending_movies' => 'https://api.themoviedb.org/3/trending/movie/week',
            'trending_tv' => 'https://api.themoviedb.org/3/trending/tv/week'
        );
        
        foreach ($endpoints as $type => $endpoint) {
            $data = $this->fetch_tmdb_data($endpoint);
            
            if ($data && isset($data['results'])) {
                foreach ($data['results'] as $item) {
                    $this->process_and_store_content($item, $type);
                }
            }
        }
    }
    
    /**
     * Crawl popular content
     */
    private function crawl_popular_content() {
        if (empty($this->tmdb_api_key)) {
            return;
        }
        
        $endpoints = array(
            'popular_movies' => 'https://api.themoviedb.org/3/movie/popular',
            'popular_tv' => 'https://api.themoviedb.org/3/tv/popular',
            'top_rated_movies' => 'https://api.themoviedb.org/3/movie/top_rated',
            'top_rated_tv' => 'https://api.themoviedb.org/3/tv/top_rated'
        );
        
        foreach ($endpoints as $type => $endpoint) {
            $data = $this->fetch_tmdb_data($endpoint, array('page' => 1));
            
            if ($data && isset($data['results'])) {
                foreach (array_slice($data['results'], 0, 20) as $item) {
                    $this->process_and_store_content($item, $type);
                }
            }
        }
    }
    
    /**
     * Fetch data from TMDB API
     */
    private function fetch_tmdb_data($endpoint, $params = array()) {
        $default_params = array(
            'api_key' => $this->tmdb_api_key,
            'language' => 'en-US'
        );
        
        $params = array_merge($default_params, $params);
        $url = $endpoint . '?' . http_build_query($params);
        
        $response = wp_remote_get($url, array(
            'timeout' => 15,
            'headers' => array(
                'User-Agent' => 'CinemaBot Pro WordPress Plugin'
            )
        ));
        
        if (is_wp_error($response)) {
            error_log('TMDB API Error: ' . $response->get_error_message());
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            error_log('TMDB API returned error code: ' . $response_code);
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }
    
    /**
     * Fetch data from OMDB API
     */
    private function fetch_omdb_data($title, $year = null, $type = null) {
        if (empty($this->omdb_api_key)) {
            return false;
        }
        
        $params = array(
            'apikey' => $this->omdb_api_key,
            't' => $title,
            'plot' => 'full'
        );
        
        if ($year) {
            $params['y'] = $year;
        }
        
        if ($type) {
            $params['type'] = $type;
        }
        
        $url = 'https://www.omdbapi.com/?' . http_build_query($params);
        
        $response = wp_remote_get($url, array(
            'timeout' => 15,
            'headers' => array(
                'User-Agent' => 'CinemaBot Pro WordPress Plugin'
            )
        ));
        
        if (is_wp_error($response)) {
            error_log('OMDB API Error: ' . $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['Response']) && $data['Response'] === 'False') {
            return false;
        }
        
        return $data;
    }
    
    /**
     * Process and store content data
     */
    private function process_and_store_content($tmdb_data, $source_type) {
        // Determine content type
        $content_type = $this->determine_content_type($tmdb_data, $source_type);
        
        // Extract basic data
        $basic_data = $this->extract_basic_data($tmdb_data, $content_type);
        
        // Get additional data from OMDB if available
        $omdb_data = null;
        if (!empty($basic_data['title'])) {
            $omdb_data = $this->fetch_omdb_data(
                $basic_data['title'], 
                $basic_data['year'], 
                $content_type === 'movie' ? 'movie' : 'series'
            );
        }
        
        // Merge data from both sources
        $combined_data = $this->merge_content_data($basic_data, $tmdb_data, $omdb_data);
        
        // Store or update content
        $this->store_content($combined_data);
        
        // Cache the data
        $this->cache_content_data($combined_data);
    }
    
    /**
     * Determine content type from TMDB data
     */
    private function determine_content_type($data, $source_type) {
        if (strpos($source_type, 'movie') !== false) {
            return 'movie';
        } elseif (strpos($source_type, 'tv') !== false) {
            return 'tv_show';
        } elseif (isset($data['media_type'])) {
            return $data['media_type'] === 'movie' ? 'movie' : 'tv_show';
        } elseif (isset($data['title'])) {
            return 'movie';
        } elseif (isset($data['name'])) {
            return 'tv_show';
        }
        
        return 'movie'; // Default
    }
    
    /**
     * Extract basic data from TMDB response
     */
    private function extract_basic_data($data, $content_type) {
        $basic = array(
            'tmdb_id' => $data['id'] ?? null,
            'content_type' => $content_type,
            'title' => $content_type === 'movie' ? ($data['title'] ?? '') : ($data['name'] ?? ''),
            'original_title' => $content_type === 'movie' ? ($data['original_title'] ?? '') : ($data['original_name'] ?? ''),
            'overview' => $data['overview'] ?? '',
            'poster_path' => $data['poster_path'] ?? '',
            'backdrop_path' => $data['backdrop_path'] ?? '',
            'genre_ids' => $data['genre_ids'] ?? array(),
            'popularity' => $data['popularity'] ?? 0,
            'vote_average' => $data['vote_average'] ?? 0,
            'vote_count' => $data['vote_count'] ?? 0,
            'adult' => $data['adult'] ?? false,
            'original_language' => $data['original_language'] ?? 'en'
        );
        
        // Date handling
        if ($content_type === 'movie') {
            $basic['release_date'] = $data['release_date'] ?? '';
            $basic['year'] = !empty($basic['release_date']) ? date('Y', strtotime($basic['release_date'])) : '';
        } else {
            $basic['first_air_date'] = $data['first_air_date'] ?? '';
            $basic['year'] = !empty($basic['first_air_date']) ? date('Y', strtotime($basic['first_air_date'])) : '';
        }
        
        return $basic;
    }
    
    /**
     * Merge data from multiple sources
     */
    private function merge_content_data($basic_data, $tmdb_data, $omdb_data) {
        $merged = $basic_data;
        
        // Add TMDB specific data
        $merged['tmdb_data'] = $tmdb_data;
        
        // Add OMDB data if available
        if ($omdb_data) {
            $merged['omdb_data'] = $omdb_data;
            $merged['imdb_id'] = $omdb_data['imdbID'] ?? '';
            $merged['imdb_rating'] = $omdb_data['imdbRating'] ?? '';
            $merged['metascore'] = $omdb_data['Metascore'] ?? '';
            $merged['rotten_tomatoes'] = $this->extract_rotten_tomatoes_rating($omdb_data);
            $merged['awards'] = $omdb_data['Awards'] ?? '';
            $merged['box_office'] = $omdb_data['BoxOffice'] ?? '';
            $merged['runtime'] = $omdb_data['Runtime'] ?? '';
            $merged['director'] = $omdb_data['Director'] ?? '';
            $merged['actors'] = $omdb_data['Actors'] ?? '';
            $merged['writer'] = $omdb_data['Writer'] ?? '';
            $merged['country'] = $omdb_data['Country'] ?? '';
            $merged['language'] = $omdb_data['Language'] ?? '';
            $merged['genre_text'] = $omdb_data['Genre'] ?? '';
            
            // Use OMDB plot if more detailed
            if (!empty($omdb_data['Plot']) && strlen($omdb_data['Plot']) > strlen($merged['overview'])) {
                $merged['overview'] = $omdb_data['Plot'];
            }
        }
        
        // Process genres
        $merged['genres'] = $this->process_genres($merged);
        
        // Generate keywords
        $merged['keywords'] = $this->generate_keywords($merged);
        
        // Add timestamps
        $merged['crawled_at'] = current_time('mysql');
        $merged['last_updated'] = current_time('mysql');
        
        return $merged;
    }
    
    /**
     * Extract Rotten Tomatoes rating from OMDB data
     */
    private function extract_rotten_tomatoes_rating($omdb_data) {
        if (empty($omdb_data['Ratings'])) {
            return '';
        }
        
        foreach ($omdb_data['Ratings'] as $rating) {
            if ($rating['Source'] === 'Rotten Tomatoes') {
                return $rating['Value'];
            }
        }
        
        return '';
    }
    
    /**
     * Process genres from multiple sources
     */
    private function process_genres($data) {
        $genres = array();
        
        // From OMDB text
        if (!empty($data['genre_text'])) {
            $omdb_genres = array_map('trim', explode(',', $data['genre_text']));
            $genres = array_merge($genres, $omdb_genres);
        }
        
        // From TMDB genre IDs (would need genre mapping)
        if (!empty($data['genre_ids'])) {
            $tmdb_genres = $this->map_genre_ids_to_names($data['genre_ids']);
            $genres = array_merge($genres, $tmdb_genres);
        }
        
        return array_unique(array_filter($genres));
    }
    
    /**
     * Map TMDB genre IDs to names
     */
    private function map_genre_ids_to_names($genre_ids) {
        $genre_map = array(
            28 => 'Action',
            12 => 'Adventure',
            16 => 'Animation',
            35 => 'Comedy',
            80 => 'Crime',
            99 => 'Documentary',
            18 => 'Drama',
            10751 => 'Family',
            14 => 'Fantasy',
            36 => 'History',
            27 => 'Horror',
            10402 => 'Music',
            9648 => 'Mystery',
            10749 => 'Romance',
            878 => 'Science Fiction',
            10770 => 'TV Movie',
            53 => 'Thriller',
            10752 => 'War',
            37 => 'Western',
            // TV Genres
            10759 => 'Action & Adventure',
            10762 => 'Kids',
            10763 => 'News',
            10764 => 'Reality',
            10765 => 'Sci-Fi & Fantasy',
            10766 => 'Soap',
            10767 => 'Talk',
            10768 => 'War & Politics'
        );
        
        $genres = array();
        foreach ($genre_ids as $id) {
            if (isset($genre_map[$id])) {
                $genres[] = $genre_map[$id];
            }
        }
        
        return $genres;
    }
    
    /**
     * Generate keywords for search
     */
    private function generate_keywords($data) {
        $keywords = array();
        
        // Title variations
        if (!empty($data['title'])) {
            $keywords[] = $data['title'];
            $keywords = array_merge($keywords, explode(' ', $data['title']));
        }
        
        if (!empty($data['original_title']) && $data['original_title'] !== $data['title']) {
            $keywords[] = $data['original_title'];
        }
        
        // Genres
        if (!empty($data['genres'])) {
            $keywords = array_merge($keywords, $data['genres']);
        }
        
        // Director and actors
        if (!empty($data['director'])) {
            $keywords = array_merge($keywords, explode(',', $data['director']));
        }
        
        if (!empty($data['actors'])) {
            $actors = array_slice(explode(',', $data['actors']), 0, 5); // Top 5 actors
            $keywords = array_merge($keywords, $actors);
        }
        
        // Year
        if (!empty($data['year'])) {
            $keywords[] = $data['year'];
        }
        
        // Clean and return
        $keywords = array_map('trim', $keywords);
        $keywords = array_filter($keywords);
        $keywords = array_unique($keywords);
        
        return array_values($keywords);
    }
    
    /**
     * Store content in WordPress
     */
    private function store_content($data) {
        // Check if content already exists
        $existing_post = $this->find_existing_content($data);
        
        if ($existing_post) {
            // Update existing post
            $this->update_existing_content_post($existing_post->ID, $data);
        } else {
            // Create new post
            $this->create_new_content_post($data);
        }
    }
    
    /**
     * Find existing content post
     */
    private function find_existing_content($data) {
        global $wpdb;
        
        $post_type = $data['content_type'] === 'movie' ? 'cbp_movie' : 'cbp_tv_show';
        
        // Search by TMDB ID first
        if (!empty($data['tmdb_id'])) {
            $meta_query = array(
                array(
                    'key' => 'tmdb_id',
                    'value' => $data['tmdb_id'],
                    'compare' => '='
                )
            );
            
            $posts = get_posts(array(
                'post_type' => $post_type,
                'meta_query' => $meta_query,
                'posts_per_page' => 1,
                'post_status' => 'any'
            ));
            
            if (!empty($posts)) {
                return $posts[0];
            }
        }
        
        // Search by title and year
        if (!empty($data['title']) && !empty($data['year'])) {
            $posts = get_posts(array(
                'post_type' => $post_type,
                'title' => $data['title'],
                'meta_query' => array(
                    array(
                        'key' => 'year',
                        'value' => $data['year'],
                        'compare' => '='
                    )
                ),
                'posts_per_page' => 1,
                'post_status' => 'any'
            ));
            
            if (!empty($posts)) {
                return $posts[0];
            }
        }
        
        return false;
    }
    
    /**
     * Create new content post
     */
    private function create_new_content_post($data) {
        $post_type = $data['content_type'] === 'movie' ? 'cbp_movie' : 'cbp_tv_show';
        
        $post_data = array(
            'post_title' => $data['title'],
            'post_content' => $data['overview'],
            'post_type' => $post_type,
            'post_status' => 'publish',
            'post_author' => 1,
            'meta_input' => $this->prepare_post_meta($data)
        );
        
        $post_id = wp_insert_post($post_data);
        
        if ($post_id && !is_wp_error($post_id)) {
            // Set featured image if poster available
            if (!empty($data['poster_path'])) {
                $this->set_featured_image($post_id, $data['poster_path']);
            }
            
            // Set taxonomies
            $this->set_content_taxonomies($post_id, $data);
            
            return $post_id;
        }
        
        return false;
    }
    
    /**
     * Update existing content post
     */
    private function update_existing_content_post($post_id, $data) {
        $post_data = array(
            'ID' => $post_id,
            'post_title' => $data['title'],
            'post_content' => $data['overview'],
            'meta_input' => $this->prepare_post_meta($data)
        );
        
        wp_update_post($post_data);
        
        // Update featured image if needed
        if (!empty($data['poster_path']) && !has_post_thumbnail($post_id)) {
            $this->set_featured_image($post_id, $data['poster_path']);
        }
        
        // Update taxonomies
        $this->set_content_taxonomies($post_id, $data);
        
        return $post_id;
    }
    
    /**
     * Prepare post meta data
     */
    private function prepare_post_meta($data) {
        $meta = array();
        
        $meta_fields = array(
            'tmdb_id', 'imdb_id', 'content_type', 'year', 'original_title',
            'release_date', 'first_air_date', 'runtime', 'vote_average',
            'vote_count', 'popularity', 'imdb_rating', 'metascore',
            'rotten_tomatoes', 'awards', 'box_office', 'director',
            'actors', 'writer', 'country', 'language', 'adult',
            'poster_path', 'backdrop_path', 'crawled_at', 'last_updated'
        );
        
        foreach ($meta_fields as $field) {
            if (isset($data[$field])) {
                $meta[$field] = $data[$field];
            }
        }
        
        // Store complex data as JSON
        if (isset($data['genres'])) {
            $meta['genres'] = wp_json_encode($data['genres']);
        }
        
        if (isset($data['keywords'])) {
            $meta['keywords'] = wp_json_encode($data['keywords']);
        }
        
        if (isset($data['tmdb_data'])) {
            $meta['tmdb_data'] = wp_json_encode($data['tmdb_data']);
        }
        
        if (isset($data['omdb_data'])) {
            $meta['omdb_data'] = wp_json_encode($data['omdb_data']);
        }
        
        return $meta;
    }
    
    /**
     * Set featured image from poster URL
     */
    private function set_featured_image($post_id, $poster_path) {
        if (empty($poster_path)) {
            return;
        }
        
        $image_url = 'https://image.tmdb.org/t/p/w500' . $poster_path;
        
        // Download and attach image
        $image_id = $this->download_and_attach_image($image_url, $post_id);
        
        if ($image_id) {
            set_post_thumbnail($post_id, $image_id);
        }
    }
    
    /**
     * Download and attach image to post
     */
    private function download_and_attach_image($image_url, $post_id) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $tmp = download_url($image_url);
        
        if (is_wp_error($tmp)) {
            return false;
        }
        
        $file_array = array(
            'name' => basename($image_url) . '.jpg',
            'tmp_name' => $tmp
        );
        
        $id = media_handle_sideload($file_array, $post_id);
        
        if (is_wp_error($id)) {
            @unlink($tmp);
            return false;
        }
        
        return $id;
    }
    
    /**
     * Set content taxonomies
     */
    private function set_content_taxonomies($post_id, $data) {
        // Set genres
        if (!empty($data['genres'])) {
            wp_set_object_terms($post_id, $data['genres'], 'cbp_genre');
        }
        
        // Set actors
        if (!empty($data['actors'])) {
            $actors = array_map('trim', explode(',', $data['actors']));
            wp_set_object_terms($post_id, array_slice($actors, 0, 10), 'cbp_actor');
        }
        
        // Set directors
        if (!empty($data['director'])) {
            $directors = array_map('trim', explode(',', $data['director']));
            wp_set_object_terms($post_id, $directors, 'cbp_director');
        }
        
        // Set year
        if (!empty($data['year'])) {
            wp_set_object_terms($post_id, array($data['year']), 'cbp_year');
        }
        
        // Set language
        if (!empty($data['language'])) {
            wp_set_object_terms($post_id, array($data['language']), 'cbp_language');
        }
    }
    
    /**
     * Cache content data
     */
    private function cache_content_data($data) {
        if (empty($data['tmdb_id'])) {
            return;
        }
        
        $cache_key = 'cinemabotpro_content_' . $data['tmdb_id'];
        set_transient($cache_key, $data, DAY_IN_SECONDS);
    }
    
    /**
     * Search content
     */
    public function search_content($query, $filters = array()) {
        $search_results = array();
        
        // Search local database first
        $local_results = $this->search_local_content($query, $filters);
        $search_results = array_merge($search_results, $local_results);
        
        // If not enough results, search external APIs
        if (count($search_results) < 10) {
            $external_results = $this->search_external_content($query, $filters);
            $search_results = array_merge($search_results, $external_results);
        }
        
        // Remove duplicates and limit results
        $search_results = $this->deduplicate_results($search_results);
        $search_results = array_slice($search_results, 0, 20);
        
        return $search_results;
    }
    
    /**
     * Search local content database
     */
    private function search_local_content($query, $filters) {
        $args = array(
            'post_type' => array('cbp_movie', 'cbp_tv_show'),
            's' => $query,
            'posts_per_page' => 10,
            'post_status' => 'publish'
        );
        
        // Add meta queries for filters
        if (!empty($filters)) {
            $args['meta_query'] = $this->build_meta_query($filters);
        }
        
        $posts = get_posts($args);
        $results = array();
        
        foreach ($posts as $post) {
            $results[] = $this->format_content_result($post);
        }
        
        return $results;
    }
    
    /**
     * Search external content APIs
     */
    private function search_external_content($query, $filters) {
        $results = array();
        
        // Search TMDB
        if (!empty($this->tmdb_api_key)) {
            $tmdb_results = $this->search_tmdb($query, $filters);
            $results = array_merge($results, $tmdb_results);
        }
        
        return $results;
    }
    
    /**
     * Search TMDB API
     */
    private function search_tmdb($query, $filters) {
        $endpoints = array(
            'https://api.themoviedb.org/3/search/movie',
            'https://api.themoviedb.org/3/search/tv'
        );
        
        $results = array();
        
        foreach ($endpoints as $endpoint) {
            $data = $this->fetch_tmdb_data($endpoint, array('query' => $query));
            
            if ($data && isset($data['results'])) {
                foreach ($data['results'] as $item) {
                    $results[] = $this->format_tmdb_result($item);
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Format content result from WordPress post
     */
    private function format_content_result($post) {
        $meta = get_post_meta($post->ID);
        
        return array(
            'id' => $post->ID,
            'title' => $post->post_title,
            'type' => get_post_meta($post->ID, 'content_type', true) ?: 'movie',
            'year' => get_post_meta($post->ID, 'year', true),
            'overview' => $post->post_content,
            'poster_url' => $this->get_poster_url($post->ID),
            'rating' => get_post_meta($post->ID, 'vote_average', true),
            'source' => 'local'
        );
    }
    
    /**
     * Format TMDB result
     */
    private function format_tmdb_result($item) {
        $content_type = isset($item['title']) ? 'movie' : 'tv_show';
        
        return array(
            'id' => $item['id'],
            'title' => $content_type === 'movie' ? $item['title'] : $item['name'],
            'type' => $content_type,
            'year' => $this->extract_year_from_date($content_type === 'movie' ? ($item['release_date'] ?? '') : ($item['first_air_date'] ?? '')),
            'overview' => $item['overview'] ?? '',
            'poster_url' => !empty($item['poster_path']) ? 'https://image.tmdb.org/t/p/w500' . $item['poster_path'] : '',
            'rating' => $item['vote_average'] ?? 0,
            'source' => 'tmdb'
        );
    }
    
    /**
     * Get poster URL for local content
     */
    private function get_poster_url($post_id) {
        if (has_post_thumbnail($post_id)) {
            return get_the_post_thumbnail_url($post_id, 'medium');
        }
        
        $poster_path = get_post_meta($post_id, 'poster_path', true);
        if ($poster_path) {
            return 'https://image.tmdb.org/t/p/w500' . $poster_path;
        }
        
        return '';
    }
    
    /**
     * Extract year from date string
     */
    private function extract_year_from_date($date) {
        if (empty($date)) {
            return '';
        }
        
        return date('Y', strtotime($date));
    }
    
    /**
     * Build meta query from filters
     */
    private function build_meta_query($filters) {
        $meta_query = array('relation' => 'AND');
        
        if (!empty($filters['year'])) {
            $meta_query[] = array(
                'key' => 'year',
                'value' => $filters['year'],
                'compare' => '='
            );
        }
        
        if (!empty($filters['type'])) {
            $meta_query[] = array(
                'key' => 'content_type',
                'value' => $filters['type'],
                'compare' => '='
            );
        }
        
        if (!empty($filters['min_rating'])) {
            $meta_query[] = array(
                'key' => 'vote_average',
                'value' => $filters['min_rating'],
                'compare' => '>=',
                'type' => 'NUMERIC'
            );
        }
        
        return $meta_query;
    }
    
    /**
     * Remove duplicate results
     */
    private function deduplicate_results($results) {
        $seen = array();
        $unique = array();
        
        foreach ($results as $result) {
            $key = strtolower($result['title']) . '_' . $result['year'];
            
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $unique[] = $result;
            }
        }
        
        return $unique;
    }
    
    /**
     * Update existing content data
     */
    private function update_existing_content() {
        // Get content that needs updating (older than 7 days)
        $cutoff_date = date('Y-m-d H:i:s', strtotime('-7 days'));
        
        $posts = get_posts(array(
            'post_type' => array('cbp_movie', 'cbp_tv_show'),
            'meta_query' => array(
                array(
                    'key' => 'last_updated',
                    'value' => $cutoff_date,
                    'compare' => '<'
                )
            ),
            'posts_per_page' => 50
        ));
        
        foreach ($posts as $post) {
            $tmdb_id = get_post_meta($post->ID, 'tmdb_id', true);
            if ($tmdb_id) {
                $this->update_content_by_tmdb_id($tmdb_id, $post->ID);
            }
        }
    }
    
    /**
     * Update content by TMDB ID
     */
    private function update_content_by_tmdb_id($tmdb_id, $post_id) {
        $content_type = get_post_meta($post_id, 'content_type', true);
        $endpoint = $content_type === 'movie' 
            ? "https://api.themoviedb.org/3/movie/$tmdb_id"
            : "https://api.themoviedb.org/3/tv/$tmdb_id";
        
        $data = $this->fetch_tmdb_data($endpoint);
        
        if ($data) {
            $this->process_and_store_content($data, $content_type);
        }
    }
    
    /**
     * Cleanup old cached data
     */
    private function cleanup_old_cache() {
        global $wpdb;
        
        // Remove old transients
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_cinemabotpro_content_%' 
            AND option_name NOT IN (
                SELECT option_name 
                FROM {$wpdb->options} 
                WHERE option_name LIKE '_transient_timeout_cinemabotpro_content_%' 
                AND option_value > UNIX_TIMESTAMP()
            )"
        );
    }
    
    /**
     * Log crawl activity
     */
    private function log_crawl_activity($activity_type, $details = '') {
        $log_data = array(
            'activity' => $activity_type,
            'details' => $details,
            'timestamp' => current_time('mysql')
        );
        
        // Save to transient for temporary logging
        $log_key = 'cinemabotpro_crawl_log_' . time();
        set_transient($log_key, $log_data, WEEK_IN_SECONDS);
    }
    
    /**
     * Handle search content AJAX
     */
    public function handle_search_content_ajax() {
        if (!wp_verify_nonce($_POST['nonce'], 'cinemabotpro_nonce')) {
            wp_die('Security check failed');
        }
        
        $query = sanitize_text_field($_POST['query']);
        $filters = array();
        
        if (!empty($_POST['type'])) {
            $filters['type'] = sanitize_text_field($_POST['type']);
        }
        
        if (!empty($_POST['year'])) {
            $filters['year'] = intval($_POST['year']);
        }
        
        if (!empty($_POST['min_rating'])) {
            $filters['min_rating'] = floatval($_POST['min_rating']);
        }
        
        $results = $this->search_content($query, $filters);
        
        wp_send_json_success(array(
            'results' => $results,
            'count' => count($results)
        ));
    }
    
    /**
     * Handle get content details AJAX
     */
    public function handle_get_content_details_ajax() {
        if (!wp_verify_nonce($_POST['nonce'], 'cinemabotpro_nonce')) {
            wp_die('Security check failed');
        }
        
        $content_id = intval($_POST['content_id']);
        $source = sanitize_text_field($_POST['source'] ?? 'local');
        
        if ($source === 'local') {
            $details = $this->get_local_content_details($content_id);
        } else {
            $details = $this->get_external_content_details($content_id, $source);
        }
        
        if ($details) {
            wp_send_json_success(array('details' => $details));
        } else {
            wp_send_json_error(array('message' => 'Content not found'));
        }
    }
    
    /**
     * Get local content details
     */
    private function get_local_content_details($post_id) {
        $post = get_post($post_id);
        
        if (!$post) {
            return false;
        }
        
        $meta = get_post_meta($post_id);
        $details = array(
            'title' => $post->post_title,
            'overview' => $post->post_content,
            'type' => get_post_meta($post_id, 'content_type', true),
            'year' => get_post_meta($post_id, 'year', true),
            'rating' => get_post_meta($post_id, 'vote_average', true),
            'imdb_rating' => get_post_meta($post_id, 'imdb_rating', true),
            'runtime' => get_post_meta($post_id, 'runtime', true),
            'director' => get_post_meta($post_id, 'director', true),
            'actors' => get_post_meta($post_id, 'actors', true),
            'genres' => json_decode(get_post_meta($post_id, 'genres', true) ?: '[]', true),
            'poster_url' => $this->get_poster_url($post_id)
        );
        
        return $details;
    }
    
    /**
     * Get external content details
     */
    private function get_external_content_details($content_id, $source) {
        if ($source === 'tmdb' && !empty($this->tmdb_api_key)) {
            // Determine if it's a movie or TV show by trying both endpoints
            $movie_data = $this->fetch_tmdb_data("https://api.themoviedb.org/3/movie/$content_id");
            if ($movie_data && !isset($movie_data['success']) || (isset($movie_data['success']) && $movie_data['success'] !== false)) {
                return $this->format_tmdb_details($movie_data, 'movie');
            }
            
            $tv_data = $this->fetch_tmdb_data("https://api.themoviedb.org/3/tv/$content_id");
            if ($tv_data && !isset($tv_data['success']) || (isset($tv_data['success']) && $tv_data['success'] !== false)) {
                return $this->format_tmdb_details($tv_data, 'tv');
            }
        }
        
        return false;
    }
    
    /**
     * Format TMDB details
     */
    private function format_tmdb_details($data, $type) {
        return array(
            'title' => $type === 'movie' ? $data['title'] : $data['name'],
            'overview' => $data['overview'] ?? '',
            'type' => $type === 'movie' ? 'movie' : 'tv_show',
            'year' => $this->extract_year_from_date($type === 'movie' ? ($data['release_date'] ?? '') : ($data['first_air_date'] ?? '')),
            'rating' => $data['vote_average'] ?? 0,
            'runtime' => $data['runtime'] ?? ($data['episode_run_time'][0] ?? ''),
            'genres' => array_column($data['genres'] ?? array(), 'name'),
            'poster_url' => !empty($data['poster_path']) ? 'https://image.tmdb.org/t/p/w500' . $data['poster_path'] : ''
        );
    }
    
    /**
     * Handle manual crawl AJAX
     */
    public function handle_manual_crawl_ajax() {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'cinemabotpro_nonce')) {
            wp_die('Security check failed');
        }
        
        // Run manual crawl
        $this->run_scheduled_crawl();
        
        wp_send_json_success(array(
            'message' => 'Manual crawl completed successfully'
        ));
    }
    
    /**
     * Get crawl statistics
     */
    public function get_crawl_statistics() {
        $stats = array(
            'total_movies' => wp_count_posts('cbp_movie')->publish,
            'total_tv_shows' => wp_count_posts('cbp_tv_show')->publish,
            'last_crawl' => get_option('cinemabotpro_last_crawl_time', 'Never'),
            'api_status' => array(
                'tmdb' => !empty($this->tmdb_api_key),
                'omdb' => !empty($this->omdb_api_key)
            ),
            'crawl_enabled' => $this->crawl_enabled
        );
        
        return $stats;
    }
}