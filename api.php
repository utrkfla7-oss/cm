<?php
// REST API for analytics, resume, feedback, download limit, favorites, etc.
add_action('rest_api_init', function() {
    // Analytics Events
    register_rest_route('cmplayer/v1', '/analytics', array(
        'methods' => 'POST',
        'callback' => 'cmplayer_save_analytics',
        'permission_callback' => '__return_true'
    ));
    // Resume Position
    register_rest_route('cmplayer/v1', '/resume', array(
        'methods' => 'POST',
        'callback' => 'cmplayer_save_resume',
        'permission_callback' => '__return_true'
    ));
    // Feedback (like/dislike/favorite/report)
    register_rest_route('cmplayer/v1', '/feedback', array(
        'methods' => 'POST',
        'callback' => 'cmplayer_save_feedback',
        'permission_callback' => '__return_true'
    ));
    // Download Limit
    register_rest_route('cmplayer/v1', '/download', array(
        'methods' => 'POST',
        'callback' => 'cmplayer_download_limit_check',
        'permission_callback' => '__return_true'
    ));
    // Reset Download Count (admin action)
    register_rest_route('cmplayer/v1', '/reset_download_count', array(
        'methods' => 'POST',
        'callback' => 'cmplayer_reset_download_count',
        'permission_callback' => function() {
            return current_user_can('manage_options');
        }
    ));
    // Get Favorites (for AJAX UI)
    register_rest_route('cmplayer/v1', '/favorites', array(
        'methods' => 'GET',
        'callback' => 'cmplayer_get_favorites',
        'permission_callback' => '__return_true'
    ));
    
    // TMDB Movie Search
    register_rest_route('cmplayer/v1', '/movies/search', array(
        'methods' => 'GET',
        'callback' => 'cmplayer_search_movies',
        'permission_callback' => '__return_true'
    ));
    
    // Get Movie Details
    register_rest_route('cmplayer/v1', '/movies/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'cmplayer_get_movie_details',
        'permission_callback' => '__return_true'
    ));
});

// Analytics event tracker
function cmplayer_save_analytics($request) {
    // Save to log file or DB if needed
    return array('status' => 'ok');
}

// Resume video position for user
function cmplayer_save_resume($request) {
    $user_id = get_current_user_id();
    if (!$user_id) return array('status'=>'error','message'=>'Login required');
    $params = $request->get_params();
    if (!empty($params['video']) && isset($params['position'])) {
        $resume = get_user_meta($user_id, 'cmplayer_resume', true);
        if (!is_array($resume)) $resume = [];
        $resume[$params['video']] = $params['position'];
        update_user_meta($user_id, 'cmplayer_resume', $resume);
        return array('status'=>'ok','message'=>'Resume position saved');
    }
    return array('status'=>'error','message'=>'Missing params');
}

// Feedback: like/dislike/favorite/report
function cmplayer_save_feedback($request) {
    $params = $request->get_params();
    $user_id = get_current_user_id();
    if (!$user_id) return array('status'=>'error','message'=>'Login required');
    // Favorite action
    if (!empty($params['action']) && $params['action'] == 'favorite') {
        $video = !empty($params['video']) ? $params['video'] : '';
        if ($video) {
            $favs = get_user_meta($user_id, 'cmplayer_favorites', true);
            if (!is_array($favs)) $favs = [];
            if (!in_array($video, $favs)) {
                $favs[] = $video;
                update_user_meta($user_id, 'cmplayer_favorites', $favs);
            }
            return array('status'=>'ok','message'=>'Added to favorites');
        }
    }
    // Remove favorite
    if (!empty($params['action']) && $params['action'] == 'unfavorite') {
        $video = !empty($params['video']) ? $params['video'] : '';
        if ($video) {
            $favs = get_user_meta($user_id, 'cmplayer_favorites', true);
            if (!is_array($favs)) $favs = [];
            $favs = array_diff($favs, array($video));
            update_user_meta($user_id, 'cmplayer_favorites', $favs);
            return array('status'=>'ok','message'=>'Removed from favorites');
        }
    }
    // Like/dislike (track if needed)
    if (!empty($params['action']) && ($params['action'] == 'like' || $params['action'] == 'dislike')) {
        // You can track per video/user if required
        return array('status'=>'ok');
    }
    // Report video
    if (!empty($params['type']) && $params['type'] == 'report') {
        // Save report if needed
        return array('status'=>'ok');
    }
    return array('status'=>'ok');
}

// Download limit per-user
function cmplayer_download_limit_check($request) {
    $user_id = get_current_user_id();
    $limit = intval(get_option('cmplayer_download_limit', 0));
    if (!$user_id) return array('status'=>'error', 'message'=>'Login required');
    if ($limit === 0) return array('status'=>'ok');
    $count = intval(get_user_meta($user_id, 'cmplayer_download_count', true));
    if ($count >= $limit) return array('status'=>'error', 'message'=>'Download limit reached!');
    update_user_meta($user_id, 'cmplayer_download_count', $count+1);
    return array('status'=>'ok');
}

// Admin: Reset all users' download counts
function cmplayer_reset_download_count($request) {
    global $wpdb;
    $meta_key = 'cmplayer_download_count';
    $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key = '{$meta_key}'");
    return array('status'=>'ok', 'message'=>'All users\' download counts reset!');
}

// Get user's favorites (AJAX UI)
function cmplayer_get_favorites($request) {
    $user_id = get_current_user_id();
    if (!$user_id) return array('favorites'=>[]);
    $favs = get_user_meta($user_id, 'cmplayer_favorites', true);
    if (!is_array($favs)) $favs = [];
    return array('favorites'=>$favs);
}

// Search movies via TMDB API
function cmplayer_search_movies($request) {
    $query = sanitize_text_field($request->get_param('q'));
    
    if (empty($query)) {
        return new WP_Error('missing_query', 'Search query is required', array('status' => 400));
    }
    
    $autopost = new CMPlayer_AutoPost_Movies();
    $results = $autopost->search_movies($query);
    
    if (is_wp_error($results)) {
        return $results;
    }
    
    return array('results' => $results);
}

// Get movie details via TMDB API
function cmplayer_get_movie_details($request) {
    $autopost = new CMPlayer_AutoPost_Movies();
    $movie_id = intval($request['id']);
    
    if (empty($movie_id)) {
        return new WP_Error('invalid_id', 'Valid movie ID is required', array('status' => 400));
    }
    
    $movie_details = $autopost->get_movie_details($movie_id);
    
    if (is_wp_error($movie_details)) {
        return $movie_details;
    }
    
    return $movie_details;
}