/**
 * Internal dependencies
 */
import { DefaultItem, Selected } from '../types';

export function isSelected< Item = DefaultItem >(
	item: Item,
	selected: Selected< Item >
) {
	if ( Array.isArray( selected ) ) {
		return selected.includes( item );
	}

	return item === selected;
}
