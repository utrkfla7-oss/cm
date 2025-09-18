<?php
// Custom Post Types for Netflix Streaming Platform

if (!defined('ABSPATH')) exit;

// Create custom post types
function netflix_create_custom_post_types() {
    // Movie post type
    register_post_type('netflix_movie', [
        'labels' => [
            'name' => __('Movies', 'netflix-streaming'),
            'singular_name' => __('Movie', 'netflix-streaming'),
            'menu_name' => __('Movies', 'netflix-streaming'),
            'add_new' => __('Add New Movie', 'netflix-streaming'),
            'add_new_item' => __('Add New Movie', 'netflix-streaming'),
            'new_item' => __('New Movie', 'netflix-streaming'),
            'edit_item' => __('Edit Movie', 'netflix-streaming'),
            'view_item' => __('View Movie', 'netflix-streaming'),
            'all_items' => __('All Movies', 'netflix-streaming'),
            'search_items' => __('Search Movies', 'netflix-streaming'),
            'not_found' => __('No movies found', 'netflix-streaming'),
            'not_found_in_trash' => __('No movies found in trash', 'netflix-streaming')
        ],
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_nav_menus' => true,
        'show_in_admin_bar' => true,
        'show_in_rest' => true,
        'query_var' => true,
        'rewrite' => ['slug' => 'movies'],
        'capability_type' => 'post',
        'capabilities' => [
            'publish_posts' => 'publish_netflix_movies',
            'edit_posts' => 'manage_netflix_content',
            'edit_others_posts' => 'manage_netflix_content',
            'delete_posts' => 'manage_netflix_content',
            'delete_others_posts' => 'manage_netflix_content',
            'read_private_posts' => 'manage_netflix_content',
            'edit_post' => 'manage_netflix_content',
            'delete_post' => 'manage_netflix_content',
            'read_post' => 'read'
        ],
        'has_archive' => true,
        'hierarchical' => false,
        'menu_position' => 26,
        'menu_icon' => 'dashicons-video-alt',
        'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'comments', 'revisions'],
        'taxonomies' => ['netflix_genre', 'netflix_year', 'netflix_rating']
    ]);

    // TV Show post type
    register_post_type('netflix_show', [
        'labels' => [
            'name' => __('TV Shows', 'netflix-streaming'),
            'singular_name' => __('TV Show', 'netflix-streaming'),
            'menu_name' => __('TV Shows', 'netflix-streaming'),
            'add_new' => __('Add New Show', 'netflix-streaming'),
            'add_new_item' => __('Add New TV Show', 'netflix-streaming'),
            'new_item' => __('New TV Show', 'netflix-streaming'),
            'edit_item' => __('Edit TV Show', 'netflix-streaming'),
            'view_item' => __('View TV Show', 'netflix-streaming'),
            'all_items' => __('All TV Shows', 'netflix-streaming'),
            'search_items' => __('Search TV Shows', 'netflix-streaming'),
            'not_found' => __('No TV shows found', 'netflix-streaming'),
            'not_found_in_trash' => __('No TV shows found in trash', 'netflix-streaming')
        ],
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_nav_menus' => true,
        'show_in_admin_bar' => true,
        'show_in_rest' => true,
        'query_var' => true,
        'rewrite' => ['slug' => 'tv-shows'],
        'capability_type' => 'post',
        'capabilities' => [
            'publish_posts' => 'publish_netflix_shows',
            'edit_posts' => 'manage_netflix_content',
            'edit_others_posts' => 'manage_netflix_content',
            'delete_posts' => 'manage_netflix_content',
            'delete_others_posts' => 'manage_netflix_content',
            'read_private_posts' => 'manage_netflix_content',
            'edit_post' => 'manage_netflix_content',
            'delete_post' => 'manage_netflix_content',
            'read_post' => 'read'
        ],
        'has_archive' => true,
        'hierarchical' => false,
        'menu_position' => 27,
        'menu_icon' => 'dashicons-video-alt2',
        'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'comments', 'revisions'],
        'taxonomies' => ['netflix_genre', 'netflix_year', 'netflix_rating']
    ]);

    // Episode post type (child of TV Shows)
    register_post_type('netflix_episode', [
        'labels' => [
            'name' => __('Episodes', 'netflix-streaming'),
            'singular_name' => __('Episode', 'netflix-streaming'),
            'menu_name' => __('Episodes', 'netflix-streaming'),
            'add_new' => __('Add New Episode', 'netflix-streaming'),
            'add_new_item' => __('Add New Episode', 'netflix-streaming'),
            'new_item' => __('New Episode', 'netflix-streaming'),
            'edit_item' => __('Edit Episode', 'netflix-streaming'),
            'view_item' => __('View Episode', 'netflix-streaming'),
            'all_items' => __('All Episodes', 'netflix-streaming'),
            'search_items' => __('Search Episodes', 'netflix-streaming'),
            'not_found' => __('No episodes found', 'netflix-streaming'),
            'not_found_in_trash' => __('No episodes found in trash', 'netflix-streaming')
        ],
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => 'edit.php?post_type=netflix_show',
        'show_in_nav_menus' => true,
        'show_in_admin_bar' => false,
        'show_in_rest' => true,
        'query_var' => true,
        'rewrite' => ['slug' => 'episodes'],
        'capability_type' => 'post',
        'capabilities' => [
            'publish_posts' => 'publish_netflix_shows',
            'edit_posts' => 'manage_netflix_content',
            'edit_others_posts' => 'manage_netflix_content',
            'delete_posts' => 'manage_netflix_content',
            'delete_others_posts' => 'manage_netflix_content',
            'read_private_posts' => 'manage_netflix_content',
            'edit_post' => 'manage_netflix_content',
            'delete_post' => 'manage_netflix_content',
            'read_post' => 'read'
        ],
        'has_archive' => false,
        'hierarchical' => false,
        'menu_position' => null,
        'menu_icon' => 'dashicons-video-alt3',
        'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'custom-fields'],
        'taxonomies' => []
    ]);

    // Register custom taxonomies
    netflix_create_taxonomies();
}

// Create custom taxonomies
function netflix_create_taxonomies() {
    // Genre taxonomy
    register_taxonomy('netflix_genre', ['netflix_movie', 'netflix_show'], [
        'labels' => [
            'name' => __('Genres', 'netflix-streaming'),
            'singular_name' => __('Genre', 'netflix-streaming'),
            'menu_name' => __('Genres', 'netflix-streaming'),
            'all_items' => __('All Genres', 'netflix-streaming'),
            'edit_item' => __('Edit Genre', 'netflix-streaming'),
            'view_item' => __('View Genre', 'netflix-streaming'),
            'update_item' => __('Update Genre', 'netflix-streaming'),
            'add_new_item' => __('Add New Genre', 'netflix-streaming'),
            'new_item_name' => __('New Genre Name', 'netflix-streaming'),
            'search_items' => __('Search Genres', 'netflix-streaming'),
            'not_found' => __('No genres found', 'netflix-streaming')
        ],
        'public' => true,
        'publicly_queryable' => true,
        'hierarchical' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_nav_menus' => true,
        'show_tagcloud' => true,
        'show_in_rest' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => ['slug' => 'genre']
    ]);

    // Release Year taxonomy
    register_taxonomy('netflix_year', ['netflix_movie', 'netflix_show'], [
        'labels' => [
            'name' => __('Release Years', 'netflix-streaming'),
            'singular_name' => __('Release Year', 'netflix-streaming'),
            'menu_name' => __('Years', 'netflix-streaming'),
            'all_items' => __('All Years', 'netflix-streaming'),
            'edit_item' => __('Edit Year', 'netflix-streaming'),
            'view_item' => __('View Year', 'netflix-streaming'),
            'update_item' => __('Update Year', 'netflix-streaming'),
            'add_new_item' => __('Add New Year', 'netflix-streaming'),
            'new_item_name' => __('New Year', 'netflix-streaming'),
            'search_items' => __('Search Years', 'netflix-streaming'),
            'not_found' => __('No years found', 'netflix-streaming')
        ],
        'public' => true,
        'publicly_queryable' => true,
        'hierarchical' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_nav_menus' => true,
        'show_tagcloud' => true,
        'show_in_rest' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => ['slug' => 'year']
    ]);

    // Rating taxonomy (G, PG, PG-13, R, etc.)
    register_taxonomy('netflix_rating', ['netflix_movie', 'netflix_show'], [
        'labels' => [
            'name' => __('Content Ratings', 'netflix-streaming'),
            'singular_name' => __('Content Rating', 'netflix-streaming'),
            'menu_name' => __('Ratings', 'netflix-streaming'),
            'all_items' => __('All Ratings', 'netflix-streaming'),
            'edit_item' => __('Edit Rating', 'netflix-streaming'),
            'view_item' => __('View Rating', 'netflix-streaming'),
            'update_item' => __('Update Rating', 'netflix-streaming'),
            'add_new_item' => __('Add New Rating', 'netflix-streaming'),
            'new_item_name' => __('New Rating', 'netflix-streaming'),
            'search_items' => __('Search Ratings', 'netflix-streaming'),
            'not_found' => __('No ratings found', 'netflix-streaming')
        ],
        'public' => true,
        'publicly_queryable' => true,
        'hierarchical' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_nav_menus' => true,
        'show_tagcloud' => false,
        'show_in_rest' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => ['slug' => 'rating']
    ]);
}

// Add meta boxes for custom fields
function netflix_add_meta_boxes() {
    // Movie meta box
    add_meta_box(
        'netflix_movie_details',
        __('Movie Details', 'netflix-streaming'),
        'netflix_movie_meta_box_callback',
        'netflix_movie',
        'normal',
        'high'
    );

    // TV Show meta box
    add_meta_box(
        'netflix_show_details',
        __('TV Show Details', 'netflix-streaming'),
        'netflix_show_meta_box_callback',
        'netflix_show',
        'normal',
        'high'
    );

    // Episode meta box
    add_meta_box(
        'netflix_episode_details',
        __('Episode Details', 'netflix-streaming'),
        'netflix_episode_meta_box_callback',
        'netflix_episode',
        'normal',
        'high'
    );

    // Streaming Settings meta box (for all types)
    add_meta_box(
        'netflix_streaming_settings',
        __('Streaming Settings', 'netflix-streaming'),
        'netflix_streaming_meta_box_callback',
        ['netflix_movie', 'netflix_show', 'netflix_episode'],
        'side',
        'high'
    );

    // Backend Sync meta box
    add_meta_box(
        'netflix_backend_sync',
        __('Backend Sync', 'netflix-streaming'),
        'netflix_backend_sync_meta_box_callback',
        ['netflix_movie', 'netflix_show', 'netflix_episode'],
        'side',
        'low'
    );
}

// Movie meta box callback
function netflix_movie_meta_box_callback($post) {
    wp_nonce_field('netflix_movie_meta_box', 'netflix_movie_meta_box_nonce');
    
    $meta = get_post_meta($post->ID);
    ?>
    <table class="form-table">
        <tr>
            <th><label for="netflix_imdb_id"><?php _e('IMDb ID', 'netflix-streaming'); ?></label></th>
            <td><input type="text" id="netflix_imdb_id" name="netflix_imdb_id" value="<?php echo esc_attr($meta['_netflix_imdb_id'][0] ?? ''); ?>" class="regular-text" placeholder="tt1234567" /></td>
        </tr>
        <tr>
            <th><label for="netflix_tmdb_id"><?php _e('TMDb ID', 'netflix-streaming'); ?></label></th>
            <td><input type="number" id="netflix_tmdb_id" name="netflix_tmdb_id" value="<?php echo esc_attr($meta['_netflix_tmdb_id'][0] ?? ''); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="netflix_duration"><?php _e('Duration (minutes)', 'netflix-streaming'); ?></label></th>
            <td><input type="number" id="netflix_duration" name="netflix_duration" value="<?php echo esc_attr($meta['_netflix_duration'][0] ?? ''); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="netflix_release_date"><?php _e('Release Date', 'netflix-streaming'); ?></label></th>
            <td><input type="date" id="netflix_release_date" name="netflix_release_date" value="<?php echo esc_attr($meta['_netflix_release_date'][0] ?? ''); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="netflix_director"><?php _e('Director', 'netflix-streaming'); ?></label></th>
            <td><input type="text" id="netflix_director" name="netflix_director" value="<?php echo esc_attr($meta['_netflix_director'][0] ?? ''); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="netflix_cast"><?php _e('Cast', 'netflix-streaming'); ?></label></th>
            <td><textarea id="netflix_cast" name="netflix_cast" rows="3" class="large-text"><?php echo esc_textarea($meta['_netflix_cast'][0] ?? ''); ?></textarea></td>
        </tr>
        <tr>
            <th><label for="netflix_rating"><?php _e('Rating (1-10)', 'netflix-streaming'); ?></label></th>
            <td><input type="number" id="netflix_rating" name="netflix_rating" value="<?php echo esc_attr($meta['_netflix_rating'][0] ?? ''); ?>" min="0" max="10" step="0.1" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="netflix_trailer_url"><?php _e('Trailer URL', 'netflix-streaming'); ?></label></th>
            <td><input type="url" id="netflix_trailer_url" name="netflix_trailer_url" value="<?php echo esc_attr($meta['_netflix_trailer_url'][0] ?? ''); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="netflix_backdrop_url"><?php _e('Backdrop URL', 'netflix-streaming'); ?></label></th>
            <td><input type="url" id="netflix_backdrop_url" name="netflix_backdrop_url" value="<?php echo esc_attr($meta['_netflix_backdrop_url'][0] ?? ''); ?>" class="regular-text" /></td>
        </tr>
    </table>
    <?php
}

// TV Show meta box callback
function netflix_show_meta_box_callback($post) {
    wp_nonce_field('netflix_show_meta_box', 'netflix_show_meta_box_nonce');
    
    $meta = get_post_meta($post->ID);
    ?>
    <table class="form-table">
        <tr>
            <th><label for="netflix_tmdb_id"><?php _e('TMDb ID', 'netflix-streaming'); ?></label></th>
            <td><input type="number" id="netflix_tmdb_id" name="netflix_tmdb_id" value="<?php echo esc_attr($meta['_netflix_tmdb_id'][0] ?? ''); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="netflix_first_air_date"><?php _e('First Air Date', 'netflix-streaming'); ?></label></th>
            <td><input type="date" id="netflix_first_air_date" name="netflix_first_air_date" value="<?php echo esc_attr($meta['_netflix_first_air_date'][0] ?? ''); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="netflix_last_air_date"><?php _e('Last Air Date', 'netflix-streaming'); ?></label></th>
            <td><input type="date" id="netflix_last_air_date" name="netflix_last_air_date" value="<?php echo esc_attr($meta['_netflix_last_air_date'][0] ?? ''); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="netflix_creator"><?php _e('Creator', 'netflix-streaming'); ?></label></th>
            <td><input type="text" id="netflix_creator" name="netflix_creator" value="<?php echo esc_attr($meta['_netflix_creator'][0] ?? ''); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="netflix_cast"><?php _e('Cast', 'netflix-streaming'); ?></label></th>
            <td><textarea id="netflix_cast" name="netflix_cast" rows="3" class="large-text"><?php echo esc_textarea($meta['_netflix_cast'][0] ?? ''); ?></textarea></td>
        </tr>
        <tr>
            <th><label for="netflix_rating"><?php _e('Rating (1-10)', 'netflix-streaming'); ?></label></th>
            <td><input type="number" id="netflix_rating" name="netflix_rating" value="<?php echo esc_attr($meta['_netflix_rating'][0] ?? ''); ?>" min="0" max="10" step="0.1" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="netflix_total_seasons"><?php _e('Total Seasons', 'netflix-streaming'); ?></label></th>
            <td><input type="number" id="netflix_total_seasons" name="netflix_total_seasons" value="<?php echo esc_attr($meta['_netflix_total_seasons'][0] ?? ''); ?>" min="1" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="netflix_total_episodes"><?php _e('Total Episodes', 'netflix-streaming'); ?></label></th>
            <td><input type="number" id="netflix_total_episodes" name="netflix_total_episodes" value="<?php echo esc_attr($meta['_netflix_total_episodes'][0] ?? ''); ?>" min="1" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="netflix_backdrop_url"><?php _e('Backdrop URL', 'netflix-streaming'); ?></label></th>
            <td><input type="url" id="netflix_backdrop_url" name="netflix_backdrop_url" value="<?php echo esc_attr($meta['_netflix_backdrop_url'][0] ?? ''); ?>" class="regular-text" /></td>
        </tr>
    </table>
    <?php
}

// Episode meta box callback
function netflix_episode_meta_box_callback($post) {
    wp_nonce_field('netflix_episode_meta_box', 'netflix_episode_meta_box_nonce');
    
    $meta = get_post_meta($post->ID);
    ?>
    <table class="form-table">
        <tr>
            <th><label for="netflix_parent_show"><?php _e('TV Show', 'netflix-streaming'); ?></label></th>
            <td>
                <?php
                wp_dropdown_pages([
                    'post_type' => 'netflix_show',
                    'selected' => $meta['_netflix_parent_show'][0] ?? '',
                    'name' => 'netflix_parent_show',
                    'id' => 'netflix_parent_show',
                    'class' => 'regular-text',
                    'show_option_none' => __('Select TV Show', 'netflix-streaming'),
                    'option_none_value' => ''
                ]);
                ?>
            </td>
        </tr>
        <tr>
            <th><label for="netflix_season_number"><?php _e('Season Number', 'netflix-streaming'); ?></label></th>
            <td><input type="number" id="netflix_season_number" name="netflix_season_number" value="<?php echo esc_attr($meta['_netflix_season_number'][0] ?? ''); ?>" min="1" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="netflix_episode_number"><?php _e('Episode Number', 'netflix-streaming'); ?></label></th>
            <td><input type="number" id="netflix_episode_number" name="netflix_episode_number" value="<?php echo esc_attr($meta['_netflix_episode_number'][0] ?? ''); ?>" min="1" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="netflix_air_date"><?php _e('Air Date', 'netflix-streaming'); ?></label></th>
            <td><input type="date" id="netflix_air_date" name="netflix_air_date" value="<?php echo esc_attr($meta['_netflix_air_date'][0] ?? ''); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="netflix_duration"><?php _e('Duration (minutes)', 'netflix-streaming'); ?></label></th>
            <td><input type="number" id="netflix_duration" name="netflix_duration" value="<?php echo esc_attr($meta['_netflix_duration'][0] ?? ''); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="netflix_rating"><?php _e('Rating (1-10)', 'netflix-streaming'); ?></label></th>
            <td><input type="number" id="netflix_rating" name="netflix_rating" value="<?php echo esc_attr($meta['_netflix_rating'][0] ?? ''); ?>" min="0" max="10" step="0.1" class="regular-text" /></td>
        </tr>
    </table>
    <?php
}

// Streaming settings meta box callback
function netflix_streaming_meta_box_callback($post) {
    wp_nonce_field('netflix_streaming_meta_box', 'netflix_streaming_meta_box_nonce');
    
    $meta = get_post_meta($post->ID);
    ?>
    <table class="form-table">
        <tr>
            <th><label for="netflix_video_url"><?php _e('Video URL', 'netflix-streaming'); ?></label></th>
            <td><input type="url" id="netflix_video_url" name="netflix_video_url" value="<?php echo esc_attr($meta['_netflix_video_url'][0] ?? ''); ?>" class="widefat" /></td>
        </tr>
        <tr>
            <th><label for="netflix_hls_url"><?php _e('HLS URL', 'netflix-streaming'); ?></label></th>
            <td><input type="url" id="netflix_hls_url" name="netflix_hls_url" value="<?php echo esc_attr($meta['_netflix_hls_url'][0] ?? ''); ?>" class="widefat" /></td>
        </tr>
        <tr>
            <th><label for="netflix_subtitle_urls"><?php _e('Subtitle URLs (JSON)', 'netflix-streaming'); ?></label></th>
            <td><textarea id="netflix_subtitle_urls" name="netflix_subtitle_urls" rows="3" class="widefat" placeholder='{"en": "url1", "bn": "url2"}'><?php echo esc_textarea($meta['_netflix_subtitle_urls'][0] ?? ''); ?></textarea></td>
        </tr>
        <tr>
            <th><label for="netflix_subscription_required"><?php _e('Subscription Required', 'netflix-streaming'); ?></label></th>
            <td>
                <select id="netflix_subscription_required" name="netflix_subscription_required" class="widefat">
                    <option value="free" <?php selected($meta['_netflix_subscription_required'][0] ?? 'free', 'free'); ?>><?php _e('Free', 'netflix-streaming'); ?></option>
                    <option value="basic" <?php selected($meta['_netflix_subscription_required'][0] ?? 'free', 'basic'); ?>><?php _e('Basic', 'netflix-streaming'); ?></option>
                    <option value="premium" <?php selected($meta['_netflix_subscription_required'][0] ?? 'free', 'premium'); ?>><?php _e('Premium', 'netflix-streaming'); ?></option>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="netflix_backend_video_id"><?php _e('Backend Video ID', 'netflix-streaming'); ?></label></th>
            <td><input type="number" id="netflix_backend_video_id" name="netflix_backend_video_id" value="<?php echo esc_attr($meta['_netflix_backend_video_id'][0] ?? ''); ?>" class="widefat" readonly /></td>
        </tr>
    </table>
    <?php
}

// Backend sync meta box callback
function netflix_backend_sync_meta_box_callback($post) {
    $meta = get_post_meta($post->ID);
    $is_imported = ($meta['_netflix_imported'][0] ?? '0') === '1';
    $backend_id = $meta['_netflix_backend_id'][0] ?? '';
    $last_sync = $meta['_netflix_last_sync'][0] ?? '';
    ?>
    <p>
        <strong><?php _e('Import Status:', 'netflix-streaming'); ?></strong>
        <?php if ($is_imported): ?>
            <span style="color: green;"><?php _e('Imported from Backend', 'netflix-streaming'); ?></span>
        <?php else: ?>
            <span style="color: orange;"><?php _e('Manual Entry', 'netflix-streaming'); ?></span>
        <?php endif; ?>
    </p>
    
    <?php if ($backend_id): ?>
        <p><strong><?php _e('Backend ID:', 'netflix-streaming'); ?></strong> <?php echo esc_html($backend_id); ?></p>
    <?php endif; ?>
    
    <?php if ($last_sync): ?>
        <p><strong><?php _e('Last Sync:', 'netflix-streaming'); ?></strong> <?php echo esc_html(date('Y-m-d H:i:s', strtotime($last_sync))); ?></p>
    <?php endif; ?>
    
    <p>
        <button type="button" class="button" onclick="netflixSyncWithBackend(<?php echo $post->ID; ?>)"><?php _e('Sync with Backend', 'netflix-streaming'); ?></button>
    </p>
    
    <script>
    function netflixSyncWithBackend(postId) {
        if (confirm('<?php _e('Are you sure you want to sync this content with the backend?', 'netflix-streaming'); ?>')) {
            // AJAX call to sync with backend
            jQuery.post(ajaxurl, {
                action: 'netflix_sync_content',
                post_id: postId,
                nonce: '<?php echo wp_create_nonce('netflix_sync'); ?>'
            }, function(response) {
                if (response.success) {
                    alert('<?php _e('Content synced successfully!', 'netflix-streaming'); ?>');
                    location.reload();
                } else {
                    alert('<?php _e('Sync failed:', 'netflix-streaming'); ?> ' + response.data);
                }
            });
        }
    }
    </script>
    <?php
}

// Save custom fields
function netflix_save_custom_fields($post_id) {
    // Check if our nonce is set and verify it
    if (!isset($_POST['netflix_movie_meta_box_nonce']) && 
        !isset($_POST['netflix_show_meta_box_nonce']) && 
        !isset($_POST['netflix_episode_meta_box_nonce']) &&
        !isset($_POST['netflix_streaming_meta_box_nonce'])) {
        return;
    }

    // Verify nonce
    $nonce_verified = false;
    if (isset($_POST['netflix_movie_meta_box_nonce'])) {
        $nonce_verified = wp_verify_nonce($_POST['netflix_movie_meta_box_nonce'], 'netflix_movie_meta_box');
    } elseif (isset($_POST['netflix_show_meta_box_nonce'])) {
        $nonce_verified = wp_verify_nonce($_POST['netflix_show_meta_box_nonce'], 'netflix_show_meta_box');
    } elseif (isset($_POST['netflix_episode_meta_box_nonce'])) {
        $nonce_verified = wp_verify_nonce($_POST['netflix_episode_meta_box_nonce'], 'netflix_episode_meta_box');
    } elseif (isset($_POST['netflix_streaming_meta_box_nonce'])) {
        $nonce_verified = wp_verify_nonce($_POST['netflix_streaming_meta_box_nonce'], 'netflix_streaming_meta_box');
    }

    if (!$nonce_verified) {
        return;
    }

    // Check if user has permission to edit the post
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Define fields to save
    $fields = [
        'netflix_imdb_id',
        'netflix_tmdb_id',
        'netflix_duration',
        'netflix_release_date',
        'netflix_director',
        'netflix_cast',
        'netflix_rating',
        'netflix_trailer_url',
        'netflix_backdrop_url',
        'netflix_first_air_date',
        'netflix_last_air_date',
        'netflix_creator',
        'netflix_total_seasons',
        'netflix_total_episodes',
        'netflix_parent_show',
        'netflix_season_number',
        'netflix_episode_number',
        'netflix_air_date',
        'netflix_video_url',
        'netflix_hls_url',
        'netflix_subtitle_urls',
        'netflix_subscription_required',
        'netflix_backend_video_id'
    ];

    // Save fields
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
        }
    }

    // Handle JSON fields separately
    if (isset($_POST['netflix_subtitle_urls'])) {
        $subtitle_urls = $_POST['netflix_subtitle_urls'];
        
        // Validate JSON
        if (!empty($subtitle_urls)) {
            $decoded = json_decode($subtitle_urls, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                update_post_meta($post_id, '_netflix_subtitle_urls', $subtitle_urls);
            }
        } else {
            update_post_meta($post_id, '_netflix_subtitle_urls', '');
        }
    }
}

// Custom columns for movie post list
function netflix_movie_columns($columns) {
    $new_columns = [];
    $new_columns['cb'] = $columns['cb'];
    $new_columns['title'] = $columns['title'];
    $new_columns['poster'] = __('Poster', 'netflix-streaming');
    $new_columns['duration'] = __('Duration', 'netflix-streaming');
    $new_columns['rating'] = __('Rating', 'netflix-streaming');
    $new_columns['release_date'] = __('Release Date', 'netflix-streaming');
    $new_columns['backend_sync'] = __('Backend', 'netflix-streaming');
    $new_columns['date'] = $columns['date'];
    
    return $new_columns;
}

// Custom columns for TV show post list
function netflix_show_columns($columns) {
    $new_columns = [];
    $new_columns['cb'] = $columns['cb'];
    $new_columns['title'] = $columns['title'];
    $new_columns['poster'] = __('Poster', 'netflix-streaming');
    $new_columns['seasons'] = __('Seasons', 'netflix-streaming');
    $new_columns['episodes'] = __('Episodes', 'netflix-streaming');
    $new_columns['rating'] = __('Rating', 'netflix-streaming');
    $new_columns['first_air_date'] = __('First Air Date', 'netflix-streaming');
    $new_columns['backend_sync'] = __('Backend', 'netflix-streaming');
    $new_columns['date'] = $columns['date'];
    
    return $new_columns;
}

// Movie column content
function netflix_movie_column_content($column, $post_id) {
    switch ($column) {
        case 'poster':
            if (has_post_thumbnail($post_id)) {
                echo get_the_post_thumbnail($post_id, [50, 75]);
            } else {
                echo '<div style="width:50px;height:75px;background:#ccc;display:flex;align-items:center;justify-content:center;font-size:10px;">No Image</div>';
            }
            break;
            
        case 'duration':
            $duration = get_post_meta($post_id, '_netflix_duration', true);
            echo $duration ? esc_html($duration . ' min') : '—';
            break;
            
        case 'rating':
            $rating = get_post_meta($post_id, '_netflix_rating', true);
            echo $rating ? esc_html($rating . '/10') : '—';
            break;
            
        case 'release_date':
            $date = get_post_meta($post_id, '_netflix_release_date', true);
            echo $date ? esc_html(date('Y-m-d', strtotime($date))) : '—';
            break;
            
        case 'backend_sync':
            $is_imported = get_post_meta($post_id, '_netflix_imported', true) === '1';
            if ($is_imported) {
                echo '<span style="color:green;">✓ Synced</span>';
            } else {
                echo '<span style="color:orange;">Manual</span>';
            }
            break;
    }
}

// TV show column content
function netflix_show_column_content($column, $post_id) {
    switch ($column) {
        case 'poster':
            if (has_post_thumbnail($post_id)) {
                echo get_the_post_thumbnail($post_id, [50, 75]);
            } else {
                echo '<div style="width:50px;height:75px;background:#ccc;display:flex;align-items:center;justify-content:center;font-size:10px;">No Image</div>';
            }
            break;
            
        case 'seasons':
            $seasons = get_post_meta($post_id, '_netflix_total_seasons', true);
            echo $seasons ? esc_html($seasons) : '—';
            break;
            
        case 'episodes':
            $episodes = get_post_meta($post_id, '_netflix_total_episodes', true);
            echo $episodes ? esc_html($episodes) : '—';
            break;
            
        case 'rating':
            $rating = get_post_meta($post_id, '_netflix_rating', true);
            echo $rating ? esc_html($rating . '/10') : '—';
            break;
            
        case 'first_air_date':
            $date = get_post_meta($post_id, '_netflix_first_air_date', true);
            echo $date ? esc_html(date('Y-m-d', strtotime($date))) : '—';
            break;
            
        case 'backend_sync':
            $is_imported = get_post_meta($post_id, '_netflix_imported', true) === '1';
            if ($is_imported) {
                echo '<span style="color:green;">✓ Synced</span>';
            } else {
                echo '<span style="color:orange;">Manual</span>';
            }
            break;
    }
}