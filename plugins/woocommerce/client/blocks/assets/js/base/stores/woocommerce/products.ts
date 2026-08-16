/**
 * External dependencies
 */
import { store, getContext } from '@wordpress/interactivity';
import type { ProductResponseItem } from '@woocommerce/types';
import type { SelectedAttributes } from '@woocommerce/stores/woocommerce/cart';

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
	 * Look up a product by ID. If the ID exists in `productVariations`,
	 * returns the variation directly (ignoring `selectedAttributes`).
	 * Otherwise looks in `products`: for variable products with
	 * `selectedAttributes`, returns the matching variation or `null`;
	 * for all other cases returns the product itself.
	 */
	findProduct: ( args: {
		id: number;
		selectedAttributes?: SelectedAttributes[] | null;
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
	mainProductInContext: ProductResponseItem | null;
	/**
	 * The currently selected variation, or null if none is selected.
	 * For simple/grouped products, this is always null.
	 */
	productVariationInContext: ProductResponseItem | null;
	/**
	 * The resolved product for the current context:
	 * `productVariationInContext` if one is set, otherwise
	 * `mainProductInContext`. This is the property most blocks should
	 * bind to — use `mainProductInContext` / `productVariationInContext`
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

const normalizeAttributeName = ( name: string ): string =>
	name
		.replace( /^attribute_(pa_)?/, '' )
		.replace( /-/g, ' ' )
		.toLowerCase();

const attributeNamesMatch = ( a: string, b: string ): boolean =>
	normalizeAttributeName( a ) === normalizeAttributeName( b );

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
			findProduct( {
				id,
				selectedAttributes,
			}: {
				id: number;
				selectedAttributes?: SelectedAttributes[] | null;
			} ): ProductResponseItem | null {
				const variation = productsState.productVariations[ id ];
				if ( variation ) {
					return variation;
				}

				const product = productsState.products[ id ];

				if ( ! product ) {
					return null;
				}

				if (
					product.type !== 'variable' ||
					! selectedAttributes?.length
				) {
					return product;
				}

				const matchedVariation = product.variations?.find( ( v ) =>
					v.attributes.every( ( attr ) => {
						const selectedAttr = selectedAttributes.find(
							( selected ) =>
								attributeNamesMatch(
									attr.name,
									selected.attribute
								)
						);

						if ( attr.value === null ) {
							return (
								selectedAttr !== undefined &&
								selectedAttr.value !== null
							);
						}

						return selectedAttr?.value === attr.value;
					} )
				);

				if ( ! matchedVariation ) {
					return null;
				}

				return (
					productsState.productVariations[ matchedVariation.id ] ??
					null
				);
			},

			get mainProductInContext(): ProductResponseItem | null {
				const context = getContext< ProductContext >(
					'woocommerce/products'
				);
				const productId =
					context && 'productId' in context
						? context.productId
						: productsState.productId;

				if ( ! productId ) {
					return null;
				}
				return productsState.products[ productId ] ?? null;
			},

			get productVariationInContext(): ProductResponseItem | null {
				const context = getContext< ProductContext >(
					'woocommerce/products'
				);
				const variationId =
					context && 'variationId' in context
						? context.variationId
						: productsState.variationId;
				if ( ! variationId ) {
					return null;
				}
				return productsState.productVariations[ variationId ] ?? null;
			},

			get productInContext(): ProductResponseItem | null {
				return (
					productsState.productVariationInContext ||
					productsState.mainProductInContext
				);
			},
		},
	},
	{ lock: universalLock }
);
