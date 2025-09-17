<?php
/**
 * CinemaBot Pro Security
 * 
 * Implements OWASP-compliant security features including
 * input validation, output encoding, and access control.
 */

class CinemaBotPro_Security {
    
    private $security_settings;
    private $rate_limits;
    private $blocked_ips;
    
    public function __construct() {
        $this->security_settings = get_option('cinemabotpro_security_settings', array());
        $this->rate_limits = array();
        $this->blocked_ips = get_option('cinemabotpro_blocked_ips', array());
        
        // Security hooks
        add_action('init', array($this, 'init_security'));
        add_action('wp_loaded', array($this, 'check_security_threats'));
        add_filter('sanitize_text_field', array($this, 'enhanced_sanitization'), 10, 1);
        add_action('wp_ajax_cinemabotpro_security_scan', array($this, 'handle_security_scan_ajax'));
        
        // Content Security Policy
        add_action('wp_head', array($this, 'add_content_security_policy'));
        
        // Security headers
        add_action('send_headers', array($this, 'add_security_headers'));
        
        // Input validation
        add_filter('cinemabotpro_validate_input', array($this, 'validate_user_input'), 10, 2);
        
        // SQL injection protection
        add_filter('query', array($this, 'prevent_sql_injection'));
        
        // XSS protection
        add_filter('the_content', array($this, 'xss_protection'), 1);
        add_filter('comment_text', array($this, 'xss_protection'), 1);
        
        // CSRF protection
        add_action('wp_ajax_cinemabotpro_chat', array($this, 'verify_csrf_token'), 1);
        add_action('wp_ajax_nopriv_cinemabotpro_chat', array($this, 'verify_csrf_token'), 1);
        
        // Brute force protection
        add_action('wp_login_failed', array($this, 'handle_failed_login'));
        add_filter('authenticate', array($this, 'check_login_attempts'), 30, 3);
        
        // File upload security
        add_filter('wp_handle_upload_prefilter', array($this, 'secure_file_upload'));
    }
    
    /**
     * Initialize security settings
     */
    public function init_security() {
        // Set default security settings if not exists
        if (empty($this->security_settings)) {
            $this->security_settings = array(
                'enable_rate_limiting' => true,
                'max_requests_per_minute' => 60,
                'enable_ip_blocking' => true,
                'enable_csrf_protection' => true,
                'enable_xss_protection' => true,
                'enable_sql_injection_protection' => true,
                'enable_file_upload_security' => true,
                'enable_brute_force_protection' => true,
                'max_login_attempts' => 5,
                'lockout_duration' => 30, // minutes
                'enable_security_logging' => true,
                'security_log_retention' => 30, // days
                'allowed_file_types' => array('jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt'),
                'max_file_size' => 5242880, // 5MB
                'enable_honeypot' => true,
                'enable_geolocation_blocking' => false,
                'blocked_countries' => array(),
                'enable_user_agent_filtering' => true,
                'suspicious_user_agents' => array('bot', 'crawler', 'spider', 'scraper')
            );
            
            update_option('cinemabotpro_security_settings', $this->security_settings);
        }
        
        // Initialize rate limiting
        $this->init_rate_limiting();
        
        // Initialize security logging
        $this->init_security_logging();
    }
    
    /**
     * Initialize rate limiting
     */
    private function init_rate_limiting() {
        if (!$this->security_settings['enable_rate_limiting']) {
            return;
        }
        
        $user_id = get_current_user_id();
        $ip_address = $this->get_client_ip();
        $key = $user_id ? "user_$user_id" : "ip_$ip_address";
        
        $transient_key = "cinemabotpro_rate_limit_$key";
        $current_count = get_transient($transient_key);
        
        if ($current_count === false) {
            set_transient($transient_key, 1, MINUTE_IN_SECONDS);
        } else {
            if ($current_count >= $this->security_settings['max_requests_per_minute']) {
                $this->log_security_event('rate_limit_exceeded', array(
                    'ip' => $ip_address,
                    'user_id' => $user_id,
                    'count' => $current_count
                ));
                
                wp_die('Rate limit exceeded. Please try again later.', 'Rate Limited', array('response' => 429));
            }
            
            set_transient($transient_key, $current_count + 1, MINUTE_IN_SECONDS);
        }
    }
    
    /**
     * Check for security threats
     */
    public function check_security_threats() {
        $ip_address = $this->get_client_ip();
        
        // Check if IP is blocked
        if ($this->is_ip_blocked($ip_address)) {
            $this->log_security_event('blocked_ip_access_attempt', array('ip' => $ip_address));
            wp_die('Access denied.', 'Blocked', array('response' => 403));
        }
        
        // Check user agent
        if ($this->security_settings['enable_user_agent_filtering']) {
            $this->check_suspicious_user_agent();
        }
        
        // Check for malicious requests
        $this->check_malicious_requests();
        
        // Check geolocation if enabled
        if ($this->security_settings['enable_geolocation_blocking']) {
            $this->check_geolocation();
        }
    }
    
    /**
     * Enhanced input sanitization
     */
    public function enhanced_sanitization($value) {
        // Remove null bytes
        $value = str_replace(chr(0), '', $value);
        
        // Remove control characters except tab, newline, and carriage return
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
        
        // Normalize unicode
        if (function_exists('normalizer_normalize')) {
            $value = normalizer_normalize($value, Normalizer::FORM_C);
        }
        
        // Additional security filtering
        $value = $this->filter_security_threats($value);
        
        return $value;
    }
    
    /**
     * Filter security threats from input
     */
    private function filter_security_threats($input) {
        // XSS patterns
        $xss_patterns = array(
            '/<script[^>]*>.*?<\/script>/is',
            '/<iframe[^>]*>.*?<\/iframe>/is',
            '/<object[^>]*>.*?<\/object>/is',
            '/<embed[^>]*>.*?<\/embed>/is',
            '/javascript:/i',
            '/vbscript:/i',
            '/onload=/i',
            '/onerror=/i',
            '/onclick=/i',
            '/onmouseover=/i'
        );
        
        foreach ($xss_patterns as $pattern) {
            $input = preg_replace($pattern, '', $input);
        }
        
        // SQL injection patterns
        $sql_patterns = array(
            '/(\bunion\s+select)/i',
            '/(\bselect\s+.*\bfrom)/i',
            '/(\binsert\s+into)/i',
            '/(\bupdate\s+.*\bset)/i',
            '/(\bdelete\s+from)/i',
            '/(\bdrop\s+table)/i',
            '/(\balter\s+table)/i',
            '/(\bexec\s*\()/i',
            '/(\beval\s*\()/i'
        );
        
        foreach ($sql_patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                $this->log_security_event('sql_injection_attempt', array(
                    'input' => substr($input, 0, 200),
                    'ip' => $this->get_client_ip()
                ));
                
                // Return sanitized version or block
                $input = preg_replace($pattern, '[BLOCKED]', $input);
            }
        }
        
        return $input;
    }
    
    /**
     * Validate user input
     */
    public function validate_user_input($input, $type = 'text') {
        if (empty($input)) {
            return $input;
        }
        
        switch ($type) {
            case 'email':
                return filter_var($input, FILTER_VALIDATE_EMAIL) ? $input : false;
                
            case 'url':
                return filter_var($input, FILTER_VALIDATE_URL) ? $input : false;
                
            case 'int':
                return filter_var($input, FILTER_VALIDATE_INT) !== false ? intval($input) : false;
                
            case 'float':
                return filter_var($input, FILTER_VALIDATE_FLOAT) !== false ? floatval($input) : false;
                
            case 'alphanum':
                return preg_match('/^[a-zA-Z0-9]+$/', $input) ? $input : false;
                
            case 'text':
            default:
                // Enhanced text validation
                $input = trim($input);
                $input = $this->enhanced_sanitization($input);
                
                // Check for suspicious patterns
                if ($this->contains_suspicious_patterns($input)) {
                    $this->log_security_event('suspicious_input_detected', array(
                        'input' => substr($input, 0, 200),
                        'ip' => $this->get_client_ip()
                    ));
                    return false;
                }
                
                return $input;
        }
    }
    
    /**
     * Check for suspicious patterns
     */
    private function contains_suspicious_patterns($input) {
        $suspicious_patterns = array(
            '/\b(union|select|insert|update|delete|drop|alter|exec|eval)\b/i',
            '/<\s*script/i',
            '/javascript:/i',
            '/data:text\/html/i',
            '/\.\.\//',
            '/\0/',
            '/\x00/'
        );
        
        foreach ($suspicious_patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Prevent SQL injection
     */
    public function prevent_sql_injection($query) {
        if (!$this->security_settings['enable_sql_injection_protection']) {
            return $query;
        }
        
        // Check for suspicious SQL patterns
        $suspicious_sql = array(
            '/(\bunion\s+select)/i',
            '/(\bor\s+1\s*=\s*1)/i',
            '/(\band\s+1\s*=\s*1)/i',
            '/(\bor\s+.*\s*=\s*.*)/i',
            '/(\bhaving\s+.*)/i',
            '/(\bexec\s*\()/i',
            '/(\bxp_cmdshell)/i'
        );
        
        foreach ($suspicious_sql as $pattern) {
            if (preg_match($pattern, $query)) {
                $this->log_security_event('sql_injection_in_query', array(
                    'query' => substr($query, 0, 200),
                    'ip' => $this->get_client_ip()
                ));
                
                // Block the query
                wp_die('Suspicious database query blocked.', 'Security Alert', array('response' => 403));
            }
        }
        
        return $query;
    }
    
    /**
     * XSS protection
     */
    public function xss_protection($content) {
        if (!$this->security_settings['enable_xss_protection']) {
            return $content;
        }
        
        // Detect and log XSS attempts
        $xss_patterns = array(
            '/<script[^>]*>/i',
            '/javascript:/i',
            '/vbscript:/i',
            '/onclick=/i',
            '/onerror=/i',
            '/onload=/i'
        );
        
        foreach ($xss_patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                $this->log_security_event('xss_attempt_detected', array(
                    'content' => substr($content, 0, 200),
                    'ip' => $this->get_client_ip()
                ));
                break;
            }
        }
        
        return $content;
    }
    
    /**
     * Verify CSRF token
     */
    public function verify_csrf_token() {
        if (!$this->security_settings['enable_csrf_protection']) {
            return;
        }
        
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cinemabotpro_nonce')) {
            $this->log_security_event('csrf_token_verification_failed', array(
                'ip' => $this->get_client_ip(),
                'user_id' => get_current_user_id()
            ));
            
            wp_die('Security check failed. Please refresh the page and try again.', 'CSRF Protection', array('response' => 403));
        }
    }
    
    /**
     * Handle failed login attempts
     */
    public function handle_failed_login($username) {
        if (!$this->security_settings['enable_brute_force_protection']) {
            return;
        }
        
        $ip_address = $this->get_client_ip();
        $attempts_key = "cinemabotpro_login_attempts_$ip_address";
        $lockout_key = "cinemabotpro_lockout_$ip_address";
        
        // Check if already locked out
        if (get_transient($lockout_key)) {
            return;
        }
        
        $attempts = get_transient($attempts_key) ?: 0;
        $attempts++;
        
        set_transient($attempts_key, $attempts, HOUR_IN_SECONDS);
        
        $this->log_security_event('failed_login_attempt', array(
            'username' => $username,
            'ip' => $ip_address,
            'attempts' => $attempts
        ));
        
        if ($attempts >= $this->security_settings['max_login_attempts']) {
            // Lock out the IP
            $lockout_duration = $this->security_settings['lockout_duration'] * MINUTE_IN_SECONDS;
            set_transient($lockout_key, true, $lockout_duration);
            
            $this->log_security_event('ip_locked_out', array(
                'ip' => $ip_address,
                'duration' => $lockout_duration
            ));
            
            // Add to blocked IPs temporarily
            $this->add_blocked_ip($ip_address, 'brute_force', $lockout_duration);
        }
    }
    
    /**
     * Check login attempts
     */
    public function check_login_attempts($user, $username, $password) {
        if (!$this->security_settings['enable_brute_force_protection']) {
            return $user;
        }
        
        $ip_address = $this->get_client_ip();
        $lockout_key = "cinemabotpro_lockout_$ip_address";
        
        if (get_transient($lockout_key)) {
            return new WP_Error('lockout', 'Too many failed login attempts. Please try again later.');
        }
        
        return $user;
    }
    
    /**
     * Secure file upload
     */
    public function secure_file_upload($file) {
        if (!$this->security_settings['enable_file_upload_security']) {
            return $file;
        }
        
        // Check file size
        if ($file['size'] > $this->security_settings['max_file_size']) {
            $file['error'] = 'File size exceeds maximum allowed size.';
            return $file;
        }
        
        // Check file type
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $this->security_settings['allowed_file_types'])) {
            $this->log_security_event('unauthorized_file_upload_attempt', array(
                'filename' => $file['name'],
                'extension' => $file_extension,
                'ip' => $this->get_client_ip()
            ));
            
            $file['error'] = 'File type not allowed.';
            return $file;
        }
        
        // Check file content
        if (!$this->validate_file_content($file['tmp_name'], $file_extension)) {
            $file['error'] = 'File content validation failed.';
            return $file;
        }
        
        return $file;
    }
    
    /**
     * Validate file content
     */
    private function validate_file_content($file_path, $extension) {
        if (!file_exists($file_path)) {
            return false;
        }
        
        // Check for embedded scripts in images
        if (in_array($extension, array('jpg', 'jpeg', 'png', 'gif'))) {
            $file_content = file_get_contents($file_path);
            
            // Look for suspicious content
            $suspicious_patterns = array(
                '/<\?php/',
                '/<script/',
                '/eval\s*\(/',
                '/exec\s*\(/',
                '/system\s*\(/',
                '/passthru\s*\(/'
            );
            
            foreach ($suspicious_patterns as $pattern) {
                if (preg_match($pattern, $file_content)) {
                    $this->log_security_event('malicious_file_content_detected', array(
                        'filename' => basename($file_path),
                        'pattern' => $pattern,
                        'ip' => $this->get_client_ip()
                    ));
                    return false;
                }
            }
        }
        
        return true;
    }
    
    /**
     * Check suspicious user agent
     */
    private function check_suspicious_user_agent() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if (empty($user_agent)) {
            $this->log_security_event('empty_user_agent', array('ip' => $this->get_client_ip()));
            return;
        }
        
        foreach ($this->security_settings['suspicious_user_agents'] as $suspicious) {
            if (stripos($user_agent, $suspicious) !== false) {
                $this->log_security_event('suspicious_user_agent_detected', array(
                    'user_agent' => $user_agent,
                    'ip' => $this->get_client_ip()
                ));
                
                // You might want to block or monitor these
                break;
            }
        }
    }
    
    /**
     * Check for malicious requests
     */
    private function check_malicious_requests() {
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        $query_string = $_SERVER['QUERY_STRING'] ?? '';
        
        $malicious_patterns = array(
            '/\.\.\//',
            '/\0/',
            '/\x00/',
            '/union.*select/i',
            '/script.*alert/i',
            '/base64_decode/i',
            '/eval\s*\(/i'
        );
        
        $check_strings = array($request_uri, $query_string);
        
        foreach ($check_strings as $string) {
            foreach ($malicious_patterns as $pattern) {
                if (preg_match($pattern, $string)) {
                    $this->log_security_event('malicious_request_detected', array(
                        'request_uri' => $request_uri,
                        'query_string' => $query_string,
                        'pattern' => $pattern,
                        'ip' => $this->get_client_ip()
                    ));
                    
                    wp_die('Malicious request detected.', 'Security Alert', array('response' => 403));
                }
            }
        }
    }
    
    /**
     * Check geolocation
     */
    private function check_geolocation() {
        if (empty($this->security_settings['blocked_countries'])) {
            return;
        }
        
        $ip_address = $this->get_client_ip();
        $country_code = $this->get_country_by_ip($ip_address);
        
        if ($country_code && in_array($country_code, $this->security_settings['blocked_countries'])) {
            $this->log_security_event('blocked_country_access_attempt', array(
                'ip' => $ip_address,
                'country' => $country_code
            ));
            
            wp_die('Access denied from your location.', 'Geo-blocked', array('response' => 403));
        }
    }
    
    /**
     * Get country by IP (basic implementation)
     */
    private function get_country_by_ip($ip) {
        // This is a simplified implementation
        // In production, you might use a service like MaxMind GeoLite2
        
        // For now, return null to disable geolocation blocking
        return null;
    }
    
    /**
     * Add Content Security Policy
     */
    public function add_content_security_policy() {
        $csp = "default-src 'self'; ";
        $csp .= "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; ";
        $csp .= "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; ";
        $csp .= "font-src 'self' https://fonts.gstatic.com; ";
        $csp .= "img-src 'self' data: https: http:; ";
        $csp .= "connect-src 'self' https://api.openai.com https://api.themoviedb.org https://www.omdbapi.com; ";
        $csp .= "frame-src 'none'; ";
        $csp .= "object-src 'none'; ";
        $csp .= "base-uri 'self'; ";
        $csp .= "form-action 'self';";
        
        echo '<meta http-equiv="Content-Security-Policy" content="' . esc_attr($csp) . '">' . "\n";
    }
    
    /**
     * Add security headers
     */
    public function add_security_headers() {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        
        // HSTS (only if HTTPS)
        if (is_ssl()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = array(
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Proxy
            'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // Standard
        );
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip_list = explode(',', $_SERVER[$key]);
                $ip = trim($ip_list[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Check if IP is blocked
     */
    private function is_ip_blocked($ip) {
        return in_array($ip, $this->blocked_ips);
    }
    
    /**
     * Add blocked IP
     */
    private function add_blocked_ip($ip, $reason = '', $duration = 0) {
        if (!in_array($ip, $this->blocked_ips)) {
            $this->blocked_ips[] = $ip;
            update_option('cinemabotpro_blocked_ips', $this->blocked_ips);
            
            // If duration is set, schedule removal
            if ($duration > 0) {
                wp_schedule_single_event(time() + $duration, 'cinemabotpro_unblock_ip', array($ip));
            }
            
            $this->log_security_event('ip_blocked', array(
                'ip' => $ip,
                'reason' => $reason,
                'duration' => $duration
            ));
        }
    }
    
    /**
     * Remove blocked IP
     */
    public function remove_blocked_ip($ip) {
        $key = array_search($ip, $this->blocked_ips);
        if ($key !== false) {
            unset($this->blocked_ips[$key]);
            $this->blocked_ips = array_values($this->blocked_ips);
            update_option('cinemabotpro_blocked_ips', $this->blocked_ips);
            
            $this->log_security_event('ip_unblocked', array('ip' => $ip));
        }
    }
    
    /**
     * Initialize security logging
     */
    private function init_security_logging() {
        if (!$this->security_settings['enable_security_logging']) {
            return;
        }
        
        // Schedule cleanup of old logs
        if (!wp_next_scheduled('cinemabotpro_cleanup_security_logs')) {
            wp_schedule_event(time(), 'daily', 'cinemabotpro_cleanup_security_logs');
        }
        
        add_action('cinemabotpro_cleanup_security_logs', array($this, 'cleanup_security_logs'));
        add_action('cinemabotpro_unblock_ip', array($this, 'remove_blocked_ip'));
    }
    
    /**
     * Log security event
     */
    private function log_security_event($event_type, $data = array()) {
        if (!$this->security_settings['enable_security_logging']) {
            return;
        }
        
        global $wpdb;
        
        $log_data = array(
            'event_type' => $event_type,
            'ip_address' => $this->get_client_ip(),
            'user_id' => get_current_user_id(),
            'timestamp' => current_time('mysql'),
            'data' => wp_json_encode($data),
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            'request_uri' => substr($_SERVER['REQUEST_URI'] ?? '', 0, 255)
        );
        
        // Store in analytics table for now
        $table = $wpdb->prefix . 'cinemabotpro_analytics';
        
        $wpdb->insert(
            $table,
            array(
                'user_id' => $log_data['user_id'] ?: null,
                'session_id' => session_id() ?: '',
                'event_type' => 'security_' . $event_type,
                'event_data' => $log_data['data'],
                'timestamp' => $log_data['timestamp']
            ),
            array('%d', '%s', '%s', '%s', '%s')
        );
        
        // Also store in transient for immediate access
        $transient_key = 'cinemabotpro_security_log_' . time() . '_' . wp_generate_uuid4();
        set_transient($transient_key, $log_data, WEEK_IN_SECONDS);
    }
    
    /**
     * Cleanup security logs
     */
    public function cleanup_security_logs() {
        if (!$this->security_settings['enable_security_logging']) {
            return;
        }
        
        global $wpdb;
        
        $retention_days = $this->security_settings['security_log_retention'];
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$retention_days} days"));
        
        // Clean up from analytics table
        $table = $wpdb->prefix . 'cinemabotpro_analytics';
        
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $table WHERE event_type LIKE 'security_%' AND timestamp < %s",
                $cutoff_date
            )
        );
        
        // Clean up transients
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_cinemabotpro_security_log_%'"
        );
    }
    
    /**
     * Handle security scan AJAX
     */
    public function handle_security_scan_ajax() {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'cinemabotpro_nonce')) {
            wp_die('Security check failed');
        }
        
        $scan_results = $this->run_security_scan();
        
        wp_send_json_success(array(
            'scan_results' => $scan_results,
            'timestamp' => current_time('mysql')
        ));
    }
    
    /**
     * Run security scan
     */
    private function run_security_scan() {
        $results = array(
            'score' => 0,
            'total_checks' => 0,
            'passed_checks' => 0,
            'issues' => array(),
            'recommendations' => array()
        );
        
        // Check WordPress version
        $results['total_checks']++;
        $wp_version = get_bloginfo('version');
        $latest_wp = $this->get_latest_wordpress_version();
        
        if (version_compare($wp_version, $latest_wp, '>=')) {
            $results['passed_checks']++;
        } else {
            $results['issues'][] = 'WordPress is not up to date (Current: ' . $wp_version . ', Latest: ' . $latest_wp . ')';
            $results['recommendations'][] = 'Update WordPress to the latest version';
        }
        
        // Check plugin security settings
        $results['total_checks']++;
        if ($this->security_settings['enable_csrf_protection'] && 
            $this->security_settings['enable_xss_protection'] && 
            $this->security_settings['enable_sql_injection_protection']) {
            $results['passed_checks']++;
        } else {
            $results['issues'][] = 'Some security features are disabled';
            $results['recommendations'][] = 'Enable all security protection features';
        }
        
        // Check SSL
        $results['total_checks']++;
        if (is_ssl()) {
            $results['passed_checks']++;
        } else {
            $results['issues'][] = 'SSL/HTTPS is not enabled';
            $results['recommendations'][] = 'Enable SSL certificate for your website';
        }
        
        // Check file permissions
        $results['total_checks']++;
        $file_permissions_ok = $this->check_file_permissions();
        if ($file_permissions_ok) {
            $results['passed_checks']++;
        } else {
            $results['issues'][] = 'Some files have incorrect permissions';
            $results['recommendations'][] = 'Review and fix file permissions';
        }
        
        // Check for suspicious files
        $results['total_checks']++;
        $suspicious_files = $this->scan_for_suspicious_files();
        if (empty($suspicious_files)) {
            $results['passed_checks']++;
        } else {
            $results['issues'][] = 'Suspicious files detected: ' . implode(', ', $suspicious_files);
            $results['recommendations'][] = 'Review and remove suspicious files';
        }
        
        // Calculate score
        $results['score'] = $results['total_checks'] > 0 ? 
            round(($results['passed_checks'] / $results['total_checks']) * 100) : 0;
        
        return $results;
    }
    
    /**
     * Get latest WordPress version
     */
    private function get_latest_wordpress_version() {
        $version_check = wp_remote_get('https://api.wordpress.org/core/version-check/1.7/');
        
        if (!is_wp_error($version_check)) {
            $body = wp_remote_retrieve_body($version_check);
            $data = json_decode($body, true);
            
            if (isset($data['offers'][0]['version'])) {
                return $data['offers'][0]['version'];
            }
        }
        
        return get_bloginfo('version'); // Fallback to current version
    }
    
    /**
     * Check file permissions
     */
    private function check_file_permissions() {
        $critical_files = array(
            ABSPATH . 'wp-config.php' => '0644',
            ABSPATH . '.htaccess' => '0644'
        );
        
        foreach ($critical_files as $file => $expected_perm) {
            if (file_exists($file)) {
                $current_perm = substr(sprintf('%o', fileperms($file)), -4);
                if ($current_perm !== $expected_perm) {
                    return false;
                }
            }
        }
        
        return true;
    }
    
    /**
     * Scan for suspicious files
     */
    private function scan_for_suspicious_files() {
        $suspicious_files = array();
        $scan_dirs = array(
            ABSPATH,
            WP_CONTENT_DIR
        );
        
        $suspicious_patterns = array(
            '/eval\s*\(/i',
            '/base64_decode/i',
            '/gzinflate/i',
            '/\$_POST\s*\[/',
            '/\$_GET\s*\[/',
            '/system\s*\(/i',
            '/exec\s*\(/i'
        );
        
        foreach ($scan_dirs as $dir) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir),
                RecursiveIteratorIterator::SELF_FIRST
            );
            
            $count = 0;
            foreach ($files as $file) {
                if ($count++ > 1000) break; // Limit scan to prevent timeout
                
                if ($file->isFile() && in_array($file->getExtension(), array('php', 'js'))) {
                    $content = file_get_contents($file->getPathname());
                    
                    foreach ($suspicious_patterns as $pattern) {
                        if (preg_match($pattern, $content)) {
                            $suspicious_files[] = str_replace(ABSPATH, '', $file->getPathname());
                            break;
                        }
                    }
                }
            }
        }
        
        return array_slice($suspicious_files, 0, 10); // Limit results
    }
    
    /**
     * Get security statistics
     */
    public function get_security_statistics() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'cinemabotpro_analytics';
        
        // Get security events from last 30 days
        $stats = array(
            'blocked_attempts' => 0,
            'failed_logins' => 0,
            'xss_attempts' => 0,
            'sql_injection_attempts' => 0,
            'malicious_uploads' => 0,
            'rate_limit_exceeded' => 0,
            'total_events' => 0
        );
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime('-30 days'));
        
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT event_type, COUNT(*) as count 
                FROM $table 
                WHERE event_type LIKE 'security_%' 
                AND timestamp >= %s 
                GROUP BY event_type",
                $cutoff_date
            )
        );
        
        foreach ($results as $result) {
            $event_type = str_replace('security_', '', $result->event_type);
            
            switch ($event_type) {
                case 'blocked_ip_access_attempt':
                case 'malicious_request_detected':
                    $stats['blocked_attempts'] += $result->count;
                    break;
                case 'failed_login_attempt':
                    $stats['failed_logins'] += $result->count;
                    break;
                case 'xss_attempt_detected':
                    $stats['xss_attempts'] += $result->count;
                    break;
                case 'sql_injection_attempt':
                case 'sql_injection_in_query':
                    $stats['sql_injection_attempts'] += $result->count;
                    break;
                case 'unauthorized_file_upload_attempt':
                case 'malicious_file_content_detected':
                    $stats['malicious_uploads'] += $result->count;
                    break;
                case 'rate_limit_exceeded':
                    $stats['rate_limit_exceeded'] += $result->count;
                    break;
            }
            
            $stats['total_events'] += $result->count;
        }
        
        return $stats;
    }
    
    /**
     * Get security settings
     */
    public function get_security_settings() {
        return $this->security_settings;
    }
    
    /**
     * Update security settings
     */
    public function update_security_settings($new_settings) {
        $this->security_settings = array_merge($this->security_settings, $new_settings);
        update_option('cinemabotpro_security_settings', $this->security_settings);
        
        $this->log_security_event('security_settings_updated', array(
            'updated_by' => get_current_user_id()
        ));
        
        return true;
    }
}