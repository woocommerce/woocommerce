/**
 * External dependencies
 */
import { isValidElement } from 'react';
import { Slot, Fill } from '@wordpress/components';
import { cloneElement } from '@wordpress/element';

/**
 * Ordered fill item.
 *
 * @param {Node}   children - Node children.
 * @param {number} order    - Node order.
 * @param {Array}  props    - Fill props.
 * @return {Node} Node.
 */
export const createOrderedChildren = (
	children: React.ReactNode,
	order: number,
	props: Fill.Props
) => {
	if ( typeof children === 'function' ) {
		return cloneElement( children( props ), { order } );
	} else if ( isValidElement( children ) ) {
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore
		return cloneElement( children, { ...props, order } );
	}
	throw Error( 'Invalid children type' );
};

/**
 * Sort fills by order for slot children.
 *
 * @param {Array} fills - slot's `Fill`s.
 * @return {Node} Node.
 */
export const sortFillsByOrder: Slot.Props[ 'children' ] = ( fills ) => {
	// Copy fills array here because its type is readonly array that doesn't have .sort method in Typescript definition.
	const sortedFills = [ ...fills ].sort( ( a, b ) => {
		return a[ 0 ].props.order - b[ 0 ].props.order;
	} );
	return <>{ sortedFills }</>;
};
