/**
 * AutoPost Movies Frontend JavaScript
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Initialize AutoPost Movies functionality
        initAutoPostMovies();
        
    });
    
    function initAutoPostMovies() {
        
        // Handle trailer button clicks with analytics
        $('.autopost-movies-trailer-button').on('click', function(e) {
            var url = $(this).attr('href');
            
            // Track trailer view if analytics are available
            if (typeof gtag !== 'undefined') {
                gtag('event', 'trailer_view', {
                    'movie_title': $('h1').text() || 'Unknown',
                    'trailer_url': url
                });
            }
            
            // Optional: Open in modal instead of new tab
            if ($(this).hasClass('modal-trailer')) {
                e.preventDefault();
                openTrailerModal(url);
            }
        });
        
        // Handle poster image lazy loading
        lazyLoadPosters();
        
        // Handle responsive trailer embeds
        makeTrailerEmbedsResponsive();
        
        // Initialize rating animations
        initRatingAnimations();
        
    }
    
    /**
     * Open trailer in modal (optional feature)
     */
    function openTrailerModal(url) {
        var videoId = extractYouTubeId(url);
        
        if (videoId) {
            var modal = $('<div class="autopost-movies-modal">' +
                '<div class="modal-content">' +
                '<span class="modal-close">&times;</span>' +
                '<iframe src="https://www.youtube.com/embed/' + videoId + '?autoplay=1" frameborder="0" allowfullscreen></iframe>' +
                '</div>' +
                '</div>');
            
            $('body').append(modal);
            modal.fadeIn();
            
            // Close modal events
            modal.find('.modal-close').on('click', function() {
                modal.fadeOut(function() { modal.remove(); });
            });
            
            modal.on('click', function(e) {
                if (e.target === this) {
                    modal.fadeOut(function() { modal.remove(); });
                }
            });
        } else {
            // Fallback to opening in new tab
            window.open(url, '_blank');
        }
    }
    
    /**
     * Extract YouTube video ID from URL
     */
    function extractYouTubeId(url) {
        var regExp = /^.*((youtu.be\/)|(v\/)|(\/u\/\w\/)|(embed\/)|(watch\?))\??v?=?([^#&?]*).*/;
        var match = url.match(regExp);
        return (match && match[7].length === 11) ? match[7] : false;
    }
    
    /**
     * Lazy load poster images
     */
    function lazyLoadPosters() {
        var posters = $('.autopost-movies-poster[data-src]');
        
        if (posters.length > 0 && 'IntersectionObserver' in window) {
            var imageObserver = new IntersectionObserver(function(entries, observer) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        var img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        imageObserver.unobserve(img);
                    }
                });
            });
            
            posters.each(function() {
                imageObserver.observe(this);
            });
        } else {
            // Fallback for browsers without IntersectionObserver
            posters.each(function() {
                this.src = this.dataset.src;
            });
        }
    }
    
    /**
     * Make trailer embeds responsive
     */
    function makeTrailerEmbedsResponsive() {
        $('.autopost-movies-trailer-embed iframe').each(function() {
            var $iframe = $(this);
            var aspectRatio = $iframe.attr('height') / $iframe.attr('width');
            
            $iframe.removeAttr('width').removeAttr('height');
            
            $iframe.wrap('<div class="responsive-iframe-container"></div>');
            
            $iframe.parent().css({
                'position': 'relative',
                'width': '100%',
                'height': 0,
                'padding-bottom': (aspectRatio * 100) + '%'
            });
            
            $iframe.css({
                'position': 'absolute',
                'top': 0,
                'left': 0,
                'width': '100%',
                'height': '100%'
            });
        });
    }
    
    /**
     * Initialize rating animations
     */
    function initRatingAnimations() {
        $('.autopost-movies-rating').each(function() {
            var $rating = $(this);
            var text = $rating.text();
            var matches = text.match(/(\d+\.?\d*)/);
            
            if (matches) {
                var value = parseFloat(matches[1]);
                var maxValue = 10;
                
                // Add visual indicator based on rating
                if (value >= 8) {
                    $rating.addClass('rating-excellent');
                } else if (value >= 6) {
                    $rating.addClass('rating-good');
                } else if (value >= 4) {
                    $rating.addClass('rating-average');
                } else {
                    $rating.addClass('rating-poor');
                }
                
                // Animate rating on scroll into view
                if ('IntersectionObserver' in window) {
                    var ratingObserver = new IntersectionObserver(function(entries) {
                        entries.forEach(function(entry) {
                            if (entry.isIntersecting) {
                                animateRating(entry.target, value, maxValue);
                                ratingObserver.unobserve(entry.target);
                            }
                        });
                    });
                    
                    ratingObserver.observe(this);
                }
            }
        });
    }
    
    /**
     * Animate rating display
     */
    function animateRating(element, value, maxValue) {
        var $element = $(element);
        var startValue = 0;
        var duration = 1000;
        var startTime = null;
        
        function animate(currentTime) {
            if (startTime === null) startTime = currentTime;
            var progress = Math.min((currentTime - startTime) / duration, 1);
            var currentValue = startValue + (value - startValue) * progress;
            
            // Update the displayed value
            var originalText = $element.text();
            var newText = originalText.replace(/\d+\.?\d*/, currentValue.toFixed(1));
            $element.text(newText);
            
            if (progress < 1) {
                requestAnimationFrame(animate);
            }
        }
        
        requestAnimationFrame(animate);
    }
    
    /**
     * Utility function to handle AJAX errors
     */
    function handleAjaxError(xhr, status, error) {
        console.error('AutoPost Movies AJAX Error:', {
            status: status,
            error: error,
            responseText: xhr.responseText
        });
    }
    
    /**
     * Track user interactions (if analytics are enabled)
     */
    function trackInteraction(action, data) {
        if (typeof autopost_movies_ajax !== 'undefined' && autopost_movies_ajax.analytics_enabled) {
            $.ajax({
                url: autopost_movies_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'autopost_movies_track_interaction',
                    interaction_action: action,
                    interaction_data: data,
                    nonce: autopost_movies_ajax.nonce
                },
                error: handleAjaxError
            });
        }
    }
    
    // Public API for external integrations
    window.AutoPostMovies = {
        trackInteraction: trackInteraction,
        openTrailerModal: openTrailerModal,
        extractYouTubeId: extractYouTubeId
    };
    
})(jQuery);

// CSS for modal and rating classes
(function() {
    var styles = `
        .autopost-movies-modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
        }
        
        .autopost-movies-modal .modal-content {
            position: relative;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 80%;
            max-width: 800px;
            background: #000;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .autopost-movies-modal .modal-close {
            position: absolute;
            top: 10px;
            right: 20px;
            color: #fff;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            z-index: 10000;
        }
        
        .autopost-movies-modal iframe {
            width: 100%;
            height: 450px;
            border: none;
        }
        
        .autopost-movies-rating.rating-excellent {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: #fff;
        }
        
        .autopost-movies-rating.rating-good {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            color: #fff;
        }
        
        .autopost-movies-rating.rating-average {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: #333;
        }
        
        .autopost-movies-rating.rating-poor {
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
            color: #333;
        }
        
        .autopost-movies-poster.lazy {
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .autopost-movies-poster:not(.lazy) {
            opacity: 1;
        }
        
        @media (max-width: 768px) {
            .autopost-movies-modal .modal-content {
                width: 95%;
                height: auto;
            }
            
            .autopost-movies-modal iframe {
                height: 250px;
            }
        }
    `;
    
    var styleSheet = document.createElement("style");
    styleSheet.type = "text/css";
    styleSheet.innerText = styles;
    document.head.appendChild(styleSheet);
})();