/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { PatternWithBlocks } from './use-patterns';

export const useSelectedPattern = ( patternSelector: string ) => {
	const [ selectedPattern, setSelectedPattern ] =
		useState< PatternWithBlocks >();

	useEffect( () => {
		// This is a hack to add the "is-selected" class to the selected pattern
		const patternElements = document.querySelectorAll( patternSelector );

		patternElements.forEach( ( patternElement ) => {
			if (
				patternElement.getAttribute( 'aria-label' ) ===
				selectedPattern?.title
			) {
				patternElement.classList.add( 'is-selected' );
			} else {
				patternElement.classList.remove( 'is-selected' );
			}
		} );
	}, [ selectedPattern, patternSelector ] );

	return {
		selectedPattern,
		setSelectedPattern,
	};
};
