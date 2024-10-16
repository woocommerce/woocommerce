/**
 * External dependencies
 */
// @ts-expect-error -- @wordpress/element doesn't export createRoot until WP6.2
// eslint-disable-next-line @woocommerce/dependency-group
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import WCAddonsTour from '../../guided-tours/wc-addons-tour/index';

const root = document.createElement( 'div' );
root.setAttribute( 'id', 'wc-addons-tour-root' );

createRoot( document.body.appendChild( root ) ).render( <WCAddonsTour /> );
