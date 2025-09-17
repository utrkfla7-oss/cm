<?php
/**
 * CinemaBot Pro Post Types
 * 
 * Handles custom post types for movies, TV shows, and related entities
 * with metadata extraction and management.
 */

class CinemaBotPro_Post_Types {
    
    public function __construct() {
        add_action('init', array($this, 'register_post_types'));
        add_action('init', array($this, 'register_taxonomies'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
        add_filter('manage_cbp_movie_posts_columns', array($this, 'set_movie_columns'));
        add_filter('manage_cbp_tv_show_posts_columns', array($this, 'set_tv_show_columns'));
        add_action('manage_cbp_movie_posts_custom_column', array($this, 'movie_custom_column'), 10, 2);
        add_action('manage_cbp_tv_show_posts_custom_column', array($this, 'tv_show_custom_column'), 10, 2);
    }
    
    /**
     * Register custom post types
     */
    public function register_post_types() {
        // Register Movie post type
        $this->register_movie_post_type();
        
        // Register TV Show post type
        $this->register_tv_show_post_type();
        
        // Register Person post type (actors, directors, etc.)
        $this->register_person_post_type();
    }
    
    /**
     * Register Movie post type
     */
    private function register_movie_post_type() {
        $labels = array(
            'name' => __('Movies', 'cinemabotpro'),
            'singular_name' => __('Movie', 'cinemabotpro'),
            'menu_name' => __('Movies', 'cinemabotpro'),
            'name_admin_bar' => __('Movie', 'cinemabotpro'),
            'add_new' => __('Add New', 'cinemabotpro'),
            'add_new_item' => __('Add New Movie', 'cinemabotpro'),
            'new_item' => __('New Movie', 'cinemabotpro'),
            'edit_item' => __('Edit Movie', 'cinemabotpro'),
            'view_item' => __('View Movie', 'cinemabotpro'),
            'all_items' => __('All Movies', 'cinemabotpro'),
            'search_items' => __('Search Movies', 'cinemabotpro'),
            'parent_item_colon' => __('Parent Movies:', 'cinemabotpro'),
            'not_found' => __('No movies found.', 'cinemabotpro'),
            'not_found_in_trash' => __('No movies found in Trash.', 'cinemabotpro'),
            'featured_image' => __('Movie Poster', 'cinemabotpro'),
            'set_featured_image' => __('Set movie poster', 'cinemabotpro'),
            'remove_featured_image' => __('Remove movie poster', 'cinemabotpro'),
            'use_featured_image' => __('Use as movie poster', 'cinemabotpro')
        );
        
        $args = array(
            'labels' => $labels,
            'description' => __('Movie information and metadata', 'cinemabotpro'),
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => 'cinemabotpro-admin',
            'query_var' => true,
            'rewrite' => array('slug' => 'movie'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => null,
            'menu_icon' => 'dashicons-video-alt',
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
            'show_in_rest' => true,
            'rest_base' => 'movies'
        );
        
        register_post_type('cbp_movie', $args);
    }
    
    /**
     * Register TV Show post type
     */
    private function register_tv_show_post_type() {
        $labels = array(
            'name' => __('TV Shows', 'cinemabotpro'),
            'singular_name' => __('TV Show', 'cinemabotpro'),
            'menu_name' => __('TV Shows', 'cinemabotpro'),
            'name_admin_bar' => __('TV Show', 'cinemabotpro'),
            'add_new' => __('Add New', 'cinemabotpro'),
            'add_new_item' => __('Add New TV Show', 'cinemabotpro'),
            'new_item' => __('New TV Show', 'cinemabotpro'),
            'edit_item' => __('Edit TV Show', 'cinemabotpro'),
            'view_item' => __('View TV Show', 'cinemabotpro'),
            'all_items' => __('All TV Shows', 'cinemabotpro'),
            'search_items' => __('Search TV Shows', 'cinemabotpro'),
            'parent_item_colon' => __('Parent TV Shows:', 'cinemabotpro'),
            'not_found' => __('No TV shows found.', 'cinemabotpro'),
            'not_found_in_trash' => __('No TV shows found in Trash.', 'cinemabotpro'),
            'featured_image' => __('TV Show Poster', 'cinemabotpro'),
            'set_featured_image' => __('Set TV show poster', 'cinemabotpro'),
            'remove_featured_image' => __('Remove TV show poster', 'cinemabotpro'),
            'use_featured_image' => __('Use as TV show poster', 'cinemabotpro')
        );
        
        $args = array(
            'labels' => $labels,
            'description' => __('TV Show information and metadata', 'cinemabotpro'),
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => 'cinemabotpro-admin',
            'query_var' => true,
            'rewrite' => array('slug' => 'tv-show'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => null,
            'menu_icon' => 'dashicons-desktop',
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
            'show_in_rest' => true,
            'rest_base' => 'tv-shows'
        );
        
        register_post_type('cbp_tv_show', $args);
    }
    
    /**
     * Register Person post type
     */
    private function register_person_post_type() {
        $labels = array(
            'name' => __('People', 'cinemabotpro'),
            'singular_name' => __('Person', 'cinemabotpro'),
            'menu_name' => __('People', 'cinemabotpro'),
            'name_admin_bar' => __('Person', 'cinemabotpro'),
            'add_new' => __('Add New', 'cinemabotpro'),
            'add_new_item' => __('Add New Person', 'cinemabotpro'),
            'new_item' => __('New Person', 'cinemabotpro'),
            'edit_item' => __('Edit Person', 'cinemabotpro'),
            'view_item' => __('View Person', 'cinemabotpro'),
            'all_items' => __('All People', 'cinemabotpro'),
            'search_items' => __('Search People', 'cinemabotpro'),
            'parent_item_colon' => __('Parent People:', 'cinemabotpro'),
            'not_found' => __('No people found.', 'cinemabotpro'),
            'not_found_in_trash' => __('No people found in Trash.', 'cinemabotpro'),
            'featured_image' => __('Profile Photo', 'cinemabotpro'),
            'set_featured_image' => __('Set profile photo', 'cinemabotpro'),
            'remove_featured_image' => __('Remove profile photo', 'cinemabotpro'),
            'use_featured_image' => __('Use as profile photo', 'cinemabotpro')
        );
        
        $args = array(
            'labels' => $labels,
            'description' => __('People in the entertainment industry', 'cinemabotpro'),
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => 'cinemabotpro-admin',
            'query_var' => true,
            'rewrite' => array('slug' => 'person'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => null,
            'menu_icon' => 'dashicons-groups',
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
            'show_in_rest' => true,
            'rest_base' => 'people'
        );
        
        register_post_type('cbp_person', $args);
    }
    
    /**
     * Register taxonomies
     */
    public function register_taxonomies() {
        // Genre taxonomy
        $this->register_genre_taxonomy();
        
        // Actor taxonomy
        $this->register_actor_taxonomy();
        
        // Director taxonomy
        $this->register_director_taxonomy();
        
        // Year taxonomy
        $this->register_year_taxonomy();
        
        // Language taxonomy
        $this->register_language_taxonomy();
        
        // Country taxonomy
        $this->register_country_taxonomy();
        
        // Rating taxonomy
        $this->register_rating_taxonomy();
    }
    
    /**
     * Register Genre taxonomy
     */
    private function register_genre_taxonomy() {
        $labels = array(
            'name' => __('Genres', 'cinemabotpro'),
            'singular_name' => __('Genre', 'cinemabotpro'),
            'search_items' => __('Search Genres', 'cinemabotpro'),
            'all_items' => __('All Genres', 'cinemabotpro'),
            'parent_item' => __('Parent Genre', 'cinemabotpro'),
            'parent_item_colon' => __('Parent Genre:', 'cinemabotpro'),
            'edit_item' => __('Edit Genre', 'cinemabotpro'),
            'update_item' => __('Update Genre', 'cinemabotpro'),
            'add_new_item' => __('Add New Genre', 'cinemabotpro'),
            'new_item_name' => __('New Genre Name', 'cinemabotpro'),
            'menu_name' => __('Genres', 'cinemabotpro')
        );
        
        $args = array(
            'hierarchical' => true,
            'labels' => $labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'genre'),
            'show_in_rest' => true
        );
        
        register_taxonomy('cbp_genre', array('cbp_movie', 'cbp_tv_show'), $args);
    }
    
    /**
     * Register Actor taxonomy
     */
    private function register_actor_taxonomy() {
        $labels = array(
            'name' => __('Actors', 'cinemabotpro'),
            'singular_name' => __('Actor', 'cinemabotpro'),
            'search_items' => __('Search Actors', 'cinemabotpro'),
            'all_items' => __('All Actors', 'cinemabotpro'),
            'edit_item' => __('Edit Actor', 'cinemabotpro'),
            'update_item' => __('Update Actor', 'cinemabotpro'),
            'add_new_item' => __('Add New Actor', 'cinemabotpro'),
            'new_item_name' => __('New Actor Name', 'cinemabotpro'),
            'menu_name' => __('Actors', 'cinemabotpro')
        );
        
        $args = array(
            'hierarchical' => false,
            'labels' => $labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'actor'),
            'show_in_rest' => true
        );
        
        register_taxonomy('cbp_actor', array('cbp_movie', 'cbp_tv_show'), $args);
    }
    
    /**
     * Register Director taxonomy
     */
    private function register_director_taxonomy() {
        $labels = array(
            'name' => __('Directors', 'cinemabotpro'),
            'singular_name' => __('Director', 'cinemabotpro'),
            'search_items' => __('Search Directors', 'cinemabotpro'),
            'all_items' => __('All Directors', 'cinemabotpro'),
            'edit_item' => __('Edit Director', 'cinemabotpro'),
            'update_item' => __('Update Director', 'cinemabotpro'),
            'add_new_item' => __('Add New Director', 'cinemabotpro'),
            'new_item_name' => __('New Director Name', 'cinemabotpro'),
            'menu_name' => __('Directors', 'cinemabotpro')
        );
        
        $args = array(
            'hierarchical' => false,
            'labels' => $labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'director'),
            'show_in_rest' => true
        );
        
        register_taxonomy('cbp_director', array('cbp_movie', 'cbp_tv_show'), $args);
    }
    
    /**
     * Register Year taxonomy
     */
    private function register_year_taxonomy() {
        $labels = array(
            'name' => __('Years', 'cinemabotpro'),
            'singular_name' => __('Year', 'cinemabotpro'),
            'search_items' => __('Search Years', 'cinemabotpro'),
            'all_items' => __('All Years', 'cinemabotpro'),
            'edit_item' => __('Edit Year', 'cinemabotpro'),
            'update_item' => __('Update Year', 'cinemabotpro'),
            'add_new_item' => __('Add New Year', 'cinemabotpro'),
            'new_item_name' => __('New Year', 'cinemabotpro'),
            'menu_name' => __('Years', 'cinemabotpro')
        );
        
        $args = array(
            'hierarchical' => false,
            'labels' => $labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'year'),
            'show_in_rest' => true
        );
        
        register_taxonomy('cbp_year', array('cbp_movie', 'cbp_tv_show'), $args);
    }
    
    /**
     * Register Language taxonomy
     */
    private function register_language_taxonomy() {
        $labels = array(
            'name' => __('Languages', 'cinemabotpro'),
            'singular_name' => __('Language', 'cinemabotpro'),
            'search_items' => __('Search Languages', 'cinemabotpro'),
            'all_items' => __('All Languages', 'cinemabotpro'),
            'edit_item' => __('Edit Language', 'cinemabotpro'),
            'update_item' => __('Update Language', 'cinemabotpro'),
            'add_new_item' => __('Add New Language', 'cinemabotpro'),
            'new_item_name' => __('New Language', 'cinemabotpro'),
            'menu_name' => __('Languages', 'cinemabotpro')
        );
        
        $args = array(
            'hierarchical' => false,
            'labels' => $labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'language'),
            'show_in_rest' => true
        );
        
        register_taxonomy('cbp_language', array('cbp_movie', 'cbp_tv_show'), $args);
    }
    
    /**
     * Register Country taxonomy
     */
    private function register_country_taxonomy() {
        $labels = array(
            'name' => __('Countries', 'cinemabotpro'),
            'singular_name' => __('Country', 'cinemabotpro'),
            'search_items' => __('Search Countries', 'cinemabotpro'),
            'all_items' => __('All Countries', 'cinemabotpro'),
            'edit_item' => __('Edit Country', 'cinemabotpro'),
            'update_item' => __('Update Country', 'cinemabotpro'),
            'add_new_item' => __('Add New Country', 'cinemabotpro'),
            'new_item_name' => __('New Country', 'cinemabotpro'),
            'menu_name' => __('Countries', 'cinemabotpro')
        );
        
        $args = array(
            'hierarchical' => false,
            'labels' => $labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'country'),
            'show_in_rest' => true
        );
        
        register_taxonomy('cbp_country', array('cbp_movie', 'cbp_tv_show'), $args);
    }
    
    /**
     * Register Rating taxonomy
     */
    private function register_rating_taxonomy() {
        $labels = array(
            'name' => __('Ratings', 'cinemabotpro'),
            'singular_name' => __('Rating', 'cinemabotpro'),
            'search_items' => __('Search Ratings', 'cinemabotpro'),
            'all_items' => __('All Ratings', 'cinemabotpro'),
            'edit_item' => __('Edit Rating', 'cinemabotpro'),
            'update_item' => __('Update Rating', 'cinemabotpro'),
            'add_new_item' => __('Add New Rating', 'cinemabotpro'),
            'new_item_name' => __('New Rating', 'cinemabotpro'),
            'menu_name' => __('Ratings', 'cinemabotpro')
        );
        
        $args = array(
            'hierarchical' => false,
            'labels' => $labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'rating'),
            'show_in_rest' => true
        );
        
        register_taxonomy('cbp_rating', array('cbp_movie', 'cbp_tv_show'), $args);
    }
    
    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        // Movie meta boxes
        add_meta_box(
            'cbp_movie_details',
            __('Movie Details', 'cinemabotpro'),
            array($this, 'movie_details_meta_box'),
            'cbp_movie',
            'normal',
            'high'
        );
        
        add_meta_box(
            'cbp_movie_ratings',
            __('Ratings & Reviews', 'cinemabotpro'),
            array($this, 'movie_ratings_meta_box'),
            'cbp_movie',
            'side',
            'default'
        );
        
        add_meta_box(
            'cbp_movie_technical',
            __('Technical Information', 'cinemabotpro'),
            array($this, 'movie_technical_meta_box'),
            'cbp_movie',
            'normal',
            'default'
        );
        
        // TV Show meta boxes
        add_meta_box(
            'cbp_tv_show_details',
            __('TV Show Details', 'cinemabotpro'),
            array($this, 'tv_show_details_meta_box'),
            'cbp_tv_show',
            'normal',
            'high'
        );
        
        add_meta_box(
            'cbp_tv_show_ratings',
            __('Ratings & Reviews', 'cinemabotpro'),
            array($this, 'tv_show_ratings_meta_box'),
            'cbp_tv_show',
            'side',
            'default'
        );
        
        add_meta_box(
            'cbp_tv_show_episodes',
            __('Episode Information', 'cinemabotpro'),
            array($this, 'tv_show_episodes_meta_box'),
            'cbp_tv_show',
            'normal',
            'default'
        );
        
        // Person meta boxes
        add_meta_box(
            'cbp_person_details',
            __('Person Details', 'cinemabotpro'),
            array($this, 'person_details_meta_box'),
            'cbp_person',
            'normal',
            'high'
        );
    }
    
    /**
     * Movie details meta box
     */
    public function movie_details_meta_box($post) {
        wp_nonce_field('cbp_movie_meta_box', 'cbp_movie_meta_box_nonce');
        
        $meta = get_post_meta($post->ID);
        ?>
        <table class="form-table">
            <tr>
                <th><label for="original_title"><?php _e('Original Title', 'cinemabotpro'); ?></label></th>
                <td><input type="text" id="original_title" name="original_title" value="<?php echo esc_attr($meta['original_title'][0] ?? ''); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="release_date"><?php _e('Release Date', 'cinemabotpro'); ?></label></th>
                <td><input type="date" id="release_date" name="release_date" value="<?php echo esc_attr($meta['release_date'][0] ?? ''); ?>" /></td>
            </tr>
            <tr>
                <th><label for="runtime"><?php _e('Runtime (minutes)', 'cinemabotpro'); ?></label></th>
                <td><input type="number" id="runtime" name="runtime" value="<?php echo esc_attr($meta['runtime'][0] ?? ''); ?>" min="1" /></td>
            </tr>
            <tr>
                <th><label for="budget"><?php _e('Budget', 'cinemabotpro'); ?></label></th>
                <td><input type="text" id="budget" name="budget" value="<?php echo esc_attr($meta['budget'][0] ?? ''); ?>" class="regular-text" placeholder="$10,000,000" /></td>
            </tr>
            <tr>
                <th><label for="box_office"><?php _e('Box Office', 'cinemabotpro'); ?></label></th>
                <td><input type="text" id="box_office" name="box_office" value="<?php echo esc_attr($meta['box_office'][0] ?? ''); ?>" class="regular-text" placeholder="$100,000,000" /></td>
            </tr>
            <tr>
                <th><label for="director"><?php _e('Director(s)', 'cinemabotpro'); ?></label></th>
                <td><input type="text" id="director" name="director" value="<?php echo esc_attr($meta['director'][0] ?? ''); ?>" class="regular-text" placeholder="Comma separated" /></td>
            </tr>
            <tr>
                <th><label for="writer"><?php _e('Writer(s)', 'cinemabotpro'); ?></label></th>
                <td><input type="text" id="writer" name="writer" value="<?php echo esc_attr($meta['writer'][0] ?? ''); ?>" class="regular-text" placeholder="Comma separated" /></td>
            </tr>
            <tr>
                <th><label for="actors"><?php _e('Main Actors', 'cinemabotpro'); ?></label></th>
                <td><textarea id="actors" name="actors" rows="3" class="large-text" placeholder="Comma separated"><?php echo esc_textarea($meta['actors'][0] ?? ''); ?></textarea></td>
            </tr>
            <tr>
                <th><label for="country"><?php _e('Country', 'cinemabotpro'); ?></label></th>
                <td><input type="text" id="country" name="country" value="<?php echo esc_attr($meta['country'][0] ?? ''); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="language"><?php _e('Language', 'cinemabotpro'); ?></label></th>
                <td><input type="text" id="language" name="language" value="<?php echo esc_attr($meta['language'][0] ?? ''); ?>" class="regular-text" /></td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Movie ratings meta box
     */
    public function movie_ratings_meta_box($post) {
        $meta = get_post_meta($post->ID);
        ?>
        <table class="form-table">
            <tr>
                <th><label for="vote_average"><?php _e('TMDB Rating', 'cinemabotpro'); ?></label></th>
                <td><input type="number" id="vote_average" name="vote_average" value="<?php echo esc_attr($meta['vote_average'][0] ?? ''); ?>" step="0.1" min="0" max="10" /></td>
            </tr>
            <tr>
                <th><label for="vote_count"><?php _e('Vote Count', 'cinemabotpro'); ?></label></th>
                <td><input type="number" id="vote_count" name="vote_count" value="<?php echo esc_attr($meta['vote_count'][0] ?? ''); ?>" min="0" /></td>
            </tr>
            <tr>
                <th><label for="imdb_rating"><?php _e('IMDB Rating', 'cinemabotpro'); ?></label></th>
                <td><input type="number" id="imdb_rating" name="imdb_rating" value="<?php echo esc_attr($meta['imdb_rating'][0] ?? ''); ?>" step="0.1" min="0" max="10" /></td>
            </tr>
            <tr>
                <th><label for="metascore"><?php _e('Metascore', 'cinemabotpro'); ?></label></th>
                <td><input type="number" id="metascore" name="metascore" value="<?php echo esc_attr($meta['metascore'][0] ?? ''); ?>" min="0" max="100" /></td>
            </tr>
            <tr>
                <th><label for="rotten_tomatoes"><?php _e('Rotten Tomatoes', 'cinemabotpro'); ?></label></th>
                <td><input type="text" id="rotten_tomatoes" name="rotten_tomatoes" value="<?php echo esc_attr($meta['rotten_tomatoes'][0] ?? ''); ?>" placeholder="85%" /></td>
            </tr>
            <tr>
                <th><label for="awards"><?php _e('Awards', 'cinemabotpro'); ?></label></th>
                <td><textarea id="awards" name="awards" rows="3" class="large-text"><?php echo esc_textarea($meta['awards'][0] ?? ''); ?></textarea></td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Movie technical meta box
     */
    public function movie_technical_meta_box($post) {
        $meta = get_post_meta($post->ID);
        ?>
        <table class="form-table">
            <tr>
                <th><label for="tmdb_id"><?php _e('TMDB ID', 'cinemabotpro'); ?></label></th>
                <td><input type="number" id="tmdb_id" name="tmdb_id" value="<?php echo esc_attr($meta['tmdb_id'][0] ?? ''); ?>" readonly /></td>
            </tr>
            <tr>
                <th><label for="imdb_id"><?php _e('IMDB ID', 'cinemabotpro'); ?></label></th>
                <td><input type="text" id="imdb_id" name="imdb_id" value="<?php echo esc_attr($meta['imdb_id'][0] ?? ''); ?>" readonly /></td>
            </tr>
            <tr>
                <th><label for="poster_path"><?php _e('Poster Path', 'cinemabotpro'); ?></label></th>
                <td><input type="text" id="poster_path" name="poster_path" value="<?php echo esc_attr($meta['poster_path'][0] ?? ''); ?>" class="regular-text" readonly /></td>
            </tr>
            <tr>
                <th><label for="backdrop_path"><?php _e('Backdrop Path', 'cinemabotpro'); ?></label></th>
                <td><input type="text" id="backdrop_path" name="backdrop_path" value="<?php echo esc_attr($meta['backdrop_path'][0] ?? ''); ?>" class="regular-text" readonly /></td>
            </tr>
            <tr>
                <th><label for="adult"><?php _e('Adult Content', 'cinemabotpro'); ?></label></th>
                <td><input type="checkbox" id="adult" name="adult" value="1" <?php checked($meta['adult'][0] ?? 0, 1); ?> /></td>
            </tr>
            <tr>
                <th><label for="popularity"><?php _e('Popularity Score', 'cinemabotpro'); ?></label></th>
                <td><input type="number" id="popularity" name="popularity" value="<?php echo esc_attr($meta['popularity'][0] ?? ''); ?>" step="0.1" readonly /></td>
            </tr>
            <tr>
                <th><label for="crawled_at"><?php _e('Last Crawled', 'cinemabotpro'); ?></label></th>
                <td><input type="datetime-local" id="crawled_at" name="crawled_at" value="<?php echo esc_attr($meta['crawled_at'][0] ?? ''); ?>" readonly /></td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * TV Show details meta box
     */
    public function tv_show_details_meta_box($post) {
        wp_nonce_field('cbp_tv_show_meta_box', 'cbp_tv_show_meta_box_nonce');
        
        $meta = get_post_meta($post->ID);
        ?>
        <table class="form-table">
            <tr>
                <th><label for="original_name"><?php _e('Original Name', 'cinemabotpro'); ?></label></th>
                <td><input type="text" id="original_name" name="original_name" value="<?php echo esc_attr($meta['original_name'][0] ?? ''); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="first_air_date"><?php _e('First Air Date', 'cinemabotpro'); ?></label></th>
                <td><input type="date" id="first_air_date" name="first_air_date" value="<?php echo esc_attr($meta['first_air_date'][0] ?? ''); ?>" /></td>
            </tr>
            <tr>
                <th><label for="last_air_date"><?php _e('Last Air Date', 'cinemabotpro'); ?></label></th>
                <td><input type="date" id="last_air_date" name="last_air_date" value="<?php echo esc_attr($meta['last_air_date'][0] ?? ''); ?>" /></td>
            </tr>
            <tr>
                <th><label for="number_of_seasons"><?php _e('Number of Seasons', 'cinemabotpro'); ?></label></th>
                <td><input type="number" id="number_of_seasons" name="number_of_seasons" value="<?php echo esc_attr($meta['number_of_seasons'][0] ?? ''); ?>" min="1" /></td>
            </tr>
            <tr>
                <th><label for="number_of_episodes"><?php _e('Total Episodes', 'cinemabotpro'); ?></label></th>
                <td><input type="number" id="number_of_episodes" name="number_of_episodes" value="<?php echo esc_attr($meta['number_of_episodes'][0] ?? ''); ?>" min="1" /></td>
            </tr>
            <tr>
                <th><label for="episode_run_time"><?php _e('Episode Runtime (minutes)', 'cinemabotpro'); ?></label></th>
                <td><input type="text" id="episode_run_time" name="episode_run_time" value="<?php echo esc_attr($meta['episode_run_time'][0] ?? ''); ?>" placeholder="22, 44" /></td>
            </tr>
            <tr>
                <th><label for="status"><?php _e('Status', 'cinemabotpro'); ?></label></th>
                <td>
                    <select id="status" name="status">
                        <option value=""><?php _e('Select Status', 'cinemabotpro'); ?></option>
                        <option value="returning" <?php selected($meta['status'][0] ?? '', 'returning'); ?>><?php _e('Returning Series', 'cinemabotpro'); ?></option>
                        <option value="ended" <?php selected($meta['status'][0] ?? '', 'ended'); ?>><?php _e('Ended', 'cinemabotpro'); ?></option>
                        <option value="canceled" <?php selected($meta['status'][0] ?? '', 'canceled'); ?>><?php _e('Canceled', 'cinemabotpro'); ?></option>
                        <option value="pilot" <?php selected($meta['status'][0] ?? '', 'pilot'); ?>><?php _e('Pilot', 'cinemabotpro'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="networks"><?php _e('Networks', 'cinemabotpro'); ?></label></th>
                <td><input type="text" id="networks" name="networks" value="<?php echo esc_attr($meta['networks'][0] ?? ''); ?>" class="regular-text" placeholder="Comma separated" /></td>
            </tr>
            <tr>
                <th><label for="creators"><?php _e('Creators', 'cinemabotpro'); ?></label></th>
                <td><input type="text" id="creators" name="creators" value="<?php echo esc_attr($meta['creators'][0] ?? ''); ?>" class="regular-text" placeholder="Comma separated" /></td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * TV Show ratings meta box
     */
    public function tv_show_ratings_meta_box($post) {
        $meta = get_post_meta($post->ID);
        ?>
        <table class="form-table">
            <tr>
                <th><label for="vote_average"><?php _e('TMDB Rating', 'cinemabotpro'); ?></label></th>
                <td><input type="number" id="vote_average" name="vote_average" value="<?php echo esc_attr($meta['vote_average'][0] ?? ''); ?>" step="0.1" min="0" max="10" /></td>
            </tr>
            <tr>
                <th><label for="vote_count"><?php _e('Vote Count', 'cinemabotpro'); ?></label></th>
                <td><input type="number" id="vote_count" name="vote_count" value="<?php echo esc_attr($meta['vote_count'][0] ?? ''); ?>" min="0" /></td>
            </tr>
            <tr>
                <th><label for="imdb_rating"><?php _e('IMDB Rating', 'cinemabotpro'); ?></label></th>
                <td><input type="number" id="imdb_rating" name="imdb_rating" value="<?php echo esc_attr($meta['imdb_rating'][0] ?? ''); ?>" step="0.1" min="0" max="10" /></td>
            </tr>
            <tr>
                <th><label for="popularity"><?php _e('Popularity Score', 'cinemabotpro'); ?></label></th>
                <td><input type="number" id="popularity" name="popularity" value="<?php echo esc_attr($meta['popularity'][0] ?? ''); ?>" step="0.1" readonly /></td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * TV Show episodes meta box
     */
    public function tv_show_episodes_meta_box($post) {
        $meta = get_post_meta($post->ID);
        $seasons_data = json_decode($meta['seasons_data'][0] ?? '[]', true);
        ?>
        <div id="seasons-data">
            <h4><?php _e('Seasons Information', 'cinemabotpro'); ?></h4>
            <div id="seasons-container">
                <?php if (!empty($seasons_data)): ?>
                    <?php foreach ($seasons_data as $index => $season): ?>
                        <div class="season-item" data-index="<?php echo $index; ?>">
                            <h5><?php printf(__('Season %d', 'cinemabotpro'), $index + 1); ?></h5>
                            <input type="text" name="seasons_data[<?php echo $index; ?>][name]" value="<?php echo esc_attr($season['name'] ?? ''); ?>" placeholder="Season name" />
                            <input type="number" name="seasons_data[<?php echo $index; ?>][episode_count]" value="<?php echo esc_attr($season['episode_count'] ?? ''); ?>" placeholder="Episode count" min="1" />
                            <input type="date" name="seasons_data[<?php echo $index; ?>][air_date]" value="<?php echo esc_attr($season['air_date'] ?? ''); ?>" />
                            <button type="button" class="button remove-season"><?php _e('Remove', 'cinemabotpro'); ?></button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <button type="button" id="add-season" class="button"><?php _e('Add Season', 'cinemabotpro'); ?></button>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#add-season').click(function() {
                var index = $('#seasons-container .season-item').length;
                var html = '<div class="season-item" data-index="' + index + '">' +
                    '<h5><?php printf(__('Season %d', 'cinemabotpro'), '+ (index + 1) +'); ?></h5>' +
                    '<input type="text" name="seasons_data[' + index + '][name]" placeholder="Season name" />' +
                    '<input type="number" name="seasons_data[' + index + '][episode_count]" placeholder="Episode count" min="1" />' +
                    '<input type="date" name="seasons_data[' + index + '][air_date]" />' +
                    '<button type="button" class="button remove-season"><?php _e('Remove', 'cinemabotpro'); ?></button>' +
                    '</div>';
                $('#seasons-container').append(html);
            });
            
            $(document).on('click', '.remove-season', function() {
                $(this).closest('.season-item').remove();
            });
        });
        </script>
        <?php
    }
    
    /**
     * Person details meta box
     */
    public function person_details_meta_box($post) {
        wp_nonce_field('cbp_person_meta_box', 'cbp_person_meta_box_nonce');
        
        $meta = get_post_meta($post->ID);
        ?>
        <table class="form-table">
            <tr>
                <th><label for="birth_date"><?php _e('Birth Date', 'cinemabotpro'); ?></label></th>
                <td><input type="date" id="birth_date" name="birth_date" value="<?php echo esc_attr($meta['birth_date'][0] ?? ''); ?>" /></td>
            </tr>
            <tr>
                <th><label for="death_date"><?php _e('Death Date', 'cinemabotpro'); ?></label></th>
                <td><input type="date" id="death_date" name="death_date" value="<?php echo esc_attr($meta['death_date'][0] ?? ''); ?>" /></td>
            </tr>
            <tr>
                <th><label for="birth_place"><?php _e('Birth Place', 'cinemabotpro'); ?></label></th>
                <td><input type="text" id="birth_place" name="birth_place" value="<?php echo esc_attr($meta['birth_place'][0] ?? ''); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="known_for"><?php _e('Known For', 'cinemabotpro'); ?></label></th>
                <td>
                    <select id="known_for" name="known_for">
                        <option value=""><?php _e('Select Department', 'cinemabotpro'); ?></option>
                        <option value="acting" <?php selected($meta['known_for'][0] ?? '', 'acting'); ?>><?php _e('Acting', 'cinemabotpro'); ?></option>
                        <option value="directing" <?php selected($meta['known_for'][0] ?? '', 'directing'); ?>><?php _e('Directing', 'cinemabotpro'); ?></option>
                        <option value="writing" <?php selected($meta['known_for'][0] ?? '', 'writing'); ?>><?php _e('Writing', 'cinemabotpro'); ?></option>
                        <option value="producing" <?php selected($meta['known_for'][0] ?? '', 'producing'); ?>><?php _e('Producing', 'cinemabotpro'); ?></option>
                        <option value="crew" <?php selected($meta['known_for'][0] ?? '', 'crew'); ?>><?php _e('Crew', 'cinemabotpro'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="tmdb_person_id"><?php _e('TMDB Person ID', 'cinemabotpro'); ?></label></th>
                <td><input type="number" id="tmdb_person_id" name="tmdb_person_id" value="<?php echo esc_attr($meta['tmdb_person_id'][0] ?? ''); ?>" readonly /></td>
            </tr>
            <tr>
                <th><label for="imdb_person_id"><?php _e('IMDB Person ID', 'cinemabotpro'); ?></label></th>
                <td><input type="text" id="imdb_person_id" name="imdb_person_id" value="<?php echo esc_attr($meta['imdb_person_id'][0] ?? ''); ?>" readonly /></td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Save meta boxes
     */
    public function save_meta_boxes($post_id) {
        // Check if it's an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        $post_type = get_post_type($post_id);
        
        switch ($post_type) {
            case 'cbp_movie':
                $this->save_movie_meta($post_id);
                break;
            case 'cbp_tv_show':
                $this->save_tv_show_meta($post_id);
                break;
            case 'cbp_person':
                $this->save_person_meta($post_id);
                break;
        }
    }
    
    /**
     * Save movie metadata
     */
    private function save_movie_meta($post_id) {
        if (!isset($_POST['cbp_movie_meta_box_nonce']) || !wp_verify_nonce($_POST['cbp_movie_meta_box_nonce'], 'cbp_movie_meta_box')) {
            return;
        }
        
        $fields = array(
            'original_title', 'release_date', 'runtime', 'budget', 'box_office',
            'director', 'writer', 'actors', 'country', 'language',
            'vote_average', 'vote_count', 'imdb_rating', 'metascore',
            'rotten_tomatoes', 'awards', 'tmdb_id', 'imdb_id',
            'poster_path', 'backdrop_path', 'popularity', 'crawled_at'
        );
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
        
        // Handle checkbox
        $adult = isset($_POST['adult']) ? 1 : 0;
        update_post_meta($post_id, 'adult', $adult);
        
        // Extract and save year from release date
        if (!empty($_POST['release_date'])) {
            $year = date('Y', strtotime($_POST['release_date']));
            update_post_meta($post_id, 'year', $year);
        }
    }
    
    /**
     * Save TV show metadata
     */
    private function save_tv_show_meta($post_id) {
        if (!isset($_POST['cbp_tv_show_meta_box_nonce']) || !wp_verify_nonce($_POST['cbp_tv_show_meta_box_nonce'], 'cbp_tv_show_meta_box')) {
            return;
        }
        
        $fields = array(
            'original_name', 'first_air_date', 'last_air_date',
            'number_of_seasons', 'number_of_episodes', 'episode_run_time',
            'status', 'networks', 'creators', 'vote_average', 'vote_count',
            'imdb_rating', 'popularity'
        );
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
        
        // Handle seasons data
        if (isset($_POST['seasons_data']) && is_array($_POST['seasons_data'])) {
            $seasons_data = array();
            foreach ($_POST['seasons_data'] as $season) {
                $seasons_data[] = array(
                    'name' => sanitize_text_field($season['name'] ?? ''),
                    'episode_count' => intval($season['episode_count'] ?? 0),
                    'air_date' => sanitize_text_field($season['air_date'] ?? '')
                );
            }
            update_post_meta($post_id, 'seasons_data', wp_json_encode($seasons_data));
        }
        
        // Extract and save year from first air date
        if (!empty($_POST['first_air_date'])) {
            $year = date('Y', strtotime($_POST['first_air_date']));
            update_post_meta($post_id, 'year', $year);
        }
    }
    
    /**
     * Save person metadata
     */
    private function save_person_meta($post_id) {
        if (!isset($_POST['cbp_person_meta_box_nonce']) || !wp_verify_nonce($_POST['cbp_person_meta_box_nonce'], 'cbp_person_meta_box')) {
            return;
        }
        
        $fields = array(
            'birth_date', 'death_date', 'birth_place', 'known_for',
            'tmdb_person_id', 'imdb_person_id'
        );
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
    }
    
    /**
     * Set movie admin columns
     */
    public function set_movie_columns($columns) {
        unset($columns['date']);
        
        $columns['year'] = __('Year', 'cinemabotpro');
        $columns['rating'] = __('Rating', 'cinemabotpro');
        $columns['runtime'] = __('Runtime', 'cinemabotpro');
        $columns['date'] = __('Date', 'cinemabotpro');
        
        return $columns;
    }
    
    /**
     * Set TV show admin columns
     */
    public function set_tv_show_columns($columns) {
        unset($columns['date']);
        
        $columns['year'] = __('Year', 'cinemabotpro');
        $columns['seasons'] = __('Seasons', 'cinemabotpro');
        $columns['episodes'] = __('Episodes', 'cinemabotpro');
        $columns['status'] = __('Status', 'cinemabotpro');
        $columns['rating'] = __('Rating', 'cinemabotpro');
        $columns['date'] = __('Date', 'cinemabotpro');
        
        return $columns;
    }
    
    /**
     * Movie custom column content
     */
    public function movie_custom_column($column, $post_id) {
        switch ($column) {
            case 'year':
                echo get_post_meta($post_id, 'year', true) ?: '—';
                break;
            case 'rating':
                $rating = get_post_meta($post_id, 'vote_average', true);
                echo $rating ? $rating . '/10' : '—';
                break;
            case 'runtime':
                $runtime = get_post_meta($post_id, 'runtime', true);
                echo $runtime ? $runtime . ' min' : '—';
                break;
        }
    }
    
    /**
     * TV show custom column content
     */
    public function tv_show_custom_column($column, $post_id) {
        switch ($column) {
            case 'year':
                echo get_post_meta($post_id, 'year', true) ?: '—';
                break;
            case 'seasons':
                echo get_post_meta($post_id, 'number_of_seasons', true) ?: '—';
                break;
            case 'episodes':
                echo get_post_meta($post_id, 'number_of_episodes', true) ?: '—';
                break;
            case 'status':
                $status = get_post_meta($post_id, 'status', true);
                echo $status ? ucfirst($status) : '—';
                break;
            case 'rating':
                $rating = get_post_meta($post_id, 'vote_average', true);
                echo $rating ? $rating . '/10' : '—';
                break;
        }
    }
}