/**
 * Uninstall Page JavaScript
 * 
 * Handles the uninstall confirmation and AJAX processing.
 * 
 * @package SpunWebArchiveElite
 * @since 0.3.9
 */

jQuery(document).ready(function($) {
    
    // Handle radio button changes
    $('input[name="swap_uninstall_option"]').on('change', function() {
        const selectedOption = $(this).val();
        
        if (selectedOption === 'remove_data') {
            // Show warning and confirmation for data removal
            $('#swap-warning-box').slideDown(300);
            $('#swap-confirmation-section').slideDown(300);
            $('#swap-uninstall-button').prop('disabled', true);
            $('#swap-uninstall-button').text(swapUninstallL10n.removeAllData);
        } else {
            // Hide warning and confirmation for keeping data
            $('#swap-warning-box').slideUp(300);
            $('#swap-confirmation-section').slideUp(300);
            $('#swap-confirm-removal').prop('checked', false);
            $('#swap-uninstall-button').prop('disabled', false);
            $('#swap-uninstall-button').text(swapUninstallL10n.uninstallPlugin);
        }
    });
    
    // Handle confirmation checkbox
    $('#swap-confirm-removal').on('change', function() {
        const isChecked = $(this).is(':checked');
        const selectedOption = $('input[name="swap_uninstall_option"]:checked').val();
        
        if (selectedOption === 'remove_data') {
            $('#swap-uninstall-button').prop('disabled', !isChecked);
        }
        
        if (isChecked) {
            $('#swap-uninstall-button').removeClass('button-secondary').addClass('button-primary');
        } else {
            $('#swap-uninstall-button').removeClass('button-primary').addClass('button-secondary');
        }
    });
    
    // Handle uninstall button click
    $('#swap-uninstall-button').on('click', function(e) {
        e.preventDefault();
        
        const selectedOption = $('input[name="swap_uninstall_option"]:checked').val();
        let confirmationMessage = '';
        let finalWarning = '';
        
        if (selectedOption === 'remove_data') {
            // Confirmation popup for data removal
            confirmationMessage = swapUninstallL10n.confirmRemoveData + '\n\n' + swapUninstallL10n.warningRemoveData;
            finalWarning = swapUninstallL10n.finalWarningRemoveData;
            
            // Double confirmation with popup for data removal
            if (!confirm(confirmationMessage)) {
                return;
            }
            
            // Final confirmation for data removal
            if (!confirm(finalWarning)) {
                return;
            }
        } else {
            // Simple confirmation for keeping data
            confirmationMessage = swapUninstallL10n.confirmKeepData;
            
            if (!confirm(confirmationMessage)) {
                return;
            }
        }
        
        // Disable button and show progress
        $(this).prop('disabled', true);
        $('#swap-uninstall-progress').show();
        $('#swap-uninstall-result').hide();
        
        // Start progress animation
        $('.swap-progress-fill').css('animation', 'progress-animation 2s infinite');
        
        // Perform AJAX request
        $.ajax({
            url: swapUninstall.ajaxUrl,
            type: 'POST',
            data: {
                action: 'swap_uninstall_plugin',
                nonce: swapUninstall.nonce,
                uninstall_option: selectedOption
            },
            success: function(response) {
                // Hide progress
                $('#swap-uninstall-progress').hide();
                
                // Show result
                $('#swap-uninstall-result')
                    .removeClass('error success')
                    .addClass('success')
                    .html('<h3>✅ Success!</h3><p>' + response.data + '</p><p><a href="' + window.location.origin + '/wp-admin/plugins.php" class="button button-primary">Go to Plugins Page</a></p>')
                    .show();
                
                // Hide the form
                $('.swap-uninstall-form').hide();
            },
            error: function(xhr, status, error) {
                // Hide progress
                $('#swap-uninstall-progress').hide();
                
                // Show error
                $('#swap-uninstall-result')
                    .removeClass('error success')
                    .addClass('error')
                    .html('<h3>❌ Error</h3><p>' + swapUninstall.errorText + '</p><p>Details: ' + error + '</p>')
                    .show();
                
                // Re-enable button
                $('#swap-uninstall-button').prop('disabled', false);
            }
        });
    });
    
    // Add warning styles to dangerous elements
    $('.swap-warning-box').on('mouseenter', function() {
        $(this).css('transform', 'scale(1.02)');
    }).on('mouseleave', function() {
        $(this).css('transform', 'scale(1)');
    });
    
    // Animate confirmation checkbox
    $('#swap-confirm-removal').on('change', function() {
        const label = $(this).closest('.swap-confirmation-label');
        if (this.checked) {
            label.css({
                'border-color': '#dc3232',
                'background-color': '#fef7f1'
            });
        } else {
            label.css({
                'border-color': '#ddd',
                'background-color': '#fff'
            });
        }
    });
    
    // Add hover effects to buttons
    $('#swap-uninstall-button').on('mouseenter', function() {
        if (!$(this).prop('disabled')) {
            $(this).css('background-color', '#dc3232');
        }
    }).on('mouseleave', function() {
        if (!$(this).prop('disabled')) {
            $(this).css('background-color', '');
        }
    });
    
    // Prevent accidental page navigation
    let uninstallInProgress = false;
    
    $('#swap-uninstall-button').on('click', function() {
        uninstallInProgress = true;
    });
    
    $(window).on('beforeunload', function(e) {
        if (uninstallInProgress) {
            const message = 'Uninstall is in progress. Are you sure you want to leave?';
            e.returnValue = message;
            return message;
        }
    });
});