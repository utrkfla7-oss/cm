<?php
/**
 * Shortcodes Class
 * Handles all plugin shortcodes for movies and TV series
 */

if (!defined('ABSPATH')) {
    exit;
}

class AutoPost_Movies_Shortcodes {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'register_shortcodes'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_shortcode_styles'));
        add_action('wp_ajax_autopost_movies_manual_add', array($this, 'handle_manual_add_ajax'));
        add_action('wp_ajax_nopriv_autopost_movies_manual_add', array($this, 'handle_manual_add_ajax'));
    }
    
    /**
     * Register all shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('autopost_movies_wikipedia_info', array($this, 'wikipedia_info_shortcode'));
        add_shortcode('autopost_movies_custom_info', array($this, 'custom_info_shortcode'));
        add_shortcode('autopost_movies_auto_links', array($this, 'auto_links_shortcode'));
        add_shortcode('autopost_movies_trailer_button', array($this, 'trailer_button_shortcode'));
        add_shortcode('autopost_movies_button', array($this, 'custom_button_shortcode'));
        add_shortcode('autopost_movies_info_table', array($this, 'info_table_shortcode'));
        add_shortcode('autopost_movies_rating', array($this, 'rating_shortcode'));
        add_shortcode('autopost_movies_poster', array($this, 'poster_shortcode'));
    }
    
    /**
     * Enqueue shortcode styles
     */
    public function enqueue_shortcode_styles() {
        wp_add_inline_style('wp-block-library', '
            .autopost-movies-button {
                display: inline-block;
                padding: 10px 20px;
                background: #0073aa;
                color: #fff;
                text-decoration: none;
                border-radius: 4px;
                margin: 5px;
                transition: background 0.3s ease;
            }
            .autopost-movies-button:hover {
                background: #005a87;
                color: #fff;
            }
            .autopost-movies-trailer-button {
                background: #ff0000;
            }
            .autopost-movies-trailer-button:hover {
                background: #cc0000;
            }
            .autopost-movies-info-table {
                width: 100%;
                border-collapse: collapse;
                margin: 20px 0;
            }
            .autopost-movies-info-table th,
            .autopost-movies-info-table td {
                padding: 8px 12px;
                border: 1px solid #ddd;
                text-align: left;
            }
            .autopost-movies-info-table th {
                background: #f9f9f9;
                font-weight: bold;
            }
            .autopost-movies-rating {
                display: inline-block;
                background: #ffb900;
                color: #fff;
                padding: 5px 10px;
                border-radius: 20px;
                font-weight: bold;
            }
            .autopost-movies-poster {
                max-width: 300px;
                height: auto;
                border-radius: 8px;
                box-shadow: 0 4px 8px rgba(0,0,0,0.1);
                margin: 10px 0;
            }
            .autopost-movies-links {
                margin: 20px 0;
            }
            .autopost-movies-links a {
                margin-right: 15px;
            }
        ');
    }
    
    /**
     * Wikipedia info shortcode
     */
    public function wikipedia_info_shortcode($atts) {
        global $post;
        
        if (!$post) {
            return '';
        }
        
        $title = get_the_title($post->ID);
        
        // Check if Wikipedia is enabled
        if (!get_option('autopost_movies_wikipedia_enabled')) {
            return '<p><em>' . __('Wikipedia integration is disabled.', 'autopost-movies') . '</em></p>';
        }
        
        $cache_key = 'autopost_movies_wiki_' . md5($title);
        $summary = get_transient($cache_key);
        
        if ($summary === false) {
            // Fetch from Wikipedia API
            $api_handler = new AutoPost_Movies_API_Handler();
            $summary = $api_handler->get_wikipedia_summary($title);
            
            if ($summary) {
                set_transient($cache_key, $summary, 7 * DAY_IN_SECONDS);
            }
        }
        
        if ($summary) {
            $wikipedia_url = 'https://en.wikipedia.org/wiki/' . urlencode(str_replace(' ', '_', $title));
            return '<div class="autopost-movies-wikipedia-info">
                <p>' . wp_kses_post($summary) . '</p>
                <p><a href="' . esc_url($wikipedia_url) . '" target="_blank" rel="noopener">' . 
                __('Read more on Wikipedia', 'autopost-movies') . '</a></p>
            </div>';
        }
        
        return '<p><em>' . __('Wikipedia information not available.', 'autopost-movies') . '</em></p>';
    }
    
    /**
     * Custom info shortcode
     */
    public function custom_info_shortcode($atts) {
        global $post;
        
        if (!$post) {
            return '';
        }
        
        $custom_info = get_option('autopost_movies_custom_info_template', '');
        
        if (empty($custom_info)) {
            return '';
        }
        
        // Replace placeholders with post meta values
        $replacements = array(
            '{title}' => get_the_title($post->ID),
            '{tmdb_id}' => get_post_meta($post->ID, 'autopost_movies_tmdb_id', true),
            '{imdb_id}' => get_post_meta($post->ID, 'autopost_movies_imdb_id', true),
            '{year}' => get_post_meta($post->ID, 'autopost_movies_year', true),
            '{genres}' => get_post_meta($post->ID, 'autopost_movies_genres', true),
            '{rating}' => get_post_meta($post->ID, 'autopost_movies_rating', true)
        );
        
        $output = str_replace(array_keys($replacements), array_values($replacements), $custom_info);
        
        return '<div class="autopost-movies-custom-info">' . wp_kses_post($output) . '</div>';
    }
    
    /**
     * Auto links shortcode
     */
    public function auto_links_shortcode($atts) {
        global $post;
        
        if (!$post) {
            return '';
        }
        
        $tmdb_id = get_post_meta($post->ID, 'autopost_movies_tmdb_id', true);
        $imdb_id = get_post_meta($post->ID, 'autopost_movies_imdb_id', true);
        $type = get_post_meta($post->ID, 'autopost_movies_type', true);
        $title = get_the_title($post->ID);
        
        $links = array();
        
        // TMDB link
        if (!empty($tmdb_id)) {
            $tmdb_url = $type === 'tv' ? 
                "https://www.themoviedb.org/tv/{$tmdb_id}" : 
                "https://www.themoviedb.org/movie/{$tmdb_id}";
            $links[] = '<a href="' . esc_url($tmdb_url) . '" target="_blank" rel="noopener" class="autopost-movies-button">TMDB</a>';
        }
        
        // IMDb link
        if (!empty($imdb_id)) {
            $imdb_url = "https://www.imdb.com/title/{$imdb_id}";
            $links[] = '<a href="' . esc_url($imdb_url) . '" target="_blank" rel="noopener" class="autopost-movies-button">IMDb</a>';
        }
        
        // Google search link
        $google_url = 'https://www.google.com/search?q=' . urlencode($title . ' ' . $type);
        $links[] = '<a href="' . esc_url($google_url) . '" target="_blank" rel="noopener" class="autopost-movies-button">Google</a>';
        
        // YouTube search link
        $youtube_url = 'https://www.youtube.com/results?search_query=' . urlencode($title . ' trailer');
        $links[] = '<a href="' . esc_url($youtube_url) . '" target="_blank" rel="noopener" class="autopost-movies-button">YouTube</a>';
        
        if (!empty($links)) {
            return '<div class="autopost-movies-links">' . implode(' ', $links) . '</div>';
        }
        
        return '';
    }
    
    /**
     * Trailer button shortcode
     */
    public function trailer_button_shortcode($atts) {
        $atts = shortcode_atts(array(
            'url' => '',
            'text' => __('Watch Trailer', 'autopost-movies'),
            'target' => '_blank'
        ), $atts);
        
        if (empty($atts['url'])) {
            global $post;
            if ($post) {
                $atts['url'] = get_post_meta($post->ID, 'autopost_movies_trailer_url', true);
            }
        }
        
        if (empty($atts['url'])) {
            return '';
        }
        
        return '<a href="' . esc_url($atts['url']) . '" target="' . esc_attr($atts['target']) . '" rel="noopener" class="autopost-movies-button autopost-movies-trailer-button">' . 
               esc_html($atts['text']) . '</a>';
    }
    
    /**
     * Custom button shortcode
     */
    public function custom_button_shortcode($atts) {
        $atts = shortcode_atts(array(
            'text' => '',
            'url' => '',
            'target' => '_blank',
            'class' => ''
        ), $atts);
        
        if (empty($atts['text']) || empty($atts['url'])) {
            return '';
        }
        
        $classes = 'autopost-movies-button';
        if (!empty($atts['class'])) {
            $classes .= ' ' . sanitize_html_class($atts['class']);
        }
        
        return '<a href="' . esc_url($atts['url']) . '" target="' . esc_attr($atts['target']) . '" rel="noopener" class="' . esc_attr($classes) . '">' . 
               esc_html($atts['text']) . '</a>';
    }
    
    /**
     * Info table shortcode
     */
    public function info_table_shortcode($atts) {
        global $post;
        
        if (!$post) {
            return '';
        }
        
        $tmdb_id = get_post_meta($post->ID, 'autopost_movies_tmdb_id', true);
        $imdb_id = get_post_meta($post->ID, 'autopost_movies_imdb_id', true);
        $type = get_post_meta($post->ID, 'autopost_movies_type', true);
        $year = get_post_meta($post->ID, 'autopost_movies_year', true);
        $genres = get_post_meta($post->ID, 'autopost_movies_genres', true);
        $rating = get_post_meta($post->ID, 'autopost_movies_rating', true);
        $runtime = get_post_meta($post->ID, 'autopost_movies_runtime', true);
        $episodes = get_post_meta($post->ID, 'autopost_movies_episodes', true);
        $seasons = get_post_meta($post->ID, 'autopost_movies_seasons', true);
        
        $rows = array();
        
        if (!empty($type)) {
            $rows[] = '<tr><th>' . __('Type', 'autopost-movies') . '</th><td>' . ucfirst($type) . '</td></tr>';
        }
        
        if (!empty($year)) {
            $rows[] = '<tr><th>' . __('Year', 'autopost-movies') . '</th><td>' . esc_html($year) . '</td></tr>';
        }
        
        if (!empty($genres)) {
            $rows[] = '<tr><th>' . __('Genres', 'autopost-movies') . '</th><td>' . esc_html($genres) . '</td></tr>';
        }
        
        if (!empty($rating)) {
            $rows[] = '<tr><th>' . __('Rating', 'autopost-movies') . '</th><td>' . esc_html($rating) . '/10</td></tr>';
        }
        
        if ($type === 'movie' && !empty($runtime)) {
            $hours = floor($runtime / 60);
            $minutes = $runtime % 60;
            $runtime_text = $hours > 0 ? "{$hours}h {$minutes}m" : "{$minutes}m";
            $rows[] = '<tr><th>' . __('Runtime', 'autopost-movies') . '</th><td>' . $runtime_text . '</td></tr>';
        }
        
        if ($type === 'tv') {
            if (!empty($seasons)) {
                $rows[] = '<tr><th>' . __('Seasons', 'autopost-movies') . '</th><td>' . esc_html($seasons) . '</td></tr>';
            }
            if (!empty($episodes)) {
                $rows[] = '<tr><th>' . __('Episodes', 'autopost-movies') . '</th><td>' . esc_html($episodes) . '</td></tr>';
            }
        }
        
        if (!empty($tmdb_id)) {
            $rows[] = '<tr><th>' . __('TMDB ID', 'autopost-movies') . '</th><td>' . esc_html($tmdb_id) . '</td></tr>';
        }
        
        if (!empty($imdb_id)) {
            $imdb_url = "https://www.imdb.com/title/{$imdb_id}";
            $rows[] = '<tr><th>' . __('IMDb', 'autopost-movies') . '</th><td><a href="' . esc_url($imdb_url) . '" target="_blank" rel="noopener">' . esc_html($imdb_id) . '</a></td></tr>';
        }
        
        if (!empty($rows)) {
            return '<table class="autopost-movies-info-table">' . implode('', $rows) . '</table>';
        }
        
        return '';
    }
    
    /**
     * Rating shortcode
     */
    public function rating_shortcode($atts) {
        global $post;
        
        $atts = shortcode_atts(array(
            'source' => 'tmdb' // tmdb, imdb, custom
        ), $atts);
        
        if (!$post) {
            return '';
        }
        
        $rating = '';
        $source_label = '';
        
        switch ($atts['source']) {
            case 'tmdb':
                $rating = get_post_meta($post->ID, 'autopost_movies_rating', true);
                $source_label = 'TMDB';
                break;
            case 'custom':
                $rating = get_post_meta($post->ID, 'autopost_movies_custom_rating', true);
                $source_label = '';
                break;
        }
        
        if (!empty($rating)) {
            $rating_text = $source_label ? "{$source_label}: {$rating}/10" : "{$rating}/10";
            return '<span class="autopost-movies-rating">' . esc_html($rating_text) . '</span>';
        }
        
        return '';
    }
    
    /**
     * Poster shortcode
     */
    public function poster_shortcode($atts) {
        global $post;
        
        $atts = shortcode_atts(array(
            'size' => 'medium', // small, medium, large
            'align' => 'none' // left, right, center, none
        ), $atts);
        
        if (!$post) {
            return '';
        }
        
        $poster_url = get_post_meta($post->ID, 'autopost_movies_poster_url', true);
        
        if (empty($poster_url)) {
            return '';
        }
        
        $size_class = 'size-' . sanitize_html_class($atts['size']);
        $align_class = $atts['align'] !== 'none' ? 'align' . sanitize_html_class($atts['align']) : '';
        
        $classes = array('autopost-movies-poster', $size_class);
        if ($align_class) {
            $classes[] = $align_class;
        }
        
        return '<img src="' . esc_url($poster_url) . '" alt="' . esc_attr(get_the_title($post->ID)) . ' Poster" class="' . implode(' ', $classes) . '" />';
    }
    
    /**
     * Handle manual add AJAX request
     */
    public function handle_manual_add_ajax() {
        // Check nonce
        if (!check_ajax_referer('autopost_movies_manual_add', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed', 'autopost-movies')));
        }
        
        // Check permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'autopost-movies')));
        }
        
        $tmdb_id = intval($_POST['tmdb_id']);
        $type = sanitize_text_field($_POST['type']);
        
        if (empty($tmdb_id) || !in_array($type, array('movie', 'tv'))) {
            wp_send_json_error(array('message' => __('Invalid parameters', 'autopost-movies')));
        }
        
        // Check if API key is configured
        $api_key = get_option('autopost_movies_tmdb_api_key', '');
        if (empty($api_key)) {
            wp_send_json_error(array('message' => __('TMDB API key not configured', 'autopost-movies')));
        }
        
        try {
            // First, add to database
            $api_handler = new AutoPost_Movies_API_Handler();
            $result = $api_handler->search_by_tmdb_id($tmdb_id, $type);
            
            if (isset($result['error'])) {
                wp_send_json_error(array('message' => $result['error']));
            }
            
            // Then create the post
            $post_creator = new AutoPost_Movies_Post_Creator();
            $post_result = $post_creator->create_manual_post($tmdb_id, $type);
            
            if (isset($post_result['error'])) {
                wp_send_json_error(array('message' => $post_result['error']));
            }
            
            wp_send_json_success(array(
                'message' => sprintf(__('Successfully created post for "%s"', 'autopost-movies'), $post_result['title']),
                'post_id' => $post_result['post_id'],
                'redirect_url' => get_edit_post_link($post_result['post_id'], 'raw')
            ));
            
        } catch (Exception $e) {
            AutoPost_Movies::log('error', 'Manual add failed: ' . $e->getMessage());
            wp_send_json_error(array('message' => __('An error occurred. Please try again.', 'autopost-movies')));
        }
    }
    
    /**
     * Get list of available shortcodes
     */
    public function get_shortcode_list() {
        return array(
            'autopost_movies_wikipedia_info' => array(
                'description' => __('Display Wikipedia information for the movie/TV series', 'autopost-movies'),
                'attributes' => array()
            ),
            'autopost_movies_custom_info' => array(
                'description' => __('Display custom information template', 'autopost-movies'),
                'attributes' => array()
            ),
            'autopost_movies_auto_links' => array(
                'description' => __('Display auto-generated links (TMDB, IMDb, Google, YouTube)', 'autopost-movies'),
                'attributes' => array()
            ),
            'autopost_movies_trailer_button' => array(
                'description' => __('Display trailer button', 'autopost-movies'),
                'attributes' => array(
                    'url' => __('Trailer URL (optional, uses post meta if not provided)', 'autopost-movies'),
                    'text' => __('Button text (default: "Watch Trailer")', 'autopost-movies'),
                    'target' => __('Link target (default: "_blank")', 'autopost-movies')
                )
            ),
            'autopost_movies_button' => array(
                'description' => __('Display custom button', 'autopost-movies'),
                'attributes' => array(
                    'text' => __('Button text (required)', 'autopost-movies'),
                    'url' => __('Button URL (required)', 'autopost-movies'),
                    'target' => __('Link target (default: "_blank")', 'autopost-movies'),
                    'class' => __('Additional CSS class', 'autopost-movies')
                )
            ),
            'autopost_movies_info_table' => array(
                'description' => __('Display movie/TV series information table', 'autopost-movies'),
                'attributes' => array()
            ),
            'autopost_movies_rating' => array(
                'description' => __('Display rating badge', 'autopost-movies'),
                'attributes' => array(
                    'source' => __('Rating source: tmdb, custom (default: tmdb)', 'autopost-movies')
                )
            ),
            'autopost_movies_poster' => array(
                'description' => __('Display movie/TV series poster', 'autopost-movies'),
                'attributes' => array(
                    'size' => __('Poster size: small, medium, large (default: medium)', 'autopost-movies'),
                    'align' => __('Alignment: left, right, center, none (default: none)', 'autopost-movies')
                )
            )
        );
    }
}