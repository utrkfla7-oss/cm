<?php
/**
 * Shortcodes for Frontend Display
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Register shortcodes
add_action('init', 'mtcm_register_shortcodes');
function mtcm_register_shortcodes() {
    add_shortcode('mtcm_movie', 'mtcm_movie_shortcode');
    add_shortcode('mtcm_tv_show', 'mtcm_tv_show_shortcode');
    add_shortcode('mtcm_movie_list', 'mtcm_movie_list_shortcode');
    add_shortcode('mtcm_tv_list', 'mtcm_tv_list_shortcode');
}

// Movie shortcode
function mtcm_movie_shortcode($atts) {
    $atts = shortcode_atts(array(
        'id' => '',
        'tmdb_id' => '',
        'display' => 'full', // full, compact, poster
        'show_rating' => 'true',
        'show_genre' => 'true',
        'show_cast' => 'true',
        'show_director' => 'true',
        'show_runtime' => 'true',
        'show_release_date' => 'true',
        'poster_size' => 'medium'
    ), $atts, 'mtcm_movie');

    // Get movie by ID or TMDB ID
    $movie = null;
    if (!empty($atts['id'])) {
        $movie = get_post($atts['id']);
    } elseif (!empty($atts['tmdb_id'])) {
        $posts = get_posts(array(
            'post_type' => 'mtcm_movie',
            'meta_key' => '_mtcm_tmdb_id',
            'meta_value' => $atts['tmdb_id'],
            'numberposts' => 1
        ));
        if (!empty($posts)) {
            $movie = $posts[0];
        }
    }

    if (!$movie || $movie->post_type !== 'mtcm_movie') {
        return '<div class="mtcm-error">' . __('Movie not found.', 'movie-tv-classic-manager') . '</div>';
    }

    // Get movie data
    $movie_data = mtcm_get_movie_data($movie->ID);
    
    // Generate output based on display type
    switch ($atts['display']) {
        case 'poster':
            return mtcm_render_movie_poster($movie, $movie_data, $atts);
        case 'compact':
            return mtcm_render_movie_compact($movie, $movie_data, $atts);
        default:
            return mtcm_render_movie_full($movie, $movie_data, $atts);
    }
}

// TV Show shortcode
function mtcm_tv_show_shortcode($atts) {
    $atts = shortcode_atts(array(
        'id' => '',
        'tmdb_id' => '',
        'display' => 'full', // full, compact, poster
        'show_rating' => 'true',
        'show_genre' => 'true',
        'show_cast' => 'true',
        'show_creator' => 'true',
        'show_seasons' => 'true',
        'show_network' => 'true',
        'show_status' => 'true',
        'poster_size' => 'medium'
    ), $atts, 'mtcm_tv_show');

    // Get TV show by ID or TMDB ID
    $tv_show = null;
    if (!empty($atts['id'])) {
        $tv_show = get_post($atts['id']);
    } elseif (!empty($atts['tmdb_id'])) {
        $posts = get_posts(array(
            'post_type' => 'mtcm_tv_show',
            'meta_key' => '_mtcm_tmdb_id',
            'meta_value' => $atts['tmdb_id'],
            'numberposts' => 1
        ));
        if (!empty($posts)) {
            $tv_show = $posts[0];
        }
    }

    if (!$tv_show || $tv_show->post_type !== 'mtcm_tv_show') {
        return '<div class="mtcm-error">' . __('TV show not found.', 'movie-tv-classic-manager') . '</div>';
    }

    // Get TV show data
    $show_data = mtcm_get_tv_show_data($tv_show->ID);
    
    // Generate output based on display type
    switch ($atts['display']) {
        case 'poster':
            return mtcm_render_tv_show_poster($tv_show, $show_data, $atts);
        case 'compact':
            return mtcm_render_tv_show_compact($tv_show, $show_data, $atts);
        default:
            return mtcm_render_tv_show_full($tv_show, $show_data, $atts);
    }
}

// Movie list shortcode
function mtcm_movie_list_shortcode($atts) {
    $atts = shortcode_atts(array(
        'limit' => 5,
        'genre' => '',
        'year' => '',
        'orderby' => 'date',
        'order' => 'DESC',
        'display' => 'compact',
        'columns' => 1
    ), $atts, 'mtcm_movie_list');

    $args = array(
        'post_type' => 'mtcm_movie',
        'posts_per_page' => intval($atts['limit']),
        'orderby' => $atts['orderby'],
        'order' => $atts['order'],
        'post_status' => 'publish'
    );

    // Add taxonomy filters
    $tax_query = array();
    if (!empty($atts['genre'])) {
        $tax_query[] = array(
            'taxonomy' => 'mtcm_genre',
            'field' => 'slug',
            'terms' => explode(',', $atts['genre'])
        );
    }
    if (!empty($atts['year'])) {
        $tax_query[] = array(
            'taxonomy' => 'mtcm_year',
            'field' => 'slug',
            'terms' => explode(',', $atts['year'])
        );
    }
    if (!empty($tax_query)) {
        $args['tax_query'] = $tax_query;
    }

    $movies = get_posts($args);
    
    if (empty($movies)) {
        return '<div class="mtcm-no-results">' . __('No movies found.', 'movie-tv-classic-manager') . '</div>';
    }

    return mtcm_render_movie_list($movies, $atts);
}

// TV show list shortcode
function mtcm_tv_list_shortcode($atts) {
    $atts = shortcode_atts(array(
        'limit' => 5,
        'genre' => '',
        'year' => '',
        'orderby' => 'date',
        'order' => 'DESC',
        'display' => 'compact',
        'columns' => 1
    ), $atts, 'mtcm_tv_list');

    $args = array(
        'post_type' => 'mtcm_tv_show',
        'posts_per_page' => intval($atts['limit']),
        'orderby' => $atts['orderby'],
        'order' => $atts['order'],
        'post_status' => 'publish'
    );

    // Add taxonomy filters
    $tax_query = array();
    if (!empty($atts['genre'])) {
        $tax_query[] = array(
            'taxonomy' => 'mtcm_genre',
            'field' => 'slug',
            'terms' => explode(',', $atts['genre'])
        );
    }
    if (!empty($atts['year'])) {
        $tax_query[] = array(
            'taxonomy' => 'mtcm_year',
            'field' => 'slug',
            'terms' => explode(',', $atts['year'])
        );
    }
    if (!empty($tax_query)) {
        $args['tax_query'] = $tax_query;
    }

    $shows = get_posts($args);
    
    if (empty($shows)) {
        return '<div class="mtcm-no-results">' . __('No TV shows found.', 'movie-tv-classic-manager') . '</div>';
    }

    return mtcm_render_tv_show_list($shows, $atts);
}

// Helper function to get movie data
function mtcm_get_movie_data($post_id) {
    return array(
        'release_date' => get_post_meta($post_id, '_mtcm_release_date', true),
        'runtime' => get_post_meta($post_id, '_mtcm_runtime', true),
        'director' => get_post_meta($post_id, '_mtcm_director', true),
        'cast' => get_post_meta($post_id, '_mtcm_cast', true),
        'rating' => get_post_meta($post_id, '_mtcm_rating', true),
        'imdb_id' => get_post_meta($post_id, '_mtcm_imdb_id', true),
        'budget' => get_post_meta($post_id, '_mtcm_budget', true),
        'revenue' => get_post_meta($post_id, '_mtcm_revenue', true),
        'tagline' => get_post_meta($post_id, '_mtcm_tagline', true),
        'country' => get_post_meta($post_id, '_mtcm_country', true),
        'language' => get_post_meta($post_id, '_mtcm_language', true),
        'poster_url' => get_post_meta($post_id, '_mtcm_poster_url', true),
        'backdrop_url' => get_post_meta($post_id, '_mtcm_backdrop_url', true),
        'tmdb_id' => get_post_meta($post_id, '_mtcm_tmdb_id', true),
    );
}

// Helper function to get TV show data
function mtcm_get_tv_show_data($post_id) {
    return array(
        'first_air_date' => get_post_meta($post_id, '_mtcm_first_air_date', true),
        'last_air_date' => get_post_meta($post_id, '_mtcm_last_air_date', true),
        'creator' => get_post_meta($post_id, '_mtcm_creator', true),
        'cast' => get_post_meta($post_id, '_mtcm_cast', true),
        'rating' => get_post_meta($post_id, '_mtcm_rating', true),
        'imdb_id' => get_post_meta($post_id, '_mtcm_imdb_id', true),
        'total_seasons' => get_post_meta($post_id, '_mtcm_total_seasons', true),
        'total_episodes' => get_post_meta($post_id, '_mtcm_total_episodes', true),
        'episode_runtime' => get_post_meta($post_id, '_mtcm_episode_runtime', true),
        'network' => get_post_meta($post_id, '_mtcm_network', true),
        'status' => get_post_meta($post_id, '_mtcm_status', true),
        'country' => get_post_meta($post_id, '_mtcm_country', true),
        'language' => get_post_meta($post_id, '_mtcm_language', true),
        'poster_url' => get_post_meta($post_id, '_mtcm_poster_url', true),
        'backdrop_url' => get_post_meta($post_id, '_mtcm_backdrop_url', true),
        'tmdb_id' => get_post_meta($post_id, '_mtcm_tmdb_id', true),
    );
}

// Render movie full display
function mtcm_render_movie_full($movie, $data, $atts) {
    $output = '<div class="mtcm-movie mtcm-movie-full">';
    
    // Movie poster and title section
    $output .= '<div class="mtcm-movie-header">';
    
    // Poster
    if (!empty($data['poster_url']) || has_post_thumbnail($movie->ID)) {
        $output .= '<div class="mtcm-movie-poster">';
        if (!empty($data['poster_url'])) {
            $output .= '<img src="' . esc_url($data['poster_url']) . '" alt="' . esc_attr($movie->post_title) . '" class="mtcm-poster-image" />';
        } elseif (has_post_thumbnail($movie->ID)) {
            $output .= get_the_post_thumbnail($movie->ID, 'mtcm-poster', array('class' => 'mtcm-poster-image'));
        }
        $output .= '</div>';
    }
    
    // Movie info
    $output .= '<div class="mtcm-movie-info">';
    $output .= '<h3 class="mtcm-movie-title">' . esc_html($movie->post_title) . '</h3>';
    
    if (!empty($data['tagline'])) {
        $output .= '<p class="mtcm-movie-tagline">"' . esc_html($data['tagline']) . '"</p>';
    }
    
    // Movie details grid
    $output .= '<div class="mtcm-movie-details">';
    
    if ($atts['show_release_date'] === 'true' && !empty($data['release_date'])) {
        $output .= '<div class="mtcm-detail-item"><strong>' . __('Release Date:', 'movie-tv-classic-manager') . '</strong> ' . date_i18n(get_option('date_format'), strtotime($data['release_date'])) . '</div>';
    }
    
    if ($atts['show_runtime'] === 'true' && !empty($data['runtime'])) {
        $output .= '<div class="mtcm-detail-item"><strong>' . __('Runtime:', 'movie-tv-classic-manager') . '</strong> ' . sprintf(_n('%d minute', '%d minutes', $data['runtime'], 'movie-tv-classic-manager'), $data['runtime']) . '</div>';
    }
    
    if ($atts['show_director'] === 'true' && !empty($data['director'])) {
        $output .= '<div class="mtcm-detail-item"><strong>' . __('Director:', 'movie-tv-classic-manager') . '</strong> ' . esc_html($data['director']) . '</div>';
    }
    
    if ($atts['show_rating'] === 'true' && !empty($data['rating'])) {
        $output .= '<div class="mtcm-detail-item"><strong>' . __('Rating:', 'movie-tv-classic-manager') . '</strong> <span class="mtcm-rating">' . esc_html($data['rating']) . '</span></div>';
    }
    
    if ($atts['show_genre'] === 'true') {
        $genres = get_the_terms($movie->ID, 'mtcm_genre');
        if ($genres && !is_wp_error($genres)) {
            $genre_names = wp_list_pluck($genres, 'name');
            $output .= '<div class="mtcm-detail-item"><strong>' . __('Genre:', 'movie-tv-classic-manager') . '</strong> ' . esc_html(implode(', ', $genre_names)) . '</div>';
        }
    }
    
    if ($atts['show_cast'] === 'true' && !empty($data['cast'])) {
        $output .= '<div class="mtcm-detail-item"><strong>' . __('Cast:', 'movie-tv-classic-manager') . '</strong> ' . esc_html($data['cast']) . '</div>';
    }
    
    $output .= '</div>'; // .mtcm-movie-details
    $output .= '</div>'; // .mtcm-movie-info
    $output .= '</div>'; // .mtcm-movie-header
    
    // Movie description
    if (!empty($movie->post_content)) {
        $output .= '<div class="mtcm-movie-description">';
        $output .= '<h4>' . __('Synopsis', 'movie-tv-classic-manager') . '</h4>';
        $output .= '<div class="mtcm-description-content">' . apply_filters('the_content', $movie->post_content) . '</div>';
        $output .= '</div>';
    }
    
    $output .= '</div>'; // .mtcm-movie
    
    return $output;
}

// Render movie compact display
function mtcm_render_movie_compact($movie, $data, $atts) {
    $output = '<div class="mtcm-movie mtcm-movie-compact">';
    
    // Small poster
    if (!empty($data['poster_url']) || has_post_thumbnail($movie->ID)) {
        $output .= '<div class="mtcm-movie-poster-small">';
        if (!empty($data['poster_url'])) {
            $output .= '<img src="' . esc_url($data['poster_url']) . '" alt="' . esc_attr($movie->post_title) . '" class="mtcm-poster-image-small" />';
        } elseif (has_post_thumbnail($movie->ID)) {
            $output .= get_the_post_thumbnail($movie->ID, 'mtcm-thumbnail', array('class' => 'mtcm-poster-image-small'));
        }
        $output .= '</div>';
    }
    
    $output .= '<div class="mtcm-movie-info-compact">';
    $output .= '<h4 class="mtcm-movie-title">' . esc_html($movie->post_title) . '</h4>';
    
    $details = array();
    if (!empty($data['release_date'])) {
        $details[] = date('Y', strtotime($data['release_date']));
    }
    if (!empty($data['runtime'])) {
        $details[] = $data['runtime'] . 'min';
    }
    if ($atts['show_rating'] === 'true' && !empty($data['rating'])) {
        $details[] = $data['rating'];
    }
    
    if (!empty($details)) {
        $output .= '<p class="mtcm-movie-meta">' . esc_html(implode(' • ', $details)) . '</p>';
    }
    
    if (!empty($movie->post_excerpt)) {
        $output .= '<p class="mtcm-movie-excerpt">' . esc_html($movie->post_excerpt) . '</p>';
    }
    
    $output .= '</div>';
    $output .= '</div>';
    
    return $output;
}

// Render movie poster only
function mtcm_render_movie_poster($movie, $data, $atts) {
    $output = '<div class="mtcm-movie mtcm-movie-poster-only">';
    
    if (!empty($data['poster_url']) || has_post_thumbnail($movie->ID)) {
        if (!empty($data['poster_url'])) {
            $output .= '<img src="' . esc_url($data['poster_url']) . '" alt="' . esc_attr($movie->post_title) . '" class="mtcm-poster-image" />';
        } elseif (has_post_thumbnail($movie->ID)) {
            $output .= get_the_post_thumbnail($movie->ID, 'mtcm-poster', array('class' => 'mtcm-poster-image'));
        }
        $output .= '<div class="mtcm-poster-overlay">';
        $output .= '<h4 class="mtcm-movie-title">' . esc_html($movie->post_title) . '</h4>';
        if (!empty($data['release_date'])) {
            $output .= '<p class="mtcm-movie-year">' . date('Y', strtotime($data['release_date'])) . '</p>';
        }
        $output .= '</div>';
    } else {
        $output .= '<div class="mtcm-no-poster">';
        $output .= '<h4 class="mtcm-movie-title">' . esc_html($movie->post_title) . '</h4>';
        if (!empty($data['release_date'])) {
            $output .= '<p class="mtcm-movie-year">' . date('Y', strtotime($data['release_date'])) . '</p>';
        }
        $output .= '</div>';
    }
    
    $output .= '</div>';
    
    return $output;
}

// Render TV show full display
function mtcm_render_tv_show_full($tv_show, $data, $atts) {
    $output = '<div class="mtcm-tv-show mtcm-tv-show-full">';
    
    // TV show poster and title section
    $output .= '<div class="mtcm-tv-show-header">';
    
    // Poster
    if (!empty($data['poster_url']) || has_post_thumbnail($tv_show->ID)) {
        $output .= '<div class="mtcm-tv-show-poster">';
        if (!empty($data['poster_url'])) {
            $output .= '<img src="' . esc_url($data['poster_url']) . '" alt="' . esc_attr($tv_show->post_title) . '" class="mtcm-poster-image" />';
        } elseif (has_post_thumbnail($tv_show->ID)) {
            $output .= get_the_post_thumbnail($tv_show->ID, 'mtcm-poster', array('class' => 'mtcm-poster-image'));
        }
        $output .= '</div>';
    }
    
    // TV show info
    $output .= '<div class="mtcm-tv-show-info">';
    $output .= '<h3 class="mtcm-tv-show-title">' . esc_html($tv_show->post_title) . '</h3>';
    
    // TV show details grid
    $output .= '<div class="mtcm-tv-show-details">';
    
    if (!empty($data['first_air_date'])) {
        $air_date_text = date_i18n(get_option('date_format'), strtotime($data['first_air_date']));
        if (!empty($data['last_air_date'])) {
            $air_date_text .= ' - ' . date_i18n(get_option('date_format'), strtotime($data['last_air_date']));
        } else {
            $air_date_text .= ' - ' . __('Present', 'movie-tv-classic-manager');
        }
        $output .= '<div class="mtcm-detail-item"><strong>' . __('Air Dates:', 'movie-tv-classic-manager') . '</strong> ' . $air_date_text . '</div>';
    }
    
    if ($atts['show_seasons'] === 'true' && !empty($data['total_seasons'])) {
        $seasons_text = sprintf(_n('%d season', '%d seasons', $data['total_seasons'], 'movie-tv-classic-manager'), $data['total_seasons']);
        if (!empty($data['total_episodes'])) {
            $seasons_text .= ' (' . sprintf(_n('%d episode', '%d episodes', $data['total_episodes'], 'movie-tv-classic-manager'), $data['total_episodes']) . ')';
        }
        $output .= '<div class="mtcm-detail-item"><strong>' . __('Seasons:', 'movie-tv-classic-manager') . '</strong> ' . $seasons_text . '</div>';
    }
    
    if ($atts['show_creator'] === 'true' && !empty($data['creator'])) {
        $output .= '<div class="mtcm-detail-item"><strong>' . __('Creator:', 'movie-tv-classic-manager') . '</strong> ' . esc_html($data['creator']) . '</div>';
    }
    
    if ($atts['show_network'] === 'true' && !empty($data['network'])) {
        $output .= '<div class="mtcm-detail-item"><strong>' . __('Network:', 'movie-tv-classic-manager') . '</strong> ' . esc_html($data['network']) . '</div>';
    }
    
    if ($atts['show_status'] === 'true' && !empty($data['status'])) {
        $output .= '<div class="mtcm-detail-item"><strong>' . __('Status:', 'movie-tv-classic-manager') . '</strong> <span class="mtcm-status">' . esc_html($data['status']) . '</span></div>';
    }
    
    if ($atts['show_rating'] === 'true' && !empty($data['rating'])) {
        $output .= '<div class="mtcm-detail-item"><strong>' . __('Rating:', 'movie-tv-classic-manager') . '</strong> <span class="mtcm-rating">' . esc_html($data['rating']) . '</span></div>';
    }
    
    if ($atts['show_genre'] === 'true') {
        $genres = get_the_terms($tv_show->ID, 'mtcm_genre');
        if ($genres && !is_wp_error($genres)) {
            $genre_names = wp_list_pluck($genres, 'name');
            $output .= '<div class="mtcm-detail-item"><strong>' . __('Genre:', 'movie-tv-classic-manager') . '</strong> ' . esc_html(implode(', ', $genre_names)) . '</div>';
        }
    }
    
    if ($atts['show_cast'] === 'true' && !empty($data['cast'])) {
        $output .= '<div class="mtcm-detail-item"><strong>' . __('Cast:', 'movie-tv-classic-manager') . '</strong> ' . esc_html($data['cast']) . '</div>';
    }
    
    $output .= '</div>'; // .mtcm-tv-show-details
    $output .= '</div>'; // .mtcm-tv-show-info
    $output .= '</div>'; // .mtcm-tv-show-header
    
    // TV show description
    if (!empty($tv_show->post_content)) {
        $output .= '<div class="mtcm-tv-show-description">';
        $output .= '<h4>' . __('Synopsis', 'movie-tv-classic-manager') . '</h4>';
        $output .= '<div class="mtcm-description-content">' . apply_filters('the_content', $tv_show->post_content) . '</div>';
        $output .= '</div>';
    }
    
    $output .= '</div>'; // .mtcm-tv-show
    
    return $output;
}

// Render TV show compact display
function mtcm_render_tv_show_compact($tv_show, $data, $atts) {
    $output = '<div class="mtcm-tv-show mtcm-tv-show-compact">';
    
    // Small poster
    if (!empty($data['poster_url']) || has_post_thumbnail($tv_show->ID)) {
        $output .= '<div class="mtcm-tv-show-poster-small">';
        if (!empty($data['poster_url'])) {
            $output .= '<img src="' . esc_url($data['poster_url']) . '" alt="' . esc_attr($tv_show->post_title) . '" class="mtcm-poster-image-small" />';
        } elseif (has_post_thumbnail($tv_show->ID)) {
            $output .= get_the_post_thumbnail($tv_show->ID, 'mtcm-thumbnail', array('class' => 'mtcm-poster-image-small'));
        }
        $output .= '</div>';
    }
    
    $output .= '<div class="mtcm-tv-show-info-compact">';
    $output .= '<h4 class="mtcm-tv-show-title">' . esc_html($tv_show->post_title) . '</h4>';
    
    $details = array();
    if (!empty($data['first_air_date'])) {
        $year = date('Y', strtotime($data['first_air_date']));
        if (!empty($data['last_air_date'])) {
            $year .= '-' . date('Y', strtotime($data['last_air_date']));
        } else {
            $year .= '-Present';
        }
        $details[] = $year;
    }
    if (!empty($data['total_seasons'])) {
        $details[] = $data['total_seasons'] . ' seasons';
    }
    if ($atts['show_rating'] === 'true' && !empty($data['rating'])) {
        $details[] = $data['rating'];
    }
    
    if (!empty($details)) {
        $output .= '<p class="mtcm-tv-show-meta">' . esc_html(implode(' • ', $details)) . '</p>';
    }
    
    if (!empty($tv_show->post_excerpt)) {
        $output .= '<p class="mtcm-tv-show-excerpt">' . esc_html($tv_show->post_excerpt) . '</p>';
    }
    
    $output .= '</div>';
    $output .= '</div>';
    
    return $output;
}

// Render TV show poster only
function mtcm_render_tv_show_poster($tv_show, $data, $atts) {
    $output = '<div class="mtcm-tv-show mtcm-tv-show-poster-only">';
    
    if (!empty($data['poster_url']) || has_post_thumbnail($tv_show->ID)) {
        if (!empty($data['poster_url'])) {
            $output .= '<img src="' . esc_url($data['poster_url']) . '" alt="' . esc_attr($tv_show->post_title) . '" class="mtcm-poster-image" />';
        } elseif (has_post_thumbnail($tv_show->ID)) {
            $output .= get_the_post_thumbnail($tv_show->ID, 'mtcm-poster', array('class' => 'mtcm-poster-image'));
        }
        $output .= '<div class="mtcm-poster-overlay">';
        $output .= '<h4 class="mtcm-tv-show-title">' . esc_html($tv_show->post_title) . '</h4>';
        if (!empty($data['first_air_date'])) {
            $output .= '<p class="mtcm-tv-show-year">' . date('Y', strtotime($data['first_air_date'])) . '</p>';
        }
        $output .= '</div>';
    } else {
        $output .= '<div class="mtcm-no-poster">';
        $output .= '<h4 class="mtcm-tv-show-title">' . esc_html($tv_show->post_title) . '</h4>';
        if (!empty($data['first_air_date'])) {
            $output .= '<p class="mtcm-tv-show-year">' . date('Y', strtotime($data['first_air_date'])) . '</p>';
        }
        $output .= '</div>';
    }
    
    $output .= '</div>';
    
    return $output;
}

// Render movie list
function mtcm_render_movie_list($movies, $atts) {
    $columns = max(1, intval($atts['columns']));
    $output = '<div class="mtcm-movie-list mtcm-columns-' . $columns . '">';
    
    foreach ($movies as $movie) {
        $movie_data = mtcm_get_movie_data($movie->ID);
        
        if ($atts['display'] === 'poster') {
            $output .= mtcm_render_movie_poster($movie, $movie_data, $atts);
        } else {
            $output .= mtcm_render_movie_compact($movie, $movie_data, $atts);
        }
    }
    
    $output .= '</div>';
    
    return $output;
}

// Render TV show list
function mtcm_render_tv_show_list($shows, $atts) {
    $columns = max(1, intval($atts['columns']));
    $output = '<div class="mtcm-tv-show-list mtcm-columns-' . $columns . '">';
    
    foreach ($shows as $show) {
        $show_data = mtcm_get_tv_show_data($show->ID);
        
        if ($atts['display'] === 'poster') {
            $output .= mtcm_render_tv_show_poster($show, $show_data, $atts);
        } else {
            $output .= mtcm_render_tv_show_compact($show, $show_data, $atts);
        }
    }
    
    $output .= '</div>';
    
    return $output;
}