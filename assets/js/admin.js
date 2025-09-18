/**
 * AutoPost Movies Admin JavaScript
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Initialize admin functionality
        initAdminInterface();
        
    });
    
    function initAdminInterface() {
        
        // Auto-save draft settings
        initAutoSave();
        
        // Real-time validation
        initValidation();
        
        // Dynamic form updates
        initDynamicForms();
        
        // Enhanced UI interactions
        initUIEnhancements();
        
    }
    
    /**
     * Initialize auto-save functionality
     */
    function initAutoSave() {
        var saveTimeout;
        var $form = $('form[method="post"]');
        var $saveIndicator = $('<div class="save-indicator" style="display:none; color: #666; font-size: 12px; margin-left: 10px;">Draft saved</div>');
        
        $form.find('input[type="submit"]').after($saveIndicator);
        
        $form.find('input, select, textarea').on('input change', function() {
            clearTimeout(saveTimeout);
            
            saveTimeout = setTimeout(function() {
                saveDraft();
            }, 2000);
        });
        
        function saveDraft() {
            var formData = $form.serialize() + '&action=autopost_movies_save_draft';
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    $saveIndicator.fadeIn().delay(2000).fadeOut();
                }
            });
        }
    }
    
    /**
     * Initialize real-time validation
     */
    function initValidation() {
        
        // TMDB API Key validation
        $('#autopost_movies_tmdb_api_key').on('blur', function() {
            var $input = $(this);
            var value = $input.val().trim();
            
            if (value && value.length !== 32) {
                showValidationError($input, 'TMDB API key should be 32 characters long');
            } else {
                clearValidationError($input);
            }
        });
        
        // YouTube API Key validation
        $('#autopost_movies_youtube_api_key').on('blur', function() {
            var $input = $(this);
            var value = $input.val().trim();
            
            if (value && value.length < 20) {
                showValidationError($input, 'YouTube API key appears to be too short');
            } else {
                clearValidationError($input);
            }
        });
        
        // TMDB ID validation
        $('input[type="number"][id*="tmdb_id"]').on('input', function() {
            var $input = $(this);
            var value = parseInt($input.val());
            
            if (value && (value < 1 || value > 9999999)) {
                showValidationError($input, 'TMDB ID should be between 1 and 9999999');
            } else {
                clearValidationError($input);
            }
        });
        
        // URL validation
        $('input[type="url"]').on('blur', function() {
            var $input = $(this);
            var value = $input.val().trim();
            
            if (value && !isValidUrl(value)) {
                showValidationError($input, 'Please enter a valid URL');
            } else {
                clearValidationError($input);
            }
        });
        
    }
    
    /**
     * Initialize dynamic form updates
     */
    function initDynamicForms() {
        
        // Show/hide fields based on selections
        $('#autopost_movies_wikipedia_enabled').on('change', function() {
            var isEnabled = $(this).is(':checked');
            $('#autopost_movies_plot_source option[value="wikipedia"]').prop('disabled', !isEnabled);
            
            if (!isEnabled && $('#autopost_movies_plot_source').val() === 'wikipedia') {
                $('#autopost_movies_plot_source').val('tmdb');
            }
        }).trigger('change');
        
        $('#autopost_movies_imdb_enabled').on('change', function() {
            var isEnabled = $(this).is(':checked');
            $('#autopost_movies_info_source option[value="imdb"]').prop('disabled', !isEnabled);
            
            if (!isEnabled && $('#autopost_movies_info_source').val() === 'imdb') {
                $('#autopost_movies_info_source').val('tmdb');
            }
        }).trigger('change');
        
        // Dynamic schedule description
        $('#autopost_movies_cron_schedule').on('change', function() {
            var schedule = $(this).val();
            var description = getScheduleDescription(schedule);
            
            var $desc = $(this).siblings('.schedule-description');
            if ($desc.length === 0) {
                $desc = $('<p class="description schedule-description"></p>').insertAfter(this);
            }
            $desc.text(description);
        }).trigger('change');
        
    }
    
    /**
     * Initialize UI enhancements
     */
    function initUIEnhancements() {
        
        // Progress bars for statistics
        $('.autopost-movies-dashboard .postbox').each(function() {
            var $box = $(this);
            var $number = $box.find('p');
            var value = parseInt($number.text());
            
            if (!isNaN(value) && value > 0) {
                addProgressBar($box, value);
            }
        });
        
        // Tooltips for form fields
        addTooltips();
        
        // Confirm dialogs for destructive actions
        $('.remove-button-row, #clear-logs').on('click', function(e) {
            if (!confirm('Are you sure? This action cannot be undone.')) {
                e.preventDefault();
                return false;
            }
        });
        
        // Loading states for buttons
        $('button[type="submit"], .button-primary').on('click', function() {
            var $btn = $(this);
            if ($btn.closest('form').valid && $btn.closest('form').valid()) {
                $btn.addClass('loading').prop('disabled', true);
                
                setTimeout(function() {
                    $btn.removeClass('loading').prop('disabled', false);
                }, 5000); // Fallback timeout
            }
        });
        
        // Copy to clipboard functionality
        addCopyToClipboard();
        
    }
    
    /**
     * Show validation error
     */
    function showValidationError($input, message) {
        clearValidationError($input);
        
        var $error = $('<div class="validation-error" style="color: #dc3232; font-size: 12px; margin-top: 5px;">' + message + '</div>');
        $input.after($error);
        $input.addClass('error');
    }
    
    /**
     * Clear validation error
     */
    function clearValidationError($input) {
        $input.removeClass('error').siblings('.validation-error').remove();
    }
    
    /**
     * Validate URL
     */
    function isValidUrl(string) {
        try {
            new URL(string);
            return true;
        } catch (_) {
            return false;
        }
    }
    
    /**
     * Get schedule description
     */
    function getScheduleDescription(schedule) {
        var descriptions = {
            'hourly': 'Runs every hour',
            'twicedaily': 'Runs twice per day (every 12 hours)',
            'daily': 'Runs once per day',
            'weekly': 'Runs once per week'
        };
        
        return descriptions[schedule] || 'Custom schedule';
    }
    
    /**
     * Add progress bar to statistics
     */
    function addProgressBar($container, value) {
        var maxValue = 100; // Adjust based on your needs
        var percentage = Math.min((value / maxValue) * 100, 100);
        
        var $progressBar = $('<div class="progress-bar" style="width: 100%; height: 4px; background: #e0e0e0; border-radius: 2px; margin-top: 10px;">' +
            '<div class="progress-fill" style="width: ' + percentage + '%; height: 100%; background: #0073aa; border-radius: 2px; transition: width 0.3s ease;"></div>' +
            '</div>');
        
        $container.find('.inside').append($progressBar);
    }
    
    /**
     * Add tooltips
     */
    function addTooltips() {
        var tooltips = {
            '#autopost_movies_tmdb_api_key': 'Required for fetching movie and TV series data. Get it free from TMDB.',
            '#autopost_movies_youtube_api_key': 'Optional. Used for automatic trailer discovery.',
            '#autopost_movies_max_posts_per_run': 'Limits how many posts are created in each cron run to prevent server overload.',
            '#autopost_movies_fifu_enabled': 'Requires the Featured Image from URL (FIFU) plugin to be installed and active.'
        };
        
        $.each(tooltips, function(selector, tooltip) {
            var $element = $(selector);
            if ($element.length) {
                $element.attr('title', tooltip);
                
                // Add visual indicator
                $('<span class="tooltip-indicator" style="margin-left: 5px; color: #666; cursor: help;" title="' + tooltip + '">ⓘ</span>')
                    .insertAfter($element);
            }
        });
    }
    
    /**
     * Add copy to clipboard functionality
     */
    function addCopyToClipboard() {
        // Add copy buttons to code blocks
        $('textarea[readonly], code').each(function() {
            var $element = $(this);
            var text = $element.text() || $element.val();
            
            if (text.length > 50) {
                var $copyBtn = $('<button type="button" class="button copy-btn" style="margin-left: 10px;">Copy</button>');
                
                $copyBtn.on('click', function() {
                    navigator.clipboard.writeText(text).then(function() {
                        $copyBtn.text('Copied!').addClass('copied');
                        setTimeout(function() {
                            $copyBtn.text('Copy').removeClass('copied');
                        }, 2000);
                    });
                });
                
                $element.after($copyBtn);
            }
        });
    }
    
    /**
     * Enhanced AJAX error handling
     */
    $(document).ajaxError(function(event, xhr, settings, error) {
        if (settings.url && settings.url.indexOf('autopost_movies') !== -1) {
            console.error('AutoPost Movies AJAX Error:', {
                url: settings.url,
                error: error,
                response: xhr.responseText
            });
            
            // Show user-friendly error message
            showNotification('An error occurred. Please try again.', 'error');
        }
    });
    
    /**
     * Show notification
     */
    function showNotification(message, type) {
        type = type || 'info';
        
        var $notification = $('<div class="notice notice-' + type + ' is-dismissible" style="margin: 10px 0;">' +
            '<p>' + message + '</p>' +
            '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>' +
            '</div>');
        
        $('.wrap > h1').after($notification);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $notification.fadeOut(function() { $notification.remove(); });
        }, 5000);
        
        // Manual dismiss
        $notification.find('.notice-dismiss').on('click', function() {
            $notification.fadeOut(function() { $notification.remove(); });
        });
    }
    
    /**
     * Form validation enhancement
     */
    $.fn.valid = function() {
        var isValid = true;
        
        this.find('input[required], select[required], textarea[required]').each(function() {
            var $field = $(this);
            if (!$field.val().trim()) {
                showValidationError($field, 'This field is required');
                isValid = false;
            }
        });
        
        this.find('.error').each(function() {
            isValid = false;
        });
        
        return isValid;
    };
    
    // Global functions for inline use
    window.autopostMoviesAdmin = {
        showNotification: showNotification,
        showValidationError: showValidationError,
        clearValidationError: clearValidationError
    };
    
})(jQuery);

// Additional CSS for admin enhancements
(function() {
    var styles = `
        .validation-error {
            animation: shake 0.5s ease-in-out;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        input.error, select.error, textarea.error {
            border-color: #dc3232 !important;
            box-shadow: 0 0 0 1px #dc3232 !important;
        }
        
        .copy-btn.copied {
            background: #00a32a;
            color: white;
            border-color: #00a32a;
        }
        
        .tooltip-indicator {
            cursor: help;
            font-weight: bold;
        }
        
        .loading {
            position: relative;
            color: transparent !important;
        }
        
        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 16px;
            height: 16px;
            margin: -8px 0 0 -8px;
            border: 2px solid #fff;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    `;
    
    var styleSheet = document.createElement("style");
    styleSheet.type = "text/css";
    styleSheet.innerText = styles;
    document.head.appendChild(styleSheet);
})();