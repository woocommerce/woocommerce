/**
 * External dependencies
 */
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ProductTour } from '../../guided-tours/add-product-tour/index';

const root = document.createElement( 'div' );
root.setAttribute( 'id', 'product-tour-root' );

createRoot( document.body.appendChild( root ) ).render( <ProductTour /> );
