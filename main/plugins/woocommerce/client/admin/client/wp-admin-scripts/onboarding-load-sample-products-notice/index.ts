/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { dispatch } from '@wordpress/data';
import domReady from '@wordpress/dom-ready';
import { getAdminLink } from '@woocommerce/settings';

domReady( () => {
	dispatch( 'core/notices' ).createSuccessNotice(
		__( 'Sample products added', 'woocommerce' ),
		{
			id: 'WOOCOMMERCE_ONBOARDING_LOAD_SAMPLE_PRODUCTS_NOTICE',
			actions: [
				{
					url: getAdminLink( 'admin.php?page=wc-admin' ),
					label: __(
						'Continue setting up your store',
						'woocommerce'
					),
				},
			],
		}
	);
} );
