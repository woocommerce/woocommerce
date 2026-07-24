/**
 * `wc-bundle-demo`: a minimal fixture Interactivity API store proving a
 * bundle-style Store API extension built entirely on the public
 * `woocommerce/cart` store surface — declared draft keys, direct mutation,
 * and `addItem( payload )` — plus today's Store API extension points, with
 * no WooCommerce core changes.
 *
 * Each child slot (`slot-1`/`slot-2`, rendered by the companion
 * `bundle-demo.php`) declares its own literal, namespaced `woocommerce/cart`
 * draft key (`wc-bundle-demo/slot-1` / `wc-bundle-demo/slot-2`, see that
 * file's `data-wp-context---draft-key` markup) — the same primitive core
 * blocks use to isolate purchase UI, addressed directly from markup with no
 * registry of any kind. Picking the same product in both slots therefore
 * produces two independent drafts rather than one draft overwriting the
 * other, because each slot resolves its own collection rather than sharing
 * the page-wide one.
 *
 * A slot's quantity input has no init. Its `data-wp-on--change` resolves
 * the slot's declared key (reading its own `woocommerce/cart` context) via
 * the store's public `state.findItem( { id } )` and writes the resolved
 * draft view's `quantity` directly (`draft.quantity = value`) — the single
 * spelling for both the slot's *first* edit (the slot's collection does not
 * exist yet, nothing seeds it up front; the view materializes the draft on
 * this first write) and every edit after that (a **direct mutation** of the
 * now-live draft), never an action call either way. Each slot renders a
 * binding onto the same draft so either write's re-render is directly
 * observable. The "Add bundle to cart" button
 * composes both slots' current drafts by reading `state.draftItems`
 * directly at the two declared keys — under its existing lock consent, so
 * no cross-collection store read needs any extra plumbing — into one
 * `cart/add-item` payload for the bundle product, carrying a
 * `wc-bundle-demo/children` prop at the payload root, and posts it verbatim
 * via the store's public `addItem( payload )`. A slot never edited has no
 * collection at all, so it contributes nothing to the composed payload —
 * the safe, expected outcome for an untouched slot.
 *
 * This is a plain, unbundled ES module (no build step): `@wordpress/interactivity`
 * and `@woocommerce/stores/woocommerce/cart` are both script modules that
 * WordPress/WooCommerce already register, so a third-party extension can
 * depend on them directly. The cart store is accessed with its
 * private-store consent lock, exactly as a real extension will while the
 * store stays private.
 */

import { store, getContext, getElement } from '@wordpress/interactivity';
// This resolves at runtime via WordPress's script-module import map (see
// `bundle-demo.php`'s `register_script_module()`), not via static bundling,
// so ESLint's module resolver cannot find it on disk.
// eslint-disable-next-line import/no-unresolved
import '@woocommerce/stores/woocommerce/cart';

/**
 * The consent string gating access to the `woocommerce/cart` store while it
 * is private. Kept identical to the store's own lock string so this fixture
 * is denied nothing a real third-party extension will be denied once the
 * store is public.
 */
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

/** The namespace shared by this client store, the request prop, and the Store API schema extension. */
const NAMESPACE = 'wc-bundle-demo';

/** The `cart/add-item` payload prop carrying the bundle's child drafts. */
const CHILDREN_PROP = `${ NAMESPACE }/children`;

/**
 * The quantity input's rendered default (`bundle-demo.php`'s
 * `render_slot()` hardcodes `value="1"` for both slots) — the value
 * `state.slotQuantityText` falls back to before a slot's first edit, when
 * its collection does not exist yet.
 */
const RENDERED_DEFAULT_QUANTITY = 1;

/**
 * The two slots' own declared, literal, namespaced draft keys
 * (`bundle-demo.php`'s `render_slot()`) — an extension addressing its own
 * collections by keys it declared, with zero core changes.
 */
const SLOT_DRAFT_KEYS = [ 'wc-bundle-demo/slot-1', 'wc-bundle-demo/slot-2' ];

const cart = store( 'woocommerce/cart', {}, { lock: universalLock } );

/**
 * Resolves the current element's own declared `woocommerce/cart` draft key
 * — the slot's isolation boundary established by its own context bag
 * (`bundle-demo.php`'s `data-wp-context---draft-key`).
 *
 * @return {string|undefined} The slot's declared draft key, or `undefined`
 *                              outside a slot.
 */
function resolveSlotDraftKey() {
	return getContext( 'woocommerce/cart' )?.draftKey;
}

/**
 * Resolves the slot's one draft, read directly off the store's public
 * `state.draftItems` at the slot's own declared key (a slot's collection
 * holds at most one draft, its own child's).
 *
 * `undefined` both outside a slot and before the slot's first edit — a
 * slot's collection is created lazily, on its first write through the
 * draft view (see {@link onSlotQuantityChange}); nothing seeds it up front.
 *
 * @return {Object|undefined} The slot's draft, or `undefined` when none is
 *                             resolved yet.
 */
function resolveSlotDraft() {
	const draftKey = resolveSlotDraftKey();
	return draftKey ? cart.state.draftItems[ draftKey ]?.[ 0 ] : undefined;
}

/**
 * Writes a shopper's quantity edit to the slot's declared collection.
 *
 * Bound to the quantity input's `data-wp-on--change`. One spelling covers
 * both the *first* edit for this slot (its collection does not exist yet —
 * nothing seeds it up front — so the draft view materializes it from the
 * slot's own child context) and every edit after that (a **direct
 * mutation** of the now-live draft): `cart.state.findItem( { id } ).draft`
 * resolves the draft view for the slot's own declared key (read off this
 * element's `woocommerce/cart` context) and `childId`, and the assignment
 * writes through it — never an action call either way. The slot's `<span>`
 * binding (`state.slotQuantityText`) reads the same draft, so either
 * write's re-render is observable.
 */
function onSlotQuantityChange() {
	const { ref } = getElement();
	const quantity = Number( ref.value );

	if ( ! Number.isFinite( quantity ) || quantity < 0 ) {
		return;
	}

	const { childId } = getContext();
	cart.state.findItem( { id: childId } ).draft.quantity = quantity;
}

store(
	NAMESPACE,
	{
		state: {
			/**
			 * The current element's slot's draft quantity, as text.
			 *
			 * A getter, re-evaluated on every render; reads the same
			 * draft view {@link onSlotQuantityChange} writes through —
			 * whether that write materializes the draft or mutates the
			 * already-live one — so either write's re-render is observable
			 * through this binding with no action call involved. Falls
			 * back to the quantity input's rendered default when the slot
			 * has no draft yet (its collection is created lazily, on its
			 * first edit).
			 */
			get slotQuantityText() {
				return String(
					resolveSlotDraft()?.quantity ?? RENDERED_DEFAULT_QUANTITY
				);
			},
		},
		actions: {
			onSlotQuantityChange,

			/**
			 * Composes both slots' current drafts into one `add-item`
			 * payload for the bundle product and posts it verbatim.
			 *
			 * Reads each slot's collection directly off the store's public
			 * `state.draftItems`, at the two literal keys the slots
			 * themselves declare — no registry, no cross-collection
			 * plumbing beyond the lock consent this fixture already holds.
			 * A slot never edited has no collection yet (`?? []`), so it
			 * contributes nothing to the composed payload.
			 */
			*addBundleToCart() {
				const { bundleProductId } = getContext();

				const children = SLOT_DRAFT_KEYS.flatMap(
					( key ) => cart.state.draftItems[ key ] ?? []
				).filter( ( draft ) => draft.quantity > 0 );

				yield cart.actions.addItem( {
					id: bundleProductId,
					quantity: 1,
					[ CHILDREN_PROP ]: children,
				} );
			},
		},
	},
	{ lock: universalLock }
);
