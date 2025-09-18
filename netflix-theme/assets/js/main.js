/**
 * Netflix Theme Main JavaScript
 */

jQuery(document).ready(function($) {
    'use strict';

    // Global Netflix object
    window.Netflix = {
        ajax_url: netflix_ajax.ajax_url,
        nonce: netflix_ajax.nonce,
        backend_url: netflix_ajax.backend_url,
        user_id: netflix_ajax.user_id,
        is_logged_in: netflix_ajax.is_user_logged_in
    };

    /**
     * Header functionality
     */
    $(window).scroll(function() {
        if ($(window).scrollTop() > 100) {
            $('#netflix-header').addClass('scrolled');
        } else {
            $('#netflix-header').removeClass('scrolled');
        }
    });

    /**
     * Mobile menu toggle
     */
    $('.netflix-mobile-toggle').click(function() {
        $('.netflix-mobile-menu').slideToggle();
        $(this).toggleClass('active');
    });

    /**
     * Search functionality
     */
    $('.netflix-search-toggle').click(function() {
        $('.netflix-search-form').slideToggle();
        $('.netflix-search-form input').focus();
    });

    // Live search
    let searchTimeout;
    $('.netflix-search-form input').on('input', function() {
        clearTimeout(searchTimeout);
        const query = $(this).val();
        
        if (query.length < 3) {
            $('.netflix-search-results').hide();
            return;
        }

        searchTimeout = setTimeout(function() {
            performSearch(query);
        }, 300);
    });

    function performSearch(query) {
        $.ajax({
            url: Netflix.ajax_url,
            type: 'POST',
            data: {
                action: 'netflix_search_content',
                nonce: Netflix.nonce,
                query: query
            },
            success: function(response) {
                if (response.success) {
                    displaySearchResults(response.data);
                }
            }
        });
    }

    function displaySearchResults(results) {
        let html = '<div class="netflix-search-results"><div class="search-results-grid">';
        
        results.forEach(function(item) {
            html += `
                <div class="search-result-item">
                    <a href="${item.url}">
                        <img src="${item.poster}" alt="${item.title}" />
                        <div class="result-info">
                            <h4>${item.title}</h4>
                            <span class="result-type">${item.type}</span>
                        </div>
                    </a>
                </div>
            `;
        });
        
        html += '</div></div>';
        
        $('.netflix-search-form').after(html);
        $('.netflix-search-results').show();
    }

    /**
     * Profile and notifications dropdowns
     */
    $('.netflix-profile-toggle').click(function() {
        $('.netflix-profile-menu').slideToggle();
    });

    $('.netflix-notifications-toggle').click(function() {
        $('.netflix-notifications-dropdown').slideToggle();
    });

    // Close dropdowns when clicking outside
    $(document).click(function(e) {
        if (!$(e.target).closest('.netflix-profile-dropdown').length) {
            $('.netflix-profile-menu').slideUp();
        }
        if (!$(e.target).closest('.netflix-notifications').length) {
            $('.netflix-notifications-dropdown').slideUp();
        }
        if (!$(e.target).closest('.netflix-search').length) {
            $('.netflix-search-form').slideUp();
            $('.netflix-search-results').hide();
        }
    });

    /**
     * Content sliders
     */
    $('.netflix-slider').each(function() {
        initSlider($(this));
    });

    function initSlider($slider) {
        const $content = $slider.find('.netflix-slider-content');
        const $prevBtn = $slider.find('.netflix-slider-prev');
        const $nextBtn = $slider.find('.netflix-slider-next');
        const cardWidth = 250 + 15; // card width + gap
        let currentPos = 0;

        $nextBtn.click(function() {
            const maxScroll = $content[0].scrollWidth - $content.width();
            if (currentPos < maxScroll) {
                currentPos = Math.min(currentPos + (cardWidth * 4), maxScroll);
                $content.css('transform', `translateX(-${currentPos}px)`);
            }
        });

        $prevBtn.click(function() {
            if (currentPos > 0) {
                currentPos = Math.max(currentPos - (cardWidth * 4), 0);
                $content.css('transform', `translateX(-${currentPos}px)`);
            }
        });
    }

    /**
     * Video player functionality
     */
    $('.netflix-play-movie, .netflix-play-show').click(function() {
        const postId = $(this).data('id');
        playContent(postId);
    });

    $('.netflix-play-trailer').click(function() {
        const trailerUrl = $(this).data('trailer');
        playTrailer(trailerUrl);
    });

    function playContent(postId) {
        // Check if user has access
        if (!Netflix.is_logged_in) {
            showLoginModal();
            return;
        }

        // Get streaming URL from backend
        $.ajax({
            url: Netflix.ajax_url,
            type: 'POST',
            data: {
                action: 'netflix_get_streaming_url',
                nonce: Netflix.nonce,
                post_id: postId,
                quality: 'auto'
            },
            beforeSend: function() {
                showLoadingOverlay();
            },
            success: function(response) {
                hideLoadingOverlay();
                if (response.success) {
                    showVideoModal(response.data);
                } else {
                    if (response.data && response.data.includes('subscription')) {
                        showSubscriptionModal();
                    } else {
                        alert('Failed to load video: ' + response.data);
                    }
                }
            },
            error: function() {
                hideLoadingOverlay();
                alert('Failed to load video');
            }
        });
    }

    function playTrailer(trailerUrl) {
        showVideoModal({
            streaming_url: trailerUrl,
            title: 'Trailer',
            is_trailer: true
        });
    }

    function showVideoModal(videoData) {
        const modal = $('#netflix-video-modal');
        const player = modal.find('#netflix-video-player');
        
        // Initialize video player
        player.html(`
            <video controls autoplay width="100%" height="100%">
                <source src="${videoData.streaming_url}" type="video/mp4">
                Your browser does not support the video tag.
            </video>
        `);
        
        modal.find('#netflix-video-title').text(videoData.title || 'Video');
        modal.find('#netflix-video-description').text(videoData.description || '');
        
        modal.fadeIn();
        
        // Track analytics if not trailer
        if (!videoData.is_trailer) {
            netflix_track_event('video_play', videoData.content_id);
        }
    }

    /**
     * My List functionality
     */
    $('.netflix-add-to-list').click(function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        if (!Netflix.is_logged_in) {
            showLoginModal();
            return;
        }

        const $btn = $(this);
        const postId = $btn.data('id');
        
        $.ajax({
            url: Netflix.ajax_url,
            type: 'POST',
            data: {
                action: 'netflix_add_to_list',
                nonce: Netflix.nonce,
                post_id: postId
            },
            beforeSend: function() {
                $btn.prop('disabled', true);
            },
            success: function(response) {
                $btn.prop('disabled', false);
                if (response.success) {
                    $btn.find('i').removeClass('fa-plus').addClass('fa-check');
                    $btn.find('span').text('Added');
                    showNotification(response.data, 'success');
                } else {
                    showNotification(response.data, 'error');
                }
            },
            error: function() {
                $btn.prop('disabled', false);
                showNotification('Failed to add to list', 'error');
            }
        });
    });

    /**
     * Like/Dislike functionality
     */
    $('.netflix-like, .netflix-dislike').click(function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        if (!Netflix.is_logged_in) {
            showLoginModal();
            return;
        }

        const $btn = $(this);
        const postId = $btn.data('id');
        const action = $btn.hasClass('netflix-like') ? 'like' : 'dislike';
        
        // Send feedback to backend
        $.ajax({
            url: Netflix.ajax_url,
            type: 'POST',
            data: {
                action: 'netflix_track_analytics',
                nonce: Netflix.nonce,
                event: 'feedback',
                post_id: postId,
                data: { type: action }
            },
            success: function(response) {
                $btn.addClass('active');
                $btn.siblings('.netflix-like, .netflix-dislike').removeClass('active');
            }
        });
    });

    /**
     * Subscription modal
     */
    $('.netflix-show-subscription-modal, .netflix-subscribe-button').click(function() {
        showSubscriptionModal();
    });

    $('.netflix-plan').click(function() {
        $('.netflix-plan').removeClass('selected');
        $(this).addClass('selected');
    });

    $('#netflix-subscribe-button').click(function() {
        const selectedPlan = $('.netflix-plan.selected').data('plan');
        if (!selectedPlan) {
            alert('Please select a plan');
            return;
        }

        // Handle subscription (integrate with payment processor)
        handleSubscription(selectedPlan);
    });

    function handleSubscription(plan) {
        if (!Netflix.is_logged_in) {
            showLoginModal();
            return;
        }

        // For demo purposes, we'll just update the user's subscription
        $.ajax({
            url: Netflix.ajax_url,
            type: 'POST',
            data: {
                action: 'netflix_update_subscription',
                nonce: Netflix.nonce,
                plan: plan
            },
            success: function(response) {
                if (response.success) {
                    $('#netflix-subscription-modal').fadeOut();
                    showNotification('Subscription updated successfully!', 'success');
                    location.reload(); // Refresh to update access
                } else {
                    showNotification('Failed to update subscription', 'error');
                }
            }
        });
    }

    /**
     * Modal functionality
     */
    $('.netflix-modal-close').click(function() {
        $(this).closest('.netflix-modal, .netflix-subscription-modal').fadeOut();
        
        // Stop video if modal is closed
        $(this).closest('.netflix-modal').find('video').each(function() {
            this.pause();
        });
    });

    // Close modals when clicking outside
    $('.netflix-modal, .netflix-subscription-modal').click(function(e) {
        if (e.target === this) {
            $(this).fadeOut();
            $(this).find('video').each(function() {
                this.pause();
            });
        }
    });

    // ESC key to close modals
    $(document).keyup(function(e) {
        if (e.keyCode === 27) {
            $('.netflix-modal, .netflix-subscription-modal').fadeOut();
            $('.netflix-modal video').each(function() {
                this.pause();
            });
        }
    });

    /**
     * Utility functions
     */
    function showLoadingOverlay() {
        $('#netflix-loading-overlay').fadeIn();
    }

    function hideLoadingOverlay() {
        $('#netflix-loading-overlay').fadeOut();
    }

    function showLoginModal() {
        // Redirect to login page or show login modal
        window.location.href = '/wp-login.php';
    }

    function showSubscriptionModal() {
        $('#netflix-subscription-modal').fadeIn();
    }

    function showNotification(message, type) {
        const notification = $(`
            <div class="netflix-notification netflix-notification-${type}">
                <span>${message}</span>
                <button class="notification-close">&times;</button>
            </div>
        `);
        
        $('body').append(notification);
        
        setTimeout(function() {
            notification.fadeOut(function() {
                notification.remove();
            });
        }, 3000);
        
        notification.find('.notification-close').click(function() {
            notification.fadeOut(function() {
                notification.remove();
            });
        });
    }

    /**
     * Analytics tracking
     */
    window.netflix_track_event = function(event, postId, additionalData) {
        $.ajax({
            url: Netflix.ajax_url,
            type: 'POST',
            data: {
                action: 'netflix_track_analytics',
                nonce: Netflix.nonce,
                event: event,
                post_id: postId,
                data: additionalData || {}
            }
        });
    };

    /**
     * Video player enhancements
     */
    $(document).on('play', 'video', function() {
        const video = this;
        const $container = $(video).closest('.netflix-player-container');
        const postId = $container.data('post-id');
        
        if (postId) {
            netflix_track_event('video_play', postId);
        }
        
        // Update progress periodically
        const progressInterval = setInterval(function() {
            if (video.paused || video.ended) {
                clearInterval(progressInterval);
                return;
            }
            
            const progress = (video.currentTime / video.duration) * 100;
            if (postId && progress > 10) { // Only track if watched more than 10%
                netflix_track_event('video_progress', postId, {
                    progress: progress,
                    current_time: video.currentTime,
                    duration: video.duration
                });
            }
        }, 30000); // Track every 30 seconds
    });

    $(document).on('ended', 'video', function() {
        const $container = $(this).closest('.netflix-player-container');
        const postId = $container.data('post-id');
        
        if (postId) {
            netflix_track_event('video_complete', postId);
        }
    });

    /**
     * Infinite scroll for content grids
     */
    if ($('.netflix-search-grid, .netflix-movies-grid, .netflix-tv-shows-grid').length) {
        let loading = false;
        let page = 2;
        
        $(window).scroll(function() {
            if (loading) return;
            
            if ($(window).scrollTop() + $(window).height() >= $(document).height() - 1000) {
                loading = true;
                loadMoreContent();
            }
        });
        
        function loadMoreContent() {
            // This would integrate with WordPress pagination
            // For now, we'll just show a loading indicator
            $('.netflix-content-section').append('<div class="netflix-loading-more">Loading more content...</div>');
            
            setTimeout(function() {
                $('.netflix-loading-more').remove();
                loading = false;
            }, 2000);
        }
    }

    /**
     * Keyboard shortcuts
     */
    $(document).keydown(function(e) {
        // Only apply shortcuts when video modal is open
        if (!$('#netflix-video-modal').is(':visible')) return;
        
        const video = $('#netflix-video-modal video')[0];
        if (!video) return;
        
        switch(e.keyCode) {
            case 32: // Spacebar - play/pause
                e.preventDefault();
                if (video.paused) {
                    video.play();
                } else {
                    video.pause();
                }
                break;
            case 37: // Left arrow - rewind 10s
                e.preventDefault();
                video.currentTime = Math.max(video.currentTime - 10, 0);
                break;
            case 39: // Right arrow - forward 10s
                e.preventDefault();
                video.currentTime = Math.min(video.currentTime + 10, video.duration);
                break;
            case 38: // Up arrow - volume up
                e.preventDefault();
                video.volume = Math.min(video.volume + 0.1, 1);
                break;
            case 40: // Down arrow - volume down
                e.preventDefault();
                video.volume = Math.max(video.volume - 0.1, 0);
                break;
            case 70: // F - fullscreen
                e.preventDefault();
                if (video.requestFullscreen) {
                    video.requestFullscreen();
                } else if (video.webkitRequestFullscreen) {
                    video.webkitRequestFullscreen();
                }
                break;
        }
    });

    /**
     * Auto-hide controls
     */
    let controlsTimeout;
    
    $(document).on('mousemove', '.netflix-player-container', function() {
        const $container = $(this);
        const $controls = $container.find('.netflix-player-overlay');
        
        $controls.fadeIn();
        
        clearTimeout(controlsTimeout);
        controlsTimeout = setTimeout(function() {
            if (!$container.find('video')[0].paused) {
                $controls.fadeOut();
            }
        }, 3000);
    });

    $(document).on('mouseleave', '.netflix-player-container', function() {
        const $container = $(this);
        const $controls = $container.find('.netflix-player-overlay');
        
        if (!$container.find('video')[0].paused) {
            $controls.fadeOut();
        }
    });

    /**
     * Initialize theme
     */
    function initNetflixTheme() {
        // Add loading class to body until everything is loaded
        $('body').addClass('netflix-loading');
        
        $(window).on('load', function() {
            $('body').removeClass('netflix-loading');
        });
        
        // Initialize any additional components
        initLazyLoading();
        initTooltips();
    }

    function initLazyLoading() {
        // Lazy load images
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver(function(entries, observer) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        imageObserver.unobserve(img);
                    }
                });
            });

            $('.lazy').each(function() {
                imageObserver.observe(this);
            });
        }
    }

    function initTooltips() {
        // Simple tooltip implementation
        $('[title]').hover(
            function() {
                const title = $(this).attr('title');
                $(this).data('tipText', title).removeAttr('title');
                $('<div class="netflix-tooltip"></div>')
                    .text(title)
                    .appendTo('body')
                    .fadeIn();
            },
            function() {
                $(this).attr('title', $(this).data('tipText'));
                $('.netflix-tooltip').remove();
            }
        ).mousemove(function(e) {
            $('.netflix-tooltip').css({
                top: e.pageY + 10,
                left: e.pageX + 10
            });
        });
    }

    // Initialize everything
    initNetflixTheme();
});

/**
 * Service Worker for offline functionality (optional)
 */
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js')
        .then(function(registration) {
            console.log('ServiceWorker registration successful');
        })
        .catch(function(err) {
            console.log('ServiceWorker registration failed');
        });
}