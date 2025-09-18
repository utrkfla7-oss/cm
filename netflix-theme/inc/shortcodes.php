<?php
/**
 * Netflix Theme Shortcodes
 * 
 * @package Netflix_Theme
 */

if (!defined('ABSPATH')) exit;

/**
 * Netflix Video Player Shortcode
 */
function netflix_player_shortcode($atts) {
    $atts = shortcode_atts(array(
        'id' => '',
        'url' => '',
        'poster' => '',
        'subtitles' => '',
        'quality' => 'auto',
        'autoplay' => 'false',
        'width' => '100%',
        'height' => '500px',
        'type' => 'video', // video, movie, episode
    ), $atts, 'netflix_player');

    // If ID is provided, get the post data
    if (!empty($atts['id'])) {
        $post = get_post($atts['id']);
        if ($post) {
            $video_url = get_post_meta($post->ID, '_netflix_video_url', true);
            $poster_url = get_the_post_thumbnail_url($post->ID, 'netflix-landscape');
            $subtitles = get_post_meta($post->ID, '_netflix_subtitles', true);
            $title = $post->post_title;
            $description = $post->post_excerpt;
            
            // Override with meta data if available
            if ($video_url && empty($atts['url'])) {
                $atts['url'] = $video_url;
            }
            if ($poster_url && empty($atts['poster'])) {
                $atts['poster'] = $poster_url;
            }
            if ($subtitles && empty($atts['subtitles'])) {
                $atts['subtitles'] = $subtitles;
            }
        }
    }

    // Return error if no video URL
    if (empty($atts['url'])) {
        return '<div class="netflix-error">' . esc_html__('No video URL provided', 'netflix-theme') . '</div>';
    }

    // Check subscription access
    if (!netflix_check_content_access($atts['id'])) {
        return netflix_subscription_required_message();
    }

    // Generate unique player ID
    $player_id = 'netflix-player-' . uniqid();
    
    ob_start();
    ?>
    <div class="netflix-player-container" style="width: <?php echo esc_attr($atts['width']); ?>; height: <?php echo esc_attr($atts['height']); ?>;">
        <video
            id="<?php echo esc_attr($player_id); ?>"
            class="netflix-video-player video-js vjs-default-skin"
            controls
            preload="auto"
            width="100%"
            height="100%"
            <?php if (!empty($atts['poster'])): ?>poster="<?php echo esc_url($atts['poster']); ?>"<?php endif; ?>
            <?php if ($atts['autoplay'] === 'true'): ?>autoplay<?php endif; ?>
            data-setup="{}"
        >
            <source src="<?php echo esc_url($atts['url']); ?>" type="video/mp4" />
            
            <?php if (!empty($atts['subtitles'])): ?>
                <?php 
                $subtitle_data = json_decode($atts['subtitles'], true);
                if (is_array($subtitle_data)):
                    foreach ($subtitle_data as $subtitle):
                ?>
                    <track
                        kind="subtitles"
                        src="<?php echo esc_url($subtitle['src']); ?>"
                        srclang="<?php echo esc_attr($subtitle['lang']); ?>"
                        label="<?php echo esc_attr($subtitle['label']); ?>"
                        <?php if (isset($subtitle['default']) && $subtitle['default']): ?>default<?php endif; ?>
                    />
                <?php 
                    endforeach;
                endif;
                ?>
            <?php endif; ?>
            
            <p class="vjs-no-js">
                <?php esc_html_e('To view this video please enable JavaScript, and consider upgrading to a web browser that', 'netflix-theme'); ?>
                <a href="https://videojs.com/html5-video-support/" target="_blank">
                    <?php esc_html_e('supports HTML5 video', 'netflix-theme'); ?>
                </a>.
            </p>
        </video>

        <!-- Custom Controls Overlay -->
        <div class="netflix-player-overlay">
            <div class="netflix-player-controls">
                <button class="netflix-control-btn netflix-play-pause" title="<?php esc_attr_e('Play/Pause', 'netflix-theme'); ?>">
                    <i class="fas fa-play"></i>
                </button>
                <button class="netflix-control-btn netflix-backward" title="<?php esc_attr_e('Backward 10s', 'netflix-theme'); ?>">
                    <i class="fas fa-backward"></i>
                </button>
                <button class="netflix-control-btn netflix-forward" title="<?php esc_attr_e('Forward 10s', 'netflix-theme'); ?>">
                    <i class="fas fa-forward"></i>
                </button>
                <div class="netflix-progress-container">
                    <div class="netflix-progress-bar">
                        <div class="netflix-progress-fill"></div>
                    </div>
                </div>
                <span class="netflix-time-display">0:00 / 0:00</span>
                <button class="netflix-control-btn netflix-volume" title="<?php esc_attr_e('Volume', 'netflix-theme'); ?>">
                    <i class="fas fa-volume-up"></i>
                </button>
                <button class="netflix-control-btn netflix-subtitles" title="<?php esc_attr_e('Subtitles', 'netflix-theme'); ?>">
                    <i class="fas fa-closed-captioning"></i>
                </button>
                <button class="netflix-control-btn netflix-quality" title="<?php esc_attr_e('Quality', 'netflix-theme'); ?>">
                    <i class="fas fa-cog"></i>
                </button>
                <button class="netflix-control-btn netflix-fullscreen" title="<?php esc_attr_e('Fullscreen', 'netflix-theme'); ?>">
                    <i class="fas fa-expand"></i>
                </button>
            </div>
        </div>

        <!-- Additional Action Buttons -->
        <?php if (!empty($atts['id'])): ?>
        <div class="netflix-video-actions">
            <button class="netflix-action-btn netflix-add-to-list" data-id="<?php echo esc_attr($atts['id']); ?>">
                <i class="fas fa-plus"></i>
                <span><?php esc_html_e('My List', 'netflix-theme'); ?></span>
            </button>
            <button class="netflix-action-btn netflix-like" data-id="<?php echo esc_attr($atts['id']); ?>">
                <i class="fas fa-thumbs-up"></i>
            </button>
            <button class="netflix-action-btn netflix-dislike" data-id="<?php echo esc_attr($atts['id']); ?>">
                <i class="fas fa-thumbs-down"></i>
            </button>
            <button class="netflix-action-btn netflix-share" data-id="<?php echo esc_attr($atts['id']); ?>">
                <i class="fas fa-share"></i>
                <span><?php esc_html_e('Share', 'netflix-theme'); ?></span>
            </button>
        </div>
        <?php endif; ?>
    </div>

    <script>
    jQuery(document).ready(function($) {
        // Initialize Video.js player
        var player = videojs('<?php echo esc_js($player_id); ?>', {
            responsive: true,
            fluid: true,
            playbackRates: [0.5, 1, 1.25, 1.5, 2],
            plugins: {
                // Add HLS support
                hlsQualitySelector: {
                    displayCurrentQuality: true,
                }
            }
        });

        // Custom Netflix controls
        $('.netflix-play-pause').click(function() {
            if (player.paused()) {
                player.play();
                $(this).find('i').removeClass('fa-play').addClass('fa-pause');
            } else {
                player.pause();
                $(this).find('i').removeClass('fa-pause').addClass('fa-play');
            }
        });

        $('.netflix-backward').click(function() {
            player.currentTime(player.currentTime() - 10);
        });

        $('.netflix-forward').click(function() {
            player.currentTime(player.currentTime() + 10);
        });

        $('.netflix-fullscreen').click(function() {
            if (player.isFullscreen()) {
                player.exitFullscreen();
            } else {
                player.requestFullscreen();
            }
        });

        // Update progress bar
        player.on('timeupdate', function() {
            var currentTime = player.currentTime();
            var duration = player.duration();
            var progress = (currentTime / duration) * 100;
            
            $('.netflix-progress-fill').css('width', progress + '%');
            $('.netflix-time-display').text(
                formatTime(currentTime) + ' / ' + formatTime(duration)
            );
        });

        // Progress bar click
        $('.netflix-progress-bar').click(function(e) {
            var rect = this.getBoundingClientRect();
            var x = e.clientX - rect.left;
            var percentage = x / rect.width;
            player.currentTime(player.duration() * percentage);
        });

        function formatTime(seconds) {
            var minutes = Math.floor(seconds / 60);
            var remainingSeconds = Math.floor(seconds % 60);
            return minutes + ':' + (remainingSeconds < 10 ? '0' : '') + remainingSeconds;
        }

        // Track analytics
        player.on('play', function() {
            netflix_track_event('video_play', '<?php echo esc_js($atts['id']); ?>');
        });

        player.on('ended', function() {
            netflix_track_event('video_complete', '<?php echo esc_js($atts['id']); ?>');
        });
    });
    </script>
    <?php
    
    return ob_get_clean();
}
add_shortcode('netflix_player', 'netflix_player_shortcode');

/**
 * Netflix Movies Grid Shortcode
 */
function netflix_movies_shortcode($atts) {
    $atts = shortcode_atts(array(
        'limit' => 12,
        'genre' => '',
        'year' => '',
        'featured' => '',
        'premium_only' => '',
        'columns' => 4,
        'orderby' => 'date',
        'order' => 'DESC',
    ), $atts, 'netflix_movies');

    $args = array(
        'post_type' => 'movie',
        'posts_per_page' => intval($atts['limit']),
        'orderby' => $atts['orderby'],
        'order' => $atts['order'],
        'meta_query' => array(),
        'tax_query' => array(),
    );

    // Filter by genre
    if (!empty($atts['genre'])) {
        $args['tax_query'][] = array(
            'taxonomy' => 'genre',
            'field' => 'slug',
            'terms' => explode(',', $atts['genre']),
        );
    }

    // Filter by year
    if (!empty($atts['year'])) {
        $args['tax_query'][] = array(
            'taxonomy' => 'release_year',
            'field' => 'slug',
            'terms' => explode(',', $atts['year']),
        );
    }

    // Filter by featured
    if (!empty($atts['featured'])) {
        $args['meta_query'][] = array(
            'key' => '_netflix_featured',
            'value' => '1',
            'compare' => '=',
        );
    }

    // Filter by premium
    if (!empty($atts['premium_only'])) {
        $args['meta_query'][] = array(
            'key' => '_netflix_premium_only',
            'value' => '1',
            'compare' => '=',
        );
    }

    $query = new WP_Query($args);
    
    ob_start();
    
    if ($query->have_posts()) :
    ?>
    <div class="netflix-movies-grid" data-columns="<?php echo esc_attr($atts['columns']); ?>">
        <?php while ($query->have_posts()) : $query->the_post(); ?>
            <div class="netflix-movie-card">
                <div class="netflix-movie-poster">
                    <a href="<?php the_permalink(); ?>">
                        <?php if (has_post_thumbnail()) : ?>
                            <?php the_post_thumbnail('netflix-poster', array('alt' => get_the_title())); ?>
                        <?php else : ?>
                            <div class="netflix-no-poster">
                                <i class="fas fa-film"></i>
                                <span><?php esc_html_e('No Image', 'netflix-theme'); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="netflix-movie-overlay">
                            <div class="netflix-movie-actions">
                                <button class="netflix-action-btn netflix-play-movie" data-id="<?php the_ID(); ?>">
                                    <i class="fas fa-play"></i>
                                </button>
                                <button class="netflix-action-btn netflix-add-to-list" data-id="<?php the_ID(); ?>">
                                    <i class="fas fa-plus"></i>
                                </button>
                                <button class="netflix-action-btn netflix-more-info" data-id="<?php the_ID(); ?>">
                                    <i class="fas fa-info-circle"></i>
                                </button>
                            </div>
                            
                            <div class="netflix-movie-info">
                                <h3 class="netflix-movie-title"><?php the_title(); ?></h3>
                                <div class="netflix-movie-meta">
                                    <?php
                                    $duration = get_post_meta(get_the_ID(), '_netflix_duration', true);
                                    $rating = get_post_meta(get_the_ID(), '_netflix_content_rating', true);
                                    
                                    if ($duration) {
                                        echo '<span class="duration">' . esc_html($duration) . ' min</span>';
                                    }
                                    if ($rating) {
                                        echo '<span class="rating">' . esc_html($rating) . '</span>';
                                    }
                                    ?>
                                </div>
                                <p class="netflix-movie-description">
                                    <?php echo wp_trim_words(get_the_excerpt(), 15); ?>
                                </p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
    <?php
    wp_reset_postdata();
    else :
    ?>
    <div class="netflix-no-content">
        <p><?php esc_html_e('No movies found.', 'netflix-theme'); ?></p>
    </div>
    <?php
    endif;
    
    return ob_get_clean();
}
add_shortcode('netflix_movies', 'netflix_movies_shortcode');

/**
 * Netflix TV Shows Grid Shortcode
 */
function netflix_tv_shows_shortcode($atts) {
    $atts = shortcode_atts(array(
        'limit' => 12,
        'genre' => '',
        'year' => '',
        'featured' => '',
        'premium_only' => '',
        'columns' => 4,
        'orderby' => 'date',
        'order' => 'DESC',
    ), $atts, 'netflix_tv_shows');

    $args = array(
        'post_type' => 'tv_show',
        'posts_per_page' => intval($atts['limit']),
        'orderby' => $atts['orderby'],
        'order' => $atts['order'],
        'meta_query' => array(),
        'tax_query' => array(),
    );

    // Apply same filters as movies
    // Filter by genre
    if (!empty($atts['genre'])) {
        $args['tax_query'][] = array(
            'taxonomy' => 'genre',
            'field' => 'slug',
            'terms' => explode(',', $atts['genre']),
        );
    }

    // Filter by year
    if (!empty($atts['year'])) {
        $args['tax_query'][] = array(
            'taxonomy' => 'release_year',
            'field' => 'slug',
            'terms' => explode(',', $atts['year']),
        );
    }

    // Filter by featured
    if (!empty($atts['featured'])) {
        $args['meta_query'][] = array(
            'key' => '_netflix_featured',
            'value' => '1',
            'compare' => '=',
        );
    }

    // Filter by premium
    if (!empty($atts['premium_only'])) {
        $args['meta_query'][] = array(
            'key' => '_netflix_premium_only',
            'value' => '1',
            'compare' => '=',
        );
    }

    $query = new WP_Query($args);
    
    ob_start();
    
    if ($query->have_posts()) :
    ?>
    <div class="netflix-tv-shows-grid" data-columns="<?php echo esc_attr($atts['columns']); ?>">
        <?php while ($query->have_posts()) : $query->the_post(); ?>
            <div class="netflix-tv-show-card">
                <div class="netflix-tv-show-poster">
                    <a href="<?php the_permalink(); ?>">
                        <?php if (has_post_thumbnail()) : ?>
                            <?php the_post_thumbnail('netflix-poster', array('alt' => get_the_title())); ?>
                        <?php else : ?>
                            <div class="netflix-no-poster">
                                <i class="fas fa-tv"></i>
                                <span><?php esc_html_e('No Image', 'netflix-theme'); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="netflix-tv-show-overlay">
                            <div class="netflix-tv-show-actions">
                                <button class="netflix-action-btn netflix-play-show" data-id="<?php the_ID(); ?>">
                                    <i class="fas fa-play"></i>
                                </button>
                                <button class="netflix-action-btn netflix-add-to-list" data-id="<?php the_ID(); ?>">
                                    <i class="fas fa-plus"></i>
                                </button>
                                <button class="netflix-action-btn netflix-more-info" data-id="<?php the_ID(); ?>">
                                    <i class="fas fa-info-circle"></i>
                                </button>
                            </div>
                            
                            <div class="netflix-tv-show-info">
                                <h3 class="netflix-tv-show-title"><?php the_title(); ?></h3>
                                <div class="netflix-tv-show-meta">
                                    <?php
                                    $seasons = get_post_meta(get_the_ID(), '_netflix_seasons', true);
                                    $rating = get_post_meta(get_the_ID(), '_netflix_content_rating', true);
                                    
                                    if ($seasons) {
                                        printf(esc_html__('%d Seasons', 'netflix-theme'), $seasons);
                                    }
                                    if ($rating) {
                                        echo '<span class="rating">' . esc_html($rating) . '</span>';
                                    }
                                    ?>
                                </div>
                                <p class="netflix-tv-show-description">
                                    <?php echo wp_trim_words(get_the_excerpt(), 15); ?>
                                </p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
    <?php
    wp_reset_postdata();
    else :
    ?>
    <div class="netflix-no-content">
        <p><?php esc_html_e('No TV shows found.', 'netflix-theme'); ?></p>
    </div>
    <?php
    endif;
    
    return ob_get_clean();
}
add_shortcode('netflix_tv_shows', 'netflix_tv_shows_shortcode');

/**
 * Netflix Content Slider Shortcode
 */
function netflix_slider_shortcode($atts) {
    $atts = shortcode_atts(array(
        'title' => '',
        'type' => 'movie', // movie, tv_show, both
        'category' => '',
        'limit' => 20,
        'featured' => '',
        'orderby' => 'date',
        'order' => 'DESC',
    ), $atts, 'netflix_slider');

    $post_types = array();
    if ($atts['type'] === 'both') {
        $post_types = array('movie', 'tv_show');
    } else {
        $post_types = array($atts['type']);
    }

    $args = array(
        'post_type' => $post_types,
        'posts_per_page' => intval($atts['limit']),
        'orderby' => $atts['orderby'],
        'order' => $atts['order'],
        'meta_query' => array(),
    );

    // Filter by category/genre
    if (!empty($atts['category'])) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'genre',
                'field' => 'slug',
                'terms' => explode(',', $atts['category']),
            ),
        );
    }

    // Filter by featured
    if (!empty($atts['featured'])) {
        $args['meta_query'][] = array(
            'key' => '_netflix_featured',
            'value' => '1',
            'compare' => '=',
        );
    }

    $query = new WP_Query($args);
    
    ob_start();
    
    if ($query->have_posts()) :
    ?>
    <div class="netflix-content-row">
        <?php if (!empty($atts['title'])) : ?>
            <h2 class="netflix-row-title"><?php echo esc_html($atts['title']); ?></h2>
        <?php endif; ?>
        
        <div class="netflix-slider">
            <div class="netflix-slider-content">
                <?php while ($query->have_posts()) : $query->the_post(); ?>
                    <div class="netflix-card" style="background-image: url('<?php echo esc_url(get_the_post_thumbnail_url(get_the_ID(), 'netflix-thumb')); ?>');">
                        <a href="<?php the_permalink(); ?>" class="netflix-card-link">
                            <div class="netflix-card-overlay">
                                <h3 class="netflix-card-title"><?php the_title(); ?></h3>
                                <div class="netflix-card-meta">
                                    <?php
                                    $post_type = get_post_type();
                                    if ($post_type === 'movie') {
                                        $duration = get_post_meta(get_the_ID(), '_netflix_duration', true);
                                        if ($duration) {
                                            echo esc_html($duration . ' min');
                                        }
                                    } elseif ($post_type === 'tv_show') {
                                        $seasons = get_post_meta(get_the_ID(), '_netflix_seasons', true);
                                        if ($seasons) {
                                            printf(esc_html__('%d Seasons', 'netflix-theme'), $seasons);
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endwhile; ?>
            </div>
            
            <!-- Slider Navigation -->
            <button class="netflix-slider-prev" aria-label="<?php esc_attr_e('Previous', 'netflix-theme'); ?>">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button class="netflix-slider-next" aria-label="<?php esc_attr_e('Next', 'netflix-theme'); ?>">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>
    <?php
    wp_reset_postdata();
    else :
    ?>
    <div class="netflix-no-content">
        <p><?php esc_html_e('No content found.', 'netflix-theme'); ?></p>
    </div>
    <?php
    endif;
    
    return ob_get_clean();
}
add_shortcode('netflix_slider', 'netflix_slider_shortcode');

/**
 * Helper Functions
 */

/**
 * Check if user has access to content
 */
function netflix_check_content_access($post_id) {
    if (empty($post_id)) {
        return true;
    }

    $premium_only = get_post_meta($post_id, '_netflix_premium_only', true);
    
    if ($premium_only) {
        if (!is_user_logged_in()) {
            return false;
        }
        
        $user_subscription = get_user_meta(get_current_user_id(), 'netflix_subscription', true);
        return in_array($user_subscription, array('basic', 'standard', 'premium'));
    }
    
    return true;
}

/**
 * Subscription required message
 */
function netflix_subscription_required_message() {
    ob_start();
    ?>
    <div class="netflix-subscription-required">
        <div class="netflix-subscription-message">
            <i class="fas fa-lock"></i>
            <h3><?php esc_html_e('Premium Content', 'netflix-theme'); ?></h3>
            <p><?php esc_html_e('This content requires a subscription to view.', 'netflix-theme'); ?></p>
            <button class="netflix-btn netflix-btn-primary netflix-show-subscription-modal">
                <?php esc_html_e('Subscribe Now', 'netflix-theme'); ?>
            </button>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Netflix Content Row Function (used in templates)
 */
function netflix_content_row($type, $title) {
    $args = array(
        'post_type' => array('movie', 'tv_show'),
        'posts_per_page' => 20,
        'orderby' => 'date',
        'order' => 'DESC',
        'meta_query' => array(),
    );

    switch ($type) {
        case 'trending':
            $args['orderby'] = 'meta_value_num';
            $args['meta_key'] = '_netflix_views';
            break;
        case 'new_releases':
            $args['date_query'] = array(
                array(
                    'after' => '30 days ago',
                ),
            );
            break;
        case 'popular_movies':
            $args['post_type'] = 'movie';
            $args['orderby'] = 'meta_value_num';
            $args['meta_key'] = '_netflix_views';
            break;
        case 'popular_tv_shows':
            $args['post_type'] = 'tv_show';
            $args['orderby'] = 'meta_value_num';
            $args['meta_key'] = '_netflix_views';
            break;
        case 'my_list':
            if (is_user_logged_in()) {
                $my_list = get_user_meta(get_current_user_id(), 'netflix_my_list', true);
                if (!empty($my_list) && is_array($my_list)) {
                    $args['post__in'] = $my_list;
                    $args['orderby'] = 'post__in';
                } else {
                    return; // No items in list
                }
            } else {
                return; // Not logged in
            }
            break;
        case 'continue_watching':
            if (is_user_logged_in()) {
                $watch_history = get_user_meta(get_current_user_id(), 'netflix_watch_history', true);
                if (!empty($watch_history) && is_array($watch_history)) {
                    $post_ids = array_keys($watch_history);
                    $args['post__in'] = $post_ids;
                    $args['orderby'] = 'modified';
                } else {
                    return; // No watch history
                }
            } else {
                return; // Not logged in
            }
            break;
    }

    $query = new WP_Query($args);
    
    if ($query->have_posts()) :
    ?>
    <div class="netflix-row">
        <h2><?php echo esc_html($title); ?></h2>
        <div class="netflix-slider">
            <div class="netflix-slider-content">
                <?php while ($query->have_posts()) : $query->the_post(); ?>
                    <div class="netflix-card" style="background-image: url('<?php echo esc_url(get_the_post_thumbnail_url(get_the_ID(), 'netflix-thumb')); ?>');">
                        <a href="<?php the_permalink(); ?>" class="netflix-card-link">
                            <div class="netflix-card-overlay">
                                <h3 class="netflix-card-title"><?php the_title(); ?></h3>
                                <div class="netflix-card-meta">
                                    <?php
                                    $post_type = get_post_type();
                                    if ($post_type === 'movie') {
                                        $duration = get_post_meta(get_the_ID(), '_netflix_duration', true);
                                        if ($duration) {
                                            echo esc_html($duration . ' min');
                                        }
                                    } elseif ($post_type === 'tv_show') {
                                        $seasons = get_post_meta(get_the_ID(), '_netflix_seasons', true);
                                        if ($seasons) {
                                            printf(esc_html__('%d Seasons', 'netflix-theme'), $seasons);
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endwhile; ?>
            </div>
            
            <!-- Slider Navigation -->
            <button class="netflix-slider-prev" aria-label="<?php esc_attr_e('Previous', 'netflix-theme'); ?>">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button class="netflix-slider-next" aria-label="<?php esc_attr_e('Next', 'netflix-theme'); ?>">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>
    <?php
    wp_reset_postdata();
    endif;
}