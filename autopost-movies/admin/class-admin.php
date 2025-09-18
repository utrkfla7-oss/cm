<?php
/**
 * Admin panel for AutoPost Movies plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class APM_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_apm_manual_sync', array($this, 'ajax_manual_sync'));
        add_action('wp_ajax_apm_get_logs', array($this, 'ajax_get_logs'));
        add_action('wp_ajax_apm_clear_logs', array($this, 'ajax_clear_logs'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __('AutoPost Movies', 'autopost-movies'),
            __('AutoPost Movies', 'autopost-movies'),
            'manage_options',
            'autopost-movies',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Initialize settings
     */
    public function init_settings() {
        register_setting('apm_settings', 'apm_tmdb_api_key');
        register_setting('apm_settings', 'apm_wikipedia_enabled');
        register_setting('apm_settings', 'apm_imdb_api_key');
        register_setting('apm_settings', 'apm_youtube_api_key');
        register_setting('apm_settings', 'apm_cron_schedule');
        register_setting('apm_settings', 'apm_tmdb_plot_enabled');
        register_setting('apm_settings', 'apm_wikipedia_plot_enabled');
        register_setting('apm_settings', 'apm_imdb_plot_enabled');
        register_setting('apm_settings', 'apm_content_order');
        register_setting('apm_settings', 'apm_posts_per_run');
        register_setting('apm_settings', 'apm_post_status');
        register_setting('apm_settings', 'apm_post_category');
        register_setting('apm_settings', 'apm_featured_image_enabled');
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ('settings_page_autopost-movies' !== $hook) {
            return;
        }
        
        wp_enqueue_script('apm-admin', APM_PLUGIN_URL . 'assets/admin.js', array('jquery'), APM_VERSION, true);
        wp_enqueue_style('apm-admin', APM_PLUGIN_URL . 'assets/admin.css', array(), APM_VERSION);
        
        wp_localize_script('apm-admin', 'apm_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('apm_nonce')
        ));
    }
    
    /**
     * Admin page content
     */
    public function admin_page() {
        if (isset($_POST['submit'])) {
            $this->save_settings();
        }
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="apm-admin-container">
                <div class="apm-main-content">
                    <form method="post" action="">
                        <?php wp_nonce_field('apm_settings', 'apm_nonce'); ?>
                        
                        <div class="apm-section">
                            <h2><?php _e('API Configuration', 'autopost-movies'); ?></h2>
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="apm_tmdb_api_key"><?php _e('TMDB API Key', 'autopost-movies'); ?> *</label>
                                    </th>
                                    <td>
                                        <input type="text" id="apm_tmdb_api_key" name="apm_tmdb_api_key" 
                                               value="<?php echo esc_attr(get_option('apm_tmdb_api_key')); ?>" 
                                               class="regular-text" required />
                                        <p class="description">
                                            <?php _e('Get your API key from', 'autopost-movies'); ?> 
                                            <a href="https://www.themoviedb.org/settings/api" target="_blank">TMDB</a>
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="apm_youtube_api_key"><?php _e('YouTube API Key', 'autopost-movies'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="apm_youtube_api_key" name="apm_youtube_api_key" 
                                               value="<?php echo esc_attr(get_option('apm_youtube_api_key')); ?>" 
                                               class="regular-text" />
                                        <p class="description">
                                            <?php _e('Optional: For embedding trailers', 'autopost-movies'); ?>
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="apm_imdb_api_key"><?php _e('IMDb API Key', 'autopost-movies'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="apm_imdb_api_key" name="apm_imdb_api_key" 
                                               value="<?php echo esc_attr(get_option('apm_imdb_api_key')); ?>" 
                                               class="regular-text" />
                                        <p class="description">
                                            <?php _e('Optional: For additional movie information', 'autopost-movies'); ?>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="apm-section">
                            <h2><?php _e('Automation Settings', 'autopost-movies'); ?></h2>
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="apm_cron_schedule"><?php _e('Cron Schedule', 'autopost-movies'); ?></label>
                                    </th>
                                    <td>
                                        <select id="apm_cron_schedule" name="apm_cron_schedule">
                                            <option value="hourly" <?php selected(get_option('apm_cron_schedule'), 'hourly'); ?>>
                                                <?php _e('Hourly', 'autopost-movies'); ?>
                                            </option>
                                            <option value="daily" <?php selected(get_option('apm_cron_schedule'), 'daily'); ?>>
                                                <?php _e('Daily', 'autopost-movies'); ?>
                                            </option>
                                            <option value="weekly" <?php selected(get_option('apm_cron_schedule'), 'weekly'); ?>>
                                                <?php _e('Weekly', 'autopost-movies'); ?>
                                            </option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="apm_posts_per_run"><?php _e('Posts Per Run', 'autopost-movies'); ?></label>
                                    </th>
                                    <td>
                                        <input type="number" id="apm_posts_per_run" name="apm_posts_per_run" 
                                               value="<?php echo esc_attr(get_option('apm_posts_per_run', 5)); ?>" 
                                               min="1" max="20" />
                                        <p class="description">
                                            <?php _e('Number of posts to create per automated run', 'autopost-movies'); ?>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="apm-section">
                            <h2><?php _e('Content Sources', 'autopost-movies'); ?></h2>
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('Data Sources', 'autopost-movies'); ?></th>
                                    <td>
                                        <fieldset>
                                            <label>
                                                <input type="checkbox" name="apm_tmdb_plot_enabled" value="1" 
                                                       <?php checked(get_option('apm_tmdb_plot_enabled'), 1); ?> />
                                                <?php _e('TMDB Plot', 'autopost-movies'); ?>
                                            </label><br />
                                            <label>
                                                <input type="checkbox" name="apm_wikipedia_plot_enabled" value="1" 
                                                       <?php checked(get_option('apm_wikipedia_plot_enabled'), 1); ?> />
                                                <?php _e('Wikipedia First Paragraph', 'autopost-movies'); ?>
                                            </label><br />
                                            <label>
                                                <input type="checkbox" name="apm_imdb_plot_enabled" value="1" 
                                                       <?php checked(get_option('apm_imdb_plot_enabled'), 1); ?> />
                                                <?php _e('IMDb Plot and Info', 'autopost-movies'); ?>
                                            </label>
                                        </fieldset>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="apm_content_order"><?php _e('Content Order', 'autopost-movies'); ?></label>
                                    </th>
                                    <td>
                                        <select id="apm_content_order" name="apm_content_order">
                                            <option value="plot_first" <?php selected(get_option('apm_content_order'), 'plot_first'); ?>>
                                                <?php _e('Plot First', 'autopost-movies'); ?>
                                            </option>
                                            <option value="info_first" <?php selected(get_option('apm_content_order'), 'info_first'); ?>>
                                                <?php _e('Info First', 'autopost-movies'); ?>
                                            </option>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="apm-section">
                            <h2><?php _e('Post Settings', 'autopost-movies'); ?></h2>
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="apm_post_status"><?php _e('Post Status', 'autopost-movies'); ?></label>
                                    </th>
                                    <td>
                                        <select id="apm_post_status" name="apm_post_status">
                                            <option value="publish" <?php selected(get_option('apm_post_status'), 'publish'); ?>>
                                                <?php _e('Published', 'autopost-movies'); ?>
                                            </option>
                                            <option value="draft" <?php selected(get_option('apm_post_status'), 'draft'); ?>>
                                                <?php _e('Draft', 'autopost-movies'); ?>
                                            </option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="apm_post_category"><?php _e('Default Category', 'autopost-movies'); ?></label>
                                    </th>
                                    <td>
                                        <?php
                                        wp_dropdown_categories(array(
                                            'name' => 'apm_post_category',
                                            'id' => 'apm_post_category',
                                            'selected' => get_option('apm_post_category'),
                                            'show_option_none' => __('Select Category', 'autopost-movies'),
                                            'option_none_value' => ''
                                        ));
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Featured Image', 'autopost-movies'); ?></th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="apm_featured_image_enabled" value="1" 
                                                   <?php checked(get_option('apm_featured_image_enabled'), 1); ?> />
                                            <?php _e('Enable Featured Image from URL (requires FIFU plugin)', 'autopost-movies'); ?>
                                        </label>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        
                        <?php submit_button(); ?>
                    </form>
                </div>
                
                <div class="apm-sidebar">
                    <div class="apm-sidebar-section">
                        <h3><?php _e('Manual Actions', 'autopost-movies'); ?></h3>
                        <p>
                            <button type="button" id="apm-manual-sync" class="button button-secondary">
                                <?php _e('Run Manual Sync', 'autopost-movies'); ?>
                            </button>
                        </p>
                        <p class="description">
                            <?php _e('Manually fetch and create posts for upcoming movies/TV series', 'autopost-movies'); ?>
                        </p>
                        
                        <div id="apm-sync-status" style="display: none;"></div>
                    </div>
                    
                    <div class="apm-sidebar-section">
                        <h3><?php _e('Activity Log', 'autopost-movies'); ?></h3>
                        <p>
                            <button type="button" id="apm-view-logs" class="button button-secondary">
                                <?php _e('View Logs', 'autopost-movies'); ?>
                            </button>
                            <button type="button" id="apm-clear-logs" class="button">
                                <?php _e('Clear Logs', 'autopost-movies'); ?>
                            </button>
                        </p>
                        
                        <div id="apm-logs-container" style="display: none;">
                            <div id="apm-logs-content"></div>
                        </div>
                    </div>
                    
                    <div class="apm-sidebar-section">
                        <h3><?php _e('Statistics', 'autopost-movies'); ?></h3>
                        <?php $this->display_stats(); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Save settings
     */
    private function save_settings() {
        if (!wp_verify_nonce($_POST['apm_nonce'], 'apm_settings')) {
            wp_die(__('Security check failed', 'autopost-movies'));
        }
        
        $settings = array(
            'apm_tmdb_api_key',
            'apm_youtube_api_key',
            'apm_imdb_api_key',
            'apm_cron_schedule',
            'apm_posts_per_run',
            'apm_content_order',
            'apm_post_status',
            'apm_post_category'
        );
        
        foreach ($settings as $setting) {
            if (isset($_POST[$setting])) {
                update_option($setting, sanitize_text_field($_POST[$setting]));
            }
        }
        
        // Handle checkboxes
        $checkboxes = array(
            'apm_tmdb_plot_enabled',
            'apm_wikipedia_plot_enabled', 
            'apm_imdb_plot_enabled',
            'apm_featured_image_enabled'
        );
        
        foreach ($checkboxes as $checkbox) {
            update_option($checkbox, isset($_POST[$checkbox]) ? 1 : 0);
        }
        
        // Update cron schedule if changed
        $old_schedule = get_option('apm_cron_schedule');
        $new_schedule = sanitize_text_field($_POST['apm_cron_schedule']);
        
        if ($old_schedule !== $new_schedule) {
            wp_clear_scheduled_hook('apm_fetch_movies');
            wp_schedule_event(time(), $new_schedule, 'apm_fetch_movies');
        }
        
        echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', 'autopost-movies') . '</p></div>';
    }
    
    /**
     * Display statistics
     */
    private function display_stats() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'apm_posts';
        $total_posts = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $recent_posts = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        
        ?>
        <p><strong><?php _e('Total Posts Created:', 'autopost-movies'); ?></strong> <?php echo intval($total_posts); ?></p>
        <p><strong><?php _e('Posts This Week:', 'autopost-movies'); ?></strong> <?php echo intval($recent_posts); ?></p>
        <p><strong><?php _e('Next Scheduled Run:', 'autopost-movies'); ?></strong><br>
        <?php 
        $next_run = wp_next_scheduled('apm_fetch_movies');
        echo $next_run ? date('Y-m-d H:i:s', $next_run) : __('Not scheduled', 'autopost-movies');
        ?>
        </p>
        <?php
    }
    
    /**
     * AJAX manual sync
     */
    public function ajax_manual_sync() {
        check_ajax_referer('apm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'autopost-movies'));
        }
        
        $post_creator = new APM_Post_Creator();
        $result = $post_creator->fetch_and_create_posts();
        
        wp_send_json_success($result);
    }
    
    /**
     * AJAX get logs
     */
    public function ajax_get_logs() {
        check_ajax_referer('apm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'autopost-movies'));
        }
        
        $logger = new APM_Logger();
        $logs = $logger->get_logs(50);
        
        wp_send_json_success($logs);
    }
    
    /**
     * AJAX clear logs
     */
    public function ajax_clear_logs() {
        check_ajax_referer('apm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'autopost-movies'));
        }
        
        $logger = new APM_Logger();
        $logger->clear_logs();
        
        wp_send_json_success(array('message' => __('Logs cleared successfully', 'autopost-movies')));
    }
}