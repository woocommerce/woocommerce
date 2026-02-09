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
	const escapedInputValue = inputValue.replace(
		/[.*+?^${}()|[\]\\]/g,
		'\\$&'
	);
	const re = new RegExp( escapedInputValue, 'gi' );

	return allItems.filter( ( item ) => {
		return (
			selectedItems.indexOf( item ) < 0 &&
			re.test( getItemLabel( item ).toLowerCase() )
		);
	} );
};
