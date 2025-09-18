<?php
/**
 * Post Creator Class
 * Handles automatic post creation with Classic Editor format
 */

if (!defined('ABSPATH')) {
    exit;
}

class AutoPost_Movies_Post_Creator {
    
    /**
     * Create a new post for movie/TV series
     */
    public function create_post($item, $tmdb_details, $wikipedia_summary = '', $trailer_url = '') {
        // Prepare post content
        $content = $this->generate_post_content($item, $tmdb_details, $wikipedia_summary, $trailer_url);
        
        // Prepare post data
        $post_data = array(
            'post_title' => sanitize_text_field($item->title),
            'post_content' => $content,
            'post_status' => 'publish',
            'post_type' => 'post',
            'post_author' => 1, // Default to admin user
            'meta_input' => array(
                'autopost_movies_tmdb_id' => $item->tmdb_id,
                'autopost_movies_imdb_id' => $item->imdb_id,
                'autopost_movies_type' => $item->type,
                'autopost_movies_trailer_url' => $trailer_url,
                'autopost_movies_release_date' => $item->release_date,
                'autopost_movies_poster_url' => $item->poster_url
            )
        );
        
        // Add release year if available
        if (!empty($item->release_date)) {
            $year = date('Y', strtotime($item->release_date));
            $post_data['meta_input']['autopost_movies_year'] = $year;
        }
        
        // Add genre information if available
        if (isset($tmdb_details['genres']) && is_array($tmdb_details['genres'])) {
            $genres = array_map(function($genre) {
                return $genre['name'];
            }, $tmdb_details['genres']);
            $post_data['meta_input']['autopost_movies_genres'] = implode(', ', $genres);
        }
        
        // Add runtime/episode count
        if ($item->type === 'movie' && isset($tmdb_details['runtime'])) {
            $post_data['meta_input']['autopost_movies_runtime'] = $tmdb_details['runtime'];
        } elseif ($item->type === 'tv' && isset($tmdb_details['number_of_episodes'])) {
            $post_data['meta_input']['autopost_movies_episodes'] = $tmdb_details['number_of_episodes'];
            $post_data['meta_input']['autopost_movies_seasons'] = $tmdb_details['number_of_seasons'];
        }
        
        // Add rating if available
        if (isset($tmdb_details['vote_average'])) {
            $post_data['meta_input']['autopost_movies_rating'] = $tmdb_details['vote_average'];
        }
        
        // Insert the post
        $post_id = wp_insert_post($post_data, true);
        
        if (is_wp_error($post_id)) {
            AutoPost_Movies::log('error', 'Failed to create post: ' . $post_id->get_error_message(), $post_data);
            return false;
        }
        
        // Set featured image if FIFU is enabled and poster URL is available
        if (get_option('autopost_movies_fifu_enabled') && !empty($item->poster_url)) {
            $this->set_featured_image_from_url($post_id, $item->poster_url);
        }
        
        // Set categories/tags if needed
        $this->set_post_taxonomy($post_id, $item, $tmdb_details);
        
        AutoPost_Movies::log('post_creation', "Created post: {$item->title} (ID: {$post_id})");
        
        return $post_id;
    }
    
    /**
     * Generate post content
     */
    private function generate_post_content($item, $tmdb_details, $wikipedia_summary = '', $trailer_url = '') {
        $content = '';
        $content_order = get_option('autopost_movies_content_order', 'plot_first');
        
        // Get plot and info content
        $plot_content = $this->get_plot_content($item, $tmdb_details, $wikipedia_summary);
        $info_content = $this->get_info_content($item, $tmdb_details);
        
        // Add content based on order preference
        if ($content_order === 'info_first') {
            $content .= $info_content;
            $content .= $plot_content;
        } else {
            $content .= $plot_content;
            $content .= $info_content;
        }
        
        // Add trailer section if available
        if (!empty($trailer_url)) {
            $content .= $this->get_trailer_section($trailer_url);
        }
        
        // Add custom shortcodes section
        $content .= $this->get_shortcodes_section($item, $tmdb_details);
        
        return $content;
    }
    
    /**
     * Get plot content
     */
    private function get_plot_content($item, $tmdb_details, $wikipedia_summary = '') {
        $plot_source = get_option('autopost_movies_plot_source', 'tmdb');
        $content = '';
        
        $content .= "<h3>Plot</h3>\n";
        
        if ($plot_source === 'wikipedia' && !empty($wikipedia_summary)) {
            $content .= "<p>" . wp_kses_post($wikipedia_summary) . "</p>\n";
            $content .= "[autopost_movies_wikipedia_info]\n";
        } else {
            // Use TMDB plot as default or fallback
            $plot = !empty($item->plot) ? $item->plot : (isset($tmdb_details['overview']) ? $tmdb_details['overview'] : '');
            if (!empty($plot)) {
                $content .= "<p>" . wp_kses_post($plot) . "</p>\n";
            }
        }
        
        $content .= "\n";
        
        return $content;
    }
    
    /**
     * Get info content
     */
    private function get_info_content($item, $tmdb_details) {
        $info_source = get_option('autopost_movies_info_source', 'tmdb');
        $content = '';
        
        $content .= "<h3>" . ($item->type === 'movie' ? 'Movie' : 'TV Series') . " Information</h3>\n";
        
        // Basic information table
        $content .= "<table class='autopost-movies-info'>\n";
        
        // Title
        $content .= "<tr><td><strong>Title:</strong></td><td>" . esc_html($item->title) . "</td></tr>\n";
        
        // Type
        $content .= "<tr><td><strong>Type:</strong></td><td>" . ucfirst($item->type) . "</td></tr>\n";
        
        // Release date
        if (!empty($item->release_date)) {
            $date_label = $item->type === 'movie' ? 'Release Date' : 'First Air Date';
            $formatted_date = date('F j, Y', strtotime($item->release_date));
            $content .= "<tr><td><strong>{$date_label}:</strong></td><td>{$formatted_date}</td></tr>\n";
        }
        
        // Genres
        if (isset($tmdb_details['genres']) && is_array($tmdb_details['genres'])) {
            $genres = array_map(function($genre) {
                return $genre['name'];
            }, $tmdb_details['genres']);
            $content .= "<tr><td><strong>Genres:</strong></td><td>" . implode(', ', $genres) . "</td></tr>\n";
        }
        
        // Runtime or episode info
        if ($item->type === 'movie' && isset($tmdb_details['runtime'])) {
            $hours = floor($tmdb_details['runtime'] / 60);
            $minutes = $tmdb_details['runtime'] % 60;
            $runtime_text = $hours > 0 ? "{$hours}h {$minutes}m" : "{$minutes}m";
            $content .= "<tr><td><strong>Runtime:</strong></td><td>{$runtime_text}</td></tr>\n";
        } elseif ($item->type === 'tv') {
            if (isset($tmdb_details['number_of_seasons'])) {
                $content .= "<tr><td><strong>Seasons:</strong></td><td>{$tmdb_details['number_of_seasons']}</td></tr>\n";
            }
            if (isset($tmdb_details['number_of_episodes'])) {
                $content .= "<tr><td><strong>Episodes:</strong></td><td>{$tmdb_details['number_of_episodes']}</td></tr>\n";
            }
        }
        
        // Rating
        if (isset($tmdb_details['vote_average']) && $tmdb_details['vote_average'] > 0) {
            $rating = round($tmdb_details['vote_average'], 1);
            $content .= "<tr><td><strong>TMDB Rating:</strong></td><td>{$rating}/10</td></tr>\n";
        }
        
        // Production companies
        if (isset($tmdb_details['production_companies']) && is_array($tmdb_details['production_companies'])) {
            $companies = array_slice($tmdb_details['production_companies'], 0, 3); // Show max 3
            $company_names = array_map(function($company) {
                return $company['name'];
            }, $companies);
            if (!empty($company_names)) {
                $content .= "<tr><td><strong>Production:</strong></td><td>" . implode(', ', $company_names) . "</td></tr>\n";
            }
        }
        
        // TMDB ID
        $content .= "<tr><td><strong>TMDB ID:</strong></td><td>{$item->tmdb_id}</td></tr>\n";
        
        // IMDb ID if available
        if (!empty($item->imdb_id)) {
            $content .= "<tr><td><strong>IMDb ID:</strong></td><td><a href='https://www.imdb.com/title/{$item->imdb_id}' target='_blank'>{$item->imdb_id}</a></td></tr>\n";
        }
        
        $content .= "</table>\n\n";
        
        // Add custom info shortcode
        $content .= "[autopost_movies_custom_info]\n\n";
        
        return $content;
    }
    
    /**
     * Get trailer section
     */
    private function get_trailer_section($trailer_url) {
        $content = "<h3>Trailer</h3>\n";
        $content .= "[autopost_movies_trailer_button url=\"{$trailer_url}\"]\n\n";
        
        // Also embed YouTube video if it's a YouTube URL
        if (strpos($trailer_url, 'youtube.com') !== false || strpos($trailer_url, 'youtu.be') !== false) {
            $video_id = $this->extract_youtube_id($trailer_url);
            if ($video_id) {
                $content .= "<div class='autopost-movies-trailer-embed'>\n";
                $content .= "<iframe width='560' height='315' src='https://www.youtube.com/embed/{$video_id}' frameborder='0' allowfullscreen></iframe>\n";
                $content .= "</div>\n\n";
            }
        }
        
        return $content;
    }
    
    /**
     * Extract YouTube video ID from URL
     */
    private function extract_youtube_id($url) {
        preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $url, $matches);
        return isset($matches[1]) ? $matches[1] : false;
    }
    
    /**
     * Get shortcodes section
     */
    private function get_shortcodes_section($item, $tmdb_details) {
        $content = "<h3>Additional Links</h3>\n";
        
        // Auto-generated links
        $content .= "[autopost_movies_auto_links]\n\n";
        
        // Additional buttons (configurable via admin panel)
        $additional_buttons = get_option('autopost_movies_additional_buttons', array());
        if (is_array($additional_buttons) && !empty($additional_buttons)) {
            foreach ($additional_buttons as $button) {
                if (!empty($button['text']) && !empty($button['url'])) {
                    $content .= "[autopost_movies_button text=\"{$button['text']}\" url=\"{$button['url']}\"]\n";
                }
            }
        }
        
        return $content;
    }
    
    /**
     * Set featured image from URL (FIFU compatibility)
     */
    private function set_featured_image_from_url($post_id, $image_url) {
        // Check if FIFU plugin is active
        if (is_plugin_active('featured-image-from-url/featured-image-from-url.php')) {
            // Use FIFU functions
            update_post_meta($post_id, 'fifu_image_url', $image_url);
            update_post_meta($post_id, 'fifu_image_alt', get_the_title($post_id) . ' Poster');
        } else {
            // Fallback: store URL in custom meta
            update_post_meta($post_id, 'autopost_movies_poster_url', $image_url);
        }
        
        AutoPost_Movies::log('post_creation', "Set featured image for post {$post_id}: {$image_url}");
    }
    
    /**
     * Set post taxonomy (categories, tags)
     */
    private function set_post_taxonomy($post_id, $item, $tmdb_details) {
        // Set categories based on type
        $category_name = $item->type === 'movie' ? 'Movies' : 'TV Series';
        $category = get_category_by_slug(sanitize_title($category_name));
        
        if (!$category) {
            // Create category if it doesn't exist
            $category_id = wp_create_category($category_name);
        } else {
            $category_id = $category->term_id;
        }
        
        if ($category_id) {
            wp_set_post_categories($post_id, array($category_id));
        }
        
        // Set tags based on genres
        if (isset($tmdb_details['genres']) && is_array($tmdb_details['genres'])) {
            $tags = array_map(function($genre) {
                return $genre['name'];
            }, $tmdb_details['genres']);
            
            wp_set_post_tags($post_id, $tags);
        }
        
        // Add year as tag if available
        if (!empty($item->release_date)) {
            $year = date('Y', strtotime($item->release_date));
            wp_add_post_tags($post_id, array($year));
        }
    }
    
    /**
     * Manual post creation
     */
    public function create_manual_post($tmdb_id, $type = 'movie') {
        global $wpdb;
        
        // Get the item from database
        $table_name = $wpdb->prefix . 'autopost_movies';
        $item = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE tmdb_id = %d AND type = %s",
                $tmdb_id,
                $type
            )
        );
        
        if (!$item) {
            return array('error' => 'Item not found in database');
        }
        
        if ($item->status === 'posted' && !empty($item->post_id)) {
            return array('error' => 'Post already exists', 'post_id' => $item->post_id);
        }
        
        // Get TMDB details
        $api_handler = new AutoPost_Movies_API_Handler();
        $tmdb_details = $api_handler->get_tmdb_details($tmdb_id, $type);
        
        if (!$tmdb_details) {
            return array('error' => 'Failed to fetch TMDB details');
        }
        
        // Get additional info
        $wikipedia_summary = '';
        if (get_option('autopost_movies_wikipedia_enabled')) {
            $wikipedia_summary = $api_handler->get_wikipedia_summary($item->title);
        }
        
        $trailer_url = $item->trailer_url;
        if (empty($trailer_url)) {
            $year = !empty($item->release_date) ? date('Y', strtotime($item->release_date)) : null;
            $trailer_url = $api_handler->search_youtube_trailer($item->title, $year);
        }
        
        // Create the post
        $post_id = $this->create_post($item, $tmdb_details, $wikipedia_summary, $trailer_url);
        
        if ($post_id) {
            // Update database
            $wpdb->update(
                $table_name,
                array('status' => 'posted', 'post_id' => $post_id),
                array('id' => $item->id),
                array('%s', '%d'),
                array('%d')
            );
            
            return array('success' => true, 'post_id' => $post_id, 'title' => $item->title);
        } else {
            return array('error' => 'Failed to create post');
        }
    }
}