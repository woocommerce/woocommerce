/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { dispatch } from '@wordpress/data';
import domReady from '@wordpress/dom-ready';
import { getAdminLink } from '@woocommerce/settings';

/**
 * Returns a promise and resolves when the loader overlay no longer exists.
 *
 * @param {HTMLElement} saveButton Save button DOM element.
 * @return {Promise} Promise for save status.
 */
const saveCompleted = ( saveButton ) => {
	if ( saveButton && ! saveButton.disabled ) {
		const promise = new Promise( ( resolve ) => {
			window.requestAnimationFrame( resolve );
		} );
		return promise.then( () => saveCompleted( saveButton ) );
	}

	return Promise.resolve( true );
};

/**
 * Displays a notice on tax settings save.
 */
const showTaxCompletionNotice = () => {
	const saveButton = document.querySelector( '.woocommerce-save-button' );

	if ( saveButton.classList.contains( 'has-tax' ) ) {
		return;
	}

	saveCompleted( saveButton ).then( () => {
		// Check if a row was added successfully after WooCommerce removes invalid rows.
		if ( ! document.querySelector( '.wc_tax_rates .tips' ) ) {
			return;
		}

		saveButton.classList.add( 'has-tax' );
		dispatch( 'core/notices' ).createSuccessNotice(
			__( 'You’ve added your first tax rate!', 'woocommerce' ),
			{
				id: 'WOOCOMMERCE_ONBOARDING_TAX_NOTICE',
				actions: [
					{
						url: getAdminLink( 'admin.php?page=wc-admin' ),
						label: __( 'Continue setup.', 'woocommerce' ),
					},
				],
			}
		);
	} );
};

domReady( () => {
	const saveButton = document.querySelector( '.woocommerce-save-button' );

	if (
		window.htmlSettingsTaxLocalizeScript &&
		window.htmlSettingsTaxLocalizeScript.rates &&
		! window.htmlSettingsTaxLocalizeScript.rates.length &&
		saveButton
	) {
		saveButton.addEventListener( 'click', showTaxCompletionNotice );
	}
} );
