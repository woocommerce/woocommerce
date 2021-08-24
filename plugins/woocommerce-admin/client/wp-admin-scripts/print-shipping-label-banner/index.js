/**
 * External dependencies
 */
import { render } from '@wordpress/element';
import { withPluginsHydration } from '@woocommerce/data';
import { getSetting } from '@woocommerce/wc-admin-settings';

/**
 * Internal dependencies
 */
import ShippingBanner from './shipping-banner';

const metaBox = document.getElementById( 'wc-admin-shipping-banner-root' );
const args =
	( metaBox.dataset.args && JSON.parse( metaBox.dataset.args ) ) || {};

// Render the header.
const HydratedShippingBanner = withPluginsHydration( {
	...getSetting( 'plugins' ),
	jetpackStatus: getSetting( 'dataEndpoints', {} ).jetpackStatus,
} )( ShippingBanner );
render( <HydratedShippingBanner itemsCount={ args.items } />, metaBox );
