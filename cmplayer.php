<?php
/*
Plugin Name: CMPlayer
Description: Advanced video player with DRM, download limits, subtitles, ad integration, favorites, and more.
Version: 1.0.0
Author: Your Name
*/

if (!defined('ABSPATH')) exit;

// Load styles and scripts
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('cmplayer-style', plugin_dir_url(__FILE__).'assets/style.css', [], '1.0.0');
    wp_enqueue_script('cmplayer-js', plugin_dir_url(__FILE__).'assets/player.js', ['jquery'], '1.0.0', true);

    // Pass OneSignal App ID if set
    $onesignal = get_option('cmplayer_onesignal_app_id', '');
    if ($onesignal) {
        wp_localize_script('cmplayer-js', 'cmplayer_onesignal_app_id', $onesignal);
    }
});

// Admin panel include
if (is_admin()) {
    include_once dirname(__FILE__).'/admin-panel.php';
}

// REST API endpoints
include_once dirname(__FILE__).'/api.php';

// AutoPost Movies with TMDB API
include_once dirname(__FILE__).'/autopost-movies.php';

// Shortcode registration
add_shortcode('cmplayer', function($atts) {
    ob_start();
    include dirname(__FILE__).'/templates/player-template.php';
    return ob_get_clean();
});