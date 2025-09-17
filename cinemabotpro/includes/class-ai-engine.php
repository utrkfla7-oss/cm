<?php
/**
 * CinemaBot Pro AI Engine
 * 
 * Handles AI-powered response generation, content analysis,
 * and smart recommendations using OpenAI or custom models.
 */

class CinemaBotPro_AI_Engine {
    
    private $api_key;
    private $model;
    private $max_tokens;
    private $temperature;
    private $api_endpoint;
    
    public function __construct() {
        $this->api_key = get_option('cinemabotpro_openai_api_key', '');
        $this->model = get_option('cinemabotpro_ai_model', 'gpt-3.5-turbo');
        $this->max_tokens = get_option('cinemabotpro_max_tokens', 1000);
        $this->temperature = get_option('cinemabotpro_temperature', 0.7);
        $this->api_endpoint = 'https://api.openai.com/v1/chat/completions';
    }
    
    /**
     * Generate AI response based on context
     */
    public function generate_response($context) {
        // If no API key, use fallback responses
        if (empty($this->api_key)) {
            return $this->generate_fallback_response($context);
        }
        
        try {
            // Prepare system prompt
            $system_prompt = $this->build_system_prompt($context);
            
            // Prepare conversation history
            $messages = $this->build_message_history($context, $system_prompt);
            
            // Make API request
            $response = $this->make_api_request($messages);
            
            if ($response && isset($response['choices'][0]['message']['content'])) {
                $ai_response = trim($response['choices'][0]['message']['content']);
                
                // Post-process response
                $ai_response = $this->post_process_response($ai_response, $context);
                
                return $ai_response;
            }
            
        } catch (Exception $e) {
            error_log('CinemaBot Pro AI Error: ' . $e->getMessage());
        }
        
        // Fallback to rule-based response
        return $this->generate_fallback_response($context);
    }
    
    /**
     * Build system prompt based on context
     */
    private function build_system_prompt($context) {
        $language = $context['language'] ?? 'en';
        $user_context = $context['memory'] ?? array();
        
        $system_prompts = array(
            'en' => "You are CinemaBot Pro, an expert AI assistant specializing in movies and TV shows. You are knowledgeable, friendly, and passionate about cinema. You provide detailed, accurate information about movies, TV shows, actors, directors, and entertainment industry trends. You can recommend content based on user preferences, explain plots without major spoilers unless requested, discuss ratings and reviews, and help users discover new content. Always be enthusiastic but not overwhelming, and tailor your responses to the user's apparent interests and experience level.",
            
            'bn' => "আপনি সিনেমাবট প্রো, চলচ্চিত্র এবং টিভি শোগুলিতে বিশেষজ্ঞ একটি AI সহায়ক। আপনি জ্ঞানী, বন্ধুত্বপূর্ণ এবং সিনেমার প্রতি আবেগপ্রবণ। আপনি চলচ্চিত্র, টিভি শো, অভিনেতা, পরিচালক এবং বিনোদন শিল্পের প্রবণতা সম্পর্কে বিস্তারিত, নির্ভুল তথ্য প্রদান করেন। আপনি ব্যবহারকারীদের পছন্দের ভিত্তিতে কন্টেন্ট সুপারিশ করতে পারেন, প্রধান স্পয়লার ছাড়াই প্লট ব্যাখ্যা করতে পারেন এবং নতুন কন্টেন্ট আবিষ্কার করতে সাহায্য করতে পারেন।",
            
            'hi' => "आप सिनेमाबॉट प्रो हैं, फिल्मों और टीवी शो में विशेषज्ञ एक AI सहायक। आप जानकार, मित्रवत और सिनेमा के प्रति उत्साही हैं। आप फिल्मों, टीवी शो, अभिनेताओं, निर्देशकों और मनोरंजन उद्योग के रुझानों के बारे में विस्तृत, सटीक जानकारी प्रदान करते हैं। आप उपयोगकर्ता की प्राथमिकताओं के आधार पर सामग्री की सिफारिश कर सकते हैं, बिना बड़े स्पॉयलर के प्लॉट समझा सकते हैं और नई सामग्री खोजने में मदद कर सकते हैं।",
            
            'banglish' => "Apni CinemaBot Pro, movie ar TV show er expert ekta AI assistant. Apni knowledgeable, friendly ar cinema niye passionate. Apni movie, TV show, actor, director ar entertainment industry er trend niye detailed, accurate information diten. Apni user er preference onujayi content recommend korte paren, major spoiler chara plot explain korte paren ar notun content discover korte help korte paren."
        );
        
        $base_prompt = $system_prompts[$language] ?? $system_prompts['en'];
        
        // Add user context if available
        if (!empty($user_context)) {
            $context_info = $this->format_user_context($user_context, $language);
            $base_prompt .= "\n\n" . $context_info;
        }
        
        // Add specific guidelines
        $guidelines = $this->get_response_guidelines($language);
        $base_prompt .= "\n\n" . $guidelines;
        
        return $base_prompt;
    }
    
    /**
     * Format user context for AI
     */
    private function format_user_context($context, $language) {
        $context_text = '';
        
        $context_labels = array(
            'en' => array(
                'preferences' => 'User preferences:',
                'history' => 'Recent activity:',
                'favorites' => 'Favorite content:',
                'interests' => 'Main interests:'
            ),
            'bn' => array(
                'preferences' => 'ব্যবহারকারীর পছন্দ:',
                'history' => 'সাম্প্রতিক কার্যকলাপ:',
                'favorites' => 'প্রিয় বিষয়বস্তু:',
                'interests' => 'প্রধান আগ্রহ:'
            ),
            'hi' => array(
                'preferences' => 'उपयोगकर्ता की प्राथমिकताएं:',
                'history' => 'हाल की गतिविधि:',
                'favorites' => 'पसंदीदा सामग্री:',
                'interests' => 'मुख्य रुचियां:'
            ),
            'banglish' => array(
                'preferences' => 'User er preference:',
                'history' => 'Recent activity:',
                'favorites' => 'Favorite content:',
                'interests' => 'Main interest:'
            )
        );
        
        $labels = $context_labels[$language] ?? $context_labels['en'];
        
        // Add favorite genres
        if (!empty($context['favorite_genres'])) {
            $context_text .= $labels['interests'] . ' ' . implode(', ', $context['favorite_genres']) . "\n";
        }
        
        // Add content preferences
        if (!empty($context['content_preferences'])) {
            $prefs = $context['content_preferences'];
            if (!empty($prefs['preferred_content_type'])) {
                $context_text .= $labels['preferences'] . ' ' . $prefs['preferred_content_type'] . "\n";
            }
        }
        
        // Add recent viewing
        if (!empty($context['recent_viewing']['total_items'])) {
            $context_text .= $labels['history'] . ' ' . $context['recent_viewing']['total_items'] . " items\n";
        }
        
        return $context_text;
    }
    
    /**
     * Get response guidelines for specific language
     */
    private function get_response_guidelines($language) {
        $guidelines = array(
            'en' => "Guidelines:\n- Keep responses conversational and engaging\n- Provide specific examples when possible\n- Ask follow-up questions to understand preferences better\n- Suggest 2-3 recommendations when appropriate\n- Use emojis sparingly but effectively\n- Be culturally sensitive and inclusive",
            
            'bn' => "নির্দেশনা:\n- উত্তরগুলি কথোপকথনমূলক এবং আকর্ষক রাখুন\n- সম্ভাব্য নির্দিষ্ট উদাহরণ প্রদান করুন\n- পছন্দগুলি ভালোভাবে বোঝার জন্য ফলো-আপ প্রশ্ন করুন\n- উপযুক্ত হলে ২-৩টি সুপারিশ দিন\n- ইমোজি সংযমে কিন্তু কার্যকরভাবে ব্যবহার করুন",
            
            'hi' => "दिशानिर्देश:\n- उत्तरों को संवादात्मक और आकर्षक रखें\n- संभव होने पर विशिष्ट उदाहरण प्रदान करें\n- प्राथमिकताओं को बेहतर समझने के लिए अनुवर्ती प्रश्न पूछें\n- उपयुक्त होने पर 2-3 सिफारिशें सुझाएं\n- इमोजी का संयमित लेकिन प्रभावी उपयोग करें",
            
            'banglish' => "Guidelines:\n- Response gulo conversational ar engaging rakhben\n- Jokhn possible specific example diben\n- Preference gulo valo moto bujhar jonno follow-up question korben\n- Appropriate hole 2-3 ta recommendation diben\n- Emoji carefully kintu effectively use korben"
        );
        
        return $guidelines[$language] ?? $guidelines['en'];
    }
    
    /**
     * Build message history for API request
     */
    private function build_message_history($context, $system_prompt) {
        $messages = array(
            array(
                'role' => 'system',
                'content' => $system_prompt
            )
        );
        
        // Add conversation history
        if (!empty($context['conversation_history'])) {
            foreach ($context['conversation_history'] as $chat) {
                $messages[] = array(
                    'role' => 'user',
                    'content' => $chat->message
                );
                $messages[] = array(
                    'role' => 'assistant',
                    'content' => $chat->response
                );
            }
        }
        
        // Add current message
        $messages[] = array(
            'role' => 'user',
            'content' => $context['message']
        );
        
        return $messages;
    }
    
    /**
     * Make API request to OpenAI
     */
    private function make_api_request($messages) {
        $headers = array(
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json'
        );
        
        $body = array(
            'model' => $this->model,
            'messages' => $messages,
            'max_tokens' => $this->max_tokens,
            'temperature' => $this->temperature,
            'top_p' => 1,
            'frequency_penalty' => 0,
            'presence_penalty' => 0
        );
        
        $response = wp_remote_post($this->api_endpoint, array(
            'headers' => $headers,
            'body' => wp_json_encode($body),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            throw new Exception('API request failed: ' . $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            throw new Exception('API returned error code: ' . $response_code);
        }
        
        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }
    
    /**
     * Post-process AI response
     */
    private function post_process_response($response, $context) {
        $language = $context['language'] ?? 'en';
        
        // Clean up response
        $response = trim($response);
        
        // Add language-specific formatting
        $response = $this->add_language_specific_formatting($response, $language);
        
        // Add contextual enhancements
        $response = $this->add_contextual_enhancements($response, $context);
        
        // Ensure appropriate length
        $response = $this->ensure_appropriate_length($response);
        
        return $response;
    }
    
    /**
     * Add language-specific formatting
     */
    private function add_language_specific_formatting($response, $language) {
        switch ($language) {
            case 'bn':
                // Add Bengali-specific punctuation and spacing
                $response = str_replace('. ', '। ', $response);
                $response = str_replace('? ', '? ', $response);
                break;
                
            case 'hi':
                // Add Hindi-specific formatting
                $response = str_replace('. ', '। ', $response);
                break;
                
            case 'banglish':
                // Keep English punctuation but add Banglish expressions
                if (rand(1, 3) === 1) {
                    $expressions = array(' vai', ' bro', ' dude');
                    $response .= $expressions[array_rand($expressions)];
                }
                break;
        }
        
        return $response;
    }
    
    /**
     * Add contextual enhancements
     */
    private function add_contextual_enhancements($response, $context) {
        $intent = $context['intent'] ?? 'general';
        
        // Add intent-specific enhancements
        switch ($intent) {
            case 'recommend':
                if (strpos($response, 'recommend') === false && strpos($response, 'suggest') === false) {
                    $response = "Based on your interests, " . $response;
                }
                break;
                
            case 'search':
                if (strpos($response, 'found') === false && strpos($response, 'here') === false) {
                    $response = "Here's what I found: " . $response;
                }
                break;
                
            case 'info':
                if (strpos($response, 'about') === false) {
                    $response = "Let me tell you about that: " . $response;
                }
                break;
        }
        
        return $response;
    }
    
    /**
     * Ensure appropriate response length
     */
    private function ensure_appropriate_length($response) {
        $max_length = get_option('cinemabotpro_max_response_length', 800);
        
        if (strlen($response) > $max_length) {
            // Truncate at the last complete sentence before the limit
            $truncated = substr($response, 0, $max_length);
            $last_period = strrpos($truncated, '.');
            $last_exclamation = strrpos($truncated, '!');
            $last_question = strrpos($truncated, '?');
            
            $last_sentence_end = max($last_period, $last_exclamation, $last_question);
            
            if ($last_sentence_end !== false && $last_sentence_end > $max_length * 0.7) {
                $response = substr($truncated, 0, $last_sentence_end + 1);
            } else {
                $response = $truncated . '...';
            }
        }
        
        return $response;
    }
    
    /**
     * Generate fallback response when AI is not available
     */
    private function generate_fallback_response($context) {
        $language = $context['language'] ?? 'en';
        $intent = $context['intent'] ?? 'general';
        $message = strtolower($context['message'] ?? '');
        
        // Load fallback responses
        $fallback_responses = $this->get_fallback_responses($language);
        
        // Determine response category
        $category = $this->determine_fallback_category($intent, $message);
        
        // Get appropriate response
        if (isset($fallback_responses[$category])) {
            $responses = $fallback_responses[$category];
            $response = $responses[array_rand($responses)];
            
            // Personalize response if possible
            $response = $this->personalize_fallback_response($response, $context);
            
            return $response;
        }
        
        // Default response
        return $fallback_responses['default'][0];
    }
    
    /**
     * Get fallback responses for each language
     */
    private function get_fallback_responses($language) {
        $responses = array(
            'en' => array(
                'greeting' => array(
                    "Hello! I'm CinemaBot Pro, your movie and TV guide. What would you like to know about today? 🎬",
                    "Hi there! Ready to explore the world of movies and TV shows? What are you in the mood for?",
                    "Welcome to CinemaBot Pro! I'm here to help you discover amazing content. What interests you?"
                ),
                'search' => array(
                    "I'd love to help you find something great to watch! Could you tell me more about what you're looking for?",
                    "Let me help you discover some fantastic content! What genre or type of show interests you?",
                    "I'm ready to search for the perfect movie or show for you! What are your preferences?"
                ),
                'recommend' => array(
                    "I'd be happy to recommend something perfect for you! What genres do you usually enjoy?",
                    "Let me suggest some great options! Are you in the mood for something specific?",
                    "I have some fantastic recommendations in mind! What type of content are you looking for?"
                ),
                'info' => array(
                    "I'd love to share information about that! Could you be more specific about what you'd like to know?",
                    "There's so much to discuss about movies and TV! What particular aspect interests you?",
                    "I'm excited to tell you more! What specific information are you looking for?"
                ),
                'default' => array(
                    "I'm CinemaBot Pro, and I love talking about movies and TV shows! How can I help you today? 🎭"
                )
            ),
            
            'bn' => array(
                'greeting' => array(
                    "হ্যালো! আমি সিনেমাবট প্রো, আপনার চলচ্চিত্র এবং টিভি গাইড। আজ কী জানতে চান? 🎬",
                    "নমস্কার! চলচ্চিত্র এবং টিভি শোর জগত আবিষ্কার করতে প্রস্তুত? কোন মুডে আছেন?",
                    "সিনেমাবট প্রোতে স্বাগতম! আমি আপনাকে দুর্দান্ত কন্টেন্ট আবিষ্কার করতে সাহায্য করতে এসেছি।"
                ),
                'search' => array(
                    "আমি আপনাকে দেখার জন্য দুর্দান্ত কিছু খুঁজে দিতে সাহায্য করতে পারি! আরও বলুন কী খুঁজছেন?",
                    "চলুন দারুণ কন্টেন্ট আবিষ্কার করি! কোন ধরনের বা জানরের শো আপনার আগ্রহের?",
                    "আপনার জন্য নিখুঁত সিনেমা বা শো খুঁজতে প্রস্তুত! আপনার পছন্দ কী?"
                ),
                'recommend' => array(
                    "আপনার জন্য নিখুঁত কিছু সুপারিশ করতে খুশি হব! সাধারণত কোন জানরা উপভোগ করেন?",
                    "চমৎকার কিছু বিকল্প সুপারিশ করি! কোন নির্দিষ্ট কিছুর মুডে আছেন?",
                    "আমার মনে দারুণ কিছু সুপারিশ আছে! কোন ধরনের কন্টেন্ট খুঁজছেন?"
                ),
                'info' => array(
                    "সে সম্পর্কে তথ্য শেয়ার করতে ভালোবাসব! আরও নির্দিষ্ট করে বলুন কী জানতে চান?",
                    "চলচ্চিত্র এবং টিভি নিয়ে অনেক কিছু আলোচনা করার আছে! কোন দিকটি আপনার আগ্রহের?",
                    "আরও বলতে উৎসাহী! কোন নির্দিষ্ট তথ্য খুঁজছেন?"
                ),
                'default' => array(
                    "আমি সিনেমাবট প্রো, এবং চলচ্চিত্র ও টিভি শো নিয়ে কথা বলতে ভালোবাসি! আজ কীভাবে সাহায্য করতে পারি? 🎭"
                )
            ),
            
            'hi' => array(
                'greeting' => array(
                    "नमस्ते! मैं सिनेमाबॉट प्रो हूं, आपका फिल्म और टीवी गाइड। आज आप क्या जानना चाहेंगे? 🎬",
                    "हैलो! फिल्मों और टीवी शो की दुनिया एक्सप्लोर करने के लिए तैयार हैं? आपका मूड कैसा है?",
                    "सिनेमाबॉट प्रो में आपका स्वागत है! मैं आपको बेहतरीन कंटेंट खोजने में मदद करने के लिए यहां हूं।"
                ),
                'search' => array(
                    "मैं आपको देखने के लिए कुछ बेहतरीन खोजने में मदद करना चाहूंगा! आप क्या खोज रहे हैं, इसके बारे में और बताएं?",
                    "चलिए कुछ शानदार कंटेंट खोजते हैं! आपको कौन सा जॉनर या टाइप दिलचस्प लगता है?",
                    "मैं आपके लिए परफेक्ट फिल्म या शो खोजने के लिए तैयार हूं! आपकी पसंद क्या है?"
                ),
                'recommend' => array(
                    "आपके लिए कुछ परफेक्ट सुझाने में खुशी होगी! आप आमतौर पर कौन से जॉनर पसंद करते हैं?",
                    "कुछ बेहतरीन विकल्प सुझाता हूं! आप किसी खास चीज़ के मूड में हैं?",
                    "मेरे पास कुछ शानदार सुझाव हैं! आप किस तरह का कंटेंट खोज रहे हैं?"
                ),
                'info' => array(
                    "उसके बारे में जानकारी साझा करना पसंद करूंगा! आप और स्पेसिफिक बता सकते हैं कि क्या जानना चाहते हैं?",
                    "फिल्मों और टीवी के बारे में बहुत कुछ चर्चा करने को है! कौन सा पहलू आपको दिलचस्प लगता है?",
                    "और बताने के लिए उत्साहित हूं! आप कौन सी स्पेसिफिक जानकारी खोज रहे हैं?"
                ),
                'default' => array(
                    "मैं सिनेमाबॉट प्रो हूं, और मुझे फिल्मों और टीवी शो के बारे में बात करना पसंद है! आज मैं आपकी कैसे मदद कर सकता हूं? 🎭"
                )
            ),
            
            'banglish' => array(
                'greeting' => array(
                    "Hello! Ami CinemaBot Pro, apnar movie ar TV guide. Aj ki jante chan? 🎬",
                    "Hi! Movie ar TV show er duniya explore korte ready? Apnar mood kemon?",
                    "CinemaBot Pro te welcome! Ami apnake awesome content discover korte help korte eshechi."
                ),
                'search' => array(
                    "Ami apnake dekhaar jonno great kichu khuje dite help korte chai! Aro bolen ki khujchen?",
                    "Cholo fantastic content discover kori! Kon dhon er ba genre er show apnar interesting?",
                    "Apnar jonno perfect movie ba show khujte ready! Apnar preference ki?"
                ),
                'recommend' => array(
                    "Apnar jonno perfect kichu recommend korte khushi hobo! Normally kon genre gulo enjoy koren?",
                    "Great kichu option suggest kori! Kono specific kichu er mood e achen?",
                    "Amar mone fantastic kichu recommendation ache! Kon type er content khujchen?"
                ),
                'info' => array(
                    "Shetar bepare info share korte valobasbo! Aro specific kore bolen ki jante chan?",
                    "Movie ar TV niye onek kichu discuss korar ache! Kon aspect ta apnar interesting?",
                    "Aro bolte excited! Kon specific information khujchen?"
                ),
                'default' => array(
                    "Ami CinemaBot Pro, ar movie TV show niye kotha bolte valobaashi! Aj kivabe help korte pari? 🎭"
                )
            )
        );
        
        return $responses[$language] ?? $responses['en'];
    }
    
    /**
     * Determine fallback response category
     */
    private function determine_fallback_category($intent, $message) {
        // Map intents to categories
        $intent_map = array(
            'greeting' => 'greeting',
            'search' => 'search',
            'recommend' => 'recommend',
            'info' => 'info',
            'rating' => 'info',
            'similar' => 'recommend'
        );
        
        if (isset($intent_map[$intent])) {
            return $intent_map[$intent];
        }
        
        // Check message content for additional clues
        if (preg_match('/\b(hello|hi|hey|good morning|good evening|namaste|assalam)\b/i', $message)) {
            return 'greeting';
        }
        
        if (preg_match('/\b(find|search|look for|show me|khuje|khoj)\b/i', $message)) {
            return 'search';
        }
        
        if (preg_match('/\b(recommend|suggest|what should|suparish|suggest koro)\b/i', $message)) {
            return 'recommend';
        }
        
        if (preg_match('/\b(tell me|information|about|details|bolun|janan)\b/i', $message)) {
            return 'info';
        }
        
        return 'default';
    }
    
    /**
     * Personalize fallback response
     */
    private function personalize_fallback_response($response, $context) {
        // Add user's name if available
        $user_id = get_current_user_id();
        if ($user_id) {
            $user = get_userdata($user_id);
            if ($user && $user->display_name) {
                $response = str_replace('!', ', ' . $user->display_name . '!', $response);
            }
        }
        
        // Add context-specific personalization
        if (!empty($context['memory']['favorite_genres'])) {
            $favorite_genre = $context['memory']['favorite_genres'][0];
            $response .= " I notice you enjoy " . $favorite_genre . " content!";
        }
        
        return $response;
    }
    
    /**
     * Analyze content for recommendations
     */
    public function analyze_content_for_recommendations($content_data, $user_preferences = array()) {
        // This would typically use ML models, but for now we'll use rule-based analysis
        $analysis = array(
            'genre_match_score' => 0,
            'style_match_score' => 0,
            'decade_match_score' => 0,
            'rating_match_score' => 0,
            'overall_score' => 0,
            'reasons' => array()
        );
        
        // Genre matching
        if (!empty($user_preferences['favorite_genres']) && !empty($content_data['genres'])) {
            $user_genres = $user_preferences['favorite_genres'];
            $content_genres = $content_data['genres'];
            
            $genre_overlap = array_intersect($user_genres, $content_genres);
            $analysis['genre_match_score'] = count($genre_overlap) / count($user_genres) * 100;
            
            if ($analysis['genre_match_score'] > 0) {
                $analysis['reasons'][] = 'Matches your favorite genres: ' . implode(', ', $genre_overlap);
            }
        }
        
        // Rating matching
        if (!empty($user_preferences['preferred_rating']) && !empty($content_data['rating'])) {
            $user_min_rating = floatval($user_preferences['preferred_rating']);
            $content_rating = floatval($content_data['rating']);
            
            if ($content_rating >= $user_min_rating) {
                $analysis['rating_match_score'] = min(100, ($content_rating / 10) * 100);
                $analysis['reasons'][] = 'High rating (' . $content_rating . '/10)';
            }
        }
        
        // Calculate overall score
        $analysis['overall_score'] = (
            $analysis['genre_match_score'] * 0.4 +
            $analysis['rating_match_score'] * 0.3 +
            $analysis['style_match_score'] * 0.2 +
            $analysis['decade_match_score'] * 0.1
        );
        
        return $analysis;
    }
    
    /**
     * Generate content summary
     */
    public function generate_content_summary($content_data, $language = 'en') {
        $templates = array(
            'en' => "{title} ({year}) is a {genre} {type} {rating_text}. {plot_summary}",
            'bn' => "{title} ({year}) একটি {genre} {type} {rating_text}। {plot_summary}",
            'hi' => "{title} ({year}) एक {genre} {type} है {rating_text}। {plot_summary}",
            'banglish' => "{title} ({year}) ekta {genre} {type} {rating_text}. {plot_summary}"
        );
        
        $template = $templates[$language] ?? $templates['en'];
        
        $rating_text = '';
        if (!empty($content_data['rating'])) {
            $rating_templates = array(
                'en' => "with a {rating}/10 rating",
                'bn' => "{rating}/10 রেটিং সহ",
                'hi' => "{rating}/10 रेटिंग के साथ",
                'banglish' => "{rating}/10 rating shoho"
            );
            $rating_text = str_replace('{rating}', $content_data['rating'], $rating_templates[$language] ?? $rating_templates['en']);
        }
        
        $summary = str_replace(
            array('{title}', '{year}', '{genre}', '{type}', '{rating_text}', '{plot_summary}'),
            array(
                $content_data['title'] ?? 'Unknown',
                $content_data['year'] ?? 'Unknown',
                implode(', ', $content_data['genres'] ?? array('Unknown')),
                $content_data['type'] ?? 'content',
                $rating_text,
                $content_data['plot'] ?? 'No plot available.'
            ),
            $template
        );
        
        return $summary;
    }
    
    /**
     * Check if AI service is available
     */
    public function is_ai_available() {
        return !empty($this->api_key);
    }
    
    /**
     * Get AI model status
     */
    public function get_ai_status() {
        return array(
            'available' => $this->is_ai_available(),
            'model' => $this->model,
            'max_tokens' => $this->max_tokens,
            'temperature' => $this->temperature
        );
    }
}