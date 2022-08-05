/**
 * External dependencies
 */
import { DragEvent } from 'react';

export const moveIndex = (
	fromIndex: number,
	toIndex: number,
	arr: unknown[]
) => {
	const newArr = [ ...arr ];
	const item = arr[ fromIndex ];
	newArr.splice( fromIndex, 1 );

	// Splicing the array reduces the array size by 1 after removal.
	// Lower index items affect the position of where the item should be inserted.
	newArr.splice( fromIndex < toIndex ? toIndex - 1 : toIndex, 0, item );
	return newArr;
};

/**
 * Check whether the mouse is over the lower or upper half of the event target.
 *
 * @param  event Drag event.
 * @return boolean
 */
export const isUpperHalf = ( event: DragEvent< HTMLLIElement > ) => {
	const target = event.target as HTMLElement;
	const middle = target.offsetHeight / 2;
	const rect = target.getBoundingClientRect();
	const relativeY = event.clientY - rect.top;
	return relativeY < middle;
};
