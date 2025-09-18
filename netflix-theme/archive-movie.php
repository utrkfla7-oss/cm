<?php
/**
 * Archive template for Movies
 * 
 * @package Netflix_Theme
 */

get_header(); ?>

<main class="netflix-main">
    <div class="netflix-content-section">
        <div class="netflix-archive-header">
            <h1><?php esc_html_e('Movies', 'netflix-theme'); ?></h1>
            <p><?php esc_html_e('Discover amazing movies to watch', 'netflix-theme'); ?></p>
        </div>

        <!-- Filter Bar -->
        <div class="netflix-filter-bar">
            <div class="netflix-filter-group">
                <label for="genre-filter"><?php esc_html_e('Genre:', 'netflix-theme'); ?></label>
                <select id="genre-filter">
                    <option value=""><?php esc_html_e('All Genres', 'netflix-theme'); ?></option>
                    <?php
                    $genres = get_terms(array('taxonomy' => 'genre', 'hide_empty' => true));
                    foreach ($genres as $genre) {
                        echo '<option value="' . esc_attr($genre->slug) . '">' . esc_html($genre->name) . '</option>';
                    }
                    ?>
                </select>
            </div>
            
            <div class="netflix-filter-group">
                <label for="year-filter"><?php esc_html_e('Year:', 'netflix-theme'); ?></label>
                <select id="year-filter">
                    <option value=""><?php esc_html_e('All Years', 'netflix-theme'); ?></option>
                    <?php
                    $years = get_terms(array('taxonomy' => 'release_year', 'hide_empty' => true, 'orderby' => 'name', 'order' => 'DESC'));
                    foreach ($years as $year) {
                        echo '<option value="' . esc_attr($year->slug) . '">' . esc_html($year->name) . '</option>';
                    }
                    ?>
                </select>
            </div>
            
            <div class="netflix-filter-group">
                <label for="sort-filter"><?php esc_html_e('Sort by:', 'netflix-theme'); ?></label>
                <select id="sort-filter">
                    <option value="date"><?php esc_html_e('Latest', 'netflix-theme'); ?></option>
                    <option value="title"><?php esc_html_e('Title A-Z', 'netflix-theme'); ?></option>
                    <option value="popular"><?php esc_html_e('Most Popular', 'netflix-theme'); ?></option>
                    <option value="rating"><?php esc_html_e('Highest Rated', 'netflix-theme'); ?></option>
                </select>
            </div>
        </div>

        <?php if (have_posts()) : ?>
            <div class="netflix-movies-grid" data-columns="4">
                <?php while (have_posts()) : the_post(); ?>
                    <div class="netflix-movie-card">
                        <div class="netflix-movie-poster">
                            <a href="<?php the_permalink(); ?>">
                                <?php if (has_post_thumbnail()) : ?>
                                    <?php the_post_thumbnail('netflix-poster', array('alt' => get_the_title())); ?>
                                <?php else : ?>
                                    <div class="netflix-no-poster">
                                        <i class="fas fa-film"></i>
                                        <span><?php esc_html_e('No Image', 'netflix-theme'); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="netflix-movie-overlay">
                                    <div class="netflix-movie-actions">
                                        <button class="netflix-action-btn netflix-play-movie" data-id="<?php the_ID(); ?>">
                                            <i class="fas fa-play"></i>
                                        </button>
                                        <button class="netflix-action-btn netflix-add-to-list" data-id="<?php the_ID(); ?>">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                        <button class="netflix-action-btn netflix-more-info" data-id="<?php the_ID(); ?>">
                                            <i class="fas fa-info-circle"></i>
                                        </button>
                                    </div>
                                    
                                    <div class="netflix-movie-info">
                                        <h3 class="netflix-movie-title"><?php the_title(); ?></h3>
                                        <div class="netflix-movie-meta">
                                            <?php
                                            $duration = get_post_meta(get_the_ID(), '_netflix_duration', true);
                                            $rating = get_the_terms(get_the_ID(), 'content_rating');
                                            
                                            if ($duration) {
                                                echo '<span class="duration">' . esc_html($duration) . ' min</span>';
                                            }
                                            if ($rating && !is_wp_error($rating)) {
                                                echo '<span class="rating">' . esc_html($rating[0]->name) . '</span>';
                                            }
                                            ?>
                                        </div>
                                        <p class="netflix-movie-description">
                                            <?php echo wp_trim_words(get_the_excerpt(), 15); ?>
                                        </p>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <!-- Pagination -->
            <div class="netflix-pagination">
                <?php
                the_posts_pagination(array(
                    'prev_text' => '<i class="fas fa-chevron-left"></i> ' . esc_html__('Previous', 'netflix-theme'),
                    'next_text' => esc_html__('Next', 'netflix-theme') . ' <i class="fas fa-chevron-right"></i>',
                ));
                ?>
            </div>

        <?php else : ?>
            <div class="netflix-no-content">
                <h2><?php esc_html_e('No Movies Found', 'netflix-theme'); ?></h2>
                <p><?php esc_html_e('Sorry, no movies match your criteria. Try adjusting your filters.', 'netflix-theme'); ?></p>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
jQuery(document).ready(function($) {
    // Filter functionality
    $('#genre-filter, #year-filter, #sort-filter').change(function() {
        const genre = $('#genre-filter').val();
        const year = $('#year-filter').val();
        const sort = $('#sort-filter').val();
        
        let url = window.location.pathname + '?';
        const params = new URLSearchParams();
        
        if (genre) params.append('genre', genre);
        if (year) params.append('year', year);
        if (sort) params.append('orderby', sort);
        
        url += params.toString();
        window.location.href = url;
    });
    
    // Set current filter values from URL
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('genre')) $('#genre-filter').val(urlParams.get('genre'));
    if (urlParams.get('year')) $('#year-filter').val(urlParams.get('year'));
    if (urlParams.get('orderby')) $('#sort-filter').val(urlParams.get('orderby'));
});
</script>

<?php get_footer(); ?>