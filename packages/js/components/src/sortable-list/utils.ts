/**
 * External dependencies
 */
import { DragEvent } from 'react';

/**
 * Move an item from an index in an array to a new index.s
 *
 * @param  fromIndex Index to move the item from.
 * @param  toIndex   Index to move the item to.
 * @param  arr       The array to copy.
 * @return array
 */
export const moveIndex = < T >(
	fromIndex: number,
	toIndex: number,
	arr: T[]
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
