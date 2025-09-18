<?php
// Netflix Streaming Platform Shortcodes

if (!defined('ABSPATH')) exit;

// Main Netflix player shortcode
add_shortcode('netflix_player', 'netflix_player_shortcode');
function netflix_player_shortcode($atts) {
    $atts = shortcode_atts([
        'id' => '',
        'type' => 'movie', // movie, show, episode
        'width' => '100%',
        'height' => 'auto',
        'autoplay' => 'false',
        'controls' => 'true',
        'quality' => 'auto',
        'language' => 'en',
        'subtitle' => 'auto',
        'theme' => 'dark',
        'watermark' => 'true',
        'drm' => 'auto'
    ], $atts, 'netflix_player');

    if (empty($atts['id'])) {
        return '<div class="netflix-error">' . __('No content ID specified', 'netflix-streaming') . '</div>';
    }

    // Get content details
    $post = get_post($atts['id']);
    if (!$post || !in_array($post->post_type, ['netflix_movie', 'netflix_show', 'netflix_episode'])) {
        return '<div class="netflix-error">' . __('Content not found', 'netflix-streaming') . '</div>';
    }

    // Check user permissions
    $subscription_required = get_post_meta($post->ID, '_netflix_subscription_required', true) ?: 'free';
    if (!netflix_check_user_access($subscription_required)) {
        return netflix_subscription_required_message($subscription_required);
    }

    // Get video URLs
    $video_url = get_post_meta($post->ID, '_netflix_video_url', true);
    $hls_url = get_post_meta($post->ID, '_netflix_hls_url', true);
    $subtitle_urls = get_post_meta($post->ID, '_netflix_subtitle_urls', true);

    if (empty($video_url) && empty($hls_url)) {
        return '<div class="netflix-error">' . __('No video available for this content', 'netflix-streaming') . '</div>';
    }

    // Parse subtitle URLs
    $subtitles = [];
    if (!empty($subtitle_urls)) {
        $decoded_subtitles = json_decode($subtitle_urls, true);
        if (is_array($decoded_subtitles)) {
            $subtitles = $decoded_subtitles;
        }
    }

    // Generate unique player ID
    $player_id = 'netflix-player-' . $post->ID . '-' . wp_rand(1000, 9999);

    // Get additional metadata
    $meta = get_post_meta($post->ID);
    $poster_url = get_the_post_thumbnail_url($post->ID, 'netflix-backdrop') ?: '';
    $duration = $meta['_netflix_duration'][0] ?? '';
    $rating = $meta['_netflix_rating'][0] ?? '';

    // Build player HTML
    ob_start();
    ?>
    <div class="netflix-player-container netflix-theme-<?php echo esc_attr($atts['theme']); ?>" data-post-id="<?php echo esc_attr($post->ID); ?>">
        <?php if ($atts['watermark'] === 'true'): ?>
            <div class="netflix-watermark">
                <?php 
                $watermark_url = get_option('netflix_watermark_url', NETFLIX_PLUGIN_URL . 'assets/watermark.png');
                $watermark_link = get_option('netflix_watermark_link', home_url());
                ?>
                <a href="<?php echo esc_url($watermark_link); ?>" target="_blank">
                    <img src="<?php echo esc_url($watermark_url); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" />
                </a>
            </div>
        <?php endif; ?>

        <!-- Video Player -->
        <div class="netflix-video-wrapper">
            <video 
                id="<?php echo esc_attr($player_id); ?>"
                class="netflix-video-player"
                width="<?php echo esc_attr($atts['width']); ?>"
                height="<?php echo esc_attr($atts['height']); ?>"
                poster="<?php echo esc_attr($poster_url); ?>"
                <?php echo $atts['controls'] === 'true' ? 'controls' : ''; ?>
                <?php echo $atts['autoplay'] === 'true' ? 'autoplay' : ''; ?>
                preload="metadata"
                playsinline
                crossorigin="anonymous"
            >
                <?php if ($hls_url): ?>
                    <source src="<?php echo esc_url($hls_url); ?>" type="application/x-mpegURL" data-quality="HLS">
                <?php endif; ?>
                
                <?php if ($video_url): ?>
                    <source src="<?php echo esc_url($video_url); ?>" type="video/mp4" data-quality="MP4">
                <?php endif; ?>

                <?php if (!empty($subtitles)): ?>
                    <?php foreach ($subtitles as $lang => $subtitle_url): ?>
                        <track 
                            kind="subtitles" 
                            src="<?php echo esc_url($subtitle_url); ?>" 
                            srclang="<?php echo esc_attr($lang); ?>" 
                            label="<?php echo esc_attr(netflix_get_language_name($lang)); ?>"
                            <?php echo $lang === $atts['language'] ? 'default' : ''; ?>
                        >
                    <?php endforeach; ?>
                <?php endif; ?>

                <p><?php _e('Your browser does not support HTML5 video.', 'netflix-streaming'); ?></p>
            </video>

            <!-- Custom Controls Overlay -->
            <div class="netflix-controls-overlay" style="display: none;">
                <div class="netflix-controls">
                    <div class="netflix-controls-left">
                        <button class="netflix-btn netflix-play-pause" aria-label="<?php _e('Play/Pause', 'netflix-streaming'); ?>">
                            <span class="play-icon">▶</span>
                            <span class="pause-icon" style="display: none;">⏸</span>
                        </button>
                        <button class="netflix-btn netflix-backward" aria-label="<?php _e('Backward 10s', 'netflix-streaming'); ?>">⏪</button>
                        <button class="netflix-btn netflix-forward" aria-label="<?php _e('Forward 10s', 'netflix-streaming'); ?>">⏩</button>
                        <div class="netflix-volume-control">
                            <button class="netflix-btn netflix-mute" aria-label="<?php _e('Mute/Unmute', 'netflix-streaming'); ?>">🔊</button>
                            <input type="range" class="netflix-volume-slider" min="0" max="1" step="0.1" value="1">
                        </div>
                        <div class="netflix-time-display">
                            <span class="current-time">0:00</span>
                            <span class="separator">/</span>
                            <span class="total-time">0:00</span>
                        </div>
                    </div>

                    <div class="netflix-controls-center">
                        <div class="netflix-progress-container">
                            <div class="netflix-progress-bar">
                                <div class="netflix-progress-buffer"></div>
                                <div class="netflix-progress-played"></div>
                                <div class="netflix-progress-handle"></div>
                            </div>
                        </div>
                    </div>

                    <div class="netflix-controls-right">
                        <?php if (!empty($subtitles)): ?>
                        <div class="netflix-subtitle-menu">
                            <button class="netflix-btn netflix-subtitle-btn" aria-label="<?php _e('Subtitles', 'netflix-streaming'); ?>">CC</button>
                            <div class="netflix-subtitle-options" style="display: none;">
                                <div class="netflix-option" data-lang="off"><?php _e('Off', 'netflix-streaming'); ?></div>
                                <?php foreach ($subtitles as $lang => $subtitle_url): ?>
                                    <div class="netflix-option" data-lang="<?php echo esc_attr($lang); ?>">
                                        <?php echo esc_html(netflix_get_language_name($lang)); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="netflix-quality-menu">
                            <button class="netflix-btn netflix-quality-btn" aria-label="<?php _e('Quality', 'netflix-streaming'); ?>">HD</button>
                            <div class="netflix-quality-options" style="display: none;">
                                <div class="netflix-option" data-quality="auto"><?php _e('Auto', 'netflix-streaming'); ?></div>
                                <div class="netflix-option" data-quality="1080p">1080p</div>
                                <div class="netflix-option" data-quality="720p">720p</div>
                                <div class="netflix-option" data-quality="480p">480p</div>
                            </div>
                        </div>

                        <div class="netflix-speed-menu">
                            <button class="netflix-btn netflix-speed-btn" aria-label="<?php _e('Playback Speed', 'netflix-streaming'); ?>">1x</button>
                            <div class="netflix-speed-options" style="display: none;">
                                <div class="netflix-option" data-speed="0.5">0.5x</div>
                                <div class="netflix-option" data-speed="0.75">0.75x</div>
                                <div class="netflix-option" data-speed="1" class="active">1x</div>
                                <div class="netflix-option" data-speed="1.25">1.25x</div>
                                <div class="netflix-option" data-speed="1.5">1.5x</div>
                                <div class="netflix-option" data-speed="2">2x</div>
                            </div>
                        </div>

                        <button class="netflix-btn netflix-fullscreen" aria-label="<?php _e('Fullscreen', 'netflix-streaming'); ?>">⛶</button>
                    </div>
                </div>
            </div>

            <!-- Loading Spinner -->
            <div class="netflix-loading" style="display: none;">
                <div class="netflix-spinner"></div>
            </div>

            <!-- Error Message -->
            <div class="netflix-error-message" style="display: none;">
                <p><?php _e('An error occurred while loading the video.', 'netflix-streaming'); ?></p>
                <button class="netflix-btn netflix-retry"><?php _e('Retry', 'netflix-streaming'); ?></button>
            </div>
        </div>

        <!-- Content Info -->
        <div class="netflix-content-info">
            <h3 class="netflix-content-title"><?php echo esc_html($post->post_title); ?></h3>
            <div class="netflix-content-meta">
                <?php if ($duration): ?>
                    <span class="netflix-duration"><?php echo esc_html($duration); ?> <?php _e('min', 'netflix-streaming'); ?></span>
                <?php endif; ?>
                
                <?php if ($rating): ?>
                    <span class="netflix-rating">⭐ <?php echo esc_html($rating); ?>/10</span>
                <?php endif; ?>

                <?php 
                $genres = get_the_terms($post->ID, 'netflix_genre');
                if ($genres && !is_wp_error($genres)): 
                ?>
                    <span class="netflix-genres">
                        <?php echo esc_html(implode(', ', wp_list_pluck($genres, 'name'))); ?>
                    </span>
                <?php endif; ?>
            </div>

            <?php if ($post->post_excerpt): ?>
                <div class="netflix-content-excerpt">
                    <?php echo wp_kses_post($post->post_excerpt); ?>
                </div>
            <?php endif; ?>

            <!-- User Actions -->
            <?php if (is_user_logged_in()): ?>
                <div class="netflix-user-actions">
                    <button class="netflix-btn netflix-favorite" data-post-id="<?php echo esc_attr($post->ID); ?>" data-type="<?php echo esc_attr($post->post_type); ?>">
                        <span class="add-text">❤ <?php _e('Add to Favorites', 'netflix-streaming'); ?></span>
                        <span class="remove-text" style="display: none;">💔 <?php _e('Remove from Favorites', 'netflix-streaming'); ?></span>
                    </button>
                    
                    <button class="netflix-btn netflix-watchlist" data-post-id="<?php echo esc_attr($post->ID); ?>">
                        <span class="add-text">+ <?php _e('Add to Watchlist', 'netflix-streaming'); ?></span>
                        <span class="remove-text" style="display: none;">✓ <?php _e('In Watchlist', 'netflix-streaming'); ?></span>
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        // Initialize Netflix player
        window.NetflixPlayer.init('<?php echo esc_js($player_id); ?>', {
            postId: <?php echo intval($post->ID); ?>,
            type: '<?php echo esc_js($post->post_type); ?>',
            quality: '<?php echo esc_js($atts['quality']); ?>',
            language: '<?php echo esc_js($atts['language']); ?>',
            autoplay: <?php echo $atts['autoplay'] === 'true' ? 'true' : 'false'; ?>,
            theme: '<?php echo esc_js($atts['theme']); ?>',
            subtitles: <?php echo json_encode($subtitles); ?>,
            userId: <?php echo intval(get_current_user_id()); ?>,
            ajaxUrl: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
            nonce: '<?php echo wp_create_nonce('netflix_player_' . $post->ID); ?>'
        });
    });
    </script>
    <?php

    return ob_get_clean();
}

// Movie grid/carousel shortcode
add_shortcode('netflix_movies', 'netflix_movies_shortcode');
function netflix_movies_shortcode($atts) {
    $atts = shortcode_atts([
        'limit' => 12,
        'genre' => '',
        'year' => '',
        'rating' => '',
        'layout' => 'grid', // grid, carousel, list
        'columns' => 4,
        'show_title' => 'true',
        'show_meta' => 'true',
        'show_excerpt' => 'false',
        'order' => 'date',
        'orderby' => 'DESC'
    ], $atts, 'netflix_movies');

    $query_args = [
        'post_type' => 'netflix_movie',
        'post_status' => 'publish',
        'posts_per_page' => intval($atts['limit']),
        'orderby' => $atts['order'],
        'order' => $atts['orderby'],
        'meta_query' => []
    ];

    // Add taxonomy filters
    $tax_query = [];
    
    if (!empty($atts['genre'])) {
        $tax_query[] = [
            'taxonomy' => 'netflix_genre',
            'field' => 'slug',
            'terms' => explode(',', $atts['genre'])
        ];
    }
    
    if (!empty($atts['year'])) {
        $tax_query[] = [
            'taxonomy' => 'netflix_year',
            'field' => 'slug',
            'terms' => explode(',', $atts['year'])
        ];
    }
    
    if (!empty($tax_query)) {
        $query_args['tax_query'] = $tax_query;
    }

    $movies = new WP_Query($query_args);

    if (!$movies->have_posts()) {
        return '<div class="netflix-no-content">' . __('No movies found.', 'netflix-streaming') . '</div>';
    }

    ob_start();
    ?>
    <div class="netflix-movies-container netflix-layout-<?php echo esc_attr($atts['layout']); ?>" data-columns="<?php echo esc_attr($atts['columns']); ?>">
        <?php if ($atts['layout'] === 'carousel'): ?>
            <div class="netflix-carousel-controls">
                <button class="netflix-carousel-prev">‹</button>
                <button class="netflix-carousel-next">›</button>
            </div>
        <?php endif; ?>

        <div class="netflix-movies-grid">
            <?php while ($movies->have_posts()): $movies->the_post(); ?>
                <div class="netflix-movie-item">
                    <div class="netflix-movie-poster">
                        <a href="<?php the_permalink(); ?>">
                            <?php if (has_post_thumbnail()): ?>
                                <?php the_post_thumbnail('netflix-poster', ['alt' => get_the_title()]); ?>
                            <?php else: ?>
                                <div class="netflix-no-poster">
                                    <span><?php _e('No Image', 'netflix-streaming'); ?></span>
                                </div>
                            <?php endif; ?>
                        </a>
                        
                        <div class="netflix-movie-overlay">
                            <div class="netflix-movie-actions">
                                <button class="netflix-btn netflix-play-btn" data-post-id="<?php echo get_the_ID(); ?>">
                                    ▶ <?php _e('Play', 'netflix-streaming'); ?>
                                </button>
                                
                                <?php if (is_user_logged_in()): ?>
                                    <button class="netflix-btn netflix-favorite-btn" data-post-id="<?php echo get_the_ID(); ?>" data-type="movie">
                                        ❤
                                    </button>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($atts['show_meta'] === 'true'): ?>
                                <div class="netflix-movie-meta">
                                    <?php 
                                    $duration = get_post_meta(get_the_ID(), '_netflix_duration', true);
                                    $rating = get_post_meta(get_the_ID(), '_netflix_rating', true);
                                    ?>
                                    
                                    <?php if ($duration): ?>
                                        <span class="netflix-duration"><?php echo esc_html($duration); ?>m</span>
                                    <?php endif; ?>
                                    
                                    <?php if ($rating): ?>
                                        <span class="netflix-rating">⭐ <?php echo esc_html($rating); ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($atts['show_title'] === 'true'): ?>
                        <div class="netflix-movie-info">
                            <h4 class="netflix-movie-title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h4>
                            
                            <?php if ($atts['show_excerpt'] === 'true' && has_excerpt()): ?>
                                <div class="netflix-movie-excerpt">
                                    <?php the_excerpt(); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <?php if ($atts['layout'] === 'carousel'): ?>
        <script>
        jQuery(document).ready(function($) {
            window.NetflixCarousel.init('.netflix-movies-container');
        });
        </script>
    <?php endif; ?>
    <?php

    wp_reset_postdata();
    return ob_get_clean();
}

// TV Shows shortcode (similar to movies)
add_shortcode('netflix_shows', 'netflix_shows_shortcode');
function netflix_shows_shortcode($atts) {
    $atts = shortcode_atts([
        'limit' => 12,
        'genre' => '',
        'year' => '',
        'layout' => 'grid',
        'columns' => 4,
        'show_title' => 'true',
        'show_meta' => 'true',
        'show_excerpt' => 'false',
        'order' => 'date',
        'orderby' => 'DESC'
    ], $atts, 'netflix_shows');

    $query_args = [
        'post_type' => 'netflix_show',
        'post_status' => 'publish',
        'posts_per_page' => intval($atts['limit']),
        'orderby' => $atts['order'],
        'order' => $atts['orderby']
    ];

    // Add taxonomy filters (similar to movies)
    $tax_query = [];
    
    if (!empty($atts['genre'])) {
        $tax_query[] = [
            'taxonomy' => 'netflix_genre',
            'field' => 'slug',
            'terms' => explode(',', $atts['genre'])
        ];
    }
    
    if (!empty($atts['year'])) {
        $tax_query[] = [
            'taxonomy' => 'netflix_year',
            'field' => 'slug',
            'terms' => explode(',', $atts['year'])
        ];
    }
    
    if (!empty($tax_query)) {
        $query_args['tax_query'] = $tax_query;
    }

    $shows = new WP_Query($query_args);

    if (!$shows->have_posts()) {
        return '<div class="netflix-no-content">' . __('No TV shows found.', 'netflix-streaming') . '</div>';
    }

    ob_start();
    ?>
    <div class="netflix-shows-container netflix-layout-<?php echo esc_attr($atts['layout']); ?>" data-columns="<?php echo esc_attr($atts['columns']); ?>">
        <div class="netflix-shows-grid">
            <?php while ($shows->have_posts()): $shows->the_post(); ?>
                <div class="netflix-show-item">
                    <div class="netflix-show-poster">
                        <a href="<?php the_permalink(); ?>">
                            <?php if (has_post_thumbnail()): ?>
                                <?php the_post_thumbnail('netflix-poster', ['alt' => get_the_title()]); ?>
                            <?php else: ?>
                                <div class="netflix-no-poster">
                                    <span><?php _e('No Image', 'netflix-streaming'); ?></span>
                                </div>
                            <?php endif; ?>
                        </a>
                        
                        <div class="netflix-show-overlay">
                            <div class="netflix-show-actions">
                                <a href="<?php the_permalink(); ?>" class="netflix-btn netflix-view-btn">
                                    👁 <?php _e('View Episodes', 'netflix-streaming'); ?>
                                </a>
                                
                                <?php if (is_user_logged_in()): ?>
                                    <button class="netflix-btn netflix-favorite-btn" data-post-id="<?php echo get_the_ID(); ?>" data-type="show">
                                        ❤
                                    </button>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($atts['show_meta'] === 'true'): ?>
                                <div class="netflix-show-meta">
                                    <?php 
                                    $seasons = get_post_meta(get_the_ID(), '_netflix_total_seasons', true);
                                    $episodes = get_post_meta(get_the_ID(), '_netflix_total_episodes', true);
                                    $rating = get_post_meta(get_the_ID(), '_netflix_rating', true);
                                    ?>
                                    
                                    <?php if ($seasons): ?>
                                        <span class="netflix-seasons"><?php echo esc_html($seasons); ?> <?php _e('Seasons', 'netflix-streaming'); ?></span>
                                    <?php endif; ?>
                                    
                                    <?php if ($episodes): ?>
                                        <span class="netflix-episodes"><?php echo esc_html($episodes); ?> <?php _e('Episodes', 'netflix-streaming'); ?></span>
                                    <?php endif; ?>
                                    
                                    <?php if ($rating): ?>
                                        <span class="netflix-rating">⭐ <?php echo esc_html($rating); ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($atts['show_title'] === 'true'): ?>
                        <div class="netflix-show-info">
                            <h4 class="netflix-show-title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h4>
                            
                            <?php if ($atts['show_excerpt'] === 'true' && has_excerpt()): ?>
                                <div class="netflix-show-excerpt">
                                    <?php the_excerpt(); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php

    wp_reset_postdata();
    return ob_get_clean();
}

// Search shortcode
add_shortcode('netflix_search', 'netflix_search_shortcode');
function netflix_search_shortcode($atts) {
    $atts = shortcode_atts([
        'placeholder' => __('Search movies and TV shows...', 'netflix-streaming'),
        'show_filters' => 'true',
        'ajax' => 'true'
    ], $atts, 'netflix_search');

    ob_start();
    ?>
    <div class="netflix-search-container">
        <form class="netflix-search-form" method="get" action="<?php echo esc_url(home_url('/')); ?>">
            <div class="netflix-search-input-group">
                <input 
                    type="text" 
                    name="s" 
                    class="netflix-search-input" 
                    placeholder="<?php echo esc_attr($atts['placeholder']); ?>"
                    value="<?php echo esc_attr(get_search_query()); ?>"
                    autocomplete="off"
                >
                <input type="hidden" name="post_type" value="netflix_movie,netflix_show">
                <button type="submit" class="netflix-search-submit">🔍</button>
            </div>

            <?php if ($atts['show_filters'] === 'true'): ?>
                <div class="netflix-search-filters">
                    <select name="netflix_genre" class="netflix-filter-select">
                        <option value=""><?php _e('All Genres', 'netflix-streaming'); ?></option>
                        <?php
                        $genres = get_terms(['taxonomy' => 'netflix_genre', 'hide_empty' => true]);
                        foreach ($genres as $genre):
                        ?>
                            <option value="<?php echo esc_attr($genre->slug); ?>" <?php selected($_GET['netflix_genre'] ?? '', $genre->slug); ?>>
                                <?php echo esc_html($genre->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="netflix_year" class="netflix-filter-select">
                        <option value=""><?php _e('All Years', 'netflix-streaming'); ?></option>
                        <?php
                        $years = get_terms(['taxonomy' => 'netflix_year', 'hide_empty' => true, 'orderby' => 'name', 'order' => 'DESC']);
                        foreach ($years as $year):
                        ?>
                            <option value="<?php echo esc_attr($year->slug); ?>" <?php selected($_GET['netflix_year'] ?? '', $year->slug); ?>>
                                <?php echo esc_html($year->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="content_type" class="netflix-filter-select">
                        <option value=""><?php _e('Movies & Shows', 'netflix-streaming'); ?></option>
                        <option value="netflix_movie" <?php selected($_GET['content_type'] ?? '', 'netflix_movie'); ?>><?php _e('Movies Only', 'netflix-streaming'); ?></option>
                        <option value="netflix_show" <?php selected($_GET['content_type'] ?? '', 'netflix_show'); ?>><?php _e('TV Shows Only', 'netflix-streaming'); ?></option>
                    </select>
                </div>
            <?php endif; ?>
        </form>

        <?php if ($atts['ajax'] === 'true'): ?>
            <div class="netflix-search-results" style="display: none;">
                <div class="netflix-search-loading"><?php _e('Searching...', 'netflix-streaming'); ?></div>
                <div class="netflix-search-content"></div>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($atts['ajax'] === 'true'): ?>
        <script>
        jQuery(document).ready(function($) {
            window.NetflixSearch.init('.netflix-search-container');
        });
        </script>
    <?php endif; ?>
    <?php

    return ob_get_clean();
}

// Helper functions
function netflix_check_user_access($subscription_required) {
    if (!is_user_logged_in() && $subscription_required !== 'free') {
        return false;
    }

    if ($subscription_required === 'free') {
        return true;
    }

    $user_id = get_current_user_id();
    $user_subscription = get_user_meta($user_id, 'netflix_subscription_type', true) ?: 'free';
    $subscription_expires = get_user_meta($user_id, 'netflix_subscription_expires', true);

    // Check if subscription is expired
    if ($subscription_expires && strtotime($subscription_expires) < time()) {
        return false;
    }

    $subscription_levels = ['free' => 0, 'basic' => 1, 'premium' => 2];
    $user_level = $subscription_levels[$user_subscription] ?? 0;
    $required_level = $subscription_levels[$subscription_required] ?? 0;

    return $user_level >= $required_level;
}

function netflix_subscription_required_message($subscription_required) {
    if (!is_user_logged_in()) {
        $login_url = wp_login_url(get_permalink());
        return '<div class="netflix-subscription-required">
            <h3>' . __('Login Required', 'netflix-streaming') . '</h3>
            <p>' . __('Please log in to watch this content.', 'netflix-streaming') . '</p>
            <a href="' . esc_url($login_url) . '" class="netflix-btn netflix-login-btn">' . __('Login', 'netflix-streaming') . '</a>
        </div>';
    }

    $subscription_url = home_url('/subscription'); // You can customize this URL
    return '<div class="netflix-subscription-required">
        <h3>' . sprintf(__('%s Subscription Required', 'netflix-streaming'), ucfirst($subscription_required)) . '</h3>
        <p>' . sprintf(__('This content requires a %s subscription to watch.', 'netflix-streaming'), $subscription_required) . '</p>
        <a href="' . esc_url($subscription_url) . '" class="netflix-btn netflix-subscribe-btn">' . __('Upgrade Subscription', 'netflix-streaming') . '</a>
    </div>';
}

function netflix_get_language_name($lang_code) {
    $languages = [
        'en' => __('English', 'netflix-streaming'),
        'bn' => __('Bengali', 'netflix-streaming'),
        'es' => __('Spanish', 'netflix-streaming'),
        'fr' => __('French', 'netflix-streaming'),
        'de' => __('German', 'netflix-streaming'),
        'it' => __('Italian', 'netflix-streaming'),
        'pt' => __('Portuguese', 'netflix-streaming'),
        'ru' => __('Russian', 'netflix-streaming'),
        'ja' => __('Japanese', 'netflix-streaming'),
        'ko' => __('Korean', 'netflix-streaming'),
        'zh' => __('Chinese', 'netflix-streaming'),
        'ar' => __('Arabic', 'netflix-streaming'),
        'hi' => __('Hindi', 'netflix-streaming')
    ];

    return $languages[$lang_code] ?? ucfirst($lang_code);
}

// Keep the original CMPlayer shortcode for backward compatibility
add_shortcode('cmplayer', function($atts) {
    // Convert old attributes to new format
    $new_atts = [
        'id' => $atts['id'] ?? '',
        'type' => 'movie',
        'width' => $atts['width'] ?? '100%',
        'height' => $atts['height'] ?? 'auto',
        'autoplay' => $atts['autoplay'] ?? 'false',
        'controls' => 'true',
        'theme' => get_option('cmplayer_theme', 'dark')
    ];
    
    return netflix_player_shortcode($new_atts);
});