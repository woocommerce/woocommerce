/**
 * Gift Note Field (Demo) — frontend iAPI module.
 *
 * Buildless: a plain ES module (no JSX, no TS), loaded via `viewScriptModule`
 * and the WordPress Script Modules API. It depends on
 * `@woocommerce/stores/woocommerce/cart` (declared server-side when the module
 * is registered) so the shared cart store is registered before this runs.
 *
 * The field is a DOM descendant of the Add to Cart + Options form, which resolves
 * its product identity through the `woocommerce/products` context / global state
 * (T12 — domain-scoped contexts). That means:
 *
 * - `store('woocommerce/cart').state.itemInContext` resolves the draft for THIS
 *   product automatically (the cart store derives the product id via the products
 *   store's `mainProductInContext`; the field needn't read or track it), and
 * - `upsertDraftItem({ ... })` writes into that same context draft, keyed by the
 *   context product id (landmine #2: never key by `productInContext.id`).
 *
 * This module contains ZERO submission code — the core Add to Cart button POSTs
 * the draft, and our `wc-gift-note-demo` extension prop (a payload-root extension
 * request param) rides along.
 */

/**
 * External dependencies
 */
import { store } from '@wordpress/interactivity';

// The draft's extension-prop key is the BARE NAMESPACE; its value is exactly the
// object the extension's cart-item `extensions[ NS ]` callback echoes back
// (`{ 'gift-note': <string> }`). That single shape travels client -> server in
// the draft and is deep-compared against the cart line's `extensions[ NS ]`
// (identity convention). See the shared-stores schema's extension contract.
const NAMESPACE = 'wc-gift-note-demo';
const NOTE_KEY = 'gift-note';

// Private cart store: same lock every core consumer uses. Reading a locked
// store requires the lock; this is a demo of the intended extension surface.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const { state: cartState, actions: cartActions } = store(
	'woocommerce/cart',
	{},
	{ lock: universalLock }
);

store( NAMESPACE, {
	state: {
		/**
		 * The current note for this product's context draft, or '' when unset.
		 * Reads the shared envelope's editable draft — no product id needed here
		 * because `itemInContext` is resolved from the surrounding shared
		 * `woocommerce` context. The note lives under the bare-namespace prop as
		 * `draft[ NAMESPACE ][ NOTE_KEY ]`.
		 *
		 * @return {string} The gift note.
		 */
		get giftNote() {
			const draft = cartState.itemInContext.draft;
			const nsData = draft ? draft[ NAMESPACE ] : undefined;
			const value =
				nsData && typeof nsData[ NOTE_KEY ] === 'string'
					? nsData[ NOTE_KEY ]
					: '';
			return value;
		},
	},
	actions: {
		/**
		 * Write the input's value into the context draft under the bare-namespace
		 * extension prop, in the SAME SHAPE the cart line echoes on
		 * `extensions[ NS ]` (`{ 'gift-note': <string> }`). `upsertDraftItem`
		 * targets the draft for the context product (resolved via the products
		 * store's `mainProductInContext` — T12; creating it if missing), so the
		 * note is stored against the right product with no id bookkeeping here.
		 *
		 * iAPI passes the DOM event as the first argument to `data-wp-on--*`
		 * handlers (same pattern as the Product Filter price inputs).
		 *
		 * @param {Event} event The input event.
		 */
		setGiftNote( event ) {
			const value = event && event.target ? event.target.value : '';
			cartActions.upsertDraftItem( {
				[ NAMESPACE ]: { [ NOTE_KEY ]: value },
			} );
		},
	},
} );
