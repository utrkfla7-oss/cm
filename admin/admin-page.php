<?php
/**
 * Admin Page Template
 */

if (!defined('ABSPATH')) {
    exit;
}

$admin = new AutoPost_Movies_Admin();
$cron_status = $admin->get_cron_status();
$statistics = $admin->get_statistics();
$available_schedules = $admin->get_available_schedules();

// Get current tab
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'settings';
?>

<div class="wrap">
    <h1><?php _e('AutoPost Movies Settings', 'autopost-movies'); ?></h1>
    
    <!-- Tabs -->
    <nav class="nav-tab-wrapper">
        <a href="?page=autopost-movies&tab=settings" class="nav-tab <?php echo $current_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Settings', 'autopost-movies'); ?>
        </a>
        <a href="?page=autopost-movies&tab=cron" class="nav-tab <?php echo $current_tab === 'cron' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Automation', 'autopost-movies'); ?>
        </a>
        <a href="?page=autopost-movies&tab=entries" class="nav-tab <?php echo $current_tab === 'entries' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Manage Entries', 'autopost-movies'); ?>
        </a>
        <a href="?page=autopost-movies&tab=logs" class="nav-tab <?php echo $current_tab === 'logs' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Logs', 'autopost-movies'); ?>
        </a>
        <a href="?page=autopost-movies&tab=tools" class="nav-tab <?php echo $current_tab === 'tools' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Tools', 'autopost-movies'); ?>
        </a>
    </nav>

    <!-- Statistics Dashboard -->
    <div class="autopost-movies-dashboard" style="margin: 20px 0;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <div class="postbox">
                <div class="inside">
                    <h3><?php _e('Total Items', 'autopost-movies'); ?></h3>
                    <p style="font-size: 24px; margin: 0;"><?php echo $statistics['total']; ?></p>
                </div>
            </div>
            <div class="postbox">
                <div class="inside">
                    <h3><?php _e('Pending', 'autopost-movies'); ?></h3>
                    <p style="font-size: 24px; margin: 0; color: #ff9900;"><?php echo $statistics['pending']; ?></p>
                </div>
            </div>
            <div class="postbox">
                <div class="inside">
                    <h3><?php _e('Posted', 'autopost-movies'); ?></h3>
                    <p style="font-size: 24px; margin: 0; color: #00aa00;"><?php echo $statistics['posted']; ?></p>
                </div>
            </div>
            <div class="postbox">
                <div class="inside">
                    <h3><?php _e('Errors', 'autopost-movies'); ?></h3>
                    <p style="font-size: 24px; margin: 0; color: #cc0000;"><?php echo $statistics['errors']; ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab Content -->
    <div class="tab-content">
        
        <?php if ($current_tab === 'settings'): ?>
        <!-- Settings Tab -->
        <form method="post" action="">
            <?php wp_nonce_field('autopost_movies_settings', 'autopost_movies_nonce'); ?>
            
            <div class="postbox">
                <h2 class="hndle"><?php _e('API Configuration', 'autopost-movies'); ?></h2>
                <div class="inside">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="autopost_movies_tmdb_api_key"><?php _e('TMDB API Key', 'autopost-movies'); ?> *</label>
                            </th>
                            <td>
                                <input type="text" id="autopost_movies_tmdb_api_key" name="autopost_movies_tmdb_api_key" 
                                       value="<?php echo esc_attr(get_option('autopost_movies_tmdb_api_key', '')); ?>" 
                                       class="regular-text" required />
                                <button type="button" class="button" onclick="testAPI('tmdb')"><?php _e('Test', 'autopost-movies'); ?></button>
                                <p class="description">
                                    <?php printf(__('Get your free API key from <a href="%s" target="_blank">TMDB</a>', 'autopost-movies'), 'https://www.themoviedb.org/settings/api'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="autopost_movies_youtube_api_key"><?php _e('YouTube API Key', 'autopost-movies'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="autopost_movies_youtube_api_key" name="autopost_movies_youtube_api_key" 
                                       value="<?php echo esc_attr(get_option('autopost_movies_youtube_api_key', '')); ?>" 
                                       class="regular-text" />
                                <button type="button" class="button" onclick="testAPI('youtube')"><?php _e('Test', 'autopost-movies'); ?></button>
                                <p class="description">
                                    <?php printf(__('Get your API key from <a href="%s" target="_blank">Google Console</a>', 'autopost-movies'), 'https://console.developers.google.com/'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="postbox">
                <h2 class="hndle"><?php _e('Data Sources', 'autopost-movies'); ?></h2>
                <div class="inside">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Wikipedia Integration', 'autopost-movies'); ?></th>
                            <td>
                                <input type="checkbox" id="autopost_movies_wikipedia_enabled" name="autopost_movies_wikipedia_enabled" 
                                       value="1" <?php checked(get_option('autopost_movies_wikipedia_enabled'), 1); ?> />
                                <label for="autopost_movies_wikipedia_enabled"><?php _e('Enable Wikipedia API', 'autopost-movies'); ?></label>
                                <button type="button" class="button" onclick="testAPI('wikipedia')"><?php _e('Test', 'autopost-movies'); ?></button>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('IMDb Integration', 'autopost-movies'); ?></th>
                            <td>
                                <input type="checkbox" id="autopost_movies_imdb_enabled" name="autopost_movies_imdb_enabled" 
                                       value="1" <?php checked(get_option('autopost_movies_imdb_enabled'), 1); ?> />
                                <label for="autopost_movies_imdb_enabled"><?php _e('Enable IMDb data (experimental)', 'autopost-movies'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="autopost_movies_plot_source"><?php _e('Plot Source', 'autopost-movies'); ?></label>
                            </th>
                            <td>
                                <select id="autopost_movies_plot_source" name="autopost_movies_plot_source">
                                    <option value="tmdb" <?php selected(get_option('autopost_movies_plot_source'), 'tmdb'); ?>><?php _e('TMDB', 'autopost-movies'); ?></option>
                                    <option value="wikipedia" <?php selected(get_option('autopost_movies_plot_source'), 'wikipedia'); ?>><?php _e('Wikipedia', 'autopost-movies'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="autopost_movies_info_source"><?php _e('Info Source', 'autopost-movies'); ?></label>
                            </th>
                            <td>
                                <select id="autopost_movies_info_source" name="autopost_movies_info_source">
                                    <option value="tmdb" <?php selected(get_option('autopost_movies_info_source'), 'tmdb'); ?>><?php _e('TMDB', 'autopost-movies'); ?></option>
                                    <option value="imdb" <?php selected(get_option('autopost_movies_info_source'), 'imdb'); ?>><?php _e('IMDb', 'autopost-movies'); ?></option>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="postbox">
                <h2 class="hndle"><?php _e('Content Settings', 'autopost-movies'); ?></h2>
                <div class="inside">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="autopost_movies_content_order"><?php _e('Content Order', 'autopost-movies'); ?></label>
                            </th>
                            <td>
                                <select id="autopost_movies_content_order" name="autopost_movies_content_order">
                                    <option value="plot_first" <?php selected(get_option('autopost_movies_content_order'), 'plot_first'); ?>><?php _e('Plot First', 'autopost-movies'); ?></option>
                                    <option value="info_first" <?php selected(get_option('autopost_movies_content_order'), 'info_first'); ?>><?php _e('Info First', 'autopost-movies'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Featured Image from URL', 'autopost-movies'); ?></th>
                            <td>
                                <input type="checkbox" id="autopost_movies_fifu_enabled" name="autopost_movies_fifu_enabled" 
                                       value="1" <?php checked(get_option('autopost_movies_fifu_enabled'), 1); ?> />
                                <label for="autopost_movies_fifu_enabled"><?php _e('Enable FIFU compatibility', 'autopost-movies'); ?></label>
                                <p class="description"><?php _e('Requires Featured Image from URL (FIFU) plugin', 'autopost-movies'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="autopost_movies_max_posts_per_run"><?php _e('Max Posts per Run', 'autopost-movies'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="autopost_movies_max_posts_per_run" name="autopost_movies_max_posts_per_run" 
                                       value="<?php echo esc_attr(get_option('autopost_movies_max_posts_per_run', 5)); ?>" 
                                       min="1" max="50" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="autopost_movies_custom_info_template"><?php _e('Custom Info Template', 'autopost-movies'); ?></label>
                            </th>
                            <td>
                                <textarea id="autopost_movies_custom_info_template" name="autopost_movies_custom_info_template" 
                                          rows="4" cols="50" class="large-text"><?php echo esc_textarea(get_option('autopost_movies_custom_info_template', '')); ?></textarea>
                                <p class="description">
                                    <?php _e('Use placeholders: {title}, {tmdb_id}, {imdb_id}, {year}, {genres}, {rating}', 'autopost-movies'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="postbox">
                <h2 class="hndle"><?php _e('Additional Buttons', 'autopost-movies'); ?></h2>
                <div class="inside">
                    <div id="additional-buttons-container">
                        <?php
                        $additional_buttons = get_option('autopost_movies_additional_buttons', array());
                        if (empty($additional_buttons)) {
                            $additional_buttons = array(array('text' => '', 'url' => ''));
                        }
                        
                        foreach ($additional_buttons as $index => $button):
                        ?>
                        <div class="additional-button-row" style="margin-bottom: 10px;">
                            <input type="text" name="additional_button_text[]" placeholder="<?php _e('Button Text', 'autopost-movies'); ?>" 
                                   value="<?php echo esc_attr($button['text']); ?>" style="width: 200px;" />
                            <input type="url" name="additional_button_url[]" placeholder="<?php _e('Button URL', 'autopost-movies'); ?>" 
                                   value="<?php echo esc_attr($button['url']); ?>" style="width: 300px;" />
                            <button type="button" class="button remove-button-row"><?php _e('Remove', 'autopost-movies'); ?></button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" id="add-button-row" class="button"><?php _e('Add Button', 'autopost-movies'); ?></button>
                </div>
            </div>

            <p class="submit">
                <input type="submit" name="autopost_movies_save_settings" class="button-primary" 
                       value="<?php _e('Save Settings', 'autopost-movies'); ?>" />
            </p>
        </form>

        <?php elseif ($current_tab === 'cron'): ?>
        <!-- Automation Tab -->
        <div class="postbox">
            <h2 class="hndle"><?php _e('Cron Status', 'autopost-movies'); ?></h2>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Status', 'autopost-movies'); ?></th>
                        <td>
                            <?php if ($cron_status['scheduled']): ?>
                                <span style="color: green;">● <?php _e('Scheduled', 'autopost-movies'); ?></span>
                            <?php else: ?>
                                <span style="color: red;">● <?php _e('Not Scheduled', 'autopost-movies'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Next Run', 'autopost-movies'); ?></th>
                        <td>
                            <?php if ($cron_status['next_run_formatted']): ?>
                                <?php echo esc_html($cron_status['next_run_formatted']); ?>
                                (<?php echo esc_html($cron_status['timezone']); ?>)
                            <?php else: ?>
                                <?php _e('Not scheduled', 'autopost-movies'); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Current Schedule', 'autopost-movies'); ?></th>
                        <td><?php echo esc_html($cron_status['schedule']); ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <form method="post" action="">
            <?php wp_nonce_field('autopost_movies_schedule', 'autopost_movies_schedule_nonce'); ?>
            
            <div class="postbox">
                <h2 class="hndle"><?php _e('Schedule Settings', 'autopost-movies'); ?></h2>
                <div class="inside">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="autopost_movies_cron_schedule"><?php _e('Frequency', 'autopost-movies'); ?></label>
                            </th>
                            <td>
                                <select id="autopost_movies_cron_schedule" name="autopost_movies_cron_schedule">
                                    <?php foreach ($available_schedules as $key => $label): ?>
                                        <option value="<?php echo esc_attr($key); ?>" <?php selected(get_option('autopost_movies_cron_schedule'), $key); ?>>
                                            <?php echo esc_html($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" name="autopost_movies_schedule_change" class="button-primary" 
                               value="<?php _e('Update Schedule', 'autopost-movies'); ?>" />
                        <button type="button" id="manual-cron-run" class="button"><?php _e('Run Now', 'autopost-movies'); ?></button>
                    </p>
                </div>
            </div>
        </form>

        <?php elseif ($current_tab === 'entries'): ?>
        <!-- Manage Entries Tab -->
        <div class="postbox">
            <h2 class="hndle"><?php _e('Add New Entry', 'autopost-movies'); ?></h2>
            <div class="inside">
                <form id="manual-add-form">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="manual_tmdb_id"><?php _e('TMDB ID', 'autopost-movies'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="manual_tmdb_id" placeholder="123456" required />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="manual_type"><?php _e('Type', 'autopost-movies'); ?></label>
                            </th>
                            <td>
                                <select id="manual_type">
                                    <option value="movie"><?php _e('Movie', 'autopost-movies'); ?></option>
                                    <option value="tv"><?php _e('TV Series', 'autopost-movies'); ?></option>
                                </select>
                            </td>
                        </tr>
                    </table>
                    <p>
                        <button type="submit" class="button-primary"><?php _e('Add Entry', 'autopost-movies'); ?></button>
                    </p>
                </form>
                <div id="manual-add-result"></div>
            </div>
        </div>

        <!-- Pending Entries -->
        <div class="postbox">
            <h2 class="hndle"><?php _e('Pending Entries', 'autopost-movies'); ?></h2>
            <div class="inside">
                <?php
                $pending_items = $admin->get_pending_items(20);
                if (!empty($pending_items)):
                ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Title', 'autopost-movies'); ?></th>
                            <th><?php _e('Type', 'autopost-movies'); ?></th>
                            <th><?php _e('TMDB ID', 'autopost-movies'); ?></th>
                            <th><?php _e('Release Date', 'autopost-movies'); ?></th>
                            <th><?php _e('Added', 'autopost-movies'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_items as $item): ?>
                        <tr>
                            <td><?php echo esc_html($item->title); ?></td>
                            <td><?php echo esc_html(ucfirst($item->type)); ?></td>
                            <td><?php echo esc_html($item->tmdb_id); ?></td>
                            <td><?php echo esc_html($item->release_date ?: 'N/A'); ?></td>
                            <td><?php echo esc_html($item->created_at); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p><?php _e('No pending entries found.', 'autopost-movies'); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <?php elseif ($current_tab === 'logs'): ?>
        <!-- Logs Tab -->
        <div class="postbox">
            <h2 class="hndle">
                <?php _e('Recent Logs', 'autopost-movies'); ?>
                <button type="button" id="clear-logs" class="button" style="float: right;">
                    <?php _e('Clear Logs', 'autopost-movies'); ?>
                </button>
            </h2>
            <div class="inside">
                <?php
                $logs = $admin->get_recent_logs(50);
                if (!empty($logs)):
                ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Time', 'autopost-movies'); ?></th>
                            <th><?php _e('Type', 'autopost-movies'); ?></th>
                            <th><?php _e('Message', 'autopost-movies'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo esc_html($log->created_at); ?></td>
                            <td>
                                <span class="log-type log-type-<?php echo esc_attr($log->type); ?>">
                                    <?php echo esc_html(ucfirst($log->type)); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($log->message); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p><?php _e('No logs found.', 'autopost-movies'); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <?php elseif ($current_tab === 'tools'): ?>
        <!-- Tools Tab -->
        <div class="postbox">
            <h2 class="hndle"><?php _e('Import/Export', 'autopost-movies'); ?></h2>
            <div class="inside">
                <h3><?php _e('Export Configuration', 'autopost-movies'); ?></h3>
                <p><?php _e('Export your plugin settings as a JSON file.', 'autopost-movies'); ?></p>
                <button type="button" id="export-config" class="button">
                    <?php _e('Export Configuration', 'autopost-movies'); ?>
                </button>
                
                <hr>
                
                <h3><?php _e('Import Configuration', 'autopost-movies'); ?></h3>
                <p><?php _e('Import plugin settings from a JSON file.', 'autopost-movies'); ?></p>
                <input type="file" id="import-config-file" accept=".json" />
                <button type="button" id="import-config" class="button">
                    <?php _e('Import Configuration', 'autopost-movies'); ?>
                </button>
                <div id="import-result"></div>
            </div>
        </div>

        <div class="postbox">
            <h2 class="hndle"><?php _e('Field Configuration (ACF Compatible)', 'autopost-movies'); ?></h2>
            <div class="inside">
                <p><?php _e('Field configuration for ACF compatibility:', 'autopost-movies'); ?></p>
                <textarea readonly rows="10" style="width: 100%; font-family: monospace; font-size: 12px;">
<?php
$custom_fields = new AutoPost_Movies_Custom_Fields();
echo esc_textarea(json_encode($custom_fields->export_field_config(), JSON_PRETTY_PRINT));
?>
                </textarea>
            </div>
        </div>

        <div class="postbox">
            <h2 class="hndle"><?php _e('Available Shortcodes', 'autopost-movies'); ?></h2>
            <div class="inside">
                <?php
                $shortcodes = new AutoPost_Movies_Shortcodes();
                $shortcode_list = $shortcodes->get_shortcode_list();
                ?>
                <table class="wp-list-table widefat">
                    <thead>
                        <tr>
                            <th><?php _e('Shortcode', 'autopost-movies'); ?></th>
                            <th><?php _e('Description', 'autopost-movies'); ?></th>
                            <th><?php _e('Attributes', 'autopost-movies'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($shortcode_list as $shortcode => $info): ?>
                        <tr>
                            <td><code>[<?php echo esc_html($shortcode); ?>]</code></td>
                            <td><?php echo esc_html($info['description']); ?></td>
                            <td>
                                <?php if (!empty($info['attributes'])): ?>
                                    <?php foreach ($info['attributes'] as $attr => $desc): ?>
                                        <strong><?php echo esc_html($attr); ?>:</strong> <?php echo esc_html($desc); ?><br>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <?php _e('None', 'autopost-movies'); ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.log-type {
    padding: 3px 8px;
    border-radius: 3px;
    color: white;
    font-size: 11px;
    font-weight: bold;
}
.log-type-tmdb { background: #01b4e4; }
.log-type-wikipedia { background: #000; }
.log-type-youtube { background: #ff0000; }
.log-type-post_creation { background: #00aa00; }
.log-type-error { background: #cc0000; }
.log-type-cron { background: #ff9900; }
</style>

<script>
jQuery(document).ready(function($) {
    // Test API functionality
    window.testAPI = function(type) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'autopost_movies_test_api',
                api_type: type,
                nonce: '<?php echo wp_create_nonce('autopost_movies_admin_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('✓ ' + response.data.message);
                } else {
                    alert('✗ ' + response.data.message);
                }
            },
            error: function() {
                alert('✗ <?php _e('Request failed', 'autopost-movies'); ?>');
            }
        });
    };
    
    // Manual cron run
    $('#manual-cron-run').click(function() {
        var $btn = $(this);
        $btn.prop('disabled', true).text('<?php _e('Running...', 'autopost-movies'); ?>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'autopost_movies_manual_cron',
                nonce: '<?php echo wp_create_nonce('autopost_movies_admin_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('✓ ' + response.data.message);
                } else {
                    alert('✗ ' + response.data.message);
                }
            },
            error: function() {
                alert('✗ <?php _e('Request failed', 'autopost-movies'); ?>');
            },
            complete: function() {
                $btn.prop('disabled', false).text('<?php _e('Run Now', 'autopost-movies'); ?>');
                location.reload();
            }
        });
    });
    
    // Additional buttons management
    $('#add-button-row').click(function() {
        var newRow = '<div class="additional-button-row" style="margin-bottom: 10px;">' +
            '<input type="text" name="additional_button_text[]" placeholder="<?php _e('Button Text', 'autopost-movies'); ?>" style="width: 200px;" />' +
            '<input type="url" name="additional_button_url[]" placeholder="<?php _e('Button URL', 'autopost-movies'); ?>" style="width: 300px;" />' +
            '<button type="button" class="button remove-button-row"><?php _e('Remove', 'autopost-movies'); ?></button>' +
            '</div>';
        $('#additional-buttons-container').append(newRow);
    });
    
    $(document).on('click', '.remove-button-row', function() {
        $(this).closest('.additional-button-row').remove();
    });
    
    // Manual add entry
    $('#manual-add-form').submit(function(e) {
        e.preventDefault();
        
        var tmdb_id = $('#manual_tmdb_id').val();
        var type = $('#manual_type').val();
        var $result = $('#manual-add-result');
        
        $result.html('<div class="notice notice-info"><p><?php _e('Processing...', 'autopost-movies'); ?></p></div>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'autopost_movies_manual_add',
                tmdb_id: tmdb_id,
                type: type,
                nonce: '<?php echo wp_create_nonce('autopost_movies_manual_add'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $result.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                    $('#manual_tmdb_id').val('');
                } else {
                    $result.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                }
            },
            error: function() {
                $result.html('<div class="notice notice-error"><p><?php _e('Request failed', 'autopost-movies'); ?></p></div>');
            }
        });
    });
    
    // Clear logs
    $('#clear-logs').click(function() {
        if (!confirm('<?php _e('Are you sure you want to clear all logs?', 'autopost-movies'); ?>')) {
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'autopost_movies_clear_logs',
                nonce: '<?php echo wp_create_nonce('autopost_movies_admin_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('✓ ' + response.data.message);
                    location.reload();
                } else {
                    alert('✗ ' + response.data.message);
                }
            }
        });
    });
    
    // Export configuration
    $('#export-config').click(function() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'autopost_movies_export_config',
                nonce: '<?php echo wp_create_nonce('autopost_movies_admin_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    var dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(response.data.config, null, 2));
                    var downloadAnchorNode = document.createElement('a');
                    downloadAnchorNode.setAttribute("href", dataStr);
                    downloadAnchorNode.setAttribute("download", response.data.filename);
                    document.body.appendChild(downloadAnchorNode);
                    downloadAnchorNode.click();
                    downloadAnchorNode.remove();
                }
            }
        });
    });
    
    // Import configuration
    $('#import-config').click(function() {
        var fileInput = document.getElementById('import-config-file');
        var file = fileInput.files[0];
        
        if (!file) {
            alert('<?php _e('Please select a file', 'autopost-movies'); ?>');
            return;
        }
        
        var reader = new FileReader();
        reader.onload = function(e) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'autopost_movies_import_config',
                    config: e.target.result,
                    nonce: '<?php echo wp_create_nonce('autopost_movies_admin_nonce'); ?>'
                },
                success: function(response) {
                    var $result = $('#import-result');
                    if (response.success) {
                        $result.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                        setTimeout(function() { location.reload(); }, 2000);
                    } else {
                        $result.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                    }
                }
            });
        };
        reader.readAsText(file);
    });
});
</script>