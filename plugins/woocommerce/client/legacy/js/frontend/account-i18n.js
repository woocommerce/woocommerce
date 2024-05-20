/* global Cookies */
jQuery( function( $ ) {	
    // Select all elements with the class [role="alert"] attribute that contain text
    var notices = $('[role="alert"]').filter(function() {
        return $(this).text().trim().length > 0;
    });

    if (notices.length > 0) {
        setTimeout(function() {
            $(notices[0]).attr('tabindex', '-1').focus();
        });
    }
});
