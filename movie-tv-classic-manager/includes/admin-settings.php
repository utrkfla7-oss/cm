<?php
/**
 * Admin Settings Page
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add admin menu
add_action('admin_menu', 'mtcm_add_admin_menu');
function mtcm_add_admin_menu() {
    add_options_page(
        __('Movie TV Classic Manager Settings', 'movie-tv-classic-manager'),
        __('Movie TV Manager', 'movie-tv-classic-manager'),
        'manage_options',
        'mtcm-settings',
        'mtcm_settings_page'
    );
}

// Register settings
add_action('admin_init', 'mtcm_register_settings');
function mtcm_register_settings() {
    register_setting('mtcm_settings_group', 'mtcm_tmdb_api_key');
    register_setting('mtcm_settings_group', 'mtcm_fifu_support');
    register_setting('mtcm_settings_group', 'mtcm_default_poster_size');
    register_setting('mtcm_settings_group', 'mtcm_auto_fetch_poster');
    register_setting('mtcm_settings_group', 'mtcm_cache_tmdb_data');
    register_setting('mtcm_settings_group', 'mtcm_enable_debug');

    // Add settings sections
    add_settings_section(
        'mtcm_tmdb_section',
        __('TMDB API Settings', 'movie-tv-classic-manager'),
        'mtcm_tmdb_section_callback',
        'mtcm-settings'
    );

    add_settings_section(
        'mtcm_display_section',
        __('Display Settings', 'movie-tv-classic-manager'),
        'mtcm_display_section_callback',
        'mtcm-settings'
    );

    add_settings_section(
        'mtcm_integration_section',
        __('Integration Settings', 'movie-tv-classic-manager'),
        'mtcm_integration_section_callback',
        'mtcm-settings'
    );

    // Add settings fields
    add_settings_field(
        'mtcm_tmdb_api_key',
        __('TMDB API Key', 'movie-tv-classic-manager'),
        'mtcm_tmdb_api_key_callback',
        'mtcm-settings',
        'mtcm_tmdb_section'
    );

    add_settings_field(
        'mtcm_auto_fetch_poster',
        __('Auto-fetch Posters', 'movie-tv-classic-manager'),
        'mtcm_auto_fetch_poster_callback',
        'mtcm-settings',
        'mtcm_tmdb_section'
    );

    add_settings_field(
        'mtcm_cache_tmdb_data',
        __('Cache TMDB Data', 'movie-tv-classic-manager'),
        'mtcm_cache_tmdb_data_callback',
        'mtcm-settings',
        'mtcm_tmdb_section'
    );

    add_settings_field(
        'mtcm_default_poster_size',
        __('Default Poster Size', 'movie-tv-classic-manager'),
        'mtcm_default_poster_size_callback',
        'mtcm-settings',
        'mtcm_display_section'
    );

    add_settings_field(
        'mtcm_fifu_support',
        __('FIFU Integration', 'movie-tv-classic-manager'),
        'mtcm_fifu_support_callback',
        'mtcm-settings',
        'mtcm_integration_section'
    );

    add_settings_field(
        'mtcm_enable_debug',
        __('Debug Mode', 'movie-tv-classic-manager'),
        'mtcm_enable_debug_callback',
        'mtcm-settings',
        'mtcm_integration_section'
    );
}

// Section callbacks
function mtcm_tmdb_section_callback() {
    echo '<p>' . __('Configure TMDB (The Movie Database) API integration for automatic movie and TV show data fetching.', 'movie-tv-classic-manager') . '</p>';
    echo '<p>' . sprintf(
        __('Get your free API key from <a href="%s" target="_blank">TMDB</a>.', 'movie-tv-classic-manager'),
        'https://www.themoviedb.org/settings/api'
    ) . '</p>';
}

function mtcm_display_section_callback() {
    echo '<p>' . __('Configure how movies and TV shows are displayed on your website.', 'movie-tv-classic-manager') . '</p>';
}

function mtcm_integration_section_callback() {
    echo '<p>' . __('Configure integration with other WordPress plugins and systems.', 'movie-tv-classic-manager') . '</p>';
}

// Field callbacks
function mtcm_tmdb_api_key_callback() {
    $value = get_option('mtcm_tmdb_api_key', '');
    echo '<input type="text" id="mtcm_tmdb_api_key" name="mtcm_tmdb_api_key" value="' . esc_attr($value) . '" class="regular-text" />';
    echo '<p class="description">' . __('Enter your TMDB API key to enable automatic movie and TV show data fetching.', 'movie-tv-classic-manager') . '</p>';
    
    if (!empty($value)) {
        echo '<p class="mtcm-api-status"><span class="dashicons dashicons-yes-alt" style="color: green;"></span> ' . __('API key configured', 'movie-tv-classic-manager') . '</p>';
    } else {
        echo '<p class="mtcm-api-status"><span class="dashicons dashicons-warning" style="color: orange;"></span> ' . __('API key required for TMDB integration', 'movie-tv-classic-manager') . '</p>';
    }
}

function mtcm_auto_fetch_poster_callback() {
    $value = get_option('mtcm_auto_fetch_poster', '1');
    echo '<input type="checkbox" id="mtcm_auto_fetch_poster" name="mtcm_auto_fetch_poster" value="1" ' . checked($value, '1', false) . ' />';
    echo '<label for="mtcm_auto_fetch_poster">' . __('Automatically fetch and set poster images from TMDB when importing data', 'movie-tv-classic-manager') . '</label>';
}

function mtcm_cache_tmdb_data_callback() {
    $value = get_option('mtcm_cache_tmdb_data', '1');
    echo '<input type="checkbox" id="mtcm_cache_tmdb_data" name="mtcm_cache_tmdb_data" value="1" ' . checked($value, '1', false) . ' />';
    echo '<label for="mtcm_cache_tmdb_data">' . __('Cache TMDB API responses to improve performance (recommended)', 'movie-tv-classic-manager') . '</label>';
}

function mtcm_default_poster_size_callback() {
    $value = get_option('mtcm_default_poster_size', 'medium');
    $sizes = array(
        'small' => __('Small (150x225)', 'movie-tv-classic-manager'),
        'medium' => __('Medium (300x450)', 'movie-tv-classic-manager'),
        'large' => __('Large (500x750)', 'movie-tv-classic-manager'),
        'original' => __('Original Size', 'movie-tv-classic-manager')
    );
    
    echo '<select id="mtcm_default_poster_size" name="mtcm_default_poster_size">';
    foreach ($sizes as $size_key => $size_label) {
        echo '<option value="' . esc_attr($size_key) . '" ' . selected($value, $size_key, false) . '>' . esc_html($size_label) . '</option>';
    }
    echo '</select>';
    echo '<p class="description">' . __('Default size for movie and TV show poster images.', 'movie-tv-classic-manager') . '</p>';
}

function mtcm_fifu_support_callback() {
    $value = get_option('mtcm_fifu_support', '1');
    $fifu_active = mtcm_is_fifu_active();
    
    echo '<input type="checkbox" id="mtcm_fifu_support" name="mtcm_fifu_support" value="1" ' . checked($value, '1', false) . ' />';
    echo '<label for="mtcm_fifu_support">' . __('Enable Featured Image from URL (FIFU) integration', 'movie-tv-classic-manager') . '</label>';
    
    if ($fifu_active) {
        echo '<p class="mtcm-plugin-status"><span class="dashicons dashicons-yes-alt" style="color: green;"></span> ' . __('FIFU plugin is active', 'movie-tv-classic-manager') . '</p>';
    } else {
        echo '<p class="mtcm-plugin-status"><span class="dashicons dashicons-warning" style="color: orange;"></span> ' . __('FIFU plugin not detected. Install and activate Featured Image from URL plugin for best experience.', 'movie-tv-classic-manager') . '</p>';
    }
}

function mtcm_enable_debug_callback() {
    $value = get_option('mtcm_enable_debug', '0');
    echo '<input type="checkbox" id="mtcm_enable_debug" name="mtcm_enable_debug" value="1" ' . checked($value, '1', false) . ' />';
    echo '<label for="mtcm_enable_debug">' . __('Enable debug mode for troubleshooting', 'movie-tv-classic-manager') . '</label>';
    echo '<p class="description">' . __('Only enable this if you are experiencing issues and need to troubleshoot.', 'movie-tv-classic-manager') . '</p>';
}

// Settings page content
function mtcm_settings_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'movie-tv-classic-manager'));
    }

    // Handle form submission
    if (isset($_POST['submit'])) {
        // WordPress will handle the settings update through the Settings API
        add_settings_error('mtcm_messages', 'mtcm_message', __('Settings saved successfully.', 'movie-tv-classic-manager'), 'updated');
    }

    // Test TMDB API connection
    if (isset($_POST['test_tmdb'])) {
        $api_key = get_option('mtcm_tmdb_api_key', '');
        if (!empty($api_key)) {
            $test_result = mtcm_test_tmdb_connection($api_key);
            if ($test_result) {
                add_settings_error('mtcm_messages', 'mtcm_tmdb_test', __('TMDB API connection successful!', 'movie-tv-classic-manager'), 'updated');
            } else {
                add_settings_error('mtcm_messages', 'mtcm_tmdb_test', __('TMDB API connection failed. Please check your API key.', 'movie-tv-classic-manager'), 'error');
            }
        } else {
            add_settings_error('mtcm_messages', 'mtcm_tmdb_test', __('Please enter a TMDB API key first.', 'movie-tv-classic-manager'), 'error');
        }
    }

    settings_errors('mtcm_messages');
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <div class="mtcm-settings-header">
            <p><?php _e('Configure Movie TV Classic Manager settings for optimal performance and integration.', 'movie-tv-classic-manager'); ?></p>
        </div>

        <form method="post" action="options.php">
            <?php
            settings_fields('mtcm_settings_group');
            do_settings_sections('mtcm-settings');
            submit_button();
            ?>
        </form>

        <form method="post" style="margin-top: 20px;">
            <h3><?php _e('Test TMDB Connection', 'movie-tv-classic-manager'); ?></h3>
            <p><?php _e('Test your TMDB API key connection to ensure it\'s working properly.', 'movie-tv-classic-manager'); ?></p>
            <?php submit_button(__('Test TMDB API', 'movie-tv-classic-manager'), 'secondary', 'test_tmdb'); ?>
        </form>

        <div class="mtcm-info-section" style="margin-top: 30px; padding: 20px; background: #f9f9f9; border-left: 4px solid #0073aa;">
            <h3><?php _e('Plugin Information', 'movie-tv-classic-manager'); ?></h3>
            <p><strong><?php _e('Version:', 'movie-tv-classic-manager'); ?></strong> <?php echo MTCM_VERSION; ?></p>
            <p><strong><?php _e('Plugin Status:', 'movie-tv-classic-manager'); ?></strong> 
                <?php
                $status_items = array();
                
                if (get_option('mtcm_tmdb_api_key', '')) {
                    $status_items[] = '<span style="color: green;">✓ TMDB API Configured</span>';
                } else {
                    $status_items[] = '<span style="color: orange;">⚠ TMDB API Not Configured</span>';
                }
                
                if (mtcm_is_fifu_active()) {
                    $status_items[] = '<span style="color: green;">✓ FIFU Integration Active</span>';
                } else {
                    $status_items[] = '<span style="color: gray;">○ FIFU Not Active</span>';
                }
                
                if (current_theme_supports('post-thumbnails')) {
                    $status_items[] = '<span style="color: green;">✓ Featured Images Supported</span>';
                } else {
                    $status_items[] = '<span style="color: orange;">⚠ Featured Images Not Supported</span>';
                }
                
                echo implode(' | ', $status_items);
                ?>
            </p>
            
            <h4><?php _e('Shortcodes Available:', 'movie-tv-classic-manager'); ?></h4>
            <ul style="list-style-type: disc; margin-left: 20px;">
                <li><code>[mtcm_movie id="123"]</code> - <?php _e('Display single movie', 'movie-tv-classic-manager'); ?></li>
                <li><code>[mtcm_tv_show id="456"]</code> - <?php _e('Display single TV show', 'movie-tv-classic-manager'); ?></li>
                <li><code>[mtcm_movie_list limit="5"]</code> - <?php _e('Display list of movies', 'movie-tv-classic-manager'); ?></li>
                <li><code>[mtcm_tv_list limit="5"]</code> - <?php _e('Display list of TV shows', 'movie-tv-classic-manager'); ?></li>
            </ul>
            
            <h4><?php _e('TinyMCE Integration:', 'movie-tv-classic-manager'); ?></h4>
            <p><?php _e('Use the Movie/TV button in the Classic Editor toolbar to easily insert shortcodes and search TMDB content.', 'movie-tv-classic-manager'); ?></p>
        </div>
    </div>

    <style>
    .mtcm-settings-header {
        margin-bottom: 20px;
        padding: 15px;
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 3px;
    }
    
    .mtcm-api-status, .mtcm-plugin-status {
        margin-top: 5px;
        font-style: italic;
    }
    
    .mtcm-info-section {
        border-radius: 3px;
    }
    
    .mtcm-info-section h3 {
        margin-top: 0;
        color: #0073aa;
    }
    
    .mtcm-info-section code {
        background: #f0f0f0;
        padding: 2px 4px;
        border-radius: 2px;
        font-size: 13px;
    }
    </style>
    <?php
}

// Test TMDB API connection
function mtcm_test_tmdb_connection($api_key) {
    $url = 'https://api.themoviedb.org/3/configuration?api_key=' . $api_key;
    
    $response = wp_remote_get($url, array(
        'timeout' => 10,
        'headers' => array(
            'User-Agent' => 'Movie TV Classic Manager/' . MTCM_VERSION
        )
    ));
    
    if (is_wp_error($response)) {
        return false;
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    return $response_code === 200;
}