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
 * Displays a notice after product creation.
 */
const showProductCompletionNotice = () => {
	dispatch( 'core/notices' ).createSuccessNotice(
		__( 'You created your first product!', 'woocommerce-admin' ),
		{
			id: 'WOOCOMMERCE_ONBOARDING_PRODUCT_NOTICE',
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
	showProductCompletionNotice();
} );
