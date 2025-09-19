<?php
/**
 * TMDB API Integration
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// TMDB API base URL
define('MTCM_TMDB_API_BASE', 'https://api.themoviedb.org/3');
define('MTCM_TMDB_IMAGE_BASE', 'https://image.tmdb.org/t/p/');

/**
 * Search TMDB for movies or TV shows
 */
function mtcm_search_tmdb($query, $type = 'movie') {
    $api_key = get_option('mtcm_tmdb_api_key', '');
    
    if (empty($api_key)) {
        return array('error' => __('TMDB API key not configured', 'movie-tv-classic-manager'));
    }
    
    $cache_key = 'mtcm_tmdb_search_' . md5($query . $type);
    $cached_result = get_transient($cache_key);
    
    if ($cached_result !== false && get_option('mtcm_cache_tmdb_data', '1') === '1') {
        return $cached_result;
    }
    
    $url = MTCM_TMDB_API_BASE . '/search/' . $type . '?api_key=' . $api_key . '&query=' . urlencode($query);
    
    $response = wp_remote_get($url, array(
        'timeout' => 15,
        'headers' => array(
            'User-Agent' => 'Movie TV Classic Manager/' . MTCM_VERSION
        )
    ));
    
    if (is_wp_error($response)) {
        return array('error' => $response->get_error_message());
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    if ($response_code !== 200) {
        return array('error' => sprintf(__('TMDB API error: %d', 'movie-tv-classic-manager'), $response_code));
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return array('error' => __('Invalid JSON response from TMDB', 'movie-tv-classic-manager'));
    }
    
    $results = array();
    if (isset($data['results']) && is_array($data['results'])) {
        foreach ($data['results'] as $item) {
            $result = array(
                'id' => $item['id'],
                'title' => $type === 'movie' ? $item['title'] : $item['name'],
                'overview' => $item['overview'],
                'poster_path' => $item['poster_path'],
                'backdrop_path' => $item['backdrop_path'],
                'vote_average' => $item['vote_average'],
                'vote_count' => $item['vote_count'],
            );
            
            if ($type === 'movie') {
                $result['release_date'] = $item['release_date'] ?? '';
            } else {
                $result['first_air_date'] = $item['first_air_date'] ?? '';
            }
            
            $results[] = $result;
        }
    }
    
    // Cache results for 1 hour
    if (get_option('mtcm_cache_tmdb_data', '1') === '1') {
        set_transient($cache_key, $results, HOUR_IN_SECONDS);
    }
    
    return $results;
}

/**
 * Get detailed information for a specific movie or TV show from TMDB
 */
function mtcm_get_tmdb_details($tmdb_id, $type = 'movie') {
    $api_key = get_option('mtcm_tmdb_api_key', '');
    
    if (empty($api_key)) {
        return array('error' => __('TMDB API key not configured', 'movie-tv-classic-manager'));
    }
    
    $cache_key = 'mtcm_tmdb_details_' . $type . '_' . $tmdb_id;
    $cached_result = get_transient($cache_key);
    
    if ($cached_result !== false && get_option('mtcm_cache_tmdb_data', '1') === '1') {
        return $cached_result;
    }
    
    $url = MTCM_TMDB_API_BASE . '/' . $type . '/' . $tmdb_id . '?api_key=' . $api_key . '&append_to_response=credits,external_ids';
    
    $response = wp_remote_get($url, array(
        'timeout' => 15,
        'headers' => array(
            'User-Agent' => 'Movie TV Classic Manager/' . MTCM_VERSION
        )
    ));
    
    if (is_wp_error($response)) {
        return array('error' => $response->get_error_message());
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    if ($response_code !== 200) {
        return array('error' => sprintf(__('TMDB API error: %d', 'movie-tv-classic-manager'), $response_code));
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return array('error' => __('Invalid JSON response from TMDB', 'movie-tv-classic-manager'));
    }
    
    $details = mtcm_parse_tmdb_details($data, $type);
    
    // Cache results for 24 hours
    if (get_option('mtcm_cache_tmdb_data', '1') === '1') {
        set_transient($cache_key, $details, DAY_IN_SECONDS);
    }
    
    return $details;
}

/**
 * Parse TMDB API response into standardized format
 */
function mtcm_parse_tmdb_details($data, $type) {
    $details = array(
        'tmdb_id' => $data['id'],
        'title' => $type === 'movie' ? $data['title'] : $data['name'],
        'overview' => $data['overview'],
        'poster_path' => $data['poster_path'],
        'backdrop_path' => $data['backdrop_path'],
        'vote_average' => $data['vote_average'],
        'vote_count' => $data['vote_count'],
        'popularity' => $data['popularity'],
        'original_language' => $data['original_language'],
        'genres' => array(),
        'production_countries' => array(),
        'cast' => array(),
        'crew' => array(),
    );
    
    // Add poster and backdrop URLs
    if (!empty($data['poster_path'])) {
        $details['poster_url'] = MTCM_TMDB_IMAGE_BASE . 'w500' . $data['poster_path'];
        $details['poster_url_original'] = MTCM_TMDB_IMAGE_BASE . 'original' . $data['poster_path'];
    }
    
    if (!empty($data['backdrop_path'])) {
        $details['backdrop_url'] = MTCM_TMDB_IMAGE_BASE . 'w1280' . $data['backdrop_path'];
        $details['backdrop_url_original'] = MTCM_TMDB_IMAGE_BASE . 'original' . $data['backdrop_path'];
    }
    
    // Parse genres
    if (isset($data['genres']) && is_array($data['genres'])) {
        foreach ($data['genres'] as $genre) {
            $details['genres'][] = $genre['name'];
        }
    }
    
    // Parse production countries
    if (isset($data['production_countries']) && is_array($data['production_countries'])) {
        foreach ($data['production_countries'] as $country) {
            $details['production_countries'][] = $country['name'];
        }
    }
    
    // Parse cast and crew
    if (isset($data['credits'])) {
        if (isset($data['credits']['cast']) && is_array($data['credits']['cast'])) {
            foreach (array_slice($data['credits']['cast'], 0, 10) as $cast_member) {
                $details['cast'][] = $cast_member['name'];
            }
        }
        
        if (isset($data['credits']['crew']) && is_array($data['credits']['crew'])) {
            foreach ($data['credits']['crew'] as $crew_member) {
                if ($crew_member['job'] === 'Director' || $crew_member['department'] === 'Directing') {
                    $details['crew'][] = array(
                        'name' => $crew_member['name'],
                        'job' => $crew_member['job']
                    );
                }
            }
        }
    }
    
    // External IDs
    if (isset($data['external_ids'])) {
        $details['imdb_id'] = $data['external_ids']['imdb_id'] ?? '';
    }
    
    // Movie-specific data
    if ($type === 'movie') {
        $details['release_date'] = $data['release_date'] ?? '';
        $details['runtime'] = $data['runtime'] ?? 0;
        $details['budget'] = $data['budget'] ?? 0;
        $details['revenue'] = $data['revenue'] ?? 0;
        $details['tagline'] = $data['tagline'] ?? '';
        
        // Find director from crew
        $directors = array();
        if (isset($data['credits']['crew'])) {
            foreach ($data['credits']['crew'] as $crew_member) {
                if ($crew_member['job'] === 'Director') {
                    $directors[] = $crew_member['name'];
                }
            }
        }
        $details['director'] = implode(', ', $directors);
    }
    
    // TV show-specific data
    if ($type === 'tv') {
        $details['first_air_date'] = $data['first_air_date'] ?? '';
        $details['last_air_date'] = $data['last_air_date'] ?? '';
        $details['number_of_seasons'] = $data['number_of_seasons'] ?? 0;
        $details['number_of_episodes'] = $data['number_of_episodes'] ?? 0;
        $details['episode_run_time'] = !empty($data['episode_run_time']) ? $data['episode_run_time'][0] : 0;
        $details['status'] = $data['status'] ?? '';
        $details['type'] = $data['type'] ?? '';
        
        // Networks
        $networks = array();
        if (isset($data['networks']) && is_array($data['networks'])) {
            foreach ($data['networks'] as $network) {
                $networks[] = $network['name'];
            }
        }
        $details['networks'] = implode(', ', $networks);
        
        // Find creators from crew
        $creators = array();
        if (isset($data['created_by']) && is_array($data['created_by'])) {
            foreach ($data['created_by'] as $creator) {
                $creators[] = $creator['name'];
            }
        }
        $details['creator'] = implode(', ', $creators);
    }
    
    $details['last_updated'] = current_time('mysql');
    
    return $details;
}

/**
 * Import TMDB data into a WordPress post
 */
function mtcm_import_tmdb_data($tmdb_id, $type = 'movie', $post_id = null) {
    $details = mtcm_get_tmdb_details($tmdb_id, $type);
    
    if (isset($details['error'])) {
        return $details;
    }
    
    // Create or update post
    $post_data = array(
        'post_title' => $details['title'],
        'post_content' => $details['overview'],
        'post_type' => $type === 'movie' ? 'mtcm_movie' : 'mtcm_tv_show',
        'post_status' => 'draft', // Start as draft for manual review
    );
    
    if ($post_id) {
        $post_data['ID'] = $post_id;
        $result = wp_update_post($post_data);
    } else {
        $result = wp_insert_post($post_data);
    }
    
    if (is_wp_error($result)) {
        return array('error' => $result->get_error_message());
    }
    
    $post_id = $result;
    
    // Update meta fields
    update_post_meta($post_id, '_mtcm_tmdb_id', $details['tmdb_id']);
    update_post_meta($post_id, '_mtcm_tmdb_data', $details);
    
    if ($type === 'movie') {
        update_post_meta($post_id, '_mtcm_release_date', $details['release_date']);
        update_post_meta($post_id, '_mtcm_runtime', $details['runtime']);
        update_post_meta($post_id, '_mtcm_director', $details['director']);
        update_post_meta($post_id, '_mtcm_budget', $details['budget']);
        update_post_meta($post_id, '_mtcm_revenue', $details['revenue']);
        update_post_meta($post_id, '_mtcm_tagline', $details['tagline']);
    } else {
        update_post_meta($post_id, '_mtcm_first_air_date', $details['first_air_date']);
        update_post_meta($post_id, '_mtcm_last_air_date', $details['last_air_date']);
        update_post_meta($post_id, '_mtcm_total_seasons', $details['number_of_seasons']);
        update_post_meta($post_id, '_mtcm_total_episodes', $details['number_of_episodes']);
        update_post_meta($post_id, '_mtcm_episode_runtime', $details['episode_run_time']);
        update_post_meta($post_id, '_mtcm_creator', $details['creator']);
        update_post_meta($post_id, '_mtcm_network', $details['networks']);
        update_post_meta($post_id, '_mtcm_status', $details['status']);
    }
    
    // Common fields
    update_post_meta($post_id, '_mtcm_cast', implode(', ', $details['cast']));
    update_post_meta($post_id, '_mtcm_imdb_id', $details['imdb_id']);
    update_post_meta($post_id, '_mtcm_country', implode(', ', $details['production_countries']));
    update_post_meta($post_id, '_mtcm_language', $details['original_language']);
    
    // Set poster and backdrop URLs
    if (!empty($details['poster_url'])) {
        update_post_meta($post_id, '_mtcm_poster_url', $details['poster_url']);
        
        // Auto-fetch poster if enabled
        if (get_option('mtcm_auto_fetch_poster', '1') === '1') {
            mtcm_set_featured_image_from_url($post_id, $details['poster_url']);
        }
    }
    
    if (!empty($details['backdrop_url'])) {
        update_post_meta($post_id, '_mtcm_backdrop_url', $details['backdrop_url']);
    }
    
    // Set genres
    if (!empty($details['genres'])) {
        $genre_ids = array();
        foreach ($details['genres'] as $genre_name) {
            $term = get_term_by('name', $genre_name, 'mtcm_genre');
            if (!$term) {
                $term = wp_insert_term($genre_name, 'mtcm_genre');
                if (!is_wp_error($term)) {
                    $genre_ids[] = $term['term_id'];
                }
            } else {
                $genre_ids[] = $term->term_id;
            }
        }
        if (!empty($genre_ids)) {
            wp_set_post_terms($post_id, $genre_ids, 'mtcm_genre');
        }
    }
    
    // Set release year
    $year = '';
    if ($type === 'movie' && !empty($details['release_date'])) {
        $year = date('Y', strtotime($details['release_date']));
    } elseif ($type === 'tv' && !empty($details['first_air_date'])) {
        $year = date('Y', strtotime($details['first_air_date']));
    }
    
    if (!empty($year)) {
        $year_term = get_term_by('name', $year, 'mtcm_year');
        if (!$year_term) {
            $year_term = wp_insert_term($year, 'mtcm_year');
            if (!is_wp_error($year_term)) {
                wp_set_post_terms($post_id, array($year_term['term_id']), 'mtcm_year');
            }
        } else {
            wp_set_post_terms($post_id, array($year_term->term_id), 'mtcm_year');
        }
    }
    
    return array(
        'success' => true,
        'post_id' => $post_id,
        'message' => sprintf(__('Successfully imported %s from TMDB', 'movie-tv-classic-manager'), $details['title'])
    );
}

/**
 * Get TMDB configuration
 */
function mtcm_get_tmdb_configuration() {
    $api_key = get_option('mtcm_tmdb_api_key', '');
    
    if (empty($api_key)) {
        return false;
    }
    
    $cache_key = 'mtcm_tmdb_configuration';
    $config = get_transient($cache_key);
    
    if ($config !== false) {
        return $config;
    }
    
    $url = MTCM_TMDB_API_BASE . '/configuration?api_key=' . $api_key;
    
    $response = wp_remote_get($url, array(
        'timeout' => 10,
        'headers' => array(
            'User-Agent' => 'Movie TV Classic Manager/' . MTCM_VERSION
        )
    ));
    
    if (is_wp_error($response)) {
        return false;
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    if ($response_code !== 200) {
        return false;
    }
    
    $body = wp_remote_retrieve_body($response);
    $config = json_decode($body, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return false;
    }
    
    // Cache configuration for 7 days
    set_transient($cache_key, $config, 7 * DAY_IN_SECONDS);
    
    return $config;
}

/**
 * Get full image URL from TMDB path
 */
function mtcm_get_tmdb_image_url($path, $size = 'w500') {
    if (empty($path)) {
        return '';
    }
    
    return MTCM_TMDB_IMAGE_BASE . $size . $path;
}

/**
 * Test TMDB API connection
 */
function mtcm_test_tmdb_connection($api_key = null) {
    if ($api_key === null) {
        $api_key = get_option('mtcm_tmdb_api_key', '');
    }
    
    if (empty($api_key)) {
        return false;
    }
    
    $url = MTCM_TMDB_API_BASE . '/configuration?api_key=' . $api_key;
    
    $response = wp_remote_get($url, array(
        'timeout' => 10,
        'headers' => array(
            'User-Agent' => 'Movie TV Classic Manager/' . MTCM_VERSION
        )
    ));
    
    if (is_wp_error($response)) {
        return false;
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    return $response_code === 200;
}

/**
 * Clear TMDB cache
 */
function mtcm_clear_tmdb_cache() {
    global $wpdb;
    
    $wpdb->query(
        "DELETE FROM {$wpdb->options} 
         WHERE option_name LIKE '_transient_mtcm_tmdb_%' 
         OR option_name LIKE '_transient_timeout_mtcm_tmdb_%'"
    );
    
    return true;
}

/**
 * Get available poster sizes from TMDB configuration
 */
function mtcm_get_tmdb_poster_sizes() {
    $config = mtcm_get_tmdb_configuration();
    
    if (!$config || !isset($config['images']['poster_sizes'])) {
        return array('w154', 'w185', 'w342', 'w500', 'w780', 'original');
    }
    
    return $config['images']['poster_sizes'];
}

/**
 * Get available backdrop sizes from TMDB configuration
 */
function mtcm_get_tmdb_backdrop_sizes() {
    $config = mtcm_get_tmdb_configuration();
    
    if (!$config || !isset($config['images']['backdrop_sizes'])) {
        return array('w300', 'w780', 'w1280', 'original');
    }
    
    return $config['images']['backdrop_sizes'];
}