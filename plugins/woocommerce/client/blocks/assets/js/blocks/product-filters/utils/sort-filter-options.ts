/**
 * Internal dependencies
 */
import type { FilterOptionItem } from '../types';

/**
 * Gets the sortable text from a filter option item.
 * For string labels, returns the label directly.
 * For ReactNode labels, returns the ariaLabel.
 *
 * @param item - Filter option item
 * @return Sortable text string
 */
function getSortableText( item: FilterOptionItem ): string {
	if ( typeof item.label === 'string' ) {
		return item.label;
	}
	// When label is ReactNode, ariaLabel is guaranteed to exist by our type definition
	return ( item as { ariaLabel: string } ).ariaLabel;
}

/**
 * Sorts filter options based on the specified sort order
 *
 * @param options   - Array of filter option items to sort
 * @param sortOrder - Sort order (name-asc, name-desc, count-asc, count-desc, menu_order-asc)
 * @return Sorted array of filter option items
 */
export function sortFilterOptions(
	options: FilterOptionItem[],
	sortOrder: string
): FilterOptionItem[] {
	return options.sort( ( a, b ) => {
		switch ( sortOrder ) {
			case 'menu_order-asc': {
				// Sort by menu order, with secondary sort by name when equal.
				const orderA = a.menuOrder ?? 0;
				const orderB = b.menuOrder ?? 0;
				if ( orderA !== orderB ) {
					return orderA - orderB;
				}
				return getSortableText( a ).localeCompare(
					getSortableText( b )
				);
			}
			case 'name-asc':
				return getSortableText( a ).localeCompare(
					getSortableText( b )
				);
			case 'name-desc':
				return getSortableText( b ).localeCompare(
					getSortableText( a )
				);
			case 'count-asc':
				return a.count - b.count;
			default: // count-desc
				return b.count - a.count;
		}
	} );
}
