<?php
/**
 * CinemaBot Pro Chatbot Core Class
 * 
 * Handles the main chatbot functionality including multilingual support,
 * conversation management, and AI integration.
 */

class CinemaBotPro_Chatbot {
    
    private $supported_languages;
    private $current_language;
    private $session_id;
    
    public function __construct() {
        $this->supported_languages = get_option('cinemabotpro_supported_languages', array('en', 'bn', 'hi', 'banglish'));
        $this->current_language = $this->detect_language();
        $this->session_id = $this->get_session_id();
        
        add_action('wp_ajax_cinemabotpro_chat', array($this, 'handle_chat_ajax'));
        add_action('wp_ajax_nopriv_cinemabotpro_chat', array($this, 'handle_chat_ajax'));
        add_action('wp_ajax_cinemabotpro_language_switch', array($this, 'handle_language_switch'));
        add_action('wp_ajax_nopriv_cinemabotpro_language_switch', array($this, 'handle_language_switch'));
    }
    
    /**
     * Detect user's preferred language
     */
    private function detect_language() {
        // Check user preference first
        $user_id = get_current_user_id();
        if ($user_id) {
            $user_lang = get_user_meta($user_id, 'cinemabotpro_language_pref', true);
            if ($user_lang && in_array($user_lang, $this->supported_languages)) {
                return $user_lang;
            }
        }
        
        // Check session storage
        if (isset($_SESSION['cinemabotpro_language'])) {
            return $_SESSION['cinemabotpro_language'];
        }
        
        // Auto-detect from browser language
        if (get_option('cinemabotpro_auto_lang_detect', 1)) {
            $browser_lang = $this->detect_browser_language();
            if ($browser_lang) {
                return $browser_lang;
            }
        }
        
        // Default to English
        return get_option('cinemabotpro_default_language', 'en');
    }
    
    /**
     * Detect language from browser headers
     */
    private function detect_browser_language() {
        if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return false;
        }
        
        $accepted_languages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
        
        foreach ($accepted_languages as $lang) {
            $lang = trim(explode(';', $lang)[0]);
            $lang_code = explode('-', $lang)[0];
            
            // Map common language codes
            $lang_map = array(
                'bn' => 'bn',
                'hi' => 'hi',
                'en' => 'en'
            );
            
            if (isset($lang_map[$lang_code]) && in_array($lang_map[$lang_code], $this->supported_languages)) {
                return $lang_map[$lang_code];
            }
        }
        
        return false;
    }
    
    /**
     * Get or create session ID
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
     * Handle chat AJAX requests
     */
    public function handle_chat_ajax() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cinemabotpro_nonce')) {
            wp_die('Security check failed');
        }
        
        $message = sanitize_text_field($_POST['message']);
        $language = sanitize_text_field($_POST['language'] ?? $this->current_language);
        $context = sanitize_text_field($_POST['context'] ?? '');
        
        // Rate limiting
        if (!$this->check_rate_limit()) {
            wp_send_json_error(array(
                'message' => $this->get_translated_text('rate_limit_exceeded', $language)
            ));
        }
        
        // Validate message length
        $max_length = get_option('cinemabotpro_max_message_length', 500);
        if (strlen($message) > $max_length) {
            wp_send_json_error(array(
                'message' => $this->get_translated_text('message_too_long', $language)
            ));
        }
        
        // Process the message
        $response = $this->process_message($message, $language, $context);
        
        // Save chat history
        $this->save_chat_history($message, $response, $language);
        
        // Log analytics
        $this->log_chat_analytics($message, $response, $language);
        
        wp_send_json_success(array(
            'response' => $response,
            'language' => $language,
            'avatar' => $this->get_contextual_avatar($message, $response),
            'suggestions' => $this->get_suggestions($message, $language)
        ));
    }
    
    /**
     * Process user message and generate response
     */
    private function process_message($message, $language, $context = '') {
        // Detect message intent
        $intent = $this->detect_intent($message, $language);
        
        // Get user memory context
        $memory_context = $this->get_user_memory_context();
        
        // Prepare AI context
        $ai_context = array(
            'message' => $message,
            'language' => $language,
            'intent' => $intent,
            'user_context' => $context,
            'memory' => $memory_context,
            'conversation_history' => $this->get_recent_conversation()
        );
        
        // Generate response using AI engine
        $ai_engine = new CinemaBotPro_AI_Engine();
        $response = $ai_engine->generate_response($ai_context);
        
        // Post-process response
        $response = $this->post_process_response($response, $language);
        
        return $response;
    }
    
    /**
     * Detect user intent from message
     */
    private function detect_intent($message, $language) {
        $message_lower = strtolower($message);
        
        // Define intent patterns for each language
        $intent_patterns = array(
            'en' => array(
                'search' => array('search', 'find', 'look for', 'show me'),
                'recommend' => array('recommend', 'suggest', 'what should i watch'),
                'info' => array('tell me about', 'information', 'details', 'plot'),
                'rating' => array('rating', 'score', 'reviews', 'imdb'),
                'similar' => array('similar', 'like this', 'related'),
                'greeting' => array('hello', 'hi', 'hey', 'good morning', 'good evening')
            ),
            'bn' => array(
                'search' => array('খুঁজে', 'দেখাও', 'খোঁজ'),
                'recommend' => array('সুপারিশ', 'প্রস্তাব', 'কি দেখবো'),
                'info' => array('সম্পর্কে বলো', 'তথ্য', 'বিস্তারিত'),
                'rating' => array('রেটিং', 'স্কোর', 'রিভিউ'),
                'similar' => array('একই রকম', 'এর মতো'),
                'greeting' => array('হ্যালো', 'হাই', 'নমস্কার', 'আসসালামু আলাইকুম')
            ),
            'hi' => array(
                'search' => array('खोजें', 'दिखाएं', 'ढूंढें'),
                'recommend' => array('सुझाव', 'सिफारिश', 'क्या देखूं'),
                'info' => array('के बारे में बताएं', 'जानकारी', 'विवरण'),
                'rating' => array('रेटिंग', 'स्कोर', 'समीक्षा'),
                'similar' => array('समान', 'इस जैसा'),
                'greeting' => array('हैलो', 'हाय', 'नमस्ते')
            ),
            'banglish' => array(
                'search' => array('khuje', 'dekhao', 'khoj'),
                'recommend' => array('suggest koro', 'ki dekhbo', 'recommend'),
                'info' => array('somporke bolo', 'details', 'info'),
                'rating' => array('rating', 'score', 'review'),
                'similar' => array('same type', 'er moto'),
                'greeting' => array('hello', 'hi', 'nomoshkar', 'salam')
            )
        );
        
        $patterns = $intent_patterns[$language] ?? $intent_patterns['en'];
        
        foreach ($patterns as $intent => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($message_lower, $keyword) !== false) {
                    return $intent;
                }
            }
        }
        
        return 'general';
    }
    
    /**
     * Get user memory context
     */
    private function get_user_memory_context() {
        $user_memory = new CinemaBotPro_User_Memory();
        return $user_memory->get_user_context();
    }
    
    /**
     * Get recent conversation history
     */
    private function get_recent_conversation($limit = 5) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'cinemabotpro_chats';
        $user_id = get_current_user_id();
        
        $where_clause = $user_id ? "user_id = %d" : "session_id = %s";
        $where_value = $user_id ? $user_id : $this->session_id;
        
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT message, response, language, timestamp 
                FROM $table 
                WHERE $where_clause 
                ORDER BY timestamp DESC 
                LIMIT %d",
                $where_value,
                $limit
            )
        );
        
        return array_reverse($results);
    }
    
    /**
     * Post-process AI response
     */
    private function post_process_response($response, $language) {
        // Add cultural context and localization
        $response = $this->add_cultural_context($response, $language);
        
        // Format response with proper styling
        $response = $this->format_response($response);
        
        return $response;
    }
    
    /**
     * Add cultural context to response
     */
    private function add_cultural_context($response, $language) {
        $cultural_suffixes = array(
            'bn' => ' 😊',
            'hi' => ' 🙏',
            'banglish' => ' 😄',
            'en' => ' 🎬'
        );
        
        if (isset($cultural_suffixes[$language])) {
            $response .= $cultural_suffixes[$language];
        }
        
        return $response;
    }
    
    /**
     * Format response with styling
     */
    private function format_response($response) {
        // Add markdown-like formatting
        $response = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $response);
        $response = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $response);
        
        return $response;
    }
    
    /**
     * Get contextual avatar based on conversation
     */
    private function get_contextual_avatar($message, $response) {
        $avatar_system = new CinemaBotPro_Avatar_System();
        return $avatar_system->get_contextual_avatar($message, $response);
    }
    
    /**
     * Get conversation suggestions
     */
    private function get_suggestions($message, $language) {
        $suggestions = array();
        
        // Base suggestions per language
        $base_suggestions = array(
            'en' => array(
                'Recommend a movie',
                'Search for action movies',
                'Tell me about Marvel movies',
                'What\'s trending now?'
            ),
            'bn' => array(
                'একটি সিনেমা সুপারিশ করো',
                'অ্যাকশন সিনেমা খুঁজে দাও',
                'বলিউড সিনেমার কথা বলো',
                'এখন কী ট্রেন্ডিং?'
            ),
            'hi' => array(
                'एक फिल्म सुझाएं',
                'एक्शन फिल्में खोजें',
                'बॉलीवुड के बारे में बताएं',
                'अभी क्या ट्रेंडिंग है?'
            ),
            'banglish' => array(
                'Ekta movie suggest koro',
                'Action movie khuje dao',
                'Bollywood er kotha bolo',
                'Ekhon ki trending?'
            )
        );
        
        return $base_suggestions[$language] ?? $base_suggestions['en'];
    }
    
    /**
     * Handle language switching
     */
    public function handle_language_switch() {
        if (!wp_verify_nonce($_POST['nonce'], 'cinemabotpro_nonce')) {
            wp_die('Security check failed');
        }
        
        $new_language = sanitize_text_field($_POST['language']);
        
        if (!in_array($new_language, $this->supported_languages)) {
            wp_send_json_error(array('message' => 'Unsupported language'));
        }
        
        // Save language preference
        $user_id = get_current_user_id();
        if ($user_id) {
            update_user_meta($user_id, 'cinemabotpro_language_pref', $new_language);
        }
        
        // Save to session
        if (!session_id()) {
            session_start();
        }
        $_SESSION['cinemabotpro_language'] = $new_language;
        
        $this->current_language = $new_language;
        
        wp_send_json_success(array(
            'message' => $this->get_translated_text('language_switched', $new_language),
            'language' => $new_language
        ));
    }
    
    /**
     * Check rate limiting
     */
    private function check_rate_limit() {
        $rate_limit = get_option('cinemabotpro_rate_limit', 30);
        $user_id = get_current_user_id();
        $ip_address = $_SERVER['REMOTE_ADDR'];
        
        $key = $user_id ? "user_$user_id" : "ip_$ip_address";
        $transient_key = "cinemabotpro_rate_limit_$key";
        
        $current_count = get_transient($transient_key);
        
        if ($current_count === false) {
            set_transient($transient_key, 1, MINUTE_IN_SECONDS);
            return true;
        }
        
        if ($current_count >= $rate_limit) {
            return false;
        }
        
        set_transient($transient_key, $current_count + 1, MINUTE_IN_SECONDS);
        return true;
    }
    
    /**
     * Save chat history
     */
    private function save_chat_history($message, $response, $language) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'cinemabotpro_chats';
        $user_id = get_current_user_id();
        
        $wpdb->insert(
            $table,
            array(
                'user_id' => $user_id ?: 0,
                'session_id' => $this->session_id,
                'message' => $message,
                'response' => $response,
                'language' => $language,
                'avatar' => $this->get_contextual_avatar($message, $response),
                'timestamp' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Log chat analytics
     */
    private function log_chat_analytics($message, $response, $language) {
        $analytics = new CinemaBotPro_Analytics();
        $analytics->log_event('chat_interaction', array(
            'message_length' => strlen($message),
            'response_length' => strlen($response),
            'language' => $language,
            'session_id' => $this->session_id
        ));
    }
    
    /**
     * Get translated text
     */
    private function get_translated_text($key, $language) {
        $translations = array(
            'rate_limit_exceeded' => array(
                'en' => 'You are sending messages too quickly. Please wait a moment.',
                'bn' => 'আপনি খুব দ্রুত মেসেজ পাঠাচ্ছেন। অনুগ্রহ করে একটু অপেক্ষা করুন।',
                'hi' => 'आप बहुत तेज़ी से संदेश भेज रहे हैं। कृपया थोड़ा इंतज़ार करें।',
                'banglish' => 'Apni khub tara tari message pathacchen. Ektu wait korun.'
            ),
            'message_too_long' => array(
                'en' => 'Your message is too long. Please keep it under 500 characters.',
                'bn' => 'আপনার মেসেজটি খুব বড়। অনুগ্রহ করে ৫০০ অক্ষরের মধ্যে রাখুন।',
                'hi' => 'आपका संदेश बहुत लंबा है। कृपया इसे 500 अक्षरों के अंतर्गत रखें।',
                'banglish' => 'Apnar message ta khub boro. 500 character er moddhe rakhun.'
            ),
            'language_switched' => array(
                'en' => 'Language switched successfully!',
                'bn' => 'ভাষা সফলভাবে পরিবর্তিত হয়েছে!',
                'hi' => 'भाषा सफलतापूर्वक बदल गई!',
                'banglish' => 'Language successfully switch hoye geche!'
            )
        );
        
        return $translations[$key][$language] ?? $translations[$key]['en'] ?? $key;
    }
    
    /**
     * Get current language
     */
    public function get_current_language() {
        return $this->current_language;
    }
    
    /**
     * Get supported languages
     */
    public function get_supported_languages() {
        return $this->supported_languages;
    }
}