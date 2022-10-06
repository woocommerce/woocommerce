/**
 * External dependencies
 */
import { cloneElement } from '@wordpress/element';

/**
 * Remove the item with the selected index from an array of items.
 *
 * @param  items       The array to remove the item from.
 * @param  removeIndex Index to remove.
 * @return array
 */
export const removeItem = < T >( items: T[], removeIndex: number ) =>
	items.filter( ( _, index ) => index !== removeIndex );

/**
 * Replace the React Element with given index with specific props.
 *
 * @param  items        The initial array to operate on.
 * @param  replaceIndex Index to remove.
 * @return array
 */
export const replaceItem = < T extends Record< string, unknown > >(
	items: JSX.Element[],
	replaceIndex: number,
	newProps: T
) => {
	const newChildren = [ ...items ];
	newChildren.splice(
		replaceIndex,
		1,
		cloneElement( items[ replaceIndex ], newProps )
	);
	return newChildren;
};
