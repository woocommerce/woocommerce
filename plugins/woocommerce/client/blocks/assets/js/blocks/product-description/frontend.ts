/**
 * External dependencies
 */
import { getContext, store } from '@wordpress/interactivity';
import type { DisplayedProduct } from '@woocommerce/type-defs/product';
import type { SingleProductTemplateStore } from '@woocommerce/base-stores/single-product-template';

/**
 * Internal dependencies
 */

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const { state: wooState } = store< SingleProductTemplateStore >(
	'woocommerce',
	{},
	{ lock: universalLock }
);

const productDescriptionStore = store(
	'woocommerce/product-description',
	{
		state: {
			get description() {
				const context = getContext< {
					displayedProduct: DisplayedProduct;
				} >( 'woocommerce/single-product' );

				if ( context ) {
					return context.displayedProduct.description;
				} else if ( wooState.displayedProduct ) {
					return wooState.displayedProduct.description;
				}
			},
		},
	},
	{ lock: true }
);

export type ProductDescriptionStore = typeof productDescriptionStore;
