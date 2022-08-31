export const getItemLabel = < ItemType >( item: ItemType | null ) => {
	return item ? item.label : '';
};

export const getItemValue = < ItemType >( item: ItemType | null ) => {
	return item ? item.value : '';
};

export const getFilteredItems = < ItemType >(
	allItems: ItemType[],
	inputValue: string,
	selectedItems: ItemType[]
) => {
	return allItems.filter(
		( item ) =>
			selectedItems.indexOf( item ) < 0 &&
			getItemLabel( item )
				.toLowerCase()
				.startsWith( inputValue.toLowerCase() )
	);
};
