/**
 * CinemaBot Pro - Chatbot JavaScript
 * Handles multilingual AI chatbot functionality
 */

(function($) {
    'use strict';

    let currentLanguage = 'en';
    let chatHistory = [];
    let isTyping = false;
    let avatarRotationEnabled = true;
    let currentAvatar = 1;
    let avatarRotationInterval;

    // Initialize chatbot
    $(document).ready(function() {
        initializeChatbot();
        setupEventListeners();
        startAvatarRotation();
        loadChatHistory();
        detectUserLanguage();
    });

    function initializeChatbot() {
        // Auto-expand chatbot on first visit
        if (!localStorage.getItem('cinemabotpro_visited')) {
            setTimeout(() => {
                $('#cinemabotpro-chat-container').addClass('expanded');
                showWelcomeMessage();
                localStorage.setItem('cinemabotpro_visited', 'true');
            }, 1000);
        }
    }

    function setupEventListeners() {
        // Toggle chatbot
        $(document).on('click', '.cinemabotpro-chat-toggle', function() {
            $('#cinemabotpro-chat-container').toggleClass('expanded');
        });

        // Send message on Enter
        $(document).on('keypress', '#cinemabotpro-message-input', function(e) {
            if (e.which === 13 && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        // Send button click
        $(document).on('click', '.cinemabotpro-send-btn', sendMessage);

        // Language selector
        $(document).on('change', '.cinemabotpro-language-select', function() {
            changeLanguage($(this).val());
        });

        // Quick actions
        $(document).on('click', '.cinemabotpro-quick-action', function() {
            const action = $(this).data('action');
            handleQuickAction(action);
        });

        // Clear chat
        $(document).on('click', '.cinemabotpro-clear-chat', clearChat);

        // Toggle avatar rotation
        $(document).on('click', '.cinemabotpro-toggle-avatar', toggleAvatarRotation);
    }

    function sendMessage() {
        const input = $('#cinemabotpro-message-input');
        const message = input.val().trim();
        
        if (!message || isTyping) return;

        // Add user message to chat
        addMessageToChat('user', message);
        input.val('');

        // Detect language if auto-detection is enabled
        const detectedLang = detectLanguage(message);
        if (detectedLang !== currentLanguage) {
            changeLanguage(detectedLang);
        }

        // Show typing indicator
        showTypingIndicator();

        // Send to AI engine
        $.ajax({
            url: cinemabotpro_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'cinemabotpro_chat',
                message: message,
                language: currentLanguage,
                nonce: cinemabotpro_ajax.nonce,
                history: JSON.stringify(chatHistory.slice(-10)) // Last 10 messages for context
            },
            success: function(response) {
                hideTypingIndicator();
                
                if (response.success) {
                    addMessageToChat('bot', response.data.message, response.data.suggestions);
                    
                    // Update user memory if provided
                    if (response.data.memory_update) {
                        updateUserMemory(response.data.memory_update);
                    }
                } else {
                    addMessageToChat('bot', getErrorMessage());
                }
            },
            error: function() {
                hideTypingIndicator();
                addMessageToChat('bot', getErrorMessage());
            }
        });
    }

    function addMessageToChat(sender, message, suggestions = null) {
        const chatMessages = $('.cinemabotpro-chat-messages');
        const timestamp = new Date().toLocaleTimeString();
        
        let avatarHtml = '';
        if (sender === 'bot') {
            avatarHtml = `<div class="cinemabotpro-avatar">
                <img src="${cinemabotpro_ajax.plugin_url}assets/images/avatars/avatar-${currentAvatar}.png" 
                     alt="CinemaBot" class="cinemabotpro-avatar-img">
            </div>`;
        }

        const messageHtml = `
            <div class="cinemabotpro-message cinemabotpro-message-${sender}" data-timestamp="${timestamp}">
                ${avatarHtml}
                <div class="cinemabotpro-message-content">
                    <div class="cinemabotpro-message-text">${message}</div>
                    <div class="cinemabotpro-message-time">${timestamp}</div>
                    ${suggestions ? generateSuggestionsHtml(suggestions) : ''}
                </div>
            </div>
        `;

        chatMessages.append(messageHtml);
        chatMessages.scrollTop(chatMessages[0].scrollHeight);

        // Add to chat history
        chatHistory.push({
            sender: sender,
            message: message,
            timestamp: Date.now(),
            language: currentLanguage
        });

        // Limit chat history to 100 messages
        if (chatHistory.length > 100) {
            chatHistory = chatHistory.slice(-100);
        }

        // Save to localStorage
        saveChatHistory();

        // Rotate avatar for bot messages
        if (sender === 'bot' && avatarRotationEnabled) {
            rotateAvatar();
        }
    }

    function generateSuggestionsHtml(suggestions) {
        if (!suggestions || suggestions.length === 0) return '';

        let html = '<div class="cinemabotpro-suggestions">';
        suggestions.forEach(suggestion => {
            html += `<button class="cinemabotpro-suggestion-btn" data-suggestion="${suggestion}">${suggestion}</button>`;
        });
        html += '</div>';

        return html;
    }

    function showTypingIndicator() {
        isTyping = true;
        const typingHtml = `
            <div class="cinemabotpro-message cinemabotpro-message-bot cinemabotpro-typing">
                <div class="cinemabotpro-avatar">
                    <img src="${cinemabotpro_ajax.plugin_url}assets/images/avatars/avatar-${currentAvatar}.png" 
                         alt="CinemaBot" class="cinemabotpro-avatar-img">
                </div>
                <div class="cinemabotpro-message-content">
                    <div class="cinemabotpro-typing-dots">
                        <span></span><span></span><span></span>
                    </div>
                </div>
            </div>
        `;
        
        $('.cinemabotpro-chat-messages').append(typingHtml);
        $('.cinemabotpro-chat-messages').scrollTop($('.cinemabotpro-chat-messages')[0].scrollHeight);
    }

    function hideTypingIndicator() {
        isTyping = false;
        $('.cinemabotpro-typing').remove();
    }

    function changeLanguage(lang) {
        currentLanguage = lang;
        $('.cinemabotpro-language-select').val(lang);
        
        // Update UI text based on language
        updateUILanguage(lang);
        
        // Save preference
        localStorage.setItem('cinemabotpro_language', lang);
    }

    function updateUILanguage(lang) {
        const translations = {
            'en': {
                placeholder: 'Type your message...',
                send: 'Send',
                clear: 'Clear Chat',
                welcome: 'Hi! I\'m CinemaBot Pro. Ask me about movies, TV shows, or get personalized recommendations!'
            },
            'bn': {
                placeholder: 'আপনার বার্তা টাইপ করুন...',
                send: 'পাঠান',
                clear: 'চ্যাট পরিষ্কার করুন',
                welcome: 'হাই! আমি সিনেমাবট প্রো। আমাকে সিনেমা, টিভি শো সম্পর্কে জিজ্ঞাসা করুন বা ব্যক্তিগত সুপারিশ পান!'
            },
            'hi': {
                placeholder: 'अपना संदेश टाइप करें...',
                send: 'भेजें',
                clear: 'चैट साफ़ करें',
                welcome: 'हाय! मैं सिनेमाबॉट प्रो हूँ। मुझसे फिल्मों, टीवी शो के बारे में पूछें या व्यक्तिगत सिफारिशें पाएं!'
            },
            'banglish': {
                placeholder: 'Apnar message type korun...',
                send: 'Pathao',
                clear: 'Chat clear koro',
                welcome: 'Hi! Ami CinemaBot Pro. Amake cinema, TV show niye jigges koro ba personal recommendation nao!'
            }
        };

        const t = translations[lang] || translations['en'];
        
        $('#cinemabotpro-message-input').attr('placeholder', t.placeholder);
        $('.cinemabotpro-send-btn').text(t.send);
        $('.cinemabotpro-clear-chat').text(t.clear);
    }

    function detectLanguage(text) {
        // Simple language detection based on script and common words
        const bengaliPattern = /[\u0980-\u09FF]/;
        const hindiPattern = /[\u0900-\u097F]/;
        
        if (bengaliPattern.test(text)) {
            return 'bn';
        } else if (hindiPattern.test(text)) {
            return 'hi';
        } else if (containsBanglishWords(text)) {
            return 'banglish';
        } else {
            return 'en';
        }
    }

    function containsBanglishWords(text) {
        const banglishWords = ['ami', 'tumi', 'apni', 'kemon', 'achen', 'khabar', 'cinema', 'movie', 'dekhi', 'bhalo'];
        const words = text.toLowerCase().split(/\s+/);
        return banglishWords.some(word => words.includes(word));
    }

    function detectUserLanguage() {
        // Check saved preference first
        const savedLang = localStorage.getItem('cinemabotpro_language');
        if (savedLang) {
            changeLanguage(savedLang);
            return;
        }

        // Detect from browser
        const browserLang = navigator.language || navigator.userLanguage;
        if (browserLang.startsWith('bn')) {
            changeLanguage('bn');
        } else if (browserLang.startsWith('hi')) {
            changeLanguage('hi');
        } else {
            changeLanguage('en');
        }
    }

    function handleQuickAction(action) {
        const quickMessages = {
            'popular_movies': {
                'en': 'Show me popular movies',
                'bn': 'জনপ্রিয় সিনেমা দেখান',
                'hi': 'लोकप्रिय फिल्में दिखाएं',
                'banglish': 'Popular cinema gulo dekhan'
            },
            'recommendations': {
                'en': 'Give me movie recommendations',
                'bn': 'আমাকে সিনেমার সুপারিশ দিন',
                'hi': 'मुझे फिल्म की सिफारिशें दें',
                'banglish': 'Amake cinema recommend korun'
            },
            'new_releases': {
                'en': 'What are the new releases?',
                'bn': 'নতুন রিলিজ কি কি?',
                'hi': 'नई रिलीज़ क्या हैं?',
                'banglish': 'Notun release gulo ki ki?'
            }
        };

        const message = quickMessages[action][currentLanguage] || quickMessages[action]['en'];
        $('#cinemabotpro-message-input').val(message);
        sendMessage();
    }

    function startAvatarRotation() {
        avatarRotationInterval = setInterval(() => {
            if (avatarRotationEnabled) {
                rotateAvatar();
            }
        }, 30000); // Rotate every 30 seconds
    }

    function rotateAvatar() {
        currentAvatar = (currentAvatar % 50) + 1; // Cycle through 50 avatars
        $('.cinemabotpro-chat-toggle .cinemabotpro-avatar-img').attr('src', 
            `${cinemabotpro_ajax.plugin_url}assets/images/avatars/avatar-${currentAvatar}.png`);
    }

    function toggleAvatarRotation() {
        avatarRotationEnabled = !avatarRotationEnabled;
        localStorage.setItem('cinemabotpro_avatar_rotation', avatarRotationEnabled);
    }

    function clearChat() {
        if (confirm('Are you sure you want to clear the chat history?')) {
            $('.cinemabotpro-chat-messages').empty();
            chatHistory = [];
            localStorage.removeItem('cinemabotpro_chat_history');
            showWelcomeMessage();
        }
    }

    function showWelcomeMessage() {
        setTimeout(() => {
            const welcomeMessages = {
                'en': 'Hi! I\'m CinemaBot Pro. Ask me about movies, TV shows, or get personalized recommendations!',
                'bn': 'হাই! আমি সিনেমাবট প্রো। আমাকে সিনেমা, টিভি শো সম্পর্কে জিজ্ঞাসা করুন বা ব্যক্তিগত সুপারিশ পান!',
                'hi': 'हाय! मैं सिनेमाबॉट प्रो हूँ। मुझसे फिल्मों, टीवी शो के बारे में पूछें या व्यक्तिगत सिफारिशें पाएं!',
                'banglish': 'Hi! Ami CinemaBot Pro. Amake cinema, TV show niye jigges koro ba personal recommendation nao!'
            };

            const suggestions = [
                'Popular movies',
                'New releases', 
                'TV shows',
                'Recommendations'
            ];

            addMessageToChat('bot', welcomeMessages[currentLanguage], suggestions);
        }, 500);
    }

    function saveChatHistory() {
        localStorage.setItem('cinemabotpro_chat_history', JSON.stringify(chatHistory));
    }

    function loadChatHistory() {
        const saved = localStorage.getItem('cinemabotpro_chat_history');
        if (saved) {
            chatHistory = JSON.parse(saved);
            
            // Restore last 20 messages
            const recentHistory = chatHistory.slice(-20);
            recentHistory.forEach(msg => {
                addMessageToChat(msg.sender, msg.message);
            });
        }

        // Show welcome message if no history
        if (chatHistory.length === 0) {
            showWelcomeMessage();
        }
    }

    function updateUserMemory(memoryData) {
        // Send memory update to server
        $.ajax({
            url: cinemabotpro_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'cinemabotpro_update_memory',
                memory_data: JSON.stringify(memoryData),
                nonce: cinemabotpro_ajax.nonce
            }
        });
    }

    function getErrorMessage() {
        const errorMessages = {
            'en': 'Sorry, I encountered an error. Please try again.',
            'bn': 'দুঃখিত, আমি একটি ত্রুটির সম্মুখীন হয়েছি। আবার চেষ্টা করুন।',
            'hi': 'क्षमा करें, मुझे एक त्रुटि का सामना करना पड़ा। कृपया पुनः प्रयास करें।',
            'banglish': 'Sorry, ami ekta error face korlam. Please abar try korun.'
        };

        return errorMessages[currentLanguage] || errorMessages['en'];
    }

    // Handle suggestion clicks
    $(document).on('click', '.cinemabotpro-suggestion-btn', function() {
        const suggestion = $(this).data('suggestion');
        $('#cinemabotpro-message-input').val(suggestion);
        sendMessage();
    });

    // Load avatar rotation preference
    const savedRotation = localStorage.getItem('cinemabotpro_avatar_rotation');
    if (savedRotation !== null) {
        avatarRotationEnabled = savedRotation === 'true';
    }

})(jQuery);