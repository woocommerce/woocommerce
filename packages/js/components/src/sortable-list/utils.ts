/**
 * External dependencies
 */
import { arrayMove } from '@dnd-kit/sortable';
import type { UniqueIdentifier } from '@dnd-kit/core';

type MoveSortableItemProps< T > = {
	items: T[];
	activeId: UniqueIdentifier;
	overId: UniqueIdentifier;
	getItemId: ( item: T ) => UniqueIdentifier;
	getItemDisabled?: ( item: T ) => boolean;
};

export const moveSortableItem = < T >( {
	items,
	activeId,
	overId,
	getItemId,
	getItemDisabled = () => false,
}: MoveSortableItemProps< T > ): T[] => {
	if ( activeId === overId ) {
		return items;
	}

	const activeItem = items.find( ( item ) => getItemId( item ) === activeId );
	const overItem = items.find( ( item ) => getItemId( item ) === overId );

	if (
		! activeItem ||
		! overItem ||
		getItemDisabled( activeItem ) ||
		getItemDisabled( overItem )
	) {
		return items;
	}

	const sortableItems = items.filter( ( item ) => ! getItemDisabled( item ) );
	const oldIndex = sortableItems.findIndex(
		( item ) => getItemId( item ) === activeId
	);
	const newIndex = sortableItems.findIndex(
		( item ) => getItemId( item ) === overId
	);

	if ( oldIndex === -1 || newIndex === -1 ) {
		return items;
	}

	const movedSortableItems = arrayMove( sortableItems, oldIndex, newIndex );
	let sortableItemIndex = 0;

	return items.map( ( item ) => {
		if ( getItemDisabled( item ) ) {
			return item;
		}

		const nextItem = movedSortableItems[ sortableItemIndex ];
		sortableItemIndex += 1;
		return nextItem;
	} );
};
