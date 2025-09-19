<?php
/**
 * Meta Boxes for Movie and TV Show Data Entry
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add meta boxes
add_action('add_meta_boxes', 'mtcm_add_meta_boxes');
function mtcm_add_meta_boxes() {
    // Movie meta boxes
    add_meta_box(
        'mtcm_movie_details',
        __('Movie Details', 'movie-tv-classic-manager'),
        'mtcm_movie_details_callback',
        'mtcm_movie',
        'normal',
        'high'
    );

    add_meta_box(
        'mtcm_movie_tmdb',
        __('TMDB Integration', 'movie-tv-classic-manager'),
        'mtcm_tmdb_integration_callback',
        'mtcm_movie',
        'side',
        'default'
    );

    add_meta_box(
        'mtcm_movie_poster',
        __('Poster & Images', 'movie-tv-classic-manager'),
        'mtcm_poster_callback',
        'mtcm_movie',
        'side',
        'low'
    );

    // TV Show meta boxes
    add_meta_box(
        'mtcm_tv_show_details',
        __('TV Show Details', 'movie-tv-classic-manager'),
        'mtcm_tv_show_details_callback',
        'mtcm_tv_show',
        'normal',
        'high'
    );

    add_meta_box(
        'mtcm_tv_show_tmdb',
        __('TMDB Integration', 'movie-tv-classic-manager'),
        'mtcm_tmdb_integration_callback',
        'mtcm_tv_show',
        'side',
        'default'
    );

    add_meta_box(
        'mtcm_tv_show_poster',
        __('Poster & Images', 'movie-tv-classic-manager'),
        'mtcm_poster_callback',
        'mtcm_tv_show',
        'side',
        'low'
    );
}

// Movie details meta box callback
function mtcm_movie_details_callback($post) {
    wp_nonce_field('mtcm_movie_meta_nonce', 'mtcm_movie_meta_nonce');
    
    // Get current values
    $release_date = get_post_meta($post->ID, '_mtcm_release_date', true);
    $runtime = get_post_meta($post->ID, '_mtcm_runtime', true);
    $director = get_post_meta($post->ID, '_mtcm_director', true);
    $cast = get_post_meta($post->ID, '_mtcm_cast', true);
    $rating = get_post_meta($post->ID, '_mtcm_rating', true);
    $imdb_id = get_post_meta($post->ID, '_mtcm_imdb_id', true);
    $budget = get_post_meta($post->ID, '_mtcm_budget', true);
    $revenue = get_post_meta($post->ID, '_mtcm_revenue', true);
    $tagline = get_post_meta($post->ID, '_mtcm_tagline', true);
    $country = get_post_meta($post->ID, '_mtcm_country', true);
    $language = get_post_meta($post->ID, '_mtcm_language', true);
    ?>
    <table class="form-table mtcm-meta-table">
        <tr>
            <th scope="row">
                <label for="mtcm_release_date"><?php _e('Release Date', 'movie-tv-classic-manager'); ?></label>
            </th>
            <td>
                <input type="date" id="mtcm_release_date" name="mtcm_release_date" value="<?php echo esc_attr($release_date); ?>" class="regular-text" />
                <p class="description"><?php _e('Official release date of the movie.', 'movie-tv-classic-manager'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="mtcm_runtime"><?php _e('Runtime (minutes)', 'movie-tv-classic-manager'); ?></label>
            </th>
            <td>
                <input type="number" id="mtcm_runtime" name="mtcm_runtime" value="<?php echo esc_attr($runtime); ?>" class="small-text" min="1" step="1" />
                <p class="description"><?php _e('Duration of the movie in minutes.', 'movie-tv-classic-manager'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="mtcm_director"><?php _e('Director', 'movie-tv-classic-manager'); ?></label>
            </th>
            <td>
                <input type="text" id="mtcm_director" name="mtcm_director" value="<?php echo esc_attr($director); ?>" class="regular-text" />
                <p class="description"><?php _e('Main director of the movie.', 'movie-tv-classic-manager'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="mtcm_cast"><?php _e('Main Cast', 'movie-tv-classic-manager'); ?></label>
            </th>
            <td>
                <textarea id="mtcm_cast" name="mtcm_cast" rows="3" class="large-text"><?php echo esc_textarea($cast); ?></textarea>
                <p class="description"><?php _e('Main cast members, separated by commas.', 'movie-tv-classic-manager'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="mtcm_rating"><?php _e('Rating', 'movie-tv-classic-manager'); ?></label>
            </th>
            <td>
                <select id="mtcm_rating" name="mtcm_rating">
                    <option value=""><?php _e('Select Rating', 'movie-tv-classic-manager'); ?></option>
                    <option value="G" <?php selected($rating, 'G'); ?>>G</option>
                    <option value="PG" <?php selected($rating, 'PG'); ?>>PG</option>
                    <option value="PG-13" <?php selected($rating, 'PG-13'); ?>>PG-13</option>
                    <option value="R" <?php selected($rating, 'R'); ?>>R</option>
                    <option value="NC-17" <?php selected($rating, 'NC-17'); ?>>NC-17</option>
                    <option value="NR" <?php selected($rating, 'NR'); ?>><?php _e('Not Rated', 'movie-tv-classic-manager'); ?></option>
                </select>
                <p class="description"><?php _e('MPAA rating or equivalent.', 'movie-tv-classic-manager'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="mtcm_imdb_id"><?php _e('IMDb ID', 'movie-tv-classic-manager'); ?></label>
            </th>
            <td>
                <input type="text" id="mtcm_imdb_id" name="mtcm_imdb_id" value="<?php echo esc_attr($imdb_id); ?>" class="regular-text" placeholder="tt1234567" />
                <p class="description"><?php _e('IMDb identifier (e.g., tt1234567).', 'movie-tv-classic-manager'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="mtcm_budget"><?php _e('Budget ($)', 'movie-tv-classic-manager'); ?></label>
            </th>
            <td>
                <input type="number" id="mtcm_budget" name="mtcm_budget" value="<?php echo esc_attr($budget); ?>" class="regular-text" min="0" step="1000" />
                <p class="description"><?php _e('Production budget in US dollars.', 'movie-tv-classic-manager'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="mtcm_revenue"><?php _e('Box Office ($)', 'movie-tv-classic-manager'); ?></label>
            </th>
            <td>
                <input type="number" id="mtcm_revenue" name="mtcm_revenue" value="<?php echo esc_attr($revenue); ?>" class="regular-text" min="0" step="1000" />
                <p class="description"><?php _e('Total box office revenue in US dollars.', 'movie-tv-classic-manager'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="mtcm_tagline"><?php _e('Tagline', 'movie-tv-classic-manager'); ?></label>
            </th>
            <td>
                <input type="text" id="mtcm_tagline" name="mtcm_tagline" value="<?php echo esc_attr($tagline); ?>" class="large-text" />
                <p class="description"><?php _e('Movie tagline or slogan.', 'movie-tv-classic-manager'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="mtcm_country"><?php _e('Country', 'movie-tv-classic-manager'); ?></label>
            </th>
            <td>
                <input type="text" id="mtcm_country" name="mtcm_country" value="<?php echo esc_attr($country); ?>" class="regular-text" />
                <p class="description"><?php _e('Country of origin.', 'movie-tv-classic-manager'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="mtcm_language"><?php _e('Language', 'movie-tv-classic-manager'); ?></label>
            </th>
            <td>
                <input type="text" id="mtcm_language" name="mtcm_language" value="<?php echo esc_attr($language); ?>" class="regular-text" />
                <p class="description"><?php _e('Original language of the movie.', 'movie-tv-classic-manager'); ?></p>
            </td>
        </tr>
    </table>
    <?php
}

// TV Show details meta box callback
function mtcm_tv_show_details_callback($post) {
    wp_nonce_field('mtcm_tv_show_meta_nonce', 'mtcm_tv_show_meta_nonce');
    
    // Get current values
    $first_air_date = get_post_meta($post->ID, '_mtcm_first_air_date', true);
    $last_air_date = get_post_meta($post->ID, '_mtcm_last_air_date', true);
    $creator = get_post_meta($post->ID, '_mtcm_creator', true);
    $cast = get_post_meta($post->ID, '_mtcm_cast', true);
    $rating = get_post_meta($post->ID, '_mtcm_rating', true);
    $imdb_id = get_post_meta($post->ID, '_mtcm_imdb_id', true);
    $total_seasons = get_post_meta($post->ID, '_mtcm_total_seasons', true);
    $total_episodes = get_post_meta($post->ID, '_mtcm_total_episodes', true);
    $episode_runtime = get_post_meta($post->ID, '_mtcm_episode_runtime', true);
    $network = get_post_meta($post->ID, '_mtcm_network', true);
    $status = get_post_meta($post->ID, '_mtcm_status', true);
    $country = get_post_meta($post->ID, '_mtcm_country', true);
    $language = get_post_meta($post->ID, '_mtcm_language', true);
    ?>
    <table class="form-table mtcm-meta-table">
        <tr>
            <th scope="row">
                <label for="mtcm_first_air_date"><?php _e('First Air Date', 'movie-tv-classic-manager'); ?></label>
            </th>
            <td>
                <input type="date" id="mtcm_first_air_date" name="mtcm_first_air_date" value="<?php echo esc_attr($first_air_date); ?>" class="regular-text" />
                <p class="description"><?php _e('Date when the show first aired.', 'movie-tv-classic-manager'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="mtcm_last_air_date"><?php _e('Last Air Date', 'movie-tv-classic-manager'); ?></label>
            </th>
            <td>
                <input type="date" id="mtcm_last_air_date" name="mtcm_last_air_date" value="<?php echo esc_attr($last_air_date); ?>" class="regular-text" />
                <p class="description"><?php _e('Date when the show last aired (leave empty if ongoing).', 'movie-tv-classic-manager'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="mtcm_creator"><?php _e('Creator', 'movie-tv-classic-manager'); ?></label>
            </th>
            <td>
                <input type="text" id="mtcm_creator" name="mtcm_creator" value="<?php echo esc_attr($creator); ?>" class="regular-text" />
                <p class="description"><?php _e('Creator(s) of the TV show.', 'movie-tv-classic-manager'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="mtcm_cast"><?php _e('Main Cast', 'movie-tv-classic-manager'); ?></label>
            </th>
            <td>
                <textarea id="mtcm_cast" name="mtcm_cast" rows="3" class="large-text"><?php echo esc_textarea($cast); ?></textarea>
                <p class="description"><?php _e('Main cast members, separated by commas.', 'movie-tv-classic-manager'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="mtcm_total_seasons"><?php _e('Total Seasons', 'movie-tv-classic-manager'); ?></label>
            </th>
            <td>
                <input type="number" id="mtcm_total_seasons" name="mtcm_total_seasons" value="<?php echo esc_attr($total_seasons); ?>" class="small-text" min="1" step="1" />
                <p class="description"><?php _e('Total number of seasons.', 'movie-tv-classic-manager'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="mtcm_total_episodes"><?php _e('Total Episodes', 'movie-tv-classic-manager'); ?></label>
            </th>
            <td>
                <input type="number" id="mtcm_total_episodes" name="mtcm_total_episodes" value="<?php echo esc_attr($total_episodes); ?>" class="regular-text" min="1" step="1" />
                <p class="description"><?php _e('Total number of episodes across all seasons.', 'movie-tv-classic-manager'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="mtcm_episode_runtime"><?php _e('Episode Runtime (minutes)', 'movie-tv-classic-manager'); ?></label>
            </th>
            <td>
                <input type="number" id="mtcm_episode_runtime" name="mtcm_episode_runtime" value="<?php echo esc_attr($episode_runtime); ?>" class="small-text" min="1" step="1" />
                <p class="description"><?php _e('Average runtime per episode in minutes.', 'movie-tv-classic-manager'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="mtcm_network"><?php _e('Network/Platform', 'movie-tv-classic-manager'); ?></label>
            </th>
            <td>
                <input type="text" id="mtcm_network" name="mtcm_network" value="<?php echo esc_attr($network); ?>" class="regular-text" />
                <p class="description"><?php _e('TV network or streaming platform.', 'movie-tv-classic-manager'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="mtcm_status"><?php _e('Status', 'movie-tv-classic-manager'); ?></label>
            </th>
            <td>
                <select id="mtcm_status" name="mtcm_status">
                    <option value=""><?php _e('Select Status', 'movie-tv-classic-manager'); ?></option>
                    <option value="Returning Series" <?php selected($status, 'Returning Series'); ?>><?php _e('Returning Series', 'movie-tv-classic-manager'); ?></option>
                    <option value="Ended" <?php selected($status, 'Ended'); ?>><?php _e('Ended', 'movie-tv-classic-manager'); ?></option>
                    <option value="Canceled" <?php selected($status, 'Canceled'); ?>><?php _e('Canceled', 'movie-tv-classic-manager'); ?></option>
                    <option value="In Production" <?php selected($status, 'In Production'); ?>><?php _e('In Production', 'movie-tv-classic-manager'); ?></option>
                    <option value="Pilot" <?php selected($status, 'Pilot'); ?>><?php _e('Pilot', 'movie-tv-classic-manager'); ?></option>
                </select>
                <p class="description"><?php _e('Current status of the TV show.', 'movie-tv-classic-manager'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="mtcm_rating"><?php _e('Rating', 'movie-tv-classic-manager'); ?></label>
            </th>
            <td>
                <select id="mtcm_rating" name="mtcm_rating">
                    <option value=""><?php _e('Select Rating', 'movie-tv-classic-manager'); ?></option>
                    <option value="TV-Y" <?php selected($rating, 'TV-Y'); ?>>TV-Y</option>
                    <option value="TV-Y7" <?php selected($rating, 'TV-Y7'); ?>>TV-Y7</option>
                    <option value="TV-G" <?php selected($rating, 'TV-G'); ?>>TV-G</option>
                    <option value="TV-PG" <?php selected($rating, 'TV-PG'); ?>>TV-PG</option>
                    <option value="TV-14" <?php selected($rating, 'TV-14'); ?>>TV-14</option>
                    <option value="TV-MA" <?php selected($rating, 'TV-MA'); ?>>TV-MA</option>
                    <option value="NR" <?php selected($rating, 'NR'); ?>><?php _e('Not Rated', 'movie-tv-classic-manager'); ?></option>
                </select>
                <p class="description"><?php _e('TV content rating.', 'movie-tv-classic-manager'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="mtcm_imdb_id"><?php _e('IMDb ID', 'movie-tv-classic-manager'); ?></label>
            </th>
            <td>
                <input type="text" id="mtcm_imdb_id" name="mtcm_imdb_id" value="<?php echo esc_attr($imdb_id); ?>" class="regular-text" placeholder="tt1234567" />
                <p class="description"><?php _e('IMDb identifier (e.g., tt1234567).', 'movie-tv-classic-manager'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="mtcm_country"><?php _e('Country', 'movie-tv-classic-manager'); ?></label>
            </th>
            <td>
                <input type="text" id="mtcm_country" name="mtcm_country" value="<?php echo esc_attr($country); ?>" class="regular-text" />
                <p class="description"><?php _e('Country of origin.', 'movie-tv-classic-manager'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="mtcm_language"><?php _e('Language', 'movie-tv-classic-manager'); ?></label>
            </th>
            <td>
                <input type="text" id="mtcm_language" name="mtcm_language" value="<?php echo esc_attr($language); ?>" class="regular-text" />
                <p class="description"><?php _e('Original language of the show.', 'movie-tv-classic-manager'); ?></p>
            </td>
        </tr>
    </table>
    <?php
}

// TMDB integration meta box callback
function mtcm_tmdb_integration_callback($post) {
    $tmdb_id = get_post_meta($post->ID, '_mtcm_tmdb_id', true);
    $tmdb_data = get_post_meta($post->ID, '_mtcm_tmdb_data', true);
    $api_key = get_option('mtcm_tmdb_api_key', '');
    ?>
    <div class="mtcm-tmdb-integration">
        <p>
            <label for="mtcm_tmdb_id"><strong><?php _e('TMDB ID:', 'movie-tv-classic-manager'); ?></strong></label><br>
            <input type="text" id="mtcm_tmdb_id" name="mtcm_tmdb_id" value="<?php echo esc_attr($tmdb_id); ?>" class="widefat" placeholder="<?php _e('Enter TMDB ID', 'movie-tv-classic-manager'); ?>" />
        </p>
        
        <?php if (!empty($api_key)): ?>
            <p>
                <button type="button" id="mtcm_fetch_tmdb_data" class="button button-secondary" <?php echo empty($tmdb_id) ? 'disabled' : ''; ?>>
                    <?php _e('Fetch TMDB Data', 'movie-tv-classic-manager'); ?>
                </button>
                <button type="button" id="mtcm_search_tmdb" class="button button-secondary">
                    <?php _e('Search TMDB', 'movie-tv-classic-manager'); ?>
                </button>
            </p>
            
            <div id="mtcm_tmdb_search_results" style="display: none; max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; margin-top: 10px;"></div>
            
            <?php if ($tmdb_data): ?>
                <div class="mtcm-tmdb-status">
                    <p><span class="dashicons dashicons-yes-alt" style="color: green;"></span> <?php _e('TMDB data available', 'movie-tv-classic-manager'); ?></p>
                    <p><small><?php _e('Last updated:', 'movie-tv-classic-manager'); ?> <?php echo date_i18n(get_option('date_format'), strtotime($tmdb_data['last_updated'] ?? 'now')); ?></small></p>
                    <button type="button" id="mtcm_refresh_tmdb_data" class="button button-link"><?php _e('Refresh Data', 'movie-tv-classic-manager'); ?></button>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <p class="description">
                <span class="dashicons dashicons-warning" style="color: orange;"></span>
                <?php printf(
                    __('Configure TMDB API key in <a href="%s">settings</a> to enable automatic data fetching.', 'movie-tv-classic-manager'),
                    admin_url('options-general.php?page=mtcm-settings')
                ); ?>
            </p>
        <?php endif; ?>
    </div>
    <?php
}

// Poster and images meta box callback
function mtcm_poster_callback($post) {
    $poster_url = get_post_meta($post->ID, '_mtcm_poster_url', true);
    $backdrop_url = get_post_meta($post->ID, '_mtcm_backdrop_url', true);
    ?>
    <div class="mtcm-poster-section">
        <p>
            <label for="mtcm_poster_url"><strong><?php _e('Poster URL:', 'movie-tv-classic-manager'); ?></strong></label><br>
            <input type="url" id="mtcm_poster_url" name="mtcm_poster_url" value="<?php echo esc_attr($poster_url); ?>" class="widefat" placeholder="https://" />
            <button type="button" id="mtcm_set_poster" class="button button-secondary" style="margin-top: 5px;">
                <?php _e('Set as Featured Image', 'movie-tv-classic-manager'); ?>
            </button>
        </p>
        
        <p>
            <label for="mtcm_backdrop_url"><strong><?php _e('Backdrop URL:', 'movie-tv-classic-manager'); ?></strong></label><br>
            <input type="url" id="mtcm_backdrop_url" name="mtcm_backdrop_url" value="<?php echo esc_attr($backdrop_url); ?>" class="widefat" placeholder="https://" />
        </p>
        
        <?php if (mtcm_is_fifu_active()): ?>
            <p class="mtcm-fifu-notice">
                <span class="dashicons dashicons-yes-alt" style="color: green;"></span>
                <?php _e('FIFU integration is active - poster URLs will be automatically handled.', 'movie-tv-classic-manager'); ?>
            </p>
        <?php else: ?>
            <p class="mtcm-fifu-notice">
                <span class="dashicons dashicons-info"></span>
                <?php _e('Install Featured Image from URL (FIFU) plugin for automatic poster handling.', 'movie-tv-classic-manager'); ?>
            </p>
        <?php endif; ?>
        
        <?php if ($poster_url): ?>
            <div class="mtcm-poster-preview">
                <img src="<?php echo esc_url($poster_url); ?>" alt="<?php _e('Poster Preview', 'movie-tv-classic-manager'); ?>" style="max-width: 100%; height: auto; border: 1px solid #ddd;" />
            </div>
        <?php endif; ?>
    </div>
    <?php
}

// Save meta box data
add_action('save_post', 'mtcm_save_meta_box_data');
function mtcm_save_meta_box_data($post_id) {
    // Check if our nonce is set
    if (!isset($_POST['mtcm_movie_meta_nonce']) && !isset($_POST['mtcm_tv_show_meta_nonce'])) {
        return;
    }

    // Verify that the nonce is valid
    $nonce_verified = false;
    if (isset($_POST['mtcm_movie_meta_nonce']) && wp_verify_nonce($_POST['mtcm_movie_meta_nonce'], 'mtcm_movie_meta_nonce')) {
        $nonce_verified = true;
    } elseif (isset($_POST['mtcm_tv_show_meta_nonce']) && wp_verify_nonce($_POST['mtcm_tv_show_meta_nonce'], 'mtcm_tv_show_meta_nonce')) {
        $nonce_verified = true;
    }

    if (!$nonce_verified) {
        return;
    }

    // If this is an autosave, our form has not been submitted, so we don't want to do anything
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Check the user's permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Get post type
    $post_type = get_post_type($post_id);
    
    // Define fields to save based on post type
    $fields_to_save = array();
    
    if ($post_type === 'mtcm_movie') {
        $fields_to_save = array(
            'mtcm_release_date' => '_mtcm_release_date',
            'mtcm_runtime' => '_mtcm_runtime',
            'mtcm_director' => '_mtcm_director',
            'mtcm_cast' => '_mtcm_cast',
            'mtcm_rating' => '_mtcm_rating',
            'mtcm_imdb_id' => '_mtcm_imdb_id',
            'mtcm_budget' => '_mtcm_budget',
            'mtcm_revenue' => '_mtcm_revenue',
            'mtcm_tagline' => '_mtcm_tagline',
            'mtcm_country' => '_mtcm_country',
            'mtcm_language' => '_mtcm_language',
        );
    } elseif ($post_type === 'mtcm_tv_show') {
        $fields_to_save = array(
            'mtcm_first_air_date' => '_mtcm_first_air_date',
            'mtcm_last_air_date' => '_mtcm_last_air_date',
            'mtcm_creator' => '_mtcm_creator',
            'mtcm_cast' => '_mtcm_cast',
            'mtcm_rating' => '_mtcm_rating',
            'mtcm_imdb_id' => '_mtcm_imdb_id',
            'mtcm_total_seasons' => '_mtcm_total_seasons',
            'mtcm_total_episodes' => '_mtcm_total_episodes',
            'mtcm_episode_runtime' => '_mtcm_episode_runtime',
            'mtcm_network' => '_mtcm_network',
            'mtcm_status' => '_mtcm_status',
            'mtcm_country' => '_mtcm_country',
            'mtcm_language' => '_mtcm_language',
        );
    }
    
    // Common fields for both post types
    $common_fields = array(
        'mtcm_tmdb_id' => '_mtcm_tmdb_id',
        'mtcm_poster_url' => '_mtcm_poster_url',
        'mtcm_backdrop_url' => '_mtcm_backdrop_url',
    );
    
    $fields_to_save = array_merge($fields_to_save, $common_fields);
    
    // Save each field
    foreach ($fields_to_save as $form_field => $meta_key) {
        if (isset($_POST[$form_field])) {
            $value = $_POST[$form_field];
            
            // Sanitize based on field type
            if (in_array($form_field, array('mtcm_cast'))) {
                $value = sanitize_textarea_field($value);
            } elseif (in_array($form_field, array('mtcm_poster_url', 'mtcm_backdrop_url'))) {
                $value = esc_url_raw($value);
            } elseif (in_array($form_field, array('mtcm_runtime', 'mtcm_budget', 'mtcm_revenue', 'mtcm_total_seasons', 'mtcm_total_episodes', 'mtcm_episode_runtime'))) {
                $value = intval($value);
            } else {
                $value = sanitize_text_field($value);
            }
            
            update_post_meta($post_id, $meta_key, $value);
        }
    }
    
    // Handle poster URL with FIFU integration
    if (isset($_POST['mtcm_poster_url']) && !empty($_POST['mtcm_poster_url'])) {
        $poster_url = esc_url_raw($_POST['mtcm_poster_url']);
        mtcm_set_featured_image_from_url($post_id, $poster_url);
    }
}

// Add custom CSS for meta boxes
add_action('admin_head', 'mtcm_meta_box_styles');
function mtcm_meta_box_styles() {
    echo '<style>
    .mtcm-meta-table th {
        width: 200px;
        text-align: left;
        font-weight: 600;
    }
    
    .mtcm-tmdb-integration .mtcm-tmdb-status {
        background: #f0f8f0;
        border: 1px solid #c8e6c9;
        padding: 10px;
        margin-top: 10px;
        border-radius: 3px;
    }
    
    .mtcm-poster-section .mtcm-poster-preview {
        margin-top: 10px;
        text-align: center;
    }
    
    .mtcm-fifu-notice {
        font-style: italic;
        color: #666;
    }
    
    #mtcm_tmdb_search_results .mtcm-search-item {
        border-bottom: 1px solid #eee;
        padding: 8px;
        cursor: pointer;
        transition: background-color 0.2s;
    }
    
    #mtcm_tmdb_search_results .mtcm-search-item:hover {
        background-color: #f5f5f5;
    }
    
    #mtcm_tmdb_search_results .mtcm-search-item:last-child {
        border-bottom: none;
    }
    </style>';
}