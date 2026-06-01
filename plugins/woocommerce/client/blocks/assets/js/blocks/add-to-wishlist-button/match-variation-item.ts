/**
 * Narrowed `RawShopperListItem` shape so this helper stays pure and unit-testable.
 */
type MatchableItem = {
	id: number;
	variation?: Array< {
		attribute: string;
		value: string;
	} > | null;
};

/**
 * The shopper's currently picked attributes. Same shape as the cart store's `SelectedAttributes`.
 */
type SelectedPair = {
	attribute: string;
	value: string;
};

/**
 * Whether a wishlist item matches the picked variation and attributes. An `id` match alone is not
 * enough for "any" attribute slots, where several combinations share one variation product, so the
 * attribute sets are compared too. The value comparison is case-insensitive because the Store API
 * returns the term display name ("Red") while ATCWO carries the slug ("red").
 *
 * @param item     Wishlist item from the shopper-lists store.
 * @param id       Effective product/variation id to compare against.
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
