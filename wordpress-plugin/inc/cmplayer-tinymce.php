<?php
add_action('admin_init', function() {
    add_filter('mce_external_plugins', function($plugins){
        $plugins['cmplayer'] = plugins_url('cmplayer-tinymce.js', __FILE__);
        return $plugins;
    });
    add_filter('mce_buttons', function($buttons){
        $buttons[] = 'cmplayer';
        return $buttons;
    });
});

// Add icon CSS
add_action('admin_enqueue_scripts', function(){
    echo '<style>
    .mce-i-cmplayer { 
        background: url('.plugins_url('cmplayer-icon.png', __FILE__).') no-repeat center !important; 
        background-size: 20px 20px !important;
    }
    </style>';
});