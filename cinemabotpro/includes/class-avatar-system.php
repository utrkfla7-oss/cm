<?php
/**
 * CinemaBot Pro Avatar System
 * 
 * Manages dynamic avatar selection, rotation, and contextual matching
 * with 50+ pre-loaded avatars and smooth animations.
 */

class CinemaBotPro_Avatar_System {
    
    private $avatar_pack;
    private $current_avatar;
    private $rotation_interval;
    private $avatar_cache;
    
    public function __construct() {
        $this->avatar_pack = get_option('cinemabotpro_avatar_pack', 'default');
        $this->rotation_interval = get_option('cinemabotpro_avatar_rotation', 30);
        $this->avatar_cache = get_option('cinemabotpro_avatar_cache', array());
        
        // Initialize avatars if cache is empty
        if (empty($this->avatar_cache)) {
            $this->initialize_avatars();
        }
        
        // Schedule avatar rotation
        if (!wp_next_scheduled('cinemabotpro_avatar_rotation')) {
            wp_schedule_event(time(), 'every_30_seconds', 'cinemabotpro_avatar_rotation');
        }
        
        add_action('cinemabotpro_avatar_rotation', array($this, 'rotate_avatar'));
        add_action('wp_ajax_cinemabotpro_get_avatar', array($this, 'handle_get_avatar_ajax'));
        add_action('wp_ajax_nopriv_cinemabotpro_get_avatar', array($this, 'handle_get_avatar_ajax'));
    }
    
    /**
     * Initialize avatar collection
     */
    private function initialize_avatars() {
        $avatars = array(
            // Movie Genre Avatars
            'action_hero' => array(
                'name' => 'Action Hero',
                'image' => 'action-hero.png',
                'contexts' => array('action', 'thriller', 'adventure'),
                'emotions' => array('confident', 'determined', 'brave'),
                'description' => 'A heroic character perfect for action discussions'
            ),
            'romantic_lead' => array(
                'name' => 'Romantic Lead',
                'image' => 'romantic-lead.png',
                'contexts' => array('romance', 'drama', 'love'),
                'emotions' => array('loving', 'passionate', 'gentle'),
                'description' => 'A charming character for romantic content'
            ),
            'comedy_star' => array(
                'name' => 'Comedy Star',
                'image' => 'comedy-star.png',
                'contexts' => array('comedy', 'humor', 'funny'),
                'emotions' => array('happy', 'playful', 'witty'),
                'description' => 'A jovial character for comedy discussions'
            ),
            'horror_survivor' => array(
                'name' => 'Horror Survivor',
                'image' => 'horror-survivor.png',
                'contexts' => array('horror', 'scary', 'thriller'),
                'emotions' => array('scared', 'cautious', 'brave'),
                'description' => 'A resilient character for horror content'
            ),
            'sci_fi_explorer' => array(
                'name' => 'Sci-Fi Explorer',
                'image' => 'sci-fi-explorer.png',
                'contexts' => array('science fiction', 'space', 'future'),
                'emotions' => array('curious', 'intelligent', 'adventurous'),
                'description' => 'A futuristic character for sci-fi discussions'
            ),
            
            // Cultural Avatars
            'bollywood_dancer' => array(
                'name' => 'Bollywood Dancer',
                'image' => 'bollywood-dancer.png',
                'contexts' => array('bollywood', 'indian', 'musical'),
                'emotions' => array('energetic', 'graceful', 'expressive'),
                'description' => 'A vibrant character for Bollywood content'
            ),
            'bengali_poet' => array(
                'name' => 'Bengali Poet',
                'image' => 'bengali-poet.png',
                'contexts' => array('bengali', 'poetry', 'literature'),
                'emotions' => array('thoughtful', 'artistic', 'wise'),
                'description' => 'A cultured character for Bengali content'
            ),
            'hollywood_star' => array(
                'name' => 'Hollywood Star',
                'image' => 'hollywood-star.png',
                'contexts' => array('hollywood', 'blockbuster', 'celebrity'),
                'emotions' => array('glamorous', 'confident', 'charismatic'),
                'description' => 'A glamorous character for Hollywood discussions'
            ),
            
            // Personality Types
            'friendly_guide' => array(
                'name' => 'Friendly Guide',
                'image' => 'friendly-guide.png',
                'contexts' => array('help', 'guidance', 'recommendation'),
                'emotions' => array('helpful', 'kind', 'patient'),
                'description' => 'A helpful character for guidance and recommendations'
            ),
            'movie_critic' => array(
                'name' => 'Movie Critic',
                'image' => 'movie-critic.png',
                'contexts' => array('review', 'analysis', 'critique'),
                'emotions' => array('analytical', 'discerning', 'intellectual'),
                'description' => 'A sophisticated character for movie analysis'
            ),
            'entertainment_host' => array(
                'name' => 'Entertainment Host',
                'image' => 'entertainment-host.png',
                'contexts' => array('entertainment', 'news', 'celebrity'),
                'emotions' => array('enthusiastic', 'informative', 'engaging'),
                'description' => 'An energetic character for entertainment news'
            ),
            
            // Animated Characters
            'cartoon_character' => array(
                'name' => 'Cartoon Character',
                'image' => 'cartoon-character.png',
                'contexts' => array('animation', 'cartoon', 'family'),
                'emotions' => array('playful', 'colorful', 'fun'),
                'description' => 'A fun character for animated content'
            ),
            'anime_hero' => array(
                'name' => 'Anime Hero',
                'image' => 'anime-hero.png',
                'contexts' => array('anime', 'manga', 'japanese'),
                'emotions' => array('determined', 'stylish', 'cool'),
                'description' => 'A stylish character for anime discussions'
            ),
            
            // Additional Avatars (continuing to reach 50+)
            'film_noir_detective' => array(
                'name' => 'Film Noir Detective',
                'image' => 'film-noir-detective.png',
                'contexts' => array('noir', 'mystery', 'detective'),
                'emotions' => array('mysterious', 'brooding', 'sharp'),
                'description' => 'A mysterious character for noir content'
            ),
            'musical_performer' => array(
                'name' => 'Musical Performer',
                'image' => 'musical-performer.png',
                'contexts' => array('musical', 'song', 'performance'),
                'emotions' => array('melodious', 'expressive', 'talented'),
                'description' => 'A talented character for musical content'
            ),
            'documentary_narrator' => array(
                'name' => 'Documentary Narrator',
                'image' => 'documentary-narrator.png',
                'contexts' => array('documentary', 'educational', 'factual'),
                'emotions' => array('informative', 'authoritative', 'clear'),
                'description' => 'An authoritative character for documentaries'
            ),
            'fantasy_wizard' => array(
                'name' => 'Fantasy Wizard',
                'image' => 'fantasy-wizard.png',
                'contexts' => array('fantasy', 'magic', 'adventure'),
                'emotions' => array('mystical', 'wise', 'powerful'),
                'description' => 'A mystical character for fantasy content'
            ),
            'western_cowboy' => array(
                'name' => 'Western Cowboy',
                'image' => 'western-cowboy.png',
                'contexts' => array('western', 'cowboy', 'frontier'),
                'emotions' => array('rugged', 'independent', 'brave'),
                'description' => 'A rugged character for western content'
            ),
            'superhero' => array(
                'name' => 'Superhero',
                'image' => 'superhero.png',
                'contexts' => array('superhero', 'marvel', 'dc', 'comic'),
                'emotions' => array('heroic', 'powerful', 'just'),
                'description' => 'A heroic character for superhero content'
            ),
            'period_drama_character' => array(
                'name' => 'Period Drama Character',
                'image' => 'period-drama.png',
                'contexts' => array('period', 'historical', 'drama'),
                'emotions' => array('elegant', 'refined', 'dramatic'),
                'description' => 'An elegant character for period dramas'
            )
        );
        
        // Generate additional avatars to reach 50+
        $additional_avatars = $this->generate_additional_avatars();
        $avatars = array_merge($avatars, $additional_avatars);
        
        $this->avatar_cache = $avatars;
        update_option('cinemabotpro_avatar_cache', $avatars);
    }
    
    /**
     * Generate additional avatars to reach 50+
     */
    private function generate_additional_avatars() {
        $additional = array();
        
        $base_types = array(
            'tv_host', 'game_show_host', 'news_anchor', 'talk_show_host',
            'reality_star', 'cooking_show_host', 'travel_guide', 'sports_commentator',
            'fashion_expert', 'tech_reviewer', 'music_critic', 'art_curator',
            'librarian', 'professor', 'student', 'teenager',
            'elderly_sage', 'child_actor', 'voice_actor', 'stunt_performer',
            'director', 'producer', 'screenwriter', 'cinematographer',
            'composer', 'sound_engineer', 'editor', 'costume_designer',
            'makeup_artist', 'set_designer', 'special_effects_artist', 'animator'
        );
        
        $emotions = array('happy', 'sad', 'excited', 'calm', 'surprised', 'thoughtful');
        $contexts = array('general', 'professional', 'casual', 'formal', 'creative', 'technical');
        
        foreach ($base_types as $index => $type) {
            $emotion = $emotions[$index % count($emotions)];
            $context = $contexts[$index % count($contexts)];
            
            $additional[$type] = array(
                'name' => ucwords(str_replace('_', ' ', $type)),
                'image' => $type . '.png',
                'contexts' => array($context, 'general'),
                'emotions' => array($emotion, 'friendly'),
                'description' => 'A ' . $emotion . ' character for ' . $context . ' discussions'
            );
        }
        
        return $additional;
    }
    
    /**
     * Get contextual avatar based on message and response content
     */
    public function get_contextual_avatar($message, $response) {
        $context = $this->analyze_content_context($message . ' ' . $response);
        $emotion = $this->analyze_emotion($response);
        
        // Find best matching avatar
        $best_match = $this->find_best_avatar_match($context, $emotion);
        
        if ($best_match) {
            $this->current_avatar = $best_match;
            return $this->get_avatar_data($best_match);
        }
        
        // Fallback to default avatar
        return $this->get_default_avatar();
    }
    
    /**
     * Analyze content context from text
     */
    private function analyze_content_context($text) {
        $text_lower = strtolower($text);
        
        $context_keywords = array(
            'action' => array('action', 'fight', 'battle', 'explosion', 'chase', 'hero'),
            'romance' => array('love', 'romantic', 'kiss', 'heart', 'relationship', 'couple'),
            'comedy' => array('funny', 'laugh', 'joke', 'humor', 'comedy', 'hilarious'),
            'horror' => array('scary', 'horror', 'ghost', 'monster', 'frightening', 'creepy'),
            'sci-fi' => array('science', 'future', 'space', 'robot', 'alien', 'technology'),
            'bollywood' => array('bollywood', 'hindi', 'indian', 'dance', 'song'),
            'bengali' => array('bengali', 'kolkata', 'bangladesh', 'rabindranath'),
            'anime' => array('anime', 'manga', 'japanese', 'otaku'),
            'documentary' => array('documentary', 'facts', 'real', 'educational'),
            'musical' => array('musical', 'song', 'dance', 'music', 'singing'),
            'western' => array('western', 'cowboy', 'frontier', 'ranch'),
            'fantasy' => array('fantasy', 'magic', 'wizard', 'dragon', 'mythical'),
            'superhero' => array('superhero', 'marvel', 'dc', 'batman', 'superman', 'spider')
        );
        
        foreach ($context_keywords as $context => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($text_lower, $keyword) !== false) {
                    return $context;
                }
            }
        }
        
        return 'general';
    }
    
    /**
     * Analyze emotion from response text
     */
    private function analyze_emotion($text) {
        $text_lower = strtolower($text);
        
        $emotion_indicators = array(
            'happy' => array('great', 'awesome', 'wonderful', 'fantastic', 'excellent', '😊', '😄'),
            'excited' => array('amazing', 'incredible', 'wow', 'fantastic', 'thrilling', '🎉', '✨'),
            'calm' => array('peaceful', 'relaxing', 'serene', 'gentle', 'soothing'),
            'thoughtful' => array('interesting', 'consider', 'think', 'ponder', 'reflect'),
            'surprised' => array('surprising', 'unexpected', 'wow', 'shocking', 'amazing'),
            'confident' => array('definitely', 'certainly', 'sure', 'confident', 'absolutely'),
            'helpful' => array('help', 'assist', 'support', 'guide', 'recommend')
        );
        
        foreach ($emotion_indicators as $emotion => $indicators) {
            foreach ($indicators as $indicator) {
                if (strpos($text_lower, $indicator) !== false) {
                    return $emotion;
                }
            }
        }
        
        return 'friendly';
    }
    
    /**
     * Find best avatar match based on context and emotion
     */
    private function find_best_avatar_match($context, $emotion) {
        $best_score = 0;
        $best_avatar = null;
        
        foreach ($this->avatar_cache as $avatar_id => $avatar_data) {
            $score = 0;
            
            // Context matching
            if (in_array($context, $avatar_data['contexts'])) {
                $score += 10;
            }
            
            // Emotion matching
            if (in_array($emotion, $avatar_data['emotions'])) {
                $score += 5;
            }
            
            // Generic context bonus
            if (in_array('general', $avatar_data['contexts'])) {
                $score += 1;
            }
            
            if ($score > $best_score) {
                $best_score = $score;
                $best_avatar = $avatar_id;
            }
        }
        
        return $best_avatar;
    }
    
    /**
     * Get avatar data
     */
    private function get_avatar_data($avatar_id) {
        if (!isset($this->avatar_cache[$avatar_id])) {
            return $this->get_default_avatar();
        }
        
        $avatar = $this->avatar_cache[$avatar_id];
        
        return array(
            'id' => $avatar_id,
            'name' => $avatar['name'],
            'image_url' => $this->get_avatar_image_url($avatar['image']),
            'description' => $avatar['description'],
            'animation' => $this->get_avatar_animation()
        );
    }
    
    /**
     * Get avatar image URL
     */
    private function get_avatar_image_url($image_filename) {
        return CINEMABOTPRO_PLUGIN_URL . 'assets/images/avatars/' . $image_filename;
    }
    
    /**
     * Get avatar animation style
     */
    private function get_avatar_animation() {
        $animation_speed = get_option('cinemabotpro_animation_speed', 'medium');
        
        $animations = array(
            'slow' => array('duration' => '2s', 'easing' => 'ease-in-out'),
            'medium' => array('duration' => '1s', 'easing' => 'ease-in-out'),
            'fast' => array('duration' => '0.5s', 'easing' => 'ease-in-out')
        );
        
        return $animations[$animation_speed] ?? $animations['medium'];
    }
    
    /**
     * Get default avatar
     */
    private function get_default_avatar() {
        return array(
            'id' => 'friendly_guide',
            'name' => 'CinemaBot',
            'image_url' => CINEMABOTPRO_PLUGIN_URL . 'assets/images/avatars/friendly-guide.png',
            'description' => 'Your friendly movie and TV guide',
            'animation' => $this->get_avatar_animation()
        );
    }
    
    /**
     * Rotate avatar automatically
     */
    public function rotate_avatar() {
        if (!get_option('cinemabotpro_auto_avatar_rotation', 1)) {
            return;
        }
        
        $available_avatars = array_keys($this->avatar_cache);
        $current_index = array_search($this->current_avatar, $available_avatars);
        
        if ($current_index === false) {
            $current_index = 0;
        }
        
        $next_index = ($current_index + 1) % count($available_avatars);
        $this->current_avatar = $available_avatars[$next_index];
        
        // Update current avatar in session
        if (!session_id()) {
            session_start();
        }
        $_SESSION['cinemabotpro_current_avatar'] = $this->current_avatar;
    }
    
    /**
     * Handle AJAX request for avatar
     */
    public function handle_get_avatar_ajax() {
        if (!wp_verify_nonce($_POST['nonce'], 'cinemabotpro_nonce')) {
            wp_die('Security check failed');
        }
        
        $avatar_type = sanitize_text_field($_POST['type'] ?? 'current');
        
        switch ($avatar_type) {
            case 'random':
                $avatar_id = array_rand($this->avatar_cache);
                break;
            case 'contextual':
                $context = sanitize_text_field($_POST['context'] ?? '');
                $emotion = sanitize_text_field($_POST['emotion'] ?? '');
                $avatar_id = $this->find_best_avatar_match($context, $emotion);
                break;
            default:
                $avatar_id = $this->current_avatar ?: 'friendly_guide';
        }
        
        $avatar_data = $this->get_avatar_data($avatar_id);
        
        wp_send_json_success(array(
            'avatar' => $avatar_data,
            'available_avatars' => $this->get_available_avatars_list()
        ));
    }
    
    /**
     * Get list of available avatars
     */
    private function get_available_avatars_list() {
        $list = array();
        
        foreach ($this->avatar_cache as $avatar_id => $avatar_data) {
            $list[] = array(
                'id' => $avatar_id,
                'name' => $avatar_data['name'],
                'image_url' => $this->get_avatar_image_url($avatar_data['image']),
                'description' => $avatar_data['description']
            );
        }
        
        return $list;
    }
    
    /**
     * Get avatar by ID
     */
    public function get_avatar_by_id($avatar_id) {
        return $this->get_avatar_data($avatar_id);
    }
    
    /**
     * Get current avatar
     */
    public function get_current_avatar() {
        if (!$this->current_avatar) {
            if (!session_id()) {
                session_start();
            }
            $this->current_avatar = $_SESSION['cinemabotpro_current_avatar'] ?? 'friendly_guide';
        }
        
        return $this->get_avatar_data($this->current_avatar);
    }
    
    /**
     * Set current avatar
     */
    public function set_current_avatar($avatar_id) {
        if (isset($this->avatar_cache[$avatar_id])) {
            $this->current_avatar = $avatar_id;
            
            if (!session_id()) {
                session_start();
            }
            $_SESSION['cinemabotpro_current_avatar'] = $avatar_id;
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Get avatars by context
     */
    public function get_avatars_by_context($context) {
        $matching_avatars = array();
        
        foreach ($this->avatar_cache as $avatar_id => $avatar_data) {
            if (in_array($context, $avatar_data['contexts'])) {
                $matching_avatars[] = $this->get_avatar_data($avatar_id);
            }
        }
        
        return $matching_avatars;
    }
    
    /**
     * Update avatar cache
     */
    public function update_avatar_cache() {
        $this->initialize_avatars();
        return true;
    }
}