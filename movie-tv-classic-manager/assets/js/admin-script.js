/**
 * Movie TV Classic Manager - Admin JavaScript
 */

jQuery(document).ready(function($) {
    
    // TMDB ID field change handler
    $('#mtcm_tmdb_id').on('input', function() {
        var tmdbId = $(this).val().trim();
        var fetchButton = $('#mtcm_fetch_tmdb_data');
        
        if (tmdbId && tmdbId.length > 0) {
            fetchButton.prop('disabled', false);
        } else {
            fetchButton.prop('disabled', true);
        }
    });
    
    // Fetch TMDB data button
    $('#mtcm_fetch_tmdb_data').on('click', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var tmdbId = $('#mtcm_tmdb_id').val().trim();
        var postType = $('input[name="post_type"]').val();
        var dataType = postType === 'mtcm_movie' ? 'movie' : 'tv';
        
        if (!tmdbId) {
            alert('Please enter a TMDB ID first.');
            return;
        }
        
        if (!mtcm_ajax.tmdb_api_key) {
            alert('TMDB API key is not configured. Please configure it in the plugin settings.');
            return;
        }
        
        // Show loading state
        button.prop('disabled', true).text('Fetching...');
        
        $.ajax({
            url: mtcm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'mtcm_tmdb_details',
                nonce: mtcm_ajax.nonce,
                id: tmdbId,
                type: dataType
            },
            success: function(response) {
                if (response.success && response.data) {
                    mtcmFillFormWithTMDBData(response.data, dataType);
                    mtcmShowNotice('TMDB data fetched successfully!', 'success');
                    
                    // Update TMDB status
                    var statusHtml = '<p><span class="dashicons dashicons-yes-alt" style="color: green;"></span> TMDB data available</p>' +
                                   '<p><small>Last updated: ' + new Date().toLocaleDateString() + '</small></p>' +
                                   '<button type="button" id="mtcm_refresh_tmdb_data" class="button button-link">Refresh Data</button>';
                    $('.mtcm-tmdb-status').html(statusHtml);
                } else {
                    var errorMsg = response.data && response.data.error ? response.data.error : 'Failed to fetch TMDB data.';
                    mtcmShowNotice(errorMsg, 'error');
                }
            },
            error: function() {
                mtcmShowNotice('Network error occurred while fetching TMDB data.', 'error');
            },
            complete: function() {
                button.prop('disabled', false).text('Fetch TMDB Data');
            }
        });
    });
    
    // Search TMDB button
    $('#mtcm_search_tmdb').on('click', function(e) {
        e.preventDefault();
        mtcmShowTMDBSearchDialog();
    });
    
    // Set poster button
    $('#mtcm_set_poster').on('click', function(e) {
        e.preventDefault();
        
        var posterUrl = $('#mtcm_poster_url').val().trim();
        
        if (!posterUrl) {
            alert('Please enter a poster URL first.');
            return;
        }
        
        if (typeof wp !== 'undefined' && wp.media) {
            // Use WordPress media library if available
            mtcmSetFeaturedImageFromUrl(posterUrl);
        } else {
            // Fallback: just update the preview
            mtcmUpdatePosterPreview(posterUrl);
        }
    });
    
    // Poster URL field change handler
    $('#mtcm_poster_url').on('input', function() {
        var url = $(this).val().trim();
        mtcmUpdatePosterPreview(url);
    });
    
    // Function to fill form with TMDB data
    function mtcmFillFormWithTMDBData(data, type) {
        // Common fields
        if (data.title && !$('#title').val()) {
            $('#title').val(data.title);
        }
        
        if (data.overview && !$('#content').val()) {
            if (typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor) {
                tinyMCE.activeEditor.setContent(data.overview);
            } else {
                $('#content').val(data.overview);
            }
        }
        
        if (data.poster_url) {
            $('#mtcm_poster_url').val(data.poster_url);
            mtcmUpdatePosterPreview(data.poster_url);
        }
        
        if (data.backdrop_url) {
            $('#mtcm_backdrop_url').val(data.backdrop_url);
        }
        
        if (data.imdb_id) {
            $('#mtcm_imdb_id').val(data.imdb_id);
        }
        
        if (data.cast && Array.isArray(data.cast)) {
            $('#mtcm_cast').val(data.cast.join(', '));
        }
        
        if (data.production_countries && Array.isArray(data.production_countries)) {
            $('#mtcm_country').val(data.production_countries.join(', '));
        }
        
        if (data.original_language) {
            $('#mtcm_language').val(data.original_language);
        }
        
        // Movie-specific fields
        if (type === 'movie') {
            if (data.release_date) {
                $('#mtcm_release_date').val(data.release_date);
            }
            
            if (data.runtime) {
                $('#mtcm_runtime').val(data.runtime);
            }
            
            if (data.director) {
                $('#mtcm_director').val(data.director);
            }
            
            if (data.budget) {
                $('#mtcm_budget').val(data.budget);
            }
            
            if (data.revenue) {
                $('#mtcm_revenue').val(data.revenue);
            }
            
            if (data.tagline) {
                $('#mtcm_tagline').val(data.tagline);
            }
        }
        
        // TV show-specific fields
        if (type === 'tv') {
            if (data.first_air_date) {
                $('#mtcm_first_air_date').val(data.first_air_date);
            }
            
            if (data.last_air_date) {
                $('#mtcm_last_air_date').val(data.last_air_date);
            }
            
            if (data.creator) {
                $('#mtcm_creator').val(data.creator);
            }
            
            if (data.number_of_seasons) {
                $('#mtcm_total_seasons').val(data.number_of_seasons);
            }
            
            if (data.number_of_episodes) {
                $('#mtcm_total_episodes').val(data.number_of_episodes);
            }
            
            if (data.episode_run_time) {
                $('#mtcm_episode_runtime').val(data.episode_run_time);
            }
            
            if (data.networks) {
                $('#mtcm_network').val(data.networks);
            }
            
            if (data.status) {
                $('#mtcm_status').val(data.status);
            }
        }
        
        // Update genres (this would require additional AJAX to handle taxonomy terms)
        if (data.genres && Array.isArray(data.genres)) {
            // Note: This is a simplified approach - in a real implementation,
            // you'd want to handle taxonomy terms properly
            console.log('Genres to add:', data.genres);
        }
    }
    
    // Function to show TMDB search dialog
    function mtcmShowTMDBSearchDialog() {
        var postType = $('input[name="post_type"]').val();
        var searchType = postType === 'mtcm_movie' ? 'movie' : 'tv';
        
        var dialogHtml = '<div id="mtcm-search-dialog" title="Search TMDB">' +
            '<div class="mtcm-search-form">' +
                '<input type="text" id="mtcm-search-query" placeholder="Enter movie or TV show title..." style="width: 100%; margin-bottom: 10px;" />' +
                '<button type="button" id="mtcm-do-search" class="button button-primary">Search</button>' +
            '</div>' +
            '<div id="mtcm-search-results-container" style="margin-top: 15px; max-height: 300px; overflow-y: auto;"></div>' +
        '</div>';
        
        $('body').append(dialogHtml);
        
        $('#mtcm-search-dialog').dialog({
            width: 500,
            height: 450,
            modal: true,
            resizable: true,
            close: function() {
                $(this).remove();
            }
        });
        
        // Search button handler
        $('#mtcm-do-search').on('click', function() {
            var query = $('#mtcm-search-query').val().trim();
            
            if (!query) {
                alert('Please enter a search term.');
                return;
            }
            
            mtcmPerformTMDBSearch(query, searchType);
        });
        
        // Enter key handler
        $('#mtcm-search-query').on('keypress', function(e) {
            if (e.which === 13) {
                $('#mtcm-do-search').click();
            }
        });
        
        // Focus on search input
        $('#mtcm-search-query').focus();
    }
    
    // Function to perform TMDB search
    function mtcmPerformTMDBSearch(query, type) {
        var resultsContainer = $('#mtcm-search-results-container');
        var searchButton = $('#mtcm-do-search');
        
        searchButton.prop('disabled', true).text('Searching...');
        resultsContainer.html('<p>Searching...</p>');
        
        $.ajax({
            url: mtcm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'mtcm_tmdb_search',
                nonce: mtcm_ajax.nonce,
                query: query,
                type: type
            },
            success: function(response) {
                if (response.success && response.data) {
                    mtcmDisplaySearchResults(response.data, type);
                } else {
                    resultsContainer.html('<p>No results found or error occurred.</p>');
                }
            },
            error: function() {
                resultsContainer.html('<p>Network error occurred.</p>');
            },
            complete: function() {
                searchButton.prop('disabled', false).text('Search');
            }
        });
    }
    
    // Function to display search results
    function mtcmDisplaySearchResults(results, type) {
        var resultsContainer = $('#mtcm-search-results-container');
        var html = '';
        
        if (results.length === 0) {
            html = '<p>No results found.</p>';
        } else {
            results.forEach(function(item) {
                var year = '';
                if (type === 'movie' && item.release_date) {
                    year = ' (' + item.release_date.substring(0, 4) + ')';
                } else if (type === 'tv' && item.first_air_date) {
                    year = ' (' + item.first_air_date.substring(0, 4) + ')';
                }
                
                html += '<div class="mtcm-search-result-item" data-tmdb-id="' + item.id + '" style="border-bottom: 1px solid #eee; padding: 10px; cursor: pointer;">' +
                    '<strong>' + item.title + year + '</strong>';
                
                if (item.overview) {
                    html += '<br><small>' + item.overview.substring(0, 150) + (item.overview.length > 150 ? '...' : '') + '</small>';
                }
                
                html += '</div>';
            });
        }
        
        resultsContainer.html(html);
        
        // Add click handlers for results
        $('.mtcm-search-result-item').on('click', function() {
            var tmdbId = $(this).data('tmdb-id');
            $('#mtcm_tmdb_id').val(tmdbId);
            $('#mtcm-search-dialog').dialog('close');
            
            // Automatically fetch data for selected item
            $('#mtcm_fetch_tmdb_data').click();
        });
    }
    
    // Function to update poster preview
    function mtcmUpdatePosterPreview(url) {
        var previewContainer = $('.mtcm-poster-preview');
        
        if (url) {
            var img = '<img src="' + url + '" alt="Poster Preview" style="max-width: 100%; height: auto; border: 1px solid #ddd; border-radius: 3px;" />';
            
            if (previewContainer.length) {
                previewContainer.html(img);
            } else {
                $('.mtcm-poster-section').append('<div class="mtcm-poster-preview">' + img + '</div>');
            }
        } else {
            previewContainer.remove();
        }
    }
    
    // Function to set featured image from URL (simplified)
    function mtcmSetFeaturedImageFromUrl(url) {
        // This is a simplified version - in a real implementation,
        // you'd handle the media library integration properly
        mtcmShowNotice('Poster URL saved. Use "Set as Featured Image" button to set it as the featured image.', 'info');
    }
    
    // Function to show admin notices
    function mtcmShowNotice(message, type) {
        type = type || 'info';
        var noticeClass = 'notice notice-' + type + ' is-dismissible';
        
        var notice = '<div class="' + noticeClass + '">' +
            '<p>' + message + '</p>' +
            '<button type="button" class="notice-dismiss">' +
                '<span class="screen-reader-text">Dismiss this notice.</span>' +
            '</button>' +
        '</div>';
        
        $('.wrap h1').after(notice);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $('.notice').fadeOut();
        }, 5000);
    }
    
    // Handle notice dismiss buttons
    $(document).on('click', '.notice-dismiss', function() {
        $(this).closest('.notice').fadeOut();
    });
    
    // Refresh TMDB data button (delegated event handler)
    $(document).on('click', '#mtcm_refresh_tmdb_data', function(e) {
        e.preventDefault();
        $('#mtcm_fetch_tmdb_data').click();
    });
    
});