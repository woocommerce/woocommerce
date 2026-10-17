/**
 * External dependencies
 */
import { createRoot } from '@wordpress/element';
import { withPluginsHydration } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import ShippingBanner from './shipping-banner';
import { getAdminSetting } from '~/utils/admin-settings';

const metaBox = document.getElementById( 'wc-admin-shipping-banner-root' );
const args =
	( metaBox.dataset.args && JSON.parse( metaBox.dataset.args ) ) || {};

// Render the header.
const HydratedShippingBanner = withPluginsHydration( {
	...getAdminSetting( 'plugins' ),
	jetpackStatus: getAdminSetting( 'dataEndpoints', {} ).jetpackStatus,
} )( ShippingBanner );

createRoot( metaBox ).render(
	<HydratedShippingBanner itemsCount={ args.items } />
);
