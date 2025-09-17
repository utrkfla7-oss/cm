<?php
/**
 * Chatbot Template
 * Displays the AI chatbot interface
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
$user_preferences = get_user_meta($current_user->ID, 'cinemabotpro_preferences', true);
$chat_enabled = get_option('cinemabotpro_chat_enabled', 1);

if (!$chat_enabled) {
    return;
}
?>

<div id="cinemabotpro-chat-container" class="cinemabotpro-chat-container">
    <!-- Chat Toggle Button -->
    <div class="cinemabotpro-chat-toggle">
        <div class="cinemabotpro-avatar">
            <img src="<?php echo CINEMABOTPRO_PLUGIN_URL; ?>assets/images/avatars/avatar-1.png" 
                 alt="CinemaBot Pro" class="cinemabotpro-avatar-img">
        </div>
        <div class="cinemabotpro-chat-pulse"></div>
    </div>

    <!-- Chat Interface -->
    <div class="cinemabotpro-chat-interface">
        <!-- Chat Header -->
        <div class="cinemabotpro-chat-header">
            <div class="cinemabotpro-chat-title">
                <h3>CinemaBot Pro</h3>
                <div class="cinemabotpro-chat-status">
                    <span class="cinemabotpro-status-indicator online"></span>
                    <span class="cinemabotpro-status-text"><?php _e('Online', 'cinemabotpro'); ?></span>
                </div>
            </div>
            
            <div class="cinemabotpro-chat-controls">
                <!-- Language Selector -->
                <select class="cinemabotpro-language-select">
                    <option value="en" <?php selected($user_preferences['language'] ?? 'en', 'en'); ?>>English</option>
                    <option value="bn" <?php selected($user_preferences['language'] ?? 'en', 'bn'); ?>>বাংলা</option>
                    <option value="hi" <?php selected($user_preferences['language'] ?? 'en', 'hi'); ?>>हिंदी</option>
                    <option value="banglish" <?php selected($user_preferences['language'] ?? 'en', 'banglish'); ?>>Banglish</option>
                </select>
                
                <!-- Settings Button -->
                <button class="cinemabotpro-settings-btn" title="<?php _e('Settings', 'cinemabotpro'); ?>">
                    <i class="fas fa-cog"></i>
                </button>
                
                <!-- Minimize Button -->
                <button class="cinemabotpro-minimize-btn" title="<?php _e('Minimize', 'cinemabotpro'); ?>">
                    <i class="fas fa-minus"></i>
                </button>
            </div>
        </div>

        <!-- Chat Messages Area -->
        <div class="cinemabotpro-chat-messages" id="cinemabotpro-chat-messages">
            <!-- Messages will be loaded here -->
        </div>

        <!-- Quick Actions -->
        <div class="cinemabotpro-quick-actions">
            <button class="cinemabotpro-quick-action" data-action="popular_movies">
                <i class="fas fa-fire"></i>
                <span><?php _e('Popular Movies', 'cinemabotpro'); ?></span>
            </button>
            <button class="cinemabotpro-quick-action" data-action="recommendations">
                <i class="fas fa-thumbs-up"></i>
                <span><?php _e('Recommendations', 'cinemabotpro'); ?></span>
            </button>
            <button class="cinemabotpro-quick-action" data-action="new_releases">
                <i class="fas fa-star"></i>
                <span><?php _e('New Releases', 'cinemabotpro'); ?></span>
            </button>
        </div>

        <!-- Chat Input Area -->
        <div class="cinemabotpro-chat-input-area">
            <div class="cinemabotpro-input-container">
                <textarea id="cinemabotpro-message-input" 
                         class="cinemabotpro-message-input" 
                         placeholder="<?php _e('Type your message...', 'cinemabotpro'); ?>"
                         rows="1"></textarea>
                
                <div class="cinemabotpro-input-actions">
                    <button class="cinemabotpro-attachment-btn" title="<?php _e('Attach File', 'cinemabotpro'); ?>">
                        <i class="fas fa-paperclip"></i>
                    </button>
                    
                    <button class="cinemabotpro-emoji-btn" title="<?php _e('Add Emoji', 'cinemabotpro'); ?>">
                        <i class="fas fa-smile"></i>
                    </button>
                    
                    <button class="cinemabotpro-send-btn" title="<?php _e('Send Message', 'cinemabotpro'); ?>">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Chat Footer -->
        <div class="cinemabotpro-chat-footer">
            <div class="cinemabotpro-footer-actions">
                <button class="cinemabotpro-clear-chat" title="<?php _e('Clear Chat', 'cinemabotpro'); ?>">
                    <i class="fas fa-trash"></i>
                </button>
                
                <button class="cinemabotpro-toggle-avatar" title="<?php _e('Toggle Avatar Rotation', 'cinemabotpro'); ?>">
                    <i class="fas fa-sync-alt"></i>
                </button>
                
                <button class="cinemabotpro-feedback-btn" title="<?php _e('Feedback', 'cinemabotpro'); ?>">
                    <i class="fas fa-comment"></i>
                </button>
            </div>
            
            <div class="cinemabotpro-powered-by">
                <span><?php _e('Powered by CinemaBot Pro', 'cinemabotpro'); ?></span>
            </div>
        </div>
    </div>

    <!-- Settings Panel -->
    <div class="cinemabotpro-settings-panel" id="cinemabotpro-settings-panel">
        <div class="cinemabotpro-settings-header">
            <h4><?php _e('Chat Settings', 'cinemabotpro'); ?></h4>
            <button class="cinemabotpro-settings-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="cinemabotpro-settings-content">
            <div class="cinemabotpro-setting-group">
                <label><?php _e('Language Detection', 'cinemabotpro'); ?></label>
                <div class="cinemabotpro-toggle">
                    <input type="checkbox" id="auto-language-detection" checked>
                    <label for="auto-language-detection" class="cinemabotpro-toggle-label"></label>
                </div>
            </div>
            
            <div class="cinemabotpro-setting-group">
                <label><?php _e('Avatar Rotation', 'cinemabotpro'); ?></label>
                <div class="cinemabotpro-toggle">
                    <input type="checkbox" id="avatar-rotation" checked>
                    <label for="avatar-rotation" class="cinemabotpro-toggle-label"></label>
                </div>
            </div>
            
            <div class="cinemabotpro-setting-group">
                <label><?php _e('Sound Notifications', 'cinemabotpro'); ?></label>
                <div class="cinemabotpro-toggle">
                    <input type="checkbox" id="sound-notifications">
                    <label for="sound-notifications" class="cinemabotpro-toggle-label"></label>
                </div>
            </div>
            
            <div class="cinemabotpro-setting-group">
                <label><?php _e('Theme', 'cinemabotpro'); ?></label>
                <select id="chat-theme">
                    <option value="dark"><?php _e('Dark', 'cinemabotpro'); ?></option>
                    <option value="light"><?php _e('Light', 'cinemabotpro'); ?></option>
                    <option value="auto"><?php _e('Auto', 'cinemabotpro'); ?></option>
                </select>
            </div>
        </div>
    </div>
</div>

<!-- Emoji Panel -->
<div class="cinemabotpro-emoji-panel" id="cinemabotpro-emoji-panel">
    <div class="cinemabotpro-emoji-categories">
        <button class="cinemabotpro-emoji-category active" data-category="smileys">😀</button>
        <button class="cinemabotpro-emoji-category" data-category="objects">🎬</button>
        <button class="cinemabotpro-emoji-category" data-category="hearts">❤️</button>
        <button class="cinemabotpro-emoji-category" data-category="hands">👍</button>
    </div>
    
    <div class="cinemabotpro-emoji-grid" id="cinemabotpro-emoji-grid">
        <!-- Emojis will be loaded here -->
    </div>
</div>

<script type="text/javascript">
// Localize script data
window.cinemabotpro_ajax = {
    ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
    nonce: '<?php echo wp_create_nonce('cinemabotpro_nonce'); ?>',
    plugin_url: '<?php echo CINEMABOTPRO_PLUGIN_URL; ?>',
    user_id: '<?php echo get_current_user_id(); ?>',
    user_preferences: <?php echo json_encode($user_preferences); ?>,
    strings: {
        'typing': '<?php _e('Typing...', 'cinemabotpro'); ?>',
        'online': '<?php _e('Online', 'cinemabotpro'); ?>',
        'offline': '<?php _e('Offline', 'cinemabotpro'); ?>',
        'error': '<?php _e('Something went wrong. Please try again.', 'cinemabotpro'); ?>',
        'confirm_clear': '<?php _e('Are you sure you want to clear the chat history?', 'cinemabotpro'); ?>'
    }
};
</script>