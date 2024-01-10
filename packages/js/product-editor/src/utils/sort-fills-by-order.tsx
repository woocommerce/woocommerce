/**
 * External dependencies
 */
import { Fragment } from 'react';
import { Slot } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import { SlotComponentProps } from '@woocommerce/components/build-types/types';

/**
 * Sort fills by order for slot children.
 *
 * @param {Array} fills - slot's `Fill`s.
 * @return {Node} Node.
 */
export const sortFillsByOrder: SlotComponentProps[ 'children' ] = ( fills ) => {
	// Copy fills array here because its type is readonly array that doesn't have .sort method in Typescript definition.
	// @ts-expect-error - TODO: Not sure the fix here it seems children type is not iterable.
	const sortedFills = [ ...fills ].sort( ( a, b ) => {
		return a[ 0 ].props.order - b[ 0 ].props.order;
	} );

	return <Fragment>{ sortedFills }</Fragment>;
};
