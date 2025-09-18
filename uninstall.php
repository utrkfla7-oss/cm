<?php
/**
 * Uninstall AutoPost Movies Plugin
 * 
 * This file is executed when the plugin is deleted via WordPress admin.
 * It cleans up all plugin data from the database.
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Define plugin constants for cleanup
define('AUTOPOST_MOVIES_UNINSTALL', true);

/**
 * Remove plugin options
 */
function autopost_movies_remove_options() {
    $options = array(
        'autopost_movies_tmdb_api_key',
        'autopost_movies_wikipedia_enabled',
        'autopost_movies_imdb_enabled',
        'autopost_movies_youtube_api_key',
        'autopost_movies_cron_schedule',
        'autopost_movies_plot_source',
        'autopost_movies_info_source',
        'autopost_movies_content_order',
        'autopost_movies_fifu_enabled',
        'autopost_movies_max_posts_per_run',
        'autopost_movies_custom_info_template',
        'autopost_movies_additional_buttons',
        'autopost_movies_cron_schedule_changed'
    );
    
    foreach ($options as $option) {
        delete_option($option);
    }
}

/**
 * Remove scheduled cron events
 */
function autopost_movies_remove_cron() {
    wp_clear_scheduled_hook('autopost_movies_cron_hook');
}

/**
 * Remove custom database tables
 */
function autopost_movies_remove_tables() {
    global $wpdb;
    
    $tables = array(
        $wpdb->prefix . 'autopost_movies',
        $wpdb->prefix . 'autopost_movies_logs'
    );
    
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS {$table}");
    }
}

/**
 * Remove post meta created by plugin
 */
function autopost_movies_remove_post_meta() {
    global $wpdb;
    
    $meta_keys = array(
        'autopost_movies_tmdb_id',
        'autopost_movies_imdb_id',
        'autopost_movies_type',
        'autopost_movies_trailer_url',
        'autopost_movies_release_date',
        'autopost_movies_year',
        'autopost_movies_genres',
        'autopost_movies_rating',
        'autopost_movies_runtime',
        'autopost_movies_episodes',
        'autopost_movies_seasons',
        'autopost_movies_poster_url'
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
 * Remove transients created by plugin
 */
function autopost_movies_remove_transients() {
    global $wpdb;
    
    // Remove transients with autopost_movies prefix
    $wpdb->query(
        "DELETE FROM {$wpdb->options} 
         WHERE option_name LIKE '_transient_autopost_movies_%' 
         OR option_name LIKE '_transient_timeout_autopost_movies_%'"
    );
}

/**
 * Clean up user meta (if any)
 */
function autopost_movies_remove_user_meta() {
    global $wpdb;
    
    // Remove any user meta created by plugin
    $wpdb->delete(
        $wpdb->usermeta,
        array('meta_key' => 'autopost_movies_preferences'),
        array('%s')
    );
}

/**
 * Main uninstall function
 */
function autopost_movies_uninstall() {
    // Remove plugin options
    autopost_movies_remove_options();
    
    // Remove cron events
    autopost_movies_remove_cron();
    
    // Remove database tables
    autopost_movies_remove_tables();
    
    // Remove post meta
    autopost_movies_remove_post_meta();
    
    // Remove transients
    autopost_movies_remove_transients();
    
    // Remove user meta
    autopost_movies_remove_user_meta();
    
    // Clear any cached data
    wp_cache_flush();
}

// Execute uninstall
autopost_movies_uninstall();