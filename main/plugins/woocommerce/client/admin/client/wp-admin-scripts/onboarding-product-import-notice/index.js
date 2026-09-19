/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import domReady from '@wordpress/dom-ready';
import { getAdminLink } from '@woocommerce/settings';

domReady( () => {
	const actionButtons = document.querySelector( '.wc-actions' );
	if ( actionButtons ) {
		const primaryButton = document.querySelector(
			'.wc-actions .button-primary'
		);
		if ( primaryButton ) {
			primaryButton.classList.remove( 'button' );
			primaryButton.classList.remove( 'button-primary' );
		}

		const continueButton = document.createElement( 'a' );
		continueButton.classList.add( 'button' );
		continueButton.classList.add( 'button-primary' );
		continueButton.setAttribute(
			'href',
			getAdminLink( 'admin.php?page=wc-admin' )
		);
		continueButton.innerText = __( 'Continue setup', 'woocommerce' );

		actionButtons.appendChild( continueButton );
	}
} );
