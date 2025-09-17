<?php
/**
 * CinemaBot Pro Analytics
 * 
 * Handles user interaction analytics, performance monitoring,
 * and detailed reporting for the chatbot system.
 */

class CinemaBotPro_Analytics {
    
    private $analytics_settings;
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'cinemabotpro_analytics';
        $this->analytics_settings = get_option('cinemabotpro_analytics_settings', array());
        
        // Initialize analytics
        add_action('init', array($this, 'init_analytics'));
        
        // AJAX handlers
        add_action('wp_ajax_cinemabotpro_get_analytics', array($this, 'handle_get_analytics_ajax'));
        add_action('wp_ajax_cinemabotpro_export_analytics', array($this, 'handle_export_analytics_ajax'));
        
        // Scheduled cleanup
        if (!wp_next_scheduled('cinemabotpro_analytics_cleanup')) {
            wp_schedule_event(time(), 'daily', 'cinemabotpro_analytics_cleanup');
        }
        add_action('cinemabotpro_analytics_cleanup', array($this, 'cleanup_old_analytics'));
        
        // Hook into various events
        add_action('cinemabotpro_chat_interaction', array($this, 'track_chat_interaction'), 10, 3);
        add_action('cinemabotpro_search_performed', array($this, 'track_search'), 10, 2);
        add_action('cinemabotpro_recommendation_given', array($this, 'track_recommendation'), 10, 2);
        add_action('cinemabotpro_avatar_changed', array($this, 'track_avatar_change'), 10, 2);
        add_action('cinemabotpro_language_switched', array($this, 'track_language_switch'), 10, 2);
    }
    
    /**
     * Initialize analytics system
     */
    public function init_analytics() {
        // Set default analytics settings
        if (empty($this->analytics_settings)) {
            $this->analytics_settings = array(
                'enable_analytics' => true,
                'track_chat_interactions' => true,
                'track_user_behavior' => true,
                'track_performance' => true,
                'track_errors' => true,
                'anonymize_data' => true,
                'retention_days' => 90,
                'real_time_analytics' => true,
                'export_formats' => array('csv', 'json', 'pdf'),
                'dashboard_refresh_interval' => 30, // seconds
                'track_geolocation' => false,
                'track_device_info' => true,
                'track_referrers' => true,
                'enable_heatmaps' => false
            );
            
            update_option('cinemabotpro_analytics_settings', $this->analytics_settings);
        }
        
        // Start session tracking if analytics enabled
        if ($this->analytics_settings['enable_analytics']) {
            $this->start_session_tracking();
        }
    }
    
    /**
     * Start session tracking
     */
    private function start_session_tracking() {
        if (!session_id()) {
            session_start();
        }
        
        $session_id = session_id();
        $user_id = get_current_user_id();
        
        // Track page view
        $this->log_event('page_view', array(
            'url' => $_SERVER['REQUEST_URI'] ?? '',
            'referrer' => $_SERVER['HTTP_REFERER'] ?? '',
            'user_agent' => $this->get_anonymized_user_agent(),
            'device_info' => $this->get_device_info()
        ));
        
        // Track session start if new session
        if (!isset($_SESSION['cinemabotpro_session_tracked'])) {
            $_SESSION['cinemabotpro_session_tracked'] = true;
            $_SESSION['cinemabotpro_session_start'] = time();
            
            $this->log_event('session_start', array(
                'entry_point' => $_SERVER['REQUEST_URI'] ?? '',
                'referrer' => $_SERVER['HTTP_REFERER'] ?? '',
                'device_info' => $this->get_device_info()
            ));
        }
    }
    
    /**
     * Log analytics event
     */
    public function log_event($event_type, $event_data = array(), $user_id = null, $session_id = null) {
        if (!$this->analytics_settings['enable_analytics']) {
            return false;
        }
        
        global $wpdb;
        
        // Get session ID
        if (!$session_id) {
            if (!session_id()) {
                session_start();
            }
            $session_id = session_id();
        }
        
        // Get user ID
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        // Anonymize data if required
        if ($this->analytics_settings['anonymize_data']) {
            $event_data = $this->anonymize_event_data($event_data);
        }
        
        // Prepare data
        $data = array(
            'user_id' => $user_id ?: null,
            'session_id' => $session_id,
            'event_type' => $event_type,
            'event_data' => wp_json_encode($event_data),
            'timestamp' => current_time('mysql')
        );
        
        // Insert into database
        $result = $wpdb->insert(
            $this->table_name,
            $data,
            array('%d', '%s', '%s', '%s', '%s')
        );
        
        // Real-time analytics update
        if ($this->analytics_settings['real_time_analytics']) {
            $this->update_real_time_stats($event_type, $event_data);
        }
        
        return $result !== false;
    }
    
    /**
     * Track chat interaction
     */
    public function track_chat_interaction($message, $response, $context) {
        if (!$this->analytics_settings['track_chat_interactions']) {
            return;
        }
        
        $event_data = array(
            'message_length' => strlen($message),
            'response_length' => strlen($response),
            'language' => $context['language'] ?? 'en',
            'intent' => $context['intent'] ?? 'unknown',
            'avatar_used' => $context['avatar'] ?? 'default',
            'response_time' => $context['response_time'] ?? 0,
            'user_satisfaction' => null // Can be updated later
        );
        
        $this->log_event('chat_interaction', $event_data);
    }
    
    /**
     * Track search performed
     */
    public function track_search($query, $results_count) {
        $event_data = array(
            'query' => $this->analytics_settings['anonymize_data'] ? 
                hash('sha256', $query) : substr($query, 0, 100),
            'query_length' => strlen($query),
            'results_count' => $results_count,
            'has_results' => $results_count > 0
        );
        
        $this->log_event('search_performed', $event_data);
    }
    
    /**
     * Track recommendation given
     */
    public function track_recommendation($recommendation_type, $content_data) {
        $event_data = array(
            'recommendation_type' => $recommendation_type,
            'content_type' => $content_data['type'] ?? 'unknown',
            'content_genre' => $content_data['genre'] ?? 'unknown',
            'content_rating' => $content_data['rating'] ?? 0,
            'recommendation_score' => $content_data['score'] ?? 0
        );
        
        $this->log_event('recommendation_given', $event_data);
    }
    
    /**
     * Track avatar change
     */
    public function track_avatar_change($old_avatar, $new_avatar) {
        $event_data = array(
            'old_avatar' => $old_avatar,
            'new_avatar' => $new_avatar,
            'change_type' => 'manual' // or 'automatic'
        );
        
        $this->log_event('avatar_changed', $event_data);
    }
    
    /**
     * Track language switch
     */
    public function track_language_switch($old_language, $new_language) {
        $event_data = array(
            'old_language' => $old_language,
            'new_language' => $new_language,
            'switch_method' => 'manual' // could be 'auto_detect'
        );
        
        $this->log_event('language_switched', $event_data);
    }
    
    /**
     * Track performance metrics
     */
    public function track_performance($metric_name, $value, $context = array()) {
        if (!$this->analytics_settings['track_performance']) {
            return;
        }
        
        $event_data = array(
            'metric_name' => $metric_name,
            'value' => $value,
            'context' => $context,
            'page_load_time' => $this->get_page_load_time(),
            'memory_usage' => memory_get_usage(true),
            'db_queries' => get_num_queries()
        );
        
        $this->log_event('performance_metric', $event_data);
    }
    
    /**
     * Track error
     */
    public function track_error($error_type, $error_message, $context = array()) {
        if (!$this->analytics_settings['track_errors']) {
            return;
        }
        
        $event_data = array(
            'error_type' => $error_type,
            'error_message' => substr($error_message, 0, 500),
            'context' => $context,
            'stack_trace' => $this->get_limited_stack_trace(),
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
            'user_agent' => $this->get_anonymized_user_agent()
        );
        
        $this->log_event('error', $event_data);
    }
    
    /**
     * Get dashboard analytics
     */
    public function get_dashboard_analytics($period = '7days') {
        global $wpdb;
        
        $period_map = array(
            '24hours' => '-1 day',
            '7days' => '-7 days',
            '30days' => '-30 days',
            '90days' => '-90 days'
        );
        
        $since = date('Y-m-d H:i:s', strtotime($period_map[$period] ?? '-7 days'));
        
        // Get basic stats
        $stats = array(
            'total_interactions' => $this->get_event_count('chat_interaction', $since),
            'total_searches' => $this->get_event_count('search_performed', $since),
            'total_sessions' => $this->get_unique_sessions_count($since),
            'total_users' => $this->get_unique_users_count($since),
            'avg_response_time' => $this->get_avg_response_time($since),
            'top_languages' => $this->get_top_languages($since),
            'popular_avatars' => $this->get_popular_avatars($since),
            'user_satisfaction' => $this->get_user_satisfaction($since),
            'error_rate' => $this->get_error_rate($since),
            'performance_metrics' => $this->get_performance_metrics($since),
            'interaction_timeline' => $this->get_interaction_timeline($since, $period),
            'content_preferences' => $this->get_content_preferences($since)
        );
        
        return $stats;
    }
    
    /**
     * Get event count
     */
    private function get_event_count($event_type, $since) {
        global $wpdb;
        
        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} 
                WHERE event_type = %s AND timestamp >= %s",
                $event_type,
                $since
            )
        );
    }
    
    /**
     * Get unique sessions count
     */
    private function get_unique_sessions_count($since) {
        global $wpdb;
        
        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT session_id) FROM {$this->table_name} 
                WHERE timestamp >= %s",
                $since
            )
        );
    }
    
    /**
     * Get unique users count
     */
    private function get_unique_users_count($since) {
        global $wpdb;
        
        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT user_id) FROM {$this->table_name} 
                WHERE user_id IS NOT NULL AND timestamp >= %s",
                $since
            )
        );
    }
    
    /**
     * Get average response time
     */
    private function get_avg_response_time($since) {
        global $wpdb;
        
        $result = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT AVG(CAST(JSON_EXTRACT(event_data, '$.response_time') AS DECIMAL(10,3))) 
                FROM {$this->table_name} 
                WHERE event_type = 'chat_interaction' 
                AND JSON_EXTRACT(event_data, '$.response_time') IS NOT NULL 
                AND timestamp >= %s",
                $since
            )
        );
        
        return round($result, 2) ?: 0;
    }
    
    /**
     * Get top languages
     */
    private function get_top_languages($since) {
        global $wpdb;
        
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT 
                    JSON_EXTRACT(event_data, '$.language') as language,
                    COUNT(*) as count
                FROM {$this->table_name} 
                WHERE event_type = 'chat_interaction' 
                AND JSON_EXTRACT(event_data, '$.language') IS NOT NULL 
                AND timestamp >= %s
                GROUP BY JSON_EXTRACT(event_data, '$.language')
                ORDER BY count DESC
                LIMIT 5",
                $since
            )
        );
        
        $languages = array();
        foreach ($results as $result) {
            $language = trim($result->language, '"');
            $languages[$language] = intval($result->count);
        }
        
        return $languages;
    }
    
    /**
     * Get popular avatars
     */
    private function get_popular_avatars($since) {
        global $wpdb;
        
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT 
                    JSON_EXTRACT(event_data, '$.avatar_used') as avatar,
                    COUNT(*) as count
                FROM {$this->table_name} 
                WHERE event_type = 'chat_interaction' 
                AND JSON_EXTRACT(event_data, '$.avatar_used') IS NOT NULL 
                AND timestamp >= %s
                GROUP BY JSON_EXTRACT(event_data, '$.avatar_used')
                ORDER BY count DESC
                LIMIT 10",
                $since
            )
        );
        
        $avatars = array();
        foreach ($results as $result) {
            $avatar = trim($result->avatar, '"');
            $avatars[$avatar] = intval($result->count);
        }
        
        return $avatars;
    }
    
    /**
     * Get user satisfaction
     */
    private function get_user_satisfaction($since) {
        // This would be based on user feedback/ratings
        // For now, return a placeholder
        return array(
            'average_rating' => 4.2,
            'total_ratings' => 150,
            'distribution' => array(
                '5' => 45,
                '4' => 60,
                '3' => 30,
                '2' => 10,
                '1' => 5
            )
        );
    }
    
    /**
     * Get error rate
     */
    private function get_error_rate($since) {
        global $wpdb;
        
        $total_events = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE timestamp >= %s",
                $since
            )
        );
        
        $error_events = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} 
                WHERE event_type = 'error' AND timestamp >= %s",
                $since
            )
        );
        
        return $total_events > 0 ? round(($error_events / $total_events) * 100, 2) : 0;
    }
    
    /**
     * Get performance metrics
     */
    private function get_performance_metrics($since) {
        global $wpdb;
        
        $metrics = array();
        
        // Average page load time
        $avg_load_time = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT AVG(CAST(JSON_EXTRACT(event_data, '$.page_load_time') AS DECIMAL(10,3))) 
                FROM {$this->table_name} 
                WHERE event_type = 'performance_metric' 
                AND JSON_EXTRACT(event_data, '$.page_load_time') IS NOT NULL 
                AND timestamp >= %s",
                $since
            )
        );
        
        $metrics['avg_page_load_time'] = round($avg_load_time, 3) ?: 0;
        
        // Average memory usage
        $avg_memory = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT AVG(CAST(JSON_EXTRACT(event_data, '$.memory_usage') AS UNSIGNED)) 
                FROM {$this->table_name} 
                WHERE event_type = 'performance_metric' 
                AND JSON_EXTRACT(event_data, '$.memory_usage') IS NOT NULL 
                AND timestamp >= %s",
                $since
            )
        );
        
        $metrics['avg_memory_usage'] = round($avg_memory / 1024 / 1024, 2) ?: 0; // Convert to MB
        
        return $metrics;
    }
    
    /**
     * Get interaction timeline
     */
    private function get_interaction_timeline($since, $period) {
        global $wpdb;
        
        $group_format = $period === '24hours' ? '%Y-%m-%d %H:00:00' : '%Y-%m-%d';
        
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT 
                    DATE_FORMAT(timestamp, %s) as time_period,
                    COUNT(*) as count
                FROM {$this->table_name} 
                WHERE event_type = 'chat_interaction' AND timestamp >= %s
                GROUP BY time_period
                ORDER BY time_period",
                $group_format,
                $since
            )
        );
        
        $timeline = array();
        foreach ($results as $result) {
            $timeline[$result->time_period] = intval($result->count);
        }
        
        return $timeline;
    }
    
    /**
     * Get content preferences
     */
    private function get_content_preferences($since) {
        global $wpdb;
        
        $preferences = array();
        
        // Top content types
        $content_types = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT 
                    JSON_EXTRACT(event_data, '$.content_type') as content_type,
                    COUNT(*) as count
                FROM {$this->table_name} 
                WHERE event_type = 'recommendation_given' 
                AND JSON_EXTRACT(event_data, '$.content_type') IS NOT NULL 
                AND timestamp >= %s
                GROUP BY JSON_EXTRACT(event_data, '$.content_type')
                ORDER BY count DESC
                LIMIT 5",
                $since
            )
        );
        
        $preferences['content_types'] = array();
        foreach ($content_types as $type) {
            $content_type = trim($type->content_type, '"');
            $preferences['content_types'][$content_type] = intval($type->count);
        }
        
        // Top genres
        $genres = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT 
                    JSON_EXTRACT(event_data, '$.content_genre') as genre,
                    COUNT(*) as count
                FROM {$this->table_name} 
                WHERE event_type = 'recommendation_given' 
                AND JSON_EXTRACT(event_data, '$.content_genre') IS NOT NULL 
                AND timestamp >= %s
                GROUP BY JSON_EXTRACT(event_data, '$.content_genre')
                ORDER BY count DESC
                LIMIT 10",
                $since
            )
        );
        
        $preferences['genres'] = array();
        foreach ($genres as $genre) {
            $genre_name = trim($genre->genre, '"');
            $preferences['genres'][$genre_name] = intval($genre->count);
        }
        
        return $preferences;
    }
    
    /**
     * Anonymize event data
     */
    private function anonymize_event_data($data) {
        // Remove or hash sensitive information
        $sensitive_keys = array('ip_address', 'email', 'phone', 'user_agent');
        
        foreach ($sensitive_keys as $key) {
            if (isset($data[$key])) {
                $data[$key] = hash('sha256', $data[$key]);
            }
        }
        
        // Truncate long text fields
        $text_keys = array('message', 'query', 'error_message');
        foreach ($text_keys as $key) {
            if (isset($data[$key]) && strlen($data[$key]) > 200) {
                $data[$key] = substr($data[$key], 0, 200) . '...';
            }
        }
        
        return $data;
    }
    
    /**
     * Get device info
     */
    private function get_device_info() {
        if (!$this->analytics_settings['track_device_info']) {
            return array();
        }
        
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $device_info = array(
            'is_mobile' => wp_is_mobile(),
            'browser' => $this->detect_browser($user_agent),
            'os' => $this->detect_os($user_agent),
            'screen_resolution' => null // Would need JavaScript to get this
        );
        
        return $device_info;
    }
    
    /**
     * Detect browser from user agent
     */
    private function detect_browser($user_agent) {
        $browsers = array(
            'Chrome' => '/Chrome/i',
            'Firefox' => '/Firefox/i',
            'Safari' => '/Safari/i',
            'Edge' => '/Edge/i',
            'Opera' => '/Opera/i',
            'Internet Explorer' => '/MSIE/i'
        );
        
        foreach ($browsers as $browser => $pattern) {
            if (preg_match($pattern, $user_agent)) {
                return $browser;
            }
        }
        
        return 'Unknown';
    }
    
    /**
     * Detect OS from user agent
     */
    private function detect_os($user_agent) {
        $os_array = array(
            'Windows' => '/Windows/i',
            'Mac' => '/Mac/i',
            'Linux' => '/Linux/i',
            'iOS' => '/iPhone|iPad/i',
            'Android' => '/Android/i'
        );
        
        foreach ($os_array as $os => $pattern) {
            if (preg_match($pattern, $user_agent)) {
                return $os;
            }
        }
        
        return 'Unknown';
    }
    
    /**
     * Get anonymized user agent
     */
    private function get_anonymized_user_agent() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if ($this->analytics_settings['anonymize_data']) {
            // Remove version numbers and specific identifying info
            $user_agent = preg_replace('/\d+\.\d+\.\d+[\.\d]*/', 'X.X.X', $user_agent);
            $user_agent = substr($user_agent, 0, 100);
        }
        
        return $user_agent;
    }
    
    /**
     * Get page load time
     */
    private function get_page_load_time() {
        if (defined('WP_START_TIMESTAMP')) {
            return round(microtime(true) - WP_START_TIMESTAMP, 3);
        }
        
        return 0;
    }
    
    /**
     * Get limited stack trace
     */
    private function get_limited_stack_trace() {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
        $simplified_trace = array();
        
        foreach ($trace as $frame) {
            $simplified_trace[] = array(
                'file' => basename($frame['file'] ?? ''),
                'line' => $frame['line'] ?? 0,
                'function' => $frame['function'] ?? ''
            );
        }
        
        return $simplified_trace;
    }
    
    /**
     * Update real-time stats
     */
    private function update_real_time_stats($event_type, $event_data) {
        $stats_key = 'cinemabotpro_realtime_stats';
        $stats = get_transient($stats_key) ?: array();
        
        // Initialize if needed
        if (!isset($stats['events'])) {
            $stats['events'] = array();
        }
        
        if (!isset($stats['counters'])) {
            $stats['counters'] = array();
        }
        
        // Add event to recent events
        $stats['events'][] = array(
            'type' => $event_type,
            'timestamp' => time(),
            'data' => $event_data
        );
        
        // Keep only last 50 events
        $stats['events'] = array_slice($stats['events'], -50);
        
        // Update counters
        if (!isset($stats['counters'][$event_type])) {
            $stats['counters'][$event_type] = 0;
        }
        $stats['counters'][$event_type]++;
        
        // Update timestamp
        $stats['last_updated'] = time();
        
        // Save back to transient
        set_transient($stats_key, $stats, HOUR_IN_SECONDS);
    }
    
    /**
     * Handle get analytics AJAX
     */
    public function handle_get_analytics_ajax() {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'cinemabotpro_nonce')) {
            wp_die('Security check failed');
        }
        
        $period = sanitize_text_field($_POST['period'] ?? '7days');
        $analytics = $this->get_dashboard_analytics($period);
        
        wp_send_json_success(array(
            'analytics' => $analytics,
            'period' => $period,
            'generated_at' => current_time('mysql')
        ));
    }
    
    /**
     * Handle export analytics AJAX
     */
    public function handle_export_analytics_ajax() {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'cinemabotpro_nonce')) {
            wp_die('Security check failed');
        }
        
        $format = sanitize_text_field($_POST['format'] ?? 'csv');
        $period = sanitize_text_field($_POST['period'] ?? '30days');
        
        $export_data = $this->export_analytics($format, $period);
        
        if ($export_data) {
            wp_send_json_success(array(
                'download_url' => $export_data['url'],
                'filename' => $export_data['filename'],
                'size' => $export_data['size']
            ));
        } else {
            wp_send_json_error(array('message' => 'Export failed'));
        }
    }
    
    /**
     * Export analytics data
     */
    public function export_analytics($format, $period) {
        global $wpdb;
        
        $period_map = array(
            '7days' => '-7 days',
            '30days' => '-30 days',
            '90days' => '-90 days'
        );
        
        $since = date('Y-m-d H:i:s', strtotime($period_map[$period] ?? '-30 days'));
        
        // Get data
        $data = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE timestamp >= %s ORDER BY timestamp DESC",
                $since
            ),
            ARRAY_A
        );
        
        if (empty($data)) {
            return false;
        }
        
        $upload_dir = wp_upload_dir();
        $filename = 'cinemabotpro-analytics-' . date('Y-m-d-H-i-s') . '.' . $format;
        $filepath = $upload_dir['path'] . '/' . $filename;
        
        switch ($format) {
            case 'csv':
                $success = $this->export_to_csv($data, $filepath);
                break;
            case 'json':
                $success = $this->export_to_json($data, $filepath);
                break;
            case 'pdf':
                $success = $this->export_to_pdf($data, $filepath);
                break;
            default:
                return false;
        }
        
        if ($success && file_exists($filepath)) {
            return array(
                'url' => $upload_dir['url'] . '/' . $filename,
                'filename' => $filename,
                'size' => filesize($filepath)
            );
        }
        
        return false;
    }
    
    /**
     * Export to CSV
     */
    private function export_to_csv($data, $filepath) {
        $file = fopen($filepath, 'w');
        
        if (!$file) {
            return false;
        }
        
        // Header
        $headers = array_keys($data[0]);
        fputcsv($file, $headers);
        
        // Data
        foreach ($data as $row) {
            fputcsv($file, $row);
        }
        
        fclose($file);
        return true;
    }
    
    /**
     * Export to JSON
     */
    private function export_to_json($data, $filepath) {
        $json = wp_json_encode($data, JSON_PRETTY_PRINT);
        return file_put_contents($filepath, $json) !== false;
    }
    
    /**
     * Export to PDF (basic implementation)
     */
    private function export_to_pdf($data, $filepath) {
        // This is a simplified PDF export
        // In production, you might want to use a proper PDF library
        
        $html = '<html><body>';
        $html .= '<h1>CinemaBot Pro Analytics Report</h1>';
        $html .= '<p>Generated: ' . current_time('mysql') . '</p>';
        $html .= '<table border="1" cellpadding="5">';
        
        // Header
        $headers = array_keys($data[0]);
        $html .= '<tr>';
        foreach ($headers as $header) {
            $html .= '<th>' . esc_html($header) . '</th>';
        }
        $html .= '</tr>';
        
        // Data (limit to first 100 rows for PDF)
        foreach (array_slice($data, 0, 100) as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td>' . esc_html(substr($cell, 0, 50)) . '</td>';
            }
            $html .= '</tr>';
        }
        
        $html .= '</table></body></html>';
        
        return file_put_contents($filepath, $html) !== false;
    }
    
    /**
     * Cleanup old analytics data
     */
    public function cleanup_old_analytics() {
        if (!$this->analytics_settings['enable_analytics']) {
            return;
        }
        
        global $wpdb;
        
        $retention_days = $this->analytics_settings['retention_days'];
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$retention_days} days"));
        
        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->table_name} WHERE timestamp < %s",
                $cutoff_date
            )
        );
        
        if ($deleted) {
            $this->log_event('analytics_cleanup', array(
                'deleted_records' => $deleted,
                'cutoff_date' => $cutoff_date
            ));
        }
    }
    
    /**
     * Get real-time stats
     */
    public function get_real_time_stats() {
        return get_transient('cinemabotpro_realtime_stats') ?: array();
    }
    
    /**
     * Get analytics settings
     */
    public function get_analytics_settings() {
        return $this->analytics_settings;
    }
    
    /**
     * Update analytics settings
     */
    public function update_analytics_settings($new_settings) {
        $this->analytics_settings = array_merge($this->analytics_settings, $new_settings);
        update_option('cinemabotpro_analytics_settings', $this->analytics_settings);
        
        $this->log_event('analytics_settings_updated', array(
            'updated_by' => get_current_user_id()
        ));
        
        return true;
    }
}