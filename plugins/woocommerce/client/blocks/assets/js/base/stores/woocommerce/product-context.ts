/**
 * External dependencies
 */
import { store } from '@wordpress/interactivity';
import type { ProductResponseItem } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import type { ProductsStore } from './products';
import './products'; // Ensure store is registered (side effect)

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const productsStore = store< ProductsStore >(
	'woocommerce/products',
	{},
	{ lock: universalLock }
);

export type ProductContextStore = {
	state: {
		productId: number;
		variationId: number | null;
		/**
		 * The currently active product for display/cart operations.
		 * Returns the variation if variationId is set, otherwise the main product.
		 */
		currentProduct: ProductResponseItem | undefined;
		/**
		 * The parent variable product when a variation is active.
		 * Null for simple products or when no variation is selected.
		 */
		parentProduct: ProductResponseItem | null;
	};
};

const productContextStore = store< ProductContextStore >(
	'woocommerce/product-context',
	{
		state: {
			productId: 0,
			variationId: null,

			get currentProduct(): ProductResponseItem | undefined {
				const { productId, variationId } =
					productContextStore.state;
				if ( ! productId ) {
					return undefined;
				}
				if ( variationId ) {
					return productsStore.state.productVariations[
						variationId
					];
				}
				return productsStore.state.products[ productId ];
			},

			get parentProduct(): ProductResponseItem | null {
				const { productId, variationId } =
					productContextStore.state;
				if ( ! variationId ) {
					return null;
				}
				return (
					productsStore.state.products[ productId ] ?? null
				);
			},
		},
	},
	{ lock: universalLock }
);
