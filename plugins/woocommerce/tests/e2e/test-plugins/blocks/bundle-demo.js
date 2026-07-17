/**
 * `wc-bundle-demo`: a minimal fixture Interactivity API store proving a
 * bundle-style Store API extension built entirely on the public
 * `woocommerce/cart` store surface — draft collections, direct mutation, and
 * `addItem( payload )` — plus today's Store API extension points, with no
 * WooCommerce core changes.
 *
 * Each child slot (`slot-1`/`slot-2`, rendered by the companion
 * `bundle-demo.php`) is a real container: it declares its own empty
 * `woocommerce/cart` draft-items collection (see that file's
 * `data-wp-context---draft-items` markup), the same primitive core blocks
 * use to isolate purchase UI. Picking the same product in both slots
 * therefore produces two independent drafts rather than one draft
 * overwriting the other, because each slot resolves its own collection
 * rather than sharing the page-wide one.
 *
 * A slot-level `data-wp-init` resolves the slot's collection (reading its
 * own `woocommerce/cart` context), seeds its one draft via the store's
 * public `upsertDraftItem` (a creation convenience — this is the only place
 * this fixture calls it), and registers the collection's live reference in
 * this module's own registry, keyed by slot id. Every later edit of a
 * slot's quantity is a **direct mutation** of that resolved draft object
 * (`draft.quantity = value`), never an action call; each slot renders a
 * binding onto the same draft so a direct write's re-render is directly
 * observable. The "Add bundle to cart" button composes both slots' current
 * drafts from this module's registry — reading the registry's live
 * collections at click time, so any direct write is honored — into one
 * `cart/add-item` payload for the bundle product, carrying a
 * `wc-bundle-demo/children` prop at the payload root, and posts it verbatim
 * via the store's public `addItem( payload )`.
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

const cart = store( 'woocommerce/cart', {}, { lock: universalLock } );

/**
 * Module-scope registry of each slot's resolved draft-items collection,
 * keyed by slot id (`slot-1`/`slot-2`, from the `wc-bundle-demo` context).
 *
 * Holds each slot's live collection reference — populated once by
 * {@link registerSlotCollection} — so the "Add bundle to cart" button
 * (outside any slot's own `woocommerce/cart` context) can compose both
 * slots' current drafts without a cross-collection store read. Because the
 * stored reference is the same live array a slot's direct writes mutate,
 * reading it here at compose time always reflects the latest edit.
 */
const slotCollections = new Map();

/**
 * Resolves the current element's own `woocommerce/cart` collection — the
 * slot's isolated draft-items array established by its container context
 * bag (`bundle-demo.php`'s `data-wp-context---draft-items`).
 *
 * @return {Object[]|undefined} The slot's resolved collection, or
 *                               `undefined` outside a slot.
 */
function resolveSlotCollection() {
	return getContext( 'woocommerce/cart' )?.draftItems;
}

/**
 * Resolves the slot's one draft (a slot's collection holds at most one, its
 * own child's).
 *
 * @return {Object|undefined} The slot's draft, or `undefined` when none is
 *                             resolved yet.
 */
function resolveSlotDraft() {
	return resolveSlotCollection()?.[ 0 ];
}

/**
 * Registers the slot's resolved collection into the module-scope registry
 * and seeds its one draft from the quantity input's current value.
 *
 * Bound to the quantity input's `data-wp-init`. A slot's collection starts
 * empty (its own container bag), so this seed is always a creation, never
 * an edit — the sole `upsertDraftItem` call in this fixture. Every
 * subsequent shopper edit is a direct mutation instead (see
 * {@link onSlotQuantityChange}).
 */
function registerSlotCollection() {
	const { slotId, childId } = getContext();
	const collection = resolveSlotCollection();

	if ( ! collection ) {
		return;
	}

	slotCollections.set( slotId, collection );

	const { ref } = getElement();
	const quantity = Number( ref.value );

	if ( ! Number.isFinite( quantity ) || quantity < 0 ) {
		return;
	}

	cart.actions.upsertDraftItem( { id: childId, quantity } );
}

/**
 * Writes a shopper's quantity edit as a direct mutation of the slot's
 * already-resolved draft — no action call.
 *
 * Bound to the quantity input's `data-wp-on--change`. The slot's `<span>`
 * binding (`state.slotQuantityText`) reads the same draft, so this write's
 * re-render is observable without any action having run.
 */
function onSlotQuantityChange() {
	const { ref } = getElement();
	const quantity = Number( ref.value );

	if ( ! Number.isFinite( quantity ) || quantity < 0 ) {
		return;
	}

	const draft = resolveSlotDraft();

	if ( ! draft ) {
		return;
	}

	// Direct mutation of the resolved draft object — reactive per the
	// store's live envelope, honored by `addBundleToCart`'s compose below,
	// deliberately not routed through `upsertDraftItem`.
	draft.quantity = quantity;
}

store(
	NAMESPACE,
	{
		state: {
			/**
			 * The current element's slot's draft quantity, as text.
			 *
			 * A getter, re-evaluated on every render; reads the same
			 * resolved draft {@link onSlotQuantityChange} mutates directly,
			 * so a direct write's re-render is observable through this
			 * binding with no action call involved.
			 */
			get slotQuantityText() {
				return String( resolveSlotDraft()?.quantity ?? 0 );
			},
		},
		actions: {
			onSlotQuantityChange,

			/**
			 * Composes both slots' current drafts into one `add-item`
			 * payload for the bundle product and posts it verbatim.
			 *
			 * Reads each slot's draft off this module's own registry —
			 * populated by {@link registerSlotCollection} — rather than any
			 * cross-collection store read; because the registry holds live
			 * collection references, this reflects direct writes made after
			 * registration.
			 */
			*addBundleToCart() {
				const { bundleProductId } = getContext();

				const children = [ ...slotCollections.values() ]
					.map( ( collection ) => collection[ 0 ] )
					.filter( ( draft ) => draft && draft.quantity > 0 );

				yield cart.actions.addItem( {
					id: bundleProductId,
					quantity: 1,
					[ CHILDREN_PROP ]: children,
				} );
			},
		},
		callbacks: {
			registerSlotCollection,
		},
	},
	{ lock: universalLock }
);
