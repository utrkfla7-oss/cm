(function() {
    tinymce.create('tinymce.plugins.cmplayer', {
        init : function(ed, url) {
            ed.addButton('cmplayer', {
                title : 'Insert CMPlayer',
                icon : 'cmplayer',
                onclick : function() {
                    ed.insertContent('[cmplayer]');
                }
            });
        },
        createControl : function(n, cm) { return null; }
    });
    tinymce.PluginManager.add('cmplayer', tinymce.plugins.cmplayer);
})();