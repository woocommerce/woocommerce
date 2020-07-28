/**
 * External dependencies
 */
import { render } from '@wordpress/element';

/**
 * WooCommerce dependencies
 */
import { withPluginsHydration } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import ShippingBanner from './shipping-banner';

const metaBox = document.getElementById( 'wc-admin-shipping-banner-root' );
const args =
	( metaBox.dataset.args && JSON.parse( metaBox.dataset.args ) ) || {};

// Render the header.
const HydratedShippingBanner = withPluginsHydration( {
	...window.wcSettings.plugins,
	jetpackStatus: window.wcSettings.dataEndpoints.jetpackStatus,
} )( ShippingBanner );
render(
	<HydratedShippingBanner itemsCount={ args.shippable_items_count } />,
	metaBox
);
