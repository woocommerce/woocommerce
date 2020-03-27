/**
 * External dependencies
 */
import { render } from '@wordpress/element';

/**
 * Internal dependencies
 */
import ShippingBanner from './shipping-banner';

const metaBox = document.getElementById( 'wc-admin-shipping-banner-root' );
const args =
	( metaBox.dataset.args && JSON.parse( metaBox.dataset.args ) ) || {};

// Render the header.
render( <ShippingBanner itemsCount={ args.shippable_items_count } />, metaBox );
