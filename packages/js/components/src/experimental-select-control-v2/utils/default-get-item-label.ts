/**
 * Internal dependencies
 */
import { DefaultItem } from '../types';

function isDefaultItemType< ItemType >(
	item: ItemType | DefaultItem | null
): item is DefaultItem {
	return (
		Boolean( item ) &&
		( item as DefaultItem ).label !== undefined &&
		( item as DefaultItem ).value !== undefined
	);
}

export const defaultGetItemLabel = < ItemType >( item: ItemType | null ) => {
	if ( isDefaultItemType< ItemType >( item ) ) {
		return item.label;
	}
	return '';
};
