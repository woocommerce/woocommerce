/**
 * External dependencies
 */
import { store } from '@wordpress/interactivity';
import type { ProductResponseItem } from '@woocommerce/types';
import type { SelectedAttributes } from '@woocommerce/stores/woocommerce/cart';

/**
 * Internal dependencies
 */
import { findMatchingVariation } from '../../utils/variations/attribute-matching';

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
	/**
	 * Look up a product by ID, resolving to the matching variation for
	 * variable products when selectedAttributes are provided.
	 */
	getProduct: ( args: {
		id: number;
		selectedAttributes?: SelectedAttributes[];
	} ) => ProductResponseItem | null;
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
 * Consumers access it via store() call with the namespace.
 *
 * State structure:
 * - products: Record<productId, ProductResponseItem>
 * - productVariations: Record<variationId, ProductResponseItem>
 */
const { state: productsState } = store< ProductsStore >(
	'woocommerce/products',
	{
		state: {
			products: {},
			productVariations: {},
			getProduct( {
				id,
				selectedAttributes,
			}: {
				id: number;
				selectedAttributes?: SelectedAttributes[];
			} ): ProductResponseItem | null {
				const product = productsState.products[ id ];

				if ( ! product ) {
					return null;
				}

				if (
					product.type === 'variable' &&
					selectedAttributes?.length
				) {
					const matchedVariation = findMatchingVariation(
						product,
						selectedAttributes
					);

					if ( ! matchedVariation ) {
						return null;
					}

					return (
						productsState.productVariations[
							matchedVariation.id
						] ?? null
					);
				}

				return product;
			},
		},
	},
	{ lock: universalLock }
);
