/**
 * Internal dependencies
 */
import { ItemType } from './types';

export const itemToString = ( item: ItemType | null ) => {
	return item ? item.value : '';
};

export const getFilteredItems = (
	allItems: ItemType[],
	selectedItems: ItemType[],
	inputValue: string
) => {
	return allItems.filter(
		( item ) =>
			selectedItems.indexOf( item ) < 0 &&
			item.value.toLowerCase().startsWith( inputValue.toLowerCase() )
	);
};
