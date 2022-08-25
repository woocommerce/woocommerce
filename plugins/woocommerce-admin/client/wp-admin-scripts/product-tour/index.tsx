/**
 * External dependencies
 */
import { render } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ProductTour } from '../../guided-tours/add-product-tour/index';

const root = document.createElement( 'div' );
root.setAttribute( 'id', 'product-tour-root' );
render( <ProductTour />, document.body.appendChild( root ) );
