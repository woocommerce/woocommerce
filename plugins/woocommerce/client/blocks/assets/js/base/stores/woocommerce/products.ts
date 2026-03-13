/**
 * External dependencies
 */
import { store, getContext } from '@wordpress/interactivity';
import type { ProductResponseItem } from '@woocommerce/types';
import type { SelectedAttributes } from '@woocommerce/stores/woocommerce/cart';

/**
 * Internal dependencies
 */
import { findMatchingVariation } from '../../utils/variations/attribute-matching';

/**
 * Per-element context set via data-wp-context on wrapper elements (e.g. the
 * SingleProduct block). When present, this takes precedence over the
 * server-hydrated state so that each product in a loop gets its own IDs.
 */
type ProductContext = {
	productId: number;
	variationId?: number | null;
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
	variations: Record< number, ProductResponseItem >;
	/**
	 * Look up a product by ID, resolving to the matching variation for
	 * variable products when selectedAttributes are provided.
	 */
	findVariation: ( args: {
		id: number;
		selectedAttributes?: SelectedAttributes[];
	} ) => ProductResponseItem | null;

	/**
	 * The global product ID for the current page.
	 */
	productId: number;
	/**
	 * The global selected variation ID, or null if none is selected.
	 */
	variationId: number | null;
	/**
	 * The main product for this page/block. Always the top-level product
	 * (e.g. the variable product "Hoodie"), never a variation.
	 * Resolves productId from per-block context when available.
	 */
	product: ProductResponseItem | null;
	/**
	 * The currently selected variation, or null if none is selected.
	 * For simple/grouped products, this is always null.
	 */
	variation: ProductResponseItem | null;
	/**
	 * The currently active product: the selected variation if one exists,
	 * otherwise the main product. Convenience getter that replaces the
	 * repeated `variation || product` pattern.
	 */
	selected: ProductResponseItem | null;
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
 * - variations: Record<variationId, ProductResponseItem>
 * - productId / variationId: current product-in-context IDs
 * - product / variation / selected: derived getters
 */
const { state: productsState } = store< ProductsStore >(
	'woocommerce/products',
	{
		state: {
			products: {},
			variations: {},
			findVariation( {
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
						productsState.variations[
							matchedVariation.id
						] ?? null
					);
				}

				return product;
			},

			get product(): ProductResponseItem | null {
				const context = getContext< ProductContext >(
					'woocommerce/products'
				);
				const productId = context
					? context.productId
					: productsState.productId;

				if ( ! productId ) {
					return null;
				}
				return productsState.products[ productId ] ?? null;
			},

			get variation(): ProductResponseItem | null {
				const context = getContext< ProductContext >(
					'woocommerce/products'
				);
				const variationId = context
					? context.variationId
					: productsState.variationId;
				if ( ! variationId ) {
					return null;
				}
				return productsState.variations[ variationId ] ?? null;
			},

			get selected(): ProductResponseItem | null {
				return productsState.variation || productsState.product;
			},
		},
	},
	{ lock: universalLock }
);
