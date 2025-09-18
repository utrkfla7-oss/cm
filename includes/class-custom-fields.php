<?php
/**
 * Custom Fields Class
 * Lightweight custom fields management without ACF dependency
 */

if (!defined('ABSPATH')) {
    exit;
}

class AutoPost_Movies_Custom_Fields {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_meta_styles'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_meta_scripts'));
    }
    
    /**
     * Add meta boxes to post edit screen
     */
    public function add_meta_boxes() {
        add_meta_box(
            'autopost_movies_meta',
            __('Movie/TV Series Information', 'autopost-movies'),
            array($this, 'meta_box_callback'),
            'post',
            'normal',
            'high'
        );
        
        add_meta_box(
            'autopost_movies_manual_add',
            __('Auto Post Movie/TV Series', 'autopost-movies'),
            array($this, 'manual_add_meta_box'),
            'post',
            'side',
            'default'
        );
    }
    
    /**
     * Meta box callback for movie information
     */
    public function meta_box_callback($post) {
        wp_nonce_field('autopost_movies_meta_nonce', 'autopost_movies_meta_nonce');
        
        // Get current values
        $tmdb_id = get_post_meta($post->ID, 'autopost_movies_tmdb_id', true);
        $imdb_id = get_post_meta($post->ID, 'autopost_movies_imdb_id', true);
        $type = get_post_meta($post->ID, 'autopost_movies_type', true);
        $trailer_url = get_post_meta($post->ID, 'autopost_movies_trailer_url', true);
        $release_date = get_post_meta($post->ID, 'autopost_movies_release_date', true);
        $year = get_post_meta($post->ID, 'autopost_movies_year', true);
        $genres = get_post_meta($post->ID, 'autopost_movies_genres', true);
        $rating = get_post_meta($post->ID, 'autopost_movies_rating', true);
        $runtime = get_post_meta($post->ID, 'autopost_movies_runtime', true);
        $episodes = get_post_meta($post->ID, 'autopost_movies_episodes', true);
        $seasons = get_post_meta($post->ID, 'autopost_movies_seasons', true);
        $poster_url = get_post_meta($post->ID, 'autopost_movies_poster_url', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="autopost_movies_tmdb_id"><?php _e('TMDB ID', 'autopost-movies'); ?></label>
                </th>
                <td>
                    <input type="number" id="autopost_movies_tmdb_id" name="autopost_movies_tmdb_id" 
                           value="<?php echo esc_attr($tmdb_id); ?>" class="regular-text" />
                    <p class="description"><?php _e('The Movie Database (TMDB) ID', 'autopost-movies'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="autopost_movies_imdb_id"><?php _e('IMDb ID', 'autopost-movies'); ?></label>
                </th>
                <td>
                    <input type="text" id="autopost_movies_imdb_id" name="autopost_movies_imdb_id" 
                           value="<?php echo esc_attr($imdb_id); ?>" class="regular-text" 
                           placeholder="tt1234567" />
                    <p class="description"><?php _e('Internet Movie Database ID (e.g., tt1234567)', 'autopost-movies'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="autopost_movies_type"><?php _e('Type', 'autopost-movies'); ?></label>
                </th>
                <td>
                    <select id="autopost_movies_type" name="autopost_movies_type">
                        <option value=""><?php _e('Select Type', 'autopost-movies'); ?></option>
                        <option value="movie" <?php selected($type, 'movie'); ?>><?php _e('Movie', 'autopost-movies'); ?></option>
                        <option value="tv" <?php selected($type, 'tv'); ?>><?php _e('TV Series', 'autopost-movies'); ?></option>
                    </select>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="autopost_movies_trailer_url"><?php _e('Trailer URL', 'autopost-movies'); ?></label>
                </th>
                <td>
                    <input type="url" id="autopost_movies_trailer_url" name="autopost_movies_trailer_url" 
                           value="<?php echo esc_attr($trailer_url); ?>" class="regular-text" 
                           placeholder="https://www.youtube.com/watch?v=..." />
                    <p class="description"><?php _e('YouTube or other video platform trailer URL', 'autopost-movies'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="autopost_movies_release_date"><?php _e('Release Date', 'autopost-movies'); ?></label>
                </th>
                <td>
                    <input type="date" id="autopost_movies_release_date" name="autopost_movies_release_date" 
                           value="<?php echo esc_attr($release_date); ?>" />
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="autopost_movies_year"><?php _e('Year', 'autopost-movies'); ?></label>
                </th>
                <td>
                    <input type="number" id="autopost_movies_year" name="autopost_movies_year" 
                           value="<?php echo esc_attr($year); ?>" min="1900" max="2030" />
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="autopost_movies_genres"><?php _e('Genres', 'autopost-movies'); ?></label>
                </th>
                <td>
                    <input type="text" id="autopost_movies_genres" name="autopost_movies_genres" 
                           value="<?php echo esc_attr($genres); ?>" class="regular-text" 
                           placeholder="Action, Comedy, Drama" />
                    <p class="description"><?php _e('Comma-separated list of genres', 'autopost-movies'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="autopost_movies_rating"><?php _e('Rating', 'autopost-movies'); ?></label>
                </th>
                <td>
                    <input type="number" id="autopost_movies_rating" name="autopost_movies_rating" 
                           value="<?php echo esc_attr($rating); ?>" min="0" max="10" step="0.1" />
                    <p class="description"><?php _e('TMDB rating (0-10)', 'autopost-movies'); ?></p>
                </td>
            </tr>
            
            <tr id="autopost_movies_runtime_row" style="display: <?php echo ($type === 'movie' || empty($type)) ? 'table-row' : 'none'; ?>;">
                <th scope="row">
                    <label for="autopost_movies_runtime"><?php _e('Runtime (minutes)', 'autopost-movies'); ?></label>
                </th>
                <td>
                    <input type="number" id="autopost_movies_runtime" name="autopost_movies_runtime" 
                           value="<?php echo esc_attr($runtime); ?>" min="1" />
                </td>
            </tr>
            
            <tr id="autopost_movies_episodes_row" style="display: <?php echo ($type === 'tv') ? 'table-row' : 'none'; ?>;">
                <th scope="row">
                    <label for="autopost_movies_episodes"><?php _e('Episodes', 'autopost-movies'); ?></label>
                </th>
                <td>
                    <input type="number" id="autopost_movies_episodes" name="autopost_movies_episodes" 
                           value="<?php echo esc_attr($episodes); ?>" min="1" />
                </td>
            </tr>
            
            <tr id="autopost_movies_seasons_row" style="display: <?php echo ($type === 'tv') ? 'table-row' : 'none'; ?>;">
                <th scope="row">
                    <label for="autopost_movies_seasons"><?php _e('Seasons', 'autopost-movies'); ?></label>
                </th>
                <td>
                    <input type="number" id="autopost_movies_seasons" name="autopost_movies_seasons" 
                           value="<?php echo esc_attr($seasons); ?>" min="1" />
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="autopost_movies_poster_url"><?php _e('Poster URL', 'autopost-movies'); ?></label>
                </th>
                <td>
                    <input type="url" id="autopost_movies_poster_url" name="autopost_movies_poster_url" 
                           value="<?php echo esc_attr($poster_url); ?>" class="regular-text" />
                    <p class="description"><?php _e('Featured image URL (for FIFU compatibility)', 'autopost-movies'); ?></p>
                    <?php if (!empty($poster_url)): ?>
                        <div class="autopost-movies-poster-preview">
                            <img src="<?php echo esc_url($poster_url); ?>" style="max-width: 200px; max-height: 300px;" />
                        </div>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#autopost_movies_type').change(function() {
                var type = $(this).val();
                if (type === 'movie') {
                    $('#autopost_movies_runtime_row').show();
                    $('#autopost_movies_episodes_row, #autopost_movies_seasons_row').hide();
                } else if (type === 'tv') {
                    $('#autopost_movies_runtime_row').hide();
                    $('#autopost_movies_episodes_row, #autopost_movies_seasons_row').show();
                } else {
                    $('#autopost_movies_runtime_row').show();
                    $('#autopost_movies_episodes_row, #autopost_movies_seasons_row').hide();
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Manual add meta box for Auto Post button
     */
    public function manual_add_meta_box($post) {
        ?>
        <div id="autopost-movies-manual-add">
            <p><?php _e('Add movie or TV series automatically using TMDB/IMDb codes:', 'autopost-movies'); ?></p>
            
            <p>
                <label for="autopost_manual_tmdb_id"><?php _e('TMDB ID:', 'autopost-movies'); ?></label><br>
                <input type="number" id="autopost_manual_tmdb_id" placeholder="123456" style="width: 100%;" />
            </p>
            
            <p>
                <label for="autopost_manual_type"><?php _e('Type:', 'autopost-movies'); ?></label><br>
                <select id="autopost_manual_type" style="width: 100%;">
                    <option value="movie"><?php _e('Movie', 'autopost-movies'); ?></option>
                    <option value="tv"><?php _e('TV Series', 'autopost-movies'); ?></option>
                </select>
            </p>
            
            <p>
                <button type="button" id="autopost_manual_add_btn" class="button button-primary" style="width: 100%;">
                    <?php _e('Auto Post', 'autopost-movies'); ?>
                </button>
            </p>
            
            <div id="autopost_manual_result" style="margin-top: 10px;"></div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#autopost_manual_add_btn').click(function() {
                var tmdb_id = $('#autopost_manual_tmdb_id').val();
                var type = $('#autopost_manual_type').val();
                var $result = $('#autopost_manual_result');
                var $btn = $(this);
                
                if (!tmdb_id) {
                    $result.html('<div class="notice notice-error"><p><?php _e('Please enter a TMDB ID', 'autopost-movies'); ?></p></div>');
                    return;
                }
                
                $btn.prop('disabled', true).text('<?php _e('Processing...', 'autopost-movies'); ?>');
                $result.html('<div class="notice notice-info"><p><?php _e('Processing...', 'autopost-movies'); ?></p></div>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'autopost_movies_manual_add',
                        tmdb_id: tmdb_id,
                        type: type,
                        nonce: '<?php echo wp_create_nonce('autopost_movies_manual_add'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $result.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                            if (response.data.redirect_url) {
                                setTimeout(function() {
                                    window.location.href = response.data.redirect_url;
                                }, 2000);
                            }
                        } else {
                            $result.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                        }
                    },
                    error: function() {
                        $result.html('<div class="notice notice-error"><p><?php _e('An error occurred. Please try again.', 'autopost-movies'); ?></p></div>');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('<?php _e('Auto Post', 'autopost-movies'); ?>');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Save meta box data
     */
    public function save_meta_boxes($post_id) {
        // Check nonce
        if (!isset($_POST['autopost_movies_meta_nonce']) || 
            !wp_verify_nonce($_POST['autopost_movies_meta_nonce'], 'autopost_movies_meta_nonce')) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Skip autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Define fields to save
        $fields = array(
            'autopost_movies_tmdb_id',
            'autopost_movies_imdb_id',
            'autopost_movies_type',
            'autopost_movies_trailer_url',
            'autopost_movies_release_date',
            'autopost_movies_year',
            'autopost_movies_genres',
            'autopost_movies_rating',
            'autopost_movies_runtime',
            'autopost_movies_episodes',
            'autopost_movies_seasons',
            'autopost_movies_poster_url'
        );
        
        // Save each field
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                $value = sanitize_text_field($_POST[$field]);
                update_post_meta($post_id, $field, $value);
            }
        }
        
        // Update FIFU if poster URL is set and FIFU is enabled
        if (get_option('autopost_movies_fifu_enabled') && !empty($_POST['autopost_movies_poster_url'])) {
            $poster_url = esc_url_raw($_POST['autopost_movies_poster_url']);
            update_post_meta($post_id, 'fifu_image_url', $poster_url);
            update_post_meta($post_id, 'fifu_image_alt', get_the_title($post_id) . ' Poster');
        }
    }
    
    /**
     * Enqueue styles for meta boxes
     */
    public function enqueue_meta_styles() {
        if (is_admin()) {
            wp_add_inline_style('wp-admin', '
                .autopost-movies-poster-preview img {
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    margin-top: 10px;
                }
                #autopost-movies-manual-add .notice {
                    margin: 10px 0;
                    padding: 5px 10px;
                }
            ');
        }
    }
    
    /**
     * Enqueue admin scripts for manual add functionality
     */
    public function enqueue_admin_meta_scripts($hook) {
        if ('post.php' === $hook || 'post-new.php' === $hook) {
            wp_add_inline_script('jquery', 'var ajaxurl = "' . admin_url('admin-ajax.php') . '";');
        }
    }
    
    /**
     * Get all custom fields for a post
     */
    public function get_post_fields($post_id) {
        $fields = array();
        
        $meta_keys = array(
            'autopost_movies_tmdb_id',
            'autopost_movies_imdb_id', 
            'autopost_movies_type',
            'autopost_movies_trailer_url',
            'autopost_movies_release_date',
            'autopost_movies_year',
            'autopost_movies_genres',
            'autopost_movies_rating',
            'autopost_movies_runtime',
            'autopost_movies_episodes',
            'autopost_movies_seasons',
            'autopost_movies_poster_url'
        );
        
        foreach ($meta_keys as $key) {
            $fields[$key] = get_post_meta($post_id, $key, true);
        }
        
        return $fields;
    }
    
    /**
     * Export field configuration as JSON (ACF compatibility)
     */
    public function export_field_config() {
        $config = array(
            'key' => 'group_autopost_movies',
            'title' => 'Movie/TV Series Information',
            'fields' => array(
                array(
                    'key' => 'field_tmdb_id',
                    'label' => 'TMDB ID',
                    'name' => 'autopost_movies_tmdb_id',
                    'type' => 'number',
                    'required' => 0,
                ),
                array(
                    'key' => 'field_imdb_id',
                    'label' => 'IMDb ID',
                    'name' => 'autopost_movies_imdb_id',
                    'type' => 'text',
                    'required' => 0,
                ),
                array(
                    'key' => 'field_type',
                    'label' => 'Type',
                    'name' => 'autopost_movies_type',
                    'type' => 'select',
                    'choices' => array(
                        'movie' => 'Movie',
                        'tv' => 'TV Series'
                    ),
                    'required' => 0,
                ),
                array(
                    'key' => 'field_trailer_url',
                    'label' => 'Trailer URL',
                    'name' => 'autopost_movies_trailer_url',
                    'type' => 'url',
                    'required' => 0,
                ),
                array(
                    'key' => 'field_year',
                    'label' => 'Year',
                    'name' => 'autopost_movies_year',
                    'type' => 'number',
                    'required' => 0,
                ),
                array(
                    'key' => 'field_poster_url',
                    'label' => 'Poster URL',
                    'name' => 'autopost_movies_poster_url',
                    'type' => 'url',
                    'required' => 0,
                )
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'post',
                    ),
                ),
            ),
        );
        
        return $config;
    }
}