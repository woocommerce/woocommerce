document.addEventListener('DOMContentLoaded', function() {
    // Select all elements with the class 'wc-block-components-notice-banner'
    const notices = Array.from(document.querySelectorAll('.wc-block-components-notice-banner')).filter(el => {
        // Filter elements that contain text
        return el.textContent.trim().length > 0;
    });

    // Focus on the first element if any are found
    if (notices.length > 0) {
        notices[0].setAttribute('tabindex', '-1');
        notices[0].focus();
    }
});
