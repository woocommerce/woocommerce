/**
 * Internal dependencies
 */
import { updateBlockSettings } from '../../config-tools/block-config';

/**
 * Enable alignment support for the product image block.
 */
function enableProductImageAlignment() {
	updateBlockSettings( 'woocommerce/product-image', ( current ) => ( {
		...current,
		supports: {
			...( current.supports || {} ),
			align: [ 'full' ],
		},
	} ) );
}

export { enableProductImageAlignment };
