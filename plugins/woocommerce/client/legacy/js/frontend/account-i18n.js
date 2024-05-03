/* global Cookies */
jQuery( function( $ ) {	
    // Select all elements with the class 'wc-block-components-notice-banner' that contain text
    var notices = $('.wc-block-components-notice-banner').filter(function() {
        return $(this).text().trim().length > 0;
    });

    if (notices.length > 0) {
        $(notices[0]).attr('tabindex', '-1').focus();
    }
});