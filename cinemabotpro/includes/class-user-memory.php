<?php
/**
 * CinemaBot Pro User Memory System
 * 
 * Manages cross-session memory for user preferences, viewing history,
 * and GDPR-compliant user data handling.
 */

class CinemaBotPro_User_Memory {
    
    private $user_id;
    private $session_id;
    private $memory_enabled;
    private $gdpr_compliance;
    private $retention_period;
    
    public function __construct() {
        $this->user_id = get_current_user_id();
        $this->session_id = $this->get_session_id();
        $this->memory_enabled = get_option('cinemabotpro_memory_enabled', 1);
        $this->gdpr_compliance = get_option('cinemabotpro_gdpr_enabled', 1);
        $this->retention_period = get_option('cinemabotpro_data_retention_days', 365);
        
        // AJAX handlers
        add_action('wp_ajax_cinemabotpro_save_preference', array($this, 'handle_save_preference_ajax'));
        add_action('wp_ajax_nopriv_cinemabotpro_save_preference', array($this, 'handle_save_preference_ajax'));
        add_action('wp_ajax_cinemabotpro_get_preferences', array($this, 'handle_get_preferences_ajax'));
        add_action('wp_ajax_nopriv_cinemabotpro_get_preferences', array($this, 'handle_get_preferences_ajax'));
        add_action('wp_ajax_cinemabotpro_gdpr_consent', array($this, 'handle_gdpr_consent_ajax'));
        add_action('wp_ajax_nopriv_cinemabotpro_gdpr_consent', array($this, 'handle_gdpr_consent_ajax'));
        add_action('wp_ajax_cinemabotpro_export_data', array($this, 'handle_export_data_ajax'));
        add_action('wp_ajax_cinemabotpro_delete_data', array($this, 'handle_delete_data_ajax'));
        
        // Scheduled cleanup
        if (!wp_next_scheduled('cinemabotpro_memory_cleanup')) {
            wp_schedule_event(time(), 'daily', 'cinemabotpro_memory_cleanup');
        }
        add_action('cinemabotpro_memory_cleanup', array($this, 'cleanup_old_data'));
        
        // GDPR hooks
        add_filter('wp_privacy_personal_data_exporters', array($this, 'register_data_exporter'));
        add_filter('wp_privacy_personal_data_erasers', array($this, 'register_data_eraser'));
    }
    
    /**
     * Get session ID
     */
    private function get_session_id() {
        if (!session_id()) {
            session_start();
        }
        
        if (!isset($_SESSION['cinemabotpro_session_id'])) {
            $_SESSION['cinemabotpro_session_id'] = wp_generate_uuid4();
        }
        
        return $_SESSION['cinemabotpro_session_id'];
    }
    
    /**
     * Save user preference
     */
    public function save_preference($key, $value, $context = 'general') {
        if (!$this->memory_enabled || !$this->check_gdpr_consent()) {
            return false;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'cinemabotpro_user_prefs';
        
        $preference_data = array(
            'value' => $value,
            'context' => $context,
            'timestamp' => current_time('mysql'),
            'ip_address' => $this->get_anonymized_ip(),
            'user_agent' => $this->get_anonymized_user_agent()
        );
        
        $preference_key = $this->sanitize_preference_key($key);
        
        $existing = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id FROM $table WHERE user_id = %d AND preference_key = %s",
                $this->user_id ?: 0,
                $preference_key
            )
        );
        
        if ($existing) {
            $result = $wpdb->update(
                $table,
                array(
                    'preference_value' => wp_json_encode($preference_data),
                    'updated_at' => current_time('mysql')
                ),
                array(
                    'user_id' => $this->user_id ?: 0,
                    'preference_key' => $preference_key
                ),
                array('%s', '%s'),
                array('%d', '%s')
            );
        } else {
            $result = $wpdb->insert(
                $table,
                array(
                    'user_id' => $this->user_id ?: 0,
                    'preference_key' => $preference_key,
                    'preference_value' => wp_json_encode($preference_data),
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ),
                array('%d', '%s', '%s', '%s', '%s')
            );
        }
        
        // Log the action for audit trail
        $this->log_data_action('save_preference', $preference_key);
        
        return $result !== false;
    }
    
    /**
     * Get user preference
     */
    public function get_preference($key, $default = null) {
        if (!$this->memory_enabled) {
            return $default;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'cinemabotpro_user_prefs';
        
        $preference_key = $this->sanitize_preference_key($key);
        
        $result = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT preference_value FROM $table WHERE user_id = %d AND preference_key = %s",
                $this->user_id ?: 0,
                $preference_key
            )
        );
        
        if ($result) {
            $data = json_decode($result, true);
            return $data['value'] ?? $default;
        }
        
        return $default;
    }
    
    /**
     * Save viewing history
     */
    public function save_viewing_history($content_id, $content_type, $metadata = array()) {
        if (!$this->memory_enabled || !$this->check_gdpr_consent()) {
            return false;
        }
        
        $history = $this->get_preference('viewing_history', array());
        
        $entry = array(
            'content_id' => $content_id,
            'content_type' => $content_type,
            'metadata' => $metadata,
            'timestamp' => current_time('mysql'),
            'session_id' => $this->session_id
        );
        
        // Add to beginning of array
        array_unshift($history, $entry);
        
        // Limit history size
        $max_history = get_option('cinemabotpro_max_history_items', 100);
        $history = array_slice($history, 0, $max_history);
        
        return $this->save_preference('viewing_history', $history, 'history');
    }
    
    /**
     * Get viewing history
     */
    public function get_viewing_history($limit = 20) {
        $history = $this->get_preference('viewing_history', array());
        return array_slice($history, 0, $limit);
    }
    
    /**
     * Save user favorites
     */
    public function save_favorite($content_id, $content_type, $metadata = array()) {
        if (!$this->memory_enabled || !$this->check_gdpr_consent()) {
            return false;
        }
        
        $favorites = $this->get_preference('favorites', array());
        
        $favorite_key = $content_id . '_' . $content_type;
        
        $favorites[$favorite_key] = array(
            'content_id' => $content_id,
            'content_type' => $content_type,
            'metadata' => $metadata,
            'added_date' => current_time('mysql')
        );
        
        return $this->save_preference('favorites', $favorites, 'favorites');
    }
    
    /**
     * Remove favorite
     */
    public function remove_favorite($content_id, $content_type) {
        $favorites = $this->get_preference('favorites', array());
        $favorite_key = $content_id . '_' . $content_type;
        
        if (isset($favorites[$favorite_key])) {
            unset($favorites[$favorite_key]);
            return $this->save_preference('favorites', $favorites, 'favorites');
        }
        
        return false;
    }
    
    /**
     * Get user favorites
     */
    public function get_favorites() {
        return $this->get_preference('favorites', array());
    }
    
    /**
     * Save user interests
     */
    public function save_interest($interest_type, $interest_value, $weight = 1) {
        if (!$this->memory_enabled || !$this->check_gdpr_consent()) {
            return false;
        }
        
        $interests = $this->get_preference('interests', array());
        
        if (!isset($interests[$interest_type])) {
            $interests[$interest_type] = array();
        }
        
        if (isset($interests[$interest_type][$interest_value])) {
            $interests[$interest_type][$interest_value] += $weight;
        } else {
            $interests[$interest_type][$interest_value] = $weight;
        }
        
        // Keep only top interests per type
        arsort($interests[$interest_type]);
        $interests[$interest_type] = array_slice($interests[$interest_type], 0, 20, true);
        
        return $this->save_preference('interests', $interests, 'interests');
    }
    
    /**
     * Get user interests
     */
    public function get_interests($interest_type = null) {
        $interests = $this->get_preference('interests', array());
        
        if ($interest_type) {
            return $interests[$interest_type] ?? array();
        }
        
        return $interests;
    }
    
    /**
     * Get user context for AI
     */
    public function get_user_context() {
        if (!$this->memory_enabled) {
            return array();
        }
        
        $context = array(
            'language_preference' => $this->get_preference('language', 'en'),
            'favorite_genres' => $this->get_top_interests('genre', 5),
            'favorite_actors' => $this->get_top_interests('actor', 3),
            'favorite_directors' => $this->get_top_interests('director', 3),
            'recent_viewing' => $this->get_recent_viewing_summary(),
            'content_preferences' => array(
                'preferred_content_type' => $this->get_preference('content_type', 'movie'),
                'preferred_rating' => $this->get_preference('rating_preference', 'all'),
                'preferred_decade' => $this->get_preference('decade_preference', 'all'),
                'preferred_language' => $this->get_preference('content_language', 'all')
            ),
            'interaction_style' => array(
                'formality' => $this->get_preference('formality', 'casual'),
                'detail_level' => $this->get_preference('detail_level', 'medium'),
                'recommendation_style' => $this->get_preference('rec_style', 'balanced')
            )
        );
        
        return $context;
    }
    
    /**
     * Get top interests of specific type
     */
    private function get_top_interests($type, $limit = 5) {
        $interests = $this->get_interests($type);
        return array_slice(array_keys($interests), 0, $limit);
    }
    
    /**
     * Get recent viewing summary
     */
    private function get_recent_viewing_summary() {
        $history = $this->get_viewing_history(10);
        
        $summary = array(
            'total_items' => count($history),
            'content_types' => array(),
            'recent_genres' => array(),
            'last_activity' => null
        );
        
        foreach ($history as $item) {
            $content_type = $item['content_type'];
            $summary['content_types'][$content_type] = ($summary['content_types'][$content_type] ?? 0) + 1;
            
            if (isset($item['metadata']['genre'])) {
                $genre = $item['metadata']['genre'];
                $summary['recent_genres'][$genre] = ($summary['recent_genres'][$genre] ?? 0) + 1;
            }
            
            if (!$summary['last_activity'] || $item['timestamp'] > $summary['last_activity']) {
                $summary['last_activity'] = $item['timestamp'];
            }
        }
        
        return $summary;
    }
    
    /**
     * Check GDPR consent
     */
    private function check_gdpr_consent() {
        if (!$this->gdpr_compliance) {
            return true;
        }
        
        if ($this->user_id) {
            return get_user_meta($this->user_id, 'cinemabotpro_consent_given', true) === 'yes';
        }
        
        // For non-logged-in users, check session
        if (!session_id()) {
            session_start();
        }
        
        return isset($_SESSION['cinemabotpro_consent']) && $_SESSION['cinemabotpro_consent'] === 'yes';
    }
    
    /**
     * Record GDPR consent
     */
    public function record_gdpr_consent($consent_given = true) {
        $consent_value = $consent_given ? 'yes' : 'no';
        
        if ($this->user_id) {
            update_user_meta($this->user_id, 'cinemabotpro_consent_given', $consent_value);
            update_user_meta($this->user_id, 'cinemabotpro_consent_date', current_time('mysql'));
        }
        
        // Also save in session for non-logged-in users
        if (!session_id()) {
            session_start();
        }
        $_SESSION['cinemabotpro_consent'] = $consent_value;
        $_SESSION['cinemabotpro_consent_date'] = current_time('mysql');
        
        $this->log_data_action('gdpr_consent', $consent_value);
        
        return true;
    }
    
    /**
     * Get anonymized IP address
     */
    private function get_anonymized_ip() {
        if (!$this->gdpr_compliance) {
            return '';
        }
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        
        // Anonymize IP by removing last octet for IPv4
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            $parts[3] = '0';
            return implode('.', $parts);
        }
        
        // Anonymize IPv6 by keeping only first 64 bits
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $parts = explode(':', $ip);
            return implode(':', array_slice($parts, 0, 4)) . '::';
        }
        
        return '';
    }
    
    /**
     * Get anonymized user agent
     */
    private function get_anonymized_user_agent() {
        if (!$this->gdpr_compliance) {
            return '';
        }
        
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Remove version numbers and specific identifying information
        $user_agent = preg_replace('/\d+\.\d+\.\d+[\.\d]*/', 'X.X.X', $user_agent);
        $user_agent = preg_replace('/[A-F0-9]{8}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{12}/i', 'XXXX-XXXX-XXXX-XXXX', $user_agent);
        
        return substr($user_agent, 0, 200); // Limit length
    }
    
    /**
     * Sanitize preference key
     */
    private function sanitize_preference_key($key) {
        return sanitize_key($key);
    }
    
    /**
     * Log data action for audit trail
     */
    private function log_data_action($action, $details = '') {
        if (!$this->gdpr_compliance) {
            return;
        }
        
        $log_entry = array(
            'user_id' => $this->user_id ?: 0,
            'session_id' => $this->session_id,
            'action' => $action,
            'details' => $details,
            'timestamp' => current_time('mysql'),
            'ip_address' => $this->get_anonymized_ip()
        );
        
        // Save to WordPress transient for temporary storage
        $log_key = 'cinemabotpro_audit_log_' . wp_generate_uuid4();
        set_transient($log_key, $log_entry, DAY_IN_SECONDS);
    }
    
    /**
     * Handle save preference AJAX
     */
    public function handle_save_preference_ajax() {
        if (!wp_verify_nonce($_POST['nonce'], 'cinemabotpro_nonce')) {
            wp_die('Security check failed');
        }
        
        $key = sanitize_text_field($_POST['key']);
        $value = sanitize_text_field($_POST['value']);
        $context = sanitize_text_field($_POST['context'] ?? 'general');
        
        $result = $this->save_preference($key, $value, $context);
        
        if ($result) {
            wp_send_json_success(array('message' => 'Preference saved successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to save preference'));
        }
    }
    
    /**
     * Handle get preferences AJAX
     */
    public function handle_get_preferences_ajax() {
        if (!wp_verify_nonce($_POST['nonce'], 'cinemabotpro_nonce')) {
            wp_die('Security check failed');
        }
        
        $preferences = array(
            'language' => $this->get_preference('language', 'en'),
            'favorites' => $this->get_favorites(),
            'interests' => $this->get_interests(),
            'viewing_history' => $this->get_viewing_history(5),
            'content_preferences' => array(
                'preferred_content_type' => $this->get_preference('content_type', 'movie'),
                'preferred_rating' => $this->get_preference('rating_preference', 'all'),
                'preferred_decade' => $this->get_preference('decade_preference', 'all')
            )
        );
        
        wp_send_json_success(array('preferences' => $preferences));
    }
    
    /**
     * Handle GDPR consent AJAX
     */
    public function handle_gdpr_consent_ajax() {
        if (!wp_verify_nonce($_POST['nonce'], 'cinemabotpro_nonce')) {
            wp_die('Security check failed');
        }
        
        $consent = sanitize_text_field($_POST['consent']) === 'yes';
        
        $result = $this->record_gdpr_consent($consent);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => 'Consent recorded successfully',
                'consent_given' => $consent
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to record consent'));
        }
    }
    
    /**
     * Handle export data AJAX
     */
    public function handle_export_data_ajax() {
        if (!wp_verify_nonce($_POST['nonce'], 'cinemabotpro_nonce')) {
            wp_die('Security check failed');
        }
        
        if (!$this->user_id) {
            wp_send_json_error(array('message' => 'User must be logged in'));
        }
        
        $data = $this->export_user_data();
        
        wp_send_json_success(array(
            'data' => $data,
            'export_date' => current_time('mysql')
        ));
    }
    
    /**
     * Handle delete data AJAX
     */
    public function handle_delete_data_ajax() {
        if (!wp_verify_nonce($_POST['nonce'], 'cinemabotpro_nonce')) {
            wp_die('Security check failed');
        }
        
        if (!$this->user_id) {
            wp_send_json_error(array('message' => 'User must be logged in'));
        }
        
        $result = $this->delete_user_data();
        
        if ($result) {
            wp_send_json_success(array('message' => 'User data deleted successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete user data'));
        }
    }
    
    /**
     * Export user data for GDPR compliance
     */
    public function export_user_data() {
        global $wpdb;
        
        $data = array();
        
        // Get user preferences
        $prefs_table = $wpdb->prefix . 'cinemabotpro_user_prefs';
        $preferences = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT preference_key, preference_value, created_at, updated_at FROM $prefs_table WHERE user_id = %d",
                $this->user_id
            )
        );
        
        $data['preferences'] = array();
        foreach ($preferences as $pref) {
            $data['preferences'][] = array(
                'key' => $pref->preference_key,
                'value' => json_decode($pref->preference_value, true),
                'created' => $pref->created_at,
                'updated' => $pref->updated_at
            );
        }
        
        // Get chat history
        $chat_table = $wpdb->prefix . 'cinemabotpro_chats';
        $chats = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT message, response, language, timestamp FROM $chat_table WHERE user_id = %d ORDER BY timestamp DESC",
                $this->user_id
            )
        );
        
        $data['chat_history'] = $chats;
        
        // Get analytics data
        $analytics_table = $wpdb->prefix . 'cinemabotpro_analytics';
        $analytics = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT event_type, event_data, timestamp FROM $analytics_table WHERE user_id = %d ORDER BY timestamp DESC",
                $this->user_id
            )
        );
        
        $data['analytics'] = $analytics;
        
        return $data;
    }
    
    /**
     * Delete user data for GDPR compliance
     */
    public function delete_user_data() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'cinemabotpro_user_prefs',
            $wpdb->prefix . 'cinemabotpro_chats',
            $wpdb->prefix . 'cinemabotpro_analytics'
        );
        
        $success = true;
        
        foreach ($tables as $table) {
            $result = $wpdb->delete(
                $table,
                array('user_id' => $this->user_id),
                array('%d')
            );
            
            if ($result === false) {
                $success = false;
            }
        }
        
        // Delete user meta
        delete_user_meta($this->user_id, 'cinemabotpro_consent_given');
        delete_user_meta($this->user_id, 'cinemabotpro_consent_date');
        
        $this->log_data_action('data_deletion', 'complete');
        
        return $success;
    }
    
    /**
     * Cleanup old data based on retention policy
     */
    public function cleanup_old_data() {
        if (!$this->gdpr_compliance) {
            return;
        }
        
        global $wpdb;
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$this->retention_period} days"));
        
        $tables = array(
            $wpdb->prefix . 'cinemabotpro_chats' => 'timestamp',
            $wpdb->prefix . 'cinemabotpro_analytics' => 'timestamp',
            $wpdb->prefix . 'cinemabotpro_user_prefs' => 'updated_at'
        );
        
        foreach ($tables as $table => $date_column) {
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM $table WHERE $date_column < %s",
                    $cutoff_date
                )
            );
        }
        
        // Clean up transients
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_cinemabotpro_%' 
            AND option_name LIKE '%audit_log%'"
        );
    }
    
    /**
     * Register data exporter for GDPR
     */
    public function register_data_exporter($exporters) {
        $exporters['cinemabotpro'] = array(
            'exporter_friendly_name' => 'CinemaBot Pro Data',
            'callback' => array($this, 'data_exporter_callback')
        );
        
        return $exporters;
    }
    
    /**
     * Register data eraser for GDPR
     */
    public function register_data_eraser($erasers) {
        $erasers['cinemabotpro'] = array(
            'eraser_friendly_name' => 'CinemaBot Pro Data',
            'callback' => array($this, 'data_eraser_callback')
        );
        
        return $erasers;
    }
    
    /**
     * Data exporter callback for GDPR
     */
    public function data_exporter_callback($email_address, $page = 1) {
        $user = get_user_by('email', $email_address);
        
        if (!$user) {
            return array(
                'data' => array(),
                'done' => true
            );
        }
        
        $this->user_id = $user->ID;
        $data = $this->export_user_data();
        
        $export_items = array();
        
        if (!empty($data['preferences'])) {
            $export_items[] = array(
                'group_id' => 'cinemabotpro_preferences',
                'group_label' => 'CinemaBot Pro Preferences',
                'item_id' => 'user-preferences',
                'data' => $data['preferences']
            );
        }
        
        if (!empty($data['chat_history'])) {
            $export_items[] = array(
                'group_id' => 'cinemabotpro_chats',
                'group_label' => 'CinemaBot Pro Chat History',
                'item_id' => 'chat-history',
                'data' => $data['chat_history']
            );
        }
        
        return array(
            'data' => $export_items,
            'done' => true
        );
    }
    
    /**
     * Data eraser callback for GDPR
     */
    public function data_eraser_callback($email_address, $page = 1) {
        $user = get_user_by('email', $email_address);
        
        if (!$user) {
            return array(
                'items_removed' => 0,
                'items_retained' => 0,
                'messages' => array(),
                'done' => true
            );
        }
        
        $this->user_id = $user->ID;
        $result = $this->delete_user_data();
        
        return array(
            'items_removed' => $result ? 1 : 0,
            'items_retained' => $result ? 0 : 1,
            'messages' => $result ? array() : array('Failed to delete CinemaBot Pro data'),
            'done' => true
        );
    }
}