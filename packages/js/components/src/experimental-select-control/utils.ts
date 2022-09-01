/**
 * Internal dependencies
 */
import { getItemLabelType } from './types';

export const defaultGetItemLabel = < ItemType >( item: ItemType | null ) => {
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore this default handler should only be used with the default item type
	return item ? item.label : '';
};

export const defaultGetItemValue = < ItemType >( item: ItemType | null ) => {
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore this default handler should only be used with the default item type
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
