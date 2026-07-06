/**
 * External dependencies
 */
import { store, getContext } from '@wordpress/interactivity';
import type { ProductResponseItem } from '@woocommerce/types';
import type {
	SelectedAttributes,
	Store as CartStore,
	DraftItem,
} from '@woocommerce/stores/woocommerce/cart';

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
 * The shared `woocommerce` context namespace read by both shared stores. It
 * carries the surface's product identity (`productId`) — the key under which the
 * cart store's drafts live (identity rule 3: one draft per product context,
 * keyed by the main/context product id). The variation selector writes the
 * shopper's attribute selection into that draft's `variation`, which is the
 * single source of selection truth (schema: "`productVariationInContext` derives
 * from the context draft's `variation` array").
 */
type SharedWooCommerceContext = {
	productId?: number;
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

/**
 * Read the shared `woocommerce` context, or `null` when called outside a
 * directive scope. The context carries the surface's `productId` — the key the
 * cart store's drafts are stored under. Out-of-scope reads degrade silently.
 */
function getSharedContext(): SharedWooCommerceContext | null {
	try {
		return getContext< SharedWooCommerceContext >( 'woocommerce' ) ?? null;
	} catch {
		return null;
	}
}

/**
 * Lazy cross-store read into `woocommerce/cart` to find the draft for a product
 * id (identity rule 3: one draft per product context, keyed by the main/context
 * product id).
 *
 * The products store needs the cart store only to resolve the variation the
 * shopper selected — the draft's `variation` is the single source of selection
 * truth (schema: "`productVariationInContext` derives from the context draft's
 * `variation` array … a lazy cross-store `store('woocommerce/cart', …)` read,
 * core-internal, allowed, documented"). Read lazily rather than at module load
 * so the cart store's own registration order doesn't matter, and degrade to
 * `null` when the cart store isn't registered on this surface (e.g. a product
 * grid card with no purchase form) — there is then no draft-driven selection and
 * the getter falls back to the explicit `variationId` override.
 *
 * @param productId The main/context product id.
 * @return The matching draft, or `null` when unavailable.
 */
function getContextDraft( productId: number | undefined ): DraftItem | null {
	if ( productId === undefined ) {
		return null;
	}

	try {
		const { state: cartState } = store< CartStore >(
			'woocommerce/cart',
			{},
			{ lock: universalLock }
		);
		const draft = cartState.draftItems.find(
			( item ) => item.id === productId
		);
		return draft ?? null;
	} catch {
		// Cart store not registered on this surface.
		return null;
	}
}

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
type VariationAttribute = { name: string; value: string | null };

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
const variationMatchesSelection = (
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

				// Precedence (documented open point in the schema): an explicit
				// `variationId` — set in the `woocommerce/products` context or
				// global state — is an OVERRIDE and wins. It is how a surface can
				// pin a specific variation directly (e.g. a Single Product block
				// bound to one variation) without going through a draft. This
				// preserves the pre-T6 behavior for every consumer that set
				// `variationId` explicitly.
				const explicitVariationId =
					context && 'variationId' in context
						? context.variationId
						: productsState.variationId;
				if ( explicitVariationId ) {
					return (
						productsState.productVariations[
							explicitVariationId
						] ?? null
					);
				}

				// Otherwise derive from the context draft's `variation` — the
				// single source of the shopper's selection truth (T6). The draft
				// lives in `woocommerce/cart`, keyed by the shared `woocommerce`
				// context `productId`. Resolve that selection to a variation the
				// same deterministic way the server would (identity rule 6, via
				// `findProduct`).
				const sharedProductId = getSharedContext()?.productId;
				const draft = getContextDraft( sharedProductId );
				if ( ! draft?.variation?.length ) {
					return null;
				}

				const resolved = productsState.findProduct( {
					id: draft.id,
					selectedAttributes: draft.variation as SelectedAttributes[],
				} );

				// `findProduct` returns the parent when no variation matches (or
				// for a partial/ambiguous selection) — only an actual variation
				// counts here. A resolved product whose id equals the draft's
				// main product id is the parent, not a variation.
				if ( ! resolved || resolved.id === draft.id ) {
					return null;
				}

				return resolved;
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
