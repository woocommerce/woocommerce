/**
 * WooCommerce Status Widget Async Loading
 */
jQuery(function($) {
    'use strict';
    
    // Only run on admin dashboard
    if (!$('#wc-status-widget-loading').length) {
        return;
    }
    
    // Load the widget content via AJAX
    function loadStatusWidget() {
        $.ajax({
            url: wc_status_widget_params.ajax_url,
            data: {
                action: 'woocommerce_load_status_widget',
                security: wc_status_widget_params.security
            },
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response && response.success && response.data.content) {
                    $('#wc-status-widget-content').html(response.data.content).show();
                    $('#wc-status-widget-loading').hide();
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
        $('#wc-status-widget-loading').html('<p>' + 'Error loading widget' + '</p>');
    }
    
    // Start loading the widget after a very short delay
    // This allows the dashboard to render quickly first
    setTimeout(loadStatusWidget, 100);
});