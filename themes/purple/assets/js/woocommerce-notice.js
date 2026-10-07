/* global ajaxurl, purpleWooCommerceNotice */
( function ( $ ) {
	$( document ).on( 'click', '#purple-woocommerce-notice .notice-dismiss', function () {
		$.post( ajaxurl, {
			action: 'purple_dismiss_woocommerce_notice',
			nonce: purpleWooCommerceNotice.nonce
		} );
	} );
} )( jQuery );
