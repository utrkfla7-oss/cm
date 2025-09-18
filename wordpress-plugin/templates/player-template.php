<?php
// 1. Custom Error Page
if (isset($atts['custom_error']) && $atts['custom_error']) {
    echo '<div class="cmplayer-error">' . esc_html($atts['custom_error']) . '</div>';
    return;
}

// 2. DRM Support
$drm_enabled = (get_option('cmplayer_drm') == 'on' || (isset($atts['drm']) && $atts['drm'] == 'on'));
$user_id = get_current_user_id();
$is_premium = get_user_meta($user_id, 'is_premium', true) == 'yes';
if ($drm_enabled && !$is_premium) {
    echo '<div class="cmplayer-error">This video is protected. Please subscribe as a premium user to watch!</div>';
    return;
}

// 3. Playlist/Quality/Subtitles
$playlist = !empty($atts['playlist']) ? explode(',', $atts['playlist']) : array(
    'Episode 1|https://www.w3schools.com/html/mov_bbb.mp4',
    'Episode 2|https://www.w3schools.com/html/movie.mp4'
);
$qualities = !empty($atts['qualities']) ? explode(',', $atts['qualities']) : array(
    '480p|https://www.w3schools.com/html/mov_bbb.mp4',
    '720p|https://www.w3schools.com/html/movie.mp4'
);
$subtitles = !empty($atts['subtitles']) ? explode(',', $atts['subtitles']) : array();
$watermark_url = get_option('cmplayer_watermark_url', '');
$watermark_link = get_option('cmplayer_watermark_link', '');
$ad_code = get_option('cmplayer_ad_network', '');
?>
<div class="cmplayer-container theme-<?php echo esc_attr(get_option('cmplayer_theme', 'default')); ?>">
    <?php if ($ad_code): ?>
        <div class="cmplayer-ad"><?php echo $ad_code; ?></div>
    <?php endif; ?>
    <div class="cmplayer-video-wrapper">
        <video id="cmplayer-video" width="100%" controls poster="" playsinline>
            <?php foreach ($qualities as $q) {
                list($label, $src) = explode('|', $q);
                echo "<source src='$src' data-quality='$label' type='video/mp4'>";
            } ?>
            Your browser does not support the video tag.
        </video>
        <?php if ($watermark_url) : ?>
            <a href="<?php echo esc_url($watermark_link); ?>" target="_blank" class="cmplayer-watermark">
                <img src="<?php echo esc_url($watermark_url); ?>" alt="Logo" style="opacity:0.7;position:absolute;top:10px;right:10px;width:80px;">
            </a>
        <?php endif; ?>
        <!-- All controls together, VLC/FVPlayer-style -->
        <div class="cmplayer-controls">
            <button id="cmplayer-backward" aria-label="Backward">« 10s</button>
            <button id="cmplayer-speed" aria-label="Speed">Speed</button>
            <button id="cmplayer-fullscreen" aria-label="Fullscreen">Fullscreen</button>
            <button id="cmplayer-cast" aria-label="Cast">Cast</button>
            <button id="cmplayer-download" aria-label="Download">Download</button>
            <button id="cmplayer-report" aria-label="Report">Report</button>
            <button id="cmplayer-forward" aria-label="Forward">10s »</button>
            <select id="cmplayer-quality">
                <?php foreach ($qualities as $q) {
                    list($label, $src) = explode('|', $q);
                    echo "<option value='$src'>$label</option>";
                } ?>
            </select>
            <select id="cmplayer-subtitle">
                <option value="">No Subtitle</option>
                <?php foreach ($subtitles as $s) {
                    list($label, $src) = explode('|', $s);
                    echo "<option value='$src'>$label</option>";
                } ?>
            </select>
        </div>
    </div>
    <!-- Tabs below, not mixed with controls -->
    <div class="cmplayer-tabs">
        <button class="cmplayer-tab" data-tab="details">Details</button>
        <button class="cmplayer-tab" data-tab="add">Add Content</button>
        <button class="cmplayer-tab" data-tab="episodes">Episodes</button>
        <button class="cmplayer-tab" data-tab="favorites">Favorites</button>
        <button class="cmplayer-tab" data-tab="analytics">Analytics</button>
    </div>
    <div class="cmplayer-tab-content" id="cmplayer-tab-details">
        <h4>Video Details</h4>
        <div id="cmplayer-details-content"></div>
    </div>
    <div class="cmplayer-tab-content" id="cmplayer-tab-add" style="display:none;">
        <h4>Add New Content</h4>
        <form id="cmplayer-add-content-form">
            <input type="text" name="title" placeholder="Title" required><br>
            <input type="url" name="url" placeholder="Video URL" required><br>
            <input type="text" name="playlist" placeholder="Playlist (optional)"><br>
            <input type="text" name="quality" placeholder="Quality label (e.g. 720p)"><br>
            <input type="url" name="subtitle_url" placeholder="Subtitle URL (optional)"><br>
            <button type="submit">Add</button>
        </form>
        <div id="cmplayer-add-content-result"></div>
    </div>
    <div class="cmplayer-tab-content" id="cmplayer-tab-episodes" style="display:none;">
        <h4>Episodes & Playlist</h4>
        <ul id="cmplayer-episodes-list"></ul>
    </div>
    <div class="cmplayer-tab-content" id="cmplayer-tab-favorites" style="display:none;">
        <h4>Your Favorites</h4>
        <ul id="cmplayer-favorites-list"></ul>
    </div>
    <div class="cmplayer-tab-content" id="cmplayer-tab-analytics" style="display:none;">
        <h4>Analytics</h4>
        <div id="cmplayer-analytics-content"></div>
    </div>
    <div class="cmplayer-feedback">
        <button aria-label="Like" id="cmplayer-like">👍 Like</button>
        <button aria-label="Dislike" id="cmplayer-dislike">👎 Dislike</button>
        <button aria-label="Favorite" id="cmplayer-favorite">⭐ Favorite</button>
    </div>
    <div class="cmplayer-favorites">
        <h4>Your Favorites</h4>
        <?php
        $favs = ($user_id) ? get_user_meta($user_id, 'cmplayer_favorites', true) : [];
        if (!empty($favs) && is_array($favs)) {
            echo "<ul>";
            foreach ($favs as $url) {
                echo "<li><a href='$url' target='_blank'>".basename($url)."</a></li>";
            }
            echo "</ul>";
        } else {
            echo "<p>No favorites yet!</p>";
        }
        ?>
    </div>
    <div class="cmplayer-analytics" style="display:none"></div>
</div>