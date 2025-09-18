<?php
/**
 * Single Movie Template
 * 
 * @package Netflix_Theme
 */

get_header(); ?>

<?php if (have_posts()) : while (have_posts()) : the_post(); ?>

<main class="netflix-main netflix-single-content">
    
    <!-- Hero Section -->
    <section class="netflix-detail-hero" style="background-image: url('<?php echo esc_url(get_the_post_thumbnail_url(get_the_ID(), 'netflix-hero')); ?>');">
        <div class="netflix-detail-content">
            <h1 class="netflix-detail-title"><?php the_title(); ?></h1>
            
            <div class="netflix-detail-meta">
                <?php
                $duration = get_post_meta(get_the_ID(), '_netflix_duration', true);
                $release_year = get_the_terms(get_the_ID(), 'release_year');
                $content_rating = get_the_terms(get_the_ID(), 'content_rating');
                $genres = get_the_terms(get_the_ID(), 'genre');
                
                if ($release_year && !is_wp_error($release_year)) {
                    echo '<span class="release-year">' . esc_html($release_year[0]->name) . '</span>';
                }
                
                if ($content_rating && !is_wp_error($content_rating)) {
                    echo '<span class="netflix-rating">' . esc_html($content_rating[0]->name) . '</span>';
                }
                
                if ($duration) {
                    echo '<span class="duration">' . esc_html($duration) . ' min</span>';
                }
                ?>
            </div>
            
            <p class="netflix-detail-description"><?php echo wp_trim_words(get_the_excerpt(), 30); ?></p>
            
            <div class="netflix-detail-actions">
                <?php
                $video_url = get_post_meta(get_the_ID(), '_netflix_video_url', true);
                $trailer_url = get_post_meta(get_the_ID(), '_netflix_trailer_url', true);
                ?>
                
                <?php if ($video_url): ?>
                    <button class="netflix-btn netflix-btn-primary netflix-play-movie" data-id="<?php the_ID(); ?>">
                        <i class="fas fa-play"></i> <?php esc_html_e('Play', 'netflix-theme'); ?>
                    </button>
                <?php endif; ?>
                
                <?php if ($trailer_url): ?>
                    <button class="netflix-btn netflix-btn-secondary netflix-play-trailer" data-trailer="<?php echo esc_url($trailer_url); ?>">
                        <i class="fas fa-info-circle"></i> <?php esc_html_e('Trailer', 'netflix-theme'); ?>
                    </button>
                <?php endif; ?>
                
                <?php if (is_user_logged_in()): ?>
                    <button class="netflix-btn netflix-btn-secondary netflix-add-to-list" data-id="<?php the_ID(); ?>">
                        <i class="fas fa-plus"></i> <?php esc_html_e('My List', 'netflix-theme'); ?>
                    </button>
                    
                    <button class="netflix-btn netflix-btn-secondary netflix-like" data-id="<?php the_ID(); ?>">
                        <i class="fas fa-thumbs-up"></i>
                    </button>
                    
                    <button class="netflix-btn netflix-btn-secondary netflix-dislike" data-id="<?php the_ID(); ?>">
                        <i class="fas fa-thumbs-down"></i>
                    </button>
                <?php endif; ?>
                
                <button class="netflix-btn netflix-btn-secondary netflix-share" data-id="<?php the_ID(); ?>">
                    <i class="fas fa-share"></i> <?php esc_html_e('Share', 'netflix-theme'); ?>
                </button>
            </div>
        </div>
    </section>

    <!-- Video Player Section -->
    <?php if ($video_url && netflix_check_content_access(get_the_ID())): ?>
    <section class="netflix-video-section">
        <div class="netflix-container">
            <?php echo do_shortcode('[netflix_player id="' . get_the_ID() . '" width="100%" height="600px"]'); ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Movie Details -->
    <section class="netflix-content-details">
        <div class="netflix-container">
            <div class="netflix-details-grid">
                
                <div class="netflix-main-details">
                    <h2><?php esc_html_e('About this movie', 'netflix-theme'); ?></h2>
                    
                    <div class="netflix-description">
                        <?php the_content(); ?>
                    </div>
                    
                    <?php if ($genres && !is_wp_error($genres)): ?>
                    <div class="netflix-genres">
                        <h3><?php esc_html_e('Genres', 'netflix-theme'); ?></h3>
                        <div class="netflix-genre-list">
                            <?php foreach ($genres as $genre): ?>
                                <a href="<?php echo esc_url(get_term_link($genre)); ?>" class="netflix-genre-tag">
                                    <?php echo esc_html($genre->name); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="netflix-sidebar-details">
                    <div class="netflix-movie-info">
                        <?php
                        $director = get_post_meta(get_the_ID(), '_netflix_director', true);
                        $cast = get_post_meta(get_the_ID(), '_netflix_cast', true);
                        $imdb_id = get_post_meta(get_the_ID(), '_netflix_imdb_id', true);
                        $tmdb_id = get_post_meta(get_the_ID(), '_netflix_tmdb_id', true);
                        ?>
                        
                        <?php if ($director): ?>
                        <div class="netflix-info-item">
                            <strong><?php esc_html_e('Director:', 'netflix-theme'); ?></strong>
                            <span><?php echo esc_html($director); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($cast): ?>
                        <div class="netflix-info-item">
                            <strong><?php esc_html_e('Cast:', 'netflix-theme'); ?></strong>
                            <span><?php echo esc_html($cast); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($duration): ?>
                        <div class="netflix-info-item">
                            <strong><?php esc_html_e('Runtime:', 'netflix-theme'); ?></strong>
                            <span><?php echo esc_html($duration); ?> minutes</span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="netflix-info-item">
                            <strong><?php esc_html_e('Release Date:', 'netflix-theme'); ?></strong>
                            <span><?php echo get_the_date(); ?></span>
                        </div>
                        
                        <?php if ($imdb_id): ?>
                        <div class="netflix-info-item">
                            <strong><?php esc_html_e('IMDb:', 'netflix-theme'); ?></strong>
                            <a href="https://www.imdb.com/title/<?php echo esc_attr($imdb_id); ?>" target="_blank" rel="noopener">
                                <?php esc_html_e('View on IMDb', 'netflix-theme'); ?>
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <div class="netflix-social-share">
                            <h3><?php esc_html_e('Share', 'netflix-theme'); ?></h3>
                            <div class="netflix-share-buttons">
                                <a href="#" class="netflix-share-btn" data-platform="facebook">
                                    <i class="fab fa-facebook-f"></i>
                                </a>
                                <a href="#" class="netflix-share-btn" data-platform="twitter">
                                    <i class="fab fa-twitter"></i>
                                </a>
                                <a href="#" class="netflix-share-btn" data-platform="whatsapp">
                                    <i class="fab fa-whatsapp"></i>
                                </a>
                                <a href="#" class="netflix-share-btn" data-platform="copy">
                                    <i class="fas fa-link"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </section>

    <!-- Related Movies -->
    <section class="netflix-related-content">
        <div class="netflix-container">
            <h2><?php esc_html_e('More Like This', 'netflix-theme'); ?></h2>
            
            <?php
            // Get related movies by genre
            $movie_genres = get_the_terms(get_the_ID(), 'genre');
            if ($movie_genres && !is_wp_error($movie_genres)) {
                $genre_ids = wp_list_pluck($movie_genres, 'term_id');
                
                $related_query = new WP_Query(array(
                    'post_type' => 'movie',
                    'posts_per_page' => 12,
                    'post__not_in' => array(get_the_ID()),
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'genre',
                            'field' => 'term_id',
                            'terms' => $genre_ids,
                        ),
                    ),
                    'orderby' => 'rand',
                ));
                
                if ($related_query->have_posts()) :
            ?>
            <div class="netflix-slider">
                <div class="netflix-slider-content">
                    <?php while ($related_query->have_posts()) : $related_query->the_post(); ?>
                        <div class="netflix-card" style="background-image: url('<?php echo esc_url(get_the_post_thumbnail_url(get_the_ID(), 'netflix-thumb')); ?>');">
                            <a href="<?php the_permalink(); ?>" class="netflix-card-link">
                                <div class="netflix-card-overlay">
                                    <h3 class="netflix-card-title"><?php the_title(); ?></h3>
                                    <div class="netflix-card-meta">
                                        <?php
                                        $duration = get_post_meta(get_the_ID(), '_netflix_duration', true);
                                        if ($duration) {
                                            echo esc_html($duration . ' min');
                                        }
                                        ?>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endwhile; ?>
                </div>
                
                <button class="netflix-slider-prev" aria-label="<?php esc_attr_e('Previous', 'netflix-theme'); ?>">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="netflix-slider-next" aria-label="<?php esc_attr_e('Next', 'netflix-theme'); ?>">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            <?php
                wp_reset_postdata();
                endif;
            }
            ?>
        </div>
    </section>

    <!-- Comments Section -->
    <?php if (comments_open() || get_comments_number()) : ?>
    <section class="netflix-comments-section">
        <div class="netflix-container">
            <?php comments_template(); ?>
        </div>
    </section>
    <?php endif; ?>

</main>

<?php endwhile; endif; ?>

<script>
jQuery(document).ready(function($) {
    // Share functionality
    $('.netflix-share-btn').click(function(e) {
        e.preventDefault();
        const platform = $(this).data('platform');
        const url = window.location.href;
        const title = '<?php echo esc_js(get_the_title()); ?>';
        
        let shareUrl = '';
        
        switch(platform) {
            case 'facebook':
                shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`;
                break;
            case 'twitter':
                shareUrl = `https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}&text=${encodeURIComponent(title)}`;
                break;
            case 'whatsapp':
                shareUrl = `https://wa.me/?text=${encodeURIComponent(title + ' ' + url)}`;
                break;
            case 'copy':
                navigator.clipboard.writeText(url).then(function() {
                    alert('Link copied to clipboard!');
                });
                return;
        }
        
        if (shareUrl) {
            window.open(shareUrl, '_blank', 'width=600,height=400');
        }
    });
});
</script>

<?php get_footer(); ?>