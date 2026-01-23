/**
 * External dependencies
 */
import { store } from '@wordpress/interactivity';

/**
 * Internal dependencies
 */
import type { ProductResponseItem } from '../../../types';

/**
 * Quantity constraints normalized from the Store API format.
 */
export type QuantityConstraints = {
	min: number;
	max: number;
	step: number;
};

/**
 * The state shape for the products store.
 * This matches the server-side ProductsStore state structure.
 */
export type ProductsStoreState = {
	/**
	 * Products keyed by product ID.
	 * These are in Store API format (ProductResponseItem).
	 */
	products: Record< number, ProductResponseItem >;
	/**
	 * Product variations keyed by variation ID.
	 * These are in Store API format (ProductResponseItem).
	 */
	productVariations: Record< number, ProductResponseItem >;
};

/**
 * The products store type definition.
 */
export type ProductsStore = {
	state: ProductsStoreState;
};

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

/**
 * The woocommerce/products store.
 *
 * This store manages product data in Store API format for use with the
 * Interactivity API. Data is hydrated server-side via PHP ProductsStore.
 *
 * State structure:
 * - products: Record<productId, ProductResponseItem>
 * - productVariations: Record<variationId, ProductResponseItem>
 */
const productsStore = store< ProductsStore >(
	'woocommerce/products',
	{
		state: {
			products: {},
			productVariations: {},
		},
	},
	{ lock: universalLock }
);

const { state } = productsStore;

/**
 * Extract quantity constraints from a product in Store API format.
 *
 * @param product The product in Store API format.
 * @return Normalized quantity constraints.
 */
export const getQuantityConstraints = (
	product: ProductResponseItem | null
): QuantityConstraints => {
	if ( ! product ) {
		return { min: 1, max: Number.MAX_SAFE_INTEGER, step: 1 };
	}

	const addToCart = product.add_to_cart;
	const maximum = addToCart?.maximum ?? 0;

	return {
		min: addToCart?.minimum ?? 1,
		max: maximum > 0 ? maximum : Number.MAX_SAFE_INTEGER,
		step: addToCart?.multiple_of ?? 1,
	};
};

export { state, productsStore };
export default productsStore;
