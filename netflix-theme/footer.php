<footer class="netflix-footer">
    <div class="netflix-footer-content">
        <div class="netflix-footer-links">
            <div class="netflix-footer-column">
                <h4><?php esc_html_e('Browse', 'netflix-theme'); ?></h4>
                <ul>
                    <li><a href="<?php echo esc_url(home_url('/movies/')); ?>"><?php esc_html_e('Movies', 'netflix-theme'); ?></a></li>
                    <li><a href="<?php echo esc_url(home_url('/tv-shows/')); ?>"><?php esc_html_e('TV Shows', 'netflix-theme'); ?></a></li>
                    <li><a href="<?php echo esc_url(home_url('/genres/')); ?>"><?php esc_html_e('Genres', 'netflix-theme'); ?></a></li>
                    <li><a href="<?php echo esc_url(home_url('/new-releases/')); ?>"><?php esc_html_e('New Releases', 'netflix-theme'); ?></a></li>
                </ul>
            </div>
            
            <div class="netflix-footer-column">
                <h4><?php esc_html_e('Account', 'netflix-theme'); ?></h4>
                <ul>
                    <?php if (is_user_logged_in()) : ?>
                        <li><a href="<?php echo esc_url(home_url('/account/')); ?>"><?php esc_html_e('My Account', 'netflix-theme'); ?></a></li>
                        <li><a href="<?php echo esc_url(home_url('/subscription/')); ?>"><?php esc_html_e('Subscription', 'netflix-theme'); ?></a></li>
                        <li><a href="<?php echo esc_url(home_url('/my-list/')); ?>"><?php esc_html_e('My List', 'netflix-theme'); ?></a></li>
                        <li><a href="<?php echo esc_url(wp_logout_url()); ?>"><?php esc_html_e('Sign Out', 'netflix-theme'); ?></a></li>
                    <?php else : ?>
                        <li><a href="<?php echo esc_url(wp_login_url()); ?>"><?php esc_html_e('Sign In', 'netflix-theme'); ?></a></li>
                        <li><a href="<?php echo esc_url(wp_registration_url()); ?>"><?php esc_html_e('Sign Up', 'netflix-theme'); ?></a></li>
                        <li><a href="<?php echo esc_url(home_url('/subscription/')); ?>"><?php esc_html_e('Plans & Pricing', 'netflix-theme'); ?></a></li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <div class="netflix-footer-column">
                <h4><?php esc_html_e('Support', 'netflix-theme'); ?></h4>
                <ul>
                    <li><a href="<?php echo esc_url(home_url('/help/')); ?>"><?php esc_html_e('Help Center', 'netflix-theme'); ?></a></li>
                    <li><a href="<?php echo esc_url(home_url('/contact/')); ?>"><?php esc_html_e('Contact Us', 'netflix-theme'); ?></a></li>
                    <li><a href="<?php echo esc_url(home_url('/faq/')); ?>"><?php esc_html_e('FAQ', 'netflix-theme'); ?></a></li>
                    <li><a href="<?php echo esc_url(home_url('/terms/')); ?>"><?php esc_html_e('Terms of Service', 'netflix-theme'); ?></a></li>
                </ul>
            </div>
            
            <div class="netflix-footer-column">
                <h4><?php esc_html_e('Company', 'netflix-theme'); ?></h4>
                <ul>
                    <li><a href="<?php echo esc_url(home_url('/about/')); ?>"><?php esc_html_e('About Us', 'netflix-theme'); ?></a></li>
                    <li><a href="<?php echo esc_url(home_url('/privacy/')); ?>"><?php esc_html_e('Privacy Policy', 'netflix-theme'); ?></a></li>
                    <li><a href="<?php echo esc_url(home_url('/cookies/')); ?>"><?php esc_html_e('Cookie Preferences', 'netflix-theme'); ?></a></li>
                    <li><a href="<?php echo esc_url(home_url('/careers/')); ?>"><?php esc_html_e('Careers', 'netflix-theme'); ?></a></li>
                </ul>
            </div>
        </div>

        <!-- Social Media Links -->
        <div class="netflix-social-media">
            <a href="#" class="netflix-social-link" aria-label="<?php esc_attr_e('Facebook', 'netflix-theme'); ?>">
                <i class="fab fa-facebook-f"></i>
            </a>
            <a href="#" class="netflix-social-link" aria-label="<?php esc_attr_e('Twitter', 'netflix-theme'); ?>">
                <i class="fab fa-twitter"></i>
            </a>
            <a href="#" class="netflix-social-link" aria-label="<?php esc_attr_e('Instagram', 'netflix-theme'); ?>">
                <i class="fab fa-instagram"></i>
            </a>
            <a href="#" class="netflix-social-link" aria-label="<?php esc_attr_e('YouTube', 'netflix-theme'); ?>">
                <i class="fab fa-youtube"></i>
            </a>
        </div>

        <!-- Footer Bottom -->
        <div class="netflix-footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>. <?php esc_html_e('All rights reserved.', 'netflix-theme'); ?></p>
            <p>
                <?php
                printf(
                    /* translators: %s: theme name and version */
                    esc_html__('Powered by %s', 'netflix-theme'),
                    '<a href="#" target="_blank">Netflix Streaming Platform v' . NETFLIX_THEME_VERSION . '</a>'
                );
                ?>
            </p>
        </div>
    </div>
</footer>

<!-- Video Player Modal -->
<div id="netflix-video-modal" class="netflix-modal" style="display: none;">
    <div class="netflix-modal-content">
        <button class="netflix-modal-close">&times;</button>
        <div class="netflix-video-container">
            <div id="netflix-video-player"></div>
        </div>
        <div class="netflix-video-info">
            <h3 id="netflix-video-title"></h3>
            <p id="netflix-video-description"></p>
            <div class="netflix-video-controls">
                <button class="netflix-btn netflix-btn-primary" id="netflix-play-button">
                    <i class="fas fa-play"></i> <?php esc_html_e('Play', 'netflix-theme'); ?>
                </button>
                <button class="netflix-btn netflix-btn-secondary" id="netflix-add-to-list">
                    <i class="fas fa-plus"></i> <?php esc_html_e('My List', 'netflix-theme'); ?>
                </button>
                <button class="netflix-btn netflix-btn-secondary" id="netflix-like-button">
                    <i class="fas fa-thumbs-up"></i>
                </button>
                <button class="netflix-btn netflix-btn-secondary" id="netflix-dislike-button">
                    <i class="fas fa-thumbs-down"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Subscription Modal -->
<div id="netflix-subscription-modal" class="netflix-subscription-modal" style="display: none;">
    <div class="netflix-subscription-content">
        <button class="netflix-modal-close">&times;</button>
        <h2><?php esc_html_e('Choose Your Plan', 'netflix-theme'); ?></h2>
        <p><?php esc_html_e('Upgrade to unlock premium content and features', 'netflix-theme'); ?></p>
        
        <div class="netflix-subscription-plans">
            <div class="netflix-plan" data-plan="basic">
                <h3><?php esc_html_e('Basic', 'netflix-theme'); ?></h3>
                <div class="price">$9.99<span>/month</span></div>
                <ul>
                    <li><?php esc_html_e('HD quality', 'netflix-theme'); ?></li>
                    <li><?php esc_html_e('1 device', 'netflix-theme'); ?></li>
                    <li><?php esc_html_e('Limited content', 'netflix-theme'); ?></li>
                </ul>
            </div>
            
            <div class="netflix-plan" data-plan="standard">
                <h3><?php esc_html_e('Standard', 'netflix-theme'); ?></h3>
                <div class="price">$15.99<span>/month</span></div>
                <ul>
                    <li><?php esc_html_e('Full HD quality', 'netflix-theme'); ?></li>
                    <li><?php esc_html_e('2 devices', 'netflix-theme'); ?></li>
                    <li><?php esc_html_e('Full content library', 'netflix-theme'); ?></li>
                </ul>
            </div>
            
            <div class="netflix-plan" data-plan="premium">
                <h3><?php esc_html_e('Premium', 'netflix-theme'); ?></h3>
                <div class="price">$19.99<span>/month</span></div>
                <ul>
                    <li><?php esc_html_e('4K + HDR quality', 'netflix-theme'); ?></li>
                    <li><?php esc_html_e('4 devices', 'netflix-theme'); ?></li>
                    <li><?php esc_html_e('Full content + exclusives', 'netflix-theme'); ?></li>
                </ul>
            </div>
        </div>
        
        <button class="netflix-btn netflix-btn-primary" id="netflix-subscribe-button">
            <?php esc_html_e('Subscribe Now', 'netflix-theme'); ?>
        </button>
    </div>
</div>

<!-- Loading Overlay -->
<div id="netflix-loading-overlay" class="netflix-loading-overlay" style="display: none;">
    <div class="netflix-loading-spinner">
        <div class="netflix-loading"></div>
        <p><?php esc_html_e('Loading...', 'netflix-theme'); ?></p>
    </div>
</div>

<?php wp_footer(); ?>

<script>
// Initialize theme JavaScript
jQuery(document).ready(function($) {
    // Header scroll effect
    $(window).scroll(function() {
        if ($(window).scrollTop() > 100) {
            $('#netflix-header').addClass('scrolled');
        } else {
            $('#netflix-header').removeClass('scrolled');
        }
    });

    // Mobile menu toggle
    $('.netflix-mobile-toggle').click(function() {
        $('.netflix-mobile-menu').slideToggle();
        $(this).toggleClass('active');
    });

    // Search toggle
    $('.netflix-search-toggle').click(function() {
        $('.netflix-search-form').slideToggle();
        $('.netflix-search-form input').focus();
    });

    // Profile dropdown
    $('.netflix-profile-toggle').click(function() {
        $('.netflix-profile-menu').slideToggle();
    });

    // Notifications dropdown
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
        }
    });

    // Modal close functionality
    $('.netflix-modal-close').click(function() {
        $(this).closest('.netflix-modal, .netflix-subscription-modal').fadeOut();
    });

    // Close modals when clicking outside
    $('.netflix-modal, .netflix-subscription-modal').click(function(e) {
        if (e.target === this) {
            $(this).fadeOut();
        }
    });
});
</script>

</body>
</html>