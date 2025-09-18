<?php
/**
 * Netflix Theme Main Template
 * 
 * @package Netflix_Theme
 */

get_header(); ?>

<main class="netflix-main">
    <?php if (is_home() || is_front_page()): ?>
        
        <!-- Hero Section -->
        <section class="netflix-hero">
            <?php
            // Get featured content for hero
            $featured_query = new WP_Query(array(
                'post_type' => array('movie', 'tv_show'),
                'posts_per_page' => 1,
                'meta_key' => '_netflix_featured',
                'meta_value' => '1',
                'orderby' => 'rand'
            ));
            
            if ($featured_query->have_posts()) :
                while ($featured_query->have_posts()) : $featured_query->the_post();
                    $poster = get_the_post_thumbnail_url(get_the_ID(), 'netflix-hero');
                    $trailer_url = get_post_meta(get_the_ID(), '_netflix_trailer_url', true);
                    $video_url = get_post_meta(get_the_ID(), '_netflix_video_url', true);
            ?>
                <div class="netflix-hero-content" style="background-image: url('<?php echo esc_url($poster); ?>');">
                    <h1><?php the_title(); ?></h1>
                    <p><?php echo wp_trim_words(get_the_excerpt(), 30); ?></p>
                    <div class="netflix-hero-buttons">
                        <?php if ($video_url): ?>
                            <a href="<?php the_permalink(); ?>" class="netflix-btn netflix-btn-primary">
                                <i class="fas fa-play"></i> <?php esc_html_e('Play', 'netflix-theme'); ?>
                            </a>
                        <?php endif; ?>
                        <?php if ($trailer_url): ?>
                            <a href="#" class="netflix-btn netflix-btn-secondary netflix-play-trailer" data-trailer="<?php echo esc_url($trailer_url); ?>">
                                <i class="fas fa-info-circle"></i> <?php esc_html_e('More Info', 'netflix-theme'); ?>
                            </a>
                        <?php endif; ?>
                        <a href="#" class="netflix-btn netflix-btn-secondary netflix-add-to-list" data-id="<?php the_ID(); ?>">
                            <i class="fas fa-plus"></i> <?php esc_html_e('My List', 'netflix-theme'); ?>
                        </a>
                    </div>
                </div>
            <?php
                endwhile;
                wp_reset_postdata();
            endif;
            ?>
        </section>

        <!-- Content Sections -->
        <div class="netflix-content-section">
            
            <!-- Trending Now -->
            <?php netflix_content_row('trending', __('Trending Now', 'netflix-theme')); ?>
            
            <!-- New Releases -->
            <?php netflix_content_row('new_releases', __('New Releases', 'netflix-theme')); ?>
            
            <!-- Popular Movies -->
            <?php netflix_content_row('popular_movies', __('Popular Movies', 'netflix-theme')); ?>
            
            <!-- Popular TV Shows -->
            <?php netflix_content_row('popular_tv_shows', __('Popular TV Shows', 'netflix-theme')); ?>
            
            <!-- My List (if user is logged in) -->
            <?php if (is_user_logged_in()): ?>
                <?php netflix_content_row('my_list', __('My List', 'netflix-theme')); ?>
            <?php endif; ?>
            
            <!-- Continue Watching (if user is logged in) -->
            <?php if (is_user_logged_in()): ?>
                <?php netflix_content_row('continue_watching', __('Continue Watching', 'netflix-theme')); ?>
            <?php endif; ?>

        </div>

    <?php else: ?>
        
        <!-- Archive/Search Results -->
        <div class="netflix-content-section">
            <?php if (have_posts()) : ?>
                
                <div class="netflix-page-header">
                    <?php if (is_search()) : ?>
                        <h1><?php printf(esc_html__('Search Results for: %s', 'netflix-theme'), get_search_query()); ?></h1>
                    <?php elseif (is_archive()) : ?>
                        <h1><?php the_archive_title(); ?></h1>
                        <?php if (get_the_archive_description()) : ?>
                            <div class="archive-description"><?php echo wp_kses_post(get_the_archive_description()); ?></div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <div class="netflix-search-grid">
                    <?php while (have_posts()) : the_post(); ?>
                        <div class="netflix-card" style="background-image: url('<?php echo esc_url(get_the_post_thumbnail_url(get_the_ID(), 'netflix-thumb')); ?>');">
                            <a href="<?php the_permalink(); ?>" class="netflix-card-link">
                                <div class="netflix-card-overlay">
                                    <h3 class="netflix-card-title"><?php the_title(); ?></h3>
                                    <div class="netflix-card-meta">
                                        <?php
                                        $post_type = get_post_type();
                                        if ($post_type === 'movie') {
                                            $duration = get_post_meta(get_the_ID(), '_netflix_duration', true);
                                            if ($duration) {
                                                echo esc_html($duration . ' min');
                                            }
                                        } elseif ($post_type === 'tv_show') {
                                            $seasons = get_post_meta(get_the_ID(), '_netflix_seasons', true);
                                            if ($seasons) {
                                                printf(esc_html__('%d Seasons', 'netflix-theme'), $seasons);
                                            }
                                        }
                                        ?>
                                    </div>
                                </div>
                            </a>
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
                    <h2><?php esc_html_e('Nothing Found', 'netflix-theme'); ?></h2>
                    <p><?php esc_html_e('Sorry, but nothing matched your search terms. Please try again with different keywords.', 'netflix-theme'); ?></p>
                    <?php get_search_form(); ?>
                </div>

            <?php endif; ?>
        </div>

    <?php endif; ?>
</main>

<?php get_footer(); ?>