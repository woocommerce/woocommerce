/**
 * External dependencies
 */
import { store } from '@wordpress/interactivity';
import type { ProductResponseItem } from '@woocommerce/types';
import '@woocommerce/stores/woocommerce/products';
import type { ProductsStore } from '@woocommerce/stores/woocommerce/products';

/**
 * Product and variation data in the `woocommerce` store, keyed by ID, in
 * Store API format. Read-only: product data is loaded from PHP.
 */
export type ProductDataState = {
	readonly products: Readonly< Record< number, ProductResponseItem > >;
	readonly productVariations: Readonly<
		Record< number, ProductResponseItem >
	>;
};

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const { state: productsState } = store< ProductsStore >(
	'woocommerce/products',
	{},
	{ lock: universalLock }
);

// Temporary: until every block reads product data from `woocommerce`, these
// getters return the `woocommerce/products` data instead of a copy. The store
// then holds the data itself, and code that reads it doesn't change.
store< { state: ProductDataState } >(
	'woocommerce',
	{
		state: {
			get products() {
				return productsState.products;
			},
			get productVariations() {
				return productsState.productVariations;
			},
		},
	},
	{ lock: universalLock }
);
