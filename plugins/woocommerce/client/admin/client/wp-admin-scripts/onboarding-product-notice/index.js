/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { dispatch } from '@wordpress/data';
import domReady from '@wordpress/dom-ready';
import { getAdminLink } from '@woocommerce/settings';

/**
 * Displays a notice after product creation.
 */
const showProductCompletionNotice = () => {
	dispatch( 'core/notices' ).createSuccessNotice(
		__( '🎉 Congrats on adding your first product!', 'woocommerce' ),
		{
			id: 'WOOCOMMERCE_ONBOARDING_PRODUCT_NOTICE',
			actions: [
				{
					url: getAdminLink( 'admin.php?page=wc-admin' ),
					label: __( 'Continue setup.', 'woocommerce' ),
				},
			],
		}
	);
};

domReady( () => {
	showProductCompletionNotice();
} );
