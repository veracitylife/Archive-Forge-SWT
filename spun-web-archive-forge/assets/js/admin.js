/**
 * Spun Web Archive Forge Admin JavaScript
 */

(function($) {
    'use strict';
    
    var SWAP = {
        isSubmitting: false,
        
        init: function() {
            console.log('SWAP Admin JS: Initializing...');
            this.bindEvents();
            this.initTabs();
            this.initEnhancedUI();
            
            // Restore form data if available
            $('.swap-auto-save').each(function() {
                SWAP.restoreFormData($(this));
            });
        },
        
        bindEvents: function() {
            // API test button
            $('#test-api-connection').on('click', this.testApiConnection);
            
            // Queue management buttons
            $('#process-queue-btn').on('click', this.processQueue);
            $('#validate-archives-btn').on('click', this.validateArchives);
            $('#clear-completed-btn').on('click', this.clearCompleted);
            $('#clear-failed-btn').on('click', this.clearFailed);
            $('#refresh-stats-btn').on('click', this.refreshQueueStats);
            
            // Submission method radio buttons
            $('input[name="swap_api_settings[submission_method]"]').on('change', this.toggleApiCredentials);
            
            // Form submission validation
            $('form').on('submit', this.validateFormSubmission);
            
            // Single post submission
            window.swapSubmitSingle = this.submitSinglePost;
            
            // Initialize submission method toggle on page load
            this.toggleApiCredentials();
        },
        
        initTabs: function() {
            console.log('SWAP Admin JS: Initializing tabs...');
            
            // Tab functionality for admin page
            $('.nav-tab-wrapper .nav-tab').on('click', function(e) {
                e.preventDefault();
                var $clickedTab = $(this);
                var targetUrl = $clickedTab.attr('href');
                
                console.log('SWAP Admin JS: Tab clicked:', targetUrl);
                
                // Add loading state
                $clickedTab.addClass('loading');
                
                // Navigate to the tab URL
                window.location.href = targetUrl;
            });
            
            // Add visual feedback for tab clicks
            $('.nav-tab-wrapper .nav-tab').on('mousedown', function() {
                $(this).addClass('nav-tab-pressed');
            }).on('mouseup mouseleave', function() {
                $(this).removeClass('nav-tab-pressed');
            });
            
            console.log('SWAP Admin JS: Tabs initialized successfully');
        },
        
        // API testing functionality
        testApiConnection: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $result = $('#api-test-result');
            
            console.log('SWAP Admin JS: Testing API connection...');
            
            $button.prop('disabled', true).text('Testing...');
            $result.hide();
            
            $.ajax({
                url: swapAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'swap_test_api_credentials',
                    nonce: swapAdmin.nonce
                },
                success: function(response) {
                    console.log('SWAP Admin JS: API test response:', response);
                    
                    if (response.success) {
                        $result.removeClass('notice-error').addClass('notice-success')
                            .text(response.data.message || 'API connection successful!').show();
                    } else {
                        $result.removeClass('notice-success').addClass('notice-error')
                            .text(response.data.message || 'API test failed').show();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('SWAP Admin JS: API test error:', error);
                    $result.removeClass('notice-success').addClass('notice-error')
                        .text('An error occurred while testing the API connection.').show();
                },
                complete: function() {
                    $button.prop('disabled', false).text('Test Connection');
                }
            });
        },
        
        // Queue management functionality
        processQueue: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $result = $('#queue-operation-result');
            
            console.log('SWAP Admin JS: Processing queue...');
            
            $button.prop('disabled', true).text('Processing...');
            $result.hide();
            
            $.ajax({
                url: swapAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'swap_process_queue',
                    nonce: swapAdmin.nonce
                },
                success: function(response) {
                    console.log('SWAP Admin JS: Queue process response:', response);
                    
                    if (response.success) {
                        $result.removeClass('notice-error').addClass('notice-success')
                            .text(response.data.message || 'Queue processed successfully!').show();
                        
                        // Refresh stats after successful processing
                        SWAP.refreshQueueStats();
                    } else {
                        $result.removeClass('notice-success').addClass('notice-error')
                            .text(response.data.message || 'Queue processing failed').show();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('SWAP Admin JS: Queue process error:', error);
                    $result.removeClass('notice-success').addClass('notice-error')
                        .text('An error occurred while processing the queue.').show();
                },
                complete: function() {
                    $button.prop('disabled', false).text('Process Queue Now');
                }
            });
        },
        
        validateArchives: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $results = $('#validation-results');
            
            console.log('SWAP Admin JS: Validating archives...');
            
            $button.prop('disabled', true).text('Validating...');
            $results.hide();
            
            $.ajax({
                url: swapAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'swap_validate_now',
                    _ajax_nonce: swapAdmin.nonce
                },
                success: function(response) {
                    console.log('SWAP Admin JS: Validation response:', response);
                    
                    if (response.success && response.data) {
                        var results = response.data;
                        var summary = Object.keys(results).length + ' items processed';
                        var details = Object.entries(results).map(function(entry) {
                            return 'ID ' + entry[0] + ': ' + entry[1];
                        }).join('\n');
                        
                        $results.removeClass('notice-error notice-warning')
                               .addClass('notice-success')
                               .html('<p><strong>Validation Complete:</strong> ' + summary + '</p><pre>' + details + '</pre>')
                               .show();
                    } else {
                        $results.removeClass('notice-success notice-warning')
                               .addClass('notice-error')
                               .html('<p>Validation failed or returned no data.</p>')
                               .show();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('SWAP Admin JS: Validation error:', error);
                    $results.removeClass('notice-success notice-warning')
                           .addClass('notice-error')
                           .html('<p>Validation request failed.</p>')
                           .show();
                },
                complete: function() {
                    $button.prop('disabled', false).text('Validate Archives');
                }
            });
        },
        
        clearCompleted: function(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to clear all completed items from the queue?')) {
                return;
            }
            
            var $button = $(this);
            var $result = $('#queue-operation-result');
            
            console.log('SWAP Admin JS: Clearing completed items...');
            
            $button.prop('disabled', true).text('Clearing...');
            $result.hide();
            
            $.ajax({
                url: swapAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'swap_clear_completed',
                    nonce: swapAdmin.nonce
                },
                success: function(response) {
                    console.log('SWAP Admin JS: Clear completed response:', response);
                    
                    if (response.success) {
                        $result.removeClass('notice-error').addClass('notice-success')
                            .text(response.data.message || 'Completed items cleared successfully!').show();
                        
                        // Refresh stats after clearing
                        SWAP.refreshQueueStats();
            } else {
                        $result.removeClass('notice-success').addClass('notice-error')
                            .text(response.data.message || 'Failed to clear completed items').show();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('SWAP Admin JS: Clear completed error:', error);
                    $result.removeClass('notice-success').addClass('notice-error')
                        .text('An error occurred while clearing completed items.').show();
                },
                complete: function() {
                    $button.prop('disabled', false).text('Clear Completed');
                }
            });
        },
        
        clearFailed: function(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to clear all failed items from the queue?')) {
                return;
            }
            
            var $button = $(this);
            var $result = $('#queue-operation-result');
            
            console.log('SWAP Admin JS: Clearing failed items...');
            
            $button.prop('disabled', true).text('Clearing...');
            $result.hide();
            
            $.ajax({
                url: swapAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'swap_clear_failed',
                    nonce: swapAdmin.nonce
                },
                success: function(response) {
                    console.log('SWAP Admin JS: Clear failed response:', response);
                    
                    if (response.success) {
                        $result.removeClass('notice-error').addClass('notice-success')
                            .text(response.data.message || 'Failed items cleared successfully!').show();
                        
                        // Refresh stats after clearing
                        SWAP.refreshQueueStats();
                    } else {
                        $result.removeClass('notice-success').addClass('notice-error')
                            .text(response.data.message || 'Failed to clear failed items').show();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('SWAP Admin JS: Clear failed error:', error);
                    $result.removeClass('notice-success').addClass('notice-error')
                        .text('An error occurred while clearing failed items.').show();
                },
                complete: function() {
                    $button.prop('disabled', false).text('Clear Failed');
                }
            });
        },
        
        refreshQueueStats: function(e) {
            if (e) e.preventDefault();
            
            var $button = $(this);
            var $result = $('#queue-operation-result');
            
            console.log('SWAP Admin JS: Refreshing queue stats...');
            
            if ($button.length) {
                $button.prop('disabled', true).text('Refreshing...');
            }
            
            $.ajax({
                url: swapAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'swap_refresh_queue_stats',
                    nonce: swapAdmin.nonce
                },
                success: function(response) {
                    console.log('SWAP Admin JS: Refresh stats response:', response);
                    
                    if (response.success && response.data.stats) {
                        // Update the stats display
                        var stats = response.data.stats;
                        $('.swap-stat-item').each(function() {
                            var $item = $(this);
                            var label = $item.find('.swap-stat-label').text().toLowerCase();
                            var $number = $item.find('.swap-stat-number');
                            
                            if (label === 'pending') {
                                $number.text(stats.pending || 0);
                            } else if (label === 'processing') {
                                $number.text(stats.processing || 0);
                            } else if (label === 'completed') {
                                $number.text(stats.completed || 0);
                            } else if (label === 'failed') {
                                $number.text(stats.failed || 0);
                            }
                        });
                        
                        // Update sidebar stats
                        $('.swap-stats-list li').each(function() {
                            var $li = $(this);
                            var text = $li.text();
                            
                            if (text.includes('Total:')) {
                                $li.text('Total: ' + (stats.total || 0));
                            } else if (text.includes('Successful:')) {
                                $li.text('Successful: ' + (stats.completed || 0));
                            } else if (text.includes('Failed:')) {
                                $li.text('Failed: ' + (stats.failed || 0));
                            } else if (text.includes('Pending:')) {
                                $li.text('Pending: ' + (stats.pending || 0));
                            }
                        });
                        
                        if ($result.length) {
                            $result.removeClass('notice-error').addClass('notice-success')
                                .text('Queue stats refreshed successfully!').show().delay(3000).fadeOut();
                        }
                    } else {
                        if ($result.length) {
                            $result.removeClass('notice-success').addClass('notice-error')
                                .text(response.data.message || 'Failed to refresh queue stats').show();
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.error('SWAP Admin JS: Refresh stats error:', error);
                    if ($result.length) {
                        $result.removeClass('notice-success').addClass('notice-error')
                            .text('An error occurred while refreshing queue stats.').show();
                    }
                },
                complete: function() {
                    if ($button.length) {
                        $button.prop('disabled', false).text('Refresh Stats');
                    }
                }
            });
        },
        
        toggleApiCredentials: function() {
            var submissionMethod = $('input[name="swap_api_settings[submission_method]"]:checked').val();
            var $apiSection = $('#api-credentials-section');
            var $errorDiv = $('#api-validation-error');
            
            if (submissionMethod === 'api') {
                $apiSection.show();
            } else {
                $apiSection.hide();
                $errorDiv.hide(); // Hide error when switching to simple mode
            }
        },
        
        validateFormSubmission: function(e) {
            var submissionMethod = $('input[name="swap_api_settings[submission_method]"]:checked').val();
            var $errorDiv = $('#api-validation-error');
            
            // Only validate if API method is selected
            if (submissionMethod === 'api') {
                var apiKey = $('#api_key').val().trim();
                var apiSecret = $('#api_secret').val().trim();
                
                if (!apiKey || !apiSecret) {
                    e.preventDefault(); // Prevent form submission
                    $errorDiv.show();
                    
                    // Scroll to the error message
                    $('html, body').animate({
                        scrollTop: $errorDiv.offset().top - 100
                    }, 500);
                    
                    return false;
                }
            }
            
            // Hide error if validation passes
            $errorDiv.hide();
            return true;
        },
        
        submitSinglePost: function(postId) {
            if (SWAP.isSubmitting) {
                return;
            }
            
            if (!confirm('Submit this post to the Internet Archive?')) {
                return;
            }
            
            SWAP.isSubmitting = true;
            
            $.ajax({
                url: swap_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'swap_submit_single',
                    nonce: swap_ajax.nonce,
                    post_id: postId
                },
                success: function(response) {
                    if (response.success) {
                        alert('Post successfully submitted to the archive!');
                        location.reload();
                    } else {
                        alert('Submission failed: ' + (response.data || 'Unknown error'));
                    }
                },
                error: function() {
                    alert('Network error. Please try again.');
                },
                complete: function() {
                    SWAP.isSubmitting = false;
                }
            });
        },
        
        displayCallbackResults: function(data) {
            var $callbackResults = $('#callback-results');
            var $testDetails = $('#test-details');
            var $callbackUrls = $('#callback-urls');
            
            // Show the callback results section
            $callbackResults.show();
            
            // Display test details
            var detailsHtml = '<h4>Test Details</h4>';
            detailsHtml += '<p><strong>Test ID:</strong> ' + data.test_id + '</p>';
            
            if (data.response_time) {
                detailsHtml += '<p><strong>Response Time:</strong> ' + data.response_time + 'ms</p>';
            }
            
            if (data.endpoint) {
                detailsHtml += '<p><strong>Endpoint:</strong> ' + data.endpoint + '</p>';
            }
            
            if (data.status_code) {
                detailsHtml += '<p><strong>Status Code:</strong> ' + data.status_code + '</p>';
            }
            
            $testDetails.html(detailsHtml);
            
            // Display callback URLs if available
            if (data.callback_url || data.status_url) {
                var urlsHtml = '<h4>Callback URLs</h4>';
                
                if (data.callback_url) {
                    urlsHtml += '<p><strong>Callback URL:</strong> <a href="' + data.callback_url + '" target="_blank">' + data.callback_url + '</a></p>';
                }
                
                if (data.status_url) {
                    urlsHtml += '<p><strong>Status URL:</strong> <a href="' + data.status_url + '" target="_blank">' + data.status_url + '</a></p>';
                }
                
                $callbackUrls.html(urlsHtml);
            } else {
                $callbackUrls.empty();
            }
        },
        
        // Enhanced UI functionality
        showNotification: function(message, type, duration) {
            type = type || 'info';
            duration = duration || 5000;
            
            var $notification = $('<div class="swap-alert swap-alert-' + type + ' swap-alert-dismissible swap-animate-fade-in">' +
                '<span>' + message + '</span>' +
                '<button type="button" class="swap-alert-dismiss" aria-label="Close">&times;</button>' +
                '</div>');
            
            // Add to page
            if ($('.swap-notifications').length === 0) {
                $('body').append('<div class="swap-notifications" style="position: fixed; top: 20px; right: 20px; z-index: 10000; max-width: 300px;"></div>');
            }
            
            $('.swap-notifications').append($notification);
            
            // Handle dismiss button
            $notification.find('.swap-alert-dismiss').on('click', function() {
                $notification.fadeOut(300, function() {
                    $notification.remove();
                    if ($('.swap-notifications').children().length === 0) {
                        $('.swap-notifications').remove();
                    }
                });
            });
            
            // Auto-hide
            if (duration > 0) {
                setTimeout(function() {
                    if ($notification.is(':visible')) {
                        $notification.fadeOut(300, function() {
                            $notification.remove();
                            if ($('.swap-notifications').children().length === 0) {
                                $('.swap-notifications').remove();
                            }
                        });
                    }
                }, duration);
            }
        },
        
        initEnhancedUI: function() {
            // Initialize enhanced buttons
            $('.swap-button').each(function() {
                var $button = $(this);
                
                // Add ripple effect
                $button.on('click', function(e) {
                    var $ripple = $('<span class="swap-ripple"></span>');
                    var size = Math.max($button.outerWidth(), $button.outerHeight());
                    var x = e.pageX - $button.offset().left - size / 2;
                    var y = e.pageY - $button.offset().top - size / 2;
                    
                    $ripple.css({
                        width: size,
                        height: size,
                        left: x,
                        top: y
                    }).appendTo($button);
                    
                    setTimeout(function() {
                        $ripple.remove();
                    }, 600);
                });
            });
            
            // Initialize tooltips
            $('.swap-tooltip-trigger').each(function() {
                var $trigger = $(this);
                var tooltipText = $trigger.data('tooltip');
                
                if (tooltipText) {
                    $trigger.attr('title', tooltipText);
                }
            });
            
            // Initialize form validation
            $('.swap-form-control').on('blur', function() {
                SWAP.validateField($(this));
            });
            
            // Initialize auto-save for forms
            $('.swap-auto-save').on('input change', SWAP.debounce(function() {
                SWAP.autoSaveForm($(this).closest('form'));
            }, 1000));
        },
        
        validateField: function($field) {
            var value = $field.val().trim();
            var required = $field.prop('required');
            var type = $field.attr('type');
            var pattern = $field.attr('pattern');
            var isValid = true;
            var errorMessage = '';
            
            // Required validation
            if (required && !value) {
                isValid = false;
                errorMessage = 'This field is required.';
            }
            
            // Email validation
            if (isValid && type === 'email' && value) {
                var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(value)) {
                    isValid = false;
                    errorMessage = 'Please enter a valid email address.';
                }
            }
            
            // URL validation
            if (isValid && type === 'url' && value) {
                try {
                    new URL(value);
                } catch (e) {
                    isValid = false;
                    errorMessage = 'Please enter a valid URL.';
                }
            }
            
            // Pattern validation
            if (isValid && pattern && value) {
                var regex = new RegExp(pattern);
                if (!regex.test(value)) {
                    isValid = false;
                    errorMessage = 'Please match the required format.';
                }
            }
            
            // Update field state
            var $errorElement = $field.siblings('.swap-form-error');
            
            if (isValid) {
                $field.removeClass('error');
                $errorElement.remove();
            } else {
                $field.addClass('error');
                if ($errorElement.length === 0) {
                    $field.after('<div class="swap-form-error">' + errorMessage + '</div>');
                } else {
                    $errorElement.text(errorMessage);
                }
            }
            
            return isValid;
        },
        
        autoSaveForm: function($form) {
            if (!$form.hasClass('swap-auto-save')) {
                return;
            }
            
            var formData = $form.serialize();
            var formId = $form.attr('id') || 'unknown';
            
            // Show saving indicator
            var $indicator = $form.find('.swap-auto-save-indicator');
            if ($indicator.length === 0) {
                $indicator = $('<span class="swap-auto-save-indicator">Saving...</span>');
                $form.append($indicator);
            }
            $indicator.show();
            
            // Save to localStorage as backup
            localStorage.setItem('swap_form_' + formId, formData);
            
            // Make AJAX request if endpoint is defined
            var saveUrl = $form.data('auto-save-url');
            if (saveUrl) {
                $.ajax({
                    url: saveUrl,
                    type: 'POST',
                    data: formData + '&action=swap_auto_save&nonce=' + (swap_ajax ? swap_ajax.nonce : ''),
                    success: function(response) {
                        $indicator.text('Saved').delay(2000).fadeOut();
                        if (response.success) {
                            SWAP.showNotification('Form auto-saved successfully', 'success', 2000);
                        }
                    },
                    error: function() {
                        $indicator.text('Save failed').delay(2000).fadeOut();
                    }
                });
            } else {
                $indicator.text('Saved locally').delay(2000).fadeOut();
            }
        },
        
        restoreFormData: function($form) {
            var formId = $form.attr('id');
            if (!formId) return;
            
            var savedData = localStorage.getItem('swap_form_' + formId);
            if (savedData) {
                var params = new URLSearchParams(savedData);
                params.forEach(function(value, key) {
                    var $field = $form.find('[name="' + key + '"]');
                    if ($field.length) {
                        if ($field.is(':checkbox') || $field.is(':radio')) {
                            $field.filter('[value="' + value + '"]').prop('checked', true);
                        } else {
                            $field.val(value);
                        }
                    }
                });
                
                SWAP.showNotification('Form data restored from auto-save', 'info', 3000);
            }
        },
        
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
        SWAP.init();
    });
    
})(jQuery);