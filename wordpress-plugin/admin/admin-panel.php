<?php
// CMPlayer Admin Panel

add_action('admin_menu', function() {
    add_menu_page('CMPlayer', 'CMPlayer', 'manage_options', 'cmplayer-admin', 'cmplayer_admin_panel', 'dashicons-controls-play', 82);
});

function cmplayer_admin_panel() {
    // Handle settings update
    if (isset($_POST['cmplayer_save_settings'])) {
        check_admin_referer('cmplayer_settings');
        update_option('cmplayer_theme', sanitize_text_field($_POST['cmplayer_theme']));
        update_option('cmplayer_size', sanitize_text_field($_POST['cmplayer_size']));
        update_option('cmplayer_drm', isset($_POST['cmplayer_drm']) ? 1 : 0);
        update_option('cmplayer_download_limit', intval($_POST['cmplayer_download_limit']));
        update_option('cmplayer_ad_code', wp_kses_post($_POST['cmplayer_ad_code']));
        update_option('cmplayer_watermark_url', esc_url_raw($_POST['cmplayer_watermark_url']));
        update_option('cmplayer_watermark_link', esc_url_raw($_POST['cmplayer_watermark_link']));
        update_option('cmplayer_watermark_position', sanitize_text_field($_POST['cmplayer_watermark_position']));
        update_option('cmplayer_watermark_opacity', floatval($_POST['cmplayer_watermark_opacity']));
        update_option('cmplayer_sub_translate', isset($_POST['cmplayer_sub_translate']) ? 1 : 0);
        update_option('cmplayer_openai_api', sanitize_text_field($_POST['cmplayer_openai_api']));
        update_option('cmplayer_onesignal_app_id', sanitize_text_field($_POST['cmplayer_onesignal_app_id']));
        update_option('cmplayer_accessibility', isset($_POST['cmplayer_accessibility']) ? 1 : 0);
        update_option('cmplayer_feedback', isset($_POST['cmplayer_feedback']) ? 1 : 0);
        update_option('cmplayer_favorites', isset($_POST['cmplayer_favorites']) ? 1 : 0);
        update_option('cmplayer_errorpage', isset($_POST['cmplayer_errorpage']) ? 1 : 0);
        echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
    }

    // Export/import settings
    if (isset($_POST['cmplayer_export_settings'])) {
        $settings = [];
        foreach (cmplayer_admin_fields() as $field => $label) {
            $settings[$field] = get_option($field);
        }
        echo '<textarea style="width:100%;height:120px;">'.esc_textarea(json_encode($settings, JSON_PRETTY_PRINT)).'</textarea>';
    }
    if (isset($_POST['cmplayer_import_settings']) && !empty($_POST['cmplayer_import_json'])) {
        $import = json_decode(stripslashes($_POST['cmplayer_import_json']), true);
        if (is_array($import)) {
            foreach ($import as $k => $v) update_option($k, $v);
            echo '<div class="notice notice-success"><p>Settings imported!</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Invalid JSON!</p></div>';
        }
    }

    // Subtitle upload + EN→BN translation (OpenAI)
    if (isset($_POST['cmplayer_sub_upload']) && !empty($_FILES['cmplayer_sub_file']['tmp_name'])) {
        $file = $_FILES['cmplayer_sub_file']['tmp_name'];
        $content = file_get_contents($file);
        $type = pathinfo($_FILES['cmplayer_sub_file']['name'], PATHINFO_EXTENSION);
        $out = "";
        // Convert SRT to VTT if needed
        if (strtolower($type) == 'srt') {
            $out = "WEBVTT\n\n" . preg_replace('/(\d+\s*\n\d{2}:\d{2}:\d{2},\d{3}\s*-->\s*\d{2}:\d{2}:\d{2},\d{3})/', "\n$1", $content);
            $out = str_replace(',', '.', $out);
        } else {
            $out = $content;
        }
        // EN→BN translate if enabled and API key set
        if (!empty($_POST['cmplayer_sub_translate']) && get_option('cmplayer_openai_api')) {
            $out = cmplayer_translate_sub_en_bn($out, get_option('cmplayer_openai_api'));
        }
        // Show output (for demo)
        echo '<textarea style="width:100%;height:150px;">'.esc_textarea($out).'</textarea>';
    }

    // FVPlayer import
    cmplayer_admin_import_fvplayer();

    // Panel UI
    ?>
    <div class="wrap">
        <h1>CMPlayer Settings</h1>
        <form method="post">
            <?php wp_nonce_field('cmplayer_settings'); ?>
            <table class="form-table">
                <tr><th>Theme</th><td>
                    <select name="cmplayer_theme">
                        <option value="dark" <?php selected(get_option('cmplayer_theme'),'dark');?>>Dark</option>
                        <option value="colorful" <?php selected(get_option('cmplayer_theme'),'colorful');?>>Colorful</option>
                        <option value="light" <?php selected(get_option('cmplayer_theme'),'light');?>>Light</option>
                    </select>
                </td></tr>
                <tr><th>Player Size</th><td>
                    <select name="cmplayer_size">
                        <option value="small" <?php selected(get_option('cmplayer_size'),'small');?>>Small</option>
                        <option value="medium" <?php selected(get_option('cmplayer_size'),'medium');?>>Medium</option>
                        <option value="large" <?php selected(get_option('cmplayer_size'),'large');?>>Large</option>
                        <option value="custom" <?php selected(get_option('cmplayer_size'),'custom');?>>Custom</option>
                    </select>
                </td></tr>
                <tr><th>DRM Protection</th><td>
                    <input type="checkbox" name="cmplayer_drm" value="1" <?php checked(get_option('cmplayer_drm'),1);?>> Enable premium-only access
                </td></tr>
                <tr><th>Download Limit</th><td>
                    <input type="number" name="cmplayer_download_limit" value="<?php echo esc_attr(get_option('cmplayer_download_limit',0));?>" min="0"> (0 = unlimited)
                </td></tr>
                <tr><th>Ad Code</th><td>
                    <textarea name="cmplayer_ad_code" rows="2" style="width:100%;"><?php echo esc_textarea(get_option('cmplayer_ad_code'));?></textarea>
                </td></tr>
                <tr><th>Watermark Logo URL</th><td>
                    <input type="text" name="cmplayer_watermark_url" value="<?php echo esc_attr(get_option('cmplayer_watermark_url'));?>" style="width:100%;">
                </td></tr>
                <tr><th>Watermark Link</th><td>
                    <input type="text" name="cmplayer_watermark_link" value="<?php echo esc_attr(get_option('cmplayer_watermark_link'));?>" style="width:100%;">
                </td></tr>
                <tr><th>Watermark Position</th><td>
                    <select name="cmplayer_watermark_position">
                        <option value="top-left" <?php selected(get_option('cmplayer_watermark_position'),'top-left');?>>Top Left</option>
                        <option value="top-right" <?php selected(get_option('cmplayer_watermark_position'),'top-right');?>>Top Right</option>
                        <option value="bottom-left" <?php selected(get_option('cmplayer_watermark_position'),'bottom-left');?>>Bottom Left</option>
                        <option value="bottom-right" <?php selected(get_option('cmplayer_watermark_position'),'bottom-right');?>>Bottom Right</option>
                    </select>
                </td></tr>
                <tr><th>Watermark Opacity</th><td>
                    <input type="number" step="0.1" min="0" max="1" name="cmplayer_watermark_opacity" value="<?php echo esc_attr(get_option('cmplayer_watermark_opacity',0.7));?>">
                </td></tr>
                <tr><th>Subtitle EN→BN Translation</th><td>
                    <input type="checkbox" name="cmplayer_sub_translate" value="1" <?php checked(get_option('cmplayer_sub_translate'),1);?>> Enable (OpenAI API Key required)
                </td></tr>
                <tr><th>OpenAI API Key</th><td>
                    <input type="text" name="cmplayer_openai_api" value="<?php echo esc_attr(get_option('cmplayer_openai_api'));?>" style="width:100%;">
                </td></tr>
                <tr><th>OneSignal App ID</th><td>
                    <input type="text" name="cmplayer_onesignal_app_id" value="<?php echo esc_attr(get_option('cmplayer_onesignal_app_id'));?>" style="width:100%;">
                </td></tr>
                <tr><th>Accessibility Features</th><td>
                    <input type="checkbox" name="cmplayer_accessibility" value="1" <?php checked(get_option('cmplayer_accessibility'),1);?>> Enable screen reader & subtitle styling
                </td></tr>
                <tr><th>User Feedback</th><td>
                    <input type="checkbox" name="cmplayer_feedback" value="1" <?php checked(get_option('cmplayer_feedback'),1);?>> Enable like/dislike/report
                </td></tr>
                <tr><th>User Favorites</th><td>
                    <input type="checkbox" name="cmplayer_favorites" value="1" <?php checked(get_option('cmplayer_favorites'),1);?>> Enable favorites
                </td></tr>
                <tr><th>Custom Error Page</th><td>
                    <input type="checkbox" name="cmplayer_errorpage" value="1" <?php checked(get_option('cmplayer_errorpage'),1);?>> Enable custom error page
                </td></tr>
            </table>
            <p>
                <button type="submit" name="cmplayer_save_settings" class="button button-primary">Save Settings</button>
            </p>
        </form>

        <hr>
        <h3>Export Settings</h3>
        <form method="post">
            <button type="submit" name="cmplayer_export_settings" class="button">Export as JSON</button>
        </form>

        <h3>Import Settings</h3>
        <form method="post">
            <textarea name="cmplayer_import_json" style="width:100%;height:50px;"></textarea>
            <button type="submit" name="cmplayer_import_settings" class="button">Import JSON</button>
        </form>

        <hr>
        <h3>Subtitle Upload & EN→BN Translate</h3>
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="cmplayer_sub_file" accept=".srt,.vtt">
            <input type="checkbox" name="cmplayer_sub_translate" value="1"> EN→BN Translate
            <button type="submit" name="cmplayer_sub_upload" class="button">Upload & Convert</button>
        </form>
    </div>
    <?php
}

// List of admin fields
function cmplayer_admin_fields() {
    return [
        'cmplayer_theme' => 'Theme',
        'cmplayer_size' => 'Player Size',
        'cmplayer_drm' => 'DRM Protection',
        'cmplayer_download_limit' => 'Download Limit',
        'cmplayer_ad_code' => 'Ad Code',
        'cmplayer_watermark_url' => 'Watermark URL',
        'cmplayer_watermark_link' => 'Watermark Link',
        'cmplayer_watermark_position' => 'Watermark Position',
        'cmplayer_watermark_opacity' => 'Watermark Opacity',
        'cmplayer_sub_translate' => 'Subtitle EN→BN',
        'cmplayer_openai_api' => 'OpenAI API Key',
        'cmplayer_onesignal_app_id' => 'OneSignal App ID',
        'cmplayer_accessibility' => 'Accessibility',
        'cmplayer_feedback' => 'User Feedback',
        'cmplayer_favorites' => 'User Favorites',
        'cmplayer_errorpage' => 'Custom Error Page'
    ];
}

// Subtitle translation function (OpenAI, demo stub)
function cmplayer_translate_sub_en_bn($subtitle, $api_key) {
    // For demo: just mark as "Translated to BN"
    return $subtitle . "\n\n[Translated to Bangla]";
}

// FVPlayer import UI & logic
function cmplayer_admin_import_fvplayer() {
    global $wpdb;
    // Find FVPlayer shortcodes in posts
    $results = $wpdb->get_results("SELECT ID, post_title, post_content FROM {$wpdb->posts} WHERE post_content LIKE '%[fvplayer%'");
    echo '<hr><h3>Import FVPlayer Posts</h3>';
    if ($results) {
        echo '<form method="post">';
        echo '<table><tr><th>Select</th><th>Title</th><th>Shortcode</th></tr>';
        foreach ($results as $r) {
            preg_match('/(\[fvplayer[^\]]+\])/', $r->post_content, $m);
            $shortcode = $m[1] ?? '';
            echo '<tr>
                <td><input type="checkbox" name="cmplayer_fv_import[]" value="'.$r->ID.'"></td>
                <td>'.esc_html($r->post_title).'</td>
                <td><code>'.esc_html($shortcode).'</code></td>
            </tr>';
        }
        echo '</table>';
        echo '<button type="submit" name="cmplayer_import_fv_submit" class="button">Import Selected</button>';
        echo '</form>';
    } else {
        echo '<p>No FVPlayer shortcodes found!</p>';
    }

    // Handle import
    if (isset($_POST['cmplayer_import_fv_submit']) && !empty($_POST['cmplayer_fv_import'])) {
        foreach ($_POST['cmplayer_fv_import'] as $post_id) {
            $post = get_post($post_id);
            preg_match('/(\[fvplayer[^\]]+\])/', $post->post_content, $m);
            $fv_shortcode = $m[1] ?? '';
            $cm_shortcode = cmplayer_convert_fvplayer_shortcode($fv_shortcode);
            echo '<div><strong>Imported:</strong> '.esc_html($post->post_title).' <br> <code>'.esc_html($cm_shortcode).'</code></div>';
        }
    }
}

// Simple FVPlayer→CMPlayer shortcode converter
function cmplayer_convert_fvplayer_shortcode($fv_shortcode) {
    if (preg_match('/src="([^"]+)"/', $fv_shortcode, $src) &&
        preg_match('/caption="([^"]+)"/', $fv_shortcode, $title)) {
        return '[cmplayer playlist="'.esc_attr($title[1]).'|'.esc_attr($src[1]).'"]';
    }
    return '[cmplayer]';
}