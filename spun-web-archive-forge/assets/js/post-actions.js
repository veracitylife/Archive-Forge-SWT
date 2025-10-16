/**
 * Spun Web Archive Forge Post Actions JavaScript
 * Handles individual post submission functionality
 */

jQuery(document).ready(function($) {
    
    var SWAPPostActions = {
        
        /**
         * Initialize post actions
         */
        init: function() {
            this.bindEvents();
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Handle submission link clicks
            $(document).on('click', '.swap-submit-link', function(e) {
                e.preventDefault();
                
                var $link = $(this);
                var href = $link.attr('href');
                
                // Show confirmation dialog
                if (confirm(swapPostActions.strings.confirm)) {
                    // Show loading state
                    $link.text(swapPostActions.strings.submitting);
                    $link.css('pointer-events', 'none');
                    
                    // Navigate to submission URL
                    window.location.href = href;
                }
            });
        }
    };
    
    // Initialize when DOM is ready
    SWAPPostActions.init();
});