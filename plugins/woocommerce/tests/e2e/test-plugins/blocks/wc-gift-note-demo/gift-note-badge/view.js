/**
 * Gift Note Badge (Demo) — the `findItem({ filter })` escape-hatch demo.
 *
 * Buildless ES module. It registers ONE private UI store,
 * `wc-gift-note-demo/badge`, whose `hasMarkedLine` getter calls
 * `cartState.findItem({ id, filter })` with a LOCAL predicate. The predicate
 * REPLACES the generic narrowing and is the sole narrowing authority, so it pairs
 * a line the generic rules would exclude (this surface carries no draft note, so
 * the presence heuristic would drop every note-carrying line). No public filter
 * store and no `cartItemFilter` context reference are involved — custom matching
 * is a caller-supplied function passed directly to `findItem`.
 *
 * The predicate contract (schema): `( cartItem, { draft } ) => boolean`, pure and
 * synchronous, no mutations.
 */

/**
 * External dependencies
 */
import { store, getContext } from '@wordpress/interactivity';

const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const NOTE_NS = 'wc-gift-note-demo';
const NOTE_KEY = 'gift-note';

// Private cart store: same lock every core consumer uses.
const { state: cartState } = store(
	'woocommerce/cart',
	{},
	{ lock: universalLock }
);

/**
 * Build the local match-by-marker predicate. Matches a cart line whose gift note
 * (exposed on `extensions['wc-gift-note-demo']['gift-note']`) STARTS WITH the
 * marker. This deliberately pairs a line the generic rules would NOT.
 *
 * @param {string} marker The note-prefix marker to match.
 * @return {(cartItem: Object) => boolean} The predicate.
 */
function makeMatchByMarker( marker ) {
	return ( cartItem ) => {
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
	};
}

store( 'wc-gift-note-demo/badge', {
	state: {
		/**
		 * True when the local `findItem` predicate paired exactly one line for
		 * this surface's product.
		 *
		 * The badge's own store context (see render.php) carries the `productId`
		 * to scope the lookup and the `giftNoteMarker` the predicate reads. We
		 * call `cartState.findItem({ id, filter })` directly — the predicate is
		 * the SOLE narrowing authority, so `envelope.cart` is the line the marker
		 * selected, a line the generic rules would exclude (this surface's draft
		 * carries no note, so the presence heuristic drops every note-carrying
		 * line). `cart` is populated only when the predicate pairs exactly one
		 * line (the exactly-one rule still applies).
		 *
		 * DX FINDING (landmine): a scope context only resolves when it sits on, or
		 * inside, the interactive-island root. With a context on a bare ancestor of
		 * the island, `getContext()` silently returns nothing. See render.php and
		 * the T8 report.
		 *
		 * @return {boolean} Whether a marked line is paired.
		 */
		get hasMarkedLine() {
			const context = getContext();
			const productId =
				context && typeof context.productId === 'number'
					? context.productId
					: undefined;
			const marker =
				context && typeof context.giftNoteMarker === 'string'
					? context.giftNoteMarker
					: '';
			if ( productId === undefined ) {
				return false;
			}
			const envelope = cartState.findItem( {
				id: productId,
				filter: makeMatchByMarker( marker ),
			} );
			return Boolean( envelope.cart );
		},
	},
} );
