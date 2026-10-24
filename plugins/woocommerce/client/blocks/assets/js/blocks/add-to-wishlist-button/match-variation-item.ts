/**
 * Items in the wishlist whose variation values we can compare against the
 * shopper's currently picked attributes. Narrowed from `RawShopperListItem`
 * so this helper stays pure (no iAPI deps) and unit-testable in isolation.
 */
type MatchableItem = {
	id: number;
	variation?: Array< {
		attribute: string;
		value: string;
	} > | null;
};

/**
 * The shopper's currently picked attributes — same shape as ATCWO's
 * `selectedAttributes` context entries (also re-exported from the cart store
 * as `SelectedAttributes`).
 */
type SelectedPair = {
	attribute: string;
	value: string;
};

/**
 * Decide whether a wishlist item represents the exact variation + attribute
 * combination the shopper has currently picked. For fully-constrained
 * variations a single `id` match is enough, but for "any" attribute slots
 * several attribute combinations can share the same variation product (and
 * therefore the same `item.id`), so we additionally compare the attribute
 * sets. The asymmetric `value` comparison is case-insensitive because the
 * Store API returns each entry's `value` as the term display name ("Red")
 * while ATCWO carries the term slug ("red") in `selectedAttributes`.
 *
 * Edge cases out of scope here (would need a slug→name lookup via the parent
 * product's `terms`): slugs whose shape differs from the display name beyond
 * capitalization, e.g. `"bright-red"` vs `"Bright Red"`.
 *
 * @param item     Wishlist item from the shopper-lists store.
 * @param id       Effective product/variation id we're comparing against.
 * @param selected Shopper's picked attribute/value pairs.
 */
export function matchVariationItem(
	item: MatchableItem,
	id: number,
	selected: SelectedPair[]
): boolean {
	if ( item.id !== id ) {
		return false;
	}
	const stored = item.variation ?? [];
	if ( stored.length !== selected.length ) {
		return false;
	}
	return selected.every( ( sel ) =>
		stored.some(
			( v ) =>
				v.attribute === sel.attribute &&
				v.value.toLowerCase() === sel.value.toLowerCase()
		)
	);
}
