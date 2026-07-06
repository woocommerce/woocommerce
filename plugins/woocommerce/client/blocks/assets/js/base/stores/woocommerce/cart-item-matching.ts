/**
 * External dependencies
 */
import type { CartItem, CartVariationItem } from '@woocommerce/types';

/**
 * Cart-item matching helpers — the pure core of the envelope resolution ladder.
 *
 * These functions know nothing about the store, the network, or the
 * Interactivity API. They implement the identity/matching law from
 * `docs/internal-developers/blocks/shared-stores-schema.md`
 * ("Line identity rules" + "Envelope resolution ladder"), so they can be unit
 * tested in isolation and reused by the `woocommerce/cart` store.
 *
 * The four data shapes the glossary warns never to confuse:
 *
 * - **Extension request params** — what a draft holds at its payload root
 *   (namespaced, machine-comparable). The client → server direction.
 * - **`cart_item_data`** — server-internal, never leaves the server.
 * - **`item_data`** — server → client, display-only, NEVER machine-compared.
 * - **`extensions[ns]`** — server → client, the machine-readable projection an
 *   extension exposes for matching (identity convention).
 *
 * Matching therefore compares a draft's namespaced payload-root props against a
 * line's `extensions[ns]` — and only uses `item_data` for the presence
 * heuristic (rule 4: "`item_data` is never machine-compared").
 */

/**
 * A draft is literally a `cart/add-item` payload — no bookkeeping fields. `id`
 * is always the main/context product id and doubles as the draft's identity
 * (identity rule 3). The shopper's variation choice lives in `variation`;
 * extension request params live at the payload root under their namespace.
 */
export type DraftItem = {
	/** Main/context product id — also the draft's identity. */
	id: number;
	/** Target quantity (absolute). Optional; `addItem` falls back to 1. */
	quantity?: number;
	/** The shopper's attribute selection (Store API `add-item` shape). */
	variation?:
		| CartVariationItem[]
		| Array< { attribute: string; value: string } >;
	/**
	 * Extension request params at the payload root, keyed by namespace (e.g.
	 * `"my-plugin/gift-note"`). Legacy non-namespaced names are tolerated. Every
	 * such prop is assumed identity-affecting (rule 2).
	 */
	[ key: string ]: unknown;
};

/**
 * Payload-root keys that are part of the add-item envelope itself, NOT
 * namespaced extension request params. Everything on a draft that is not one of
 * these is treated as an extension prop and matched against `extensions[ns]`.
 *
 * `key` and `type` are tolerated here because callers occasionally reuse a cart
 * line as a payload seed; they never participate in extension matching.
 */
const RESERVED_DRAFT_KEYS = new Set( [
	'id',
	'quantity',
	'variation',
	'key',
	'type',
] );

/**
 * Extract the namespaced extension request params from a draft — every own,
 * enumerable payload-root prop that is not a reserved envelope key.
 *
 * @param draft The draft to read.
 * @return A map of namespace → value for the draft's extension props.
 */
export function getDraftExtensionProps(
	draft: Record< string, unknown >
): Record< string, unknown > {
	const props: Record< string, unknown > = {};
	for ( const key of Object.keys( draft ) ) {
		if ( ! RESERVED_DRAFT_KEYS.has( key ) ) {
			props[ key ] = draft[ key ];
		}
	}
	return props;
}

/**
 * Deep structural equality for JSON-shaped values (the machine-comparable
 * projection: primitives, arrays, plain objects). Key order is irrelevant;
 * arrays are order-sensitive.
 *
 * Chosen semantics (documented per the task's freedom area): two values are
 * equal when they serialize to the same normalized structure — recursively
 * equal primitives, same-length arrays with equal elements in order, and plain
 * objects with the same set of keys and equal values. This deliberately does
 * NOT treat `[]` and `{}` as equal to each other; emptiness normalization for
 * the "absent/empty" case is handled separately by `isEmptyValue`.
 *
 * @param a First value.
 * @param b Second value.
 * @return True when the two values are deeply equal.
 */
export function deepEqual( a: unknown, b: unknown ): boolean {
	if ( a === b ) {
		return true;
	}

	if (
		typeof a !== 'object' ||
		typeof b !== 'object' ||
		a === null ||
		b === null
	) {
		return false;
	}

	const aIsArray = Array.isArray( a );
	const bIsArray = Array.isArray( b );
	if ( aIsArray !== bIsArray ) {
		return false;
	}

	if ( aIsArray && bIsArray ) {
		if ( a.length !== b.length ) {
			return false;
		}
		return a.every( ( item, index ) => deepEqual( item, b[ index ] ) );
	}

	const aObj = a as Record< string, unknown >;
	const bObj = b as Record< string, unknown >;
	const aKeys = Object.keys( aObj );
	const bKeys = Object.keys( bObj );
	if ( aKeys.length !== bKeys.length ) {
		return false;
	}
	return aKeys.every(
		( key ) =>
			Object.prototype.hasOwnProperty.call( bObj, key ) &&
			deepEqual( aObj[ key ], bObj[ key ] )
	);
}

/**
 * Whether a value counts as "empty" for absent/empty normalization: `undefined`,
 * `null`, `''`, `[]`, or `{}`. A draft prop the extension set to an empty value
 * and a line that carries nothing under that namespace must compare equal
 * (schema: "absent/empty normalized").
 *
 * @param value The value to test.
 * @return True when the value is considered empty.
 */
export function isEmptyValue( value: unknown ): boolean {
	if ( value === undefined || value === null || value === '' ) {
		return true;
	}
	if ( Array.isArray( value ) ) {
		return value.length === 0;
	}
	if ( typeof value === 'object' ) {
		return Object.keys( value as Record< string, unknown > ).length === 0;
	}
	return false;
}

/**
 * Per-namespace deep-compare of a draft's namespaced props against a line's
 * `extensions[ns]`, with absent/empty normalization (ladder step 2, generic
 * narrowing — the non-`cartItemFilter` path).
 *
 * For every extension prop the draft carries, the line's matching
 * `extensions[ns]` must be deeply equal (both sides normalized so absent/empty
 * are interchangeable). A draft prop with an empty value places no constraint.
 *
 * This is a one-directional check (draft → line): props the LINE carries that
 * the draft does not are handled by the presence heuristic, not here.
 *
 * @param draftProps A draft's extracted extension props (see
 *                   `getDraftExtensionProps`).
 * @param line       The cart line to test.
 * @return True when every non-empty draft prop matches the line's extensions.
 */
export function draftPropsMatchLineExtensions(
	draftProps: Record< string, unknown >,
	line: CartItem
): boolean {
	const extensions = ( line.extensions ?? {} ) as Record< string, unknown >;

	return Object.keys( draftProps ).every( ( ns ) => {
		const draftValue = draftProps[ ns ];
		const lineValue = extensions[ ns ];

		// Absent/empty on both sides → no constraint.
		if ( isEmptyValue( draftValue ) && isEmptyValue( lineValue ) ) {
			return true;
		}

		return deepEqual( draftValue, lineValue );
	} );
}

/**
 * Presence heuristic (identity rule 4: "unaccounted line data = no exact
 * pairing").
 *
 * A line carrying visible machine-readable content the draft does not account
 * for must be excluded from exact pairing. There are two ways a line can carry
 * unaccounted content:
 *
 * 1. **Unaccounted extension namespace** — the line exposes a non-empty
 *    `extensions[ns]` under a namespace the draft has NO (non-empty) prop for.
 *    The draft cannot vouch for that identity-affecting data. This ALWAYS
 *    excludes.
 *
 * 2. **Unaccounted display data** — the line has visible (non-hidden)
 *    `item_data`. `item_data` is display-only and NEVER machine-compared (rule
 *    4), so we cannot map it to a namespace. It signals unaccounted content
 *    ONLY when the draft does not already positively account for the line's
 *    identity via `extensions`. When the draft's props deep-match every
 *    non-empty `extensions[ns]` on the line, that `item_data` is just the
 *    display projection of data the draft already matched, so it must NOT
 *    exclude the line — otherwise a gift-note draft could never pair with the
 *    line whose note it exactly matches (its note also renders as `item_data`).
 *
 * This split is what lets:
 * - two note-lines + a draft with the matching note → pair the right line, but
 * - the same two note-lines + a draft with no note props → both excluded →
 *   zero survivors → `cart` undefined AND `isInCart` false: THIS (plain)
 *   configuration is not in the cart, only decorated lines the draft cannot
 *   account for are (the #65869-safe outcome — the surface falls back to a
 *   plain add button).
 *
 * Hidden `item_data` entries (`hidden: true`, from the
 * `__experimental_woocommerce_blocks_hidden` convention) are internal and never
 * count as visible.
 *
 * @param draftProps A draft's extracted extension props.
 * @param line       The cart line to test.
 * @return True when the line has visible content the draft does not account for.
 */
export function lineHasUnaccountedContent(
	draftProps: Record< string, unknown >,
	line: CartItem
): boolean {
	const extensions = ( line.extensions ?? {} ) as Record< string, unknown >;

	const lineNamespacesWithContent = Object.keys( extensions ).filter(
		( ns ) => ! isEmptyValue( extensions[ ns ] )
	);

	// (1) A non-empty line extension the draft has no non-empty prop for.
	const hasUnaccountedExtension = lineNamespacesWithContent.some( ( ns ) =>
		isEmptyValue( draftProps[ ns ] )
	);
	if ( hasUnaccountedExtension ) {
		return true;
	}

	// (2) Visible item_data only counts as unaccounted when the draft does not
	// positively account for the line's identity via extensions. If the line
	// exposes machine-readable extension content AND the draft matched all of
	// it (checked above → no unaccounted extension), the item_data is the
	// display of accounted-for data and must not exclude. Only when the line
	// carries NO extension content the draft positively matched (e.g. a
	// meta-split line that never exposed its identity via `extensions`, or a
	// draft with no props at all) does visible item_data signal ambiguity.
	const draftAccountsForLineExtensions = lineNamespacesWithContent.length > 0;
	if ( draftAccountsForLineExtensions ) {
		return false;
	}

	const itemData = line.item_data ?? [];
	return itemData.some(
		( entry ) => ! ( entry as { hidden?: boolean } ).hidden
	);
}

/**
 * Whether a draft can pair exactly with a candidate line under generic
 * narrowing: the draft's props must match the line's extensions AND the line
 * must carry no unaccounted visible content.
 *
 * @param draftProps A draft's extracted extension props.
 * @param line       A candidate cart line (already id + variation matched).
 * @return True when the draft and line are an exact generic-narrowing pair.
 */
export function isGenericExactPair(
	draftProps: Record< string, unknown >,
	line: CartItem
): boolean {
	return (
		draftPropsMatchLineExtensions( draftProps, line ) &&
		! lineHasUnaccountedContent( draftProps, line )
	);
}

/**
 * Narrow candidate lines to the survivors of a narrowing predicate (ladder
 * step 2's output). The envelope derives BOTH of its cart-side values from
 * this single survivor set:
 *
 * - `cart` — via `resolveExactlyOne( survivors )`, and
 * - `isInCart` — `survivors.length > 0` ("THIS configuration is in the cart").
 *   Pre-narrowing candidates deliberately do NOT count: a product present only
 *   as lines the draft cannot account for (e.g. a decorated bundle child, or
 *   note-split lines with no matching draft props) is NOT this configuration,
 *   so `isInCart` is `false` and the surface falls back to plain add-button UI
 *   (the #65869-aligned behavior).
 *
 * @param candidates Lines already matched by purchasable id + variation.
 * @param predicate  The narrowing predicate (generic pair, or a context
 *                   filter).
 * @return The surviving lines.
 */
export function narrowCandidates< T >(
	candidates: T[],
	predicate: ( line: T ) => boolean
): T[] {
	return candidates.filter( predicate );
}

/**
 * Reduce narrowed survivors to exactly one line, or `undefined`.
 *
 * Implements the exactly-one rule (ladder step 3): zero or several survivors →
 * `undefined`. NEVER falls back to "first match" — an inferred pairing would
 * feed `updateItem({ key })` and become a wrong-line mutation (the #65869 bug
 * class).
 *
 * @param survivors Lines that survived narrowing (see `narrowCandidates`).
 * @return The sole surviving line, or `undefined` when zero or many survive.
 */
export function resolveExactlyOne< T >( survivors: T[] ): T | undefined {
	return survivors.length === 1 ? survivors[ 0 ] : undefined;
}
