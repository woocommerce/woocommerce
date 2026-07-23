/**
 * Pure, side-effect-free helpers backing the Mini-Cart product table's
 * per-entry `item_data`/`variation` rendering.
 *
 * This module must stay free of top-level side effects and must not import
 * `@wordpress/interactivity` so it can be unit-tested with zero mocking.
 * `frontend.ts`'s store getters resolve the Interactivity context and
 * delegate the actual decision-making to these functions.
 */

/**
 * Raw `item_data` or `variation` entry as returned by the Store API.
 *
 * An entry is malformed when it has no usable name and no usable value; see
 * `isItemDataEntryVisible`.
 */
export type ItemData = {
	/** Raw (non-display) attribute name, used by variation entries. */
	raw_attribute?: string | undefined;
	/** Raw (non-display) value. */
	value?: string | undefined;
	/** Display-ready value; preferred over `value` when present. */
	display?: string;
	/** Variation attribute name; preferred over `name` when present. */
	attribute?: string;
	/** Truthy under `true`, `'true'`, `'1'`, or `1` marks the entry hidden. */
	hidden?: boolean | string | number;
} & (
	| {
			/** Item-data entry key. Mutually exclusive with `name`. */
			key: string;
			name?: never;
	  }
	| {
			key?: never;
			/** Variation attribute name. Mutually exclusive with `key`. */
			name: string;
	  }
);

/**
 * Normalized, entity-decoded representation of an `ItemData` entry, ready
 * for rendering. Always fully populated, so markup bindings never need to
 * guard against `null`. See `buildCartItemDataAttr`.
 */
export type CartItemDataAttr = {
	/** Entity-decoded entry value; empty string when the entry has no usable value. */
	value: string;
	/** Entity-decoded entry name, suffixed with `:`; empty string when the entry has no usable name. */
	name: string;
	/** BEM-style modifier class derived from `name`, e.g. `wc-block-components-product-details__color`. */
	className: string;
};

/**
 * Extracts the raw (pre-entity-decoding) name or value of an item-data or
 * variation entry, exactly as the Store API returns it.
 *
 * @param {ItemData | undefined} entry Entry to read from, or `undefined`
 *                                     when no entry is available.
 * @param {'name'|'value'}       field Which raw field to extract.
 * @return {string} The raw field value, or an empty string when the entry
 *                   is `undefined` or the field has no usable value.
 */
export function getEntryFieldRaw(
	entry: ItemData | undefined,
	field: 'name' | 'value'
): string {
	if ( ! entry ) {
		return '';
	}

	if ( field === 'name' ) {
		return entry.key || entry.attribute || entry.name || '';
	}

	return entry.display || entry.value || '';
}

/**
 * Determines whether an entry's `hidden` field marks it as explicitly
 * hidden.
 *
 * @param {ItemData | undefined} entry Entry to check, or `undefined`.
 * @return {boolean} True when `hidden` is truthy under any of `true`,
 *                    `'true'`, `'1'`, or `1`.
 */
export function isEntryHiddenFlag( entry: ItemData | undefined ): boolean {
	const hiddenValue = entry?.hidden;

	return (
		hiddenValue === true ||
		hiddenValue === 'true' ||
		hiddenValue === '1' ||
		hiddenValue === 1
	);
}

/**
 * Single source of truth for whether an item-data or variation entry
 * contributes anything visible to the Mini-Cart. Every visibility-dependent
 * behavior (content-hiding, " / " separator placement) derives from this one
 * predicate, so they can never disagree.
 *
 * @param {ItemData | undefined} entry Entry to check, or `undefined`.
 * @return {boolean} True when the entry has a usable name or value and is
 *                    not hidden-flagged.
 */
export function isItemDataEntryVisible( entry: ItemData | undefined ): boolean {
	const hasUsableName = !! getEntryFieldRaw( entry, 'name' );
	const hasUsableValue = !! getEntryFieldRaw( entry, 'value' );

	return ( hasUsableName || hasUsableValue ) && ! isEntryHiddenFlag( entry );
}

/**
 * Determines whether `entry` is the last visible entry of `items`, i.e. the
 * one whose trailing " / " separator must be suppressed.
 *
 * Uses referential identity (`===`) against the visible subset of `items`,
 * matching the wp-each iteration's per-entry context object.
 *
 * @param {ItemData[] | undefined} items List of entries the Mini-Cart is
 *                                       rendering (`item_data` or
 *                                       `variation`), or `undefined`.
 * @param {ItemData | undefined}   entry Entry to test.
 * @return {boolean} True when there are no items, no visible items, or
 *                    `entry` is the last visible one.
 */
export function isLastVisibleEntry(
	items: ItemData[] | undefined,
	entry: ItemData | undefined
): boolean {
	if ( ! items || items.length === 0 ) {
		return true;
	}

	const visibleItems = items.filter( isItemDataEntryVisible );

	if ( visibleItems.length === 0 ) {
		return true;
	}

	return entry === visibleItems[ visibleItems.length - 1 ];
}

/**
 * Builds the normalized, entity-decoded `CartItemDataAttr` for an entry.
 * Always returns a fully-populated object — never `null` or `undefined` —
 * so markup bindings can dereference its properties unconditionally.
 *
 * @param {ItemData | undefined} entry Entry to build from, or `undefined`.
 * @return {CartItemDataAttr} The normalized entry. A malformed or empty
 *                            entry produces empty `name`/`value`,
 *                            and `className: 'wc-block-components-product-details__'`.
 */
export function buildCartItemDataAttr(
	entry: ItemData | undefined
): CartItemDataAttr {
	const nameTxt = document.createElement( 'textarea' );
	nameTxt.innerHTML = getEntryFieldRaw( entry, 'name' );

	const valueTxt = document.createElement( 'textarea' );
	valueTxt.innerHTML = getEntryFieldRaw( entry, 'value' );

	return {
		name: nameTxt.value ? nameTxt.value + ':' : '',
		value: valueTxt.value,
		className: `wc-block-components-product-details__${ nameTxt.value
			.replace( /([a-z])([A-Z])/g, '$1-$2' )
			.replace( /<[^>]*>/g, '' )
			.replace( /[\s_&]+/g, '-' )
			.toLowerCase() }`,
	};
}
