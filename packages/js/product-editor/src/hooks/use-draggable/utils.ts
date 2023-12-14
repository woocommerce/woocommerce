export function findDraggableIndex(
	draggableElements: HTMLElement[],
	element: HTMLElement
) {
	const index = draggableElements.findIndex(
		( child ) => child === element || child.contains( element )
	);
	return {
		draggable: index >= 0 ? draggableElements[ index ] : undefined,
		index,
	};
}

export function sort< T >(
	items: T[],
	currentIndex: number,
	newIndex: number
): T[] {
	const currentItem = items[ currentIndex ];
	const newItems = items.reduce< T[] >( ( current, item, index ) => {
		if ( index !== currentIndex ) {
			if ( index === newIndex ) {
				current.push( currentItem );
			}
			current.push( item );
		}
		return current;
	}, [] );

	if ( newIndex >= items.length ) {
		newItems.push( currentItem );
	}

	return newItems;
}
