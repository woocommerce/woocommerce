/**
 * External dependencies
 */
import { Fragment } from 'react';
import { Slot } from '@wordpress/components';
import { createElement } from '@wordpress/element';

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
