/* Classic checkout uses jQuery defaults; address values are strings. */
function updateCheckout( address ) { return jQuery.ajax( { url: wc_checkout_params.wc_ajax_url, type: 'POST', data: { security: wc_checkout_params.update_order_review_nonce, country: address.country } } ); }

jQuery.ajaxPrefilter( ( options, originalOptions ) => { if ( originalOptions.data?.country === 'AQ' ) { options.beforeSend = () => false; } } );
