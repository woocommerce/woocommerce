jQuery( function ( $ ) {
	$( document ).on( 'click', '#purple-woocommerce-notice .notice-dismiss', function () {
		$.post( purpleWooCommerceNotice.ajaxUrl, {
			action: 'purple_dismiss_woocommerce_notice',
			nonce: purpleWooCommerceNotice.nonce,
		} );
	} );
} );
