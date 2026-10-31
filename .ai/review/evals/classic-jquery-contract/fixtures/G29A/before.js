/* Classic checkout request and an installed extension’s prefilter. */
function updateCheckout( address ) { return jQuery.ajax( { url: wc_checkout_params.wc_ajax_url, type: 'POST', data: { security: wc_checkout_params.update_order_review_nonce, country: address.country, postcode: address.postcode } } ); }

jQuery.ajaxPrefilter( ( options, originalOptions ) => { if ( originalOptions.data?.country === 'AQ' ) { options.beforeSend = () => false; } } );
