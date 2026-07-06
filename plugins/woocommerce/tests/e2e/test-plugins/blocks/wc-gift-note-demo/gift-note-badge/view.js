/**
 * Gift Note Badge (Demo) — the cartItemFilter escape-hatch demo.
 *
 * Buildless ES module. It registers TWO stores:
 *
 * 1. `wc-gift-note-demo/filter` — a PUBLIC (unlocked) store exposing the
 *    `matchByMarker` predicate. It MUST be public: core resolves a
 *    `cartItemFilter` reference with a plain `store( namespace )` read, which
 *    only works on an unlocked store (a locked store throws and core degrades to
 *    generic narrowing). This is the documented requirement in the schema.
 *
 * 2. `wc-gift-note-demo/badge` — a private UI store whose `hasMarkedLine` getter
 *    reads `itemInContext.cart`. Because the badge's markup sits inside a
 *    `woocommerce::{ cartItemFilter }` context, that envelope is derived with the
 *    predicate as the SOLE narrowing authority — so `cart` is the line the
 *    filter selected, not what the generic rules would pick.
 *
 * The predicate contract (schema): `( cartItem, { draft, context } ) => boolean`,
 * pure and synchronous, no mutations.
 */

/**
 * External dependencies
 */
import { store } from '@wordpress/interactivity';

const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const NOTE_NS = 'wc-gift-note-demo';
const NOTE_KEY = 'gift-note';

// PUBLIC store — no lock passed to store(), so it stays unlocked and core can
// resolve the `cartItemFilter` reference to this predicate.
store( 'wc-gift-note-demo/filter', {
	actions: {
		/**
		 * cartItemFilter predicate: match a cart line whose gift note (exposed on
		 * `extensions['wc-gift-note-demo']['gift-note']`) STARTS WITH the marker
		 * carried in the shared context. This deliberately pairs a line the
		 * generic rules would NOT: the badge surface has no draft note, so
		 * generic narrowing's presence heuristic excludes every note-carrying
		 * line; the filter instead selects lines by marker prefix.
		 *
		 * @param {Object} cartItem The candidate cart line.
		 * @param {Object} extra    Derivation extras.
		 * @param {Object} extra.context The shared `woocommerce` context.
		 * @return {boolean} Whether the line matches the marker.
		 */
		matchByMarker( cartItem, { context } ) {
			const marker =
				context && typeof context.giftNoteMarker === 'string'
					? context.giftNoteMarker
					: '';
			if ( ! marker ) {
				return false;
			}
			const extensions = cartItem && cartItem.extensions;
			const nsData = extensions ? extensions[ NOTE_NS ] : undefined;
			const note =
				nsData && typeof nsData[ NOTE_KEY ] === 'string'
					? nsData[ NOTE_KEY ]
					: '';
			return note.indexOf( marker ) === 0;
		},
	},
} );

// Private UI store: reads the (filter-narrowed) envelope from the cart store.
const { state: cartState } = store(
	'woocommerce/cart',
	{},
	{ lock: universalLock }
);

store( 'wc-gift-note-demo/badge', {
	state: {
		/**
		 * True when the `cartItemFilter` paired exactly one line for this
		 * surface's context.
		 *
		 * This is the PURE context-reference path: the badge renders a
		 * `woocommerce::{ ..., cartItemFilter: { namespace, action } }` context
		 * (see render.php), and core resolves that serialized reference to our
		 * public `wc-gift-note-demo/filter` store's `matchByMarker` predicate at
		 * envelope-derivation time. So `cartState.itemInContext.cart` is the line
		 * the FILTER selected — a line the generic rules would exclude (this
		 * surface's draft carries no note, so the presence heuristic drops every
		 * note-carrying line). `cart` is populated only when the filter pairs
		 * exactly one line (the exactly-one rule still applies).
		 *
		 * DX FINDING (landmine): `itemInContext` only resolves the context when
		 * `data-wp-interactive` and the `woocommerce::` context sit on the SAME
		 * element (the interactive-island root). With the context on a bare
		 * ancestor of the island, `getContext('woocommerce')` — and therefore the
		 * whole envelope, filter included — silently returns nothing. See
		 * render.php and the T8 report.
		 *
		 * @return {boolean} Whether a marked line is paired.
		 */
		get hasMarkedLine() {
			return Boolean( cartState.itemInContext.cart );
		},
	},
} );
