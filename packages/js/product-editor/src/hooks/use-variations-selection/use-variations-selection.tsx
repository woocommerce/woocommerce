/**
 * External dependencies
 */
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { Selection } from './types';

export function useVariationsSelection(): [
	Selection,
	( selection: Selection ) => void
] {
	const [ selectedVariations, setSelectedVariations ] = useState< Selection >(
		{}
	);

	function onSelect( selection: Selection ) {
		setSelectedVariations( ( currentSelection ) => ( {
			...currentSelection,
			...selection,
		} ) );
	}

	return [ selectedVariations, onSelect ];
}
