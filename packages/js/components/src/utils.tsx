/**
 * External dependencies
 */
import { isValidElement, Fragment, ReactNode, ReactElement } from 'react';
import { Slot, Fill } from '@wordpress/components';
import { cloneElement, createElement } from '@wordpress/element';

/**
 * Ordered fill item.
 *
 * @param {Node}   children - Node children.
 * @param {number} order    - Node order.
 * @param {Array}  props    - Fill props.
 * @return {Node} Node.
 */
function createOrderedChildren< T = Fill.Props, S = Record< string, unknown > >(
	children: ReactNode,
	order: number,
	props: T,
	injectProps?: S
): ReactElement {
	if ( typeof children === 'function' ) {
		return cloneElement( children( props ), { order, ...injectProps } );
	} else if ( isValidElement( children ) ) {
		return cloneElement( children, { ...props, order, ...injectProps } );
	}
	throw Error( 'Invalid children type' );
}
export { createOrderedChildren };

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

	return <Fragment>{ sortedFills }</Fragment>;
};
