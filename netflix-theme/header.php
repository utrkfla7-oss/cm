<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="netflix-header" id="netflix-header">
    <nav class="netflix-nav">
        <!-- Logo -->
        <div class="netflix-brand">
            <?php if (has_custom_logo()) : ?>
                <div class="custom-logo">
                    <?php the_custom_logo(); ?>
                </div>
            <?php else : ?>
                <a href="<?php echo esc_url(home_url('/')); ?>" class="netflix-logo">
                    <?php bloginfo('name'); ?>
                </a>
            <?php endif; ?>
        </div>

        <!-- Main Navigation -->
        <div class="netflix-navigation">
            <?php
            wp_nav_menu(array(
                'theme_location' => 'primary',
                'menu_class'     => 'netflix-menu',
                'container'      => false,
                'fallback_cb'    => 'netflix_fallback_menu',
            ));
            ?>
        </div>

        <!-- User Actions -->
        <div class="netflix-user-actions">
            <!-- Search -->
            <div class="netflix-search">
                <button class="netflix-search-toggle" aria-label="<?php esc_attr_e('Search', 'netflix-theme'); ?>">
                    <i class="fas fa-search"></i>
                </button>
                <div class="netflix-search-form" style="display: none;">
                    <?php get_search_form(); ?>
                </div>
            </div>

            <!-- Notifications (for logged-in users) -->
            <?php if (is_user_logged_in()) : ?>
                <div class="netflix-notifications">
                    <button class="netflix-notifications-toggle" aria-label="<?php esc_attr_e('Notifications', 'netflix-theme'); ?>">
                        <i class="fas fa-bell"></i>
                        <span class="notification-count" style="display: none;">0</span>
                    </button>
                    <div class="netflix-notifications-dropdown" style="display: none;">
                        <div class="notifications-header">
                            <h3><?php esc_html_e('Notifications', 'netflix-theme'); ?></h3>
                        </div>
                        <div class="notifications-list">
                            <p><?php esc_html_e('No new notifications', 'netflix-theme'); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- User Menu -->
            <div class="netflix-user-menu">
                <?php if (is_user_logged_in()) : ?>
                    <div class="netflix-profile-dropdown">
                        <button class="netflix-profile-toggle" aria-label="<?php esc_attr_e('Account', 'netflix-theme'); ?>">
                            <?php echo get_avatar(get_current_user_id(), 32, '', '', array('class' => 'netflix-avatar')); ?>
                            <i class="fas fa-caret-down"></i>
                        </button>
                        <div class="netflix-profile-menu" style="display: none;">
                            <?php
                            wp_nav_menu(array(
                                'theme_location' => 'user',
                                'menu_class'     => 'netflix-user-links',
                                'container'      => false,
                                'fallback_cb'    => 'netflix_user_fallback_menu',
                            ));
                            ?>
                        </div>
                    </div>
                <?php else : ?>
                    <div class="netflix-auth-buttons">
                        <a href="<?php echo esc_url(wp_login_url()); ?>" class="netflix-btn-login">
                            <?php esc_html_e('Sign In', 'netflix-theme'); ?>
                        </a>
                        <a href="<?php echo esc_url(wp_registration_url()); ?>" class="netflix-btn-register">
                            <?php esc_html_e('Sign Up', 'netflix-theme'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Mobile Menu Toggle -->
        <button class="netflix-mobile-toggle" aria-label="<?php esc_attr_e('Menu', 'netflix-theme'); ?>">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </nav>

    <!-- Mobile Menu -->
    <div class="netflix-mobile-menu" style="display: none;">
        <div class="netflix-mobile-nav">
            <?php
            wp_nav_menu(array(
                'theme_location' => 'primary',
                'menu_class'     => 'netflix-mobile-links',
                'container'      => false,
                'fallback_cb'    => 'netflix_fallback_menu',
            ));
            ?>
        </div>
        
        <?php if (is_user_logged_in()) : ?>
            <div class="netflix-mobile-user">
                <div class="netflix-mobile-profile">
                    <?php echo get_avatar(get_current_user_id(), 48, '', '', array('class' => 'netflix-mobile-avatar')); ?>
                    <span class="netflix-mobile-username"><?php echo esc_html(wp_get_current_user()->display_name); ?></span>
                </div>
                <?php
                wp_nav_menu(array(
                    'theme_location' => 'user',
                    'menu_class'     => 'netflix-mobile-user-links',
                    'container'      => false,
                    'fallback_cb'    => 'netflix_user_fallback_menu',
                ));
                ?>
            </div>
        <?php else : ?>
            <div class="netflix-mobile-auth">
                <a href="<?php echo esc_url(wp_login_url()); ?>" class="netflix-mobile-login">
                    <?php esc_html_e('Sign In', 'netflix-theme'); ?>
                </a>
                <a href="<?php echo esc_url(wp_registration_url()); ?>" class="netflix-mobile-register">
                    <?php esc_html_e('Sign Up', 'netflix-theme'); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
</header>

<?php
/**
 * Fallback menu for primary navigation
 */
function netflix_fallback_menu() {
    echo '<ul class="netflix-menu">';
    echo '<li><a href="' . esc_url(home_url('/')) . '">' . esc_html__('Home', 'netflix-theme') . '</a></li>';
    echo '<li><a href="' . esc_url(home_url('/movies/')) . '">' . esc_html__('Movies', 'netflix-theme') . '</a></li>';
    echo '<li><a href="' . esc_url(home_url('/tv-shows/')) . '">' . esc_html__('TV Shows', 'netflix-theme') . '</a></li>';
    echo '<li><a href="' . esc_url(home_url('/genres/')) . '">' . esc_html__('Genres', 'netflix-theme') . '</a></li>';
    if (is_user_logged_in()) {
        echo '<li><a href="' . esc_url(home_url('/my-list/')) . '">' . esc_html__('My List', 'netflix-theme') . '</a></li>';
    }
    echo '</ul>';
}

/**
 * Fallback menu for user navigation
 */
function netflix_user_fallback_menu() {
    if (is_user_logged_in()) {
        echo '<ul class="netflix-user-links">';
        echo '<li><a href="' . esc_url(home_url('/account/')) . '">' . esc_html__('Account', 'netflix-theme') . '</a></li>';
        echo '<li><a href="' . esc_url(home_url('/subscription/')) . '">' . esc_html__('Subscription', 'netflix-theme') . '</a></li>';
        echo '<li><a href="' . esc_url(home_url('/my-list/')) . '">' . esc_html__('My List', 'netflix-theme') . '</a></li>';
        echo '<li><a href="' . esc_url(wp_logout_url()) . '">' . esc_html__('Sign Out', 'netflix-theme') . '</a></li>';
        echo '</ul>';
    }
}
?>