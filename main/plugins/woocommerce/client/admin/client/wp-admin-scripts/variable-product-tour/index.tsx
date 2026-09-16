/**
 * External dependencies
 */
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { VariableProductTour } from '../../guided-tours/variable-product-tour';

const root = document.createElement( 'div' );
root.setAttribute( 'id', 'variable-product-tour-root' );

createRoot( document.body.appendChild( root ) ).render(
	<VariableProductTour />
);
