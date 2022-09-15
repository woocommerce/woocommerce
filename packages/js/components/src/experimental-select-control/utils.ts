/**
 * Internal dependencies
 */
import { getItemLabelType, DefaultItemType } from './types';

function isDefaultItemType< ItemType >(
	item: ItemType | DefaultItemType | null
): item is DefaultItemType {
	return (
		Boolean( item ) &&
		( item as DefaultItemType ).label !== undefined &&
		( item as DefaultItemType ).value !== undefined
	);
}

export const defaultGetItemLabel = < ItemType >( item: ItemType | null ) => {
	if ( isDefaultItemType< ItemType >( item ) ) {
		return item.label;
	}
	return '';
};

export const defaultGetItemValue = < ItemType >( item: ItemType | null ) => {
	if ( isDefaultItemType< ItemType >( item ) ) {
		return item.value;
	}
	return '';
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
