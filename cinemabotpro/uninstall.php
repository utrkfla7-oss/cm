<?php
/**
 * Uninstall script for CinemaBot Pro
 * 
 * This file is executed when the plugin is uninstalled.
 * It removes all plugin data including options, tables, and user metadata.
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Define plugin constants
define('CINEMABOTPRO_TEXT_DOMAIN', 'cinemabotpro');

/**
 * Remove all plugin options
 */
function cinemabotpro_remove_options() {
    $options = array(
        'cinemabotpro_enabled',
        'cinemabotpro_avatar_rotation',
        'cinemabotpro_auto_lang_detect',
        'cinemabotpro_animation_speed',
        'cinemabotpro_memory_enabled',
        'cinemabotpro_gdpr_enabled',
        'cinemabotpro_openai_api_key',
        'cinemabotpro_default_language',
        'cinemabotpro_supported_languages',
        'cinemabotpro_avatar_pack',
        'cinemabotpro_chat_theme',
        'cinemabotpro_max_message_length',
        'cinemabotpro_rate_limit',
        'cinemabotpro_content_crawling',
        'cinemabotpro_recommendations',
        'cinemabotpro_version',
        'cinemabotpro_db_version',
        'cinemabotpro_avatar_cache',
        'cinemabotpro_ai_model',
        'cinemabotpro_content_api_key',
        'cinemabotpro_tmdb_api_key',
        'cinemabotpro_omdb_api_key',
        'cinemabotpro_security_settings',
        'cinemabotpro_performance_settings',
        'cinemabotpro_analytics_settings'
    );
    
    foreach ($options as $option) {
        delete_option($option);
    }
}

/**
 * Remove plugin database tables
 */
function cinemabotpro_remove_tables() {
    global $wpdb;
    
    $tables = array(
        $wpdb->prefix . 'cinemabotpro_chats',
        $wpdb->prefix . 'cinemabotpro_user_prefs',
        $wpdb->prefix . 'cinemabotpro_analytics',
        $wpdb->prefix . 'cinemabotpro_content_cache',
        $wpdb->prefix . 'cinemabotpro_sessions'
    );
    
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS $table");
    }
}

/**
 * Remove user metadata
 */
function cinemabotpro_remove_user_meta() {
    global $wpdb;
    
    $meta_keys = array(
        'cinemabotpro_preferences',
        'cinemabotpro_viewing_history',
        'cinemabotpro_favorites',
        'cinemabotpro_language_pref',
        'cinemabotpro_avatar_pref',
        'cinemabotpro_chat_history',
        'cinemabotpro_last_session',
        'cinemabotpro_consent_given',
        'cinemabotpro_data_retention'
    );
    
    foreach ($meta_keys as $meta_key) {
        $wpdb->delete(
            $wpdb->usermeta,
            array('meta_key' => $meta_key),
            array('%s')
        );
    }
}

/**
 * Remove post metadata for custom post types
 */
function cinemabotpro_remove_post_meta() {
    global $wpdb;
    
    $meta_keys = array(
        'cinemabotpro_movie_data',
        'cinemabotpro_tv_data',
        'cinemabotpro_ratings',
        'cinemabotpro_recommendations',
        'cinemabotpro_metadata',
        'cinemabotpro_crawled_data',
        'cinemabotpro_ai_analysis'
    );
    
    foreach ($meta_keys as $meta_key) {
        $wpdb->delete(
            $wpdb->postmeta,
            array('meta_key' => $meta_key),
            array('%s')
        );
    }
}

/**
 * Remove custom posts
 */
function cinemabotpro_remove_posts() {
    $post_types = array('cbp_movie', 'cbp_tv_show', 'cbp_person');
    
    foreach ($post_types as $post_type) {
        $posts = get_posts(array(
            'post_type' => $post_type,
            'numberposts' => -1,
            'post_status' => 'any'
        ));
        
        foreach ($posts as $post) {
            wp_delete_post($post->ID, true);
        }
    }
}

/**
 * Clear cached data
 */
function cinemabotpro_clear_cache() {
    // Clear WordPress transients
    global $wpdb;
    
    $wpdb->query(
        "DELETE FROM $wpdb->options 
        WHERE option_name LIKE '_transient_cinemabotpro_%' 
        OR option_name LIKE '_transient_timeout_cinemabotpro_%'"
    );
    
    // Clear any file-based cache if exists
    $upload_dir = wp_upload_dir();
    $cache_dir = $upload_dir['basedir'] . '/cinemabotpro-cache/';
    
    if (is_dir($cache_dir)) {
        $files = glob($cache_dir . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        rmdir($cache_dir);
    }
}

/**
 * Log uninstallation for GDPR compliance
 */
function cinemabotpro_log_uninstall() {
    $log_data = array(
        'timestamp' => current_time('mysql'),
        'site_url' => get_site_url(),
        'wp_version' => get_bloginfo('version'),
        'php_version' => PHP_VERSION,
        'plugin_version' => '1.0.0'
    );
    
    // This could be sent to a remote logging service if needed
    // For now, we'll just create a temporary log file
    $upload_dir = wp_upload_dir();
    $log_file = $upload_dir['basedir'] . '/cinemabotpro-uninstall.log';
    
    file_put_contents($log_file, json_encode($log_data) . "\n", FILE_APPEND | LOCK_EX);
    
    // Schedule cleanup of this log file
    wp_schedule_single_event(time() + (7 * DAY_IN_SECONDS), 'cinemabotpro_cleanup_uninstall_log');
}

// Only proceed if user has the required capabilities
if (!current_user_can('activate_plugins')) {
    return;
}

// Check if we should preserve data (user option)
$preserve_data = get_option('cinemabotpro_preserve_data_on_uninstall', false);

if (!$preserve_data) {
    // Log the uninstallation for compliance
    cinemabotpro_log_uninstall();
    
    // Remove all plugin data
    cinemabotpro_remove_options();
    cinemabotpro_remove_tables();
    cinemabotpro_remove_user_meta();
    cinemabotpro_remove_post_meta();
    cinemabotpro_remove_posts();
    cinemabotpro_clear_cache();
    
    // Clear any scheduled hooks
    wp_clear_scheduled_hook('cinemabotpro_content_crawl');
    wp_clear_scheduled_hook('cinemabotpro_analytics_cleanup');
    wp_clear_scheduled_hook('cinemabotpro_avatar_rotation');
    wp_clear_scheduled_hook('cinemabotpro_cache_cleanup');
}

// Always remove the preserve data option itself
delete_option('cinemabotpro_preserve_data_on_uninstall');