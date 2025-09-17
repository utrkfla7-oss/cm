/**
 * CinemaBot Pro - Search and Filter JavaScript
 * Handles movie/TV show search and filtering functionality
 */

(function($) {
    'use strict';

    let searchTimeout;
    let currentFilters = {};
    let isLoading = false;

    $(document).ready(function() {
        initializeSearch();
        setupEventListeners();
        loadInitialContent();
    });

    function initializeSearch() {
        // Initialize filter states
        currentFilters = {
            search: '',
            type: 'all',
            genre: 'all',
            year: 'all',
            rating: 'all',
            language: 'all'
        };
    }

    function setupEventListeners() {
        // Search input with debounce
        $(document).on('input', '.cinemabotpro-search-input', function() {
            const query = $(this).val();
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                performSearch(query);
            }, 300);
        });

        // Filter changes
        $(document).on('change', '.cinemabotpro-filter-select', function() {
            const filterType = $(this).data('filter');
            const value = $(this).val();
            updateFilter(filterType, value);
        });

        // Content type toggle
        $(document).on('click', '.cinemabotpro-content-type', function() {
            $('.cinemabotpro-content-type').removeClass('active');
            $(this).addClass('active');
            const type = $(this).data('type');
            updateFilter('type', type);
        });

        // Sort options
        $(document).on('change', '.cinemabotpro-sort-select', function() {
            const sortBy = $(this).val();
            sortResults(sortBy);
        });

        // Load more
        $(document).on('click', '.cinemabotpro-load-more', loadMoreContent);

        // Content item clicks
        $(document).on('click', '.cinemabotpro-content-item', function() {
            const contentId = $(this).data('content-id');
            showContentDetails(contentId);
        });

        // Add to favorites
        $(document).on('click', '.cinemabotpro-add-favorite', function(e) {
            e.stopPropagation();
            const contentId = $(this).data('content-id');
            toggleFavorite(contentId, $(this));
        });

        // Rating interaction
        $(document).on('click', '.cinemabotpro-rating-star', function() {
            const contentId = $(this).data('content-id');
            const rating = $(this).data('rating');
            submitRating(contentId, rating);
        });
    }

    function performSearch(query) {
        updateFilter('search', query);
        loadContent();
    }

    function updateFilter(filterType, value) {
        currentFilters[filterType] = value;
        loadContent();
    }

    function loadContent(append = false) {
        if (isLoading) return;

        isLoading = true;
        showLoading();

        const data = {
            action: 'cinemabotpro_search_content',
            filters: currentFilters,
            page: append ? getNextPage() : 1,
            nonce: cinemabotpro_ajax.nonce
        };

        $.ajax({
            url: cinemabotpro_ajax.ajax_url,
            type: 'POST',
            data: data,
            success: function(response) {
                hideLoading();
                isLoading = false;

                if (response.success) {
                    if (append) {
                        appendContent(response.data.content);
                    } else {
                        displayContent(response.data.content);
                    }
                    
                    updateResultsCount(response.data.total);
                    updateLoadMoreButton(response.data.has_more);
                } else {
                    showError('Failed to load content');
                }
            },
            error: function() {
                hideLoading();
                isLoading = false;
                showError('Network error occurred');
            }
        });
    }

    function loadInitialContent() {
        loadContent();
    }

    function loadMoreContent() {
        loadContent(true);
    }

    function displayContent(content) {
        const container = $('.cinemabotpro-content-grid');
        container.empty();

        if (content.length === 0) {
            container.html('<div class="cinemabotpro-no-results">No content found matching your criteria.</div>');
            return;
        }

        content.forEach(item => {
            container.append(createContentCard(item));
        });
    }

    function appendContent(content) {
        const container = $('.cinemabotpro-content-grid');
        
        content.forEach(item => {
            container.append(createContentCard(item));
        });
    }

    function createContentCard(item) {
        const isFavorite = item.is_favorite ? 'active' : '';
        const ratingStars = generateRatingStars(item.rating, item.id);
        
        return `
            <div class="cinemabotpro-content-item" data-content-id="${item.id}">
                <div class="cinemabotpro-content-poster">
                    <img src="${item.poster_url}" alt="${item.title}" loading="lazy">
                    <div class="cinemabotpro-content-overlay">
                        <button class="cinemabotpro-add-favorite ${isFavorite}" data-content-id="${item.id}">
                            <i class="fas fa-heart"></i>
                        </button>
                        <div class="cinemabotpro-content-type-badge">${item.type}</div>
                    </div>
                </div>
                <div class="cinemabotpro-content-info">
                    <h3 class="cinemabotpro-content-title">${item.title}</h3>
                    <div class="cinemabotpro-content-meta">
                        <span class="cinemabotpro-content-year">${item.year}</span>
                        <span class="cinemabotpro-content-genre">${item.genres.join(', ')}</span>
                    </div>
                    <div class="cinemabotpro-content-rating">
                        ${ratingStars}
                        <span class="cinemabotpro-rating-text">${item.rating}/5</span>
                    </div>
                    <p class="cinemabotpro-content-description">${item.description}</p>
                </div>
            </div>
        `;
    }

    function generateRatingStars(rating, contentId) {
        let stars = '';
        for (let i = 1; i <= 5; i++) {
            const filled = i <= rating ? 'filled' : '';
            stars += `<span class="cinemabotpro-rating-star ${filled}" data-content-id="${contentId}" data-rating="${i}">
                <i class="fas fa-star"></i>
            </span>`;
        }
        return stars;
    }

    function showContentDetails(contentId) {
        // Show loading modal
        showModal('Loading...');

        $.ajax({
            url: cinemabotpro_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'cinemabotpro_get_content_details',
                content_id: contentId,
                nonce: cinemabotpro_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showContentModal(response.data);
                } else {
                    showModal('Error loading content details');
                }
            },
            error: function() {
                showModal('Network error occurred');
            }
        });
    }

    function showContentModal(content) {
        const modalContent = `
            <div class="cinemabotpro-content-modal">
                <div class="cinemabotpro-modal-header">
                    <h2>${content.title}</h2>
                    <button class="cinemabotpro-modal-close">&times;</button>
                </div>
                <div class="cinemabotpro-modal-body">
                    <div class="cinemabotpro-modal-poster">
                        <img src="${content.poster_url}" alt="${content.title}">
                    </div>
                    <div class="cinemabotpro-modal-details">
                        <div class="cinemabotpro-modal-meta">
                            <span class="cinemabotpro-modal-year">${content.year}</span>
                            <span class="cinemabotpro-modal-type">${content.type}</span>
                            <span class="cinemabotpro-modal-duration">${content.duration}</span>
                        </div>
                        <div class="cinemabotpro-modal-genres">
                            ${content.genres.map(genre => `<span class="cinemabotpro-genre-tag">${genre}</span>`).join('')}
                        </div>
                        <div class="cinemabotpro-modal-rating">
                            Rating: ${content.rating}/5 (${content.votes} votes)
                        </div>
                        <div class="cinemabotpro-modal-description">
                            <p>${content.description}</p>
                        </div>
                        <div class="cinemabotpro-modal-cast">
                            <h4>Cast</h4>
                            <p>${content.cast.join(', ')}</p>
                        </div>
                        <div class="cinemabotpro-modal-director">
                            <h4>Director</h4>
                            <p>${content.director}</p>
                        </div>
                        ${content.trailer_url ? `
                            <div class="cinemabotpro-modal-trailer">
                                <h4>Trailer</h4>
                                <iframe src="${content.trailer_url}" frameborder="0" allowfullscreen></iframe>
                            </div>
                        ` : ''}
                        <div class="cinemabotpro-modal-actions">
                            <button class="cinemabotpro-btn cinemabotpro-btn-primary cinemabotpro-add-favorite" data-content-id="${content.id}">
                                <i class="fas fa-heart"></i> Add to Favorites
                            </button>
                            <button class="cinemabotpro-btn cinemabotpro-btn-secondary cinemabotpro-share-content" data-content-id="${content.id}">
                                <i class="fas fa-share"></i> Share
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        showModal(modalContent);
    }

    function toggleFavorite(contentId, button) {
        const isActive = button.hasClass('active');
        const action = isActive ? 'remove' : 'add';

        $.ajax({
            url: cinemabotpro_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'cinemabotpro_toggle_favorite',
                content_id: contentId,
                action: action,
                nonce: cinemabotpro_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    button.toggleClass('active');
                    showNotification(response.data.message);
                } else {
                    showNotification('Error updating favorites', 'error');
                }
            },
            error: function() {
                showNotification('Network error occurred', 'error');
            }
        });
    }

    function submitRating(contentId, rating) {
        $.ajax({
            url: cinemabotpro_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'cinemabotpro_rate_content',
                content_id: contentId,
                rating: rating,
                nonce: cinemabotpro_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateRatingDisplay(contentId, rating);
                    showNotification('Rating submitted successfully');
                } else {
                    showNotification('Error submitting rating', 'error');
                }
            },
            error: function() {
                showNotification('Network error occurred', 'error');
            }
        });
    }

    function updateRatingDisplay(contentId, rating) {
        const stars = $(`.cinemabotpro-rating-star[data-content-id="${contentId}"]`);
        stars.removeClass('filled');
        stars.filter(`[data-rating<="${rating}"]`).addClass('filled');
    }

    function sortResults(sortBy) {
        const container = $('.cinemabotpro-content-grid');
        const items = container.children('.cinemabotpro-content-item').toArray();

        items.sort((a, b) => {
            const aItem = $(a);
            const bItem = $(b);

            switch (sortBy) {
                case 'title':
                    return aItem.find('.cinemabotpro-content-title').text().localeCompare(
                        bItem.find('.cinemabotpro-content-title').text()
                    );
                case 'year':
                    return parseInt(bItem.find('.cinemabotpro-content-year').text()) - 
                           parseInt(aItem.find('.cinemabotpro-content-year').text());
                case 'rating':
                    return parseFloat(bItem.find('.cinemabotpro-rating-text').text()) - 
                           parseFloat(aItem.find('.cinemabotpro-rating-text').text());
                default:
                    return 0;
            }
        });

        container.empty().append(items);
    }

    function showLoading() {
        $('.cinemabotpro-search-loading').show();
    }

    function hideLoading() {
        $('.cinemabotpro-search-loading').hide();
    }

    function showError(message) {
        showNotification(message, 'error');
    }

    function showNotification(message, type = 'success') {
        const notificationClass = type === 'error' ? 'cinemabotpro-notification-error' : 'cinemabotpro-notification-success';
        
        const notification = $(`
            <div class="cinemabotpro-notification ${notificationClass}">
                ${message}
                <button class="cinemabotpro-notification-close">&times;</button>
            </div>
        `);

        $('body').append(notification);
        
        setTimeout(() => {
            notification.fadeOut(() => notification.remove());
        }, 5000);
    }

    function showModal(content) {
        const modal = $(`
            <div class="cinemabotpro-modal-overlay">
                <div class="cinemabotpro-modal">
                    ${content}
                </div>
            </div>
        `);

        $('body').append(modal);
        modal.fadeIn();

        // Close modal handlers
        modal.on('click', '.cinemabotpro-modal-close, .cinemabotpro-modal-overlay', function(e) {
            if (e.target === this) {
                modal.fadeOut(() => modal.remove());
            }
        });
    }

    function updateResultsCount(total) {
        $('.cinemabotpro-results-count').text(`${total} results found`);
    }

    function updateLoadMoreButton(hasMore) {
        const button = $('.cinemabotpro-load-more');
        if (hasMore) {
            button.show();
        } else {
            button.hide();
        }
    }

    function getNextPage() {
        const currentItems = $('.cinemabotpro-content-item').length;
        const itemsPerPage = 12; // Should match backend
        return Math.floor(currentItems / itemsPerPage) + 1;
    }

    // Close notification handler
    $(document).on('click', '.cinemabotpro-notification-close', function() {
        $(this).parent().fadeOut(() => $(this).parent().remove());
    });

})(jQuery);