/**
 * WooCommerce Recent reviews widget async loading
 */
jQuery(function($) {
    'use strict';

    // Only run on admin dashboard
    if ( ! $( '#wc-recent-reviews-widget-loading' ).length ) {
        return;
    }

    // Load the widget content via AJAX
    function loadRecentReviewsWidget() {
        $.ajax({
            url: wc_recent_reviews_widget_params.ajax_url,
            data: {
                action: 'woocommerce_load_recent_reviews_widget',
                security: wc_recent_reviews_widget_params.security
            },
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if ( response && response.success && response.data.content ) {
                    $( '#wc-recent-reviews-widget-content' ).html( response.data.content ).show();
                    $( '#wc-recent-reviews-widget-loading' ).hide();
                } else {
                    showErrorMessage();
                }
            },
            error: function() {
                showErrorMessage();
            }
        });
    }

    function showErrorMessage() {
		const message = wc_recent_reviews_widget_params.error_message || 'Error loading widget';
        $( '#wc-recent-reviews-widget-loading' ).html( '<p>' + message + '</p>' );
    }

    // Start loading the widget after a very short delay
    // This allows the dashboard to render quickly first
    setTimeout( loadRecentReviewsWidget, 100 );
});
