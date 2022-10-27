/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { store as WP_BLOCKS_STORE } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { QUERY_LOOP_ID } from './constants';
import {
	ProductQueryArguments,
	ProductQueryBlock,
	QueryVariation,
} from './types';

/**
 * Creates an array that is the symmetric difference of the given arrays
 */
export function ArrayXOR< T extends Array< unknown > >( a: T, b: T ) {
	return a.filter( ( el ) => ! b.includes( el ) );
}

/**
 * Identifies if a block is a Query block variation from our conventions
 *
 * We are extending Gutenberg's core Query block with our variations, and
 * also adding extra namespaced attributes. If those namespaced attributes
 * are present, we can be fairly sure it is our own registered variation.
 */
export function isWooQueryBlockVariation( block: ProductQueryBlock ) {
	return (
		block.name === QUERY_LOOP_ID &&
		Object.values( QueryVariation ).includes(
			block.attributes.namespace as QueryVariation
		)
	);
}

/**
 * Sets the new query arguments of a Product Query block
 *
 * Because we add a new set of deeply nested attributes to the query
 * block, this utility function makes it easier to change just the
 * options relating to our custom query, while keeping the code
 * clean.
 */
export function setCustomQueryAttribute(
	block: ProductQueryBlock,
	queryParams: Partial< ProductQueryArguments >
) {
	const { query } = block.attributes;

	block.setAttributes( {
		query: {
			...query,
			...queryParams,
		},
	} );
}

/**
 * Hook that returns the query properties' names defined by the active
 * block variation, to determine which block inspector controls to show.
 *
 * @param {Object} attributes Block attributes.
 * @return {string[]} An array of the controls keys.
 */
export function useAllowedControls(
	attributes: ProductQueryBlock[ 'attributes' ]
) {
	return useSelect(
		( select ) =>
			select( WP_BLOCKS_STORE ).getActiveBlockVariation(
				QUERY_LOOP_ID,
				attributes
			)?.allowedControls,

		[ attributes ]
	);
}
