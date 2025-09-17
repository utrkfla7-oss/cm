<?php
/**
 * CinemaBot Pro API
 * 
 * Handles REST API endpoints for the chatbot and content management
 */

class CinemaBotPro_API {
    
    private $namespace;
    private $version;
    
    public function __construct() {
        $this->namespace = 'cinemabotpro';
        $this->version = 'v1';
        
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        $full_namespace = $this->namespace . '/' . $this->version;
        
        // Chat endpoints
        register_rest_route($full_namespace, '/chat', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_chat'),
            'permission_callback' => '__return_true',
            'args' => array(
                'message' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => array($this, 'validate_message')
                ),
                'language' => array(
                    'required' => false,
                    'type' => 'string',
                    'default' => 'en',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'context' => array(
                    'required' => false,
                    'type' => 'string',
                    'default' => '',
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));
        
        // Avatar endpoints
        register_rest_route($full_namespace, '/avatar/current', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_current_avatar'),
            'permission_callback' => '__return_true'
        ));
        
        register_rest_route($full_namespace, '/avatar/(?P<id>[a-zA-Z0-9_-]+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_avatar'),
            'permission_callback' => '__return_true',
            'args' => array(
                'id' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));
        
        register_rest_route($full_namespace, '/avatar/set', array(
            'methods' => 'POST',
            'callback' => array($this, 'set_avatar'),
            'permission_callback' => '__return_true',
            'args' => array(
                'avatar_id' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));
        
        register_rest_route($full_namespace, '/avatars', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_avatars'),
            'permission_callback' => '__return_true',
            'args' => array(
                'context' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));
        
        // Search endpoints
        register_rest_route($full_namespace, '/search', array(
            'methods' => 'GET',
            'callback' => array($this, 'search_content'),
            'permission_callback' => '__return_true',
            'args' => array(
                'q' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => array($this, 'validate_search_query')
                ),
                'type' => array(
                    'required' => false,
                    'type' => 'string',
                    'default' => 'all',
                    'enum' => array('all', 'movie', 'tv_show'),
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'limit' => array(
                    'required' => false,
                    'type' => 'integer',
                    'default' => 10,
                    'minimum' => 1,
                    'maximum' => 50
                ),
                'year' => array(
                    'required' => false,
                    'type' => 'integer',
                    'minimum' => 1900,
                    'maximum' => date('Y') + 2
                ),
                'genre' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));
        
        // Content endpoints
        register_rest_route($full_namespace, '/content/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_content'),
            'permission_callback' => '__return_true',
            'args' => array(
                'id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'minimum' => 1
                )
            )
        ));
        
        register_rest_route($full_namespace, '/content/(?P<id>\d+)/similar', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_similar_content'),
            'permission_callback' => '__return_true',
            'args' => array(
                'id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'minimum' => 1
                ),
                'limit' => array(
                    'required' => false,
                    'type' => 'integer',
                    'default' => 5,
                    'minimum' => 1,
                    'maximum' => 20
                )
            )
        ));
        
        // Recommendations endpoints
        register_rest_route($full_namespace, '/recommendations', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_recommendations'),
            'permission_callback' => '__return_true',
            'args' => array(
                'type' => array(
                    'required' => false,
                    'type' => 'string',
                    'default' => 'all',
                    'enum' => array('all', 'movie', 'tv_show'),
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'limit' => array(
                    'required' => false,
                    'type' => 'integer',
                    'default' => 10,
                    'minimum' => 1,
                    'maximum' => 50
                )
            )
        ));
        
        // User preferences endpoints
        register_rest_route($full_namespace, '/preferences', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_user_preferences'),
            'permission_callback' => array($this, 'check_user_permission')
        ));
        
        register_rest_route($full_namespace, '/preferences', array(
            'methods' => 'POST',
            'callback' => array($this, 'update_user_preferences'),
            'permission_callback' => array($this, 'check_user_permission'),
            'args' => array(
                'preferences' => array(
                    'required' => true,
                    'type' => 'object'
                )
            )
        ));
        
        // Analytics endpoints (admin only)
        register_rest_route($full_namespace, '/analytics', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_analytics'),
            'permission_callback' => array($this, 'check_admin_permission'),
            'args' => array(
                'period' => array(
                    'required' => false,
                    'type' => 'string',
                    'default' => '7days',
                    'enum' => array('24hours', '7days', '30days', '90days')
                )
            )
        ));
        
        // Language endpoints
        register_rest_route($full_namespace, '/language', array(
            'methods' => 'POST',
            'callback' => array($this, 'set_language'),
            'permission_callback' => '__return_true',
            'args' => array(
                'language' => array(
                    'required' => true,
                    'type' => 'string',
                    'enum' => array('en', 'bn', 'hi', 'banglish'),
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));
        
        // Feedback endpoints
        register_rest_route($full_namespace, '/feedback', array(
            'methods' => 'POST',
            'callback' => array($this, 'submit_feedback'),
            'permission_callback' => '__return_true',
            'args' => array(
                'type' => array(
                    'required' => true,
                    'type' => 'string',
                    'enum' => array('rating', 'comment', 'report'),
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'value' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field'
                ),
                'session_id' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));
        
        // Health check endpoint
        register_rest_route($full_namespace, '/health', array(
            'methods' => 'GET',
            'callback' => array($this, 'health_check'),
            'permission_callback' => '__return_true'
        ));
    }
    
    /**
     * Handle chat request
     */
    public function handle_chat($request) {
        $message = $request->get_param('message');
        $language = $request->get_param('language');
        $context = $request->get_param('context');
        
        try {
            // Initialize chatbot
            $chatbot = new CinemaBotPro_Chatbot();
            
            // Process message
            $response_data = $chatbot->process_message_api($message, $language, $context);
            
            // Track analytics
            $analytics = new CinemaBotPro_Analytics();
            $analytics->track_chat_interaction($message, $response_data['response'], array(
                'language' => $language,
                'context' => $context,
                'response_time' => $response_data['response_time']
            ));
            
            return new WP_REST_Response(array(
                'success' => true,
                'data' => $response_data
            ), 200);
            
        } catch (Exception $e) {
            return new WP_Error('chat_error', $e->getMessage(), array('status' => 500));
        }
    }
    
    /**
     * Get current avatar
     */
    public function get_current_avatar($request) {
        $avatar_system = new CinemaBotPro_Avatar_System();
        $current_avatar = $avatar_system->get_current_avatar();
        
        return new WP_REST_Response(array(
            'success' => true,
            'data' => $current_avatar
        ), 200);
    }
    
    /**
     * Get specific avatar
     */
    public function get_avatar($request) {
        $avatar_id = $request->get_param('id');
        
        $avatar_system = new CinemaBotPro_Avatar_System();
        $avatar = $avatar_system->get_avatar_by_id($avatar_id);
        
        if ($avatar) {
            return new WP_REST_Response(array(
                'success' => true,
                'data' => $avatar
            ), 200);
        } else {
            return new WP_Error('avatar_not_found', 'Avatar not found', array('status' => 404));
        }
    }
    
    /**
     * Set avatar
     */
    public function set_avatar($request) {
        $avatar_id = $request->get_param('avatar_id');
        
        $avatar_system = new CinemaBotPro_Avatar_System();
        $success = $avatar_system->set_current_avatar($avatar_id);
        
        if ($success) {
            // Track avatar change
            $analytics = new CinemaBotPro_Analytics();
            $analytics->track_avatar_change('previous', $avatar_id);
            
            return new WP_REST_Response(array(
                'success' => true,
                'message' => 'Avatar updated successfully',
                'data' => $avatar_system->get_current_avatar()
            ), 200);
        } else {
            return new WP_Error('avatar_set_failed', 'Failed to set avatar', array('status' => 400));
        }
    }
    
    /**
     * Get avatars
     */
    public function get_avatars($request) {
        $context = $request->get_param('context');
        
        $avatar_system = new CinemaBotPro_Avatar_System();
        
        if ($context) {
            $avatars = $avatar_system->get_avatars_by_context($context);
        } else {
            $avatars = $avatar_system->get_available_avatars_list();
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'data' => $avatars,
            'count' => count($avatars)
        ), 200);
    }
    
    /**
     * Search content
     */
    public function search_content($request) {
        $query = $request->get_param('q');
        $type = $request->get_param('type');
        $limit = $request->get_param('limit');
        $year = $request->get_param('year');
        $genre = $request->get_param('genre');
        
        // Build filters
        $filters = array();
        if ($type !== 'all') {
            $filters['type'] = $type;
        }
        if ($year) {
            $filters['year'] = $year;
        }
        if ($genre) {
            $filters['genre'] = $genre;
        }
        
        try {
            $content_crawler = new CinemaBotPro_Content_Crawler();
            $results = $content_crawler->search_content($query, $filters);
            
            // Limit results
            $results = array_slice($results, 0, $limit);
            
            // Track search
            $analytics = new CinemaBotPro_Analytics();
            $analytics->track_search($query, count($results));
            
            return new WP_REST_Response(array(
                'success' => true,
                'data' => $results,
                'count' => count($results),
                'query' => $query,
                'filters' => $filters
            ), 200);
            
        } catch (Exception $e) {
            return new WP_Error('search_error', $e->getMessage(), array('status' => 500));
        }
    }
    
    /**
     * Get content details
     */
    public function get_content($request) {
        $content_id = $request->get_param('id');
        
        $post = get_post($content_id);
        
        if (!$post || !in_array($post->post_type, array('cbp_movie', 'cbp_tv_show'))) {
            return new WP_Error('content_not_found', 'Content not found', array('status' => 404));
        }
        
        $content_data = $this->format_content_response($post);
        
        return new WP_REST_Response(array(
            'success' => true,
            'data' => $content_data
        ), 200);
    }
    
    /**
     * Get similar content
     */
    public function get_similar_content($request) {
        $content_id = $request->get_param('id');
        $limit = $request->get_param('limit');
        
        $similar_content = $this->find_similar_content($content_id, $limit);
        
        return new WP_REST_Response(array(
            'success' => true,
            'data' => $similar_content,
            'count' => count($similar_content)
        ), 200);
    }
    
    /**
     * Get recommendations
     */
    public function get_recommendations($request) {
        $type = $request->get_param('type');
        $limit = $request->get_param('limit');
        
        try {
            $recommendations = $this->generate_recommendations($type, $limit);
            
            return new WP_REST_Response(array(
                'success' => true,
                'data' => $recommendations,
                'count' => count($recommendations),
                'type' => $type
            ), 200);
            
        } catch (Exception $e) {
            return new WP_Error('recommendations_error', $e->getMessage(), array('status' => 500));
        }
    }
    
    /**
     * Get user preferences
     */
    public function get_user_preferences($request) {
        $user_memory = new CinemaBotPro_User_Memory();
        $preferences = $user_memory->get_user_context();
        
        return new WP_REST_Response(array(
            'success' => true,
            'data' => $preferences
        ), 200);
    }
    
    /**
     * Update user preferences
     */
    public function update_user_preferences($request) {
        $preferences = $request->get_param('preferences');
        
        $user_memory = new CinemaBotPro_User_Memory();
        $success = true;
        
        foreach ($preferences as $key => $value) {
            $result = $user_memory->save_preference($key, $value);
            if (!$result) {
                $success = false;
            }
        }
        
        if ($success) {
            return new WP_REST_Response(array(
                'success' => true,
                'message' => 'Preferences updated successfully'
            ), 200);
        } else {
            return new WP_Error('preferences_update_failed', 'Failed to update preferences', array('status' => 500));
        }
    }
    
    /**
     * Get analytics (admin only)
     */
    public function get_analytics($request) {
        $period = $request->get_param('period');
        
        $analytics = new CinemaBotPro_Analytics();
        $analytics_data = $analytics->get_dashboard_analytics($period);
        
        return new WP_REST_Response(array(
            'success' => true,
            'data' => $analytics_data,
            'period' => $period
        ), 200);
    }
    
    /**
     * Set language
     */
    public function set_language($request) {
        $language = $request->get_param('language');
        
        // Save language preference
        $user_memory = new CinemaBotPro_User_Memory();
        $user_memory->save_preference('language', $language);
        
        // Track language switch
        $analytics = new CinemaBotPro_Analytics();
        $analytics->track_language_switch('previous', $language);
        
        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Language updated successfully',
            'language' => $language
        ), 200);
    }
    
    /**
     * Submit feedback
     */
    public function submit_feedback($request) {
        $type = $request->get_param('type');
        $value = $request->get_param('value');
        $session_id = $request->get_param('session_id');
        
        // Save feedback
        $feedback_data = array(
            'type' => $type,
            'value' => $value,
            'session_id' => $session_id,
            'user_id' => get_current_user_id(),
            'timestamp' => current_time('mysql'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
        );
        
        // Log as analytics event
        $analytics = new CinemaBotPro_Analytics();
        $analytics->log_event('feedback_submitted', $feedback_data);
        
        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Feedback submitted successfully'
        ), 200);
    }
    
    /**
     * Health check
     */
    public function health_check($request) {
        $health_data = array(
            'status' => 'healthy',
            'timestamp' => current_time('mysql'),
            'version' => CINEMABOTPRO_VERSION,
            'services' => array(
                'database' => $this->check_database_health(),
                'ai_service' => $this->check_ai_service_health(),
                'content_api' => $this->check_content_api_health()
            ),
            'performance' => array(
                'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
                'load_time' => round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 3) . 's'
            )
        );
        
        // Determine overall status
        $all_healthy = true;
        foreach ($health_data['services'] as $service => $status) {
            if (!$status) {
                $all_healthy = false;
                break;
            }
        }
        
        $health_data['status'] = $all_healthy ? 'healthy' : 'degraded';
        
        return new WP_REST_Response($health_data, $all_healthy ? 200 : 503);
    }
    
    /**
     * Format content response
     */
    private function format_content_response($post) {
        $meta = get_post_meta($post->ID);
        
        $content_data = array(
            'id' => $post->ID,
            'title' => $post->post_title,
            'description' => $post->post_content,
            'type' => $post->post_type === 'cbp_movie' ? 'movie' : 'tv_show',
            'year' => $meta['year'][0] ?? '',
            'rating' => array(
                'tmdb' => floatval($meta['vote_average'][0] ?? 0),
                'imdb' => floatval($meta['imdb_rating'][0] ?? 0),
                'vote_count' => intval($meta['vote_count'][0] ?? 0)
            ),
            'runtime' => $meta['runtime'][0] ?? '',
            'genres' => json_decode($meta['genres'][0] ?? '[]', true),
            'director' => $meta['director'][0] ?? '',
            'actors' => $meta['actors'][0] ?? '',
            'country' => $meta['country'][0] ?? '',
            'language' => $meta['language'][0] ?? '',
            'poster_url' => $this->get_poster_url($post->ID),
            'backdrop_url' => $this->get_backdrop_url($post->ID),
            'external_ids' => array(
                'tmdb' => $meta['tmdb_id'][0] ?? '',
                'imdb' => $meta['imdb_id'][0] ?? ''
            )
        );
        
        // Add TV show specific data
        if ($post->post_type === 'cbp_tv_show') {
            $content_data['seasons'] = intval($meta['number_of_seasons'][0] ?? 0);
            $content_data['episodes'] = intval($meta['number_of_episodes'][0] ?? 0);
            $content_data['status'] = $meta['status'][0] ?? '';
            $content_data['networks'] => $meta['networks'][0] ?? '';
        }
        
        return $content_data;
    }
    
    /**
     * Find similar content
     */
    private function find_similar_content($content_id, $limit) {
        $post = get_post($content_id);
        
        if (!$post) {
            return array();
        }
        
        // Get content genres
        $genres = wp_get_post_terms($post->ID, 'cbp_genre', array('fields' => 'slugs'));
        
        // Find similar content based on genres
        $args = array(
            'post_type' => $post->post_type,
            'post_status' => 'publish',
            'posts_per_page' => $limit * 2, // Get more to filter out current post
            'post__not_in' => array($content_id),
            'meta_query' => array(
                array(
                    'key' => 'vote_average',
                    'value' => 5.0,
                    'compare' => '>=',
                    'type' => 'DECIMAL'
                )
            )
        );
        
        if (!empty($genres)) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'cbp_genre',
                    'field' => 'slug',
                    'terms' => $genres,
                    'operator' => 'IN'
                )
            );
        }
        
        $similar_posts = get_posts($args);
        
        $similar_content = array();
        foreach (array_slice($similar_posts, 0, $limit) as $similar_post) {
            $similar_content[] = $this->format_content_response($similar_post);
        }
        
        return $similar_content;
    }
    
    /**
     * Generate recommendations
     */
    private function generate_recommendations($type, $limit) {
        $user_memory = new CinemaBotPro_User_Memory();
        $user_context = $user_memory->get_user_context();
        
        // Get user's favorite genres
        $favorite_genres = $user_context['favorite_genres'] ?? array();
        
        // Build query args
        $post_types = array();
        if ($type === 'all') {
            $post_types = array('cbp_movie', 'cbp_tv_show');
        } elseif ($type === 'movie') {
            $post_types = array('cbp_movie');
        } elseif ($type === 'tv_show') {
            $post_types = array('cbp_tv_show');
        }
        
        $args = array(
            'post_type' => $post_types,
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'meta_query' => array(
                array(
                    'key' => 'vote_average',
                    'value' => 7.0,
                    'compare' => '>=',
                    'type' => 'DECIMAL'
                )
            ),
            'orderby' => 'meta_value_num',
            'meta_key' => 'popularity',
            'order' => 'DESC'
        );
        
        // Add genre filter if user has preferences
        if (!empty($favorite_genres)) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'cbp_genre',
                    'field' => 'name',
                    'terms' => $favorite_genres,
                    'operator' => 'IN'
                )
            );
        }
        
        $recommended_posts = get_posts($args);
        
        $recommendations = array();
        foreach ($recommended_posts as $post) {
            $recommendation = $this->format_content_response($post);
            $recommendation['recommendation_score'] = $this->calculate_recommendation_score($post, $user_context);
            $recommendations[] = $recommendation;
        }
        
        // Sort by recommendation score
        usort($recommendations, function($a, $b) {
            return $b['recommendation_score'] <=> $a['recommendation_score'];
        });
        
        return $recommendations;
    }
    
    /**
     * Calculate recommendation score
     */
    private function calculate_recommendation_score($post, $user_context) {
        $score = 0;
        
        // Base score from rating
        $rating = floatval(get_post_meta($post->ID, 'vote_average', true) ?: 0);
        $score += $rating * 10;
        
        // Bonus for user's favorite genres
        if (!empty($user_context['favorite_genres'])) {
            $post_genres = wp_get_post_terms($post->ID, 'cbp_genre', array('fields' => 'names'));
            $genre_matches = array_intersect($user_context['favorite_genres'], $post_genres);
            $score += count($genre_matches) * 15;
        }
        
        // Bonus for popularity
        $popularity = floatval(get_post_meta($post->ID, 'popularity', true) ?: 0);
        $score += min($popularity / 10, 20); // Max 20 points for popularity
        
        // Bonus for recent content
        $year = intval(get_post_meta($post->ID, 'year', true) ?: 0);
        if ($year >= date('Y') - 2) {
            $score += 10;
        }
        
        return round($score, 2);
    }
    
    /**
     * Get poster URL
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
     * Get backdrop URL
     */
    private function get_backdrop_url($post_id) {
        $backdrop_path = get_post_meta($post_id, 'backdrop_path', true);
        if ($backdrop_path) {
            return 'https://image.tmdb.org/t/p/w1280' . $backdrop_path;
        }
        
        return '';
    }
    
    /**
     * Check database health
     */
    private function check_database_health() {
        global $wpdb;
        
        $result = $wpdb->get_var("SELECT 1");
        return $result === '1';
    }
    
    /**
     * Check AI service health
     */
    private function check_ai_service_health() {
        $ai_engine = new CinemaBotPro_AI_Engine();
        return $ai_engine->is_ai_available();
    }
    
    /**
     * Check content API health
     */
    private function check_content_api_health() {
        $tmdb_key = get_option('cinemabotpro_tmdb_api_key', '');
        return !empty($tmdb_key);
    }
    
    /**
     * Validate message
     */
    public function validate_message($value, $request, $param) {
        if (empty(trim($value))) {
            return new WP_Error('empty_message', 'Message cannot be empty');
        }
        
        if (strlen($value) > 500) {
            return new WP_Error('message_too_long', 'Message is too long (max 500 characters)');
        }
        
        return true;
    }
    
    /**
     * Validate search query
     */
    public function validate_search_query($value, $request, $param) {
        if (empty(trim($value))) {
            return new WP_Error('empty_query', 'Search query cannot be empty');
        }
        
        if (strlen($value) < 2) {
            return new WP_Error('query_too_short', 'Search query must be at least 2 characters');
        }
        
        if (strlen($value) > 100) {
            return new WP_Error('query_too_long', 'Search query is too long (max 100 characters)');
        }
        
        return true;
    }
    
    /**
     * Check user permission
     */
    public function check_user_permission($request) {
        return is_user_logged_in();
    }
    
    /**
     * Check admin permission
     */
    public function check_admin_permission($request) {
        return current_user_can('manage_options');
    }
}