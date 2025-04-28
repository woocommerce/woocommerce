( function ( $ ) {
	'use strict';

	function trackDeprecationNotice() {
		$.post( ajaxurl, {
			action: 'woo_ai_track_deprecation',
			nonce: wooAITracker.nonce,
		} );
	}

	// When the AI features are used, track the usage interaction so we can show the notice to the user
	$( document ).on(
		'click',
		`
		#woocommerce-ai-app-product-gpt-button,
		#woocommerce-ai-app-product-short-description-gpt-button,
		.woo-ai-get-suggestions-btn,
		.woocommerce-ai-app-product-category-suggestions
	`,
		trackDeprecationNotice
	);

	$( document ).on(
		'click',
		'.woo-ai-deprecation-notice .notice-dismiss',
		function () {
			$.post( ajaxurl, {
				action: 'woo_ai_dismiss_deprecation',
				nonce: wooAITracker.nonce,
			} );
		}
	);
} )( jQuery );
