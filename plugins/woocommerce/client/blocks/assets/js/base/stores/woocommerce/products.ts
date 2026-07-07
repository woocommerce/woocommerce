/**
 * External dependencies
 */
import { store, getContext } from '@wordpress/interactivity';
import type {
	ProductResponseItem,
	CartVariationItem,
} from '@woocommerce/types';

/**
 * A shopper's single attribute selection (Store API `add-item`/variation shape),
 * minus the display-only `raw_attribute`. Defined locally from `@woocommerce/types`
 * so the products store imports NOTHING from `woocommerce/cart` — coupling is
 * one-directional (cart → products only).
 */
export type SelectedAttributes = Omit< CartVariationItem, 'raw_attribute' >;

/**
 * The products store's OWN context namespace, `woocommerce/products`. Per-element
 * selection for the current product/variation.
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
 *
 * `variationId` is THE derivation source for the selected variation: a purchase
 * surface (e.g. the Add to Cart + Options form) resolves the shopper's attribute
 * selection to a variation via `findProduct` and writes the id here, alongside
 * the draft it upserts into `woocommerce/cart` (the "double write"). The products
 * store reads ONLY its own context/state — it never reads the cart store (neither
 * its context nor its state), so coupling is one-directional: cart → products
 * only.
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
	/**
	 * `productInContext` ONLY when the current selection is RESOLVED to one
	 * specific, concrete product a shopper can act on, else `null`.
	 *
	 * A selection is resolved when EITHER:
	 * - a variation is selected (`productVariationInContext` is non-null), OR
	 * - the product exposes no options to pick (`has_options === false`,
	 *   which covers simple, grouped, external — everything that needs no
	 *   attribute selection before it can be acted on).
	 *
	 * It is `null` while a variable product still has unresolved options
	 * (the shopper hasn't picked a variation yet). This is the type-invariant
	 * read that lets surfaces like the wishlist button express "the selection
	 * is actionable" without branching on `type === 'variable'`: they read
	 * `resolvedProductInContext?.id` and treat `null` as "not yet
	 * selectable". `has_options` is a server-computed capability field, so the
	 * decision is made from data, not a client-side type sniff.
	 *
	 * Deliberately named "resolved", NOT "purchasable": resolution says
	 * nothing about stock or purchasability (an external-with-URL product
	 * resolves here but is not add-to-cart purchasable). A server-computed
	 * purchasability capability field is a separate, named follow-up gap.
	 */
	resolvedProductInContext: ProductResponseItem | null;
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
 * A single variation attribute as it appears on a parent product's
 * `variations[].attributes` array (Store API format). `value` is `null`
 * when the attribute is set to "Any" for that variation.
 */
export type VariationAttribute = { name: string; value: string | null };

/**
 * Decide whether a variation matches a shopper's attribute selection,
 * mirroring the server's `find_matching_product_variation`
 * (WC_Product_Data_Store_CPT) exactly.
 *
 * The server iterates each of the variation's stored attributes and applies
 * two rules (an "Any" attribute is stored as an empty string server-side and
 * surfaces as `value === null` in the Store API):
 *
 * 1. If the attribute is NOT "Any" and the selection does not include it, the
 *    variation does not match (it requires a value none was given for).
 * 2. If the selection includes the attribute and it is NOT "Any", the selected
 *    value must equal the variation's value, otherwise no match.
 *
 * An "Any" variation attribute is therefore unconditionally permissive: it can
 * never cause a mismatch, regardless of whether the selection omits it, sets it
 * to null, or sets it to any concrete value. This is what lets a partial
 * selection resolve to an all-"Any"/partially-"Any" variation on the server,
 * and the client must agree.
 *
 * Extra selected attributes that the variation does not define are ignored, as
 * on the server (which only loops over the variation's own attributes).
 *
 * @param variationAttributes The variation's `attributes` array (Store API).
 * @param selectedAttributes  The shopper's attribute selection.
 * @return `true` when the variation matches the selection.
 */
export const variationMatchesSelection = (
	variationAttributes: VariationAttribute[],
	selectedAttributes: SelectedAttributes[]
): boolean =>
	variationAttributes.every( ( attr ) => {
		// An "Any" attribute (null value) never causes a mismatch, exactly
		// like the server's `'' === $attribute_value` short-circuit.
		if ( attr.value === null ) {
			return true;
		}

		const selectedAttr = selectedAttributes.find( ( selected ) =>
			attributeNamesMatch( attr.name, selected.attribute )
		);

		// Rule 1: the variation requires a specific value but the selection
		// does not include this attribute at all.
		if ( selectedAttr === undefined ) {
			return false;
		}

		// Rule 2: the selection provides a value; it must equal the
		// variation's required value.
		return selectedAttr.value === attr.value;
	} );

/**
 * The woocommerce/products store.
 *
 * Server-hydrated cache of product and variation data in Store API format
 * (`ProductResponseItem`). PHP loaders populate `products` / `productVariations`;
 * derived getters below resolve the "current" product from either global state
 * or per-element context. The context getters are mirrored in PHP
 * (see ProductsStore::register_getters) so directive bindings like
 * `state.productInContext.sku` resolve during SSR as well as on the client.
 * PHP mirrors are added on demand only: `resolvedProductInContext` is
 * client-only today (no server-side directive binds to it — the wishlist
 * button's SSR computes its initial state directly in PHP).
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

				// Deterministic variation resolution: mirror the server's
				// `find_matching_product_variation` tie-breaking. The server
				// iterates variations ordered by `menu_order ASC, ID ASC` and
				// returns the FIRST that matches. The Store API already
				// delivers `product.variations` in that exact order (it is
				// built from `get_visible_children()`, which orders by
				// `menu_order ASC, ID ASC`), so iterating this array in order
				// with `find` reproduces the server's first-match choice. See
				// ./README.md — "Variation resolution is deterministic and
				// mirrors the server".
				const matchedVariation = product.variations?.find( ( v ) =>
					variationMatchesSelection(
						v.attributes as VariationAttribute[],
						selectedAttributes
					)
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

				// `variationId` — set in the `woocommerce/products` context or
				// this store's global state — is THE derivation source for the
				// selected variation. A purchase surface resolves the shopper's
				// attribute selection to a variation via `findProduct` and writes
				// the id here (the "double write": draft in `woocommerce/cart` for
				// submission/pairing, `variationId` here for derivation). It is
				// also how a surface pins a specific variation directly (e.g. a
				// Single Product block bound to one variation). The products store
				// reads ONLY its own context/state — never the cart store — so
				// with no `variationId` set, there is no selected variation.
				//
				// CONTEXT PRESENCE SHADOWS GLOBAL: a surface that declares its own
				// `woocommerce/products` context (e.g. a collection card or a
				// grouped-child row) has scoped OUT of the page's product entirely.
				// Its `variationId` is therefore whatever THAT context carries — and
				// only that. We must NOT fall back to the page-global `variationId`
				// when the context defines a `productId` but no `variationId`, or a
				// scoped card would inherit the global selection and derive the
				// wrong variation. Fallback to global happens only when there is no
				// products context at all.
				const explicitVariationId = context
					? context.variationId
					: productsState.variationId;
				if ( explicitVariationId ) {
					return (
						productsState.productVariations[
							explicitVariationId
						] ?? null
					);
				}

				return null;
			},

			get productInContext(): ProductResponseItem | null {
				return (
					productsState.productVariationInContext ||
					productsState.mainProductInContext
				);
			},

			get resolvedProductInContext(): ProductResponseItem | null {
				// A selected variation is always a resolved, concrete product.
				const variation = productsState.productVariationInContext;
				if ( variation ) {
					return variation;
				}

				// Otherwise the main product is resolved only when it needs
				// no options picked (`has_options === false` — simple, grouped,
				// external, ...). A variable product with options still to pick
				// resolves to `null`. Reading the server-computed `has_options`
				// keeps this type-invariant: no `type === 'variable'` sniff.
				const main = productsState.mainProductInContext;
				if ( main && ! main.has_options ) {
					return main;
				}

				return null;
			},
		},
	},
	{ lock: universalLock }
);
