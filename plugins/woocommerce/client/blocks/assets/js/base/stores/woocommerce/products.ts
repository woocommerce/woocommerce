/**
 * External dependencies
 */
import { store } from '@wordpress/interactivity';
import type { ProductResponseItem } from '@woocommerce/types';

/**
 * The product data the `woocommerce` store holds.
 *
 * The PHP `ProductsStore` loaders seed both members, with the same values as
 * `woocommerce/products`, so the two stores read equal data.
 */
export type WooCommerceProductData = {
	/**
	 * Products keyed by product ID, in Store API format.
	 */
	products: Record< number, ProductResponseItem >;
	/**
	 * Product variations keyed by variation ID, in Store API format.
	 */
	productVariations: Record< number, ProductResponseItem >;
};

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

/**
 * Declares the product data members of the `woocommerce` store.
 *
 * The `{}` defaults match those of `woocommerce/products`, so a page where no
 * loader ran reads empty records from both stores and a server-side empty
 * record is normalized to `{}` in both.
 */
store< { state: WooCommerceProductData } >(
	'woocommerce',
	{
		state: {
			products: {},
			productVariations: {},
		},
	},
	{ lock: universalLock }
);
