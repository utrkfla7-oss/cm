(function() {
    tinymce.PluginManager.add('mtcm_button', function(editor, url) {
        // Add movie button
        editor.addButton('mtcm_button', {
            title: 'Insert Movie/TV Show',
            icon: 'mtcm-movie',
            type: 'menubutton',
            menu: [
                {
                    text: 'Movie (Full Display)',
                    onclick: function() {
                        editor.windowManager.open({
                            title: 'Insert Movie Shortcode',
                            width: 400,
                            height: 300,
                            body: [
                                {
                                    type: 'textbox',
                                    name: 'movie_id',
                                    label: 'Movie ID:',
                                    value: ''
                                },
                                {
                                    type: 'listbox',
                                    name: 'display_type',
                                    label: 'Display Type:',
                                    values: [
                                        {text: 'Full Details', value: 'full'},
                                        {text: 'Compact', value: 'compact'},
                                        {text: 'Poster Only', value: 'poster'}
                                    ]
                                },
                                {
                                    type: 'checkbox',
                                    name: 'show_rating',
                                    label: 'Show Rating',
                                    checked: true
                                },
                                {
                                    type: 'checkbox',
                                    name: 'show_genre',
                                    label: 'Show Genre',
                                    checked: true
                                }
                            ],
                            onsubmit: function(e) {
                                var shortcode = '[mtcm_movie id="' + e.data.movie_id + '"';
                                
                                if (e.data.display_type !== 'full') {
                                    shortcode += ' display="' + e.data.display_type + '"';
                                }
                                
                                if (!e.data.show_rating) {
                                    shortcode += ' show_rating="false"';
                                }
                                
                                if (!e.data.show_genre) {
                                    shortcode += ' show_genre="false"';
                                }
                                
                                shortcode += ']';
                                
                                editor.insertContent(shortcode);
                            }
                        });
                    }
                },
                {
                    text: 'TV Show (Full Display)',
                    onclick: function() {
                        editor.windowManager.open({
                            title: 'Insert TV Show Shortcode',
                            width: 400,
                            height: 300,
                            body: [
                                {
                                    type: 'textbox',
                                    name: 'show_id',
                                    label: 'TV Show ID:',
                                    value: ''
                                },
                                {
                                    type: 'listbox',
                                    name: 'display_type',
                                    label: 'Display Type:',
                                    values: [
                                        {text: 'Full Details', value: 'full'},
                                        {text: 'Compact', value: 'compact'},
                                        {text: 'Poster Only', value: 'poster'}
                                    ]
                                },
                                {
                                    type: 'checkbox',
                                    name: 'show_seasons',
                                    label: 'Show Seasons',
                                    checked: true
                                },
                                {
                                    type: 'checkbox',
                                    name: 'show_rating',
                                    label: 'Show Rating',
                                    checked: true
                                }
                            ],
                            onsubmit: function(e) {
                                var shortcode = '[mtcm_tv_show id="' + e.data.show_id + '"';
                                
                                if (e.data.display_type !== 'full') {
                                    shortcode += ' display="' + e.data.display_type + '"';
                                }
                                
                                if (!e.data.show_seasons) {
                                    shortcode += ' show_seasons="false"';
                                }
                                
                                if (!e.data.show_rating) {
                                    shortcode += ' show_rating="false"';
                                }
                                
                                shortcode += ']';
                                
                                editor.insertContent(shortcode);
                            }
                        });
                    }
                },
                {
                    text: 'Movie List',
                    onclick: function() {
                        editor.windowManager.open({
                            title: 'Insert Movie List Shortcode',
                            width: 350,
                            height: 250,
                            body: [
                                {
                                    type: 'textbox',
                                    name: 'limit',
                                    label: 'Number of Movies:',
                                    value: '5'
                                },
                                {
                                    type: 'textbox',
                                    name: 'genre',
                                    label: 'Filter by Genre (optional):',
                                    value: ''
                                },
                                {
                                    type: 'listbox',
                                    name: 'orderby',
                                    label: 'Order By:',
                                    values: [
                                        {text: 'Date Added', value: 'date'},
                                        {text: 'Title', value: 'title'},
                                        {text: 'Random', value: 'rand'}
                                    ]
                                }
                            ],
                            onsubmit: function(e) {
                                var shortcode = '[mtcm_movie_list';
                                
                                if (e.data.limit !== '5') {
                                    shortcode += ' limit="' + e.data.limit + '"';
                                }
                                
                                if (e.data.genre) {
                                    shortcode += ' genre="' + e.data.genre + '"';
                                }
                                
                                if (e.data.orderby !== 'date') {
                                    shortcode += ' orderby="' + e.data.orderby + '"';
                                }
                                
                                shortcode += ']';
                                
                                editor.insertContent(shortcode);
                            }
                        });
                    }
                },
                {
                    text: 'TMDB Search',
                    onclick: function() {
                        // Check if TMDB API key is configured
                        if (!mtcm_ajax.tmdb_api_key) {
                            editor.windowManager.alert('Please configure TMDB API key in Movie TV Classic Manager settings first.');
                            return;
                        }
                        
                        editor.windowManager.open({
                            title: 'Search TMDB',
                            width: 500,
                            height: 400,
                            body: [
                                {
                                    type: 'textbox',
                                    name: 'search_query',
                                    label: 'Search:',
                                    value: ''
                                },
                                {
                                    type: 'listbox',
                                    name: 'search_type',
                                    label: 'Type:',
                                    values: [
                                        {text: 'Movie', value: 'movie'},
                                        {text: 'TV Show', value: 'tv'}
                                    ]
                                },
                                {
                                    type: 'container',
                                    html: '<div id="mtcm-search-results" style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; margin-top: 10px;">Search results will appear here...</div>'
                                }
                            ],
                            onsubmit: function(e) {
                                // Handle TMDB search submission
                                console.log('TMDB search submitted:', e.data);
                            },
                            buttons: [
                                {
                                    text: 'Search',
                                    onclick: function() {
                                        var dialog = this.parent();
                                        var searchQuery = dialog.find('#search_query').value();
                                        var searchType = dialog.find('#search_type').value();
                                        
                                        if (!searchQuery) {
                                            return;
                                        }
                                        
                                        // Perform TMDB search via AJAX
                                        jQuery.post(mtcm_ajax.ajax_url, {
                                            action: 'mtcm_tmdb_search',
                                            nonce: mtcm_ajax.nonce,
                                            query: searchQuery,
                                            type: searchType
                                        }, function(response) {
                                            if (response.success) {
                                                var html = '<div class="mtcm-search-results">';
                                                if (response.data.length > 0) {
                                                    response.data.forEach(function(item) {
                                                        html += '<div class="mtcm-search-item" style="border-bottom: 1px solid #eee; padding: 5px 0; cursor: pointer;" data-id="' + item.id + '" data-type="' + searchType + '">';
                                                        html += '<strong>' + item.title + '</strong>';
                                                        if (item.release_date) {
                                                            html += ' (' + item.release_date.substring(0, 4) + ')';
                                                        }
                                                        if (item.overview) {
                                                            html += '<br><small>' + item.overview.substring(0, 100) + '...</small>';
                                                        }
                                                        html += '</div>';
                                                    });
                                                } else {
                                                    html += '<p>No results found.</p>';
                                                }
                                                html += '</div>';
                                                
                                                jQuery('#mtcm-search-results').html(html);
                                                
                                                // Add click handlers for search results
                                                jQuery('.mtcm-search-item').on('click', function() {
                                                    var itemId = jQuery(this).data('id');
                                                    var itemType = jQuery(this).data('type');
                                                    var itemTitle = jQuery(this).find('strong').text();
                                                    
                                                    // Insert shortcode for selected item
                                                    var shortcode = itemType === 'movie' ? 
                                                        '[mtcm_movie tmdb_id="' + itemId + '"]' : 
                                                        '[mtcm_tv_show tmdb_id="' + itemId + '"]';
                                                    
                                                    editor.insertContent(shortcode);
                                                    dialog.close();
                                                });
                                            }
                                        });
                                    }
                                },
                                {
                                    text: 'Cancel',
                                    onclick: 'close'
                                }
                            ]
                        });
                    }
                }
            ]
        });
    });
})();