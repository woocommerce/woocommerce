/**
 * External dependencies
 */
import domReady from '@wordpress/dom-ready';
import { recordEvent } from '@woocommerce/tracks';

domReady( () => {
	const renewSubscriptionLink = document.querySelectorAll(
		'.woocommerce-renew-subscription'
	);

	if ( renewSubscriptionLink.length > 0 ) {
		recordEvent( 'woo_renew_subscription_in_plugins_shown' );
		renewSubscriptionLink.forEach( ( link ) => {
			link.addEventListener( 'click', function () {
				recordEvent( 'woo_renew_subscription_in_plugins_clicked' );
			} );
		} );
	}
} );
