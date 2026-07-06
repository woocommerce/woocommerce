/**
 * Gift Note Field (Demo) — frontend iAPI module.
 *
 * Buildless: a plain ES module (no JSX, no TS), loaded via `viewScriptModule`
 * and the WordPress Script Modules API. It depends on
 * `@woocommerce/stores/woocommerce/cart` (declared server-side when the module
 * is registered) so the shared cart store is registered before this runs.
 *
 * The field is a DOM descendant of the Add to Cart + Options form, which is
 * wrapped in `data-wp-context='woocommerce::{"productId":N}'`. That means:
 *
 * - `store('woocommerce/cart').state.itemInContext` resolves the draft for THIS
 *   product automatically (no need to read/track the product id ourselves), and
 * - `upsertDraftItem({ ... })` writes into that same context draft, keyed by the
 *   shared-context product id (landmine #2: never key by `productInContext.id`).
 *
 * This module contains ZERO submission code — the core Add to Cart button POSTs
 * the draft, and our namespaced `wc-gift-note-demo/gift-note` prop (a payload-
 * root extension request param) rides along.
 */

/**
 * External dependencies
 */
import { store } from '@wordpress/interactivity';

// The namespaced payload-root key that travels client -> server in the draft
// and is echoed back on the cart line's `extensions` (identity convention).
const NAMESPACE = 'wc-gift-note-demo';
const NOTE_KEY = 'wc-gift-note-demo/gift-note';

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
		 * `woocommerce` context.
		 *
		 * @return {string} The gift note.
		 */
		get giftNote() {
			const draft = cartState.itemInContext.draft;
			const value = draft ? draft[ NOTE_KEY ] : '';
			return typeof value === 'string' ? value : '';
		},
	},
	actions: {
		/**
		 * Write the input's value into the context draft under the namespaced
		 * payload-root key. `upsertDraftItem` targets the draft for the shared
		 * `woocommerce::productId` context (creating it if missing), so the note
		 * is stored against the right product with no id bookkeeping here.
		 *
		 * iAPI passes the DOM event as the first argument to `data-wp-on--*`
		 * handlers (same pattern as the Product Filter price inputs).
		 *
		 * @param {Event} event The input event.
		 */
		setGiftNote( event ) {
			const value =
				event && event.target ? event.target.value : '';
			cartActions.upsertDraftItem( { [ NOTE_KEY ]: value } );
		},
	},
} );
