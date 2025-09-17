<?php
/**
 * Movie/TV Search Template
 * Displays the search and filter interface for movies and TV shows
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$genres = get_terms(array(
    'taxonomy' => 'movie_genre',
    'hide_empty' => false
));

$years = range(date('Y'), 1950);
$languages = array(
    'en' => __('English', 'cinemabotpro'),
    'bn' => __('Bengali', 'cinemabotpro'),
    'hi' => __('Hindi', 'cinemabotpro'),
    'es' => __('Spanish', 'cinemabotpro'),
    'fr' => __('French', 'cinemabotpro'),
    'de' => __('German', 'cinemabotpro'),
    'ja' => __('Japanese', 'cinemabotpro'),
    'ko' => __('Korean', 'cinemabotpro')
);
?>

<div class="cinemabotpro-search-container">
    <!-- Search Header -->
    <div class="cinemabotpro-search-header">
        <h2><?php _e('Discover Movies & TV Shows', 'cinemabotpro'); ?></h2>
        <p><?php _e('Find your next favorite movie or TV show with our AI-powered recommendations', 'cinemabotpro'); ?></p>
    </div>

    <!-- Search Bar -->
    <div class="cinemabotpro-search-bar">
        <div class="cinemabotpro-search-input-container">
            <i class="fas fa-search cinemabotpro-search-icon"></i>
            <input type="text" 
                   class="cinemabotpro-search-input" 
                   placeholder="<?php _e('Search for movies, TV shows, actors, directors...', 'cinemabotpro'); ?>"
                   autocomplete="off">
            <div class="cinemabotpro-search-loading">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
        </div>
        
        <button class="cinemabotpro-voice-search" title="<?php _e('Voice Search', 'cinemabotpro'); ?>">
            <i class="fas fa-microphone"></i>
        </button>
        
        <button class="cinemabotpro-advanced-search-toggle" title="<?php _e('Advanced Search', 'cinemabotpro'); ?>">
            <i class="fas fa-sliders-h"></i>
        </button>
    </div>

    <!-- Content Type Toggle -->
    <div class="cinemabotpro-content-types">
        <button class="cinemabotpro-content-type active" data-type="all">
            <i class="fas fa-th"></i>
            <span><?php _e('All', 'cinemabotpro'); ?></span>
        </button>
        <button class="cinemabotpro-content-type" data-type="movies">
            <i class="fas fa-film"></i>
            <span><?php _e('Movies', 'cinemabotpro'); ?></span>
        </button>
        <button class="cinemabotpro-content-type" data-type="tv_shows">
            <i class="fas fa-tv"></i>
            <span><?php _e('TV Shows', 'cinemabotpro'); ?></span>
        </button>
        <button class="cinemabotpro-content-type" data-type="documentaries">
            <i class="fas fa-book-open"></i>
            <span><?php _e('Documentaries', 'cinemabotpro'); ?></span>
        </button>
    </div>

    <!-- Advanced Filters -->
    <div class="cinemabotpro-advanced-filters" id="cinemabotpro-advanced-filters">
        <div class="cinemabotpro-filters-row">
            <div class="cinemabotpro-filter-group">
                <label><?php _e('Genre', 'cinemabotpro'); ?></label>
                <select class="cinemabotpro-filter-select" data-filter="genre">
                    <option value="all"><?php _e('All Genres', 'cinemabotpro'); ?></option>
                    <?php foreach ($genres as $genre): ?>
                        <option value="<?php echo esc_attr($genre->slug); ?>">
                            <?php echo esc_html($genre->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="cinemabotpro-filter-group">
                <label><?php _e('Year', 'cinemabotpro'); ?></label>
                <select class="cinemabotpro-filter-select" data-filter="year">
                    <option value="all"><?php _e('All Years', 'cinemabotpro'); ?></option>
                    <?php foreach ($years as $year): ?>
                        <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="cinemabotpro-filter-group">
                <label><?php _e('Rating', 'cinemabotpro'); ?></label>
                <select class="cinemabotpro-filter-select" data-filter="rating">
                    <option value="all"><?php _e('All Ratings', 'cinemabotpro'); ?></option>
                    <option value="5">5 <?php _e('Stars', 'cinemabotpro'); ?></option>
                    <option value="4">4+ <?php _e('Stars', 'cinemabotpro'); ?></option>
                    <option value="3">3+ <?php _e('Stars', 'cinemabotpro'); ?></option>
                    <option value="2">2+ <?php _e('Stars', 'cinemabotpro'); ?></option>
                </select>
            </div>
            
            <div class="cinemabotpro-filter-group">
                <label><?php _e('Language', 'cinemabotpro'); ?></label>
                <select class="cinemabotpro-filter-select" data-filter="language">
                    <option value="all"><?php _e('All Languages', 'cinemabotpro'); ?></option>
                    <?php foreach ($languages as $code => $name): ?>
                        <option value="<?php echo esc_attr($code); ?>">
                            <?php echo esc_html($name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="cinemabotpro-filters-row">
            <div class="cinemabotpro-filter-group">
                <label><?php _e('Sort By', 'cinemabotpro'); ?></label>
                <select class="cinemabotpro-sort-select">
                    <option value="relevance"><?php _e('Relevance', 'cinemabotpro'); ?></option>
                    <option value="popularity"><?php _e('Popularity', 'cinemabotpro'); ?></option>
                    <option value="rating"><?php _e('Rating', 'cinemabotpro'); ?></option>
                    <option value="year"><?php _e('Release Year', 'cinemabotpro'); ?></option>
                    <option value="title"><?php _e('Title (A-Z)', 'cinemabotpro'); ?></option>
                </select>
            </div>
            
            <div class="cinemabotpro-filter-group">
                <label><?php _e('Duration', 'cinemabotpro'); ?></label>
                <select class="cinemabotpro-filter-select" data-filter="duration">
                    <option value="all"><?php _e('Any Duration', 'cinemabotpro'); ?></option>
                    <option value="short"><?php _e('Short (< 90 min)', 'cinemabotpro'); ?></option>
                    <option value="medium"><?php _e('Medium (90-150 min)', 'cinemabotpro'); ?></option>
                    <option value="long"><?php _e('Long (> 150 min)', 'cinemabotpro'); ?></option>
                </select>
            </div>
            
            <div class="cinemabotpro-filter-actions">
                <button class="cinemabotpro-clear-filters">
                    <i class="fas fa-times"></i>
                    <?php _e('Clear Filters', 'cinemabotpro'); ?>
                </button>
                
                <button class="cinemabotpro-save-search">
                    <i class="fas fa-bookmark"></i>
                    <?php _e('Save Search', 'cinemabotpro'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Search Results Header -->
    <div class="cinemabotpro-results-header">
        <div class="cinemabotpro-results-info">
            <span class="cinemabotpro-results-count">0 results found</span>
            <span class="cinemabotpro-results-time"></span>
        </div>
        
        <div class="cinemabotpro-view-options">
            <button class="cinemabotpro-view-toggle active" data-view="grid" title="<?php _e('Grid View', 'cinemabotpro'); ?>">
                <i class="fas fa-th"></i>
            </button>
            <button class="cinemabotpro-view-toggle" data-view="list" title="<?php _e('List View', 'cinemabotpro'); ?>">
                <i class="fas fa-list"></i>
            </button>
        </div>
    </div>

    <!-- Search Suggestions -->
    <div class="cinemabotpro-search-suggestions" id="cinemabotpro-search-suggestions">
        <div class="cinemabotpro-trending-searches">
            <h4><?php _e('Trending Searches', 'cinemabotpro'); ?></h4>
            <div class="cinemabotpro-trending-tags">
                <span class="cinemabotpro-trending-tag">Marvel Movies</span>
                <span class="cinemabotpro-trending-tag">Korean Drama</span>
                <span class="cinemabotpro-trending-tag">Comedy Movies 2024</span>
                <span class="cinemabotpro-trending-tag">Netflix Originals</span>
                <span class="cinemabotpro-trending-tag">Sci-Fi Thriller</span>
            </div>
        </div>
        
        <div class="cinemabotpro-ai-suggestions">
            <h4><?php _e('AI Recommendations for You', 'cinemabotpro'); ?></h4>
            <div class="cinemabotpro-ai-suggestion-cards">
                <!-- AI suggestions will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Content Grid -->
    <div class="cinemabotpro-content-grid" id="cinemabotpro-content-grid">
        <!-- Content items will be loaded here -->
    </div>

    <!-- Load More Button -->
    <div class="cinemabotpro-load-more-container">
        <button class="cinemabotpro-load-more">
            <i class="fas fa-plus"></i>
            <?php _e('Load More', 'cinemabotpro'); ?>
        </button>
    </div>

    <!-- No Results Message -->
    <div class="cinemabotpro-no-results" id="cinemabotpro-no-results" style="display: none;">
        <div class="cinemabotpro-no-results-icon">
            <i class="fas fa-search"></i>
        </div>
        <h3><?php _e('No Results Found', 'cinemabotpro'); ?></h3>
        <p><?php _e('We couldn\'t find any content matching your search criteria.', 'cinemabotpro'); ?></p>
        <div class="cinemabotpro-no-results-actions">
            <button class="cinemabotpro-clear-all-filters">
                <?php _e('Clear All Filters', 'cinemabotpro'); ?>
            </button>
            <button class="cinemabotpro-ask-ai">
                <?php _e('Ask AI for Help', 'cinemabotpro'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Voice Search Modal -->
<div class="cinemabotpro-voice-modal" id="cinemabotpro-voice-modal">
    <div class="cinemabotpro-voice-content">
        <div class="cinemabotpro-voice-animation">
            <div class="cinemabotpro-voice-circle"></div>
            <div class="cinemabotpro-voice-waves">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
        <h3><?php _e('Listening...', 'cinemabotpro'); ?></h3>
        <p><?php _e('Say something like "Show me action movies from 2023"', 'cinemabotpro'); ?></p>
        <button class="cinemabotpro-voice-stop">
            <i class="fas fa-stop"></i>
            <?php _e('Stop', 'cinemabotpro'); ?>
        </button>
    </div>
</div>

<script type="text/javascript">
// Initialize search functionality
jQuery(document).ready(function($) {
    // Toggle advanced filters
    $('.cinemabotpro-advanced-search-toggle').on('click', function() {
        $('#cinemabotpro-advanced-filters').slideToggle();
        $(this).toggleClass('active');
    });
    
    // Toggle view modes
    $('.cinemabotpro-view-toggle').on('click', function() {
        $('.cinemabotpro-view-toggle').removeClass('active');
        $(this).addClass('active');
        
        const view = $(this).data('view');
        $('#cinemabotpro-content-grid').removeClass('grid-view list-view').addClass(view + '-view');
    });
    
    // Clear all filters
    $('.cinemabotpro-clear-filters, .cinemabotpro-clear-all-filters').on('click', function() {
        $('.cinemabotpro-filter-select').val('all');
        $('.cinemabotpro-sort-select').val('relevance');
        $('.cinemabotpro-search-input').val('');
        $('.cinemabotpro-content-type').removeClass('active').first().addClass('active');
        
        // Trigger search with cleared filters
        if (typeof performSearch === 'function') {
            performSearch('');
        }
    });
    
    // Trending tag clicks
    $('.cinemabotpro-trending-tag').on('click', function() {
        const tag = $(this).text();
        $('.cinemabotpro-search-input').val(tag);
        if (typeof performSearch === 'function') {
            performSearch(tag);
        }
    });
    
    // Voice search (placeholder - requires Web Speech API)
    $('.cinemabotpro-voice-search').on('click', function() {
        if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
            $('#cinemabotpro-voice-modal').show();
            // Implementation would go here
        } else {
            alert('<?php _e('Voice search is not supported in your browser.', 'cinemabotpro'); ?>');
        }
    });
    
    // Close voice modal
    $('.cinemabotpro-voice-stop').on('click', function() {
        $('#cinemabotpro-voice-modal').hide();
    });
});
</script>