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
 * Per-element selection for the current product/variation.
 *
 * The "current" product can be set in two ways:
 * - Globally, via `wp_interactivity_state( 'woocommerce/products', [ ... ] )`
 *   (used by SingleProductTemplate — one product per page).
 * - Per-element, via `data-wp-context="woocommerce/products::{ ... }"` on a
 *   wrapper element (used by SingleProduct so each product in a loop gets
 *   its own IDs).
 *
 * When present, per-element context takes precedence over the global state.
 * See ./README.md for the full model and precedence rules.
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
	productVariations: Record< number, ProductResponseItem >;
	/**
	 * Look up a product by ID, resolving to the matching variation for
	 * variable products when selectedAttributes are provided.
	 */
	findProductVariation: ( args: {
		id: number;
		selectedAttributes?: SelectedAttributes[];
	} ) => ProductResponseItem | null;
	/**
	 * The current product ID from state or per-element context.
	 */
	productId: number;
	/**
	 * The current variation ID from state or per-element context.
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
	selectedVariation: ProductResponseItem | null;
	/**
	 * The resolved product for the current context: `selectedVariation`
	 * if one is set, otherwise the main `product`. This is the property
	 * most blocks should bind to — use `product` / `selectedVariation`
	 * explicitly only when the distinction matters.
	 *
	 * Blocks can bind directly to properties, e.g.:
	 *   state.productInContext.stock_availability.text
	 *   state.productInContext.sku
	 */
	productInContext: ProductResponseItem | null;
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
 * Server-hydrated cache of product and variation data in Store API format
 * (`ProductResponseItem`). PHP loaders populate `products` / `productVariations`;
 * derived getters below resolve the "current" product from either global state
 * or per-element context. These getters are mirrored in PHP
 * (see ProductsStore::register_getters) so directive bindings like
 * `state.productInContext.sku` resolve during SSR as well as on the client.
 *
 * See ./README.md for the complete model, loaders, and consumer patterns.
 */
const { state: productsState } = store< ProductsStore >(
	'woocommerce/products',
	{
		state: {
			products: {},
			productVariations: {},
			findProductVariation( {
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

			get selectedVariation(): ProductResponseItem | null {
				const context = getContext< ProductContext >(
					'woocommerce/products'
				);
				const variationId = context
					? context.variationId
					: productsState.variationId;
				if ( ! variationId ) {
					return null;
				}
				return productsState.productVariations[ variationId ] ?? null;
			},

			get productInContext(): ProductResponseItem | null {
				return productsState.selectedVariation || productsState.product;
			},
		},
	},
	{ lock: universalLock }
);
