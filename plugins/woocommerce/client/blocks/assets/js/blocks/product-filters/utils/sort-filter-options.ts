/**
 * Internal dependencies
 */
import type { FilterOptionItem } from '../types';

/**
 * Sorts filter options based on the specified sort order
 *
 * @param options   - Array of filter option items to sort
 * @param sortOrder - Sort order (name-asc, name-desc, count-asc, count-desc)
 * @return Sorted array of filter option items
 */
export function sortFilterOptions(
	options: FilterOptionItem[],
	sortOrder: string
): FilterOptionItem[] {
	return options.sort( ( a, b ) => {
		switch ( sortOrder ) {
			case 'name-asc':
				return a.label.localeCompare( b.label );
			case 'name-desc':
				return b.label.localeCompare( a.label );
			case 'count-asc':
				return a.count - b.count;
			default: // count-desc
				return b.count - a.count;
		}
	} );
}
