/**
 * External dependencies
 */
import type { CartItem } from '@woocommerce/types';
import type { SelectedAttributes } from '@woocommerce/stores/woocommerce/products';

/**
 * Cart-item matching helpers — the pure core of the envelope resolution ladder.
 *
 * These functions know nothing about the store, the network, or the
 * Interactivity API, so they can be unit tested in isolation and reused by the
 * `woocommerce/cart` store.
 *
 * Matching compares a draft's namespaced payload-root props against a line's
 * `extensions[ns]` (the machine-readable projection an extension exposes for
 * matching). Display-only `item_data` is NEVER machine-compared; it only feeds
 * the presence heuristic.
 */

/**
 * A draft is literally a `cart/add-item` payload — no bookkeeping fields. `id`
 * is the product id and doubles as the draft's identity. The shopper's variation
 * choice lives in `variation`; extension request params live at the payload root
 * under their namespace.
 */
export type DraftItem = {
	/** Product id — also the draft's identity. */
	id: number;
	/** Target quantity (absolute). Optional; `addItem` falls back to a min. */
	quantity?: number;
	/**
	 * The shopper's attribute selection, in the canonical `SelectedAttributes`
	 * shape (`{ attribute, value }[]`, the products store's one variation type).
	 * The label-vs-slug form the form writes is absorbed at resolution time by
	 * `findProduct` — see the schema's variation-payload note.
	 */
	variation?: SelectedAttributes[];
	/**
	 * Extension request params at the payload root, keyed by namespace (e.g.
	 * `"my-plugin/gift-note"`). Legacy non-namespaced names are tolerated. Every
	 * such prop is assumed identity-affecting: over-splitting is free (the server
	 * merges on POST); under-splitting destroys shopper input.
	 */
	[ key: string ]: unknown;
};

/**
 * The optional `findItem({ filter })` predicate contract. Pure and synchronous —
 * it receives a candidate cart line and the active draft and returns whether the
 * line is a match. It MUST NOT mutate its arguments or perform side effects; it
 * runs during derived-state evaluation, potentially many times per render.
 *
 * When passed to `findItem`, it becomes the SOLE narrowing authority over the
 * id+variation candidates — it REPLACES the generic narrowing (per-namespace
 * compare + presence heuristic), it does not compose with it. This is what lets a
 * caller (e.g. a bundle editor) pair with a line the presence heuristic would
 * otherwise exclude. It is a caller-supplied function only — there is no
 * serialized context-reference machinery.
 */
export type CartItemFilterPredicate = (
	cartItem: CartItem,
	extra: {
		draft?: DraftItem;
	}
) => boolean;

/**
 * Payload-root keys that are part of the add-item envelope itself, NOT
 * namespaced extension request params. Everything on a draft that is not one of
 * these is treated as an extension prop and matched against `extensions[ns]`.
 *
 * `draftKey` is a store-internal routing field (an optional imperative override
 * on `upsertDraftItem`), never an extension prop and never POSTed — so it is
 * reserved to keep it out of the extension-prop projection and off the add-item
 * body.
 *
 * A draft is a payload, not a cart line: line-only fields like `key`/`type` are
 * deliberately NOT reserved, so seeding a draft from a cart line surfaces them as
 * (bogus) extension props and fails loudly rather than silently.
 */
const RESERVED_DRAFT_KEYS = new Set( [
	'id',
	'quantity',
	'variation',
	'draftKey',
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
 * Two values are equal when they serialize to the same normalized structure —
 * recursively equal primitives, same-length arrays with equal elements in order,
 * and plain objects with the same set of keys and equal values. This deliberately
 * does NOT treat `[]` and `{}` as equal to each other; emptiness normalization
 * for the "absent/empty" case is handled separately by `isEmptyValue`.
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
 * Whether a value counts as "empty" for absent/empty normalization. Emptiness is
 * DEEP: `undefined`, `null`, `''`, `[]`, `{}`, AND any array/object whose members
 * are ALL recursively empty (e.g. `{ ns: { field: '' } }`, `[ {} ]`).
 *
 * The deep rule is what keeps pairing forgiving when the two sides of a comparison
 * disagree only on presence-of-shape, not on data. A draft that touched an
 * extension field and then cleared it holds `{ ns: { field: '' } }`, while a line
 * that never carried that field exposes nothing (`{}` or absent) — both must read
 * as "no content under this namespace" so the surface still pairs with its line.
 * A shallow check would treat `{ ns: { field: '' } }` as non-empty and break the
 * match store-wide (the gift-note demo's touched-then-cleared draft is the
 * canonical case).
 *
 * @param value The value to test.
 * @return True when the value is considered (recursively) empty.
 */
export function isEmptyValue( value: unknown ): boolean {
	if ( value === undefined || value === null || value === '' ) {
		return true;
	}
	if ( Array.isArray( value ) ) {
		return value.every( ( item ) => isEmptyValue( item ) );
	}
	if ( typeof value === 'object' ) {
		return Object.values( value as Record< string, unknown > ).every(
			( item ) => isEmptyValue( item )
		);
	}
	return false;
}

/**
 * Per-namespace deep-compare of a draft's namespaced props against a line's
 * `extensions[ns]`, with absent/empty normalization (generic narrowing).
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
 * Presence heuristic: a line carrying visible machine-readable content the draft
 * does not account for must be excluded from exact pairing. There are two ways a
 * line can carry unaccounted content:
 *
 * 1. **Unaccounted extension namespace** — the line exposes a non-empty
 *    `extensions[ns]` under a namespace the draft has NO (non-empty) prop for.
 *    The draft cannot vouch for that identity-affecting data. This ALWAYS
 *    excludes.
 *
 * 2. **Unaccounted display data** — the line has visible (non-hidden)
 *    `item_data`. `item_data` is display-only and NEVER machine-compared, so we
 *    cannot map it to a namespace. It signals unaccounted content
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
 *   zero survivors → `cart` undefined: the plain surface is not paired with a
 *   decorated line it cannot account for, and falls back to a plain add button.
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
