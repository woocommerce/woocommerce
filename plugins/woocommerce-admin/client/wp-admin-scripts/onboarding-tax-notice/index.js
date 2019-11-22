/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { dispatch } from '@wordpress/data';
import domReady from '@wordpress/dom-ready';

/**
 * WooCommerce dependencies
 */
import { getAdminLink } from '@woocommerce/wc-admin-settings';

/**
 * Returns a promise and resolves when the loader overlay no longer exists.
 *
 * @return {Promise} Promise for overlay existence.
 */
const saveCompleted = () => {
	if ( document.querySelector( '.blockUI.blockOverlay' ) !== null ) {
		const promise = new Promise( resolve => {
			requestAnimationFrame( resolve );
		} );
		return promise.then( () => saveCompleted() );
	}

	return Promise.resolve( true );
};

/**
 * Displays a notice on tax settings save.
 */
const showTaxCompletionNotice = () => {
	const saveButton = document.querySelector( '.woocommerce-save-button' );

	if ( saveButton.classList.contains( 'is-clicked' ) ) {
		return;
	}

	saveButton.classList.add( 'is-clicked' );
	saveCompleted().then( () =>
		dispatch( 'core/notices' ).createSuccessNotice(
			__( "You've added your first tax rate!", 'woocommerce-admin' ),
			{
				id: 'WOOCOMMERCE_ONBOARDING_TAX_NOTICE',
				actions: [
					{
						url: getAdminLink( 'admin.php?page=wc-admin' ),
						label: __( 'Continue setup.', 'woocommerce-admin' ),
					},
				],
			}
		)
	);
};

domReady( () => {
	const saveButton = document.querySelector( '.woocommerce-save-button' );
	if ( saveButton ) {
		saveButton.addEventListener( 'click', showTaxCompletionNotice );
	}
} );
