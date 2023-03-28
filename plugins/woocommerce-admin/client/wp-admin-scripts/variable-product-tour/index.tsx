/**
 * External dependencies
 */
import { render } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { VariableProductTour } from '../../guided-tours/variable-product-tour';

const root = document.createElement( 'div' );
root.setAttribute( 'id', 'variable-product-tour-root' );
render( <VariableProductTour />, document.body.appendChild( root ) );
