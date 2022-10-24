/**
 * External dependencies
 */
import { render } from '@wordpress/element';

/**
 * Internal dependencies
 */
import WCAddonsTour from '../../guided-tours/wc-addons-tour/index';

const root = document.createElement( 'div' );
root.setAttribute( 'id', 'wc-addons-tour-root' );
render( <WCAddonsTour />, document.body.appendChild( root ) );
