/**
 * External dependencies
 */
import domReady from '@wordpress/dom-ready';
import { recordEvent } from '@woocommerce/tracks';

domReady( () => {
	const purchaseSubscriptionLink = document.querySelectorAll(
		'.woocommerce-purchase-subscription'
	);

	if ( purchaseSubscriptionLink.length > 0 ) {
		recordEvent( 'woo_purchase_subscription_in_plugins_shown' );
		purchaseSubscriptionLink.forEach( ( link ) => {
			link.addEventListener( 'click', function () {
				recordEvent( 'woo_purchase_subscription_in_plugins_clicked' );
			} );
		} );
	}
} );
