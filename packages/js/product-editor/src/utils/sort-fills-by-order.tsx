/**
 * External dependencies
 */
import { Fragment } from 'react';
import { createElement } from '@wordpress/element';

type Fills = Array< Array< React.ReactElement > >;

export const sortFillsByOrder = ( fills: Fills ): React.ReactElement => {
	// Copy fills array here because its type is readonly array that doesn't have .sort method in Typescript definition.
	const sortedFills = [ ...fills ].sort( ( a, b ) => {
		return a[ 0 ].props.order - b[ 0 ].props.order;
	} );

	return <Fragment>{ sortedFills }</Fragment>;
};
