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
 * Check whether the mouse is over the first half of the event target.
 *
 * @param  event        Drag event.
 * @param  isHorizontal Check horizontally or vertically.
 * @return boolean
 */
export const isBefore = (
	event: DragEvent< HTMLLIElement >,
	isHorizontal = false
) => {
	const target = event.target as HTMLElement;

	if ( isHorizontal ) {
		const middle = target.offsetWidth / 2;
		const rect = target.getBoundingClientRect();
		const relativeX = event.clientX - rect.left;
		return relativeX < middle;
	}

	const middle = target.offsetHeight / 2;
	const rect = target.getBoundingClientRect();
	const relativeY = event.clientY - rect.top;
	return relativeY < middle;
};

export const isDraggingOverBefore = (
	index: number,
	dragIndex: number | null,
	dropIndex: number | null
) => {
	if ( index === dragIndex ) {
		return false;
	}

	if ( dropIndex === index ) {
		return true;
	}

	if ( dragIndex === index - 1 && index - 1 === dropIndex ) {
		return true;
	}

	return false;
};

export const isDraggingOverAfter = (
	index: number,
	dragIndex: number | null,
	dropIndex: number | null
) => {
	if ( index === dragIndex ) {
		return false;
	}

	if ( dropIndex === index + 1 ) {
		return true;
	}

	if ( dragIndex === index + 1 && index + 2 === dropIndex ) {
		return true;
	}

	return false;
};

export const isLastDroppable = (
	index: number,
	dragIndex: number | null,
	itemCount: number
) => {
	if ( dragIndex === index ) {
		return false;
	}

	if ( index === itemCount - 1 ) {
		return true;
	}

	if ( dragIndex === itemCount - 1 && index === itemCount - 2 ) {
		return true;
	}

	return false;
};
