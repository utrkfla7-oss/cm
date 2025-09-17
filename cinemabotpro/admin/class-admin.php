<?php
/**
 * CinemaBot Pro Admin Dashboard
 * 
 * Provides comprehensive admin interface for managing the chatbot,
 * analytics, user interactions, and system settings.
 */

class CinemaBotPro_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menus'));
        add_action('admin_init', array($this, 'init_admin'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_cinemabotpro_dashboard_stats', array($this, 'handle_dashboard_stats_ajax'));
        add_action('wp_ajax_cinemabotpro_save_settings', array($this, 'handle_save_settings_ajax'));
        add_action('wp_ajax_cinemabotpro_test_api', array($this, 'handle_test_api_ajax'));
        add_action('wp_ajax_cinemabotpro_export_data', array($this, 'handle_export_data_ajax'));
    }
    
    /**
     * Initialize admin interface
     */
    public function init_admin() {
        // Register settings
        $this->register_settings();
        
        // Add custom meta boxes for dashboard
        add_action('add_meta_boxes', array($this, 'add_dashboard_meta_boxes'));
    }
    
    /**
     * Add admin menus
     */
    public function add_admin_menus() {
        // Main menu
        add_menu_page(
            __('CinemaBot Pro', 'cinemabotpro'),
            __('CinemaBot Pro', 'cinemabotpro'),
            'manage_options',
            'cinemabotpro-admin',
            array($this, 'dashboard_page'),
            'dashicons-format-chat',
            25
        );
        
        // Dashboard submenu
        add_submenu_page(
            'cinemabotpro-admin',
            __('Dashboard', 'cinemabotpro'),
            __('Dashboard', 'cinemabotpro'),
            'manage_options',
            'cinemabotpro-admin',
            array($this, 'dashboard_page')
        );
        
        // Analytics submenu
        add_submenu_page(
            'cinemabotpro-admin',
            __('Analytics', 'cinemabotpro'),
            __('Analytics', 'cinemabotpro'),
            'manage_options',
            'cinemabotpro-analytics',
            array($this, 'analytics_page')
        );
        
        // Chat Management submenu
        add_submenu_page(
            'cinemabotpro-admin',
            __('Chat Management', 'cinemabotpro'),
            __('Chat Management', 'cinemabotpro'),
            'manage_options',
            'cinemabotpro-chat',
            array($this, 'chat_management_page')
        );
        
        // Avatar Management submenu
        add_submenu_page(
            'cinemabotpro-admin',
            __('Avatar Management', 'cinemabotpro'),
            __('Avatar Management', 'cinemabotpro'),
            'manage_options',
            'cinemabotpro-avatars',
            array($this, 'avatar_management_page')
        );
        
        // Content Management submenu
        add_submenu_page(
            'cinemabotpro-admin',
            __('Content Management', 'cinemabotpro'),
            __('Content Management', 'cinemabotpro'),
            'manage_options',
            'cinemabotpro-content',
            array($this, 'content_management_page')
        );
        
        // Settings submenu
        add_submenu_page(
            'cinemabotpro-admin',
            __('Settings', 'cinemabotpro'),
            __('Settings', 'cinemabotpro'),
            'manage_options',
            'cinemabotpro-settings',
            array($this, 'settings_page')
        );
        
        // Security submenu
        add_submenu_page(
            'cinemabotpro-admin',
            __('Security', 'cinemabotpro'),
            __('Security', 'cinemabotpro'),
            'manage_options',
            'cinemabotpro-security',
            array($this, 'security_page')
        );
        
        // Tools submenu
        add_submenu_page(
            'cinemabotpro-admin',
            __('Tools', 'cinemabotpro'),
            __('Tools', 'cinemabotpro'),
            'manage_options',
            'cinemabotpro-tools',
            array($this, 'tools_page')
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'cinemabotpro') === false) {
            return;
        }
        
        // Enqueue CSS
        wp_enqueue_style(
            'cinemabotpro-admin',
            CINEMABOTPRO_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            CINEMABOTPRO_VERSION
        );
        
        // Enqueue JavaScript
        wp_enqueue_script(
            'cinemabotpro-admin',
            CINEMABOTPRO_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-api'),
            CINEMABOTPRO_VERSION,
            true
        );
        
        // Chart.js for analytics
        wp_enqueue_script(
            'chart-js',
            'https://cdn.jsdelivr.net/npm/chart.js',
            array(),
            '3.9.1',
            true
        );
        
        // Localize script
        wp_localize_script('cinemabotpro-admin', 'cinemabotpro_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cinemabotpro_admin_nonce'),
            'rest_url' => rest_url('cinemabotpro/v1/'),
            'rest_nonce' => wp_create_nonce('wp_rest'),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this?', 'cinemabotpro'),
                'loading' => __('Loading...', 'cinemabotpro'),
                'error' => __('An error occurred', 'cinemabotpro'),
                'success' => __('Operation completed successfully', 'cinemabotpro')
            )
        ));
    }
    
    /**
     * Register admin settings
     */
    private function register_settings() {
        // General settings
        register_setting('cinemabotpro_general', 'cinemabotpro_enabled');
        register_setting('cinemabotpro_general', 'cinemabotpro_default_language');
        register_setting('cinemabotpro_general', 'cinemabotpro_supported_languages');
        register_setting('cinemabotpro_general', 'cinemabotpro_avatar_rotation');
        register_setting('cinemabotpro_general', 'cinemabotpro_auto_lang_detect');
        
        // AI settings
        register_setting('cinemabotpro_ai', 'cinemabotpro_openai_api_key');
        register_setting('cinemabotpro_ai', 'cinemabotpro_ai_model');
        register_setting('cinemabotpro_ai', 'cinemabotpro_max_tokens');
        register_setting('cinemabotpro_ai', 'cinemabotpro_temperature');
        
        // Content API settings
        register_setting('cinemabotpro_content', 'cinemabotpro_tmdb_api_key');
        register_setting('cinemabotpro_content', 'cinemabotpro_omdb_api_key');
        register_setting('cinemabotpro_content', 'cinemabotpro_content_crawling');
        register_setting('cinemabotpro_content', 'cinemabotpro_crawl_interval');
        
        // Security settings
        register_setting('cinemabotpro_security', 'cinemabotpro_security_settings');
        
        // Analytics settings
        register_setting('cinemabotpro_analytics', 'cinemabotpro_analytics_settings');
    }
    
    /**
     * Dashboard page
     */
    public function dashboard_page() {
        $analytics = new CinemaBotPro_Analytics();
        $dashboard_stats = $analytics->get_dashboard_analytics('7days');
        
        $security = new CinemaBotPro_Security();
        $security_stats = $security->get_security_statistics();
        
        $content_crawler = new CinemaBotPro_Content_Crawler();
        $crawl_stats = $content_crawler->get_crawl_statistics();
        
        ?>
        <div class="wrap cinemabotpro-admin">
            <h1><?php _e('CinemaBot Pro Dashboard', 'cinemabotpro'); ?></h1>
            
            <div class="cinemabotpro-dashboard">
                <!-- Quick Stats -->
                <div class="cinemabotpro-stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">💬</div>
                        <div class="stat-content">
                            <h3><?php echo number_format($dashboard_stats['total_interactions']); ?></h3>
                            <p><?php _e('Chat Interactions (7 days)', 'cinemabotpro'); ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">👥</div>
                        <div class="stat-content">
                            <h3><?php echo number_format($dashboard_stats['total_users']); ?></h3>
                            <p><?php _e('Active Users', 'cinemabotpro'); ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">🔍</div>
                        <div class="stat-content">
                            <h3><?php echo number_format($dashboard_stats['total_searches']); ?></h3>
                            <p><?php _e('Content Searches', 'cinemabotpro'); ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">⚡</div>
                        <div class="stat-content">
                            <h3><?php echo $dashboard_stats['avg_response_time']; ?>s</h3>
                            <p><?php _e('Avg Response Time', 'cinemabotpro'); ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Charts Section -->
                <div class="cinemabotpro-charts">
                    <div class="chart-container">
                        <h3><?php _e('Interaction Timeline', 'cinemabotpro'); ?></h3>
                        <canvas id="interactionChart"></canvas>
                    </div>
                    
                    <div class="chart-container">
                        <h3><?php _e('Language Distribution', 'cinemabotpro'); ?></h3>
                        <canvas id="languageChart"></canvas>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="cinemabotpro-recent-activity">
                    <h3><?php _e('Recent Activity', 'cinemabotpro'); ?></h3>
                    <div id="recent-activity-list">
                        <?php echo $this->get_recent_activity_html(); ?>
                    </div>
                </div>
                
                <!-- System Status -->
                <div class="cinemabotpro-system-status">
                    <h3><?php _e('System Status', 'cinemabotpro'); ?></h3>
                    <div class="status-grid">
                        <div class="status-item">
                            <span class="status-indicator <?php echo $this->get_ai_status_class(); ?>"></span>
                            <span><?php _e('AI Service', 'cinemabotpro'); ?></span>
                        </div>
                        <div class="status-item">
                            <span class="status-indicator <?php echo $crawl_stats['api_status']['tmdb'] ? 'green' : 'red'; ?>"></span>
                            <span><?php _e('TMDB API', 'cinemabotpro'); ?></span>
                        </div>
                        <div class="status-item">
                            <span class="status-indicator <?php echo $crawl_stats['api_status']['omdb'] ? 'green' : 'red'; ?>"></span>
                            <span><?php _e('OMDB API', 'cinemabotpro'); ?></span>
                        </div>
                        <div class="status-item">
                            <span class="status-indicator <?php echo $security_stats['total_events'] < 100 ? 'green' : 'yellow'; ?>"></span>
                            <span><?php _e('Security', 'cinemabotpro'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Initialize charts
            const interactionData = <?php echo wp_json_encode($dashboard_stats['interaction_timeline']); ?>;
            const languageData = <?php echo wp_json_encode($dashboard_stats['top_languages']); ?>;
            
            initializeCharts(interactionData, languageData);
            
            // Auto-refresh dashboard every 30 seconds
            setInterval(function() {
                refreshDashboard();
            }, 30000);
        });
        </script>
        <?php
    }
    
    /**
     * Analytics page
     */
    public function analytics_page() {
        ?>
        <div class="wrap cinemabotpro-admin">
            <h1><?php _e('Analytics', 'cinemabotpro'); ?></h1>
            
            <div class="cinemabotpro-analytics">
                <!-- Period Selector -->
                <div class="period-selector">
                    <label for="analytics-period"><?php _e('Time Period:', 'cinemabotpro'); ?></label>
                    <select id="analytics-period">
                        <option value="24hours"><?php _e('Last 24 Hours', 'cinemabotpro'); ?></option>
                        <option value="7days" selected><?php _e('Last 7 Days', 'cinemabotpro'); ?></option>
                        <option value="30days"><?php _e('Last 30 Days', 'cinemabotpro'); ?></option>
                        <option value="90days"><?php _e('Last 90 Days', 'cinemabotpro'); ?></option>
                    </select>
                    <button type="button" class="button" id="refresh-analytics"><?php _e('Refresh', 'cinemabotpro'); ?></button>
                    <button type="button" class="button button-secondary" id="export-analytics"><?php _e('Export', 'cinemabotpro'); ?></button>
                </div>
                
                <!-- Analytics Content -->
                <div id="analytics-content">
                    <?php echo $this->get_analytics_content(); ?>
                </div>
                
                <!-- Export Modal -->
                <div id="export-modal" class="cinemabotpro-modal" style="display: none;">
                    <div class="modal-content">
                        <h3><?php _e('Export Analytics', 'cinemabotpro'); ?></h3>
                        <form id="export-form">
                            <p>
                                <label><?php _e('Format:', 'cinemabotpro'); ?></label>
                                <select name="format">
                                    <option value="csv">CSV</option>
                                    <option value="json">JSON</option>
                                    <option value="pdf">PDF</option>
                                </select>
                            </p>
                            <p>
                                <label><?php _e('Period:', 'cinemabotpro'); ?></label>
                                <select name="period">
                                    <option value="7days"><?php _e('Last 7 Days', 'cinemabotpro'); ?></option>
                                    <option value="30days"><?php _e('Last 30 Days', 'cinemabotpro'); ?></option>
                                    <option value="90days"><?php _e('Last 90 Days', 'cinemabotpro'); ?></option>
                                </select>
                            </p>
                            <div class="modal-actions">
                                <button type="submit" class="button button-primary"><?php _e('Export', 'cinemabotpro'); ?></button>
                                <button type="button" class="button modal-close"><?php _e('Cancel', 'cinemabotpro'); ?></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Chat Management page
     */
    public function chat_management_page() {
        ?>
        <div class="wrap cinemabotpro-admin">
            <h1><?php _e('Chat Management', 'cinemabotpro'); ?></h1>
            
            <div class="cinemabotpro-chat-management">
                <!-- Live Chat Monitor -->
                <div class="chat-monitor">
                    <h3><?php _e('Live Chat Monitor', 'cinemabotpro'); ?></h3>
                    <div id="live-chat-feed">
                        <?php echo $this->get_live_chat_feed(); ?>
                    </div>
                </div>
                
                <!-- Chat History -->
                <div class="chat-history">
                    <h3><?php _e('Chat History', 'cinemabotpro'); ?></h3>
                    <div class="chat-filters">
                        <input type="text" id="chat-search" placeholder="<?php _e('Search conversations...', 'cinemabotpro'); ?>" />
                        <select id="language-filter">
                            <option value=""><?php _e('All Languages', 'cinemabotpro'); ?></option>
                            <option value="en"><?php _e('English', 'cinemabotpro'); ?></option>
                            <option value="bn"><?php _e('Bengali', 'cinemabotpro'); ?></option>
                            <option value="hi"><?php _e('Hindi', 'cinemabotpro'); ?></option>
                            <option value="banglish"><?php _e('Banglish', 'cinemabotpro'); ?></option>
                        </select>
                        <input type="date" id="date-filter" />
                    </div>
                    <div id="chat-history-list">
                        <?php echo $this->get_chat_history_html(); ?>
                    </div>
                </div>
                
                <!-- AI Training -->
                <div class="ai-training">
                    <h3><?php _e('AI Training', 'cinemabotpro'); ?></h3>
                    <div class="training-section">
                        <h4><?php _e('Response Improvement', 'cinemabotpro'); ?></h4>
                        <form id="response-training-form">
                            <textarea name="user_message" placeholder="<?php _e('User message...', 'cinemabotpro'); ?>" rows="3"></textarea>
                            <textarea name="improved_response" placeholder="<?php _e('Improved response...', 'cinemabotpro'); ?>" rows="4"></textarea>
                            <select name="language">
                                <option value="en"><?php _e('English', 'cinemabotpro'); ?></option>
                                <option value="bn"><?php _e('Bengali', 'cinemabotpro'); ?></option>
                                <option value="hi"><?php _e('Hindi', 'cinemabotpro'); ?></option>
                                <option value="banglish"><?php _e('Banglish', 'cinemabotpro'); ?></option>
                            </select>
                            <button type="submit" class="button button-primary"><?php _e('Add Training Data', 'cinemabotpro'); ?></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Avatar Management page
     */
    public function avatar_management_page() {
        $avatar_system = new CinemaBotPro_Avatar_System();
        $avatars = $avatar_system->get_available_avatars_list();
        
        ?>
        <div class="wrap cinemabotpro-admin">
            <h1><?php _e('Avatar Management', 'cinemabotpro'); ?></h1>
            
            <div class="cinemabotpro-avatar-management">
                <!-- Avatar Gallery -->
                <div class="avatar-gallery">
                    <h3><?php _e('Avatar Gallery', 'cinemabotpro'); ?></h3>
                    <div class="avatar-grid">
                        <?php foreach ($avatars as $avatar): ?>
                        <div class="avatar-item" data-avatar-id="<?php echo esc_attr($avatar['id']); ?>">
                            <img src="<?php echo esc_url($avatar['image_url']); ?>" alt="<?php echo esc_attr($avatar['name']); ?>" />
                            <h4><?php echo esc_html($avatar['name']); ?></h4>
                            <p><?php echo esc_html($avatar['description']); ?></p>
                            <div class="avatar-actions">
                                <button type="button" class="button button-small set-default-avatar"><?php _e('Set Default', 'cinemabotpro'); ?></button>
                                <button type="button" class="button button-small edit-avatar"><?php _e('Edit', 'cinemabotpro'); ?></button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Avatar Settings -->
                <div class="avatar-settings">
                    <h3><?php _e('Avatar Settings', 'cinemabotpro'); ?></h3>
                    <form id="avatar-settings-form">
                        <table class="form-table">
                            <tr>
                                <th><?php _e('Auto Rotation', 'cinemabotpro'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="auto_rotation" value="1" <?php checked(get_option('cinemabotpro_auto_avatar_rotation', 1)); ?> />
                                        <?php _e('Enable automatic avatar rotation', 'cinemabotpro'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Rotation Interval', 'cinemabotpro'); ?></th>
                                <td>
                                    <input type="number" name="rotation_interval" value="<?php echo esc_attr(get_option('cinemabotpro_avatar_rotation', 30)); ?>" min="10" max="300" />
                                    <span class="description"><?php _e('seconds', 'cinemabotpro'); ?></span>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Animation Speed', 'cinemabotpro'); ?></th>
                                <td>
                                    <select name="animation_speed">
                                        <option value="slow" <?php selected(get_option('cinemabotpro_animation_speed', 'medium'), 'slow'); ?>><?php _e('Slow', 'cinemabotpro'); ?></option>
                                        <option value="medium" <?php selected(get_option('cinemabotpro_animation_speed', 'medium'), 'medium'); ?>><?php _e('Medium', 'cinemabotpro'); ?></option>
                                        <option value="fast" <?php selected(get_option('cinemabotpro_animation_speed', 'medium'), 'fast'); ?>><?php _e('Fast', 'cinemabotpro'); ?></option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        <p class="submit">
                            <button type="submit" class="button button-primary"><?php _e('Save Settings', 'cinemabotpro'); ?></button>
                        </p>
                    </form>
                </div>
                
                <!-- Upload New Avatar -->
                <div class="avatar-upload">
                    <h3><?php _e('Upload New Avatar', 'cinemabotpro'); ?></h3>
                    <form id="avatar-upload-form" enctype="multipart/form-data">
                        <table class="form-table">
                            <tr>
                                <th><?php _e('Avatar Name', 'cinemabotpro'); ?></th>
                                <td><input type="text" name="avatar_name" required /></td>
                            </tr>
                            <tr>
                                <th><?php _e('Avatar Image', 'cinemabotpro'); ?></th>
                                <td><input type="file" name="avatar_image" accept="image/*" required /></td>
                            </tr>
                            <tr>
                                <th><?php _e('Description', 'cinemabotpro'); ?></th>
                                <td><textarea name="avatar_description" rows="3"></textarea></td>
                            </tr>
                            <tr>
                                <th><?php _e('Context Tags', 'cinemabotpro'); ?></th>
                                <td>
                                    <input type="text" name="context_tags" placeholder="action, comedy, drama" />
                                    <span class="description"><?php _e('Comma-separated tags', 'cinemabotpro'); ?></span>
                                </td>
                            </tr>
                        </table>
                        <p class="submit">
                            <button type="submit" class="button button-primary"><?php _e('Upload Avatar', 'cinemabotpro'); ?></button>
                        </p>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Content Management page
     */
    public function content_management_page() {
        $content_crawler = new CinemaBotPro_Content_Crawler();
        $crawl_stats = $content_crawler->get_crawl_statistics();
        
        ?>
        <div class="wrap cinemabotpro-admin">
            <h1><?php _e('Content Management', 'cinemabotpro'); ?></h1>
            
            <div class="cinemabotpro-content-management">
                <!-- Content Statistics -->
                <div class="content-stats">
                    <h3><?php _e('Content Statistics', 'cinemabotpro'); ?></h3>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <strong><?php echo number_format($crawl_stats['total_movies']); ?></strong>
                            <span><?php _e('Movies', 'cinemabotpro'); ?></span>
                        </div>
                        <div class="stat-item">
                            <strong><?php echo number_format($crawl_stats['total_tv_shows']); ?></strong>
                            <span><?php _e('TV Shows', 'cinemabotpro'); ?></span>
                        </div>
                        <div class="stat-item">
                            <strong><?php echo esc_html($crawl_stats['last_crawl']); ?></strong>
                            <span><?php _e('Last Crawl', 'cinemabotpro'); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Content Crawling -->
                <div class="content-crawling">
                    <h3><?php _e('Content Crawling', 'cinemabotpro'); ?></h3>
                    <form id="crawling-settings-form">
                        <table class="form-table">
                            <tr>
                                <th><?php _e('Enable Crawling', 'cinemabotpro'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="crawl_enabled" value="1" <?php checked($crawl_stats['crawl_enabled']); ?> />
                                        <?php _e('Enable automatic content crawling', 'cinemabotpro'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('TMDB API Key', 'cinemabotpro'); ?></th>
                                <td>
                                    <input type="text" name="tmdb_api_key" value="<?php echo esc_attr(get_option('cinemabotpro_tmdb_api_key', '')); ?>" class="regular-text" />
                                    <span class="status-indicator <?php echo $crawl_stats['api_status']['tmdb'] ? 'green' : 'red'; ?>"></span>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('OMDB API Key', 'cinemabotpro'); ?></th>
                                <td>
                                    <input type="text" name="omdb_api_key" value="<?php echo esc_attr(get_option('cinemabotpro_omdb_api_key', '')); ?>" class="regular-text" />
                                    <span class="status-indicator <?php echo $crawl_stats['api_status']['omdb'] ? 'green' : 'red'; ?>"></span>
                                </td>
                            </tr>
                        </table>
                        <p class="submit">
                            <button type="submit" class="button button-primary"><?php _e('Save Settings', 'cinemabotpro'); ?></button>
                            <button type="button" id="manual-crawl" class="button button-secondary"><?php _e('Run Manual Crawl', 'cinemabotpro'); ?></button>
                            <button type="button" id="test-apis" class="button button-secondary"><?php _e('Test APIs', 'cinemabotpro'); ?></button>
                        </p>
                    </form>
                </div>
                
                <!-- Content Search -->
                <div class="content-search">
                    <h3><?php _e('Content Search & Management', 'cinemabotpro'); ?></h3>
                    <div class="search-form">
                        <input type="text" id="content-search" placeholder="<?php _e('Search movies and TV shows...', 'cinemabotpro'); ?>" />
                        <select id="content-type-filter">
                            <option value="all"><?php _e('All Types', 'cinemabotpro'); ?></option>
                            <option value="movie"><?php _e('Movies', 'cinemabotpro'); ?></option>
                            <option value="tv_show"><?php _e('TV Shows', 'cinemabotpro'); ?></option>
                        </select>
                        <button type="button" id="search-content" class="button"><?php _e('Search', 'cinemabotpro'); ?></button>
                    </div>
                    <div id="content-search-results"></div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        ?>
        <div class="wrap cinemabotpro-admin">
            <h1><?php _e('Settings', 'cinemabotpro'); ?></h1>
            
            <div class="cinemabotpro-settings">
                <!-- Settings Tabs -->
                <div class="settings-tabs">
                    <button type="button" class="tab-button active" data-tab="general"><?php _e('General', 'cinemabotpro'); ?></button>
                    <button type="button" class="tab-button" data-tab="ai"><?php _e('AI & Language', 'cinemabotpro'); ?></button>
                    <button type="button" class="tab-button" data-tab="performance"><?php _e('Performance', 'cinemabotpro'); ?></button>
                    <button type="button" class="tab-button" data-tab="privacy"><?php _e('Privacy & GDPR', 'cinemabotpro'); ?></button>
                </div>
                
                <form id="settings-form" method="post" action="options.php">
                    <!-- General Settings Tab -->
                    <div class="tab-content active" id="general-tab">
                        <h3><?php _e('General Settings', 'cinemabotpro'); ?></h3>
                        <?php settings_fields('cinemabotpro_general'); ?>
                        <table class="form-table">
                            <tr>
                                <th><?php _e('Enable CinemaBot Pro', 'cinemabotpro'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="cinemabotpro_enabled" value="1" <?php checked(get_option('cinemabotpro_enabled', 1)); ?> />
                                        <?php _e('Enable the chatbot system', 'cinemabotpro'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Default Language', 'cinemabotpro'); ?></th>
                                <td>
                                    <select name="cinemabotpro_default_language">
                                        <option value="en" <?php selected(get_option('cinemabotpro_default_language', 'en'), 'en'); ?>><?php _e('English', 'cinemabotpro'); ?></option>
                                        <option value="bn" <?php selected(get_option('cinemabotpro_default_language', 'en'), 'bn'); ?>><?php _e('Bengali', 'cinemabotpro'); ?></option>
                                        <option value="hi" <?php selected(get_option('cinemabotpro_default_language', 'en'), 'hi'); ?>><?php _e('Hindi', 'cinemabotpro'); ?></option>
                                        <option value="banglish" <?php selected(get_option('cinemabotpro_default_language', 'en'), 'banglish'); ?>><?php _e('Banglish', 'cinemabotpro'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Auto Language Detection', 'cinemabotpro'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="cinemabotpro_auto_lang_detect" value="1" <?php checked(get_option('cinemabotpro_auto_lang_detect', 1)); ?> />
                                        <?php _e('Automatically detect user language', 'cinemabotpro'); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- AI Settings Tab -->
                    <div class="tab-content" id="ai-tab">
                        <h3><?php _e('AI & Language Settings', 'cinemabotpro'); ?></h3>
                        <?php settings_fields('cinemabotpro_ai'); ?>
                        <table class="form-table">
                            <tr>
                                <th><?php _e('OpenAI API Key', 'cinemabotpro'); ?></th>
                                <td>
                                    <input type="password" name="cinemabotpro_openai_api_key" value="<?php echo esc_attr(get_option('cinemabotpro_openai_api_key', '')); ?>" class="regular-text" />
                                    <p class="description"><?php _e('Enter your OpenAI API key for advanced AI responses', 'cinemabotpro'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('AI Model', 'cinemabotpro'); ?></th>
                                <td>
                                    <select name="cinemabotpro_ai_model">
                                        <option value="gpt-3.5-turbo" <?php selected(get_option('cinemabotpro_ai_model', 'gpt-3.5-turbo'), 'gpt-3.5-turbo'); ?>>GPT-3.5 Turbo</option>
                                        <option value="gpt-4" <?php selected(get_option('cinemabotpro_ai_model', 'gpt-3.5-turbo'), 'gpt-4'); ?>>GPT-4</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Max Response Length', 'cinemabotpro'); ?></th>
                                <td>
                                    <input type="number" name="cinemabotpro_max_tokens" value="<?php echo esc_attr(get_option('cinemabotpro_max_tokens', 1000)); ?>" min="100" max="4000" />
                                    <p class="description"><?php _e('Maximum tokens for AI responses', 'cinemabotpro'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Performance Settings Tab -->
                    <div class="tab-content" id="performance-tab">
                        <h3><?php _e('Performance Settings', 'cinemabotpro'); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th><?php _e('Cache Duration', 'cinemabotpro'); ?></th>
                                <td>
                                    <input type="number" name="cinemabotpro_cache_duration" value="<?php echo esc_attr(get_option('cinemabotpro_cache_duration', 3600)); ?>" min="300" max="86400" />
                                    <span class="description"><?php _e('seconds', 'cinemabotpro'); ?></span>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Rate Limiting', 'cinemabotpro'); ?></th>
                                <td>
                                    <input type="number" name="cinemabotpro_rate_limit" value="<?php echo esc_attr(get_option('cinemabotpro_rate_limit', 30)); ?>" min="5" max="100" />
                                    <span class="description"><?php _e('requests per minute', 'cinemabotpro'); ?></span>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Privacy Settings Tab -->
                    <div class="tab-content" id="privacy-tab">
                        <h3><?php _e('Privacy & GDPR Settings', 'cinemabotpro'); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th><?php _e('GDPR Compliance', 'cinemabotpro'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="cinemabotpro_gdpr_enabled" value="1" <?php checked(get_option('cinemabotpro_gdpr_enabled', 1)); ?> />
                                        <?php _e('Enable GDPR compliance features', 'cinemabotpro'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Data Retention', 'cinemabotpro'); ?></th>
                                <td>
                                    <input type="number" name="cinemabotpro_data_retention_days" value="<?php echo esc_attr(get_option('cinemabotpro_data_retention_days', 365)); ?>" min="30" max="2555" />
                                    <span class="description"><?php _e('days', 'cinemabotpro'); ?></span>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('User Memory', 'cinemabotpro'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="cinemabotpro_memory_enabled" value="1" <?php checked(get_option('cinemabotpro_memory_enabled', 1)); ?> />
                                        <?php _e('Enable user memory and preferences', 'cinemabotpro'); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <p class="submit">
                        <button type="submit" class="button button-primary"><?php _e('Save All Settings', 'cinemabotpro'); ?></button>
                        <button type="button" id="reset-settings" class="button button-secondary"><?php _e('Reset to Defaults', 'cinemabotpro'); ?></button>
                    </p>
                </form>
            </div>
        </div>
        <?php
    }
    
    /**
     * Security page
     */
    public function security_page() {
        $security = new CinemaBotPro_Security();
        $security_stats = $security->get_security_statistics();
        $security_settings = $security->get_security_settings();
        
        ?>
        <div class="wrap cinemabotpro-admin">
            <h1><?php _e('Security', 'cinemabotpro'); ?></h1>
            
            <div class="cinemabotpro-security">
                <!-- Security Status -->
                <div class="security-status">
                    <h3><?php _e('Security Status', 'cinemabotpro'); ?></h3>
                    <div class="security-overview">
                        <div class="security-score">
                            <div class="score-circle">
                                <span class="score">85</span>
                                <span class="score-label"><?php _e('Security Score', 'cinemabotpro'); ?></span>
                            </div>
                        </div>
                        <div class="security-stats">
                            <div class="stat-item">
                                <strong><?php echo number_format($security_stats['blocked_attempts']); ?></strong>
                                <span><?php _e('Blocked Attempts', 'cinemabotpro'); ?></span>
                            </div>
                            <div class="stat-item">
                                <strong><?php echo number_format($security_stats['failed_logins']); ?></strong>
                                <span><?php _e('Failed Logins', 'cinemabotpro'); ?></span>
                            </div>
                            <div class="stat-item">
                                <strong><?php echo number_format($security_stats['xss_attempts']); ?></strong>
                                <span><?php _e('XSS Attempts', 'cinemabotpro'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Security Settings -->
                <div class="security-settings">
                    <h3><?php _e('Security Settings', 'cinemabotpro'); ?></h3>
                    <form id="security-settings-form">
                        <table class="form-table">
                            <tr>
                                <th><?php _e('Rate Limiting', 'cinemabotpro'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="enable_rate_limiting" value="1" <?php checked($security_settings['enable_rate_limiting']); ?> />
                                        <?php _e('Enable rate limiting protection', 'cinemabotpro'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Brute Force Protection', 'cinemabotpro'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="enable_brute_force_protection" value="1" <?php checked($security_settings['enable_brute_force_protection']); ?> />
                                        <?php _e('Enable brute force protection', 'cinemabotpro'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('XSS Protection', 'cinemabotpro'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="enable_xss_protection" value="1" <?php checked($security_settings['enable_xss_protection']); ?> />
                                        <?php _e('Enable XSS protection', 'cinemabotpro'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('SQL Injection Protection', 'cinemabotpro'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="enable_sql_injection_protection" value="1" <?php checked($security_settings['enable_sql_injection_protection']); ?> />
                                        <?php _e('Enable SQL injection protection', 'cinemabotpro'); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>
                        <p class="submit">
                            <button type="submit" class="button button-primary"><?php _e('Save Security Settings', 'cinemabotpro'); ?></button>
                            <button type="button" id="run-security-scan" class="button button-secondary"><?php _e('Run Security Scan', 'cinemabotpro'); ?></button>
                        </p>
                    </form>
                </div>
                
                <!-- Security Log -->
                <div class="security-log">
                    <h3><?php _e('Recent Security Events', 'cinemabotpro'); ?></h3>
                    <div id="security-log-list">
                        <?php echo $this->get_security_log_html(); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Tools page
     */
    public function tools_page() {
        ?>
        <div class="wrap cinemabotpro-admin">
            <h1><?php _e('Tools', 'cinemabotpro'); ?></h1>
            
            <div class="cinemabotpro-tools">
                <!-- System Information -->
                <div class="system-info">
                    <h3><?php _e('System Information', 'cinemabotpro'); ?></h3>
                    <table class="widefat">
                        <tr>
                            <td><?php _e('Plugin Version', 'cinemabotpro'); ?></td>
                            <td><?php echo CINEMABOTPRO_VERSION; ?></td>
                        </tr>
                        <tr>
                            <td><?php _e('WordPress Version', 'cinemabotpro'); ?></td>
                            <td><?php echo get_bloginfo('version'); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e('PHP Version', 'cinemabotpro'); ?></td>
                            <td><?php echo PHP_VERSION; ?></td>
                        </tr>
                        <tr>
                            <td><?php _e('Memory Limit', 'cinemabotpro'); ?></td>
                            <td><?php echo ini_get('memory_limit'); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e('Upload Max Size', 'cinemabotpro'); ?></td>
                            <td><?php echo wp_max_upload_size(); ?></td>
                        </tr>
                    </table>
                </div>
                
                <!-- Import/Export -->
                <div class="import-export">
                    <h3><?php _e('Import/Export', 'cinemabotpro'); ?></h3>
                    <div class="tool-section">
                        <h4><?php _e('Export Data', 'cinemabotpro'); ?></h4>
                        <p><?php _e('Export all plugin data including settings, chat history, and analytics.', 'cinemabotpro'); ?></p>
                        <button type="button" id="export-all-data" class="button button-primary"><?php _e('Export All Data', 'cinemabotpro'); ?></button>
                    </div>
                    
                    <div class="tool-section">
                        <h4><?php _e('Import Data', 'cinemabotpro'); ?></h4>
                        <p><?php _e('Import previously exported plugin data.', 'cinemabotpro'); ?></p>
                        <form id="import-data-form" enctype="multipart/form-data">
                            <input type="file" name="import_file" accept=".json" />
                            <button type="submit" class="button button-secondary"><?php _e('Import Data', 'cinemabotpro'); ?></button>
                        </form>
                    </div>
                </div>
                
                <!-- Maintenance -->
                <div class="maintenance">
                    <h3><?php _e('Maintenance', 'cinemabotpro'); ?></h3>
                    <div class="tool-section">
                        <h4><?php _e('Clear Cache', 'cinemabotpro'); ?></h4>
                        <p><?php _e('Clear all cached data to improve performance.', 'cinemabotpro'); ?></p>
                        <button type="button" id="clear-cache" class="button button-secondary"><?php _e('Clear Cache', 'cinemabotpro'); ?></button>
                    </div>
                    
                    <div class="tool-section">
                        <h4><?php _e('Reset Plugin', 'cinemabotpro'); ?></h4>
                        <p><?php _e('Reset all plugin settings to default values. This will not delete content data.', 'cinemabotpro'); ?></p>
                        <button type="button" id="reset-plugin" class="button button-secondary"><?php _e('Reset Plugin', 'cinemabotpro'); ?></button>
                    </div>
                    
                    <div class="tool-section">
                        <h4><?php _e('Database Cleanup', 'cinemabotpro'); ?></h4>
                        <p><?php _e('Clean up old analytics data and optimize database tables.', 'cinemabotpro'); ?></p>
                        <button type="button" id="cleanup-database" class="button button-secondary"><?php _e('Cleanup Database', 'cinemabotpro'); ?></button>
                    </div>
                </div>
                
                <!-- Debug -->
                <div class="debug">
                    <h3><?php _e('Debug Information', 'cinemabotpro'); ?></h3>
                    <div class="tool-section">
                        <h4><?php _e('System Check', 'cinemabotpro'); ?></h4>
                        <button type="button" id="run-system-check" class="button button-secondary"><?php _e('Run System Check', 'cinemabotpro'); ?></button>
                        <div id="system-check-results"></div>
                    </div>
                    
                    <div class="tool-section">
                        <h4><?php _e('Error Log', 'cinemabotpro'); ?></h4>
                        <button type="button" id="view-error-log" class="button button-secondary"><?php _e('View Error Log', 'cinemabotpro'); ?></button>
                        <div id="error-log-content"></div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Helper methods for generating HTML content
     */
    private function get_recent_activity_html() {
        // Implementation for recent activity
        return '<p>' . __('Loading recent activity...', 'cinemabotpro') . '</p>';
    }
    
    private function get_ai_status_class() {
        $ai_engine = new CinemaBotPro_AI_Engine();
        return $ai_engine->is_ai_available() ? 'green' : 'red';
    }
    
    private function get_analytics_content() {
        return '<div id="analytics-charts"></div>';
    }
    
    private function get_live_chat_feed() {
        return '<p>' . __('Loading live chat feed...', 'cinemabotpro') . '</p>';
    }
    
    private function get_chat_history_html() {
        return '<p>' . __('Loading chat history...', 'cinemabotpro') . '</p>';
    }
    
    private function get_security_log_html() {
        return '<p>' . __('Loading security log...', 'cinemabotpro') . '</p>';
    }
    
    /**
     * AJAX handlers
     */
    public function handle_dashboard_stats_ajax() {
        check_ajax_referer('cinemabotpro_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $analytics = new CinemaBotPro_Analytics();
        $period = sanitize_text_field($_POST['period'] ?? '7days');
        $stats = $analytics->get_dashboard_analytics($period);
        
        wp_send_json_success($stats);
    }
    
    public function handle_save_settings_ajax() {
        check_ajax_referer('cinemabotpro_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $settings = $_POST['settings'] ?? array();
        
        foreach ($settings as $key => $value) {
            update_option($key, sanitize_text_field($value));
        }
        
        wp_send_json_success(array('message' => __('Settings saved successfully', 'cinemabotpro')));
    }
    
    public function handle_test_api_ajax() {
        check_ajax_referer('cinemabotpro_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $api_type = sanitize_text_field($_POST['api_type']);
        $results = array();
        
        switch ($api_type) {
            case 'openai':
                $ai_engine = new CinemaBotPro_AI_Engine();
                $results['status'] = $ai_engine->is_ai_available();
                $results['message'] = $results['status'] ? 'OpenAI API is working' : 'OpenAI API key not configured or invalid';
                break;
            case 'tmdb':
                // Test TMDB API
                $results['status'] = !empty(get_option('cinemabotpro_tmdb_api_key'));
                $results['message'] = $results['status'] ? 'TMDB API key is configured' : 'TMDB API key not configured';
                break;
            case 'omdb':
                // Test OMDB API
                $results['status'] = !empty(get_option('cinemabotpro_omdb_api_key'));
                $results['message'] = $results['status'] ? 'OMDB API key is configured' : 'OMDB API key not configured';
                break;
        }
        
        wp_send_json_success($results);
    }
    
    public function handle_export_data_ajax() {
        check_ajax_referer('cinemabotpro_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $analytics = new CinemaBotPro_Analytics();
        $format = sanitize_text_field($_POST['format'] ?? 'csv');
        $period = sanitize_text_field($_POST['period'] ?? '30days');
        
        $export_result = $analytics->export_analytics($format, $period);
        
        if ($export_result) {
            wp_send_json_success($export_result);
        } else {
            wp_send_json_error(array('message' => __('Export failed', 'cinemabotpro')));
        }
    }
}