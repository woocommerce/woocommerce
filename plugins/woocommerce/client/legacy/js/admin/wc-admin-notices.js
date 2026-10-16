( function () {
	'use strict';

	/**
	 * Dismiss WooCommerce admin notices asynchronously.
	 *
	 * Notice dismiss links point at the current page with a `wc-hide-notice`
	 * query arg, which persists the dismissal via a full page reload. Here we
	 * intercept the click, persist the dismissal over admin-ajax instead, and
	 * remove the notice in place. The link href is kept intact as a no-JS
	 * fallback, and on any request failure we fall back to following it.
	 */
	document.addEventListener( 'click', function ( event ) {
		var link = event.target.closest(
			'a.woocommerce-message-close[href*="wc-hide-notice"]'
		);

		if ( ! link || typeof window.ajaxurl === 'undefined' ) {
			return;
		}

		var url;
		try {
			url = new URL( link.href );
		} catch ( e ) {
			return;
		}

		var noticeName = url.searchParams.get( 'wc-hide-notice' );
		var nonce = url.searchParams.get( '_wc_notice_nonce' );

		if ( ! noticeName || ! nonce ) {
			return;
		}

		event.preventDefault();

		var notice = link.closest( '.notice, .woocommerce-message, #message' );

		window
			.fetch( window.ajaxurl, {
				method: 'POST',
				credentials: 'same-origin',
				body: new URLSearchParams( {
					action: 'woocommerce_hide_notice',
					'wc-hide-notice': noticeName,
					_wc_notice_nonce: nonce,
				} ),
			} )
			.then( function ( response ) {
				if ( ! response.ok ) {
					throw new Error( 'Failed to dismiss notice' );
				}

				if ( notice ) {
					notice.remove();
				}

				document.dispatchEvent(
					new CustomEvent( 'wc-admin-notice-dismissed', {
						detail: { notice: noticeName },
					} )
				);
			} )
			.catch( function () {
				window.location.href = link.href;
			} );
	} );
} )();
