/**
 * `wc-bundle-demo`: a minimal fixture Interactivity API store proving a
 * bundle-style Store API extension built entirely on the public
 * `woocommerce/cart` store surface — draft items and `addItem( payload )` —
 * plus today's Store API extension points, with no WooCommerce core
 * changes.
 *
 * Each child slot (`slot-1`/`slot-2`, rendered by the companion
 * `bundle-demo.php`) establishes its own sub-scope
 * (`wc-bundle-demo/slot-1`, `wc-bundle-demo/slot-2`) via the shared
 * `woocommerce` context namespace (see that file's `data-wp-context---scope`
 * markup). Picking the same product in both slots therefore produces two
 * independent drafts rather than one draft overwriting the other, because
 * `upsertDraftItem` keys its "one draft per product per scope" invariant by
 * scope. Each slot's quantity input upserts its own draft; the "Add bundle
 * to cart" button composes both slots' current drafts into one
 * `cart/add-item` payload for the bundle product — carrying a
 * `wc-bundle-demo/children` prop at the payload root — and posts it
 * verbatim via the store's public `addItem( payload )`.
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

/** The two child slots' sub-scopes, matching the PHP-emitted `data-wp-context---scope` values. */
const SLOT_SCOPES = [ `${ NAMESPACE }/slot-1`, `${ NAMESPACE }/slot-2` ];

const cart = store( 'woocommerce/cart', {}, { lock: universalLock } );

/**
 * Upserts the current element's slot draft from its own `childId` context
 * and the quantity input's current value.
 *
 * Shared by the slot's `data-wp-init` (seeding the draft with the input's
 * default quantity so a shopper who never touches the input still adds the
 * default amount) and its `data-wp-on--change` (mirroring later edits).
 * `upsertDraftItem` resolves its target scope from `currentScope`, which the
 * slot's `data-wp-context---scope` override makes the slot's own sub-scope,
 * not the page scope.
 */
function upsertSlotDraft() {
	const { ref } = getElement();
	const quantity = Number( ref.value );

	if ( ! Number.isFinite( quantity ) || quantity < 0 ) {
		return;
	}

	const { childId } = getContext();
	cart.actions.upsertDraftItem( { id: childId, quantity } );
}

store(
	NAMESPACE,
	{
		actions: {
			onSlotQuantityChange: upsertSlotDraft,

			/**
			 * Composes both slots' current drafts into one `add-item`
			 * payload for the bundle product and posts it verbatim.
			 *
			 * Reads each slot's draft directly off `draftItems` — the
			 * public state each slot's `upsertDraftItem` call wrote into —
			 * rather than resolving product/cart identity, since a slot's
			 * sub-scope holds at most one draft by construction.
			 */
			*addBundleToCart() {
				const { bundleProductId } = getContext();

				const children = SLOT_SCOPES.map(
					( scope ) => cart.state.draftItems[ scope ]?.[ 0 ]
				).filter( ( draft ) => draft && draft.quantity > 0 );

				yield cart.actions.addItem( {
					id: bundleProductId,
					quantity: 1,
					[ CHILDREN_PROP ]: children,
				} );
			},
		},
		callbacks: {
			initSlotDraft: upsertSlotDraft,
		},
	},
	{ lock: universalLock }
);
