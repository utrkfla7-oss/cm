jQuery(function($) {
    var video = $('#cmplayer-video')[0];
    var playlist = [];
    var currentIndex = 0;

    function loadVideo(idx) {
        if (!playlist[idx]) return;
        video.src = playlist[idx].url;
        $('#cmplayer-details-content').text(playlist[idx].title || 'Untitled');
        $('#cmplayer-quality').val(playlist[idx].url);
        $('#cmplayer-subtitle').val(playlist[idx].subtitle || '');
        currentIndex = idx;
        video.load();
    }

    $('#cmplayer-playlist .cmplayer-playlist-item').on('click', function() {
        var src = $(this).data('src');
        var idx = playlist.findIndex(item => item.url === src);
        if (idx !== -1) loadVideo(idx);
    });

    $('#cmplayer-backward').on('click', function() { video.currentTime -= 10; });
    $('#cmplayer-forward').on('click', function() { video.currentTime += 10; });

    $('#cmplayer-fullscreen').on('click', function() {
        if (video.requestFullscreen) video.requestFullscreen();
        else if (video.webkitRequestFullscreen) video.webkitRequestFullscreen();
    });

    $('#cmplayer-speed').on('click', function() {
        video.playbackRate = video.playbackRate === 1 ? 1.5 : 1;
        $(this).text(video.playbackRate + 'x');
    });

    $('#cmplayer-quality').on('change', function() {
        var url = $(this).val();
        var idx = playlist.findIndex(item => item.url === url);
        if (idx !== -1) loadVideo(idx);
    });

    $('#cmplayer-subtitle').on('change', function() {
        $('track').remove();
        var url = $(this).val();
        if (url) {
            var track = document.createElement('track');
            track.kind = 'subtitles';
            track.label = 'Subtitle';
            track.src = url;
            track.default = true;
            video.appendChild(track);
        }
    });

    $('#cmplayer-download').on('click', function() {
        window.open(video.src, '_blank');
    });

    $('#cmplayer-cast').on('click', function() {
        alert('Cast feature coming soon!');
    });

    $('#cmplayer-report').on('click', function() {
        $.post('/wp-json/cmplayer/v1/feedback', { type: 'report', video: video.src }, function(r) {
            alert(r.message || 'Report sent!');
        }, 'json');
    });

    $('#cmplayer-like').on('click', function() {
        $.post('/wp-json/cmplayer/v1/feedback', { type: 'like', video: video.src }, function(r) {
            alert(r.message || 'Liked!');
        }, 'json');
    });
    $('#cmplayer-dislike').on('click', function() {
        $.post('/wp-json/cmplayer/v1/feedback', { type: 'dislike', video: video.src }, function(r) {
            alert(r.message || 'Disliked!');
        }, 'json');
    });
    $('#cmplayer-favorite').on('click', function() {
        $.post('/wp-json/cmplayer/v1/favorites', { video: video.src, title: $('#cmplayer-details-content').text() }, function(r) {
            alert('Added to favorites!');
            loadFavorites();
        }, 'json');
    });

    // Tab system
    $('.cmplayer-tab').on('click', function() {
        $('.cmplayer-tab-content').hide();
        $('#cmplayer-tab-' + $(this).data('tab')).show();
    });

    // Add content system
    $('#cmplayer-add-content-form').on('submit', function(e) {
        e.preventDefault();
        var fd = $(this).serialize();
        $.post('/wp-json/cmplayer/v1/add_content', fd, function(resp) {
            $('#cmplayer-add-content-result').text(resp.message || '');
            if (resp.status == 'ok' && resp.item) {
                playlist.push(resp.item);
                updatePlaylistUI();
            }
        }, 'json');
    });

    function updatePlaylistUI() {
        var html = '';
        playlist.forEach(function(item, i) {
            html += '<li><a href="#" class="cmplayer-play-epi" data-idx="'+i+'">'+(item.title || 'Untitled')+'</a></li>';
        });
        $('#cmplayer-episodes-list').html(html);
    }

    $(document).on('click', '.cmplayer-play-epi', function(e) {
        e.preventDefault();
        var idx = $(this).data('idx');
        loadVideo(idx);
    });

    // Load favorites
    function loadFavorites() {
        $.get('/wp-json/cmplayer/v1/favorites', {}, function(r) {
            var html = '';
            if (r.items) r.items.forEach(function(f) {
                html += '<li><a href="#" class="cmplayer-play-fav" data-url="'+f.url+'">'+f.title+'</a></li>';
            });
            $('#cmplayer-favorites-list').html(html);
        }, 'json');
    }
    $(document).on('click', '.cmplayer-play-fav', function(e) {
        e.preventDefault();
        video.src = $(this).data('url');
        video.load();
    });

    // Analytics
    video.addEventListener('play', function() { sendAnalytics('play'); });
    video.addEventListener('ended', function() { sendAnalytics('complete'); });
    video.addEventListener('timeupdate', function() { sendAnalytics('watch', { time: video.currentTime }); });

    function sendAnalytics(event, extra) {
        $.post('/wp-json/cmplayer/v1/analytics', { event: event, video: video.src, ...(extra||{}) });
    }

    // Initial tab
    $('.cmplayer-tab').first().click();

    // Demo playlist (replace with backend data if needed)
    playlist = [
        { title: "Demo Video", url: "https://www.w3schools.com/html/mov_bbb.mp4", quality: "720p", subtitle: "" }
    ];
    updatePlaylistUI();
    loadVideo(0);
    loadFavorites();
});