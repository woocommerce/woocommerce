/**
 * External dependencies
 */
import { store } from '@wordpress/interactivity';
import type {
	Store as WooCommerce,
	DraftItem,
	SelectedAttributes,
} from '@woocommerce/stores/woocommerce/cart';
import type { ProductsStore } from '@woocommerce/stores/woocommerce/products';

/**
 * Cart-draft helpers for the Add to Cart + Options block family (T6).
 *
 * Shopper input for this form — quantity and the selected variation attributes —
 * lives in the shared `woocommerce/cart` store as a "draft" (a pure
 * `cart/add-item` payload), one per product context, keyed by the main/context
 * product id (identity rule 3). The draft is the submission/pairing truth the
 * cart POST (`addItem()`) reads. Shopper input is written through the cart
 * store's draft actions (`upsertDraftItem`) — the write-policy contract.
 *
 * The draft's product id is the `woocommerce/products` context `productId` the
 * form (and each grouped child row) renders (T12 — domain-scoped contexts). These
 * helpers centralize the lazy cross-store access so the family's frontend modules
 * don't each re-implement it.
 *
 * The `woocommerce/add-to-cart-with-options` context keeps its own
 * `selectedAttributes` / `quantity` fields as the compatibility surface that
 * out-of-scope consumers (Product Button, Add to Wishlist Button) still read;
 * the selector actions mirror shopper input into the draft alongside those
 * fields. Those consumers migrate to the draft in their own tasks (T7).
 */

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

/**
 * Lazily access the `woocommerce/cart` store. Read lazily (not at module load)
 * so the cart store's registration order doesn't matter.
 */
const getCartStore = (): WooCommerce =>
	store< WooCommerce >( 'woocommerce/cart', {}, { lock: universalLock } );

/**
 * The product id identifying the current context draft: the top-level product
 * for this surface, resolved through the products store's `mainProductInContext`
 * derived state (T12 — domain-scoped contexts). That getter reads the products
 * store's OWN context/state:
 *
 * - a per-element `woocommerce/products::{ productId }` context (grouped child
 *   rows, SingleProduct-in-loop cards), else
 * - the products store's global `state.productId` (the single product page seeds
 *   it via `SingleProductTemplate`).
 *
 * Reading `mainProductInContext.id` (rather than a raw products-context read)
 * lets the id come from EITHER source, matching the cart store's own resolution
 * and preserving the single-product-page flow where no per-form products context
 * exists (so the variation selector's `variationId` write stays global for the
 * Product Gallery). Degrades to `undefined` when the products store isn't
 * registered or resolves no product.
 *
 * @return The context product id, or `undefined` when out of scope.
 */
export function getContextProductId(): number | undefined {
	try {
		const { state } = store< ProductsStore >(
			'woocommerce/products',
			{},
			{ lock: universalLock }
		);
		return state.mainProductInContext?.id;
	} catch {
		return undefined;
	}
}

/**
 * Find the draft for a product id (defaults to the shared context product id).
 * Returns the live, editable draft object or `undefined` when none exists yet.
 *
 * This family uses the default draft keying (`String(productId)`) — it never
 * declares a `draftKey` — so the draft lives under the product id's string key.
 *
 * @param productId Optional explicit product id; defaults to the context id.
 * @return The matching draft, or `undefined`.
 */
export function getDraft( productId?: number ): DraftItem | undefined {
	const id = productId ?? getContextProductId();
	if ( id === undefined ) {
		return undefined;
	}
	const { state } = getCartStore();
	return state.draftItems[ String( id ) ];
}

/**
 * The draft's quantity, or `0` when there is no draft yet. Quantity-selector
 * getters bind to this.
 *
 * @param productId Optional explicit product id; defaults to the context id.
 * @return The draft quantity, or `0`.
 */
export function getDraftQuantity( productId?: number ): number {
	return getDraft( productId )?.quantity ?? 0;
}

/**
 * Set a draft's quantity through the cart store's `upsertDraftItem` action — the
 * write-policy write path (creates the draft on first touch, merges thereafter).
 *
 * When the target equals the draft's current quantity, we write `NaN` first and
 * then the real value. Assigning an unchanged value to a reactive signal fires no
 * update, but the input's displayed value can still be out of sync (e.g. the
 * shopper typed letters — the numeric value clamps back to the same number, yet
 * the input must visibly reset to it). The throwaway `NaN` write guarantees a
 * signal change so the bound `state.inputQuantity` re-renders. This preserves the
 * behavior the old draft-less path implemented directly on `context.quantity`.
 *
 * @param productId The draft's product id.
 * @param quantity  The absolute target quantity.
 */
export function setDraftQuantity( productId: number, quantity: number ): void {
	const { actions } = getCartStore();
	const current = getDraftQuantity( productId );
	if ( current === quantity ) {
		actions.upsertDraftItem( { id: productId, quantity: NaN } );
	}
	actions.upsertDraftItem( { id: productId, quantity } );
}

/**
 * Mirror the shopper's attribute selection into the context draft's `variation`
 * (the single source of selection truth `productVariationInContext` reads).
 *
 * @param productId The draft's product id (the main/parent product).
 * @param variation The shopper's attribute selection (`add-item` shape).
 */
export function setDraftVariation(
	productId: number,
	variation: SelectedAttributes[]
): void {
	const { actions } = getCartStore();
	actions.upsertDraftItem( { id: productId, variation } );
}
