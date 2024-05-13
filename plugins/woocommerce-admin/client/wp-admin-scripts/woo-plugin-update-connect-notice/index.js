/**
 * External dependencies
 */
import domReady from '@wordpress/dom-ready';
import { recordEvent } from '@woocommerce/tracks';

domReady( () => {
	const connectYourStoreLinks = document.querySelectorAll(
		'.woocommerce-connect-your-store'
	);

	if ( connectYourStoreLinks.length > 0 ) {
		recordEvent( 'woo_connect_notice_in_plugins_shown' );
		connectYourStoreLinks.forEach( ( link ) => {
			link.addEventListener( 'click', function () {
				recordEvent( 'woo_connect_notice_in_plugins_clicked' );
			} );
		} );
	}
} );
