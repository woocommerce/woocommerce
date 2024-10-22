/**
 * External dependencies
 */
// @ts-expect-error -- @wordpress/element doesn't export createRoot until WP6.2
// eslint-disable-next-line @woocommerce/dependency-group
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
