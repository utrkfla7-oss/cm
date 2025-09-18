<?php
/**
 * API Handler for AutoPost Movies plugin
 * Handles TMDB, Wikipedia, IMDb, and YouTube API interactions
 */

if (!defined('ABSPATH')) {
    exit;
}

class APM_API_Handler {
    
    private $tmdb_api_key;
    private $youtube_api_key;
    private $imdb_api_key;
    private $logger;
    
    public function __construct() {
        $this->tmdb_api_key = get_option('apm_tmdb_api_key');
        $this->youtube_api_key = get_option('apm_youtube_api_key');
        $this->imdb_api_key = get_option('apm_imdb_api_key');
        $this->logger = new APM_Logger();
    }
    
    /**
     * Get upcoming popular movies from TMDB
     */
    public function get_upcoming_movies($page = 1) {
        if (empty($this->tmdb_api_key)) {
            $this->logger->log('error', 'TMDB API key not configured');
            return false;
        }
        
        $cache_key = 'apm_upcoming_movies_' . $page;
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $url = sprintf(
            'https://api.themoviedb.org/3/movie/upcoming?api_key=%s&language=en-US&page=%d',
            $this->tmdb_api_key,
            $page
        );
        
        $response = $this->make_request($url);
        
        if ($response) {
            set_transient($cache_key, $response, HOUR_IN_SECONDS);
            $this->logger->log('info', 'Successfully fetched upcoming movies from TMDB', array('page' => $page));
        }
        
        return $response;
    }
    
    /**
     * Get popular TV series from TMDB
     */
    public function get_popular_tv($page = 1) {
        if (empty($this->tmdb_api_key)) {
            $this->logger->log('error', 'TMDB API key not configured');
            return false;
        }
        
        $cache_key = 'apm_popular_tv_' . $page;
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $url = sprintf(
            'https://api.themoviedb.org/3/tv/popular?api_key=%s&language=en-US&page=%d',
            $this->tmdb_api_key,
            $page
        );
        
        $response = $this->make_request($url);
        
        if ($response) {
            set_transient($cache_key, $response, HOUR_IN_SECONDS);
            $this->logger->log('info', 'Successfully fetched popular TV series from TMDB', array('page' => $page));
        }
        
        return $response;
    }
    
    /**
     * Get detailed movie information from TMDB
     */
    public function get_movie_details($movie_id) {
        if (empty($this->tmdb_api_key)) {
            return false;
        }
        
        $cache_key = 'apm_movie_details_' . $movie_id;
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $url = sprintf(
            'https://api.themoviedb.org/3/movie/%d?api_key=%s&language=en-US&append_to_response=videos,credits,external_ids',
            $movie_id,
            $this->tmdb_api_key
        );
        
        $response = $this->make_request($url);
        
        if ($response) {
            set_transient($cache_key, $response, 6 * HOUR_IN_SECONDS);
            $this->logger->log('info', 'Successfully fetched movie details from TMDB', array('movie_id' => $movie_id));
        }
        
        return $response;
    }
    
    /**
     * Get detailed TV series information from TMDB
     */
    public function get_tv_details($tv_id) {
        if (empty($this->tmdb_api_key)) {
            return false;
        }
        
        $cache_key = 'apm_tv_details_' . $tv_id;
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $url = sprintf(
            'https://api.themoviedb.org/3/tv/%d?api_key=%s&language=en-US&append_to_response=videos,credits,external_ids',
            $tv_id,
            $this->tmdb_api_key
        );
        
        $response = $this->make_request($url);
        
        if ($response) {
            set_transient($cache_key, $response, 6 * HOUR_IN_SECONDS);
            $this->logger->log('info', 'Successfully fetched TV details from TMDB', array('tv_id' => $tv_id));
        }
        
        return $response;
    }
    
    /**
     * Get Wikipedia information (first paragraph)
     */
    public function get_wikipedia_info($title) {
        if (!get_option('apm_wikipedia_plot_enabled')) {
            return false;
        }
        
        $cache_key = 'apm_wikipedia_' . md5($title);
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        // First, search for the article
        $search_url = sprintf(
            'https://en.wikipedia.org/w/api.php?action=opensearch&search=%s&limit=1&namespace=0&format=json',
            urlencode($title)
        );
        
        $search_response = $this->make_request($search_url);
        
        if (!$search_response || empty($search_response[1])) {
            return false;
        }
        
        $page_title = $search_response[1][0];
        
        // Get the extract (first paragraph)
        $extract_url = sprintf(
            'https://en.wikipedia.org/w/api.php?action=query&format=json&titles=%s&prop=extracts&exintro=true&explaintext=true&exsectionformat=plain',
            urlencode($page_title)
        );
        
        $extract_response = $this->make_request($extract_url);
        
        if ($extract_response && isset($extract_response['query']['pages'])) {
            $pages = $extract_response['query']['pages'];
            $page = reset($pages);
            
            if (isset($page['extract']) && !empty($page['extract'])) {
                $extract = $page['extract'];
                set_transient($cache_key, $extract, 24 * HOUR_IN_SECONDS);
                $this->logger->log('info', 'Successfully fetched Wikipedia info', array('title' => $title));
                return $extract;
            }
        }
        
        return false;
    }
    
    /**
     * Get IMDb information
     */
    public function get_imdb_info($imdb_id) {
        if (empty($this->imdb_api_key) || !get_option('apm_imdb_plot_enabled')) {
            return false;
        }
        
        $cache_key = 'apm_imdb_' . $imdb_id;
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        // Using OMDb API as a free alternative to IMDb API
        $url = sprintf(
            'http://www.omdbapi.com/?i=%s&apikey=%s&plot=full',
            $imdb_id,
            $this->imdb_api_key
        );
        
        $response = $this->make_request($url);
        
        if ($response && isset($response['Response']) && $response['Response'] === 'True') {
            set_transient($cache_key, $response, 24 * HOUR_IN_SECONDS);
            $this->logger->log('info', 'Successfully fetched IMDb info', array('imdb_id' => $imdb_id));
            return $response;
        }
        
        return false;
    }
    
    /**
     * Get YouTube trailer URL
     */
    public function get_youtube_trailer($title, $year = null) {
        if (empty($this->youtube_api_key)) {
            return false;
        }
        
        $search_query = $title . ' trailer';
        if ($year) {
            $search_query .= ' ' . $year;
        }
        
        $cache_key = 'apm_youtube_' . md5($search_query);
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $url = sprintf(
            'https://www.googleapis.com/youtube/v3/search?part=snippet&q=%s&type=video&maxResults=1&key=%s',
            urlencode($search_query),
            $this->youtube_api_key
        );
        
        $response = $this->make_request($url);
        
        if ($response && isset($response['items']) && !empty($response['items'])) {
            $video = $response['items'][0];
            $video_url = 'https://www.youtube.com/watch?v=' . $video['id']['videoId'];
            
            set_transient($cache_key, $video_url, 24 * HOUR_IN_SECONDS);
            $this->logger->log('info', 'Successfully found YouTube trailer', array('title' => $title, 'url' => $video_url));
            
            return $video_url;
        }
        
        return false;
    }
    
    /**
     * Get trailer URL from TMDB videos
     */
    public function get_tmdb_trailer($videos) {
        if (empty($videos) || !isset($videos['results'])) {
            return false;
        }
        
        foreach ($videos['results'] as $video) {
            if ($video['type'] === 'Trailer' && $video['site'] === 'YouTube') {
                return 'https://www.youtube.com/watch?v=' . $video['key'];
            }
        }
        
        return false;
    }
    
    /**
     * Make HTTP request with error handling and retry logic
     */
    private function make_request($url, $max_retries = 3) {
        $retry_count = 0;
        
        while ($retry_count < $max_retries) {
            $response = wp_remote_get($url, array(
                'timeout' => 30,
                'user-agent' => 'AutoPost Movies WordPress Plugin'
            ));
            
            if (is_wp_error($response)) {
                $this->logger->log('error', 'HTTP request failed', array(
                    'url' => $url,
                    'error' => $response->get_error_message(),
                    'retry' => $retry_count + 1
                ));
                
                $retry_count++;
                
                if ($retry_count < $max_retries) {
                    sleep(pow(2, $retry_count)); // Exponential backoff
                }
                
                continue;
            }
            
            $body = wp_remote_retrieve_body($response);
            $status_code = wp_remote_retrieve_response_code($response);
            
            if ($status_code !== 200) {
                $this->logger->log('error', 'HTTP request returned error status', array(
                    'url' => $url,
                    'status_code' => $status_code,
                    'retry' => $retry_count + 1
                ));
                
                $retry_count++;
                
                if ($retry_count < $max_retries) {
                    sleep(pow(2, $retry_count));
                }
                
                continue;
            }
            
            $data = json_decode($body, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->log('error', 'Invalid JSON response', array(
                    'url' => $url,
                    'json_error' => json_last_error_msg(),
                    'retry' => $retry_count + 1
                ));
                
                $retry_count++;
                
                if ($retry_count < $max_retries) {
                    sleep(pow(2, $retry_count));
                }
                
                continue;
            }
            
            return $data;
        }
        
        $this->logger->log('error', 'Max retries exceeded for HTTP request', array('url' => $url));
        return false;
    }
    
    /**
     * Test API connections
     */
    public function test_apis() {
        $results = array();
        
        // Test TMDB
        if (!empty($this->tmdb_api_key)) {
            $test_url = 'https://api.themoviedb.org/3/configuration?api_key=' . $this->tmdb_api_key;
            $response = $this->make_request($test_url);
            $results['tmdb'] = $response !== false;
        } else {
            $results['tmdb'] = false;
        }
        
        // Test YouTube
        if (!empty($this->youtube_api_key)) {
            $test_url = 'https://www.googleapis.com/youtube/v3/search?part=snippet&q=test&maxResults=1&key=' . $this->youtube_api_key;
            $response = $this->make_request($test_url);
            $results['youtube'] = $response !== false;
        } else {
            $results['youtube'] = false;
        }
        
        // Test IMDb (OMDb)
        if (!empty($this->imdb_api_key)) {
            $test_url = 'http://www.omdbapi.com/?i=tt0111161&apikey=' . $this->imdb_api_key;
            $response = $this->make_request($test_url);
            $results['imdb'] = $response !== false && isset($response['Response']) && $response['Response'] === 'True';
        } else {
            $results['imdb'] = false;
        }
        
        // Test Wikipedia (no API key needed)
        $test_url = 'https://en.wikipedia.org/w/api.php?action=opensearch&search=test&limit=1&namespace=0&format=json';
        $response = $this->make_request($test_url);
        $results['wikipedia'] = $response !== false;
        
        return $results;
    }
}