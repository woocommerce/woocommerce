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
import { getAdminLink } from '@woocommerce/navigation';

/**
 * Displays a notice on tax settings save.
 */
const showTaxCompletionNotice = () => {
	dispatch( 'core/notices' ).createSuccessNotice(
		__( 'Your tax settings have been saved.', 'woocommerce-admin' ),
		{
			id: 'WOOCOMMERCE_ONBOARDING_TAX_NOTICE',
			actions: [
				{
					url: getAdminLink( 'admin.php?page=wc-admin' ),
					label: __( 'Continue setup.', 'woocommerce-admin' ),
				},
			],
		}
	);
};

domReady( () => {
	const saveButton = document.querySelector( '.woocommerce-save-button' );
	if ( saveButton ) {
		saveButton.addEventListener( 'click', showTaxCompletionNotice );
	}
} );
