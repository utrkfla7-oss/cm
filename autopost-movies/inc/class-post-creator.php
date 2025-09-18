<?php
/**
 * Post Creator for AutoPost Movies plugin
 * Handles creating WordPress posts from movie/TV data
 */

if (!defined('ABSPATH')) {
    exit;
}

class APM_Post_Creator {
    
    private $api_handler;
    private $logger;
    
    public function __construct() {
        $this->api_handler = new APM_API_Handler();
        $this->logger = new APM_Logger();
    }
    
    /**
     * Main function to fetch and create posts
     */
    public function fetch_and_create_posts() {
        $posts_per_run = get_option('apm_posts_per_run', 5);
        $created_posts = 0;
        $errors = array();
        
        $this->logger->log('info', 'Starting automated post creation', array('posts_per_run' => $posts_per_run));
        
        try {
            // Fetch upcoming movies
            $movies = $this->api_handler->get_upcoming_movies();
            if ($movies && isset($movies['results'])) {
                foreach ($movies['results'] as $movie) {
                    if ($created_posts >= $posts_per_run) {
                        break;
                    }
                    
                    if ($this->should_create_post($movie['id'], 'movie')) {
                        $result = $this->create_movie_post($movie);
                        if ($result['success']) {
                            $created_posts++;
                        } else {
                            $errors[] = $result['error'];
                        }
                    }
                }
            }
            
            // Fetch popular TV series if we haven't reached the limit
            if ($created_posts < $posts_per_run) {
                $tv_series = $this->api_handler->get_popular_tv();
                if ($tv_series && isset($tv_series['results'])) {
                    foreach ($tv_series['results'] as $tv) {
                        if ($created_posts >= $posts_per_run) {
                            break;
                        }
                        
                        if ($this->should_create_post($tv['id'], 'tv')) {
                            $result = $this->create_tv_post($tv);
                            if ($result['success']) {
                                $created_posts++;
                            } else {
                                $errors[] = $result['error'];
                            }
                        }
                    }
                }
            }
            
        } catch (Exception $e) {
            $this->logger->log('error', 'Exception during post creation', array('message' => $e->getMessage()));
            $errors[] = $e->getMessage();
        }
        
        $result = array(
            'created_posts' => $created_posts,
            'errors' => $errors,
            'message' => sprintf(__('Created %d posts', 'autopost-movies'), $created_posts)
        );
        
        $this->logger->log('info', 'Completed automated post creation', $result);
        
        return $result;
    }
    
    /**
     * Create a movie post
     */
    public function create_movie_post($movie_data) {
        try {
            // Get detailed movie information
            $details = $this->api_handler->get_movie_details($movie_data['id']);
            if (!$details) {
                return array('success' => false, 'error' => 'Failed to fetch movie details');
            }
            
            // Check if post already exists
            if ($this->post_exists($movie_data['id'], 'movie')) {
                return array('success' => false, 'error' => 'Post already exists');
            }
            
            // Prepare post data
            $post_title = $details['title'];
            $post_content = $this->build_post_content($details, 'movie');
            $post_excerpt = wp_trim_words($details['overview'], 30);
            
            // Create the post
            $post_data = array(
                'post_title' => $post_title,
                'post_content' => $post_content,
                'post_excerpt' => $post_excerpt,
                'post_status' => get_option('apm_post_status', 'publish'),
                'post_type' => 'post',
                'post_author' => 1,
                'meta_input' => array(
                    'apm_tmdb_id' => $details['id'],
                    'apm_media_type' => 'movie',
                    'apm_release_date' => $details['release_date'],
                    'apm_poster_url' => 'https://image.tmdb.org/t/p/w500' . $details['poster_path'],
                    'apm_backdrop_url' => 'https://image.tmdb.org/t/p/w1280' . $details['backdrop_path'],
                    'apm_tmdb_rating' => $details['vote_average'],
                    'apm_runtime' => $details['runtime'],
                    'apm_genres' => $this->format_genres($details['genres']),
                    'apm_year' => date('Y', strtotime($details['release_date']))
                )
            );
            
            // Add IMDb ID if available
            if (isset($details['external_ids']['imdb_id'])) {
                $post_data['meta_input']['apm_imdb_id'] = $details['external_ids']['imdb_id'];
            }
            
            // Add trailer URL if available
            $trailer_url = $this->api_handler->get_tmdb_trailer($details['videos']);
            if ($trailer_url) {
                $post_data['meta_input']['apm_trailer_url'] = $trailer_url;
            }
            
            // Set category if configured
            $category = get_option('apm_post_category');
            if ($category) {
                $post_data['post_category'] = array($category);
            }
            
            // Insert the post
            $post_id = wp_insert_post($post_data);
            
            if (is_wp_error($post_id)) {
                return array('success' => false, 'error' => $post_id->get_error_message());
            }
            
            // Set featured image if enabled and FIFU plugin is active
            if (get_option('apm_featured_image_enabled') && function_exists('fifu_dev_set_image')) {
                $poster_url = 'https://image.tmdb.org/t/p/w500' . $details['poster_path'];
                fifu_dev_set_image($post_id, $poster_url);
            }
            
            // Track the created post
            $this->track_created_post($details['id'], $post_id, 'movie');
            
            $this->logger->log('info', 'Successfully created movie post', array(
                'post_id' => $post_id,
                'tmdb_id' => $details['id'],
                'title' => $post_title
            ));
            
            return array('success' => true, 'post_id' => $post_id);
            
        } catch (Exception $e) {
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
    
    /**
     * Create a TV series post
     */
    public function create_tv_post($tv_data) {
        try {
            // Get detailed TV information
            $details = $this->api_handler->get_tv_details($tv_data['id']);
            if (!$details) {
                return array('success' => false, 'error' => 'Failed to fetch TV details');
            }
            
            // Check if post already exists
            if ($this->post_exists($tv_data['id'], 'tv')) {
                return array('success' => false, 'error' => 'Post already exists');
            }
            
            // Prepare post data
            $post_title = $details['name'];
            $post_content = $this->build_post_content($details, 'tv');
            $post_excerpt = wp_trim_words($details['overview'], 30);
            
            // Create the post
            $post_data = array(
                'post_title' => $post_title,
                'post_content' => $post_content,
                'post_excerpt' => $post_excerpt,
                'post_status' => get_option('apm_post_status', 'publish'),
                'post_type' => 'post',
                'post_author' => 1,
                'meta_input' => array(
                    'apm_tmdb_id' => $details['id'],
                    'apm_media_type' => 'tv',
                    'apm_first_air_date' => $details['first_air_date'],
                    'apm_poster_url' => 'https://image.tmdb.org/t/p/w500' . $details['poster_path'],
                    'apm_backdrop_url' => 'https://image.tmdb.org/t/p/w1280' . $details['backdrop_path'],
                    'apm_tmdb_rating' => $details['vote_average'],
                    'apm_seasons' => $details['number_of_seasons'],
                    'apm_episodes' => $details['number_of_episodes'],
                    'apm_genres' => $this->format_genres($details['genres']),
                    'apm_year' => date('Y', strtotime($details['first_air_date']))
                )
            );
            
            // Add IMDb ID if available
            if (isset($details['external_ids']['imdb_id'])) {
                $post_data['meta_input']['apm_imdb_id'] = $details['external_ids']['imdb_id'];
            }
            
            // Add trailer URL if available
            $trailer_url = $this->api_handler->get_tmdb_trailer($details['videos']);
            if ($trailer_url) {
                $post_data['meta_input']['apm_trailer_url'] = $trailer_url;
            }
            
            // Set category if configured
            $category = get_option('apm_post_category');
            if ($category) {
                $post_data['post_category'] = array($category);
            }
            
            // Insert the post
            $post_id = wp_insert_post($post_data);
            
            if (is_wp_error($post_id)) {
                return array('success' => false, 'error' => $post_id->get_error_message());
            }
            
            // Set featured image if enabled and FIFU plugin is active
            if (get_option('apm_featured_image_enabled') && function_exists('fifu_dev_set_image')) {
                $poster_url = 'https://image.tmdb.org/t/p/w500' . $details['poster_path'];
                fifu_dev_set_image($post_id, $poster_url);
            }
            
            // Track the created post
            $this->track_created_post($details['id'], $post_id, 'tv');
            
            $this->logger->log('info', 'Successfully created TV post', array(
                'post_id' => $post_id,
                'tmdb_id' => $details['id'],
                'title' => $post_title
            ));
            
            return array('success' => true, 'post_id' => $post_id);
            
        } catch (Exception $e) {
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
    
    /**
     * Build post content based on enabled data sources and content order
     */
    private function build_post_content($details, $type) {
        $content = array();
        $content_order = get_option('apm_content_order', 'plot_first');
        
        $plot_content = $this->build_plot_content($details, $type);
        $info_content = $this->build_info_content($details, $type);
        
        if ($content_order === 'plot_first') {
            $content[] = $plot_content;
            $content[] = $info_content;
        } else {
            $content[] = $info_content;
            $content[] = $plot_content;
        }
        
        // Add trailer button if available
        $trailer_url = $this->api_handler->get_tmdb_trailer($details['videos']);
        if ($trailer_url) {
            $content[] = '[apm_trailer_button url="' . $trailer_url . '"]';
        }
        
        return implode("\n\n", array_filter($content));
    }
    
    /**
     * Build plot content section
     */
    private function build_plot_content($details, $type) {
        $plot_parts = array();
        
        // TMDB plot
        if (get_option('apm_tmdb_plot_enabled') && !empty($details['overview'])) {
            $plot_parts[] = '<h3>' . __('Plot', 'autopost-movies') . '</h3>';
            $plot_parts[] = '<p>' . esc_html($details['overview']) . '</p>';
        }
        
        // Wikipedia info
        if (get_option('apm_wikipedia_plot_enabled')) {
            $title = $type === 'movie' ? $details['title'] : $details['name'];
            $wikipedia_info = $this->api_handler->get_wikipedia_info($title);
            if ($wikipedia_info) {
                $plot_parts[] = '[apm_wikipedia_info title="' . $title . '"]';
            }
        }
        
        // IMDb plot
        if (get_option('apm_imdb_plot_enabled') && isset($details['external_ids']['imdb_id'])) {
            $imdb_info = $this->api_handler->get_imdb_info($details['external_ids']['imdb_id']);
            if ($imdb_info && isset($imdb_info['Plot'])) {
                $plot_parts[] = '<h3>' . __('IMDb Plot', 'autopost-movies') . '</h3>';
                $plot_parts[] = '<p>' . esc_html($imdb_info['Plot']) . '</p>';
            }
        }
        
        return implode("\n", $plot_parts);
    }
    
    /**
     * Build info content section
     */
    private function build_info_content($details, $type) {
        $info_parts = array();
        
        $info_parts[] = '<h3>' . __('Information', 'autopost-movies') . '</h3>';
        $info_parts[] = '<ul>';
        
        if ($type === 'movie') {
            $info_parts[] = '<li><strong>' . __('Release Date:', 'autopost-movies') . '</strong> ' . date('F j, Y', strtotime($details['release_date'])) . '</li>';
            if ($details['runtime']) {
                $info_parts[] = '<li><strong>' . __('Runtime:', 'autopost-movies') . '</strong> ' . $details['runtime'] . ' ' . __('minutes', 'autopost-movies') . '</li>';
            }
        } else {
            $info_parts[] = '<li><strong>' . __('First Air Date:', 'autopost-movies') . '</strong> ' . date('F j, Y', strtotime($details['first_air_date'])) . '</li>';
            $info_parts[] = '<li><strong>' . __('Seasons:', 'autopost-movies') . '</strong> ' . $details['number_of_seasons'] . '</li>';
            $info_parts[] = '<li><strong>' . __('Episodes:', 'autopost-movies') . '</strong> ' . $details['number_of_episodes'] . '</li>';
        }
        
        $info_parts[] = '<li><strong>' . __('Rating:', 'autopost-movies') . '</strong> ' . $details['vote_average'] . '/10</li>';
        
        if (!empty($details['genres'])) {
            $genres = array_map(function($genre) { return $genre['name']; }, $details['genres']);
            $info_parts[] = '<li><strong>' . __('Genres:', 'autopost-movies') . '</strong> ' . implode(', ', $genres) . '</li>';
        }
        
        $info_parts[] = '</ul>';
        
        return implode("\n", $info_parts);
    }
    
    /**
     * Check if a post should be created (not a duplicate)
     */
    private function should_create_post($tmdb_id, $type) {
        return !$this->post_exists($tmdb_id, $type);
    }
    
    /**
     * Check if a post already exists for this TMDB ID
     */
    private function post_exists($tmdb_id, $type) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'apm_posts';
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE tmdb_id = %s AND media_type = %s",
            $tmdb_id,
            $type
        ));
        
        return $exists > 0;
    }
    
    /**
     * Track created post in database
     */
    private function track_created_post($tmdb_id, $post_id, $type) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'apm_posts';
        $wpdb->insert(
            $table_name,
            array(
                'tmdb_id' => $tmdb_id,
                'post_id' => $post_id,
                'media_type' => $type,
                'created_at' => current_time('mysql')
            ),
            array('%s', '%d', '%s', '%s')
        );
    }
    
    /**
     * Format genres array to string
     */
    private function format_genres($genres) {
        if (empty($genres)) {
            return '';
        }
        
        $genre_names = array_map(function($genre) { 
            return $genre['name']; 
        }, $genres);
        
        return implode(', ', $genre_names);
    }
}