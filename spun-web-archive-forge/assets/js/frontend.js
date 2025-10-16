/**
 * Spun Web Archive Forge Frontend JavaScript
 * Handles frontend widget and shortcode interactions
 * 
 * @package SpunWebArchiveElite
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    var SWAPFrontend = {
        
        /**
         * Initialize frontend functionality
         */
        init: function() {
            this.bindEvents();
            this.initTooltips();
            this.initLazyLoading();
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Handle archive link clicks with analytics
            $(document).on('click', '.swap-archive-link, .swap-shortcode-archive-link', this.handleArchiveLinkClick);
            
            // Handle widget refresh
            $(document).on('click', '.swap-refresh-widget', this.refreshWidget);
            
            // Handle shortcode refresh
            $(document).on('click', '.swap-refresh-shortcode', this.refreshShortcode);
            
            // Handle keyboard navigation
            $(document).on('keydown', '.swap-archive-links a', this.handleKeyboardNavigation);
        },
        
        /**
         * Handle archive link clicks
         */
        handleArchiveLinkClick: function(e) {
            var $link = $(this);
            var url = $link.attr('href');
            var postId = $link.data('post-id');
            var source = $link.data('source') || 'unknown';
            
            // Track click if analytics is available
            if (typeof gtag !== 'undefined') {
                gtag('event', 'archive_link_click', {
                    'post_id': postId,
                    'source': source,
                    'url': url
                });
            }
            
            // Add visual feedback
            $link.addClass('swap-clicked');
            setTimeout(function() {
                $link.removeClass('swap-clicked');
            }, 200);
            
            // Open in new tab/window
            if (!e.ctrlKey && !e.metaKey) {
                e.preventDefault();
                window.open(url, '_blank', 'noopener,noreferrer');
            }
        },
        
        /**
         * Refresh widget content
         */
        refreshWidget: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $widget = $button.closest('.widget');
            var widgetId = $widget.attr('id');
            
            if (!widgetId) {
                return;
            }
            
            // Show loading state
            $button.addClass('swap-loading').prop('disabled', true);
            var originalText = $button.text();
            $button.text(swapFrontend.strings.loading);
            
            // Make AJAX request
            $.ajax({
                url: swapFrontend.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'swap_refresh_widget',
                    nonce: swapFrontend.nonce,
                    widget_id: widgetId
                },
                success: function(response) {
                    if (response.success && response.data.html) {
                        // Update widget content
                        $widget.find('.swap-archive-links').html(response.data.html);
                        
                        // Show success feedback
                        SWAPFrontend.showNotification(swapFrontend.strings.refreshSuccess, 'success');
                    } else {
                        SWAPFrontend.showNotification(swapFrontend.strings.refreshError, 'error');
                    }
                },
                error: function() {
                    SWAPFrontend.showNotification(swapFrontend.strings.refreshError, 'error');
                },
                complete: function() {
                    // Restore button state
                    $button.removeClass('swap-loading').prop('disabled', false);
                    $button.text(originalText);
                }
            });
        },
        
        /**
         * Refresh shortcode content
         */
        refreshShortcode: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $container = $button.closest('.swap-shortcode-container');
            var shortcodeId = $container.data('shortcode-id');
            
            if (!shortcodeId) {
                return;
            }
            
            // Show loading state
            $button.addClass('swap-loading').prop('disabled', true);
            var originalText = $button.text();
            $button.text(swapFrontend.strings.loading);
            
            // Make AJAX request
            $.ajax({
                url: swapFrontend.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'swap_refresh_shortcode',
                    nonce: swapFrontend.nonce,
                    shortcode_id: shortcodeId
                },
                success: function(response) {
                    if (response.success && response.data.html) {
                        // Update shortcode content
                        $container.find('.swap-shortcode-content').html(response.data.html);
                        
                        // Show success feedback
                        SWAPFrontend.showNotification(swapFrontend.strings.refreshSuccess, 'success');
                    } else {
                        SWAPFrontend.showNotification(swapFrontend.strings.refreshError, 'error');
                    }
                },
                error: function() {
                    SWAPFrontend.showNotification(swapFrontend.strings.refreshError, 'error');
                },
                complete: function() {
                    // Restore button state
                    $button.removeClass('swap-loading').prop('disabled', false);
                    $button.text(originalText);
                }
            });
        },
        
        /**
         * Handle keyboard navigation
         */
        handleKeyboardNavigation: function(e) {
            var $current = $(this);
            var $links = $('.swap-archive-links a');
            var currentIndex = $links.index($current);
            
            switch(e.which) {
                case 38: // Up arrow
                    e.preventDefault();
                    if (currentIndex > 0) {
                        $links.eq(currentIndex - 1).focus();
                    }
                    break;
                    
                case 40: // Down arrow
                    e.preventDefault();
                    if (currentIndex < $links.length - 1) {
                        $links.eq(currentIndex + 1).focus();
                    }
                    break;
                    
                case 13: // Enter
                case 32: // Space
                    e.preventDefault();
                    $current[0].click();
                    break;
            }
        },
        
        /**
         * Initialize tooltips
         */
        initTooltips: function() {
            // Simple tooltip implementation
            $(document).on('mouseenter', '[data-swap-tooltip]', function() {
                var $element = $(this);
                var tooltipText = $element.data('swap-tooltip');
                
                if (!tooltipText) return;
                
                var $tooltip = $('<div class="swap-tooltip">' + tooltipText + '</div>');
                $('body').append($tooltip);
                
                var elementOffset = $element.offset();
                var elementWidth = $element.outerWidth();
                var elementHeight = $element.outerHeight();
                var tooltipWidth = $tooltip.outerWidth();
                var tooltipHeight = $tooltip.outerHeight();
                
                // Position tooltip
                var left = elementOffset.left + (elementWidth / 2) - (tooltipWidth / 2);
                var top = elementOffset.top - tooltipHeight - 10;
                
                // Adjust if tooltip goes off screen
                if (left < 0) left = 10;
                if (left + tooltipWidth > $(window).width()) {
                    left = $(window).width() - tooltipWidth - 10;
                }
                if (top < 0) {
                    top = elementOffset.top + elementHeight + 10;
                }
                
                $tooltip.css({
                    position: 'absolute',
                    left: left + 'px',
                    top: top + 'px',
                    zIndex: 9999
                }).fadeIn(200);
                
                $element.data('swap-tooltip-element', $tooltip);
            });
            
            $(document).on('mouseleave', '[data-swap-tooltip]', function() {
                var $element = $(this);
                var $tooltip = $element.data('swap-tooltip-element');
                
                if ($tooltip) {
                    $tooltip.fadeOut(200, function() {
                        $tooltip.remove();
                    });
                    $element.removeData('swap-tooltip-element');
                }
            });
        },
        
        /**
         * Initialize lazy loading for archive links
         */
        initLazyLoading: function() {
            if ('IntersectionObserver' in window) {
                var observer = new IntersectionObserver(function(entries) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            var $element = $(entry.target);
                            SWAPFrontend.loadArchiveData($element);
                            observer.unobserve(entry.target);
                        }
                    });
                });
                
                $('.swap-lazy-load').each(function() {
                    observer.observe(this);
                });
            } else {
                // Fallback for older browsers
                $('.swap-lazy-load').each(function() {
                    SWAPFrontend.loadArchiveData($(this));
                });
            }
        },
        
        /**
         * Load archive data for lazy-loaded elements
         */
        loadArchiveData: function($element) {
            var postId = $element.data('post-id');
            
            if (!postId) return;
            
            $.ajax({
                url: swapFrontend.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'swap_get_archive_data',
                    nonce: swapFrontend.nonce,
                    post_id: postId
                },
                success: function(response) {
                    if (response.success && response.data.html) {
                        $element.html(response.data.html);
                        $element.removeClass('swap-lazy-load');
                    }
                },
                error: function() {
                    $element.html('<span class="swap-error">' + swapFrontend.strings.loadError + '</span>');
                }
            });
        },
        
        /**
         * Show notification message
         */
        showNotification: function(message, type) {
            type = type || 'info';
            
            var $notification = $('<div class="swap-notification swap-notification-' + type + '">' + message + '</div>');
            
            // Add to page
            if ($('.swap-notifications').length === 0) {
                $('body').append('<div class="swap-notifications"></div>');
            }
            
            $('.swap-notifications').append($notification);
            
            // Show notification
            $notification.slideDown(300);
            
            // Auto-hide after 5 seconds
            setTimeout(function() {
                $notification.slideUp(300, function() {
                    $notification.remove();
                    
                    // Remove container if empty
                    if ($('.swap-notifications').children().length === 0) {
                        $('.swap-notifications').remove();
                    }
                });
            }, 5000);
        },
        
        /**
         * Utility function to debounce events
         */
        debounce: function(func, wait, immediate) {
            var timeout;
            return function() {
                var context = this, args = arguments;
                var later = function() {
                    timeout = null;
                    if (!immediate) func.apply(context, args);
                };
                var callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func.apply(context, args);
            };
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        SWAPFrontend.init();
    });
    
    // Add CSS for notifications and tooltips
    var css = `
        .swap-tooltip {
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 12px;
            max-width: 200px;
            word-wrap: break-word;
            display: none;
        }
        
        .swap-notifications {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            max-width: 300px;
        }
        
        .swap-notification {
            padding: 12px 16px;
            margin-bottom: 10px;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            display: none;
        }
        
        .swap-notification-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .swap-notification-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .swap-notification-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .swap-clicked {
            transform: scale(0.95);
            transition: transform 0.1s ease;
        }
        
        .swap-loading {
            opacity: 0.6;
            pointer-events: none;
        }
        
        @media (max-width: 768px) {
            .swap-notifications {
                left: 20px;
                right: 20px;
                max-width: none;
            }
        }
    `;
    
    // Inject CSS
    $('<style>').text(css).appendTo('head');
    
})(jQuery);