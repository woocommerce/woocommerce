/* Classic checkout uses jQuery defaults; address values are strings. */
function updateCheckout( address ) { const buildData = () => ( { security: wc_checkout_params.update_order_review_nonce, country: address.country } ); const data = buildData(); return jQuery.ajax( { url: wc_checkout_params.wc_ajax_url, type: 'POST', data } ); }

jQuery.ajaxPrefilter( ( options, originalOptions ) => { if ( originalOptions.data?.country === 'AQ' ) { options.beforeSend = () => false; } } );
