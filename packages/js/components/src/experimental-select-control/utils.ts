import { getItemLabelType } from './types';
export const defaultGetItemLabel = < ItemType >( item: ItemType | null ) => {
	return item ? item.label : '';
};

export const defaultGetItemValue = < ItemType >( item: ItemType | null ) => {
	return item ? item.value : '';
};

export const defaultGetFilteredItems = < ItemType >(
	allItems: ItemType[],
	inputValue: string,
	selectedItems: ItemType[],
	getItemLabel: getItemLabelType< ItemType >
) => {
	return allItems.filter(
		( item ) =>
			selectedItems.indexOf( item ) < 0 &&
			getItemLabel( item )
				.toLowerCase()
				.startsWith( inputValue.toLowerCase() )
	);
};
