/**
 * Internal dependencies
 */
import { ItemType, SelectedType } from './types';

export const itemToString = ( item: ItemType | null ) => {
	return item ? item.label : '';
};

export const getFilteredItems = (
	allItems: ItemType[],
	inputValue: string,
	selectedItems: ItemType[]
) => {
	return allItems.filter(
		( item ) =>
			selectedItems.indexOf( item ) < 0 &&
			item.label.toLowerCase().startsWith( inputValue.toLowerCase() )
	);
};
