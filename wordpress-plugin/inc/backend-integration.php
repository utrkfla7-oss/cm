<?php
// Backend Integration for Netflix Streaming Platform

if (!defined('ABSPATH')) exit;

// Make API request to backend
function netflix_api_request($endpoint, $method = 'GET', $data = [], $auth_required = true) {
    $backend_url = get_option('netflix_backend_url', '');
    $api_key = get_option('netflix_api_key', '');
    
    if (empty($backend_url)) {
        return new WP_Error('no_backend_url', __('Backend URL not configured', 'netflix-streaming'));
    }
    
    if ($auth_required && empty($api_key)) {
        return new WP_Error('no_api_key', __('API key not configured', 'netflix-streaming'));
    }
    
    $url = rtrim($backend_url, '/') . '/' . ltrim($endpoint, '/');
    
    $headers = [
        'Content-Type' => 'application/json',
    ];
    
    if ($auth_required) {
        $headers['X-API-Key'] = $api_key;
    }
    
    $args = [
        'method' => $method,
        'headers' => $headers,
        'timeout' => 30,
    ];
    
    if ($method !== 'GET' && !empty($data)) {
        $args['body'] = json_encode($data);
    }
    
    $response = wp_remote_request($url, $args);
    
    if (is_wp_error($response)) {
        return $response;
    }
    
    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    $decoded_body = json_decode($body, true);
    
    if ($code >= 200 && $code < 300) {
        return $decoded_body;
    } else {
        return new WP_Error('api_error', $decoded_body['message'] ?? 'API request failed', $decoded_body);
    }
}

// Sync content from backend to WordPress
function netflix_sync_content_from_backend($request = null) {
    $params = $request ? $request->get_params() : $_POST;
    $content_type = $params['type'] ?? 'movies';
    $page = $params['page'] ?? 1;
    $limit = $params['limit'] ?? 20;
    
    // Fetch content from backend
    $endpoint = $content_type === 'movies' ? '/api/wp/movies' : '/api/wp/tv-shows';
    $response = netflix_api_request($endpoint, 'GET', [
        'page' => $page,
        'limit' => $limit,
        'status' => 'published'
    ]);
    
    if (is_wp_error($response)) {
        return $request ? rest_ensure_response($response) : wp_send_json_error($response->get_error_message());
    }
    
    $synced_count = 0;
    $errors = [];
    
    if ($content_type === 'movies' && isset($response['movies'])) {
        foreach ($response['movies'] as $movie_data) {
            $result = netflix_create_movie_from_backend($movie_data);
            if (is_wp_error($result)) {
                $errors[] = $result->get_error_message();
            } else {
                $synced_count++;
            }
        }
    } elseif ($content_type === 'tv-shows' && isset($response['tv_shows'])) {
        foreach ($response['tv_shows'] as $show_data) {
            $result = netflix_create_show_from_backend($show_data);
            if (is_wp_error($result)) {
                $errors[] = $result->get_error_message();
            } else {
                $synced_count++;
            }
        }
    }
    
    $result = [
        'synced_count' => $synced_count,
        'errors' => $errors,
        'total_available' => $response['pagination']['total'] ?? 0
    ];
    
    return $request ? rest_ensure_response($result) : wp_send_json_success($result);
}

// Create WordPress movie post from backend data
function netflix_create_movie_from_backend($movie_data) {
    // Check if movie already exists
    $existing_posts = get_posts([
        'post_type' => 'netflix_movie',
        'meta_query' => [
            [
                'key' => '_netflix_backend_id',
                'value' => $movie_data['id'],
                'compare' => '='
            ]
        ],
        'posts_per_page' => 1
    ]);
    
    if (!empty($existing_posts)) {
        // Update existing post
        $post_id = $existing_posts[0]->ID;
        $update_result = netflix_update_movie_from_backend($post_id, $movie_data);
        return $update_result ? $post_id : new WP_Error('update_failed', 'Failed to update existing movie');
    }
    
    // Create new post
    $post_data = [
        'post_title' => sanitize_text_field($movie_data['title']),
        'post_content' => wp_kses_post($movie_data['description']),
        'post_type' => 'netflix_movie',
        'post_status' => get_option('netflix_auto_publish', '0') === '1' ? 'publish' : 'draft',
        'meta_input' => [
            '_netflix_backend_id' => $movie_data['id'],
            '_netflix_imported' => '1',
            '_netflix_last_sync' => current_time('mysql'),
            '_netflix_duration' => $movie_data['duration'] ?? '',
            '_netflix_release_date' => $movie_data['release_date'] ?? '',
            '_netflix_director' => $movie_data['director'] ?? '',
            '_netflix_cast' => $movie_data['cast'] ?? '',
            '_netflix_rating' => $movie_data['rating'] ?? '',
            '_netflix_backdrop_url' => $movie_data['backdrop_url'] ?? '',
            '_netflix_video_url' => $movie_data['streaming_url'] ?? '',
            '_netflix_hls_url' => $movie_data['streaming_url'] ?? ''
        ]
    ];
    
    $post_id = wp_insert_post($post_data);
    
    if (is_wp_error($post_id)) {
        return $post_id;
    }
    
    // Set featured image from poster URL
    if (!empty($movie_data['poster_url'])) {
        netflix_set_featured_image_from_url($post_id, $movie_data['poster_url']);
    }
    
    // Set genres
    if (!empty($movie_data['genre'])) {
        $genres = explode(',', $movie_data['genre']);
        $genre_terms = [];
        foreach ($genres as $genre) {
            $genre = trim($genre);
            if (!empty($genre)) {
                $term = get_term_by('name', $genre, 'netflix_genre');
                if (!$term) {
                    $term = wp_insert_term($genre, 'netflix_genre');
                    if (!is_wp_error($term)) {
                        $genre_terms[] = $term['term_id'];
                    }
                } else {
                    $genre_terms[] = $term->term_id;
                }
            }
        }
        if (!empty($genre_terms)) {
            wp_set_post_terms($post_id, $genre_terms, 'netflix_genre');
        }
    }
    
    // Set release year
    if (!empty($movie_data['release_date'])) {
        $year = date('Y', strtotime($movie_data['release_date']));
        $year_term = get_term_by('name', $year, 'netflix_year');
        if (!$year_term) {
            $year_term = wp_insert_term($year, 'netflix_year');
            if (!is_wp_error($year_term)) {
                wp_set_post_terms($post_id, [$year_term['term_id']], 'netflix_year');
            }
        } else {
            wp_set_post_terms($post_id, [$year_term->term_id], 'netflix_year');
        }
    }
    
    return $post_id;
}

// Create WordPress TV show post from backend data
function netflix_create_show_from_backend($show_data) {
    // Check if show already exists
    $existing_posts = get_posts([
        'post_type' => 'netflix_show',
        'meta_query' => [
            [
                'key' => '_netflix_backend_id',
                'value' => $show_data['id'],
                'compare' => '='
            ]
        ],
        'posts_per_page' => 1
    ]);
    
    if (!empty($existing_posts)) {
        // Update existing post
        $post_id = $existing_posts[0]->ID;
        $update_result = netflix_update_show_from_backend($post_id, $show_data);
        return $update_result ? $post_id : new WP_Error('update_failed', 'Failed to update existing show');
    }
    
    // Create new post
    $post_data = [
        'post_title' => sanitize_text_field($show_data['title']),
        'post_content' => wp_kses_post($show_data['description']),
        'post_type' => 'netflix_show',
        'post_status' => get_option('netflix_auto_publish', '0') === '1' ? 'publish' : 'draft',
        'meta_input' => [
            '_netflix_backend_id' => $show_data['id'],
            '_netflix_imported' => '1',
            '_netflix_last_sync' => current_time('mysql'),
            '_netflix_first_air_date' => $show_data['first_air_date'] ?? '',
            '_netflix_last_air_date' => $show_data['last_air_date'] ?? '',
            '_netflix_creator' => $show_data['creator'] ?? '',
            '_netflix_cast' => $show_data['cast'] ?? '',
            '_netflix_rating' => $show_data['rating'] ?? '',
            '_netflix_backdrop_url' => $show_data['backdrop_url'] ?? '',
            '_netflix_total_episodes' => $show_data['episode_count'] ?? ''
        ]
    ];
    
    $post_id = wp_insert_post($post_data);
    
    if (is_wp_error($post_id)) {
        return $post_id;
    }
    
    // Set featured image from poster URL
    if (!empty($show_data['poster_url'])) {
        netflix_set_featured_image_from_url($post_id, $show_data['poster_url']);
    }
    
    // Set genres
    if (!empty($show_data['genre'])) {
        $genres = explode(',', $show_data['genre']);
        $genre_terms = [];
        foreach ($genres as $genre) {
            $genre = trim($genre);
            if (!empty($genre)) {
                $term = get_term_by('name', $genre, 'netflix_genre');
                if (!$term) {
                    $term = wp_insert_term($genre, 'netflix_genre');
                    if (!is_wp_error($term)) {
                        $genre_terms[] = $term['term_id'];
                    }
                } else {
                    $genre_terms[] = $term->term_id;
                }
            }
        }
        if (!empty($genre_terms)) {
            wp_set_post_terms($post_id, $genre_terms, 'netflix_genre');
        }
    }
    
    // Set release year
    if (!empty($show_data['first_air_date'])) {
        $year = date('Y', strtotime($show_data['first_air_date']));
        $year_term = get_term_by('name', $year, 'netflix_year');
        if (!$year_term) {
            $year_term = wp_insert_term($year, 'netflix_year');
            if (!is_wp_error($year_term)) {
                wp_set_post_terms($post_id, [$year_term['term_id']], 'netflix_year');
            }
        } else {
            wp_set_post_terms($post_id, [$year_term->term_id], 'netflix_year');
        }
    }
    
    return $post_id;
}

// Update existing movie from backend data
function netflix_update_movie_from_backend($post_id, $movie_data) {
    $update_data = [
        'ID' => $post_id,
        'post_title' => sanitize_text_field($movie_data['title']),
        'post_content' => wp_kses_post($movie_data['description'])
    ];
    
    $result = wp_update_post($update_data);
    
    if (is_wp_error($result)) {
        return false;
    }
    
    // Update meta fields
    update_post_meta($post_id, '_netflix_last_sync', current_time('mysql'));
    update_post_meta($post_id, '_netflix_duration', $movie_data['duration'] ?? '');
    update_post_meta($post_id, '_netflix_release_date', $movie_data['release_date'] ?? '');
    update_post_meta($post_id, '_netflix_director', $movie_data['director'] ?? '');
    update_post_meta($post_id, '_netflix_cast', $movie_data['cast'] ?? '');
    update_post_meta($post_id, '_netflix_rating', $movie_data['rating'] ?? '');
    update_post_meta($post_id, '_netflix_backdrop_url', $movie_data['backdrop_url'] ?? '');
    update_post_meta($post_id, '_netflix_video_url', $movie_data['streaming_url'] ?? '');
    update_post_meta($post_id, '_netflix_hls_url', $movie_data['streaming_url'] ?? '');
    
    return true;
}

// Update existing TV show from backend data
function netflix_update_show_from_backend($post_id, $show_data) {
    $update_data = [
        'ID' => $post_id,
        'post_title' => sanitize_text_field($show_data['title']),
        'post_content' => wp_kses_post($show_data['description'])
    ];
    
    $result = wp_update_post($update_data);
    
    if (is_wp_error($result)) {
        return false;
    }
    
    // Update meta fields
    update_post_meta($post_id, '_netflix_last_sync', current_time('mysql'));
    update_post_meta($post_id, '_netflix_first_air_date', $show_data['first_air_date'] ?? '');
    update_post_meta($post_id, '_netflix_last_air_date', $show_data['last_air_date'] ?? '');
    update_post_meta($post_id, '_netflix_creator', $show_data['creator'] ?? '');
    update_post_meta($post_id, '_netflix_cast', $show_data['cast'] ?? '');
    update_post_meta($post_id, '_netflix_rating', $show_data['rating'] ?? '');
    update_post_meta($post_id, '_netflix_backdrop_url', $show_data['backdrop_url'] ?? '');
    update_post_meta($post_id, '_netflix_total_episodes', $show_data['episode_count'] ?? '');
    
    return true;
}

// Set featured image from URL
function netflix_set_featured_image_from_url($post_id, $image_url) {
    if (empty($image_url)) {
        return false;
    }
    
    // Check if image already exists
    $existing_image_id = get_post_meta($post_id, '_thumbnail_id', true);
    if (!empty($existing_image_id)) {
        return $existing_image_id; // Already has featured image
    }
    
    // Download image
    $tmp = download_url($image_url);
    
    if (is_wp_error($tmp)) {
        return false;
    }
    
    $file_array = [
        'name' => basename($image_url),
        'tmp_name' => $tmp
    ];
    
    // Upload to media library
    $id = media_handle_sideload($file_array, $post_id);
    
    // Clean up tmp file
    @unlink($tmp);
    
    if (is_wp_error($id)) {
        return false;
    }
    
    // Set as featured image
    set_post_thumbnail($post_id, $id);
    
    return $id;
}

// Sync single post to backend
function netflix_sync_post_to_backend($post_id) {
    $post = get_post($post_id);
    
    if (!$post || !in_array($post->post_type, ['netflix_movie', 'netflix_show'])) {
        return false;
    }
    
    // Get post meta
    $meta = get_post_meta($post_id);
    
    // Prepare data for backend
    $data = [
        'title' => $post->post_title,
        'description' => $post->post_content,
        'status' => $post->post_status === 'publish' ? 'published' : 'draft'
    ];
    
    if ($post->post_type === 'netflix_movie') {
        $data = array_merge($data, [
            'tmdb_id' => $meta['_netflix_tmdb_id'][0] ?? null,
            'imdb_id' => $meta['_netflix_imdb_id'][0] ?? null,
            'duration' => $meta['_netflix_duration'][0] ?? null,
            'release_date' => $meta['_netflix_release_date'][0] ?? null,
            'director' => $meta['_netflix_director'][0] ?? null,
            'cast' => $meta['_netflix_cast'][0] ?? null,
            'rating' => $meta['_netflix_rating'][0] ?? null,
            'trailer_url' => $meta['_netflix_trailer_url'][0] ?? null,
            'backdrop_url' => $meta['_netflix_backdrop_url'][0] ?? null
        ]);
        
        $endpoint = '/api/admin/movies';
    } else {
        $data = array_merge($data, [
            'tmdb_id' => $meta['_netflix_tmdb_id'][0] ?? null,
            'first_air_date' => $meta['_netflix_first_air_date'][0] ?? null,
            'last_air_date' => $meta['_netflix_last_air_date'][0] ?? null,
            'creator' => $meta['_netflix_creator'][0] ?? null,
            'cast' => $meta['_netflix_cast'][0] ?? null,
            'rating' => $meta['_netflix_rating'][0] ?? null,
            'backdrop_url' => $meta['_netflix_backdrop_url'][0] ?? null
        ]);
        
        $endpoint = '/api/admin/tv-shows';
    }
    
    // Get featured image URL
    $thumbnail_id = get_post_thumbnail_id($post_id);
    if ($thumbnail_id) {
        $data['poster_url'] = wp_get_attachment_image_url($thumbnail_id, 'full');
    }
    
    // Check if this is an update or create
    $backend_id = $meta['_netflix_backend_id'][0] ?? '';
    
    if ($backend_id) {
        // Update existing
        $response = netflix_api_request($endpoint . '/' . $backend_id, 'PUT', $data);
    } else {
        // Create new
        $response = netflix_api_request($endpoint, 'POST', $data);
        
        if (!is_wp_error($response) && isset($response['id'])) {
            update_post_meta($post_id, '_netflix_backend_id', $response['id']);
        }
    }
    
    if (!is_wp_error($response)) {
        update_post_meta($post_id, '_netflix_last_sync', current_time('mysql'));
        return true;
    }
    
    return false;
}

// Start batch import job
function netflix_start_batch_import($request = null) {
    $params = $request ? $request->get_params() : $_POST;
    
    $import_type = $params['type'] ?? 'movie'; // movie or tv
    $source = $params['source'] ?? 'popular'; // popular, search, manual
    $pages = absint($params['pages'] ?? 1);
    $include_episodes = ($params['include_episodes'] ?? false) === true;
    
    if (!in_array($import_type, ['movie', 'tv'])) {
        $error = new WP_Error('invalid_type', 'Invalid import type');
        return $request ? rest_ensure_response($error) : wp_send_json_error($error->get_error_message());
    }
    
    // Prepare backend request data
    $backend_data = [
        'auto_popular' => $source === 'popular',
        'pages' => $pages
    ];
    
    if ($import_type === 'tv' && $include_episodes) {
        $backend_data['include_episodes'] = true;
    }
    
    // Start import job on backend
    $endpoint = $import_type === 'movie' ? '/api/imdb/batch-import/movies' : '/api/imdb/batch-import/tv';
    $response = netflix_api_request($endpoint, 'POST', $backend_data);
    
    if (is_wp_error($response)) {
        return $request ? rest_ensure_response($response) : wp_send_json_error($response->get_error_message());
    }
    
    // Store job info in WordPress
    $job_data = [
        'backend_job_id' => $response['job_id'],
        'type' => $import_type,
        'source' => $source,
        'pages' => $pages,
        'total_items' => $response['total_items'],
        'status' => 'processing',
        'created_at' => current_time('mysql')
    ];
    
    // Store in options (in a real implementation, you might use a custom table)
    $jobs = get_option('netflix_import_jobs', []);
    $job_id = 'job_' . time() . '_' . wp_rand(1000, 9999);
    $jobs[$job_id] = $job_data;
    update_option('netflix_import_jobs', $jobs);
    
    $result = [
        'job_id' => $job_id,
        'backend_job_id' => $response['job_id'],
        'total_items' => $response['total_items'],
        'status' => 'processing'
    ];
    
    return $request ? rest_ensure_response($result) : wp_send_json_success($result);
}

// Check import job status
function netflix_check_import_job_status($job_id) {
    $jobs = get_option('netflix_import_jobs', []);
    
    if (!isset($jobs[$job_id])) {
        return new WP_Error('job_not_found', 'Import job not found');
    }
    
    $job = $jobs[$job_id];
    
    // Check status on backend
    $response = netflix_api_request('/api/imdb/import-jobs/' . $job['backend_job_id']);
    
    if (is_wp_error($response)) {
        return $response;
    }
    
    // Update local job status
    $job['status'] = $response['status'];
    $job['processed_items'] = $response['processed_items'];
    $job['failed_items'] = $response['failed_items'];
    $job['progress'] = $response['progress'];
    
    $jobs[$job_id] = $job;
    update_option('netflix_import_jobs', $jobs);
    
    // If completed, sync content to WordPress
    if ($response['status'] === 'completed') {
        // Trigger content sync
        do_action('netflix_import_job_completed', $job_id, $job);
    }
    
    return $job;
}

// Get content details from backend
function netflix_get_content_details($request) {
    $type = $request['type']; // movie or show
    $id = $request['id'];
    
    $endpoint = $type === 'movie' ? '/api/wp/content/movie/' . $id : '/api/wp/content/tv/' . $id;
    $response = netflix_api_request($endpoint);
    
    if (is_wp_error($response)) {
        return rest_ensure_response($response);
    }
    
    return rest_ensure_response($response);
}

// AJAX handler for syncing single content
add_action('wp_ajax_netflix_sync_content', function() {
    check_ajax_referer('netflix_sync', 'nonce');
    
    if (!current_user_can('manage_netflix_content')) {
        wp_send_json_error('Permission denied');
    }
    
    $post_id = absint($_POST['post_id']);
    
    if (!$post_id) {
        wp_send_json_error('Invalid post ID');
    }
    
    $result = netflix_sync_post_to_backend($post_id);
    
    if ($result) {
        wp_send_json_success('Content synced successfully');
    } else {
        wp_send_json_error('Sync failed');
    }
});

// Hook to handle completed import jobs
add_action('netflix_import_job_completed', function($job_id, $job) {
    // Auto-sync content when import is completed
    if (get_option('netflix_auto_sync_after_import', '1') === '1') {
        netflix_sync_content_from_backend();
    }
}, 10, 2);