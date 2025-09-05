/* global Cookies */
jQuery( function( $ ) {	
    // Select all elements with the class [role="alert"] attribute that contain text
    var notices = $('[role="alert"]').filter(function() {
        return $(this).text().trim().length > 0;
    });

    if (notices.length > 0) {
        /**
         * Queuing the focus event at last of the event queue
         * to override any other focus events in case of critical error.
         * For example, "Skip to content" was being focused just after
         * the error, resulting in the voiceover breaking the message
         * in between.
         */
        setTimeout(function() {
            $(notices[0]).attr('tabindex', '-1').focus();
        });
    }
});
